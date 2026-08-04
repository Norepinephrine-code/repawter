<?php
declare(strict_types=1);

class PetModel
{
    public static function create(array $data): int
    {
        db_exec(
            'INSERT INTO pets
                (created_by, name, animal_type, species_other, breed, sex,
                 approx_age_months, size, color, is_vaccinated, is_neutered,
                 description, photo_path, status, published_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                     IF(? = \'available\', NOW(), NULL), NOW(), NOW())',
            [
                $data['created_by'],
                $data['name'],
                $data['animal_type'],
                $data['species_other'] ?? null,
                $data['breed'] ?? null,
                $data['sex'] ?? 'unknown',
                $data['approx_age_months'] ?? null,
                $data['size'] ?? null,
                $data['color'] ?? null,
                isset($data['is_vaccinated']) ? (int)$data['is_vaccinated'] : null,
                isset($data['is_neutered']) ? (int)$data['is_neutered'] : null,
                $data['description'] ?? null,
                $data['photo_path'] ?? null,
                $data['status'] ?? 'draft',
                $data['status'] ?? 'draft',
            ]
        );
        return (int)db_insert_id();
    }

    public static function update(int $id, array $data): void
    {

        $allowed = [
            'name', 'animal_type', 'species_other', 'breed', 'sex',
            'approx_age_months', 'size', 'color', 'is_vaccinated',
            'is_neutered', 'description', 'photo_path', 'status',
        ];
        $sets   = [];
        $params = [];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[]   = "`$col` = ?";
                $params[] = $data[$col];
            }
        }

        if (isset($data['status']) && $data['status'] === 'available') {
            $sets[]   = 'published_at = IFNULL(published_at, NOW())';
        }
        if (empty($sets)) {
            return;
        }
        $params[] = $id;
        db_exec(
            'UPDATE pets SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = ?',
            $params
        );
    }

    public static function find(int $id): ?array
    {
        return db_one('SELECT * FROM pets WHERE id = ? LIMIT 1', [$id]);
    }

    public static function list_public(array $filters, int $page): array
    {
        $where  = ["p.status = 'available'"];
        $params = [];

        if (!empty($filters['animal_type']) && in_array($filters['animal_type'], ['dog', 'cat', 'other'], true)) {
            $where[]  = 'p.animal_type = ?';
            $params[] = $filters['animal_type'];
        }

        $sql = 'SELECT p.* FROM pets p WHERE ' . implode(' AND ', $where)
             . ' ORDER BY p.published_at DESC, p.id DESC';

        return paginate($sql, $params, $page, 12);
    }

    public static function list_admin(array $filters, int $page): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'p.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['animal_type']) && in_array($filters['animal_type'], ['dog', 'cat', 'other'], true)) {
            $where[]  = 'p.animal_type = ?';
            $params[] = $filters['animal_type'];
        }

        $sql = 'SELECT p.* FROM pets p WHERE ' . implode(' AND ', $where)
             . ' ORDER BY p.updated_at DESC, p.id DESC';

        return paginate($sql, $params, $page, 20);
    }

    public static function set_status(int $id, string $status): void
    {
        if ($status === 'available') {
            db_exec(
                "UPDATE pets SET status = ?, published_at = IFNULL(published_at, NOW()), updated_at = NOW() WHERE id = ?",
                [$status, $id]
            );
        } else {
            db_exec(
                'UPDATE pets SET status = ?, updated_at = NOW() WHERE id = ?',
                [$status, $id]
            );
        }
    }
}
