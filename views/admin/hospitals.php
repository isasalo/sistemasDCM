<?php
/**
 * views/admin/hospitals.php
 * Variables: $hospitals
 */
?>
<div class="page-header">
    <div>
        <h1>Sedes Hospitalarias</h1>
        <p>Gestiona los hospitales y centros médicos disponibles en Antioquia</p>
    </div>
    <button class="btn btn-primary" data-open-modal="modal-add-hospital">
        <i class="fa-solid fa-plus"></i> Añadir Hospital
    </button>
</div>

<div class="card">
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Hospital</th>
                    <th>Ubicación</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($hospitals)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:30px;color:#64748B">No hay hospitales registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($hospitals as $h): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:12px">
                                    <div style="width:36px;height:36px;background:var(--primary-light);color:var(--primary);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem">
                                        <i class="fa-solid fa-hospital"></i>
                                    </div>
                                    <div style="font-weight:600;color:var(--slate-800)"><?= htmlspecialchars($h['name']) ?></div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size:0.9rem;color:var(--slate-600)"><?= htmlspecialchars($h['address']) ?></div>
                                <div style="font-size:0.75rem;color:var(--slate-400);font-weight:500"><?= htmlspecialchars($h['city']) ?>, <?= htmlspecialchars($h['region']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($h['phone']) ?: '<span style="color:var(--slate-300)">No asignado</span>' ?></td>
                            <td>
                                <span class="badge badge-<?= $h['active'] ? 'success' : 'danger' ?>">
                                    <?= $h['active'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button class="action-edit" title="Editar" 
                                            onclick="editHospital(<?= htmlspecialchars(json_encode($h)) ?>)">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <form method="POST" action="index.php?module=admin&action=hospitals" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="hospital_id" value="<?= $h['id'] ?>">
                                        <button type="submit" class="action-delete" title="<?= $h['active'] ? 'Desactivar' : 'Activar' ?>" 
                                                style="background:<?= $h['active'] ? 'rgba(239, 68, 68, 0.1)' : 'rgba(34, 197, 94, 0.1)' ?>;color:<?= $h['active'] ? 'var(--danger)' : 'var(--success)' ?>">
                                            <i class="fa-solid fa-power-off"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="index.php?module=admin&action=hospitals" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="hospital_id" value="<?= $h['id'] ?>">
                                        <button type="submit" class="action-delete" title="Eliminar permanentemente" style="background:rgba(239, 68, 68, 0.15);color:var(--danger)" data-confirm="¿Estás seguro de que deseas eliminar permanentemente el hospital '<?= htmlspecialchars($h['name']) ?>'?">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Hospital -->
<div class="modal-overlay" id="modal-add-hospital">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-hospital-user" style="color:var(--primary);margin-right:8px"></i>Añadir Nuevo Hospital</h3>
            <button class="modal-close" data-close-modal="modal-add-hospital"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="index.php?module=admin&action=hospitals">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre del Hospital</label>
                    <input type="text" name="name" class="form-control" placeholder="Ej. Hospital San Vicente" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="address" class="form-control" placeholder="Calle, Carrera, Av..." required>
                </div>
                <div class="form-group">
                    <label class="form-label">Ciudad (Antioquia)</label>
                    <select name="city" class="form-control" required>
                        <option value="Medellín">Medellín</option>
                        <option value="Bello">Bello</option>
                        <option value="Itagüí">Itagüí</option>
                        <option value="Envigado">Envigado</option>
                        <option value="Rionegro">Rionegro</option>
                        <option value="Apartadó">Apartadó</option>
                        <option value="Turbo">Turbo</option>
                        <option value="Caucasia">Caucasia</option>
                        <option value="Sabaneta">Sabaneta</option>
                        <option value="La Estrella">La Estrella</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono de contacto</label>
                    <input type="text" name="phone" class="form-control" placeholder="Ej. 604 123 4567">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modal-add-hospital">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Hospital</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Hospital -->
<div class="modal-overlay" id="modal-edit-hospital">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen-to-square" style="color:var(--warning);margin-right:8px"></i>Editar Hospital</h3>
            <button class="modal-close" data-close-modal="modal-edit-hospital"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="index.php?module=admin&action=hospitals">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="hospital_id" id="edit-id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre del Hospital</label>
                    <input type="text" name="name" id="edit-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="address" id="edit-address" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Ciudad</label>
                    <select name="city" id="edit-city" class="form-control" required>
                        <option value="Medellín">Medellín</option>
                        <option value="Bello">Bello</option>
                        <option value="Itagüí">Itagüí</option>
                        <option value="Envigado">Envigado</option>
                        <option value="Rionegro">Rionegro</option>
                        <option value="Apartadó">Apartadó</option>
                        <option value="Turbo">Turbo</option>
                        <option value="Caucasia">Caucasia</option>
                        <option value="Sabaneta">Sabaneta</option>
                        <option value="La Estrella">La Estrella</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="phone" id="edit-phone" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modal-edit-hospital">Cancelar</button>
                <button type="submit" class="btn btn-warning" style="color:white">Actualizar Hospital</button>
            </div>
        </form>
    </div>
</div>

<script>
function editHospital(hospital) {
    document.getElementById('edit-id').value = hospital.id;
    document.getElementById('edit-name').value = hospital.name;
    document.getElementById('edit-address').value = hospital.address;
    document.getElementById('edit-city').value = hospital.city;
    document.getElementById('edit-phone').value = hospital.phone;
    openModal('modal-edit-hospital');
}
</script>
