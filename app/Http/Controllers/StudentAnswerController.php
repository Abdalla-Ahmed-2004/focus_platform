<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentAnswerRequest;
use App\Http\Requests\UpdateStudentAnswerRequest;
use App\Http\Resources\QuizAttemptCollection;
use App\Http\Resources\StudentAnswerCollection;
use App\Models\LessonAttempt;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentAnswer;
use Tymon\JWTAuth\Facades\JWTAuth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class StudentAnswerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     $student = JWTAuth::user()->student;
    //     $cacheKey = 'quiz_attempts_student_'.$student->id;

    //     // Paginated version
    //     // $quizAttempts = cache()->remember($cacheKey, 1440, function () use ($student) {
    //     //     return $student->quizzesAttempt()->paginate(10);
    //     // });

    //     // Non-paginated version
    //     $quizAttempts = cache()->remember($cacheKey.'_all', 60, function () use ($student) {
    //         return $student->quizzesAttempt;
    //     });

    //     return ['quizzesAttempt' => new QuizAttemptCollection($quizAttempts)];
    // }

    /**
     * Store a newly created resource in storage.
     */


    public function answer(StoreStudentAnswerRequest $request, Quiz $quiz)
    {
        $student = JWTAuth::user()->student;
        $submittedAnswers = collect($request->validated()['answers']);

        $questions = $quiz->questions()
            ->get(['id', 'subtopic_id', 'question', 'correct_answer'])
            ->keyBy('id');

        $latestAnswers = [];
        $score = 0;

        DB::transaction(function () use ($student, $quiz, $submittedAnswers, $questions, &$latestAnswers, &$score) {
            LessonAttempt::where('student_id', $student->id)
                ->where('lesson_id', $quiz->video->lesson_id)
                ->update([
                    'quiz_attempted' => true,
                    'quiz_id' => $quiz->id,
                    'video_id' => $quiz->video_id,
                    'teacher_id' => $quiz->teacher_id,
                ]);

            foreach ($submittedAnswers as $submittedAnswer) {
                $questionId = $submittedAnswer['question_id'];
                $question = $questions->get($questionId);

                if (! $question) {
                    continue;
                }

                $isCorrect = $question->correct_answer === $submittedAnswer['answer_text'];

                if ($isCorrect) {
                    $score++;
                }

                StudentAnswer::create([
                    'quiz_id' => $quiz->id,
                    'question_id' => $question->id,
                    'subtopic_id' => $question->subtopic_id,
                    'student_id' => $student->id,
                    'answer_text' => $submittedAnswer['answer_text'],
                    'correctness' => $isCorrect,
                ]);

                $latestAnswers[$question->id] = [
                    'question_id' => $question->id,
                    'subtopic_id' => $question->subtopic_id,
                    'question' => $question->question,
                    'correct_answer' => $question->correct_answer,
                    'student_answer' => $submittedAnswer['answer_text'],
                    'is_correct' => $isCorrect,
                ];
            }

            QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'student_id' => $student->id,
                'score' => $score,
                'total_marks' => $quiz->total_marks,
                'started_at' => now()->subMinutes(30),
                'completed_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Answer saved',
            'answers' => collect($latestAnswers)->values(),
            'score' => $score,
            'total_marks' => $quiz->total_marks,
            'ai_evaluation' => (new StudentController())->subtopicEvaluation($quiz),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(StudentAnswer $answer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentAnswerRequest $request, StudentAnswer $answer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StudentAnswer $answer)
    {
        //
    }
}
