<?php

namespace App\Repositories\Comment;

use App\Models\Comments;
use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

class CommentRepositoryElq implements CommentRepository
{
    protected $commentModel;
    protected $postModel;

    public function __construct(Comments $comment, Post $post)
    {
        $this->commentModel = $comment;
        $this->postModel = $post;
    }

    /**
     * Lấy danh sách comment của một post
     */
    public function getCommentsByPostId(int $postId): Collection
    {
        return $this->commentModel->where('post_id', $postId)
            ->with(['user:id,username,nickname,avatar_url', 'replies.user:id,username,nickname,avatar_url'])
            ->whereNull('reply_comment_id')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Lấy comment theo ID
     */
    public function findById(int $id): ?Comments
    {
        return $this->commentModel->find($id);
    }

    /**
     * Lấy comment theo ID và user_id
     */
    public function findByIdAndUserId(int $id, int $userId): ?Comments
    {
        return $this->commentModel->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Tạo comment mới
     */
    public function create(array $data): Comments
    {
        return $this->commentModel->create($data);
    }

    /**
     * Cập nhật comment
     */
    public function update(Comments $comment, array $data): bool
    {
        return $comment->update($data);
    }

    /**
     * Xóa comment
     */
    public function delete(Comments $comment): bool
    {
        return $comment->delete();
    }

    /**
     * Lấy danh sách reply của một comment
     */
    public function getRepliesByCommentId(int $commentId): Collection
    {
        return $this->commentModel->where('reply_comment_id', $commentId)
            ->with('user:id,username,nickname,avatar_url')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Kiểm tra comment có thuộc về post không
     */
    public function isCommentBelongsToPost(int $commentId, int $postId): bool
    {
        $comment = $this->commentModel->find($commentId);
        return $comment && $comment->post_id === $postId;
    }

    /**
     * Lấy post theo ID
     */
    public function findPostById(int $postId): ?Post
    {
        return $this->postModel->find($postId);
    }
}
