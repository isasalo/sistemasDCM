<?php
/**
 * views/admin/patients.php
 */
?>
<div class="page-header">
    <div>
        <h1>Gestión de Pacientes</h1>
        <p>Administra los usuarios registrados como pacientes</p>
    </div>
    <button class="btn btn-primary" data-open-modal="modal-patient">
        <i class="fa-solid fa-user-plus"></i> Nuevo Paciente
    </button>
</div>

<div class="card">
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>F. Nacimiento</th>
                    <th>Tipo Sangre</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patients as $p): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div class="avatar" style="width:32px;height:32px;font-size:.8rem">
                                <?= mb_strtoupper(mb_substr($p['name'],0,1)) ?>
                            </div>
                            <span style="font-weight:600"><?= htmlspecialchars($p['name']) ?></span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($p['email']) ?></td>
                    <td><?= $p['birth_date'] ?></td>
                    <td><span class="badge badge-info"><?= $p['blood_type'] ?></span></td>
                    <td>
                        <span class="badge badge-<?= $p['active'] ? 'success' : 'danger' ?>">
                            <?= $p['active'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <button class="action-edit" title="Editar" 
                                onclick="editPatient(<?= htmlspecialchars(json_encode($p)) ?>)">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Nuevo/Editar Paciente -->
<div class="modal-overlay" id="modal-patient">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h3 id="modal-title">Registrar Paciente</h3>
            <button class="modal-close" data-close-modal="modal-patient"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="index.php?module=admin&action=patients">
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="user_id" id="patient-user-id">
            <input type="hidden" name="patient_id" id="patient-row-id">
            
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" name="name" id="p-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="p-email" class="form-control" required>
                </div>
                <div class="form-group" id="pw-group">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" id="p-pass" class="form-control" placeholder="Mínimo 6 caracteres">
                </div>
                <div class="grid-2" style="margin-top:16px">
                    <div class="form-group">
                        <label class="form-label">F. Nacimiento</label>
                        <input type="date" name="birth_date" id="p-birth" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo Sangre</label>
                        <select name="blood_type" id="p-blood" class="form-control" required>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modal-patient">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Paciente</button>
            </div>
        </form>
    </div>
</div>

<script>
function editPatient(p) {
    document.getElementById('modal-title').textContent = 'Editar Paciente';
    document.getElementById('form-action').value = 'edit';
    document.getElementById('patient-user-id').value = p.user_id;
    document.getElementById('patient-row-id').value = p.id;
    
    document.getElementById('p-name').value = p.name;
    document.getElementById('p-email').value = p.email;
    document.getElementById('p-birth').value = p.birth_date;
    document.getElementById('p-blood').value = p.blood_type;
    
    document.getElementById('pw-group').querySelector('label').textContent = 'Contraseña (dejar en blanco para no cambiar)';
    
    openModal('modal-patient');
}

// Reset modal on close
document.querySelectorAll('[data-close-modal="modal-patient"]').forEach(btn => {
    btn.addEventListener('click', () => {
        setTimeout(() => {
            document.getElementById('modal-title').textContent = 'Registrar Paciente';
            document.getElementById('form-action').value = 'create';
            document.getElementById('pw-group').querySelector('label').textContent = 'Contraseña';
            document.getElementById('p-pass').required = true;
            document.getElementById('login-form')?.reset();
        }, 400);
    });
});
</script>
