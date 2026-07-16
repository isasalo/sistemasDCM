<?php
/**
 * views/admin/dashboard.php
 * Variables: $totalPatients, $totalDoctors, $apptStats, $financial, $urgencyCount, $recentPayments
 */
?>
<div class="page-header">
    <div>
        <h1>Panel de Administración</h1>
        <p>Estado global del sistema hospitalario</p>
    </div>
    <div style="display:flex;gap:10px">
        <button class="btn btn-outline" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Reporte Rápido
        </button>
        <a href="index.php?module=admin&action=doctors" class="btn btn-primary">
            <i class="fa-solid fa-user-plus"></i> Gestionar Staff
        </a>
    </div>
</div>

<!-- Global Stats -->
<div class="stats-grid">
    <div class="stat-card purple">
        <div class="icon"><i class="fa-solid fa-hospital-user"></i></div>
        <div class="info">
            <div class="value"><?= $totalPatients ?></div>
            <div class="label">Pacientes Registrados</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fa-solid fa-user-md"></i></div>
        <div class="info">
            <div class="value"><?= $totalDoctors ?></div>
            <div class="label">Staff Médico</div>
        </div>
    </div>
    <div class="stat-card warning">
        <div class="icon"><i class="fa-solid fa-truck-medical"></i></div>
        <div class="info">
            <div class="value"><?= $urgencyCount ?></div>
            <div class="label">Urgencias Activas</div>
        </div>
    </div>
    <div class="stat-card success">
        <div class="icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        <div class="info">
            <div class="value">$<?= number_format(($financial['total_revenue'] ?? 0) / 1000000, 1) ?>M</div>
            <div class="label">Ingresos Mensuales</div>
        </div>
    </div>
</div>

<div class="grid-3-1">
    <!-- Appointments Overview -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3>Resumen de Citas</h3>
            </div>
            <div class="card-body">
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-amount"><?= $apptStats['confirmed'] ?? 0 ?></div>
                        <div class="kpi-label">Confirmadas</div>
                    </div>
                    <div class="kpi-card green">
                        <div class="kpi-amount"><?= $apptStats['completed'] ?? 0 ?></div>
                        <div class="kpi-label">Atendidas</div>
                    </div>
                    <div class="kpi-card amber">
                        <div class="kpi-amount"><?= $apptStats['pending'] ?? 0 ?></div>
                        <div class="kpi-label">Pendientes</div>
                    </div>
                    <div class="kpi-card red" style="border-bottom:3px solid var(--danger)">
                        <div class="kpi-amount" style="color:var(--danger)"><?= $apptStats['cancelled'] ?? 0 ?></div>
                        <div class="kpi-label">Canceladas</div>
                    </div>
                </div>

                <div class="table-container" style="margin-top:20px">
                    <div class="card-header" style="border-bottom:none">
                        <h4>Transacciones Recientes</h4>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Factura</th>
                                <th>Paciente</th>
                                <th>Monto</th>
                                <th>Método</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentPayments)): ?>
                                <tr><td colspan="6" style="text-align:center; padding:20px">No hay transacciones recientes.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentPayments as $pay): ?>
                                <tr>
                                    <td style="font-family:monospace; font-size:0.75rem"><?= htmlspecialchars($pay['invoice_number']) ?></td>
                                    <td><?= htmlspecialchars($pay['patient_name']) ?></td>
                                    <td style="font-weight:700">$<?= number_format($pay['total'], 0, ',', '.') ?></td>
                                    <td><i class="fa-solid fa-credit-card"></i> <?= ucfirst($pay['payment_method']) ?></td>
                                    <td style="font-size:0.75rem"><?= date('d/m H:i', strtotime($pay['created_at'])) ?></td>
                                    <td><span class="badge badge-<?= $pay['status'] === 'paid' ? 'confirmed' : 'pending' ?>"><?= $pay['status'] === 'paid' ? 'Pagado' : 'Pendiente' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- System Health & Quick Actions -->
    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><h3>Finanzas Rápidas</h3></div>
            <div style="display:flex; flex-direction:column; gap:12px">
                <div class="vital-row" style="background:var(--success-light); border-color:var(--success)">
                    <div class="vital-icon" style="background:var(--success); color:white"><i class="fa-solid fa-arrow-trend-up"></i></div>
                    <div>
                        <div class="vital-label">Recaudado (Mes)</div>
                        <div class="vital-value" style="color:var(--success)">$<?= number_format($financial['total_revenue'] ?? 0, 0, ',', '.') ?></div>
                    </div>
                </div>
                <div class="vital-row" style="background:var(--warning-light); border-color:var(--warning)">
                    <div class="vital-icon" style="background:var(--warning); color:white"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                    <div>
                        <div class="vital-label">Por Recaudar</div>
                        <div class="vital-value" style="color:var(--warning)">$<?= number_format($financial['pending_revenue'] ?? 0, 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>
            <a href="index.php?module=admin&action=finances" class="btn btn-sm btn-outline" style="width:100%; margin-top:15px; justify-content:center">
                Ver Detalles Financieros
            </a>
        </div>

        <div class="card">
            <div class="card-header"><h3>Gestión de Staff</h3></div>
            <div class="quick-links" style="grid-template-columns:1fr 1fr">
                <a href="index.php?module=admin&action=doctors" class="quick-link">
                    <i class="fa-solid fa-user-doctor"></i>
                    <span>Ver Staff</span>
                </a>
                <a href="index.php?module=admin&action=schedules" class="quick-link">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Horarios</span>
                </a>
            </div>
        </div>
    </div>
</div>
