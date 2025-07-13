<?php

use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FollowerController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ConversationController;

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
Route::post('/forgot-password/send-code', [AuthController::class, 'sendResetCode']);
Route::post('/forgot-password/verify-code', [AuthController::class, 'verifyResetCode']);
Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword']);
Route::post('/refresh', [AuthController::class, 'refreshToken']);

Route::middleware('auth:api')->group(function () {
    //auth
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user/me', [AuthController::class, 'getUserByToken']);
    Route::put('/user/{userId}', [AuthController::class, 'updateUser'])->name('update-user');
    //post
    Route::get('/user/{userId}/post', [UserController::class, 'getAllPostsByUser']);
    Route::get('/posts/feed', [PostController::class, 'getFeedPost'])->name('posts.feed');
    Route::apiResource('post', PostController::class);
    Route::post('/post/{id}/like', [PostController::class, 'like']);
    Route::delete('/post/{id}/like', [PostController::class, 'unlike']);

    // User routes
    Route::get('/users', [UserController::class, 'getUsers']);
    Route::get('/users/suggestions', [UserController::class, 'getSuggestionUser']);
    Route::get('/users/{userId}', [UserController::class, 'getUserById']);
    Route::get('/users/{userId}/profile', [UserController::class, 'getPublicProfile']);
    Route::get('/users/{userId}/follow-status', [UserController::class, 'getFollowStatus']);
    Route::get('/users/{userId}/followers', [UserController::class, 'getFollowers']);
    Route::get('/users/{userId}/following', [UserController::class, 'getFollowing']);

    // Follower routes
    Route::post('/user/{userId}/follow', [FollowerController::class, 'follow']);
    Route::post('/user/{userId}/unfollow', [FollowerController::class, 'unfollow']);
    Route::post('/accept-follow', [FollowerController::class, 'acceptFollow']);
    Route::post('/decline-follow', [FollowerController::class, 'declineFollow']);
    Route::get('/pending-follow-requests', [FollowerController::class, 'getPendingFollowRequests']);

    // Comment routes
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    Route::get('/comments/{comment}/replies', [CommentController::class, 'getReplies']);

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications/by-sender', [NotificationController::class, 'deleteBySenderAndType']);

    // Conversation (Chat) routes
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{conversationId}', [ConversationController::class, 'show']);
    Route::get('/conversations/{userId}/direct', [ConversationController::class, 'getOrCreateDirect']);
    Route::get('/conversations/{conversationId}/messages', [ConversationController::class, 'getMessages']);
    Route::post('/conversations/{conversationId}/messages', [ConversationController::class, 'sendMessage']);
    Route::post('/conversations/{conversationId}/read', [ConversationController::class, 'markAsRead']);
    Route::delete('/conversations/{conversationId}', [ConversationController::class, 'destroy']);
    
    // Search users for messaging
    Route::get('/users/search-for-messages', [ConversationController::class, 'searchUsers']);

});
