<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminUserController extends Controller
{
    /**
     * List all users with optional filtering by role or search by name/email.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'role' => 'nullable|string|in:admin,teacher,student',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);
        $role = $request->query('role');
        $search = $request->query('search');

        $query = User::query();

        // Filter by role - with safe handling
        if ($role) {
            try {
                $query->role($role);
            } catch (\Exception $e) {
                // Role doesn't exist, return empty results
                Log::warning("Role '{$role}' not found: " . $e->getMessage());
                return response()->json([
                    'data' => [],
                    'pagination' => [
                        'total' => 0,
                        'per_page' => $perPage,
                        'current_page' => $page,
                        'last_page' => 0,
                        'from' => 0,
                        'to' => 0,
                    ],
                ], 200);
            }
        }

        // Search by name or email
        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }

        // Load relationships
        $query->with(['student', 'teacher', 'roles']);

        $users = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $users->items(),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
        ], 200);
    }

    /**
     * Get detailed information about a specific user.
     */
    public function show(int $userId): JsonResponse
    {
        try {
            $user = User::with(['student', 'teacher', 'roles', 'permissions'])->find($userId);

            if (!$user) {
                return response()->json([
                    'message' => 'User not found.',
                ], 404);
            }

            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_picture' => $user->profile_picture,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->permissions->pluck('name'),
                'student' => $user->student,
                'teacher' => $user->teacher,
            ];

            // If user is a student, add student stats
            if ($user->student) {
                $data['student_stats'] = [
                    'total_quiz_attempts' => $user->student->quizzesAttempt()->count(),
                    'avg_quiz_score' => $user->student->quizzesAttempt()->avg('score'),
                    'total_lesson_attempts' => $user->student->lessonAttempts()->count(),
                    'total_evaluations' => $user->student->subtopicEvaluations()->count(),
                ];
            }

            // If user is a teacher, add teacher stats
            if ($user->teacher) {
                $data['teacher_stats'] = [
                    'subject' => $user->teacher->subject?->title,
                    'total_quizzes' => $user->teacher->quizzes()->count(),
                    'total_videos' => $user->teacher->videos()->count(),
                    'total_lesson_attempts' => $user->teacher->lessonAttempts()->count(),
                ];
            }

            return response()->json($data, 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error fetching user details', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error fetching user details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user information (name, email, profile_picture).
     */
    public function update(Request $request, int $userId): JsonResponse
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'message' => 'User not found.',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email|unique:users,email,' . $userId,
                'profile_picture' => 'nullable|url',
            ]);

            $user->update($validated);

            return response()->json([
                'message' => 'User updated successfully.',
                'user' => $user->load(['student', 'teacher', 'roles']),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error updating user', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error updating user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a user (cascading delete for related records).
     */
    public function destroy(int $userId): JsonResponse
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'message' => 'User not found.',
                ], 404);
            }

            // Prevent deleting current authenticated admin
            if ($user->id === auth('api')->id()) {
                return response()->json([
                    'message' => 'Cannot delete your own account.',
                ], 403);
            }

            $userName = $user->name;
            $userEmail = $user->email;

            // Delete related data based on user role
            if ($user->student) {
                $user->student()->delete();
            }

            if ($user->teacher) {
                $user->teacher()->delete();
            }

            // Delete user
            $user->delete();

            Log::info('Admin: User deleted', ['deleted_user_id' => $userId, 'user_name' => $userName, 'user_email' => $userEmail]);

            return response()->json([
                'message' => 'User deleted successfully.',
                'deleted_user' => [
                    'id' => $userId,
                    'name' => $userName,
                    'email' => $userEmail,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error deleting user', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error deleting user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(Request $request, int $userId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role' => 'required|string|in:admin,teacher,student',
            ]);

            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'message' => 'User not found.',
                ], 404);
            }

            // Sync role (replace existing roles with new one)
            $user->syncRoles($validated['role']);

            return response()->json([
                'message' => "Role '{$validated['role']}' assigned to user successfully.",
                'user' => $user->load('roles'),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error assigning role', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error assigning role.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a role from a user.
     */
    public function removeRole(Request $request, int $userId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role' => 'required|string|in:admin,teacher,student',
            ]);

            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'message' => 'User not found.',
                ], 404);
            }

            $user->removeRole($validated['role']);

            return response()->json([
                'message' => "Role '{$validated['role']}' removed from user successfully.",
                'user' => $user->load('roles'),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error removing role', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error removing role.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get activity/statistics for a user.
     */
    public function userActivity(int $userId): JsonResponse
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'message' => 'User not found.',
                ], 404);
            }

            $activity = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'created_at' => $user->created_at,
                'last_update' => $user->updated_at,
            ];

            // Add student activity
            if ($user->student) {
                $student = $user->student;
                $activity['student_activity'] = [
                    'last_quiz_attempt' => $student->quizzesAttempt()->latest('created_at')->first()?->created_at,
                    'total_quiz_attempts' => $student->quizzesAttempt()->count(),
                    'avg_quiz_score' => round($student->quizzesAttempt()->avg('score') ?? 0, 2),
                    'last_lesson_attempt' => $student->lessonAttempts()->latest('created_at')->first()?->created_at,
                    'total_lesson_attempts' => $student->lessonAttempts()->count(),
                    'total_evaluations' => $student->subtopicEvaluations()->count(),
                ];
            }

            // Add teacher activity
            if ($user->teacher) {
                $teacher = $user->teacher;
                $activity['teacher_activity'] = [
                    'subject' => $teacher->subject?->title,
                    'total_quizzes_created' => $teacher->quizzes()->count(),
                    'total_videos_created' => $teacher->videos()->count(),
                    'student_interactions' => $teacher->lessonAttempts()->count(),
                    'last_activity' => $teacher->updated_at,
                ];
            }

            return response()->json($activity, 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error fetching user activity', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error fetching user activity.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user statistics dashboard.
     */
    public function statistics(): JsonResponse
    {
        try {
            // Count total users
            $totalUsers = User::count();

            // Count by actual relationships instead of roles
            $studentCount = Student::count();
            $teacherCount = Teacher::count();

            // Admin users are those with admin role
            $adminCount = 0;
            try {
                $adminCount = User::role('admin')->count();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Admin role lookup: ' . $e->getMessage());
            }

            $stats = [
                'total_users' => $totalUsers,
                'total_admins' => $adminCount,
                'total_teachers' => $teacherCount,
                'total_students' => $studentCount,
                'users_by_role' => [
                    'admin' => $adminCount,
                    'teacher' => $teacherCount,
                    'student' => $studentCount,
                ],
                'new_users_today' => User::whereDate('created_at', today())->count(),
                'new_users_this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'new_users_this_month' => User::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            ];

            return response()->json($stats, 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error fetching user statistics', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error fetching user statistics.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
