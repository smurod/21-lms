<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Chats where this user is either reviewer or reviewee
        $chats = Chat::where('reviewer_id', $user->id)
            ->orWhere('reviewee_id', $user->id)
            ->with([
                'submission.project:title,slug',
                'submission.user:id,name,username',
            ])
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return view('public.chats.index', compact('chats'));
    }

    public function show(int $chatId, ChatService $chatService)
    {
        $user = auth()->user();
        $chat = Chat::where('id', $chatId)
            ->whereIn('reviewer_id', [$user->id])
            ->orWhere('reviewee_id', $user->id)
            ->firstOrFail();

        // Load messages
        $messages = $chatService->getMessages($chat->id);

        // Mark as read for current user
        $chatService->markAsRead($chat->id, $user->id);

        // Determine who is who for this user
        $isReviewer = $chat->reviewer_id === $user->id;
        $otherUser = $isReviewer
            ? $chat->submission->user
            : $chat->submission->user; // For simplicity, both refer to the same submission's user

        return view('public.chats.show', compact('chat', 'messages', 'isReviewer'));
    }

    public function store(Request $request, int $chatId, ChatService $chatService): JsonResponse
    {
        $user = auth()->user();

        $data = $request->validate([
            'message' => 'required|string|min:1|max:2000',
        ]);

        // Verify user has access to this chat
        $chat = Chat::where('id', $chatId)
            ->where(function ($q) use ($user) {
                $q->where('reviewer_id', $user->id)
                  ->orWhere('reviewee_id', $user->id);
            })
            ->firstOrFail();

        $message = $chatService->sendMessage($chat->id, $user->id, $data['message']);

        // Load sender for the response
        $message->load('sender:id,name,username');

        return response()->json([
            'message' => $message,
            'time' => $message->created_at->diffForHumans(),
        ], 201);
    }

    /**
     * Get messages for a chat (used for polling).
     */
    public function messages(int $chatId, ChatService $chatService): JsonResponse
    {
        $user = auth()->user();

        $chat = Chat::where('id', $chatId)
            ->where(function ($q) use ($user) {
                $q->where('reviewer_id', $user->id)
                  ->orWhere('reviewee_id', $user->id);
            })
            ->firstOrFail();

        // Mark as read
        $chatService->markAsRead($chat->id, $user->id);

        $messages = $chatService->getMessages($chat->id);

        return response()->json([
            'messages' => $messages,
            'last_checked' => now()->toISOString(),
        ]);
    }
}
