<?php
/**
 * views/patient/payments.php
 * Variables: $payments, $services, $patient
 */
$statusLabels  = ['pending'=>'Pendiente','paid'=>'Pagado','refunded'=>'Reembolsado','partial'=>'Parcial'];
$methodLabels  = ['cash'=>'Efectivo','card'=>'Tarjeta','transfer'=>'Transferencia','insurance'=>'Seguro'];
$methodIcons   = ['cash'=>'fa-money-bill-wave','card'=>'fa-credit-card','transfer'=>'fa-building-columns','insurance'=>'fa-building-shield'];
?>
<div class="page-header">
    <div>
        <h1>Mis Pagos</h1>
        <p>Historial de pagos y comprobantes</p>
    </div>
</div>

<!-- Summary stats -->
<?php
$totalPaid    = array_sum(array_column(array_filter($payments, fn($p) => $p['status'] === 'paid'),    'total'));
$totalPending = array_sum(array_column(array_filter($payments, fn($p) => $p['status'] === 'pending'), 'total'));
?>
<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card success">
        <div class="icon"><i class="fa-solid fa-circle-check"></i></div>
        <div class="info">
            <div class="value">$<?= number_format($totalPaid, 0, ',', '.') ?></div>
            <div class="label">Total pagado</div>
        </div>
    </div>
    <div class="stat-card warning">
        <div class="icon"><i class="fa-solid fa-clock"></i></div>
        <div class="info">
            <div class="value">$<?= number_format($totalPending, 0, ',', '.') ?></div>
            <div class="label">Por pagar</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fa-solid fa-receipt"></i></div>
        <div class="info">
            <div class="value"><?= count($payments) ?></div>
            <div class="label">Total facturas</div>
        </div>
    </div>
</div>

<!-- Payments table -->
<div class="table-container" style="margin-bottom:24px">
    <table class="table">
        <thead>
            <tr>
                <th>Factura</th>
                <th>Descripción</th>
                <th>Subtotal</th>
                <th>IVA (19%)</th>
                <th>Total</th>
                <th>Método</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($payments)): ?>
        <tr><td colspan="8" style="text-align:center;padding:32px;color:#94A3B8">No tienes pagos registrados.</td></tr>
        <?php else: ?>
        <?php foreach ($payments as $pay): ?>
        <tr>
            <td style="font-family:monospace;font-size:.8rem"><?= htmlspecialchars($pay['invoice_number'] ?? '—') ?></td>
            <td><?= htmlspecialchars($pay['description'] ?? $pay['service_name'] ?? '—') ?></td>
            <td>$<?= number_format($pay['subtotal'], 0, ',', '.') ?></td>
            <td>$<?= number_format($pay['tax_amount'], 0, ',', '.') ?></td>
            <td style="font-weight:700;color:var(--primary)">$<?= number_format($pay['total'], 0, ',', '.') ?></td>
            <td>
                <?php if (!empty($pay['payment_method'])): ?>
                <i class="fa-solid <?= $methodIcons[$pay['payment_method']] ?? 'fa-money-bill' ?>"></i>
                <?= $methodLabels[$pay['payment_method']] ?? $pay['payment_method'] ?>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td><span class="badge badge-<?= $pay['status'] === 'paid' ? 'confirmed' : ($pay['status'] === 'pending' ? 'pending' : 'cancelled') ?>"><?= $statusLabels[$pay['status']] ?? $pay['status'] ?></span></td>
            <td>
                <div class="table-actions">
                    <?php if ($pay['status'] === 'pending'): ?>
                    <button class="action-view" title="Pagar ahora" onclick="openPayModal(<?= $pay['id'] ?>, '<?= htmlspecialchars($pay['description'] ?? '') ?>', <?= $pay['total'] ?>)">
                        <i class="fa-solid fa-dollar-sign"></i>
                    </button>
                    <?php endif; ?>
                    <?php if ($pay['status'] === 'paid'): ?>
                    <button class="action-edit" title="Ver comprobante" onclick="openVoucher(<?= $pay['id'] ?>)">
                        <i class="fa-solid fa-file-invoice"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ─── Modal: Process payment ───────────────────────── -->
