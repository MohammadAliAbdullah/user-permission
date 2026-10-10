<?php declare(strict_types=1); ?>
<div class="mb-6">
  <p class="text-xs font-semibold uppercase tracking-wider text-slate-400"><?= e($link['MODULE_NAME']) ?></p>
  <h1 class="mt-1 text-2xl font-bold text-slate-900"><?= e($link['LINK_NAME']) ?></h1>
</div>

<div class="rounded-xl bg-white p-8 shadow-sm ring-1 ring-slate-200/60">
  <div class="mx-auto max-w-lg text-center">
    <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-brand-50 text-3xl">🧩</div>
    <h2 class="mt-4 text-lg font-semibold text-slate-800">This module link is live &amp; permission-checked</h2>
    <p class="mt-2 text-sm text-slate-500">
      You reached <b><?= e($link['LINK_NAME']) ?></b> (link #<?= (int)$link['LINK_ID'] ?>) through the dynamic menu.
      Business screens attached to this link can be developed under <code>app/views/</code> and routed from
      <code>public/page.php</code>. Your role currently has:
    </p>
    <div class="mt-4 flex justify-center gap-2">
      <?php foreach (['create', 'read', 'update', 'delete'] as $a): $ok = \Auth::can((int)$link['LINK_ID'], $a); ?>
        <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $ok ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-400 line-through' ?>">
          <?= ucfirst($a) ?></span>
      <?php endforeach; ?>
    </div>
    <a href="<?= url('index.php?action=dashboard') ?>" class="mt-6 inline-block rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-brand-700">Back to Dashboard</a>
  </div>
</div>
