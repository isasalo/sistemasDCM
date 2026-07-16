<?php
/**
 * models/User.php
 */
class User {
    public function __construct(private PDO $pdo) {}

    public function findByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND active = 1 LIMIT 1");
        $stmt->execute([strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT id,name,email,role,phone,avatar,active,created_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name,email,password,role,phone) VALUES (?,?,?,?,?)"
        );
        $stmt->execute([
            trim($data['name']),
            strtolower(trim($data['email'])),
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['role'],
            $data['phone'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $values = [];
        foreach (['name','email','phone','avatar','active'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (empty($fields)) return false;
        $values[] = $id;
        $stmt = $this->pdo->prepare("UPDATE users SET " . implode(',', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }

    public function updatePassword(int $id, string $newPassword): bool {
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([password_hash($newPassword, PASSWORD_BCRYPT), $id]);
    }

    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    // Rate limiting: registrar intento de login
    public function recordLoginAttempt(string $ip, string $email): void {
        $stmt = $this->pdo->prepare("INSERT INTO login_attempts (ip_address, email) VALUES (?,?)");
        $stmt->execute([$ip, $email]);
    }

    // Rate limiting: contar intentos últimos 15 minutos
    public function countRecentAttempts(string $ip): int {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
        );
        $stmt->execute([$ip]);
        return (int)$stmt->fetchColumn();
    }

    // Notificaciones del usuario
    public function getNotifications(int $userId, bool $unreadOnly = false): array {
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        if ($unreadOnly) $sql .= " AND read_at IS NULL";
        $sql .= " ORDER BY created_at DESC LIMIT 50";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function countUnreadNotifications(int $userId): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function markNotificationsRead(int $userId): void {
        $stmt = $this->pdo->prepare("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL");
        $stmt->execute([$userId]);
    }

    public function createNotification(int $userId, string $title, string $message, string $type = 'system'): void {
        $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id,title,message,type) VALUES (?,?,?,?)");
        $stmt->execute([$userId, $title, $message, $type]);
    }
}
