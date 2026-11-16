document.addEventListener('DOMContentLoaded', () => {
    const modals = [...document.querySelectorAll('[data-reschedule-modal]')];
    let activeModal = null;

    const closeModal = (modal) => {
        modal.hidden = true;
        if (activeModal === modal) activeModal = null;
        modal._opener?.focus();
    };

    modals.forEach((modal) => {
        document.body.appendChild(modal);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) closeModal(modal);
        });
        modal.addEventListener('keydown', (event) => {
            if (event.key !== 'Tab') return;
            const focusable = [...modal.querySelectorAll('button:not([disabled]), input:not([disabled]), select:not([disabled]), [href], [tabindex]:not([tabindex="-1"])')];
            if (!focusable.length) {
                event.preventDefault();
                return;
            }
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });
        modal.querySelector('[data-reschedule-close]')?.addEventListener('click', () => closeModal(modal));
    });

    document.querySelectorAll('[data-open-reschedule-modal]').forEach((trigger) => {
        const modal = document.getElementById(trigger.dataset.openRescheduleModal);
        if (!modal) return;
        trigger.addEventListener('click', () => {
            activeModal = modal;
            modal._opener = trigger;
            modal.hidden = false;
            modal.querySelector('[data-reschedule-close]')?.focus();
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeModal) closeModal(activeModal);
    });

    const initializeForms = (scope = document) => scope.querySelectorAll('[data-proposal-availability-url]').forEach((form) => {
        if (form.dataset.proposalInitialized) return;
        form.dataset.proposalInitialized = 'true';
        const date = form.querySelector('[name="preferred_date"]');
        const timeSelect = form.querySelector('select[name="preferred_time"]');
        const preferredTimeField = form.querySelector('input[name="preferred_time"]');
        const preferredTimeValue = preferredTimeField || timeSelect;
        const period = form.querySelector('[name="time_period"]');
        const utcSlot = form.querySelector('[name="slot_start_utc"]');
        const timezone = form.querySelector('[name="timezone"]');
        const status = form.querySelector('.appointment-slots-status');
        const submitButton = form.querySelector('button[type="submit"]');

        form._availability = window.LjsAvailability.init({
            dateInput: date,
            timeSelect,
            periodSelect: period,
            preferredTimeField: timeSelect ? null : preferredTimeField,
            submitButton,
            utcField: utcSlot,
            status,
            timezone: () => timezone.value,
            excludeSlotUtc: form.dataset.excludeSlotUtc,
            selectionMessage: 'Your proposal will use the earliest available time in this period:',
            urlFor: ({ date: day, withDates }) => {
                const url = new URL(form.dataset.proposalAvailabilityUrl, window.location.href);
                url.searchParams.set('date', day);
                url.searchParams.set('timezone', timezone.value);
                if (withDates) url.searchParams.set('with_dates', '1');
                return url;
            },
        });

        let submitting = false;
        form.addEventListener('submit', async (event) => {
            // Prevent default and handle with fetch
            if (event.defaultPrevented) return;
            event.preventDefault();
            if (submitting) return;

            if (!utcSlot.value) {
                status.textContent = 'Choose a date and available time period before sending the proposal.';
                status.style.color = '#ef4444';
                if (date.value) period.focus();
                else date.focus();
                return;
            }

            // Validate that the proposed slot is not the current appointment's time
            const excludeSlotUtc = form.dataset.excludeSlotUtc;
            if (excludeSlotUtc && utcSlot.value === excludeSlotUtc) {
                status.textContent = 'You cannot reschedule to the current appointment time. Choose a different time.';
                status.style.color = '#ef4444';
                return;
            }

            status.textContent = 'Sending proposal...';
            status.style.color = 'inherit';
            submitting = true;
            if (submitButton) submitButton.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: form.method || 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: new FormData(form)
                });

                const data = await response.json();

                if (response.status === 422) {
                    status.textContent = data.message || 'That is already the scheduled appointment time.';
                    status.style.color = '#ef4444';
                    return;
                }

                if (!response.ok) {
                    throw new Error(data.message || 'An error occurred while submitting.');
                }

                // Success: close modal and reload the page to reflect changes
                const modal = form.closest('[data-reschedule-modal]');
                if (modal) closeModal(modal);
                window.location.reload();

            } catch (error) {
                status.textContent = error.message;
                status.style.color = '#ef4444';
            } finally {
                submitting = false;
                if (submitButton) submitButton.disabled = !utcSlot.value || !preferredTimeValue?.value;
            }
        });
    });
    initializeForms();
    document.addEventListener('ljs:split-before-update', (event) => {
        event.target.querySelectorAll('[data-proposal-availability-url]').forEach((form) => form._availability?.destroy());
    });
    document.addEventListener('ljs:split-updated', (event) => initializeForms(event.detail.panel));
});
