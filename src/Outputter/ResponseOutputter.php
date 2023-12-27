<?php
declare(strict_types=1);

namespace MediaServer\Outputter;

use MediaServer\Config\Contract\Config;
use MediaServer\HttpMessage\Contract\Request as RequestInterface;
use MediaServer\HttpMessage\Contract\RequestHandler;
use MediaServer\HttpMessage\Message\Response;
use RuntimeException;
use Throwable;

use function dirname;
use function error_log;
use function explode;
use function fclose;
use function file_get_contents;
use function filesize;
use function fopen;
use function fread;
use function fseek;
use function header;
use function http_response_code;
use function ini_get;
use function readfile;
use function str_replace;
use function unlink;

final class ResponseOutputter
{
    public function __construct(
        private readonly Config $cfg,
        private readonly RequestInterface $request,
        private readonly RequestHandler $handler,
    ) {
    }

    public function output(): void
    {
        try {
            $response = $this->handler->handle($this->request);
        } catch (Throwable $e) {
            if ($e->getCode() === 404) {
                $notFoundFile = file_get_contents(dirname(__DIR__, 2) . '/public/file-empty.png');
                $response = new Response($notFoundFile, 404, ['Content-Type' => ['image/png']]);
            } else {
                $errorMessage = "{$e->getFile()}:{$e->getLine()}: {$e->getMessage()}";
                /** @noinspection ForgottenDebugOutputInspection */
                error_log($errorMessage);
                if (ini_get('display_errors') === '1') {
                    $responseText = $errorMessage;
                } else {
                    $responseText = 'Oh no! It\'s broken! Please wait while we fix it...';
                }

                $response = new Response($responseText, $e->getCode() === 0 ? 500 : $e->getCode());
            }
        }

        $range = ($this->request->header('Range')[0] ?? '');
        $isPartial = $range !== '';

        if ($response->statusCode() !== 0 && $response->statusCode() !== 1) {
            http_response_code($response->statusCode());
            foreach ($response->headers() as $name => $values) {
                foreach ($values as $value) {
                    header("{$name}: {$value}", false);
                }
            }
            echo $response->body();

            return;
        }

        if (!$isPartial) {
            foreach ($response->headers() as $name => $values) {
                foreach ($values as $value) {
                    header("{$name}: {$value}", false);
                }
            }

            $this->sendFile($response->body());
            if ($response->statusCode() === 1) {
                unlink($file);
            }

            return;
        }

        // Partial response
        // Does not support multiple ranges, fix if ever needed
        $range = str_replace('bytes=', '', $range);
        $range = explode(',', $range, 1)[0];
        $rangeBytes = explode('-', $range);
        $filesize = filesize($response->body());

        if ($filesize === false) {
            throw new RuntimeException("Could not read file {$response->body()}");
        }

        if (!isset($rangeBytes[1]) || $rangeBytes[1] === '') {
            $endOffset = $filesize - 1;
        } else {
            $endOffset = (int)$rangeBytes[1];
        }
        if (!isset($rangeBytes[0]) || $rangeBytes[0] === '') {
            $startOffset = $filesize - ($endOffset + 1);
            $endOffset = $filesize - 1;
        } else {
            $startOffset = (int)$rangeBytes[0];
        }

        if ($startOffset > $endOffset || $startOffset > $filesize - 1) {
            http_response_code(416);
            header("Content-Range: */{$filesize}");

            return;
        }

        http_response_code(206);
        $response = $response
            ->withHeader('Content-Length', (string)($endOffset - $startOffset + 1))
            ->withHeader('Content-Range', "bytes {$startOffset}-{$endOffset}/{$filesize}");
        foreach ($response->headers() as $name => $values) {
            foreach ($values as $value) {
                header("{$name}: {$value}", false);
            }
        }

        $this->sendFileRange($response->body(), $startOffset, $endOffset);
        if ($response->statusCode() === 1) {
            unlink($file);
        }
    }

    private function sendFile(string $file): void
    {
        readfile($file);
    }

    private function sendFileRange(string $file, int $startOffset, int $endOffset): void
    {
        $fh = fopen($file, 'rb');
        if ($fh === false) {
            throw new RuntimeException("Failed to open file {$file}");
        }

        if ($startOffset !== 0) {
            fseek($fh, $startOffset);
        }

        $chunkSize = 8192;
        $bytesLeft = $endOffset - $startOffset;
        while ($bytesLeft > 0) {
            echo fread($fh, $chunkSize);
            $bytesLeft -= $chunkSize;
        }

        fclose($fh);
    }
}