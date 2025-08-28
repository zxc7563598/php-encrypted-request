# hejunjie/encrypted-request

<div align="center">
  <a href="./README.md">English</a>｜<a href="./README.zh-CN.md">简体中文</a>
  <hr width="50%"/>
</div>

A PHP toolkit for handling encrypted requests, enabling fast and secure front-end to back-end communication.

In real-world development, you often encounter scenarios where requests need to be secure: data must be encrypted to prevent sniffing, and requests must be protected from tampering or replay attacks. Coordinating encryption methods and signature rules with the front-end can be cumbersome. This PHP package simplifies the process. Paired with a dedicated npm package, the front-end can generate encrypted request parameters with a single call, enabling secure and fast data transmission.

Front-end companion npm package: [npm-encrypted-request](https://github.com/zxc7563598/npm-encrypted-request)

**This project has been parsed by Zread. To quickly understand it, you can click here:** **[Learn More](https://zread.ai/zxc7563598/php-encrypted-request)**

## Features

- ♾️ **Hybrid encryption**: AES key is randomly generated, no need for front-end to store a fixed key, improving security
- 🔐 **AES-128-CBC decryption**: Securely decrypt front-end encrypted data, back-end only needs to configure the RSA private key
- ✍️ **Dynamic MD5 signature verification**: Prevents forged requests
- ⏰ **Second-level timestamp validation**: Customizable tolerance to prevent request hijacking
- ⚙️ **Flexible configuration**: Use `.env` or pass an array directly
- 🧠 **Minimal code changes required**: Front-end can securely send data without worrying about the underlying logic

## Installation

```bash
composer require hejunjie/encrypted-request
```

## Configuration Example

You can configure via `.env`:

```dotenv
RSA_PRIVATE_KEY=your-private-key
DEFAULT_TIMESTAMP_DIFF=60
```

Or pass an array directly:

```php
$config = [
    'RSA_PRIVATE_KEY' => 'your-private-key', // Private key string (including -----BEGIN PRIVATE KEY-----)
    'DEFAULT_TIMESTAMP_DIFF' => 60, // Optional, used to validate request expiry in seconds, default is 60
];
```

## Usage

### Basic Example

```php
use Hejunjie\EncryptedRequest\EncryptedRequestHandler;

$params = $_POST; // Obtain front-end request parameters

$config = ['RSA_PRIVATE_KEY' => 'your-private-key']; // Not needed if using .env

$handler = new EncryptedRequestHandler($config);
try {
    $data = $handler->handle(
        $params['en_data'] ?? '',
        $params['enc_payload'] ?? '',
        $params['timestamp'] ?? '',
        $params['sign'] ?? ''
    );
    // $data contains the decrypted array
} catch (\Hejunjie\EncryptedRequest\Exceptions\SignatureException $e) {
    echo "Signature error: " . $e->getMessage();
} catch (\Hejunjie\EncryptedRequest\Exceptions\TimestampException $e) {
    echo "Timestamp error: " . $e->getMessage();
} catch (\Hejunjie\EncryptedRequest\Exceptions\DecryptionException $e) {
    echo "Decryption error: " . $e->getMessage();
}
```

## Front-end Integration

The front-end uses the [hejunjie-encrypted-request](https://github.com/zxc7563598/npm-encrypted-request) npm package to generate encrypted data and send it to the PHP back-end:

```typescript
import { encryptRequest } from "hejunjie-encrypted-request";

const encrypted = encryptRequest(
  { message: "Hello" },
  {
    rsaPubKey: "your-public-key",
  }
);
```

The PHP back-end can directly decrypt using `EncryptedRequestHandler`.

## Compatibility

- PHP \>\= 8.1
- Works with any PSR-4 autoloading framework or plain PHP project

## Development & Contribution

Contributions are welcome! Submit issues or pull requests to add new decoders, optimize features, or provide examples.
