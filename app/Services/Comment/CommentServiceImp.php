<?php

namespace App\Services\Comment;

use App\Repositories\Comment\CommentRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CommentServiceImp implements CommentService
{
    protected $commentRepository;

    public function __construct(CommentRepository $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }

    /**
     * Lấy danh sách comment của một post
     */
    public function getCommentsByPostId(int $postId): array
    {
        try {
            $post = $this->commentRepository->findPostById($postId);
            if (!$post) {
                return [
                    'success' => false,
                    'error' => 'Post not found'
                ];
            }

            $comments = $this->commentRepository->getCommentsByPostId($postId);

            return [
                'success' => true,
                'comments' => $comments
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error fetching comments: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Tạo comment mới
     */
    public function createComment(int $postId, int $userId, array $data): array
    {
        try {
            $post = $this->commentRepository->findPostById($postId);
            if (!$post) {
                return [
                    'success' => false,
                    'error' => 'Post not found'
                ];
            }

            // Validate data
            $validationResult = $this->validateCommentData($data, $postId);
            if (!$validationResult['success']) {
                return $validationResult;
            }

            DB::beginTransaction();

            $comment = $this->commentRepository->create([
                'post_id' => $postId,
                'user_id' => $userId,
                'content' => $data['content'],
                'reply_comment_id' => $data['reply_comment_id'] ?? null
            ]);

            // Load relationships để trả về đầy đủ thông tin
            $comment->load(['user:id,username,nickname,avatar_url', 'parent.user:id,username,nickname,avatar_url']);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Comment created successfully',
                'comment' => $comment
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => 'Error creating comment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cập nhật comment
     */
    public function updateComment(int $commentId, int $userId, array $data): array
    {
        try {
            $comment = $this->commentRepository->findByIdAndUserId($commentId, $userId);
            if (!$comment) {
                return [
                    'success' => false,
                    'error' => 'Comment not found or you are not authorized to update this comment'
                ];
            }

            $validator = Validator::make($data, [
                'content' => 'required|string|max:1000'
            ]);

            if ($validator->fails()) {
                return [
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ];
            }

            DB::beginTransaction();

            $this->commentRepository->update($comment, [
                'content' => $data['content']
            ]);

            // Load lại relationships
            $comment->load(['user:id,username,nickname,avatar_url', 'parent.user:id,username,nickname,avatar_url']);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Comment updated successfully',
                'comment' => $comment
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => 'Error updating comment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Xóa comment
     */
    public function deleteComment(int $commentId, int $userId): array
    {
        try {
            $comment = $this->commentRepository->findByIdAndUserId($commentId, $userId);
            if (!$comment) {
                return [
                    'success' => false,
                    'error' => 'Comment not found or you are not authorized to delete this comment'
                ];
            }

            DB::beginTransaction();

            $this->commentRepository->delete($comment);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Comment deleted successfully'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => 'Error deleting comment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy danh sách reply của một comment
     */
    public function getRepliesByCommentId(int $commentId): array
    {
        try {
            $comment = $this->commentRepository->findById($commentId);
            if (!$comment) {
                return [
                    'success' => false,
                    'error' => 'Comment not found'
                ];
            }

            $replies = $this->commentRepository->getRepliesByCommentId($commentId);

            return [
                'success' => true,
                'replies' => $replies
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error fetching replies: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate comment data
     */
    public function validateCommentData(array $data, int $postId): array
    {
        $validator = Validator::make($data, [
            'content' => 'required|string|max:1000',
            'reply_comment_id' => [
                'nullable',
                'exists:comments,id',
                function ($attribute, $value, $fail) use ($postId) {
                    if ($value) {
                        if (!$this->commentRepository->isCommentBelongsToPost($value, $postId)) {
                            $fail('The reply comment must belong to the same post.');
                        }
                    }
                }
            ]
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ];
        }

        return ['success' => true];
    }
}
