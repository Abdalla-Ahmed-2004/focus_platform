<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Teacher;
use App\Models\Video;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templateTeacher = Teacher::whereHas('user', function ($query) {
            $query->where('email', 'magdy@gmail.com');
        })->with(['videos', 'quizzes.questions'])->first();

        if (! $templateTeacher) {
            Teacher::factory(20)->create();

            return;
        }

        $teachers = Teacher::factory(20)->create([
            'subject_id' => $templateTeacher->subject_id,
        ]);

        foreach ($teachers as $teacher) {
            $videoMap = [];

            foreach ($templateTeacher->videos as $templateVideo) {
                $video = Video::updateOrCreate(
                    [
                        'teacher_id' => $teacher->id,
                        'lesson_id' => $templateVideo->lesson_id,
                        'subtopic_id' => $templateVideo->subtopic_id,
                    ],
                    [
                        'title' => $templateVideo->title,
                        'url' => $templateVideo->url,
                        'duration' => $templateVideo->duration,
                        'thumbnail' => $templateVideo->thumbnail,
                        'views' => 0,
                    ]
                );

                $videoMap[$templateVideo->id] = $video;
            }

            foreach ($templateTeacher->quizzes as $templateQuiz) {
                $video = $videoMap[$templateQuiz->video_id] ?? null;

                if (! $video) {
                    continue;
                }

                $quiz = Quiz::updateOrCreate(
                    [
                        'teacher_id' => $teacher->id,
                        'lesson_id' => $templateQuiz->lesson_id,
                        'video_id' => $video->id,
                    ],
                    [
                        'title' => $templateQuiz->title,
                        'time_limit' => $templateQuiz->time_limit,
                        'total_marks' => $templateQuiz->total_marks,
                    ]
                );

                foreach ($templateQuiz->questions as $templateQuestion) {
                    Question::updateOrCreate(
                        [
                            'quiz_id' => $quiz->id,
                            'question' => $templateQuestion->question,
                        ],
                        [
                            'subtopic_id' => $templateQuestion->subtopic_id,
                            'option_1' => $templateQuestion->option_1,
                            'option_2' => $templateQuestion->option_2,
                            'option_3' => $templateQuestion->option_3,
                            'option_4' => $templateQuestion->option_4,
                            'correct_answer' => $templateQuestion->correct_answer,
                            'difficulty' => fake()->randomElement([1, 2, 3]),
                            'cognitive_skill' => $templateQuestion->cognitive_skill,
                        ]
                    );
                }
            }
        }
    }
}
