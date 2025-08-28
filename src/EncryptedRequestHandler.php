<?php

namespace Hejunjie\EncryptedRequest;

use Hejunjie\EncryptedRequest\Config\EnvConfigLoader;
use Hejunjie\EncryptedRequest\Drivers\AesDecryptor;
use Hejunjie\EncryptedRequest\Drivers\RsaDecryptor;
use Hejunjie\EncryptedRequest\Exceptions\DecryptionException;
use Hejunjie\EncryptedRequest\Exceptions\SignatureException;
use Hejunjie\EncryptedRequest\Exceptions\TimestampException;

class EncryptedRequestHandler
{
    private array $config;

    /**
     * 构造方法
     *
     * @param array|string|null $config 配置数组或.env路径
     */
    public function __construct(array|string $config = '')
    {
        if (is_array($config)) {
            $loader = new EnvConfigLoader($config);
        } elseif (is_string($config)) {
            $loader = new EnvConfigLoader([], $config);
        } else {
            $loader = new EnvConfigLoader();
        }
        $this->config = [
            'default_timestamp_diff' => $loader->get('DEFAULT_TIMESTAMP_DIFF', 60),
            'rsa_private_key' => $loader->get('RSA_PRIVATE_KEY', '')
        ];
    }

    /**
     * 验证请求并解密数据
     *
     * @param string $en_data 加密数据
     * @param int $timestamp 时间戳
     * @param string $sign 签名
     * 
     * @return array 解密后的数据
     * @throws SignatureException 签名验证错误
     * @throws TimestampException 请求时间错误
     * @throws DecryptionException 数据解密错误
     */
    public function handle(string $en_data, string $enc_payload, int $timestamp, string $sign): array
    {
        if (!isset($timestamp, $sign, $en_data)) {
            throw new SignatureException("Missing required parameters");
        }
        // 1. 时间戳验证
        $timestampDiff = $this->config['default_timestamp_diff'];
        $now = time();
        if (abs($now - $timestamp) > $timestampDiff) {
            throw new TimestampException("Timestamp difference too large");
        }
        // 2. 获取签名数据
        $rsa = new RsaDecryptor($this->config['rsa_private_key']);
        $enc_payload = $rsa->decrypt($enc_payload);
        // 3. 签名验证
        $expectedSign = md5(md5($enc_payload) . $timestamp);
        if ($expectedSign !== $sign) {
            throw new SignatureException("Invalid signature");
        }
        // 4. 解密数据
        $aesKeyBase64 = substr($enc_payload, 0, 24);
        $aesIvBase64  = substr($enc_payload, -24);
        $aes = new AesDecryptor(base64_decode($aesKeyBase64), base64_decode($aesIvBase64));
        $decrypted = $aes->decrypt($en_data);
        return $decrypted;
    }
}
