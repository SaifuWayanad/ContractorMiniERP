<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $data['username'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Invalid username or password.'],
            ]);
        }

        if (! $user->canSignIn()) {
            throw ValidationException::withMessages([
                'username' => ['Account is inactive. Please contact your administrator.'],
            ]);
        }

        $token = $user->createToken('mobile', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'contractor_id' => $user->contractor_id,
                'is_admin'      => $user->isAdmin() || $user->isSuperAdmin(),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
