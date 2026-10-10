# BNSIS Admin Panel — Static HTML Template

This folder contains **all admin-panel pages as ready-to-open static HTML files**,
exported live from the running application with **real data** from the
`bnsis_permission` database (users, roles, permissions, modules, activity log).

## Pages in this template

| File | Page | What it shows (with real data) |
|---|---|---|
| `index.html` | Dashboard | KPI cards (users / roles / permission grants / modules), role access matrix, recent activity |
| `login.html` | Login | Sign-in screen design (`admin / admin123`) |
| `users.html` | Users | Paginated user list with names, usernames, emails, assigned roles, status badges, search & filter UI |
| `user-form.html` | User form | Create/edit user: personal fields, account status, level, primary + secondary role checkboxes |
| `roles.html` | Roles | Role cards with user counts, module counts, permission counts, system-role badges |
| `role-form.html` | Role form | Create/edit role fields |
| `permissions.html` | Permissions | Full CRUD matrix per role — every module link × Create/Read/Update/Delete checkboxes (2,163 seeded grants) |
| `modules.html` | Modules | All menu links grouped by module with icons, URLs, sort order, active state |
| `activity.html` | Activity log | Recent audit entries (who did what, when, IP) |
| `database-schema.html` | DB schema | Documentation of all 19 tables of `bnsis_permission` with PK/UQ/FK badges and relationships |
| `assets/app.css` | — | Shared custom stylesheet (used alongside Tailwind CDN) |

## How to view

- Just **double-click any `.html` file** — everything is self-contained.
- Or serve the folder: `php -S localhost:8000 -t template` → http://localhost:8000/index.html
- Tailwind CSS + Alpine.js load from CDN, so an internet connection is needed for full styling.

## Notes

- Navigation between pages works (sidebar/topbar links point to these `.html` files).
- Forms are display-only here (`action="#"`); wire them back to `public/index.php?action=…` endpoints when integrating into a real app.
- To regenerate after data changes, re-export the live pages and rewrite links to relative `.html` targets.
