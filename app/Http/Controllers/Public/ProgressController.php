<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Admin\Project;
use App\Models\Admin\Submission;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProgressController extends Controller
{
    public function __invoke(): View
    {
        $stats = [];

        if (Auth::check()) {
            $userId = Auth::id();

            // Projects stats
            $totalProjects = Project::count();
            $publishedProjects = Project::where('is_published', true)->count();

            // Submissions stats
            $enrolledCount = Submission::where('user_id', $userId)
                ->whereIn('status', ['pending', 'queued', 'testing', 'in_review', 'reviewed', 'in_progress', 'resubmitted'])
                ->count();

            $completedCount = Submission::where('user_id', $userId)
                ->whereIn('status', ['passed', 'failed'])
                ->count();

            $passedCount = Submission::where('user_id', $userId)
                ->where('status', 'passed')
                ->count();

            // Courses
            $totalCourses = Course::where('is_published', true)->count();

            $stats = [
                'total_projects' => $totalProjects,
                'published_projects' => $publishedProjects,
                'enrolled_projects' => $enrolledCount,
                'completed_projects' => $completedCount,
                'passed_projects' => $passedCount,
                'total_courses' => $totalCourses,
            ];
        } else {
            $stats = [
                'total_projects' => Project::count(),
                'published_projects' => Project::where('is_published', true)->count(),
                'enrolled_projects' => 0,
                'completed_projects' => 0,
                'passed_projects' => 0,
                'total_courses' => Course::where('is_published', true)->count(),
            ];
        }

        return view('public.profile', compact('stats'));
    }
    public function index(){
        return view('public.profile');
    }
}
