import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

window.addEventListener('DOMContentLoaded', () => {
    const activeSidebarLink = document.querySelector('[data-active-sidebar-link]');

    if (activeSidebarLink) {
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        activeSidebarLink.scrollIntoView({
            behavior: prefersReducedMotion ? 'auto' : 'smooth',
            block: 'nearest',
            inline: 'nearest',
        });
    }

    document.querySelectorAll('[data-resolution-draft]').forEach((form) => {
        const storageKey = form.dataset.resolutionDraft;
        const controls = [...form.querySelectorAll('select[name], input[name="reason"]')];
        const status = form.querySelector('[data-draft-status]');

        try {
            const draft = JSON.parse(sessionStorage.getItem(storageKey));

            if (draft) {
                controls.forEach((control) => {
                    if (Object.hasOwn(draft, control.name)) {
                        control.value = draft[control.name];
                    }
                });
                status?.classList.remove('hidden');
            }
        } catch {
            sessionStorage.removeItem(storageKey);
        }

        const saveDraft = () => {
            const draft = Object.fromEntries(controls.map((control) => [control.name, control.value]));

            sessionStorage.setItem(storageKey, JSON.stringify(draft));
            status?.classList.remove('hidden');
        };

        form.addEventListener('input', saveDraft);
        form.addEventListener('change', saveDraft);
    });

    document.querySelectorAll('[data-live-official-record-search]').forEach((form) => {
        const input = form.querySelector('input[name="student_search"]');
        const status = form.querySelector('[data-live-search-status]');
        const selects = [...document.querySelectorAll('[data-official-record-select]')];
        let debounceTimer;
        let activeRequest;

        const updateSelects = (records) => {
            selects.forEach((select) => {
                const selectedValue = select.value;
                const emptyOption = select.options[0].cloneNode(true);
                const pinnedOption = select.querySelector('[data-pinned-official-record]')?.cloneNode(true);
                const options = [emptyOption];

                if (pinnedOption) {
                    options.push(pinnedOption);
                }

                records.forEach((record) => {
                    if (options.some((option) => option.value === String(record.id))) {
                        return;
                    }

                    const option = document.createElement('option');
                    option.value = record.id;
                    option.textContent = `${record.student_name} — ${record.student_id_number || 'No ID'} — ${record.course || 'No course'}`;
                    options.push(option);
                });

                select.replaceChildren(...options);
                select.value = options.some((option) => option.value === selectedValue) ? selectedValue : '';
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
        };

        const search = async () => {
            const query = input.value.trim();
            activeRequest?.abort();

            if (query === '') {
                updateSelects([]);
                status.textContent = 'Type to search this campus without reloading the page.';
                return;
            }

            activeRequest = new AbortController();
            status.textContent = 'Searching campus enrollment records…';

            try {
                const url = new URL(form.dataset.searchUrl, window.location.origin);
                url.searchParams.set('query', query);
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    signal: activeRequest.signal,
                });

                if (!response.ok) {
                    throw new Error('Search request failed.');
                }

                const payload = await response.json();
                updateSelects(payload.data);
                status.textContent = payload.data.length === 1
                    ? '1 campus record found.'
                    : `${payload.data.length} campus records found.`;
            } catch (error) {
                if (error.name !== 'AbortError') {
                    status.textContent = 'Search is temporarily unavailable. You can still use the Search button.';
                }
            }
        };

        input.addEventListener('input', () => {
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(search, 250);
        });
    });
});
