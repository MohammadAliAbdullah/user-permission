# -*- coding: utf-8 -*-
"""Parse docs/database-schema.sql -> per-table HTML cards + meta.json (FK graph)."""
import re, html, json, os
HERE=os.path.dirname(os.path.abspath(__file__))
SQL=open(os.path.join(HERE,'..','database-schema.sql'),encoding='utf-8').read()

tables={}
blocks=re.findall(r"CREATE TABLE `(\w+)` \((.*?)\n\) ENGINE=([^;]*);", SQL, re.S)
for tname, body, tail in blocks:
    cols=[]; pk=[]; uniques=[]; indexes=[]; fks=[]
    for line in body.split('\n'):
        s=line.strip().rstrip(',')
        if not s: continue
        if s.startswith(('PRIMARY KEY','UNIQUE KEY','KEY','INDEX','CONSTRAINT')):
            if s.startswith('PRIMARY KEY'):
                pk=re.findall(r"`(\w+)`", s)
                for p in pk:
                    for c in cols:
                        if c['name']==p: c['pk']=True
            elif s.startswith('UNIQUE KEY'):
                nm=re.match(r"UNIQUE KEY `(\w+)` \((.*)\)", s)
                if nm: uniques.append((nm.group(1), re.findall(r"`(\w+)`", nm.group(2))))
            elif s.startswith(('KEY','INDEX')):
                km=re.match(r"(?:KEY|INDEX) `(\w+)` \((.*)\)", s)
                if km: indexes.append((km.group(1), re.findall(r"`(\w+)`", km.group(2))))
            else:
                fm=re.match(r"CONSTRAINT `(\w+)` FOREIGN KEY \(`(\w+)`\) REFERENCES `(\w+)` \(`(\w+)`\)(.*)", s)
                if fm:
                    rest=fm.group(5).upper()
                    od='CASCADE' if 'CASCADE' in rest else ('SET NULL' if 'SET NULL' in rest else ('RESTRICT' if 'RESTRICT' in rest else ''))
                    fks.append(dict(name=fm.group(1),col=fm.group(2),rtab=fm.group(3),rcol=fm.group(4),on_delete=od))
            continue
        m=re.match(r"`(\w+)` (.+)", s)
        if m:
            cname, rest = m.group(1), m.group(2)
            tm=re.match(r"([\w]+(?:\([\d,]+\))?(?: unsigned)?)(.*)", rest)
            ctype, mod = tm.group(1), tm.group(2)
            nullable='NO' if 'NOT NULL' in mod else 'YES'
            default='NULL'
            dm=re.search(r"DEFAULT (current_timestamp\(\)|'[^']*'|[^\s,]+)", mod)
            if dm: default=dm.group(1).strip("'")
            cmt=re.search(r"COMMENT '((?:[^']|'')*)'", mod)
            cols.append(dict(name=cname,type=ctype,nullable=nullable,default=default,
                             comment=cmt.group(1).replace("''","'") if cmt else ''))
    em=re.search(r"AUTO_INCREMENT=(\d+)", tail)
    tables[tname]=dict(cols=cols,pk=pk,uniques=uniques,indexes=indexes,fks=fks,
                       auto_inc=int(em.group(1)) if em else None)

RBAC={'activity_logs','permissions','role_permissions','roles','user_roles'}
DESC={
 'activity_logs':'Admin activity audit trail — every login / create / update / delete action is recorded here.',
 'permissions':'Granular CRUD permission definitions derived from module links (create · read · update · delete per link).',
 'role_permissions':'Junction table granting permissions to roles; UNIQUE(role, permission) prevents duplicate grants.',
 'roles':'RBAC role master. IS_SYSTEM roles (e.g. Super Admin) cannot be deleted.',
 'user_roles':'User ↔ role assignment junction; DEFAULT_FG marks the user primary/default group.',
 'sa_module_links':'Legacy menu/action links (pages & buttons) under modules; carries inline CREATE/READ/UPDATE/DELETE flags.',
 'sa_modules':'Legacy application modules (top-level menu groups) with Bengali names & icons.',
 'sa_org_mlinks':'Per-organization copies of module links (legacy org-level menu access grants).',
 'sa_org_modules':'Per-organization module activation — which modules an organization has enabled.',
 'sa_organizations':'Organization master — name, address, contacts, logo and parent-org hierarchy.',
 'sa_ug_level':'User-group level definitions (hierarchy levels used by the legacy permission model).',
 'sa_uglw_mlink':'Legacy grants: user-group ↔ work/school-level ↔ module-link access.',
 'sa_ugw_mlink':'Legacy grants: user-group ↔ module-link access (institution-wide).',
 'sa_user_group':'Legacy user-group master (predecessor of the new roles table).',
 'sa_user_wmgl':'Legacy mapping: user ↔ work/school ↔ group-level assignments.',
 'sa_users':'Core user accounts — login credentials, profile fields and organization/institution scoping.',
 'userdaogroup':'Legacy DAO (office/institution) ↔ user-group mapping.',
 'usergroups':'Legacy table exposing user groups joined with their DAO scope.',
 'users':'Legacy users table kept for backward compatibility with older modules.',
}
def group(t): return 'rbac' if t in RBAC else 'legacy'

