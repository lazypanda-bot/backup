document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('ljs:split-updated', (event) => {
        if (event.target.dataset.splitView !== 'appointments') return;
        
        const activeAppointment = document.querySelector('.appointment-master-item.active');
        if (activeAppointment && activeAppointment.classList.contains('has-pending')) {
            activeAppointment.classList.remove('has-pending');
            const indicator = activeAppointment.querySelector('.appointment-pending-indicator');
            if (indicator) indicator.remove();
        }
    });
});

