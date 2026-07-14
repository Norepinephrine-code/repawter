<?php
declare(strict_types=1);

function notify(
    int $userId,
    string $type,
    string $title,
    string $body,
    ?string $link = null,
    ?string $relatedType = null,
    ?int $relatedId = null
): int {
    db_exec(
        'INSERT INTO notifications
            (user_id, type, title, body, related_type, related_id, link_url, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
        [$userId, $type, $title, $body, $relatedType, $relatedId, $link]
    );
    $notifId = (int)db_insert_id();

    $user = db_one('SELECT email FROM users WHERE id = ? LIMIT 1', [$userId]);
    if ($user !== null) {
        db_exec(
            'INSERT INTO email_outbox
                (user_id, to_email, subject, body, type, related_type, related_id,
                 status, sent_at, queued_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, \'sent\', NOW(), NOW(), NOW())',
            [
                $userId,
                $user['email'],
                $title,
                $body,
                $type,
                $relatedType,
                $relatedId,
            ]
        );
    }

    return $notifId;
}

function notify_role(
    string $role,
    string $type,
    string $title,
    string $body,
    ?string $link = null
): int {
    $users = db_all(
        "SELECT id FROM users WHERE role = ? AND account_status = 'active'",
        [$role]
    );
    $count = 0;
    foreach ($users as $u) {
        notify((int)$u['id'], $type, $title, $body, $link);
        $count++;
    }
    return $count;
}

function unread_count(int $userId): int
{
    $row = db_one(
        'SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND read_at IS NULL',
        [$userId]
    );
    return (int)($row['cnt'] ?? 0);
}

function mark_read(int $id, int $userId): void
{
    db_exec(
        'UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ? AND read_at IS NULL',
        [$id, $userId]
    );
}

function mark_all_read(int $userId): void
{
    db_exec(
        'UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL',
        [$userId]
    );
}
