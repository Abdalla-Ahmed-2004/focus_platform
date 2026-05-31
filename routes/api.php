<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

require __DIR__ . '/api/auth.php';
require __DIR__ . '/api/public.php';
require __DIR__ . '/api/teacher.php';
require __DIR__ . '/api/student.php';
require __DIR__ . '/api/admin.php';

use Illuminate\Support\Facades\Redis;

Route::get('/test-redis', function () {
    // تخزين قيمة في الريديس
    Redis::set('user_name', 'Yahya');

    // استرجاع القيمة
    return Redis::get('user_name');
});
