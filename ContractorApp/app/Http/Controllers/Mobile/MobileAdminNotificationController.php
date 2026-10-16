<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\PushService;

class MobileAdminNotificationController extends Controller
{
    public function send(Request $request, PushService $push): JsonResponse
    {
        $data = $request->validate([
            'title'         => ['required', 'string', 'max:100'],
            'body'          => ['required', 'string', 'max:500'],
            'contractor_id' => ['nullable', 'integer', 'exists:contractors,id'],
        ]);

        $query = User::whereNotNull('push_token');

        if (! empty($data['contractor_id'])) {
            $query->where('contractor_id', $data['contractor_id']);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            return response()->json(['message' => 'No devices registered.', 'sent' => 0]);
        }

        $sent = 0;

        foreach ($users as $user) {
            if ($push->send($user->push_token, $data['title'], $data['body'])['ok']) {
                $sent++;
            }
        }

        return response()->json([
            'message' => "Notification sent to {$sent} device(s).",
            'sent'    => $sent,
            'total'   => $users->count(),
        ]);
    }
}
