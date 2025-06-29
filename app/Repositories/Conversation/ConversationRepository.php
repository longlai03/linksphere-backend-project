<?php

namespace App\Repositories\Conversation;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ConversationRepository
{
    public function getUserConversations(int $userId): Collection;
    public function findConversationById(int $conversationId): ?Chat;
    public function findOrCreateDirectConversation(int $userId, int $otherUserId): Chat;
    public function getConversationMessages(int $conversationId): Collection;
    public function createMessage(int $conversationId, int $senderId, array $data): ChatMessage;
    public function markMessagesAsRead(int $conversationId, int $userId): void;
    public function getUnreadCount(Chat $chat, int $userId): int;
    public function canAccessChat(Chat $chat, int $userId): bool;
    public function deleteConversation(int $conversationId): bool;
    public function searchUsers(int $userId, string $query, int $limit = 10): Collection;
    public function findUserById(int $userId): ?User;
}
