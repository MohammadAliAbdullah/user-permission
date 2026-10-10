<?php declare(strict_types=1); ?>
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Activity Log</h1>
    <p class="text-sm text-slate-500">Audit trail of sign-ins and every CRUD action performed in the admin panel.</p>
  </div>
  <?php if (\Auth::can(LINK_ACTIVITY, 'delete')): ?>
    <form method="post" action="<?= url('index.php?action=activity&do=purge') ?>"
          onsubmit="return confirm('Purge activity logs older than 30 days?')">
      <?= csrf_field() ?>
      <button class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-rose-700 shadow ring-1 ring-rose-200 hover:bg-rose-50">Purge &gt;30 days</button>
    </form>
  <?php endif; ?>
</div>

<div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200/60">
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
      <thead class="bg-slate-50/70 text-left text-xs uppercase tracking-wider text-slate-400">
        <tr>
          <th class="px-5 py-3">When</th><th class="px-3 py-3">User</th><th class="px-3 py-3">Action</th>
          <th class="px-3 py-3">Area</th><th class="px-3 py-3">Details</th><th class="px-3 py-3">IP</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php foreach ($logs as $lg): ?>
        <tr class="hover:bg-slate-50/70">
          <td class="whitespace-nowrap px-5 py-3 text-slate-500"><?= e($lg['CRE_DT']) ?></td>
          <td class="px-3 py-3 font-medium text-slate-800"><?= e($lg['USERNAME']) ?></td>
          <td class="px-3 py-3">
            <span class="rounded-full px-2 py-0.5 text-xs font-semibold
              <?= match(strtolower((string)$lg['ACTION'])) {
                  'delete','purge' => 'bg-rose-50 text-rose-700',
                  'create' => 'bg-emerald-50 text-emerald-700',
                  'update','toggle','save' => 'bg-amber-50 text-amber-700',
                  'login','logout' => 'bg-sky-50 text-sky-700',
                  default => 'bg-slate-100 text-slate-600' } ?>">
              <?= e(strtoupper((string)$lg['ACTION'])) ?></span>
          </td>
          <td class="px-3 py-3 text-slate-600"><?= e($lg['MODULE_LINK']) ?></td>
          <td class="max-w-md truncate px-3 py-3 text-slate-500" title="<?= e($lg['DETAILS']) ?>"><?= e($lg['DETAILS']) ?></td>
          <td class="px-3 py-3 font-mono text-xs text-slate-400"><?= e($lg['IP_ADDRESS']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$logs): ?><tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">No activity yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/70 px-5 py-3 text-sm text-slate-500">
    <span><?= count($logs) ?> of <?= (int)$total ?> entries shown</span>
    <div class="flex gap-1">
      <?php for ($p = 1; $p <= max(1, (int)ceil($total / $perPage)); $p++): ?>
        <a href="<?= url('index.php?action=activity&page=' . $p) ?>"
           class="rounded px-2 py-1 <?= $p === (int)($page ?? 1) ? 'bg-brand-600 text-white' : 'bg-white ring-1 ring-slate-200 hover:bg-slate-100' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
  </div>
</div>
