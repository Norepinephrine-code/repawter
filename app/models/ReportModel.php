<?php
declare(strict_types=1);

class ReportModel
{

    public static function create(array $data): int
    {
        db_exec(
            'INSERT INTO reports
                (reporter_id, barangay_id, animal_type, species_other, title,
                 description, location_address, location_landmark, urgency,
                 photo_path, photo_original_name, status, is_locked, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'submitted\', 0, NOW(), NOW())',
            [
                $data['reporter_id'],
                $data['barangay_id']       ?? null,
                $data['animal_type'],
                $data['species_other']     ?? null,
                $data['title']             ?? null,
                $data['description'],
                $data['location_address'],
                $data['location_landmark'] ?? null,
                $data['urgency'],
                $data['photo_path'],
                $data['photo_original_name'] ?? null,
            ]
        );

        $reportId = (int) db_insert_id();

        db_exec(
            'INSERT INTO report_status_history
                (report_id, from_status, to_status, changed_by, note, created_at)
             VALUES (?, NULL, \'submitted\', ?, NULL, NOW())',
            [$reportId, $data['reporter_id']]
        );

        return $reportId;
    }

    public static function find(int $id): ?array
    {
        return db_one('SELECT * FROM reports WHERE id = ?', [$id]);
    }

    public static function find_with_details(int $id): ?array
    {
        return db_one(
            'SELECT r.*,
                    b.name            AS barangay_name,
                    rep.full_name     AS reporter_name,
                    rep.email         AS reporter_email,
                    rep.contact_number AS reporter_contact,
                    asgn.full_name    AS assigned_to_name,
                    veri.full_name    AS verified_by_name
             FROM reports r
             LEFT JOIN barangays b    ON b.id  = r.barangay_id
             LEFT JOIN users     rep  ON rep.id = r.reporter_id
             LEFT JOIN users     asgn ON asgn.id = r.assigned_to
             LEFT JOIN users     veri ON veri.id = r.verified_by
             WHERE r.id = ?',
            [$id]
        );
    }

    public static function list_by_user(int $userId, int $page): array
    {
        $sql = 'SELECT r.*,
                       b.name AS barangay_name
                FROM reports r
                LEFT JOIN barangays b ON b.id = r.barangay_id
                WHERE r.reporter_id = ?
                ORDER BY r.created_at DESC';

        return paginate($sql, [$userId], $page);
    }

    public static function list_filtered(array $filters, int $page): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'r.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['urgency'])) {
            $where[]  = 'r.urgency = ?';
            $params[] = $filters['urgency'];
        }

        if (!empty($filters['barangay_id'])) {
            $where[]  = 'r.barangay_id = ?';
            $params[] = (int) $filters['barangay_id'];
        }

        if (isset($filters['assigned_to']) && $filters['assigned_to'] !== '') {
            $where[]  = 'r.assigned_to = ?';
            $params[] = (int) $filters['assigned_to'];
        }

        if (!empty($filters['search'])) {
            $like     = '%' . $filters['search'] . '%';
            $where[]  = '(r.title LIKE ? OR r.description LIKE ? OR r.location_address LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = 'SELECT r.*,
                       b.name         AS barangay_name,
                       rep.full_name  AS reporter_name,
                       asgn.full_name AS assigned_to_name
                FROM reports r
                LEFT JOIN barangays b    ON b.id  = r.barangay_id
                LEFT JOIN users     rep  ON rep.id = r.reporter_id
                LEFT JOIN users     asgn ON asgn.id = r.assigned_to
                ' . $whereClause . '
                ORDER BY r.created_at DESC';

        return paginate($sql, $params, $page);
    }

    public static function update(int $id, array $data): void
    {
        $allowed = [
            'barangay_id', 'animal_type', 'species_other', 'title',
            'description', 'location_address', 'location_landmark',
            'urgency', 'photo_path', 'photo_original_name',
        ];

        $setClauses = [];
        $params     = [];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $setClauses[] = $col . ' = ?';
                $params[]     = $data[$col];
            }
        }

        if (empty($setClauses)) {
            return;
        }

        $setClauses[] = 'updated_at = NOW()';
        $params[]     = $id;

