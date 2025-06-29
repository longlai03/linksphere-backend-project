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
    public function sendVerificationCode(string $email): bool;
    public function verifyCode(string $email, string $code): bool;
    public function resetPassword(string $email, string $code, string $password): bool;
    
    // New methods for UserController
    public function getUserById(int $userId): mixed;
    public function getUsers(array $filters): mixed;
    public function getPublicProfile(int $userId): mixed;
    public function getFollowStatus(int $currentUserId, int $targetUserId): mixed;
    public function getFollowers(int $userId): mixed;
    public function getFollowing(int $userId): mixed;
    public function getAllPostsByUser(int $userId, int $currentUserId): mixed;
}
