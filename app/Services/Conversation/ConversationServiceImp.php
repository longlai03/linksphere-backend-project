<?php

namespace App\Services\Conversation;

use App\Repositories\Conversation\ConversationRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class ConversationServiceImp implements ConversationService
{
    protected $conversationRepository;

    public function __construct(ConversationRepository $conversationRepository)
    {
        $this->conversationRepository = $conversationRepository;
    }

    public function getUserConversations(int $userId): array
    {
        try {
            $chats = $this->conversationRepository->getUserConversations($userId);
            $conversations = $chats->map(function ($chat) use ($userId) {
                $lastMessage = $chat->chatMessages->first();
                $participant = $chat->participant_id == $userId ? $chat->creator : $chat->participant;
                return [
                    'id' => $chat->id,
                    'type' => 'direct',
                    'name' => $participant ? $participant->username : 'Người dùng',
                    'avatar' => $participant ? $participant->avatar_url : null,
                    'last_message' => $lastMessage ? [
                        'id' => $lastMessage->id,
                        'chat_id' => $lastMessage->chat_id,
                        'attachment_id' => $lastMessage->attachment_id,
                        'sender_id' => $lastMessage->sender_id,
                        'status' => $lastMessage->status,
                        'content' => $lastMessage->content,
                        'sent_at' => $lastMessage->sent_at,
                        'created_at' => $lastMessage->created_at,
                        'updated_at' => $lastMessage->updated_at,
                        'sender' => $lastMessage->sender,
                    ] : null,
                    'unread_count' => $this->conversationRepository->getUnreadCount($chat, $userId),
                    'updated_at' => $chat->updated_at,
                    'other_participant' => $participant,
                    'creator' => $chat->creator,
                    'participant' => $chat->participant
                ];
            });
            return [
                'success' => true,
                'data' => $conversations
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getConversationDetail(int $conversationId, int $userId): array
    {
        $chat = $this->conversationRepository->findConversationById($conversationId);
        if (!$chat) {
            return ['success' => false, 'error' => 'Conversation not found'];
        }
        if (!$this->conversationRepository->canAccessChat($chat, $userId)) {
            return ['success' => false, 'error' => 'Forbidden'];
        }
        $participant = $chat->participant_id == $userId ? $chat->creator : $chat->participant;
        $lastMessage = $chat->chatMessages->first();
        return [
            'success' => true,
            'data' => [
                'id' => $chat->id,
                'type' => 'direct',
                'name' => $participant ? $participant->nickname : 'Người dùng',
                'avatar' => $participant ? $participant->avatar_url : null,
                'last_message' => $lastMessage ? [
                    'id' => $lastMessage->id,
                    'chat_id' => $lastMessage->chat_id,
                    'attachment_id' => $lastMessage->attachment_id,
                    'sender_id' => $lastMessage->sender_id,
                    'status' => $lastMessage->status,
                    'content' => $lastMessage->content,
                    'sent_at' => $lastMessage->sent_at,
                    'created_at' => $lastMessage->created_at,
                    'updated_at' => $lastMessage->updated_at,
                    'sender' => $lastMessage->sender,
                ] : null,
                'unread_count' => $this->conversationRepository->getUnreadCount($chat, $userId),
                'other_participant' => $participant,
                'creator' => $chat->creator,
                'participant' => $chat->participant
            ]
        ];
    }

    public function getOrCreateDirectConversation(int $userId, int $otherUserId): array
    {
        if ($userId == $otherUserId) {
            return [
                'success' => false,
                'message' => 'Cannot create conversation with yourself'
            ];
        }
        $otherUser = $this->conversationRepository->findUserById($otherUserId);
        if (!$otherUser) {
            return ['success' => false, 'error' => 'User not found'];
        }
        $chat = $this->conversationRepository->findOrCreateDirectConversation($userId, $otherUserId);
        $participant = $chat->participant_id == $userId ? $chat->creator : $chat->participant;
        return [
            'success' => true,
            'data' => [
                'id' => $chat->id,
                'type' => 'direct',
                'name' => $participant ? $participant->nickname : 'Người dùng',
                'avatar' => $participant ? $participant->avatar_url : null,
                'unread_count' => $this->conversationRepository->getUnreadCount($chat, $userId),
                'other_participant' => $participant,
                'creator' => $chat->creator,
                'participant' => $chat->participant
            ]
        ];
    }

    public function getConversationMessages(int $conversationId, int $userId): array
    {
        $chat = $this->conversationRepository->findConversationById($conversationId);
        if (!$chat) {
            return ['success' => false, 'error' => 'Conversation not found'];
        }
        $messages = $this->conversationRepository->getConversationMessages($conversationId);
        $this->conversationRepository->markMessagesAsRead($conversationId, $userId);
        return [
            'success' => true,
            'data' => $messages
        ];
    }

    public function sendMessage(int $conversationId, int $userId, array $data): array
    {
        $chat = $this->conversationRepository->findConversationById($conversationId);
        if (!$chat) {
            return ['success' => false, 'error' => 'Conversation not found'];
        }
        $validator = Validator::make($data, [
            'content' => 'required_without:attachment_id|string|max:1000',
            'attachment_id' => 'nullable|integer|exists:attachment,id',
        ]);
        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ];
        }
        DB::beginTransaction();
        try {
            $message = $this->conversationRepository->createMessage($conversationId, $userId, $data);
            $chat->update(['updated_at' => now()]);
            DB::commit();
            return [
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $message
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ];
        }
    }

    public function markAsRead(int $conversationId, int $userId): array
    {
        $chat = $this->conversationRepository->findConversationById($conversationId);
        if (!$chat) {
            return ['success' => false, 'error' => 'Conversation not found'];
        }
        $this->conversationRepository->markMessagesAsRead($conversationId, $userId);
        return [
            'success' => true,
            'message' => 'Messages marked as read'
        ];
    }
}
