<?php

namespace App\Services\Comment;

interface CommentService
{
    /**
     * Lấy danh sách comment của một post
     */
    public function getCommentsByPostId(int $postId): array;

    /**
     * Tạo comment mới
     */
    public function createComment(int $postId, int $userId, array $data): array;

    /**
     * Cập nhật comment
     */
    public function updateComment(int $commentId, int $userId, array $data): array;

    /**
     * Xóa comment
     */
    public function deleteComment(int $commentId, int $userId): array;

    /**
     * Lấy danh sách reply của một comment
     */
    public function getRepliesByCommentId(int $commentId): array;

    /**
     * Validate comment data
     */
    public function validateCommentData(array $data, int $postId): array;
}
