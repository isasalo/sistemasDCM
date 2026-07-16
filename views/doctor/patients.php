<?php
/**
 * views/doctor/patients.php
 * Variables: $doctor, $patients, $selected, $records, $activeRx, $notifCount
 */
?>
<div class="page-header">
    <div>
        <h1>Gestión de Pacientes</h1>
        <p>Consulta historiales, realiza diagnósticos y prescribe medicamentos</p>
    </div>
</div>

<div class="grid-1-3">
    <!-- Left: Patient Search & List -->
    <div>
        <div class="card" style="padding:15px">
            <form method="GET" action="index.php" class="search-bar" style="max-width:100%; margin-bottom:15px">
                <input type="hidden" name="module" value="doctor">
                <input type="hidden" name="action" value="patients">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" placeholder="Buscar por nombre o DNI..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </form>

            <div style="max-height:600px; overflow-y:auto; display:flex; flex-direction:column; gap:8px">
                <?php if (empty($patients)): ?>
                    <p style="text-align:center; color:var(--gray-400); font-size:0.8rem">No se encontraron pacientes.</p>
                <?php else: ?>
                    <?php foreach ($patients as $p): ?>
                    <a href="index.php?module=doctor&action=patients&patient_id=<?= $p['id'] ?><?= !empty($_GET['appointment_id']) ? '&appointment_id='.$_GET['appointment_id'] : '' ?>" 
                       class="appt-card <?= ($selected && $selected['id'] == $p['id']) ? 'active-item' : '' ?>" 
                       style="text-decoration:none; border-left:3px solid <?= ($selected && $selected['id'] == $p['id']) ? 'var(--primary)' : 'transparent' ?>">
                        <div class="appt-date-block" style="background:<?= ($selected && $selected['id'] == $p['id']) ? 'var(--primary)' : 'var(--gray-400)' ?>; width:40px; height:40px">
                            <?= mb_strtoupper(mb_substr($p['name'], 0, 1)) ?>
                        </div>
                        <div class="appt-info">
                            <div class="appt-doctor" style="font-size:0.85rem"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="appt-specialty" style="font-size:0.7rem">DNI: <?= htmlspecialchars($p['dni'] ?? '—') ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right: Patient Detail & Clinical Form -->
    <div data-tab-group="patient-mgmt">
        <?php if (!$selected): ?>
            <div class="card empty-state" style="height:100%; display:flex; flex-direction:column; justify-content:center">
                <i class="fa-solid fa-user-injured" style="font-size:4rem; margin-bottom:20px; opacity:0.1"></i>
                <h2>Selecciona un paciente</h2>
                <p>Busca y selecciona un paciente de la lista para ver su información o crear un registro clínico.</p>
            </div>
        <?php else: ?>
            <!-- Patient Header -->
            <div class="patient-ficha" style="margin-bottom:20px">
                <div class="patient-ficha-header">
                    <div class="patient-ficha-avatar"><?= mb_strtoupper(mb_substr($selected['name'], 0, 1)) ?></div>
                    <div>
                        <div class="patient-ficha-name"><?= htmlspecialchars($selected['name']) ?></div>
                        <div class="patient-ficha-meta">
                            <?= $selected['age'] ?? '—' ?> años • <?= htmlspecialchars(($selected['gender'] ?? '') === 'male' ? 'Masculino' : (($selected['gender'] ?? '') === 'female' ? 'Femenino' : 'No especificado')) ?> • Sangre: <?= htmlspecialchars($selected['blood_type'] ?? '—') ?>
                        </div>
                    </div>
                </div>
                <div class="patient-ficha-body">
                    <div class="ficha-grid">
                        <div class="ficha-field"><label>Alergias</label><p><?= htmlspecialchars(($selected['allergies'] ?? '') ?: 'Ninguna conocida') ?></p></div>
                        <div class="ficha-field"><label>Condiciones</label><p><?= htmlspecialchars(($selected['chronic_conditions'] ?? '') ?: 'Ninguna registrada') ?></p></div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tabs" data-tabs="patient-mgmt">
                <button class="tab-btn active" data-tab="tab-new-record">Nuevo Registro</button>
                <button class="tab-btn" data-tab="tab-history">Historial</button>
                <button class="tab-btn" data-tab="tab-rx">Recetas Activas</button>
            </div>

            <!-- Tab: New Record -->
            <div class="tab-content active" id="tab-new-record">
                <div class="card">
                    <form method="POST" action="index.php?module=doctor&action=patients&patient_id=<?= $selected['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="create_record">
                        <input type="hidden" name="patient_id" value="<?= $selected['id'] ?>">
                        <input type="hidden" name="appointment_id" value="<?= $_GET['appointment_id'] ?? '' ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Tipo de atención</label>
                                <select name="record_type" class="form-control">
                                    <option value="consultation">Consulta General</option>
                                    <option value="follow_up">Seguimiento</option>
                                    <option value="exam">Revisión de Exámenes</option>
                                    <option value="urgency">Urgencia</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Presión Arterial</label>
                                <input type="text" name="blood_pressure" class="form-control" placeholder="120/80">
                            </div>
                        </div>

                        <div class="form-row cols-3">
                            <div class="form-group"><label class="form-label">Frec. Cardíaca</label><input type="number" name="heart_rate" class="form-control" placeholder="bpm"></div>
                            <div class="form-group"><label class="form-label">Temperatura</label><input type="number" step="0.1" name="temperature" class="form-control" placeholder="°C"></div>
                            <div class="form-group"><label class="form-label">Peso (kg)</label><input type="number" step="0.1" name="weight" class="form-control"></div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Diagnóstico</label>
                            <textarea name="diagnosis" class="form-control" rows="3" required placeholder="Escribe el diagnóstico médico..."></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tratamiento y Plan</label>
                            <textarea name="treatment" class="form-control" rows="3" placeholder="Pasos a seguir por el paciente..."></textarea>
                        </div>

                        <hr style="margin:20px 0; border:0; border-top:1px solid var(--gray-100)">
                        
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px">
                            <h4 style="font-size:0.9rem; color:var(--gray-700)">Prescribir Medicamentos</h4>
                            <button type="button" class="btn btn-sm btn-outline" id="add-med-btn"><i class="fa-solid fa-plus"></i> Añadir</button>
                        </div>

                        <div id="med-rows-container">
                            <!-- JS will inject rows here -->
                        </div>

                        <div class="form-group" style="margin-top:15px">
                            <label class="form-label">Instrucciones de la Receta</label>
                            <textarea name="rx_instructions" class="form-control" rows="2" placeholder="Instrucciones generales para la receta..."></textarea>
                        </div>

                        <div class="form-footer" style="margin-top:25px; display:flex; justify-content:flex-end; gap:10px">
                            <button type="reset" class="btn btn-secondary">Limpiar</button>
                            <button type="submit" class="btn btn-primary">Guardar Registro Clínico</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab: History -->
            <div class="tab-content" id="tab-history">
                <?php if (empty($records)): ?>
                    <div class="empty-state card"><p>No hay registros previos.</p></div>
                <?php else: ?>
                    <?php foreach ($records as $rec): ?>
                    <div class="card" style="margin-bottom:10px; border-left:4px solid var(--primary)">
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px">
                            <span class="badge badge-info"><?= ucfirst($rec['record_type']) ?></span>
                            <span style="font-size:0.75rem; color:var(--gray-400)"><?= date('d/m/Y', strtotime($rec['created_at'])) ?></span>
                        </div>
                        <p style="font-weight:600; font-size:0.85rem">Diagnóstico:</p>
                        <p style="font-size:0.8rem; color:var(--gray-700); margin-bottom:8px"><?= nl2br(htmlspecialchars($rec['diagnosis'])) ?></p>
                        <?php if ($rec['treatment']): ?>
                            <p style="font-weight:600; font-size:0.85rem">Tratamiento:</p>
                            <p style="font-size:0.8rem; color:var(--gray-600)"><?= nl2br(htmlspecialchars($rec['treatment'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Tab: Rx -->
            <div class="tab-content" id="tab-rx">
                <?php if (empty($activeRx)): ?>
                    <div class="empty-state card"><p>No hay recetas activas.</p></div>
                <?php else: ?>
                    <?php foreach ($activeRx as $rx): ?>
                    <div class="rx-card" style="margin-bottom:15px; padding:15px">
                        <div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--gray-100); padding-bottom:10px; margin-bottom:10px">
                            <span style="font-weight:700; color:var(--primary)">Receta #<?= $rx['id'] ?></span>
                            <span style="font-size:0.75rem">Vence: <?= $rx['expiry_date'] ?: 'N/A' ?></span>
                        </div>
                        <ul style="font-size:0.8rem; color:var(--gray-700); padding-left:20px">
                            <?php foreach ($rx['medications'] as $med): ?>
                            <li><strong><?= htmlspecialchars($med['name']) ?></strong>: <?= htmlspecialchars($med['dose']) ?> c/<?= htmlspecialchars($med['frequency']) ?> por <?= htmlspecialchars($med['duration']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.active-item { background: var(--primary-light) !important; border-color: var(--primary) !important; }
</style>
