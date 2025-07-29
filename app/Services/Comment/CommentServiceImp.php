<?php

namespace App\Services\Comment;

use App\Repositories\Comment\CommentRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\Notification\NotificationService;
use Exception;

class CommentServiceImp implements CommentService
{
    protected $commentRepository;
    protected $notificationService;

    public function __construct(CommentRepository $commentRepository, NotificationService $notificationService)
    {
        $this->commentRepository = $commentRepository;
        $this->notificationService = $notificationService;
    }

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
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error fetching comments: ' . $e->getMessage()
            ];
        }
    }

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
            $comment->load(['user:id,username,nickname,avatar_url', 'parent.user:id,username,nickname,avatar_url']);
            DB::commit();
            if ($post->user_id != $userId) {
                $this->notificationService->createNotification([
                    'user_id' => $post->user_id,
                    'sender_id' => $userId,
                    'content' => 'đã bình luận vào bài viết của bạn',
                    'type' => 'comment_post',
                    'read' => false,
                ]);
            }
            return [
                'success' => true,
                'message' => 'Comment created successfully',
                'comment' => $comment
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => 'Error creating comment: ' . $e->getMessage()
            ];
        }
    }
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
            $comment->load(['user:id,username,nickname,avatar_url', 'parent.user:id,username,nickname,avatar_url']);
            DB::commit();
            return [
                'success' => true,
                'message' => 'Comment updated successfully',
                'comment' => $comment
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => 'Error updating comment: ' . $e->getMessage()
            ];
        }
    }

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
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => 'Error deleting comment: ' . $e->getMessage()
            ];
        }
    }

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
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error fetching replies: ' . $e->getMessage()
            ];
        }
    }

    public function validateCommentData(array $data, int $postId): array
    {
        $validator = Validator::make($data, [
            'content' => 'required|string|max:1000',
            'reply_comment_id' => [
                'nullable',
                'exists:comments,id',
                function ($key, $value, $fail) use ($postId) {
                    logger("postId received in comment store: {$value}");
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
