<?php declare(strict_types=1); ?>
<div class="mb-6">
  <a href="<?= url('index.php?action=roles') ?>" class="text-sm text-brand-600 hover:underline">&larr; Back to roles</a>
  <h1 class="mt-1 text-2xl font-bold text-slate-900"><?= $mode === 'edit' ? 'Edit Role' : 'Create Role' ?></h1>
</div>

<?php if (!empty($errors)): ?>
  <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
    <ul class="list-inside list-disc"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form method="post" action="<?= url('index.php?action=roles&do=' . ($mode === 'edit' ? 'update' : 'store')) ?>"
      class="max-w-xl space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
  <?= csrf_field() ?>
  <?php if ($mode === 'edit'): ?><input type="hidden" name="id" value="<?= (int)$role['USERGRP_ID'] ?>"><?php endif; ?>

  <div>
    <label class="mb-1 block text-sm font-medium text-slate-600">Role name *</label>
    <input name="USERGRP_NAME" required maxlength="50" value="<?= e(old('USERGRP_NAME', $role['USERGRP_NAME'] ?? '')) ?>"
           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
  </div>

  <div>
    <label class="mb-1 block text-sm font-medium text-slate-600">Description</label>
    <textarea name="DESCRIPTION" rows="2" maxlength="255"
      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100"><?= e(old('DESCRIPTION', $role['DESCRIPTION'] ?? '')) ?></textarea>
  </div>

  <div>
    <label class="mb-1 block text-sm font-medium text-slate-600">Status</label>
    <select name="ACTIVE_STATUS" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none">
      <option value="1" <?= (int)old('ACTIVE_STATUS', $role['ACTIVE_STATUS'] ?? 1) === 1 ? 'selected' : '' ?>>Active</option>
      <option value="0" <?= (int)old('ACTIVE_STATUS', $role['ACTIVE_STATUS'] ?? 1) === 0 ? 'selected' : '' ?>>Inactive</option>
    </select>
  </div>

  <div class="rounded-lg bg-sky-50 p-3 text-xs text-sky-800 ring-1 ring-sky-200">
    After saving, use the <b>Permissions</b> screen to grant this role CRUD access to modules.
  </div>

  <div class="flex items-center gap-3">
    <button class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-brand-700">
      <?= $mode === 'edit' ? 'Save changes' : 'Create role' ?></button>
    <a href="<?= url('index.php?action=roles') ?>" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</a>
  </div>
</form>
