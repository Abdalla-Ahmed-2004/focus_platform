<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\StudentSubtopicEvaluation;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Quiz;
use App\Models\LessonAttempt;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Return general analytics for the platform.
     */
    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'admin:dashboard:' . md5(json_encode([
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ]));

        $data = Cache::remember($cacheKey, 900, function () use ($request) {
            $from = $request->query('from');
            $to = $request->query('to');

            $quizAttemptsQuery = QuizAttempt::query();
            $lessonAttemptsQuery = LessonAttempt::query();
            $studentEvaluationsQuery = StudentSubtopicEvaluation::query();

            if ($from) {
                $quizAttemptsQuery->whereDate('created_at', '>=', $from);
                $lessonAttemptsQuery->whereDate('created_at', '>=', $from);
                $studentEvaluationsQuery->whereDate('created_at', '>=', $from);
            }

            if ($to) {
                $quizAttemptsQuery->whereDate('created_at', '<=', $to);
                $lessonAttemptsQuery->whereDate('created_at', '<=', $to);
                $studentEvaluationsQuery->whereDate('created_at', '<=', $to);
            }

            $totalStudents = Student::count();
            $totalTeachers = Teacher::count();
            // $totalAdmins = User::role('admin')->count();

            $totalSubjects = Subject::count();
            $totalTeachersWithSubject = Teacher::count();
            $totalQuizzes = Quiz::count();
            $totalQuizAttempts = (clone $quizAttemptsQuery)->count();
            $totalLessonAttempts = (clone $lessonAttemptsQuery)->count();
            $totalStudentEvaluations = (clone $studentEvaluationsQuery)->count();

            $avgQuizScore = (float) (clone $quizAttemptsQuery)->avg('score');
            $avgQuizScorePercent = (float) DB::table('quiz_attempts')
                ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
                ->when($from, fn($query) => $query->whereDate('created_at', '>=', $from))
                ->when($to, fn($query) => $query->whereDate('created_at', '<=', $to))
                ->selectRaw('COALESCE(AVG(CASE WHEN quizzes.total_marks > 0 THEN (quiz_attempts.score * 100.0 / quizzes.total_marks) END), 0) as avg_percent')
                ->value('avg_percent');

            $quizAttemptsByDay = (clone $quizAttemptsQuery)
                ->selectRaw('DATE(created_at) as day, COUNT(*) as attempts_count, AVG(score) as avg_score')
                ->groupBy('day')
                ->orderBy('day')
                ->limit(14)
                ->get();

            // $teacherRankings = Teacher::query()
            //     ->with(['user:id,name'])
            //     ->withCount(['quizzes as quizzes_count'])
            //     ->withCount(['videos as videos_count'])
            //     ->withCount(['lessonAttempts as lesson_attempts_count'])
            //     ->withCount(['tsubtopicEvaluations as tsubtopic_evaluations_count'])
            //     ->orderByDesc('quizzes_count')
            //     ->limit(10)
            //     ->get()
            //     ->map(function ($teacher) use ($from, $to) {
            //         $scoreQuery = QuizAttempt::query()
            //             ->whereHas('quiz', function ($query) use ($teacher) {
            //                 $query->where('teacher_id', $teacher->id);
            //             });

            //         if ($from) {
            //             $scoreQuery->whereDate('created_at', '>=', $from);
            //         }

            //         if ($to) {
            //             $scoreQuery->whereDate('created_at', '<=', $to);
            //         }

            //         $avgScorePercent = (float) DB::table('quiz_attempts')
            //             ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            //             ->where('quizzes.teacher_id', $teacher->id)
            //             ->when($from, fn($query) => $query->whereDate('quiz_attempts.created_at', '>=', $from))
            //             ->when($to, fn($query) => $query->whereDate('quiz_attempts.created_at', '<=', $to))
            //             ->selectRaw('COALESCE(AVG(CASE WHEN quizzes.total_marks > 0 THEN (quiz_attempts.score * 100.0 / quizzes.total_marks) END), 0) as avg_percent')
            //             ->value('avg_percent');

            //         return [
            //             'teacher_id' => $teacher->id,
            //             'teacher_name' => $teacher->user?->name,
            //             'subject_id' => $teacher->subject_id,
            //             'quizzes_count' => $teacher->quizzes_count,
            //             'videos_count' => $teacher->videos_count,
            //             'lesson_attempts_count' => $teacher->lesson_attempts_count,
            //             'tsubtopic_evaluations_count' => $teacher->tsubtopic_evaluations_count,
            //             'quiz_attempts_count' => $scoreQuery->count(),
            //             'avg_score_percent' => round($avgScorePercent, 2),
            //         ];
            //     });
            $teacherRankings = Teacher::query()
                ->with(['user:id,name'])
                ->withCount(['quizzes as quizzes_count'])
                ->withCount(['videos as videos_count'])
                ->withCount(['lessonAttempts as lesson_attempts_count'])
                ->withCount(['tsubtopicEvaluations as tsubtopic_evaluations_count'])

                // 1. ربط جدول المدرسين بجدول الكويزات وجدول محاولات الكويزات
                ->leftJoin('quizzes', 'teachers.id', '=', 'quizzes.teacher_id')
                ->leftJoin('quiz_attempts', 'quizzes.id', '=', 'quiz_attempts.quiz_id')

                // 2. تطبيق فلتر التاريخ (إذا تم إرساله) على محاولات الكويزات
                ->when($from, fn($query) => $query->whereDate('quiz_attempts.created_at', '>=', $from))
                ->when($to, fn($query) => $query->whereDate('quiz_attempts.created_at', '<=', $to))

                // 3. تحديد الأعمدة المراد جلبها مع حساب المتوسط بالنسبة المئوية مباشرة من الداتا بيز
                ->selectRaw('
        teachers.*,
        COALESCE(AVG(CASE WHEN quizzes.total_marks > 0 THEN (quiz_attempts.score * 100.0 / quizzes.total_marks) END), 0) as db_avg_score_percent
    ')
                ->groupBy('teachers.id') // تجميع البيانات لكل مدرس لمنع تكرار الصفوف بسبب الـ Join

                // 4. الترتيب التنازلي بناءً على النسبة المئوية المحسوبة (من الأعلى للأقل)
                ->orderByDesc('db_avg_score_percent')
                ->limit(10)
                ->get()
                ->map(function ($teacher) use ($from, $to) {
                    // حساب عدد محاولات الكويزات الخاص بهذا المدرس مع الفلترة بالتاريخ
                    $scoreQuery = QuizAttempt::query()
                        ->whereHas('quiz', function ($query) use ($teacher) {
                            $query->where('teacher_id', $teacher->id);
                        });

                    if ($from) {
                        $scoreQuery->whereDate('created_at', '>=', $from);
                    }

                    if ($to) {
                        $scoreQuery->whereDate('created_at', '<=', $to);
                    }

                    return [
                        'teacher_id' => $teacher->id,
                        'teacher_name' => $teacher->user?->name,
                        'subject_id' => $teacher->subject_id,
                        'quizzes_count' => $teacher->quizzes_count,
                        'videos_count' => $teacher->videos_count,
                        'lesson_attempts_count' => $teacher->lesson_attempts_count,
                        'tsubtopic_evaluations_count' => $teacher->tsubtopic_evaluations_count,
                        'quiz_attempts_count' => $scoreQuery->count(),
                        // نأخذ القيمة التي حسبناها في الداتا بيز ونقوم بعمل Round لها
                        'avg_score_percent' => round((float) $teacher->db_avg_score_percent, 2),
                    ];
                });
            $weakSubtopics = StudentSubtopicEvaluation::query()
                ->with(['subtopic:id,title,lesson_id'])
                ->when($from, fn($query) => $query->whereDate('created_at', '>=', $from))
                ->when($to, fn($query) => $query->whereDate('created_at', '<=', $to))
                ->selectRaw('subtopic_id, COUNT(*) as evaluations_count, AVG(subtopic_evaluation) as avg_mastery, AVG(correct_count) as avg_correct, AVG(question_count) as avg_questions')
                ->groupBy('subtopic_id')
                ->orderBy('avg_mastery')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'subtopic_id' => $item->subtopic_id,
                        'subtopic_title' => $item->subtopic?->title,
                        'lesson_id' => $item->subtopic?->lesson_id,
                        'evaluations_count' => (int) $item->evaluations_count,
                        'avg_mastery' => round((float) $item->avg_mastery, 2),
                        'avg_correct_count' => round((float) $item->avg_correct, 2),
                        'avg_question_count' => round((float) $item->avg_questions, 2),
                    ];
                });

            return response()->json([
                'summary' => [
                    'total_students' => $totalStudents,
                    'total_teachers' => $totalTeachers,
                    // 'total_admins' => $totalAdmins,
                    'total_subjects' => $totalSubjects,
                    'total_teachers_with_subjects' => $totalTeachersWithSubject,
                    'total_quizzes' => $totalQuizzes,
                    'total_quiz_attempts' => $totalQuizAttempts,
                    'total_lesson_attempts' => $totalLessonAttempts,
                    'total_student_evaluations' => $totalStudentEvaluations,
                    'avg_quiz_score' => round($avgQuizScore, 2),
                    'avg_quiz_score_percent' => round((float) $avgQuizScorePercent, 2),
                ],
                'activity' => [
                    'quiz_attempts_by_day' => $quizAttemptsByDay,
                ],
                'teacher_rankings' => $teacherRankings,
                'weak_subtopics' => $weakSubtopics,
            ]);
        });

        return response()->json($data, 200);
    }
}
