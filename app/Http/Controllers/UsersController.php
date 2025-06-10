<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Post;
use App\Models\User;
use App\Services\User\UserService;
use Exception;
use http\Env\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class UsersController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function register(UserRequest $request): JsonResponse
    {
        try {
            $register = $this->userService->registerUser($request->validated());
            if (!$register) {
                return response()->json([
                    'error' => 'Lỗi đăng ký'
                ], 401);
            }
            return response()->json([
                'message' => 'Register successfully',
                'user' => $register['user'],
                'token' => $register['token'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Register error: ' . $e->getMessage(),
            ]);
        }
    }

    public function login(UserRequest $request): JsonResponse
    {
        try {
            $login = $this->userService->loginUser($request->validated());
            if (!$login) {
                return response()->json([
                    'error' => 'Thông tin đăng nhập không hợp lệ'
                ], 401);
            }
            return response()->json([
                'message' => 'Login successfully',
                'user' => $login['user'],
                'token' => $login['token'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Login error: ' . $e->getMessage(),
            ]);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $this->userService->logoutUser();
            return response()->json([
                'message' => 'Logout successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Logout failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function forgotPassword(UserRequest $request)
    {
        //
    }

    public function resetPassword(UserRequest $request)
    {
        //
    }

    public function updateUser(UserRequest $request, int $userId): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'error' => 'User not found or unauthorized'
            ], 401);
        }

        if ($user->id !== $userId) {
            return response()->json([
                'error' => 'You are not authorized to update this user.'
            ], 403);
        }

        $userToUpdate = User::query()->find($userId);
        if (!$userToUpdate) {
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }
        // Validate input
        $data = $request->validated();

        // Handle avatar upload if present
        if (!empty($data['avatar_url'])) {
            $avatarBase64 = $data['avatar_url'];

            if (preg_match('/^data:image\/(jpeg|png|jpg);base64,/', $avatarBase64)) {
                [$type, $imageData] = explode(';', $avatarBase64);
                [, $imageData] = explode(',', $imageData);

                $imageBinary = base64_decode($imageData);

                if ($imageBinary === false) {
                    return response()->json(['error' => 'Failed to decode image'], 400);
                }

                $fileName = 'avatar_' . time() . '.png';
                $path = 'avatars/' . $fileName;

                Storage::disk('public')->put($path, $imageBinary);

                $userToUpdate->avatar_url = 'storage/' . $path;
            } else {
                return response()->json([
                    'error' => 'Invalid Base64 image string'
                ], 400);
            }
        }

        try {
            foreach ($data as $key => $value) {
                if ($key !== 'avatar_url') {
                    $userToUpdate->$key = $value;
                }
            }
            $userToUpdate->save();

            return response()->json([
                'message' => 'User profile updated successfully',
                'user' => $userToUpdate
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUserByToken(): JsonResponse
    {
        try {
            $user = auth()->user();
            return response()->json([
                'user' => $user
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Unauthorized: ' . $e], 401);
        }
    }
    public function getAllPostsByUser(int $userId): JsonResponse
    {
        try {
            $viewer = auth()->user();

            // Lấy các bài viết của người dùng $userId
            $query = Post::query()->where('user_id', $userId)
                ->with(['media.attachment'])
                ->orderByDesc('created_at');

            // Nếu người xem không phải chủ bài viết, lọc theo privacy
            if ($viewer->id !== $userId) {
                $query->where('privacy', '!=', 'private');
            }

            $posts = $query->get();

            return response()->json([
                'posts' => $posts
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error fetching user posts: ' . $e->getMessage(),
            ], 500);
        }
    }
}
