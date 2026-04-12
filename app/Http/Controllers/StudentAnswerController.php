<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentAnswerRequest;
use App\Http\Requests\UpdateStudentAnswerRequest;
use App\Http\Resources\QuizAttemptCollection;
use App\Http\Resources\StudentAnswerCollection;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentAnswer;
use Tymon\JWTAuth\Facades\JWTAuth;

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
    public function store(StoreStudentAnswerRequest $request, Quiz $quiz)
    {
        $answers = $request->validated();
        $student = JWTAuth::user()->student;
        if ($student->quizzesAttempt()->where('quiz_id', $quiz->id)->exists()) {
            return response()->json([
                'message' => 'you attempt this quiz ',
            ]);
        }

        $score = 0;
        for ($i = 0; $i < $quiz->questions->count(); $i++) {

            if (isset($answers['answers'][$i])) {

                $answer_question_id = $answers['answers'][$i]['question_id'];

                $quiz_question = $quiz->questions->where('id', $answer_question_id)->first();
                // dd($quiz_question);
                if ($quiz_question->correct_answer == $answers['answers'][$i]['answer_text']) {
                    $score += 1;
                }
                $answer = StudentAnswer::create([
                    'quiz_id' => $quiz->id,
                    'question_id' => $answers['answers'][$i]['question_id'],
                    'student_id' => $student->id,
                    'answer_text' => $answers['answers'][$i]['answer_text'],
                    'correctness' => ($quiz_question->correct_answer === $answers['answers'][$i]['answer_text']),
                ]);
            }
        }
        $quizAttempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'score' => $score,
        ]);
        $s_q_answers = StudentAnswer::where('quiz_id', $quiz->id)->where('student_id', $student->id)->get();
        
        return response()->json([
            'student_answers' => (new StudentAnswerCollection($s_q_answers)),
            'score' => $score
        ]);
    }

    public function answer(StoreStudentAnswerRequest $request, Quiz $quiz)
    {
        // dd($quiz);
        $student = JWTAuth::user()->student;
        // dd($student);
        $answers = $request->validated();
        if ($student->quizzesAttempt()->where('quiz_id', $quiz->id)->first()) {
            return response()->json(['message' => 'Attempt already submitted'], 400);
        }
        $score = 0;
        foreach ($answers['answers'] as $answer) {
            $question = $quiz->questions()->findOrFail($answer['question_id']);
            $isCorrect = ($question->correct_answer === $answer['answer_text']);
            if ($isCorrect) {
                $score += 1;
            }
            $answer = StudentAnswer::create([
                'quiz_id' => $quiz->id,
                'question_id' => $answer['question_id'],
                'student_id' => $student->id,
                'answer_text' => $answer['answer_text'],
                'correctness' => $isCorrect,
            ]);
        }
        $quizAttempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'score' => $score,
        ]);
        // dd($quiz->questions);
        // $question = $quiz->questions()->findOrFail($answers['answers'][0]['question_id']);
        // dd($question);
        // $isCorrect = ($question->correct_answer === $answers['answers'][0]['answer_text']);
        // dd($answers['answers']);
        // $answer = StudentAnswer::updateOrCreate(
        //     [
        //         'student_id' => $student->id,
        //         'quiz_id' => $quiz->id,
        //         'question_id' => $answers['answers'][0]['question_id'],
        //     ],
        //     [
        //         'answer_text' => $answers['answers'][0]['answer_text'],
        //         'correctness' => $isCorrect,
        //     ]
        // );

        return response()->json(['message' => 'Answer saved', 'answers' => $answers['answers'],'correct_answers_quiz' => $quiz->questions()->get(['id','question', 'correct_answer']), 'score' => $score]);
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
