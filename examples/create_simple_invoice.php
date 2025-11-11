<?php

require_once __DIR__ . '/../vendor/autoload.php';

use B2BRouter\B2BRouterClient;
use B2BRouter\Exception\ApiErrorException;

// Initialize client
$client = new B2BRouterClient($_ENV['B2B_API_KEY'] ?? 'your-api-key');
$accountId = $_ENV['B2B_ACCOUNT_ID'] ?? 'your-account-id';

try {
    // Create a simple invoice
    $invoice = $client->invoices->create($accountId, [
        'invoice' => [
            'number' => 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'currency' => 'EUR',
            'buyer' => [
                'name' => 'Acme Corporation',
                'tax_id' => 'ESB12345678',
                'country' => 'ES',
                'address' => [
                    'street' => 'Calle Mayor 1',
                    'city' => 'Madrid',
                    'postal_code' => '28001',
                    'country' => 'ES',
                ],
                'email' => 'billing@acme.com',
            ],
            'seller' => [
                'name' => 'Your Company SL',
                'tax_id' => 'ESA87654321',
                'country' => 'ES',
            ],
            'lines' => [
                [
                    'description' => 'Professional Services - January 2025',
                    'quantity' => 1,
                    'unit_price' => 1000.00,
                    'amount' => 1000.00,
                    'tax_rate' => 21.0,
                    'tax_amount' => 210.00,
                ]
            ],
            'total_before_tax' => 1000.00,
            'total_tax' => 210.00,
            'total_amount' => 1210.00,
        ],
        'send_after_import' => false
    ]);

    echo "✓ Invoice created successfully!\n";
    echo "  ID: {$invoice['id']}\n";
    echo "  Number: {$invoice['number']}\n";
    echo "  Total: {$invoice['total_amount']} {$invoice['currency']}\n";

} catch (ApiErrorException $e) {
    echo "✗ Error creating invoice:\n";
    echo "  {$e->getMessage()}\n";
    echo "  Status: {$e->getHttpStatus()}\n";

    if ($e->getJsonBody()) {
        echo "  Details: " . json_encode($e->getJsonBody(), JSON_PRETTY_PRINT) . "\n";
    }

    exit(1);
}
