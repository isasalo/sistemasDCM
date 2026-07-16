<?php
/**
 * views/patient/health_info.php
 * Variables: $records, $prescriptions, $vitalHistory, $patient
 */
$statusLabels = ['active'=>'Activa','completed'=>'Completada','cancelled'=>'Cancelada'];
$typeLabels   = ['consultation'=>'Consulta','surgery'=>'Cirugía','exam'=>'Examen','urgency'=>'Urgencia','general'=>'General'];
?>
<div class="page-header">
    <div>
        <h1>Mi Salud</h1>
        <p>Historial clínico, recetas activas y signos vitales</p>
    </div>
</div>

<div class="grid-3-1">
    <div data-tab-group="health">
        <!-- Tabs -->
        <div class="tabs" data-tabs="health">
            <button class="tab-btn active" data-tab="tab-records">Historial Clínico</button>
            <button class="tab-btn" data-tab="tab-rx">Recetas</button>
        </div>

        <!-- Records -->
        <div class="tab-content active" id="tab-records">
            <?php if (empty($records)): ?>
            <div class="empty-state card">
                <i class="fa-regular fa-folder-open"></i>
                <p>No tienes registros clínicos aún.</p>
            </div>
            <?php else: ?>
            <?php foreach ($records as $rec): ?>
            <div class="card" style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
                    <div>
                        <span class="badge badge-info"><?= $typeLabels[$rec['record_type']] ?? $rec['record_type'] ?></span>
                        <p style="margin-top:6px;font-size:.8rem;color:var(--gray-600)">
                            Dr. <?= htmlspecialchars($rec['doctor_name']) ?> — <?= htmlspecialchars($rec['specialty_name']) ?>
                            &nbsp;|&nbsp; <?= date('d/m/Y', strtotime($rec['created_at'])) ?>
                        </p>
                    </div>
                </div>
                <?php if ($rec['diagnosis']): ?>
                <div style="margin-bottom:10px">
                    <p style="font-size:.75rem;font-weight:700;text-transform:uppercase;color:var(--gray-600);letter-spacing:.05em">Diagnóstico</p>
                    <p style="font-size:.88rem;color:var(--gray-900)"><?= nl2br(htmlspecialchars($rec['diagnosis'])) ?></p>
                </div>
                <?php endif; ?>
                <?php if ($rec['treatment']): ?>
                <div style="margin-bottom:10px">
                    <p style="font-size:.75rem;font-weight:700;text-transform:uppercase;color:var(--gray-600);letter-spacing:.05em">Tratamiento</p>
                    <p style="font-size:.88rem;color:var(--gray-900)"><?= nl2br(htmlspecialchars($rec['treatment'])) ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($rec['vital_signs'])): ?>
                <?php $vs = is_array($rec['vital_signs']) ? $rec['vital_signs'] : json_decode($rec['vital_signs'], true); ?>
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;padding-top:10px;border-top:1px solid var(--gray-100)">
                    <?php if (!empty($vs['blood_pressure'])): ?><span class="badge badge-info"><i class="fa-solid fa-heart-pulse"></i> <?= htmlspecialchars($vs['blood_pressure']) ?> mmHg</span><?php endif; ?>
                    <?php if (!empty($vs['heart_rate'])): ?><span class="badge badge-pending"><i class="fa-solid fa-wave-square"></i> <?= htmlspecialchars($vs['heart_rate']) ?> bpm</span><?php endif; ?>
                    <?php if (!empty($vs['temperature'])): ?><span class="badge badge-warning"><i class="fa-solid fa-temperature-half"></i> <?= htmlspecialchars($vs['temperature']) ?> °C</span><?php endif; ?>
                    <?php if (!empty($vs['weight'])): ?><span class="badge badge-completed"><i class="fa-solid fa-weight-scale"></i> <?= htmlspecialchars($vs['weight']) ?> kg</span><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Prescriptions -->
        <div class="tab-content" id="tab-rx">
            <?php if (empty($prescriptions)): ?>
            <div class="empty-state card"><i class="fa-solid fa-pills"></i><p>No tienes recetas registradas.</p></div>
            <?php else: ?>
            <?php foreach ($prescriptions as $rx): ?>
            <div class="rx-card" style="margin-bottom:16px">
                <div class="rx-header">
                    <div class="rx-logo"><i class="fa-solid fa-heart-pulse"></i> Tu Salud Primero</div>
                    <div class="rx-date">
                        <span class="badge badge-<?= $rx['status'] === 'active' ? 'confirmed' : 'cancelled' ?>"><?= $statusLabels[$rx['status']] ?></span><br>
                        <small><?= date('d/m/Y', strtotime($rx['issue_date'])) ?></small>
                    </div>
                </div>
                <div class="rx-patient-info">
                    <p><strong>Médico:</strong> Dr. <?= htmlspecialchars($rx['doctor_name']) ?></p>
                    <?php if ($rx['expiry_date']): ?><p><strong>Válida hasta:</strong> <?= date('d/m/Y', strtotime($rx['expiry_date'])) ?></p><?php endif; ?>
                </div>
                <ol class="rx-med-list">
                    <?php foreach ($rx['medications'] as $med): ?>
                    <li>
                        <div class="rx-med-name"><?= htmlspecialchars($med['name']) ?> — <?= htmlspecialchars($med['dose']) ?></div>
                        <div class="rx-med-detail"><?= htmlspecialchars($med['frequency']) ?> por <?= htmlspecialchars($med['duration']) ?></div>
                    </li>
                    <?php endforeach; ?>
                </ol>
                <?php if (!empty($rx['instructions'])): ?>
                <p style="margin-top:12px;font-size:.82rem;color:var(--gray-600);padding:10px;background:var(--gray-50);border-radius:6px">
                    <i class="fa-solid fa-circle-info" style="color:var(--info)"></i> <?= htmlspecialchars($rx['instructions']) ?>
                </p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: vitals chart -->
    <div>
        <div class="vitals-card">
            <div class="card-header"><h3><i class="fa-solid fa-chart-line" style="color:var(--primary);margin-right:8px"></i>Signos Vitales</h3></div>
            <?php if (!empty($vitalHistory)): ?>
            <canvas id="vitals-chart" style="width:100%;height:200px"></canvas>
            <div style="margin-top:16px;display:flex;flex-direction:column;gap:8px">
                <?php $last = end($vitalHistory); $vs = $last['vital_signs'] ?? []; ?>
                <?php if (!empty($vs['heart_rate'])): ?>
                <div class="vital-row"><div class="vital-icon"><i class="fa-solid fa-heart-pulse"></i></div><div><div class="vital-label">Frecuencia cardíaca</div><div class="vital-value"><?= $vs['heart_rate'] ?> bpm</div></div></div>
                <?php endif; ?>
                <?php if (!empty($vs['blood_pressure'])): ?>
                <div class="vital-row"><div class="vital-icon"><i class="fa-solid fa-gauge-high"></i></div><div><div class="vital-label">Presión arterial</div><div class="vital-value"><?= $vs['blood_pressure'] ?></div></div></div>
                <?php endif; ?>
                <?php if (!empty($vs['temperature'])): ?>
                <div class="vital-row"><div class="vital-icon"><i class="fa-solid fa-temperature-half"></i></div><div><div class="vital-label">Temperatura</div><div class="vital-value"><?= $vs['temperature'] ?> °C</div></div></div>
                <?php endif; ?>
                <?php if (!empty($vs['weight'])): ?>
                <div class="vital-row"><div class="vital-icon"><i class="fa-solid fa-weight-scale"></i></div><div><div class="vital-label">Peso</div><div class="vital-value"><?= $vs['weight'] ?> kg</div></div></div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="empty-state"><i class="fa-solid fa-chart-line"></i><p>No hay datos de signos vitales aún.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($vitalHistory)): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const history = <?= json_encode($vitalHistory) ?>;
    const labels  = history.map(h => h.created_at.slice(0,10));
    const hr      = history.map(h => h.vital_signs?.heart_rate   ?? 0);
    const temp    = history.map(h => (h.vital_signs?.temperature ?? 0) * 10); // scale for visibility
    const weight  = history.map(h => h.vital_signs?.weight       ?? 0);

    renderVitalChart('vitals-chart', labels, [
        { values: hr,     color: '#E74C3C', label: 'FC (bpm)' },
        { values: weight, color: '#0A6EBD', label: 'Peso (kg)' },
    ]);
});
</script>
<?php endif; ?>
