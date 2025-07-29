<?php

namespace App\Repositories\Post;

use App\Models\Post;
use App\Models\PostMedia;
use App\Models\Attachment;
use App\Models\Reaction;
use App\Repositories\Base\BaseRepositoryElq;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
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
            $user = Auth::user();
            $post = Post::create([
                'user_id' => $user->id,
                'caption' => $attributes['caption'],
                'privacy' => $attributes['privacy'],
            ]);
            if (!empty($attributes['media']) && is_array($attributes['media'])) {
                foreach ($attributes['media'] as $index => $mediaItem) {
                    $base64Image = $mediaItem['base64'] ?? null;
                    $position = $mediaItem['position'] ?? $index;
                    $taggedUser = $mediaItem['tagged_user'] ?? null;
                    $postMedia = PostMedia::create([
                        'post_id' => $post->id,
                        'user_id' => $user->id,
                        'position' => $position,
                        'tagged_user' => $taggedUser,
                        'uploaded_at' => now(),
                    ]);
                    if ($base64Image && preg_match('/^data:image\/(jpeg|png|jpg);base64,/', $base64Image)) {
                        [$type, $imageData] = explode(';', $base64Image);
                        [, $imageData] = explode(',', $imageData);

                        $imageBinary = base64_decode($imageData);
                        if ($imageBinary === false) {
                            throw new Exception('Failed to decode image');
                        }
                        $extension = explode('/', explode(':', $type)[1])[1];
                        $fileName = 'post_' . time() . '_' . $index . '.' . $extension;
                        $path = 'attachments/' . $fileName;
                        Storage::disk('public')->put($path, $imageBinary);
                        $fileUrl = 'storage/' . $path;
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
        $post->update([
            'caption' => $attributes['caption'] ?? $post->caption,
            'privacy' => $attributes['privacy'] ?? $post->privacy,
        ]);
        if (!empty($attributes['media']) && is_array($attributes['media'])) {
            $hasNewImages = false;
            foreach ($attributes['media'] as $mediaItem) {
                if (!empty($mediaItem['base64']) && preg_match('/^data:image\/(jpeg|png|jpg);base64,/', $mediaItem['base64'])) {
                    $hasNewImages = true;
                    break;
                }
            }
            // Chỉ xử lý nếu có ảnh mới
            if ($hasNewImages) {
                $post->media()->delete();
                foreach ($attributes['media'] as $index => $mediaItem) {
                    $base64Image = $mediaItem['base64'] ?? null;
                    $position = $mediaItem['position'] ?? $index;
                    $taggedUser = $mediaItem['tagged_user'] ?? null;
                    $postMedia = PostMedia::create([
                        'post_id' => $post->id,
                        'user_id' => Auth::user()->id,
                        'position' => $position,
                        'tagged_user' => $taggedUser,
                        'uploaded_at' => now(),
                    ]);
                    if ($base64Image && preg_match('/^data:image\/(jpeg|png|jpg);base64,/', $base64Image)) {
                        [$type, $imageData] = explode(';', $base64Image);
                        [, $imageData] = explode(',', $imageData);

                        $imageBinary = base64_decode($imageData);
                        if ($imageBinary === false) {
                            throw new Exception('Failed to decode image');
                        }
                        $extension = explode('/', explode(':', $type)[1])[1];
                        $fileName = 'post_' . time() . '_' . $index . '.' . $extension;
                        $path = 'attachments/' . $fileName;
                        Storage::disk('public')->put($path, $imageBinary);
                        $fileUrl = 'storage/' . $path;
                        Attachment::create([
                            'user_id' => Auth::user()->id,
                            'post_media_id' => $postMedia->id,
                            'file_url' => $fileUrl,
                            'file_type' => 'image/' . $extension,
                            'size' => strlen($imageBinary),
                            'original_file_name' => $mediaItem['original_file_name'] ?? $fileName,
                            'uploaded_at' => now(),
                        ]);
                    }
                }
            }
        }
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
                        ->orderBy('created_at', 'desc');
                },
                'reactions' => function ($query) {
                    $query->with('user:id,username,nickname,avatar_url');
                }
            ])
            ->where(function ($q) use ($userId, $followingIds) {
                $q->where('user_id', $userId);
                $q->orWhere(function ($q2) use ($followingIds) {
                    $q2->whereIn('user_id', $followingIds)
                        ->whereIn('privacy', ['public', 'friends']);
                });
                $q->orWhere(function ($q3) use ($userId, $followingIds) {
                    $q3->whereNotIn('user_id', array_merge([$userId], $followingIds))
                        ->where('privacy', 'public');
                });
            });
        if (!empty($filters['exclude_ids'])) {
            $query->whereNotIn('id', $filters['exclude_ids']);
        }
        return $query->orderBy('created_at', 'desc')->get();
    }

    public function likePost(int $postId, int $userId): mixed
    {
        $reaction = Reaction::where('user_id', $userId)
            ->where('post_id', $postId)
            ->first();
        if ($reaction) {
            return false;
        }
        Reaction::create([
            'user_id' => $userId,
            'post_id' => $postId,
        ]);
        return $this->getPostLikesCount($postId);
    }

    public function unlikePost(int $postId, int $userId): mixed
    {
        $reaction = Reaction::where('user_id', $userId)
            ->where('post_id', $postId)
            ->first();
        if (!$reaction) {
            return false;
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
