<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Models\Attachment;
use App\Models\Post;
use App\Models\PostMedia;
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
            $post = Post::with(['media.attachment', 'user'])->find($postId);

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
    
    public function getAllPost(Request $request): JsonResponse
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

            // Lấy danh sách ID của những post đã được load (để tránh trùng lặp)
            $excludeIds = $request->input('exclude_ids', []);
            if (!is_array($excludeIds)) {
                $excludeIds = [];
            }

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
                ->where(function ($query) use ($user, $followingIds) {
                    $query->where(function ($q) use ($user, $followingIds) {
                        // Lấy tất cả bài post public của mọi người
                        $q->where('privacy', 'public')
                            // HOẶC
                            ->orWhere(function ($q) use ($user, $followingIds) {
                                // Lấy bài post của những người user đang theo dõi
                                $q->whereIn('user_id', $followingIds)
                                    ->where('privacy', 'public');
                            })
                            // HOẶC
                            ->orWhere(function ($q) use ($user) {
                                // Lấy tất cả bài post của chính user (cả private và public)
                                $q->where('user_id', $user->id);
                            });
                    });
                });

            // Loại bỏ các post đã load nếu có exclude_ids
            if (!empty($excludeIds)) {
                $query->whereNotIn('id', $excludeIds);
            }

            // Kiểm tra xem có yêu cầu sắp xếp ngẫu nhiên không
            if ($request->has('random') && $request->input('random')) {
                $query->inRandomOrder();
                // Nếu là random, lấy 5 post thay vì phân trang
                $posts = $query->limit(5)->get();
                
                return response()->json([
                    'posts' => [
                        'data' => $posts,
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 5,
                        'total' => $posts->count(),
                        'has_more' => $posts->count() === 5
                    ]
                ]);
            } else {
                // Phân trang kết quả như cũ
                $posts = $query->orderBy('created_at', 'desc')->paginate(10);

                return response()->json([
                    'posts' => $posts,
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total()
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error fetching posts: ' . $e->getMessage()
            ], 500);
        }
    }
}
