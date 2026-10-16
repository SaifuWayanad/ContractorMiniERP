<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileDealerController extends Controller
{
    public function index(): JsonResponse
    {
        $dealers = Dealer::query()
            ->withCount('projects')
            ->orderBy('name')
            ->get()
            ->map(fn (Dealer $d) => [
                'id'             => $d->id,
                'name'           => $d->name,
                'city'           => $d->city,
                'phone'          => $d->phone,
                'projects_count' => $d->projects_count,
            ]);

        return response()->json($dealers);
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
            'name'  => ['required', 'string', 'max:120'],
            'city'  => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $dealer = new Dealer($data);
        $dealer->contractor_id = $contractorId;
        $dealer->save();

        return response()->json(['id' => $dealer->id, 'message' => 'Dealer created.'], 201);
    }

    public function show(Dealer $dealer): JsonResponse
    {
        $dealer->load('projects:id,dealer_id,name,project_number,status');

        return response()->json([
            'id'             => $dealer->id,
            'name'           => $dealer->name,
            'city'           => $dealer->city,
            'phone'          => $dealer->phone,
            'projects_count' => $dealer->projects()->count(),
            'projects'       => $dealer->projects->map(fn ($p) => [
                'id'             => $p->id,
                'name'           => $p->name,
                'project_number' => $p->project_number,
                'status'         => $p->status->value,
            ]),
        ]);
    }

    public function update(Request $request, Dealer $dealer): JsonResponse
    {
        $data = $request->validate([
            'name'  => ['sometimes', 'string', 'max:120'],
            'city'  => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $dealer->update($data);

        return response()->json(['message' => 'Dealer updated.']);
    }

    public function destroy(Dealer $dealer): JsonResponse
    {
        $dealer->delete();
        return response()->json(['message' => 'Contact deleted.']);
    }
}
