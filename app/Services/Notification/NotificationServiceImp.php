<?php

namespace App\Services\Notification;

use App\Repositories\Notification\NotificationRepository;
use App\Repositories\Follower\FollowerRepository;
use Exception;

class NotificationServiceImp implements NotificationService
{
    protected $notificationRepository;
    protected $followerRepository;

    public function __construct(NotificationRepository $notificationRepository, FollowerRepository $followerRepository)
    {
        $this->notificationRepository = $notificationRepository;
        $this->followerRepository = $followerRepository;
    }

    public function getUserNotifications(int $userId): array
    {
        try {
            $notifications = $this->notificationRepository->getNotificationsByUserId($userId);
            $enrichedNotifications = $notifications->map(function ($notification) {
                $notificationData = $notification->toArray();
                if ($notification->sender) {
                    $notificationData['from_user'] = [
                        'id' => $notification->sender->id,
                        'username' => $notification->sender->username,
                        'nickname' => $notification->sender->nickname,
                        'avatar_url' => $notification->sender->avatar_url,
                    ];
                } else {
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
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }
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
            if ($notification->type === 'follow_request' && $notification->sender_id) {
                $this->followerRepository->declineFollow($userId, $notification->sender_id);
            }
            $this->notificationRepository->deleteById($id);
            return [
                'success' => true,
                'message' => 'Đã xóa thông báo thành công'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }
    public function deleteBySenderAndType(int $userId, int $senderId, string $type): array
    {
        try {
            $deleted = $this->notificationRepository->deleteBySenderAndType($userId, $senderId, $type);
            return [
                'success' => true,
                'message' => 'Đã xóa thông báo thành công',
                'deleted_count' => $deleted
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    public function createNotification(array $data): array
    {
        try {
            $notification = $this->notificationRepository->create($data);
            return [
                'success' => true,
                'data' => $notification,
                'message' => 'Tạo thông báo thành công'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

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
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }
}
