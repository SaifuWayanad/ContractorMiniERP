<?php

namespace App\Http\Controllers\Mobile;

use App\Enums\ClientStatus;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileClientController extends Controller
{
    public function index(): JsonResponse
    {
        $clients = Client::query()
            ->withCount('projects')
            ->orderBy('name')
            ->get()
            ->map(fn (Client $c) => [
                'id'             => $c->id,
                'name'           => $c->name,
                'company'        => $c->company,
                'contact_person' => $c->contact_person,
                'phone'          => $c->phone,
                'status'         => $c->status->value,
                'projects_count' => $c->projects_count,
            ]);

        return response()->json($clients);
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
            'name'           => ['required', 'string', 'max:150'],
            'company'        => ['nullable', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'email'          => ['nullable', 'email', 'max:150'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'address'        => ['nullable', 'string', 'max:200'],
            'city'           => ['nullable', 'string', 'max:100'],
            'state'          => ['nullable', 'string', 'max:100'],
            'notes'          => ['nullable', 'string', 'max:2000'],
        ]);

        $data['status'] = 'active';

        $client = new Client($data);
        $client->contractor_id = $contractorId;
        $client->save();

        return response()->json(['id' => $client->id, 'message' => 'Client created.'], 201);
    }

    public function show(Client $client): JsonResponse
    {
        $client->load('projects:id,client_id,name,project_number,status');

        return response()->json([
            'id'             => $client->id,
            'name'           => $client->name,
            'company'        => $client->company,
            'contact_person' => $client->contact_person,
            'phone'          => $client->phone,
            'email'          => $client->email,
            'address'        => $client->address,
            'city'           => $client->city,
            'state'          => $client->state,
            'status'         => $client->status->value,
            'notes'          => $client->notes,
            'projects'       => $client->projects->map(fn ($p) => [
                'id'             => $p->id,
                'name'           => $p->name,
                'project_number' => $p->project_number,
                'status'         => $p->status->value,
            ]),
        ]);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $data = $request->validate([
            'name'           => ['sometimes', 'string', 'max:150'],
            'company'        => ['nullable', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'email'          => ['nullable', 'email', 'max:150'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'address'        => ['nullable', 'string', 'max:200'],
            'city'           => ['nullable', 'string', 'max:100'],
            'state'          => ['nullable', 'string', 'max:100'],
            'status'         => ['nullable', Rule::enum(ClientStatus::class)],
            'notes'          => ['nullable', 'string', 'max:2000'],
        ]);

        $client->update($data);

        return response()->json(['message' => 'Client updated.']);
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();
        return response()->json(['message' => 'Client deleted.']);
    }
}
