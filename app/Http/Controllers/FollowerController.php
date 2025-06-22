<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FollowerController extends Controller
{
    /**
     * Gửi yêu cầu theo dõi một người dùng
     */
    public function follow(Request $request, $userId): JsonResponse
    {
        $follower = Auth::user();
        $followed = User::findOrFail($userId);

        // Không thể tự theo dõi chính mình
        if ($follower->id === $followed->id) {
            return response()->json(['message' => 'You cannot follow yourself'], 400);
        }

        // Kiểm tra xem đã có mối quan hệ theo dõi chưa
        $existingFollow = $follower->followings()
            ->where('followed_id', $followed->id)
            ->first();

        if ($existingFollow) {
            $status = match($existingFollow->pivot->status) {
                'pending' => 'Follow request is already pending',
                'accepted' => 'You are already following this user',
                'declined' => 'Your follow request was declined',
                'blocked' => 'You are blocked by this user',
                default => 'Unknown status'
            };
            return response()->json(['message' => $status], 400);
        }

        // Tạo yêu cầu theo dõi mới
        $follower->followings()->attach($followed->id, [
            'status' => 'pending',
            'request_at' => now()
        ]);

        // Tạo thông báo cho người được theo dõi
        $followed->notifications()->create([
            'sender_id' => $follower->id,
            'content' => "{$follower->nickname} đã gửi yêu cầu theo dõi bạn",
            'type' => 'follow_request',
            'read' => false
        ]);

        return response()->json(['message' => 'Follow request sent successfully']);
    }

    /**
     * Chấp nhận yêu cầu theo dõi
     */
    public function acceptFollow(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'follower_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $follower = User::findOrFail($request->follower_id);

        // Sử dụng Eloquent relationship
        $follow = $user->followers()
            ->where('follower_id', $follower->id)
            ->wherePivot('status', 'pending')
            ->first();

        if (!$follow) {
            return response()->json(['message' => 'No pending follow request found'], 404);
        }

        // Cập nhật trạng thái follow
        $user->followers()->updateExistingPivot($follower->id, [
            'status' => 'accepted',
            'respond_at' => now()
        ]);

        // Tạo thông báo cho người theo dõi
        $follower->notifications()->create([
            'sender_id' => $user->id,
            'content' => "{$user->nickname} đã chấp nhận yêu cầu theo dõi của bạn",
            'type' => 'follow_accepted',
            'read' => false
        ]);

        return response()->json(['message' => 'Follow request accepted successfully']);
    }

    /**
     * Từ chối yêu cầu theo dõi
     */
    public function declineFollow(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'follower_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $follower = User::findOrFail($request->follower_id);

        // Sử dụng Eloquent relationship
        $follow = $user->followers()
            ->where('follower_id', $follower->id)
            ->wherePivot('status', 'pending')
            ->first();

        if (!$follow) {
            return response()->json(['message' => 'No pending follow request found'], 404);
        }

        // Cập nhật trạng thái follow
        $user->followers()->updateExistingPivot($follower->id, [
            'status' => 'declined',
            'respond_at' => now()
        ]);

        return response()->json(['message' => 'Follow request declined successfully']);
    }

    /**
     * Hủy theo dõi một người dùng
     */
    public function unfollow(Request $request, $userId): JsonResponse
    {
        $follower = Auth::user();
        $followed = User::findOrFail($userId);

        // Sử dụng Eloquent relationship để xóa
        $detached = $follower->followings()->detach($followed->id);

        if ($detached === 0) {
            return response()->json(['message' => 'No follow relationship found'], 404);
        }

        return response()->json(['message' => 'Unfollowed successfully']);
    }

    /**
     * Lấy danh sách yêu cầu theo dõi đang chờ xử lý
     */
    public function getPendingFollowRequests(): JsonResponse
    {
        $user = Auth::user();
        $pendingRequests = $user->followers()
            ->where('status', 'pending')
            ->get()
            ->map(function ($follower) {
                return [
                    'id' => $follower->id,
                    'username' => $follower->username,
                    'nickname' => $follower->nickname,
                    'avatar_url' => $follower->avatar_url,
                    'request_at' => $follower->pivot->request_at
                ];
            });

        return response()->json(['pending_requests' => $pendingRequests]);
    }
}
