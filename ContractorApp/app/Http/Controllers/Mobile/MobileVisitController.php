<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileVisitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clientId = $request->integer('client_id') ?: null;
        $search   = $request->string('search')->trim()->value();

        $visits = Visit::query()
            ->with('client:id,name')
            ->when($clientId, fn ($q, $id) => $q->where('client_id', $id))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('purpose', 'like', '%'.$search.'%')
                  ->orWhere('description', 'like', '%'.$search.'%')
                  ->orWhere('location', 'like', '%'.$search.'%')
                  ->orWhereHas('client', fn ($c) => $c->where('name', 'like', '%'.$search.'%'));
            }))
            ->orderByDesc('meeting_date')
            ->latest()
            ->get()
            ->map(fn (Visit $v) => [
                'id'                => $v->id,
                'client_id'         => $v->client_id,
                'client_name'       => $v->client?->name,
                'meeting_date'      => $v->meeting_date?->toDateString(),
                'purpose'           => $v->purpose,
                'location'          => $v->location,
                'description'       => $v->description,
                'next_meeting_date' => $v->next_meeting_date?->toDateString(),
            ]);

        return response()->json($visits);
    }

    public function store(Request $request): JsonResponse
    {
        $contractorId = $request->user()->contractor_id;

        $data = $request->validate([
            'client_id'         => ['required', 'integer', Rule::exists('clients', 'id')->whereNull('deleted_at')],
            'meeting_date'      => ['required', 'date'],
            'purpose'           => ['nullable', 'string', 'max:200'],
            'location'          => ['nullable', 'string', 'max:200'],
            'description'       => ['nullable', 'string', 'max:5000'],
            'next_meeting_date' => ['nullable', 'date'],
        ]);

        $visit = new Visit($data);
        $visit->contractor_id = $contractorId;
        $visit->save();

        return response()->json(['id' => $visit->id, 'message' => 'Visit logged.'], 201);
    }

    public function update(Request $request, Visit $visit): JsonResponse
    {
        $data = $request->validate([
            'client_id'         => ['sometimes', 'integer', Rule::exists('clients', 'id')->whereNull('deleted_at')],
            'meeting_date'      => ['sometimes', 'date'],
            'purpose'           => ['nullable', 'string', 'max:200'],
            'location'          => ['nullable', 'string', 'max:200'],
            'description'       => ['nullable', 'string', 'max:5000'],
            'next_meeting_date' => ['nullable', 'date'],
        ]);

        $visit->update($data);

        return response()->json(['message' => 'Visit updated.']);
    }

    public function destroy(Visit $visit): JsonResponse
    {
        $visit->delete();

        return response()->json(['message' => 'Visit deleted.']);
    }

    public function clients(): JsonResponse
    {
        $clients = Client::orderBy('name')->get(['id', 'name'])->map(fn ($c) => [
            'id'   => $c->id,
            'name' => $c->name,
        ]);

        return response()->json($clients);
    }
}
