<?php
/**
 * models/Payment.php
 */
class Payment {
    public function __construct(private PDO $pdo) {}

    public function getByPatient(int $patientId): array {
        $stmt = $this->pdo->prepare(
            "SELECT py.*, s.name AS service_name
             FROM payments py
             LEFT JOIN services s ON s.id = py.service_id
             WHERE py.patient_id = ?
             ORDER BY py.created_at DESC"
        );
        $stmt->execute([$patientId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT py.*, u.name AS patient_name, s.name AS service_name
             FROM payments py
             JOIN patients p ON p.id = py.patient_id
             JOIN users u ON u.id = p.user_id
             LEFT JOIN services s ON s.id = py.service_id
             WHERE py.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getAll(array $filters = []): array {
        $sql = "SELECT py.*, u.name AS patient_name, s.name AS service_name
                FROM payments py
                JOIN patients p ON p.id = py.patient_id
                JOIN users u ON u.id = p.user_id
                LEFT JOIN services s ON s.id = py.service_id
                WHERE 1=1";
        $params = [];
        if (!empty($filters['status'])) {
            $sql .= " AND py.status = ?"; $params[] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(py.created_at) >= ?"; $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(py.created_at) <= ?"; $params[] = $filters['date_to'];
        }
        $sql .= " ORDER BY py.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $invoiceNumber = 'FAC-' . date('Y') . '-' . str_pad((int)$this->pdo->query("SELECT COUNT(*)+1 FROM payments")->fetchColumn(), 4, '0', STR_PAD_LEFT);
        $stmt = $this->pdo->prepare(
            "INSERT INTO payments
             (patient_id,appointment_id,service_id,description,subtotal,tax_amount,discount,total,payment_method,status,invoice_number)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $data['patient_id'],
            $data['appointment_id'] ?? null,
            $data['service_id']     ?? null,
            $data['description']    ?? null,
            $data['subtotal'],
            $data['tax_amount']     ?? 0,
            $data['discount']       ?? 0,
            $data['total'],
            $data['payment_method'] ?? 'cash',
            $data['status']         ?? 'pending',
            $invoiceNumber,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function markAsPaid(int $id, string $method): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE payments SET status='paid', payment_method=?, paid_at=NOW() WHERE id=?"
        );
        return $stmt->execute([$method, $id]);
    }

    public function getFinancialSummary(): array {
        $stmt = $this->pdo->query(
            "SELECT
                SUM(CASE WHEN DATE(paid_at) = CURDATE() THEN total ELSE 0 END) AS today,
                SUM(CASE WHEN YEARWEEK(paid_at,1) = YEARWEEK(CURDATE(),1) THEN total ELSE 0 END) AS week,
                SUM(CASE WHEN MONTH(paid_at) = MONTH(CURDATE()) AND YEAR(paid_at) = YEAR(CURDATE()) THEN total ELSE 0 END) AS month,
                SUM(CASE WHEN status='pending' THEN total ELSE 0 END) AS pending_total,
                SUM(CASE WHEN status='paid' THEN tax_amount ELSE 0 END) AS tax_collected,
                COUNT(CASE WHEN status='paid' THEN 1 END) AS paid_count
             FROM payments"
        );
        return $stmt->fetch() ?: [];
    }

    public function getAllServices(): array {
        return $this->pdo->query(
            "SELECT s.*, 
                    COALESCE(COUNT(py.id), 0) AS count, 
                    COALESCE(SUM(py.total), 0) AS total_amount
             FROM services s
             LEFT JOIN payments py ON py.service_id = s.id AND py.status = 'paid'
             WHERE s.active = 1
             GROUP BY s.id
             ORDER BY s.name"
        )->fetchAll();
    }

    public function countPendingByPatient(int $patientId): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM payments WHERE patient_id=? AND status='pending'");
        $stmt->execute([$patientId]);
        return (int)$stmt->fetchColumn();
    }
}
