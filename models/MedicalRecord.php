<?php
/**
 * models/MedicalRecord.php
 */
class MedicalRecord {
    public function __construct(private PDO $pdo) {}

    public function getByPatient(int $patientId): array {
        $stmt = $this->pdo->prepare(
            "SELECT mr.*, u.name AS doctor_name, s.name AS specialty_name
             FROM medical_records mr
             JOIN doctors d ON d.id = mr.doctor_id
             JOIN users u ON u.id = d.user_id
             JOIN specialties s ON s.id = d.specialty_id
             WHERE mr.patient_id = ?
             ORDER BY mr.created_at DESC"
        );
        $stmt->execute([$patientId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT mr.*, u_d.name AS doctor_name, u_p.name AS patient_name,
                    s.name AS specialty_name
             FROM medical_records mr
             JOIN doctors d ON d.id = mr.doctor_id
             JOIN users u_d ON u_d.id = d.user_id
             JOIN patients p ON p.id = mr.patient_id
             JOIN users u_p ON u_p.id = p.user_id
             JOIN specialties s ON s.id = d.specialty_id
             WHERE mr.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        if ($record && $record['vital_signs']) {
            $record['vital_signs'] = json_decode($record['vital_signs'], true);
        }
        return $record ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO medical_records
             (patient_id,doctor_id,appointment_id,record_type,diagnosis,treatment,observations,vital_signs,attachments)
             VALUES (?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $data['patient_id'],
            $data['doctor_id'],
            $data['appointment_id'] ?? null,
            $data['record_type']    ?? 'consultation',
            $data['diagnosis']      ?? null,
            $data['treatment']      ?? null,
            $data['observations']   ?? null,
            isset($data['vital_signs']) ? json_encode($data['vital_signs']) : null,
            isset($data['attachments']) ? json_encode($data['attachments']) : null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $allowed = ['diagnosis','treatment','observations','vital_signs','attachments'];
        $fields = $values = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = in_array($f, ['vital_signs','attachments']) ? json_encode($data[$f]) : $data[$f];
            }
        }
        if (empty($fields)) return false;
        $values[] = $id;
        $stmt = $this->pdo->prepare("UPDATE medical_records SET " . implode(',', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }

    /** Historial de signos vitales para gráficos Canvas */
    public function getVitalHistory(int $patientId, int $limit = 10): array {
        $stmt = $this->pdo->prepare(
            "SELECT vital_signs, created_at
             FROM medical_records
             WHERE patient_id = ? AND vital_signs IS NOT NULL
             ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$patientId, $limit]);
        $rows = $stmt->fetchAll();
        return array_map(function($r) {
            $r['vital_signs'] = json_decode($r['vital_signs'], true);
            return $r;
        }, array_reverse($rows));
    }

    public function countByDoctor(int $doctorId): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM medical_records WHERE doctor_id = ?");
        $stmt->execute([$doctorId]);
        return (int)$stmt->fetchColumn();
    }
}
