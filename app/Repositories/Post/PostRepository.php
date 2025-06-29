<?php

namespace App\Repositories\Post;

use App\Repositories\Base\BaseRepository;

interface PostRepository extends BaseRepository
{
    public function createPost(array $attributes): mixed;
    public function getPostById(int $postId): mixed;
    public function updatePost(int $postId, array $attributes): mixed;
    public function deletePost(int $postId): mixed;
    public function getFeedPosts(int $userId, array $followingIds, array $filters): mixed;
    public function likePost(int $postId, int $userId): mixed;
    public function unlikePost(int $postId, int $userId): mixed;
    public function checkUserLikedPost(int $postId, int $userId): bool;
    public function getPostLikesCount(int $postId): int;
}
