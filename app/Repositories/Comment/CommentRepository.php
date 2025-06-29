<?php

namespace App\Repositories\Comment;

use App\Models\Comments;
use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

interface CommentRepository
{
    /**
     * Lấy danh sách comment của một post
     */
    public function getCommentsByPostId(int $postId): Collection;

    /**
     * Lấy comment theo ID
     */
    public function findById(int $id): ?Comments;

    /**
     * Lấy comment theo ID và user_id
     */
    public function findByIdAndUserId(int $id, int $userId): ?Comments;

    /**
     * Tạo comment mới
     */
    public function create(array $data): Comments;

    /**
     * Cập nhật comment
     */
    public function update(Comments $comment, array $data): bool;

    /**
     * Xóa comment
     */
    public function delete(Comments $comment): bool;

    /**
     * Lấy danh sách reply của một comment
     */
    public function getRepliesByCommentId(int $commentId): Collection;

    /**
     * Kiểm tra comment có thuộc về post không
     */
    public function isCommentBelongsToPost(int $commentId, int $postId): bool;

    /**
     * Lấy post theo ID
     */
    public function findPostById(int $postId): ?Post;
}
