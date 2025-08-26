<?php

namespace Hejunjie\EncryptedRequest\Drivers;

use Hejunjie\EncryptedRequest\Contracts\DecryptorInterface;
use Hejunjie\EncryptedRequest\Exceptions\DecryptionException;

class AesDecryptor implements DecryptorInterface
{
    private string $key;
    private string $iv;
    private string $cipher;

    /**
     * 构造方法
     *
     * @param string $key AES 密钥
     * @param string $iv AES 偏移量（IV）
     * @param string $cipher 加密方式，默认 AES-128-CBC
     */
    public function __construct(string $key, string $iv, string $cipher = 'AES-128-CBC')
    {
        $this->key = $key;
        $this->iv = $iv;
        $this->cipher = $cipher;
    }

    /**
     * 解密方法
     *
     * @param string $data 加密数据
     * 
     * @return array 解密后的数组
     * @throws DecryptionException
     */
    public function decrypt(string $data): array
    {
        try {
            // Base64 解码
            $decoded = base64_decode($data, true);
            if ($decoded === false) {
                throw new DecryptionException("Base64 decode failed");
            }
            // AES 解密
            $decrypted = openssl_decrypt($decoded, $this->cipher, $this->key, OPENSSL_RAW_DATA, $this->iv);
            if ($decrypted === false) {
                throw new DecryptionException("AES decryption failed");
            }
            // JSON 解析
            $result = json_decode($decrypted, true);
            if (!is_array($result)) {
                throw new DecryptionException("Decrypted data is not valid JSON");
            }
            return $result;
        } catch (\Throwable $e) {
            throw new DecryptionException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
