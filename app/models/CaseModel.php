<?php
declare(strict_types=1);

class CaseModel
{
    // -------------------------------------------------------------------------
    // CREATE
    // -------------------------------------------------------------------------

    /**
     * Open a new case from an existing report.
     * Generates case_number = CASE-<year>-<6-digit zero-padded sequential>.
     * Copies animal_type from the report.
     * Inserts initial case_updates row with to_status = 'open'.
     * Returns the new case id.
     */
    public static function create_from_report(int $reportId, int $managedBy): int
    {
        $report = db_one('SELECT animal_type FROM reports WHERE id = ?', [$reportId]);
        if (!$report) {
            throw new RuntimeException("Report {$reportId} not found.");
        }

        $year  = date('Y');
        $count = db_one('SELECT COUNT(*) AS cnt FROM cases', []);
        $seq   = (int)($count['cnt'] ?? 0) + 1;
        $caseNumber = 'CASE-' . $year . '-' . str_pad((string)$seq, 6, '0', STR_PAD_LEFT);

        db_exec(
            'INSERT INTO cases
                (report_id, case_number, status, animal_type, managed_by, opened_at, created_at, updated_at)
             VALUES (?, ?, \'open\', ?, ?, NOW(), NOW(), NOW())',
            [$reportId, $caseNumber, $report['animal_type'], $managedBy]
        );

        $caseId = (int) db_insert_id();

        // Initial update row
        db_exec(
            'INSERT INTO case_updates
                (case_id, from_status, to_status, note, created_by, created_at)
             VALUES (?, NULL, \'open\', \'Case opened.\', ?, NOW())',
            [$caseId, $managedBy]
        );

        return $caseId;
    }

    // -------------------------------------------------------------------------
    // READ
    // -------------------------------------------------------------------------

    /**
     * Return a case row joined with report title, barangay, and manager full_name.
     * Also joins pet name.
     */
    public static function find(int $id): ?array
    {
        return db_one(
            'SELECT c.*,
                    r.title         AS report_title,
                    r.reporter_id   AS reporter_id,
                    b.name          AS barangay_name,
                    m.full_name     AS manager_name,
                    p.name          AS pet_name
             FROM cases c
             LEFT JOIN reports  r ON r.id = c.report_id
             LEFT JOIN barangays b ON b.id = r.barangay_id
             LEFT JOIN users    m ON m.id = c.managed_by
             LEFT JOIN pets     p ON p.id = c.pet_profile_id
             WHERE c.id = ?',
            [$id]
        );
    }

    /** Return a case by its linked report_id, or null if none. */
    public static function find_by_report(int $reportId): ?array
    {
        return db_one(
            'SELECT c.*,
                    m.full_name AS manager_name,
                    p.name      AS pet_name
             FROM cases c
             LEFT JOIN users m ON m.id = c.managed_by
             LEFT JOIN pets  p ON p.id = c.pet_profile_id
             WHERE c.report_id = ?',
            [$reportId]
        );
    }

    /**
     * Paginated, filtered list of cases.
     * Supported filter keys: status.
     * Joined with report title, barangay name, manager name.
     */
    public static function list_filtered(array $filters, int $page): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'c.status = ?';
            $params[] = $filters['status'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = 'SELECT c.*,
                       r.title        AS report_title,
                       b.name         AS barangay_name,
                       m.full_name    AS manager_name
                FROM cases c
                LEFT JOIN reports   r ON r.id = c.report_id
                LEFT JOIN barangays b ON b.id = r.barangay_id
                LEFT JOIN users     m ON m.id = c.managed_by
                ' . $whereClause . '
                ORDER BY c.opened_at DESC';

        return paginate($sql, $params, $page);
    }

    // -------------------------------------------------------------------------
    // UPDATE
    // -------------------------------------------------------------------------

    /**
     * Update case status; sets closed_at when status is terminal.
     * Records a case_updates row with from/to statuses and optional note.
     */
    public static function update_status(int $id, string $newStatus, int $by, ?string $note): void
    {
        $current    = db_one('SELECT status FROM cases WHERE id = ?', [$id]);
        $fromStatus = $current ? $current['status'] : null;

        $terminalStatuses = ['closed', 'deceased', 'released', 'adopted'];
        $extraSets = in_array($newStatus, $terminalStatuses, true) ? ', closed_at = NOW()' : '';

        db_exec(
            'UPDATE cases
             SET status = ?, updated_at = NOW()' . $extraSets . '
             WHERE id = ?',
            [$newStatus, $id]
        );

        db_exec(
            'INSERT INTO case_updates
                (case_id, from_status, to_status, note, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())',
            [$id, $fromStatus, $newStatus, $note, $by]
        );
    }

    /** Insert a note-only case_updates row (no status change). */
    public static function add_update(int $caseId, string $note, int $by): void
    {
        db_exec(
            'INSERT INTO case_updates
                (case_id, from_status, to_status, note, created_by, created_at)
             VALUES (?, NULL, NULL, ?, ?, NOW())',
            [$caseId, $note, $by]
        );
    }

    /** Link a pet profile to a case. */
    public static function link_pet(int $caseId, int $petId): void
    {
        db_exec(
            'UPDATE cases SET pet_profile_id = ?, updated_at = NOW() WHERE id = ?',
            [$petId, $caseId]
        );
    }

    /**
     * Return all case_updates rows for a case, oldest first,
     * joined with author full_name.
     */
    public static function updates_for(int $caseId): array
    {
        return db_all(
            'SELECT cu.*, u.full_name AS author_name
             FROM case_updates cu
             LEFT JOIN users u ON u.id = cu.created_by
             WHERE cu.case_id = ?
             ORDER BY cu.created_at ASC',
            [$caseId]
        );
    }
}
