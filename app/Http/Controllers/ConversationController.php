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

    public function index(): JsonResponse
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

    public function getMessages($conversationId): JsonResponse
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
} 