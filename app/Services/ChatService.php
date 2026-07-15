<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\DB;

class ChatService
{
    /**
     * Create or get an existing chat for a submission.
     */
    public function getOrCreateChat(int $submissionId, int $reviewerId, int $revieweeId): Chat
    {
        $chat = Chat::where('submission_id', $submissionId)
            ->where('reviewer_id', $reviewerId)
            ->first();

        if (!$chat) {
            $chat = Chat::create([
                'submission_id' => $submissionId,
                'reviewer_id' => $reviewerId,
                'reviewee_id' => $revieweeId,
            ]);
        }

        return $chat;
    }

    /**
     * Send a message in a chat.
     */
    public function sendMessage(int $chatId, int $senderId, string $message): ChatMessage
    {
        return ChatMessage::create([
            'chat_id' => $chatId,
            'sender_id' => $senderId,
            'message' => $message,
            'is_read' => false,
        ]);
    }

    /**
     * Get messages for a chat, ordered by created_at.
     */
    public function getMessages(int $chatId): \Illuminate\Database\Eloquent\Collection
    {
        return ChatMessage::where('chat_id', $chatId)
            ->with('sender:id,name,username')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Mark all messages in a chat as read for a given user.
     */
    public function markAsRead(int $chatId, int $userId): void
    {
        ChatMessage::where('chat_id', $chatId)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}
