---
name: project-overview
description: ContractorMiniERP stack, key models, notification system, and timezone configuration
metadata:
  type: project
---

Laravel 11 / PHP 8.4 backend in `ContractorApp/`. Mobile app is React Native (BuildMate), consuming a `/mobile` API secured by Sanctum tokens.

**Timezone:** `Asia/Kolkata` (IST) — hardcoded in `config/app.php`.

**Push notifications:** `PushService` supports both Expo (`ExponentPushToken[…]`) and FCM (HTTP v1) tokens. Users store their token in `users.push_token`. Deduplication lives in `push_notification_logs`.

**Meeting data sources (two):**
- `project_meetings` table — formally logged meetings (`ProjectMeeting` model, `meeting_at` + `purpose`)
- `projects.next_meeting_at` — quick "next meeting" field on `Project` model

**Why:** Both sources must be queried for the 6 AM digest because `next_meeting_at` is the live upcoming meeting and `project_meetings` can hold future meetings logged explicitly.

**Scheduler:** `SendMeetingReminders` command runs every 15 min via `Schedule::everyFifteenMinutes()->withoutOverlapping(20)`. The command guards on `now()->hour === 6` to send the daily digest. Deduplication uses `push_notification_logs` with `project_id = null`, `type = 'daily_digest'`.

**How to apply:** When touching notifications or meetings, check both `project_meetings` and `projects.next_meeting_at`. The `ContractorScope` global scope does NOT apply in console context (no auth user), so console commands see all contractors' data unfiltered.
