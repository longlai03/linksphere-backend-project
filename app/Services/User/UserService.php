<?php

namespace App\Services\User;

use App\Services\Base\BaseService;

interface UserService extends BaseService
{
    public function loginUser(array $attributes): mixed;
    public function registerUser(array $attributes): mixed;
    public function getUserByToken(string $token): mixed;
    public function logOutUser(): mixed;
    public function updateUser(int $id, array $attributes): mixed;
    public function deleteUser(int $id): mixed;
    public function updateVerifyUser(int $id, bool $verify): bool;
}
