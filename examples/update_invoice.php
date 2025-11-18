<?php

require_once __DIR__ . '/bootstrap.php';

use B2BRouter\B2BRouterClient;

$client = new B2BRouterClient($_ENV['B2B_API_KEY'] ?? 'your-api-key');
$invoiceId = $argv[1] ?? null;

if (!$invoiceId) {
    echo "Usage: php update_invoice.php <invoice_id>\n";
    exit(1);
}

try {
    // Retrieve existing invoice
    echo "Fetching invoice {$invoiceId}...\n";
    $invoice = $client->invoices->retrieve($invoiceId);

    echo "Current status: {$invoice['status']}\n";
    echo "Current notes: " . ($invoice['notes'] ?? 'None') . "\n\n";

    // Update invoice
    echo "Updating invoice...\n";
    $updated = $client->invoices->update($invoiceId, [
        'invoice' => [
            'notes' => 'Updated on ' . date('Y-m-d H:i:s') . ' - Payment reminder sent',
            'metadata' => [
                'reminder_sent' => true,
                'reminder_date' => date('c'),
            ],
        ]
    ]);

    echo "✓ Invoice updated successfully!\n";
    echo "  New notes: {$updated['notes']}\n";

} catch (Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
    exit(1);
}
