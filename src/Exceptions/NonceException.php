<?php

declare(strict_types=1);

namespace Hejunjie\EncryptedRequest\Exceptions;

use Exception;

class NonceException extends Exception
{
    public function __construct(
        string $message = "检测到重复请求",
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
