<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $data['username'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password_hash) || ! $user->is_active) {
            return response()->json(['message' => 'Invalid username or password.'], 401);
        }

        $token = $user->createToken('buildtrack', ['*'], now()->addHours(8));

        return response()->json([
            'token' => $token->plainTextToken,
            'expiresAt' => $token->accessToken->expires_at?->toIso8601String(),
            'user' => $this->userInfo($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->userInfo($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:6'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['currentPassword'], $user->password_hash)) {
            return response()->json(['message' => 'Current password is incorrect.'], 400);
        }

        $user->forceFill(['password_hash' => Hash::make($data['newPassword'])])->save();

        return response()->json(['message' => 'Password changed successfully.']);
    }

    /** @return array<string, mixed> */
    private function userInfo(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'fullName' => $user->full_name,
            'role' => $user->role,
            'isActive' => $user->is_active,
        ];
    }
}
