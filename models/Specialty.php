<?php
/**
 * models/Specialty.php
 * Modelo para gestionar las especialidades médicas
 */
class Specialty {
    public function __construct(private PDO $pdo) {}

    /**
     * Obtener todas las especialidades
     */
    public function getAll(): array {
        $stmt = $this->pdo->prepare("SELECT * FROM specialties ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar una especialidad por ID
     */
    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM specialties WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Crear una nueva especialidad
     */
    public function create(array $data): int {
        $sql = "INSERT INTO specialties (name, description, icon) VALUES (:name, :description, :icon)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'icon'        => $data['icon'] ?? 'fa-stethoscope'
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Actualizar una especialidad existente
     */
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [];
        
        foreach (['name', 'description', 'icon'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE specialties SET " . implode(', ', $fields) . " WHERE id = :id";
        $params['id'] = $id;
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Eliminar una especialidad
     * Lanza excepción si está asociada a algún doctor para mantener la integridad referencial.
     */
    public function delete(int $id): bool {
        // Verificar si la especialidad tiene doctores asociados
        $stmtCheck = $this->pdo->prepare("SELECT COUNT(*) FROM doctors WHERE specialty_id = ?");
        $stmtCheck->execute([$id]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar la especialidad porque tiene doctores asignados.");
        }

        $stmt = $this->pdo->prepare("DELETE FROM specialties WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
