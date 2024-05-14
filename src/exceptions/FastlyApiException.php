<?php

namespace OneTribe\Upper\Exceptions;

use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class FastlyApiException extends Exception
{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct("Fastly API error: $message", $code, $previous);
    }

    public static function create(RequestInterface $request, ResponseInterface $response = null): static
    {
        $uri = $request->getUri();

        if (is_null($response)) {
            return new static("Cloudflare no response error, uri: '$uri'");
        }

        // Extract error message from body
        $status = $response->getStatusCode();
        $json   = json_decode($response->getBody());

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new static("Cloudflare API error ($status) on: '$uri'", $status);
        }

        // Error message
        if (isset($json->msg)) {
            return new static($json->msg . ", uri: '$uri'", $response->getStatusCode());
        }

        // Unknown
        return new static("Unknown error, uri: '$uri'");
    }
}

