<?php

declare(strict_types=1);

namespace ModelsAlpha\Exceptions;

use RuntimeException;
use Throwable;

final class ApiException extends RuntimeException
{
    public function __construct(
        string $message = 'ModelsAlpha BaseApiModel exception',
        int $code = 520,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function api(string $modelMethod, string $msg, int $code): self
    {
        return new self($modelMethod . ' API-error #### ' . $msg, $code);
    }

//    public static function unauthorized(string $message = 'Unauthorized'): self
//    {
//        return new self($message, 401);
//    }
//
//    public static function forbidden(string $message = 'Forbidden'): self
//    {
//        return new self($message, 403);
//    }
//
//    public static function notFound(string $message = 'Not Found'): self
//    {
//        return new self($message, 404);
//    }
//
//    public static function tooManyRequests(string $message = 'Too Many Requests'): self
//    {
//        return new self($message, 429);
//    }
//
//    public static function serverError(string $message = 'Server Error'): self
//    {
//        return new self($message, 500);
//    }
}