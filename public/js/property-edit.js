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

// Image preview functionality
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[type="file"][name="images[]"]').forEach((fileInput) => {
        fileInput.addEventListener('change', (event) => {
            const files = event.target.files;
            const gallery = fileInput.closest('.property-edit-gallery');
            if (!gallery) return;

            // Remove any previous preview items that were just added (from previous selections)
            gallery.querySelectorAll('.property-edit-image.preview-image').forEach(el => el.remove());

            Array.from(files).forEach((file) => {
                if (!file.type.startsWith('image/')) return;

                const reader = new FileReader();
                reader.onload = (e) => {
                    // Create a preview label similar to existing image labels
                    const previewLabel = document.createElement('label');
                    previewLabel.className = 'property-edit-image preview-image';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = `Preview: ${file.name}`;
                    img.style.objectFit = 'cover';
                    previewLabel.appendChild(img);
                    
                    // Insert before the add-image button
                    const addImageButton = gallery.querySelector('.property-edit-add-image');
                    if (addImageButton) {
                        gallery.insertBefore(previewLabel, addImageButton);
                    } else {
                        gallery.appendChild(previewLabel);
                    }
                };
                reader.readAsDataURL(file);
            });
        });
    });
});

