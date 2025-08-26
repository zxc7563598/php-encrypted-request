# hejunjie/encrypted-request

<div align="center">
  <a href="./README.md">English</a>｜<a href="./README.zh-CN.md">简体中文</a>
  <hr width="50%"/>
</div>

PHP 请求加密处理工具包，用于快速实现安全的前后端通信。

在实际开发中，经常会遇到前后端请求有安全要求的情况：数据需要加密防止抓包，同时也要防止请求被篡改或重复发送。每次写接口还要和前端协调，加密方式和签名规则可能不一致。为了简化这一流程，我开发了这个 PHP 仓库，并顺便发布了一个配套的 npm 包，让前端只需调用一个方法即可在原本请求上生成加密请求参数，从而快速实现接口数据加密与安全通信。

前端配套 npm 包：[npm-encrypted-request](https://github.com/zxc7563598/npm-encrypted-request)

**本项目已经经由 Zread 解析完成，如果需要快速了解项目，可以点击此处进行查看：[了解本项目](https://zread.ai/zxc7563598/php-encrypted-request)**

## 功能特性

- 🔐 AES-128-CBC 解密前端加密数据，防止数据泄露
- ✍️ 动态 MD5 签名校验，防止伪造签名
- ⏰ 秒级时间戳验证，可自定义误差范围，防止劫持请求
- ⚙️ 支持通过 `.env` 或数组传入配置
- 🧩 可扩展自定义解密器

## 安装

```bash
composer require hejunjie/encrypted-request
```

## 配置示例

可以通过 `.env` 文件配置：

```dotenv
APP_KEY=your-app-key
DEFAULT_TIMESTAMP_DIFF=60

# 自定义解密器时可不传
AES_KEY=your-aes-key
AES_IV=your-aes-iv
```

也可以通过数组直接传入：

```php
$config = [
    'APP_KEY' => 'your-app-key', // 必传，签名密钥，用于接口签名校验（32位字母或数字）
    'DEFAULT_TIMESTAMP_DIFF' => 60, // 必传，用于验证请求是否过期，秒级
    'AES_KEY' => 'your-aes-key', // 非必传，AES 加密的密钥（16位），自定义解密器时可不传
    'AES_IV' => 'your-aes-iv' // 非必传，AES 加密的初始化向量（16位），自定义解密器时可不传
];
```

## 使用方法

### 基本示例

```php
use Hejunjie\EncryptedRequest\EncryptedRequestHandler;

$param = $_POST; // 自行获取前端请求的参数

$handler = new EncryptedRequestHandler();
try {
    $data = $handler->handle(
        $param['en_data'] ?? '',
        $param['timestamp'] ?? '',
        $param['sign'] ?? ''
    );
    // $data 为解密后的数组
} catch (\Hejunjie\EncryptedRequest\Exceptions\SignatureException $e) {
    echo "签名错误: " . $e->getMessage();
} catch (\Hejunjie\EncryptedRequest\Exceptions\TimestampException $e) {
    echo "时间戳错误: " . $e->getMessage();
} catch (\Hejunjie\EncryptedRequest\Exceptions\DecryptionException $e) {
    echo "解密错误: " . $e->getMessage();
}
```

### 自定义解密器

> 实际上，对于绝大多数场景，如果你的 AES_KEY、AES_IV 和 APP_KEY 保密，默认的 AES-128-CBC 解密已经足够满足接口数据加密的需求。

> 自定义解密器主要适用于特殊场景，比如需要使用完全不同的加密算法或对加密规则有更高安全要求。

> 如果你的项目对保密性有极高要求，建议自行设计和实现接口数据的加密规则，而不是使用公开方案。

```php
class MyCustomDecryptor implements \Hejunjie\EncryptedRequest\Contracts\DecryptorInterface
{
    /**
     * 解密方法
     *
     * @param string $data 加密数据
     *
     * @return array 解密后的数组
     * @throws \Hejunjie\EncryptedRequest\Exceptions\DecryptionException
     */
    public function decrypt(string $data): array
    {
        // 自定义解密逻辑
    }
}

$customDecryptor = new MyCustomDecryptor();
$handler = new EncryptedRequestHandler($customDecryptor);
```

## 前端配合

前端使用 [hejunjie-encrypted-request](https://github.com/zxc7563598/npm-encrypted-request) npm 包生成加密数据，并发送给 PHP 后端：

```typescript
import { encryptRequest } from "hejunjie-encrypted-request";

const encrypted = encryptRequest(
  { message: "Hello" },
  {
    appKey: "your-app-key",
    aesKey: "your-aes-key",
    aesIv: "your-aes-iv",
    token: "your-token",
  }
);
```

PHP 后端直接使用 `EncryptedRequestHandler` 解密即可。

## 注意事项

1. AES 密钥和向量必须为 16 位。
2. 时间戳单位为秒，`DEFAULT_TIMESTAMP_DIFF` 为允许的最大时间差；需要注意时区问题。
3. 前端 `appKey` / `aesKey` / `aesIv` 与后端保持一致，否则签名验证失败。
4. ​`token` 可选，会与密文一起传给 PHP 后端

## 兼容性

- PHP \>\= 8.1
- 支持任何遵循 PSR-4 自动加载的框架或原生 PHP 项目

## 开发与贡献

欢迎提交 Issue 或 Pull Request，贡献新解密器、优化功能或增加示例。
