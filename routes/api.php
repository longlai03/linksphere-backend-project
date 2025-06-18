<?php

use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\FollowerController;
use App\Http\Controllers\CommentController;

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');


Route::post('/register', [UsersController::class, 'register'])->name('register');
Route::post('/login', [UsersController::class, 'login'])->name('login');
Route::post('/forgot-password', [UsersController::class, 'forgotPassword'])->name('forgot-password');
Route::post('/forgot-password/send-code', [UsersController::class, 'sendResetCode']);
Route::post('/forgot-password/verify-code', [UsersController::class, 'verifyResetCode']);
Route::post('/forgot-password/reset', [UsersController::class, 'resetPassword']);

Route::middleware('auth:api')->group(function () {
    //auth
    Route::post('/logout', [UsersController::class, 'logout'])->name('logout');
    Route::get('/user/me', [UsersController::class, 'getUserByToken']);
    Route::put('/user/{userId}', [UsersController::class, 'updateUser'])->name('update-user');
    //post
    Route::get('/user/{userId}/post', [UsersController::class, 'getAllPostsByUser']);
    Route::get('/posts/feed', [PostController::class, 'getAllPost'])->name('posts.feed');
    Route::apiResource('post', PostController::class);

    // Follower routes
    Route::post('/follow', [FollowerController::class, 'follow']);
    Route::post('/unfollow', [FollowerController::class, 'unfollow']);
    Route::post('/accept-follow', [FollowerController::class, 'acceptFollow']);
    Route::post('/decline-follow', [FollowerController::class, 'declineFollow']);
    Route::post('/block', [FollowerController::class, 'block']);
    Route::post('/unblock', [FollowerController::class, 'unblock']);
    Route::get('/followers', [FollowerController::class, 'getFollowers']);
    Route::get('/following', [FollowerController::class, 'getFollowing']);
    Route::get('/pending-follow-requests', [FollowerController::class, 'getPendingFollowRequests']);
    Route::get('/blocked-users', [FollowerController::class, 'getBlockedUsers']);

    // Comment routes
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    Route::get('/comments/{comment}/replies', [CommentController::class, 'getReplies']);
});
