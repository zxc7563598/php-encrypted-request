<?php

declare(strict_types=1);

namespace Hejunjie\EncryptedRequest;

use Hejunjie\EncryptedRequest\Config\EnvConfigLoader;
use Hejunjie\EncryptedRequest\Contracts\NonceValidatorInterface;
use Hejunjie\EncryptedRequest\Drivers\AesDecryptor;
use Hejunjie\EncryptedRequest\Drivers\InMemoryNonceValidator;
use Hejunjie\EncryptedRequest\Drivers\RsaDecryptor;
use Hejunjie\EncryptedRequest\Exceptions\DecryptionException;
use Hejunjie\EncryptedRequest\Exceptions\NonceException;
use Hejunjie\EncryptedRequest\Exceptions\SignatureException;
use Hejunjie\EncryptedRequest\Exceptions\TimestampException;

class EncryptedRequestHandler
{
    private array $config;
    private NonceValidatorInterface $nonceValidator;
    private int $protocolVersion;

    /**
     * @param array|string                 $config          配置数组或 .env 路径
     * @param int                          $protocolVersion 协议版本号，默认 1
     * @param NonceValidatorInterface|null $nonceValidator  Nonce 校验器，默认内存实现（仅适合单进程/测试）
     */
    public function __construct(array|string $config = '', int $protocolVersion = 1, ?NonceValidatorInterface $nonceValidator = null)
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
            'rsa_private_key'        => $loader->get('RSA_PRIVATE_KEY', ''),
            'sign_secret'            => $loader->get('SIGN_SECRET', ''),
            'nonce_ttl'              => (int) $loader->get('NONCE_TTL', 300),
        ];

        $this->nonceValidator = $nonceValidator ?? new InMemoryNonceValidator();
        $this->protocolVersion = $protocolVersion;
    }

    /**
     * 验证请求并解密数据（协议 v2）。
     *
     * 流程：签名验证 → 时间戳 → RSA 解密 → Nonce 防重放 → AES-256-GCM 解密
     *
     * @param string $en_data      AES-GCM 加密的请求数据（Base64）
     * @param string $enc_payload  RSA 加密的 payload（含 AES 密钥 / IV / Nonce）
     * @param int    $timestamp    Unix 时间戳
     * @param string $sign         HMAC-SHA256 签名，覆盖 en_data + enc_payload + timestamp
     *
     * @return array 解密后的请求数据
     *
     * @throws SignatureException  签名无效
     * @throws TimestampException  时间戳偏差过大
     * @throws DecryptionException RSA/AES 解密失败
     * @throws NonceException      Nonce 已被使用（重放攻击）
     */
    public function handle(string $en_data, string $enc_payload, int $timestamp, string $sign): array
    {
        // 1. 签名验证（最先执行，无效请求直接拒绝）
        $signSecret = $this->config['sign_secret'];
        if ($signSecret === '') {
            throw new SignatureException("SIGN_SECRET 未配置");
        }
        $expectedSign = hash_hmac('sha256', $en_data . $enc_payload . $timestamp, $signSecret);
        if (!hash_equals($expectedSign, $sign)) {
            throw new SignatureException("签名验证失败");
        }
        // 2. 时间戳验证
        $timestampDiff = $this->config['default_timestamp_diff'];
        $now = time();
        if (abs($now - $timestamp) > $timestampDiff) {
            throw new TimestampException("请求时间戳偏差过大");
        }
        // 3. RSA 解密获取结构化 payload
        $rsa = new RsaDecryptor($this->config['rsa_private_key']);
        $decryptedPayload = $rsa->decrypt($enc_payload);
        $payload = json_decode($decryptedPayload, true);
        if (!is_array($payload) || ($payload['v'] ?? null) !== $this->protocolVersion) {
            throw new DecryptionException("Payload 格式无效或协议版本不支持");
        }
        $aesKey = base64_decode((string) ($payload['aes_key'] ?? ''), true);
        $aesIv  = base64_decode((string) ($payload['aes_iv'] ?? ''), true);
        $nonce  = (string) ($payload['nonce'] ?? '');
        if ($aesKey === false || $aesIv === false || $nonce === '') {
            throw new DecryptionException("Payload 不完整：缺少 aes_key、aes_iv 或 nonce");
        }
        // 4. Nonce 验证（防重放攻击）
        if (!$this->nonceValidator->verify($nonce, $this->config['nonce_ttl'])) {
            throw new NonceException("检测到重复请求（nonce 已被使用）");
        }
        // 5. AES-256-GCM 解密
        $aes = new AesDecryptor($aesKey, $aesIv);
        return $aes->decrypt($en_data);
    }
}
