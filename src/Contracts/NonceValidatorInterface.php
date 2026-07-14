<?php

declare(strict_types=1);

namespace Hejunjie\EncryptedRequest\Contracts;

interface NonceValidatorInterface
{
    /**
     * 验证 nonce 是否有效（未使用过且未过期）
     *
     * @param string $nonce 一次性随机数
     * @param int    $ttl   有效期（秒）
     *
     * @return bool true = 有效，false = 已使用或已过期
     */
    public function verify(string $nonce, int $ttl): bool;
}
