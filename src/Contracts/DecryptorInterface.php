<?php

namespace Hejunjie\EncryptedRequest\Contracts;

interface DecryptorInterface
{
    /**
     * 解密数据
     *
     * @param string $data 加密字符串
     * 
     * @return array|string 解密后的数组或字符串
     * @throws \Hejunjie\EncryptedRequest\Exceptions\DecryptionException
     */
    public function decrypt(string $data): array|string;
}
