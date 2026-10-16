# Changelog

## Unreleased

### School attendance system

- Schools, grades, classes and students with Excel/CSV import and bulk class moves.
- Daily attendance per class (present, absent, late with minutes, excused) and a daily tracking overview.
- Roles: system administrator, principal, vice principal, student affairs, health advisor, student counselor and guardian, with school-scoped permissions.
- Guardian registration verified against student details, email-verified linking, optional staff approval and automatic guardian contact completion.
- Online excuses with attachments and staff review; student notes shared with guardians when chosen.
- Guardian communication by WhatsApp, phone and email with contact logging.
- Configurable in-app and email notifications per event and recipient role, including repeated-absence alerts.
- Reports with Excel and Arabic PDF export; automatic audit of all school records; school activity log.
- Arabic as the default language; `school:admin` and `school:demo` console commands.

### Foundation

Initial standalone release of Arabic Laravel SaaS Starter Kit.

- English/Arabic interfaces, RTL navigation and locally hosted fonts.
- Authentication, verification, password recovery, optional MFA and granular administration permissions.
- Role creation, renaming, permission editing and deletion with protected ownership.
- Optional plans, manual subscriptions, feature switches and monthly allowances.
- Branding tabs, localized content, light/dark themes and maintenance/security settings.
- Phrase-based translation drafts, preview, publication and language visibility.
- Notifications dropdown, read state, preferences, support inbox, mail history, audit and health checks.
- Resend, Gemini, reCAPTCHA and Cloudflare R2 configuration.
- SQLite snapshots, optional AES-256 archive encryption and scheduled backup foundations.
