<?php

namespace App\Http\Controllers;

use App\Services\Comment\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    protected $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * Lấy danh sách comment của một post
     */
    public function index(Request $request, int $postId): JsonResponse
    {
        $result = $this->commentService->getCommentsByPostId($postId);
        if ($result['success']) {
            return response()->json(['comments' => $result['comments']]);
        } else {
            return response()->json(['error' => $result['error']], 404);
        }
    }

    /**
     * Tạo comment mới
     */
    public function store(Request $request, int $postId): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized: No user found'], 401);
        }
        $result = $this->commentService->createComment($postId, $user->id, $request->all());
        if ($result['success']) {
            return response()->json([
                'message' => $result['message'],
                'comment' => $result['comment']
            ], 201);
        } else {
            $status = isset($result['errors']) ? 422 : 404;
            return response()->json($result, $status);
        }
    }

    /**
     * Cập nhật comment
     */
    public function update(Request $request, int $commentId): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized: No user found'], 401);
        }
        $result = $this->commentService->updateComment($commentId, $user->id, $request->all());
        if ($result['success']) {
            return response()->json([
                'message' => $result['message'],
                'comment' => $result['comment']
            ]);
        } else {
            $status = isset($result['errors']) ? 422 : (isset($result['error']) && $result['error'] === 'Comment not found or you are not authorized to update this comment' ? 403 : 404);
            return response()->json($result, $status);
        }
    }

    /**
     * Xóa comment
     */
    public function destroy(int $commentId): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized: No user found'], 401);
        }
        $result = $this->commentService->deleteComment($commentId, $user->id);
        if ($result['success']) {
            return response()->json(['message' => $result['message']]);
        } else {
            $status = ($result['error'] === 'Comment not found or you are not authorized to delete this comment') ? 403 : 404;
            return response()->json($result, $status);
        }
    }

    /**
     * Lấy danh sách reply của một comment
     */
    public function getReplies(Request $request, int $commentId): JsonResponse
    {
        $result = $this->commentService->getRepliesByCommentId($commentId);
        if ($result['success']) {
            return response()->json(['replies' => $result['replies']]);
        } else {
            return response()->json(['error' => $result['error']], 404);
        }
    }
}
