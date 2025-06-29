<?php

namespace App\Services\Post;

use App\Models\User;
use App\Repositories\Post\PostRepository;
use App\Services\Base\BaseServiceImp;
use Exception;

class PostServiceImp extends BaseServiceImp implements PostService
{
    private PostRepository $postRepository;

    public function __construct(PostRepository $postRepository)
    {
        $this->postRepository = $postRepository;
    }

    public function createPost(array $attributes): mixed
    {
        try {
            return $this->postRepository->createPost($attributes);
        } catch (Exception $e) {
            logger()->error('Error creating post: ' . $e->getMessage());
            return false;
        }
    }

    public function getPostById(int $postId, int $currentUserId): mixed
    {
        try {
            $post = $this->postRepository->getPostById($postId);
            if (!$post) {
                return false;
            }

            // Thêm số lượng likes
            $post->likesCount = $this->postRepository->getPostLikesCount($postId);
            
            // Kiểm tra xem user hiện tại đã like post này chưa
            if ($currentUserId > 0) {
                $post->liked = $this->postRepository->checkUserLikedPost($postId, $currentUserId);
            } else {
                $post->liked = false;
            }

            return $post;
        } catch (Exception $e) {
            logger()->error('Error getting post by id: ' . $e->getMessage());
            return false;
        }
    }

    public function updatePost(int $postId, int $userId, array $attributes): mixed
    {
        try {
            $post = $this->postRepository->getPostById($postId);
            if (!$post) {
                return false;
            }

            // Kiểm tra quyền sở hữu
            if ($post->user_id !== $userId) {
                return false;
            }

            return $this->postRepository->updatePost($postId, $attributes);
        } catch (Exception $e) {
            logger()->error('Error updating post: ' . $e->getMessage());
            return false;
        }
    }

    public function deletePost(int $postId, int $userId): mixed
    {
        try {
            $post = $this->postRepository->getPostById($postId);
            if (!$post) {
                return false;
            }

            // Kiểm tra quyền sở hữu
            if ($post->user_id !== $userId) {
                return false;
            }

            return $this->postRepository->deletePost($postId);
        } catch (Exception $e) {
            logger()->error('Error deleting post: ' . $e->getMessage());
            return false;
        }
    }

    public function getFeedPosts(int $userId, array $filters): mixed
    {
        try {
            // Lấy danh sách ID của những người user đang theo dõi
            $user = User::find($userId);
            $followingIds = $user->followings()
                ->where('status', 'accepted')
                ->pluck('followed_id')
                ->toArray();

            $posts = $this->postRepository->getFeedPosts($userId, $followingIds, $filters);

            // Thêm thông tin liked và likes count cho mỗi post
            $posts = $posts->map(function ($post) use ($userId) {
                $post->likesCount = $this->postRepository->getPostLikesCount($post->id);
                $post->liked = $this->postRepository->checkUserLikedPost($post->id, $userId);
                return $post;
            });

            return $posts;
        } catch (Exception $e) {
            logger()->error('Error getting feed posts: ' . $e->getMessage());
            return false;
        }
    }

    public function likePost(int $postId, int $userId): mixed
    {
        try {
            $likesCount = $this->postRepository->likePost($postId, $userId);
            
            if ($likesCount === false) {
                return false; // Đã like rồi
            }

            return [
                'likes_count' => $likesCount,
                'is_liked' => true
            ];
        } catch (Exception $e) {
            logger()->error('Error liking post: ' . $e->getMessage());
            return false;
        }
    }

    public function unlikePost(int $postId, int $userId): mixed
    {
        try {
            $likesCount = $this->postRepository->unlikePost($postId, $userId);
            
            if ($likesCount === false) {
                return false; // Chưa like
            }

            return [
                'likes_count' => $likesCount,
                'is_liked' => false
            ];
        } catch (Exception $e) {
            logger()->error('Error unliking post: ' . $e->getMessage());
            return false;
        }
    }
}
