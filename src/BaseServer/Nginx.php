<?php declare(strict_types=1);

namespace AP\HttpServer\BaseServer;

use AP\HttpServer\HttpServer;
use AP\Logger\Log;
use AP\Routing\Request\Method;
use RuntimeException;

readonly class Nginx implements ServerInterface
{
    /**
     * @param bool $use_proxy_header Whether to use proxy headers
     * @param string $proxy_header The name of the header to trust
     * @param array|true $trusted_proxies A list of IPs or CIDRs to trust as proxies
     *                                    true - allowed all
     */
    public function __construct(
        public bool       $use_proxy_header = false,
        public string     $proxy_header = 'X-Forwarded-For',
        public array|true $trusted_proxies = true,
    )
    {
    }

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

    public function parseRequestIP(): string
    {
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            throw new RuntimeException("`parseMethod` error: _SERVER['REMOTE_ADDR'] is required");
        }
        if (!filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            throw new RuntimeException("_SERVER['REMOTE_ADDR'] invalid ip");
        }
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!$this->use_proxy_header) {
            return $ip;
        }
        $name = $this->normalizeHeaderName($this->proxy_header);
        if (isset($_SERVER[$name]) && $this->isTrustedProxy($ip)) {
            $proxy_ips = explode(
                ',',
                $_SERVER[$name]
            );
            foreach ($proxy_ips as $proxy_ip) {
                $proxy_ip = trim($proxy_ip);
                if (filter_var($proxy_ip, FILTER_VALIDATE_IP)) {
                    $ip = $proxy_ip;
                    break;
                }
            }
        }
        return $ip;
    }

    private function normalizeHeaderName(string $header): string
    {
        return 'HTTP_' . strtoupper(str_replace('-', '_', $header));
    }

    /**
     * Determine whether the given IP is in the list of trusted proxies.
     */
    private function isTrustedProxy(string $ip): bool
    {
        if ($this->trusted_proxies === true) {
            return true;
        }
        foreach ($this->trusted_proxies as $proxy) {
            if ($this->ipInRange($ip, $proxy)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if an IP is within a given IP/CIDR range.
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        [$subnet, $bits] = explode('/', $range);
        $ip_long     = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask        = ~((1 << (32 - $bits)) - 1);

        return ($ip_long & $mask) === ($subnet_long & $mask);
    }
}