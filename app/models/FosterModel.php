<?php
declare(strict_types=1);

class FosterModel
{
    // -------------------------------------------------------------------------
    // CREATE
    // -------------------------------------------------------------------------

    /**
     * Insert a new foster application row.
     * Returns the new record id.
     */
    public static function create(array $data): int
    {
        db_exec(
            'INSERT INTO foster_applications
                (applicant_id, status, preferred_animal_type, preferred_notes,
                 household_capacity, current_foster_count, has_yard, household_notes,
                 created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 0, ?, ?, NOW(), NOW())',
            [
                $data['applicant_id'],
                $data['status']               ?? 'submitted',
                $data['preferred_animal_type'],
                $data['preferred_notes']      ?? null,
                (int)$data['household_capacity'],
                isset($data['has_yard']) && $data['has_yard'] !== '' ? (int)$data['has_yard'] : null,
                $data['household_notes']      ?? null,
            ]
        );

        return (int) db_insert_id();
    }

    // -------------------------------------------------------------------------
    // READ
    // -------------------------------------------------------------------------

    /** Return a single foster_applications row or null (no JOINs). */
    public static function find(int $id): ?array
    {
        return db_one(
            'SELECT * FROM foster_applications WHERE id = ?',
            [$id]
        );
    }

    /**
     * Return a foster application with applicant details joined.
     * Joined columns: applicant_full_name, applicant_email,
     *                 applicant_contact_number, applicant_barangay
     */
    public static function find_with_applicant(int $id): ?array
    {
        return db_one(
            'SELECT fa.*,
                    u.full_name        AS applicant_full_name,
                    u.email            AS applicant_email,
                    u.contact_number   AS applicant_contact_number,
                    b.name             AS applicant_barangay
               FROM foster_applications fa
               JOIN users              u  ON u.id = fa.applicant_id
               LEFT JOIN barangays     b  ON b.id = u.barangay_id
              WHERE fa.id = ?',
            [$id]
        );
    }

    /**
     * Paginated list of applications for a single user, newest first.
     * Returns paginate() result: [rows, total, page, pages, perPage].
     */
    public static function list_by_user(int $userId, int $page): array
    {
        return paginate(
            'SELECT * FROM foster_applications WHERE applicant_id = ? ORDER BY created_at DESC',
            [$userId],
            $page
        );
    }

    /**
     * Paginated, optionally status-filtered list for staff, with applicant name joined.
     * $filters may contain 'status' key.
     * Returns paginate() result.
     */
    public static function list_filtered(array $filters, int $page): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'fa.status = ?';
            $params[] = $filters['status'];
        }

        $whereClause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT fa.*, u.full_name AS applicant_name
                  FROM foster_applications fa
                  JOIN users u ON u.id = fa.applicant_id
                  {$whereClause}
                 ORDER BY fa.created_at DESC";

        return paginate($sql, $params, $page);
    }

    // -------------------------------------------------------------------------
    // UPDATE
    // -------------------------------------------------------------------------

    /**
     * Record an approve / reject decision.
     * Sets status, reviewed_by, reviewed_at=NOW(), decision_notes.
     */
    public static function decide(int $id, string $status, int $reviewerId, ?string $notes): void
    {
        db_exec(
            'UPDATE foster_applications
                SET status        = ?,
                    reviewed_by   = ?,
                    reviewed_at   = NOW(),
                    decision_notes= ?,
                    updated_at    = NOW()
              WHERE id = ?',
            [$status, $reviewerId, $notes, $id]
        );
    }

    // -------------------------------------------------------------------------
    // HISTORY
    // -------------------------------------------------------------------------

    /**
     * All foster applications submitted by a given user, newest first.
     * Used on the staff view page to show the applicant's history.
     */
    public static function applicant_history(int $userId): array
    {
        return db_all(
            'SELECT * FROM foster_applications
              WHERE applicant_id = ?
              ORDER BY created_at DESC',
            [$userId]
        );
    }
}
