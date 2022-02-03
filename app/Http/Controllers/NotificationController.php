<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get the paginated notifications.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $data = $request->user()->notifications()->withPaginated();

        return response()->json($data);
    }

     /**
     * Get the number of new notifications.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCount(Request $request)
    {
        $data = $request->user()->notifications()->unpeeked()->count();

        return response()->json(compact('data'));
    }

    /**
     * Peek at newly received notifications.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function peek(Request $request)
    {
        $request->user()->notifications()->unpeeked()->update([
            'peeked_at' => now()
        ]);

        return response()->success();
    }

    /**
     * Mark a notification as read.
     * 
     * @param \App\Models\Notification  $notification
     * @return \Illuminate\Http\JsonResponse
     */
    public function read(Notification $notification)
    {
        if ($notification->notifiable->isNot(auth()->user())) {
            return response()->error('Unauthorized.', 401);
        }

        $notification->markAsRead();

        return response()->success();
    }

    /**
     * Mark all notifications as read.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        
        return response()->success();
    }
}
