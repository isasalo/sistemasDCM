<?php
/**
 * models/Urgency.php — (referenced as Urgency in controllers)
 */
class Urgency {
    public function __construct(private PDO $pdo) {}

    public static array $TRIAGE_COLORS = [
        '1_critico'       => ['label' => 'Crítico',        'color' => '#E74C3C', 'bg' => '#FEE2E2'],
        '2_emergencia'    => ['label' => 'Emergencia',     'color' => '#E67E22', 'bg' => '#FEF3C7'],
        '3_urgente'       => ['label' => 'Urgente',        'color' => '#F39C12', 'bg' => '#FFFBEB'],
        '4_menos_urgente' => ['label' => 'Menos urgente',  'color' => '#27AE60', 'bg' => '#D1FAE5'],
        '5_no_urgente'    => ['label' => 'No urgente',     'color' => '#3498DB', 'bg' => '#DBEAFE'],
    ];

    public function getActive(): array {
        $stmt = $this->pdo->query(
            "SELECT u.*, p.id AS patient_row_id, p.insurance_number AS patient_dni,
                    up.name AS patient_name, up.phone AS patient_phone,
                    ud.name AS doctor_name
             FROM urgencies u
             LEFT JOIN patients p ON p.id = u.patient_id
             LEFT JOIN users up ON up.id = p.user_id
             LEFT JOIN doctors d ON d.id = u.attending_doctor_id
             LEFT JOIN users ud ON ud.id = d.user_id
             WHERE u.status IN ('waiting','in_treatment')
             ORDER BY FIELD(u.triage_level,'1_critico','2_emergencia','3_urgente','4_menos_urgente','5_no_urgente'),
                      u.arrival_time"
        );
        return $stmt->fetchAll();
    }

    public function getAll(?string $status = null): array {
        $sql = "SELECT u.*, p.insurance_number AS patient_dni, up.name AS patient_name, ud.name AS doctor_name
                FROM urgencies u
                LEFT JOIN patients p ON p.id = u.patient_id
                LEFT JOIN users up ON up.id = p.user_id
                LEFT JOIN doctors d ON d.id = u.attending_doctor_id
                LEFT JOIN users ud ON ud.id = d.user_id";
        $params = [];
        if ($status) {
            $sql .= " WHERE u.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY u.arrival_time DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO urgencies (patient_id,triage_level,chief_complaint,status)
             VALUES (?,?,?,?)"
        );
        $stmt->execute([
            $data['patient_id']    ?? null,
            $data['triage_level'],
            $data['chief_complaint'],
            $data['status']        ?? 'waiting',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function attend(int $id, int $doctorId): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE urgencies SET status='in_treatment', attending_doctor_id=?, attended_at=NOW() WHERE id=?"
        );
        return $stmt->execute([$doctorId, $id]);
    }

    public function discharge(int $id, string $notes = ''): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE urgencies SET status='discharged', discharge_at=NOW(), notes=? WHERE id=?"
        );
        return $stmt->execute([$notes, $id]);
    }

    public function countActive(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM urgencies WHERE status IN ('waiting','in_treatment')")->fetchColumn();
    }

    public function countWaiting(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM urgencies WHERE status='waiting'")->fetchColumn();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM urgencies WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE urgencies 
             SET patient_id = ?, triage_level = ?, chief_complaint = ?, status = ?, notes = ? 
             WHERE id = ?"
        );
        return $stmt->execute([
            $data['patient_id'],
            $data['triage_level'],
            $data['chief_complaint'],
            $data['status'],
            $data['notes'] ?? null,
            $id
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM urgencies WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
