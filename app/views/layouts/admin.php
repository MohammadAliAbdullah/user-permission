<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'BNSIS Admin') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = { theme: { extend: { colors: {
    brand: {50:'#eef4ff',100:'#dbe6fe',500:'#3b6ff6',600:'#2456e0',700:'#1c46b8',900:'#152a63'}
  }}}}
</script>
<link rel="stylesheet" href="<?= url('assets/app.css') ?>">
</head>
<body class="h-full bg-slate-100 text-slate-800 antialiased">
<div x-data="{ sidebar:false, openGrp: {} }"
     x-init="openGrp = JSON.parse(localStorage.getItem('openGrp')||'{}')"
     class="min-h-full">

  <?php if (!isset($standalone)): ?>
  <!-- Top bar -->
  <header class="fixed inset-x-0 top-0 z-40 h-16 bg-brand-900 text-white shadow">
    <div class="flex h-full items-center justify-between px-4">
      <div class="flex items-center gap-3">
        <button @click="sidebar=!sidebar" class="rounded p-2 hover:bg-white/10 lg:hidden" aria-label="Toggle menu">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="flex items-center gap-2 font-semibold tracking-wide">
          <span class="grid h-9 w-9 place-items-center rounded-lg bg-white/10 text-lg">⚓</span>
          <span class="hidden sm:block">BNSIS · Security &amp; Access</span>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <span class="hidden text-sm text-white/80 md:block"><?= e(\Auth::user()['FULL_NAME'] ?? '') ?></span>
        <span class="hidden rounded-full bg-white/10 px-3 py-1 text-xs md:block"><?= e(implode(', ', $roleNames ?? [])) ?: 'No role' ?></span>
        <form method="post" action="<?= url('index.php?action=logout') ?>">
          <?= csrf_field() ?>
          <button class="rounded-lg bg-white/10 px-3 py-1.5 text-sm hover:bg-white/20">Sign out</button>
        </form>
      </div>
    </div>
  </header>

  <!-- Sidebar -->
  <aside class="fixed top-16 bottom-0 left-0 z-30 w-72 transform bg-white shadow-xl transition-transform duration-200
                lg:translate-x-0"
         :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
    <nav class="h-full overflow-y-auto px-3 py-4 text-sm" id="app-nav">
      <a href="<?= url('index.php?action=dashboard') ?>"
         class="mb-1 flex items-center gap-2 rounded-lg px-3 py-2 font-medium <?= ($active ?? '')==='dashboard' ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50' ?>">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10"/></svg>
        Dashboard
      </a>

      <?php foreach ($menu as $grp): ?>
        <?php $key = 'm' . $grp['module']['MODULE_ID']; ?>
        <div class="mt-1">
          <button @click="openGrp['<?= $key ?>'] = !openGrp['<?= $key ?>']; localStorage.setItem('openGrp', JSON.stringify(openGrp))"
                  class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left font-semibold text-slate-700 hover:bg-slate-50">
            <span class="flex items-center gap-2">
              <span class="text-brand-600">◆</span> <?= e($grp['module']['MODULE_NAME']) ?>
            </span>
            <svg class="h-4 w-4 transition" :class="openGrp['<?= $key ?>'] && 'rotate-90'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 6l6 6-6 6"/></svg>
          </button>
          <ul x-show="openGrp['<?= $key ?>']" x-collapse class="ml-3 border-l border-slate-200 pl-2">
            <?php $renderItems = function (array $items) use (&$renderItems, $active) { ?>
              <?php foreach ($items as $node): $l = $node['link']; ?>
                <li>
                  <?php if ($node['children']): ?>
                    <span class="block px-2 pb-1 pt-2 text-[11px] font-bold uppercase tracking-wider text-slate-400"><?= e($l['LINK_NAME']) ?></span>
                    <ul class="ml-2 border-l border-slate-100 pl-2"><?php $renderItems($node['children']); ?></ul>
                  <?php else: ?>
                    <a href="<?= url('page.php?link=' . (int)$l['LINK_ID']) ?>"
                       class="block truncate rounded px-2 py-1.5 <?= ($currentLink ?? 0) === (int)$l['LINK_ID'] ? 'bg-brand-50 text-brand-700 font-medium' : 'text-slate-600 hover:bg-slate-50' ?>"
                       title="<?= e($l['LINK_NAME']) ?>"><?= e($l['LINK_NAME']) ?></a>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            <?php }; $renderItems($grp['items']); ?>
          </ul>
        </div>
      <?php endforeach; ?>

      <div class="mt-4 border-t border-slate-200 pt-3">
        <p class="px-3 text-[11px] uppercase tracking-wider text-slate-400">Administration</p>
        <a href="<?= url('index.php?action=users') ?>" class="mt-1 block rounded px-3 py-2 <?= ($active ?? '')==='users' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-slate-600 hover:bg-slate-50' ?>">👤 Users</a>
        <a href="<?= url('index.php?action=roles') ?>" class="block rounded px-3 py-2 <?= ($active ?? '')==='roles' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-slate-600 hover:bg-slate-50' ?>">🛡️ Roles</a>
        <a href="<?= url('index.php?action=permissions') ?>" class="block rounded px-3 py-2 <?= ($active ?? '')==='permissions' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-slate-600 hover:bg-slate-50' ?>">🔑 Permissions</a>
        <a href="<?= url('index.php?action=modules') ?>" class="block rounded px-3 py-2 <?= ($active ?? '')==='modules' ? 'bg-brand-50 text-brand-700 font-medium' : 'text-slate-600 hover:bg-slate-50' ?>">📦 Modules &amp; Links</a>
      </div>
    </nav>
  </aside>
  <div class="fixed inset-0 z-20 bg-black/40" x-show="sidebar" @click="sidebar=false" x-cloak></div>
  <?php endif; ?>

  <main class="pt-16 <?= isset($standalone) ? '' : 'lg:pl-72' ?>">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
      <?php foreach (['success'=>'emerald','error'=>'rose','info'=>'sky'] as $type => $color):
            foreach (['ok'=>$type] as $_): endforeach;
            $msgs = $_SESSION['_flash'][$type] ?? [];
            foreach ($msgs as $m): unset($_SESSION['_flash'][$type]); ?>
        <div class="mb-4 rounded-lg border border-<?= $color ?>-200 bg-<?= $color ?>-50 px-4 py-3 text-sm text-<?= $color ?>-800 shadow-sm"><?= e($m) ?></div>
      <?php endforeach; ?>

      <?= $__content /* view buffer */ ?>
    </div>
  </main>
</div>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>document.addEventListener('alpine:init',()=>Alpine.data('collapse',()=>({})))</script>
</body>
</html>
