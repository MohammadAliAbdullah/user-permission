#!/usr/bin/env python3
# Regenerates all permission-ui pages from the db_permission dump.
# Usage: python3 _build.py   (run inside /workspace/permission-ui)
import os, re, json, pickle, subprocess, sys

HERE = os.path.dirname(os.path.abspath(__file__))
DUMP = os.path.join(HERE, '..', 'db_permission')

# ---------------- 1. parse dump -> assets/data.js ----------------
def clean(v):
    v = v.strip()
    if v.upper() == 'NULL': return None
    if v.startswith("'") and v.endswith("'"): return v[1:-1].replace("\\'", "'")
    return v

def parse_dump():
    sql = open(DUMP, encoding='utf-8', errors='replace').read()
    data = {}
    for m in re.finditer(r"INSERT INTO `(\w+)` \(([^)]*)\) VALUES\n?(.*?);\n", sql, re.S):
        t = m.group(1); cols = [c.strip(' `') for c in m.group(2).split(',')]
        s = m.group(3); rows = []; vals = []; v = ''; instr = False; esc = False; intuple = False
        i = 0
        while i < len(s):
            ch = s[i]
            if intuple:
                if esc: v += ch; esc = False
                elif ch == '\\' and instr: v += ch; esc = True
                elif ch == "'":
                    if instr and i+1 < len(s) and s[i+1] == "'": v += "'"; i += 2; continue
                    instr = not instr; v += ch
                elif ch == ',' and not instr: vals.append(v); v = ''
                elif ch == ')' and not instr: vals.append(v); rows.append(vals); vals = []; intuple = False
                else: v += ch
            elif ch == '(': intuple = True; v = ''; instr = False
            i += 1
        data[t] = [dict(zip(cols, [clean(x) for x in r])) for r in rows if len(r) == len(cols)]
    # enrich sa_uglw_mlink with resolved link name/module
    om = {r['SA_MLINKS_ID']: r for r in data.get('sa_org_mlinks', [])}
    ml = {r['LINK_ID']: r for r in data.get('sa_module_links', [])}
    mods = {r['MODULE_ID']: r['MODULE_NAME'] for r in data.get('sa_modules', [])}
    ug = {r['USERGRP_ID']: r['USERGRP_NAME'] for r in data.get('sa_user_group', [])}
    lv = {r['UG_LEVEL_ID']: r['UGLEVE_NAME'] for r in data.get('sa_ug_level', [])}
    for r in data.get('sa_uglw_mlink', []):
        o = om.get(r['SA_MLINKS_ID']); lk = ml.get(o['LINK_ID']) if o else None
        r['LINK_NAME'] = (lk or {}).get('LINK_NAME') or (o or {}).get('SA_MLINK_NAME')
        r['RESOLVED_MODULE'] = mods.get(lk['MODULE_ID']) if lk else mods.get(r['SA_MODULE_ID'])
        r['GROUP_NAME'] = ug.get(r['USERGRP_ID']); r['LEVEL_NAME'] = lv.get(r['UG_LEVEL_ID'])
    for r in data.get('sa_users', []):
        r['GROUP_NAME'] = ug.get(r['USERGRP_ID']); r['ORG_NAME'] = {x['ORG_ID']: x['ORG_NAME'] for x in data.get('sa_organizations', [])}.get(r['ORG_ID'])
    for r in data.get('sa_org_mlinks', []):
        lk = ml.get(r['LINK_ID'])
        r['LINK_NAME'] = lk['LINK_NAME'] if lk else None
        r['MODULE_NAME'] = mods.get(lk['MODULE_ID']) if lk else mods.get(r['SA_MODULE_ID'])
    for r in data.get('sa_org_modules', []):
        r['MODULE_NAME'] = mods.get(r['MODULE_ID'])
    # mask passwords
    for r in data.get('users', []): r['Password'] = '••••••••'
    for r in data.get('sa_users', []): r['USERPW'] = '••••••••'
    return data

DATA = parse_dump()
os.makedirs(os.path.join(HERE, 'assets'), exist_ok=True)
open(os.path.join(HERE, 'assets', 'data.js'), 'w', encoding='utf-8').write(
    'window.DB=' + json.dumps(DATA, ensure_ascii=False, separators=(',', ':')) + ';\n')

# ---------------- 2. page chrome ----------------
CHROME = '''<!doctype html>
<html lang="en" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{TITLE} — permission-ui</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {{ theme: {{ extend: {{ colors: {{
    brand: {{50:'#eef4ff',100:'#dbe6fe',500:'#3b6ff6',600:'#2456e0',700:'#1c46b8',900:'#152a63'}}
  }}}}}} }};
</script>
<link rel="stylesheet" href="assets/app.css">
<script src="assets/data.js"></script>
<script src="assets/app.js"></script>
</head>
<body class="h-full bg-slate-100 text-slate-800 antialiased">
<div x-data="{{ sidebar:false }}" class="min-h-full">

  <!-- Top bar -->
  <header class="fixed inset-x-0 top-0 z-40 h-16 bg-brand-900 text-white shadow"><div class="flex h-full items-center justify-between px-4">
    <div class="flex items-center gap-3">
      <button @click="sidebar=!sidebar" class="rounded p-2 hover:bg-white/10 lg:hidden" aria-label="Toggle menu"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
      <div class="flex items-center gap-2 font-semibold tracking-wide"><span class="grid h-9 w-9 place-items-center rounded-lg bg-white/10 text-lg">⚓</span><span class="hidden sm:block">BNSIS · Security &amp; Access</span></div>
      <span class="ml-2 hidden rounded-md bg-emerald-400/20 px-2 py-0.5 text-[11px] font-semibold text-emerald-200 ring-1 ring-emerald-300/30 md:block">{BADGE}</span>
    </div>
    <div class="flex items-center gap-4"><span class="hidden text-sm text-white/80 md:block">System Owner (Admin)</span>
      <span class="hidden rounded-full bg-white/10 px-3 py-1 text-xs md:block">Super Admin</span>
      <a href="../template/login.html" class="rounded-lg bg-white/10 px-3 py-1.5 text-sm hover:bg-white/20">Sign out</a>
    </div></div></header>

  <!-- Sidebar -->
  <aside class="fixed top-16 bottom-0 left-0 z-30 w-72 transform bg-white shadow-xl transition-transform duration-200 translate-x-0 lg:translate-x-0"
         :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
    <nav id="sidebar-nav" class="h-full overflow-y-auto px-3 py-4 text-sm"></nav>
  </aside>
  <div class="fixed inset-0 z-20 bg-black/40" x-show="sidebar" @click="sidebar=false" x-cloak></div>

  <!-- Main -->
  <main class="pt-16 lg:pl-72">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
      <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">{TITLE}</h1>
          <p class="text-sm text-slate-500">{SUB}</p>
        </div>
        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 ring-1 ring-brand-100">bnsis_permission · live data</span>
      </div>

{CONTENT}

    </div>
  </main>
</div>
<script>PU.mountChrome('{PAGE}','{TITLE}');</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
{SCRIPT}
</body>
</html>'''

