@extends('public.layouts.app')

@section('title', 'Activities — School 21')

@section('content')
    @php $activities = [
    ['icon'=>'check','color'=>'#8bea9a','title'=>'Completed','desc'=>'Morning meditation session.','time'=>'2 hours ago'],
    ['icon'=>'refresh','color'=>'#7dd3fc','title'=>'Updated','desc'=>'Project progress on Mobile App.','time'=>'5 hours ago'],
    ['icon'=>'trophy','color'=>'#9d7ff4','title'=>'Achieved','desc'=>'New personal record: 7 day streak.','time'=>'1 day ago'],
    ['icon'=>'code','color'=>'#f0abfc','title'=>'Code Review','desc'=>'Reviewed s21_string+ project.','time'=>'2 days ago'],
    ['icon'=>'users','color'=>'#fda4af','title'=>'Peer Review','desc'=>'Received feedback on CICD project.','time'=>'3 days ago'],
    ['icon'=>'star','color'=>'#fcd34d','title'=>'Earned','desc'=>'+50 XP for LinuxMonitoring completion.','time'=>'4 days ago'],
]; @endphp
    <main class="main-content activities-page">
        <div class="activities-header"><h1>Activity Log</h1><p class="subtitle">Your recent activities and achievements</p></div>
        <div class="activities-timeline">
            @foreach($activities as $act)
                <div class="activity-item">
                    <div class="activity-icon" style="background:{{ $act['color'] }}20;color:{{ $act['color'] }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div class="activity-body"><div class="activity-title">{{ $act['title'] }}</div><div class="activity-desc">{{ $act['desc'] }}</div></div>
                    <div class="activity-time">{{ $act['time'] }}</div>
                </div>
            @endforeach
        </div>
    </main>
@endsection
