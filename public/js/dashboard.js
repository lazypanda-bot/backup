const initializeDashboardCalendar = function () {
    const blockDateModal = document.getElementById('blockDateModal');
    document.getElementById('openBlockDateModal')?.addEventListener('click', () => blockDateModal?.classList.add('open'));
    document.getElementById('closeBlockDateModal')?.addEventListener('click', () => blockDateModal?.classList.remove('open'));

    const calendarDates = document.getElementById('calendarDates');
    if (!calendarDates || calendarDates.dataset.dashboardInitialized) return;
    calendarDates.dataset.dashboardInitialized = 'true';

    let appointments = [];
    let blockedDates = [];
    if (calendarDates.dataset.appointments) {
        try {
            appointments = JSON.parse(calendarDates.dataset.appointments);
        } catch (e) {
            console.error('Failed to parse appointments data', e);
        }
    }
    if (calendarDates.dataset.blockedDates) {
        try {
            blockedDates = JSON.parse(calendarDates.dataset.blockedDates);
        } catch (e) {
            console.error('Failed to parse blocked dates data', e);
        }
    }

    const cells = calendarDates.querySelectorAll('.calendar-cell:not(.other-month)');
    
    cells.forEach(cell => {
        const dayNumberElem = cell.querySelector('.calendar-day-number');
        if (!dayNumberElem) return;

        const dayNum = String(dayNumberElem.textContent.trim()).padStart(2, '0');
        const monthDisplay = document.querySelector('.calendar-month-display')?.textContent || '';
        
        if (!monthDisplay) return;

        const [monthName, year] = monthDisplay.trim().split(/\s+/);
        if (!monthName || !year) return;

        const dateObj = new Date(`${monthName} 1, ${year}`);
        const monthNum = String(dateObj.getMonth() + 1).padStart(2, '0');
        const dateString = `${year}-${monthNum}-${dayNum}`;

        const dayAppointments = appointments.filter((app) => String(app.preferred_date).slice(0, 10) === dateString);
        const dayBlockedDates = blockedDates.filter((blocked) => String(blocked.date).slice(0, 10) === dateString);
        const fullyBlocked = dayBlockedDates.some((blocked) => !blocked.start_time || !blocked.end_time);
        const formatTime = (value) => {
            if (!value) return '';
            const [hours, minutes] = String(value).split(':').map(Number);
            const date = new Date();
            date.setHours(hours || 0, minutes || 0, 0, 0);
            return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        };
        
        // CLEAR EXISTING BADGES & TOOLTIPS TO PREVENT DUPLICATES
        let badgeContainer = cell.querySelector('.day-badges');
        if (badgeContainer) {
            badgeContainer.innerHTML = ''; // Removes old duplicate dots
        }

        let customTooltip = cell.querySelector('.custom-day-tooltip');
        if (customTooltip) {
            customTooltip.innerHTML = ''; // Clears old tooltip lines
        }

        if (dayAppointments.length > 0 || dayBlockedDates.length > 0) {
            if (!badgeContainer) {
                badgeContainer = document.createElement('div');
                badgeContainer.className = 'day-badges';
                badgeContainer.style.display = 'flex';
                badgeContainer.style.gap = '4px';
                badgeContainer.style.marginTop = '6px';
                cell.appendChild(badgeContainer);
            }

            const tooltipDate = document.createElement('div');
            tooltipDate.className = 'custom-day-tooltip-date';
            tooltipDate.textContent = dateString;
            customTooltip ??= document.createElement('div');
            customTooltip.className = 'custom-day-tooltip';
            customTooltip.replaceChildren(tooltipDate);

            dayAppointments.forEach((app) => {
                const badge = document.createElement('span');
                badge.className = 'appointment-badge appointment-event';
                badge.classList.add('calendar-cell-entry');
                badge.textContent = formatTime(app.preferred_time) || 'Appointment';
                badgeContainer.appendChild(badge);

                const appointmentLine = document.createElement('div');
                appointmentLine.textContent = formatTime(app.preferred_time) || 'Appointment';
                customTooltip.appendChild(appointmentLine);
            });

            dayBlockedDates.forEach((blocked) => {
                const badge = document.createElement('span');
                badge.className = 'appointment-badge blocked-date-event';
                badge.classList.add('calendar-cell-entry');
                badgeContainer.appendChild(badge);
                cell.classList.add('calendar-cell-blocked');

                const rawReason = String(blocked.reason || '').trim();
                const reason = /^all day$/i.test(rawReason) ? 'Whole day' : rawReason;
                const startTime = blocked.start_time ? formatTime(blocked.start_time) : 'Whole day';
                const endTime = blocked.end_time ? ` - ${formatTime(blocked.end_time)}` : '';
                badge.textContent = reason && !blocked.start_time && /^whole day$/i.test(reason)
                    ? 'Whole day'
                    : (reason || `${startTime}${endTime}`);
                const blockedLine = document.createElement('div');
                blockedLine.textContent = reason
                    ? `${reason} · ${startTime}${endTime}`
                    : `${startTime}${endTime}`;
                customTooltip.appendChild(blockedLine);
            });

            if (fullyBlocked) {
                cell.classList.add('calendar-cell-fully-blocked');
                cell.setAttribute('aria-disabled', 'true');
            }
            if (!cell.contains(customTooltip)) cell.appendChild(customTooltip);
        }
    });
};
document.addEventListener('DOMContentLoaded', initializeDashboardCalendar);
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.block-date-dialog');
    const dateInput = form?.querySelector('[name="date"]');
    const slotSelect = form?.querySelector('[name="time_slot"]');
    const status = form?.querySelector('.block-date-availability');
    const submitButton = form?.querySelector('button[type="submit"]');
    if (!form || !dateInput || !slotSelect) return;

    let blockedDates = [];
    try {
        blockedDates = JSON.parse(form.dataset.blockedDates || '[]');
    } catch (error) {
        console.error('Unable to parse blocked date ranges.', error);
    }

    const updateAvailability = () => {
        const selectedDate = dateInput.value;
        const blocks = blockedDates.filter((blocked) => String(blocked.date).slice(0, 10) === selectedDate);
        const wholeDay = blocks.some((blocked) => !blocked.start_time || !blocked.end_time);
        const morning = blocks.some((blocked) => String(blocked.start_time).slice(0, 5) === '08:00');
        const afternoon = blocks.some((blocked) => String(blocked.start_time).slice(0, 5) === '13:00');

        [...slotSelect.options].forEach((option) => {
            option.disabled = wholeDay
                || (option.value === 'morning' && morning)
                || (option.value === 'afternoon' && afternoon);
        });

        dateInput.classList.toggle('is-blocked', wholeDay);
        dateInput.setCustomValidity(wholeDay ? 'This date is already blocked for the whole day.' : '');
        if (wholeDay) {
            dateInput.setAttribute('aria-invalid', 'true');
            slotSelect.disabled = true;
            if (submitButton) submitButton.disabled = true;
            if (status) status.textContent = 'Whole day already blocked. Choose another date.';
            return;
        }

        dateInput.removeAttribute('aria-invalid');
        slotSelect.disabled = false;
        if (submitButton) submitButton.disabled = false;
        if (slotSelect.selectedOptions[0]?.disabled) slotSelect.value = '';
        if (morning && afternoon) {
            dateInput.setCustomValidity('Morning and afternoon are already blocked. Choose another date.');
            dateInput.classList.add('is-blocked');
            dateInput.setAttribute('aria-invalid', 'true');
            slotSelect.disabled = true;
            if (submitButton) submitButton.disabled = true;
            if (status) status.textContent = 'Whole day already blocked. Choose another date.';
        } else if (status) {
            status.textContent = morning
                ? 'Morning is already blocked; afternoon is available.'
                : afternoon
                    ? 'Afternoon is already blocked; morning is available.'
                    : '';
        }
    };

    dateInput.addEventListener('change', updateAvailability);
    form.addEventListener('reset', () => queueMicrotask(updateAvailability));
    updateAvailability();
});
