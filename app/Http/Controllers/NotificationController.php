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
}
