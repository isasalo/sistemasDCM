<?php
/**
 * models/Prescription.php
 */
class Prescription {
    public function __construct(private PDO $pdo) {}

    public function getByPatient(int $patientId, bool $activeOnly = false): array {
        $sql = "SELECT pr.*, u.name AS doctor_name, s.name AS specialty_name
                FROM prescriptions pr
                JOIN doctors d ON d.id = pr.doctor_id
                JOIN users u ON u.id = d.user_id
                JOIN specialties s ON s.id = d.specialty_id
                WHERE pr.patient_id = ?";
        $params = [$patientId];
        if ($activeOnly) { $sql .= " AND pr.status = 'active'"; }
        $sql .= " ORDER BY pr.issue_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        return array_map(fn($r) => array_merge($r, ['medications' => json_decode($r['medications'], true)]), $rows);
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT pr.*, u_d.name AS doctor_name, u_p.name AS patient_name,
                    s.name AS specialty_name, s.icon AS specialty_icon
             FROM prescriptions pr
             JOIN doctors d ON d.id = pr.doctor_id
             JOIN users u_d ON u_d.id = d.user_id
             JOIN patients p ON p.id = pr.patient_id
             JOIN users u_p ON u_p.id = p.user_id
             JOIN specialties s ON s.id = d.specialty_id
             WHERE pr.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) $row['medications'] = json_decode($row['medications'], true);
        return $row ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO prescriptions
             (medical_record_id,doctor_id,patient_id,medications,instructions,issue_date,expiry_date,status)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $data['medical_record_id'],
            $data['doctor_id'],
            $data['patient_id'],
            json_encode($data['medications']),
            $data['instructions'] ?? null,
            $data['issue_date']   ?? date('Y-m-d'),
            $data['expiry_date']  ?? null,
            $data['status']       ?? 'active',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->pdo->prepare("UPDATE prescriptions SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function getByRecord(int $recordId): array {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM prescriptions WHERE medical_record_id = ? ORDER BY issue_date DESC"
        );
        $stmt->execute([$recordId]);
        $rows = $stmt->fetchAll();
        return array_map(fn($r) => array_merge($r, ['medications' => json_decode($r['medications'], true)]), $rows);
    }
}
