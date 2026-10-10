<?php declare(strict_types=1); ?>
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Role Permissions</h1>
    <p class="text-sm text-slate-500">Assign Create / Read / Update / Delete rights per module link. Changes apply instantly and drive menu visibility.</p>
  </div>
</div>

<div class="mb-4 flex flex-wrap gap-2">
  <?php foreach ($roles as $r): $rid = (int)$r['USERGRP_ID']; ?>
    <a href="<?= url('index.php?action=permissions&role=' . $rid) ?>"
       class="rounded-full px-4 py-1.5 text-sm font-medium ring-1 transition
              <?= $rid === $selectedRoleId ? 'bg-brand-600 text-white ring-brand-600 shadow' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' ?>">
      <?= e($r['USERGRP_NAME']) ?>
    </a>
  <?php endforeach; ?>
</div>

<form method="post" action="<?= url('index.php?action=permissions&do=save') ?>" id="permForm">
  <?= csrf_field() ?>
  <input type="hidden" name="role" value="<?= $selectedRoleId ?>">

  <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-5 py-3">
      <h2 class="text-sm font-semibold text-slate-700">
        Matrix for <span class="text-brand-700"><?= e($selectedRole['USERGRP_NAME']) ?></span>
        <?php if ((int)$selectedRoleId === 99): ?><span class="ml-2 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold uppercase text-amber-700 ring-1 ring-amber-200">Bypasses all checks</span><?php endif; ?>
      </h2>
      <div class="flex items-center gap-3 text-xs text-slate-500">
        <label class="flex cursor-pointer items-center gap-1"><input type="checkbox" onclick="toggleAll(this,'check')" class="rounded border-slate-300 text-brand-600"> Check all</label>
        <label class="flex cursor-pointer items-center gap-1"><input type="checkbox" onclick="toggleAll(this,'uncheck')" class="rounded border-slate-300 text-brand-600"> Clear all</label>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-white text-left text-xs uppercase tracking-wider text-slate-400">
          <tr>
            <th class="px-5 py-3">Module / Link</th>
            <th class="px-3 py-3 text-center w-24">Create</th>
            <th class="px-3 py-3 text-center w-24">Read</th>
            <th class="px-3 py-3 text-center w-24">Update</th>
            <th class="px-3 py-3 text-center w-24">Delete</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        <?php foreach ($matrix as $modName => $rows): ?>
          <tr class="bg-slate-50/60"><td colspan="5" class="px-5 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-500"><?= e($modName) ?></td></tr>
          <?php foreach ($rows as $row): ?>
            <tr class="hover:bg-brand-50/30">
              <td class="px-5 py-3">
                <p class="font-medium text-slate-800"><?= e($row['LINK_NAME']) ?></p>
                <p class="text-xs text-slate-400">link #<?= (int)$row['LINK_ID'] ?> · <?= e($row['URL_URI'] ?: '#') ?></p>
              </td>
              <?php foreach (['create', 'read', 'update', 'delete'] as $a): ?>
                <td class="px-3 py-3 text-center">
                  <input type="checkbox" name="perms[<?= (int)$row['LINK_ID'] ?>][]" value="<?= $a ?>"
                         class="perm-cb h-5 w-5 cursor-pointer rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                         <?= !empty($row[$a]) ? 'checked' : '' ?>>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if (!$matrix): ?>
          <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">No module links found — run migration 002.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/70 px-5 py-3">
      <p class="text-xs text-slate-400">Tip: unchecking <em>Read</em> hides the menu item entirely.</p>
      <?php if (\Auth::can(LINK_PERMS, 'update')): ?>
        <button class="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow hover:bg-brand-700">Save permissions</button>
      <?php else: ?>
        <span class="rounded-lg bg-slate-100 px-4 py-2 text-xs text-slate-500">Read-only — you lack the Update permission here</span>
      <?php endif; ?>
    </div>
  </div>
</form>

<script>
function toggleAll(master, mode) {
  document.querySelectorAll('.perm-cb').forEach(cb => { cb.checked = (mode === 'check'); master.checked = false; });
}
</script>
