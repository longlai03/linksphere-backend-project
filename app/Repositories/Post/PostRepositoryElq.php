<?php

namespace App\Repositories\Post;

use App\Models\Post;
use App\Models\PostMedia;
use App\Models\Attachment;
use App\Models\Reaction;
use App\Repositories\Base\BaseRepositoryElq;
use Illuminate\Support\Facades\Storage;
use Exception;

class PostRepositoryElq extends BaseRepositoryElq implements PostRepository
{
    public function getModel(): string
    {
        return Post::class;
    }

    public function createPost(array $attributes): mixed
    {
        try {
            $user = auth()->user();
            $post = Post::create([
                'user_id' => $user->id,
                'caption' => $attributes['caption'],
                'privacy' => $attributes['privacy'],
            ]);

            // Xử lý media gửi dưới dạng base64
            if (!empty($attributes['media']) && is_array($attributes['media'])) {
                foreach ($attributes['media'] as $index => $mediaItem) {
                    $base64Image = $mediaItem['base64'] ?? null;
                    $position = $mediaItem['position'] ?? $index;
                    $taggedUser = $mediaItem['tagged_user'] ?? null;

                    // Tạo PostMedia
                    $postMedia = PostMedia::create([
                        'post_id' => $post->id,
                        'user_id' => $user->id,
                        'position' => $position,
                        'tagged_user' => $taggedUser,
                        'uploaded_at' => now(),
                    ]);

                    // Xử lý base64 image
                    if ($base64Image && preg_match('/^data:image\/(jpeg|png|jpg);base64,/', $base64Image)) {
                        [$type, $imageData] = explode(';', $base64Image);
                        [, $imageData] = explode(',', $imageData);

                        $imageBinary = base64_decode($imageData);
                        if ($imageBinary === false) {
                            throw new Exception('Failed to decode image');
                        }

                        // Đặt tên và lưu ảnh
                        $extension = explode('/', explode(':', $type)[1])[1];
                        $fileName = 'post_' . time() . '_' . $index . '.' . $extension;
                        $path = 'attachments/' . $fileName;

                        Storage::disk('public')->put($path, $imageBinary);
                        $fileUrl = 'storage/' . $path;

                        // Lưu thông tin vào bảng attachments
                        Attachment::create([
                            'user_id' => $user->id,
                            'post_media_id' => $postMedia->id,
                            'file_url' => $fileUrl,
                            'file_type' => 'image/' . $extension,
                            'size' => strlen($imageBinary),
                            'original_file_name' => $mediaItem['original_file_name'] ?? $fileName,
                            'uploaded_at' => now(),
                        ]);
                    } else {
                        throw new Exception("Invalid base64 image at index {$index}");
                    }
                }
            }

            // Load media and attachment relationships
            $post->load(['media.attachment']);
            return $post;
        } catch (Exception $e) {
            throw new Exception('Error creating post: ' . $e->getMessage());
        }
    }

    public function getPostById(int $postId): mixed
    {
        return Post::with(['media.attachment', 'user'])->find($postId);
    }

    public function updatePost(int $postId, array $attributes): mixed
    {
        $post = Post::find($postId);
        if (!$post) {
            return false;
        }

        $post->update($attributes);
        $post->load(['media.attachment']);
        return $post;
    }

    public function deletePost(int $postId): mixed
    {
        $post = Post::find($postId);
        if (!$post) {
            return false;
        }

        return $post->delete();
    }

    public function getFeedPosts(int $userId, array $followingIds, array $filters): mixed
    {
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
            ->where(function ($q) use ($userId, $followingIds) {
                // Bài viết của chính mình
                $q->where('user_id', $userId);
                // Bài viết của bạn bè (theo dõi) với privacy là friends hoặc public
                $q->orWhere(function ($q2) use ($followingIds) {
                    $q2->whereIn('user_id', $followingIds)
                        ->whereIn('privacy', ['public', 'friends']);
                });
                // Bài viết của người khác (không phải bạn bè) với privacy là public
                $q->orWhere(function ($q3) use ($userId, $followingIds) {
                    $q3->whereNotIn('user_id', array_merge([$userId], $followingIds))
                        ->where('privacy', 'public');
                });
            });

        // Loại bỏ các post đã load nếu có exclude_ids
        if (!empty($filters['exclude_ids'])) {
            $query->whereNotIn('id', $filters['exclude_ids']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function likePost(int $postId, int $userId): mixed
    {
        // Kiểm tra xem user đã like post này chưa
        $existingReaction = Reaction::where('user_id', $userId)
            ->where('post_id', $postId)
            ->first();

        if ($existingReaction) {
            return false; // Đã like rồi
        }

        // Tạo reaction mới
        Reaction::create([
            'user_id' => $userId,
            'post_id' => $postId,
        ]);

        return $this->getPostLikesCount($postId);
    }

    public function unlikePost(int $postId, int $userId): mixed
    {
        // Tìm và xóa reaction
        $reaction = Reaction::where('user_id', $userId)
            ->where('post_id', $postId)
            ->first();

        if (!$reaction) {
            return false; // Chưa like
        }

        $reaction->delete();
        return $this->getPostLikesCount($postId);
    }

    public function checkUserLikedPost(int $postId, int $userId): bool
    {
        return Reaction::where('user_id', $userId)
            ->where('post_id', $postId)
            ->exists();
    }

    public function getPostLikesCount(int $postId): int
    {
        return Reaction::where('post_id', $postId)->count();
    }
}
