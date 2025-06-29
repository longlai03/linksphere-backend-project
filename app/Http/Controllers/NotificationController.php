<?php

namespace App\Http\Controllers;

use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Lấy danh sách thông báo của user đang đăng nhập (có phân trang)
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $result = $this->notificationService->getUserNotifications($user->id);

        if ($result['success']) {
            return response()->json($result);
        } else {
            return response()->json(['error' => $result['error']], 500);
        }
    }

    /**
     * Xóa thông báo theo ID
     */
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $result = $this->notificationService->deleteNotification($id, $user->id);

        if ($result['success']) {
            return response()->json($result);
        } else {
            return response()->json(['error' => $result['error']], 404);
        }
    }

    /**
     * Xóa thông báo theo sender_id và type
     */
    public function deleteBySenderAndType(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|integer|exists:users,id',
            'type' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->notificationService->deleteBySenderAndType(
            $user->id,
            $request->sender_id,
            $request->type
        );

        if ($result['success']) {
            return response()->json($result);
        } else {
            return response()->json(['error' => $result['error']], 500);
        }
    }

    /**
     * Đánh dấu thông báo đã đọc
     */
    public function markAsRead($id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $result = $this->notificationService->markAsRead($id, $user->id);

        if ($result['success']) {
            return response()->json($result);
        } else {
            return response()->json(['error' => $result['error']], 404);
        }
    }
} 