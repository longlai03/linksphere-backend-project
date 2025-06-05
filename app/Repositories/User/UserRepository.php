<?php

namespace App\Repositories\User;

use App\Repositories\Base\BaseRepository;

interface UserRepository extends BaseRepository
{
    public function registerUser(array $attributes): mixed;
    public function loginUser(array $attributes): mixed;
    public function updateUser(int $id, array $attributes): mixed;
    public function deleteUser(int $id): mixed;
    public function updateVerifyUser(int $id, bool $verify): bool;

}