<div class="modal-overlay" id="modal-pay">
    <div class="modal" style="max-width:420px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-credit-card" style="color:var(--primary);margin-right:8px"></i>Procesar Pago</h3>
            <button class="modal-close" data-close-modal="modal-pay"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="index.php?module=patient&action=payments">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <input type="hidden" name="action" value="pay">
            <input type="hidden" name="payment_id" id="pay-id">
            <div class="modal-body">
                <p id="pay-description" style="font-weight:600;margin-bottom:8px;color:var(--gray-900)"></p>
                <p id="pay-total" style="font-size:1.5rem;font-weight:800;color:var(--primary);margin-bottom:20px"></p>
                <div class="form-group">
                    <label class="form-label">Método de pago</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="cash">💵 Efectivo</option>
                        <option value="card">💳 Tarjeta débito/crédito</option>
                        <option value="transfer">🏦 Transferencia bancaria</option>
                        <option value="insurance">🛡️ Seguro médico</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modal-pay">Cancelar</button>
                <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Confirmar Pago</button>
            </div>
        </form>
    </div>
</div>

<!-- ─── Voucher modals (one per paid payment) ────────── -->
<?php foreach ($payments as $pay): if ($pay['status'] !== 'paid') continue; ?>
<div class="modal-overlay" id="voucher-<?= $pay['id'] ?>">
    <div class="modal" style="max-width:460px">
        <div class="modal-header">
            <h3>Comprobante de Pago</h3>
            <button class="modal-close" data-close-modal="voucher-<?= $pay['id'] ?>"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="voucher" id="voucher-content-<?= $pay['id'] ?>">
                <div class="voucher-logo"><i class="fa-solid fa-heart-pulse"></i> Tu Salud Primero</div>
                <div class="voucher-title">Comprobante de Pago</div>
                <div class="voucher-invoice">Factura: <?= htmlspecialchars($pay['invoice_number']) ?></div>
                <hr class="voucher-divider">
                <div class="voucher-row"><span>Servicio</span><span><?= htmlspecialchars($pay['description'] ?? $pay['service_name'] ?? '—') ?></span></div>
                <div class="voucher-row"><span>Subtotal</span><span>$<?= number_format($pay['subtotal'], 0, ',', '.') ?></span></div>
                <div class="voucher-row"><span>IVA (19%)</span><span>$<?= number_format($pay['tax_amount'], 0, ',', '.') ?></span></div>
                <?php if ($pay['discount'] > 0): ?>
                <div class="voucher-row" style="color:var(--success)"><span>Descuento</span><span>-$<?= number_format($pay['discount'], 0, ',', '.') ?></span></div>
                <?php endif; ?>
                <hr class="voucher-divider">
                <div class="voucher-total"><span>Total pagado</span><span>$<?= number_format($pay['total'], 0, ',', '.') ?></span></div>
                <hr class="voucher-divider" style="margin-top:0">
                <div class="voucher-row" style="font-size:.8rem"><span>Método</span><span><?= $methodLabels[$pay['payment_method']] ?? '—' ?></span></div>
                <div class="voucher-row" style="font-size:.8rem"><span>Fecha</span><span><?= $pay['paid_at'] ? date('d/m/Y H:i', strtotime($pay['paid_at'])) : '—' ?></span></div>
                <p style="text-align:center;margin-top:20px;font-size:.75rem;color:#94A3B8">Gracias por confiar en Tu Salud Primero ❤</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" data-close-modal="voucher-<?= $pay['id'] ?>">Cerrar</button>
            <button class="btn btn-primary" onclick="printVoucher('voucher-content-<?= $pay['id'] ?>')">
                <i class="fa-solid fa-print"></i> Imprimir
            </button>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
function openPayModal(id, desc, total) {
    document.getElementById('pay-id').value = id;
    document.getElementById('pay-description').textContent = desc;
    document.getElementById('pay-total').textContent = '$' + parseInt(total).toLocaleString('es-CO');
    openModal('modal-pay');
}
function openVoucher(id) { openModal('voucher-' + id); }
</script>
