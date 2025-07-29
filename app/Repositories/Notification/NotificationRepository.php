<?php

namespace App\Repositories\Notification;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;

interface NotificationRepository
{
    public function getNotificationsByUserId(int $userId): Collection;
    public function findByIdAndUserId(int $id, int $userId): ?Notification;
    public function deleteById(int $id): bool;
    public function deleteBySenderAndType(int $userId, int $senderId, string $type): int;
    public function create(array $data): Notification;
    public function update(Notification $notification, array $data): bool;
}
