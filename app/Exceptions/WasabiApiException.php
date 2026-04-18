<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when the Wasabi Card upstream API returns a non-success response
 * or is unreachable. Carries both the Wasabi business code and the
 * appropriate HTTP status to return to our API clients.
 */
final class WasabiApiException extends RuntimeException
{
    private readonly int $httpStatus;

    public function __construct(string $message = '', int $code = 500, int $httpStatus = 0)
    {
        parent::__construct($message, $code);
        $this->httpStatus = $httpStatus !== 0 ? $httpStatus : $this->resolveHttpStatus($code);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * Map Wasabi business codes to appropriate HTTP status codes.
     * Upstream errors become 502 Bad Gateway so clients can distinguish
     * our own errors (4xx) from upstream failures.
     */
    private function resolveHttpStatus(int $apiCode): int
    {
        return match (true) {
            $apiCode === 401             => 401,
            $apiCode === 403             => 403,
            $apiCode === 404             => 404,
            $apiCode === 503             => 503,
            $apiCode >= 400 && $apiCode < 500 => 422,
            default                     => 502,
        };
    }
}
