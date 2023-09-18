<?php
declare(strict_types=1);

namespace MediaServer\HttpMessage\Message;

use MediaServer\HttpMessage\Contract\UploadedFile as UploadedFileInterface;
use RuntimeException;

use function count;
use function is_array;
use function is_uploaded_file;
use function move_uploaded_file;
use function rename;

use const PHP_SAPI;

final class UploadedFile implements UploadedFileInterface
{
    private bool $moved = false;

    public function __construct(
        private string $file,
        private int $size,
        private int $error,
        private string $clientFilename
    ) {
    }

    /**
     * @param array $superglobalFiles
     * @return array<string, array<int, UploadedFileInterface>>
     */
    public static function fromSuperglobals(array $superglobalFiles): array
    {
        if (empty($superglobalFiles)) {
            return [];
        }

        $return = [];

        /** @var array $files */
        foreach ($superglobalFiles as $fieldName => $files) {
            $return[(string)$fieldName] = [];
            if (is_array($files['name'])) {
                // Multiple files from same input
                $count = count($files['name']);
                /** @var array<string, string[]> $files */
                for ($i = 0; $i < $count; ++$i) {
                    $return[(string)$fieldName][] = new self(
                        (string)$files['tmp_name'][$i],
                        (int)$files['size'][$i],
                        (int)$files['error'][$i],
                        (string)($files['name'][$i] ?? '')
                    );
                }
            } else {
                // Single file from one input
                /** @var array<string, string> $files */
                $return[(string)$fieldName][] = new self(
                    $files['tmp_name'],
                    (int)$files['size'],
                    (int)$files['error'],
                    (string)($files['name'] ?? '')
                );
            }
        }

        return $return;
    }

    public function moveTo(string $targetPath): void
    {
        if ($this->moved) {
            throw new RuntimeException('The uploaded file has already been moved', 1);
        }

        if (PHP_SAPI !== 'cli') {
            if (!is_uploaded_file($this->file)) {
                throw new RuntimeException($this->file . ' is not an uploaded file', 3);
            }

            move_uploaded_file($this->file, $targetPath);
        } else {
            rename($this->file, $targetPath);
        }

        $this->moved = true;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function error(): int
    {
        return $this->error;
    }

    public function clientFilename(): string
    {
        return $this->clientFilename;
    }
}