document.querySelectorAll('textarea[data-auto-grow]').forEach((textarea) => {
    if (!(textarea instanceof HTMLTextAreaElement)) {
        return;
    }

    const resizeToContent = () => {
        textarea.style.height = 'auto';

        const styles = window.getComputedStyle(textarea);
        const borderHeight = parseFloat(styles.borderTopWidth) + parseFloat(styles.borderBottomWidth);
        textarea.style.height = `${textarea.scrollHeight + borderHeight}px`;
    };

    resizeToContent();
    textarea.addEventListener('input', resizeToContent);
    window.addEventListener('resize', resizeToContent);
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[type="file"][name="images[]"]').forEach((fileInput) => {
        const gallery = fileInput.closest('.property-edit-gallery');
        if (!gallery) return;

        let selectedFiles = [];
        const existingImageCount = Number(gallery.dataset.existingImageCount || 0);
        const maxImages = 12;

        const remainingCapacity = () => {
            const removals = gallery.querySelectorAll('input[name="remove_images[]"]:checked').length;
            return Math.max(0, maxImages - (existingImageCount - removals) - selectedFiles.length);
        };

        const syncInput = () => {
            const transfer = new DataTransfer();
            selectedFiles.forEach((file) => transfer.items.add(file));
            fileInput.files = transfer.files;
        };

        const renderPreviews = () => {
            gallery.querySelectorAll('.property-edit-image.preview-image').forEach((item) => item.remove());
            selectedFiles.forEach((file, index) => {
                const preview = document.createElement('div');
                preview.className = 'property-edit-image preview-image';

                const image = document.createElement('img');
                image.src = URL.createObjectURL(file);
                image.alt = `Preview: ${file.name}`;

                const actions = document.createElement('div');
                actions.className = 'property-edit-preview-actions';
                const caption = document.createElement('span');
                caption.textContent = file.name;
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'property-edit-preview-remove';
                remove.setAttribute('aria-label', `Remove ${file.name}`);
                remove.textContent = 'Remove';
                remove.addEventListener('click', () => {
                    selectedFiles.splice(index, 1);
                    syncInput();
                    renderPreviews();
                });
                actions.append(caption, remove);
                preview.append(image, actions);
                gallery.querySelector('.property-edit-add-image')?.before(preview);
            });
        };

        fileInput.addEventListener('change', () => {
            const incoming = Array.from(fileInput.files || []);
            const capacity = remainingCapacity();
            let added = 0;

            incoming.forEach((file) => {
                if (!file.type.startsWith('image/') || added >= capacity) return;
                const duplicate = selectedFiles.some((selected) => selected.name === file.name && selected.size === file.size);
                if (!duplicate) {
                    selectedFiles.push(file);
                    added++;
                }
            });

            syncInput();
            renderPreviews();
            if (incoming.length > added) {
                window.showToast?.(`A listing can contain at most ${maxImages} photos.`, 'warning');
            }
        });

        gallery.querySelectorAll('input[name="remove_images[]"]').forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                if (remainingCapacity() >= 0) return;
                const excess = selectedFiles.splice(0, Math.abs(remainingCapacity()));
                if (excess.length) {
                    syncInput();
                    renderPreviews();
                }
            });
        });
    });
});

