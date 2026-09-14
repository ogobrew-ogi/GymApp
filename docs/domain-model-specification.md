# Gym Training Reservation System — Domain Model Specification

Version: 1.2 — revised: User Delete no longer restricted by training history (§10); accepted engineering recommendations — UTC storage, transactional overlap checks, backup strategy, login throttling, migration sequencing, admin-action logging, cascade-impact confirmation copy (§11)
Status: Final — blueprint for migrations
Based on: Architecture Specification v2.0, UX/UI Specification v1.0 (both unchanged, both binding)

This document describes data, not code. No SQL, no PHP, no migrations. Every field below exists because a specific approved business rule requires it — where that isn't obvious, the reasoning is stated explicitly.

---

## 1. Entity: User

### Purpose
Represents a person authorized to use the private system — every member and administrator. There is no separate "Member" entity; a `User` with an admin flag *is* the administrator. Splitting these into two tables/models would duplicate authentication logic for no benefit, since every admin capability is additive to member capability, never a different identity.

### Responsibilities
- Authenticate into the system.
- Own trainings (as creator).
- Participate in trainings (as joiner).
- If flagged as admin: manage other users and gym closures.

### Fields

| Field | Type | Nullable | Default | Why it exists |
|---|---|---|---|---|
| `id` | unsigned big integer | No | auto-increment | Stable internal identity, referenced by every other entity. |
| `name` | string | No | — | Displayed everywhere another user needs to identify who owns/joined a training (UX/UI Spec §11: "Organized by [Name]", participant list) — this is the transparency principle made concrete. |
| `username` (or `email`) | string | No | — | Login identifier. A private, admin-provisioned system doesn't strictly need email (no verification, no password-reset email per architecture §5.9), so a plain `username` is arguably simpler than requiring a real email address for every member. If email is later wanted for convenience (e.g. distinguishing similarly-named members), it can be added as an optional field without restructuring — but it is not required for login in v1. |
| `password` | string (hashed) | No | — | Standard Laravel hashed credential. |
| `is_admin` | boolean | No | `false` | The entire role model (§6, UserRole). A boolean is sufficient because there are exactly two roles and admin is strictly additive — introducing a roles table/pivot here would be the "complex administration" the brief explicitly excludes. |
| `is_active` | boolean | No | `true` | Backs "Disable users" (Architecture §Roles). Disabling must not delete history — a disabled user's past trainings/participation must remain visible for transparency, so this is a flag, not a deletion. |
| `must_change_password` | boolean | No | `true` on creation | Backs the forced password-change flow (UX/UI Spec §9.9) triggered whenever an admin creates a user or resets a password. Without this flag, there is no way to distinguish "admin just set a temp password" from "user's normal password" at login time. |
| `created_at` | timestamp | No | now | Standard audit timestamp; also usable for a simple "member since" fact if ever surfaced — not required by any current screen, but a near-zero-cost standard Laravel column. |
| `updated_at` | timestamp | No | now, on change | Standard Laravel convention. |

### Validation Rules
- `name`: required, reasonable max length (e.g. 255), plain text.
- `username`: required, unique, reasonable max length, no formatting requirements beyond uniqueness (this is an internal tool, not a public-facing product — no need for complex username policies).
- `password`: required on creation, meets a simple minimum-strength rule per UX/UI Spec §9.9 ("simple minimum rule, not a complex policy").
- `is_admin`, `is_active`, `must_change_password`: booleans, no user-facing validation (system/admin-set only).

