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

- ♾️ 混合加密，AES 密钥随机生成，前端不需要存储固定密钥，提高安全性
- 🔐 AES-128-CBC 解密前端加密数据，防止数据泄露，后端只需配置 RSA 私钥即可
- ✍️ 动态 MD5 签名校验，防止伪造签名
- ⏰ 秒级时间戳验证，可自定义误差范围，防止劫持请求
- ⚙️ 支持通过 `.env` 或数组传入配置
- 🧠 对代码改动极小，配套 npm 无需关注原理即可安全传输数据

## 安装

```bash
composer require hejunjie/encrypted-request
```

## 配置示例

可以通过 `.env` 文件配置：

```dotenv
RSA_PRIVATE_KEY=your-private-key
DEFAULT_TIMESTAMP_DIFF=60
```

也可以通过数组直接传入：

```php
$config = [
    'RSA_PRIVATE_KEY' => 'your-private-key', // 私钥字符串（包含 -----BEGIN PRIVATE KEY-----）
    'DEFAULT_TIMESTAMP_DIFF' => 60, // 非必传，用于验证请求是否过期，秒级，默认60
];
```

## 使用方法

### 基本示例

```php
use Hejunjie\EncryptedRequest\EncryptedRequestHandler;

$param = $_POST; // 自行获取前端请求的参数

$config = ['RSA_PRIVATE_KEY' => 'your-private-key']; // 如果通过.env配置则无需在此处传递

$handler = new EncryptedRequestHandler($config);
try {
    $data = $handler->handle(
        $param['en_data'] ?? '',
        $param['enc_payload'] ?? '',
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

## 前端配合

前端使用 [hejunjie-encrypted-request](https://github.com/zxc7563598/npm-encrypted-request) npm 包生成加密数据，并发送给 PHP 后端：

```typescript
import { encryptRequest } from "hejunjie-encrypted-request";

const encrypted = encryptRequest(
  { message: "Hello" },
  {
    rsaPubKey: "your-public-key",
  }
);
```

PHP 后端直接使用 `EncryptedRequestHandler` 解密即可。

## 兼容性

- PHP \>\= 8.1
- 支持任何遵循 PSR-4 自动加载的框架或原生 PHP 项目

## 开发与贡献

欢迎提交 Issue 或 Pull Request，贡献新解密器、优化功能或增加示例。
