<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\Post;
use App\Models\VerificationCode;
use App\Repositories\User\UserRepository;
use App\Services\Base\BaseServiceImp;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;


class UserServiceImp extends BaseServiceImp implements UserService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @throws Exception
     */
    public function registerUser(array $attributes): array|false
    {
        try {
            $register = $this->userRepository->registerUser($attributes);
            if (!$register) {
                return false;
            }
            $token = app('tymon.jwt.auth')->fromUser($register);
            return [
                'user' => $register,
                'token' => $token,
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws Exception
     */
    public function loginUser(array $attributes): array | false
    {
        try {
            $login = $this->userRepository->loginUser($attributes);
            if (!$login || !Hash::check($attributes['password'], $login->password)) {
                return false;
            }
            $token = app('tymon.jwt.auth')->fromUser($login);
            return [
                'user' => $login,
                'token' => $token,
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getUserByToken(string $token): array|false
    {
        try {
            // Sử dụng JWTAuth để set token và lấy user
            $user = app('tymon.jwt.auth')->setToken($token)->toUser();
            if (!$user) {
                return false;
            }
            return [
                'user' => $user
            ];
        } catch (JWTException $e) {
            logger()->error('JWT Error: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            logger()->error('Error getUserByToken: ' . $e->getMessage());
            return false;
        }
    }
    public function logoutUser(): bool
    {
        try {
            $token = app('tymon.jwt.auth')->getToken();
            app('tymon.jwt.auth')->invalidate($token);
            return true;
        } catch (Exception $e) {
            logger()->error('Error logoutUser: ' . $e->getMessage());
            return false;
        }
    }
    public function updateUser(int $id, array $attributes): mixed
    {
        try {
            return $this->userRepository->updateUser($id, $attributes);
        } catch (Exception $e) {
            logger()->error($e->getMessage());
            return false;
        }
    }

    public function deleteUser(int $id): mixed
    {
        try {
            return $this->userRepository->deleteUser($id);
        } catch (Exception $e) {
            logger()->error($e->getMessage());
            return false;
        }
    }

    public function updateVerifyUser(int $id, bool $verify): bool
    {
        try {
            return $this->userRepository->updateVerifyUser($id, $verify);
        } catch (Exception $e) {
            logger()->error($e->getMessage());
            return false;
        }
    }

    public function sendVerificationCode(string $email): bool
    {
        try {
            $user = User::where('email', $email)->first();
            if (!$user) {
                return false;
            }

            // Tạo mã xác thực mới
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = now()->addMinutes(15); // Mã hết hạn sau 15 phút

            // Lưu mã vào database
            VerificationCode::create([
                'user_id' => $user->id,
                'code' => $code,
                'type' => 'password_reset',
                'expires_at' => $expiresAt,
            ]);
            logger()->error('call me');

            // Gửi email chứa mã xác thực
            Mail::send('emails.password-reset', ['code' => $code], function ($message) use ($email) {
                $message->to($email)
                    ->subject('Đặt lại mật khẩu');
            });
            return true;
        } catch (Exception $e) {
            logger()->error('Error sendVerificationCode: ' . $e->getMessage());
            return false;
        }
    }

    public function verifyCode(string $email, string $code): bool
    {
        try {
            $user = User::where('email', $email)->first();
            if (!$user) {
                return false;
            }

            $verificationCode = VerificationCode::where('user_id', $user->id)
                ->where('code', $code)
                ->where('type', 'password_reset')
                ->where('is_verified', false)
                ->where('expires_at', '>', now())
                ->latest()
                ->first();

            if (!$verificationCode) {
                return false;
            }

            // Đánh dấu mã đã được xác thực
            $verificationCode->update(['is_verified' => true]);

            return true;
        } catch (Exception $e) {
            logger()->error('Error verifyCode: ' . $e->getMessage());
            return false;
        }
    }

    public function resetPassword(string $email, string $code, string $password): bool
    {
        try {
            $user = User::where('email', $email)->first();
            if (!$user) {
                return false;
            }

            $verificationCode = VerificationCode::where('user_id', $user->id)
                ->where('code', $code)
                ->where('type', 'password_reset')
                ->where('is_verified', true)
                ->where('expires_at', '>', now())
                ->latest()
                ->first();

            if (!$verificationCode) {
                return false;
            }

            // Cập nhật mật khẩu mới
            $user->update([
                'password' => Hash::make($password)
            ]);

            // Xóa mã xác thực đã sử dụng
            $verificationCode->delete();

            return true;
        } catch (Exception $e) {
            logger()->error('Error resetPassword: ' . $e->getMessage());
            return false;
        }
    }

    // New methods for UserController
    public function getUserById(int $userId): mixed
    {
        try {
            $result = $this->userRepository->getUserById($userId);
            if (!$result) {
                return false;
            }

            $user = $result['user'];
            $stats = $result['stats'];

            // Kiểm tra xem user hiện tại có đang follow user này không
            $currentUser = auth()->user();
            $isFollowing = false;
            $followStatus = null;

            if ($currentUser && $currentUser->id !== $userId) {
                $followStatus = $this->userRepository->getFollowStatus($currentUser->id, $userId);
                $isFollowing = $followStatus === 'accepted';
            }

            return [
                'user' => $user,
                'stats' => $stats,
                'is_following' => $isFollowing,
                'follow_status' => $followStatus
            ];
        } catch (Exception $e) {
            logger()->error('Error getUserById: ' . $e->getMessage());
            return false;
        }
    }

    public function getUsers(array $filters): mixed
    {
        try {
            return $this->userRepository->getUsers($filters);
        } catch (Exception $e) {
            logger()->error('Error getUsers: ' . $e->getMessage());
            return false;
        }
    }

    public function getPublicProfile(int $userId): mixed
    {
        try {
            return $this->userRepository->getPublicProfile($userId);
        } catch (Exception $e) {
            logger()->error('Error getPublicProfile: ' . $e->getMessage());
            return false;
        }
    }

    public function getFollowStatus(int $currentUserId, int $targetUserId): mixed
    {
        try {
            return $this->userRepository->getFollowStatus($currentUserId, $targetUserId);
        } catch (Exception $e) {
            logger()->error('Error getFollowStatus: ' . $e->getMessage());
            return false;
        }
    }

    public function getFollowers(int $userId): mixed
    {
        try {
            return $this->userRepository->getFollowers($userId);
        } catch (Exception $e) {
            logger()->error('Error getFollowers: ' . $e->getMessage());
            return false;
        }
    }

    public function getFollowing(int $userId): mixed
    {
        try {
            return $this->userRepository->getFollowing($userId);
        } catch (Exception $e) {
            logger()->error('Error getFollowing: ' . $e->getMessage());
            return false;
        }
    }

    public function getAllPostsByUser(int $userId, int $currentUserId): mixed
    {
        try {
            $posts = $this->userRepository->getAllPostsByUser($userId, $currentUserId);
            
            // Thêm thông tin likes và liked status
            $posts = $posts->map(function ($post) use ($currentUserId) {
                $post->likesCount = $post->reactions()->count();
                $post->liked = $currentUserId ? $post->reactions()->where('user_id', $currentUserId)->exists() : false;
                return $post;
            });

            return $posts;
        } catch (Exception $e) {
            logger()->error('Error getAllPostsByUser: ' . $e->getMessage());
            return false;
        }
    }

    public function getSuggestionUser(int $currentUserId): mixed
    {
        try {
            return $this->userRepository->getSuggestionUser($currentUserId);
        } catch (Exception $e) {
            logger()->error('Error getSuggestionUser: ' . $e->getMessage());
            return false;
        }
    }
}
