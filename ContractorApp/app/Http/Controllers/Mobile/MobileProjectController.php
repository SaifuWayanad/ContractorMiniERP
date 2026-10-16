<?php

namespace App\Http\Controllers\Mobile;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectMeeting;
use App\Models\User;
use App\Services\PushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MobileProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projects = Project::query()
            ->with('client:id,name')
            ->latest()
            ->orderByDesc('id')
            ->get()
            ->map(fn (Project $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'project_number' => $p->project_number,
                'status' => $p->status->value,
                'client' => $p->client?->name,
                'start_date' => $p->start_date?->toDateString(),
                'next_meeting_at' => $p->next_meeting_at?->toJson(),
            ]);

        return response()->json($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $contractorId = $request->user()->contractor_id
            ?? (int) $request->input('contractor_id')
            ?: null;

        if ($contractorId === null) {
            return response()->json(['message' => 'contractor_id is required for admin users.'], 422);
        }

        $data = $request->validate([
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:150'],
            'project_number' => ['nullable', 'string', 'max:30'],
            'type_of_work' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['nullable', 'date'],
            'expected_end_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(ProjectStatus::class)],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'next_meeting_at' => ['nullable', 'date'],
            'next_meeting_notes' => ['nullable', 'string', 'max:250'],
        ]);

        if (empty($data['project_number'])) {
            $seq = Project::withTrashed()->count() + 1;
            do {
                $number = sprintf('PRJ-%04d', $seq++);
            } while (Project::withTrashed()->where('project_number', $number)->exists());
            $data['project_number'] = $number;
        }

        $data['status'] ??= ProjectStatus::Planning->value;

        $project = new Project($data);
        $project->contractor_id = $contractorId;
        $project->save();

        return response()->json([
            'id' => $project->id,
            'project_number' => $project->project_number,
            'message' => 'Project created.',
        ], 201);
    }

    public function show(Project $project): JsonResponse
    {
        foreach (['client', 'dealers', 'locations', 'works.assignee:id,name', 'meetings.createdBy:id,name'] as $rel) {
            try {
                $project->load($rel);
            } catch (\Throwable) {
            }
        }

        return response()->json([
            'id' => $project->id,
            'name' => $project->name,
            'project_number' => $project->project_number,
            'status' => $project->status->value,
            'type_of_work' => $project->type_of_work,
            'description' => $project->description,
            'start_date' => $project->start_date?->toDateString(),
            'expected_end_date' => $project->expected_end_date?->toDateString(),
            'budget' => $project->budget,
            'notes' => $project->notes,
            'next_meeting_at' => $project->next_meeting_at?->toJson(),
            'next_meeting_notes' => $project->next_meeting_notes,
            'client_id' => $project->client_id,
            'client' => [
                'name' => $project->client?->name,
                'company' => $project->client?->company,
                'contact_person' => $project->client?->contact_person,
                'phone' => $project->client?->phone,
                'email' => $project->client?->email,
            ],
            'dealers' => ($project->relationLoaded('dealers') ? $project->dealers : collect())->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'city' => $d->city,
                'phone' => $d->phone,
            ]),
            'locations' => ($project->relationLoaded('locations') ? $project->locations : collect())->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'city' => $l->city,
                'state' => $l->state,
                'contact_person' => $l->contact_person,
                'contact_phone' => $l->contact_phone,
            ]),
            'works' => ($project->relationLoaded('works') ? $project->works : collect())->map(fn ($w) => [
                'id' => $w->id,
                'title' => $w->title,
                'status' => $w->status->value,
                'priority' => $w->priority->value,
                'due_date' => $w->due_date?->toDateString(),
            ]),
            'meetings' => ($project->relationLoaded('meetings') ? $project->meetings : collect())->map(fn ($m) => [
                'id' => $m->id,
                'meeting_at' => $m->meeting_at->toJson(),
                'purpose' => $m->purpose,
                'created_by' => $m->createdBy?->name,
            ]),
        ]);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'client_id' => ['sometimes', 'integer'],
            'name' => ['sometimes', 'string', 'max:150'],
            'project_number' => ['nullable', 'string', 'max:30'],
            'type_of_work' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['nullable', 'date'],
            'expected_end_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(ProjectStatus::class)],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'next_meeting_at' => ['nullable', 'date'],
            'next_meeting_notes' => ['nullable', 'string', 'max:250'],
        ]);

        $project->update($data);
        $project->refresh();

        if ($project->next_meeting_at && $project->next_meeting_at->isToday()) {
            $this->sendMeetingScheduledToday($project);
        }

        return response()->json(['message' => 'Project updated.']);
    }

    public function updateStatus(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(ProjectStatus::class)],
        ]);

        $project->status = ProjectStatus::from($data['status']);

        if ($project->status === ProjectStatus::Completed && $project->actual_end_date === null) {
            $project->actual_end_date = today();
        }

        $project->save();

        return response()->json([
            'message' => "Status updated to {$project->status->label()}.",
            'status' => $project->status->value,
            'status_label' => $project->status->label(),
        ]);
    }

    public function completeMeeting(Request $request, Project $project): JsonResponse
    {
        if ($project->next_meeting_at) {
            $meeting = new ProjectMeeting([
                'meeting_at' => $project->next_meeting_at,
                'purpose'    => $project->next_meeting_notes ?? 'Meeting completed',
            ]);
            $meeting->project_id    = $project->id;
            $meeting->contractor_id = $project->contractor_id;
            $meeting->created_by    = $request->user()->id;
            $meeting->save();
        }

        $project->update(['next_meeting_at' => null, 'next_meeting_notes' => null]);

        return response()->json(['message' => 'Meeting marked as done.']);
    }

    public function updateMeeting(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'next_meeting_at' => ['nullable', 'date'],
            'next_meeting_notes' => ['nullable', 'string', 'max:250'],
        ]);

        $project->update($data);
        $project->refresh();

        // Send immediate notification if meeting is today
        if ($project->next_meeting_at && $project->next_meeting_at->isToday()) {
            $this->sendMeetingScheduledToday($project);
        }

        return response()->json(['message' => 'Meeting schedule updated.']);
    }

    private function sendMeetingScheduledToday(Project $project): void
    {
        $users = User::where('contractor_id', $project->contractor_id)
            ->whereNotNull('push_token')
            ->get();

        $time = $project->next_meeting_at->setTimezone(config('app.timezone'))->format('h:i A');

        $push = app(PushService::class);

        foreach ($users as $user) {
            $push->send($user->push_token, 'Meeting Scheduled for Today', "Meeting at {$time} — {$project->name}", ['projectId' => $project->id]);
        }
    }

    public function storeMeeting(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'meeting_at' => ['required', 'date'],
            'purpose' => ['required', 'string', 'max:500'],
        ]);

        $meeting = new ProjectMeeting($data);
        $meeting->project_id = $project->id;
        $meeting->contractor_id = $project->contractor_id;
        $meeting->created_by = $request->user()->id;
        $meeting->save();

        return response()->json([
            'id' => $meeting->id,
            'meeting_at' => $meeting->meeting_at->toJson(),
            'purpose' => $meeting->purpose,
            'created_by' => $request->user()->name,
            'message' => 'Meeting logged.',
        ], 201);
    }

    public function destroyMeeting(Project $project, ProjectMeeting $meeting): JsonResponse
    {
        $meeting->delete();

        return response()->json(['message' => 'Meeting removed.']);
    }

    public function activity(Project $project): JsonResponse
    {
        $logs = ActivityLog::query()
            ->with('user:id,name')
            ->where('entity_type', 'project')
            ->where('entity_id', $project->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user' => $log->user?->name,
                'created_at' => $log->created_at->toJson(),
            ]);

        return response()->json($logs);
    }

    public function addContact(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'dealer_id' => ['required', 'integer', Rule::exists('dealers', 'id')->whereNull('deleted_at')],
        ]);

        $project->dealers()->syncWithoutDetaching([$data['dealer_id']]);

        return response()->json(['message' => 'Contact added.']);
    }

    public function removeContact(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'dealer_id' => ['required', 'integer'],
        ]);

        $project->dealers()->detach($data['dealer_id']);

        return response()->json(['message' => 'Contact removed.']);
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json(['message' => 'Project deleted.']);
    }

    public function meetings(Request $request): JsonResponse
    {
        $projects = Project::query()
            ->with('client:id,name')
            ->where('next_meeting_at', '>=', now()->startOfDay())
            ->orderBy('next_meeting_at')
            ->get()
            ->map(fn (Project $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'client' => $p->client?->name,
                'next_meeting_at' => $p->next_meeting_at?->toJson(),
                'next_meeting_notes' => $p->next_meeting_notes,
            ]);

        return response()->json($projects);
    }
}
