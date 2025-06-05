<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Repositories\Base\BaseRepositoryElq;
use Illuminate\Support\Facades\Hash;

class UserRepositoryElq extends BaseRepositoryElq implements UserRepository
{
    public function getModel(): string
    {
        return User::class;
    }

    public function registerUser(array $attributes): mixed
    {

        return $this->create([
            'email' => $attributes['email'],
            'password' => Hash::make($attributes['password']),
            'username' => $attributes['username'],
            'nickname' => $attributes['nickname'],
        ]);
    }

    public function loginUser(array $attributes): mixed
    {
        return User::query()->where('email', $attributes['email'])->first();
    }
    public function updateUser(int $id, array $attributes): mixed
    {
        return $this->update($id, $attributes);
    }

    public function deleteUser(int $id): mixed
    {
        return $this->delete($id);
    }

    public function updateVerifyUser(int $id, bool $verify): bool
    {
        $user = $this->find($id);
        if (!$user) return false;

        $user->email_verified_at = now();
        $user->save();

        info('Email verified for user:', ['id' => $id]);
        return true;
    }
}
