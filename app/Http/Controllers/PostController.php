<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Models\Attachment;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\Reaction;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{

    public function store(PostRequest $request): JsonResponse
    {
        $data = $request->validated();
        try {
            $user = auth()->user();
            $post = Post::query()->create([
                'user_id' => $user->id,
                'caption' => $data['caption'],
                'privacy' => $data['privacy'],
            ]);


            // Xử lý media gửi dưới dạng base64
            if (!empty($data['media']) && is_array($data['media'])) {
                foreach ($data['media'] as $index => $mediaItem) {
                    // Trích xuất thông tin từ phần tử media
                    $base64Image = $mediaItem['base64'] ?? null;
                    $position = $mediaItem['position'] ?? $index;
                    $taggedUser = $mediaItem['tagged_user'] ?? null;

                    // Tạo PostMedia
                    $postMedia = PostMedia::query()->create([
                        'post_id' => $post->id,
                        'user_id' => $user->id,
                        'position' => $position,
                        'tagged_user' => $taggedUser,
                        'uploaded_at' => now(),
                    ]);

                    // Kiểm tra base64 hợp lệ
                    if ($base64Image && preg_match('/^data:image\/(jpeg|png|jpg);base64,/', $base64Image)) {
                        [$type, $imageData] = explode(';', $base64Image);
                        [, $imageData] = explode(',', $imageData);

                        $imageBinary = base64_decode($imageData);
                        if ($imageBinary === false) {
                            return response()->json(['error' => 'Failed to decode image'], 400);
                        }

                        // Đặt tên và lưu ảnh
                        $extension = explode('/', explode(':', $type)[1])[1];
                        $fileName = 'post_' . time() . '_' . $index . '.' . $extension;
                        $path = 'attachments/' . $fileName;

                        Storage::disk('public')->put($path, $imageBinary);
                        $fileUrl = 'storage/' . $path;

                        // Lưu thông tin vào bảng attachments
                        Attachment::query()->create([
                            'user_id' => $user->id,
                            'post_media_id' => $postMedia->id,
                            'file_url' => $fileUrl,
                            'file_type' => 'image/' . $extension,
                            'size' => strlen($imageBinary), // Lấy kích thước nhị phân
                            'original_file_name' => $mediaItem['original_file_name'] ?? $fileName,
                            'uploaded_at' => now(),
                        ]);
                    } else {
                        return response()->json([
                            'error' => "Invalid base64 image at index {$index}"
                        ], 400);
                    }
                }
            }

            // Load media and attachment relationships
            $post->load(['media.attachment']);

            // Return the post as the root object (not wrapped in 'post' or 'posts')
            return response()->json([
                'message' => 'Post created successfully',
                'post' => $post,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // Phương thức lấy bài post theo ID
    public function show(int $postId): JsonResponse
    {
        try {
            $currentUser = auth()->user();
            
            $post = Post::with(['media.attachment', 'user'])->find($postId);

            if (!$post) {
                return response()->json([
                    'error' => 'Post not found'
                ], 404);
            }

            // Thêm số lượng likes
            $post->likesCount = $post->reactions()->count();
            
            // Kiểm tra xem user hiện tại đã like post này chưa
            if ($currentUser) {
                $post->liked = $post->reactions()->where('user_id', $currentUser->id)->exists();
            } else {
                $post->liked = false;
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

    // Phương thức cập nhật bài post (user cần phải là chủ bài post)
    public function update(PostRequest $request, int $postId): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized: No user found'
                ], 401);
            }

            $post = Post::find($postId);

            if (!$post) {
                return response()->json([
                    'error' => 'Post not found'
                ], 404);
            }

            // Only the owner can update
            if ($post->user_id !== $user->id) {
                return response()->json([
                    'error' => 'You are not authorized to update this post.'
                ], 403);
            }

            $validatedData = $request->validated();
            $post->update($validatedData);

            // Load media and attachment relationships
            $post->load(['media.attachment']);

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

    // Phương thức xóa bài post
    public function destroy(int $postId): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized: No user found'
                ], 401);
            }

            $post = Post::find($postId);

            if (!$post) {
                return response()->json([
                    'error' => 'Post not found'
                ], 404);
            }

            // Only the owner can delete
            if ($post->user_id !== $user->id) {
                return response()->json([
                    'error' => 'You are not authorized to delete this post.'
                ], 403);
            }

            $post->delete();

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
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized: No user found'
                ], 401);
            }

            // Lấy danh sách ID của những người user đang theo dõi
            $followingIds = $user->followings()
                ->where('status', 'accepted')
                ->pluck('followed_id')
                ->toArray();

            // Query để lấy posts
            $query = Post::query()
                ->with([
                    'user:id,username,nickname,avatar_url',
                    'media.attachment',
                    'comments' => function ($query) {
                        $query->with('user:id,username,nickname,avatar_url')
                            ->orderBy('created_at', 'desc')
                            ->limit(3);
                    },
                    'reactions' => function ($query) {
                        $query->with('user:id,username,nickname,avatar_url');
                    }
                ])
                ->where(function ($q) use ($user, $followingIds) {
                    // Bài viết của chính mình
                    $q->where('user_id', $user->id);
                    // Bài viết của bạn bè (theo dõi) với privacy là friends hoặc public
                    $q->orWhere(function ($q2) use ($followingIds) {
                        $q2->whereIn('user_id', $followingIds)
                            ->whereIn('privacy', ['public', 'friends']);
                    });
                    // Bài viết của người khác (không phải bạn bè) với privacy là public
                    $q->orWhere(function ($q3) use ($user, $followingIds) {
                        $q3->whereNotIn('user_id', array_merge([$user->id], $followingIds))
                            ->where('privacy', 'public');
                    });
                });

            // Loại bỏ các post đã load nếu có exclude_ids
            if (!empty($excludeIds)) {
                $query->whereNotIn('id', $excludeIds);
            }

            // Lấy tất cả post, không phân trang
            $posts = $query->orderBy('created_at', 'desc')->get();

            // Thêm thông tin liked và likes count cho mỗi post
            $posts = $posts->map(function ($post) use ($user) {
                $post->likesCount = $post->reactions()->count();
                $post->liked = $post->reactions()->where('user_id', $user->id)->exists();
                return $post;
            });

            return response()->json([
                'posts' => $posts
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error fetching posts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Like a post
     */
    public function like(int $postId): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized: No user found'
                ], 401);
            }

            $post = Post::find($postId);
            if (!$post) {
                return response()->json([
                    'error' => 'Post not found'
                ], 404);
            }

            // Kiểm tra xem user đã like post này chưa
            $existingReaction = Reaction::where('user_id', $user->id)
                ->where('post_id', $postId)
                ->first();

            if ($existingReaction) {
                return response()->json([
                    'error' => 'User has already liked this post'
                ], 400);
            }

            // Tạo reaction mới
            Reaction::create([
                'user_id' => $user->id,
                'post_id' => $postId,
            ]);

            // Lấy số lượng likes mới
            $likesCount = Reaction::where('post_id', $postId)->count();

            return response()->json([
                'message' => 'Post liked successfully',
                'likes_count' => $likesCount,
                'is_liked' => true
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error liking post: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unlike a post
     */
    public function unlike(int $postId): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized: No user found'
                ], 401);
            }

            $post = Post::find($postId);
            if (!$post) {
                return response()->json([
                    'error' => 'Post not found'
                ], 404);
            }

            // Tìm và xóa reaction bằng composite key
            $reaction = Reaction::where('user_id', $user->id)
                ->where('post_id', $postId)
                ->first();

            if (!$reaction) {
                return response()->json([
                    'error' => 'User has not liked this post'
                ], 400);
            }

            $reaction->delete();

            // Lấy số lượng likes mới
            $likesCount = Reaction::where('post_id', $postId)->count();

            return response()->json([
                'message' => 'Post unliked successfully',
                'likes_count' => $likesCount,
                'is_liked' => false
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error unliking post: ' . $e->getMessage()
            ], 500);
        }
    }
}
