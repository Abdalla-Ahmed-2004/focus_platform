<?php

namespace App\Http\Controllers;

use App\Http\Resources\QuizCollection;
use App\Http\Resources\TeacherCollection;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
    
        switch ($request->query('type')) {
            case 'teacher':
                $results = Teacher::with('user')->whereHas('user', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->query('value') . '%');
                })->get();
                return response()->json(['teachers' => new TeacherCollection($results)]);
                break;
            case 'subject':
                $results = Subject::where('title', 'like', '%' . $request->query('value') . '%')->get();
                return response()->json(['subjects' => $results]);
                break;
            case 'quiz':
                $results = Quiz::where('title', 'like', '%' . $request->query('value') . '%')->get();
                return response()->json(['quizzes' => new QuizCollection($results)]);
                break;
            case 'lesson':
                $results = Lesson::where('title', 'like', '%' . $request->query('value') . '%')->get();
                return response()->json(['lessons' => $results]);
                
                break;
                // Implement lesson search logic here
            default:
                return response()->json(['error' => 'Invalid search type'], 400);
        }

        // $query = request()->get('q', '');
        // Implement search logic here, e.g., search teachers by name or subject
        // $teachers = Teacher::where('name', 'like', '%' . $query . '%')->get();
        // return response()->json(['teachers' => $teachers]);                          
    }
}
