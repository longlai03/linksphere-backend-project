<?php

namespace App\Providers;

use App\Repositories\User\UserRepository;
use App\Repositories\User\UserRepositoryElq;
use App\Repositories\Post\PostRepository;
use App\Repositories\Post\PostRepositoryElq;
use App\Repositories\Follower\FollowerRepository;
use App\Repositories\Follower\FollowerRepositoryElq;
use App\Repositories\Notification\NotificationRepository;
use App\Repositories\Notification\NotificationRepositoryElq;
use App\Repositories\Comment\CommentRepository;
use App\Repositories\Comment\CommentRepositoryElq;
use App\Repositories\Conversation\ConversationRepository;
use App\Repositories\Conversation\ConversationRepositoryElq;
use App\Services\User\UserService;
use App\Services\User\UserServiceImp;
use App\Services\Post\PostService;
use App\Services\Post\PostServiceImp;
use App\Services\Follower\FollowerService;
use App\Services\Follower\FollowerServiceImp;
use App\Services\Notification\NotificationService;
use App\Services\Notification\NotificationServiceImp;
use App\Services\Comment\CommentService;
use App\Services\Comment\CommentServiceImp;
use App\Services\Conversation\ConversationService;
use App\Services\Conversation\ConversationServiceImp;
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
        // User bindings
        $this->app->bind(UserRepository::class, UserRepositoryElq::class);
        $this->app->bind(UserService::class, UserServiceImp::class);
        // Post bindings
        $this->app->bind(PostRepository::class, PostRepositoryElq::class);
        $this->app->bind(PostService::class, PostServiceImp::class);
        // Follower bindings
        $this->app->bind(FollowerRepository::class, FollowerRepositoryElq::class);
        $this->app->bind(FollowerService::class, FollowerServiceImp::class);
        // Notification bindings
        $this->app->bind(NotificationRepository::class, NotificationRepositoryElq::class);
        $this->app->bind(NotificationService::class, NotificationServiceImp::class);
        // Comment bindings
        $this->app->bind(CommentRepository::class, CommentRepositoryElq::class);
        $this->app->bind(CommentService::class, CommentServiceImp::class);
        // Conversation bindings
        $this->app->bind(ConversationRepository::class, ConversationRepositoryElq::class);
        $this->app->bind(ConversationService::class, ConversationServiceImp::class);
    }
}