        db_exec(
            'UPDATE reports SET ' . implode(', ', $setClauses) . ' WHERE id = ?',
            $params
        );
    }

    public static function set_status(int $id, string $newStatus, int $changedBy, ?string $note): void
    {
        $current = db_one('SELECT status FROM reports WHERE id = ?', [$id]);
        $fromStatus = $current ? $current['status'] : null;

        $extraSets = '';
        if ($newStatus === 'resolved') {
            $extraSets = ', resolved_at = NOW()';
        } elseif ($newStatus === 'archived') {
            $extraSets = ', archived_at = NOW()';
        }

        $isLocked = ($newStatus !== 'submitted') ? 1 : 0;

        db_exec(
            'UPDATE reports
             SET status = ?, is_locked = ?, updated_at = NOW()' . $extraSets . '
             WHERE id = ?',
            [$newStatus, $isLocked, $id]
        );

        db_exec(
            'INSERT INTO report_status_history
                (report_id, from_status, to_status, changed_by, note, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())',
            [$id, $fromStatus, $newStatus, $changedBy, $note]
        );
    }

    public static function assign(int $id, int $assigneeId, int $by): void
    {
        $current    = db_one('SELECT status FROM reports WHERE id = ?', [$id]);
        $fromStatus = $current ? $current['status'] : null;

        db_exec(
            'UPDATE reports
             SET assigned_to = ?, assigned_at = NOW(), status = \'assigned\',
                 is_locked = 1, updated_at = NOW()
             WHERE id = ?',
            [$assigneeId, $id]
        );

        db_exec(
            'INSERT INTO report_status_history
                (report_id, from_status, to_status, changed_by, note, created_at)
             VALUES (?, ?, \'assigned\', ?, NULL, NOW())',
            [$id, $fromStatus, $by]
        );
    }

    public static function set_verified(int $id, int $by): void
    {
        $current    = db_one('SELECT status FROM reports WHERE id = ?', [$id]);
        $fromStatus = $current ? $current['status'] : null;

        db_exec(
            'UPDATE reports
             SET verified_by = ?, verified_at = NOW(), status = \'verified\',
                 is_locked = 1, updated_at = NOW()
             WHERE id = ?',
            [$by, $id]
        );

        db_exec(
            'INSERT INTO report_status_history
                (report_id, from_status, to_status, changed_by, note, created_at)
             VALUES (?, ?, \'verified\', ?, NULL, NOW())',
            [$id, $fromStatus, $by]
        );
    }

    public static function archive(int $id, int $by): void
    {
        self::set_status($id, 'archived', $by, null);
    }

    public static function status_history(int $reportId): array
    {
        return db_all(
            'SELECT h.*, u.full_name AS changer_name
             FROM report_status_history h
             LEFT JOIN users u ON u.id = h.changed_by
             WHERE h.report_id = ?
             ORDER BY h.created_at ASC',
            [$reportId]
        );
    }

    public static function is_locked(int $id): bool
    {
        $row = db_one('SELECT is_locked FROM reports WHERE id = ?', [$id]);
        return (bool) ($row['is_locked'] ?? false);
    }

    public static function active_criteria(): array
    {
        return db_all(
            'SELECT * FROM verification_criteria WHERE is_active = 1 ORDER BY sort_order ASC',
            []
        );
    }

    public static function checklist_for(int $reportId): array
    {
        return db_all(
            'SELECT vc.id AS criteria_id, vc.code, vc.label, vc.description,
                    vc.is_required, vc.sort_order,
                    rcr.is_met, rcr.note, rcr.checked_by, rcr.checked_at
             FROM verification_criteria vc
             LEFT JOIN report_checklist_responses rcr
                   ON rcr.criteria_id = vc.id AND rcr.report_id = ?
             WHERE vc.is_active = 1
             ORDER BY vc.sort_order ASC',
            [$reportId]
        );
    }

    public static function save_checklist(int $reportId, array $responses, int $by): void
    {
        foreach ($responses as $criteriaId => $resp) {
            $isMet = isset($resp['is_met']) && $resp['is_met'] ? 1 : 0;
            $note  = isset($resp['note']) ? (string) $resp['note'] : null;

            db_exec(
                'INSERT INTO report_checklist_responses
                    (report_id, criteria_id, is_met, note, checked_by, checked_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    is_met = VALUES(is_met),
                    note   = VALUES(note),
                    checked_by  = VALUES(checked_by),
                    checked_at  = NOW(),
                    updated_at  = NOW()',
                [$reportId, (int) $criteriaId, $isMet, $note, $by]
            );
        }
    }

    public static function all_required_met(int $reportId): bool
    {

        $row = db_one(
            'SELECT COUNT(*) AS cnt
             FROM verification_criteria vc
             LEFT JOIN report_checklist_responses rcr
                   ON rcr.criteria_id = vc.id AND rcr.report_id = ?
             WHERE vc.is_active = 1
               AND vc.is_required = 1
               AND (rcr.id IS NULL OR rcr.is_met = 0)',
            [$reportId]
        );

        return ((int) ($row['cnt'] ?? 1)) === 0;
    }
}
