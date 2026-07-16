<?php
/**
 * views/doctor/urgencies.php
 * Variables: $urgencies, $triageMap, $patients
 */
?>
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center">
    <div>
        <h1>Central de Urgencias</h1>
        <p>Monitoreo en tiempo real y triaje de pacientes</p>
    </div>
    <button class="btn btn-primary" onclick="openCreateUrgencyModal()">
        <i class="fa-solid fa-plus"></i> Registrar Cita de Urgencia
    </button>
</div>

<div class="triage-grid">
    <?php if (empty($urgencies)): ?>
        <div class="card empty-state" style="grid-column:1/-1">
            <i class="fa-solid fa-check-double" style="font-size:3rem; color:var(--success); margin-bottom:15px"></i>
            <h3>No hay pacientes en espera</h3>
            <p>La cola de urgencias está vacía en este momento.</p>
        </div>
    <?php else: ?>
        <?php foreach ($urgencies as $urg): ?>
            <?php 
            $triage = $triageMap[$urg['triage_level']] ?? ['color' => '#64748b', 'label' => 'Desconocido'];
            $triageColor = $triage['color'];
            $triageLabel = $triage['label'];
            $waitingMinutes = max(0, round((time() - strtotime($urg['arrival_time'])) / 60));
            ?>
        <div class="triage-card">
            <div class="triage-header" style="background-color:<?= $triageColor ?>">
                <span>Triage Nivel <?= htmlspecialchars($triageLabel) ?></span>
                <span class="badge" style="background:rgba(255,255,255,0.2); color:white">
                    <i class="fa-solid fa-clock"></i> Espera: <?= $waitingMinutes ?> min
                </span>
            </div>
            <div class="triage-body">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px">
                    <h3 class="triage-complaint"><?= htmlspecialchars($urg['patient_name'] ?? 'Paciente Desconocido') ?></h3>
                    <span class="badge badge-<?= $urg['status'] === 'waiting' ? 'warning' : ($urg['status'] === 'in_treatment' ? 'info' : 'success') ?>">
                        <?= $urg['status'] === 'waiting' ? 'En Espera' : ($urg['status'] === 'in_treatment' ? 'En Atención' : ($urg['status'] === 'discharged' ? 'Alta' : htmlspecialchars($urg['status']))) ?>
                    </span>
                </div>
                
                <div class="triage-meta" style="margin-bottom:15px">
                    <span><strong>DNI:</strong> <?= htmlspecialchars($urg['patient_dni'] ?? '—') ?></span>
                    <span><strong>Ingreso:</strong> <?= date('H:i', strtotime($urg['arrival_time'])) ?></span>
                </div>

                <div style="background:var(--gray-50); padding:10px; border-radius:var(--radius-sm); margin-bottom:15px; border-left:3px solid <?= $triageColor ?>">
                    <p style="font-size:0.75rem; font-weight:700; text-transform:uppercase; color:var(--gray-600); margin-bottom:4px">Motivo de Urgencia</p>
                    <p style="font-size:0.85rem; line-height:1.4"><?= htmlspecialchars($urg['chief_complaint']) ?></p>
                </div>

                <div class="triage-actions" style="display:flex; gap:10px; align-items:center">
                    <?php if ($urg['status'] === 'waiting'): ?>
                        <form method="POST" action="index.php?module=doctor&action=urgencies" style="flex:1; margin:0">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="attend">
                            <input type="hidden" name="urgency_id" value="<?= $urg['id'] ?>">
                            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; white-space:nowrap; padding: 10px 12px">
                                <i class="fa-solid fa-hand-holding-medical"></i> Atender
                            </button>
                        </form>
                    <?php elseif ($urg['status'] === 'in_treatment' && $urg['attending_doctor_id'] == $_SESSION['user_id']): ?>
                        <button class="btn btn-success" style="flex:1; justify-content:center; white-space:nowrap; padding: 10px 12px" onclick="openDischargeModal(<?= $urg['id'] ?>, '<?= htmlspecialchars($urg['patient_name'] ?? 'Paciente') ?>')">
                            <i class="fa-solid fa-person-walking-arrow-right"></i> Dar de Alta
                        </button>
                    <?php elseif ($urg['status'] === 'in_treatment'): ?>
                        <div class="btn btn-secondary" style="flex:1; cursor:default; justify-content:center; white-space:nowrap; padding: 10px 12px; font-size: 0.8rem">
                            <i class="fa-solid fa-user-doctor"></i> Otro médico
                        </div>
                    <?php else: ?>
                        <div class="btn btn-info" style="flex:1; cursor:default; justify-content:center; white-space:nowrap; padding: 10px 12px; font-size: 0.8rem; background-color: var(--success); color: white; border: none">
                            <i class="fa-solid fa-circle-check"></i> Completado
                        </div>
                    <?php endif; ?>

                    <button class="btn btn-outline" style="padding:10px 12px; min-width:auto" onclick="openEditUrgencyModal(<?= htmlspecialchars(json_encode([
                        'id' => $urg['id'],
                        'patient_id' => $urg['patient_id'],
                        'triage_level' => $urg['triage_level'],
                        'chief_complaint' => $urg['chief_complaint'],
                        'status' => $urg['status'],
                        'notes' => $urg['notes'] ?? ''
                    ])) ?>)" title="Editar">
                        <i class="fa-solid fa-pen"></i>
                    </button>

                    <form method="POST" action="index.php?module=doctor&action=urgencies" onsubmit="return confirm('¿Está seguro de eliminar esta urgencia de los registros?')" style="margin:0">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="urgency_id" value="<?= $urg['id'] ?>">
                        <button type="submit" class="btn" style="background:#e74c3c; color:white; border:none; padding:10px 12px; min-width:auto; border-radius:12px" title="Eliminar">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal: Discharge Urgency -->
