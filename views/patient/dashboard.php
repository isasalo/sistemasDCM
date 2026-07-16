<?php
/**
 * views/patient/dashboard.php
 * Variables: $patient, $upcoming, $pendingPayments, $totalAppts, $activeRx
 */
$months_es = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
?>
<div class="page-header">
    <div>
        <h1>¡Hola, <?= htmlspecialchars(explode(' ', $patient['name'] ?? 'Paciente')[0]) ?>! 👋</h1>
        <p>Aquí está el resumen de tu salud hoy, <?= date('d \d\e F \d\e Y') ?></p>
    </div>
    <a href="index.php?module=patient&action=appointments" class="btn btn-primary">
        <i class="fa-solid fa-calendar-plus"></i> Nueva Cita
    </a>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="icon"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="info">
            <div class="value"><?= count($upcoming) ?></div>
            <div class="label">Citas próximas</div>
        </div>
    </div>
    <div class="stat-card warning">
        <div class="icon"><i class="fa-solid fa-clock"></i></div>
        <div class="info">
            <div class="value"><?= $totalAppts ?></div>
            <div class="label">Total de citas</div>
        </div>
    </div>
    <div class="stat-card danger">
        <div class="icon"><i class="fa-solid fa-credit-card"></i></div>
        <div class="info">
            <div class="value"><?= $pendingPayments ?></div>
            <div class="label">Pagos pendientes</div>
        </div>
    </div>
    <div class="stat-card success">
        <div class="icon"><i class="fa-solid fa-pills"></i></div>
        <div class="info">
            <div class="value"><?= $activeRx ?></div>
            <div class="label">Recetas activas</div>
        </div>
    </div>
</div>

<div class="grid-3-1" style="margin-top:0">
    <!-- Upcoming appointments -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-calendar-check" style="color:var(--primary);margin-right:8px"></i>Próximas Citas</h3>
                <a href="index.php?module=patient&action=appointments" class="btn btn-outline btn-sm">Ver todas</a>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming)): ?>
                <div class="empty-state">
                    <i class="fa-regular fa-calendar"></i>
                    <p>No tienes citas próximas.<br>
                    <a href="index.php?module=patient&action=appointments" style="color:var(--primary);font-weight:600">Agenda una ahora</a></p>
                </div>
                <?php else: ?>
                <?php foreach ($upcoming as $appt): ?>
                <?php
                    $dateParts = explode('-', $appt['appointment_date']);
                    $timeStr   = substr($appt['appointment_time'], 0, 5);
                ?>
                <div class="appt-card">
                    <div class="appt-date-block">
                        <div class="day"><?= $dateParts[2] ?></div>
                        <div class="month"><?= $months_es[(int)$dateParts[1]] ?></div>
                    </div>
                    <div class="appt-info">
                        <div class="appt-doctor">Dr. <?= htmlspecialchars($appt['doctor_name']) ?></div>
                        <div class="appt-specialty"><?= htmlspecialchars($appt['specialty_name']) ?></div>
                        <div style="font-size:0.75rem;color:var(--slate-500);margin-bottom:4px"><i class="fa-solid fa-hospital-user"></i> <?= htmlspecialchars($appt['hospital_name'] ?? 'Sede Principal') ?></div>
                        <div class="appt-time"><i class="fa-regular fa-clock"></i> <?= $timeStr ?> &nbsp;|&nbsp; <span class="badge badge-<?= $appt['status'] ?>"><?= ucfirst($appt['status']) ?></span></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending payment alert -->
        <?php if ($pendingPayments > 0): ?>
        <div class="card" style="margin-top:16px;border-left:4px solid var(--warning)">
            <div style="display:flex;align-items:center;gap:12px">
                <div style="width:40px;height:40px;border-radius:8px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="fa-solid fa-triangle-exclamation" style="color:var(--warning)"></i>
                </div>
                <div>
                    <p style="font-weight:600;font-size:.9rem">Tienes <?= $pendingPayments ?> pago<?= $pendingPayments > 1 ? 's' : '' ?> pendiente<?= $pendingPayments > 1 ? 's' : '' ?></p>
                    <a href="index.php?module=patient&action=payments" style="font-size:.8rem;color:var(--warning);font-weight:600">Ir a pagos →</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick links & patient info -->
    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><h3>Accesos rápidos</h3></div>
            <div class="quick-links" style="grid-template-columns:1fr 1fr">
                <a href="index.php?module=patient&action=appointments" class="quick-link">
                    <i class="fa-solid fa-calendar-plus"></i>
                    <span>Nueva Cita</span>
                </a>
                <a href="index.php?module=patient&action=payments" class="quick-link">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Mis Pagos</span>
                </a>
                <a href="index.php?module=patient&action=health" class="quick-link">
                    <i class="fa-solid fa-file-medical"></i>
                    <span>Mi Salud</span>
                </a>
                <a href="index.php?module=patient&action=health" class="quick-link">
                    <i class="fa-solid fa-pills"></i>
                    <span>Recetas</span>
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Mi perfil</h3></div>
            <div style="display:flex;flex-direction:column;gap:10px">
                <div class="vital-row">
                    <div class="vital-icon"><i class="fa-solid fa-tint"></i></div>
                    <div>
                        <div class="vital-label">Tipo de sangre</div>
                        <div class="vital-value"><?= htmlspecialchars($patient['blood_type'] ?? '—') ?></div>
                    </div>
                </div>
                <div class="vital-row">
                    <div class="vital-icon"><i class="fa-solid fa-shield-virus"></i></div>
                    <div>
                        <div class="vital-label">Alergias</div>
                        <div class="vital-value" style="font-size:.85rem"><?= htmlspecialchars($patient['allergies'] ?? 'Ninguna registrada') ?></div>
                    </div>
                </div>
                <?php if (!empty($patient['insurance_provider'])): ?>
                <div class="vital-row">
                    <div class="vital-icon"><i class="fa-solid fa-building-shield"></i></div>
                    <div>
                        <div class="vital-label">Seguro médico</div>
                        <div class="vital-value" style="font-size:.85rem"><?= htmlspecialchars($patient['insurance_provider']) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
