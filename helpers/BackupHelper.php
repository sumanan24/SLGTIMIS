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

        $host = escapeshellarg(DB_HOST);
        $user = escapeshellarg(DB_USER);
        $pass = escapeshellarg(DB_PASS);
        $name = escapeshellarg($dbName);

        $cmd = escapeshellarg($mysqldump)
            . " --host={$host} --user={$user} --password={$pass}"
            . ' --routines --triggers --events --single-transaction --hex-blob --default-character-set=utf8mb4'
            . " {$name} 2>&1";

        $output = [];
        $exitCode = 1;
        @exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || $output === []) {
            return null;
        }

        $sql = implode("\n", $output);
        if (stripos($sql, 'CREATE TABLE') === false && stripos($sql, 'CREATE DATABASE') === false) {
            return null;
        }

        return $sql;
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
