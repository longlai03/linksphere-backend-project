<?php

namespace App\Http\Controllers;

use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Lấy thông tin người dùng theo ID
     */
    public function getUserById(int $userId): JsonResponse
    {
        try {
            $result = $this->userService->getUserById($userId);
            
            if (!$result) {
                return response()->json([
                    'error' => 'Người dùng không tồn tại'
                ], 404);
            }

            $user = $result['user'];
            $isFollowing = $result['is_following'];
            $followStatus = $result['follow_status'];

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
                'phone' => $user->phone,
                'created_at' => $user->created_at,
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
     * Lấy danh sách người dùng
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

            $filters = [];
            if ($request->has('search')) {
                $filters['search'] = $request->input('search');
            }

            $users = $this->userService->getUsers($filters);
            
            if ($users === false) {
                return response()->json([
                    'error' => 'Có lỗi xảy ra khi lấy danh sách người dùng'
                ], 500);
            }

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
            $result = $this->userService->getPublicProfile($userId);
            
            if (!$result) {
                return response()->json([
                    'error' => 'Người dùng không tồn tại'
                ], 404);
            }

            $user = $result['user'];
            $stats = $result['stats'];
            $recentPosts = $result['recent_posts'];

            $profileData = [
                'id' => $user->id,
                'username' => $user->username,
                'nickname' => $user->nickname,
                'avatar_url' => $user->avatar_url,
                'bio' => $user->bio,
                'created_at' => $user->created_at,
                'phone' => $user->phone,
                'stats' => $stats,
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
            $currentUser = Auth::user();

            if (!$currentUser) {
                return response()->json([
                    'error' => 'Unauthorized'
                ], 401);
            }

            $followStatus = $this->userService->getFollowStatus($currentUser->id, $targetUserId);
            
            if ($followStatus === false) {
                return response()->json([
                    'error' => 'Có lỗi xảy ra khi kiểm tra trạng thái follow'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $followStatus
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách người theo dõi của một user
     */
    public function getFollowers(int $userId): JsonResponse
    {
        try {
            $followers = $this->userService->getFollowers($userId);
            
            if ($followers === false) {
                return response()->json([
                    'error' => 'Người dùng không tồn tại'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $followers
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách người mà một user đang theo dõi
     */
    public function getFollowing(int $userId): JsonResponse
    {
        try {
            $following = $this->userService->getFollowing($userId);
            
            if ($following === false) {
                return response()->json([
                    'error' => 'Người dùng không tồn tại'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $following
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy tất cả bài đăng của một user
     */
    public function getAllPostsByUser(int $userId): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            $currentUserId = $currentUser ? $currentUser->id : 0;

            $posts = $this->userService->getAllPostsByUser($userId, $currentUserId);
            
            if ($posts === false) {
                return response()->json([
                    'error' => 'Có lỗi xảy ra khi lấy bài đăng'
                ], 500);
            }

            return response()->json([
                'posts' => $posts
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error getting posts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách gợi ý kết bạn
     */
    public function getSuggestionUser(): JsonResponse
    {
        try {
            $currentUser = Auth::user();

            if (!$currentUser) {
                return response()->json([
                    'error' => 'Unauthorized'
                ], 401);
            }

            $suggestions = $this->userService->getSuggestionUser($currentUser->id);
            
            if ($suggestions === false) {
                return response()->json([
                    'error' => 'Có lỗi xảy ra khi lấy danh sách gợi ý kết bạn'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $suggestions
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
