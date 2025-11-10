<?php

require_once __DIR__ . '/../vendor/autoload.php';

use B2BRouter\B2BRouterClient;
use B2BRouter\Exception\ApiErrorException;

$client = new B2BRouterClient($_ENV['B2B_API_KEY'] ?? 'your-api-key');
$accountId = $_ENV['B2B_ACCOUNT_ID'] ?? 'your-account-id';

function step($message) {
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "STEP: {$message}\n";
    echo str_repeat('=', 60) . "\n";
}

try {
    step("1. Create Invoice");

    $invoice = $client->invoices->create($accountId, [
        'invoice' => [
            'number' => 'WORKFLOW-' . date('Ymd-His'),
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'currency' => 'EUR',
            'buyer' => [
                'name' => 'Demo Customer',
                'tax_id' => 'ESB11111111',
                'country' => 'ES',
                'email' => 'demo@example.com',
            ],
            'lines' => [
                [
                    'description' => 'Consulting Services',
                    'quantity' => 1,
                    'unit_price' => 500.00,
                    'amount' => 500.00,
                    'tax_rate' => 21.0,
                    'tax_amount' => 105.00,
                ]
            ],
            'total_before_tax' => 500.00,
            'total_tax' => 105.00,
            'total_amount' => 605.00,
        ]
    ]);

    $invoiceId = $invoice['id'];
    echo "✓ Invoice created: {$invoice['number']} (ID: {$invoiceId})\n";

    sleep(1);

    step("2. Retrieve Invoice");

    $retrieved = $client->invoices->retrieve($invoiceId);
    echo "✓ Retrieved invoice: {$retrieved['number']}\n";
    echo "  Status: {$retrieved['status']}\n";
    echo "  Amount: {$retrieved['total_amount']} {$retrieved['currency']}\n";

    sleep(1);

    step("3. Validate Invoice");

    $validation = $client->invoices->validate($invoiceId);
    echo "✓ Validation result: " . json_encode($validation, JSON_PRETTY_PRINT) . "\n";

    sleep(1);

    step("4. Update Invoice");

    $updated = $client->invoices->update($invoiceId, [
        'invoice' => [
            'notes' => 'Validated and ready to send - ' . date('Y-m-d H:i:s'),
        ]
    ]);
    echo "✓ Invoice updated\n";

    sleep(1);

    step("5. Mark Invoice as Sent");

    $marked = $client->invoices->markAs($invoiceId, [
        'status' => 'sent'
    ]);
    echo "✓ Invoice marked as sent\n";

    sleep(1);

    step("6. Send Invoice (Optional)");

    // Uncomment to actually send the invoice
    // $sent = $client->invoices->send($invoiceId);
    // echo "✓ Invoice sent to customer\n";
    echo "⚠ Skipped sending (uncomment to enable)\n";

    step("Workflow Complete!");

    echo "\nInvoice lifecycle completed successfully!\n";
    echo "Invoice ID: {$invoiceId}\n";
    echo "Invoice Number: {$invoice['number']}\n";

} catch (ApiErrorException $e) {
    echo "\n✗ API Error occurred:\n";
    echo "  Message: {$e->getMessage()}\n";
    echo "  Status: {$e->getHttpStatus()}\n";
    echo "  Request ID: {$e->getRequestId()}\n";

    if ($e->getJsonBody()) {
        echo "  Details: " . json_encode($e->getJsonBody(), JSON_PRETTY_PRINT) . "\n";
    }

    exit(1);
} catch (Exception $e) {
    echo "\n✗ Error: {$e->getMessage()}\n";
    exit(1);
}
