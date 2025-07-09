<?php

namespace App\Http\Controllers;

use App\Helpers\AvatarHelper;
use App\Http\Requests\UserRequest;
use App\Services\User\UserService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AuthController extends Controller
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
                ], 400);
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
                ], 400);
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
                'error' => 'Unauthorized to update this user'
            ], 403);
        }

        try {
            $updateData = $request->validated();

            if (!empty($updateData['avatar_url'])) {
                try {
                    $updateData['avatar_url'] = AvatarHelper::processAvatarBase64(
                        $updateData['avatar_url'],
                        $user->avatar_url
                    );
                } catch (Exception $e) {
                    return response()->json([
                        'error' => $e->getMessage()
                    ], 400);
                }
            }

            $updatedUser = $this->userService->updateUser($userId, $updateData);

            if ($updatedUser) {
                return response()->json([
                    'message' => 'User updated successfully',
                    'user' => $updatedUser
                ]);
            }

            return response()->json([
                'error' => 'Failed to update user'
            ], 500);
        } catch (Exception $e) {
            Log::error('Update user error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Có lỗi xảy ra khi cập nhật thông tin: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUserByToken(): JsonResponse
    {
        try {
            $userData = $this->userService->getUserByToken(JWTAuth::getToken());
            if ($userData && isset($userData['user'])) {
                return response()->json([
                    'user' => $userData['user']
                ]);
            }
            return response()->json([
                'error' => 'User not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error getting user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Làm mới access token từ refresh token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $oldToken = JWTAuth::getToken();
            if (!$oldToken) {
                return response()->json(['error' => 'Token not provided'], 401);
            }
            $newToken = JWTAuth::refresh($oldToken);
            return response()->json([
                'token' => $newToken
            ]);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        } catch (Exception $e) {
            return response()->json(['error' => 'Could not refresh token: ' . $e->getMessage()], 500);
        }
    }
}