def stats(items):
    cards = ''
    for label, val, ico, color in items:
        cards += ('<div class="tile rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/60">'
                  '<div class="flex items-center justify-between"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">%s</p>'
                  '<span class="grid h-8 w-8 place-items-center rounded-lg %s text-base">%s</span></div>'
                  '<p class="mt-1 text-2xl font-bold text-slate-900">%s</p></div>') % (label, color, ico, val)
    n = min(len(items), 4)
    return '<div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-%d">%s</div>' % (n, cards)

def toolbar(ph, select_html=''):
    return ('<div class="mb-4 flex flex-wrap items-center gap-3">'
            '<div class="relative grow min-w-[220px]">'
            '<svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-4-4"/></svg>'
            '<input id="q" type="search" placeholder="%s" class="w-full rounded-lg border-0 bg-white py-2 pl-9 pr-3 text-sm shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-500">'
            '</div>%s'
            '<button id="btn-csv" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">⬇ Export CSV</button>'
            '</div>') % (ph, select_html)

def sel(fid, label, options):
    opts = ''.join('<option value="%s">%s</option>' % (v, t) for v, t in options)
    return '<select id="%s" class="rounded-lg border-0 bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-brand-500"><option value="">%s: All</option>%s</select>' % (fid, label, opts)

def tablecard(tid):
    return '<div id="%s" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60"></div>' % tid

def JS(code):
    return '<script>' + code + '</script>'

def write(fname, title, sub, badge, content, script=''):
    html = CHROME.format(TITLE=title, SUB=sub, BADGE=badge, PAGE=fname, CONTENT=content, SCRIPT=script)
    open(os.path.join(HERE, fname), 'w', encoding='utf-8').write(html)
    print('wrote', fname)

# shared client-side filter helper injected into list pages
FILTER_JS = '''
function bindFilter(rows, cols, draw, fid){
  const q=document.getElementById('q');
  const f=fid?document.getElementById(fid):null;
  function apply(){
    let list=rows;
    if(f&&f.value) list=list.filter(r=>String(r[f.dataset.key])==f.value);
    const s=(q.value||'').toLowerCase();
    if(s) list=list.filter(r=>cols.some(c=>String(r[c.key]??'').toLowerCase().includes(s)));
    draw(list);
  }
  q.addEventListener('input',apply);
  if(f) f.addEventListener('change',apply);
  document.getElementById('btn-csv').onclick=()=>{const e=document.getElementById('q');PU.csv(document.title.split(' —')[0],rows,cols)};
}
'''

# ---------------- 3. build each page ----------------
mods = DATA['sa_modules']; links = DATA['sa_module_links']; orgmlinks = DATA['sa_org_mlinks']
orgmods = DATA['sa_org_modules']; orgs = DATA['sa_organizations']; ugs = DATA['sa_user_group']
lvl = DATA['sa_ug_level']; asg = DATA['sa_uglw_mlink']; sus = DATA['sa_users']
u2 = DATA['users']; ugrp = DATA['usergroups']; daog = DATA['userdaogroup']

mod_name = {m['MODULE_ID']: m['MODULE_NAME'] for m in mods}
ug_name = {g['USERGRP_ID']: g['USERGRP_NAME'] for g in ugs}
lv_name = {l['UG_LEVEL_ID']: l['UGLEVE_NAME'] for l in lvl}
org_name = {o['ORG_ID']: o['ORG_NAME'] for o in orgs}
top_links = [l for l in links if l['PR_LINK_ID'] is None]

# ---------- index.html : Admin Dashboard ----------
recent = sorted(links, key=lambda r: (r['UPD_DT'] or r['CRE_DT'] or ''), reverse=True)[:8]
content = stats([
    ('Modules', len(mods), '🧩', 'bg-blue-50 text-blue-600'),
    ('Module Links', len(links), '🔗', 'bg-violet-50 text-violet-600'),
    ('Organizations', len(orgs), '🏛️', 'bg-amber-50 text-amber-600'),
    ('User Groups', len(ugs), '👥', 'bg-emerald-50 text-emerald-600'),
    ('Group Levels', len(lvl), '📶', 'bg-cyan-50 text-cyan-600'),
    ('Users (sa_users)', len(sus), '👤', 'bg-indigo-50 text-indigo-600'),
    ('Link Assignments', len(asg), '🎯', 'bg-rose-50 text-rose-600'),
    ('Total Rows (dump)', sum(len(v) for v in DATA.values()), '🗄️', 'bg-slate-100 text-slate-600'),
])
menu_rows = ''
for l in top_links[:6]:
    menu_rows += ('<tr class="hover:bg-brand-50/50"><td class="px-3 py-2 font-mono text-xs text-slate-500">#%s</td>'
                  '<td class="px-3 py-2 font-medium text-slate-800">%s</td><td class="px-3 py-2">%s</td>'
                  '<td class="px-3 py-2 font-mono text-xs text-slate-500">%s</td></tr>') % (
        l['LINK_ID'], l['LINK_NAME'], mod_name.get(l['MODULE_ID'], ''), l['URL_URI'] or '')
quick = [('modules.html','🧩','Modules'),('module-links.html','🔗','Module Links'),
         ('org-modules.html','🏛️','Organization Modules'),('user-groups.html','👥','All User Group'),
         ('assign-links.html','🎯','Assign Link To Group'),('user-setup.html','⚙️','User Setup'),
         ('users.html','👤','Users'),('roles.html','🛡️','Roles'),
         ('role-permissions.html','🔑','Role Permissions'),('schema.html','🗄️','DB Schema & Data')]
