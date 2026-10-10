# permission-ui — full UI design for all menu pages (with live data)

BNSIS Security & Access admin panel. Every page renders REAL rows parsed from the
`../db_permission` MySQL dump — no mock data.

## Pages (sidebar menu)
| Page | Menu label | Source table(s) | Rows |
|---|---|---|---|
| index.html | Admin Dashboard | all | summary + quick nav |
| modules.html | Modules | sa_modules | 11 |
| module-links.html | Module Links | sa_module_links | 231 (+menu tree) |
| org-modules.html | Organization Modules | sa_organizations, sa_org_modules, sa_org_mlinks | 4+11+231 |
| user-groups.html | All User Group | sa_user_group, sa_ug_level, usergroups | 5+10+94 |
| assign-links.html | Assign Link To Group | sa_uglw_mlink (names resolved) | 834 |
| user-setup.html | User Setup | sa_users, userdaogroup | 124+517 |
| users.html | Users | users (legacy, passwords masked) | 93 |
| roles.html | Roles | sa_user_group as roles + counts | 5 |
| role-permissions.html | Role Permissions | sa_uglw_mlink matrix per group×level | 834 |
| schema.html | DB Schema & Data | all 19 tables row-count map | 19 |

RBAC tables (roles, permissions, role_permissions, user_roles, activity_logs) exist in
schema but are empty in this dump — pages show them as "empty".

## Features per page
- Identical chrome: top bar + sidebar built by `assets/app.js → PU.sidebar()`, active-link highlight
- Stat tiles, sticky-header paginated tables (Prev/Next)
- Live search + dropdown filters (module / org / group / group×level)
- CRUD flags as ✓ badges, statuses as pills, NULL shown italic; passwords masked
- ⬇ Export CSV of current table
- Related names resolved across tables (no raw-ID-only views)

## Regenerate after dump changes
    cd permission-ui && python3 _build.py
(re-parses ../db_permission → assets/data.js and rewrites all 11 HTML files)

## Preview
    python3 -m http.server 8000 --directory /workspace/permission-ui
open http://localhost:8000/index.html
