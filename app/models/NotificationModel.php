<?php
declare(strict_types=1);

class NotificationModel
{

    public static function list_for(int $userId, int $page): array
    {
        $sql = 'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC';
        return paginate($sql, [$userId], $page, 20);
    }
}
