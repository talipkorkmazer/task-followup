<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class EmptyBodyException extends HttpException
{
    /**
     * @param string|null     $message  The internal exception message
     * @param \Throwable|null $previous The previous exception
     * @param int             $code     The internal exception code
     */
    public function __construct(?string $message = '', \Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(400, 'The body of the POST/PUT method cannot be empty', $previous, $headers, $code);
    }
}