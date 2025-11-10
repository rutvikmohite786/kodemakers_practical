<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('/blogs', [BlogController::class, 'index'])->name('blogs.index');
        Route::post('/blogs', [BlogController::class, 'store'])->name('blogs.store');
        Route::put('/blogs/{blog}', [BlogController::class, 'update'])->name('blogs.update');
        Route::patch('/blogs/{blog}', [BlogController::class, 'update']);
        Route::delete('/blogs/{blog}', [BlogController::class, 'destroy'])->name('blogs.destroy');
        Route::post('/blogs/{blog}/like-toggle', [BlogController::class, 'toggleLike'])->name('blogs.like-toggle');
    });
});

