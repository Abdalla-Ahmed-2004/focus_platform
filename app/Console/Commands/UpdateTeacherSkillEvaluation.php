<?php

namespace App\Console\Commands;

use App\Models\StudentAnswer;
use App\Models\Teacher;
use App\Models\TsubtopicEvaluation;
use Illuminate\Console\Command;

class UpdateTeacherSkillEvaluation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subtopic:update-teacher-skill-evaluation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and store teacher subtopic evaluation aggregates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Starting teacher subtopic evaluation update...');

            Teacher::query()->chunkById(50, function ($teachers) {
                foreach ($teachers as $teacher) {
                    $teacherQuizzes = $teacher->quizzes()->with(['questions:id,quiz_id,subtopic_id'])->get();
                    if ($teacherQuizzes->isEmpty()) {
                        TsubtopicEvaluation::where('teacher_id', $teacher->id)->delete();
                        continue;
                    }

                    $teacherSubtopicIds = $teacherQuizzes
                        ->flatMap(function ($quiz) {
                            return $quiz->questions->pluck('subtopic_id');
                        })
                        ->filter()
                        ->unique()
                        ->values();

                    if ($teacherSubtopicIds->isEmpty()) {
                        TsubtopicEvaluation::where('teacher_id', $teacher->id)->delete();
                        continue;
                    }

                    $subtopicEvaluations = StudentAnswer::query()
                        ->whereIn('quiz_id', $teacherQuizzes->pluck('id'))
                        ->whereIn('subtopic_id', $teacherSubtopicIds)
                        ->selectRaw('
                            subtopic_id,
                            COUNT(*) as answers_count,
                            SUM(CASE WHEN correctness = 1 THEN 1 ELSE 0 END) as correct_answers_count
                        ')
                        ->groupBy('subtopic_id')
                        ->get();

                    $subtopicEvaluationsById = $subtopicEvaluations->keyBy('subtopic_id');

                    foreach ($teacherSubtopicIds as $subtopicId) {
                        $evaluation = $subtopicEvaluationsById->get($subtopicId);
                        $answersCount = (int) ($evaluation->answers_count ?? 0);
                        $correctAnswersCount = (int) ($evaluation->correct_answers_count ?? 0);
                        $evaluationScore = $answersCount > 0
                            ? (int) round(($correctAnswersCount / $answersCount) * 100)
                            : 0;

                        TsubtopicEvaluation::updateOrCreate(
                            [
                                'teacher_id' => $teacher->id,
                                'subtopic_id' => $subtopicId,
                            ],
                            [
                                'answers_count' => $answersCount,
                                'correct_answers_count' => $correctAnswersCount,
                                'evaluation_score' => $evaluationScore,
                            ]
                        );
                    }

                    TsubtopicEvaluation::where('teacher_id', $teacher->id)
                        ->whereNotIn('subtopic_id', $teacherSubtopicIds)
                        ->delete();
                }
            });

            $this->info('Teacher skill evaluation update completed successfully!');
        } catch (\Exception $e) {
            $this->error('An error occurred while updating teacher skill evaluations: ' . $e->getMessage());
        }
    }
}
