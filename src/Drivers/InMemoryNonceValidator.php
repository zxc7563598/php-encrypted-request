<?php

declare(strict_types=1);

namespace Hejunjie\EncryptedRequest\Drivers;

use Hejunjie\EncryptedRequest\Contracts\NonceValidatorInterface;

/**
 * 内存 nonce 校验器 —— 默认实现。
 *
 * 注意：每次 PHP 请求结束数据即丢失，多进程/多服务器场景无效。
 * 生产环境应替换为 Redis / APCu / DB 等持久化实现。
 */
class InMemoryNonceValidator implements NonceValidatorInterface
{
    /**
     * @var array<string, int> nonce → 记录时间戳
     */
    private array $store = [];

    /**
     * 验证器
     *
     * @param string $nonce 随机码（crypto.randomUUID()）
     * @param integer $ttl 有效时间（秒）
     * 
     * @return boolean
     */
    public function verify(string $nonce, int $ttl): bool
    {
        $now = time();
        // 清理过期条目
        foreach ($this->store as $key => $recordedAt) {
            if ($now - $recordedAt > $ttl) {
                unset($this->store[$key]);
            }
        }
        // 检查是否重复使用
        if (isset($this->store[$nonce])) {
            return false;
        }
        $this->store[$nonce] = $now;
        return true;
    }
}
