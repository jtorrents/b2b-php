# B2BRouter PHP SDK

Official PHP SDK for the B2BRouter API, inspired by Stripe's PHP SDK architecture.

## Features

- **Simple and intuitive API** - Clean, modern PHP interface
- **Automatic authentication** - API key management built-in
- **Pagination support** - Easy iteration through large result sets
- **Error handling** - Comprehensive exception hierarchy
- **Automatic retries** - Network failures handled automatically
- **Type safety** - Full PHP 7.4+ support with type hints

## Requirements

- PHP 7.4 or higher
- cURL extension
- JSON extension
- mbstring extension

## Installation

Install via Composer:

```bash
composer require b2brouter/b2brouter-php
```

## Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use B2BRouter\B2BRouterClient;

// Initialize the client
$client = new B2BRouterClient('your-api-key-here');

// Create an invoice
$invoice = $client->invoices->create('your-account-id', [
    'invoice' => [
        'number' => 'INV-2025-001',
        'issue_date' => '2025-01-15',
        'due_date' => '2025-02-15',
        'currency' => 'EUR',
        'total_amount' => 1000.00,
        'buyer' => [
            'name' => 'Acme Corporation',
            'tax_id' => 'B12345678',
            'country' => 'ES',
        ],
        'lines' => [
            [
                'description' => 'Professional Services',
                'quantity' => 10,
                'unit_price' => 100.00,
                'amount' => 1000.00,
            ]
        ]
    ]
]);

echo "Invoice created: {$invoice['id']}\n";
```

## Configuration

The client accepts several configuration options:

```php
$client = new B2BRouterClient('your-api-key', [
    'api_base' => 'https://api.b2brouter.net',  // API base URL (default: staging)
    'api_version' => '2025-10-13',              // API version
    'timeout' => 80,                             // Request timeout in seconds
    'max_retries' => 3,                          // Maximum retry attempts
]);
```

## Invoice Operations

### Create an Invoice

```php
$invoice = $client->invoices->create('account-id', [
    'invoice' => [
        'number' => 'INV-2025-001',
        'issue_date' => '2025-01-15',
        'due_date' => '2025-02-15',
        'currency' => 'EUR',
        'total_amount' => 1000.00,
        // ... more fields
    ],
    'send_after_import' => false
]);
```

### Retrieve an Invoice

```php
$invoice = $client->invoices->retrieve('invoice-id');
```

### Update an Invoice

```php
$invoice = $client->invoices->update('invoice-id', [
    'invoice' => [
        'notes' => 'Payment terms: 30 days net'
    ]
]);
```

### Delete an Invoice

```php
$invoice = $client->invoices->delete('invoice-id');
```

### List Invoices

```php
$invoices = $client->invoices->all('account-id', [
    'limit' => 10,
    'offset' => 0,
    'date_from' => '2025-01-01',
    'date_to' => '2025-12-31',
]);

// Iterate through results
foreach ($invoices as $invoice) {
    echo "Invoice: {$invoice['number']}\n";
}

// Check pagination info
echo "Total invoices: {$invoices->getTotal()}\n";
echo "Has more: " . ($invoices->hasMore() ? 'yes' : 'no') . "\n";
```

### Paginate Through All Invoices

```php
$offset = 0;
$limit = 25;
$allInvoices = [];

do {
    $page = $client->invoices->all('account-id', [
        'limit' => $limit,
        'offset' => $offset,
    ]);

    foreach ($page as $invoice) {
        $allInvoices[] = $invoice;
    }

    $offset += $limit;
} while ($page->hasMore());
```

### Additional Invoice Operations

```php
// Validate an invoice
$validation = $client->invoices->validate('invoice-id');

// Send an invoice
$result = $client->invoices->send('invoice-id');

// Mark invoice status
$invoice = $client->invoices->markAs('invoice-id', [
    'status' => 'sent'
]);

// Acknowledge an invoice
$result = $client->invoices->acknowledge('invoice-id', [
    'ack' => true
]);

// Import an invoice
$invoice = $client->invoices->import('account-id', [
    // import parameters
]);
```

## Error Handling

The SDK provides specific exception types for different error scenarios:

```php
use B2BRouter\Exception\ApiErrorException;
use B2BRouter\Exception\AuthenticationException;
use B2BRouter\Exception\PermissionException;
use B2BRouter\Exception\ResourceNotFoundException;
use B2BRouter\Exception\InvalidRequestException;
use B2BRouter\Exception\ApiConnectionException;

try {
    $invoice = $client->invoices->retrieve('invoice-id');
} catch (AuthenticationException $e) {
    // Handle authentication errors (401)
    echo "Authentication failed: {$e->getMessage()}\n";
} catch (PermissionException $e) {
    // Handle permission errors (403)
    echo "Permission denied: {$e->getMessage()}\n";
} catch (ResourceNotFoundException $e) {
    // Handle not found errors (404)
    echo "Invoice not found: {$e->getMessage()}\n";
} catch (InvalidRequestException $e) {
    // Handle invalid request errors (400, 422)
    echo "Invalid request: {$e->getMessage()}\n";
    print_r($e->getJsonBody());
} catch (ApiConnectionException $e) {
    // Handle network errors
    echo "Connection error: {$e->getMessage()}\n";
} catch (ApiErrorException $e) {
    // Handle all other API errors
    echo "API error: {$e->getMessage()}\n";
    echo "HTTP Status: {$e->getHttpStatus()}\n";
    echo "Request ID: {$e->getRequestId()}\n";
}
```

## Automatic Retries

The SDK automatically retries failed requests due to network issues with exponential backoff. You can configure the retry behavior:

```php
$client = new B2BRouterClient('your-api-key', [
    'max_retries' => 3  // Retry up to 3 times (default)
]);
```

## Examples

See the `examples/` directory for complete working examples:

- `examples/invoices.php` - Complete invoice CRUD operations

## API Documentation

For complete API documentation, visit: https://developer.b2brouter.net/v2025-10-13/reference

## Support

- **API Documentation**: https://developer.b2brouter.net
- **Email**: servicedelivery@b2brouter.net

## License

MIT License

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
