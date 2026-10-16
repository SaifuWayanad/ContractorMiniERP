<?php

namespace App\Http\Controllers\Mobile;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileAdminUserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->with(['role:id,name', 'contractor:id,company_name'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'username'   => $u->username,
                'status'     => $u->status->value,
                'role'       => $u->role?->name,
                'contractor' => $u->contractor?->company_name,
            ]);

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'username'      => ['required', 'string', 'max:50', 'unique:users,username'],
            'email'         => ['nullable', 'email', 'max:150'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'password'      => ['required', 'string', 'min:6'],
            'role_id'       => ['required', 'integer', 'exists:roles,id'],
            'contractor_id' => ['nullable', 'integer', 'exists:contractors,id'],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'username' => $data['username'],
            'email'    => $data['email'] ?? null,
            'phone'    => $data['phone'] ?? null,
            'password' => $data['password'],
        ]);

        $user->role_id       = $data['role_id'];
        $user->contractor_id = $data['contractor_id'] ?? null;
        $user->status        = UserStatus::Active;
        $user->save();

        return response()->json([
            'id'      => $user->id,
            'name'    => $user->name,
            'message' => 'User created successfully.',
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['role:id,name', 'contractor:id,company_name']);

        return response()->json([
            'id'            => $user->id,
            'name'          => $user->name,
            'username'      => $user->username,
            'email'         => $user->email,
            'phone'         => $user->phone,
            'status'        => $user->status->value,
            'role_id'       => $user->role_id,
            'role'          => $user->role?->name,
            'contractor_id' => $user->contractor_id,
            'contractor'    => $user->contractor?->company_name,
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name'          => ['sometimes', 'string', 'max:100'],
            'username'      => ['sometimes', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email'         => ['nullable', 'email', 'max:150'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'role_id'       => ['sometimes', 'integer', 'exists:roles,id'],
            'contractor_id' => ['nullable', 'integer', 'exists:contractors,id'],
            'status'        => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $fillable = array_intersect_key($data, array_flip(['name', 'username', 'email', 'phone']));
        if ($fillable) {
            $user->fill($fillable);
        }

        if (isset($data['role_id'])) {
            $user->role_id = $data['role_id'];
        }
        if (array_key_exists('contractor_id', $data)) {
            $user->contractor_id = $data['contractor_id'];
        }
        if (isset($data['status'])) {
            $user->status = UserStatus::from($data['status']);
        }

        $user->save();

        return response()->json(['message' => 'User updated successfully.']);
    }
}
