<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Project;
use App\Models\Admin\Submission;
use App\Models\Admin\Review;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::whereHas('submissions')->distinct()->count(),
            'total_projects' => Project::count(),
            'published_projects' => Project::where('is_published', true)->count(),
            'pending_submissions' => Submission::whereIn('status', ['pending', 'queued', 'testing'])->count(),
            'pending_reviews' => Review::where('status', 'pending')->count(),
            'completed_submissions' => Submission::whereIn('status', ['passed', 'reviewed'])->count(),
            'total_reviews' => Review::count(),
        ];

        return view('admin.dashboard.index', compact('stats'));
    }
}
