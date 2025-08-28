<?php

namespace Hejunjie\EncryptedRequest\Drivers;

use Hejunjie\EncryptedRequest\Contracts\DecryptorInterface;
use Hejunjie\EncryptedRequest\Exceptions\DecryptionException;

class RsaDecryptor implements DecryptorInterface
{
    private mixed $privateKey;

    /**
     * 构造方法
     *
     * @param string $private_key 私钥字符串（包含 -----BEGIN PRIVATE KEY-----）
     */
    public function __construct(string $private_key)
    {
        $this->privateKey = @openssl_pkey_get_private($private_key);
        if ($this->privateKey === false) {
            $errs = [];
            while ($err = openssl_error_string()) {
                $errs[] = $err;
            }
            $msg = 'Failed to load private key';
            if (!empty($errs)) {
                $msg .= ': ' . implode(' | ', $errs);
            }
            throw new DecryptionException($msg);
        }
    }

    /**
     * 解密方法
     *
     * @param string $data 加密数据
     * 
     * @return string 解密后的字符串
     * @throws DecryptionException
     */
    public function decrypt(string $data): string
    {
        try {
            // Base64 解码
            $ciphertext = base64_decode($data, true);
            if ($ciphertext === false) {
                throw new DecryptionException('enc_payload is not valid base64');
            }
            // 检查 OpenSSL 扩展是否可用
            if (!function_exists('openssl_pkey_get_private')) {
                throw new DecryptionException('OpenSSL extension is not available in PHP');
            }
            // 尝试解密
            $decrypted = '';
            $success = @openssl_private_decrypt($ciphertext, $decrypted, $this->privateKey, OPENSSL_PKCS1_OAEP_PADDING);
            if ($success === false) {
                $errs = [];
                while ($err = openssl_error_string()) {
                    $errs[] = $err;
                }
                $msg = 'RSA decryption failed';
                if (!empty($errs)) {
                    $msg .= ': ' . implode(' | ', $errs);
                } else {
                    $msg .= '. No OpenSSL errors captured.';
                }
                throw new DecryptionException($msg);
            }
            // 额外校验：解密结果不能为空
            if ($decrypted === '' || $decrypted === null) {
                throw new DecryptionException('RSA decryption returned empty string');
            }
            return $decrypted;
        } catch (\Throwable $e) {
            throw new DecryptionException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
