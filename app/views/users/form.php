<?php declare(strict_types=1); ?>
<div class="mb-6">
  <a href="<?= url('index.php?action=users') ?>" class="text-sm text-brand-600 hover:underline">&larr; Back to users</a>
  <h1 class="mt-1 text-2xl font-bold text-slate-900"><?= $mode === 'edit' ? 'Edit User #' . (int)$user['USER_ID'] : 'Create User' ?></h1>
</div>

<?php if (!empty($errors)): ?>
  <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
    <ul class="list-inside list-disc"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form method="post" action="<?= url('index.php?action=users&do=' . ($mode === 'edit' ? 'update' : 'store')) ?>"
      class="grid max-w-3xl grid-cols-1 gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60 sm:grid-cols-2">
  <?= csrf_field() ?>
  <?php if ($mode === 'edit'): ?><input type="hidden" name="id" value="<?= (int)$user['USER_ID'] ?>"><?php endif; ?>

  <div class="sm:col-span-2">
    <label class="mb-1 block text-sm font-medium text-slate-600">Full name *</label>
    <input name="FULL_NAME" required maxlength="100" value="<?= e(old('FULL_NAME', $user['FULL_NAME'] ?? '')) ?>"
           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
  </div>

  <div>
    <label class="mb-1 block text-sm font-medium text-slate-600">Username *</label>
    <input name="USERNAME" required maxlength="16" value="<?= e(old('USERNAME', $user['USERNAME'] ?? '')) ?>"
           class="w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
  </div>

  <div>
    <label class="mb-1 block text-sm font-medium text-slate-600">Email</label>
    <input type="email" name="EMAIL" maxlength="40" value="<?= e(old('EMAIL', $user['EMAIL'] ?? '')) ?>"
           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
  </div>

  <div>
    <label class="mb-1 block text-sm font-medium text-slate-600">Password <?= $mode === 'edit' ? '(leave blank to keep)' : '*' ?></label>
    <input type="password" name="password" <?= $mode === 'edit' ? '' : 'required' ?> minlength="6" autocomplete="new-password"
           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
  </div>

  <div>
    <label class="mb-1 block text-sm font-medium text-slate-600">Primary role *</label>
    <select name="USERGRP_ID" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none">
      <?php foreach ($allRoles as $r): ?>
        <option value="<?= (int)$r['USERGRP_ID'] ?>" <?= (int)old('USERGRP_ID', $user['USERGRP_ID'] ?? 0) === (int)$r['USERGRP_ID'] ? 'selected' : '' ?>>
          <?= e($r['USERGRP_NAME']) ?><?= (int)$r['IS_SYSTEM'] === 1 ? ' ★' : '' ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label class="mb-1 block text-sm font-medium text-slate-600">Access level</label>
    <select name="USERLVL_ID" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none">
      <?php foreach ($levels as $lv): ?>
        <option value="<?= (int)$lv['UG_LEVEL_ID'] ?>" <?= (int)old('USERLVL_ID', $user['USERLVL_ID'] ?? 0) === (int)$lv['UG_LEVEL_ID'] ? 'selected' : '' ?>>
          <?= e($lv['UGLEVE_NAME']) ?> (group <?= (int)$lv['USERGRP_ID'] ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label class="mb-1 block text-sm font-medium text-slate-600">Account status</label>
    <select name="ACTIVE_STATUS" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none">
      <option value="1" <?= (int)old('ACTIVE_STATUS', $user['ACTIVE_STATUS'] ?? 1) === 1 ? 'selected' : '' ?>>Active</option>
      <option value="0" <?= (int)old('ACTIVE_STATUS', $user['ACTIVE_STATUS'] ?? 1) === 0 ? 'selected' : '' ?>>Disabled</option>
    </select>
  </div>

  <fieldset class="sm:col-span-2 rounded-lg border border-slate-200 p-4">
    <legend class="px-2 text-sm font-semibold text-slate-700">Additional roles (multi-role user)</legend>
    <p class="mb-3 text-xs text-slate-400">Permissions are merged from every checked role. The primary role above is always active.</p>
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
      <?php foreach ($allRoles as $r): $rid = (int)$r['USERGRP_ID']; ?>
        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50">
          <input type="checkbox" name="roles[]" value="<?= $rid ?>" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
            <?= in_array($rid, $assignedRoles ?? [], true) || $rid === (int)($user['USERGRP_ID'] ?? -1) ? 'checked' : '' ?>>
          <?= e($r['USERGRP_NAME']) ?>
        </label>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <div class="flex items-center gap-3 sm:col-span-2">
    <button class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-brand-700">
      <?= $mode === 'edit' ? 'Save changes' : 'Create user' ?></button>
    <a href="<?= url('index.php?action=users') ?>" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</a>
  </div>
</form>