<div class="modal-overlay" id="modal-discharge">
    <div class="modal" style="max-width:450px">
        <div class="modal-header">
            <h3>Dar de Alta — <span id="discharge-patient-name"></span></h3>
            <button class="modal-close" data-close-modal="modal-discharge"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="index.php?module=doctor&action=urgencies">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="discharge">
            <input type="hidden" name="urgency_id" id="discharge-urgency-id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Notas de Alta / Resolución</label>
                    <textarea name="notes" class="form-control" rows="4" required placeholder="Describe el tratamiento realizado y las recomendaciones de alta..."></textarea>
                </div>
                <p style="font-size:0.75rem; color:var(--gray-600)">
                    <i class="fa-solid fa-info-circle"></i> Esto cerrará el caso de urgencia y lo marcará como completado.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modal-discharge">Cancelar</button>
                <button type="submit" class="btn btn-success">Confirmar Alta</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Crear Urgencia -->
<div class="modal-overlay" id="modal-create-urgency">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h3>Registrar Cita de Urgencia</h3>
            <button class="modal-close" data-close-modal="modal-create-urgency"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="index.php?module=doctor&action=urgencies">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group" style="margin-bottom:15px">
                    <label class="form-label">Paciente</label>
                    <select name="patient_id" class="form-control" required style="width:100%; height:45px; border-radius:10px; border:1px solid var(--border-color); padding:0 10px">
                        <option value="">-- Seleccionar Paciente --</option>
                        <?php foreach ($patients as $pat): ?>
                            <option value="<?= $pat['id'] ?>"><?= htmlspecialchars($pat['name']) ?> (DNI: <?= htmlspecialchars($pat['insurance_number'] ?? '—') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:15px">
                    <label class="form-label">Nivel de Triaje</label>
                    <select name="triage_level" class="form-control" required style="width:100%; height:45px; border-radius:10px; border:1px solid var(--border-color); padding:0 10px">
                        <?php foreach ($triageMap as $lvl => $info): ?>
                            <option value="<?= $lvl ?>"><?= htmlspecialchars($info['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:15px">
                    <label class="form-label">Estado Inicial</label>
                    <select name="status" class="form-control" required style="width:100%; height:45px; border-radius:10px; border:1px solid var(--border-color); padding:0 10px">
                        <option value="waiting">En Espera</option>
                        <option value="in_treatment">En Atención</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Motivo de Urgencia</label>
                    <textarea name="chief_complaint" class="form-control" rows="3" required placeholder="Describe los síntomas y motivo de ingreso..." style="width:100%; border-radius:10px; border:1px solid var(--border-color); padding:10px"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="padding-top:15px; border-top:1px solid var(--border-color)">
                <button type="button" class="btn btn-secondary" data-close-modal="modal-create-urgency">Cancelar</button>
                <button type="submit" class="btn btn-primary">Registrar Ingreso</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar Urgencia -->
<div class="modal-overlay" id="modal-edit-urgency">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h3>Editar Cita de Urgencia</h3>
            <button class="modal-close" data-close-modal="modal-edit-urgency"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="index.php?module=doctor&action=urgencies">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="urgency_id" id="edit-urgency-id">
            <div class="modal-body">
                <div class="form-group" style="margin-bottom:15px">
                    <label class="form-label">Paciente</label>
                    <select name="patient_id" id="edit-patient-id" class="form-control" required style="width:100%; height:45px; border-radius:10px; border:1px solid var(--border-color); padding:0 10px">
                        <option value="">-- Seleccionar Paciente --</option>
                        <?php foreach ($patients as $pat): ?>
                            <option value="<?= $pat['id'] ?>"><?= htmlspecialchars($pat['name']) ?> (DNI: <?= htmlspecialchars($pat['insurance_number'] ?? '—') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:15px">
                    <label class="form-label">Nivel de Triaje</label>
                    <select name="triage_level" id="edit-triage-level" class="form-control" required style="width:100%; height:45px; border-radius:10px; border:1px solid var(--border-color); padding:0 10px">
                        <?php foreach ($triageMap as $lvl => $info): ?>
                            <option value="<?= $lvl ?>"><?= htmlspecialchars($info['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:15px">
                    <label class="form-label">Estado</label>
                    <select name="status" id="edit-status" class="form-control" required style="width:100%; height:45px; border-radius:10px; border:1px solid var(--border-color); padding:0 10px">
                        <option value="waiting">En Espera</option>
                        <option value="in_treatment">En Atención</option>
                        <option value="discharged">Dado de Alta</option>
                        <option value="hospitalized">Hospitalizado</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:15px">
                    <label class="form-label">Motivo de Urgencia</label>
                    <textarea name="chief_complaint" id="edit-chief-complaint" class="form-control" rows="3" required placeholder="Describe los síntomas y motivo de ingreso..." style="width:100%; border-radius:10px; border:1px solid var(--border-color); padding:10px"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Notas de Alta / Seguimiento</label>
                    <textarea name="notes" id="edit-notes" class="form-control" rows="3" placeholder="Ingresa notas de tratamiento u observaciones..." style="width:100%; border-radius:10px; border:1px solid var(--border-color); padding:10px"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="padding-top:15px; border-top:1px solid var(--border-color)">
                <button type="button" class="btn btn-secondary" data-close-modal="modal-edit-urgency">Cancelar</button>
                <button type="submit" class="btn btn-primary">Actualizar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDischargeModal(id, name) {
    document.getElementById('discharge-urgency-id').value = id;
    document.getElementById('discharge-patient-name').textContent = name;
    openModal('modal-discharge');
}

function openCreateUrgencyModal() {
    openModal('modal-create-urgency');
}

function openEditUrgencyModal(urg) {
    document.getElementById('edit-urgency-id').value = urg.id;
    document.getElementById('edit-patient-id').value = urg.patient_id;
    document.getElementById('edit-triage-level').value = urg.triage_level;
    document.getElementById('edit-status').value = urg.status;
    document.getElementById('edit-chief-complaint').value = urg.chief_complaint;
    document.getElementById('edit-notes').value = urg.notes || '';
    openModal('modal-edit-urgency');
}
</script>
