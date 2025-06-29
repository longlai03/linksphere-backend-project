<?php

namespace App\Services\Notification;

use App\Repositories\Notification\NotificationRepository;
use App\Models\Notification;

class NotificationServiceImp implements NotificationService
{
    protected $notificationRepository;

    public function __construct(NotificationRepository $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * Lấy danh sách thông báo của user đang đăng nhập
     */
    public function getUserNotifications(int $userId): array
    {
        try {
            $notifications = $this->notificationRepository->getNotificationsByUserId($userId);

            // Enrich notifications with user information
            $enrichedNotifications = $notifications->map(function ($notification) {
                $notificationData = $notification->toArray();
                
                // Add sender information if available
                if ($notification->sender) {
                    $notificationData['from_user'] = [
                        'id' => $notification->sender->id,
                        'username' => $notification->sender->username,
                        'nickname' => $notification->sender->nickname,
                        'avatar_url' => $notification->sender->avatar_url,
                    ];
                } else {
                    // System notification (sender_id is null)
                    $notificationData['from_user'] = [
                        'id' => null,
                        'username' => 'system',
                        'nickname' => 'Hệ thống',
                        'avatar_url' => null,
                    ];
                }
                
                return $notificationData;
            });

            return [
                'success' => true,
                'data' => $enrichedNotifications
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Xóa thông báo theo ID
     */
    public function deleteNotification(int $id, int $userId): array
    {
        try {
            $notification = $this->notificationRepository->findByIdAndUserId($id, $userId);

            if (!$notification) {
                return [
                    'success' => false,
                    'error' => 'Thông báo không tồn tại hoặc bạn không có quyền xóa'
                ];
            }

            $this->notificationRepository->deleteById($id);

            return [
                'success' => true,
                'message' => 'Đã xóa thông báo thành công'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Xóa thông báo theo sender_id và type
     */
    public function deleteBySenderAndType(int $userId, int $senderId, string $type): array
    {
        try {
            $deleted = $this->notificationRepository->deleteBySenderAndType($userId, $senderId, $type);

            return [
                'success' => true,
                'message' => 'Đã xóa thông báo thành công',
                'deleted_count' => $deleted
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Tạo thông báo mới
     */
    public function createNotification(array $data): array
    {
        try {
            $notification = $this->notificationRepository->create($data);

            return [
                'success' => true,
                'data' => $notification,
                'message' => 'Tạo thông báo thành công'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Đánh dấu thông báo đã đọc
     */
    public function markAsRead(int $id, int $userId): array
    {
        try {
            $notification = $this->notificationRepository->findByIdAndUserId($id, $userId);

            if (!$notification) {
                return [
                    'success' => false,
                    'error' => 'Thông báo không tồn tại'
                ];
            }

            $this->notificationRepository->update($notification, ['read' => true]);

            return [
                'success' => true,
                'message' => 'Đã đánh dấu thông báo đã đọc'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }
}
