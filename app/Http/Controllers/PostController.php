<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Services\Post\PostService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    private PostService $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    public function store(PostRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $post = $this->postService->createPost($data);
            if (!$post) {
                return response()->json([
                    'error' => 'Có lỗi xảy ra khi tạo bài đăng'
                ], 500);
            }
            return response()->json([
                'message' => 'Post created successfully',
                'post' => $post,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(int $postId): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            $currentUserId = $currentUser ? $currentUser->id : 0;
            $post = $this->postService->getPostById($postId, $currentUserId);
            if (!$post) {
                return response()->json([
                    'error' => 'Post not found'
                ], 404);
            }
            return response()->json([
                'post' => $post,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error fetching post: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(PostRequest $request, int $postId): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized: No user found'
                ], 401);
            }
            $validatedData = $request->validated();
            $post = $this->postService->updatePost($postId, $user->id, $validatedData);
            if ($post === false) {
                return response()->json([
                    'error' => 'Post not found or you are not authorized to update this post.'
                ], 403);
            }
            return response()->json([
                'message' => 'Post updated successfully',
                'post' => $post,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error updating post: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $postId): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized: No user found'
                ], 401);
            }
            $result = $this->postService->deletePost($postId, $user->id);
            if ($result === false) {
                return response()->json([
                    'error' => 'Post not found or you are not authorized to delete this post.'
                ], 403);
            }
            return response()->json([
                'message' => 'Post deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error deleting post: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getFeedPost(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized: No user found'
                ], 401);
            }
            $filters = [];
            if ($request->has('exclude_ids')) {
                $filters['exclude_ids'] = $request->input('exclude_ids');
            }
            $posts = $this->postService->getFeedPosts($user->id, $filters);
            if ($posts === false) {
                return response()->json([
                    'error' => 'Có lỗi xảy ra khi lấy feed posts'
                ], 500);
            }
            return response()->json([
                'posts' => $posts
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error fetching posts: ' . $e->getMessage()
            ], 500);
        }
    }

    public function like(int $postId): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized: No user found'
                ], 401);
            }
            $result = $this->postService->likePost($postId, $user->id);
            if ($result === false) {
                return response()->json([
                    'error' => 'User has already liked this post'
                ], 400);
            }
            return response()->json([
                'message' => 'Post liked successfully',
                'likes_count' => $result['likes_count'],
                'is_liked' => $result['is_liked']
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error liking post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function unlike(int $postId): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized: No user found'
                ], 401);
            }
            $result = $this->postService->unlikePost($postId, $user->id);
            if ($result === false) {
                return response()->json([
                    'error' => 'User has not liked this post'
                ], 400);
            }
            return response()->json([
                'message' => 'Post unliked successfully',
                'likes_count' => $result['likes_count'],
                'is_liked' => $result['is_liked']
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error unliking post: ' . $e->getMessage()
            ], 500);
        }
    }
}
