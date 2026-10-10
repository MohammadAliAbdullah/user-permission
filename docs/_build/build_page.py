# -*- coding: utf-8 -*-
"""Assemble docs/database-schema.html from _build/tables.html + _build/meta.json."""
import json, html, os
HERE=os.path.dirname(os.path.abspath(__file__))
meta=json.load(open(os.path.join(HERE,'meta.json')))
counts=meta['counts']; edges=meta['edges']
RBAC={'activity_logs','permissions','role_permissions','roles','user_roles'}
order=[t for t in sorted(counts) if t in RBAC]+[t for t in sorted(counts) if t not in RBAC]
total_cols=sum(counts.values())
E=html.escape

cards=''
for i,t in enumerate(order):
    g='em' if t in RBAC else 'sk'
    cards+='<a class="stat-mini m-%s" href="#%s"><span class="sm-num">%02d</span><span class="sm-name">%s</span><span class="sm-cols">%d cols</span></a>'%(g,t,i+1,E(t),counts[t])

pos={
 'sa_organizations':(30,20),'sa_modules':(30,150),'sa_module_links':(300,150),
 'permissions':(570,150),'role_permissions':(840,150),'roles':(1080,150),
 'user_roles':(1080,320),'sa_users':(840,320),'usergroups':(570,320),
 'sa_org_modules':(30,320),'sa_org_mlinks':(300,320),
 'sa_user_group':(570,490),'sa_ug_level':(840,490),'sa_uglw_mlink':(1080,490),
 'sa_ugw_mlink':(300,490),'sa_user_wmgl':(30,490),
 'userdaogroup':(300,640),'users':(570,640),'activity_logs':(840,640),
}
W,H=190,46
def anchor(n,side):
    x,y=pos[n]
    return {'l':(x,y+H/2.0),'r':(x+W,y+H/2.0),'t':(x+W/2.0,y),'b':(x+W/2.0,y+H)}[side]
def edge(a,b,as_,bs,curve=0.0,dash=False,color='#64748b'):
    (x1,y1)=anchor(a,as_); (x2,y2)=anchor(b,bs)
    mx,my=(x1+x2)/2,(y1+y2)/2
    if curve:
        dx,dy=x2-x1,y2-y1; L=(dx*dx+dy*dy)**0.5 or 1
        cx,cy=mx-dy/L*curve, my+dx/L*curve
        d='M%.0f %.0f Q%.0f %.0f %.0f %.0f'%(x1,y1,cx,cy,x2,y2)
    else: d='M%.0f %.0f L%.0f %.0f'%(x1,y1,x2,y2)
    return '<path d="%s" fill="none" stroke="%s" stroke-width="1.4"%s marker-end="url(#arr)"/>'%(d,color,' stroke-dasharray="5 4"' if dash else '')

svg_edges=''
for src,scol,dst,dcol,name in edges:
    sx,sy=pos[src]; dx,dy=pos[dst]
    if abs(dx-sx)>abs(dy-sy): as_,bs=('r','l') if dx>sx else ('l','r')
    else: as_,bs=('b','t') if dy>sy else ('t','b')
    svg_edges+=edge(src,dst,as_,bs,curve=18,color='#0ea5e9')
logical=[
 ('sa_users','user_roles','r','l'),('user_roles','roles','r','l'),('user_roles','usergroups','b','t'),
 ('sa_organizations','sa_org_modules','b','t'),('sa_modules','sa_org_modules','r','l'),
 ('sa_module_links','sa_org_mlinks','b','t'),('sa_organizations','sa_org_mlinks','r','l'),
 ('sa_user_group','sa_uglw_mlink','r','l'),('sa_ug_level','sa_uglw_mlink','r','l'),
 ('sa_user_group','sa_ugw_mlink','t','b'),('sa_module_links','sa_ugw_mlink','b','t'),
 ('sa_users','sa_user_wmgl','l','r'),('sa_user_group','userdaogroup','b','t'),
 ('sa_users','users','b','t'),('sa_users','activity_logs','b','t'),
 ('sa_user_group','usergroups','t','b'),
]
for a,b,s1,s2 in logical:
    svg_edges+=edge(a,b,s1,s2,curve=14,dash=True,color='#94a3b8')

nodes=''
for t,(x,y) in pos.items():
    cls='er-em' if t in RBAC else 'er-sk'
    nodes+='<g class="er-node" onclick="location.hash=\'#%s\'"><rect x="%d" y="%d" width="%d" height="%d" rx="10" class="%s"/><text x="%d" y="%d" class="er-t">%s</text><text x="%d" y="%d" class="er-c">%d columns · %s</text></g>'%(
        t,x,y,W,H,cls,x+12,y+20,E(t),x+12,y+36,counts[t],'RBAC' if t in RBAC else 'legacy')

