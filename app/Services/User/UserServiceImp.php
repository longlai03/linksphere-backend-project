<?php

namespace App\Services\User;

use App\Models\User;
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
            if(!$register){
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
}
