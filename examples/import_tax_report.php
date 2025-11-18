<?php
/**
 * Import Tax Report from XML Example
 *
 * This example demonstrates how to import tax reports from XML files.
 * This is useful if your system can already generate VeriFactu or TicketBAI XML.
 *
 * Important notes:
 * - For VeriFactu: Import RegistroAlta or RegistroAnulacion from SuministroInformacion namespace
 * - Chaining information in the XML (fingerprint, RegistroAnterior) will be ignored
 * - B2BRouter performs its own chaining to ensure data integrity
 * - The QR code and identifier will be generated after import
 *
 * Setup:
 *   1. Copy .env.example to .env
 *   2. Add your B2B_API_KEY and B2B_ACCOUNT_ID
 *   3. Run: php examples/import_tax_report.php
 */

require_once __DIR__ . '/bootstrap.php';

use B2BRouter\B2BRouterClient;
use B2BRouter\Exception\ApiErrorException;

// Check required environment variables
checkRequiredEnv();

// Initialize the client
$client = new B2BRouterClient(env('B2B_API_KEY'), [
    'api_version' => env('B2B_API_VERSION', '2025-10-13'),
    'api_base' => env('B2B_API_BASE'),
]);

$accountId = env('B2B_ACCOUNT_ID');

try {
    echo "=== Import Tax Report from XML Example ===\n\n";

    // ============================================
    // Example 1: Import VeriFactu XML
    // ============================================
    echo "Example 1: Importing VeriFactu XML...\n\n";

    // Example VeriFactu XML (RegistroAlta)
    $verifactuXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<RegistroAlta xmlns="https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd">
    <IDFactura>
        <FechaExpedicionFactura>15-04-2025</FechaExpedicionFactura>
        <NumSerieFactura>2025-VF-XML-001</NumSerieFactura>
    </IDFactura>
    <NombreRazonEmisor>Mi Empresa S.L.</NombreRazonEmisor>
    <TipoFactura>F1</TipoFactura>
    <DescripcionOperacion>Imported from XML - Professional services</DescripcionOperacion>
    <FacturaSimplificadaArt7273>N</FacturaSimplificadaArt7273>
    <FacturaSinIdentifDestinatarioArt61d>N</FacturaSinIdentifDestinatarioArt61d>
    <Macrodato>N</Macrodato>
    <EmitidaPorTerceroODestinatario>N</EmitidaPorTerceroODestinatario>
    <Destinatarios>
        <IDDestinatario>
            <NombreRazon>Cliente XML S.L.</NombreRazon>
            <NIF>B12345678</NIF>
        </IDDestinatario>
    </Destinatarios>
    <Desglose>
        <DetalleDesglose>
            <Impuesto>01</Impuesto>
            <ClaveRegimen>01</ClaveRegimen>
            <CalificacionOperacion>S1</CalificacionOperacion>
            <TipoImpositivo>21.00</TipoImpositivo>
            <BaseImponibleOimporteNoSujeto>100.00</BaseImponibleOimporteNoSujeto>
            <CuotaRepercutida>21.00</CuotaRepercutida>
        </DetalleDesglose>
    </Desglose>
    <CuotaTotal>21.00</CuotaTotal>
    <ImporteTotal>121.00</ImporteTotal>
</RegistroAlta>
XML;

    try {
        $importedReport = $client->taxReports->import($accountId, [
            'xml' => $verifactuXml
        ]);

        echo "VeriFactu XML imported successfully!\n";
        echo "  ID: {$importedReport['id']}\n";
        echo "  Type: {$importedReport['type']}\n";
        echo "  State: {$importedReport['state']}\n";
        echo "  Invoice number: {$importedReport['invoice_number']}\n";
        echo "  QR code: " . (isset($importedReport['qr']) ? 'Available' : 'Will be generated after chaining') . "\n";
        echo "\n";

        // Monitor the imported report
        echo "Monitoring imported report...\n";
        $importedId = $importedReport['id'];
        $finalStates = ['registered', 'error', 'registered_with_errors'];

        for ($i = 0; $i < 10; $i++) {
            sleep(2);

            $status = $client->taxReports->retrieve($importedId);
            echo "  Check {$i+1}: State = {$status['state']}\n";

            if (in_array($status['state'], $finalStates)) {
                echo "Reached final state: {$status['state']}\n\n";

                if ($status['state'] === 'registered' && isset($status['qr'])) {
                    echo "QR code is now available!\n";
                    if (isset($status['identifier'])) {
                        echo "Verification URL: {$status['identifier']}\n";
                    }
                }

                break;
            }
        }

    } catch (ApiErrorException $e) {
        echo "Error importing VeriFactu XML: {$e->getMessage()}\n";
        if ($e->getJsonBody()) {
            echo "Details: " . json_encode($e->getJsonBody(), JSON_PRETTY_PRINT) . "\n";
        }
    }
    echo "\n";

    // ============================================
    // Example 2: Import from file
    // ============================================
    echo "Example 2: Importing from XML file...\n\n";

    // Create a sample XML file
    $xmlFilePath = sys_get_temp_dir() . '/sample_verifactu.xml';
    file_put_contents($xmlFilePath, $verifactuXml);
    echo "Created sample XML file: {$xmlFilePath}\n";

    // Read and import
    $xmlContent = file_get_contents($xmlFilePath);

    try {
        $fileImport = $client->taxReports->import($accountId, [
            'xml' => $xmlContent
        ]);

        echo "XML file imported successfully!\n";
        echo "  ID: {$fileImport['id']}\n";
        echo "  Label: {$fileImport['label']}\n";

    } catch (ApiErrorException $e) {
        echo "Error importing from file: {$e->getMessage()}\n";
    }

    // Clean up
    unlink($xmlFilePath);
    echo "\n";

    // ============================================
    // Example 3: Error handling
    // ============================================
    echo "Example 3: Error handling with invalid XML...\n\n";

    $invalidXml = '<?xml version="1.0"?><Invalid>Not a valid tax report</Invalid>';

    try {
        $client->taxReports->import($accountId, [
            'xml' => $invalidXml
        ]);

        echo "This should not be reached\n";

    } catch (ApiErrorException $e) {
        echo "Expected error caught:\n";
        echo "  Message: {$e->getMessage()}\n";
        echo "  HTTP Status: {$e->getHttpStatus()}\n";

        if ($e->getJsonBody()) {
            $body = $e->getJsonBody();
            if (isset($body['error']['message'])) {
                echo "  Error details: {$body['error']['message']}\n";
            }
        }
    }
    echo "\n";

    // ============================================
    // Tips and best practices
    // ============================================
    echo "=== Tips for importing XML ===\n";
    echo "1. Ensure your XML conforms to VeriFactu or TicketBAI schema\n";
    echo "2. Chaining information will be ignored - B2BRouter handles chaining\n";
    echo "3. Always monitor the state after import\n";
    echo "4. Use webhooks in production instead of polling\n";
    echo "5. The QR code is generated after import and chaining\n";
    echo "6. Validate your XML before importing to catch errors early\n\n";

    echo "=== Import Example Complete ===\n";

} catch (ApiErrorException $e) {
    echo "\nAPI Error: {$e->getMessage()}\n";
    echo "HTTP Status: {$e->getHttpStatus()}\n";

    if ($e->getRequestId()) {
        echo "Request ID: {$e->getRequestId()}\n";
    }

    if ($e->getJsonBody()) {
        echo "\nError details:\n";
        echo json_encode($e->getJsonBody(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (\Exception $e) {
    echo "\nError: {$e->getMessage()}\n";
}
