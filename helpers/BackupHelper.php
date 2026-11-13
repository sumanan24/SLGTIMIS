<?php
/**
 * Database and file backup utilities for admin downloads.
 */

class BackupHelper {
    /**
     * Prepare response headers so the browser saves the file to the user's PC (Downloads folder).
     */
    public static function beginBrowserDownload(string $filename, string $mimeType, ?int $contentLength = null): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $filename) ?: 'backup';

        header('Content-Type: ' . $mimeType);
        header('Content-Transfer-Encoding: Binary');
        header('Content-Disposition: attachment; filename="' . $safeName . '"');
        header('Cache-Control: private, no-store, must-revalidate');
        header('Pragma: public');
        header('Expires: 0');
        header('X-Content-Type-Options: nosniff');

        if ($contentLength !== null && $contentLength > 0) {
            header('Content-Length: ' . (string) $contentLength);
        }
    }

    /**
     * Statement placed at the top of every dump so it also imports on MySQL 8.4+.
     *
     * MySQL 8.4 turned on restrict_fk_on_non_standard_key, which rejects a foreign key that points at a
     * column that is not a full PRIMARY/UNIQUE key (e.g. assessments_type.module_id -> module.module_id,
     * while module's key is (module_id, course_id)). Older servers accept those keys, so the dump can
     * contain them. Without this line the import stops with error 6125 and `module` is never created.
     *
     * The /*!80400 comment only runs on servers >= 8.4, and the change lasts for the import session only.
     */
    private const IMPORT_COMPAT_PRELUDE =
        "-- Import compatibility: allow foreign keys on non-unique columns (MySQL 8.4+ rejects them by default).\n"
        . "/*!80400 SET @OLD_RESTRICT_FK_ON_NON_STANDARD_KEY=@@SESSION.restrict_fk_on_non_standard_key, SESSION restrict_fk_on_non_standard_key=0 */;\n";

    /**
     * Build a full SQL dump (tables, data, views, triggers, routines, events).
     */
    public static function generateSqlDump(mysqli $conn, string $dbName): string {
        $tables = [];
        $views = [];

        $result = $conn->query('SHOW FULL TABLES');
        if ($result) {
            while ($row = $result->fetch_array()) {
                $name = $row[0];
                $type = $row[1] ?? 'BASE TABLE';
                if (strcasecmp($type, 'VIEW') === 0) {
                    $views[] = $name;
                } else {
                    $tables[] = $name;
                }
            }
        }

        $sql = "-- SLGTI MIS Database Backup\n";
        $sql .= '-- Generated: ' . date('Y-m-d H:i:s') . "\n";
        $sql .= '-- Database: ' . $dbName . "\n";
        $sql .= '-- MySQL Version: ' . $conn->server_info . "\n\n";
        $sql .= "SET NAMES utf8mb4;\n";
        $sql .= self::IMPORT_COMPAT_PRELUDE;
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql .= "SET time_zone = \"+00:00\";\n\n";

        foreach ($tables as $table) {
            $sql .= self::exportTable($conn, $table);
        }

        if ($views !== []) {
            $sql .= "\n\n-- VIEWS --\n";
            foreach ($views as $view) {
                $create = $conn->query("SHOW CREATE VIEW `$view`");
                if ($create && ($row = $create->fetch_row())) {
                    $sql .= "\nDROP VIEW IF EXISTS `$view`;\n";
                    $sql .= $row[1] . ";\n\n";
                }
            }
        }

        $sql .= self::exportTriggers($conn, $dbName);
        $sql .= self::exportRoutines($conn, $dbName);
        $sql .= self::exportEvents($conn, $dbName);
        $sql .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

        return $sql;
    }

    /**
     * Try mysqldump when available (includes triggers, routines, events).
     */
    public static function tryMysqldump(string $dbName): ?string {
        $mysqldump = self::findMysqldumpBinary();
        if ($mysqldump === null) {
            return null;
        }

        $cnfPath = self::writeMysqlClientDefaultsFile();
        if ($cnfPath === null) {
            return null;
        }

        try {
            $cmd = escapeshellarg($mysqldump)
                . ' --defaults-extra-file=' . escapeshellarg($cnfPath)
                . ' --routines --triggers --events --single-transaction --hex-blob'
                . ' --default-character-set=utf8mb4 --set-gtid-purged=OFF --column-statistics=0'
                . ' ' . escapeshellarg($dbName);

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $proc = @proc_open($cmd, $descriptors, $pipes);
            if (!is_resource($proc)) {
                return null;
            }

            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($proc);

            if ($exitCode !== 0 || !is_string($stdout) || trim($stdout) === '') {
                error_log('BackupHelper::tryMysqldump failed (exit ' . $exitCode . '): ' . trim((string) $stderr));
                return null;
            }

            $sql = self::sanitizeSqlForImport($stdout);
            if (stripos($sql, 'CREATE TABLE') === false && stripos($sql, 'CREATE DATABASE') === false) {
                return null;
            }

            return $sql;
        } finally {
            @unlink($cnfPath);
        }
    }

    /**
     * Memory-safe backup: run mysqldump straight into a temp file, then copy it line by line
     * (applying the same import fixes as sanitizeSqlForImport) into a second temp file.
     * A full database can be tens of MB, so it is never held in PHP memory as one string.
     *
     * @return string|null Path of the finished .sql temp file (caller must unlink it), or null when
     *                     mysqldump is unavailable or fails (use generateSqlDump() as a fallback).
     */
    public static function tryMysqldumpToFile(string $dbName): ?string {
        $mysqldump = self::findMysqldumpBinary();
        if ($mysqldump === null) {
            return null;
        }

        $cnfPath = self::writeMysqlClientDefaultsFile();
        if ($cnfPath === null) {
            return null;
        }

        $rawPath = tempnam(sys_get_temp_dir(), 'slgti_dump_raw_');
        $cleanPath = tempnam(sys_get_temp_dir(), 'slgti_dump_');
        if ($rawPath === false || $cleanPath === false) {
            @unlink($cnfPath);
            return null;
        }

        try {
            $cmd = escapeshellarg($mysqldump)
                . ' --defaults-extra-file=' . escapeshellarg($cnfPath)
                . ' --routines --triggers --events --single-transaction --hex-blob'
                . ' --default-character-set=utf8mb4 --set-gtid-purged=OFF --column-statistics=0'
                . ' --result-file=' . escapeshellarg($rawPath)
                . ' ' . escapeshellarg($dbName);

            $proc = @proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            if (!is_resource($proc)) {
                @unlink($cleanPath);
                return null;
            }
            fclose($pipes[0]);
            stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($proc);

            if ($exitCode !== 0 || !is_file($rawPath) || filesize($rawPath) === 0) {
                error_log('BackupHelper::tryMysqldumpToFile failed (exit ' . $exitCode . '): ' . trim((string) $stderr));
                @unlink($cleanPath);
                return null;
            }

            if (!self::sanitizeDumpFile($rawPath, $cleanPath)) {
                @unlink($cleanPath);
                return null;
            }

            return $cleanPath;
        } finally {
            @unlink($cnfPath);
            @unlink($rawPath);
        }
    }

    /**
     * Line-by-line version of sanitizeSqlForImport() for large dump files.
     *
     * @return bool true when the output file looks like a real dump (contains CREATE TABLE)
     */
    public static function sanitizeDumpFile(string $inPath, string $outPath): bool {
        $in = @fopen($inPath, 'rb');
        $out = @fopen($outPath, 'wb');
        if ($in === false || $out === false) {
            if (is_resource($in)) {
                fclose($in);
            }
            if (is_resource($out)) {
                fclose($out);
            }
            return false;
        }

        $started = false;          // skip any mysqldump warnings printed before the real header
        $preludeDone = false;
        $hasCreateTable = false;

        while (($line = fgets($in)) !== false) {
            if (!$started) {
                if (!preg_match('/^(--\s*MySQL dump|\/\*!40101 SET @OLD_CHARACTER_SET)/i', $line)) {
                    continue;
                }
                $started = true;
            }

            // GTID can break restore on another server when privileges differ
            if (preg_match('/^SET\s+@@GLOBAL\.GTID_PURGED\s*=/i', $line)) {
                continue;
            }

            // DEFINER often missing on a new PC / new MySQL user
            if (stripos($line, 'DEFINER') !== false) {
                $line = preg_replace('/CREATE\s+DEFINER\s*=\s*`[^`]+`@`[^`]+`\s+/i', 'CREATE ', $line);
            }

            if (!$hasCreateTable && strncmp($line, 'CREATE TABLE', 12) === 0) {
                $hasCreateTable = true;
            }

            fwrite($out, $line);

            // Let the dump import on MySQL 8.4+ (see IMPORT_COMPAT_PRELUDE).
            if (!$preludeDone && strpos($line, '/*!40014 SET @OLD_FOREIGN_KEY_CHECKS') === 0) {
                fwrite($out, self::IMPORT_COMPAT_PRELUDE);
                $preludeDone = true;
            }
        }

        fclose($in);
        fclose($out);

        return $started && $hasCreateTable;
    }

    /**
     * Send a finished file to the browser as a download, then delete it and stop the script.
     */
    public static function streamFileDownloadAndExit(string $path, string $downloadName, string $mimeType): void {
        $size = (int) filesize($path);
        @set_time_limit(0);
        self::beginBrowserDownload($downloadName, $mimeType, $size);

        $fh = fopen($path, 'rb');
        if ($fh !== false) {
            while (!feof($fh)) {
                echo fread($fh, 1048576);
                flush();
            }
            fclose($fh);
        }
        @unlink($path);
        exit;
    }

    /**
     * Remove mysqldump CLI warnings and other junk so SQL imports on another PC/phpMyAdmin.
     */
    public static function sanitizeSqlForImport(string $sql): string {
        // Warning glued to dump header: "mysqldump: [Warning] ... insecure.-- MySQL dump"
        $sql = preg_replace(
            '/^mysqldump:\s*(?:\[[^\]]+\]\s*)?(?:Warning:\s*)?.*?insecure\.(?=\s*--\s*MySQL dump|\s*\/\*!)/is',
            '',
            $sql
        );

        // Any other mysqldump stderr lines at the top
        $sql = preg_replace('/^(?:mysqldump:|Warning:).*?(?:\r?\n|$)/im', '', $sql);

        // Start at the real dump header
        if (preg_match('/(--\s*MySQL dump|\/\*!40101 SET @OLD_CHARACTER_SET)/i', $sql, $match, PREG_OFFSET_CAPTURE)) {
            $sql = substr($sql, $match[0][1]);
        }

        // GTID can break restore on another server when privileges differ
        $sql = preg_replace('/^SET\s+@@GLOBAL\.GTID_PURGED\s*=.*?;\s*$/im', '', $sql);

        // DEFINER often missing on a new PC / new MySQL user
        $sql = preg_replace(
            '/CREATE\s+DEFINER\s*=\s*`[^`]+`@`[^`]+`\s+/i',
            'CREATE ',
            $sql
        );

        // Let the dump import on MySQL 8.4+ (see IMPORT_COMPAT_PRELUDE); skip if already present.
        if (strpos($sql, 'restrict_fk_on_non_standard_key') === false) {
            $sql = preg_replace(
                '/^(\/\*!40014 SET @OLD_FOREIGN_KEY_CHECKS[^\r\n]*\r?\n)/m',
                '$1' . self::IMPORT_COMPAT_PRELUDE,
                $sql,
                1,
                $inserted
            );
            if (!$inserted) {
                $sql = self::IMPORT_COMPAT_PRELUDE . "\n" . $sql;
            }
        }

        return ltrim($sql);
    }

    /**
     * Zip one or more folders under BASE_PATH and stream to browser.
     *
     * @param list<string> $relativeDirs Paths relative to BASE_PATH (e.g. uploads, assets)
     */
    public static function streamFoldersZip(array $relativeDirs, string $downloadFilename): void {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('ZipArchive extension is not enabled on this server.');
        }

        $base = realpath(BASE_PATH);
        if ($base === false) {
            throw new RuntimeException('Project base path could not be resolved.');
        }

        $resolvedDirs = [];
        foreach ($relativeDirs as $dir) {
            $dir = trim(str_replace('\\', '/', $dir), '/');
            if ($dir === '' || strpos($dir, '..') !== false) {
                continue;
            }
            $full = realpath($base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir));
            if ($full === false || !is_dir($full)) {
                continue;
            }
            if (strpos($full, $base) !== 0) {
                continue;
            }
            $resolvedDirs[$dir] = $full;
        }

        if ($resolvedDirs === []) {
            throw new RuntimeException('No backup folders found to include in the zip.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'slgti_files_');
        if ($tmp === false) {
            throw new RuntimeException('Could not create temporary zip file.');
        }
        $zipPath = $tmp . '.zip';
        @unlink($tmp);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create zip archive.');
        }

        foreach ($resolvedDirs as $label => $fullPath) {
            self::addDirectoryToZip($zip, $fullPath, $label);
        }

        $zip->close();

        if (!is_file($zipPath) || filesize($zipPath) === 0) {
            @unlink($zipPath);
            throw new RuntimeException('Zip archive is empty.');
        }

        $size = (int) filesize($zipPath);
        self::beginBrowserDownload($downloadFilename, 'application/zip', $size);

        readfile($zipPath);
        @unlink($zipPath);
        exit;
    }

    /**
     * Default upload/asset folders for file backup.
     *
     * @return list<string>
     */
    public static function defaultFileBackupDirs(): array {
        return ['uploads', 'assets'];
    }

    /**
     * Student documents + profile photos only (smaller, focused backup).
     *
     * @return list<string>
     */
    public static function studentDocumentsBackupDirs(): array {
        return [
            'uploads/student_applications',
            'uploads/students_applications',
            'uploads/student_documents',
            'assets/img/Studnet_profile',
            'assets/img/student_profile',
            'assets/img/Student_profile',
        ];
    }

    private static function exportTable(mysqli $conn, string $table): string {
        $sql = "\n\n-- Table: `$table` --\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";

        $create = $conn->query("SHOW CREATE TABLE `$table`");
        if (!$create || !($row = $create->fetch_row())) {
            return $sql;
        }
        $sql .= $row[1] . ";\n\n";

        $data = $conn->query("SELECT * FROM `$table`");
        if (!$data || $data->num_rows === 0) {
            return $sql;
        }

        $fields = $data->fetch_fields();
        $numFields = $data->field_count;

        while ($row = $data->fetch_row()) {
            $values = [];
            for ($i = 0; $i < $numFields; $i++) {
                $values[] = self::sqlValue($conn, $row[$i], $fields[$i]->type ?? null);
            }
            $sql .= "INSERT INTO `$table` VALUES(" . implode(',', $values) . ");\n";
        }

        return $sql;
    }

    private static function sqlValue(mysqli $conn, $value, $fieldType): string {
        if ($value === null) {
            return 'NULL';
        }

        $numericTypes = [
            MYSQLI_TYPE_TINY, MYSQLI_TYPE_SHORT, MYSQLI_TYPE_LONG, MYSQLI_TYPE_INT24,
            MYSQLI_TYPE_LONGLONG, MYSQLI_TYPE_FLOAT, MYSQLI_TYPE_DOUBLE, MYSQLI_TYPE_DECIMAL,
            MYSQLI_TYPE_NEWDECIMAL, MYSQLI_TYPE_BIT,
        ];

        if ($fieldType !== null && in_array((int) $fieldType, $numericTypes, true) && is_numeric($value)) {
            return (string) $value;
        }

        return "'" . $conn->real_escape_string((string) $value) . "'";
    }

    private static function exportTriggers(mysqli $conn, string $dbName): string {
        $sql = '';

        $stmt = $conn->prepare(
            'SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = ? ORDER BY TRIGGER_NAME'
        );
        if (!$stmt) {
            return $sql;
        }

        $stmt->bind_param('s', $dbName);
        $stmt->execute();
        $result = $stmt->get_result();

        $names = [];
        while ($row = $result->fetch_assoc()) {
            $names[] = $row['TRIGGER_NAME'];
        }
        $stmt->close();

        if ($names === []) {
            return $sql;
        }

        $sql .= "\n\n-- TRIGGERS --\n";
        foreach ($names as $triggerName) {
            $create = $conn->query('SHOW CREATE TRIGGER `' . str_replace('`', '``', $triggerName) . '`');
            $statement = self::extractCreateStatement($create, ['SQL Original Statement', 'Create Trigger']);
            if ($statement === null) {
                continue;
            }
            $sql .= "\nDROP TRIGGER IF EXISTS `$triggerName`;\n";
            $sql .= "DELIMITER ;;\n";
            $sql .= $statement . ";;\n";
            $sql .= "DELIMITER ;\n";
        }

        return $sql;
    }

    private static function exportRoutines(mysqli $conn, string $dbName): string {
        $sql = '';

        $procedures = $conn->query(
            "SHOW PROCEDURE STATUS WHERE Db = '" . $conn->real_escape_string($dbName) . "'"
        );
        if ($procedures) {
            while ($row = $procedures->fetch_assoc()) {
                $name = $row['Name'];
                $res = $conn->query('SHOW CREATE PROCEDURE `' . str_replace('`', '``', $name) . '`');
                $statement = self::extractCreateStatement($res, ['Create Procedure']);
                if ($statement !== null) {
                    $sql .= "\n\n-- PROCEDURE: {$name} --\n";
                    $sql .= "DROP PROCEDURE IF EXISTS `$name`;\n";
                    $sql .= "DELIMITER ;;\n" . $statement . ";;\nDELIMITER ;\n";
                }
            }
        }

        $functions = $conn->query(
            "SHOW FUNCTION STATUS WHERE Db = '" . $conn->real_escape_string($dbName) . "'"
        );
        if ($functions) {
            while ($row = $functions->fetch_assoc()) {
                $name = $row['Name'];
                $res = $conn->query('SHOW CREATE FUNCTION `' . str_replace('`', '``', $name) . '`');
                $statement = self::extractCreateStatement($res, ['Create Function']);
                if ($statement !== null) {
                    $sql .= "\n\n-- FUNCTION: {$name} --\n";
                    $sql .= "DROP FUNCTION IF EXISTS `$name`;\n";
                    $sql .= "DELIMITER ;;\n" . $statement . ";;\nDELIMITER ;\n";
                }
            }
        }

        return $sql;
    }

    private static function exportEvents(mysqli $conn, string $dbName): string {
        $sql = '';
        $events = $conn->query('SHOW EVENTS FROM `' . str_replace('`', '``', $dbName) . '`');
        if (!$events || $events->num_rows === 0) {
            return $sql;
        }

        $sql .= "\n\n-- EVENTS --\n";
        while ($row = $events->fetch_assoc()) {
            $name = $row['Name'];
            $res = $conn->query('SHOW CREATE EVENT `' . str_replace('`', '``', $name) . '`');
            $statement = self::extractCreateStatement($res, ['Create Event']);
            if ($statement !== null) {
                $sql .= "\nDROP EVENT IF EXISTS `$name`;\n";
                $sql .= "DELIMITER ;;\n" . $statement . ";;\nDELIMITER ;\n";
            }
        }

        return $sql;
    }

    private static function addDirectoryToZip(ZipArchive $zip, string $directory, string $zipPrefix): void {
        $directory = rtrim(str_replace('\\', '/', $directory), '/');
        $zipPrefix = trim(str_replace('\\', '/', $zipPrefix), '/');

        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }
            $filePath = $fileInfo->getRealPath();
            if ($filePath === false) {
                continue;
            }
            $relative = substr($filePath, strlen($directory) + 1);
            $entryName = $zipPrefix . '/' . str_replace('\\', '/', $relative);
            $zip->addFile($filePath, $entryName);
        }
    }

    /**
     * Temporary MySQL client config so password is not passed on the command line (avoids stderr warnings in dump).
     */
    private static function writeMysqlClientDefaultsFile(): ?string {
        $path = tempnam(sys_get_temp_dir(), 'slgti_mycnf_');
        if ($path === false) {
            return null;
        }

        $content = "[client]\n"
            . 'user=' . self::optionFileValue(DB_USER) . "\n"
            . 'password=' . self::optionFileValue(DB_PASS) . "\n"
            . 'host=' . self::optionFileValue(DB_HOST) . "\n";

        if (file_put_contents($path, $content) === false) {
            @unlink($path);
            return null;
        }

        @chmod($path, 0600);
        return $path;
    }

    private static function optionFileValue(string $value): string {
        if ($value === '') {
            return '""';
        }
        if (preg_match('/[\s#";=\']/', $value)) {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
        }
        return $value;
    }

    private static function findMysqldumpBinary(): ?string {
        $candidates = ['mysqldump'];

        $wampPatterns = [
            'C:\\wamp64\\bin\\mysql\\mysql*\\bin\\mysqldump.exe',
            'C:\\wamp\\bin\\mysql\\mysql*\\bin\\mysqldump.exe',
        ];
        foreach ($wampPatterns as $pattern) {
            $matches = glob($pattern);
            if (is_array($matches)) {
                foreach ($matches as $match) {
                    $candidates[] = $match;
                }
            }
        }

        foreach ($candidates as $candidate) {
            $output = [];
            $code = 1;
            @exec(escapeshellarg($candidate) . ' --version 2>&1', $output, $code);
            if ($code === 0) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param mysqli_result|false $result
     * @param list<string> $preferredKeys
     */
    private static function extractCreateStatement($result, array $preferredKeys): ?string {
        if (!$result) {
            return null;
        }

        $row = $result->fetch_assoc();
        if (!$row) {
            return null;
        }

        foreach ($preferredKeys as $key) {
            if (!empty($row[$key]) && is_string($row[$key])) {
                return $row[$key];
            }
        }

        foreach ($row as $value) {
            if (is_string($value) && stripos($value, 'CREATE') === 0) {
                return $value;
            }
        }

        return null;
    }
}
