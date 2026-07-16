/**
 * assets/js/notifications.js — Polling, urgency alerts, vital signs chart
 */

// ─── Urgency polling (doctor) ─────────────────────────────
const UrgencyPoller = (() => {
    let intervalId   = null;
    let lastCount    = -1;

    async function check() {
        try {
            const res  = await fetch('api/appointments.php?action=urgency_count');
            if (!res.ok) return;
            const data = await res.json();
            const count = data.count ?? 0;

            const badge = document.getElementById('urgency-badge');
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'inline-flex' : 'none';
            }

            // Show toast only when count increases
            if (lastCount !== -1 && count > lastCount) {
                const diff = count - lastCount;
                showNotification(`🚨 ${diff} nueva${diff > 1 ? 's' : ''} urgencia${diff > 1 ? 's' : ''} en espera`, 'warning', 6000);
            }
            lastCount = count;

            // Update urgency counter on dashboard
            const dashCounter = document.getElementById('urgency-counter');
            if (dashCounter) dashCounter.textContent = count;

        } catch (_) {}
    }

    function start(intervalMs = 30000) {
        check();
        intervalId = setInterval(check, intervalMs);
    }

    function stop() {
        if (intervalId) clearInterval(intervalId);
    }

    return { start, stop, check };
})();

// ─── Notification badge poller ────────────────────────────
const NotifPoller = (() => {
    let intervalId = null;

    async function check() {
        try {
            const res  = await fetch('api/appointments.php?action=notif_count');
            if (!res.ok) return;
            const data = await res.json();
            const count = data.count ?? 0;

            document.querySelectorAll('.badge-count.notif-badge').forEach(el => {
                el.textContent = count;
                el.style.display = count > 0 ? 'flex' : 'none';
            });
        } catch (_) {}
    }

    function start(ms = 60000) {
        check();
        intervalId = setInterval(check, ms);
    }

    return { start, check };
})();

// ─── Vital Signs Chart (Canvas API) ──────────────────────
function renderVitalChart(canvasId, labels, datasets) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    const W = canvas.offsetWidth || 500;
    const H = canvas.offsetHeight || 200;
    canvas.width  = W;
    canvas.height = H;

    const PAD  = { top: 20, right: 20, bottom: 40, left: 50 };
    const plotW = W - PAD.left - PAD.right;
    const plotH = H - PAD.top  - PAD.bottom;

    // Find min/max across all datasets
    const allVals = datasets.flatMap(d => d.values);
    const maxVal  = Math.max(...allVals) * 1.1;
    const minVal  = Math.min(...allVals) * 0.9;
    const range   = maxVal - minVal || 1;

    function xPos(i) { return PAD.left + (i / Math.max(labels.length - 1, 1)) * plotW; }
    function yPos(v) { return PAD.top  + plotH - ((v - minVal) / range) * plotH; }

    ctx.clearRect(0, 0, W, H);

    // Grid lines
    ctx.strokeStyle = '#E2E8F0';
    ctx.lineWidth   = 1;
    for (let i = 0; i <= 4; i++) {
        const y = PAD.top + (plotH / 4) * i;
        ctx.beginPath(); ctx.moveTo(PAD.left, y); ctx.lineTo(W - PAD.right, y); ctx.stroke();
        const val = (maxVal - (range / 4) * i).toFixed(0);
        ctx.fillStyle = '#94A3B8'; ctx.font = '11px Inter, sans-serif'; ctx.textAlign = 'right';
        ctx.fillText(val, PAD.left - 8, y + 4);
    }

    // X labels
    ctx.fillStyle = '#94A3B8'; ctx.font = '11px Inter, sans-serif'; ctx.textAlign = 'center';
    labels.forEach((lbl, i) => {
        ctx.fillText(lbl.slice(5), xPos(i), H - PAD.bottom + 18);
    });

    // Lines & points
    datasets.forEach(({ values, color, label }) => {
        if (!values.length) return;

        // Gradient fill
        const grad = ctx.createLinearGradient(0, PAD.top, 0, H - PAD.bottom);
        grad.addColorStop(0, color + '30');
        grad.addColorStop(1, color + '00');

        ctx.beginPath();
        ctx.moveTo(xPos(0), yPos(values[0]));
        values.forEach((v, i) => { if (i > 0) ctx.lineTo(xPos(i), yPos(v)); });
        ctx.lineTo(xPos(values.length-1), H - PAD.bottom);
        ctx.lineTo(xPos(0), H - PAD.bottom);
        ctx.closePath();
        ctx.fillStyle = grad; ctx.fill();

        // Line
        ctx.beginPath();
        ctx.strokeStyle = color; ctx.lineWidth = 2.5;
        ctx.lineJoin = 'round'; ctx.lineCap = 'round';
        values.forEach((v, i) => { i === 0 ? ctx.moveTo(xPos(i), yPos(v)) : ctx.lineTo(xPos(i), yPos(v)); });
        ctx.stroke();

        // Dots
        values.forEach((v, i) => {
            ctx.beginPath();
            ctx.arc(xPos(i), yPos(v), 4, 0, Math.PI*2);
            ctx.fillStyle = 'white'; ctx.fill();
            ctx.strokeStyle = color; ctx.lineWidth = 2; ctx.stroke();
        });

        // Tooltip value on hover (static: show last value)
        const lastI = values.length - 1;
        ctx.fillStyle = color; ctx.font = 'bold 11px Inter, sans-serif'; ctx.textAlign = 'center';
        ctx.fillText(values[lastI], xPos(lastI), yPos(values[lastI]) - 10);
    });

    // Legend
    let lx = PAD.left;
    datasets.forEach(({ color, label }) => {
        ctx.fillStyle = color;
        ctx.fillRect(lx, 4, 12, 4);
        ctx.fillStyle = '#475569'; ctx.font = '11px Inter, sans-serif'; ctx.textAlign = 'left';
        ctx.fillText(label, lx + 16, 12);
        lx += ctx.measureText(label).width + 40;
    });
}

