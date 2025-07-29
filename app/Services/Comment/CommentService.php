<?php

namespace App\Services\Comment;

interface CommentService
{
    public function getCommentsByPostId(int $postId): array;
    public function createComment(int $postId, int $userId, array $data): array;
    public function updateComment(int $commentId, int $userId, array $data): array;
    public function deleteComment(int $commentId, int $userId): array;
    public function getRepliesByCommentId(int $commentId): array;
    public function validateCommentData(array $data, int $postId): array;
}
