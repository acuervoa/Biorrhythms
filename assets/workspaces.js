(() => {
    const body = document.body;
    if (body.classList.contains('embed-view')) return;

    const panels = [...document.querySelectorAll('[data-workspace-panel]')];
    const links = [...document.querySelectorAll('[data-workspace-link]')];
    if (panels.length === 0 || links.length === 0) return;

    const panelIds = new Set(panels.map((panel) => panel.dataset.workspacePanel));
    const aliases = {
        monthCalendar: 'explorar',
        diary: 'diario',
        chrono: 'herramientas',
        prefs: 'herramientas',
        context: 'herramientas',
        compat: 'herramientas',
        sharePreview: 'herramientas',
        widgetSnippet: 'herramientas',
        apiPreview: 'herramientas',
    };

    function fragmentId() {
        try {
            return decodeURIComponent(window.location.hash.slice(1));
        } catch {
            return window.location.hash.slice(1);
        }
    }

    function targetWorkspace(fragment) {
        if (panelIds.has(fragment)) return fragment;
        if (aliases[fragment]) return aliases[fragment];
        const target = document.getElementById(fragment);
        return target?.closest('[data-workspace-panel]')?.dataset.workspacePanel || 'lectura';
    }

    function activateWorkspace(id, { focusHeading = false, historyMode = 'none' } = {}) {
        if (!panelIds.has(id)) return;

        panels.forEach((panel) => {
            panel.hidden = panel.dataset.workspacePanel !== id;
        });
        links.forEach((link) => {
            if (link.dataset.workspaceLink === id) link.setAttribute('aria-current', 'page');
            else link.removeAttribute('aria-current');
        });

        if (historyMode === 'push' && fragmentId() !== id) {
            const url = new URL(window.location.href);
            url.hash = id;
            window.history.pushState(null, '', url);
        }

        if (focusHeading) {
            const panel = panels.find((item) => item.dataset.workspacePanel === id);
            const headingId = panel?.getAttribute('aria-labelledby');
            const heading = headingId ? document.getElementById(headingId) : null;
            heading?.setAttribute('tabindex', '-1');
            heading?.focus({ preventScroll: true });
            panel?.scrollIntoView({
                block: 'start',
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
            });
        }
    }

    function syncFromLocation({ focusTarget = false } = {}) {
        const fragment = fragmentId();
        const id = targetWorkspace(fragment || 'lectura');
        activateWorkspace(id, { focusHeading: focusTarget && panelIds.has(fragment) });

        if (!focusTarget || !fragment || panelIds.has(fragment)) return;
        const target = document.getElementById(fragment);
        const panel = panels.find((item) => item.dataset.workspacePanel === id);
        target?.closest('details')?.setAttribute('open', '');
        const focusable = target?.matches('a, button, input, select, textarea, [tabindex]');
        const focusNode = (target && focusable ? target : null) || (panel?.getAttribute('aria-labelledby')
            ? document.getElementById(panel.getAttribute('aria-labelledby'))
            : null);
        if (target && !focusable) target.setAttribute('tabindex', '-1');
        focusNode?.setAttribute('tabindex', '-1');
        (target && !focusable ? target : focusNode)?.focus({ preventScroll: true });
        target?.scrollIntoView({ block: 'start' });
    }

    links.forEach((link) => {
        link.addEventListener('click', (event) => {
            if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            activateWorkspace(link.dataset.workspaceLink, { focusHeading: true, historyMode: 'push' });
        });
    });

    window.addEventListener('hashchange', () => syncFromLocation({ focusTarget: true }));
    window.addEventListener('popstate', () => syncFromLocation({ focusTarget: true }));
    syncFromLocation({ focusTarget: true });
})();
