<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'phone'         => $user->phone ?? null,
            'contractor_id' => $user->contractor_id,
            'is_admin'      => $user->isAdmin() || $user->isSuperAdmin(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user->fill($data)->save();

        return response()->json([
            'message' => 'Profile updated.',
            'user' => [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'phone'         => $user->phone ?? null,
                'contractor_id' => $user->contractor_id,
                'is_admin'      => $user->isAdmin() || $user->isSuperAdmin(),
            ],
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', 'different:current_password'],
        ]);

        $request->user()->forceFill(['password' => $request->input('password')])->save();

        return response()->json(['message' => 'Password changed successfully.']);
    }

    public function updateDeviceToken(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string', 'max:500']]);

        $request->user()->forceFill(['push_token' => $request->input('token')])->save();

        return response()->json(['message' => 'Device token saved.']);
    }
}
