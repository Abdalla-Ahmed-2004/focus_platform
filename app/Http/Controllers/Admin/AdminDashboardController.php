<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    /**
     * Return general analytics for the platform.
     */
    public function index(): JsonResponse
    {
        // Placeholder analytics; replace with real queries as needed
        $totalStudents = User::role('student')->count();
        $totalTeachers = User::role('teacher')->count();
        $totalCourses  = 0; // Replace with Course::count() if you have a Course model

        return response()->json([
            'total_students' => $totalStudents,
            'total_teachers' => $totalTeachers,
            'total_courses'  => $totalCourses,
        ], 200);
    }
}
