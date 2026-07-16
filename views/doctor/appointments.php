<?php
/**
 * views/doctor/appointments.php
 * Variables: $appointments, $doctor
 */
?>
<div class="page-header">
    <div>
        <h1>Gestión de Citas</h1>
        <p>Revisa, confirma o cancela las solicitudes de tus pacientes</p>
    </div>
</div>

<div class="card">
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Motivo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appointments)): ?>
                <tr><td colspan="6" style="text-align:center;padding:32px;color:#94A3B8">No tienes citas pendientes.</td></tr>
                <?php else: ?>
                <?php foreach ($appointments as $appt): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div class="avatar" style="width:32px;height:32px;font-size:.8rem">
                                <?= mb_strtoupper(mb_substr($appt['patient_name'],0,1)) ?>
                            </div>
                            <span style="font-weight:600"><?= htmlspecialchars($appt['patient_name']) ?></span>
                        </div>
                    </td>
                    <td><?= date('d/m/Y', strtotime($appt['appointment_date'])) ?></td>
                    <td><?= substr($appt['appointment_time'], 0, 5) ?></td>
                    <td style="max-width:200px;font-size:.85rem;color:var(--gray-600)"><?= htmlspecialchars($appt['reason'] ?: 'Consulta general') ?></td>
                    <td><span class="badge badge-<?= $appt['status'] ?>"><?= ucfirst($appt['status']) ?></span></td>
                    <td>
                        <div class="table-actions">
                            <?php if ($appt['status'] === 'pending'): ?>
                            <form method="POST" action="index.php?module=doctor&action=appointments" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                <input type="hidden" name="action" value="confirm">
                                <input type="hidden" name="appointment_id" value="<?= $appt['id'] ?>">
                                <button type="submit" class="action-edit" title="Confirmar" style="color:var(--success);background:#EFFFF4">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>
                            <button class="action-delete" title="Rechazar" onclick="openCancelModal(<?= $appt['id'] ?>)">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                            <?php endif; ?>
                            
                            <?php if ($appt['status'] === 'confirmed'): ?>
                            <a href="index.php?module=doctor&action=patients&patient_id=<?= $appt['patient_id'] ?>&appointment_id=<?= $appt['id'] ?>" 
                                class="btn btn-sm btn-primary" style="padding:4px 8px;font-size:.75rem">
                                Atender
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Cancelar Cita (Doctor) -->
<div class="modal-overlay" id="modal-cancel">
    <div class="modal" style="max-width:400px">
        <div class="modal-header">
            <h3>Rechazar Cita</h3>
            <button class="modal-close" data-close-modal="modal-cancel"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="index.php?module=doctor&action=appointments">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="appointment_id" id="cancel-appt-id">
            <div class="modal-body">
                <p style="margin-bottom:12px;font-size:.9rem">Indica el motivo del rechazo para informar al paciente:</p>
                <textarea name="reason" class="form-control" rows="3" placeholder="Ej: No estaré disponible a esa hora..." required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modal-cancel">Volver</button>
                <button type="submit" class="btn btn-danger">Confirmar Rechazo</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCancelModal(id) {
    document.getElementById('cancel-appt-id').value = id;
    openModal('modal-cancel');
}
</script>
