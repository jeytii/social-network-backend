<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\NotificationRepository;
use App\Services\NotificationService;

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
        $data = $this->notificationRepository->get($request->user());

        return response()->json($data);
    }

    /**
     * Peek at the newly received notifications.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function peek(Request $request)
    {
        $data = $this->notificationService->peek($request->user());

        return response()->json($data);
    }

    /**
     * Update an unread notification's status into read.
     * 
     * @param \Illuminate\Http\Request  $request
     * @param string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function read(Request $request, string $id)
    {
        $data = $this->notificationService->readOne($request->user(), $id);

        return response()->json($data);
    }

    /**
     * Update all unread notifications' status into read.
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function readAll(Request $request)
    {
        $data = $this->notificationService->readAll($request->user());

        return response()->json($data);
    }
}
