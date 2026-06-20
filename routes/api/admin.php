<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminContentController;

Route::prefix('admin')
    ->middleware(['auth:api', 'role:admin']) // JWT auth and Spatie role protection
    ->group(function () {
        // ============================================
        // DASHBOARD & ANALYTICS
        // ============================================
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        // ============================================
        // USER MANAGEMENT
        // ============================================
        Route::prefix('users')->group(function () {
            // List and statistics
            Route::get('/', [AdminUserController::class, 'index']);
            Route::get('/statistics', [AdminUserController::class, 'statistics']);

            // User operations
            Route::get('/{userId}', [AdminUserController::class, 'show']);
            Route::put('/{userId}', [AdminUserController::class, 'update']);
            Route::delete('/{userId}', [AdminUserController::class, 'destroy']);

            // Role management
            Route::post('/{userId}/assign-role', [AdminUserController::class, 'assignRole']);
            Route::post('/{userId}/remove-role', [AdminUserController::class, 'removeRole']);

            // User activity
            Route::get('/{userId}/activity', [AdminUserController::class, 'userActivity']);
        });

        // ============================================
        // CONTENT MANAGEMENT
        // ============================================
        Route::prefix('content')->group(function () {
            // Content overview
            Route::get('/statistics', [AdminContentController::class, 'contentStatistics']);

            // Subject management
            Route::prefix('subjects')->group(function () {
                Route::get('/', [AdminContentController::class, 'listSubjects']);
                Route::get('/{subjectId}', [AdminContentController::class, 'showSubject']);
                Route::delete('/{subjectId}', [AdminContentController::class, 'deleteSubject']);
            });

            // Quiz management
            Route::prefix('quizzes')->group(function () {
                Route::get('/', [AdminContentController::class, 'listQuizzes']);
                Route::get('/{quizId}', [AdminContentController::class, 'showQuiz']);
                Route::delete('/{quizId}', [AdminContentController::class, 'deleteQuiz']);
            });

            // Video management
            Route::prefix('videos')->group(function () {
                Route::get('/', [AdminContentController::class, 'listVideos']);
                Route::get('/{videoId}', [AdminContentController::class, 'showVideo']);
                Route::delete('/{videoId}', [AdminContentController::class, 'deleteVideo']);
            });

            // Lesson management
            Route::prefix('lessons')->group(function () {
                Route::get('/', [AdminContentController::class, 'listLessons']);
                Route::delete('/{lessonId}', [AdminContentController::class, 'deleteLesson']);
            });
        });
    });
