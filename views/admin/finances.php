<?php
/**
 * views/admin/finances.php
 * Variables: $payments, $financial, $services, $flash
 */
$statusLabels = ['pending' => 'Pendiente', 'paid' => 'Pagado', 'refunded' => 'Reembolsado'];
?>
<div class="page-header">
    <div>
        <h1>Gestión Financiera</h1>
        <p>Reportes de ingresos, facturación y servicios</p>
    </div>
    <div style="display:flex; gap:10px">
        <button class="btn btn-secondary" onclick="exportTableCSV('finances-table', 'reporte_financiero_<?= date('Ymd') ?>.csv')">
            <i class="fa-solid fa-file-csv"></i> Exportar CSV
        </button>
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimir Reporte
        </button>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card success">
        <div class="icon"><i class="fa-solid fa-money-check-dollar"></i></div>
        <div class="info">
            <div class="value">$<?= number_format($financial['total_revenue'] ?? 0, 0, ',', '.') ?></div>
            <div class="label">Recaudación Total (Mes)</div>
        </div>
    </div>
    <div class="stat-card warning">
        <div class="icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div class="info">
            <div class="value">$<?= number_format($financial['pending_revenue'] ?? 0, 0, ',', '.') ?></div>
            <div class="label">Pendiente por Cobrar</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fa-solid fa-receipt"></i></div>
        <div class="info">
            <div class="value"><?= count($payments) ?></div>
            <div class="label">Transacciones</div>
        </div>
    </div>
    <div class="stat-card danger">
        <div class="icon"><i class="fa-solid fa-percent"></i></div>
        <div class="info">
            <div class="value">19%</div>
            <div class="label">IVA Aplicado</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="flex-wrap:wrap; gap:15px">
        <h3>Historial de Transacciones</h3>
        <form method="GET" action="index.php" class="filter-bar" style="margin:0; box-shadow:none; padding:0">
            <input type="hidden" name="module" value="admin">
            <input type="hidden" name="action" value="finances">
            
            <input type="date" name="date_from" value="<?= $_GET['date_from'] ?? '' ?>" class="form-control" style="width:140px">
            <input type="date" name="date_to" value="<?= $_GET['date_to'] ?? '' ?>" class="form-control" style="width:140px">
            
            <select name="status" class="form-control" style="width:120px">
                <option value="">Todos</option>
                <option value="paid" <?= ($_GET['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Pagado</option>
                <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pendiente</option>
            </select>
            
            <button type="submit" class="btn btn-icon btn-primary">
                <i class="fa-solid fa-filter"></i>
            </button>
        </form>
    </div>

    <div class="table-container">
        <table class="table" id="finances-table">
            <thead>
                <tr>
                    <th>ID / Factura</th>
                    <th>Paciente</th>
                    <th>Descripción</th>
                    <th>Monto</th>
                    <th>IVA</th>
                    <th>Total</th>
                    <th>Método</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="9" style="text-align:center; padding:30px">No se encontraron transacciones.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $pay): ?>
                    <tr>
                        <td style="font-family:monospace; font-size:0.75rem"><?= htmlspecialchars($pay['invoice_number'] ?: $pay['id']) ?></td>
                        <td><?= htmlspecialchars($pay['patient_name']) ?></td>
                        <td style="font-size:0.8rem"><?= htmlspecialchars(($pay['description'] ?: $pay['service_name']) ?? '—') ?></td>
                        <td>$<?= number_format($pay['subtotal'], 0, ',', '.') ?></td>
                        <td style="color:var(--gray-400)">$<?= number_format($pay['tax_amount'], 0, ',', '.') ?></td>
                        <td style="font-weight:700; color:var(--primary)">$<?= number_format($pay['total'], 0, ',', '.') ?></td>
                        <td><span style="font-size:0.7rem"><i class="fa-solid fa-money-bill-wave"></i> <?= ucfirst($pay['payment_method'] ?? '—') ?></span></td>
                        <td style="font-size:0.75rem"><?= date('d/m/Y H:i', strtotime($pay['created_at'])) ?></td>
                        <td>
                            <span class="badge badge-<?= $pay['status'] === 'paid' ? 'confirmed' : ($pay['status'] === 'pending' ? 'pending' : 'cancelled') ?>">
                                <?= $statusLabels[$pay['status']] ?? $pay['status'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="grid-2" style="margin-top:20px">
    <div class="card">
        <div class="card-header"><h3>Ingresos por Servicio</h3></div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr><th>Servicio</th><th>Cantidad</th><th>Monto Bruto</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $srv): ?>
                    <tr>
                        <td><?= htmlspecialchars($srv['name']) ?></td>
                        <td><?= $srv['count'] ?></td>
                        <td style="font-weight:700">$<?= number_format($srv['total_amount'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header"><h3>Información de Impuestos</h3></div>
        <div class="card-body">
            <?php 
            $totalTax = array_sum(array_column($payments, 'tax_amount'));
            $totalBase = array_sum(array_column($payments, 'subtotal'));
            ?>
            <div class="vital-row">
                <div class="vital-icon" style="background:var(--primary-light)"><i class="fa-solid fa-calculator"></i></div>
                <div>
                    <div class="vital-label">Base Gravable Total</div>
                    <div class="vital-value">$<?= number_format($totalBase, 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="vital-row" style="margin-top:10px">
                <div class="vital-icon" style="background:var(--danger-light); color:var(--danger)"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div>
                    <div class="vital-label">IVA 19% Recaudado</div>
                    <div class="vital-value" style="color:var(--danger)">$<?= number_format($totalTax, 0, ',', '.') ?></div>
                </div>
            </div>
            <p style="font-size:0.7rem; color:var(--gray-400); margin-top:15px; line-height:1.4">
                * Valores calculados sobre todas las transacciones en el periodo seleccionado. 
                El IVA se calcula automáticamente (19%) sobre el valor de la consulta o servicio.
            </p>
        </div>
    </div>
</div>
