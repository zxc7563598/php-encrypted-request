<?php

namespace Hejunjie\EncryptedRequest\Contracts;

interface DecryptorInterface
{
    /**
     * 解密数据
     *
     * @param string $data 加密字符串
     * 
     * @return array 解密后的数组
     * @throws \Hejunjie\EncryptedRequest\Exceptions\DecryptionException
     */
    public function decrypt(string $data): array;
}
