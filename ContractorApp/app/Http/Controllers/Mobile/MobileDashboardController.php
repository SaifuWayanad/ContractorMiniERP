<?php

namespace App\Http\Controllers\Mobile;

use App\Enums\ProjectStatus;
use App\Enums\WorkStatus;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Project;
use App\Models\Work;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $contractorId = $request->user()->contractor_id;

        // Summary
        $projects = $this->projectQuery($contractorId);
        $total    = (clone $projects)->count();
        $active   = (clone $projects)->where('status', ProjectStatus::Active)->count();
        $completed = (clone $projects)->where('status', ProjectStatus::Completed)->count();

        $summary = [
            'projects'           => $total,
            'active_projects'    => $active,
            'completed_projects' => $completed,
            'pending_works'      => $this->workQuery($contractorId)->whereIn('status', array_map(fn ($s) => $s->value, WorkStatus::open()))->count(),
            'clients'            => $this->clientQuery($contractorId)->count(),
            'locations'          => 0,
        ];

        // Today's meetings
        $todaysMeetings = (clone $projects)
            ->with('client:id,name')
            ->whereDate('next_meeting_at', today())
            ->orderBy('next_meeting_at')
            ->get()
            ->map(fn (Project $p) => [
                'id'                 => $p->id,
                'name'               => $p->name,
                'client'             => $p->client?->name,
                'next_meeting_at'    => $p->next_meeting_at?->toJson(),
                'next_meeting_notes' => $p->next_meeting_notes,
            ]);

        // Upcoming meetings
        $upcomingMeetings = (clone $projects)
            ->with('client:id,name')
            ->where('next_meeting_at', '>', now()->endOfDay())
            ->where('next_meeting_at', '<=', now()->addDays(30))
            ->orderBy('next_meeting_at')
            ->limit(6)
            ->get()
            ->map(fn (Project $p) => [
                'id'                 => $p->id,
                'name'               => $p->name,
                'client'             => $p->client?->name,
                'next_meeting_at'    => $p->next_meeting_at?->toJson(),
                'next_meeting_notes' => $p->next_meeting_notes,
            ]);

        // Recent projects
        $recentProjects = (clone $projects)
            ->with('client:id,name')
            ->latest()
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Project $p) => [
                'id'             => $p->id,
                'name'           => $p->name,
                'project_number' => $p->project_number,
                'status'         => $p->status->value,
                'client'         => $p->client?->name,
            ]);

        // Upcoming deadlines
        $today = CarbonImmutable::today();
        $until = $today->addDays(14);

        $deadlineProjects = (clone $projects)
            ->whereIn('status', array_map(fn ($s) => $s->value, ProjectStatus::open()))
            ->whereNotNull('expected_end_date')
            ->where('expected_end_date', '<=', $until)
            ->with('client:id,name')
            ->orderBy('expected_end_date')
            ->limit(8)
            ->get()
            ->map(fn (Project $p) => [
                'type'     => 'Project',
                'title'    => $p->name,
                'subtitle' => $p->client?->name,
                'date'     => $p->expected_end_date->toDateString(),
                'days'     => (int) $today->diffInDays($p->expected_end_date, false),
            ]);

        $deadlineWorks = $this->workQuery($contractorId)
            ->whereIn('status', array_map(fn ($s) => $s->value, WorkStatus::open()))
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $until)
            ->with('project:id,name')
            ->orderBy('due_date')
            ->limit(8)
            ->get()
            ->map(fn (Work $w) => [
                'type'     => 'Work',
                'title'    => $w->title,
                'subtitle' => $w->project?->name,
                'date'     => $w->due_date->toDateString(),
                'days'     => (int) $today->diffInDays($w->due_date, false),
            ]);

        $upcomingDeadlines = $deadlineProjects->concat($deadlineWorks)->sortBy('date')->take(8)->values();

        return response()->json([
            'summary'            => $summary,
            'todays_meetings'    => $todaysMeetings,
            'upcoming_meetings'  => $upcomingMeetings,
            'recent_projects'    => $recentProjects,
            'upcoming_deadlines' => $upcomingDeadlines,
        ]);
    }

    /**
     * Returns a base Project query scoped to the contractor (or all, for admin).
     * Relies on the global ContractorScope which skips filtering when contractor_id is null.
     */
    private function projectQuery(?int $contractorId): Builder
    {
        return Project::query();
    }

    private function workQuery(?int $contractorId): Builder
    {
        return Work::query();
    }

    private function clientQuery(?int $contractorId): Builder
    {
        return Client::query();
    }
}
