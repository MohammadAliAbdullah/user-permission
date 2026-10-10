<?php declare(strict_types=1); ?>
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Roles</h1>
    <p class="text-sm text-slate-500">Group users and grant them CRUD access to application modules.</p>
  </div>
  <?php if (\Auth::can(LINK_ROLES, 'create')): ?>
    <a href="<?= url('index.php?action=roles&mode=create') ?>"
       class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-brand-700">+ New Role</a>
  <?php endif; ?>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
  <?php foreach ($roles as $r): ?>
    <div class="flex flex-col rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60 transition hover:shadow-md">
      <div class="flex items-start justify-between">
        <div>
          <h3 class="font-semibold text-slate-800"><?= e($r['USERGRP_NAME']) ?>
            <?php if ((int)$r['IS_SYSTEM'] === 1): ?><span class="ml-1 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold uppercase text-amber-700 ring-1 ring-amber-200">System</span><?php endif; ?>
          </h3>
          <p class="mt-0.5 text-xs text-slate-400"><?= e($r['DESCRIPTION'] ?: 'No description') ?></p>
        </div>
        <?php if ((int)$r['ACTIVE_STATUS'] === 1): ?>
          <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Active</span>
        <?php else: ?>
          <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500">Inactive</span>
        <?php endif; ?>
      </div>

      <dl class="mt-4 grid grid-cols-3 gap-2 text-center text-sm">
        <div class="rounded-lg bg-slate-50 py-2"><dt class="text-xs text-slate-400">Users</dt><dd class="font-bold text-slate-700"><?= (int)$r['USER_CNT'] ?></dd></div>
        <div class="rounded-lg bg-slate-50 py-2"><dt class="text-xs text-slate-400">Perms</dt><dd class="font-bold text-slate-700"><?= (int)$r['PERM_CNT'] ?></dd></div>
        <div class="rounded-lg bg-slate-50 py-2"><dt class="text-xs text-slate-400">Modules</dt><dd class="font-bold text-slate-700"><?= (int)$r['MODULE_CNT'] ?></dd></div>
      </dl>

      <div class="mt-4 flex gap-2 border-t border-slate-100 pt-4">
        <?php if (\Auth::can(LINK_PERMS, 'read')): ?>
          <a href="<?= url('index.php?action=permissions&role=' . (int)$r['USERGRP_ID']) ?>"
             class="flex-1 rounded-lg bg-brand-50 px-3 py-2 text-center text-xs font-semibold text-brand-700 hover:bg-brand-100">Permissions</a>
        <?php endif; ?>
        <?php if (\Auth::can(LINK_ROLES, 'update')): ?>
          <a href="<?= url('index.php?action=roles&mode=edit&id=' . (int)$r['USERGRP_ID']) ?>"
             class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-center text-xs font-medium text-slate-600 hover:bg-slate-50">Edit</a>
        <?php endif; ?>
        <?php if (\Auth::can(LINK_ROLES, 'delete') && (int)$r['IS_SYSTEM'] === 0): ?>
          <form method="post" action="<?= url('index.php?action=roles&do=delete') ?>" onsubmit="return confirm('Delete role &quot;<?= e($r['USERGRP_NAME']) ?>&quot;? Its permission grants will be removed.')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$r['USERGRP_ID'] ?>">
            <button class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50">Delete</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
