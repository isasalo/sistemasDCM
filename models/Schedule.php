<?php
/**
 * models/Schedule.php
 */
class Schedule {
    public function __construct(private PDO $pdo) {}

    public static array $DAYS = [
        0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes',
        3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
    ];

    public function getByDoctor(int $doctorId): array {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM schedules WHERE doctor_id = ? AND active = 1 ORDER BY day_of_week, start_time"
        );
        $stmt->execute([$doctorId]);
        return $stmt->fetchAll();
    }

    public function getDoctorScheduleForDate(int $doctorId, string $date): ?array {
        $dayOfWeek = (int)date('w', strtotime($date)); // 0=Sun
        $stmt = $this->pdo->prepare(
            "SELECT * FROM schedules WHERE doctor_id = ? AND day_of_week = ? AND active = 1 LIMIT 1"
        );
        $stmt->execute([$doctorId, $dayOfWeek]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Genera todos los slots posibles para un doctor en una fecha
     * @return string[] array de "HH:MM:SS"
     */
    public function generateSlots(int $doctorId, string $date): array {
        $schedule = $this->getDoctorScheduleForDate($doctorId, $date);
        if (!$schedule) return [];

        $slots    = [];
        $current  = strtotime($date . ' ' . $schedule['start_time']);
        $end      = strtotime($date . ' ' . $schedule['end_time']);
        $duration = (int)$schedule['slot_duration'] * 60;

        while ($current + $duration <= $end) {
            $slots[] = date('H:i:s', $current);
            $current += $duration;
        }
        return $slots;
    }

    public function setSchedule(int $doctorId, int $dayOfWeek, string $start, string $end, int $slotDuration = 30): void {
        // Eliminar el existente
        $stmt = $this->pdo->prepare("DELETE FROM schedules WHERE doctor_id = ? AND day_of_week = ?");
        $stmt->execute([$doctorId, $dayOfWeek]);

        // Insertar nuevo
        $stmt = $this->pdo->prepare(
            "INSERT INTO schedules (doctor_id,day_of_week,start_time,end_time,slot_duration) VALUES (?,?,?,?,?)"
        );
        $stmt->execute([$doctorId, $dayOfWeek, $start, $end, $slotDuration]);
    }

    public function deleteDay(int $doctorId, int $dayOfWeek): void {
        $stmt = $this->pdo->prepare("DELETE FROM schedules WHERE doctor_id = ? AND day_of_week = ?");
        $stmt->execute([$doctorId, $dayOfWeek]);
    }

    public function replaceAll(int $doctorId, array $days): void {
        $this->pdo->prepare("DELETE FROM schedules WHERE doctor_id = ?")->execute([$doctorId]);
        $stmt = $this->pdo->prepare(
            "INSERT INTO schedules (doctor_id,day_of_week,start_time,end_time,slot_duration) VALUES (?,?,?,?,?)"
        );
        foreach ($days as $day) {
            $stmt->execute([
                $doctorId,
                (int)$day['day'],
                $day['start'],
                $day['end'],
                (int)($day['slot'] ?? 30),
            ]);
        }
    }

    public function hasDoctorSchedule(int $doctorId, string $date): bool {
        return !empty($this->getDoctorScheduleForDate($doctorId, $date));
    }
}
