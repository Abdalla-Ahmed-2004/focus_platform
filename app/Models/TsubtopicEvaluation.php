<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TsubtopicEvaluation extends Model
{
    /** @use HasFactory<\Database\Factories\TsubtopicEvaluationFactory> */
    use HasFactory;
    protected $fillable = [
        'teacher_id',
        'subtopic_id',
        'answers_count',
        'correct_answers_count',
        'evaluation_score',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
