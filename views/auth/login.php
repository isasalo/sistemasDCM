<?php
/**
 * views/auth/login.php
 */
$flash = $flash ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Tu Salud Primero</title>
    <meta name="description" content="Accede al sistema hospitalario Tu Salud Primero">
    <link rel="stylesheet" href="/proyecto hospital/assets/css/main.css">
    <link rel="stylesheet" href="/proyecto hospital/assets/css/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <!-- Header -->
        <div class="login-card-top">
            <div class="login-logo">
                <div class="login-logo-icon">
                    <i class="fa-solid fa-heart-pulse"></i>
                </div>
                <span class="login-logo-text">Tu Salud Primero</span>
            </div>
            <p class="login-subtitle">Sistema de Gestión Hospitalaria</p>
        </div>

        <!-- Body -->
        <div class="login-card-body">
            <h1 style="font-size:1.3rem;font-weight:700;color:var(--gray-900);margin-bottom:4px">Bienvenido de nuevo</h1>
            <p style="font-size:.85rem;color:var(--gray-600);margin-bottom:24px">Ingresa tus credenciales para acceder</p>

            <?php if ($flash): ?>
            <div class="flash-bar <?= htmlspecialchars($flash['type']) ?>" style="margin-bottom:16px">
                <i class="fa-solid <?= $flash['type']==='error' ? 'fa-circle-xmark' : 'fa-circle-info' ?>"></i>
                <?= htmlspecialchars($flash['message']) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="index.php?module=auth&action=login" class="login-form" id="login-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">

                <!-- Email -->
                <div class="form-group">
                    <i class="input-icon fa-solid fa-envelope"></i>
                    <input type="email" name="email" id="email" class="form-control"
                        placeholder="correo@ejemplo.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required autocomplete="email" autofocus
                        aria-label="Correo electrónico">
                </div>

                <!-- Password -->
                <div class="form-group">
                    <i class="input-icon fa-solid fa-lock"></i>
                    <input type="password" name="password" id="password" class="form-control"
                        placeholder="Contraseña" required autocomplete="current-password"
                        aria-label="Contraseña">
                    <i class="input-toggle fa-solid fa-eye" id="toggle-pw" role="button" aria-label="Mostrar contraseña"></i>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="login-btn">
                <i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión
            </button>

            <div style="text-align:center;margin-top:20px;font-size:.9rem;color:#64748B">
                ¿No tienes una cuenta? <a href="index.php?module=auth&action=register" style="color:#0A6EBD;font-weight:600;text-decoration:none">Regístrate aquí</a>
            </div>
            </form>

            <!-- Demo role chips -->
            <div style="margin-top:24px;text-align:center">
                <p style="font-size:.75rem;color:var(--gray-600);margin-bottom:8px">Acceso rápido (demo)</p>
                <div class="login-roles">
                    <button class="role-chip patient" onclick="fillCredentials('carlos.garcia@email.com','paciente123')">
                        <i class="fa-solid fa-user"></i> Paciente
                    </button>
                    <button class="role-chip doctor" onclick="fillCredentials('samuel.goe@hospital.com','doctor123')">
                        <i class="fa-solid fa-user-doctor"></i> Doctor
                    </button>
                    <button class="role-chip admin" onclick="fillCredentials('admin@hospital.com','admin123')">
                        <i class="fa-solid fa-shield-halved"></i> Admin
                    </button>
                </div>
            </div>

            <div class="login-footer">
                <a href="/" style="color:var(--gray-600)"><i class="fa-solid fa-arrow-left"></i> Volver al inicio</a>
            </div>
        </div>
    </div>
</div>

<div class="toast-container" id="toast-container"></div>
<script src="/proyecto hospital/assets/js/main.js"></script>
<script>
function fillCredentials(email, pass) {
    document.getElementById('email').value    = email;
    document.getElementById('password').value = pass;
    showNotification('Credenciales cargadas. Haz clic en Iniciar Sesión.', 'info');
}
document.getElementById('toggle-pw')?.addEventListener('click', function() {
    const pw = document.getElementById('password');
    const isText = pw.type === 'text';
    pw.type = isText ? 'password' : 'text';
    this.classList.toggle('fa-eye', isText);
    this.classList.toggle('fa-eye-slash', !isText);
});
document.getElementById('login-form')?.addEventListener('submit', function() {
    const btn = document.getElementById('login-btn');
    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner"></span> Verificando...';
});
</script>
</body>
</html>
