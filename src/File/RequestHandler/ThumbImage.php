<?php
declare(strict_types=1);

namespace MediaServer\File\RequestHandler;

use MediaServer\Config\Contract\Config;
use MediaServer\Downloader\Downloader;
use MediaServer\HeaderGenerator\FileResponseHeaders;
use MediaServer\HttpMessage\Contract\Request;
use MediaServer\HttpMessage\Contract\RequestHandler;
use MediaServer\HttpMessage\Contract\Response as ResponseInterface;
use MediaServer\HttpMessage\Message\EmptyResponse;
use MediaServer\HttpMessage\Message\Response;
use MediaServer\Scaler\ImageMagick;
use RuntimeException;
use Throwable;

use function escapeshellarg;
use function filesize;
use function in_array;
use function is_file;
use function rename;
use function shell_exec;
use function sys_get_temp_dir;
use function tempnam;
use function var_dump;

final class ThumbImage implements RequestHandler
{
    public function __construct(private Config $cfg)
    {
    }

    public function handle(Request $request): ResponseInterface
    {
        $format = $request->attribute('format');
        $basename = $request->attribute('basename');
        $width = $request->attribute('width');
        $thumbFormat = $request->attribute('thumbFormat');

        if (!in_array($format, $this->cfg->allowedFiletypes(), true)) {
            return new EmptyResponse(404);
        }

        if ($format === 'mp4') {
            $format = 'jpg';
        }

        $file = (new Downloader("{$this->cfg->fileSourceUrl()}{$basename}.{$format}"))->download();
        try {
            if ($thumbFormat === 'avif') {
                $fmt = 'jpg';
            } else {
                $fmt = $thumbFormat;
            }

            (new ImageMagick(sys_get_temp_dir(), $file))->scale(
                (int)$width,
                (int)$width,
                $this->cfg->thumbQuality($fmt),
                $fmt
            );

            if ($thumbFormat === 'avif') {
                $tempfile = tempnam(sys_get_temp_dir(), 'avifout-');
                shell_exec('/usr/bin/avifenc --speed 9 ' . escapeshellarg($file) . ' ' . escapeshellarg($tempfile));
                if (filesize($tempfile) === 0) {
                    unlink($tempfile);
                    throw new RuntimeException('avifenc failed');
                }
                rename($tempfile, $file);
            }
        } catch (Throwable $e) {
            if (is_file($file)) {
                unlink($file);
            }

            throw new RuntimeException("Failed to scale {$basename}: {$e->getMessage()}", 1, $e);
        }

        return new Response(
            $file,
            0,
            (new FileResponseHeaders($this->cfg, $file))->headers()
        );
    }
}