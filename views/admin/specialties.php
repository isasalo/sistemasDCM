<?php
/**
 * views/admin/specialties.php
 * Variables: $specialties
 */
?>
<div class="page-header">
    <div>
        <h1>Especialidades Médicas</h1>
        <p>Gestiona el catálogo de especialidades disponibles para la asignación de médicos y citas</p>
    </div>
    <button class="btn btn-primary" data-open-modal="modal-add-specialty">
        <i class="fa-solid fa-plus"></i> Añadir Especialidad
    </button>
</div>

<div class="card">
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Especialidad</th>
                    <th>Descripción</th>
                    <th>Ícono</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($specialties)): ?>
                    <tr><td colspan="4" style="text-align:center;padding:30px;color:#64748B">No hay especialidades registradas.</td></tr>
                <?php else: ?>
                    <?php foreach ($specialties as $sp): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:12px">
                                    <div style="width:36px;height:36px;background:var(--primary-light);color:var(--primary);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem">
                                        <i class="fa-solid <?= htmlspecialchars($sp['icon'] ?? 'fa-stethoscope') ?>"></i>
                                    </div>
                                    <div style="font-weight:600;color:var(--slate-800)"><?= htmlspecialchars($sp['name']) ?></div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size:0.9rem;color:var(--slate-600);max-width:400px;white-space:normal;word-wrap:break-word;line-height:1.4">
                                    <?= htmlspecialchars($sp['description']) ?: '<span style="color:var(--slate-300);font-style:italic">Sin descripción</span>' ?>
                                </div>
                            </td>
                            <td>
                                <code style="background:var(--gray-100);padding:4px 8px;border-radius:4px;font-size:0.8rem;color:var(--primary)">
                                    <?= htmlspecialchars($sp['icon'] ?? 'fa-stethoscope') ?>
                                </code>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button class="action-edit" title="Editar" 
                                            onclick="editSpecialty(<?= htmlspecialchars(json_encode($sp)) ?>)">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <form method="POST" action="index.php?module=admin&action=specialties" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="specialty_id" value="<?= $sp['id'] ?>">
                                        <button type="submit" class="action-delete" title="Eliminar" 
                                                style="background:rgba(239, 68, 68, 0.15);color:var(--danger)"
                                                onclick="return confirm('¿Estás seguro de que deseas eliminar la especialidad «<?= htmlspecialchars($sp['name'], ENT_QUOTES) ?>»?');">
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

<!-- Modal: Add Specialty -->
<div class="modal-overlay" id="modal-add-specialty">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-stethoscope" style="color:var(--primary);margin-right:8px"></i>Añadir Nueva Especialidad</h3>
            <button class="modal-close" data-close-modal="modal-add-specialty"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="index.php?module=admin&action=specialties">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre de la Especialidad</label>
                    <input type="text" name="name" class="form-control" placeholder="Ej. Dermatología" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Ej. Diagnóstico y tratamiento de afecciones de la piel..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Ícono Representativo</label>
                    <select name="icon" class="form-control" onchange="toggleCustomIcon(this, 'add-custom-icon-group')" required>
                        <option value="fa-stethoscope">Estetoscopio (General)</option>
                        <option value="fa-brain">Cerebro (Psicología/Psiquiatría)</option>
                        <option value="fa-venus">Símbolo Femenino (Ginecología)</option>
                        <option value="fa-apple-whole">Manzana (Nutrición)</option>
                        <option value="fa-heart-pulse">Corazón (Cardiología)</option>
                        <option value="fa-child">Niño (Pediatría)</option>
                        <option value="fa-eye">Ojo (Oftalmología)</option>
                        <option value="fa-tooth">Diente (Odontología)</option>
                        <option value="fa-bone">Hueso (Traumatología)</option>
                        <option value="fa-pills">Píldoras (Farmacia/Medicina)</option>
                        <option value="custom">Otro ícono (Clase personalizada)...</option>
                    </select>
                </div>
                <div class="form-group" id="add-custom-icon-group" style="display:none">
                    <label class="form-label">Clase de ícono FontAwesome 6</label>
                    <input type="text" name="custom_icon" class="form-control" placeholder="Ej. fa-hand-dots" oninput="syncCustomIcon(this, 'modal-add-specialty')">
                    <p style="font-size:0.75rem;color:var(--slate-400);margin-top:4px">Escribe la clase del ícono (sin incluir "fa-solid"). Ver listado en fontawesome.com</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modal-add-specialty">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Especialidad</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Specialty -->
