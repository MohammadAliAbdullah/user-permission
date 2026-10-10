<?php declare(strict_types=1); ?>
<div class="mb-6">
  <a href="<?= url('index.php?action=modules') ?>" class="text-sm text-brand-600 hover:underline">&larr; Back to modules</a>
  <h1 class="mt-1 text-2xl font-bold text-slate-900"><?= $mode === 'edit' ? 'Edit Link #' . (int)$link['LINK_ID'] : 'Create Module Link' ?></h1>
  <p class="text-sm text-slate-500">A link is a menu entry / page. Grant roles CRUD access to it from the Permissions screen.</p>
</div>

<?php if (!empty($errors)): ?>
  <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
    <?php foreach ($errors as $er): ?><p>&bull; <?= e($er) ?></p><?php endforeach; ?>
  </div>
<?php endif; ?>

<form method="post" action="<?= url('index.php?action=modules&do=' . ($mode === 'edit' ? 'update' : 'store')) ?>"
      class="grid grid-cols-1 gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60 sm:grid-cols-2">
  <?= csrf_field() ?>
  <?php if ($mode === 'edit'): ?><input type="hidden" name="id" value="<?= (int)$link['LINK_ID'] ?>"><?php endif; ?>

  <div>
    <label class="mb-1 block text-sm font-medium text-slate-600">Link name *</label>
    <input name="LINK_NAME" required maxlength="100" value="<?= e(old('LINK_NAME', $link['LINK_NAME'] ?? '')) ?>"
           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
  </div>
  <div>
    <label class="mb-1 block text-sm font-medium text-slate-600">Module *</label>
    <select name="MODULE_ID" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none">
      <?php foreach ($modules as $m): ?>
        <option value="<?= (int)$m['MODULE_ID'] ?>" <?= (int)($link['MODULE_ID'] ?? 0) === (int)$m['MODULE_ID'] ? 'selected' : '' ?>>
          <?= e($m['MODULE_NAME']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="mb-1 block text-sm font-medium text-slate-600">Parent link (optional)</label>
    <select name="PR_LINK_ID" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none">
      <option value="">— top level —</option>
      <?php foreach ($allLinks as $l): if ($mode === 'edit' && (int)$l['LINK_ID'] === (int)$link['LINK_ID']) continue; ?>
        <option value="<?= (int)$l['LINK_ID'] ?>" <?= (int)($link['PR_LINK_ID'] ?? 0) === (int)$l['LINK_ID'] ? 'selected' : '' ?>>
          <?= e($l['LINK_NAME']) ?> (#<?= (int)$l['LINK_ID'] ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="mb-1 block text-sm font-medium text-slate-600">URL (use # for pure category)</label>
    <input name="URL_URI" maxlength="255" value="<?= e(old('URL_URI', $link['URL_URI'] ?? 'index.php?action=')) ?>"
           class="w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
  </div>
  <div>
    <label class="mb-1 block text-sm font-medium text-slate-600">Sort order</label>
    <input type="number" name="SL_NO" min="0" value="<?= e(old('SL_NO', (string)($link['SL_NO'] ?? 99))) ?>"
           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none">
  </div>
  <div>
    <label class="mb-1 block text-sm font-medium text-slate-600">Status</label>
    <select name="ACTIVE_STATUS" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none">
      <option value="1" <?= (int)($link['ACTIVE_STATUS'] ?? 1) === 1 ? 'selected' : '' ?>>Active (visible in menu when permitted)</option>
      <option value="0" <?= (int)($link['ACTIVE_STATUS'] ?? 1) === 0 ? 'selected' : '' ?>>Inactive (hidden)</option>
    </select>
  </div>

  <fieldset class="sm:col-span-2 rounded-lg border border-slate-200 p-4">
    <legend class="px-2 text-sm font-semibold text-slate-700">Default CRUD flags (legacy compatibility)</legend>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <?php foreach (['CREATE' => 'Create', 'READ' => 'Read', 'UPDATE' => 'Update', 'DELETE' => 'Delete'] as $col => $lbl): ?>
        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50">
          <input type="checkbox" name="flags[<?= $col ?>]" value="1"
            <?= !empty($link[$col]) ? 'checked' : '' ?> class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
          <?= $lbl ?>
        </label>
      <?php endforeach; ?>
    </div>
    <p class="mt-2 text-xs text-slate-400">These mirror <code>sa_module_links</code>; actual enforcement uses role permissions.</p>
  </fieldset>

  <div class="flex items-center gap-3 sm:col-span-2">
    <button class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-brand-700">
      <?= $mode === 'edit' ? 'Save changes' : 'Create link' ?></button>
    <a href="<?= url('index.php?action=modules') ?>" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</a>
  </div>
</form>
