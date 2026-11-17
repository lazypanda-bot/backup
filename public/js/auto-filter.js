document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.staff-filter-bar').forEach((form) => {
        const search = form.querySelector('input[type="search"]');
        const results = form.parentElement?.querySelector('.au-table-container');
        if (!search || !results) return;

        let timer;
        let activeRequest;
        let composing = false;

        const notify = (message) => {
            if (message && window.showToast) window.showToast(message, 'error');
            else if (message) console.error(message);
        };

        const urlForFilters = (source = form.action) => {
            const url = new URL(source, window.location.href);
            url.search = '';
            new FormData(form).forEach((value, name) => {
                const text = String(value).trim();
                if (text) url.searchParams.set(name, text);
            });
            url.searchParams.delete('page');
            return url;
        };

        const loadResults = async (url, updateHistory = true) => {
            activeRequest?.abort();
            const request = new AbortController();
            activeRequest = request;
            form.setAttribute('aria-busy', 'true');
            results.setAttribute('aria-busy', 'true');

            try {
                const response = await fetch(url, {
                    headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    signal: request.signal,
                });
                if (!response.ok) throw new Error(`Unable to filter results (${response.status}).`);

                const responseDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
                const updatedResults = responseDocument.querySelector('.au-table-container');
                if (!updatedResults) throw new Error('The filtered results could not be loaded.');

                results.dispatchEvent(new CustomEvent('ljs:auto-filter-before-update', { bubbles: true }));
                results.innerHTML = updatedResults.innerHTML;
                window.lucide?.createIcons();
                results.dispatchEvent(new CustomEvent('ljs:auto-filter-updated', {
                    bubbles: true,
                    detail: { panel: results, url },
                }));

                if (updateHistory && url.href !== window.location.href) {
                    window.history.pushState({}, '', url);
                }
            } catch (error) {
                if (error.name !== 'AbortError') notify(error.message || 'Unable to filter results. Please try again.');
            } finally {
                if (activeRequest === request) {
                    activeRequest = null;
                    form.removeAttribute('aria-busy');
                    results.removeAttribute('aria-busy');
                }
            }
        };

        const scheduleSearch = () => {
            window.clearTimeout(timer);
            if (composing) return;
            timer = window.setTimeout(() => loadResults(urlForFilters()), 600);
        };

        search.addEventListener('compositionstart', () => {
            composing = true;
            window.clearTimeout(timer);
        });
        search.addEventListener('compositionend', () => {
            composing = false;
            scheduleSearch();
        });
        search.addEventListener('input', scheduleSearch);

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            window.clearTimeout(timer);
            if (!composing) loadResults(urlForFilters());
        });

        results.addEventListener('click', (event) => {
            const link = event.target.closest?.('.au-pagination a, .ljs-pagination a');
            if (!link) return;
            const url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) return;
            event.preventDefault();
            loadResults(url);
        });

        window.addEventListener('popstate', () => {
            const url = new URL(window.location.href);
            new FormData(form).forEach((_value, name) => {
                const field = form.elements.namedItem(name);
                if (field) field.value = url.searchParams.get(name) || '';
            });
            loadResults(url, false);
        });
    });
});
