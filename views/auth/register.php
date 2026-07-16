<?php
/**
 * views/auth/register.php
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Paciente — Tu Salud Primero</title>
    <link rel="stylesheet" href="/proyecto hospital/assets/css/main.css">
    <link rel="stylesheet" href="/proyecto hospital/assets/css/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="login-page">
    <div class="login-card" style="max-width:500px">
        <div class="login-card-top">
            <div class="login-logo">
                <div class="login-logo-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                <span class="login-logo-text">Tu Salud Primero</span>
            </div>
            <p class="login-subtitle">Crea tu cuenta de paciente</p>
        </div>

        <div class="login-card-body">
            <?php if ($flash = getFlash()): ?>
            <div class="flash-bar <?= $flash['type'] ?>" style="margin-bottom:15px">
                <?= $flash['message'] ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="index.php?module=auth&action=register" class="login-form">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                
                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" name="name" class="form-control" required placeholder="Ej: Carlos García">
                </div>

                <div class="form-group">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="email" class="form-control" required placeholder="tu@correo.com">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">DNI / Identificación</label>
                        <input type="text" name="dni" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirmar Contraseña</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="login-btn" style="margin-top:10px">
                    <i class="fa-solid fa-user-plus"></i> Registrarse
                </button>
            </form>

            <div class="login-footer">
                ¿Ya tienes cuenta? <a href="index.php?module=auth&action=login">Inicia sesión aquí</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
