<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class UserController extends Controller
{
    /**
     * Lấy thông tin người dùng theo ID
     */
    public function getUserById(int $userId): JsonResponse
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'error' => 'Người dùng không tồn tại'
                ], 404);
            }

            // Lấy số lượng followers và following
            $followersCount = DB::table('followers')
                ->where('followed_id', $userId)
                ->where('status', 'accepted')
                ->count();

            $followingCount = DB::table('followers')
                ->where('follower_id', $userId)
                ->where('status', 'accepted')
                ->count();

            // Lấy số lượng posts
            $postsCount = Post::where('user_id', $userId)->count();

            // Kiểm tra xem user hiện tại có đang follow user này không
            $currentUser = auth()->user();
            $isFollowing = false;
            $followStatus = null;

            if ($currentUser && $currentUser->id !== $userId) {
                $followRelation = DB::table('followers')
                    ->where('follower_id', $currentUser->id)
                    ->where('followed_id', $userId)
                    ->first();

                if ($followRelation) {
                    $isFollowing = $followRelation->status === 'accepted';
                    $followStatus = $followRelation->status;
                }
            }

            $userData = [
                'id' => $user->id,
                'username' => $user->username,
                'nickname' => $user->nickname,
                'avatar_url' => $user->avatar_url,
                'bio' => $user->bio,
                'gender' => $user->gender,
                'birthday' => $user->birthday,
                'address' => $user->address,
                'hobbies' => $user->hobbies,
                'created_at' => $user->created_at,
                'stats' => [
                    'followers_count' => $followersCount,
                    'following_count' => $followingCount,
                    'posts_count' => $postsCount
                ],
                'is_following' => $isFollowing,
                'follow_status' => $followStatus
            ];

            return response()->json([
                'success' => true,
                'data' => $userData
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách người dùng (có thể dùng để tìm kiếm)
     */
    public function getUsers(Request $request): JsonResponse
    {
        try {
            // Kiểm tra header Authorization
            if (!$request->hasHeader('Authorization')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            $query = User::query();

            // Tìm kiếm theo username hoặc nickname
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                      ->orWhere('nickname', 'like', "%{$search}%");
                });
            }

            // Phân trang
            $perPage = $request->input('per_page', 10);
            $users = $query->select([
                'id', 'username', 'nickname', 'avatar_url', 'bio', 'created_at'
            ])->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thông tin profile công khai của người dùng
     */
    public function getPublicProfile(int $userId): JsonResponse
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'error' => 'Người dùng không tồn tại'
                ], 404);
            }

            // Lấy thống kê
            $followersCount = DB::table('followers')
                ->where('followed_id', $userId)
                ->where('status', 'accepted')
                ->count();

            $followingCount = DB::table('followers')
                ->where('follower_id', $userId)
                ->where('status', 'accepted')
                ->count();

            $postsCount = Post::where('user_id', $userId)->count();

            // Lấy 3 posts gần nhất để preview
            $recentPosts = Post::where('user_id', $userId)
                ->with(['media.attachment'])
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get();

            $profileData = [
                'id' => $user->id,
                'username' => $user->username,
                'nickname' => $user->nickname,
                'avatar_url' => $user->avatar_url,
                'bio' => $user->bio,
                'created_at' => $user->created_at,
                'stats' => [
                    'followers_count' => $followersCount,
                    'following_count' => $followingCount,
                    'posts_count' => $postsCount
                ],
                'recent_posts' => $recentPosts
            ];

            return response()->json([
                'success' => true,
                'data' => $profileData
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kiểm tra trạng thái follow giữa 2 user
     */
    public function getFollowStatus(int $targetUserId): JsonResponse
    {
        try {
            $currentUser = auth()->user();

            if (!$currentUser) {
                return response()->json([
                    'error' => 'Unauthorized'
                ], 401);
            }

            if ($currentUser->id === $targetUserId) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'is_following' => false,
                        'is_followed_by' => false,
                        'follow_status' => 'self'
                    ]
                ]);
            }

            $following = DB::table('followers')
                ->where('follower_id', $currentUser->id)
                ->where('followed_id', $targetUserId)
                ->first();

            $followedBy = DB::table('followers')
                ->where('follower_id', $targetUserId)
                ->where('followed_id', $currentUser->id)
                ->where('status', 'accepted')
                ->exists();

            $followStatus = 'not_following';
            $isFollowing = false;

            if ($following) {
                $followStatus = $following->status;
                $isFollowing = $following->status === 'accepted';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'is_following' => $isFollowing,
                    'is_followed_by' => $followedBy,
                    'follow_status' => $followStatus
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
