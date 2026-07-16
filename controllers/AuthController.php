<?php
/**
 * controllers/AuthController.php
 */
require_once BASE_PATH . 'models/User.php';
require_once BASE_PATH . 'models/Patient.php';

class AuthController {
    private User $userModel;
    private Patient $patientModel;

    public function __construct(private PDO $pdo) {
        $this->userModel = new User($pdo);
        $this->patientModel = new Patient($pdo);
    }

    public function login(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirectByRole($_SESSION['user_role']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validateCsrf()) {
                setFlash('Token de seguridad inválido.', 'error');
                require BASE_PATH . 'views/auth/login.php';
                return;
            }

            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';

            $user = $this->userModel->findByEmail($email);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_avatar'] = $user['avatar'];
                
                setFlash("Bienvenido de nuevo, {$user['name']}.", 'success');
                $this->redirectByRole($user['role']);
            } else {
                setFlash('Credenciales incorrectas.', 'error');
            }
        }

        require BASE_PATH . 'views/auth/login.php';
    }

    public function register(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirectByRole($_SESSION['user_role']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validateCsrf()) {
                setFlash('Token inválido.', 'error');
            } else {
                $name = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES);
                $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
                $pass = $_POST['password'] ?? '';
                $conf = $_POST['confirm_password'] ?? '';
                $dni = $_POST['dni'] ?? '';
                $phone = $_POST['phone'] ?? '';

                if ($pass !== $conf) {
                    setFlash('Las contraseñas no coinciden.', 'error');
                } elseif ($this->userModel->findByEmail($email)) {
                    setFlash('El correo ya está registrado.', 'error');
                } else {
                    $userId = $this->userModel->create([
                        'name' => $name,
                        'email' => $email,
                        'password' => $pass,
                        'role' => 'patient',
                        'phone' => $phone
                    ]);

                    if ($userId) {
                        $this->patientModel->create($userId, ['insurance_number' => $dni]);
                        
                        // Iniciar sesión automáticamente para el paciente
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['user_role'] = 'patient';
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_avatar'] = null;
                        
                        setFlash("¡Registro exitoso! Bienvenido a tu portal de salud, {$name}.", 'success');
                        $this->redirectByRole('patient');
                    }
                }
            }
        }

        require BASE_PATH . 'views/auth/register.php';
    }

    public function logout(): void {
        session_destroy();
        header('Location: index.php?module=auth&action=login');
        exit;
    }

    private function redirectByRole(string $role): void {
        header("Location: index.php?module=$role&action=dashboard");
        exit;
    }
}
