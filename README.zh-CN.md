# hejunjie/encrypted-request

[English](./README.md) ｜ 简体中文

PHP 请求加密工具包，用于快速实现前后端安全通信。

在实际开发中，前后端接口经常有安全要求：数据需要加密防抓包，请求需要防止篡改和重放攻击。每次写接口还要和前端协调加密方式与签名规则，流程繁琐。这个包配合[前端 npm 包](https://github.com/zxc7563598/npm-encrypted-request)，让前端调用一个方法即可生成加密请求参数，后端同样几行代码完成解密验证，快速实现安全的端到端通信。

前端配套 npm 包：[npm-encrypted-request](https://github.com/zxc7563598/npm-encrypted-request)

**本项目已由 Zread 解析，可点击快速了解：[了解本项目](https://zread.ai/zxc7563598/php-encrypted-request)**

## 特性

- 🔐 **混合加密**：AES-256-GCM 对称加密 + RSA OAEP 非对称加密，AES 密钥由前端随机生成并用 RSA 公钥加密传输，后端仅需配置 RSA 私钥
- ✍️ **HMAC-SHA256 签名校验**：防止请求参数被篡改
- ⏰ **时间戳验证**：秒级校验，可自定义容差范围，防止请求被挟持重放
- 🔑 **Nonce 防重放**：一次性随机数机制，阻止同一请求被重复使用
- ⚙️ **灵活配置**：支持 `.env` 文件或数组直接传入，也支持指定自定义 `.env` 路径
- 🧩 **可扩展**：Nonce 校验器支持注入自定义实现（如 Redis、APCu），满足生产环境需求

## 安装

```bash
composer require hejunjie/encrypted-request
```

## 快速开始

### 1. 配置

通过 `.env` 文件配置：

```dotenv
RSA_PRIVATE_KEY=your-private-key
SIGN_SECRET=your-sign-secret
DEFAULT_TIMESTAMP_DIFF=60
NONCE_TTL=300
```

也可以通过数组直接传入：

```php
$config = [
    'RSA_PRIVATE_KEY'      => 'your-private-key',   // RSA 私钥（包含 -----BEGIN PRIVATE KEY-----）
    'SIGN_SECRET'           => 'your-sign-secret',   // HMAC-SHA256 签名密钥
    'DEFAULT_TIMESTAMP_DIFF' => 60,                  // 时间戳容差（秒），默认 60
    'NONCE_TTL'             => 300,                  // Nonce 有效期（秒），默认 300
];
```

### 2. 解密请求

```php
use Hejunjie\EncryptedRequest\EncryptedRequestHandler;
use Hejunjie\EncryptedRequest\Contracts\NonceValidatorInterface;

$params = $_POST; // 自行获取前端请求参数

// EncryptedRequestHandler 构造函数：
//   __construct(array|string $config = '', int $protocolVersion = 1, ?NonceValidatorInterface $nonceValidator = null)
//
// - 第一个参数：配置数组、.env 文件路径、或不传（自动检测 .env）
// - 第二个参数：协议版本号，默认 1
// - 第三个参数：Nonce 校验器，不传则使用默认内存实现（仅适合测试环境！）

$handler = new EncryptedRequestHandler($config);  // 如果使用 .env 配置，无需传入第一个参数

try {
    $data = $handler->handle(
        $params['en_data'] ?? '',
        $params['enc_payload'] ?? '',
        (int)($params['timestamp'] ?? 0),
        $params['sign'] ?? ''
    );
    // $data 为解密后的数组
} catch (\Hejunjie\EncryptedRequest\Exceptions\SignatureException $e) {
    // 签名无效
} catch (\Hejunjie\EncryptedRequest\Exceptions\TimestampException $e) {
    // 时间戳偏差过大
} catch (\Hejunjie\EncryptedRequest\Exceptions\NonceException $e) {
    // Nonce 已被使用（重放攻击）
} catch (\Hejunjie\EncryptedRequest\Exceptions\DecryptionException $e) {
    // RSA 或 AES 解密失败
}
```

> [!WARNING]
> 不传第三个参数时，Nonce 校验器默认使用 `InMemoryNonceValidator`，它将数据保存在进程内存中，**PHP 请求结束即丢失**，无法真正防止重放攻击。**生产环境务必传入自定义 Nonce 校验器**，参考下方「自定义 Nonce 校验器」章节。

## 配置说明

| 配置项 | 类型 | 必需 | 默认值 | 说明 |
| --- | --- | --- | --- | --- |
| `RSA_PRIVATE_KEY` | string | ✅ | - | RSA 私钥，用于解密前端传来的 AES 密钥 |
| `SIGN_SECRET` | string | ✅ | - | HMAC-SHA256 签名密钥，前后端需保持一致 |
| `DEFAULT_TIMESTAMP_DIFF` | int | ❌ | `60` | 时间戳容差，单位秒 |
| `NONCE_TTL` | int | ❌ | `300` | Nonce 有效期，单位秒 |

## 工作流程

```
签名验证 → 时间戳校验 → RSA 解密获取 AES 密钥 → Nonce 防重放 → AES-256-GCM 解密
```

1. **签名验证**（最先执行）：使用 `SIGN_SECRET` 对 `en_data + enc_payload + timestamp` 进行 HMAC-SHA256 签名校验，无效请求直接拒绝
2. **时间戳校验**：检查请求时间戳与服务器时间的偏差是否在容差范围内
3. **RSA 解密**：用 RSA 私钥解密 `enc_payload`，获取 AES 密钥、IV 和 Nonce
4. **Nonce 验证**：检查 Nonce 是否已被使用，防止重放攻击
5. **AES-256-GCM 解密**：使用解密出的 AES 密钥和 IV，解密请求数据

## 前端配合

前端使用 [hejunjie-encrypted-request](https://github.com/zxc7563598/npm-encrypted-request) npm 包生成加密数据：

```typescript
import { encryptRequest, EncryptOptions } from "hejunjie-encrypted-request";

const options: EncryptOptions = {
    data: { message: "Hello" },
    rsaPublicKey: pubKey,
    signSecret: signSecret,
};

const payload = await encryptRequest(options, version);
```

PHP 后端直接使用 `EncryptedRequestHandler` 解密即可。

## 自定义 Nonce 校验器

默认 `InMemoryNonceValidator` 将 Nonce 存储在进程内存中，**仅适合单进程或测试环境**。生产环境需要自行实现 `NonceValidatorInterface` 接口（如 Redis、APCu、数据库），并通过 `EncryptedRequestHandler` 构造函数的**第三个参数**传入：

```php
use Hejunjie\EncryptedRequest\EncryptedRequestHandler;
use Hejunjie\EncryptedRequest\Contracts\NonceValidatorInterface;

// 1. 实现 NonceValidatorInterface 接口
class RedisNonceValidator implements NonceValidatorInterface
{
    private \Redis $redis;

    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    public function verify(string $nonce, int $ttl): bool
    {
        // 使用 SET NX EX 原子操作，同时完成"检查是否存在"和"写入"
        return $this->redis->set("nonce:{$nonce}", 1, ['nx', 'ex' => $ttl]) === true;
    }
}

// 2. 作为第三个参数注入（使用命名参数）
$handler = new EncryptedRequestHandler(
    $config,                                    // 第一个参数：配置
    nonceValidator: new RedisNonceValidator($redis)  // 第三个参数：自定义 Nonce 校验器
);
```

## 目录结构

```
src/
├── Config/
│   └── EnvConfigLoader.php          # 配置加载器（.env + 数组）
├── Contracts/
│   ├── DecryptorInterface.php       # 解密器接口
│   └── NonceValidatorInterface.php  # Nonce 校验器接口
├── Drivers/
│   ├── AesDecryptor.php             # AES-256-GCM 解密器
│   ├── InMemoryNonceValidator.php   # 内存 Nonce 校验器（默认，仅测试用）
│   └── RsaDecryptor.php             # RSA-OAEP 解密器
├── Exceptions/
│   ├── DecryptionException.php      # 解密异常
│   ├── NonceException.php           # Nonce 异常（重放攻击）
│   ├── SignatureException.php       # 签名异常
│   └── TimestampException.php       # 时间戳异常
└── EncryptedRequestHandler.php      # 核心处理器
```

## 兼容性

- PHP >= 8.0
- 需要 PHP OpenSSL 扩展
- 支持任何遵循 PSR-4 自动加载的框架或原生 PHP 项目

## 常见问题

### 默认 Nonce 校验器适合生产环境吗？

不适合。`InMemoryNonceValidator` 将数据存在进程内存中，PHP 请求结束即丢失，无法真正防重放。生产环境请注入 Redis 或数据库实现，参考上方「自定义 Nonce 校验器」章节。

### 前后端签名密钥如何保持一致？

`SIGN_SECRET` 配置项的值需要和前端 `encryptRequest()` 的 `signSecret` 参数完全一致。建议将密钥放在 `.env` 中并通过安全渠道同步给前端。

### 支持哪些 PHP 框架？

任何遵循 PSR-4 自动加载标准的项目均可使用，包括但不限于 Laravel、Symfony、Slim、ThinkPHP，以及原生 PHP 项目。

## 参与贡献

欢迎提交 Issue 或 Pull Request。
