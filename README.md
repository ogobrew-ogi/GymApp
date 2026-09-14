# Gym Training Reservation System — Final Package

This package is a complete snapshot of the project as of this handoff:
approved specifications, plus the full Laravel 12 / Livewire 3 / Tailwind 4
codebase built against them.

## Contents

```
docs/
  project-brief.md               Original approved brief (v1.0, unmodified,
                                  with one superseded-decision note appended)
  architecture-specification.md  v2.0 — final architecture, incl. accepted
                                  engineering recommendations (§12)
  domain-model-specification.md  v1.2 — full schema blueprint, cascade
                                  rules, decision log (§10)
  ux-ui-specification.md         v1.1 — screen-by-screen UX spec, user
                                  journey analysis (§19)
gym-app/                         Full application source code
```

## Reading order (for anyone picking this up fresh)

1. `project-brief.md` — what this is and isn't, and why.
2. `architecture-specification.md` — the binding technical decisions.
3. `domain-model-specification.md` — exact schema, cascade behavior, enums.
4. `ux-ui-specification.md` — every screen, state, and message, plus the
   user journey walkthroughs.

Each later document explicitly cross-references the earlier ones where a
rule originates — if a decision seems arbitrary in one file, its
justification is almost always numbered and cited in an earlier one.

## Getting the app running

This codebase was written without a live PHP/Composer environment
available to execute it (noted throughout the build). Every file was
hand-written and visually reviewed against the specs, but has not been
run. Before relying on it:

```bash
cd gym-app
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build   # or `npm run dev` while developing
php artisan serve
```

Recommended first real check: `php artisan migrate:fresh` to confirm the
six migrations (users/sessions, cache, jobs, trainings,
training_participants, gym_closures) run cleanly in order, then manually
walk through: create a user via `php artisan tinker` (no seeder exists
yet), log in, create a training, join it from a second account, and try
the overlap/gym-closure flows.

## What's implemented

- Full auth (login, forced password change, logout), no email/reset flow
- Home Screen (chronological list), Create/Edit/Show Training, with the
  overlap "join instead" flow and the field-lock-after-join rule
- Join/Leave/Cancel/Delete, all through Policy + Action + Service layers
- Admin: User Management (create/disable/reset password/delete with
  cascade-impact confirmation), Gym Closures (create with cascade-cancel
  preview + confirmation, delete)
- Scheduled command to auto-complete past trainings (`trainings:complete-past`)
- PWA scaffolding (Vite PWA plugin, manifest config, network-first pages)

## What's not implemented yet

- History screen (completed/cancelled trainings list) — UX/UI Spec §9.7
- Database seeders / factories (no sample data exists yet)
- Automated tests
- Actual PWA icons (referenced at `/icons/icon-192.png` etc. — not generated)
- Production deployment config (backup automation, process supervisor, etc.
  — recommended in Architecture Spec §12 but not wired up)
