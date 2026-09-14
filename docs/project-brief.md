# Gym Training Reservation System

Version: 1.0

Status: Project Definition

---

# Project Goal

Develop a lightweight, private web application for a martial arts / fitness gym where members can organize their own training sessions.

The primary objective is simplicity, speed and usability.

This application is intended for everyday use by gym members.

It should require as few clicks as possible.

---

# Main Philosophy

The application is NOT a booking platform.

The application is NOT a CMS.

The application is NOT an online store.

The application is NOT a social network.

It is simply a shared private training schedule.

---

# Users

Only registered users can access the system.

There is NO public access.

There is NO registration page.

Only the administrator creates user accounts.

---

# Roles

## Administrator

The administrator has all Member permissions plus:

- Create users
- Disable users
- Delete users
- Reset passwords

The administrator does NOT manage trainings.

The administrator does NOT create reservations for users.

The administrator should rarely need to use the admin features after users are created.

---

## Member

Every member can:

- Login
- Create a training
- Join another training
- Leave a training
- Delete their own training
- Edit their own training (only if nobody else has joined)
- View all trainings
- View all participants

---

# Training

Each training contains:

- Owner
- Date
- Start Time
- End Time
- Maximum Participants
- Optional Note
- Created At
- Updated At

The creator automatically becomes the first participant.

---

# Transparency

Every member can always see:

- Who created the training
- Who participates
- Number of participants
- Remaining available places

Nothing is hidden between members.

---

# Business Rules

A user cannot create overlapping trainings.

A user cannot participate in overlapping trainings.

If another training already exists during the selected period, the system should suggest joining that training instead of creating a duplicate.

Completed trainings are archived automatically.

Cancelled trainings remain visible with a "Cancelled" status.

---

# Gym Rules

The gym has a configurable maximum capacity.

The system should prevent the total number of people inside the gym from exceeding this limit.

The gym can also be marked as temporarily closed for:

- Maintenance
- Competition
- Seminar
- Holiday
- Other events

During a closed period, no new trainings can be created.

---

# User Interface

Mobile First.

Desktop compatible.

Very clean interface.

No unnecessary menus.

Large buttons.

Large touch targets.

Simple navigation.

The application should feel as simple as WhatsApp.

---

# Home Screen

The home screen is a chronological list of trainings.

No monthly calendar.

Users should immediately see:

Today

Tomorrow

Upcoming trainings

Participants

Remaining places

Create Training button

Join button

Leave button

---

# Authentication

Private application.

Everything requires login.

No public pages.

Secure authentication.

Secure sessions.

---

# Technology Stack

Preferred technologies:

- Laravel 12
- PHP 8.4
- Livewire
- TailwindCSS
- SQLite
- PWA

The architecture should remain simple and maintainable.

---

# Performance

The application should be optimized for:

- Fast loading
- Minimal database queries
- Mobile performance
- Easy deployment
- Easy maintenance

---

# Future Features

The architecture should allow adding future features without major refactoring.

Possible future additions:

- Push notifications
- Statistics
- Attendance reports
- Multiple gyms
- Coach accounts

These features should NOT be implemented in version 1.

---

# Explicitly Out of Scope

Do NOT implement:

- Payments
- Online shop
- Membership billing
- Public profiles
- Chat
- Forum
- Comments
- Likes
- Social features
- Public calendar
- Registration page
- Email verification
- Complex administration

---

# Development Philosophy

Every implementation decision should prioritize:

1. Simplicity
2. Reliability
3. Maintainability
4. Security
5. Mobile usability

Whenever multiple technical solutions exist, choose the simplest one that satisfies the requirements.

This document is the foundation of the entire project.

Future architectural decisions should remain compatible with this specification.

---

**Note:** As documented in the Architecture Specification (§2, "Gym Capacity") and Domain Model Specification, the "configurable maximum capacity" concept described above under "Gym Rules" was superseded by a later, approved architectural decision: the gym has only one training at a time, and each training's own `Maximum Participants` field effectively serves this role. This note preserves the original brief text unmodified while flagging the supersession, consistent with the project's rule that this document is never edited, only built upon.
