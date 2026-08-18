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
});
