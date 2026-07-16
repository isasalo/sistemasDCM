/**
 * assets/js/main.js — Core utilities, sidebar, CSRF, toasts
 */

// ─── Toast notifications ──────────────────────────────────
const ToastManager = (() => {
    let container = null;
    const icons = { success:'fa-circle-check', error:'fa-circle-xmark', warning:'fa-triangle-exclamation', info:'fa-circle-info' };

    function getContainer() {
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    function show(message, type = 'info', duration = 3500) {
        const c    = getContainer();
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="toast-icon fa-solid ${icons[type] || icons.info}"></i>
            <span class="toast-text">${message}</span>
            <i class="toast-close fa-solid fa-xmark" role="button"></i>`;
        c.appendChild(toast);

        const dismiss = () => {
            toast.classList.add('hiding');
            toast.addEventListener('animationend', () => toast.remove(), { once: true });
        };
        toast.querySelector('.toast-close').addEventListener('click', dismiss);
        setTimeout(dismiss, duration);
        return toast;
    }

    return { show };
})();

window.showNotification = (msg, type = 'info') => ToastManager.show(msg, type);

// ─── Flash messages from PHP ──────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-flash]').forEach(el => {
        const { flash, flashType } = el.dataset;
        if (flash) ToastManager.show(flash, flashType || 'info');
    });
});

// ─── API fetch helper ─────────────────────────────────────
async function apiCall(url, method = 'GET', body = null) {
    try {
        const opts = {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() }
        };
        if (body) opts.body = JSON.stringify(body);
        const res = await fetch(url, opts);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return await res.json();
    } catch (err) {
        const errorMsg = err.message || 'Error desconocido';
        showNotification(`Error de red: ${errorMsg}`, 'error');
        console.error('[apiCall]', err);
        return null;
    }
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

// ─── Loader wrapper ───────────────────────────────────────
async function withLoader(btn, asyncFn) {
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Cargando...';
    try {
        return await asyncFn();
    } finally {
        btn.disabled = false;
        btn.innerHTML = orig;
    }
}

// ─── Sidebar mobile toggle ────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const toggler = document.querySelector('.mobile-menu-btn');

    function openSidebar()  { sidebar?.classList.add('open'); overlay?.classList.add('open'); document.body.style.overflow = 'hidden'; }
    function closeSidebar() { sidebar?.classList.remove('open'); overlay?.classList.remove('open'); document.body.style.overflow = ''; }

    toggler?.addEventListener('click', openSidebar);
    overlay?.addEventListener('click', closeSidebar);
});

// ─── Modal helpers ────────────────────────────────────────
function openModal(id) {
    const m = document.getElementById(id);
    m?.classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    const m = document.getElementById(id);
    m?.classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', () => {
    // Close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) closeModal(overlay.id);
        });
    });
    // Close buttons
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });
    // Open buttons
    document.querySelectorAll('[data-open-modal]').forEach(btn => {
        btn.addEventListener('click', () => openModal(btn.dataset.openModal));
    });
    // ESC key
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.open').forEach(m => closeModal(m.id));
        }
    });
});

// ─── Tab system ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const group  = btn.closest('[data-tabs]')?.dataset.tabs || btn.dataset.tabGroup;
            const target = btn.dataset.tab;
            document.querySelectorAll(`[data-tab-group="${group}"] .tab-btn`).forEach(b => b.classList.remove('active'));
            document.querySelectorAll(`[data-tab-group="${group}"] .tab-content`).forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(target)?.classList.add('active');
        });
    });
});

// ─── Confirm dialogs ──────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm)) e.preventDefault();
        });
    });
});

// ─── Format currency (COP) ───────────────────────────────
function formatCOP(num) {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(num);
}

// ─── Print voucher ────────────────────────────────────────
function printVoucher(id) {
    const el = document.getElementById(id);
    if (!el) return;
    const win = window.open('', '_blank');
    win.document.write(`<!DOCTYPE html><html><head><title>Comprobante</title>
        <link rel="stylesheet" href="/proyecto hospital/assets/css/main.css">
        <link rel="stylesheet" href="/proyecto hospital/assets/css/dashboard.css">
        <link rel="stylesheet" href="/proyecto hospital/assets/css/components.css">
        <style>body{padding:40px}@media print{body{padding:0}}</style>
        </head><body>${el.outerHTML}</body></html>`);
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); win.close(); }, 400);
}

// ─── Notification badge update ────────────────────────────
async function refreshNotifBadge() {
    try {
        const res = await fetch('api/appointments.php?action=notif_count');
        if (!res.ok) return;
        const data = await res.json();
        const badge = document.querySelector('.notif-badge');
        if (badge) {
            badge.textContent = data.count || 0;
            badge.style.display = data.count > 0 ? 'flex' : 'none';
        }
    } catch (_) {}
}
