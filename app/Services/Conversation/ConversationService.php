<?php

namespace App\Services\Conversation;

interface ConversationService
{
    public function getUserConversations(int $userId): array;
    public function getConversationDetail(int $conversationId, int $userId): array;
    public function getOrCreateDirectConversation(int $userId, int $otherUserId): array;
    public function getConversationMessages(int $conversationId, int $userId): array;
    public function sendMessage(int $conversationId, int $userId, array $data): array;
    public function markAsRead(int $conversationId, int $userId): array;
    public function searchUsers(int $userId, string $query): array;
    public function deleteConversation(int $conversationId, int $userId): array;
}
