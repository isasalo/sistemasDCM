<?php
/**
 * models/Appointment.php
 */
class Appointment {
    public function __construct(private PDO $pdo) {}

    public function getByPatient(int $patientId, ?string $status = null): array {
        $sql = "SELECT a.*, d.id AS doctor_id,
                       u.name AS doctor_name, s.name AS specialty_name, s.icon AS specialty_icon,
                       h.name AS hospital_name
                FROM appointments a
                JOIN doctors d ON d.id = a.doctor_id
                JOIN users u ON u.id = d.user_id
                JOIN specialties s ON s.id = d.specialty_id
                LEFT JOIN hospitals h ON h.id = a.hospital_id
                WHERE a.patient_id = ?";
        $params = [$patientId];
        if ($status) { $sql .= " AND a.status = ?"; $params[] = $status; }
        $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getByDoctor(int $doctorId, ?string $date = null): array {
        $sql = "SELECT a.*, p.id AS patient_row_id,
                       u.name AS patient_name, u.phone AS patient_phone
                FROM appointments a
                JOIN patients p ON p.id = a.patient_id
                JOIN users u ON u.id = p.user_id
                WHERE a.doctor_id = ?";
        $params = [$doctorId];
        if ($date) { $sql .= " AND a.appointment_date = ?"; $params[] = $date; }
        $sql .= " ORDER BY a.appointment_date, a.appointment_time";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT a.*, p.user_id AS patient_user_id,
                    u_p.name AS patient_name, u_p.phone AS patient_phone, u_p.email AS patient_email,
                    u_d.name AS doctor_name,
                    s.name AS specialty_name, s.icon AS specialty_icon,
                    d.consultation_fee,
                    h.name AS hospital_name, h.address AS hospital_address
             FROM appointments a
             JOIN patients p ON p.id = a.patient_id
             JOIN users u_p ON u_p.id = p.user_id
             JOIN doctors d ON d.id = a.doctor_id
             JOIN users u_d ON u_d.id = d.user_id
             JOIN specialties s ON s.id = d.specialty_id
             LEFT JOIN hospitals h ON h.id = a.hospital_id
             WHERE a.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO appointments (patient_id,doctor_id,hospital_id,appointment_date,appointment_time,reason,status,type)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $data['patient_id'],
            $data['doctor_id'],
            $data['hospital_id'] ?? null,
            $data['appointment_date'],
            $data['appointment_time'],
            $data['reason']  ?? null,
            $data['status']  ?? 'pending',
            $data['type']    ?? 'consultation',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status, array $extra = []): bool {
        $sql = "UPDATE appointments SET status = ?, updated_at = NOW()";
        $params = [$status];
        if (isset($extra['cancelled_by']))       { $sql .= ", cancelled_by = ?";       $params[] = $extra['cancelled_by']; }
        if (isset($extra['cancellation_reason'])){ $sql .= ", cancellation_reason = ?"; $params[] = $extra['cancellation_reason']; }
        if (isset($extra['notes']))              { $sql .= ", notes = ?";               $params[] = $extra['notes']; }
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function reschedule(int $id, string $date, string $time): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE appointments SET appointment_date=?, appointment_time=?, status='pending', updated_at=NOW() WHERE id=?"
        );
        return $stmt->execute([$date, $time, $id]);
    }

    /** Devuelve los slots ya ocupados para un doctor en una fecha */
    public function getBookedSlots(int $doctorId, string $date): array {
        $stmt = $this->pdo->prepare(
            "SELECT appointment_time FROM appointments
             WHERE doctor_id = ? AND appointment_date = ?
             AND status NOT IN ('cancelled','no_show')"
        );
        $stmt->execute([$doctorId, $date]);
        return array_column($stmt->fetchAll(), 'appointment_time');
    }

    public function getUpcoming(int $patientId, int $limit = 3): array {
        $stmt = $this->pdo->prepare(
            "SELECT a.*, u.name AS doctor_name, s.name AS specialty_name, s.icon AS specialty_icon
             FROM appointments a
             JOIN doctors d ON d.id = a.doctor_id
             JOIN users u ON u.id = d.user_id
             JOIN specialties s ON s.id = d.specialty_id
             WHERE a.patient_id = ?
               AND (a.appointment_date > CURDATE()
                    OR (a.appointment_date = CURDATE() AND a.appointment_time >= CURTIME()))
               AND a.status IN ('pending','confirmed')
             ORDER BY a.appointment_date, a.appointment_time
             LIMIT ?"
        );
        $stmt->execute([$patientId, $limit]);
        return $stmt->fetchAll();
    }

    public function countByStatus(): array {
        $stmt = $this->pdo->query(
            "SELECT status, COUNT(*) AS total FROM appointments GROUP BY status"
        );
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = (int)$row['total'];
        }
        return $result;
    }

    public function getWeeklyForDoctor(int $doctorId, string $weekStart): array {
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
        $stmt = $this->pdo->prepare(
            "SELECT a.*, u.name AS patient_name
             FROM appointments a
             JOIN patients p ON p.id = a.patient_id
             JOIN users u ON u.id = p.user_id
             WHERE a.doctor_id = ?
               AND a.appointment_date BETWEEN ? AND ?
             ORDER BY a.appointment_date, a.appointment_time"
        );
        $stmt->execute([$doctorId, $weekStart, $weekEnd]);
        return $stmt->fetchAll();
    }
    public function getAvailableSlots(int $doctorId, string $date): array {
        $dayOfWeek = date('w', strtotime($date));
        
        $stmt = $this->pdo->prepare("SELECT * FROM schedules WHERE doctor_id = ? AND day_of_week = ? AND active = 1 LIMIT 1");
        $stmt->execute([$doctorId, $dayOfWeek]);
        $schedule = $stmt->fetch();
        
        if (!$schedule) return [];

        $slots = [];
        $start = strtotime($schedule['start_time']);
        $end   = strtotime($schedule['end_time']);
        $gap   = (int)($schedule['slot_duration'] ?? 30) * 60;

        $current = $start;
        while ($current < $end) {
            $slots[] = date('H:i:s', $current);
            $current += $gap;
        }

        return $slots;
    }
}
