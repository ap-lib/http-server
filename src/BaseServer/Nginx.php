<?php declare(strict_types=1);

namespace AP\HttpServer\BaseServer;

use AP\HttpServer\HttpServer;
use AP\Logger\Log;
use AP\Routing\Request\Method;
use RuntimeException;

class Nginx implements ServerInterface
{
    public function parseHeaders(): array
    {
        return getallheaders();
    }

    public function parseMethod(): Method
    {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            throw new RuntimeException("`parseMethod` error: _SERVER['REQUEST_METHOD'] is required");
        }
        return Method::from($_SERVER['REQUEST_METHOD']);
    }

    public function parsePath(): string
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            throw new RuntimeException("`parsePath` error: _SERVER['REQUEST_URI'] is required");
        }
        return explode('?', $_SERVER['REQUEST_URI'], 2)[0];
    }

    public function parseGet(): array
    {
        return $_GET;
    }

    public function parsePost(): array
    {
        return $_POST;
    }

    public function parseCookie(): array
    {
        return $_COOKIE;
    }

    public function parseFiles(): array
    {
        return $_FILES;
    }

    public function parseRequestBody(): string
    {
        return (string)file_get_contents('php://input');
    }

    public function finishRequest(): void
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            Log::warn(
                "function `fastcgi_finish_request` no exist",
                module: HttpServer::LOG_MODULE_NAME
            );
        }
    }
}