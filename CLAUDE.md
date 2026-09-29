# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

NutriTrace: a Laravel 12 (PHP 8.2) food-traceability app. A public site lets shoppers look up a product by lot code (`NT-AAAA-0000`) and read its supply-chain journey (producteur → transformateur → distributeur → consommateur), the kg CO₂e per step, and its labels (Bio / Local / Équitable). An admin area ("Espace pro", `/admin`) manages products. All UI copy is French (`APP_LOCALE=fr`).

## Commands

- `php artisan serve` runs the app. `composer dev` also starts the queue, the pail log viewer and Vite, but the pages don't need Vite (see Styling).
- `composer test` runs the tests (`php artisan test`).
- `php artisan test --filter=ExampleTest` runs a single test.
- `vendor/bin/pint` formats PHP.
- The database is SQLite (`database/database.sqlite`). The sessions, cache and queue drivers are all `database`.

## Architecture

- **Sample data lives in the routes file.** There are no Produit/Etape models yet. `routes/web.php` defines a global `produits()` function that returns a hard-coded array keyed by slug. All routes are closures that filter this array and pass it to views. The planned next step, marked by a `ponytail:` comment, is to replace it with Eloquent models `Produit` and `Etape`.
  - Each product has these keys: `slug`, `nom`, `categorie`, `lot`, `origine`, `certifs[]`, `etapes[]`.
  - Each entry in `certifs[]` has `type`, `statut` and `organisme`.
  - Each entry in `etapes[]` has `role`, `acteur`, `lieu`, `date` and `co2`.
  - Totals and percentages are computed inline in the Blade views.
- **Verified vs declared is a core rule.** A certification's `statut` is either `vérifié` or `déclaré`, never anything in between. Declared labels must stay visually distinct (a yellow "!" badge on the front, `tag-warn` in admin), and a product page shows an alert when a label is unverified.
- **Views.** There are two layouts, `layouts/front` and `layouts/admin`, with pages under `front/` and `admin/`.
  - Shared Blade components: `x-trace-route` (the journey timeline), `x-cert-badge` (label badge), `x-theme-toggle` (light/dark button).
  - The admin product form (`admin/produits/form.blade.php`) posts to `#`. There is no store route or validation yet.
  - The Acteurs and Certifications links in the admin nav are disabled placeholders.

## Styling

- The layouts do **not** use Vite or Tailwind. They load hand-written CSS from `public/css/front.css` and `public/css/admin.css`, plus fonts from Google Fonts (Bricolage Grotesque, Instrument Sans, JetBrains Mono). `resources/css/app.css` and `resources/js` are unused skeleton files.
- **Palette "Bocage".** Colors are CSS custom properties defined on `:root` at the top of each file.
  - `front.css` uses mist, paper, forest, moss, fern, sprout and pollen. `admin.css` uses the same colors under the names bg, surface, ink, ink-2, accent and harvest.
  - Any new color should be added as a token, not hard-coded.
- **Dark mode.** Each token block is redefined under `@media (prefers-color-scheme: dark)` (guarded by `:root:not([data-theme="light"])`) and again under `:root[data-theme="dark"]`. Keep all three blocks in sync when adding tokens.
  - The theme toggle saves the choice in `localStorage.theme`. An inline script in each layout's `<head>` applies it before the page renders.
- **Chart colors.** The admin dashboard donut charts use the series tokens `--s1`…`--s4`, one per step, in step order. They were checked for colorblind separation; re-validate them if you change them.
- **Accessibility.** Respect the existing skip links, `:focus-visible` outlines, `prefers-reduced-motion` blocks, and mobile breakpoints (820px on the front, 900px in admin).
- **Motion.** The load sequences are staggered with `animation-delay`. The front page times its steps in order: hero rise, then the trace line draws, then the stops arrive, then the CO₂ bars grow. If you change one delay, shift the others to keep that order. Front-page scroll reveals use CSS `animation-timeline: view()` inside `@supports`, so browsers without it simply show the content. Entrance animations use the `translate` property, not `transform`, so they don't fight the card hover `transform`. The theme toggle uses the View Transitions API for a circular reveal and skips it when reduced motion is requested.
