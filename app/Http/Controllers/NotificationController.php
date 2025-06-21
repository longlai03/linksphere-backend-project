<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Lấy danh sách thông báo của user đang đăng nhập (có phân trang)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $notifications = Notification::where('user_id', $user->id)
                ->with(['sender' => function ($query) {
                    $query->select(['id', 'username', 'nickname', 'avatar_url']);
                }])
                ->orderByDesc('created_at')
                ->get();

            // Enrich notifications with user information
            $enrichedNotifications = $notifications->map(function ($notification) {
                $notificationData = $notification->toArray();
                
                // Add sender information if available
                if ($notification->sender) {
                    $notificationData['from_user'] = [
                        'id' => $notification->sender->id,
                        'username' => $notification->sender->username,
                        'nickname' => $notification->sender->nickname,
                        'avatar_url' => $notification->sender->avatar_url,
                    ];
                } else {
                    // System notification (sender_id is null)
                    $notificationData['from_user'] = [
                        'id' => null,
                        'username' => 'system',
                        'nickname' => 'Hệ thống',
                        'avatar_url' => null,
                    ];
                }
                
                return $notificationData;
            });

            return response()->json([
                'success' => true,
                'data' => $enrichedNotifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa thông báo theo ID
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return response()->json([
                    'error' => 'Thông báo không tồn tại hoặc bạn không có quyền xóa'
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa thông báo thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa thông báo theo sender_id và type
     */
    public function deleteBySenderAndType(Request $request): JsonResponse
    {
        try {
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

            $deleted = Notification::where('user_id', $user->id)
                ->where('sender_id', $request->sender_id)
                ->where('type', $request->type)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa thông báo thành công',
                'deleted_count' => $deleted
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
} 