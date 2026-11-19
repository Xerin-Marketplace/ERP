# UI Design System

Stack: **Blade + Alpine.js + Tailwind CSS** (server-rendered, no SPA).
Reference pattern: shadcn `sidebar-08` adapted to Blade.

## Principles

- Clean, enterprise, minimal decoration — no gradient-heavy surfaces.
- Sharp geometry: cards/panels `rounded-sm`; inputs `rounded-md`.
- Motion is functional: skeletons, top progress bar, toast fade — nothing else.
- Light + dark via `dark:` classes (dark ready, light is default).

## Tokens

| Token | Value |
|---|---|
| Surface | `bg-white`, page `bg-gray-50` |
| Border | `border-gray-200` |
| Text | `gray-900` / muted `gray-500` |
| Accent | `indigo-600` (links, focus rings, progress) |
| Radius | cards `rounded-sm`, inputs `rounded-md`, pills `rounded-full` |
| Shadow | `shadow-sm` only |
| Font | Instrument Sans (default Laravel) |
| Icons | Heroicons outline, `h-5 w-5` / `h-4 w-4`, `stroke-width 1.5` |

## Layout shell (`layouts/app.blade.php`)

- Collapsible icon sidebar (`w-64 → w-16`, persisted in localStorage)
- Mobile: slide-over drawer + overlay
- Header: collapse trigger + breadcrumbs + user dropdown
- Top loading progress bar (Alpine, runs on nav + form submit)

## Components

- **Stat card**: `bg-white border rounded-sm p-5` + label + big value + icon.
- **Table**: `text-sm`, `thead bg-gray-50 uppercase text-xs`, `divide-y`.
- **Badge**: `rounded-full px-2 py-0.5 text-xs` — gray/green/red/amber variants.
- **Toast** (`<x-toast>`): `window.toast('msg','success|error|info')`, top-right,
  auto-dismiss; session flash auto-fires on auth + dashboard.
- **Skeleton**: `.skeleton` shimmer class for loading panels.
- **Buttons**: Breeze `x-primary-button` (gray-800) — variants: primary / secondary
  (white+border) / danger (red-600) / ghost.

## Inputs

`x-text-input` supports `icon` slot (left icon inside input) and
`type="password"` gets eye toggle via Alpine. Keep labels `text-sm font-medium`.

## Rules for module UIs

- Build inside `x-app-layout`; use the page-header slot for title + breadcrumbs.
- Empty states: icon + one-line message + optional action — never blank boxes.
- Never invent per-module colors; use tokens above.
- Real data or explicit empty state — no static placeholder numbers.
