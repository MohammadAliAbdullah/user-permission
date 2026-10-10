function toggleTbl(el){el.classList.toggle('open');}
function setAll(open){
  document.querySelectorAll('.tbl').forEach(function(t){
    if(!t.classList.contains('hidden')) t.classList.toggle('open',open);
  });
  document.getElementById('btnExpand').textContent=open?'Collapse all':'Expand all';
}
function toggleAll(){
  var anyOpen=[].slice.call(document.querySelectorAll('.tbl:not(.hidden)')).some(function(t){return t.classList.contains('open');});
  setAll(!anyOpen);
}
var mode='all';
function filterMode(m){
  mode=m;
  apply();
  document.querySelectorAll('.seg button').forEach(function(b){b.classList.toggle('on',b.dataset.m===m);});
}
function apply(){
  var q=document.getElementById('q').value.trim().toLowerCase();
  var shown=0;
  document.querySelectorAll('.tbl').forEach(function(t){
    var okG=(mode==='all')||(t.dataset.group===mode);
    var okQ=!q||t.dataset.search.indexOf(q)>-1;
    var vis=okG&&okQ;
    t.classList.toggle('hidden',!vis);
    if(vis){shown++;if(q)t.classList.add('open');}
  });
  document.getElementById('count').textContent=shown+' of 19 tables';
  document.querySelectorAll('.nav-chip').forEach(function(a){
    var el=document.getElementById(a.getAttribute('href').slice(1));
    a.style.display=(el&&!el.classList.contains('hidden'))?'':'none';
  });
}
window.addEventListener('scroll',function(){document.getElementById('bt').classList.toggle('show',scrollY>600);});
document.addEventListener('DOMContentLoaded',function(){
  document.getElementById('q').addEventListener('input',apply);
  filterMode('all');
  if(location.hash){
    var el=document.querySelector(location.hash+'.tbl');
    if(el){el.classList.add('open');setTimeout(function(){el.scrollIntoView({block:'start'});},60);}
  }
});
