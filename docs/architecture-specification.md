# Gym Training Reservation System — Architecture Specification

Version: 2.0 (Final, incorporates Architecture Decisions round)
Status: Approved — ready for implementation planning
Supersedes: Project Definition v1.0 (business rules below are authoritative where they refine v1.0)

---

## 1. System Model in One Sentence

A private Laravel 12 + Livewire PWA where the gym has **exactly one training running at any moment in time**; members create, join, and manage that shared schedule with no overlap ever permitted.

This single decision (one training at a time) is the architectural backbone of the whole system — it eliminates the need for gym-wide capacity aggregation, simplifies conflict detection to a single query, and keeps the UI a flat chronological list instead of a calendar/grid.

---

## 2. Domain Entities

### 2.1 User
| Field | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | string | |
| username or email | string, unique | Used as login identifier. No email verification required; email can still be stored as a unique identifier without a verification flow. |
| password | hashed string | Admin-assigned initial value |
| is_admin | boolean | Simple role flag — no need for a roles/permissions package at this scale |
| is_active | boolean | Admin can disable a user without deleting (preserves historical participation records) |
| must_change_password | boolean | Set true when admin resets/creates a password; forces change on next login |
| timestamps | | |

**Design note:** A full RBAC package (spatie/permission) would be over-engineering for two roles. A single `is_admin` boolean plus Laravel Policies is sufficient and keeps the architecture simple, per the project's own design principle.

### 2.2 Training
| Field | Type | Notes |
|---|---|---|
| id | bigint PK | |
| owner_id | FK → users | Immutable after creation (ownership never transfers) |
| date | date | Stored in Europe/Sofia local date |
| start_time | datetime | Stored as full datetime (date + time combined) for simpler comparisons |
| end_time | datetime | Must be > start_time |
| max_participants | unsigned int | ≥ current participant count at all times |
| note | string, nullable, max 200 chars | Plain text, no HTML/Markdown allowed — sanitized/stripped on save, not just on display |
| status | enum: `scheduled`, `completed`, `cancelled` | See state machine §4 |
| created_at / updated_at | | |

**No soft deletes needed for Training** — "delete" is a real hard delete (only allowed when solo), and "cancel" is a status transition, not a deletion. Completed/cancelled trainings simply remain rows with a status, which already satisfies "archived means kept for history."

### 2.3 TrainingParticipant (pivot)
| Field | Type | Notes |
|---|---|---|
| id | bigint PK | |
| training_id | FK → trainings | |
| user_id | FK → users | |
| joined_at | timestamp | |

Unique constraint on (`training_id`, `user_id`). The owner is inserted into this table automatically at training creation — the owner **is** a participant, not a separate concept, which keeps "leave" logic simple (see §5.7).

### 2.4 GymClosure
| Field | Type | Notes |
|---|---|---|
| id | bigint PK | |
| starts_at | datetime | |
| ends_at | datetime | |
| reason | enum: `maintenance`, `competition`, `seminar`, `holiday`, `other` | |
| note | string, nullable | |
| created_by | FK → users (admin) | |
| timestamps | | |

### 2.5 Settings
No dedicated settings table is needed in v1. The only former "setting" (gym capacity) has been removed entirely per decision #2. Timezone is hardcoded in `config/app.php` (`Europe/Sofia`), not a runtime setting. If a genuine runtime setting is needed later, a simple `settings` key/value table can be added without touching existing tables — this is a safe future extension point.

---

## 3. Core Invariants (Enforced at the Model/Service Layer, Not Just UI)

These must be enforced server-side regardless of UI state, ideally via a dedicated `TrainingConflictChecker` / `TrainingSchedulingService` class (single responsibility, easily unit-testable):

1. **No two `scheduled` trainings may overlap**, including partial overlap and edge-touching-with-overlap. Overlap test: `existing.start_time < new.end_time AND existing.end_time > new.start_time`. Only `scheduled` trainings are checked — `cancelled`/`completed` trainings never block new ones.
2. **Duration bounds:** `end_time - start_time` must be between 30 minutes and 4 hours inclusive.
3. **Future-scheduling window:** `date`/`start_time` must be ≤ 30 days from now. (Whether "now" resets daily at midnight or is a rolling 30×24h window should be confirmed — recommend rolling window from current timestamp for simplicity.)
4. **Cannot create a training during a closure period** — check against all `GymClosure` rows overlapping the requested window.
5. **`max_participants` can never be set below current participant count** (enforced both on creation, trivially satisfied since count=1, and on edit).
6. **A user cannot join a training whose `max_participants` is already reached**, and cannot join a training they're already in.
7. **A user cannot join a `scheduled` training that overlaps another `scheduled` training they're already part of** (personal conflict rule — note this is now a much cheaper check since it's really "am I already in *the* one active training that overlaps this window," given only one training can exist per time slot anyway. Practically this collapses to: since overlapping trainings can never coexist, this rule is now largely a safety net rather than a frequent real-world case — but still enforced for correctness).

---

## 4. Training State Machine

