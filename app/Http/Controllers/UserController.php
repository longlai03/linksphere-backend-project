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

    public function getUsers(Request $request): JsonResponse
    {
        try {
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
