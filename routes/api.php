<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StudentAnswerController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SubtopicController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\VideoController;
use App\Models\Quiz;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
// Content Browsing (Student)
Route::get('subjects', [SubjectController::class, 'index']);
Route::get('subjects/{subject}/units', [UnitController::class, 'index']);
Route::get('units/{unit}/lessons', [LessonController::class, 'index']);
Route::get('lesson/{lesson}/subtopics', [SubtopicController::class, 'index']);
// 
Route::get('subjects/{subject}/teachers', [TeacherController::class, 'index']);
Route::get('teachers/{teacher}/lessons', [TeacherController::class, 'show']);
// 
Route::get('teachers/{teacher}/lessons/{lesson}/content', [TeacherController::class, 'showContent'])->scopeBindings();

Route::get('search/teachers', [SearchController::class, 'search']);
// Apply middleware to all routes
Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::put('user', [AuthController::class, 'updateProfile']); // New: Profile update


    // Route::get('lessons/{lesson}', [LessonController::class, 'show']);

    // Teacher Content Management (already existed, scoped in controller)
    Route::apiResource('videos', VideoController::class)->middleware(['role:teacher']);
    Route::apiResource('quizzes', QuizController::class)->middleware(['role:teacher']);

    // // Quiz Taking Flow (New Incremental Flow)
    // Route::post('quizzes/{quiz}/attempt', [QuizAttemptController::class, 'start'])->middleware(['role:student']);
    Route::post('attempts/{attempt}/answer', [QuizAttemptController::class, 'answer'])->middleware(['role:student']);
    // Route::post('attempts/{attempt}/submit', [QuizAttemptController::class, 'submit'])->middleware(['role:student']);
    // Route::get('attempts/{attempt}/results', [QuizAttemptController::class, 'results'])->middleware(['role:student']);

    // Legacy / Other

   
    Route::get('quizzes-details/{quiz}', [TeacherController::class, 'showQuiz'])->scopeBindings();
    Route::post('quiz/{quiz}/answer', [StudentAnswerController::class, 'answer'])->middleware(['role:student']);
    Route::get('students/attempts', [QuizAttemptController::class, 'index'])->middleware(['role:student']);
    Route::get('subjects/{subject}/subtopics', [SubjectController::class, 'showSubtopics']);
});

// Route::middleware(['auth:api', 'role:teacher'])->group(function () {
//     Route::apiResource('subjects', SubjectController::class);

//     Route::prefix('subjects/{subject}')->group(function () {
//         Route::apiResource('subtopics', SubtopicController::class);
//     });
// });