```
                 ┌─────────────┐
   create ─────▶ │  scheduled  │
                 └──────┬──────┘
                        │
        ┌───────────────┼───────────────────┐
        │                │                    │
        ▼                ▼                    ▼
   [end_time      [owner cancels,       [gym closure
    passes]        others joined]        created over
        │                │                this window]
        ▼                ▼                    ▼
  ┌───────────┐   ┌────────────┐      ┌────────────┐
  │ completed │   │ cancelled  │      │ cancelled  │
  └───────────┘   └────────────┘      └────────────┘

  (hard delete, not a state) ◀── owner deletes, still solo participant
```

Rules:
- `scheduled → completed`: automatic, via scheduled job (see §7), once `end_time` is in the past. Terminal state.
- `scheduled → cancelled`: via owner action (only if ≥2 participants) or automatically via new gym closure overlapping the training. Terminal state.
- `scheduled → (deleted)`: via owner action, only if owner is still the sole participant. Not a status — the row is removed.
- No transitions out of `completed` or `cancelled`.

---

## 5. Business Rule Details

### 5.1 Overlap → Suggest Join
On create-training validation failure due to overlap, the response is not a generic error — the system must look up the conflicting `scheduled` training and present a "Join this training instead" action inline, with that training's time, owner, and remaining slots. Since only one active training can exist at a time, this lookup is always at most one row.

### 5.2 Delete vs Cancel
- **Delete** — only when `participants().count() === 1` (owner only). Hard delete, including the pivot row (cascade).
- **Cancel** — only when `participants().count() > 1`. Sets `status = cancelled`. Row and all participant records remain for history.
- These are mutually exclusive actions in the UI: the button shown depends on current participant count, computed at render time.

### 5.3 Editing Rules
- While solo (owner is only participant): all fields editable (date, start_time, end_time, max_participants, note), subject to the same conflict/duration/window validation as creation.
- Once a second participant joins: `date`, `start_time`, `end_time` become immutable. Only `note` and `max_participants` remain editable, and `max_participants` can only move within `[current_participant_count, +∞)`.
- This is enforced via a Form Request / Policy check that inspects `participants_count` before allowing specific fields to be dirty — not just hidden in the UI.

### 5.4 Owner Cannot Leave
- The "Leave" action is simply not available to the owner in the UI, and blocked server-side in the policy/service layer regardless.
- Owner's only exit paths are Delete (solo) or Cancel (has participants).
- Ownership is permanently fixed to `owner_id` — no transfer mechanism exists or is needed in v1.

