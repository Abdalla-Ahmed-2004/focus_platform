<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory;


    protected $fillable = [
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(StudentAnswer::class, 'student_id');
    }

    public function quizzesAttempt()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function lessonAttempts()
    {
        $lessons = $this->hasMany(LessonAttempt::class);
    $subject_lessons = $lessons->join('lessons', 'lesson_attempts.lesson_id', '=', 'lessons.id')
    ->join('units', 'lessons.unit_id', '=', 'units.id')
    ->join('subjects', 'units.subject_id', '=', 'subjects.id')
    ->where('quiz_attempted', true)
    ->select('lesson_attempts.lesson_id','lesson_attempts.video_id','lesson_attempts.teacher_id','lesson_attempts.quiz_id', 'lessons.title as lesson_title', 'subjects.title as subject_title', 'subjects.code as subject_code')->get();
        $grouped = $subject_lessons->groupBy('subject_code')->map(function ($items) {
            return $items->values();
        });
    return $grouped;
        //  

        return $this->hasMany(LessonAttempt::class);
    }

    public function weaknessProfiles()
    {
        return $this->hasMany(StudentSubtopicProgress::class);
    }

    public function recommendations()
    {
        return $this->hasMany(Recommendation::class);
    }

    public function subtopicEvaluations()
    {
        $subtopic_evaluations = $this->hasMany(StudentSubtopicEvaluation::class);
            $grouped=$subtopic_evaluations->join('subtopics', 'student_subtopic_evaluations.subtopic_id', '=', 'subtopics.id')
            ->join('lessons', 'subtopics.lesson_id', '=', 'lessons.id')
            ->join('units', 'lessons.unit_id', '=', 'units.id')
            ->join('subjects', 'units.subject_id', '=', 'subjects.id')->select('student_subtopic_evaluations.*', 'subtopics.title as subtopic_title', 'lessons.title as lesson_title', 'units.title as unit_title', 'subjects.title as subject_title', 'subjects.code as code')->latest('student_subtopic_evaluations.created_at');
           

            
            return $grouped;
     
    }
}
