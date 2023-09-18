<?php
declare(strict_types=1);

namespace MediaServer\Scaler;

use Imagick;
use ImagickException;
use RuntimeException;

use function filesize;
use function in_array;
use function is_file;
use function min;
use function rename;
use function tempnam;
use function unlink;

final class ImageMagick
{
    public function __construct(
        private string $tempDir,
        private string $file
    ) {
    }

    /**
     * @param int $width
     * @param int $height
     * @param int $quality
     * @param string $type
     * @return void
     * @throws ImagickException
     */
    public function scale(int $width, int $height, int $quality, string $type): void
    {
        if (!in_array($type, ['jpg', 'png', 'avif'])) {
            throw new RuntimeException("Unsupported file type: {$type}", 500);
        }

        $scaledTempFile = tempnam($this->tempDir, 'scaled-');

        Imagick::setResourceLimit(imagick::RESOURCETYPE_MEMORY, 128 * 1024 * 1024);
        Imagick::setResourceLimit(imagick::RESOURCETYPE_MAP, 256 * 1024 * 1024);
        Imagick::setResourceLimit(imagick::RESOURCETYPE_AREA, 256 * 1024 * 1024);
        Imagick::setResourceLimit(imagick::RESOURCETYPE_FILE, 512 * 1024 * 1024);
        Imagick::setResourceLimit(imagick::RESOURCETYPE_DISK, 512 * 1024 * 1024);

        $im = new Imagick();
        $im->readImage($this->file);
        $im->setImagePage(0, 0, 0, 0);
        $im->thumbnailImage(min($im->getImageWidth(), $width), min($im->getImageHeight(), $height), true);
        $im->stripImage();
        $im->setImageFormat($type);
        match ($type) {
            default => $im->setImageCompressionQuality($quality),
            'png', 'avif' => $im->setCompressionQuality($quality),
        };
        $im->writeImage($scaledTempFile);
        $im->destroy();

        if (!is_file($scaledTempFile) || filesize($scaledTempFile) === 0) {
            if (is_file($scaledTempFile)) {
                unlink($scaledTempFile);
            }
            throw new RuntimeException('Image scaling failed', 500);
        }

        rename($scaledTempFile, $this->file);
    }
}