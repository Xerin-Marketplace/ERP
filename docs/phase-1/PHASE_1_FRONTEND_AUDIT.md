# Phase 1 — Frontend Audit

- Stack: Laravel Blade + Tailwind CSS + Alpine.js (Vite build). No Angular/React SPA exists — spec adapted to repo stack.
- Layout: `layouts/app.blade.php` renders `NavigationRegistry` tree (collapsible groups, permission-gated, icon paths).
- Shared: `x-app-layout`, status badges (`organization::partials.status-badge`), branding partials, announcement banners.
- Organization views existed: companies/branches/departments/locations index+create+edit+show, overview, structure, audit, settings — extended in Phase 1 (branch detail tabs, positions, administrators, settings).
- Patterns kept: Tailwind cards/tables, Alpine tabs (`x-data`), flash toasts, confirmation via form + `onsubmit` confirm, pagination via `->paginate()`.