all_tables=sorted(tables)
fk_edges=[]
for t in all_tables:
    for fk in tables[t]['fks']:
        fk_edges.append((t,fk['col'],fk['rtab'],fk['rcol'],fk['name']))

E=html.escape
def badge(cls,txt): return '<span class="badge %s">%s</span>'%(cls,txt)

parts=[]
for t in all_tables:
    d=tables[t]; g=group(t)
    inb=[(ft,fc) for ft,fc,tt,tc,_ in fk_edges if tt==t]
    has_cmt=any(c['comment'] for c in d['cols'])
    rows=[]
    for c in d['cols']:
        badges=''
        if c.get('pk'): badges+=badge('b-pk','PK')
        if [c['name']] in [u[1] for u in d['uniques']]: badges+=badge('b-uq','UQ')
        if any(fk['col']==c['name'] for fk in d['fks']): badges+=badge('b-fk','FK')
        if any(c['name'] in icols for _,icols in d['indexes']): badges+=badge('b-idx','IDX')
        fknote=''
        for fk in d['fks']:
            if fk['col']==c['name']:
                fknote='<div class="fk-note">%s → <a href="#%s">%s.%s</a>%s</div>'%(
                    E(fk['name']),fk['rtab'],E(fk['rtab']),E(fk['rcol']),
                    (' · ON DELETE '+fk['on_delete']) if fk['on_delete'] else '')
        nn='NULL' if c['nullable']=='YES' else 'NOT NULL'
        dv='' if c['default']=='NULL' else E(str(c['default']))
        nullcell='<span class="mut">%s</span>%s'%(nn,(' <span class="dv">DEFAULT %s</span>'%dv) if dv else '')
        rows.append('<tr><td class="c-name"><code>%s</code> %s%s</td><td class="c-type"><code>%s</code></td><td class="c-null">%s</td>%s</tr>'%(
            E(c['name']),badges,fknote,E(c['type']),nullcell,
            ('<td class="c-cmt">%s</td>'%E(c['comment'])) if has_cmt else ''))
    keys=[]
    if d['pk']: keys.append('<span class="chip chip-pk">PRIMARY KEY (%s)</span>'%', '.join(E(p) for p in d['pk']))
    for uname,ucols in d['uniques']: keys.append('<span class="chip chip-uq">UNIQUE %s (%s)</span>'%(E(uname),', '.join(E(u) for u in ucols)))
    for iname,icols in d['indexes']: keys.append('<span class="chip chip-idx">INDEX %s (%s)</span>'%(E(iname),', '.join(E(u) for u in icols)))
    rel=[]
    for fk in d['fks']: rel.append('<span class="chip chip-out">↗ %s → <a href="#%s">%s</a>.%s</span>'%(E(fk['col']),fk['rtab'],E(fk['rtab']),E(fk['rcol'])))
    for ft,fc in inb: rel.append('<span class="chip chip-in">↙ referenced by <a href="#%s">%s</a>.%s</span>'%(ft,E(ft),E(fc)))
    hc,hcl=dict(rbac=('emerald','RBAC · New'),legacy=('sky','Legacy'))[g]
    meta='%d cols'%len(d['cols'])
    if d['pk']: meta+=' · PK: '+', '.join(E(p) for p in d['pk'])
    if d['fks']: meta+=' · %d FK'%len(d['fks'])
    if inb: meta+=' · %d inbound'%len(inb)
    card =('<article id="%s" class="tbl %s" data-group="%s" data-cols="%d" data-search="%s">\n'
      '  <div class="tbl-head" onclick="toggleTbl(this.parentElement)">\n'
      '    <svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>\n'
      '    <h3><code>%s</code></h3>\n'
      '    <span class="pill pill-%s">%s</span>\n'
      '    <span class="meta">%s</span>\n'
      '  </div>\n'
      '  <p class="tbl-desc">@@DESC@@</p>\n'
      '  <div class="keys">@@KEYS@@</div>\n'
      '  <div class="rels">@@RELS@@</div>\n'
      '  <div class="tbl-body"><table><thead><tr><th>Column</th><th>Type</th><th>Null / Default</th>%s</tr></thead><tbody>\n@@ROWS@@\n'
      '  </tbody></table></div>\n'
      '</article>')%(E(t),g,E(g),len(d['cols']),E((t+' '+' '.join(c['name'] for c in d['cols'])).lower()),
        E(t),hc,hcl,meta,
        '<th>Comment</th>' if has_cmt else '')
    card = card.replace('@@DESC@@',E(DESC.get(t,''))).replace('@@KEYS@@',''.join(keys)).replace('@@RELS@@',''.join(rel)).replace('@@ROWS@@','\n'.join(rows))
    parts.append(card)

open(os.path.join(HERE,'tables.html'),'w',encoding='utf-8').write('\n'.join(parts))
json.dump(dict(counts={t:len(tables[t]['cols']) for t in all_tables},edges=fk_edges),
          open(os.path.join(HERE,'meta.json'),'w'))
print('OK tables:',len(all_tables),'edges:',len(fk_edges),'cols:',sum(len(v['cols']) for v in tables.values()))
