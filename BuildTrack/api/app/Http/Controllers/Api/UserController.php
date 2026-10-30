<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\Paginates;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    use Paginates;

    private const ROLES = [User::ADMIN, User::CONTRACTOR];

    public function index(Request $request): JsonResponse
    {
        $query = User::orderBy('full_name');

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(fn ($q) => $q->where('username', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        return response()->json($this->paginate($query, $request, UserResource::class));
    }

    /** Active contractor users for dropdowns. Registered before {user} so it isn't read as an id. */
    public function contractors(): JsonResponse
    {
        $users = User::where('is_active', true)
            ->where('role', User::CONTRACTOR)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'username']);

        return response()->json($users->map(fn (User $u) => [
            'id' => $u->id,
            'fullName' => $u->full_name,
            'username' => $u->username,
        ]));
    }

    public function show(User $user): JsonResponse
    {
        return response()->json((new UserResource($user))->resolve());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
            'fullName' => ['required', 'string', 'max:200'],
            'email' => ['nullable', 'string', 'max:200'],
            'role' => ['required', 'string'],
        ]);

        if (! in_array($data['role'], self::ROLES, true)) {
            return response()->json(['title' => 'Invalid role', 'detail' => 'Role must be Admin or Contractor.'], 400);
        }

        if (User::where('username', $data['username'])->exists()) {
            return response()->json([
                'title' => 'Username taken',
                'detail' => "Username '{$data['username']}' already exists.",
            ], 409);
        }

        $user = User::create([
            'username' => $data['username'],
            'password_hash' => Hash::make($data['password']),
            'full_name' => $data['fullName'],
            'email' => $data['email'] ?? '',
            'role' => $data['role'],
            'is_active' => true,
        ]);

        return response()->json((new UserResource($user))->resolve(), Response::HTTP_CREATED);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'fullName' => ['required', 'string', 'max:200'],
            'email' => ['nullable', 'string', 'max:200'],
            'role' => ['required', 'string'],
            'isActive' => ['required', 'boolean'],
            'password' => ['nullable', 'string'],
        ]);

        if (! in_array($data['role'], self::ROLES, true)) {
            return response()->json(['title' => 'Invalid role'], 400);
        }

        if ($user->id === $request->user()->id && ! $data['isActive']) {
            return response()->json(['title' => 'Cannot deactivate yourself.'], 409);
        }

        $user->forceFill([
            'full_name' => $data['fullName'],
            'email' => $data['email'] ?? '',
            'role' => $data['role'],
            'is_active' => $data['isActive'],
        ]);

        if (! empty($data['password'])) {
            $user->password_hash = Hash::make($data['password']);
        }

        $user->save();

        return response()->json((new UserResource($user))->resolve());
    }

    /** Soft-deactivates the user, as the .NET controller does. */
    public function destroy(Request $request, User $user): Response|JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['title' => 'Cannot delete yourself.'], 409);
        }

        $user->update(['is_active' => false]);

        return response()->noContent();
    }
}
