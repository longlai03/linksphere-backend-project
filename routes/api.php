<?php

use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');


Route::post('/register', [UsersController::class, 'register'])->name('register');
Route::post('/login', [UsersController::class, 'login'])->name('login');
Route::post('/forgot-password', [UsersController::class, 'forgotPassword'])->name('forgot-password');

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [UsersController::class, 'logout'])->name('logout');
    Route::get('/user/me', [UsersController::class, 'getUserByToken']);
    Route::put('/user/{userId}', [UsersController::class, 'updateUser'])->name('update-user');
    Route::get('/user/{userId}/post', [UsersController::class, 'getAllPostsByUser']);
    Route::apiResource('post', PostController::class);
});
