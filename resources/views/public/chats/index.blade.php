@extends('public.layouts.app')

@section('title', 'Messages')

@section('content')
<div class="space-y-6">

    <div class="flex items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Messages</h1>
            <p class="text-gray-500 dark:text-dark-400 mt-0.5">Ваши чаты с проверяющими и студентами</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Chat List -->
        <div class="lg:col-span-1 space-y-4">
            @forelse($chats as $chat)
            <a href="{{ route('chats.show', $chat->id) }}"
               class="block p-4 rounded-xl border border-gray-200 dark:border-dark-700 hover:bg-gray-50 dark:hover:bg-dark-850 transition-colors {{ request('chat_id') == $chat->id ? 'ring-2 ring-brand-500' : '' }}">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full bg-brand-500 flex items-center justify-center text-white text-sm font-semibold shrink-0">
                        {{ $chat->submission->user->name ? substr($chat->submission->user->name, 0, 1) : '?' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ $chat->submission->user->name ?? 'Unknown' }}
                            </span>
                            @if($chat->updated_at)
                            <span class="text-xs text-gray-400 dark:text-dark-400 shrink-0">
                                {{ $chat->updated_at->diffForHumans() }}
                            </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 dark:text-dark-400 truncate mt-0.5">
                            {{ $chat->submission->project->title ?? 'No project' }}
                        </p>
                        @php
                            $lastMsg = $chat->messages()->latest()->first();
                        @endphp
                        @if($lastMsg)
                        <p class="text-xs text-gray-400 dark:text-dark-500 mt-1 truncate">
                            {{ $lastMsg->message }}
                        </p>
                        @endif
                    </div>
                </div>
            </a>
            @empty
            <div class="text-center py-12 text-gray-500 dark:text-dark-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-dark-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <p class="text-lg font-medium">Нет чатов</p>
            </div>
            @endforelse

            @if($chats->hasPages())
            <div class="flex justify-center">
                {{ $chats->links() }}
            </div>
            @endif
        </div>

        <!-- Right: Selected Chat Placeholder (use JS to load) -->
        <div class="lg:col-span-2 hidden lg:block">
            <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 p-6 flex items-center justify-center h-96 text-gray-400 dark:text-dark-500">
                <p>Выберите чат для просмотра</p>
            </div>
        </div>
    </div>
</div>
@endsection
