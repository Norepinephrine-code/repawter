<?php
declare(strict_types=1);

class AdoptionModel
{
    public static function create(array $data): int
    {
        db_exec(
            'INSERT INTO adoption_applications
                (pet_id, applicant_id, status, message, home_type, has_other_pets, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $data['pet_id'],
                $data['applicant_id'],
                $data['status'] ?? 'submitted',
                $data['message'] ?? null,
                $data['home_type'] ?? null,
                isset($data['has_other_pets']) ? (int)$data['has_other_pets'] : null,
            ]
        );
        return (int)db_insert_id();
    }

    public static function find(int $id): ?array
    {
        return db_one('SELECT * FROM adoption_applications WHERE id = ? LIMIT 1', [$id]);
    }

    public static function find_with_details(int $id): ?array
    {
        return db_one(
            'SELECT aa.*,
                    p.name  AS pet_name,
                    p.photo_path AS pet_photo,
                    p.animal_type AS pet_animal_type,
                    p.breed AS pet_breed,
                    p.sex AS pet_sex,
                    p.approx_age_months AS pet_approx_age_months,
                    p.species_other AS pet_species_other,
                    u.full_name  AS applicant_full_name,
                    u.email      AS applicant_email,
                    u.contact_number AS applicant_contact_number
             FROM   adoption_applications aa
             JOIN   pets p  ON p.id = aa.pet_id
             JOIN   users u ON u.id = aa.applicant_id
             WHERE  aa.id = ?
             LIMIT  1',
            [$id]
        );
    }

    public static function list_by_user(int $userId, int $page): array
    {
        $sql = 'SELECT aa.*, p.name AS pet_name, p.photo_path AS pet_photo
                FROM adoption_applications aa
                JOIN pets p ON p.id = aa.pet_id
                WHERE aa.applicant_id = ?
                ORDER BY aa.created_at DESC';
        return paginate($sql, [$userId], $page, 10);
    }

    public static function list_filtered(array $filters, int $page): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'aa.status = ?';
            $params[] = $filters['status'];
        }

        $sql = 'SELECT aa.*,
                       p.name  AS pet_name,
                       u.full_name AS applicant_name
                FROM adoption_applications aa
                JOIN pets p  ON p.id = aa.pet_id
                JOIN users u ON u.id = aa.applicant_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY aa.created_at DESC';

        return paginate($sql, $params, $page, 20);
    }

    public static function decide(int $id, string $status, int $reviewerId, ?string $notes): void
    {
        db_exec(
            'UPDATE adoption_applications
             SET status = ?, reviewed_by = ?, reviewed_at = NOW(), decision_notes = ?, updated_at = NOW()
             WHERE id = ?',
            [$status, $reviewerId, $notes, $id]
        );
    }

    public static function complete(int $id, int $by): void
    {

        $app = db_one('SELECT pet_id FROM adoption_applications WHERE id = ? LIMIT 1', [$id]);
        if ($app === null) {
            return;
        }
        db_exec(
            'UPDATE adoption_applications
             SET status = \'completed\', completed_at = NOW(), updated_at = NOW()
             WHERE id = ?',
            [$id]
        );

        PetModel::set_status((int)$app['pet_id'], 'adopted');
    }

    public static function create_agreement(int $appId, string $pdfPath, int $by): int
    {
        $app = db_one('SELECT pet_id, applicant_id FROM adoption_applications WHERE id = ? LIMIT 1', [$appId]);
        if ($app === null) {
            throw new \RuntimeException("Application $appId not found.");
        }

        $agreementNumber = 'ADOPT-' . date('Y') . '-' . str_pad((string)$appId, 6, '0', STR_PAD_LEFT);

        db_exec(
            'INSERT INTO adoption_agreements
                (adoption_application_id, pet_id, adopter_id, agreement_number,
                 pdf_path, generated_by, generated_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $appId,
                $app['pet_id'],
                $app['applicant_id'],
                $agreementNumber,
                $pdfPath,
                $by,
            ]
        );
        return (int)db_insert_id();
    }

    public static function find_agreement(int $appId): ?array
    {
        return db_one(
            'SELECT * FROM adoption_agreements WHERE adoption_application_id = ? LIMIT 1',
            [$appId]
        );
    }
}