<div class="modal-overlay" id="modal-edit-specialty">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen-to-square" style="color:var(--warning);margin-right:8px"></i>Editar Especialidad</h3>
            <button class="modal-close" data-close-modal="modal-edit-specialty"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="index.php?module=admin&action=specialties">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="specialty_id" id="edit-id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre de la Especialidad</label>
                    <input type="text" name="name" id="edit-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" id="edit-description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Ícono Representativo</label>
                    <select name="icon" id="edit-icon-select" class="form-control" onchange="toggleCustomIcon(this, 'edit-custom-icon-group')" required>
                        <option value="fa-stethoscope">Estetoscopio (General)</option>
                        <option value="fa-brain">Cerebro (Psicología/Psiquiatría)</option>
                        <option value="fa-venus">Símbolo Femenino (Ginecología)</option>
                        <option value="fa-apple-whole">Manzana (Nutrición)</option>
                        <option value="fa-heart-pulse">Corazón (Cardiología)</option>
                        <option value="fa-child">Niño (Pediatría)</option>
                        <option value="fa-eye">Ojo (Oftalmología)</option>
                        <option value="fa-tooth">Diente (Odontología)</option>
                        <option value="fa-bone">Hueso (Traumatología)</option>
                        <option value="fa-pills">Píldoras (Farmacia/Medicina)</option>
                        <option value="custom">Otro ícono (Clase personalizada)...</option>
                    </select>
                </div>
                <div class="form-group" id="edit-custom-icon-group" style="display:none">
                    <label class="form-label">Clase de ícono FontAwesome 6</label>
                    <input type="text" name="custom_icon" id="edit-custom-icon" class="form-control" placeholder="Ej. fa-hand-dots" oninput="syncCustomIcon(this, 'modal-edit-specialty')">
                    <p style="font-size:0.75rem;color:var(--slate-400);margin-top:4px">Escribe la clase del ícono (sin incluir "fa-solid"). Ver listado en fontawesome.com</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modal-edit-specialty">Cancelar</button>
                <button type="submit" class="btn btn-warning" style="color:white">Actualizar Especialidad</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleCustomIcon(selectEl, customGroupId) {
    const customGroup = document.getElementById(customGroupId);
    const customInput = customGroup.querySelector('input');
    if (selectEl.value === 'custom') {
        customGroup.style.display = 'block';
        customInput.required = true;
    } else {
        customGroup.style.display = 'none';
        customInput.required = false;
        customInput.value = '';
    }
}

function syncCustomIcon(inputEl, modalId) {
    const modal = document.getElementById(modalId);
    const select = modal.querySelector('select[name="icon"]');
    // Si escribe algo, nos aseguramos que el valor seleccionado en el select secundario sea 'custom'
    if (inputEl.value.trim() !== '') {
        select.value = 'custom';
    }
}

function editSpecialty(sp) {
    document.getElementById('edit-id').value = sp.id;
    document.getElementById('edit-name').value = sp.name;
    document.getElementById('edit-description').value = sp.description || '';
    
    const select = document.getElementById('edit-icon-select');
    const customGroup = document.getElementById('edit-custom-icon-group');
    const customInput = document.getElementById('edit-custom-icon');
    
    // Buscar si el ícono está en las opciones por defecto
    let optionExists = false;
    for (let i = 0; i < select.options.length; i++) {
        if (select.options[i].value === sp.icon) {
            optionExists = true;
            break;
        }
    }
    
    if (optionExists) {
        select.value = sp.icon;
        customGroup.style.display = 'none';
        customInput.value = '';
        customInput.required = false;
    } else {
        select.value = 'custom';
        customGroup.style.display = 'block';
        customInput.value = sp.icon || '';
        customInput.required = true;
    }
    
    openModal('modal-edit-specialty');
}

// Interceptar envíos de formulario para inyectar el ícono customizado si aplica
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const select = this.querySelector('select[name="icon"]');
        if (select && select.value === 'custom') {
            const customInput = this.querySelector('input[name="custom_icon"]');
            if (customInput && customInput.value.trim() !== '') {
                // Remplazar el valor temporal 'custom' del select con el valor del input para que PHP lo reciba directamente
                const tempOption = document.createElement('option');
                tempOption.value = customInput.value.trim();
                tempOption.text = customInput.value.trim();
                tempOption.selected = true;
                select.appendChild(tempOption);
            }
        }
    });
});
</script>
