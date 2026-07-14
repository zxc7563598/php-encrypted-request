<?php

declare(strict_types=1);

namespace Hejunjie\EncryptedRequest\Exceptions;

use Exception;

class DecryptionException extends Exception
{
    public function __construct(
        string $message = "数据解密失败",
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
