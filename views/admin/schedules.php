<?php
/**
 * views/admin/schedules.php
 * Variables: $doctors, $selected, $schedules, $flash
 */
$days_es = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 0 => 'Domingo'];
?>
<div class="page-header">
    <div>
        <h1>Gestión de Horarios Médicos</h1>
        <p>Configura las ventanas de atención para cada doctor</p>
    </div>
</div>

<div class="grid-1-3">
    <!-- Left: Doctor Selection -->
    <div>
        <div class="card" style="padding:15px">
            <h3 style="font-size:0.9rem; margin-bottom:15px; color:var(--gray-600)">Selecciona un Médico</h3>
            <div style="display:flex; flex-direction:column; gap:8px">
                <?php foreach ($doctors as $doc): ?>
                <a href="index.php?module=admin&action=schedules&doctor_id=<?= $doc['id'] ?>" 
                   class="appt-card <?= ($selected && $selected['id'] == $doc['id']) ? 'active-item' : '' ?>" 
                   style="text-decoration:none">
                    <div class="appt-date-block" style="background:var(--primary); width:35px; height:35px">
                        <i class="fa-solid fa-user-md" style="font-size:0.9rem"></i>
                    </div>
                    <div class="appt-info">
                        <div class="appt-doctor" style="font-size:0.85rem"><?= htmlspecialchars($doc['name']) ?></div>
                        <div class="appt-specialty" style="font-size:0.7rem"><?= htmlspecialchars($doc['specialty_name'] ?? 'N/A') ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right: Schedule Configuration -->
    <div>
        <?php if (!$selected): ?>
            <div class="card empty-state" style="height:100%; display:flex; flex-direction:column; justify-content:center">
                <i class="fa-solid fa-calendar-clock" style="font-size:4rem; opacity:0.1"></i>
                <h2>Selecciona un médico para configurar su horario</h2>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h3>Configuración de Horario: Dr. <?= htmlspecialchars($selected['name']) ?></h3>
                </div>
                <form method="POST" action="index.php?module=admin&action=schedules">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="doctor_id" value="<?= $selected['id'] ?>">

                    <div style="display:flex; flex-direction:column; gap:10px">
                        <?php foreach ([1, 2, 3, 4, 5, 6, 0] as $dayNum): ?>
                            <?php 
                            $sched = null;
                            foreach ($schedules as $s) {
                                if ($s['day_of_week'] == $dayNum) {
                                    $sched = $s;
                                    break;
                                }
                            }
                            ?>
                            <div class="schedule-day-row <?= !$sched ? 'disabled-row' : '' ?>" id="row-day-<?= $dayNum ?>">
                                <div class="day-toggle">
                                    <input type="checkbox" name="days[<?= $dayNum ?>][enabled]" id="chk-day-<?= $dayNum ?>" <?= $sched ? 'checked' : '' ?> onchange="toggleDayRow(<?= $dayNum ?>)">
                                    <label for="chk-day-<?= $dayNum ?>"><?= $days_es[$dayNum] ?></label>
                                    <input type="hidden" name="days[<?= $dayNum ?>][day]" value="<?= $dayNum ?>">
                                </div>
                                <div class="form-group" style="margin:0">
                                    <input type="time" name="days[<?= $dayNum ?>][start]" value="<?= $sched ? substr($sched['start_time'], 0, 5) : '08:00' ?>" class="form-control" <?= !$sched ? 'disabled' : '' ?>>
                                </div>
                                <div class="form-group" style="margin:0">
                                    <input type="time" name="days[<?= $dayNum ?>][end]" value="<?= $sched ? substr($sched['end_time'], 0, 5) : '17:00' ?>" class="form-control" <?= !$sched ? 'disabled' : '' ?>>
                                </div>
                                <div class="form-group" style="margin:0; display:flex; align-items:center; gap:5px">
                                    <input type="number" name="days[<?= $dayNum ?>][slot]" value="<?= $sched ? $sched['slot_duration'] : '30' ?>" class="form-control" style="width:70px" <?= !$sched ? 'disabled' : '' ?>>
                                    <span style="font-size:0.7rem; color:var(--gray-400)">min</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-footer" style="margin-top:20px; display:flex; justify-content:flex-end">
                        <button type="submit" class="btn btn-primary">Guardar Configuración de Horario</button>
                    </div>
                </form>
            </div>

            <div class="card" style="margin-top:20px; border-left:4px solid var(--info)">
                <p style="font-size:0.8rem; color:var(--gray-600)">
                    <i class="fa-solid fa-circle-info" style="color:var(--info)"></i> 
                    <strong>Importante:</strong> Los cambios en el horario no afectarán a las citas que ya han sido confirmadas. 
                    El sistema generará los slots disponibles basados en la duración configurada para cada día.
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleDayRow(day) {
    const row = document.getElementById('row-day-' + day);
    const chk = document.getElementById('chk-day-' + day);
    const inputs = row.querySelectorAll('input:not([type="checkbox"])');
    
    if (chk.checked) {
        row.classList.remove('disabled-row');
        inputs.forEach(i => i.disabled = false);
    } else {
        row.classList.add('disabled-row');
        inputs.forEach(i => i.disabled = true);
    }
}
</script>

<style>
.active-item { background: var(--primary-light) !important; border-color: var(--primary) !important; }
.schedule-day-row.disabled-row { background: var(--gray-50); }
</style>
