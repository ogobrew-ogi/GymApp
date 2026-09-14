# Gym Training Reservation System — UX/UI Specification

Version: 1.1 — revised: Delete User confirmation now states cascade impact (§9.10)
Status: Final — for frontend implementation
Based on: Architecture Specification v2.0 (approved, unchanged)
Author role: UX/UI design pass — no architectural decisions altered

---

## 0. How to Read This Document

This document describes **experience**, not code. It tells a frontend developer exactly what a screen contains, what happens when, what it says, and what it must never do. Every rule here is compatible with the approved architecture — nothing here requires a new field, table, or business rule.

---

## 1. Design Philosophy

The application is a **utility**, not a product to explore. A member opens it to answer one question — "what's the plan, and am I in?" — and to act on it in seconds. Every design decision is judged against a single test:

> **Does this help someone do that faster, or does it just look nice?**

If a screen, menu, icon, or confirmation dialog doesn't directly serve "see the schedule, join, or create," it doesn't belong. The benchmark is WhatsApp: a chat list, one thread, one input. Not Google Calendar: grids, layers, settings panels. The user should never feel like they're "using an app" — it should feel like checking a shared note on the fridge.

Three non-negotiables:
1. **One glance, one answer.** The home screen alone must answer "when is training, who's coming, is there room."
2. **One action, one tap.** No action a member takes regularly (join, leave) should require a confirmation step.
3. **No dead ends.** Every error state offers the next right action (e.g., a conflict doesn't just say "no" — it offers "join instead").

---

## 2. Navigation Principles

- **Flat, not hierarchical.** There is effectively one main screen (Home) and a small number of leaf screens reached directly from it. No nested menus, no drawers-within-drawers.
- **Bottom-anchored primary navigation on mobile** (thumb zone): Home, History, Profile — a maximum of 3 destinations. Admin-only destinations (User Management, Gym Closures) live inside Profile for admins, not as permanent nav items, since "the administrator should rarely need admin features."
- **Back always means back**, never "up to some unrelated parent." Standard OS back-gesture/button behavior is respected everywhere; no custom swipe hijacking.
- **No hamburger menu.** A hamburger menu is a symptom of too many screens. This app has few enough destinations that they fit directly in a bottom bar (member) or a bottom bar + one profile-nested section (admin).

---

## 3. Interaction Rules

- **Reversible actions get zero confirmation.** Joining and leaving are instantly reversible (a member can rejoin), so they execute immediately on tap with inline feedback — no "Are you sure?" modal.
- **Irreversible or impactful actions get exactly one lightweight confirmation**, not a modal maze: Delete Training, Cancel Training, Disable/Delete User. The confirmation is a single inline step (e.g., button transforms into "Tap again to confirm" for 3 seconds) or one simple native-feeling dialog with a single clear question — never a multi-field confirmation form.
- **Nothing important is ever hidden behind a long-press, swipe-to-reveal, or icon-only tooltip.** If an action exists, it's visible as a labeled button when relevant.
- **The system never silently fails.** Every tap either changes visible state within ~300ms or shows a loading indicator, followed by success or a friendly error.

---

## 4. Button Hierarchy

Three tiers only:

1. **Primary** — the one action the screen exists for (Join, Create Training, Save). Solid fill, largest touch target, always the visually heaviest element on screen. Only one primary button visible at a time per screen/card.
2. **Secondary** — supporting actions (Edit, View Details, Cancel Training). Outlined or text-style, clearly present but visually quieter than primary.
3. **Destructive** — Delete, Disable User, Remove — visually distinct (not by color alone — also by placement, away from primary actions, so it's never tapped by reflex).

A card or screen never shows more than one primary + one secondary + one destructive action simultaneously. If more actions are theoretically possible, only the single contextually-correct one is shown (see §7 button rules).

---

## 5. Color, Typography, Spacing — Conceptual Only

**Color** (roles, not hex values):
- One neutral base (backgrounds, text) — kept quiet so status colors stand out.
- One brand/primary accent — used only for primary actions and the current/active training highlight.
- Status colors used consistently and *only* for status: a "go" tone for open/available, a "caution" tone for nearly-full, a "stop" tone for full/cancelled/closed. These map 1:1 to meaning everywhere in the app — a color never means one thing on one screen and another thing elsewhere.

**Typography:**
- One typeface family, two or three weights maximum (regular, medium/bold for emphasis).
- A clear size scale: large for the thing the user came to read (time, "Today"), medium for supporting facts (owner, participant count), small for metadata (created date, note). No more than 3 distinct text sizes on any single screen.

**Spacing:**
- Generous, consistent spacing between tappable elements — never two tappable targets closer than a clear separation gap, to prevent mis-taps on a gym-goer's phone (often used one-handed, sometimes with sweaty/gloved hands).
- Cards have consistent internal padding; lists have consistent vertical rhythm. Nothing cramped.

---

## 6. Accessibility & Touch Targets

- Minimum touch target: 44×44pt (iOS HIG) / 48×48dp (Material) — treated as an absolute floor, not a guideline, given the gym-use context.
- Color is never the *only* signal for status — status is always paired with a text label ("Full," "Cancelled," "2 spots left"), so colorblind users are never dependent on hue alone.
- Text contrast meets WCAG AA minimum against all backgrounds, including status-colored badges.
- All interactive elements are reachable and operable via screen reader with clear labels ("Join training on Tuesday at 6 PM, 3 of 10 spots" — not just "Join").
- Font sizes respect system-level text-scaling settings; layouts don't break under larger accessibility text sizes (single-column mobile layout tolerates this naturally).

---

## 7. Loading, Empty, Error, and Success States

**Loading:**
- Skeleton placeholders (shaped like the cards about to appear) for the home list on first load — never a blank white screen, never a generic spinner for content that has a predictable shape.
- Button-level loading: on tap, the button shows an inline spinner in place of its label and disables itself, so a user can't double-tap "Join" and cause a duplicate action.

**Empty states:**
- No trainings scheduled: an friendly, brief message ("No trainings scheduled yet.") plus the Create Training primary action directly beneath it — the empty state itself is an invitation to act, not a dead page.
- Empty history: "You haven't completed any trainings yet."

**Error states:**
- Always plain language, always suggests the next step (see §12 for exact copy).
- Form validation errors appear inline, next to the field they concern, the moment the issue is knowable (e.g., duration shown live as start/end time are picked) — not only after a failed submit.
- Network/connection errors get a distinct, calm state (not a red scary banner): "Can't connect right now — check your connection" with a retry action.

**Success feedback:**
- Lightweight, non-blocking (a toast/snackbar or an instant visible state change on the card itself — e.g., the Join button becomes a "You're in ✓" state with a Leave option). Never a full-screen "Success!" interstitial that requires a dismiss tap — that would violate "as few taps as possible."

---

## 8. Application Flow

### 8.1 Member Flow

```
Login
  │
  ▼
Home (chronological list of scheduled trainings)
  │
  ├── Tap a training card ──▶ Training Details
  │                              │
  │                              ├── Join ──▶ back to Details, now shows Leave
  │                              ├── Leave ──▶ back to Details, now shows Join
  │                              ├── Edit (owner only, if eligible) ──▶ Edit Training
  │                              ├── Cancel (owner only, if others joined) ──▶ Cancel confirmation
  │                              └── Delete (owner only, if solo) ──▶ Delete confirmation
  │
  ├── Tap "+ Create Training" ──▶ Create Training
  │                                   │
  │                                   ├── Save (no conflict) ──▶ Home, new training visible
  │                                   └── Conflict detected ──▶ inline "Join existing training instead" prompt
  │
  ├── Tap History (bottom nav) ──▶ History (completed + cancelled trainings, read-only)
  │
  └── Tap Profile (bottom nav) ──▶ Profile
                                      │
                                      ├── Change Password
                                      └── Logout
```

### 8.2 Administrator Flow

Administrators have the full Member flow above, plus an **Admin section nested inside Profile** (not a separate bottom-nav tab, since it's rarely used):

```
Profile (admin)
  │
  ├── Change Password
  ├── User Management
  │     ├── Add User (name + temporary password, shown once)
  │     ├── Disable / Enable User
  │     ├── Delete User
  │     └── Reset Password (generates new temporary password)
  ├── Gym Closures
  │     ├── View upcoming/active closures
  │     ├── Add Closure (dates + reason)
  │     └── Remove a future closure
  └── Logout
```

Admin screens use the exact same visual language as member screens (same card style, same button hierarchy) — there is no "separate admin dashboard aesthetic." This keeps the codebase and the mental model simple, per the architecture's own principle.

---

## 9. Screen Specifications

### 9.1 Login

- **Purpose:** Authenticate a known user. No self-service anywhere on this screen.
- **Displayed information:** App/gym name or logo, username/email field, password field.
- **Primary action:** Log In.
- **Secondary actions:** None. Explicitly **no** "Forgot password?" link — per architecture, password reset is admin-only and offline.
- **Navigation:** None (entry point). Successful login goes straight to Home.
- **States:** Default, loading (button spinner on submit), error (invalid credentials), account disabled (distinct message, see §12).
- **Validation errors:** "Incorrect username or password" (deliberately non-specific, doesn't reveal which field is wrong, standard security practice).
- **Loading behavior:** Button-level spinner; form fields disabled during submit.
- **Success behavior:** Immediate redirect to Home, no interstitial.

### 9.2 Home

- **Purpose:** The single answer screen — see §10 for full detail.
- **Displayed information:** Chronological list of `scheduled` trainings, grouped under "Today / Tomorrow / [date]" headers.
- **Primary action:** Create Training (persistent, thumb-reachable — fixed position bottom-right or as part of bottom nav on mobile).
- **Secondary actions:** Tap any card to view Training Details.
- **Navigation:** Bottom nav present (Home / History / Profile).
- **States:** Loading (skeleton cards), empty (see §7), populated, offline (cached last-seen list shown with a subtle "showing saved data" indicator — see §14).
- **Validation errors:** N/A (read-only screen).
- **Loading behavior:** Skeleton cards on first load; pull-to-refresh supported.
- **Success behavior:** N/A.

### 9.3 Create Training

- **Purpose:** Let a member propose a new session.
- **Displayed information:** Form: Date, Start Time, End Time, Max Participants, Note (optional).
- **Primary action:** Save / Create.
- **Secondary actions:** Cancel (discard, return to Home).
- **Navigation:** Reached from Home's Create button; returns to Home on success.
- **States:** Default, validating (live), submitting, conflict-detected (special state, see below), gym-closed-blocked (special state), success.
- **Validation errors (see §11 for full matrix):** missing required field, end before start, duration too short/long, date beyond 30 days, gym closed during window, overlapping training exists.
- **Special conflict state:** If the requested window overlaps an existing `scheduled` training, the form does not just show a red error — it replaces the Save action with a summary of the conflicting training (time, owner, spots remaining) and a single "Join this training instead" primary button. This is the most important UX moment in the app — the "suggest joining instead of duplicating" rule made concrete.
- **Loading behavior:** Live validation as fields are filled (duration and date-window checks don't wait for submit); button-level spinner on save.
- **Success behavior:** Return to Home; new card visible, briefly highlighted to confirm it's the one just created.

### 9.4 Edit Training

- **Purpose:** Let the owner modify their own training, within the rules of who's joined.
- **Displayed information:** Same fields as Create, but:
  - If owner is still solo participant: all fields editable, identical to Create.
  - If others have joined: Date/Start/End are shown as **read-only, visually distinct** (e.g., greyed with a small "locked" indicator) with an inline explanation ("Can't change the time once others have joined"). Only Note and Max Participants remain editable, and Max Participants has a visible floor equal to current participant count (the input cannot be decremented below it — the stepper control simply stops there rather than allowing an invalid value to be typed and rejected).
- **Primary action:** Save Changes.
- **Secondary actions:** Cancel (discard).
- **Navigation:** Reached from Training Details (Edit button, owner-only, only shown when editing is possible in some form).
- **States:** Default (full-edit), restricted (partial-edit), submitting, success.
- **Validation errors:** Same as Create for editable fields; attempts to edit locked fields are prevented at the UI level (fields aren't interactive), not just rejected after submit.
- **Loading behavior:** Button-level spinner.
- **Success behavior:** Return to Training Details, updated values visible immediately.

### 9.5 Training Details

- **Purpose:** The full picture of one training — everyone who's in, everyone who owns it, and every action available to the current user.
- **Displayed information:** Date, time range, duration, owner name, full participant list (names, not just count), max participants, remaining spots, note (if any), status.
- **Primary action:** Context-dependent — exactly one of: Join / Leave / (nothing, if viewing history — see §9.6).
- **Secondary actions:** Edit (owner, if training is `scheduled`), Cancel Training (owner, if ≥2 participants), Delete Training (owner, if solo participant).
- **Navigation:** Reached by tapping any card from Home or History. Back returns to the originating list.
- **States:** Open (spots available), full (no Join button, shows "Full" badge instead), cancelled (read-only, badge shown, no action buttons except back), completed (read-only, in History context).
- **Validation errors:** N/A directly, but attempting an action that's since become invalid (e.g., someone else filled the last spot a second before you tapped Join) shows a friendly race-condition message (see §12) and refreshes the screen state.
- **Loading behavior:** Standard page transition; action buttons show inline spinners on tap.
- **Success behavior:** In-place state update (button swaps from Join to "You're in ✓ · Leave" or similar) without leaving the screen.

### 9.6 Cancel Training

- **Purpose:** Confirm the owner's intent to cancel a training that has other participants, since this is irreversible and affects others.
- **Displayed information:** A short, clear summary: how many people are affected, the training time. No form fields.
- **Primary action:** Confirm Cancel (destructive style).
- **Secondary actions:** Go Back (dismiss, no change).
- **Navigation:** Reached from Training Details' Cancel button. Presented as a single lightweight confirmation step (not a separate full screen necessarily — can be an in-place expansion or a minimal dialog), per the "avoid unnecessary confirmation dialogs" rule — this is the one case that *does* warrant a confirmation, because it's irreversible and affects other people.
- **States:** Default, submitting, success.
- **Validation errors:** N/A.
- **Loading behavior:** Button-level spinner.
- **Success behavior:** Return to Training Details (or Home), training now shows "Cancelled" badge.

*(Delete Training follows the identical pattern but with "Delete" language, and only ever appears when the owner is solo — so no participants to be notified/affected.)*

### 9.7 History

- **Purpose:** Let anyone review past trainings — completed and cancelled — for transparency, without cluttering the active Home list.
- **Displayed information:** Same card format as Home, filtered to `completed` and `cancelled`, most recent first. Each card clearly shows its final status.
- **Primary action:** None — this is a read-only, reference screen (tapping a card still opens Training Details, but with no action buttons since it's a terminal state).
- **Secondary actions:** None beyond navigation.
- **Navigation:** Bottom nav item.
- **States:** Loading (skeleton), empty (see §7), populated.
- **Validation errors:** N/A.
- **Loading behavior:** Skeleton cards, pagination or infinite scroll if the list grows long (kept simple — a "Load more" button is acceptable and simpler than infinite scroll machinery).
- **Success behavior:** N/A.

### 9.8 Profile

- **Purpose:** Account-level actions and, for admins, the entry point to admin tools.
- **Displayed information:** User's own name, username.
- **Primary action:** None dominant — this screen is a short menu list.
- **Secondary actions:** Change Password, Logout, and (admin only) User Management, Gym Closures.
- **Navigation:** Bottom nav item. Each row navigates to its respective screen.
- **States:** Default only.
- **Validation errors:** N/A.
- **Loading behavior:** N/A (static screen).
- **Success behavior:** N/A.

### 9.9 Change Password

- **Purpose:** Let any user (especially after an admin reset) set their own password.
- **Displayed information:** Current password, new password, confirm new password.
- **Primary action:** Save.
- **Secondary actions:** Cancel.
- **Navigation:** Reached from Profile. On forced change (`must_change_password`), this screen appears immediately after login and **cannot be dismissed/skipped** until completed — the only screen in the app allowed to block navigation, since it's a security requirement, not a convenience feature.
- **States:** Default, validating (live password match check), submitting, error, success.
- **Validation errors:** Current password incorrect; new passwords don't match; new password too short/weak (simple minimum rule, not a complex policy — keep it usable).
- **Loading behavior:** Button-level spinner.
- **Success behavior:** Confirmation feedback, then redirect to Home (or wherever the user was headed).

### 9.10 Admin: User Management

- **Purpose:** Create, disable, delete, and reset passwords for members. Used rarely.
- **Displayed information:** List of all users (name, active/disabled status).
- **Primary action:** Add User.
- **Secondary actions:** Per user row — Disable/Enable, Reset Password, Delete.
- **Navigation:** Reached from Profile (admin only). "Add User" opens a minimal form (name + username, temporary password auto-generated or set by admin).
- **States:** Default list, add-user form, confirmation states for destructive actions. **Delete User** uses the same one-step confirmation pattern as Cancel Training, but its confirmation copy explicitly states the cascade impact — e.g. "Deleting [Name] will also delete 3 trainings they organized. This can't be undone." — since Delete now removes owned Trainings and participation records along with the account (per the accepted engineering recommendation on cascade deletes). Disable carries no such warning, since it's non-destructive and reversible.
- **Validation errors:** Username already taken; required fields missing.
- **Loading behavior:** Standard list loading; button-level spinners on actions.
- **Success behavior:** New user appears in list immediately; the generated temporary password is displayed once, clearly, with a "copy" affordance, and a note that it won't be shown again — the admin is expected to relay it to the member directly (matches the fully offline password-reset model).

### 9.11 Admin: Gym Closures

- **Purpose:** Mark periods when the gym is unavailable.
- **Displayed information:** List of upcoming/active closures (date range, reason); past closures may be hidden or shown in a simple history toggle.
- **Primary action:** Add Closure.
- **Secondary actions:** Remove a future closure.
- **Navigation:** Reached from Profile (admin only).
- **States:** Default list, add-closure form, submitting, success. If adding a closure will cancel existing trainings, the confirmation step **explicitly says so** before the admin commits ("This will cancel 2 scheduled trainings — continue?") — this is the one admin action with real member-facing impact, so it earns the one confirmation step.
- **Validation errors:** End before start; overlapping an existing closure (not strictly disallowed, but flagged for admin awareness rather than blocked, since it's a low-frequency admin-only edge case).
- **Loading behavior:** Button-level spinner.
- **Success behavior:** New closure appears in list; if trainings were auto-cancelled, a brief summary confirms it ("Closure added. 2 trainings were cancelled.").

---

## 10. Home Screen — Detailed Specification

The Home Screen carries the entire product's promise, so it gets its own section.

**What it must answer without any tap:**
- *When is the next training?* — first card, always visible without scrolling on a standard phone screen.
- *Who's running it, who's coming?* — owner name and participant count visible on the card face, not behind a tap.
- *Is there room?* — remaining spots shown as a number, not just a progress bar (numbers are unambiguous; bars require interpretation).
- *Can I join right now?* — the Join button is visible directly on the card, not gated behind Training Details, for the single most common action in the app.

**Structure:**
- Grouped by relative date headers: **Today**, **Tomorrow**, then absolute dates for anything further out (e.g., "Sat, Aug 2").
- Within a day, only one training can exist (per architecture), so there's no sub-grouping needed — this is a genuinely flat list.
- The very next upcoming training can optionally receive a subtle visual emphasis (e.g., slightly larger card or an accent border) since it's disproportionately more relevant than something 3 weeks out — but this is a refinement, not a requirement.

**What it must NOT do:**
- No calendar grid, no month view, no date-picker-as-navigation. The list scrolls; that's the only navigation method for time.
- No filters/sort controls cluttering the top — the list is chronological, always, by definition. Nothing to configure.

---

## 11. Training Card — Element by Element

Every element that can appear on a card, and the rule governing when it's shown:

| Element | Always shown? | Notes |
|---|---|---|
| Date/time | Always | Largest text on the card — this is what the eye should land on first |
| Status badge | Only if not "open" | "Full," "Cancelled," "Completed" — omitted entirely for the normal open/available case, to avoid visual noise on the common path |
| Owner name | Always | "Organized by [Name]" |
| Participants | Always | Either avatars (if the visual system supports small avatar chips) or a simple "4 of 10 going" text — team can choose based on what renders best, but the count must always be legible without a tap |
| Remaining spots | Always, unless full | "3 spots left" — omitted and replaced by the "Full" badge once at capacity |
| Note preview | Only if a note exists | Truncated to one line on the card; full text visible in Training Details |
| Action button | Always exactly one | Join / Leave / (no button if training is `cancelled` or `completed` — those are tap-through only) |

**Interaction:** The whole card is tappable to open Training Details, *except* the action button itself, which performs its action directly without navigating away — this dual-purpose card (navigate vs. act) is standard mobile-list behavior and keeps the join-in-one-tap promise intact.

**Possible button states on the card:**
- **Join** (default, open training, user not yet in) — primary style.
- **You're in ✓ / Leave** (user already joined) — the button itself communicates membership status; no separate "joined" badge needed elsewhere on the card.
- **Full** (no button — static badge instead, since there is no action available to a non-participant).
- Disabled/loading (mid-tap, brief spinner state).

---

## 12. Buttons — Exact Appearance Rules

| Button | Appears when |
|---|---|
| **Create Training** | Always visible to any logged-in member, on Home |
| **Join** | Training is `scheduled`, has remaining spots, current user is not already a participant, and the training doesn't overlap a training the user is already in |
| **Leave** | Current user is a participant **and** is not the owner |
| **Edit** | Current user is the owner **and** training is `scheduled` (field-level restrictions apply inside the screen per §9.4 — the button itself is not further conditioned) |
| **Delete** | Current user is the owner **and** is the only participant |
| **Cancel Training** | Current user is the owner **and** there are 2+ participants |
| **Disabled Join** | Training is full, or gym is closed for that window, or user has a conflicting training — shown as a non-interactive "Full" / status badge rather than a greyed-out button, per the principle that disabled buttons with no explanation frustrate users; a badge with a reason is clearer than a button that does nothing |

Owner never sees a Leave button under any circumstance — this is an intentional, permanent absence, not a disabled state (see architecture §5.7).

---

## 13. Forms — Validation Behavior

| Field | Rule | When validated |
|---|---|---|
| Date | Required; must be today or later; must be ≤ 30 days from now | On change (live) |
| Start Time | Required | On change |
| End Time | Required; must be after Start Time | On change (live, as soon as both are set) |
| Duration (derived) | Must be between 30 minutes and 4 hours | On change (live) — shown as a small live-calculated "Duration: 1h 30m" label so the user sees it before hitting an error |
| Max Participants | Required; minimum 1; when editing with existing participants, minimum = current participant count | On change |
| Note | Optional; max 200 characters; plain text only | Live character counter; any pasted formatting is silently stripped, not rejected with an error (friendlier than blocking paste) |
| Overlap check | Any overlap with an existing `scheduled` training | On submit (requires a server round-trip) — but as a UX pattern, this is the one check that can't be fully live/client-side, so the form should show a brief "Checking availability…" state right before submit completes, avoiding the impression of a stalled button |
| Gym closure check | Requested window must not fall inside a closure | On submit, same pattern as overlap |

---

## 14. Error Messages — Exact Copy Guidance

No technical language, no error codes, always human and calm, always paired with a next step where one exists.

| Situation | Message |
|---|---|
| Overlapping training exists | "There's already a training at that time — [Join it instead] or pick a different time." |
| Gym closed during selected window | "The gym is closed during that time ([reason]). Choose another date." |
| Training is full | "This training is full." |
| Duration too short | "Trainings must be at least 30 minutes long." |
| Duration too long | "Trainings can be up to 4 hours long." |
| Date too far ahead | "You can only schedule up to 30 days in advance." |
| Trying to edit a locked field | "The time can't be changed once someone else has joined." |
| Owner trying to leave | (Not shown as an error — the Leave button simply never appears for the owner, so no message is needed.) |
| Race condition (spot taken between view and tap) | "Someone just took the last spot — sorry! [View training]" |
| Invalid login | "Incorrect username or password." |
| Disabled account | "Your account is currently inactive. Please contact the gym admin." |
| Network/offline | "Can't connect right now. Check your connection and try again." |
| Wrong current password (change password) | "That current password isn't correct." |
| New passwords don't match | "Those passwords don't match." |

---

## 15. Success Messages

Kept brief, non-blocking, and often expressed through UI state change rather than text:

- **Join:** Button changes to "You're in ✓" — no separate toast needed, the state change *is* the feedback.
- **Leave:** Button reverts to "Join" — same principle.
- **Create Training:** Return to Home, new card briefly highlighted (e.g., a soft highlight fade over ~1–2 seconds) — no modal, no toast required, but a small toast ("Training created") is acceptable if the highlight alone feels insufficient during testing.
- **Edit saved:** "Changes saved" toast, return to Training Details with updated values.
- **Cancel/Delete:** "Training cancelled" / "Training deleted" toast, return to Home or History.
- **Password changed:** "Password updated" toast, redirect.
- **User created (admin):** Temporary password shown clearly in a persistent (not auto-dismissing) panel, since it must be manually relayed.
- **Closure added:** "Closure added" (+ cancelled-trainings count if applicable).

---

## 16. Responsive Behavior

| Aspect | Phone (primary) | Tablet | Desktop |
|---|---|---|---|
| Layout | Single column, bottom nav | Single column, slightly wider cards, bottom or side nav acceptable | Single centered column (not a dashboard-style multi-panel layout) — the app never grows "wider" in complexity, just in comfortable margins; side navigation replaces bottom nav since there's no thumb-zone constraint |
| Card density | One card's full detail requires a tap (Details) | Same | Same — resist the temptation to cram more info into a wider card just because there's room; consistency across devices matters more than exploiting screen space |
| Forms | Full-screen | Full-screen or centered modal | Centered modal/panel acceptable |
| Touch targets | 44–48pt minimum | Same minimum maintained even though mouse precision is higher on desktop, for consistency and eventual touch-laptop support | Same |
| Navigation | Bottom tab bar | Bottom or side bar | Side bar or top bar acceptable |

The guiding rule: **desktop gets more breathing room, never more density or more navigation depth.** The information architecture is identical across all sizes — only spacing and nav placement adapt.

---

## 17. PWA Experience

- **Home Screen icon:** Simple, high-contrast gym-branded icon, installable via standard "Add to Home Screen" browser prompt — no custom install-nagging UI beyond the platform default.
- **Splash screen:** Minimal — logo/name centered on a plain background, shown only for the brief native app-launch moment; no animation that delays perceived load time.
- **Offline behavior:** The last successfully loaded Home list remains visible with a small, calm indicator ("Showing saved data — reconnect to update"). Actions requiring a network call (Join, Create, etc.) are disabled with an inline explanation rather than appearing to work and failing silently.
- **Reconnect behavior:** Automatic silent refresh when connectivity returns; if the currently viewed data has changed (e.g., a training filled up while offline), the UI updates in place rather than requiring a manual refresh, and any now-invalid pending action is cancelled with a clear message (see race-condition copy in §14).
- **Loading behavior:** App shell (nav, layout) loads instantly from cache on repeat visits; only the data (training list) waits on network, using the skeleton-loading pattern from §7 — so the app never feels like it's "booting up" on subsequent opens.

---

## 18. What Should Never Exist

Explicitly excluded from the UI, because each would add complexity without serving the app's single purpose:

- A calendar grid or month view of any kind.
- A hamburger menu.
- Multi-step confirmation wizards for anything.
- Confirmation dialogs for Join/Leave (reversible, low-stakes).
- Filters, sort dropdowns, or search on the Home screen (the list is short and chronological by nature).
- Avatars-as-decoration without functional meaning (no profile pictures/customization — out of scope, and not needed since the app has no social dimension).
- Notification badges/counters for anything other than what the architecture explicitly supports (none in v1 — no fake badge system implying features that don't exist).
- Settings screens beyond Change Password (no theme toggles, no notification preferences, no display customization) — nothing to configure is itself a feature.
- Loading spinners with no accompanying skeleton/context for predictable-shape content (Home, History).
- Tooltips that hide essential information — if it's essential, it's on-screen by default.
- Any screen requiring horizontal scrolling on mobile.
- A generic "Settings" catch-all screen — every screen in this app has a specific, nameable purpose.

---

## 19. User Journey Analysis

Real scenarios, walked through tap-by-tap, to validate the "as few clicks as possible" promise concretely.

### Scenario 1 — Ivan wants to create a training

**Goal:** Ivan opens the app to schedule a training for tomorrow evening.

| Step | Action | Screen |
|---|---|---|
| 1 | Opens app (already logged in, PWA icon) | Home loads |
| 2 | Taps "+ Create Training" | Create Training form opens |
| 3 | Picks date (Tomorrow, likely pre-selectable as a quick option), start time, end time | Same screen |
| 4 | Taps "Create" | Submits |
| 5 | (If no conflict) Lands back on Home, sees his new training | Home |

**Taps required: 3** (Create Training → confirm date shortcut or picker → Create). Time: with native time pickers and a "Tomorrow" quick-select, this realistically takes **15–25 seconds** for someone who already knows what they want. If a conflict exists, one extra tap ("Join instead") resolves it — still under 5 total taps and under 30 seconds.

### Scenario 2 — Petar wants to join

**Goal:** Petar sees there's a training tonight and wants in.

| Step | Action | Screen |
|---|---|---|
| 1 | Opens app | Home loads, tonight's training is the first/only visible card |
| 2 | Taps "Join" directly on the card | In place — no navigation required |

**Taps required: 1** (from Home, no need to even open Training Details). Time: **under 5 seconds** once the app is open — this is the single most important interaction in the product, and it's a single tap by design, matching the "closer to WhatsApp" goal directly.

*(If Petar wants to double check who else is going before joining, he can tap the card first to see full participant names — Details — then Join: 2 taps, a few seconds more. Still trivial.)*

### Scenario 3 — The administrator adds a new user

**Goal:** Admin needs to onboard a new gym member.

| Step | Action | Screen |
|---|---|---|
| 1 | Taps Profile (bottom nav) | Profile |
| 2 | Taps "User Management" | User list |
| 3 | Taps "Add User" | Add User form |
| 4 | Enters name + username, taps "Create" | Submits |
| 5 | Sees generated temporary password displayed once | User list, new user visible |

**Taps required: 4** (Profile → User Management → Add User → Create), plus form-filling time. This is intentionally a few more taps than member actions, because it's an infrequent admin task — per the architecture, the admin "should rarely need to use admin features," so optimizing this path below member-facing paths would be solving the wrong problem.

### Scenario 4 — The gym closes for renovation

**Goal:** Admin declares a closure period that overlaps two already-scheduled trainings; understand what every affected person sees.

**Admin's flow:**
1. Profile → Gym Closures → Add Closure.
2. Enters date range + reason ("Maintenance").
3. Before confirming, sees: *"This will cancel 2 scheduled trainings — continue?"*
4. Confirms. Sees: *"Closure added. 2 trainings were cancelled."*

**What affected members see (next time they open the app, no push notification in v1):**
- On **Home**: the previously-scheduled trainings they were part of no longer appear in the active/upcoming list (since they're no longer `scheduled`).
- On **History**: those same trainings now appear with a **"Cancelled"** status badge, fully visible with original time/owner/participants intact — nothing is hidden, per the transparency principle.
- If a member taps into one of those trainings from History, **Training Details** shows the Cancelled state — read-only, no action buttons, just the historical record.
- There is no special "cancelled due to closure" distinction shown to members beyond the plain "Cancelled" badge (per the earlier confirmed simplification) — consistent with keeping status meaning uniform across the app (§5, Color section) and avoiding a new data field.

This scenario is the clearest illustration of the transparency principle in practice: nothing is deleted, nothing is silently hidden — the record simply moves from "active" to "history," visible to everyone exactly as it would if the owner had cancelled it themselves.

---

## 20. Summary

This specification describes an interface with almost no learning curve: one list, one card pattern, one button per context, and confirmations reserved only for the two truly irreversible, other-people-affecting actions (Cancel Training, Delete Training/User, and closures that cancel trainings). Every screen, state, and message above is implementable directly against the approved architecture without requiring any new backend concept — this document adds experience, not scope.
