<?php

namespace App\Services\Notification;

interface NotificationService
{
    public function getUserNotifications(int $userId): array;
    public function deleteNotification(int $id, int $userId): array;
    public function deleteBySenderAndType(int $userId, int $senderId, string $type): array;
    public function createNotification(array $data): array;
    public function markAsRead(int $id, int $userId): array;
}
