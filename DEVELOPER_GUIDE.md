# Developer Guide - B2BRouter PHP SDK

Quick guide for setting up and working with the B2BRouter PHP SDK.

## Table of Contents

- [Quick Setup](#quick-setup)
- [Neovim PHP Setup](#neovim-php-setup)
- [Running Tests](#running-tests)
- [Working with Examples](#working-with-examples)
- [Best Practices](#best-practices)

---

## Quick Setup

### 1. Install PHP and Dependencies

```bash
# Install PHP and required extensions (Debian/Ubuntu)
sudo apt update
sudo apt install -y \
    php-cli \
    php-curl \
    php-mbstring \
    php-xml \
    composer

# Verify installation
php --version
composer --version
```

### 2. Clone and Setup

```bash
# Clone the repository
git clone https://github.com/yourusername/b2b-php.git
cd b2b-php

# Install dependencies
composer install

# Run tests to verify setup
vendor/bin/phpunit
```

### 3. Configure Environment

Create a `.env` file for your API credentials:

```bash
# .env (never commit this file!)
B2B_API_KEY=your_api_key_here
B2B_ACCOUNT_ID=your_account_id
B2B_API_BASE=https://api-staging.b2brouter.net
```

---

## Neovim PHP Setup

This section assumes you already have Neovim installed and configured with a plugin manager (lazy.nvim, packer, etc.).

### Install PHP Language Server

Use Mason (if you have it) or install Intelephense manually:

```vim
:Mason
```

Search for and install:
- `intelephense` (PHP language server)

### Manual Intelephense Installation

If you don't use Mason:

```bash
npm install -g intelephense
```

### LSP Configuration

Add to your Neovim LSP config:

```lua
-- In your LSP setup file
require('lspconfig').intelephense.setup({
  settings = {
    intelephense = {
      files = {
        maxSize = 5000000,
      },
      environment = {
        phpVersion = "8.2.0",
      },
    },
  },
})
```

### PHP-Specific Keybindings (Optional)

Add PHP-specific shortcuts:

```lua
-- Add to your config
vim.api.nvim_create_autocmd("FileType", {
  pattern = "php",
  callback = function()
    local opts = { buffer = true, noremap = true, silent = true }

    -- Run tests
    vim.keymap.set("n", "<leader>t", ":!vendor/bin/phpunit<CR>", opts)
    vim.keymap.set("n", "<leader>tf", ":!vendor/bin/phpunit %<CR>", opts)

    -- Syntax check
    vim.keymap.set("n", "<leader>ps", ":!php -l %<CR>", opts)

    -- Composer
    vim.keymap.set("n", "<leader>ci", ":!composer install<CR>", opts)
  end,
})
```

---

## Running Tests

### Basic Usage

```bash
# Run all tests
vendor/bin/phpunit

# Run with detailed output
vendor/bin/phpunit --testdox

# Run specific test file
vendor/bin/phpunit tests/Unit/InvoiceServiceTest.php

# Run specific test method
vendor/bin/phpunit --filter testCreateInvoice

# Generate coverage report (requires xdebug)
vendor/bin/phpunit --coverage-html coverage
```

### Test Structure

```
tests/
├── Unit/              # Unit tests
│   ├── InvoiceServiceTest.php
│   ├── CollectionTest.php
│   └── ExceptionTest.php
├── Mock/              # Mock objects
│   └── MockHttpClient.php
└── TestCase.php       # Base test class
```

---

## Working with Examples

All examples are in the `examples/` directory.

### Run Examples

```bash
# Set environment variables
export B2B_API_KEY="your_api_key"
export B2B_ACCOUNT_ID="your_account_id"

# Run examples
php examples/invoices.php
php examples/create_simple_invoice.php
php examples/list_invoices.php
```

### Available Examples

- `invoices.php` - Complete CRUD operations demo
- `create_simple_invoice.php` - Simple invoice creation
- `create_detailed_invoice.php` - Multi-line invoice
- `list_invoices.php` - List and filter invoices
- `paginate_all_invoices.php` - Pagination example
- `update_invoice.php` - Update existing invoice
- `invoice_workflow.php` - Complete workflow from creation to sending

---

## Best Practices

### 1. Error Handling

Always catch specific exceptions:

```php
use B2BRouter\Exception\{
    AuthenticationException,
    InvalidRequestException,
    ResourceNotFoundException,
    ApiErrorException
};

try {
    $invoice = $client->invoices->create($accountId, $params);
} catch (InvalidRequestException $e) {
    // Handle validation errors
    $errors = $e->getJsonBody()['errors'] ?? [];
    // Log or display errors
} catch (AuthenticationException $e) {
    // Handle auth errors
} catch (ResourceNotFoundException $e) {
    // Handle 404 errors
} catch (ApiErrorException $e) {
    // Handle other API errors
    error_log("API Error: {$e->getMessage()}, Status: {$e->getHttpStatus()}");
}
```

### 2. Use Idempotency Keys

For create operations:

```php
$invoice = $client->invoices->create($accountId, $params, [
    'idempotency_key' => 'invoice_' . $userId . '_' . time()
]);
```

### 3. Pagination

Handle large datasets properly:

```php
$offset = 0;
$limit = 100;

do {
    $page = $client->invoices->all($accountId, [
        'limit' => $limit,
        'offset' => $offset
    ]);

    foreach ($page as $invoice) {
        processInvoice($invoice);
    }

    $offset += $limit;
} while ($page->hasMore());
```

### 4. Environment-Specific Config

```php
$config = [
    'development' => [
        'api_base' => 'https://api-staging.b2brouter.net',
        'api_key' => getenv('B2B_DEV_API_KEY'),
    ],
    'production' => [
        'api_base' => 'https://api.b2brouter.net',
        'api_key' => getenv('B2B_PROD_API_KEY'),
    ],
];

$env = getenv('APP_ENV') ?: 'development';
$client = new B2BRouterClient($config[$env]['api_key'], $config[$env]);
```

### 5. Logging Requests (Debug)

Wrap HTTP client for debugging:

```php
class LoggingHttpClient implements ClientInterface
{
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function request($method, $url, $headers, $body, $timeout)
    {
        error_log("API Request: {$method} {$url}");
        $response = $this->client->request($method, $url, $headers, $body, $timeout);
        error_log("API Response: {$response['status']}");
        return $response;
    }
}

// Use it
$httpClient = new LoggingHttpClient(new CurlClient());
$client = new B2BRouterClient('api-key', ['http_client' => $httpClient]);
```

---

## Troubleshooting

### Common Issues

**PHPUnit not found**
```bash
# Make sure dependencies are installed
composer install

# Or use full path
./vendor/bin/phpunit
```

**SSL Certificate errors**
```bash
# Install ca-certificates
sudo apt install ca-certificates

# Or disable SSL verification (not recommended for production)
# Add to php.ini: curl.cainfo=/etc/ssl/certs/ca-certificates.crt
```

**Composer out of memory**
```bash
php -d memory_limit=-1 $(which composer) install
```

---

## Quick Command Reference

```bash
# PHP
php --version                      # Check PHP version
php -m                             # List extensions
php -l file.php                    # Check syntax

# Composer
composer install                   # Install dependencies
composer update                    # Update dependencies
composer dump-autoload             # Regenerate autoloader

# PHPUnit
vendor/bin/phpunit                 # Run all tests
vendor/bin/phpunit --testdox       # Detailed output
vendor/bin/phpunit --filter name   # Run specific test

# Git
git status                         # Check status
git add .                          # Stage changes
git commit -m "message"            # Commit
git push                           # Push to remote
```

---

## Additional Resources

- [B2BRouter API Documentation](https://developer.b2brouter.net)
- [PHP Documentation](https://www.php.net/docs.php)
- [Composer Documentation](https://getcomposer.org/doc/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

---

For issues or questions, contact: servicedelivery@b2brouter.net