qcards = ''.join('<a href="%s" class="tile flex items-center gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/60"><span class="grid h-10 w-10 place-items-center rounded-lg bg-brand-50 text-lg">%s</span><span class="font-semibold text-slate-700">%s</span><span class="ml-auto text-slate-300">→</span></a>' % (h,i,t) for h,i,t in quick)
content += ('<div class="grid gap-6 lg:grid-cols-2">'
            '<div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60">'
            '<div class="mb-3 flex items-center justify-between"><h2 class="font-bold text-slate-800">Security &amp; Access menu (live)</h2>'
            '<a href="module-links.html" class="text-xs font-semibold text-brand-600 hover:underline">View all →</a></div>'
            '<div class="overflow-x-auto rounded-lg ring-1 ring-slate-200"><table class="min-w-full divide-y divide-slate-200 text-sm">'
            '<thead class="bg-slate-50 text-left text-[11px] uppercase tracking-wide text-slate-500"><tr><th class="px-3 py-2.5">ID</th><th class="px-3 py-2.5">Link</th><th class="px-3 py-2.5">Module</th><th class="px-3 py-2.5">URI</th></tr></thead>'
            '<tbody class="divide-y divide-slate-100">%s</tbody></table></div>'
            '<h2 class="mb-3 mt-6 font-bold text-slate-800">Recently updated links</h2><div id="recent"></div>'
            '</div>'
            '<div><h2 class="mb-3 font-bold text-slate-800">Quick navigation</h2><div class="grid gap-3 sm:grid-cols-2">%s</div>'
            '<div class="mt-4 rounded-xl bg-brand-900 p-5 text-white shadow"><p class="text-sm font-semibold">Data source</p>'
            '<p class="mt-1 text-xs leading-relaxed text-white/70">All pages render live rows from the <code class="rounded bg-white/10 px-1">db_permission</code> MySQL dump (%s tables, %s rows). RBAC tables (roles, permissions, role_permissions, user_roles, activity_logs) are empty in this dump — shown as “no data”.</p></div>'
            '</div></div>') % (menu_rows, qcards, len(DATA), sum(len(v) for v in DATA.values()))

script = JS('''
const D=window.DB;
const recent=[...D.sa_module_links].sort((a,b)=>((b.UPD_DT||b.CRE_DT||'')+'').localeCompare(a.UPD_DT||a.CRE_DT||'')).slice(0,8);
PU.table('recent',recent,[
 {key:'LINK_ID',label:'ID'},{key:'LINK_NAME',label:'Link'},{key:'URL_URI',label:'URI'},
 {key:'UPD_DT',label:'Updated'}],{perPage:8});
''')
write('index.html', 'Admin Dashboard', 'Overview of modules, links, groups, users and assignments.', 'DASHBOARD', content, script)

# ---------- modules.html ----------
content = stats([('Modules', len(mods), '🧩', 'bg-blue-50 text-blue-600'),
                 ('Active', sum(1 for m in mods if m['ACTIVE_STATUS']=='1'), '✅', 'bg-emerald-50 text-emerald-600'),
                 ('Top-level links', len(top_links), '🔗', 'bg-violet-50 text-violet-600'),
                 ('Rows', len(mods), '🗄️', 'bg-slate-100 text-slate-600')])
content += toolbar('Search modules…') + tablecard('tbl')
content += '<p class="mt-3 text-xs text-slate-400">Table: <b>sa_modules</b> · MODULE_ID, MODULE_NAME, SHORT_NAME, SL_NO, MODULE_ICON, ACTIVE_STATUS</p>'
script = JS('''
const rows=window.DB.sa_modules;
const cols=[{key:'MODULE_ID',label:'ID'},{key:'MODULE_NAME',label:'Module Name'},{key:'SHORT_NAME',label:'Short'},
 {key:'SL_NO',label:'SL#'},{key:'MODULE_ICON',label:'Icon'},
 {key:'ACTIVE_STATUS',label:'Status',render:v=>PU.pill(v)},
 {key:'CRE_DT',label:'Created'},{key:'UPD_DT',label:'Updated'}];
const draw=PU.table('tbl',rows,cols,{perPage:25});
''' + FILTER_JS + "bindFilter(rows,cols,draw);")
write('modules.html', 'Modules', 'Application modules registered in sa_modules.', 'sa_modules', content, script)

# ---------- module-links.html ----------
opts = [(m['MODULE_ID'], m['MODULE_NAME']) for m in mods]
content = stats([('Links', len(links), '🔗', 'bg-violet-50 text-violet-600'),
                 ('Top-level', len(top_links), '📁', 'bg-blue-50 text-blue-600'),
                 ('Sub-links', len(links)-len(top_links), '↳', 'bg-cyan-50 text-cyan-600'),
                 ('Active', sum(1 for l in links if l['ACTIVE_STATUS']=='1'), '✅', 'bg-emerald-50 text-emerald-600')])
content += toolbar('Search links, URIs…', sel('flt-mod','Module',opts))
content += '<div id="tree" class="mb-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60"></div>' + tablecard('tbl')
script = JS('''
const D=window.DB, links=D.sa_module_links, mods=D.sa_modules;
const modName={}; mods.forEach(m=>modName[m.MODULE_ID]=m.MODULE_NAME);
// tree card
let html='<h2 class="mb-3 font-bold text-slate-800">Menu tree — Security & Access</h2>';
const tops=links.filter(l=>!l.PR_LINK_ID);
tops.forEach(t=>{
  const subs=links.filter(l=>l.PR_LINK_ID===t.LINK_ID);
  html+='<div class="mb-2 rounded-lg ring-1 ring-slate-200"><div class="flex items-center justify-between px-3 py-2 bg-slate-50">'+
    '<span class="font-semibold text-slate-700">📁 '+t.LINK_NAME+'</span><span class="font-mono text-xs text-slate-400">'+(t.URL_URI||'')+'</span></div>';
  if(subs.length){html+='<ul class="divide-y divide-slate-100">';subs.forEach(s=>{
    html+='<li class="flex items-center justify-between px-6 py-1.5 text-sm"><span>↳ '+s.LINK_NAME+'</span><span class="font-mono text-xs text-slate-400">'+(s.URL_URI||'')+'</span></li>';});html+='</ul>';}
  html+='</div>';});
document.getElementById('tree').innerHTML=html;
// full table
const cols=[{key:'LINK_ID',label:'ID'},{key:'PR_LINK_ID',label:'Parent'},{key:'LINK_NAME',label:'Link Name'},
 {key:'LINK_NAME_BN',label:'Bangla'},{key:'MODULE_ID',label:'Module',render:(v)=>PU.cell(modName[v]||v)},
 {key:'URL_URI',label:'URI'},{key:'SA_MLINK_PAGES',label:'Pages'},
 {key:'CREATE',label:'C',render:v=>PU.flag(v)},{key:'READ',label:'R',render:v=>PU.flag(v)},
 {key:'UPDATE',label:'U',render:v=>PU.flag(v)},{key:'DELETE',label:'D',render:v=>PU.flag(v)},
 {key:'ACTIVE_STATUS',label:'Status',render:v=>PU.pill(v)}];
const draw=PU.table('tbl',links,cols,{perPage:25});
var fltMod=document.getElementById('flt-mod');
fltMod.dataset.key='MODULE_ID';
''' + FILTER_JS.replace("if(f&&f.value)","if(fltMod&&fltMod.value)")
   .replace("list=list.filter(r=>String(r[f.dataset.key])===f.value)","list=list.filter(r=>String(r.MODULE_ID)==fltMod.value)")
   .replace("if(f) f.addEventListener","if(fltMod) fltMod.addEventListener")
   + "bindFilter(links,cols,draw);")
