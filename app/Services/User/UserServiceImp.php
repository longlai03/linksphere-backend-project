<?php

namespace App\Services\User;

use App\Repositories\User\UserRepository;
use App\Services\Base\BaseServiceImp;
use Exception;
use Illuminate\Support\Facades\Hash;
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
}
