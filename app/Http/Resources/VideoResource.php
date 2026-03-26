<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Tymon\JWTAuth\Facades\JWTAuth;

class VideoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($student=JWTAuth::user()->student) {
        $quizzes=$this->quizzes;
        $quiz_attempted=[];
        foreach ($quizzes as $quiz) {
            if ($student->quizzesAttempt()->where('quiz_id',$quiz->id)->exists()) {
                $quiz_attempted['quiz_'.$quiz->id]=true;
            }
            else{
                $quiz_attempted['quiz_'.$quiz->id]=false;
            }
        }
            return [
                'lesson_id' => $this->lesson->id,
                'lesson_title' => $this->lesson->title,
                'video_id' => $this->id,
                'video_title' => $this->title,
                'video_url' => $this->url,
                'created_at' => $this->created_at->format('Y-m-d h:i:s'),
                // 'quizzes'=>new quizCollection($this->quizzes)
                'quizzes_count' => $this->quizzes->count(),
                'quizzes' => $this->quizzes->pluck('id')->toArray(),
                "quiz_attempted" => $quiz_attempted,
            ];
        }
        return [
            'lesson_id' => $this->lesson->id,
            'lesson_title' => $this->lesson->title,
            'video_id' => $this->id,
            'video_title' => $this->title,
            'video_url' => $this->url,
            'created_at' => $this->created_at->format('Y-m-d h:i:s'),
            // 'quizzes'=>new quizCollection($this->quizzes)
            'quizzes_count' => $this->quizzes->count(),
            'quizzes' => $this->quizzes->pluck('id')->toArray(),
            
        ];
    }
}