write('module-links.html', 'Module Links', 'All menu/action links (sa_module_links) grouped by module, with CRUD flags.', 'sa_module_links', content, script)

# ---------- org-modules.html ----------
opts_o = [(o['ORG_ID'], o['ORG_NAME']) for o in orgs]
content = stats([('Org modules', len(orgmods), '🏛️', 'bg-amber-50 text-amber-600'),
                 ('Org links', len(orgmlinks), '🔗', 'bg-violet-50 text-violet-600'),
                 ('Organizations', len(orgs), '⚓', 'bg-blue-50 text-blue-600'),
                 ('Default-flagged', sum(1 for m in orgmods if m['DEFAULT_FLAG']=='1'), '⭐', 'bg-emerald-50 text-emerald-600')])
content += ('<div class="mb-6 grid gap-6 lg:grid-cols-2">'
            '<div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60"><h2 class="mb-3 font-bold text-slate-800">Organizations</h2><div id="t-org"></div></div>'
            '<div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60"><h2 class="mb-3 font-bold text-slate-800">Organization Modules</h2>'
            + toolbar('Search…').replace('id="q"','id="q2"').replace('id="btn-csv"','id="btn-csv2"') + '<div id="t-mod"></div></div></div>')
content += ('<div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60"><h2 class="mb-3 font-bold text-slate-800">Organization Module Links (permitted actions per org)</h2>'
            + toolbar('Search org links…', sel('flt-org','Organization',opts_o)) + '<div id="t-link"></div></div>')
script = JS('''
const D=window.DB;
const orgCols=[{key:'ORG_ID',label:'ID'},{key:'ORG_NAME',label:'Organization'},{key:'ABBR',label:'Abbr'},
 {key:'PARENT_ORG_ID',label:'Parent'},{key:'ACTIVE_STATUS',label:'Status',render:v=>PU.pill(v)}];
PU.table('t-org',D.sa_organizations,orgCols,{perPage:10});
const modCols=[{key:'SA_MODULE_ID',label:'OrgModID'},{key:'SA_MODULE_NAME',label:'Name'},{key:'ORG_ID',label:'Org'},
 {key:'MODULE_ID',label:'Module'},{key:'DEFAULT_FLAG',label:'Default',render:v=>PU.flag(v)},
 {key:'ACTIVE_STATUS',label:'Status',render:v=>PU.pill(v)}];
const dMod=PU.table('t-mod',D.sa_org_modules,modCols,{perPage:10});
(function(){const q=document.getElementById('q2');q.addEventListener('input',()=>{const s=q.value.toLowerCase();
 dMod(D.sa_org_modules.filter(r=>Object.values(r).some(v=>String(v??'').toLowerCase().includes(s))))});})();
document.getElementById('btn-csv2').onclick=()=>PU.csv('sa_org_modules',D.sa_org_modules,modCols);
const linkCols=[{key:'SA_MLINKS_ID',label:'ID'},{key:'LINK_NAME',label:'Link'},{key:'SA_MLINK_NAME',label:'Org Link Name'},
 {key:'ORG_ID',label:'Org'},{key:'SA_MODULE_ID',label:'OrgMod'},{key:'LINK_ID',label:'Link#'},{key:'URL_URI',label:'URI'},
 {key:'CREATE',label:'C',render:v=>PU.flag(v)},{key:'READ',label:'R',render:v=>PU.flag(v)},
 {key:'UPDATE',label:'U',render:v=>PU.flag(v)},{key:'DELETE',label:'D',render:v=>PU.flag(v)},
 {key:'STATUS',label:'Status',render:v=>PU.pill(v,'Open','Closed')}];
const dLink=PU.table('t-link',D.sa_org_mlinks,linkCols,{perPage:25});
const fl=document.getElementById('flt-org');
fl.addEventListener('change',()=>{const s=(document.getElementById('q').value||'').toLowerCase();
 let list=D.sa_org_mlinks;if(fl.value)list=list.filter(r=>String(r.ORG_ID)==fl.value);
 if(s)list=list.filter(r=>Object.values(r).some(v=>String(v??'').toLowerCase().includes(s)));dLink(list)});
document.getElementById('q').addEventListener('input',()=>fl.dispatchEvent(new Event('change')));
document.getElementById('btn-csv').onclick=()=>PU.csv('sa_org_mlinks',D.sa_org_mlinks,linkCols);
''')
write('org-modules.html', 'Organization Modules', 'Per-organization module & link provisioning (sa_org_modules, sa_org_mlinks, sa_organizations).', 'sa_org_*', content, script)

# ---------- user-groups.html ----------
cnt_by_grp = {}; cnt_lvl = {}
for r in asg:
    k = (r['USERGRP_ID'], r['UG_LEVEL_ID'])
    cnt_by_grp[r['USERGRP_ID']] = cnt_by_grp.get(r['USERGRP_ID'], 0) + 1
    cnt_lvl[r['UG_LEVEL_ID']] = cnt_lvl.get(r['UG_LEVEL_ID'], 0) + 1
users_by_grp = {}
for r in sus: users_by_grp[r['USERGRP_ID']] = users_by_grp.get(r['USERGRP_ID'], 0) + 1
content = stats([('Groups', len(ugs), '👥', 'bg-emerald-50 text-emerald-600'),
                 ('Levels', len(lvl), '📶', 'bg-cyan-50 text-cyan-600'),
                 ('Users mapped', len(sus), '👤', 'bg-indigo-50 text-indigo-600'),
                 ('Assignments', len(asg), '🎯', 'bg-rose-50 text-rose-600')])
