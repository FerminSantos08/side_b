(() => {
    const trigger = document.querySelector('[data-search-open]');
    const dropdown = document.getElementById('sideb-search');
    if (!trigger || !dropdown) return;

    const input = document.getElementById('sideb-search-query');
    const results = dropdown.querySelector('.search-dropdown-results');
    const status = dropdown.querySelector('.search-dropdown-status');
    let timer;
    let controller;

    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-controls', dropdown.id);

    function closeSearch(restoreFocus) {
        dropdown.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        clearTimeout(timer);
        controller?.abort();
        if (restoreFocus) trigger.focus();
    }

    trigger.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        if (!dropdown.hidden) {
            closeSearch(true);
            return;
        }
        dropdown.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        input.focus();
        scheduleSearch();
    });
    dropdown.querySelector('.search-dropdown-close').addEventListener('click', () => closeSearch(true));
    document.addEventListener('click', (event) => {
        if (!dropdown.hidden && !dropdown.contains(event.target) && !trigger.contains(event.target)) {
            closeSearch(false);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !event.isComposing && !dropdown.hidden) {
            const focusInside = dropdown.contains(document.activeElement) || trigger === document.activeElement;
            if (focusInside) event.preventDefault();
            closeSearch(focusInside);
        }
    });

    function renderGroups(groups, query) {
        const content = document.createDocumentFragment();
        for (const group of groups) {
            const section = document.createElement('section');
            const heading = document.createElement('h3');
            heading.className = 'eyebrow';
            heading.textContent = group.label;
            const list = document.createElement('ul');
            list.setAttribute('role', 'list');
            for (const item of group.items) {
                const entry = document.createElement('li');
                const link = document.createElement('a');
                link.className = 'search-dropdown-item';
                link.href = item.url;
                if (item.image) {
                    const cover = document.createElement('img');
                    cover.src = item.image;
                    cover.alt = '';
                    cover.loading = 'lazy';
                    cover.width = 48;
                    cover.height = 48;
                    cover.addEventListener('error', () => cover.remove(), { once: true });
                    link.append(cover);
                }
                const copy = document.createElement('span');
                if (group.key === 'productos' || (group.key === 'albumes' && item.meta !== 'Álbum')) {
                    const meta = document.createElement('small');
                    meta.textContent = item.meta;
                    copy.append(meta);
                }
                const title = document.createElement('strong');
                title.textContent = item.label;
                copy.append(title);
                if (item.description) {
                    const description = document.createElement('span');
                    description.textContent = item.description;
                    copy.append(description);
                }
                link.append(copy);
                entry.append(link);
                list.append(entry);
            }
            const header = document.createElement('div');
            header.className = 'search-dropdown-group';
            const more = document.createElement('a');
            more.href = 'buscar.php?' + new URLSearchParams({ q: query, categoria: group.key });
            more.textContent = 'Ver todos';
            more.setAttribute('aria-label', 'Ver todos: ' + group.label);
            header.append(heading, more);
            section.append(header, list);
            content.append(section);
        }
        results.replaceChildren(content);
    }

    async function search(query) {
        const request = new AbortController();
        controller = request;
        try {
            const response = await fetch('buscar_sugerencias.php?' + new URLSearchParams({ q: query }), {
                signal: request.signal,
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Error de búsqueda');
            const { groups } = await response.json();
            if (request.signal.aborted || dropdown.hidden || input.value.trim() !== query) return;
            renderGroups(groups, query);
            const count = groups.reduce((sum, group) => sum + group.items.length, 0);
            status.textContent = count ? (count === 1 ? '1 sugerencia.' : `${count} sugerencias.`) : 'No encontramos coincidencias.';
        } catch {
            if (!request.signal.aborted && !dropdown.hidden) {
                status.textContent = 'No pudimos cargar las sugerencias. Puedes abrir la búsqueda completa.';
            }
        } finally {
            if (controller === request) {
                controller = null;
                if (!request.signal.aborted) results.removeAttribute('aria-busy');
            }
        }
    }

    function scheduleSearch() {
        clearTimeout(timer);
        controller?.abort();
        results.replaceChildren();
        results.removeAttribute('aria-busy');
        const query = input.value.trim();
        if ([...query].length < 2) {
            status.textContent = 'Escribe al menos dos caracteres.';
            return;
        }
        results.setAttribute('aria-busy', 'true');
        status.textContent = 'Buscando…';
        timer = setTimeout(() => search(query), 300);
    }

    input.addEventListener('input', (event) => {
        if (!event.isComposing) scheduleSearch();
    });
    input.addEventListener('compositionend', scheduleSearch);

    dropdown.addEventListener('keydown', (event) => {
        if (event.isComposing || !['ArrowDown', 'ArrowUp'].includes(event.key)) return;
        if (event.target !== input && !results.contains(event.target)) return;
        const links = [...results.querySelectorAll('.search-dropdown-item')];
        if (!links.length) return;
        const current = links.indexOf(event.target);
        const next = current === -1
            ? (event.key === 'ArrowDown' ? links[0] : links.at(-1))
            : links[current + (event.key === 'ArrowDown' ? 1 : -1)] ?? input;
        event.preventDefault();
        next.focus();
    });
})();
