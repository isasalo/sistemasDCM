<?php
/**
 * models/Hospital.php
 */
class Hospital {
    public function __construct(private PDO $pdo) {}

    /**
     * Get all hospitals
     */
    public function getAll(bool $onlyActive = true): array {
        $sql = "SELECT * FROM hospitals";
        if ($onlyActive) {
            $sql .= " WHERE active = 1";
        }
        $sql .= " ORDER BY name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a hospital by ID
     */
    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM hospitals WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Create a new hospital
     */
    public function create(array $data): int {
        $sql = "INSERT INTO hospitals (name, address, city, phone) VALUES (:name, :address, :city, :phone)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name'    => $data['name'],
            'address' => $data['address'] ?? null,
            'city'    => $data['city'] ?? 'Medellín',
            'phone'   => $data['phone'] ?? null
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update an existing hospital
     */
    public function update(int $id, array $data): bool {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
        }
        $sql = "UPDATE hospitals SET " . implode(', ', $fields) . " WHERE id = :id";
        $data['id'] = $id;
        return $this->pdo->prepare($sql)->execute($data);
    }

    /**
     * Toggle hospital active status
     */
    public function toggleActive(int $id): bool {
        $stmt = $this->pdo->prepare("UPDATE hospitals SET active = NOT active WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Delete a hospital by ID
     */
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM hospitals WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
