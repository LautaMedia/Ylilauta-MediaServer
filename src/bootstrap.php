<?php
declare(strict_types=1);

use MediaServer\HttpMessage\Message\Headers;
use MediaServer\HttpMessage\Message\Request;
use MediaServer\HttpMessage\Message\UploadedFile;
use MediaServer\HttpMessage\Message\Uri;
use MediaServer\Outputter\ResponseOutputter;
use MediaServer\Route;

spl_autoload_register(static function (string $class) {
    /** @psalm-suppress UnresolvableInclude */
    require preg_replace(
        ['#^MediaServer/#', '#^Config/#'],
        [__DIR__ . '/', dirname(__DIR__) . '/config/'],
        str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php'
    );
});

/** @var array<string, string|string[]> $_POST - Could not find any cases where this is not true */
$request = new Request(
    (string)($_SERVER['REQUEST_ID'] ?? ''),
    Headers::fromSuperglobals($_SERVER),
    (string)($_SERVER['REQUEST_METHOD'] ?? 'GET'),
    new Uri(((string)($_SERVER['REQUEST_SCHEME'] ?? 'https')) . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"),
    $_SERVER,
    $_COOKIE,
    $_GET,
    UploadedFile::fromSuperglobals($_FILES),
    $_POST
);

(new ResponseOutputter($request, new Route()))->output();