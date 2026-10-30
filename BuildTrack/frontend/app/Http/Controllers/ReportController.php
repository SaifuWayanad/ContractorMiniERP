<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use App\Services\ApiException;
use App\Services\AuthException;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private function api(): ApiService
    {
        return new ApiService(session('api_token'));
    }

    public function outstanding(Request $request)
    {
        $api = $this->api();
        $report = [];
        $customers = [];
        $projects = [];
        $locations = [];
        $error = null;

        $filterParams = $request->only([
            'customerId', 'projectId', 'locationId',
            'dateFrom', 'dateTo', 'search', 'includeArchived',
            'page', 'pageSize',
        ]);
        if (!isset($filterParams['pageSize'])) {
            $filterParams['pageSize'] = 20;
        }

        try {
            $report    = $api->get('/reports/outstanding', $filterParams) ?? [];
            $customers = $api->get('/customers', ['pageSize' => 500]) ?? [];
            $projects  = $api->get('/projects',  ['pageSize' => 500]) ?? [];
            $locations = $api->get('/locations',  ['pageSize' => 500]) ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            $error = $e->getMessage();
        }

        return view('reports.outstanding', compact('report', 'customers', 'projects', 'locations', 'error'));
    }

    public function exportOutstanding(Request $request)
    {
        $api = $this->api();

        $filterParams = $request->only([
            'customerId', 'projectId', 'locationId',
            'dateFrom', 'dateTo', 'search', 'includeArchived',
        ]);
        $filterParams['pageSize'] = 9999;

        try {
            $report = $api->get('/reports/outstanding', $filterParams) ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired');
        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }

        $items = $report['items'] ?? [];

        $filename = 'outstanding-report-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
        ];

        $callback = function () use ($items) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Project', 'Customer', 'Location', 'Budget',
                'Purchases', 'Expenses', 'Total Cost',
                'Budget Remaining', 'Budget Utilization %',
                'Payments Received', 'Balance Due',
                'Last Payment Date', 'Status',
            ]);

            foreach ($items as $row) {
                fputcsv($handle, [
                    $row['projectName']       ?? '',
                    $row['customerName']      ?? '',
                    $row['locationName']      ?? '',
                    isset($row['budget'])             ? number_format((float)$row['budget'], 2, '.', '') : '',
                    isset($row['totalPurchases'])     ? number_format((float)$row['totalPurchases'], 2, '.', '') : '0.00',
                    isset($row['totalExpenses'])      ? number_format((float)$row['totalExpenses'], 2, '.', '') : '0.00',
                    isset($row['totalProjectCost'])   ? number_format((float)$row['totalProjectCost'], 2, '.', '') : '0.00',
                    isset($row['remainingBudget'])    ? number_format((float)$row['remainingBudget'], 2, '.', '') : '',
                    isset($row['budgetUtilizationPct']) ? number_format((float)$row['budgetUtilizationPct'], 1) . '%' : '',
                    isset($row['totalReceived'])      ? number_format((float)$row['totalReceived'], 2, '.', '') : '0.00',
                    isset($row['balanceDue'])         ? number_format((float)$row['balanceDue'], 2, '.', '') : '',
                    isset($row['lastPaymentDate'])    ? date('d M Y', strtotime($row['lastPaymentDate'])) : '',
                    ($row['isArchived'] ?? false) ? 'Archived' : 'Active',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
