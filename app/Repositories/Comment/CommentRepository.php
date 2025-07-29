<?php

namespace App\Repositories\Comment;

use App\Models\Comments;
use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

interface CommentRepository
{
    public function getCommentsByPostId(int $postId): Collection;
    public function findById(int $id): ?Comments;
    public function findByIdAndUserId(int $id, int $userId): ?Comments;
    public function create(array $data): Comments;
    public function update(Comments $comment, array $data): bool;
    public function delete(Comments $comment): bool;
    public function getRepliesByCommentId(int $commentId): Collection;
    public function isCommentBelongsToPost(int $commentId, int $postId): bool;
    public function findPostById(int $postId): ?Post;
}
