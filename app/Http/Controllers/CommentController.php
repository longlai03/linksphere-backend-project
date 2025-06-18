<?php

namespace App\Http\Controllers;

use App\Models\Comments;
use App\Models\Post;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    /**
     * Lấy danh sách comment của một post
     */
    public function index(Request $request, int $postId): JsonResponse
    {
        try {
            $post = Post::find($postId);
            if (!$post) {
                return response()->json([
                    'error' => 'Post not found'
                ], 404);
            }

            $comments = $post->comments()
                ->with(['user:id,username,nickname,avatar_url', 'replies.user:id,username,nickname,avatar_url'])
                ->whereNull('reply_comment_id')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'comments' => $comments,
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error fetching comments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo comment mới
     */
    public function store(Request $request, int $postId): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized: No user found'
                ], 401);
            }

            $post = Post::find($postId);
            if (!$post) {
                return response()->json([
                    'error' => 'Post not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:1000',
                'reply_comment_id' => [
                    'nullable',
                    'exists:comments,id',
                    function ($attribute, $value, $fail) use ($post) {
                        if ($value) {
                            $comment = Comments::find($value);
                            if ($comment && $comment->post_id !== $post->id) {
                                $fail('The reply comment must belong to the same post.');
                            }
                        }
                    }
                ]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $comment = $post->comments()->create([
                'user_id' => $user->id,
                'content' => $request->input('content'),
                'reply_comment_id' => $request->input('reply_comment_id')
            ]);

            // Load relationships để trả về đầy đủ thông tin
            $comment->load(['user:id,username,nickname,avatar_url', 'parent.user:id,username,nickname,avatar_url']);

            DB::commit();

            return response()->json([
                'message' => 'Comment created successfully',
                'comment' => $comment
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error creating comment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật comment
     */
    public function update(Request $request, int $commentId): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized: No user found'
                ], 401);
            }

            $comment = Comments::find($commentId);
            if (!$comment) {
                return response()->json([
                    'error' => 'Comment not found'
                ], 404);
            }

            // Kiểm tra quyền sửa comment
            if ($comment->user_id !== $user->id) {
                return response()->json([
                    'error' => 'You are not authorized to update this comment'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $comment->update([
                'content' => $request->input('content')
            ]);

            // Load lại relationships
            $comment->load(['user:id,username,nickname,avatar_url', 'parent.user:id,username,nickname,avatar_url']);

            DB::commit();

            return response()->json([
                'message' => 'Comment updated successfully',
                'comment' => $comment
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error updating comment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa comment
     */
    public function destroy(int $commentId): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized: No user found'
                ], 401);
            }

            $comment = Comments::find($commentId);
            if (!$comment) {
                return response()->json([
                    'error' => 'Comment not found'
                ], 404);
            }

            // Kiểm tra quyền xóa comment
            if ($comment->user_id !== $user->id) {
                return response()->json([
                    'error' => 'You are not authorized to delete this comment'
                ], 403);
            }

            DB::beginTransaction();

            $comment->delete();

            DB::commit();

            return response()->json([
                'message' => 'Comment deleted successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error deleting comment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách reply của một comment
     */
    public function getReplies(Request $request, int $commentId): JsonResponse
    {
        try {
            $comment = Comments::find($commentId);
            if (!$comment) {
                return response()->json([
                    'error' => 'Comment not found'
                ], 404);
            }

            $replies = $comment->replies()
                ->with('user:id,username,nickname,avatar_url')
                ->orderBy('created_at', 'asc')
                ->paginate(10);

            return response()->json([
                'replies' => $replies,
                'current_page' => $replies->currentPage(),
                'last_page' => $replies->lastPage(),
                'per_page' => $replies->perPage(),
                'total' => $replies->total()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error fetching replies: ' . $e->getMessage()
            ], 500);
        }
    }
}