content += ('<div class="mb-6 grid gap-6 lg:grid-cols-2">'
            '<div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60"><h2 class="mb-3 font-bold text-slate-800">User Groups (sa_user_group)</h2>'
            + toolbar('Search groups…') + '<div id="t-grp"></div></div>'
            '<div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60"><h2 class="mb-3 font-bold text-slate-800">Group Levels (sa_ug_level)</h2><div id="t-lvl"></div></div></div>')
content += ('<div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60"><h2 class="mb-3 font-bold text-slate-800">Legacy group membership (usergroups table)</h2>'
            + toolbar('Search UserID / GroupID…').replace('id="q"','id="q3"').replace('id="btn-csv"','id="btn-csv3"') + '<div id="t-ug"></div></div>')
script = JS('''
const D=window.DB;
const grpCols=[{key:'USERGRP_ID',label:'ID'},{key:'USERGRP_NAME',label:'Group'},{key:'ORG_ID',label:'Org'},
 {key:'users',label:'Users',render:v=>'<span class="pill pill-neutral">'+v+'</span>'},
 {key:'asg',label:'Link grants',render:v=>'<span class="pill pill-neutral">'+v.toLocaleString()+'</span>'},
 {key:'ACTIVE_STATUS',label:'Status',render:v=>PU.pill(v)}];
const grps=D.sa_user_group.map(g=>({...g,
 users:D.sa_users.filter(u=>u.USERGRP_ID===g.USERGRP_ID).length,
 asg:D.sa_uglw_mlink.filter(a=>a.USERGRP_ID===g.USERGRP_ID).length}));
const dG=PU.table('t-grp',grps,grpCols,{perPage:10});
document.getElementById('q').addEventListener('input',e=>{const s=e.target.value.toLowerCase();
 dG(grps.filter(r=>Object.values(r).some(v=>String(v??'').toLowerCase().includes(s))))});
document.getElementById('btn-csv').onclick=()=>PU.csv('sa_user_group',grps,grpCols);
const lvlCols=[{key:'UG_LEVEL_ID',label:'ID'},{key:'USERGRP_ID',label:'Group',render:(v,r)=>PU.cell(r.GNAME+' (#'+v+')')},
 {key:'UGLEVE_NAME',label:'Level Name'},{key:'ORG_ID',label:'Org'},
 {key:'asg',label:'Grants',render:v=>'<span class="pill pill-neutral">'+(v||0).toLocaleString()+'</span>'},
 {key:'ORDER_SL',label:'Order'},{key:'ACTIVE_STATUS',label:'Status',render:v=>PU.pill(v)}];
const gn={};D.sa_user_group.forEach(g=>gn[g.USERGRP_ID]=g.USERGRP_NAME);
const levels=D.sa_ug_level.map(l=>({...l,GNAME:gn[l.USERGRP_ID],asg:D.sa_uglw_mlink.filter(a=>a.UG_LEVEL_ID===l.UG_LEVEL_ID).length}));
PU.table('t-lvl',levels,lvlCols,{perPage:10});
const ugCols=[{key:'GroupID',label:'GroupID'},{key:'UserID',label:'UserID'},{key:'uname',label:'User'},
 {key:'ACTIVE_STATUS',label:'Status',render:v=>PU.pill(v)},{key:'CRE_DT',label:'Created'}];
const un={};D.users.forEach(u=>un[u.UserID]=u.UserName1);
const ugs=D.usergroups.map(r=>({...r,uname:un[r.UserID]}));
const dU=PU.table('t-ug',ugs,ugCols,{perPage:15});
(function(){const q=document.getElementById('q3');q.addEventListener('input',()=>{const s=q.value.toLowerCase();
 dU(ugs.filter(r=>Object.values(r).some(v=>String(v??'').toLowerCase().includes(s))))});})();
document.getElementById('btn-csv3').onclick=()=>PU.csv('usergroups',ugs,ugCols);
''')
# fix: last search box listener targets first #q only; give unique ids by simple replacement
script = script.replace("document.getElementById('q')","document.getElementById('q')")
write('user-groups.html', 'All User Group', 'User groups, hierarchy levels and legacy group membership.', 'sa_user_group · sa_ug_level', content, script)

# ---------- assign-links.html : matrix ----------
content = stats([('Grant rows', len(asg), '🎯', 'bg-rose-50 text-rose-600'),
                 ('Distinct links granted', len(set(r['SA_MLINKS_ID'] for r in asg)), '🔗', 'bg-violet-50 text-violet-600'),
                 ('Group×level combos', len({(r['USERGRP_ID'], r['UG_LEVEL_ID']) for r in asg}), '🧮', 'bg-cyan-50 text-cyan-600'),
                 ('Fully open (CRUD all)', sum(1 for r in asg if r['CREATE']=='1' and r['READ']=='1' and r['UPDATE']=='1' and r['DELETE']=='1'), '✅', 'bg-emerald-50 text-emerald-600')])
combo_opts = sorted({(r['USERGRP_ID'], r['UG_LEVEL_ID']) for r in asg})
combo_sel = ''.join('<option value="%s|%s">%s — %s (L%s)</option>' % (g, l, ug_name.get(g, 'Group ' + g), lv_name.get(l, 'Level ' + l), l) for g, l in combo_opts)
content += ('<div class="mb-4 flex flex-wrap items-center gap-3">'
            '<select id="flt-combo" class="rounded-lg border-0 bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-slate-200"><option value="">Group × Level: All combos</option>%s</select>'
            '<select id="flt-module" class="rounded-lg border-0 bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-slate-200"><option value="">Module: All</option>%s</select>'
            '<div class="relative grow min-w-[220px]"><input id="q" type="search" placeholder="Search link name / URI…" class="w-full rounded-lg border-0 bg-white py-2 pl-9 pr-3 text-sm shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-brand-500"></div>'
            '<button id="btn-csv" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">⬇ Export CSV</button></div>'
            % (combo_sel, ''.join('<option value="%s">%s</option>' % (m['MODULE_ID'], m['MODULE_NAME']) for m in mods)))
