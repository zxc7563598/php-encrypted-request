<?php

declare(strict_types=1);

namespace Hejunjie\EncryptedRequest\Drivers;

use Hejunjie\EncryptedRequest\Contracts\DecryptorInterface;
use Hejunjie\EncryptedRequest\Exceptions\DecryptionException;

class AesDecryptor implements DecryptorInterface
{
    private const GCM_TAG_LENGTH = 16;
    private const KEY_LENGTH = 32;
    private const IV_LENGTH = 12;

    /**
     * @param string $key AES-256 密钥（32 字节原始二进制）
     * @param string $iv  GCM 初始化向量（12 字节原始二进制）
     *
     * @throws DecryptionException
     */
    public function __construct(
        private string $key,
        private string $iv,
    ) {
        if (strlen($key) !== self::KEY_LENGTH) {
            throw new DecryptionException(
                sprintf('AES-256-GCM 密钥长度错误：需要 %d 字节，实际 %d 字节', self::KEY_LENGTH, strlen($key))
            );
        }
        if (strlen($iv) !== self::IV_LENGTH) {
            throw new DecryptionException(
                sprintf('AES-GCM IV 长度错误：建议 %d 字节，实际 %d 字节', self::IV_LENGTH, strlen($iv))
            );
        }
    }

    /**
     * AES-256-GCM 解密。
     *
     * 输入格式：base64(ciphertext || 16-byte-auth-tag)
     *
     * @param string $data Base64 编码的密文（含 GCM 认证标签）
     *
     * @return array 解密后的数据
     * @throws DecryptionException
     */
    public function decrypt(string $data): array
    {
        try {
            $decoded = base64_decode($data, true);
            if ($decoded === false) {
                throw new DecryptionException("Base64 解码失败");
            }
            if (strlen($decoded) < self::GCM_TAG_LENGTH) {
                throw new DecryptionException("密文长度不足：缺少 GCM 认证标签");
            }
            // GCM 密文格式：ciphertext + 16-byte tag（由 Web Crypto API 标准输出）
            $ciphertext = substr($decoded, 0, -self::GCM_TAG_LENGTH);
            $tag = substr($decoded, -self::GCM_TAG_LENGTH);
            $decrypted = openssl_decrypt(
                $ciphertext,
                'aes-256-gcm',
                $this->key,
                OPENSSL_RAW_DATA,
                $this->iv,
                $tag,
            );
            if ($decrypted === false) {
                throw new DecryptionException("AES-256-GCM 解密或认证失败");
            }
            $result = json_decode($decrypted, true);
            if (!is_array($result)) {
                throw new DecryptionException("解密后的数据不是有效的 JSON");
            }
            return $result;
        } catch (DecryptionException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new DecryptionException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
