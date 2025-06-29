<?php

namespace App\Repositories\Notification;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;

interface NotificationRepository
{
    /**
     * Lấy danh sách thông báo của user
     */
    public function getNotificationsByUserId(int $userId): Collection;

    /**
     * Lấy thông báo theo ID và user_id
     */
    public function findByIdAndUserId(int $id, int $userId): ?Notification;

    /**
     * Xóa thông báo theo ID
     */
    public function deleteById(int $id): bool;

    /**
     * Xóa thông báo theo sender_id và type
     */
    public function deleteBySenderAndType(int $userId, int $senderId, string $type): int;

    /**
     * Tạo thông báo mới
     */
    public function create(array $data): Notification;

    /**
     * Cập nhật thông báo
     */
    public function update(Notification $notification, array $data): bool;
}
