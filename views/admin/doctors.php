<?php
/**
 * views/admin/doctors.php
 * Variables: $doctors, $specialties, $flash, $notifCount
 */
?>
<div class="page-header">
    <div>
        <h1>Gestión de Staff Médico</h1>
        <p>Registra, edita y gestiona el personal médico del hospital</p>
    </div>
    <button class="btn btn-primary" data-open-modal="modal-create-doctor">
        <i class="fa-solid fa-plus"></i> Registrar Doctor
    </button>
</div>

<div class="doctor-cards-grid">
    <?php if (empty($doctors)): ?>
        <div class="card empty-state" style="grid-column:1/-1">
            <i class="fa-solid fa-user-md" style="font-size:3rem; opacity:0.1"></i>
            <p>No hay doctores registrados.</p>
        </div>
    <?php else: ?>
        <?php foreach ($doctors as $doc): ?>
        <div class="doctor-card">
            <div class="doctor-card-banner"></div>
            <div class="doctor-card-body">
                <div class="doctor-card-avatar">
                    <?php if ($doc['avatar']): ?>
                        <img src="<?= htmlspecialchars($doc['avatar']) ?>" alt="">
                    <?php else: ?>
                        <?= mb_strtoupper(mb_substr($doc['name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:flex-start">
                    <div>
                        <h3 class="doctor-card-name"><?= htmlspecialchars($doc['name']) ?></h3>
                        <p class="doctor-card-spec"><?= htmlspecialchars($doc['specialty_name'] ?? 'N/A') ?></p>
                    </div>
                    <div class="status-dot <?= $doc['active'] ? 'active' : 'inactive' ?>" title="<?= $doc['active'] ? 'Activo' : 'Inactivo' ?>"></div>
                </div>
                
                <p class="doctor-card-fee">Tarifa: $<?= number_format($doc['consultation_fee'], 0, ',', '.') ?></p>
                <p style="font-size:0.75rem; color:var(--gray-600); margin-top:5px"><i class="fa-solid fa-id-card"></i> MP: <?= htmlspecialchars($doc['license_number']) ?></p>

                <div class="doctor-card-stats">
                    <div class="doctor-card-stat">
                        <div class="val"><?= $doc['completed_appts'] ?? 0 ?></div>
                        <div class="lbl">Citas</div>
                    </div>
                    <div class="doctor-card-stat">
                        <div class="val"><?= number_format($doc['rating'] ?? 0, 1) ?></div>
                        <div class="lbl">Calif.</div>
                    </div>
                </div>

                <div class="doctor-card-footer">
                    <button class="btn btn-sm btn-outline" style="flex:1" onclick='openEditDoctorModal(<?= json_encode($doc) ?>)'>
                        <i class="fa-solid fa-edit"></i> Editar
                    </button>
                    <form method="POST" action="index.php?module=admin&action=doctors" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="user_id" value="<?= $doc['user_id'] ?>">
                        <input type="hidden" name="active" value="<?= $doc['active'] ?>">
                        <button type="submit" class="btn btn-sm btn-icon <?= $doc['active'] ? 'btn-danger' : 'btn-success' ?>" title="<?= $doc['active'] ? 'Desactivar' : 'Activar' ?>">
                            <i class="fa-solid fa-<?= $doc['active'] ? 'user-slash' : 'user-check' ?>"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal: Create Doctor -->
<div class="modal-overlay" id="modal-create-doctor">
    <div class="modal" style="max-width:550px">
        <div class="modal-header">
            <h3>Registrar Nuevo Doctor</h3>
            <button class="modal-close" data-close-modal="modal-create-doctor"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="index.php?module=admin&action=doctors">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" name="name" class="form-control" required placeholder="Ej: Dr. Samuel Goe">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control" required placeholder="doctor@hospital.com">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Especialidad</label>
                        <select name="specialty_id" class="form-control" required>
                            <?php foreach ($specialties as $sp): ?>
                                <option value="<?= $sp['id'] ?>"><?= htmlspecialchars($sp['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Matrícula Profesional</label>
                        <input type="text" name="license_number" class="form-control" required placeholder="MP-12345">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="phone" class="form-control" placeholder="+57 300...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tarifa de Consulta</label>
                        <input type="number" name="consultation_fee" class="form-control" value="50000">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Biografía / Perfil</label>
                    <textarea name="bio" class="form-control" rows="2" placeholder="Breve descripción del médico..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Contraseña Temporal</label>
                    <input type="password" name="password" class="form-control" value="doctor123">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modal-create-doctor">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Staff Médico</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Doctor -->
<div class="modal-overlay" id="modal-edit-doctor">
    <div class="modal" style="max-width:550px">
        <div class="modal-header">
            <h3>Editar Staff Médico</h3>
            <button class="modal-close" data-close-modal="modal-edit-doctor"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="index.php?module=admin&action=doctors">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="doctor_id" id="edit-doctor-id">
            <input type="hidden" name="user_id" id="edit-user-id">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Especialidad</label>
                        <select name="specialty_id" id="edit-specialty-id" class="form-control">
                            <?php foreach ($specialties as $sp): ?>
                                <option value="<?= $sp['id'] ?>"><?= htmlspecialchars($sp['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tarifa de Consulta</label>
                        <input type="number" name="consultation_fee" id="edit-fee" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="phone" id="edit-phone" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Biografía / Perfil</label>
                    <textarea name="bio" id="edit-bio" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modal-edit-doctor">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditDoctorModal(doc) {
    document.getElementById('edit-doctor-id').value = doc.id;
    document.getElementById('edit-user-id').value = doc.user_id;
    document.getElementById('edit-specialty-id').value = doc.specialty_id;
    document.getElementById('edit-fee').value = doc.consultation_fee;
    document.getElementById('edit-phone').value = doc.phone || '';
    document.getElementById('edit-bio').value = doc.bio || '';
    openModal('modal-edit-doctor');
}
</script>
