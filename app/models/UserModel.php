<?php
declare(strict_types=1);

class UserModel
{
    public static function find(int $id): ?array
    {
        return db_one(
            'SELECT u.*, b.name AS barangay_name
             FROM users u
             LEFT JOIN barangays b ON b.id = u.barangay_id
             WHERE u.id = ?
             LIMIT 1',
            [$id]
        );
    }

    public static function find_by_email(string $email): ?array
    {
        return db_one(
            'SELECT u.*, b.name AS barangay_name
             FROM users u
             LEFT JOIN barangays b ON b.id = u.barangay_id
             WHERE u.email = ?
             LIMIT 1',
            [strtolower(trim($email))]
        );
    }

    public static function create_resident(array $data): int
    {
        db_exec(
            'INSERT INTO users
                (role, account_status, email, password_hash, full_name, contact_number, barangay_id, created_at)
             VALUES
                (\'community_resident\', \'active\', ?, ?, ?, ?, ?, NOW())',
            [
                strtolower(trim($data['email'])),
                $data['password_hash'],
                trim($data['full_name']),
                !empty($data['contact_number']) ? trim($data['contact_number']) : null,
                !empty($data['barangay_id'])    ? (int)$data['barangay_id']     : null,
            ]
        );

        return (int)db_insert_id();
    }

    public static function create_staff(array $data): int
    {
        db_exec(
            'INSERT INTO users
                (role, account_status, email, password_hash, full_name, contact_number,
                 barangay_id, organization_name, created_by, created_at)
             VALUES (?, \'active\', ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                $data['role'],
                strtolower(trim($data['email'])),
                $data['password_hash'],
                trim($data['full_name']),
                !empty($data['contact_number'])   ? trim($data['contact_number'])  : null,
                !empty($data['barangay_id'])       ? (int)$data['barangay_id']      : null,
                !empty($data['organization_name']) ? trim($data['organization_name']) : null,
                !empty($data['created_by'])        ? (int)$data['created_by']       : null,
            ]
        );

        return (int)db_insert_id();
    }

    public static function update_profile(int $id, array $data): void
    {
        db_exec(
            'UPDATE users
             SET full_name       = ?,
                 contact_number  = ?,
                 barangay_id     = ?,
                 updated_at      = NOW()
             WHERE id = ?',
            [
                trim($data['full_name']),
                !empty($data['contact_number']) ? trim($data['contact_number']) : null,
                !empty($data['barangay_id'])    ? (int)$data['barangay_id']     : null,
                $id,
            ]
        );
    }

    public static function update_password(int $id, string $hash): void
    {
        db_exec(
            'UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?',
            [$hash, $id]
        );
    }

    /**
     * Returns paginate() result. Supports filters: role, status, search (on name/email).
     */
    public static function list_filtered(array $filters, int $page): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['role'])) {
            $where[]  = 'u.role = ?';
            $params[] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $where[]  = 'u.account_status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[]  = '(u.full_name LIKE ? OR u.email LIKE ?)';
            $term     = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $sql = 'SELECT u.*, b.name AS barangay_name
                FROM users u
                LEFT JOIN barangays b ON b.id = u.barangay_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY u.created_at DESC';

        return paginate($sql, $params, $page);
    }

    /**
     * Sets account_status; stores flagged_reason when status is 'flagged'.
     */
    public static function set_status(int $id, string $status, ?string $reason): void
    {
        db_exec(
            'UPDATE users
             SET account_status = ?,
                 flagged_reason  = ?,
                 updated_at      = NOW()
             WHERE id = ?',
            [$status, $reason, $id]
        );
    }

    /**
     * Returns counts keyed by role string.
     * e.g. ['community_resident' => 5, 'barangay_official' => 2, ...]
     */
    public static function count_by_role(): array
    {
        $rows = db_all(
            'SELECT role, COUNT(*) AS cnt FROM users GROUP BY role'
        );

        $result = [];
        foreach ($rows as $row) {
            $result[$row['role']] = (int)$row['cnt'];
        }

        return $result;
    }
}
