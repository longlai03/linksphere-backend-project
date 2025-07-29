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

    public function getNotificationsByUserId(int $userId): Collection
    {
        return Notification::where('user_id', $userId)
            ->with(['sender' => function ($query) {
                $query->select(['id', 'username', 'nickname', 'avatar_url']);
            }])
            ->orderByDesc('created_at')
            ->get();
    }

    public function findByIdAndUserId(int $id, int $userId): ?Notification
    {
        return Notification::where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function deleteById(int $id): bool
    {
        $notification = Notification::find($id);
        if ($notification) {
            return $notification->delete();
        }
        return false;
    }

    public function deleteBySenderAndType(int $userId, int $senderId, string $type): int
    {
        return Notification::where('user_id', $userId)
            ->where('sender_id', $senderId)
            ->where('type', $type)
            ->delete();
    }

    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    public function update(Notification $notification, array $data): bool
    {
        return Notification::update($data);
    }
}