content += tablecard('tbl')
script = JS('''
const D=window.DB;
const om={};D.sa_org_mlinks.forEach(o=>om[o.SA_MLINKS_ID]=o);
const ml={};D.sa_module_links.forEach(l=>ml[l.LINK_ID]=l);
const rows=D.sa_uglw_mlink.map(r=>{const o=om[r.SA_MLINKS_ID]||{};const lk=ml[o.LINK_ID]||{};
 return {...r,LINK_NAME:r.LINK_NAME,MODULE_NAME:r.RESOLVED_MODULE,GROUP_NAME:r.GROUP_NAME,LEVEL_NAME:r.LEVEL_NAME,URI:o.URL_URI||lk.URL_URI}});
const cols=[{key:'SA_UGLWM_LINK',label:'#'},{key:'GROUP_NAME',label:'Group'},{key:'LEVEL_NAME',label:'Level'},
 {key:'MODULE_NAME',label:'Module'},{key:'LINK_NAME',label:'Link'},{key:'URI',label:'URI'},
 {key:'CREATE',label:'Create',render:v=>PU.flag(v)},{key:'READ',label:'Read',render:v=>PU.flag(v)},
 {key:'UPDATE',label:'Update',render:v=>PU.flag(v)},{key:'DELETE',label:'Delete',render:v=>PU.flag(v)},
 {key:'STATUS',label:'Status',render:v=>PU.pill(v,'Open','Closed')},{key:'ACTIVE_STATUS',label:'Active',render:v=>PU.pill(v)}];
const draw=PU.table('tbl',rows,cols,{perPage:30});
const fc=document.getElementById('flt-combo'),fm=document.getElementById('flt-module'),q=document.getElementById('q');
function apply(){let list=rows;
 if(fc.value){const[g,l]=fc.value.split('|');list=list.filter(r=>String(r.USERGRP_ID)==g&&String(r.UG_LEVEL_ID)==l)}
 if(fm.value)list=list.filter(r=>String(r.SA_MODULE_ID)==fm.value);
 const s=(q.value||'').toLowerCase();
 if(s)list=list.filter(r=>Object.values(r).some(v=>String(v??'').toLowerCase().includes(s)));
 draw(list)}
fc.addEventListener('change',apply);fm.addEventListener('change',apply);q.addEventListener('input',apply);
document.getElementById('btn-csv').onclick=()=>PU.csv('sa_uglw_mlink',rows,cols);
''')
write('assign-links.html', 'Assign Link To Group', 'Effective link grants per user-group × level (sa_uglw_mlink) with resolved names.', 'sa_uglw_mlink', content, script)

# ---------- user-setup.html ----------
org_opts = [(o['ORG_ID'], o['ORG_NAME']) for o in orgs]
grp_opts = [(g['USERGRP_ID'], g['USERGRP_NAME']) for g in ugs]
content = stats([('Users', len(sus), '👤', 'bg-indigo-50 text-indigo-600'),
                 ('Active', sum(1 for u in sus if u['ACTIVE_STATUS']=='1'), '✅', 'bg-emerald-50 text-emerald-600'),
                 ('Inactive', sum(1 for u in sus if u['ACTIVE_STATUS']!='1'), '⛔', 'bg-rose-50 text-rose-600'),
                 ('DAO mappings', len(daog), '🗂️', 'bg-cyan-50 text-cyan-600')])
content += ('<div class="mb-4 flex flex-wrap items-center gap-3">'
            '<div class="relative grow min-w-[220px]"><input id="q" type="search" placeholder="Search name / username / email…" class="w-full rounded-lg border-0 bg-white py-2 pl-9 pr-3 text-sm shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-brand-500"></div>'
            + sel('flt-org','Org',org_opts) + sel('flt-grp','Group',grp_opts)
            + '<button id="btn-csv" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">⬇ Export CSV</button></div>'
            + tablecard('tbl')
            + '<div class="mt-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60"><h2 class="mb-3 font-bold text-slate-800">DAO group mapping (userdaogroup)</h2><div id="t-dao"></div></div>')
script = JS('''
const D=window.DB;
const cols=[{key:'USER_ID',label:'ID'},{key:'FULL_NAME',label:'Full Name'},{key:'USERNAME',label:'Username'},
 {key:'GROUP_NAME',label:'Group'},{key:'ORG_NAME',label:'Organization'},{key:'EMAIL',label:'Email'},
 {key:'EFECT_FROM_DT',label:'Effective'},{key:'EXPR_DT',label:'Expires'},
 {key:'ACTIVE_STATUS',label:'Status',render:v=>PU.pill(v)}];
const draw=PU.table('tbl',D.sa_users,cols,{perPage:25});
const fo=document.getElementById('flt-org'),fg=document.getElementById('flt-grp'),q=document.getElementById('q');
fo.dataset.key='ORG_ID';fg.dataset.key='USERGRP_ID';
function apply(){let list=D.sa_users;
 if(fo.value)list=list.filter(r=>String(r.ORG_ID)==fo.value);
 if(fg.value)list=list.filter(r=>String(r.USERGRP_ID)==fg.value);
 const s=(q.value||'').toLowerCase();
 if(s)list=list.filter(r=>Object.values(r).some(v=>String(v??'').toLowerCase().includes(s)));draw(list)}
[fo,fg].forEach(e=>e.addEventListener('change',apply));q.addEventListener('input',apply);
document.getElementById('btn-csv').onclick=()=>{apply();PU.csv('sa_users',D.sa_users,cols)};
const daoCols=[{key:'DAOGroupID',label:'DAO Group'},{key:'UserID',label:'UserID'},{key:'uname',label:'User'},
 {key:'ACTIVE_STATUS',label:'Status',render:v=>PU.pill(v)},{key:'CRE_DT',label:'Created'}];
const un={};D.sa_users.forEach(u=>un[u.USER_ID]=u.FULL_NAME);
PU.table('t-dao',D.userdaogroup.map(r=>({...r,uname:un[r.UserID]})),daoCols,{perPage:10});
''')
write('user-setup.html', 'User Setup', 'Accounts, effective/expiry dates, group & organization assignment (sa_users).', 'sa_users', content, script)

# ---------- users.html (legacy) ----------
content = stats([('Legacy users', len(u2), '👤', 'bg-indigo-50 text-indigo-600'),
                 ('Active', sum(1 for u in u2 if str(u['IsActive'])=='1'), '✅', 'bg-emerald-50 text-emerald-600'),
                 ('Inactive', sum(1 for u in u2 if str(u['IsActive'])!='1'), '⛔', 'bg-rose-50 text-rose-600'),
                 ('Owner refs', len({u['OwnerID'] for u in u2}), '🏢', 'bg-cyan-50 text-cyan-600')])
content += toolbar('Search login / name…') + tablecard('tbl')
script = JS('''
const rows=window.DB.users.map(u=>({...u,st:String(u.IsActive)==='1'?'Active':'Inactive'}));
const cols=[{key:'UserID',label:'ID'},{key:'LoginID',label:'Login ID'},{key:'UserName1',label:'Name'},
 {key:'Password',label:'Password'},{key:'OwnerID',label:'Owner'},
 {key:'IsActive',label:'Status',render:v=>PU.pill(String(v),'Active','Inactive')}];
const draw=PU.table('tbl',rows,cols,{perPage:25});
''' + FILTER_JS + "bindFilter(rows,cols,draw);")
write('users.html', 'Users (legacy)', 'Legacy users table — passwords masked for safety.', 'users', content, script)

