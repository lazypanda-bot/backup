document.addEventListener('DOMContentLoaded', () => {
    // Remove pending indicator from already-open appointment on initial load
    const initialActiveAppointment = document.querySelector('.appointment-master-item.active');
    if (initialActiveAppointment && initialActiveAppointment.classList.contains('has-pending')) {
        initialActiveAppointment.classList.remove('has-pending');
        const indicator = initialActiveAppointment.querySelector('.appointment-pending-indicator');
        if (indicator) indicator.remove();
    }
    
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

