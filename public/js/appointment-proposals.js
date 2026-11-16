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
        let confirmationDialogShown = false;
        form.addEventListener('submit', async (event) => {
            // Show confirmation dialog on first submit
            if (!form.dataset.confirmed && !confirmationDialogShown) {
                confirmationDialogShown = true;
                event.preventDefault();
                
                // Create confirmation dialog
                const dialog = document.createElement('div');
                dialog.className = 'confirmation-dialog-overlay';
                dialog.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 3100;
                `;
                
                const content = document.createElement('div');
                content.style.cssText = `
                    background: white;
                    border-radius: 8px;
                    padding: 32px 24px;
                    max-width: 400px;
                    text-align: center;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                `;
                
                content.innerHTML = `
                    <h2 style="margin: 0 0 12px; font-size: 20px; font-weight: 600; color: #111827;">Are you sure?</h2>
                    <p style="margin: 0 0 24px; font-size: 14px; color: #6b7280;">Are you sure you want to continue with this action?</p>
                    <div style="display: flex; gap: 12px; justify-content: center;">
                        <button class="confirm-no" style="padding: 8px 24px; border: 1px solid #e5e7eb; background: #fecaca; color: #991b1b; border-radius: 6px; font-weight: 500; cursor: pointer; font-size: 14px;">No</button>
                        <button class="confirm-yes" style="padding: 8px 24px; border: none; background: #bbf7d0; color: #065f46; border-radius: 6px; font-weight: 500; cursor: pointer; font-size: 14px;">Yes</button>
                    </div>
                `;
                
                dialog.appendChild(content);
                document.body.appendChild(dialog);
                
                const noBtn = content.querySelector('.confirm-no');
                const yesBtn = content.querySelector('.confirm-yes');
                
                noBtn.addEventListener('click', () => {
                    dialog.remove();
                    confirmationDialogShown = false;
                });
                
                yesBtn.addEventListener('click', () => {
                    dialog.remove();
                    form.dataset.confirmed = 'true';
                    form.dispatchEvent(new Event('submit'));
                });
                
                return;
            }

            // Actual form submission
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
                confirmationDialogShown = false;
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
                confirmationDialogShown = false;
                delete form.dataset.confirmed;
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
