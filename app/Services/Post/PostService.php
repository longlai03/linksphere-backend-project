<?php

namespace App\Services\Post;

use App\Services\Base\BaseService;

interface PostService extends BaseService
{
    public function createPost(array $attributes): mixed;
    public function getPostById(int $postId, int $currentUserId): mixed;
    public function updatePost(int $postId, int $userId, array $attributes): mixed;
    public function deletePost(int $postId, int $userId): mixed;
    public function getFeedPosts(int $userId, array $filters): mixed;
    public function likePost(int $postId, int $userId): mixed;
    public function unlikePost(int $postId, int $userId): mixed;
}
