<?php

namespace App\Services\Notification;

use Illuminate\Database\Eloquent\Collection;

interface NotificationService
{
    /**
     * Lấy danh sách thông báo của user đang đăng nhập
     */
    public function getUserNotifications(int $userId): array;

    /**
     * Xóa thông báo theo ID
     */
    public function deleteNotification(int $id, int $userId): array;

    /**
     * Xóa thông báo theo sender_id và type
     */
    public function deleteBySenderAndType(int $userId, int $senderId, string $type): array;

    /**
     * Tạo thông báo mới
     */
    public function createNotification(array $data): array;

    /**
     * Đánh dấu thông báo đã đọc
     */
    public function markAsRead(int $id, int $userId): array;
}
