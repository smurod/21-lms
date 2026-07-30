@extends('public.layouts.app')
@section('name', 'page')
@section('title', 'Messages')

@section('content')
    @include('public.components.notifications-overlay')
    @include('public.components.user-menu-overlay')
    @include('public.components.activities-overlay')
    @include('public.components.projects-overlay')

    <main class="chat-list-page">
        <section class="chat-list-hero">
            <div>
                <p class="public-kicker">Messages</p>
                <h1>Chats</h1>
                <p>Ваши чаты с проверяющими и студентами.</p>
            </div>
            <a class="chat-list-back" href="{{ route('public.home') }}">Dashboard</a>
        </section>

        <section class="chat-list-layout">
            <div class="chat-list-panel">
                <div class="chat-list-panel-head">
                    <h2>Диалоги</h2>
                    <span>{{ $chats->total() ?? $chats->count() }}</span>
                </div>

                <div class="chat-list-items">
                    @forelse($chats as $chat)
                        @php
                            $lastMsg = $chat->messages()->latest()->first();
                            $student = $chat->submission->user ?? null;
                            $project = $chat->submission->project ?? null;
                        @endphp
                        <a href="{{ route('chats.show', $chat->id) }}" class="chat-list-item {{ request('chat_id') == $chat->id ? 'is-active' : '' }}">
                            <div class="chat-avatar">
                                {{ $student?->name ? mb_substr($student->name, 0, 1) : '?' }}
                            </div>
                            <div class="chat-list-body">
                                <div class="chat-list-row">
                                    <strong>{{ $student->name ?? 'Unknown' }}</strong>
                                    @if($chat->updated_at)
                                        <time>{{ $chat->updated_at->diffForHumans() }}</time>
                                    @endif
                                </div>
                                <p class="chat-project">{{ $project->title ?? 'No project' }}</p>
                                @if($lastMsg)
                                    <p class="chat-last-message">{{ $lastMsg->message }}</p>
                                @else
                                    <p class="chat-last-message">Пока нет сообщений</p>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="public-empty-state chat-empty-state">
                            <div>💬</div>
                            <h3>Нет чатов</h3>
                            <p>Когда появятся peer-review диалоги, они будут отображаться здесь.</p>
                        </div>
                    @endforelse
                </div>

                @if($chats->hasPages())
                    <div class="chat-pagination">
                        {{ $chats->links() }}
                    </div>
                @endif
            </div>

            <aside class="chat-preview-panel">
                <div class="chat-preview-icon">💬</div>
                <h2>Выберите чат</h2>
                <p>Откройте диалог слева, чтобы посмотреть сообщения и продолжить обсуждение.</p>
            </aside>
        </section>
    </main>
@endsection
