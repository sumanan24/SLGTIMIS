<?php
/**
 * Builds a minimal ZIP archive (compression method 0 = stored) without ext-zip / ZipArchive.
 * Sufficient for small OOXML packages (.xlsx). Excel accepts stored entries.
 */
class StoredZipWriter {
    /**
     * @param array<string,string> $entries Zip entry path (use forward slashes) => raw file contents
     */
    public static function writeFile(string $outPath, array $entries): void {
        if ($entries === []) {
            throw new InvalidArgumentException('StoredZipWriter: no entries.');
        }
        $fh = fopen($outPath, 'wb');
        if ($fh === false) {
            throw new RuntimeException('StoredZipWriter: could not open output file for writing.');
        }

        $central = '';
        $offset = 0;
        $dosTime = 0;
        $dosDate = 0;

        foreach ($entries as $name => $data) {
            $name = str_replace('\\', '/', (string) $name);
            if ($name === '' || $name[0] === '/') {
                throw new InvalidArgumentException('StoredZipWriter: invalid entry name.');
            }
            $nameBin = $name;
            $body = (string) $data;
            $unc = strlen($body);
            $crc = crc32($body) & 0xffffffff;
            $nl = strlen($nameBin);

            $local = pack('V', 0x04034b50);
            $local .= pack('v', 20);
            $local .= pack('v', 0);
            $local .= pack('v', 0);
            $local .= pack('v', $dosTime);
            $local .= pack('v', $dosDate);
            $local .= pack('V', $crc);
            $local .= pack('V', $unc);
            $local .= pack('V', $unc);
            $local .= pack('v', $nl);
            $local .= pack('v', 0);
            $local .= $nameBin;

            $localHeaderOffset = $offset;
            if (fwrite($fh, $local) !== strlen($local)) {
                fclose($fh);
                throw new RuntimeException('StoredZipWriter: write failed (local header).');
            }
            if ($unc > 0 && fwrite($fh, $body) !== $unc) {
                fclose($fh);
                throw new RuntimeException('StoredZipWriter: write failed (payload).');
            }
            $offset += strlen($local) + $unc;

            $cd = pack('V', 0x02014b50);
            $cd .= pack('v', 0x0314);
            $cd .= pack('v', 20);
            $cd .= pack('v', 0);
            $cd .= pack('v', 0);
            $cd .= pack('v', $dosTime);
            $cd .= pack('v', $dosDate);
            $cd .= pack('V', $crc);
            $cd .= pack('V', $unc);
            $cd .= pack('V', $unc);
            $cd .= pack('v', $nl);
            $cd .= pack('v', 0);
            $cd .= pack('v', 0);
            $cd .= pack('v', 0);
            $cd .= pack('v', 0);
            $cd .= pack('V', 0);
            $cd .= pack('V', $localHeaderOffset);
            $cd .= $nameBin;
            $central .= $cd;
        }

        $cdOffset = $offset;
        $cdSize = strlen($central);
        if (fwrite($fh, $central) !== $cdSize) {
            fclose($fh);
            throw new RuntimeException('StoredZipWriter: write failed (central directory).');
        }

        $n = count($entries);
        if ($n > 0xffff) {
            fclose($fh);
            throw new RuntimeException('StoredZipWriter: too many entries.');
        }

        $end = pack('V', 0x06054b50);
        $end .= pack('v', 0);
        $end .= pack('v', 0);
        $end .= pack('v', $n);
        $end .= pack('v', $n);
        $end .= pack('V', $cdSize);
        $end .= pack('V', $cdOffset);
        $end .= pack('v', 0);
        if (fwrite($fh, $end) !== strlen($end)) {
            fclose($fh);
            throw new RuntimeException('StoredZipWriter: write failed (end record).');
        }
        fclose($fh);
    }
}
