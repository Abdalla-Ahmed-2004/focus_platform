<?php

namespace App\Http\Controllers;

use App\Models\Recommendation;
use App\Http\Requests\StoreRecommendationRequest;
use App\Http\Requests\UpdateRecommendationRequest;
use App\Models\StudentAnswer;
use App\Models\Subtopic;
use App\Models\TsubtopicEvaluation;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Http;
use App\Models\StudentSubtopicEvaluation;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function recommendations(Subtopic $subtopic)
    {
        $student = JWTAuth::user()->student;
        $subtopic_student_status = $subtopic->studentEvaluations()->where('student_id', $student->id)->latest()->first();
        $teacherEvaluations = TsubtopicEvaluation::query()
            ->where('subtopic_id', $subtopic->id)
            ->with([
                'teacher.user',
                'teacher.videos' => function ($query) use ($subtopic) {
                    $query->where('subtopic_id', $subtopic->id);
                },
            ])
            ->orderByDesc('evaluation_score')
            // ->orderByDesc('correct_answers_count')
            // ->orderByDesc('answers_count')
            ->get();

        $teacherRecommendations = $teacherEvaluations->map(function ($evaluation) {
            $teacher = $evaluation->teacher;
            $videos = ($teacher?->videos ?? collect())
                ->where('subtopic_id', $evaluation->subtopic_id)
                ->values();

            return [
                'teacher_id' => $teacher?->id,
                'teacher_name' => $teacher?->user?->name,
                'evaluation_score' => $evaluation->evaluation_score,
                // 'answers_count' => $evaluation->answers_count,
                // 'correct_answers_count' => $evaluation->correct_answers_count,
                'videos' => $videos->map(function ($video) use ($evaluation, $teacher) {
                    return [
                        'id' => $video->id,
                        'lesson_id' => $video->lesson_id,
                        'title' => $video->title,
                        'url' => $video->url,
                        'duration' => $video->duration,
                        'thumbnail' => $video->thumbnail,
                        'subtopic_id' => $video->subtopic_id,
                        'teacher_id' => $teacher?->id,
                        'teacher_name' => $teacher?->user?->name,
                        'teacher_evaluation_score' => $evaluation->evaluation_score,
                    ];
                })->values(),
            ];
        })->values();

        $recommendation_videos = $teacherRecommendations
            ->pluck('videos')
            ->flatten(1)
            ->values();

        return response()->json([
            'subtopic_status' => $subtopic_student_status ? $subtopic_student_status->evaluation_status : 'not attempted',
            'subtopic_difficulty' => $subtopic->subtopic_difficulty ?? null,
            'subtopic_title' => $subtopic->title,
            'subtopic_evaluation' => $subtopic_student_status ? $subtopic_student_status->subtopic_evaluation : null,
            'teacher_recommendations' => $teacherRecommendations,
            // 'recommendations' => $recommendation_videos,

        ]);
    }
    public function recommendation_questions(Subtopic $subtopic)
    {
        $student = JWTAuth::user()->student;
        // return($subtopic);
        $subtopic_student_status =  $student->subtopicEvaluations()
            ->latest('student_subtopic_evaluations.created_at')
            ->get()
            ->unique('subtopic_id')
            ->values();
            // return($subtopic_student_status);
        $recommendation_questions = [];
        $evaluation_status = $subtopic_student_status->where('subtopic_id', $subtopic->id)->first()->toArray();
        // return($subtopic->questions()->whereIn('difficulty', [2,1,3])->get());
        switch ($evaluation_status['evaluation_status']) {
            case 'Red (weak skill)':
                $recommendation_questions = $subtopic->questions()->where('difficulty', 1)->inRandomOrder()->limit(30)->get();
                break;
            case 'Developing (On Track)':
                $recommendation_questions = $subtopic->questions()->whereIn('difficulty', [1, 2])->inRandomOrder()->limit(30)->get();
                break;
            case 'Green (Mastered)':
                $recommendation_questions = $subtopic->questions()->whereIn('difficulty', [1, 2, 3])->inRandomOrder()->limit(30)->get();
                break;
            default:
                $recommendation_questions = [];
        }

        return response()->json([
            'subtopic_status' => $evaluation_status,
            'subtopic_difficulty' => $subtopic->subtopic_difficulty ?? null,
            'subtopic_title' => $subtopic->title,
            'subtopic_evaluation' => $evaluation_status['subtopic_evaluation'] ?? null,
            'recommendation_questions' => $recommendation_questions,

        ]);
    }



    public function answers(Subtopic $subtopic)
    {
        $student = JWTAuth::user()->student;

        $answers = Request()->input('answers');
        $results = [];

        // Save submitted answers
        foreach ($answers as $answer) {
            $question = $subtopic->questions()->find($answer['id']);
            if (!$question) {
                continue; // Skip if question not found in the subtopic
            }
            $is_correct = $question->correct_answer == $answer['answer_text'];

            // Log individual answers 
            $student->answers()->create([
                'quiz_id' => null,
                'subtopic_id' => $subtopic->id,
                'question_id' => $question->id,
                'answer_text' => $answer['answer_text'] ?? '',
                'correctness' => $is_correct ? 1 : 0,
            ]);

            $results[] = [
                'question_id' => $question->id,
                'is_correct' => $is_correct
            ];
        }

        // Fetch completely updated history for AI
        $student_answers = $student->answers()
            ->where('subtopic_id', $subtopic->id)
            ->orderBy('id', 'asc')
            ->get(['correctness']);

        // Build Payload for the AI predictor
        $payload = [
            [
                'skill_id' => (string) $subtopic->id,
                'skill_name' => $subtopic->title ?? 'Unknown',
                'skill_difficulty_avg' => (float) ($subtopic->subtopic_difficulty ?? 0.5),
                'student_history' => $student_answers->pluck('correctness')->map(fn($v) => (int)$v)->toArray(),
            ]
        ];

        // Ensure we send something if there's history
        if (empty($payload[0]['student_history'])) {
            return response()->json([
                'results' => $results,
                'message' => 'No history to send to AI.'
            ]);
        }

        try {
            $aiUrl = env('AI_PREDICTOR_URL', 'http://127.0.0.1:5000/predict');
            $response = Http::acceptJson()->timeout(12)->post($aiUrl, $payload);

            if ($response->successful()) {
                $evaluations = [];
                foreach ($response->json() as $item) {
                    $status = $item['status'] ?? $item['evaluation_status'] ?? null;

                    $evaluation = StudentSubtopicEvaluation::create([
                        'student_id' => $student->id,
                        'subtopic_id' => $subtopic->id,
                        'subtopic_title' => $subtopic->title ?? 'Unknown',
                        'subtopic_difficulty' => $subtopic->subtopic_difficulty ?? null,
                        'subtopic_evaluation' => isset($item['mastery_score']) ? round($item['mastery_score']) : null,
                        'evaluation_status' => $status,
                        'question_count' => $item['total_attempts'] ?? null,
                        'correct_count' => $item['total_correct'] ?? null,
                    ]);
                    $evaluations[] = $evaluation;
                }

                return response()->json([
                    'results' => $results,
                    'ai_evaluations' => $evaluations
                ]);
            }

            return response()->json([
                'results' => $results,
                'error' => 'AI returned an error',
                'details' => $response->json() ?? $response->body()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'results' => $results,
                'error' => 'AI could not be reached',
                'message' => $e->getMessage()
            ]);
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRecommendationRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Recommendation $recommendation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Recommendation $recommendation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRecommendationRequest $request, Recommendation $recommendation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Recommendation $recommendation)
    {
        //
    }
}
