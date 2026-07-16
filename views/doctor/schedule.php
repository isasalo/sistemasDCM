<?php
/**
 * views/doctor/schedule.php
 * Variables: $doctor, $weekStart, $weekAppts, $notifCount
 */
$weekStart = $weekStart ?? date('Y-m-d');
$days_es = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
$weekDates = [];
for ($i = 0; $i < 7; $i++) {
    $weekDates[] = date('Y-m-d', strtotime($weekStart . " +$i days"));
}

$prevWeek = date('Y-m-d', strtotime($weekStart . " -7 days"));
$nextWeek = date('Y-m-d', strtotime($weekStart . " +7 days"));
?>
<div class="page-header">
    <div>
        <h1>Mi Agenda Semanal</h1>
        <p>Visualiza y gestiona tus citas para la semana del <?= date('d/m', strtotime($weekStart)) ?> al <?= date('d/m', strtotime($weekDates[6])) ?></p>
    </div>
    <div class="week-nav">
        <a href="index.php?module=doctor&action=schedule&week=<?= $prevWeek ?>" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-chevron-left"></i> Anterior
        </a>
        <span class="week-label"><?= date('F Y', strtotime($weekStart)) ?></span>
        <a href="index.php?module=doctor&action=schedule&week=<?= $nextWeek ?>" class="btn btn-secondary btn-sm">
            Siguiente <i class="fa-solid fa-chevron-right"></i>
        </a>
    </div>
</div>

<div class="card" style="padding:0; overflow:hidden">
    <div class="week-grid">
        <!-- Time Column Header -->
        <div class="week-header time-col">Hora</div>
        
        <!-- Day Headers -->
        <?php foreach ($weekDates as $i => $date): ?>
        <div class="week-header <?= $date === date('Y-m-d') ? 'today' : '' ?>">
            <div style="font-size:0.7rem; opacity:0.8"><?= $days_es[$i] ?></div>
            <div style="font-size:1.1rem"><?= date('d', strtotime($date)) ?></div>
        </div>
        <?php endforeach; ?>

        <!-- Time Rows (08:00 to 18:00) -->
        <?php for ($h = 8; $h <= 18; $h++): ?>
            <?php foreach (['00', '30'] as $m): ?>
                <?php $time = sprintf('%02d:%s', $h, $m); ?>
                <div class="week-hour-row">
                    <div class="week-time-cell"><?= $time ?></div>
                    
                    <?php foreach ($weekDates as $date): ?>
                        <div class="week-cell" style="cursor:pointer; position:relative" onclick="clickCell('<?= $date ?>', '<?= $time ?>')">
                            <?php if (isset($weekAppts[$date][$time])): ?>
                                <?php $appt = $weekAppts[$date][$time]; ?>
                                <div class="week-appt <?= $appt['status'] ?>" 
                                     title="<?= htmlspecialchars($appt['patient_name']) ?>: <?= htmlspecialchars($appt['reason']) ?>"
                                     style="cursor:pointer"
                                     onclick="event.stopPropagation(); manageAppointment(<?= htmlspecialchars(json_encode($appt)) ?>)">
                                    <?= htmlspecialchars(explode(' ', $appt['patient_name'])[0]) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endfor; ?>
    </div>
</div>

