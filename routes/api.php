<?php

use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FollowerController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\NotificationController;

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');


Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
Route::post('/forgot-password/send-code', [AuthController::class, 'sendResetCode']);
Route::post('/forgot-password/verify-code', [AuthController::class, 'verifyResetCode']);
Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword']);

Route::middleware('auth:api')->group(function () {
    //auth
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user/me', [AuthController::class, 'getUserByToken']);
    Route::put('/user/{userId}', [AuthController::class, 'updateUser'])->name('update-user');
    //post
    Route::get('/user/{userId}/post', [AuthController::class, 'getAllPostsByUser']);
    Route::get('/posts/feed', [PostController::class, 'getFeedPost'])->name('posts.feed');
    Route::apiResource('post', PostController::class);

    // User routes
    Route::get('/users', [UserController::class, 'getUsers']);
    Route::get('/users/{userId}', [UserController::class, 'getUserById']);
    Route::get('/users/{userId}/profile', [UserController::class, 'getPublicProfile']);
    Route::get('/users/{userId}/follow-status', [UserController::class, 'getFollowStatus']);

    // Follower routes
    Route::post('/follow', [FollowerController::class, 'follow']);
    Route::post('/unfollow', [FollowerController::class, 'unfollow']);
    Route::post('/accept-follow', [FollowerController::class, 'acceptFollow']);
    Route::post('/decline-follow', [FollowerController::class, 'declineFollow']);
    Route::get('/followers', [FollowerController::class, 'getFollowers']);
    Route::get('/following', [FollowerController::class, 'getFollowing']);
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
});
