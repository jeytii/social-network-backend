<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function peek(Request $request)
    {
        $request->user()->notifications()
            ->where('peeked_at', null)
            ->update(['peeked_at' => now()]);

        return response()->json([
            'status' => 200,
            'message' => 'Successfully peeked at new notifications.'
        ]);
    }

    public function read(Request $request, string $id)
    {
        $request->user()->notifications()->firstWhere('id', $id)->markAsRead();

        return response()->json([
            'status' => 200,
            'message' => 'Successfully marked a notification as read.'
        ]);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'status' => 200,
            'message' => 'Successfully marked all unread notifications as read.'
        ]);
    }
}
