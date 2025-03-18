<?php declare(strict_types=1);

namespace AP\HttpServer\BaseServer;

use AP\Routing\Request\Method;

interface ServerInterface
{
    public function parseHeaders(): array;

    public function parseMethod(): Method;

    public function parsePath(): string;

    public function parseGet(): array;

    public function parsePost(): array;

    public function parseCookie(): array;

    public function parseFiles(): array;

    public function parseRequestBody(): string;

    public function parseRequestIP(): string;

    /**
     * Flushes all response data to the client and finishes the request.
     * This allows for time-consuming tasks to be performed without leaving the connection to the client open.
     *
     * @return void
     */
    public function finishRequest(): void;
}