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

        return view('dashboard', [
            'user' => $user,
            'period' => $period,
            'summary' => $this->dashboard->summary($contractorId, $period),
            'statusDistribution' => $this->dashboard->projectStatusDistribution($contractorId),
            'trend' => $this->dashboard->projectTrend($contractorId, $request->string('trend', '6m')->value()),
            'recentProjects' => $this->dashboard->recentProjects($contractorId),
            'recentClients' => $this->dashboard->recentClients($contractorId),
            'deadlines' => $this->dashboard->upcomingDeadlines($contractorId),
            'todaysMeetings' => $this->dashboard->todaysMeetings($contractorId),
            'upcomingMeetings' => $this->dashboard->upcomingMeetings($contractorId),
            'activity' => $user->can('activity.view') ? $this->dashboard->recentActivity($contractorId) : collect(),
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