<!-- Modal: Gestionar Cita (Doctor) -->
<div class="modal-overlay" id="modal-manage-appt">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-calendar-check" style="color:var(--primary);margin-right:8px"></i>Detalles de la Cita</h3>
            <button class="modal-close" data-close-modal="modal-manage-appt"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div style="background:var(--gray-50); padding:16px; border-radius:var(--radius); margin-bottom:20px">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px">
                    <div>
                        <h4 id="manage-patient-name" style="font-size:1.1rem; font-weight:700; color:var(--gray-900)">-</h4>
                        <p id="manage-current-datetime" style="font-size:0.85rem; color:var(--gray-600); margin-top:4px">-</p>
                    </div>
                    <span id="manage-status-badge" class="badge">-</span>
                </div>
                <div style="border-top:1px solid var(--gray-200); padding-top:12px; margin-top:12px">
                    <p style="font-size:0.75rem; font-weight:700; text-transform:uppercase; color:var(--gray-500); margin-bottom:4px">Motivo de consulta</p>
                    <p id="manage-reason" style="font-size:0.88rem; color:var(--gray-800); line-height:1.4">-</p>
                </div>
            </div>

            <!-- Action buttons to toggle options -->
            <div id="manage-main-actions" style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:20px">
                <button type="button" class="btn btn-outline" onclick="toggleManageOption('reschedule')" style="justify-content:center">
                    <i class="fa-solid fa-calendar-arrow-up"></i> Reprogramar
                </button>
                <button type="button" class="btn btn-outline" onclick="toggleManageOption('cancel')" style="justify-content:center; color:var(--danger); border-color:var(--danger)">
                    <i class="fa-solid fa-ban"></i> Cancelar Cita
                </button>
            </div>
            
            <div id="manage-attend-action" style="margin-bottom:20px">
                <a href="#" id="manage-attend-btn" class="btn btn-primary" style="width:100%; justify-content:center">
                    <i class="fa-solid fa-hand-holding-medical"></i> Atender Consulta
                </a>
            </div>

            <!-- Option: Reschedule Form -->
            <form method="POST" action="api/appointments.php" id="manage-form-reschedule" style="display:none; border-top:1px solid var(--gray-100); padding-top:20px">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                <input type="hidden" name="action" value="reschedule">
                <input type="hidden" name="appointment_id" id="reschedule-appt-id">
                <input type="hidden" name="doctor_id" id="reschedule-doctor-id">
                
                <h4 style="font-size:0.9rem; font-weight:700; color:var(--gray-800); margin-bottom:15px">Reprogramar Cita</h4>
                
                <div class="form-group">
                    <label class="form-label">Nueva Fecha</label>
                    <input type="date" name="new_date" id="reschedule-date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nueva Hora</label>
                    <select name="new_time" id="reschedule-time" class="form-control" required>
                        <option value="">Selecciona una fecha primero</option>
                    </select>
                </div>
                
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:15px">
                    <button type="button" class="btn btn-secondary" onclick="toggleManageOption('main')">Volver</button>
                    <button type="submit" class="btn btn-primary">Confirmar Reprogramación</button>
                </div>
            </form>

            <!-- Option: Cancel Form -->
            <form method="POST" action="index.php?module=doctor&action=appointments" id="manage-form-cancel" style="display:none; border-top:1px solid var(--gray-100); padding-top:20px">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="appointment_id" id="cancel-appt-id">
                <input type="hidden" name="redirect_to" value="schedule">
                
                <h4 style="font-size:0.9rem; font-weight:700; color:var(--gray-800); margin-bottom:15px; color:var(--danger)">Cancelar Cita</h4>
                
                <div class="form-group">
                    <label class="form-label">Motivo de cancelación</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Indica el motivo de la cancelación para informar al paciente..." required></textarea>
                </div>
                
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:15px">
                    <button type="button" class="btn btn-secondary" onclick="toggleManageOption('main')">Volver</button>
                    <button type="submit" class="btn btn-danger">Confirmar Cancelación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function manageAppointment(appt) {
    document.getElementById('manage-patient-name').textContent = appt.patient_name;
    
    // Format Date & Time
    const dateParts = appt.appointment_date.split('-');
    const dateStr = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
    const timeStr = appt.appointment_time.slice(0, 5);
    document.getElementById('manage-current-datetime').innerHTML = `<i class="fa-regular fa-clock"></i> ${dateStr} a las ${timeStr}`;
    
    document.getElementById('manage-reason').textContent = appt.reason || 'Consulta general';
    
    const badge = document.getElementById('manage-status-badge');
    badge.className = 'badge badge-' + appt.status;
    const statusLabels = { pending: 'Pendiente', confirmed: 'Confirmada', cancelled: 'Cancelada', completed: 'Completada' };
    badge.textContent = statusLabels[appt.status] || appt.status;
    
    // Set IDs
    document.getElementById('reschedule-appt-id').value = appt.id;
    document.getElementById('reschedule-doctor-id').value = appt.doctor_id;
    document.getElementById('cancel-appt-id').value = appt.id;
    
    // Attend URL
    document.getElementById('manage-attend-btn').href = `index.php?module=doctor&action=patients&patient_id=${appt.patient_id}&appointment_id=${appt.id}`;
    
    // Show/hide actions based on status
    const canManage = ['pending', 'confirmed'].includes(appt.status);
    document.getElementById('manage-main-actions').style.display = canManage ? 'grid' : 'none';
    
    // Only show "Attend" button if appointment is confirmed
    document.getElementById('manage-attend-action').style.display = (appt.status === 'confirmed') ? 'block' : 'none';
    
    // Reset view
    toggleManageOption('main');
    
    openModal('modal-manage-appt');
}

