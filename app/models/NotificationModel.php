<?php
declare(strict_types=1);

class NotificationModel
{
    /**
     * Return a paginated list of notifications for a user, newest first.
     *
     * @param int $userId
     * @param int $page
     * @return array  paginate() result: ['rows', 'total', 'page', 'pages', 'perPage']
     */
    public static function list_for(int $userId, int $page): array
    {
        $sql = 'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC';
        return paginate($sql, [$userId], $page, 20);
    }
}
