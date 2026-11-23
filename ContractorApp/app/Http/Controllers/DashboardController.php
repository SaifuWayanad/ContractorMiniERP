<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\ReportingPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The contractor dashboard. The contractor is always the signed-in user's contractor.
 */
class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $contractorId = $user->contractor_id;
        $period = ReportingPeriod::fromRequest($request);

        $employee = $user->linkedEmployee();

        if ($employee !== null) {
            return view('dashboard', [
                'user'             => $user,
                'period'           => $period,
                'isEmployeePortal' => true,
                'summary'          => $this->dashboard->employeeSummary($employee),
                'recentProjects'   => $this->dashboard->employeeRecentProjects($employee),
                'employeeWorks'    => $this->dashboard->employeeWorks($employee),
                'deadlines'        => [],
                'todaysMeetings'   => collect(),
                'upcomingMeetings' => collect(),
                'recentClients'    => collect(),
                'activity'         => collect(),
                'statusDistribution' => [],
                'trend'            => [],
            ]);
        }

        return view('dashboard', [
            'user'               => $user,
            'period'             => $period,
            'isEmployeePortal'   => false,
            'summary'            => $this->dashboard->summary($contractorId, $period),
            'statusDistribution' => $this->dashboard->projectStatusDistribution($contractorId),
            'trend'              => $this->dashboard->projectTrend($contractorId, $request->string('trend', '6m')->value()),
            'recentProjects'     => $this->dashboard->recentProjects($contractorId),
            'recentClients'      => $this->dashboard->recentClients($contractorId),
            'deadlines'          => $this->dashboard->upcomingDeadlines($contractorId),
            'todaysMeetings'     => $this->dashboard->todaysMeetings($contractorId),
            'upcomingMeetings'   => $this->dashboard->upcomingMeetings($contractorId),
            'activity'           => $user->can('activity.view') ? $this->dashboard->recentActivity($contractorId) : collect(),
            'employeeWorks'      => collect(),
        ]);
    }

    /**
     * Trend chart data, fetched by the dashboard when the trend range changes.
     */
    public function trend(Request $request): JsonResponse
    {
        return response()->json(
            $this->dashboard->projectTrend($request->user()->contractor_id, $request->string('range', '30d')->value())
        );
    }
}