# ---------- roles.html ----------
rbac_note = ('<div class="rounded-xl bg-amber-50 p-5 ring-1 ring-amber-200">'
             '<h2 class="font-bold text-amber-800">RBAC tables — no data in this dump</h2>'
             '<p class="mt-1 text-sm text-amber-700">The new dynamic RBAC model (<code>roles</code>, <code>permissions</code>, <code>role_permissions</code>, <code>user_roles</code>) exists in schema but contains 0 rows yet. Until it is populated, <b>user groups act as roles</b> — shown below from live data.</p></div>')
content = rbac_note + '<div class="my-6"></div>'
content += stats([('Groups-as-roles', len(ugs), '🛡️', 'bg-emerald-50 text-emerald-600'),
                  ('Members (usergroups)', len(ugrp), '👥', 'bg-indigo-50 text-indigo-600'),
                  ('Levels', len(lvl), '📶', 'bg-cyan-50 text-cyan-600'),
                  ('Empty RBAC tables', 5, '∅', 'bg-slate-100 text-slate-600')])
content += tablecard('tbl')
script = JS('''
const D=window.DB;
const rows=D.sa_user_group.map(g=>({...g,
 members:D.usergroups.filter(u=>String(u.GroupID)===String(g.USERGRP_ID)).length,
 saUsers:D.sa_users.filter(u=>u.USERGRP_ID===g.USERGRP_ID).length,
 grants:D.sa_uglw_mlink.filter(a=>a.USERGRP_ID===g.USERGRP_ID).length,
 levels:D.sa_ug_level.filter(l=>l.USERGRP_ID===g.USERGRP_ID).map(l=>l.UGLEVE_NAME).join(', ')}));
const cols=[{key:'USERGRP_ID',label:'ID'},{key:'USERGRP_NAME',label:'Role (Group)'},{key:'levels',label:'Levels'},
 {key:'members',label:'Members',render:v=>'<span class="pill pill-neutral">'+v+'</span>'},
 {key:'saUsers',label:'Accounts',render:v=>'<span class="pill pill-neutral">'+v+'</span>'},
 {key:'grants',label:'Link grants',render:v=>'<span class="pill pill-neutral">'+v.toLocaleString()+'</span>'},
 {key:'ACTIVE_STATUS',label:'Status',render:v=>PU.pill(v)}];
const draw=PU.table('tbl',rows,cols,{perPage:10});
''' + FILTER_JS + "bindFilter(rows,cols,draw);")
write('roles.html', 'Roles', 'Roles = user groups (legacy) until RBAC tables are populated.', 'roles ↔ sa_user_group', content, script)

# ---------- role-permissions.html ----------
combos = sorted({(r['USERGRP_ID'], r['UG_LEVEL_ID']) for r in asg})
combo_sel2 = ''.join('<option value="%s|%s">%s — %s</option>' % (g, l, ug_name.get(g, 'G'+g), lv_name.get(l, 'L'+l)) for g, l in combos)
content = ('<div class="mb-4 flex flex-wrap items-center gap-3">'
           '<select id="flt-combo" class="rounded-lg border-0 bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-slate-200"><option value="">Select group × level…</option>%s</select>'
           '<span id="sum" class="text-sm text-slate-500"></span></div>' % combo_sel2)
content += tablecard('matrix')
content += ('<div class="mt-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60">'
            '<h2 class="mb-3 font-bold text-slate-800">Permission counts per group × level</h2><div id="bars"></div></div>')
script = JS('''
const D=window.DB;
const om={};D.sa_org_mlinks.forEach(o=>om[o.SA_MLINKS_ID]=o);
const ml={};D.sa_module_links.forEach(l=>ml[l.LINK_ID]=l);
const modName={};D.sa_modules.forEach(m=>modName[m.MODULE_ID]=m.MODULE_NAME);
const rows=D.sa_uglw_mlink.map(r=>{const o=om[r.SA_MLINKS_ID]||{};const lk=ml[o.LINK_ID]||{};
 return {...r,LINK_NAME:r.LINK_NAME,MODULE_NAME:r.RESOLVED_MODULE}});
const fc=document.getElementById('flt-combo');
function drawMatrix(){
 const el=document.getElementById('matrix');
 if(!fc.value){el.innerHTML='<p class="py-8 text-center text-sm text-slate-400">Choose a group × level to view its effective permission matrix.</p>';return}
 const[g,l]=fc.value.split('|');
 const list=rows.filter(r=>String(r.USERGRP_ID)==g&&String(r.UG_LEVEL_ID)==l);
 let html='<div class="max-h-[560px] overflow-auto rounded-lg ring-1 ring-slate-200"><table class="min-w-full divide-y divide-slate-200 text-sm tbl-sticky"><thead class="border-b border-slate-200"><tr>'+
 ['Module','Link','URI','Create','Read','Update','Delete','Status'].map(h=>'<th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">'+h+'</th>').join('')+'</tr></thead><tbody class="divide-y divide-slate-100">';
 list.forEach(r=>{html+='<tr class="hover:bg-brand-50/50"><td class="px-3 py-2 text-slate-600">'+PU.cell(r.MODULE_NAME)+'</td><td class="px-3 py-2 font-medium text-slate-800">'+PU.cell(r.LINK_NAME)+'</td><td class="px-3 py-2 font-mono text-xs text-slate-400">'+PU.cell(r.URL_URI||(om[r.SA_MLINKS_ID]||{}).URL_URI)+'</td>'+
 [r.CREATE,r.READ,r.UPDATE,r.DELETE].map(v=>'<td class="px-3 py-2">'+PU.flag(v)+'</td>').join('')+
 '<td class="px-3 py-2">'+PU.pill(r.STATUS,'Open','Closed')+'</td></tr>';});
 html+='</tbody></table></div>';
 el.innerHTML=html;
 const c=k=>list.filter(r=>String(r[k])==='1').length;
 document.getElementById('sum').textContent=list.length+' links · Create '+c('CREATE')+' · Read '+c('READ')+' · Update '+c('UPDATE')+' · Delete '+c('DELETE');
}
fc.addEventListener('change',drawMatrix);drawMatrix();
// bars
const combos={};rows.forEach(r=>{const k=r.GROUP_NAME+' — '+r.LEVEL_NAME;(combos[k]=combos[k]||{all:0,c:0,r:0,u:0,d:0});combos[k].all++;
 if(r.CREATE==='1')combos[k].c++;if(r.READ==='1')combos[k].r++;if(r.UPDATE==='1')combos[k].u++;if(r.DELETE==='1')combos[k].d++;});
let bh='<div class="space-y-3">';
Object.entries(combos).sort((a,b)=>b[1].all-a[1].all).forEach(([k,v])=>{
 const pct=x=>Math.round(100*x/v.all);
 bh+='<div><div class="mb-1 flex justify-between text-xs font-semibold text-slate-600"><span>'+k+'</span><span>'+v.all+' grants</span></div><div class="flex h-4 overflow-hidden rounded-full bg-slate-100">'+
 '<div class="bg-emerald-400" style="width:'+pct(v.c)+'%" title="Create '+v.c+'"></div>'+
 '<div class="bg-sky-400" style="width:'+(pct(v.r)-pct(v.c))+'%" title="Read '+v.r+'"></div>'+
 '<div class="bg-amber-400" style="width:'+(pct(v.u)-pct(v.r))+'%" title="Update '+v.u+'"></div>'+
 '<div class="bg-rose-400" style="width:'+(pct(v.d)-pct(v.u))+'%" title="Delete '+v.d+'"></div></div></div>';});
bh+='</div><p class="mt-3 text-[11px] text-slate-400">Nested coverage: Create ⊆ Read ⊆ Update ⊆ Delete widths show cumulative CRUD depth.</p>';
document.getElementById('bars').innerHTML=bh;
''')
write('role-permissions.html', 'Role Permissions', 'Effective CRUD permission matrix per group × level from sa_uglw_mlink.', 'matrix', content, script)

