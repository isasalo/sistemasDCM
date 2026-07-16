<?php
/**
 * views/doctor/dashboard.php
 * Variables: $doctor, $todayAppts, $urgencies, $stats, $urgencyCount, $notifCount
 */
?>
<div class="page-header">
    <div>
        <h1>Bienvenido, Dr. <?= htmlspecialchars(explode(' ', $doctor['name'] ?? 'Doctor')[0]) ?> 🩺</h1>
        <p>Resumen de tu jornada para hoy, <?= date('d \d\e F \d\e Y') ?></p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="index.php?module=doctor&action=urgencies" class="btn btn-danger">
            <i class="fa-solid fa-truck-medical"></i> Urgencias (<span id="urgency-counter"><?= $urgencyCount ?></span>)
        </a>
        <a href="index.php?module=doctor&action=schedule" class="btn btn-primary">
            <i class="fa-solid fa-calendar-week"></i> Mi Agenda
        </a>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="icon"><i class="fa-solid fa-calendar-day"></i></div>
        <div class="info">
            <div class="value"><?= count($todayAppts) ?></div>
            <div class="label">Citas para hoy</div>
        </div>
    </div>
    <div class="stat-card success">
        <div class="icon"><i class="fa-solid fa-user-check"></i></div>
        <div class="info">
            <div class="value"><?= $stats['total_patients'] ?? 0 ?></div>
            <div class="label">Pacientes totales</div>
        </div>
    </div>
    <div class="stat-card warning">
        <div class="icon"><i class="fa-solid fa-clock"></i></div>
        <div class="info">
            <div class="value"><?= $urgencyCount ?></div>
            <div class="label">Urgencias en espera</div>
        </div>
    </div>
    <div class="stat-card danger">
        <div class="icon"><i class="fa-solid fa-file-medical"></i></div>
        <div class="info">
            <div class="value"><?= $stats['completed_appts'] ?? 0 ?></div>
            <div class="label">Consultas realizadas</div>
        </div>
    </div>
</div>

<div class="grid-3-1">
    <!-- Today's Timeline -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-clock-rotate-left" style="color:var(--primary);margin-right:8px"></i>Cronograma de Hoy</h3>
            </div>
            <div class="card-body">
                <div class="timeline-header">
                    <div class="timeline-legend"><span class="legend-dot confirmed"></span> Confirmada</div>
                    <div class="timeline-legend"><span class="legend-dot pending"></span> Pendiente</div>
                    <div class="timeline-legend"><span class="legend-dot completed"></span> Completada</div>
                </div>

                <div class="day-timeline">
                    <?php if (empty($todayAppts)): ?>
                        <div class="timeline-empty">
                            <i class="fa-solid fa-calendar-day"></i>
                            <p>No tienes citas programadas para hoy.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($todayAppts as $appt): ?>
                        <div class="timeline-block">
                            <div class="timeline-time"><?= substr($appt['appointment_time'], 0, 5) ?></div>
                            <div class="timeline-content <?= $appt['status'] ?>">
                                <div style="display:flex;justify-content:space-between">
                                    <div class="patient-name"><?= htmlspecialchars($appt['patient_name']) ?></div>
                                    <div class="badge badge-<?= $appt['status'] ?>" style="font-size:0.6rem">
                                        <?= ucfirst($appt['status']) ?>
                                    </div>
                                </div>
                                <div class="appt-reason"><?= htmlspecialchars($appt['reason'] ?: 'Consulta general') ?></div>
                                <div style="margin-top:8px;display:flex;gap:5px">
                                    <a href="index.php?module=doctor&action=patients&patient_id=<?= $appt['patient_id'] ?>&appointment_id=<?= $appt['id'] ?>" class="btn btn-sm btn-outline" style="padding:2px 8px;font-size:0.7rem">
                                        Atender
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Urgencies Sidebar -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3>Urgencias Críticas</h3>
            </div>
            <div class="card-body">
                <?php if (empty($urgencies)): ?>
                    <div class="empty-state" style="padding:20px">
                        <i class="fa-solid fa-circle-check" style="color:var(--success)"></i>
                        <p style="font-size:0.8rem">No hay urgencias activas.</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($urgencies, 0, 4) as $urg): ?>
                        <?php 
                        $triage = Urgency::$TRIAGE_COLORS[$urg['triage_level']] ?? ['color' => '#64748b', 'label' => 'Desconocido'];
                        $triageColor = $triage['color'];
                        $triageLabel = $triage['label'];
                        $waitingMinutes = max(0, round((time() - strtotime($urg['arrival_time'])) / 60));
                        ?>
                    <div class="triage-card" style="margin-bottom:10px;border:1px solid var(--gray-200)">
                        <div class="triage-header" style="background-color:<?= $triageColor ?>;padding:8px 12px">
                            <span>Nivel <?= htmlspecialchars($triageLabel) ?></span>
                            <span><?= $waitingMinutes ?> min</span>
                        </div>
                        <div class="triage-body" style="padding:10px">
                            <div class="triage-complaint" style="font-size:0.8rem"><?= htmlspecialchars($urg['patient_name']) ?></div>
                            <p style="font-size:0.7rem;color:var(--gray-600);margin-bottom:8px"><?= htmlspecialchars($urg['chief_complaint']) ?></p>
                            <a href="index.php?module=doctor&action=urgencies" class="btn btn-sm btn-primary" style="width:100%;justify-content:center;font-size:0.7rem">
                                Ver Detalle
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card" style="margin-top:16px">
            <div class="card-header"><h3>Enlaces Rápidos</h3></div>
            <div class="quick-links" style="grid-template-columns:1fr 1fr">
                <a href="index.php?module=doctor&action=schedule" class="quick-link">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Mi Agenda</span>
                </a>
                <a href="index.php?module=doctor&action=patients" class="quick-link">
                    <i class="fa-solid fa-users"></i>
                    <span>Pacientes</span>
                </a>
                <a href="index.php?module=doctor&action=records" class="quick-link">
                    <i class="fa-solid fa-file-invoice"></i>
                    <span>Historiales</span>
                </a>
                <a href="index.php?module=doctor&action=urgencies" class="quick-link">
                    <i class="fa-solid fa-heart-pulse"></i>
                    <span>Urgencias</span>
                </a>
            </div>
        </div>
    </div>
</div>