// ─── Medication rows (dynamic add/remove) ─────────────────
function initMedRows() {
    const container = document.getElementById('med-rows-container');
    const addBtn    = document.getElementById('add-med-btn');
    if (!container || !addBtn) return;

    let rowCount = container.querySelectorAll('.med-row').length;

    addBtn.addEventListener('click', () => {
        rowCount++;
        const row = document.createElement('div');
        row.className = 'med-row';
        row.innerHTML = `
            <div class="form-group" style="margin:0">
                <input name="medications[${rowCount}][name]" class="form-control" placeholder="Medicamento" required>
            </div>
            <div class="form-group" style="margin:0">
                <input name="medications[${rowCount}][dose]" class="form-control" placeholder="Dosis (ej: 500mg)">
            </div>
            <div class="form-group" style="margin:0">
                <input name="medications[${rowCount}][frequency]" class="form-control" placeholder="Frecuencia">
            </div>
            <div class="form-group" style="margin:0">
                <input name="medications[${rowCount}][duration]" class="form-control" placeholder="Duración">
            </div>
            <button type="button" class="remove-med" onclick="this.closest('.med-row').remove()">
                <i class="fa-solid fa-minus"></i>
            </button>`;
        container.appendChild(row);
    });
}

// ─── Finance CSV export ───────────────────────────────────
function exportTableCSV(tableId, filename = 'reporte.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = [];
    table.querySelectorAll('tr').forEach(tr => {
        const cols = [...tr.querySelectorAll('th,td')].map(td => `"${td.innerText.replace(/"/g,'""')}"`);
        rows.push(cols.join(','));
    });

    const csv  = rows.join('\n');
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url; a.download = filename; a.click();
    URL.revokeObjectURL(url);
    showNotification('Reporte exportado exitosamente', 'success');
}

// ─── Init on load ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    initMedRows();

    // Start urgency polling if on doctor pages
    if (document.body.dataset.role === 'doctor') {
        UrgencyPoller.start(30000);
    }

    // Start notification polling for all roles
    if (document.body.dataset.userId) {
        NotifPoller.start(60000);
    }

    // CSV export buttons
    document.querySelectorAll('[data-export-csv]').forEach(btn => {
        btn.addEventListener('click', () => exportTableCSV(btn.dataset.exportCsv, btn.dataset.filename || 'reporte.csv'));
    });
});