function toggleManageOption(opt) {
    const main = document.getElementById('manage-main-actions');
    const reschedule = document.getElementById('manage-form-reschedule');
    const cancel = document.getElementById('manage-form-cancel');
    const attend = document.getElementById('manage-attend-action');
    const isConfirmed = document.getElementById('manage-status-badge').textContent === 'Confirmada';
    
    if (opt === 'main') {
        main.style.display = ['Pendiente', 'Confirmada'].includes(document.getElementById('manage-status-badge').textContent) ? 'grid' : 'none';
        reschedule.style.display = 'none';
        cancel.style.display = 'none';
        if (attend) attend.style.display = isConfirmed ? 'block' : 'none';
    } else {
        main.style.display = 'none';
        if (attend) attend.style.display = 'none';
        reschedule.style.display = opt === 'reschedule' ? 'block' : 'none';
        cancel.style.display = opt === 'cancel' ? 'block' : 'none';
    }
}

document.getElementById('reschedule-date').addEventListener('change', async function() {
    const date   = this.value;
    const docId  = document.getElementById('reschedule-doctor-id').value;
    const select = document.getElementById('reschedule-time');
    
    if (!date || !docId) return;
    
    select.innerHTML = '<option value="">Cargando horarios disponibles...</option>';
    
    try {
        const res = await fetch(`api/appointments.php?action=slots&date=${date}&doctor_id=${docId}`);
        const json = await res.json();
        
        if (json.success) {
            const { available, booked } = json.data;
            const free = available.filter(t => !booked.includes(t));
            
            if (free.length === 0) {
                select.innerHTML = '<option value="">No hay horarios disponibles para esta fecha</option>';
            } else {
                select.innerHTML = '<option value="">Selecciona una hora</option>';
                free.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t;
                    opt.textContent = t.slice(0, 5);
                    select.appendChild(opt);
                });
            }
        } else {
            select.innerHTML = '<option value="">Error al cargar horarios</option>';
        }
    } catch (err) {
        select.innerHTML = '<option value="">Error de conexión</option>';
    }
});

function clickCell(date, time) {
    document.getElementById('create-appt-date').value = date;
    document.getElementById('create-appt-time').value = time;
    
    // Format label
    const dateParts = date.split('-');
    const dateStr = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
    document.getElementById('create-appt-datetime-label').textContent = `${dateStr} a las ${time}`;
    
    openModal('modal-create-appt');
}
</script>

<!-- Modal: Crear Cita desde Agenda (Doctor) -->
<div class="modal-overlay" id="modal-create-appt">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-calendar-plus" style="color:var(--primary);margin-right:8px"></i>Agendar Cita en Agenda</h3>
            <button class="modal-close" data-close-modal="modal-create-appt"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="index.php?module=doctor&action=schedule">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="appointment_date" id="create-appt-date">
            <input type="hidden" name="appointment_time" id="create-appt-time">
            <input type="hidden" name="week_start" value="<?= htmlspecialchars($weekStart) ?>">
            
            <div class="modal-body">
                <div style="background:var(--primary-light); padding:12px; border-radius:var(--radius); margin-bottom:20px; color:var(--primary); font-size:0.9rem">
                    <i class="fa-regular fa-clock"></i> Horario seleccionado: <strong id="create-appt-datetime-label">-</strong>
                </div>

                <div class="form-group">
                    <label class="form-label">Seleccionar Paciente</label>
                    <select name="patient_id" class="form-control" required style="width:100%">
                        <option value="">-- Selecciona un paciente --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (CC: <?= htmlspecialchars($p['document_number'] ?? 'N/A') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Sede / Hospital de atención</label>
                    <select name="hospital_id" class="form-control" required>
                        <option value="">-- Selecciona una sede --</option>
                        <?php foreach ($hospitals as $h): ?>
                            <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['name']) ?> (<?= htmlspecialchars($h['city']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Motivo de consulta</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Ej: Control mensual, revisión de exámenes..." required></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modal-create-appt">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Agendar Cita</button>
            </div>
        </form>
    </div>
</div>

<style>
.week-header.today {
    background-color: var(--primary-dark);
    box-shadow: inset 0 -4px 0 var(--secondary);
}
.week-cell {
    border-right: 1px solid var(--gray-100);
    border-bottom: 1px solid var(--gray-100);
}
.week-time-cell {
    border-bottom: 1px solid var(--gray-200);
}
</style>
