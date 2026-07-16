<?php
/**
 * models/Patient.php
 */
class Patient {
    public function __construct(private PDO $pdo) {}

    public function findByUserId(int $userId): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, u.name, u.email, u.phone, u.avatar, u.active, u.created_at AS joined_at
             FROM patients p JOIN users u ON u.id = p.user_id
             WHERE p.user_id = ? LIMIT 1"
        );
        $stmt->execute([$userId]);
        $res = $stmt->fetch() ?: null;
        if ($res) {
            $res['age'] = !empty($res['birth_date']) ? $this->getAge($res['birth_date']) : 0;
        }
        return $res;
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, u.name, u.email, u.phone, u.avatar, u.active, u.created_at AS joined_at
             FROM patients p JOIN users u ON u.id = p.user_id
             WHERE p.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $res = $stmt->fetch() ?: null;
        if ($res) {
            $res['age'] = !empty($res['birth_date']) ? $this->getAge($res['birth_date']) : 0;
        }
        return $res;
    }

    public function getAll(): array {
        return $this->pdo->query(
            "SELECT p.*, u.name, u.email, u.phone, u.avatar, u.active
             FROM patients p JOIN users u ON u.id = p.user_id
             ORDER BY u.name"
        )->fetchAll();
    }

    public function search(string $term): array {
        $like = '%' . $term . '%';
        $stmt = $this->pdo->prepare(
            "SELECT p.*, u.name, u.email, u.phone, u.avatar
             FROM patients p JOIN users u ON u.id = p.user_id
             WHERE u.name LIKE ? OR u.email LIKE ? OR p.insurance_number LIKE ?
             ORDER BY u.name LIMIT 20"
        );
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }

    public function create(int $userId, array $data): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO patients (user_id,birth_date,blood_type,allergies,emergency_contact_name,
             emergency_contact_phone,insurance_provider,insurance_number)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $userId,
            $data['birth_date']              ?? null,
            $data['blood_type']              ?? null,
            $data['allergies']               ?? null,
            $data['emergency_contact_name']  ?? null,
            $data['emergency_contact_phone'] ?? null,
            $data['insurance_provider']      ?? null,
            $data['insurance_number']        ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $allowed = ['birth_date','blood_type','allergies','emergency_contact_name',
                    'emergency_contact_phone','insurance_provider','insurance_number'];
        $fields = $values = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (empty($fields)) return false;
        $values[] = $id;
        $stmt = $this->pdo->prepare("UPDATE patients SET " . implode(',', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }

    public function countTotal(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    }

    public function getAge(string $birthDate): int {
        return (int)date_diff(date_create($birthDate), date_create('today'))->y;
    }
}