SW,SH=1300,720
CSS=open(os.path.join(HERE,'style.css'),encoding='utf-8').read()
JS=open(os.path.join(HERE,'app.js'),encoding='utf-8').read()
tables_html=open(os.path.join(HERE,'tables.html'),encoding='utf-8').read()
navsec=''.join('<a class="nav-chip %s" href="#%s">%s<span class="nc">%d</span></a>'%('nc-em' if t in RBAC else 'nc-sk',t,E(t),counts[t]) for t in order)

page='''<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>bnsis_permission — Full Database Schema (19 Tables)</title>
<meta name="description" content="Complete visual database schema UI for the Dynamic User Role &amp; Permission Management Module — all 19 tables with columns, keys, indexes and relationships.">
<style>'''+CSS+'''</style>
</head>
<body>
<div class="hero"><div class="hero-in">
<h1>🗄️ Database Schema — <code>bnsis_permission</code></h1>
<p>Dynamic User Role &amp; Permission Management Module · complete UI design of all <b>19 tables</b> — columns, types, defaults, primary / unique / foreign keys, indexes and relationships — generated from MySQL/MariaDB · updated 2026-10-11</p>
<div class="stats">
<div class="stat"><b>19</b><span>Total Tables</span></div>
<div class="stat"><b style="color:#6ee7b7">5</b><span>RBAC (new)</span></div>
<div class="stat"><b style="color:#7dd3fc">14</b><span>Legacy</span></div>
<div class="stat"><b>'''+str(total_cols)+'''</b><span>Columns</span></div>
<div class="stat"><b>'''+str(len(edges))+'''</b><span>FK Constraints</span></div>
</div>
</div></div>

<div class="wrap">

<section class="card" id="index">
<h2><span class="dot"></span>Table Index — 19 Tables</h2>
<p class="sub">Click any table to jump to its full column design. <span style="color:#047857;font-weight:700">Green</span> = new RBAC tables · <span style="color:#0369a1;font-weight:700">Blue</span> = legacy tables.</p>
<div class="nav-grid">'''+navsec+'''</div>
<div class="mini-grid">'''+cards+'''</div>
</section>

<section class="card">
<h2><span class="dot" style="background:#0284c7"></span>Relationship Diagram (ER Overview)</h2>
<p class="sub">Solid blue lines = enforced FOREIGN KEY constraints · dashed grey lines = logical relationships (legacy model). Click a node to open its table below.</p>
<div class="er-scroll">
<svg width="'''+str(SW)+'''" height="'''+str(SH)+'''" viewBox="0 0 '''+str(SW)+''' '''+str(SH)+'''" xmlns="http://www.w3.org/2000/svg">
<defs><marker id="arr" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z" fill="#64748b"/></marker></defs>
'''+svg_edges+'''
'''+nodes+'''
</svg>
</div>
<div class="legend">
<span><span class="badge b-pk">PK</span> Primary Key</span>
<span><span class="badge b-uq">UQ</span> Unique Key</span>
<span><span class="badge b-fk">FK</span> Foreign Key</span>
<span><span class="badge b-idx">IDX</span> Index</span>
</div>
</section>

<h2 style="margin:8px 4px 14px;font-size:20px">Full Table Designs</h2>
<div class="toolbar">
<div class="search"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input id="q" type="search" placeholder="Search table or column name… (e.g. USER_ID, role, link)"></div>
<div class="seg"><button data-m="all" class="on" onclick="filterMode('all')">All (19)</button><button data-m="rbac" onclick="filterMode('rbac')">RBAC (5)</button><button data-m="legacy" onclick="filterMode('legacy')">Legacy (14)</button></div>
<button class="btn" id="btnExpand" onclick="toggleAll()">Expand all</button>
<span id="count" style="font-size:12.5px;color:#64748b;font-weight:700"></span>
</div>

'''+tables_html+'''

<footer>bnsis_permission · 19 tables · generated from docs/database-schema.sql · <a href="#index" style="text-decoration:underline">Back to index ↑</a></footer>
</div>
<button class="backtop" id="bt" title="Back to top" onclick="scrollTo({top:0,behavior:'smooth'})">↑</button>
<script>'''+JS+'''</script>
</body></html>'''
open(os.path.join(HERE,'..','database-schema.html'),'w',encoding='utf-8').write(page)
print('written', len(page), 'bytes ->', os.path.abspath(os.path.join(HERE,'..','database-schema.html')))
