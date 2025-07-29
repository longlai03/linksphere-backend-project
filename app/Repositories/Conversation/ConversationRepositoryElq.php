<?php

namespace App\Repositories\Conversation;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ConversationRepositoryElq implements ConversationRepository
{
    public function getUserConversations(int $userId): Collection
    {
        return Chat::where(function ($query) use ($userId) {
            $query->where('create_by', $userId)
                ->orWhere('participant_id', $userId);
        })
            ->with([
                'creator',
                'participant',
                'chatMessages' => function ($query) {
                    $query->latest('sent_at')->limit(1);
                },
                'chatMessages.sender'
            ])
            ->orderByDesc('updated_at')
            ->get();
    }

    public function findConversationById(int $conversationId): ?Chat
    {
        return Chat::with([
            'creator',
            'participant',
            'chatMessages' => function ($query) {
                $query->latest('sent_at')->limit(1);
            },
            'chatMessages.sender'
        ])->find($conversationId);
    }

    public function findOrCreateDirectConversation(int $userId, int $otherUserId): Chat
    {
        $chat = Chat::where(function ($query) use ($userId, $otherUserId) {
            $query->where('create_by', $userId)->where('participant_id', $otherUserId);
        })->orWhere(function ($query) use ($userId, $otherUserId) {
            $query->where('create_by', $otherUserId)->where('participant_id', $userId);
        })->first();

        if (!$chat) {
            $chat = Chat::create([
                'create_by' => $userId,
                'participant_id' => $otherUserId
            ]);
        }
        $chat->load(['creator', 'participant', 'chatMessages.sender']);
        return $chat;
    }

    public function getConversationMessages(int $conversationId): Collection
    {
        return ChatMessage::where('chat_id', $conversationId)
            ->with('sender')
            ->orderBy('sent_at', 'desc')
            ->get();
    }

    public function createMessage(int $conversationId, int $senderId, array $data): ChatMessage
    {
        $message = ChatMessage::create([
            'chat_id' => $conversationId,
            'sender_id' => $senderId,
            'content' => $data['content'] ?? null,
            'attachment_id' => $data['attachment_id'] ?? null,
            'sent_at' => now(),
            'status' => 'sent'
        ]);
        $message->load('sender');
        return $message;
    }

    public function markMessagesAsRead(int $conversationId, int $userId): void
    {
        ChatMessage::where('chat_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->where('status', '!=', 'read')
            ->update(['status' => 'read']);
    }

    public function getUnreadCount(Chat $chat, int $userId): int
    {
        return $chat->chatMessages()
            ->where('sender_id', '!=', $userId)
            ->count();
    }

    public function canAccessChat(Chat $chat, int $userId): bool
    {
        return $chat->create_by === $userId || $chat->participant_id === $userId;
    }


    public function findUserById(int $userId): ?User
    {
        return User::find($userId);
    }
}
