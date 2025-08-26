<?php

namespace Hejunjie\EncryptedRequest;

use Hejunjie\EncryptedRequest\Contracts\DecryptorInterface;
use Hejunjie\EncryptedRequest\Config\EnvConfigLoader;
use Hejunjie\EncryptedRequest\Exceptions\DecryptionException;
use Hejunjie\EncryptedRequest\Exceptions\SignatureException;
use Hejunjie\EncryptedRequest\Exceptions\TimestampException;

class EncryptedRequestHandler
{
    private array $config;
    private DecryptorInterface $decryptor;

    /**
     * 构造方法
     *
     * @param DecryptorInterface $decryptor 解密器
     * @param array|string|null $config 配置数组或.env路径
     */
    public function __construct(DecryptorInterface $decryptor, array|string $config = '')
    {
        if (is_array($config)) {
            $loader = new EnvConfigLoader($config);
        } elseif (is_string($config)) {
            $loader = new EnvConfigLoader([], $config);
        } else {
            $loader = new EnvConfigLoader();
        }
        $this->config = [
            'key' => $loader->get('APP_KEY'),
            'aes_key' => $loader->get('AES_KEY'),
            'aes_iv' => $loader->get('AES_IV'),
            'default_timestamp_diff' => $loader->get('DEFAULT_TIMESTAMP_DIFF', 60)
        ];

        $this->decryptor = $decryptor;
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
    public function handle(string $en_data, int $timestamp, string $sign): array
    {
        // 1. 签名验证
        if (!isset($timestamp, $sign, $en_data)) {
            throw new SignatureException("Missing required parameters");
        }
        $expectedSign = md5($this->config['key'] . $timestamp);
        if ($expectedSign !== $sign) {
            throw new SignatureException("Invalid signature");
        }
        // 2. 时间戳验证
        $timestampDiff = $this->config['default_timestamp_diff'];
        $now = time();
        if (abs($now - $timestamp) > $timestampDiff) {
            throw new TimestampException("Timestamp difference too large");
        }
        // 3. 解密数据
        $decrypted = $this->decryptor->decrypt($en_data);
        return $decrypted;
    }
}
