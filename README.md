# hejunjie/encrypted-request

<div align="center">
  <a href="./README.md">English</a>｜<a href="./README.zh-CN.md">简体中文</a>
  <hr width="50%"/>
</div>

A PHP package for request encryption, designed for quickly implementing secure communication between frontend and backend.

In real-world development, there are often scenarios where requests need extra security: data must be encrypted to prevent sniffing, and requests should be protected against tampering or replay attacks. Coordinating encryption methods and signature rules with frontend teams each time can be cumbersome. To simplify this workflow, I created this PHP package and a companion npm package, allowing frontend developers to generate encrypted request parameters with a single function call, making it easy to implement secure API communication.

Frontend companion npm package: [npm-encrypted-request](https://github.com/zxc7563598/npm-encrypted-request)

**This project has been parsed by Zread. If you need a quick overview of the project, you can click here to view it：[Understand this project](https://zread.ai/zxc7563598/php-encrypted-request)**

## Features

- 🔐 AES-128-CBC decryption of frontend-encrypted data to prevent leaks
- ✍️ Dynamic MD5 signature verification to prevent forged signatures
- ⏰ Timestamp validation (in seconds) with configurable tolerance, to prevent request hijacking
- ⚙️ Configurable via `.env` file or array
- 🧩 Extensible with custom decryptors

## Installation

```bash
composer require hejunjie/encrypted-request
```

## Configuration Example

You can configure via `.env` file:

```dotenv
APP_KEY=your-app-key
DEFAULT_TIMESTAMP_DIFF=60

# Optional when using a custom decryptor
AES_KEY=your-aes-key
AES_IV=your-aes-iv
```

Or pass configuration as an array:

```php
$config = [
    'APP_KEY' => 'your-app-key', // Required: key used for signature verification (32 alphanumeric characters)
    'DEFAULT_TIMESTAMP_DIFF' => 60, // Required: maximum allowed timestamp difference in seconds
    'AES_KEY' => 'your-aes-key', // Optional: AES encryption key (16 characters); not needed for custom decryptor
    'AES_IV' => 'your-aes-iv' // Optional: AES initialization vector (16 characters); not needed for custom decryptor
];
```

## Usage

### Basic Example

```php
use Hejunjie\EncryptedRequest\EncryptedRequestHandler;

$param = $_POST; // Fetch frontend request parameters

$handler = new EncryptedRequestHandler();
try {
    $data = $handler->handle(
        $param['en_data'] ?? '',
        $param['timestamp'] ?? '',
        $param['sign'] ?? ''
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

### Custom Decryptor

> In most cases, if your `AES_KEY`, `AES_IV`, and `APP_KEY` remain confidential, the default AES-128-CBC decryption is sufficient for secure API communication.

> Custom decryptors are mainly for special scenarios, such as using a completely different encryption algorithm or requiring higher security standards.

> If your project demands extremely high confidentiality, it is recommended to design your own encryption rules rather than relying solely on the default AES implementation.

```php
class MyCustomDecryptor implements \Hejunjie\EncryptedRequest\Contracts\DecryptorInterface
{
    /**
     * Decrypt method
     *
     * @param string $data Encrypted data
     *
     * @return array Decrypted array
     * @throws \Hejunjie\EncryptedRequest\Exceptions\DecryptionException
     */
    public function decrypt(string $data): array
    {
        // Custom decryption logic
    }
}

$customDecryptor = new MyCustomDecryptor();
$handler = new EncryptedRequestHandler($customDecryptor);
```

## Frontend Integration

Frontend can use the [hejunjie-encrypted-request](https://github.com/zxc7563598/npm-encrypted-request) npm package to generate encrypted data and send it to the PHP backend:

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

The PHP backend can directly use `EncryptedRequestHandler` to decrypt.

## Notes

1. AES key and IV must be 16 characters.
2. Timestamp is in seconds; `DEFAULT_TIMESTAMP_DIFF` is the maximum allowed difference. Be mindful of time zones.
3. Frontend `appKey`, `aesKey`, and `aesIv` must match backend, otherwise signature verification will fail.
4. ​`token` is optional and will be passed together with encrypted data.

## Compatibility

- PHP \>\= 8.1
- Compatible with any PSR-4 autoloading framework or plain PHP project

## Development & Contribution

Feel free to submit issues or pull requests, contribute new decryptors, optimize functionality, or add examples.
