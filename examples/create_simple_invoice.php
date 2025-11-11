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
            'date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'currency' => 'EUR',
            'contact' => [
                'name' => 'Acme Corporation',
                'tin_value' => 'ESB12345678',
                'country' => 'ES',
                'address' => 'Calle Mayor 1',
                'city' => 'Madrid',
                'postalcode' => '28001',
                'email' => 'billing@acme.com',
            ],
            'invoice_lines_attributes' => [
                [
                    'description' => 'Professional Services - January 2025',
                    'quantity' => 1,
                    'price' => 1000.00,
                    'taxes_attributes' => [
                        [
                            'name' => 'IVA',
                            'category' => 'S',
                            'percent' => 21.0,
                        ]
                    ]
                ]
            ],
        ],
        'send_after_import' => false
    ]);

    echo "✓ Invoice created successfully!\n";
    echo "  ID: {$invoice['id']}\n";
    echo "  Number: {$invoice['number']}\n";
    echo "  Subtotal: €{$invoice['subtotal']}\n";
    echo "  Total: €{$invoice['total']} {$invoice['currency']}\n";
    echo "  State: {$invoice['state']}\n";

} catch (ApiErrorException $e) {
    echo "✗ Error creating invoice:\n";
    echo "  {$e->getMessage()}\n";
    echo "  Status: {$e->getHttpStatus()}\n";

    if ($e->getJsonBody()) {
        echo "  Details: " . json_encode($e->getJsonBody(), JSON_PRETTY_PRINT) . "\n";
    }

    exit(1);
}
