<?php

namespace App\Http\Controllers;

use App\Services\Conversation\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    protected $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
    }

    /**
     * Lấy danh sách các cuộc trò chuyện của user hiện tại
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $result = $this->conversationService->getUserConversations($user->id);
        if ($result['success']) {
            return response()->json($result);
        } else {
            return response()->json(['error' => $result['error']], 500);
        }
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
        $result = $this->conversationService->getConversationDetail($conversationId, $user->id);
        if ($result['success']) {
            return response()->json($result);
        } else {
            $status = $result['error'] === 'Forbidden' ? 403 : 404;
            return response()->json(['error' => $result['error']], $status);
        }
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
        $result = $this->conversationService->getOrCreateDirectConversation($user->id, $userId);
        if ($result['success']) {
            return response()->json($result);
        } else {
            $status = isset($result['message']) ? 400 : 404;
            return response()->json($result, $status);
        }
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
        $result = $this->conversationService->getConversationMessages($conversationId, $user->id);
        if ($result['success']) {
            return response()->json($result);
        } else {
            $status = $result['error'] === 'Forbidden' ? 403 : 404;
            return response()->json(['error' => $result['error']], $status);
        }
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
        $result = $this->conversationService->sendMessage($conversationId, $user->id, $request->all());
        if ($result['success']) {
            return response()->json($result, 201);
        } else {
            $status = isset($result['errors']) ? 422 : ($result['error'] === 'Forbidden' ? 403 : 404);
            return response()->json($result, $status);
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
        $result = $this->conversationService->markAsRead($conversationId, $user->id);
        if ($result['success']) {
            return response()->json($result);
        } else {
            $status = $result['error'] === 'Forbidden' ? 403 : 404;
            return response()->json(['error' => $result['error']], $status);
        }
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
        $result = $this->conversationService->searchUsers($user->id, $query);
        return response()->json($result);
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
        $result = $this->conversationService->deleteConversation($conversationId, $user->id);
        if ($result['success']) {
            return response()->json($result);
        } else {
            $status = $result['error'] === 'Forbidden' ? 403 : 404;
            return response()->json($result, $status);
        }
    }
} 