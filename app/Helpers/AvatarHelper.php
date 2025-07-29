<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class AvatarHelper
{
    /**
     * Xử lý avatar base64 và lưu vào storage
     *
     */
    public static function processAvatarBase64(string $base64Data, ?string $oldAvatarUrl = null): ?string
    {
        if (empty($base64Data)) {
            return null;
        }
        // Kiểm tra format base64 hợp lệ
        if (!preg_match('/^data:image\/(jpeg|png|jpg);base64,/', $base64Data)) {
            throw new Exception('Invalid Base64 image string');
        }
        // Tách thông tin từ base64
        [$type, $imageData] = explode(';', $base64Data);
        [, $imageData] = explode(',', $imageData);
        // Decode base64
        $imageBinary = base64_decode($imageData);
        if ($imageBinary === false) {
            throw new Exception('Failed to decode image');
        }
        $fileName = 'avatar_' . time() . '.png';
        $path = 'avatars/' . $fileName;
        // Lưu file vào storage
        $saved = Storage::disk('public')->put($path, $imageBinary);
        if (!$saved) {
            throw new Exception('Failed to save image to storage');
        }
        // Xóa avatar cũ nếu có
        if ($oldAvatarUrl) {
            self::deleteOldAvatar($oldAvatarUrl);
        }
        return 'storage/' . $path;
    }

    /**
     * Xóa avatar cũ từ storage
     *
     */
    public static function deleteOldAvatar(string $avatarUrl): bool
    {
        try {
            if (empty($avatarUrl)) {
                return false;
            }
            $path = str_replace('storage/', '', $avatarUrl);
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->delete($path);
            }
            return false;
        } catch (Exception $e) {
            Log::error('Error deleting old avatar: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate base64 image
     *
     */
    public static function isValidBase64Image(string $base64Data): bool
    {
        if (empty($base64Data)) {
            return false;
        }
        // Kiểm tra format
        if (!preg_match('/^data:image\/(jpeg|png|jpg);base64,/', $base64Data)) {
            return false;
        }
        [$type, $imageData] = explode(';', $base64Data);
        [, $imageData] = explode(',', $imageData);
        $decoded = base64_decode($imageData, true);
        return $decoded !== false;
    }

    /**
     * Lấy extension từ base64 string
     *
     */
    public static function getExtensionFromBase64(string $base64Data): string
    {
        if (preg_match('/^data:image\/(jpeg|png|jpg);base64,/', $base64Data, $matches)) {
            $extension = $matches[1];
            return $extension === 'jpeg' ? 'jpg' : $extension;
        }
        return 'png';
    }
} 