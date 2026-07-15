@extends('public.layouts.app')

@section('title', 'Support — School 21')

@section('content')
    <main class="main-content support-page">
        <div class="support-header">
            <h1>Help Center</h1>
            <p>Find answers and get support for School 21 platform</p>
        </div>

        <div class="support-grid">
            <a href="https://21-school.ru/faq" target="_blank" class="support-card">
                <div class="support-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <h3>FAQ</h3>
                <p>Frequently asked questions about the platform</p>
            </a>
            <a href="https://21-school.ru/docs" target="_blank" class="support-card">
                <div class="support-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                </div>
                <h3>Documentation</h3>
                <p>Platform documentation and guides</p>
            </a>
            <a href="mailto:support@21-school.ru" class="support-card">
                <div class="support-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </div>
                <h3>Contact Support</h3>
                <p>Reach out to our support team</p>
            </a>
        </div>
    </main>
@endsection
