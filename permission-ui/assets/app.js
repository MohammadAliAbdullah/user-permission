/* permission-ui shared helpers: sidebar chrome + DB access */
window.PU = {
  db(){ return window.DB || {}; },

  // generic value cell renderer -> HTML string
  cell(v){
    if (v === null || v === undefined || v === '') return '<span class="nullv">NULL</span>';
    const s = String(v);
    const esc = s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    if (/^-?\d+(\.\d+)?$/.test(s)) return '<span class="text-amber-700 font-mono text-xs">'+esc+'</span>';
    if (s.length > 42) return '<span title="'+esc+'">'+esc.slice(0,42)+'…</span>';
    return esc;
  },

  pill(v, onTxt='Active', offTxt='Inactive'){
    if (v === '1' || v === 1) return '<span class="pill pill-on">'+onTxt+'</span>';
    if (v === '0' || v === 0) return '<span class="pill pill-off">'+offTxt+'</span>';
    return '<span class="pill pill-neutral">'+PU.cell(v)+'</span>';
  },

  flag(v){ return (v==='1'||v===1) ? '<span class="cb-y">✓</span>' : '<span class="cb-n">—</span>'; },

  // render a table into element id from array of objects
  table(elId, rows, cols, opts={}){
    const el = document.getElementById(elId); if(!el) return;
    const per = opts.perPage || 25;
    const head = '<tr>'+cols.map(c=>'<th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">'+c.label+'</th>').join('')+'</tr>';
    let page = 1;
    function draw(list){
      const total = list.length, pages = Math.max(1, Math.ceil(total/per));
      page = Math.min(page, pages);
      const slice = list.slice((page-1)*per, page*per);
      const body = slice.map((r,i)=>'<tr class="hover:bg-brand-50/50 '+(opts.rowClass?opts.rowClass(r):'')+'">'+
        cols.map(c=>'<td class="px-3 py-2 align-top whitespace-nowrap">'+(c.render?c.render(r[c.key],r):(PU.cell(r[c.key])))+'</td>').join('')+'</tr>').join('');
      el.innerHTML =
        '<div class="mb-2 flex items-center justify-between text-xs text-slate-500"><span>'+total.toLocaleString()+' records'+(opts.suffix?(' · '+opts.suffix):'')+'</span>'+
        (pages>1?'<span>Page '+page+' / '+pages+'</span>':'')+'</div>'+
        '<div class="max-h-[560px] overflow-auto rounded-lg ring-1 ring-slate-200"><table class="min-w-full divide-y divide-slate-200 text-sm tbl-sticky">'+
        '<thead class="border-b border-slate-200">'+head+'</thead><tbody class="divide-y divide-slate-100">'+(body||'<tr><td colspan="'+cols.length+'" class="px-3 py-6 text-center text-slate-400">No data found</td></tr>')+'</tbody></table></div>'+
        (pages>1?'<div class="mt-3 flex justify-end gap-2"><button id="'+elId+'-prev" class="rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50" '+(page<=1?'disabled':'')+'>← Prev</button>'+
        '<button id="'+elId+'-next" class="rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50" '+(page>=pages?'disabled':'')+'>Next →</button></div>':'');
      const p=document.getElementById(elId+'-prev'), n=document.getElementById(elId+'-next');
      if(p) p.onclick=()=>{page--;draw(list)}; if(n) n.onclick=()=>{page++;draw(list)};
    }
    el._draw = draw; draw(rows);
    return draw;
  },

  csv(filename, rows, cols){
    const q = s => '"'+String(s==null?'':s).replace(/"/g,'""')+'"';
    const lines=[cols.map(c=>q(c.label)).join(',')];
    rows.forEach(r=>lines.push(cols.map(c=>q(r[c.key])).join(',')));
    const a=document.createElement('a');
    a.href=URL.createObjectURL(new Blob([lines.join('\n')],{type:'text/csv'}));
    a.download=filename+'.csv'; a.click(); URL.revokeObjectURL(a.href);
  }
};

// Sidebar builder — keeps every page's chrome identical
PU.sidebar = function(active){
  const links = [
    ['index.html','📊','Admin Dashboard'],
    ['modules.html','🧩','Modules'],
    ['module-links.html','🔗','Module Links'],
    ['org-modules.html','🏛️','Organization Modules'],
    ['user-groups.html','👥','All User Group'],
    ['assign-links.html','🎯','Assign Link To Group'],
    ['user-setup.html','⚙️','User Setup'],
    ['users.html','👤','Users'],
    ['roles.html','🛡️','Roles'],
    ['role-permissions.html','🔑','Role Permissions'],
    ['schema.html','🗄️','DB Schema & Data'],
  ];
  const grp = (title, arr) =>
    '<div class="mt-4 border-t border-slate-200 pt-3"><p class="px-3 pb-1 text-[11px] uppercase tracking-wider text-slate-400">'+title+'</p>'+
    arr.map(l=>navA(l)).join('')+'</div>';
  function navA([href,ico,label]){
    const on = href===active;
    return '<a href="'+href+'" class="flex items-center gap-2.5 rounded-lg px-3 py-2 '+(on?'bg-brand-50 font-medium text-brand-700':'text-slate-600 hover:bg-slate-50')+'">'+
      '<span class="w-5 text-center">'+ico+'</span><span>'+label+'</span>'+
      (on?'<span class="ml-auto h-1.5 w-1.5 rounded-full bg-brand-500"></span>':'')+'</a>';
  }
  return '<a href="index.html" class="mb-1 flex items-center gap-2.5 rounded-lg px-3 py-2 '+(active==='index.html'?'bg-brand-50 font-medium text-brand-700':'font-medium text-slate-600 hover:bg-slate-50')+'">'+
    '<span class="w-5 text-center">📊</span>Dashboard</a>'+
    grp('Access Control', links.slice(1,7))+
    grp('Administration', links.slice(7));
};

PU.mountChrome = function(active, title){
  document.title = title + ' — permission-ui';
  const sb = document.getElementById('sidebar-nav');
  if(sb) sb.innerHTML = PU.sidebar(active);
};
