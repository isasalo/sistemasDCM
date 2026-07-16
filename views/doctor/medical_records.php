<?php
/**
 * views/doctor/medical_records.php
 * Variables: $doctor, $records, $notifCount
 */
?>
<div class="page-header">
    <div>
        <h1>Archivo Historial Clínico</h1>
        <p>Consulta todos los registros médicos realizados por el equipo</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="search-bar" style="max-width:300px">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="record-search" placeholder="Filtrar por paciente o diagnóstico...">
        </div>
    </div>
    <div class="table-container">
        <table class="table" id="records-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Paciente</th>
                    <th>Doctor</th>
                    <th>Tipo</th>
                    <th>Diagnóstico</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:30px">No hay registros clínicos disponibles.</td></tr>
                <?php else: ?>
                    <?php foreach ($records as $rec): ?>
                    <tr>
                        <td style="font-size:0.8rem"><?= date('d/m/Y', strtotime($rec['created_at'])) ?></td>
                        <td style="font-weight:600"><?= htmlspecialchars($rec['patient_name']) ?></td>
                        <td style="font-size:0.8rem">Dr. <?= htmlspecialchars($rec['doctor_name']) ?></td>
                        <td><span class="badge badge-info"><?= ucfirst($rec['record_type']) ?></span></td>
                        <td style="font-size:0.8rem; max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap" title="<?= htmlspecialchars($rec['diagnosis']) ?>">
                            <?= htmlspecialchars($rec['diagnosis']) ?>
                        </td>
                        <td>
                            <div class="table-actions">
                                <button class="action-view" title="Ver Detalle Completos" onclick="openRecordDetail(<?= json_encode($rec) ?>)">
                                    <i class="fa-solid fa-file-medical"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Record Detail -->
<div class="modal-overlay" id="modal-record-detail">
    <div class="modal" style="max-width:600px">
        <div class="modal-header">
            <h3>Detalle de Registro Clínico</h3>
            <button class="modal-close" data-close-modal="modal-record-detail"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" id="record-detail-body">
            <!-- Dynamic Content -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" data-close-modal="modal-record-detail">Cerrar</button>
            <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir</button>
        </div>
    </div>
</div>

<script>
function openRecordDetail(rec) {
    const body = document.getElementById('record-detail-body');
    const vs = typeof rec.vital_signs === 'string' ? JSON.parse(rec.vital_signs) : rec.vital_signs;
    
    let vsHtml = '';
    if (vs) {
        vsHtml = `
            <div style="display:flex; gap:10px; margin-top:15px; padding:10px; background:var(--gray-50); border-radius:8px">
                ${vs.blood_pressure ? `<span class="badge badge-info"><i class="fa-solid fa-heart-pulse"></i> ${vs.blood_pressure}</span>` : ''}
                ${vs.heart_rate ? `<span class="badge badge-pending"><i class="fa-solid fa-wave-square"></i> ${vs.heart_rate} bpm</span>` : ''}
                ${vs.temperature ? `<span class="badge badge-warning"><i class="fa-solid fa-temperature-half"></i> ${vs.temperature} °C</span>` : ''}
                ${vs.weight ? `<span class="badge badge-completed"><i class="fa-solid fa-weight-scale"></i> ${vs.weight} kg</span>` : ''}
            </div>
        `;
    }

    body.innerHTML = `
        <div style="margin-bottom:20px">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px">
                <strong>Paciente:</strong> <span>${rec.patient_name}</span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:10px">
                <strong>Médico Atendiente:</strong> <span>Dr. ${rec.doctor_name}</span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:10px">
                <strong>Fecha:</strong> <span>${new Date(rec.created_at).toLocaleString()}</span>
            </div>
            <div style="display:flex; justify-content:space-between">
                <strong>Tipo:</strong> <span class="badge badge-info">${rec.record_type.toUpperCase()}</span>
            </div>
        </div>

        <div style="margin-bottom:15px">
            <h4 style="font-size:0.9rem; color:var(--gray-700); border-bottom:1px solid var(--gray-100); padding-bottom:5px; margin-bottom:8px">Diagnóstico</h4>
            <p style="font-size:0.85rem; line-height:1.5">${rec.diagnosis.replace(/\n/g, '<br>')}</p>
        </div>

        <div style="margin-bottom:15px">
            <h4 style="font-size:0.9rem; color:var(--gray-700); border-bottom:1px solid var(--gray-100); padding-bottom:5px; margin-bottom:8px">Tratamiento</h4>
            <p style="font-size:0.85rem; line-height:1.5">${rec.treatment ? rec.treatment.replace(/\n/g, '<br>') : '—'}</p>
        </div>

        ${vsHtml}

        ${rec.observations ? `
        <div style="margin-top:15px">
            <h4 style="font-size:0.9rem; color:var(--gray-700); border-bottom:1px solid var(--gray-100); padding-bottom:5px; margin-bottom:8px">Observaciones</h4>
            <p style="font-size:0.8rem; color:var(--gray-600); font-style:italic">${rec.observations}</p>
        </div>` : ''}
    `;
    
    openModal('modal-record-detail');
}

document.getElementById('record-search').addEventListener('input', function(e) {
    const q = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#records-table tbody tr');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
    });
});
</script>
