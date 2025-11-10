<?php

require_once __DIR__ . '/../vendor/autoload.php';

use B2BRouter\B2BRouterClient;

$client = new B2BRouterClient($_ENV['B2B_API_KEY'] ?? 'your-api-key');
$accountId = $_ENV['B2B_ACCOUNT_ID'] ?? 'your-account-id';

// Define invoice line items
$items = [
    [
        'description' => 'Web Development - 40 hours',
        'quantity' => 40,
        'unit_price' => 75.00,
        'tax_rate' => 21.0,
    ],
    [
        'description' => 'UI/UX Design - 20 hours',
        'quantity' => 20,
        'unit_price' => 85.00,
        'tax_rate' => 21.0,
    ],
    [
        'description' => 'Project Management - 10 hours',
        'quantity' => 10,
        'unit_price' => 95.00,
        'tax_rate' => 21.0,
    ],
];

// Calculate totals
$lines = [];
$subtotal = 0;
$totalTax = 0;

foreach ($items as $item) {
    $amount = $item['quantity'] * $item['unit_price'];
    $taxAmount = $amount * ($item['tax_rate'] / 100);

    $lines[] = [
        'description' => $item['description'],
        'quantity' => $item['quantity'],
        'unit_price' => $item['unit_price'],
        'amount' => $amount,
        'tax_rate' => $item['tax_rate'],
        'tax_amount' => $taxAmount,
    ];

    $subtotal += $amount;
    $totalTax += $taxAmount;
}

$total = $subtotal + $totalTax;

try {
    $invoice = $client->invoices->create($accountId, [
        'invoice' => [
            'number' => 'INV-2025-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'currency' => 'EUR',
            'buyer' => [
                'name' => 'Tech Startup Inc',
                'tax_id' => 'ESB98765432',
                'country' => 'ES',
                'email' => 'accounts@techstartup.com',
            ],
            'lines' => $lines,
            'total_before_tax' => $subtotal,
            'total_tax' => $totalTax,
            'total_amount' => $total,
            'notes' => 'Payment terms: 30 days net. Bank transfer preferred.',
        ]
    ]);

    echo "✓ Detailed invoice created!\n";
    echo "  Invoice: {$invoice['number']}\n";
    echo "  Items: " . count($lines) . "\n";
    echo "  Subtotal: €" . number_format($subtotal, 2) . "\n";
    echo "  Tax (21%): €" . number_format($totalTax, 2) . "\n";
    echo "  Total: €" . number_format($total, 2) . "\n";

} catch (Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
    exit(1);
}
