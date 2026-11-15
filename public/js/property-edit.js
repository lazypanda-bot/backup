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
