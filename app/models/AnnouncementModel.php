<?php
declare(strict_types=1);

class AnnouncementModel
{
    public static function create(array $data): int
    {
        db_exec(
            "INSERT INTO announcements
                (author_id, barangay_id, category, title, body, location_text,
                 event_start, event_end, is_all_day, facebook_url,
                 status, publish_at, published_at, created_at, updated_at)
             VALUES
                (:author_id, :barangay_id, :category, :title, :body, :location_text,
                 :event_start, :event_end, :is_all_day, :facebook_url,
                 :status, :publish_at, :published_at, NOW(), NOW())",
            [
                ':author_id'    => $data['author_id'],
                ':barangay_id'  => $data['barangay_id']  ?? null,
                ':category'     => $data['category'],
                ':title'        => $data['title'],
                ':body'         => $data['body'],
                ':location_text'=> $data['location_text'] ?? null,
                ':event_start'  => $data['event_start']   ?? null,
                ':event_end'    => $data['event_end']     ?? null,
                ':is_all_day'   => isset($data['is_all_day']) ? (int)(bool)$data['is_all_day'] : 0,
                ':facebook_url' => $data['facebook_url']  ?? null,
                ':status'       => $data['status']        ?? 'draft',
                ':publish_at'   => $data['publish_at']    ?? null,
                ':published_at' => $data['published_at']  ?? null,
            ]
        );
        return (int)db_insert_id();
    }

    public static function update(int $id, array $data): void
    {
        db_exec(
            "UPDATE announcements SET
                barangay_id   = :barangay_id,
                category      = :category,
                title         = :title,
                body          = :body,
                location_text = :location_text,
                event_start   = :event_start,
                event_end     = :event_end,
                is_all_day    = :is_all_day,
                facebook_url  = :facebook_url,
                status        = :status,
                publish_at    = :publish_at,
                published_at  = :published_at,
                updated_at    = NOW()
             WHERE id = :id",
            [
                ':id'           => $id,
                ':barangay_id'  => $data['barangay_id']  ?? null,
                ':category'     => $data['category'],
                ':title'        => $data['title'],
                ':body'         => $data['body'],
                ':location_text'=> $data['location_text'] ?? null,
                ':event_start'  => $data['event_start']   ?? null,
                ':event_end'    => $data['event_end']     ?? null,
                ':is_all_day'   => isset($data['is_all_day']) ? (int)(bool)$data['is_all_day'] : 0,
                ':facebook_url' => $data['facebook_url']  ?? null,
                ':status'       => $data['status']        ?? 'draft',
                ':publish_at'   => $data['publish_at']    ?? null,
                ':published_at' => $data['published_at']  ?? null,
            ]
        );
    }

    public static function find(int $id): ?array
    {
        return db_one(
            "SELECT a.*,
                    u.full_name AS author_name,
                    b.name      AS barangay_name
             FROM   announcements a
             JOIN   users          u ON u.id = a.author_id
             LEFT JOIN barangays   b ON b.id = a.barangay_id
             WHERE  a.id = :id",
            [':id' => $id]
        );
    }

    public static function list_published(int $page): array
    {
        $sql = "SELECT a.*,
                       u.full_name AS author_name,
                       b.name      AS barangay_name
                FROM   announcements a
                JOIN   users          u ON u.id = a.author_id
                LEFT JOIN barangays   b ON b.id = a.barangay_id
                WHERE  a.status = 'published'
                ORDER  BY a.published_at DESC, a.id DESC";
        return paginate($sql, [], $page);
    }

    public static function list_admin(array $filters, int $page): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]            = 'a.status = :status';
            $params[':status']  = $filters['status'];
        }

        if (!empty($filters['category'])) {
            $where[]             = 'a.category = :category';
            $params[':category'] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $where[]            = 'a.title LIKE :search';
            $params[':search']  = '%' . $filters['search'] . '%';
        }

        $sql = "SELECT a.*,
                       u.full_name AS author_name,
                       b.name      AS barangay_name
                FROM   announcements a
                JOIN   users          u ON u.id = a.author_id
                LEFT JOIN barangays   b ON b.id = a.barangay_id
                WHERE  " . implode(' AND ', $where) . "
                ORDER  BY a.created_at DESC";

        return paginate($sql, $params, $page);
    }

    public static function events_between(string $startIso, string $endIso): array
    {
        return db_all(
            "SELECT id, title, category, event_start, event_end,
                    is_all_day, facebook_url, location_text
             FROM   announcements
             WHERE  status = 'published'
               AND  event_start IS NOT NULL
               AND  event_start < :end
               AND  COALESCE(event_end, event_start) >= :start",
            [':start' => $startIso, ':end' => $endIso]
        );
    }

    public static function publish(int $id, int $by): void
    {
        db_exec(
            "UPDATE announcements
             SET    status = 'published', published_at = NOW(), updated_at = NOW()
             WHERE  id = :id",
            [':id' => $id]
        );
    }

    public static function archive(int $id): void
    {
        db_exec(
            "UPDATE announcements
             SET    status = 'archived', updated_at = NOW()
             WHERE  id = :id",
            [':id' => $id]
        );
    }
}
