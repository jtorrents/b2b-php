<?php

namespace B2BRouter\Tests\Unit;

use B2BRouter\Collection;
use B2BRouter\Tests\TestCase;

class TaxReportServiceTest extends TestCase
{
    public function testListTaxReports()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'tax_reports' => [
                [
                    'id' => 'tr_12345',
                    'invoice_id' => 'inv_001',
                    'status' => 'registered'
                ],
                [
                    'id' => 'tr_67890',
                    'invoice_id' => 'inv_002',
                    'status' => 'pending'
                ]
            ],
            'meta' => [
                'total' => 50,
                'offset' => 0,
                'limit' => 2
            ]
        ]));

        $result = $client->taxReports->all('test-account', [
            'limit' => 2,
            'offset' => 0
        ]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals(50, $result->getTotal());
        $this->assertTrue($result->hasMore());

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('GET', $request['method']);
        $this->assertStringContainsString('/accounts/test-account/tax_reports', $request['url']);
        $this->assertStringContainsString('limit=2', $request['url']);
        $this->assertStringContainsString('offset=0', $request['url']);
    }

    public function testListTaxReportsWithFilters()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'tax_reports' => [],
            'meta' => ['total' => 0, 'offset' => 0, 'limit' => 25]
        ]));

        $client->taxReports->all('test-account', [
            'invoice_id' => 'inv_12345',
            'sent_at_from' => '2025-01-01',
            'updated_at_from' => '2025-01-01'
        ]);

        $request = $mockHttp->getLastRequest();
        $this->assertStringContainsString('invoice_id=inv_12345', $request['url']);
        $this->assertStringContainsString('sent_at_from=2025-01-01', $request['url']);
        $this->assertStringContainsString('updated_at_from=2025-01-01', $request['url']);
    }

    public function testRetrieveTaxReport()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'tax_report' => [
                'id' => 'tr_12345',
                'invoice_id' => 'inv_001',
                'status' => 'registered',
                'qr_code' => 'https://example.com/qr/tr_12345',
                'created_at' => '2025-11-11T10:00:00Z'
            ]
        ]));

        $result = $client->taxReports->retrieve('tr_12345');

        $this->assertEquals('tr_12345', $result['id']);
        $this->assertEquals('inv_001', $result['invoice_id']);
        $this->assertEquals('registered', $result['status']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('GET', $request['method']);
        $this->assertStringContainsString('/tax_reports/tr_12345', $request['url']);
    }

    public function testRetrieveTaxReportWithParameters()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'tax_report' => ['id' => 'tr_12345']
        ]));

        $client->taxReports->retrieve('tr_12345', [
            'include' => 'invoice'
        ]);

        $request = $mockHttp->getLastRequest();
        $this->assertStringContainsString('include=invoice', $request['url']);
    }
}
