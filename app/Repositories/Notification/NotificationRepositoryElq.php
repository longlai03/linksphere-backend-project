<?php

namespace App\Repositories\Notification;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;

class NotificationRepositoryElq implements NotificationRepository
{
    protected $model;

    public function __construct(Notification $notification)
    {
        $this->model = $notification;
    }

    /**
     * Lấy danh sách thông báo của user
     */
    public function getNotificationsByUserId(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->with(['sender' => function ($query) {
                $query->select(['id', 'username', 'nickname', 'avatar_url']);
            }])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Lấy thông báo theo ID và user_id
     */
    public function findByIdAndUserId(int $id, int $userId): ?Notification
    {
        return $this->model->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Xóa thông báo theo ID
     */
    public function deleteById(int $id): bool
    {
        $notification = $this->model->find($id);
        if ($notification) {
            return $notification->delete();
        }
        return false;
    }

    /**
     * Xóa thông báo theo sender_id và type
     */
    public function deleteBySenderAndType(int $userId, int $senderId, string $type): int
    {
        return $this->model->where('user_id', $userId)
            ->where('sender_id', $senderId)
            ->where('type', $type)
            ->delete();
    }

    /**
     * Tạo thông báo mới
     */
    public function create(array $data): Notification
    {
        return $this->model->create($data);
    }

    /**
     * Cập nhật thông báo
     */
    public function update(Notification $notification, array $data): bool
    {
        return $notification->update($data);
    }
}
