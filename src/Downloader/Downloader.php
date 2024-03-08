<?php
declare(strict_types=1);

namespace MediaServer\Downloader;

use RuntimeException;

use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function fclose;
use function flock;
use function fopen;
use function is_file;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const CURLINFO_RESPONSE_CODE;
use const CURLOPT_FILE;
use const LOCK_EX;

final class Downloader
{
    public function __construct(private readonly string $sourceUrl)
    {
    }

    public function download(): string
    {
        $failTitle = "Could not download file from '{$this->sourceUrl}'";

        $tempFile = tempnam(sys_get_temp_dir(), 'ylilauta-mediaserver-download-');
        $fp = fopen($tempFile, 'wb');

        if (!$fp || !flock($fp, LOCK_EX)) {
            throw new RuntimeException("{$failTitle}: failed to open or lock temp file");
        }

        $ch = curl_init($this->sourceUrl);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $curl = curl_exec($ch);
        $responseCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);
        fclose($fp);

        $success = $curl === true && $responseCode === 200;
        if (!$success) {
            if (is_file($tempFile)) {
                unlink($tempFile);
            }

            throw new RuntimeException("{$failTitle}: download failed: {$responseCode}", 404);
        }

        return $tempFile;
    }
}