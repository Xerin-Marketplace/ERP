<x-app-layout>
    <x-slot name="header">{{ __('Organizational Structure') }}</x-slot>

    <div class="max-w-3xl">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <input id="org-search" type="search" placeholder="Search units…"
                   class="w-full sm:w-80 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <button id="expand-all" class="text-xs font-medium text-gray-500 hover:text-gray-700">Expand all</button>
            <span class="text-gray-300">·</span>
            <button id="collapse-all" class="text-xs font-medium text-gray-500 hover:text-gray-700">Collapse all</button>
        </div>

        <div id="org-tree" class="rounded-sm border border-gray-200 bg-white p-4"></div>
    </div>

    <script>
        (() => {
            const TREE = @js($tree);
            const ICONS = {
                company: 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
                branch: 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z',
                department: 'M15 6.75a3 3 0 11-6 0 3 3 0 016 0z M12 21a9 9 0 100-18 9 9 0 000 18z',
            };
            const COLORS = {
                active: 'bg-green-100 text-green-700',
                suspended: 'bg-amber-100 text-amber-700',
                archived: 'bg-gray-200 text-gray-600',
            };
            const open = new Set();
            const host = document.getElementById('org-tree');
            const searchEl = document.getElementById('org-search');

            const matches = (node, q) =>
                !q || node.label.toLowerCase().includes(q) ||
                (node.children || []).some(c => matches(c, q));

            const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

            function row(node, depth) {
                const kids = node.children || [];
                const isOpen = open.has(node.id);
                const badge = COLORS[node.status] || COLORS.archived;
                let html = `<div class="flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-gray-50" style="padding-left:${depth * 1.5 + 0.5}rem">`;
                html += kids.length
                    ? `<button data-toggle="${node.id}" class="w-4 shrink-0 text-center text-gray-400 hover:text-gray-600 font-medium">${isOpen ? '−' : '+'}</button>`
                    : '<span class="w-4 shrink-0"></span>';
                html += `<svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="${ICONS[node.type] || ICONS.department}"/></svg>`;
                html += `<span class="text-sm font-medium text-gray-900">${esc(node.label)}</span>`;
                html += `<span class="text-xs text-gray-400">${esc((node.meta || node.type).replaceAll('_',' '))}</span>`;
                html += `<span class="ml-auto inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${badge}">${node.status[0].toUpperCase()+node.status.slice(1)}</span></div>`;
                if (kids.length && isOpen) {
                    html += `<div data-kids="${node.id}">`;
                    for (const c of kids) html += row(c, depth + 1);
                    html += '</div>';
                }
                return html;
            }

            function render() {
                const q = searchEl.value.trim().toLowerCase();
                if (q) {
                    // auto-open nodes on matching paths
                    const openMatching = (node) => {
                        if (node.label.toLowerCase().includes(q)) { open.add(node.id); return; }
                        let kidMatch = false;
                        for (const c of node.children || []) {
                            if (matches(c, q)) { openMatching(c); kidMatch = true; }
                        }
                        if (kidMatch) open.add(node.id);
                    };
                    TREE.forEach(openMatching);
                }
                const visible = TREE.filter(n => matches(n, q));
                host.innerHTML = visible.length
                    ? visible.map(n => row(n, 0)).join('')
                    : '<p class="py-10 text-center text-sm text-gray-400">No organizational units found.</p>';
            }

            const allIds = (nodes, acc = []) => { for (const n of nodes) { acc.push(n.id); allIds(n.children || [], acc); } return acc; };

            // default: open root level
            TREE.forEach(n => open.add(n.id));

            host.addEventListener('click', e => {
                const btn = e.target.closest('[data-toggle]');
                if (!btn) return;
                const id = btn.dataset.toggle;
                open.has(id) ? open.delete(id) : open.add(id);
                render();
            });
            searchEl.addEventListener('input', render);
            document.getElementById('expand-all').onclick = () => { allIds(TREE).forEach(id => open.add(id)); render(); };
            document.getElementById('collapse-all').onclick = () => { open.clear(); render(); };

            render();
        })();
    </script>
</x-app-layout>
