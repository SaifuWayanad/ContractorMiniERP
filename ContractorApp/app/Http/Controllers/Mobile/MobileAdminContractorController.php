<?php

namespace App\Http\Controllers\Mobile;

use App\Enums\ContractorStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileAdminContractorController extends Controller
{
    public function index(): JsonResponse
    {
        $contractors = Contractor::query()
            ->withCount(['users', 'projects'])
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'contact_person', 'email', 'phone', 'status'])
            ->map(fn (Contractor $c) => [
                'id'             => $c->id,
                'company_name'   => $c->company_name,
                'contact_person' => $c->contact_person,
                'email'          => $c->email,
                'phone'          => $c->phone,
                'status'         => $c->status->value,
                'users_count'    => $c->users_count,
                'projects_count' => $c->projects_count,
            ]);

        return response()->json($contractors);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_name'   => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'email'          => ['nullable', 'string', 'max:150'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'city'           => ['nullable', 'string', 'max:80'],
            'state'          => ['nullable', 'string', 'max:80'],
            'create_user'    => ['nullable', 'boolean'],
            'user_name'      => ['required_if:create_user,true', 'nullable', 'string', 'max:100'],
            'user_password'  => ['required_if:create_user,true', 'nullable', 'string', 'min:6'],
            'username'       => ['nullable', 'string', 'max:50', 'unique:users,username'],
        ]);

        $contractor = DB::transaction(function () use ($data): Contractor {
            $contractor = Contractor::create([
                'company_name'   => $data['company_name'],
                'contact_person' => $data['contact_person'] ?? null,
                'email'          => $data['email'] ?? null,
                'phone'          => $data['phone'] ?? null,
                'city'           => $data['city'] ?? null,
                'state'          => $data['state'] ?? null,
            ]);

            if ($data['create_user'] ?? false) {
                $userName = $data['user_name'];
                $base     = $data['username'] ?? strtolower(str_replace(' ', '.', $userName));
                $username = $base;
                $n        = 1;
                while (User::where('username', $username)->exists()) {
                    $username = $base . $n++;
                }

                $user = new User([
                    'name'     => $userName,
                    'username' => $username,
                    'password' => $data['user_password'],
                ]);
                $user->contractor_id = $contractor->id;
                $user->role_id       = Role::where('slug', Role::CONTRACTOR_OWNER)->value('id');
                $user->status        = UserStatus::Active;
                $user->save();
            }

            return $contractor;
        });

        return response()->json([
            'id'           => $contractor->id,
            'company_name' => $contractor->company_name,
            'message'      => 'Contractor created successfully.',
        ], 201);
    }

    public function show(Contractor $contractor): JsonResponse
    {
        $contractor->load('users.role:id,name');

        return response()->json([
            'id'             => $contractor->id,
            'company_name'   => $contractor->company_name,
            'contact_person' => $contractor->contact_person,
            'email'          => $contractor->email,
            'phone'          => $contractor->phone,
            'alternate_phone' => $contractor->alternate_phone,
            'address'        => $contractor->address,
            'city'           => $contractor->city,
            'state'          => $contractor->state,
            'postal_code'    => $contractor->postal_code,
            'website'        => $contractor->website,
            'tax_number'     => $contractor->tax_number,
            'license_number' => $contractor->license_number,
            'status'         => $contractor->status->value,
            'projects_count' => $contractor->projects()->count(),
            'users'          => $contractor->users->map(fn ($u) => [
                'id'     => $u->id,
                'name'   => $u->name,
                'email'  => $u->email,
                'role'   => $u->role?->name,
                'status' => $u->status->value,
            ]),
        ]);
    }

    public function update(Request $request, Contractor $contractor): JsonResponse
    {
        $data = $request->validate([
            'company_name'   => ['sometimes', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'email'          => ['nullable', 'string', 'max:150'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'city'           => ['nullable', 'string', 'max:80'],
            'state'          => ['nullable', 'string', 'max:80'],
        ]);

        $contractor->update($data);

        return response()->json(['message' => 'Contractor updated successfully.']);
    }

    public function toggleStatus(Contractor $contractor): JsonResponse
    {
        $contractor->status = $contractor->status === ContractorStatus::Active
            ? ContractorStatus::Inactive
            : ContractorStatus::Active;
        $contractor->save();

        return response()->json([
            'status'  => $contractor->status->value,
            'message' => "Contractor marked as {$contractor->status->value}.",
        ]);
    }
}
