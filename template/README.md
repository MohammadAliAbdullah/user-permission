# BNSIS Admin — HTML UI Template

Sob page ekhon ekti **shared layout template** follow kore:
- Top bar (brand-900), fixed sidebar (Alpine collapse + localStorage memory), main content area, page header (title + badge).
- Tailwind CDN + Alpine.js; color/typography tokens: `assets/app.css`.

## Files
| File | Page |
|---|---|
| `page-template.html` | ← **notun page er jonno master template** (copy korun) |
| `login.html` | Standalone login card (sidebar chara) |
| `index.html` | Dashboard (KPI cards, charts, tables) |
| `users.html`, `user-form.html` | User list / create-edit form |
| `roles.html`, `role-form.html` | Roles / role form |
| `permissions.html` | Role permissions matrix |
| `modules.html` | Modules & links tree |
| `activity.html` | Activity log |
| `database-schema.html` | DB schema browser |
| `database-data.html` | Table data viewer |

## Notun page bananor niyom
1. `cp page-template.html branch.html`
2. `<title>` + page-header `<h1>/<p>` update korun.
3. Sidebar e ei page er link add/active korun (`bg-brand-50 text-brand-700 font-medium`).
4. Content `PAGE CONTENT START/END` marker er moddhe likhun.
5. Full menu block dorkar hole `index.html` theke `<aside>…</aside>` copy korun.

## Preview
```bash
python3 -m http.server 8000 --directory .   # http://localhost:8000/index.html
```
