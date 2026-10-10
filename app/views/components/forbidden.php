<?php declare(strict_types=1); ?>
<div class="mx-auto max-w-lg rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200/60">
  <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-rose-50 text-3xl">🔒</div>
  <h1 class="mt-4 text-xl font-bold text-slate-900">403 — Access Denied</h1>
  <p class="mt-2 text-sm text-slate-500">
    Your roles do not grant the <b><?= e($action ?? '?') ?></b> action on module link
    <b>#<?= (int)($linkId ?? 0) ?></b>. Ask an administrator to assign this permission
    under <em>Roles → Permissions</em>.
  </p>
  <a href="<?= url('index.php?action=dashboard') ?>"
     class="mt-5 inline-block rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-brand-700">
    Back to Dashboard
  </a>
</div>
