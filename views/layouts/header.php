<?php
/**
 * views/layouts/header.php
 * Variables esperadas: $pageTitle, $notifCount, $_SESSION['user_role']
 */
$role     = $_SESSION['user_role'] ?? 'patient';
$userName = $_SESSION['user_name']  ?? 'Usuario';
$userAvatar = $_SESSION['user_avatar'] ?? null;
$module   = $_GET['module'] ?? $role;
$action   = $_GET['action'] ?? 'dashboard';

// Nav items por rol
$navItems = [
    'patient' => [
        ['url' => 'index.php?module=patient&action=dashboard',     'icon' => 'fa-house',          'label' => 'Dashboard'],
        ['url' => 'index.php?module=patient&action=appointments',   'icon' => 'fa-calendar-check', 'label' => 'Mis Citas'],
        ['url' => 'index.php?module=patient&action=payments',       'icon' => 'fa-credit-card',    'label' => 'Pagos'],
        ['url' => 'index.php?module=patient&action=health',         'icon' => 'fa-heart-pulse',    'label' => 'Mi Salud'],
    ],
    'doctor' => [
        ['url' => 'index.php?module=doctor&action=dashboard',      'icon' => 'fa-chart-pie',      'label' => 'Dashboard'],
        ['url' => 'index.php?module=doctor&action=appointments',   'icon' => 'fa-calendar-check', 'label' => 'Citas Pendientes'],
        ['url' => 'index.php?module=doctor&action=schedule',       'icon' => 'fa-calendar-week',  'label' => 'Mi Agenda'],
        ['url' => 'index.php?module=doctor&action=patients',        'icon' => 'fa-users',          'label' => 'Pacientes'],
        ['url' => 'index.php?module=doctor&action=records',         'icon' => 'fa-file-medical',   'label' => 'Historial Clínico'],
        ['url' => 'index.php?module=doctor&action=urgencies',       'icon' => 'fa-truck-medical',  'label' => 'Urgencias'],
    ],
    'admin' => [
        ['url' => 'index.php?module=admin&action=dashboard',       'icon' => 'fa-chart-pie',      'label' => 'Panel de Control'],
        ['url' => 'index.php?module=admin&action=doctors',          'icon' => 'fa-user-doctor',    'label' => 'Doctores'],
        ['url' => 'index.php?module=admin&action=patients',         'icon' => 'fa-user-injured',   'label' => 'Pacientes'],
        ['url' => 'index.php?module=admin&action=schedules',        'icon' => 'fa-calendar-days',  'label' => 'Horarios'],
        ['url' => 'index.php?module=admin&action=hospitals',        'icon' => 'fa-hospital',       'label' => 'Hospitales'],
        ['url' => 'index.php?module=admin&action=specialties',      'icon' => 'fa-stethoscope',    'label' => 'Especialidades'],
        ['url' => 'index.php?module=admin&action=finances',         'icon' => 'fa-dollar-sign',    'label' => 'Finanzas'],
    ],
];

$roleColors = ['patient' => 'var(--patient-color)', 'doctor' => 'var(--doctor-color)', 'admin' => 'var(--admin-color)'];
$roleLabels = ['patient' => 'Paciente', 'doctor' => 'Doctor', 'admin' => 'Administrador'];
$currentUrl = 'index.php?module=' . $module . '&action=' . $action;
$flash      = $flash ?? getFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">
    <title><?= htmlspecialchars($pageTitle ?? 'Tu Salud Primero') ?> — Tu Salud Primero</title>
    <meta name="description" content="Sistema de gestión hospitalaria Tu Salud Primero">
    <link rel="stylesheet" href="/proyecto hospital/assets/css/main.css">
    <link rel="stylesheet" href="/proyecto hospital/assets/css/dashboard.css">
    <link rel="stylesheet" href="/proyecto hospital/assets/css/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body data-role="<?= $role ?>" data-user-id="<?= $_SESSION['user_id'] ?? '' ?>">

<div class="app-layout">

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay"></div>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="fa-solid fa-heart-pulse"></i></div>
        <div>
            <div class="logo-text">Tu Salud Primero</div>
            <div class="logo-sub">Sistema Hospitalario</div>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="avatar">
            <?php if ($userAvatar): ?>
                <img src="<?= htmlspecialchars($userAvatar) ?>" alt="<?= htmlspecialchars($userName) ?>">
            <?php else: ?>
                <?= mb_strtoupper(mb_substr($userName, 0, 1)) ?>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($userName) ?></div>
            <div class="user-role"><?= $roleLabels[$role] ?? $role ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Menú principal</div>
        <?php foreach ($navItems[$role] ?? [] as $item): ?>
            <a href="<?= $item['url'] ?>" class="<?= ($currentUrl === $item['url'] || strpos($currentUrl, explode('&action=', $item['url'])[0]) !== false && strpos($item['url'], 'action=' . $action) !== false) ? 'active' : '' ?>">
                <i class="fa-solid <?= $item['icon'] ?>"></i>
                <?= htmlspecialchars($item['label']) ?>
                <?php if ($item['icon'] === 'fa-truck-medical' && $role === 'doctor'): ?>
                    <span id="urgency-badge" class="badge-count" style="margin-left:auto;background:#E74C3C;color:white;min-width:18px;height:18px;border-radius:9px;display:none;align-items:center;justify-content:center;font-size:.65rem;font-weight:700">0</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="index.php?module=auth&action=logout">
            <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
        </a>
    </div>
</aside>

<!-- Main wrapper -->
<div class="main-wrapper">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="mobile-menu-btn" aria-label="Abrir menú">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="page-breadcrumb">
                Tu Salud Primero / <span><?= htmlspecialchars($pageTitle ?? '') ?></span>
            </div>
        </div>
        <div class="topbar-right">
            <a href="index.php?module=<?= $role ?>&action=dashboard" class="topbar-icon-btn" style="position:relative" title="Notificaciones">
                <i class="fa-regular fa-bell"></i>
                <?php if (!empty($notifCount) && $notifCount > 0): ?>
                <span class="badge-count notif-badge"><?= $notifCount ?></span>
                <?php else: ?>
                <span class="badge-count notif-badge" style="display:none">0</span>
                <?php endif; ?>
            </a>
            <div class="topbar-divider"></div>
            <div class="topbar-user" title="<?= htmlspecialchars($userName) ?>">
                <div class="user-avatar">
                    <?php if ($userAvatar): ?>
                        <img src="<?= htmlspecialchars($userAvatar) ?>" alt="">
                    <?php else: ?>
                        <?= mb_strtoupper(mb_substr($userName, 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <span class="user-name"><?= htmlspecialchars(explode(' ', $userName)[0]) ?></span>
                <i class="fa-solid fa-chevron-down" style="font-size:.7rem;color:#94A3B8"></i>
            </div>
            <a href="index.php?module=auth&action=logout" class="topbar-icon-btn" title="Cerrar sesión">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </header>

    <!-- Page content -->
    <main class="page-content">

    <?php if ($flash): ?>
    <div class="flash-bar <?= htmlspecialchars($flash['type']) ?>">
        <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : ($flash['type'] === 'error' ? 'fa-circle-xmark' : 'fa-circle-info') ?>"></i>
        <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>