### 5.5 Gym Closures
- Creating a closure period triggers a check against all currently `scheduled` trainings overlapping `[starts_at, ends_at)`; any match is transitioned to `cancelled` automatically (same as owner-cancel, no participant-count restriction applies here since it's a system/admin-driven cancellation, not a user action).
- This should run inside the same DB transaction as closure creation to avoid inconsistent state.
- No new training can be created if its window overlaps any closure with `ends_at > now()`.

### 5.6 Completion
- A scheduled Laravel command (`trainings:complete-past`), run every few minutes via the task scheduler, finds all `scheduled` trainings with `end_time < now()` and sets `status = completed`. Idempotent by construction (only touches `scheduled` rows).
- This requires the Laravel scheduler (`schedule:run` via cron) to be active on the host — a deployment requirement to confirm with hosting.

### 5.7 Participants & Home Screen Visibility
- "View all trainings" includes `scheduled`, `completed`, and `cancelled` — default home view shows only `scheduled` (chronological, upcoming first), with completed/cancelled reachable via a simple filter/history view, not a separate menu structure (keeps navigation minimal per "no unnecessary menus").

---

## 6. Authorization (Laravel Policies)

A `TrainingPolicy` cleanly encodes the rules above:

| Ability | Rule |
|---|---|
| `create` | User must be active, gym not currently closed for the requested window (checked in Form Request, not policy, since it's data-dependent) |
| `update` | `user.id === training.owner_id`, and field-level restrictions per §5.3 |
| `delete` | `user.id === training.owner_id AND participants.count() === 1` |
| `cancel` | `user.id === training.owner_id AND participants.count() > 1` |
| `join` | `user not already joined AND participants.count() < max_participants AND status === scheduled AND no personal overlap` |
| `leave` | `user is participant AND user.id !== training.owner_id` |

A separate lightweight `AdminPolicy`/gate covers user management (create/disable/delete/reset password), restricted to `is_admin === true`.

---

## 7. Scheduled Jobs / Console Commands

| Command | Frequency | Purpose |
|---|---|---|
| `trainings:complete-past` | Every 5 min (adjustable) | Transition `scheduled` → `completed` |

Only one scheduled job is needed in v1. Gym-closure auto-cancellation is event-driven (happens at closure-creation time), not a polling job — simpler and immediate.

---

## 8. Application Structure (Laravel Conventions, Livewire)

```
app/
  Models/
    User.php
    Training.php
    TrainingParticipant.php   (or implicit pivot via belongsToMany)
    GymClosure.php
  Policies/
    TrainingPolicy.php
  Services/
    TrainingSchedulingService.php   // overlap checks, duration/window validation
    TrainingLifecycleService.php    // join/leave/cancel/delete/complete transitions
  Http/
    Requests/
      StoreTrainingRequest.php
      UpdateTrainingRequest.php
  Livewire/
    Trainings/
      TrainingList.php        // home screen, chronological feed
      TrainingForm.php        // create/edit, reused component
      TrainingCard.php        // single item: join/leave/cancel/delete actions
    Admin/
      UserManager.php
      ClosureManager.php
  Console/
    Commands/
      CompletePastTrainings.php
```

This keeps a clean separation: **Policies** answer "is this allowed," **Services** hold the actual business logic/queries (single responsibility, reusable from both Livewire components and console commands), and **Livewire components** stay thin — just wiring UI to services/policies. This satisfies SOLID (services depend on abstractions where useful, each class has one reason to change) without introducing unnecessary layers (no repository pattern, no CQRS — genuinely unneeded at this scale).

---

## 9. UI / UX (Mobile First)

- **Home screen**: flat chronological list of `scheduled` trainings, grouped by simple relative-date headers (Today / Tomorrow / date). One primary "+ Create Training" floating/fixed button, thumb-reachable.
- **Training card**: shows time, owner, participants (avatars or count), remaining slots, and a single primary action button whose label/behavior is contextual — Join / Leave / Cancel / Delete / Edit — never more than one primary action visible per card, to honor "as few clicks as possible."
- **Create/Edit form**: single screen, large touch targets, native date/time pickers. On overlap conflict, replace the error state with a "Join existing training" prompt rather than a dead-end validation message.
- **History view**: same card layout, filtered to `completed`/`cancelled`, reachable via a simple toggle/tab — not a separate complex admin-style report.

---

## 10. PWA Considerations

- Service worker caches static shell assets for fast repeat loads; the training list itself should not be aggressively cached offline-first, since staleness (showing a full/cancelled training as joinable) is worse than a brief loading state — a simple network-first strategy for data, cache-first for shell/static assets.
- Add to Home Screen manifest with gym branding, since usage pattern is "everyday use," similar to WhatsApp.
- No push notifications in v1 (explicitly deferred) — but service worker registration now makes that a additive change later, not a refactor, which satisfies the "allow future features without major refactoring" requirement.

---

## 11. Explicitly Confirmed Out of Scope (v1)

Unchanged from the original brief: payments, shop, billing, public profiles, chat/forum/comments/likes, public calendar, self-registration, email verification, gym-wide capacity as a separate concept (removed), coach accounts, multi-gym, push notifications, statistics/reports.

---

## 12. Operational & Security Recommendations (Accepted)

These are implementation-detail recommendations, not new business features — accepted alongside the Domain Model Specification's §11:

- **UTC storage, Sofia-only display.** All stored datetimes use UTC internally; conversion to `Europe/Sofia` happens only at input parsing and display. Keeps overlap checks correct across DST transitions without introducing per-user timezone logic.
- **Transactional overlap enforcement.** The one-training-at-a-time check and the gym-closure overlap check both run inside a single DB transaction with the write, closing the race window between "check" and "create."
- **Automated SQLite backups.** Since the whole dataset is one file, a scheduled backup (periodic copy or streaming replication) runs from day one — a deployment concern, not an app feature.
- **Login throttling.** Standard rate limiting on the login route, given there's no 2FA or email verification layer.
- **Migration sequencing.** Given SQLite's limited `ALTER TABLE` support, initial migrations aim to encode the full Domain Model Specification as close to final as possible rather than iterating via many follow-ups.
- **Plain-log observability for destructive admin actions.** Deleting a User and creating a Training-cancelling GymClosure both write a log entry with the affected-row count — no new audit table, per §9's original "no audit table" decision, which still stands.

## 13. Summary of What Changed From v1.0

| Area | v1.0 | v2.0 (Final) |
|---|---|---|
| Concurrent trainings | Implied possible | **Not allowed** — one at a time |
| Gym capacity | Separate global setting | **Removed** — `max_participants` per training is the capacity |
| Overlap definition | Unspecified | **Any overlap**, including partial/edge |
| Archiving | Ambiguous (separate storage?) | Status flag only, same table, never deleted |
| Delete/Cancel | Single "delete" concept | Two distinct actions based on participant count |
| Editing after join | Fully blocked | **Partially blocked** — note & max_participants (increase only, floor = current count) still editable |
| Leaving | Implied all members incl. owner | **Owner exempt** — can only delete or cancel |
| Gym closures | Blocks new trainings only | Also **auto-cancels** existing overlapping trainings |
| Password reset | Unspecified mechanism | Fully manual, no email infrastructure at all |
| Timezone | Unspecified | Hardcoded `Europe/Sofia` |
| Note field | Unspecified limits | Plain text, ≤200 chars, no markup |
| Scheduling window | Not specified | Max 30 days ahead |
| Duration bounds | Not specified | 30 min – 4 hours |

---

This specification is now considered the binding architecture reference. Implementation (migrations, models, policies, services, Livewire components) should proceed directly from this document without re-litigating the decisions above.
