<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use App\Repositories\NotificationRepository;

class NotificationController extends Controller
{
    protected $notificationRepository;

    protected $notificationService;

    /**
     * Create a new controller instance.
     *
     * @param \App\Repository\NotificationRepository  $notificationRepository
     * @param \App\Service\NotificationService  $notificationService
     * @return void
     */
    public function __construct(
        NotificationRepository $notificationRepository,
        NotificationService $notificationService
    )
    {
        $this->notificationRepository = $notificationRepository;
        $this->notificationService = $notificationService;
    }

    /**
     * Get the paginated notifications.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $response = $this->notificationRepository->get($request->user());

        return response()->json($response, $response['status']);
    }

    /**
     * Peek at newly received notifications.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function peek(Request $request)
    {
        $response = $this->notificationService->peek($request->user());

        return response()->json($response, $response['status']);
    }

    /**
     * Mark a notification as read.
     * 
     * @param \App\Models\Notification  $notification
     * @return \Illuminate\Http\JsonResponse
     */
    public function read(Notification $notification)
    {
        $response = $this->notificationService->readOne($notification);

        return response()->json($response, $response['status']);
    }

    /**
     * Mark all notifications as read.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function readAll(Request $request)
    {
        $response = $this->notificationService->readAll($request->user());

        return response()->json($response, $response['status']);
    }
}
