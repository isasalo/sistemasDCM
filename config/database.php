<?php
/**
 * config/database.php
 * Conexión PDO singleton + constantes globales
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'hospital_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Detectar BASE_URL dinámicamente
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
// Siempre apunta a la raíz del proyecto
define('BASE_URL', '/proyecto hospital/');
define('BASE_PATH', __DIR__ . '/../');

// Zona horaria Colombia
date_default_timezone_set('America/Bogota');

/**
 * Retorna la conexión PDO (singleton)
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Mostrar error amigable y detener ejecución
            http_response_code(500);
            die(renderDbError($e->getMessage()));
        }
    }
    return $pdo;
}

/**
 * Respuesta JSON estandarizada para API
 */
function jsonResponse(bool $success, mixed $data = null, string $message = ''): never {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode([
        'success' => $success,
        'data'    => $data,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Redirigir con mensaje flash
 */
function redirect(string $url, string $flash = '', string $type = 'info'): never {
    if ($flash) {
        $_SESSION['flash'] = ['message' => $flash, 'type' => $type];
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Establecer mensaje flash sin redirección
 */
function setFlash(string $message, string $type = 'info'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/**
 * Obtener y limpiar mensaje flash
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Generar token CSRF
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validar token CSRF
 */
function validateCsrf(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Cabeceras de seguridad
 */
function securityHeaders(): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/**
 * Render error de BD amigable
 */
function renderDbError(string $msg): string {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error BD</title>
    <style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#FEF2F2;}
    .box{background:white;padding:40px;border-radius:12px;max-width:500px;text-align:center;border-left:4px solid #E74C3C;}
    h2{color:#C0392B;}p{color:#666;margin-top:12px;font-size:.9rem;}</style></head>
    <body><div class="box"><h2>⚠️ Error de conexión</h2>
    <p>No se pudo conectar a la base de datos.<br>
    Asegúrate de haber ejecutado <strong>install.php</strong> primero.</p>
    <p style="font-size:.75rem;color:#999;margin-top:16px;">' . htmlspecialchars($msg) . '</p>
    <a href="install.php" style="display:inline-block;margin-top:20px;background:#0A6EBD;color:white;padding:10px 24px;border-radius:8px;text-decoration:none;">Ir a instalación</a>
    </div></body></html>';
}
