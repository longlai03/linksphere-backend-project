<?php

namespace App\Providers;

use App\Repositories\User\UserRepository;
use App\Repositories\User\UserRepositoryElq;
use App\Services\User\UserService;
use App\Services\User\UserServiceImp;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->bind(UserRepository::class, UserRepositoryElq::class);
        $this->app->bind(UserService::class, UserServiceImp::class);
    }
}
