/**
 * assets/js/appointments.js — Calendario interactivo + modal de citas
 */

const AppointmentCalendar = (() => {
    let currentDate   = new Date();
    let selectedDate  = null;
    let selectedSlot  = null;
    let selectedDoctor = null;
    let bookedDates   = [];

    const MONTHS_ES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const DAYS_ES   = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

    // ─── Render calendar ──────────────────────────────────
    function render(containerId = 'calendar-container') {
        const container = document.getElementById(containerId);
        if (!container) return;

        const year  = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const today = new Date();

        const firstDay  = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const prevDays  = new Date(year, month, 0).getDate();

        let html = `
        <div class="calendar-wrapper">
          <div class="calendar-nav">
            <button class="cal-nav-btn" id="cal-prev"><i class="fa-solid fa-chevron-left"></i></button>
            <span class="cal-title">${MONTHS_ES[month]} ${year}</span>
            <button class="cal-nav-btn" id="cal-next"><i class="fa-solid fa-chevron-right"></i></button>
          </div>
          <div class="calendar-grid">`;

        DAYS_ES.forEach(d => { html += `<div class="cal-day-header">${d}</div>`; });

        // Prev month filler
        for (let i = firstDay - 1; i >= 0; i--) {
            html += `<div class="cal-day other-month">${prevDays - i}</div>`;
        }

        // Current month days
        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            const dayDate = new Date(year, month, d);
            const isPast  = dayDate < new Date(today.getFullYear(), today.getMonth(), today.getDate());
            const isToday = dateStr === formatDate(today);
            const isSel   = dateStr === selectedDate;
            const hasAppt = bookedDates.includes(dateStr);
            const isSun   = dayDate.getDay() === 0;

            let cls = 'cal-day';
            if (isPast || isSun)    cls += ' disabled';
            if (isToday && !isPast) cls += ' today';
            if (isSel)              cls += ' selected';
            if (hasAppt)            cls += ' has-appt';

            html += `<div class="${cls}" data-date="${dateStr}">${d}</div>`;
        }

        // Next month filler
        const totalCells = Math.ceil((firstDay + daysInMonth) / 7) * 7;
        for (let d = 1; d <= totalCells - firstDay - daysInMonth; d++) {
            html += `<div class="cal-day other-month">${d}</div>`;
        }

        html += `</div></div>`;
        container.innerHTML = html;

        // Events
        container.querySelectorAll('.cal-day:not(.disabled):not(.other-month)').forEach(day => {
            day.addEventListener('click', () => onDayClick(day.dataset.date));
        });
        document.getElementById('cal-prev')?.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth()-1); render(containerId); });
        document.getElementById('cal-next')?.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth()+1); render(containerId); });
    }

    function formatDate(d) {
        return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    }

    // ─── Day click ────────────────────────────────────────
    async function onDayClick(dateStr) {
        selectedDate = dateStr;
        selectedSlot = null;

        // Update display
        const selDateEl = document.getElementById('selected-date-display');
        if (selDateEl) {
            const [y,m,d] = dateStr.split('-');
            selDateEl.textContent = `${d} de ${MONTHS_ES[parseInt(m)-1]} de ${y}`;
        }
        
        const hiddenDate = document.getElementById('hidden-date');
        if (hiddenDate) hiddenDate.value = dateStr;

        // Re-render to show selection
        render(document.getElementById('calendar-container') ? 'calendar-container' : undefined);

        // Load specialty & doctors
        const specSelect = document.getElementById('specialty-select');
        if (specSelect) {
            await loadDoctors(dateStr, specSelect.value);
        }

        document.getElementById('appt-date-section')?.classList.remove('hidden');
        document.getElementById('appt-date-section').style.display = 'block';
    }

    // ─── Load doctors for date ─────────────────────────────
    async function loadDoctors(date, specialtyId = '') {
        const container = document.getElementById('doctors-section');
        if (!container) return;

        container.innerHTML = '<p style="padding:12px;color:#64748B;font-size:.85rem"><i class="fa-solid fa-spinner fa-spin"></i> Cargando doctores...</p>';

        let url = `/proyecto hospital/api/doctors.php?date=${date}`;
        if (specialtyId) url += `&specialty_id=${specialtyId}`;

        const data = await apiCall(url);
        if (!data?.success || !data.data?.length) {
            container.innerHTML = '<p style="padding:12px;color:#94A3B8;font-size:.85rem">No hay doctores disponibles para esta fecha.</p>';
            return;
        }

        let html = '<div style="display:flex;flex-direction:column;gap:8px;margin-top:8px">';
        data.data.forEach(doc => {
            html += `
            <div class="appt-card doctor-option" data-doctor-id="${doc.id}" data-doctor-name="${doc.name}" data-fee="${doc.fee}" style="cursor:pointer">
              <div class="appt-date-block" style="background:#6366F1">
                <i class="fa-solid fa-user-doctor" style="font-size:1.1rem"></i>
              </div>
              <div class="appt-info">
                <div class="appt-doctor">Dr. ${doc.name}</div>
                <div class="appt-specialty">${doc.specialty}</div>
                <div class="appt-time">Tarifa: $${parseInt(doc.fee).toLocaleString('es-CO')}</div>
              </div>
            </div>`;
        });
        html += '</div>';
        container.innerHTML = html;

        container.querySelectorAll('.doctor-option').forEach(card => {
            card.addEventListener('click', () => {
                container.querySelectorAll('.doctor-option').forEach(c => { c.style.borderColor=''; c.style.background=''; });
                card.style.borderColor = 'var(--primary)';
                card.style.background  = 'var(--primary-light)';
                selectedDoctor = { id: card.dataset.doctorId, name: card.dataset.doctorName, fee: card.dataset.fee };
                document.getElementById('hidden-doctor-id').value = selectedDoctor.id;
                loadSlots(date, selectedDoctor.id);
            });
        });
    }

    // ─── Load time slots ──────────────────────────────────
    async function loadSlots(date, doctorId) {
        const container = document.getElementById('slots-grid-container');
        if (!container) return;

        container.innerHTML = '<p style="color:#64748B;font-size:.85rem"><i class="fa-solid fa-spinner fa-spin"></i> Cargando horarios...</p>';

        const data = await apiCall(`/proyecto hospital/api/appointments.php?action=slots&date=${date}&doctor_id=${doctorId}`);
        if (!data?.success) {
            container.innerHTML = '<p style="color:#94A3B8;font-size:.85rem">No se pudieron cargar los horarios.</p>';
            return;
        }

        const { available = [], booked = [] } = data.data;

        if (!available.length) {
            container.innerHTML = '<p style="color:#94A3B8;font-size:.85rem">No hay horarios disponibles.</p>';
            return;
        }

        let html = '<div class="slots-grid">';
        available.forEach(slot => {
            const isBooked = booked.includes(slot);
            const [h,m]    = slot.split(':');
            const label    = `${h}:${m}`;
            html += `<button class="slot-btn ${isBooked ? 'booked' : ''}" data-slot="${slot}" ${isBooked ? 'disabled' : ''}>${label}</button>`;
        });
        html += '</div>';
        container.innerHTML = html;

        container.querySelectorAll('.slot-btn:not(.booked)').forEach(btn => {
            btn.addEventListener('click', () => {
                container.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
                selectedSlot = btn.dataset.slot;
                document.getElementById('hidden-time').value = selectedSlot;
                document.getElementById('book-confirm-btn')?.removeAttribute('disabled');
            });
        });

        document.getElementById('appt-slots-container')?.classList.remove('hidden');
        document.getElementById('appt-slots-container').style.display = 'block';
    }

    // ─── Public init ──────────────────────────────────────
    function init(options = {}) {
        if (options.bookedDates) bookedDates = options.bookedDates;
        render(options.containerId || 'calendar-container');

        // Specialty filter
        document.getElementById('specialty-select')?.addEventListener('change', function() {
            if (selectedDate) loadDoctors(selectedDate, this.value);
        });

        // Date input hidden sync
        document.getElementById('hidden-date')?.addEventListener('change', function() {
            selectedDate = this.value;
        });
    }

    return { init, render, loadDoctors, loadSlots };
})();

// ─── Reschedule modal ─────────────────────────────────────
function initReschedule() {
    document.querySelectorAll('[data-reschedule]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id     = btn.dataset.reschedule;
            const docId  = btn.dataset.doctorId;
            document.getElementById('reschedule-appt-id').value    = id;
            document.getElementById('reschedule-doctor-id').value  = docId;
            openModal('modal-reschedule');
        });
    });

    document.getElementById('reschedule-date')?.addEventListener('change', async function() {
        const docId  = document.getElementById('reschedule-doctor-id').value;
        if (!docId) return;
        await AppointmentCalendar.loadSlots(this.value, docId);
        document.getElementById('reschedule-time').value = '';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Initialize if calendar container exists
    if (document.getElementById('calendar-container')) {
        AppointmentCalendar.init({ bookedDates: window.BOOKED_DATES || [] });
    }
    initReschedule();
});
