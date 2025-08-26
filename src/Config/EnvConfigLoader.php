<?php

namespace Hejunjie\EncryptedRequest\Config;

use Dotenv\Dotenv;

class EnvConfigLoader
{
    private array $config = [];

    /**
     * 构造方法
     *
     * @param array $config 传入数组配置，会覆盖 .env 配置
     * @param string $envPath .env 文件所在目录，默认为项目根目录
     */
    public function __construct(array $config = [], string $envPath = '')
    {
        // 加载 .env 文件
        $path = $envPath ?: $this->detectProjectRoot(); // 优先使用传入路径，否则默认当前工作目录
        if (file_exists($path . '/.env')) {
            $dotenv = Dotenv::createImmutable($path);
            $dotenv->load();
        }
        // 将 $_ENV 中的配置转成 array
        foreach ($_ENV as $key => $value) {
            $this->config[$key] = $value;
        }
        // 覆盖传入的配置
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 获取配置
     *
     * @param string $key 配置键名
     * @param mixed $default 默认值
     * 
     * @return mixed
     */
    public function get(string $key, $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * 设置配置值
     *
     * @param string $key 配置键名
     * @param mixed $value 配置值
     * 
     * @return void
     */
    public function set(string $key, $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * 检测项目根目录
     * 
     * @return string 
     */
    private function detectProjectRoot(): string
    {
        $basePath = getcwd(); // 从当前工作目录开始
        while ($basePath !== dirname($basePath)) {
            if (is_dir("$basePath/vendor") && is_file("$basePath/composer.json")) {
                return $basePath;
            }
            $basePath = dirname($basePath);
        }
        // 如果遍历到根目录还没找到，则 fallback 到当前包目录上几级
        return realpath(__DIR__ . '/../../../../../') ?: __DIR__;
    }
}
