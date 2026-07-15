<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Admin\Submission;
use App\Models\Admin\Review;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivitiesController extends Controller
{
    public function __invoke(): View
    {
        $activities = [];

        if (Auth::check()) {
            $userId = Auth::id();

            $pendingReviews = Review::where('status', 'pending')
                ->where('is_calibration', false)
                ->count();

            $recentSubmissions = Submission::where('user_id', $userId)
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get();

            $courses = Course::where('is_published', true)
                ->withCount('modules')
                ->limit(3)
                ->get();

            $activities = [
                'pending_reviews' => $pendingReviews,
                'recent_submissions' => $recentSubmissions,
                'courses' => $courses,
            ];
        }

        return view('public.activities', compact('activities'));
    }
}
