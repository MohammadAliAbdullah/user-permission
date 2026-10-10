<?php declare(strict_types=1); ?>
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
    <p class="text-sm text-slate-500">Welcome back, <?= e(\Auth::user()['FULL_NAME']) ?> — role(s): <?= e(implode(', ', $roleNames)) ?></p>
  </div>
  <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 ring-1 ring-brand-100">bnsis_permission · live data</span>
</div>

<!-- Stat cards -->
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
  <?php foreach ($stats as $s): ?>
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60 transition hover:shadow-md">
      <div class="flex items-center justify-between">
        <p class="text-sm font-medium text-slate-500"><?= e($s['label']) ?></p>
        <span class="grid h-9 w-9 place-items-center rounded-lg text-lg <?= $s['bg'] ?>"><?= $s['icon'] ?></span>
      </div>
      <p class="mt-2 text-3xl font-bold text-slate-900"><?= number_format((float)$s['value']) ?></p>
      <p class="mt-1 text-xs text-slate-400"><?= e($s['hint']) ?></p>
    </div>
  <?php endforeach; ?>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
  <!-- Your access matrix -->
  <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60 xl:col-span-2">
    <h2 class="mb-3 text-base font-semibold text-slate-800">Your module access</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead><tr class="border-b text-left text-xs uppercase tracking-wider text-slate-400">
          <th class="py-2 pr-4">Module</th><th class="py-2 px-3 text-center">Create</th>
          <th class="py-2 px-3 text-center">Read</th><th class="py-2 px-3 text-center">Update</th>
          <th class="py-2 px-3 text-center">Delete</th></tr></thead>
        <tbody>
        <?php foreach ($myAccess as $row): ?>
          <tr class="border-b border-slate-100 last:border-0">
            <td class="py-2.5 pr-4 font-medium text-slate-700"><?= e($row['MODULE_NAME']) ?></td>
            <?php foreach (['create','read','update','delete'] as $a): ?>
              <td class="px-3 py-2.5 text-center">
                <?php if ($row[$a] > 0): ?><span class="inline-block rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700"><?= (int)$row[$a] ?></span>
                <?php else: ?><span class="text-slate-300">—</span><?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
        <?php if (!$myAccess): ?><tr><td colspan="5" class="py-6 text-center text-slate-400">No permissions assigned yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent activity -->
  <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60">
    <h2 class="mb-3 text-base font-semibold text-slate-800">Recent activity</h2>
    <ul class="space-y-3">
      <?php foreach ($logs as $lg): ?>
        <li class="flex gap-3 text-sm">
          <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-brand-500"></span>
          <div>
            <p class="text-slate-700"><b><?= e($lg['USERNAME']) ?></b> · <?= e($lg['ACTION']) ?>
              <span class="text-slate-400">(<?= e($lg['MODULE_LINK']) ?>)</span></p>
            <p class="truncate text-xs text-slate-400"><?= e($lg['DETAILS']) ?> — <?= e($lg['CRE_DT']) ?></p>
          </div>
        </li>
      <?php endforeach; ?>
      <?php if (!$logs): ?><li class="text-sm text-slate-400">No activity recorded.</li><?php endif; ?>
    </ul>
  </div>
</div>

<!-- Quick actions (permission-gated) -->
<div class="mt-6 flex flex-wrap gap-3">
  <?php if (\Auth::can(LINK_USERS, 'create')): ?>
    <a href="<?= url('index.php?action=users&mode=create') ?>" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-brand-700">+ New User</a>
  <?php endif; ?>
  <?php if (\Auth::can(LINK_ROLES, 'create')): ?>
    <a href="<?= url('index.php?action=roles&mode=create') ?>" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-brand-700 shadow ring-1 ring-brand-200 hover:bg-brand-50">+ New Role</a>
  <?php endif; ?>
  <?php if (\Auth::can(LINK_PERMS, 'update')): ?>
    <a href="<?= url('index.php?action=permissions') ?>" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow ring-1 ring-slate-200 hover:bg-slate-50">Manage Permissions</a>
  <?php endif; ?>
</div>
