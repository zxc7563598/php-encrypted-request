<?php

namespace Hejunjie\EncryptedRequest\Exceptions;

use Exception;

class SignatureException extends Exception
{
    public function __construct(
        string $message = "Invalid request signature",
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
