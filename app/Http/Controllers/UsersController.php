<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Post;
use App\Models\User;
use App\Services\User\UserService;
use Exception;
use Illuminate\Http\Request;
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

    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'identity' => 'required|string',
            ]);

            $result = $this->userService->sendVerificationCode($request->input('identity'));
            if ($result) {
                return response()->json([
                    'message' => 'Mã xác thực đã được gửi về email của bạn.'
                ]);
            }
            return response()->json([
                'error' => 'Không thể gửi mã xác thực. Vui lòng kiểm tra lại thông tin.'
            ], 400);
        } catch (Exception $e) {
            Log::error('Forgot password error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Có lỗi xảy ra khi xử lý yêu cầu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gửi mã xác thực về email
     */
    public function sendResetCode(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $result = $this->userService->sendVerificationCode($request->input('email'));
            if ($result) {
                return response()->json([
                    'message' => 'Mã xác thực đã được gửi về email.'
                ]);
            }
            return response()->json([
                'error' => 'Không thể gửi mã xác thực.'
            ], 400);
        } catch (Exception $e) {
            Log::error('Send reset code error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Có lỗi xảy ra khi gửi mã xác thực: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xác thực mã xác thực
     */
    public function verifyResetCode(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'code' => 'required|string',
            ]);

            $result = $this->userService->verifyCode($request->input('email'), $request->input('code'));
            if ($result) {
                return response()->json([
                    'message' => 'Mã xác thực hợp lệ.'
                ]);
            }
            return response()->json([
                'error' => 'Mã xác thực không hợp lệ hoặc đã hết hạn.'
            ], 400);
        } catch (Exception $e) {
            Log::error('Verify reset code error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Có lỗi xảy ra khi xác thực mã: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đặt lại mật khẩu
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'code' => 'required|string',
                'password' => 'required|string|min:6|confirmed',
            ]);

            $result = $this->userService->resetPassword(
                $request->input('email'),
                $request->input('code'),
                $request->input('password')
            );

            if ($result) {
                return response()->json([
                    'message' => 'Đặt lại mật khẩu thành công.'
                ]);
            }
            return response()->json([
                'error' => 'Không thể đặt lại mật khẩu.'
            ], 400);
        } catch (Exception $e) {
            Log::error('Reset password error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Có lỗi xảy ra khi đặt lại mật khẩu: ' . $e->getMessage()
            ], 500);
        }
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
