<?php

namespace App\Http\Controllers;

use App\Models\User;
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
    public function follow(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'followed_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $follower = Auth::user();
        $followed = User::findOrFail($request->followed_id);

        // Không thể tự theo dõi chính mình
        if ($follower->id === $followed->id) {
            return response()->json(['message' => 'You cannot follow yourself'], 400);
        }

        // Kiểm tra xem đã có mối quan hệ theo dõi chưa
        $existingFollow = DB::table('followers')
            ->where('follower_id', $follower->id)
            ->where('followed_id', $followed->id)
            ->first();

        if ($existingFollow) {
            $status = match($existingFollow->status) {
                'pending' => 'Follow request is already pending',
                'accepted' => 'You are already following this user',
                'declined' => 'Your follow request was declined',
                'blocked' => 'You are blocked by this user',
                default => 'Unknown status'
            };
            return response()->json(['message' => $status], 400);
        }

        // Tạo yêu cầu theo dõi mới
        DB::table('followers')->insert([
            'follower_id' => $follower->id,
            'followed_id' => $followed->id,
            'status' => 'pending',
            'request_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Tạo thông báo cho người được theo dõi
        $followed->notifications()->create([
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

        $follow = DB::table('followers')
            ->where('follower_id', $follower->id)
            ->where('followed_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$follow) {
            return response()->json(['message' => 'No pending follow request found'], 404);
        }

        DB::table('followers')
            ->where('follower_id', $follower->id)
            ->where('followed_id', $user->id)
            ->update([
                'status' => 'accepted',
                'respond_at' => now(),
                'updated_at' => now()
            ]);

        // Tạo thông báo cho người theo dõi
        $follower->notifications()->create([
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

        $follow = DB::table('followers')
            ->where('follower_id', $follower->id)
            ->where('followed_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$follow) {
            return response()->json(['message' => 'No pending follow request found'], 404);
        }

        DB::table('followers')
            ->where('follower_id', $follower->id)
            ->where('followed_id', $user->id)
            ->update([
                'status' => 'declined',
                'respond_at' => now(),
                'updated_at' => now()
            ]);

        return response()->json(['message' => 'Follow request declined successfully']);
    }

    /**
     * Hủy theo dõi một người dùng
     */
    public function unfollow(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'followed_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $follower = Auth::user();
        $followed = User::findOrFail($request->followed_id);

        $deleted = DB::table('followers')
            ->where('follower_id', $follower->id)
            ->where('followed_id', $followed->id)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'No follow relationship found'], 404);
        }

        return response()->json(['message' => 'Unfollowed successfully']);
    }

    /**
     * Lấy danh sách người theo dõi của một user
     */
    public function getFollowers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::findOrFail($request->user_id);
        $followers = $user->followers()
            ->where('status', 'accepted')
            ->with(['followers' => function ($query) {
                $query->where('status', 'accepted');
            }])
            ->get()
            ->map(function ($follower) {
                return [
                    'id' => $follower->id,
                    'username' => $follower->username,
                    'nickname' => $follower->nickname,
                    'avatar_url' => $follower->avatar_url,
                    'is_following_back' => $follower->followers->isNotEmpty()
                ];
            });

        return response()->json(['followers' => $followers]);
    }

    /**
     * Lấy danh sách người mà một user đang theo dõi
     */
    public function getFollowing(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::findOrFail($request->user_id);
        $following = $user->followings()
            ->where('status', 'accepted')
            ->with(['followers' => function ($query) {
                $query->where('status', 'accepted');
            }])
            ->get()
            ->map(function ($followed) {
                return [
                    'id' => $followed->id,
                    'username' => $followed->username,
                    'nickname' => $followed->nickname,
                    'avatar_url' => $followed->avatar_url,
                    'is_following_back' => $followed->followers->isNotEmpty()
                ];
            });

        return response()->json(['following' => $following]);
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
