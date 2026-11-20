# Frontend Audit — DEVIATION FROM BASELINE

**Baseline assumed:** Next.js/React feature-oriented SPA.
**Reality:** Server-rendered **Blade + Tailwind CSS + Alpine.js + Vite**, single app shell (`x-app-layout`), `NavigationRegistry`-driven permission-aware sidebar with module dropdowns.

## Existing design system (working — do not discard)
- `xerin` Tailwind palette (indigo family, `--sa-primary` brand tokens, configurable branding)
- Consistent conventions: `rounded-sm border-gray-200 bg-white shadow-sm` cards, status badges, KPI cards
- 173 module/app views; permission-gated menus; responsive sidebar collapse
- Recent upgrades: employee directory KPI cards, profile header, reception dashboard

## Gaps
- No English/Swahili i18n — hardcoded English strings throughout (`__()` rarely used). Needs centralized lang files + audit of user-facing text.
- No shared chart library — attendance trend rendered as CSS bars; reporting domain will need a chart component (recommend Chart.js or ECharts via npm, one library).
- No skeleton-loading components; loading states inconsistent.
- Accessibility: semantic but not audited for ARIA/keyboard.

## Decision needed
Keep Blade (fast, consistent, tested) vs. Next.js rebuild (baseline wish). **Recommendation: keep Blade** — rebuilding costs months with zero functional gain; put the effort into Domain B.
