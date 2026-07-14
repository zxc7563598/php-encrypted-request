<?php

declare(strict_types=1);

namespace Hejunjie\EncryptedRequest\Exceptions;

use Exception;

class SignatureException extends Exception
{
    public function __construct(
        string $message = "请求签名无效",
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
