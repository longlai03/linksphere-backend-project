<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\User\UserService;
use Exception;
use http\Env\Request;
use Illuminate\Http\JsonResponse;
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

        $validatedData = $request->validated();

        if ($request->hasFile('avatar_url')) {
            $request->validate([
                'avatar_url' => 'image|mimes:jpeg,png,jpg,gif,svg',
            ]);
            // Lưu ảnh vào thư mục 'avatars' và lấy đường dẫn
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            // Lưu đường dẫn ảnh vào cơ sở dữ liệu
            $validatedData['avatar_url'] = 'storage/' . $avatarPath;
        }

        try {
            $userToUpdate->update($validatedData); // Cập nhật thông tin người dùng
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

    public
    function getUserByToken(): JsonResponse
    {
        try {
            $user = auth()->user();
            return response()->json([
                'user' => $user
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Unauthorized: '.$e], 401);
        }
    }
}