### Business Constraints
- A user cannot deactivate or delete themselves — this is an admin-only action performed on *other* accounts, and should be enforced at the authorization layer (out of scope for this document, but the data model must not prevent an admin from disabling every user *except* have no built-in protection against self-lockout; this is a policy-layer concern, not a schema concern, and is noted here only so it isn't lost).
- A disabled user (`is_active = false`) cannot log in, but their historical ownership/participation records remain fully intact and visible — this is why disabling is a flag, not a deletion, and why `User` is never hard-deleted while it has *any* historical trainings attached (see Cascade Behavior below).

### Relationships
- **User → Training**: one User owns many Trainings (`owner_id` foreign key on Training). One-to-many.
- **User ↔ Training** (via TrainingParticipant): many-to-many. A User participates in many Trainings; a Training has many participant Users.
- **User → GymClosure**: one User (an admin) creates many GymClosures (`created_by` foreign key). One-to-many.

### Cascade Behavior
- **Confirmed decision: the administrator may Delete or Disable any User regardless of training history.** Deleting a User cascades: every Training they own is deleted along with it (which in turn cascades to that Training's TrainingParticipant rows, per §2/§3), and every TrainingParticipant row where they are the participant (on someone else's Training) is deleted as well — freeing that slot for others.
- This is a deliberate simplicity trade-off against pure historical transparency: if a User who organized past trainings is deleted, those trainings disappear from everyone's History, not just that user's. Disabling (`is_active = false`) remains the non-destructive alternative when an admin wants to revoke access while keeping the historical record intact — Delete is now understood as genuinely destructive, by design, not a soft alias for Disable.
- Disabling was never conditioned on history in the first place — it is a flag, unaffected by this decision.

### Indexes
- Unique index on `username`.
- Index on `is_active` (used to filter active/disabled users in Admin User Management list).

---

## 2. Entity: Training

### Purpose
The core object of the entire system — a single scheduled session at the gym, owned by the member who created it, joined by other members, and tracked through its lifecycle (`scheduled` → `completed`/`cancelled`).

### Responsibilities
- Represent one specific block of time at the gym.
- Track who owns it and who is participating.
- Track its current lifecycle status.
- Enforce (in conjunction with the service layer) the one-training-at-a-time and duration/window rules.

### Fields

| Field | Type | Nullable | Default | Why it exists |
|---|---|---|---|---|
| `id` | unsigned big integer | No | auto-increment | Primary identity, referenced by TrainingParticipant. |
| `owner_id` | unsigned big integer (FK → User) | No | — | Identifies who created the training and who holds owner-only permissions (edit/cancel/delete). Immutable after creation — ownership is never transferred (Architecture §5.7) — so this field has no update path in the application layer once set, though the column itself is a normal foreign key. |
| `date` | date | No | — | Kept as a distinct field from the datetime start, purely to make the Home Screen's "Today / Tomorrow / [date]" grouping (UX/UI Spec §10) a trivial, index-friendly lookup rather than requiring a datetime-to-date extraction on every query. This is a deliberate denormalization for read performance on the single most-hit screen in the app. |
| `start_time` | datetime (stored UTC) | No | — | Full date+time so overlap comparisons are simple direct comparisons, not composed from separate date/time columns. **Stored in UTC, converted to/from `Europe/Sofia` only at the input/display boundary** (see §11.1) — this avoids ambiguous/invalid local times around the DST transition twice a year, while the architecture's "single timezone, no per-user handling" rule is unaffected, since Europe/Sofia remains the only timezone ever shown to a user. |
| `end_time` | datetime (stored UTC) | No | — | Same reasoning as `start_time`. Must be strictly after `start_time` (business constraint, not a schema-level type difference). |
| `max_participants` | unsigned small integer | No | — | Represents the effective gym capacity for this session (Architecture §2, capacity concept removed in favor of this per-training field). No system-wide default is meaningful — every training's context differs, so this is always explicitly set by the creator, never auto-filled. |
| `note` | string, max ~200 chars | Yes | `null` | Optional context from the owner (e.g. "bring gloves"). Nullable because most trainings won't need one — forcing a value here would violate "as few clicks as possible." |
| `status` | enum: TrainingStatus | No | `scheduled` | The lifecycle state driving every visibility and permission rule in the system (§4, §7). |
| `created_at` | timestamp | No | now | Standard; also usable to break ties if ever needed, though not currently required by any rule. |
| `updated_at` | timestamp | No | now, on change | Standard Laravel convention; reflects the most recent edit, cancellation, or completion transition. |

Notably **absent**: no `gym_capacity` field or reference — removed entirely per Architecture §2. No `cancelled_reason` field — per the earlier confirmed simplification, a plain `cancelled` status is sufficient; the system does not distinguish "owner cancelled" from "closure cancelled" in the data model.

### Validation Rules (business-rule validation, enforced at the service layer, not raw column constraints)
- `end_time` must be strictly after `start_time`.
- Duration (`end_time - start_time`) must be between 30 minutes and 4 hours inclusive.
- `date`/`start_time` must not be more than 30 days from the current moment.
- `max_participants` must be at least 1, and — on edit — never below the current participant count.
- `note`, if present, must not exceed ~200 characters and must be plain text (no markup persisted, regardless of what was submitted).
- No overlapping `scheduled` Training may exist for the requested window (the central rule — see §7).
- No `scheduled` Training may be created inside an active/future `GymClosure` window.

### Business Constraints
- Exactly one `scheduled` Training may exist for any given moment in time, system-wide (Architecture §1 — "one training at a time"). This is the single most important invariant in the whole schema and is why no gym-wide capacity table or per-slot aggregation logic is needed anywhere else.
- Once `status` leaves `scheduled`, the row becomes immutable in every business sense — no further edits, no further participants, no un-cancelling. (The schema does not need a hard database-level "immutable" mechanism; this is enforced at the service/policy layer, but it is the model's implicit contract.)
- `owner_id` never changes after creation.

### Relationships
- **Training → User** (owner): many Trainings belong to one User. Many-to-one.
- **Training ↔ User** (participants, via TrainingParticipant): many-to-many.
- **Training → GymClosure**: not a direct foreign key relationship — a Training is cancelled *as a consequence of* a closure overlapping its window, but a Training row does not need to reference *which* closure caused it, per the confirmed simplification (no `cancellation_reason`/closure-link field). The relationship is behavioral (evaluated at closure-creation time), not structural.

### Cascade Behavior
- If a Training is deleted directly by its owner (only possible per business rule when the owner is the sole participant — Architecture §5.2), its single TrainingParticipant row (the owner's own auto-join record) cascade-deletes with it.
- **A Training is also deleted as a side effect of its owner being deleted by an admin** (see §1 Cascade Behavior, updated) — this is now the one case where a Training disappears regardless of participant count or status (`scheduled`, `completed`, or `cancelled` alike), since the cascade originates from the User side, not a Training-specific business rule. Disabling a user (as opposed to deleting) has no effect on their existing Trainings at all.

### Indexes
- Index on `status` (nearly every query — Home, History — filters by status first).
- Composite index on (`status`, `start_time`) — supports both "all scheduled trainings, chronological" (Home) and the overlap-check query (find any `scheduled` training whose window intersects a candidate range) efficiently.
- Index on `owner_id` (supports "my trainings" style lookups, e.g. for edit/delete permission checks).
- Index on `date` (supports the Home Screen's date-grouped rendering).

### Unique Constraints
- None beyond the primary key. Uniqueness of "no overlapping scheduled training" is a *range* constraint (overlap, not equality), which relational unique constraints cannot express — this must be enforced in the service layer at write time, not the schema layer. This is worth stating explicitly since it's the one rule the database itself cannot fully guarantee.

---

## 3. Entity: TrainingParticipant

### Purpose
The join record between a User and a Training — represents "this person is in this session," including the owner's own automatic membership.

### Responsibilities
- Record who is attending a given Training.
- Provide the count that drives `max_participants` capacity checks and the "X of Y going" display.
- Distinguish, implicitly, the owner's row from everyone else's (by comparing `user_id` to the parent Training's `owner_id` — no separate "is_owner" flag needed, since that would be redundant, derivable data).

### Fields

| Field | Type | Nullable | Default | Why it exists |
|---|---|---|---|---|
| `id` | unsigned big integer | No | auto-increment | Standard primary key — even though the natural key is (`training_id`, `user_id`), a surrogate key keeps this consistent with the rest of the schema and avoids composite-key friction in the ORM layer. |
| `training_id` | unsigned big integer (FK → Training) | No | — | Which training this participation record belongs to. |
| `user_id` | unsigned big integer (FK → User) | No | — | Who is participating. |
| `joined_at` | timestamp | No | now | Records when the person joined — not currently surfaced on any screen, but low-cost and potentially useful for ordering the participant list (e.g. owner first, then join order) without deriving it from `id` alone. |

Notably **absent**: no `is_owner` boolean (derivable from comparing `user_id` to `Training.owner_id` — storing it would create a second source of truth that could drift out of sync), no `status` field (a participant is either present in this table or not — there is no "pending"/"invited" state in this system, since joining is immediate and unconditional per the architecture).

### Validation Rules
- A (`training_id`, `user_id`) pair must be unique — a user cannot join the same training twice (Architecture §5.2, "duplicate participation impossible").
- A row for the owner is created automatically and atomically at Training creation — never left to a separate manual "join" action.

### Business Constraints
- **The owner's row can never be removed** via the normal "leave" action (Architecture §5.4 — owner cannot leave). The owner's participation row is only ever removed as a side effect of the entire Training being deleted (solo-owner delete case).
- A user cannot join if `max_participants` is already reached, or if the Training's status is not `scheduled`, or if the user already has a conflicting `scheduled` training in an overlapping window (this last case is now a rare/edge scenario given the one-training-at-a-time rule, but still structurally possible if, e.g., a user manages to be a participant in a training whose window later gets edited — noted for completeness, enforced at the service layer).

### Relationships
- **TrainingParticipant → Training**: many-to-one (many participant rows belong to one Training).
- **TrainingParticipant → User**: many-to-one (many participant rows belong to one User, across different trainings over time).
- Functions as the resolving pivot for the User ↔ Training many-to-many relationship described in §1 and §2.

### Cascade Behavior
- **Cascade delete from Training**: if a Training row is deleted — whether via the owner's own solo-delete action, or as a cascade side effect of the owner being deleted (see §1, §2) — every TrainingParticipant row for that Training is deleted with it.
- **Cascade delete from User**: a TrainingParticipant row is also deleted directly whenever the referenced User is deleted, even when the Training itself survives (i.e., someone else's training that this user had merely joined) — this removes them from that training's participant list and frees their slot for others.
- A Training being *cancelled* or *completed* does **not** touch TrainingParticipant rows at all — participation history remains exactly as it was, which is what makes the History screen meaningful (Architecture §5.7, transparency), **provided neither the owner nor the participant has since been deleted** (as opposed to disabled).

### Indexes
- Composite unique index on (`training_id`, `user_id`) — this is simultaneously the uniqueness constraint and the primary lookup path ("is this user already in this training?").
- Index on `user_id` alone — supports "does this user have a conflicting training" checks and any future "my trainings" view.

### Unique Constraints
- Unique on (`training_id`, `user_id`) — the single most important constraint on this table; it is what makes duplicate participation structurally impossible, not just application-checked.

---

## 4. Entity: GymClosure

### Purpose
Represents a period during which the gym is unavailable for training — blocking new training creation and retroactively cancelling anything already scheduled inside the window.

### Responsibilities
- Define a blocked time window and its reason.
- Trigger cancellation of any `scheduled` Training that falls inside it, at the moment the closure is created.
- Record which admin created it, for accountability (not currently surfaced on any member-facing screen, but a natural, low-cost fact to retain).

### Fields

| Field | Type | Nullable | Default | Why it exists |
|---|---|---|---|---|
| `id` | unsigned big integer | No | auto-increment | Primary identity. |
| `starts_at` | datetime | No | — | Start of the blocked window. |
| `ends_at` | datetime | No | — | End of the blocked window; must be strictly after `starts_at`. |
| `reason` | enum: ClosureReason | No | — | Drives the label shown in the closure list and in the "gym closed" message a member sees when attempting to create a training during that window (UX/UI Spec §14: "The gym is closed during that time ([reason])"). |
| `note` | string, nullable | Yes | `null` | Optional free-text elaboration (e.g. "Roof repair, expect noise") — mirrors Training's optional note field in spirit, kept simple and unconstrained beyond a reasonable max length. |
| `created_by` | unsigned big integer (FK → User) | No | — | Which admin created the closure — accountability/traceability, consistent with treating admin actions as auditable even though there's no dedicated audit log entity (see §9, "What Should Not Exist"). |
| `created_at` | timestamp | No | now | Standard. |
| `updated_at` | timestamp | No | now, on change | Standard — relevant if a future closure's dates are edited before it takes effect. |

### Validation Rules
- `ends_at` must be strictly after `starts_at`.
- `reason` must be one of the defined ClosureReason values.
- `note`, if present, reasonable max length, plain text.
- No specific overlap-prevention rule between closures is required — per UX/UI Spec §9.11, overlapping closures are "flagged for admin awareness rather than blocked," since this is a low-frequency, admin-only scenario where blocking would add friction without protecting anything important.

### Business Constraints
- Creating a GymClosure whose window overlaps any `scheduled` Training must, in the same operation, transition every such Training to `cancelled` (Architecture §5.5). This is a write-time side effect, not a background job — it must happen atomically with closure creation.
- A Training cannot be created (i.e., its `scheduled`-state validation must fail) if its window overlaps any GymClosure with `ends_at` in the future — past closures no longer block anything.

### Relationships
- **GymClosure → User** (creator): many GymClosures belong to one User (admin). Many-to-one.
- **GymClosure ↔ Training**: no direct structural relationship (foreign key) — the effect of a closure on existing trainings is a one-time, evaluated side effect at closure-creation time, not an ongoing structural link. This mirrors the decision in §2 not to store a `cancellation_reason`/closure reference on Training.

### Cascade Behavior
- **`created_by` remains Restrict, not Cascade** — this is a deliberate, narrower scope than the User-deletion cascade described in §1/§2/§3. The confirmed instruction to disregard history applied specifically to *training* history when deleting/disabling a member; it did not extend to admin-created closure records. Under this reading, an admin account cannot be hard-deleted while it has created any GymClosure — it can still be Disabled freely, same as any other user. If this should instead cascade the same way (deleting an admin also deletes the closures they created), that's a one-line change to confirm before migrations, since it's a different call than the training-history one you just made.
- Deleting a GymClosure itself (e.g. an admin removing a future one they created by mistake) has no cascade effect on any Training — because, per above, no structural link exists. Any Trainings already cancelled as a result of that closure remain cancelled; removing the closure does not "un-cancel" them, since that would silently resurrect a Training the owner/participants may have already moved on from — a resurrection is not a feature this system offers.

### Indexes
- Index on `ends_at` (used to filter "closures still relevant," i.e. `ends_at > now()`, in both the blocking check and the admin list).
- Composite index on (`starts_at`, `ends_at`) — supports the overlap check against candidate Training windows.

### Unique Constraints
- None required.

---

## 5. Enums

### 5.1 UserRole
Not implemented as a stored/persisted enum value — represented instead by the `is_admin` boolean on User (see §1). A dedicated `UserRole` enum type is still worth defining in the domain vocabulary (for use in code/policies as `UserRole::Admin` / `UserRole::Member`), but it is **derived** from `is_admin`, not an independent stored column. Storing role as a separate enum column when there are exactly two, strictly-additive roles would be redundant with the boolean and only useful if a third role were coming — which the brief explicitly defers ("Coach accounts" is future scope, not v1).

Values: `Member`, `Admin`.

### 5.2 TrainingStatus
Persisted as a real column on Training (see §2). This is the central lifecycle enum of the system.

Values:
- `Scheduled` — active, visible on Home, joinable/editable per the rules in §7.
- `Completed` — end_time has passed; terminal, historical.
- `Cancelled` — owner-cancelled or closure-cancelled; terminal, historical.

No `Draft` or `Pending` value — trainings are created directly into `Scheduled`; there is no approval workflow (explicitly out of scope — "the administrator does NOT manage trainings").

### 5.3 ClosureReason
Persisted as a real column on GymClosure (see §4).

Values: `Maintenance`, `Competition`, `Seminar`, `Holiday`, `Other` — directly from the Project Brief's Gym Rules section, no additions or omissions.

### 5.4 Recommended Additional Enum: none beyond the above
No further enums are recommended. Specifically considered and rejected:
- A `ParticipantRole` enum on TrainingParticipant (e.g. `Owner`/`Participant`) — rejected because ownership is already fully derivable from `Training.owner_id`, and storing it redundantly risks drift (see §3).
- A `CancellationReason` enum on Training — rejected per the already-confirmed simplification that a plain `Cancelled` status is sufficient; adding this now would be scope creep beyond what was approved.

---

## 6. Database Constraints Summary

### Primary Keys
Every entity (`User`, `Training`, `TrainingParticipant`, `GymClosure`) has a single surrogate auto-incrementing integer primary key (`id`). No natural/composite primary keys are used, even where a natural key exists (e.g. TrainingParticipant's (`training_id`,`user_id`)), to keep foreign-key references and ORM behavior simple and consistent across the schema.

### Foreign Keys
| From | To | Column |
|---|---|---|
| Training | User | `owner_id` |
| TrainingParticipant | Training | `training_id` |
| TrainingParticipant | User | `user_id` |
| GymClosure | User | `created_by` |

### Unique Constraints
| Entity | Columns |
|---|---|
| User | `username` |
| TrainingParticipant | (`training_id`, `user_id`) |

### Check Constraints (business-meaning, enforced primarily at the service/validation layer — the underlying database engine's ability to enforce these varies and should not be solely relied upon)
- Training: `end_time > start_time`.
- Training: duration between 30 minutes and 4 hours.
- Training: `max_participants >= 1`.
- GymClosure: `ends_at > starts_at`.

These are stated here as domain rules the schema should support (e.g. via appropriate column types), not as a claim that raw SQL check constraints are the enforcement mechanism — given SQLite's limited/optional check-constraint support and the fact that these rules interact with *other rows* (overlap checks) which check constraints cannot express at all, the service layer remains the actual authority. This is a deliberate, simple choice consistent with "keep the architecture simple."

### Indexes (consolidated)
| Entity | Index |
|---|---|
| User | unique(`username`); index(`is_active`) |
| Training | index(`status`); composite(`status`,`start_time`); index(`owner_id`); index(`date`) |
| TrainingParticipant | unique(`training_id`,`user_id`); index(`user_id`) |
| GymClosure | index(`ends_at`); composite(`starts_at`,`ends_at`) |

### Cascade Delete Rules
| Relationship | Rule | Why |
|---|---|---|
| Training deleted → TrainingParticipant | Cascade | Whenever a Training row is removed (owner's solo-delete action, or as a further cascade from the owner being deleted), its participant rows go with it. |
| User deleted → Training (`owner_id`) | Cascade | **Confirmed decision:** admin may delete a User regardless of training history; every Training they own is deleted as a consequence. |
| User deleted → TrainingParticipant (`user_id`) | Cascade | Same decision — a deleted User's participation rows on *other* users' trainings are removed too, freeing their slot. |

### Restrict Delete Rules
| Relationship | Rule | Why |
|---|---|---|
| User referenced by GymClosure.created_by | Restrict | Scoped narrower than the training-history decision above — an admin who has created closures cannot be hard-deleted (Disable remains available). Flagged in §4 as worth explicit confirmation if you'd rather this also cascade. |

No relationship in this system uses "Set Null" — every foreign key here is meaningful and required; there is no case where a relationship becoming optional after the fact makes business sense (e.g. a Training with no owner, or a closure with no creator, are not valid states). Note that `owner_id` and `user_id` no longer need Set Null as an alternative to Restrict either, since Cascade now fully resolves what happens on User deletion.

---

## 7. Relationships — Full Description

- **User owns many Trainings.** One User (as owner) → many Training rows via `owner_id`. A Training has exactly one owner, fixed at creation, never transferred.
- **User participates in many Trainings, and a Training has many participants.** Many-to-many, resolved through TrainingParticipant. This includes the owner, who is always also a participant — there is no case of a Training with an owner who isn't in the participant list.
- **Training has many TrainingParticipants; TrainingParticipant belongs to exactly one Training.** One-to-many.
- **User has many TrainingParticipants (across different trainings, over time); TrainingParticipant belongs to exactly one User.** One-to-many.
- **User (admin) creates many GymClosures; GymClosure belongs to exactly one creating User.** One-to-many.
- **GymClosure and Training have no direct structural relationship** — their interaction is behavioral only (evaluated once, at closure-creation time), as explained in §4.

Cardinality summary:
- User : Training (owned) = 1 : N
- User : Training (participated) = N : N (via TrainingParticipant)
- Training : TrainingParticipant = 1 : N
- User : TrainingParticipant = 1 : N
- User : GymClosure = 1 : N

---

## 8. Business Rules by Entity (Consolidated Reference)

### User
- Only an admin can create, disable, delete, or reset the password of a User.
- **Delete and Disable are both available regardless of training history** (confirmed decision). Disable is non-destructive — the User cannot log in but every historical record they're linked to remains intact. Delete is destructive — it cascades to every Training they own and every Training they'd joined, per §6.
- `must_change_password` forces a password change immediately after any admin-initiated password set, before any other screen is reachable.

### Training
- Exactly one `scheduled` Training may exist for any given time window, system-wide — no overlap, including partial overlap, is ever permitted.
- Duration must be between 30 minutes and 4 hours.
- Must be scheduled no more than 30 days in advance.
- Cannot be created inside an active/future GymClosure window.
- Once other participants have joined: `date`, `start_time`, `end_time` become immutable; only `note` and `max_participants` (increase-only, floor = current participant count) remain editable.
- Can only be hard-deleted while the owner is the sole participant; otherwise the owner must cancel instead.
- Automatically transitions to `completed` once `end_time` has passed (system-driven, not user-driven).
- Automatically transitions to `cancelled` if a new GymClosure is created over its window (system-driven).

### TrainingParticipant
- The owner is automatically inserted as a participant at Training creation — never a separate action.
- A user cannot join a Training more than once (structurally impossible via the unique constraint).
- A user cannot join a full Training (`participant count >= max_participants`).
- A user cannot join a Training that is not `scheduled`.
- The owner's participation row can never be removed via "leave" — the owner has no leave path, only delete (solo) or cancel (with others).

### GymClosure
- Blocks creation of any new Training whose window overlaps it, for as long as `ends_at` is in the future.
- At creation time, automatically and atomically cancels every existing `scheduled` Training whose window overlaps it.
- Removing a closure does not retroactively un-cancel any Training that was cancelled because of it.

---

## 9. What Should Not Exist

Explicitly excluded from this domain model, consistent with the Project Brief and Architecture Specification:

- **Soft deletes** on any entity. Training uses a real `cancelled`/`completed` status for "kept but inactive," which already satisfies every retention need — a parallel `deleted_at` mechanism would be redundant and would complicate every query with an extra invisible filter. User is never soft-deleted either; it is disabled via `is_active`, which is a real, visible business state, not a hidden deletion marker.
- **A separate GymCapacity/Settings table.** Removed per Architecture §2 — per-training `max_participants` is the only capacity concept in v1.
- **An audit log table.** No generic polymorphic "activity log" entity. The small amount of accountability needed (who created a closure, who owns a training) is captured directly as foreign keys on the relevant entities, not a separate audit trail.
- **A notifications table.** Push notifications are explicitly deferred to a future version; no schema is pre-built for them now, per "never over-engineer" and "should not be implemented in version 1."
- **Payment, invoice, or membership-billing tables.** Explicitly out of scope in the Project Brief.
- **Chat, comment, like, or forum tables.** Explicitly out of scope.
- **A repository/abstraction table or generic "settings" key-value table.** Not needed since there are currently zero runtime-configurable settings after the removal of gym capacity (see above) — timezone and other values are application configuration, not domain data.
- **A `ParticipantRole`/`is_owner` flag on TrainingParticipant.** Derivable from `Training.owner_id`; storing it separately would be redundant, unsynchronized state.
- **A `cancellation_reason` field on Training, or a Training↔GymClosure foreign key.** Explicitly simplified away in the approved architecture discussion — a plain `Cancelled` status is sufficient.
- **Multi-gym or coach-role structures.** Both explicitly deferred future features; no columns, tables, or nullable "gym_id" placeholders are pre-built for them, since speculative schema for unapproved features is exactly the over-engineering this project's philosophy warns against.
- **Email verification fields** (e.g. `email_verified_at`) — there is no email-based registration or verification flow in this system at all.

---

## 10. Decision Log

**Resolved — User hard-delete regardless of history (this revision).** Earlier drafts of this document restricted User deletion to accounts with zero training history, treating "Delete" as effectively unavailable otherwise. This has been overridden by explicit instruction: an administrator may Delete *or* Disable any User regardless of training history. Deletion now cascades to owned Trainings and to TrainingParticipant rows on trainings the user had joined (§1, §2, §3, §6). Disable remains the non-destructive option when history should be preserved.

**Still open — GymClosure.created_by on admin deletion.** §4/§6 currently keep this as Restrict, on the reading that the history-disregard instruction was scoped to *training* history specifically, not closure records. If you'd like admin deletion to cascade to their created closures as well (for full consistency with the User-deletion rule), confirm and I'll update it before migrations — it's a small, contained change at this stage.

---

## 11. Approved Engineering Recommendations (This Revision)

The following were proposed as engineering recommendations — consistent with, not additions to, the approved Project Brief/Architecture/UX-UI Spec — and have been accepted. They affect implementation details, not domain scope.

### 11.1 UTC Storage for Training/GymClosure datetimes
All `datetime` columns (`Training.start_time`, `Training.end_time`, `GymClosure.starts_at`, `GymClosure.ends_at`) are stored in UTC. Conversion to/from `Europe/Sofia` happens only at the application boundary (form input parsing, and display formatting). This guarantees correct, unambiguous overlap comparisons across the twice-yearly DST transition, without introducing any per-user timezone concept — every user still only ever sees Europe/Sofia local time.

### 11.2 Transactional, Locked Overlap Checks
The "one training at a time" invariant (§2) and the closure-overlap check (§4) must be enforced inside a single database transaction that reads the current `scheduled` Trainings/GymClosures and writes the new row atomically — not as two separate steps (check, then insert) with a race window between them. On SQLite this relies on the engine's serialized-writer behavior plus an appropriate `busy_timeout`, which is sufficient at this application's scale without introducing external locking infrastructure.

### 11.3 SQLite Backup Strategy
Since the entire dataset lives in a single SQLite file, a scheduled, automated backup (e.g. a periodic file copy to separate storage, or a streaming-replication tool) is required from day one. This is an operational/deployment concern, not a schema change — no new entity or column is introduced by it.

### 11.4 Login Throttling
The login route is protected by rate limiting (Laravel's built-in throttle mechanism) to mitigate brute-force credential guessing, consistent with the Project Brief's "Secure authentication" requirement. No schema impact.

### 11.5 Migration Sequencing Given SQLite's ALTER TABLE Limits
Because SQLite's support for altering existing columns/constraints is limited compared to MySQL/Postgres, the initial migrations should encode every field, type, nullability, and index from this specification as close to final as possible, rather than planning to iterate via many small follow-up migrations. This is a process note for the next phase, not a domain change.

### 11.6 Logging of Destructive Admin Actions
No audit-log **table** is introduced (§9 still applies — that decision is unchanged). Instead, two specific destructive admin actions write a plain application log entry (not a queryable domain entity): deleting a User (logged with the count of cascaded Trainings/participations removed) and creating a GymClosure that cancels existing Trainings (logged with the count cancelled). This is observability, not a new data model.

### 11.7 UI Confirmation Shows Cascade Impact
The existing destructive-action confirmation pattern (UX/UI Spec §9.6, §9.10) is extended so that confirming a User deletion explicitly states how many Trainings will be deleted as a result, before the admin confirms — using data already available (the same counts computed for §11.6's log entry), not a new screen or component.

---

This specification is the complete blueprint for the migrations phase. No entity, field, or rule here introduces anything beyond what the Project Brief, Architecture Specification, and UX/UI Specification already approved, plus the engineering recommendations formally accepted in §11.
