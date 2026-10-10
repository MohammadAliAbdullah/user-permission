<?php declare(strict_types=1); ?>
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Modules &amp; Links</h1>
    <p class="text-sm text-slate-500">Catalogue that drives the dynamic menu. Add links here, then grant roles access to them.</p>
  </div>
  <?php if (\Auth::can(LINK_MODULES_MGMT, 'create')): ?>
    <a href="<?= url('index.php?action=modules&mode=create') ?>"
       class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-brand-700">+ New Link</a>
  <?php endif; ?>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
  <!-- Modules -->
  <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-3">
      <h2 class="text-sm font-semibold text-slate-700">Modules (<?= count($modules) ?>)</h2>
    </div>
    <ul class="divide-y divide-slate-100 text-sm">
      <?php foreach ($modules as $m): ?>
        <li class="flex items-center justify-between px-5 py-3">
          <span class="font-medium text-slate-800"><?= e($m['MODULE_ICON'] ? $m['MODULE_ICON'] . ' ' : '') ?><?= e($m['MODULE_NAME']) ?></span>
          <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">#<?= (int)$m['MODULE_ID'] ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- Links -->
  <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200/60 lg:col-span-2">
    <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-3">
      <h2 class="text-sm font-semibold text-slate-700">Module Links (<?= count($links) ?>)</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="text-left text-xs uppercase tracking-wider text-slate-400">
          <tr>
            <th class="px-5 py-3">Link</th><th class="px-3 py-3">Module</th>
            <th class="px-3 py-3">URL</th><th class="px-3 py-3">Status</th><th class="px-3 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        <?php foreach ($links as $l): ?>
          <tr class="hover:bg-slate-50/70">
            <td class="px-5 py-3">
              <p class="font-medium text-slate-800"><?= e($l['LINK_NAME']) ?></p>
              <p class="text-xs text-slate-400">ID <?= (int)$l['LINK_ID'] ?><?= $l['PR_LINK_ID'] ? ' · parent #' . (int)$l['PR_LINK_ID'] : '' ?></p>
            </td>
            <td class="px-3 py-3 text-slate-600"><?= e($moduleNames[(int)$l['MODULE_ID']] ?? '—') ?></td>
            <td class="px-3 py-3 font-mono text-xs text-slate-500"><?= e($l['URL_URI'] ?: '#') ?></td>
            <td class="px-3 py-3">
              <?php if ((int)$l['ACTIVE_STATUS'] === 1): ?><span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Active</span>
              <?php else: ?><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500">Inactive</span><?php endif; ?>
            </td>
            <td class="px-3 py-3 text-right">
              <?php if (\Auth::can(LINK_MODULES_MGMT, 'update')): ?>
                <a href="<?= url('index.php?action=modules&mode=edit&id=' . (int)$l['LINK_ID']) ?>"
                   class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200">Edit</a>
              <?php endif; ?>
              <?php if (\Auth::can(LINK_MODULES_MGMT, 'delete')): ?>
                <form method="post" action="<?= url('index.php?action=modules&do=del') ?>" class="inline"
                      onsubmit="return confirm('Delete link <?= e(addslashes($l['LINK_NAME'])) ?>? Menu entries and permissions referencing it will stop showing.')">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$l['LINK_ID'] ?>">
                  <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Delete</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$links): ?><tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">No links yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
