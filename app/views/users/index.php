<?php declare(strict_types=1); ?>
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
  <div>
    <h1 class="text-2xl font-bold text-slate-900">Users</h1>
    <p class="text-sm text-slate-500">Create, edit, assign roles and control account status.</p>
  </div>
  <div class="flex items-center gap-2">
    <form method="get" action="<?= url('index.php') ?>" class="flex items-center gap-2">
      <input type="hidden" name="action" value="users">
      <input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search name / username…"
             class="w-48 rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
      <select name="role" class="rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none">
        <option value="">All roles</option>
        <?php foreach ($allRoles as $r): ?>
          <option value="<?= (int)$r['USERGRP_ID'] ?>" <?= (int)($_GET['role'] ?? 0) === (int)$r['USERGRP_ID'] ? 'selected' : '' ?>>
            <?= e($r['USERGRP_NAME']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="rounded-lg bg-slate-800 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
    </form>
    <?php if (\Auth::can(LINK_USERS, 'create')): ?>
      <a href="<?= url('index.php?action=users&mode=create') ?>"
         class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-brand-700">+ New User</a>
    <?php endif; ?>
  </div>
</div>

<div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200/60">
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
      <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
        <tr>
          <th class="px-4 py-3">User</th>
          <th class="px-4 py-3">Username</th>
          <th class="px-4 py-3">Role(s)</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Last login</th>
          <th class="px-4 py-3 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($users as $u): ?>
          <tr class="hover:bg-slate-50/70">
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-full bg-brand-50 font-bold text-brand-700"><?= e(mb_strtoupper(mb_substr($u['FULL_NAME'] ?: 'U', 0, 1))) ?></span>
                <div>
                  <p class="font-medium text-slate-800"><?= e($u['FULL_NAME']) ?></p>
                  <p class="text-xs text-slate-400"><?= e($u['EMAIL']) ?></p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 font-mono text-slate-600"><?= e($u['USERNAME']) ?></td>
            <td class="px-4 py-3">
              <?php foreach (($u['ROLES'] ?? '') !== '' ? explode(',', $u['ROLES']) : ['<none>'] as $rn): ?>
                <span class="mr-1 inline-block rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"><?= e(trim($rn)) ?></span>
              <?php endforeach; ?>
            </td>
            <td class="px-4 py-3">
              <?php if ((int)$u['ACTIVE_STATUS'] === 1): ?>
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Active</span>
              <?php else: ?>
                <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-600"><span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span>Disabled</span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-slate-500"><?= e($u['LAST_LOGIN_DT'] ?? '—') ?></td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <?php if (\Auth::can(LINK_USERS, 'update')): ?>
                  <a href="<?= url('index.php?action=users&mode=edit&id=' . (int)$u['USER_ID']) ?>"
                     class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">Edit</a>
                  <form method="post" action="<?= url('index.php?action=users&do=toggle') ?>" onsubmit="return confirm('Toggle active status of this user?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$u['USER_ID'] ?>">
                    <button class="rounded-lg border px-3 py-1.5 text-xs font-medium <?= (int)$u['ACTIVE_STATUS'] === 1 ? 'border-amber-200 text-amber-700 hover:bg-amber-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' ?>">
                      <?= (int)$u['ACTIVE_STATUS'] === 1 ? 'Disable' : 'Enable' ?>
                    </button>
                  </form>
                <?php endif; ?>
                <?php if (\Auth::can(LINK_USERS, 'delete')): ?>
                  <form method="post" action="<?= url('index.php?action=users&do=delete') ?>" onsubmit="return confirm('Delete user <?= e($u['USERNAME']) ?> permanently?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$u['USER_ID'] ?>">
                    <button class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50">Delete</button>
                  </form>
                <?php endif; ?>
                <?php if (!\Auth::can(LINK_USERS, 'update') && !\Auth::can(LINK_USERS, 'delete')): ?>
                  <span class="text-xs italic text-slate-400">Read only</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?>
          <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">No users match the filter.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-4 py-3 text-xs text-slate-500">
    <span><?= count($users) ?> of <?= (int)$totalUsers ?> users shown</span>
    <div class="flex gap-1">
      <?php for ($p = 1; $p <= max(1, (int)ceil($totalUsers / $perPage)); $p++): ?>
        <a href="<?= url('index.php?action=users&page=' . $p . '&q=' . urlencode($_GET['q'] ?? '') . '&role=' . (int)($_GET['role'] ?? 0)) ?>"
           class="rounded px-2 py-1 <?= $p === (int)($page ?? 1) ? 'bg-brand-600 text-white' : 'bg-white ring-1 ring-slate-200 hover:bg-slate-100' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
  </div>
</div>
