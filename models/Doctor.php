<?php
/**
 * models/Doctor.php
 */
class Doctor {
    public function __construct(private PDO $pdo) {}

    public function findByUserId(int $userId): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT d.*, u.name, u.email, u.phone, u.avatar, u.active, s.name AS specialty_name, s.icon AS specialty_icon
             FROM doctors d
             JOIN users u ON u.id = d.user_id
             JOIN specialties s ON s.id = d.specialty_id
             WHERE d.user_id = ? LIMIT 1"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT d.*, u.name, u.email, u.phone, u.avatar, u.active,
                    s.name AS specialty_name, s.icon AS specialty_icon
             FROM doctors d
             JOIN users u ON u.id = d.user_id
             JOIN specialties s ON s.id = d.specialty_id
             WHERE d.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getAll(bool $activeOnly = true): array {
        $sql = "SELECT d.*, u.name, u.email, u.phone, u.avatar, u.active,
                       s.name AS specialty_name, s.icon AS specialty_icon
                FROM doctors d
                JOIN users u ON u.id = d.user_id
                JOIN specialties s ON s.id = d.specialty_id";
        if ($activeOnly) $sql .= " WHERE u.active = 1";
        $sql .= " ORDER BY u.name";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getBySpecialty(int $specialtyId): array {
        $stmt = $this->pdo->prepare(
            "SELECT d.*, u.name, u.email, u.avatar, s.name AS specialty_name
             FROM doctors d
             JOIN users u ON u.id = d.user_id
             JOIN specialties s ON s.id = d.specialty_id
             WHERE d.specialty_id = ? AND u.active = 1
             ORDER BY u.name"
        );
        $stmt->execute([$specialtyId]);
        return $stmt->fetchAll();
    }

    public function create(int $userId, int $specialtyId, string $license, string $bio = '', float $fee = 0): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO doctors (user_id,specialty_id,license_number,bio,consultation_fee) VALUES (?,?,?,?,?)"
        );
        $stmt->execute([$userId, $specialtyId, $license, $bio, $fee]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $values = [];
        foreach (['specialty_id','license_number','bio','consultation_fee'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (empty($fields)) return false;
        $values[] = $id;
        $stmt = $this->pdo->prepare("UPDATE doctors SET " . implode(',', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }

    public function getStats(int $doctorId): array {
        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(*) AS total_appointments,
                SUM(status='completed') AS completed,
                SUM(status='cancelled') AS cancelled,
                SUM(status='pending' OR status='confirmed') AS upcoming
             FROM appointments WHERE doctor_id = ?"
        );
        $stmt->execute([$doctorId]);
        return $stmt->fetch() ?: [];
    }

    public function getTodayAppointments(int $doctorId): array {
        $stmt = $this->pdo->prepare(
            "SELECT a.*, p.id AS patient_id, u.name AS patient_name, u.phone AS patient_phone
             FROM appointments a
             JOIN patients p ON p.id = a.patient_id
             JOIN users u ON u.id = p.user_id
             WHERE a.doctor_id = ? AND a.appointment_date = CURDATE()
             ORDER BY a.appointment_time"
        );
        $stmt->execute([$doctorId]);
        return $stmt->fetchAll();
    }

    public function getAllSpecialties(): array {
        return $this->pdo->query("SELECT * FROM specialties ORDER BY name")->fetchAll();
    }

    /**
     * Retorna doctores que tienen horario configurado para el día de la semana de la fecha dada
     */
    public function getAvailableByDate(string $date, ?int $specialtyId = null): array {
        $dayOfWeek = date('w', strtotime($date)); // 0 (Dom) a 6 (Sáb)
        
        $sql = "SELECT d.id, u.name, u.avatar, s.name AS specialty_name, d.consultation_fee
                FROM doctors d
                JOIN users u ON u.id = d.user_id
                JOIN specialties s ON s.id = d.specialty_id
                JOIN schedules sc ON sc.doctor_id = d.id
                WHERE u.active = 1 
                AND sc.day_of_week = ? 
                AND sc.active = 1";
        
        $params = [$dayOfWeek];
        
        if ($specialtyId) {
            $sql .= " AND d.specialty_id = ?";
            $params[] = $specialtyId;
        }
        
        $sql .= " GROUP BY d.id ORDER BY u.name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
