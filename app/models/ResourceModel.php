<?php
declare(strict_types=1);

class ResourceModel
{
    /**
     * List published resources, optionally filtered by category, newest first.
     */
    public static function list_published(?int $categoryId, int $page): array
    {
        $params = [];
        $where  = 'WHERE r.is_published = 1';

        if ($categoryId !== null) {
            $where   .= ' AND r.category_id = ?';
            $params[] = $categoryId;
        }

        $sql = "SELECT r.*, rc.name AS category_name
                FROM educational_resources r
                LEFT JOIN resource_categories rc ON rc.id = r.category_id
                $where
                ORDER BY r.published_at DESC, r.created_at DESC";

        return paginate($sql, $params, $page, 12);
    }

    /**
     * Find a single resource by id, JOINing category name.
     */
    public static function find(int $id): ?array
    {
        return db_one(
            'SELECT r.*, rc.name AS category_name
             FROM educational_resources r
             LEFT JOIN resource_categories rc ON rc.id = r.category_id
             WHERE r.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /**
     * Find a single resource by slug, JOINing category name.
     */
    public static function find_by_slug(string $slug): ?array
    {
        return db_one(
            'SELECT r.*, rc.name AS category_name
             FROM educational_resources r
             LEFT JOIN resource_categories rc ON rc.id = r.category_id
             WHERE r.slug = ?
             LIMIT 1',
            [$slug]
        );
    }

    /**
     * List all resources for admin (includes unpublished), newest first.
     */
    public static function list_admin(int $page): array
    {
        $sql = 'SELECT r.*, rc.name AS category_name
                FROM educational_resources r
                LEFT JOIN resource_categories rc ON rc.id = r.category_id
                ORDER BY r.created_at DESC';

        return paginate($sql, [], $page, 20);
    }

    /**
     * Create a new resource and return its new id.
     */
    public static function create(array $data): int
    {
        db_exec(
            'INSERT INTO educational_resources
                (category_id, author_id, title, slug, body, summary, cover_image_path,
                 is_published, published_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $data['category_id'],
                $data['author_id'] ?? null,
                $data['title'],
                $data['slug'],
                $data['body'],
                $data['summary'] ?? null,
                $data['cover_image_path'] ?? null,
                $data['is_published'] ? 1 : 0,
                $data['is_published'] ? ($data['published_at'] ?? null) : null,
            ]
        );
        return (int)db_insert_id();
    }

    /**
     * Update an existing resource.
     */
    public static function update(int $id, array $data): void
    {
        db_exec(
            'UPDATE educational_resources
             SET category_id      = ?,
                 title            = ?,
                 slug             = ?,
                 body             = ?,
                 summary          = ?,
                 cover_image_path = COALESCE(?, cover_image_path),
                 is_published     = ?,
                 published_at     = ?,
                 updated_at       = NOW()
             WHERE id = ?',
            [
                $data['category_id'],
                $data['title'],
                $data['slug'],
                $data['body'],
                $data['summary'] ?? null,
                $data['cover_image_path'] ?? null,
                $data['is_published'] ? 1 : 0,
                $data['is_published'] ? ($data['published_at'] ?? null) : null,
                $id,
            ]
        );
    }

    /**
     * Publish or unpublish a resource.
     */
    public static function set_published(int $id, bool $published): void
    {
        if ($published) {
            db_exec(
                'UPDATE educational_resources
                 SET is_published = 1, published_at = NOW(), updated_at = NOW()
                 WHERE id = ?',
                [$id]
            );
        } else {
            db_exec(
                'UPDATE educational_resources
                 SET is_published = 0, updated_at = NOW()
                 WHERE id = ?',
                [$id]
            );
        }
    }

    /**
     * Return all active resource categories ordered by name.
     */
    public static function categories(): array
    {
        return db_all(
            'SELECT * FROM resource_categories WHERE is_active = 1 ORDER BY name'
        );
    }
}