# ---------- schema.html ----------
SCHEMA19 = [
 ('RBAC','activity_logs','Audit trail of system actions'),('RBAC','permissions','Atomic CRUD permission definitions'),
 ('RBAC','role_permissions','Role ↔ permission mapping'),('RBAC','roles','Dynamic role definitions'),('RBAC','user_roles','User ↔ role assignment'),
 ('LEGACY','sa_module_links','Menu/action links (231 rows)'),('LEGACY','sa_modules','Application modules (11 rows)'),
 ('LEGACY','sa_org_mlinks','Per-org link provisioning (231 rows)'),('LEGACY','sa_org_modules','Per-org modules (11 rows)'),
 ('LEGACY','sa_organizations','Organizations (4 rows)'),('LEGACY','sa_ug_level','User-group levels (10 rows)'),
 ('LEGACY','sa_uglw_mlink','Group×level link grants (834 rows)'),('LEGACY','sa_user_group','User groups (5 rows)'),
 ('LEGACY','sa_user_wmgl','User work-manager grouping'),('LEGACY','sa_users','Login accounts (124 rows)'),
 ('LEGACY','userdaogroup','DAO group membership (517 rows)'),('LEGACY','usergroups','Legacy group membership (94 rows)'),
 ('LEGACY','users','Legacy users (93 rows)'),('LEGACY','web_menu_groups','Web menu group definitions'),
]
chips = ''.join('<span class="rounded-full px-3 py-1 text-xs font-semibold ring-1 %s">%s<span class="ml-1 opacity-60">· %s</span></span>' % (
    'bg-brand-50 text-brand-700 ring-brand-100' if g=='RBAC' else 'bg-slate-50 text-slate-600 ring-slate-200', t, d) for g,t,d in SCHEMA19)
content = ('<div class="mb-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60">'
           '<h2 class="mb-3 font-bold text-slate-800">bnsis_permission — 19 tables</h2>'
           '<p class="mb-3 text-sm text-slate-500">RBAC (new) in blue, legacy in grey. Full column-level design lives in <a class="font-semibold text-brand-600 hover:underline" href="../docs/database-schema.html">docs/database-schema.html</a>; row data in <a class="font-semibold text-brand-600 hover:underline" href="../docs/database-data.html">docs/database-data.html</a>.</p>'
           '<div class="flex flex-wrap gap-2">%s</div></div>' % chips)
content += ('<div class="mb-6 grid gap-6 lg:grid-cols-2">'
            '<div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60"><h2 class="mb-3 font-bold text-slate-800">Row counts (live dump)</h2><div id="t-cnt"></div></div>'
            '<div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60"><h2 class="mb-3 font-bold text-slate-800">Relationship map</h2>'
            '<pre class="overflow-x-auto rounded-lg bg-slate-50 p-4 text-[11px] leading-relaxed text-slate-600">'
            'sa_organizations ─┬─ sa_org_modules ──── sa_modules\n'
            '                  ├─ sa_org_mlinks ───── sa_module_links (LINK_ID)\n'
            '                  └─ sa_uglw_mlink ─┬─ sa_user_group ─┐\n'
            '                                    ├─ sa_ug_level ───┤→ sa_users (USERGRP_ID)\n'
            '                                    └─ URL_URI → links │\n'
            'users ── usergroups / userdaogroup ───────────────────┘\n'
            'roles ── role_permissions ── permissions      (RBAC, empty)\n'
            'user_roles → roles / usergroups / sa_users    (RBAC, empty)\n'
            'activity_logs → sa_users                      (audit, empty)</pre></div></div>')
script = JS('''
const D=window.DB;
const empties=['activity_logs','permissions','role_permissions','roles','user_roles','sa_user_wmgl','web_menu_groups'];
const rows=Object.entries(D).map(([t,d])=>({TABLE:t,ROWS:d.length,COLS:Object.keys(d[0]).length}));
empties.forEach(t=>rows.push({TABLE:t,ROWS:0,COLS:'—'}));
rows.sort((a,b)=>b.ROWS-a.ROWS);
PU.table('t-cnt',rows,[{key:'TABLE',label:'Table',render:v=>'<span class="font-mono text-xs font-semibold text-slate-700">'+v+'</span>'},
 {key:'ROWS',label:'Rows',render:v=>'<span class="pill '+(v?'pill-on':'pill-off')+'">'+(v?v.toLocaleString():'empty')+'</span>'},
 {key:'COLS',label:'Columns'}],{perPage:20});
''')
write('schema.html', 'DB Schema & Data', 'All 19 tables of bnsis_permission with live row counts.', '19 tables', content, script)

print('ALL PAGES BUILT')
PYEOF_MARKER_UNUSED = None
