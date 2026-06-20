<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Quiz;
use App\Models\Video;
use App\Models\Lesson;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AdminContentController extends Controller
{
    /**
     * Get content statistics overview.
     */
    public function contentStatistics(): JsonResponse
    {
        try {
            $stats = [
                'total_subjects' => Subject::count(),
                'total_units' => Unit::count(),
                'total_lessons' => Lesson::count(),
                'total_quizzes' => Quiz::count(),
                'total_videos' => Video::count(),
                'subjects_with_units' => Subject::withCount('units')->get(['id', 'title', 'code', 'units_count']),
                'recent_quizzes' => Quiz::with(['teacher:id', 'teacher.user:id,name'])
                    ->latest('created_at')
                    ->limit(10)
                    ->get(['id', 'title', 'teacher_id', 'total_marks', 'created_at']),
                'recent_videos' => Video::with(['teacher:id', 'teacher.user:id,name'])
                    ->latest('created_at')
                    ->limit(10)
                    ->get(['id', 'title', 'teacher_id', 'url', 'created_at']),
            ];

            return response()->json($stats, 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error fetching content statistics', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error fetching content statistics.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all subjects with optional search/filter.
     */
    public function listSubjects(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'search' => 'nullable|string|max:255',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ]);

            $perPage = $request->query('per_page', 15);
            $page = $request->query('page', 1);
            $search = $request->query('search');

            $query = Subject::query();

            if ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }

            $subjects = $query->withCount(['units', 'teachers'])
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data' => $subjects->items(),
                'pagination' => [
                    'total' => $subjects->total(),
                    'per_page' => $subjects->perPage(),
                    'current_page' => $subjects->currentPage(),
                    'last_page' => $subjects->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error listing subjects', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error listing subjects.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get subject details with units and lessons.
     */
    public function showSubject(int $subjectId): JsonResponse
    {
        try {
            $subject = Subject::with(['units.lessons.subtopics', 'teachers.user:id,name'])
                ->find($subjectId);

            if (!$subject) {
                return response()->json([
                    'message' => 'Subject not found.',
                ], 404);
            }

            $data = [
                'id' => $subject->id,
                'title' => $subject->title,
                'code' => $subject->code,
                'created_at' => $subject->created_at,
                'units_count' => $subject->units()->count(),
                'lessons_count' => $subject->units()->with('lessons')->get()->sum(fn($u) => $u->lessons->count()),
                'teachers' => $subject->teachers->map(fn($t) => [
                    'id' => $t->id,
                    'name' => $t->user->name,
                    'email' => $t->user->email,
                ]),
                'units' => $subject->units->map(fn($u) => [
                    'id' => $u->id,
                    'title' => $u->title,
                    'lessons_count' => $u->lessons->count(),
                ]),
            ];

            return response()->json($data, 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error fetching subject details', ['subject_id' => $subjectId, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error fetching subject details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a subject and all related content (cascade).
     */
    public function deleteSubject(int $subjectId): JsonResponse
    {
        try {
            $subject = Subject::find($subjectId);

            if (!$subject) {
                return response()->json([
                    'message' => 'Subject not found.',
                ], 404);
            }

            $subjectTitle = $subject->title;
            $subjectCode = $subject->code;

            // Clear cache
            Cache::forget('subjects_all_all');

            // Delete subject (cascade will handle units, lessons, etc.)
            $subject->delete();

            Log::info('Admin: Subject deleted', ['deleted_subject_id' => $subjectId, 'subject_title' => $subjectTitle]);

            return response()->json([
                'message' => 'Subject and all related content deleted successfully.',
                'deleted_subject' => [
                    'id' => $subjectId,
                    'title' => $subjectTitle,
                    'code' => $subjectCode,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error deleting subject', ['subject_id' => $subjectId, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error deleting subject.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all quizzes with optional filter by teacher/subject.
     */
    public function listQuizzes(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'search' => 'nullable|string|max:255',
                'teacher_id' => 'nullable|integer',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ]);

            $perPage = $request->query('per_page', 15);
            $page = $request->query('page', 1);
            $search = $request->query('search');
            $teacherId = $request->query('teacher_id');

            $query = Quiz::query();

            if ($search) {
                $query->where('title', 'like', "%{$search}%");
            }

            if ($teacherId) {
                $query->where('teacher_id', $teacherId);
            }

            $quizzes = $query->with(['teacher.user:id,name', 'lesson:id,title'])
                ->withCount('questions')
                ->withCount('quizzesAttempt')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data' => $quizzes->items(),
                'pagination' => [
                    'total' => $quizzes->total(),
                    'per_page' => $quizzes->perPage(),
                    'current_page' => $quizzes->currentPage(),
                    'last_page' => $quizzes->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error listing quizzes', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error listing quizzes.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get quiz details with questions and statistics.
     */
    public function showQuiz(int $quizId): JsonResponse
    {
        try {
            $quiz = Quiz::with(['teacher.user:id,name', 'lesson:id,title', 'questions'])
                ->find($quizId);

            if (!$quiz) {
                return response()->json([
                    'message' => 'Quiz not found.',
                ], 404);
            }

            $attempts = $quiz->quizzesAttempt;
            $avgScore = $attempts->count() > 0 ? $attempts->avg('score') : 0;

            $data = [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'total_marks' => $quiz->total_marks,
                'questions_count' => $quiz->questions->count(),
                'teacher' => [
                    'id' => $quiz->teacher->id,
                    'name' => $quiz->teacher->user->name,
                ],
                'lesson' => $quiz->lesson,
                'attempts_count' => $attempts->count(),
                'avg_score' => round($avgScore, 2),
                'created_at' => $quiz->created_at,
                'updated_at' => $quiz->updated_at,
            ];

            return response()->json($data, 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error fetching quiz details', ['quiz_id' => $quizId, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error fetching quiz details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a quiz.
     */
    public function deleteQuiz(int $quizId): JsonResponse
    {
        try {
            $quiz = Quiz::find($quizId);

            if (!$quiz) {
                return response()->json([
                    'message' => 'Quiz not found.',
                ], 404);
            }

            $quizTitle = $quiz->title;
            $teacherId = $quiz->teacher_id;

            // Clear cache
            Cache::forget("quizzes_teacher_{$teacherId}_all");

            $quiz->delete();

            Log::info('Admin: Quiz deleted', ['deleted_quiz_id' => $quizId, 'quiz_title' => $quizTitle]);

            return response()->json([
                'message' => 'Quiz deleted successfully.',
                'deleted_quiz' => [
                    'id' => $quizId,
                    'title' => $quizTitle,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error deleting quiz', ['quiz_id' => $quizId, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error deleting quiz.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all videos with optional filter by teacher/subject.
     */
    public function listVideos(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'search' => 'nullable|string|max:255',
                'teacher_id' => 'nullable|integer',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ]);

            $perPage = $request->query('per_page', 15);
            $page = $request->query('page', 1);
            $search = $request->query('search');
            $teacherId = $request->query('teacher_id');

            $query = Video::query();

            if ($search) {
                $query->where('title', 'like', "%{$search}%");
            }

            if ($teacherId) {
                $query->where('teacher_id', $teacherId);
            }

            $videos = $query->with(['teacher.user:id,name', 'lesson:id,title'])
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data' => $videos->items(),
                'pagination' => [
                    'total' => $videos->total(),
                    'per_page' => $videos->perPage(),
                    'current_page' => $videos->currentPage(),
                    'last_page' => $videos->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error listing videos', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error listing videos.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get video details.
     */
    public function showVideo(int $videoId): JsonResponse
    {
        try {
            $video = Video::with(['teacher.user:id,name', 'lesson:id,title'])->find($videoId);

            if (!$video) {
                return response()->json([
                    'message' => 'Video not found.',
                ], 404);
            }

            $data = [
                'id' => $video->id,
                'title' => $video->title,
                'url' => $video->url,
                'teacher' => [
                    'id' => $video->teacher->id,
                    'name' => $video->teacher->user->name,
                ],
                'lesson' => $video->lesson,
                'created_at' => $video->created_at,
                'updated_at' => $video->updated_at,
            ];

            return response()->json($data, 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error fetching video details', ['video_id' => $videoId, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error fetching video details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a video.
     */
    public function deleteVideo(int $videoId): JsonResponse
    {
        try {
            $video = Video::find($videoId);

            if (!$video) {
                return response()->json([
                    'message' => 'Video not found.',
                ], 404);
            }

            $videoTitle = $video->title;
            $videoUrl = $video->url;

            $video->delete();

            Log::info('Admin: Video deleted', ['deleted_video_id' => $videoId, 'video_title' => $videoTitle]);

            return response()->json([
                'message' => 'Video deleted successfully.',
                'deleted_video' => [
                    'id' => $videoId,
                    'title' => $videoTitle,
                    'url' => $videoUrl,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error deleting video', ['video_id' => $videoId, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error deleting video.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get lessons by unit with full details.
     */
    public function listLessons(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'unit_id' => 'nullable|integer',
                'search' => 'nullable|string|max:255',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ]);

            $perPage = $request->query('per_page', 15);
            $page = $request->query('page', 1);
            $search = $request->query('search');
            $unitId = $request->query('unit_id');

            $query = Lesson::query();

            if ($search) {
                $query->where('title', 'like', "%{$search}%");
            }

            if ($unitId) {
                $query->where('unit_id', $unitId);
            }

            $lessons = $query->with(['unit:id,title', 'subtopics', 'quizzes', 'videos'])
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data' => $lessons->items(),
                'pagination' => [
                    'total' => $lessons->total(),
                    'per_page' => $lessons->perPage(),
                    'current_page' => $lessons->currentPage(),
                    'last_page' => $lessons->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error listing lessons', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error listing lessons.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a lesson.
     */
    public function deleteLesson(int $lessonId): JsonResponse
    {
        try {
            $lesson = Lesson::find($lessonId);

            if (!$lesson) {
                return response()->json([
                    'message' => 'Lesson not found.',
                ], 404);
            }

            $lessonTitle = $lesson->title;

            $lesson->delete();

            Log::info('Admin: Lesson deleted', ['deleted_lesson_id' => $lessonId, 'lesson_title' => $lessonTitle]);

            return response()->json([
                'message' => 'Lesson deleted successfully.',
                'deleted_lesson' => [
                    'id' => $lessonId,
                    'title' => $lessonTitle,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Admin: Error deleting lesson', ['lesson_id' => $lessonId, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error deleting lesson.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
