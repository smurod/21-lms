@extends('public.layouts.app')

@section('title', 'Chat')

@section('content')
<div class="h-[calc(100vh-8rem)] flex flex-col" x-data="{
    messages: @js($messages),
    newMessage: '',
    loading: false,
    lastChecked: @js(now()->toISOString()),
    fetchMessages() {
        this.loading = true;
        fetch('{{ route('chats.messages', $chat->id) }}?since=' + this.lastChecked)
            .then(res => res.json())
            .then(data => {
                if (data.messages) {
                    this.messages = data.messages;
                    this.lastChecked = data.last_checked;
                    this.$nextTick(() => {
                        this.$refs.messagesEnd?.scrollIntoView({ behavior: 'smooth' });
                    });
                }
            })
            .catch(() => {})
            .finally(() => { this.loading = false; });
    },
    sendMessage() {
        if (!this.newMessage.trim()) return;
        const msg = this.newMessage.trim();
        this.newMessage = '';
        fetch('{{ route('chats.messages.store', $chat->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.content || '{{ csrf_token() }}',
            },
            body: JSON.stringify({ message: msg })
        })
        .then(res => res.json())
        .then(data => {
            if (data.message) {
                this.messages.push(data.message);
                this.$nextTick(() => {
                    this.$refs.messagesEnd?.scrollIntoView({ behavior: 'smooth' });
                });
            }
        })
        .catch(() => {});
    }
}" x-init="setInterval(() => fetchMessages(), 10000); $nextTick(() => { $refs.messagesEnd?.scrollIntoView({ behavior: 'smooth' }); });">

    <!-- Header -->
    <div class="bg-white dark:bg-dark-800 rounded-t-xl border border-gray-200 dark:border-dark-700 px-6 py-4 flex items-center gap-3">
        <a href="{{ route('chats.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ $chat->submission->user->name ?? 'Unknown' }}
            </h1>
            <p class="text-xs text-gray-500 dark:text-dark-400">
                {{ $chat->submission->project->title ?? 'No project' }}
            </p>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium
                {{ $isReviewer ? 'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300' }}">
                {{ $isReviewer ? 'Reviewer' : 'Student' }}
            </span>
        </div>
    </div>

    <!-- Messages Area -->
    <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3 bg-gray-100 dark:bg-dark-950" style="flex: 1;">
        <template x-for="(msg, index) in messages" :key="msg.id || index">
            <div class="flex" :class="msg.sender_id === {{ auth()->id() }} ? 'justify-end' : 'justify-start'">
                <div class="max-w-[70%]" :class="msg.sender_id === {{ auth()->id() }} ? 'bg-brand-600 text-white' : 'bg-white dark:bg-dark-800 text-gray-900 dark:text-gray-100'">
                    <div class="px-4 py-2 rounded-2xl" :class="msg.sender_id === {{ auth()->id() }} ? 'rounded-br-md' : 'rounded-bl-md'">
                        <p class="text-sm" x-html="msg.message"></p>
                    </div>
                    <div class="flex items-center justify-end gap-1 px-1 mt-0.5">
                        <span class="text-[10px]" :class="msg.sender_id === {{ auth()->id() }} ? 'text-brand-200' : 'text-gray-400 dark:text-dark-500'"
                              x-text="msg.created_at ? new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : ''">
                        </span>
                    </div>
                </div>
            </div>
        </template>

        <!-- Loading indicator -->
        <div x-show="loading" class="flex justify-center py-2">
            <svg class="w-5 h-5 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <div x-ref="messagesEnd"></div>
    </div>

    <!-- Input Area -->
    <div class="bg-white dark:bg-dark-800 rounded-b-xl border border-t-0 border-gray-200 dark:border-dark-700 px-4 py-3">
        <form @submit.prevent="sendMessage" class="flex items-center gap-3">
            <textarea
                x-model="newMessage"
                @keydown.enter.prevent="sendMessage"
                placeholder="Напишите сообщение..."
                rows="1"
                class="flex-1 px-4 py-2 text-sm border border-gray-200 dark:border-dark-700 rounded-xl bg-gray-50 dark:bg-dark-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none resize-none"
            ></textarea>
            <button type="submit"
                    :disabled="!newMessage.trim()"
                    class="px-4 py-2 text-sm font-medium text-white bg-brand-600 rounded-xl hover:bg-brand-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </form>
    </div>
</div>
@endsection
