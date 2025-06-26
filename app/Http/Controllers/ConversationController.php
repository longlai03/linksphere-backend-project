<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ConversationController extends Controller
{
    /**
     * Lấy danh sách các cuộc trò chuyện của user hiện tại
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Lấy các cuộc trò chuyện mà user tham gia (là creator hoặc participant)
        $chats = Chat::where(function ($query) use ($user) {
            $query->where('create_by', $user->id)
                ->orWhere('participant_id', $user->id);
        })
        ->with([
            'creator',
            'participant',
            'chatMessages' => function ($query) {
                $query->latest('sent_at')->limit(1); // Chỉ lấy tin nhắn cuối cùng
            },
            'chatMessages.sender'
        ])
        ->orderByDesc('updated_at')
        ->get();

        // Xử lý dữ liệu để trả về format phù hợp
        $conversations = $chats->map(function ($chat) use ($user) {
            $lastMessage = $chat->chatMessages->first();
            $participant = $chat->participant_id == $user->id ? $chat->creator : $chat->participant;
            return [
                'id' => $chat->id,
                'type' => 'direct',
                'name' => $participant ? $participant->nickname : 'Người dùng',
                'avatar' => $participant ? $participant->avatar_url : null,
                'last_message' => $lastMessage ? [
                    'id' => $lastMessage->id,
                    'content' => $lastMessage->content,
                    'sender_id' => $lastMessage->sender_id,
                    'sent_at' => $lastMessage->sent_at,
                    'sender' => $lastMessage->sender,
                ] : null,
                'unread_count' => $this->getUnreadCount($chat, $user),
                'updated_at' => $chat->updated_at,
                'other_participant' => $participant,
                'creator' => $chat->creator,
                'participant' => $chat->participant
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }

    /**
     * Lấy chi tiết 1 cuộc trò chuyện
     */
    public function show($conversationId): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $chat = Chat::with([
            'creator',
            'participant',
            'chatMessages.sender'
        ])->find($conversationId);

        if (!$chat) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        // Kiểm tra quyền truy cập
        if (!$this->canAccessChat($chat, $user)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $participant = $chat->participant_id == $user->id ? $chat->creator : $chat->participant;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $chat->id,
                'type' => 'direct',
                'name' => $participant ? $participant->nickname : 'Người dùng',
                'avatar' => $participant ? $participant->avatar_url : null,
                'unread_count' => $this->getUnreadCount($chat, $user),
                'other_participant' => $participant,
                'creator' => $chat->creator,
                'participant' => $chat->participant
            ]
        ]);
    }

    /**
     * Lấy hoặc tạo cuộc trò chuyện trực tiếp với 1 user
     */
    public function getOrCreateDirect($userId): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Không thể tạo chat với chính mình
        if ($user->id == $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create conversation with yourself'
            ], 400);
        }

        $otherUser = User::find($userId);
        if (!$otherUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Tìm cuộc trò chuyện trực tiếp giữa 2 user
        $chat = Chat::where(function ($query) use ($user, $otherUser) {
            $query->where('create_by', $user->id)->where('participant_id', $otherUser->id);
        })->orWhere(function ($query) use ($user, $otherUser) {
            $query->where('create_by', $otherUser->id)->where('participant_id', $user->id);
        })->first();

        if (!$chat) {
            // Tạo mới cuộc trò chuyện
            $chat = Chat::create([
                'create_by' => $user->id,
                'participant_id' => $otherUser->id
            ]);
        }

        $chat->load([
            'creator',
            'participant',
            'chatMessages.sender'
        ]);

        // Lấy participant (người còn lại)
        $participant = $chat->participant_id == $user->id ? $chat->creator : $chat->participant;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $chat->id,
                'type' => 'direct',
                'name' => $participant ? $participant->nickname : 'Người dùng',
                'avatar' => $participant ? $participant->avatar_url : null,
                'unread_count' => $this->getUnreadCount($chat, $user),
                'other_participant' => $participant,
                'creator' => $chat->creator,
                'participant' => $chat->participant
            ]
        ]);
    }

    /**
     * Lấy danh sách tin nhắn của 1 cuộc trò chuyện
     */
    public function getMessages(Request $request, $conversationId): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $chat = Chat::find($conversationId);
        if (!$chat) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        // Kiểm tra quyền truy cập
        if (!$this->canAccessChat($chat, $user)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Lấy tin nhắn với pagination
        $perPage = $request->get('per_page', 50);
        $messages = $chat->chatMessages()
            ->with('sender')
            ->orderBy('sent_at', 'desc')
            ->paginate($perPage);

        // Đánh dấu tin nhắn đã đọc
        $this->markMessagesAsRead($chat, $user);

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Gửi tin nhắn trong 1 cuộc trò chuyện
     */
    public function sendMessage(Request $request, $conversationId): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $chat = Chat::find($conversationId);
        if (!$chat) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        // Kiểm tra quyền truy cập
        if (!$this->canAccessChat($chat, $user)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required_without:attachment_id|string|max:1000',
            'attachment_id' => 'nullable|integer|exists:attachment,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $message = $chat->chatMessages()->create([
                'sender_id' => $user->id,
                'content' => $request->input('content'),
                'attachment_id' => $request->input('attachment_id'),
                'sent_at' => now(),
                'status' => 'sent'
            ]);

            // Cập nhật thời gian tin nhắn cuối của chat
            $chat->update(['updated_at' => now()]);

            $message->load('sender');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $message
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đánh dấu tin nhắn đã đọc
     */
    public function markAsRead($conversationId): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $chat = Chat::find($conversationId);
        if (!$chat) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        if (!$this->canAccessChat($chat, $user)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $this->markMessagesAsRead($chat, $user);

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read'
        ]);
    }

    /**
     * Tìm kiếm user để nhắn tin
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $query = $request->get('q', '');
        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $users = User::where('id', '!=', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('username', 'like', "%{$query}%")
                  ->orWhere('nickname', 'like', "%{$query}%")
                  ->orWhere('name', 'like', "%{$query}%");
            })
            ->select('id', 'username', 'nickname', 'avatar_url')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Xóa cuộc trò chuyện
     */
    public function destroy($conversationId): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $chat = Chat::find($conversationId);
        if (!$chat) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        // Chỉ creator mới được xóa
        if ($chat->create_by !== $user->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        DB::beginTransaction();
        try {
            $chat->chatMessages()->delete();
            $chat->delete();
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Conversation deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting conversation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ========== HELPER METHODS ==========

    /**
     * Tìm cuộc trò chuyện trực tiếp giữa 2 user
     */
    private function findDirectChat($user1, $user2): ?Chat
    {
        return Chat::where(function ($query) use ($user1, $user2) {
            $query->where('create_by', $user1->id)
                ->orWhere('create_by', $user2->id);
        })
        ->whereHas('chatMessages', function ($q) use ($user1, $user2) {
            $q->whereIn('sender_id', [$user1->id, $user2->id]);
        })
        ->first();
    }

    /**
     * Kiểm tra user có thể truy cập chat không
     */
    private function canAccessChat($chat, $user): bool
    {
        return $chat->create_by === $user->id || 
               $chat->participant_id === $user->id;
    }

    /**
     * Đếm số tin nhắn chưa đọc
     */
    private function getUnreadCount($chat, $user): int
    {
        // Logic đơn giản: đếm tin nhắn không phải của user hiện tại
        // Trong thực tế, bạn có thể thêm bảng để track read status
        return $chat->chatMessages()
            ->where('sender_id', '!=', $user->id)
            ->count();
    }

    /**
     * Đánh dấu tin nhắn đã đọc
     */
    private function markMessagesAsRead($chat, $user): void
    {
        // Cập nhật status của tin nhắn thành 'read'
        $chat->chatMessages()
            ->where('sender_id', '!=', $user->id)
            ->where('status', '!=', 'read')
            ->update(['status' => 'read']);
    }
} 