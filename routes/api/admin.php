<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminDashboardController;

Route::prefix('admin')
    ->middleware(['auth:api', 'role:admin']) // JWT auth and Spatie role protection
    ->group(function () {
        // Admin Dashboard Analytics
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        // Admin User Management
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'createNewAdmin']);
    });
