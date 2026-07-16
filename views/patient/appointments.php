<?php
/**
 * views/patient/appointments.php
 * Variables: $appointments, $specialties, $patient
 */
$statusLabels = ['pending'=>'Pendiente','confirmed'=>'Confirmada','cancelled'=>'Cancelada','completed'=>'Completada','no_show'=>'No asistió'];
$months_es    = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
?>
<div class="page-header">
    <div>
        <h1>Mis Citas</h1>
        <p>Agenda, consulta y gestiona tus citas médicas</p>
    </div>
    <button class="btn btn-primary" data-open-modal="modal-book">
        <i class="fa-solid fa-calendar-plus"></i> Agendar Cita
    </button>
</div>

<div class="grid-3-1">
    <!-- Left: appointment list -->
    <div>
        <!-- Tabs -->
        <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
            <button class="btn btn-primary btn-sm tab-filter active" data-filter="all">Todas</button>
            <button class="btn btn-secondary btn-sm tab-filter" data-filter="pending">Pendientes</button>
            <button class="btn btn-secondary btn-sm tab-filter" data-filter="confirmed">Confirmadas</button>
            <button class="btn btn-secondary btn-sm tab-filter" data-filter="completed">Completadas</button>
            <button class="btn btn-secondary btn-sm tab-filter" data-filter="cancelled">Canceladas</button>
        </div>

        <div class="table-container">
            <table class="table" id="appt-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Doctor</th>
                        <th>Hospital</th>
                        <th>Especialidad</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($appointments)): ?>
                <tr><td colspan="6" style="text-align:center;padding:32px;color:#94A3B8">No tienes citas registradas aún.</td></tr>
                <?php else: ?>
                <?php foreach ($appointments as $appt): ?>
                <?php
                    $canCancel     = in_array($appt['status'], ['pending','confirmed']) && (strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']) - time()) > 86400;
                    $canReschedule = in_array($appt['status'], ['pending']);
                    $dateP         = explode('-', $appt['appointment_date']);
                    $timeStr       = substr($appt['appointment_time'], 0, 5);
                ?>
                <tr data-status="<?= $appt['status'] ?>">
                    <td><?= $dateP[2] ?> <?= $months_es[(int)$dateP[1]] ?> <?= $dateP[0] ?></td>
                    <td><?= $timeStr ?></td>
                    <td>Dr. <?= htmlspecialchars($appt['doctor_name']) ?></td>
                    <td><span style="font-size:0.85rem;color:var(--slate-600)"><i class="fa-solid fa-hospital-user" style="color:var(--primary);margin-right:4px"></i> <?= htmlspecialchars($appt['hospital_name'] ?? 'Sede Principal') ?></span></td>
                    <td><span class="spec-pill"><i class="fa-solid fa-stethoscope"></i> <?= htmlspecialchars($appt['specialty_name']) ?></span></td>
                    <td><span class="badge badge-<?= $appt['status'] ?>"><?= $statusLabels[$appt['status']] ?? $appt['status'] ?></span></td>
                    <td>
                        <div class="table-actions">
                            <?php if ($canCancel): ?>
                            <button class="action-delete" title="Cancelar" onclick="openCancelModal(<?= $appt['id'] ?>)">
                                <i class="fa-solid fa-ban"></i>
                            </button>
                            <?php endif; ?>
                            <?php if ($canReschedule): ?>
                            <button class="action-edit" title="Reprogramar"
                                data-reschedule="<?= $appt['id'] ?>"
                                data-doctor-id="<?= $appt['doctor_id'] ?>">
                                <i class="fa-solid fa-calendar-arrow-up"></i>
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
    </div>

    <!-- Right: mini calendar -->
    <div>
        <div class="card">
            <div class="card-header"><h3>Calendario</h3></div>
            <div id="calendar-container"></div>
        </div>
    </div>
</div>

<!-- ─── Modal: Book appointment ─────────────────────── -->
<div class="modal-overlay" id="modal-book">
    <div class="modal" style="max-width:680px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-calendar-plus" style="color:var(--primary);margin-right:8px"></i>Agendar Nueva Cita</h3>
            <button class="modal-close" data-close-modal="modal-book"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="index.php?module=patient&action=appointments" id="book-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <input type="hidden" name="action" value="book">
            <input type="hidden" name="doctor_id" id="hidden-doctor-id">
            <input type="hidden" name="appointment_date" id="hidden-date">
            <input type="hidden" name="appointment_time" id="hidden-time">

            <div class="modal-body">
                <!-- Step 1: Specialty -->
                <div class="form-group">
                    <label class="form-label">1. Especialidad</label>
                    <select name="specialty_id" id="specialty-select" class="form-control">
                        <option value="">Todas las especialidades</option>
                        <?php foreach ($specialties as $sp): ?>
                        <option value="<?= $sp['id'] ?>"><?= htmlspecialchars($sp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Step 2: Hospital -->
                <div class="form-group">
                    <label class="form-label">2. Sede (Antioquia)</label>
                    <select name="hospital_id" id="hospital-select" class="form-control" required>
                        <option value="">Selecciona una sede</option>
                        <?php foreach ($hospitals as $h): ?>
                        <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['name']) ?> (<?= htmlspecialchars($h['city']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Step 3: Calendar -->
                <div class="form-group">
                    <label class="form-label">3. Selecciona una fecha</label>
                    <div id="calendar-container-modal" style="background:var(--gray-50);border-radius:var(--radius-sm);padding:16px"></div>
                </div>

                <!-- Step 3: Doctors -->
                <div class="form-group" id="appt-date-section" style="display:none">
                    <label class="form-label">4. Doctor disponible</label>
                    <div id="doctors-section"></div>
                </div>

                <!-- Step 5: Time slots -->
                <div class="form-group hidden" id="appt-slots-container">
                    <label class="form-label">5. Hora de la cita</label>
                    <div id="slots-grid-container"></div>
                </div>

                <!-- Reason -->
                <div class="form-group">
                    <label class="form-label">Motivo de la consulta</label>
                    <textarea name="reason" class="form-control" rows="2" placeholder="Describe brevemente el motivo de tu visita..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modal-book">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="book-confirm-btn">
                    <i class="fa-solid fa-check"></i> Confirmar Cita
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ─── Modal: Cancel appointment ────────────────────── -->
<div class="modal-overlay" id="modal-cancel">
    <div class="modal" style="max-width:420px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-ban" style="color:var(--danger);margin-right:8px"></i>Cancelar Cita</h3>
            <button class="modal-close" data-close-modal="modal-cancel"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="index.php?module=patient&action=appointments">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="appointment_id" id="cancel-appt-id">
            <div class="modal-body">
                <p style="margin-bottom:16px;color:var(--gray-700)">¿Estás seguro de que deseas cancelar esta cita?</p>
                <div class="form-group">
                    <label class="form-label">Motivo de cancelación</label>
                    <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="Indica el motivo (opcional)..."></textarea>
                </div>
                <p style="font-size:.78rem;color:var(--gray-600)"><i class="fa-solid fa-circle-info" style="color:var(--info)"></i> Solo puedes cancelar citas con más de 24 horas de anticipación.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modal-cancel">Volver</button>
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Cancelar Cita</button>
            </div>
        </form>
    </div>
</div>

<!-- ─── Modal: Reschedule ─────────────────────────────── -->
<div class="modal-overlay" id="modal-reschedule">
    <div class="modal" style="max-width:420px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-calendar-arrow-up" style="color:var(--warning);margin-right:8px"></i>Reprogramar Cita</h3>
            <button class="modal-close" data-close-modal="modal-reschedule"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="api/appointments.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <input type="hidden" name="action" value="reschedule">
            <input type="hidden" name="appointment_id" id="reschedule-appt-id">
            <input type="hidden" name="doctor_id" id="reschedule-doctor-id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nueva fecha</label>
                    <input type="date" name="new_date" id="reschedule-date" class="form-control"
                        min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                </div>
                <div class="form-group" id="reschedule-slots-container">
                    <label class="form-label">Nueva hora</label>
                    <select name="new_time" id="reschedule-time" class="form-control" required>
                        <option value="">Selecciona una fecha primero</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modal-reschedule">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Reprogramar</button>
            </div>
        </form>
    </div>
</div>

<script>
// Expose booked dates for calendar
window.BOOKED_DATES = <?= json_encode(array_column($appointments ?? [], 'appointment_date')) ?>;

// Init calendar in modal
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('calendar-container-modal')) {
        const cal = Object.create(AppointmentCalendar);
        AppointmentCalendar.init({ containerId: 'calendar-container-modal', bookedDates: window.BOOKED_DATES });
    }
    if (document.getElementById('calendar-container')) {
        AppointmentCalendar.init({ bookedDates: window.BOOKED_DATES });
    }
});

// Show appt-date-section when date selected
document.addEventListener('DOMContentLoaded', () => {
    const obs = new MutationObserver(() => {
        const sec = document.getElementById('appt-date-section');
        if (document.getElementById('hidden-date')?.value) sec.style.display = 'block';
    });
    const hd = document.getElementById('hidden-date');
    if (hd) obs.observe(hd, { attributes: true, attributeFilter: ['value'] });
});

function openCancelModal(id) {
    document.getElementById('cancel-appt-id').value = id;
    openModal('modal-cancel');
}

// Tab filter for appointments table
document.querySelectorAll('.tab-filter').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-filter').forEach(b => { b.classList.remove('btn-primary'); b.classList.add('btn-secondary'); });
        btn.classList.add('btn-primary'); btn.classList.remove('btn-secondary');
        const filter = btn.dataset.filter;
        document.querySelectorAll('#appt-table tbody tr').forEach(row => {
            row.style.display = (filter === 'all' || row.dataset.status === filter) ? '' : 'none';
        });
    });
});
</script>
