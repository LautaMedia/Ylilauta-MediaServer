<?php
declare(strict_types=1);

use Config\Config;
use MediaServer\HttpMessage\Message\Headers;
use MediaServer\HttpMessage\Message\Request;
use MediaServer\HttpMessage\Message\UploadedFile;
use MediaServer\HttpMessage\Message\Uri;
use MediaServer\Outputter\ResponseOutputter;
use MediaServer\Route;

// As we always want to generate the image and do cleanup
ignore_user_abort(true);

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

$cfg = new Config();
(new ResponseOutputter($cfg, $request, new Route($cfg)))->output();