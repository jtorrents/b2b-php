<?php

namespace B2BRouter\Service;

use B2BRouter\ApiResource;
use B2BRouter\Collection;

/**
 * Service for managing tax reports.
 */
class TaxReportService extends ApiResource
{
    /**
     * List all tax reports for an account.
     *
     * @param string $account The account identifier
     * @param array $params Query parameters:
     *   - offset: Pagination offset (default: 0)
     *   - limit: Number of items per page (default: 25, max: 500)
     *   - invoice_id: Filter by invoice ID
     *   - sent_at_from: Filter by sent at from date
     *   - updated_at_from: Filter by updated at from date
     * @return Collection A paginated collection of tax reports
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function all($account, array $params = [])
    {
        $path = "/accounts/{$account}/tax_reports";
        $response = $this->request('GET', $path, $params);

        $taxReports = isset($response['tax_reports']) ? $response['tax_reports'] : [];
        $meta = isset($response['meta']) ? $response['meta'] : null;

        return new Collection($taxReports, $meta);
    }

    /**
     * Retrieve a tax report.
     *
     * @param string $id The tax report ID
     * @param array $params Query parameters
     * @return array The tax report data
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function retrieve($id, array $params = [])
    {
        $path = "/tax_reports/{$id}";
        $response = $this->request('GET', $path, $params);

        return isset($response['tax_report']) ? $response['tax_report'] : $response;
    }
}
