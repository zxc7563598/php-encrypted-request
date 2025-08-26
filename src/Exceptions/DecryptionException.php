<?php

namespace Hejunjie\EncryptedRequest\Exceptions;

use Exception;

class DecryptionException extends Exception
{
    public function __construct(
        string $message = "Failed to decrypt the data",
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
