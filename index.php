<?php
/**
 * index.php — Router principal del sistema
 * URL: http://localhost/proyecto hospital/index.php?module=auth&action=login
 */

// Iniciar sesión segura
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => false,   // true en producción con HTTPS
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

// Cargar configuración
require_once __DIR__ . '/config/database.php';

// Aplicar cabeceras de seguridad
securityHeaders();

// Obtener módulo y acción
$module = trim($_GET['module'] ?? '');
$action = trim($_GET['action'] ?? '');

// Si no hay módulo, mostrar la landing page (home.php)
if (empty($module)) {
    require_once __DIR__ . '/home.php';
    exit;
}

// Sanitizar: solo letras, números y guión bajo
$module = preg_replace('/[^a-z_]/', '', strtolower($module));
$action = preg_replace('/[^a-z_]/', '', strtolower($action));

// Mapa de módulos → controladores (y rol requerido)
$controllerMap = [
    'auth'    => ['file' => 'AuthController',    'role' => null],
    'patient' => ['file' => 'PatientController', 'role' => 'patient'],
    'doctor'  => ['file' => 'DoctorController',  'role' => 'doctor'],
    'admin'   => ['file' => 'AdminController',   'role' => 'admin'],
];

// Módulo inválido → login
if (!isset($controllerMap[$module])) {
    header('Location: index.php?module=auth&action=login');
    exit;
}

$requiredRole = $controllerMap[$module]['role'];
$isLoggedIn   = !empty($_SESSION['user_id']);
$userRole     = $_SESSION['user_role'] ?? null;

// Usuario no autenticado intentando acceder a módulo protegido
if ($requiredRole !== null && !$isLoggedIn) {
    redirect('index.php?module=auth&action=login', 'Debes iniciar sesión primero.', 'warning');
}

// Usuario autenticado intenta acceder a módulo de otro rol
if ($requiredRole !== null && $isLoggedIn && $userRole !== $requiredRole) {
    redirect("index.php?module={$userRole}&action=dashboard");
}

// Usuario ya autenticado intenta ir a auth → redirigir a su dashboard
if ($module === 'auth' && $isLoggedIn && in_array($action, ['login', 'register'])) {
    redirect("index.php?module={$userRole}&action=dashboard");
}

// Cargar y ejecutar controlador
$controllerFile = __DIR__ . '/controllers/' . $controllerMap[$module]['file'] . '.php';

if (!file_exists($controllerFile)) {
    http_response_code(404);
    die('<h2>Controlador no encontrado.</h2>');
}

require_once $controllerFile;

$controllerClass = $controllerMap[$module]['file'];
$pdo             = getDB();
$controller      = new $controllerClass($pdo);

// Verificar que el método (acción) existe
if (!method_exists($controller, $action)) {
    redirect("index.php?module={$module}&action=dashboard");
}

// Ejecutar la acción
$controller->{$action}();
