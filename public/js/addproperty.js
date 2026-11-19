//ADDS PROPERTY WHEN SAVE PROPERTY BUTTON IS CLICKED IN PROPERTY CREATE
const initializeAddPropertyPage = () => {
    const form = document.querySelector('[data-type-requirements]');
    if (!form || form.dataset.addPropertyInitialized) return;
    form.dataset.addPropertyInitialized = 'true';

    const requirements = JSON.parse(form.dataset.typeRequirements || '{}');
    const visibility = JSON.parse(form.dataset.typeVisibility || '{}');
    const detailFields = ['lot_area', 'floor_area', 'bedrooms', 'bathrooms'];
    const allFields = ['lot_area', 'floor_area', 'bedrooms', 'bathrooms', 'parking_spaces', 'number_of_floors', 'property_condition', 'year_built'];
    const labels = { lot_area: 'lot area', floor_area: 'floor area', bedrooms: 'bedrooms', bathrooms: 'bathrooms' };
    const hint = form.querySelector('[data-detail-hint]');

    const currentType = () => {
        const checked = form.querySelector('input[name="property_type"]:checked');
        return (checked?.value || '').trim().toLowerCase();
    };

    const applyRequirements = () => {
        const type = currentType();
        // Unknown or custom types keep every field visible.
        const visible = visibility[type] || allFields;

        allFields.forEach((name) => {
            const input = form.querySelector(`[name="${name}"]`);
            const label = input?.closest('label');
            if (!input || !label) return;
            const show = visible.includes(name);
            label.hidden = !show;
            input.disabled = !show;
        });

        // FORCE ALL DETAIL FIELDS TO BE OPTIONAL (Not required)
        detailFields.forEach((name) => {
            const input = form.querySelector(`[name="${name}"]`);
            if (!input) return;
            input.required = false; // <--- Changed from dynamic requirement to false
            const mark = input.closest('label')?.querySelector('.detail-required-mark');
            if (mark) mark.hidden = true; // <--- Hides the required asterisk marks
        });

        if (hint) {
            hint.textContent = !type
                ? 'Choose a property type. All property details are optional.'
                : 'All property details are optional for this property type.';
        }
    };

    const addTypeButton = document.getElementById('addTypeChoice');
    const customTypeInput = document.getElementById('customType');
    const addCustomType = () => {
        const label = customTypeInput.value.trim();
        if (!label) return;
        const list = document.getElementById('typeChoices');
        let radio = Array.from(list.querySelectorAll('input[type="radio"]'))
            .find((item) => item.value.toLowerCase() === label.toLowerCase());

        if (!radio) {
            const choice = document.createElement('label');
            choice.className = 'property-choice is-custom';
            radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = 'property_type';
            radio.value = label;
            radio.required = true;
            const text = document.createElement('span');
            text.textContent = label;
            choice.append(radio, text);
            list.appendChild(choice);
        }

        radio.checked = true;
        customTypeInput.value = '';
        applyRequirements();
    };
    addTypeButton?.addEventListener('click', addCustomType);
    customTypeInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            addCustomType();
        }
    });

    form.addEventListener('change', (event) => {
        if (event.target.name === 'property_type') applyRequirements();
    });

    const addPropertyForm = document.getElementById('addPropertyForm') || form;
    addPropertyForm.addEventListener('submit', async function (event) {
        if (event.defaultPrevented || addPropertyForm.dataset.confirmPending === 'true') return;
        event.preventDefault();
        const submitter = event.submitter || addPropertyForm.querySelector('button[type="submit"]');
        const previousLabel = submitter?.innerHTML;
        if (submitter) {
            submitter.disabled = true;
            submitter.textContent = 'Saving property...';
        }

        try {
            const response = await fetch(addPropertyForm.action, {
                method: addPropertyForm.method || 'POST',
                headers: {
                    Accept: 'application/json, text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: new FormData(addPropertyForm),
            });

            if (response.status === 422) {
                const data = await response.json();
                const messages = Object.values(data.errors || {}).flat();
                let summary = addPropertyForm.parentElement.querySelector('.property-create-errors');
                if (!summary) {
                    summary = document.createElement('div');
                    summary.className = 'property-create-errors';
                    summary.setAttribute('role', 'alert');
                    addPropertyForm.before(summary);
                }
                summary.replaceChildren();
                const heading = document.createElement('strong');
                heading.textContent = 'Please review the form errors before continuing.';
                const list = document.createElement('ul');
                messages.forEach((message) => {
                    const item = document.createElement('li');
                    item.textContent = message;
                    list.appendChild(item);
                });
                summary.append(heading, list);
                summary.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                return;
            }

            if (response.status === 403) {
                throw new Error('You do not have permission to add a property with this account. Switch to the correct Admin/Broker mode or contact an Admin.');
            }
            if (!response.ok) throw new Error('Unable to save the property. Please try again.');
            if (window.ljsLoadAdminPage) await window.ljsLoadAdminPage(response.url);
            else window.location.assign(response.url);
        } catch (error) {
            window.showToast?.(error.message || 'Unable to save the property. Please try again.', 'error');
        } finally {
            if (submitter?.isConnected) {
                submitter.disabled = false;
                submitter.innerHTML = previousLabel;
                window.lucide?.createIcons();
            }
        }
    });

    applyRequirements();
};
document.addEventListener('DOMContentLoaded', initializeAddPropertyPage);