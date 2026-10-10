<?php declare(strict_types=1); /* rendered standalone (no sidebar) */ ?>
<div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-brand-900 via-brand-700 to-brand-500 p-4">
  <div class="w-full max-w-md">
    <div class="mb-6 text-center text-white">
      <div class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-2xl bg-white/10 text-3xl shadow-lg">⚓</div>
      <h1 class="text-2xl font-bold tracking-wide">BNSIS Admin Panel</h1>
      <p class="mt-1 text-sm text-white/70">Dynamic User Role &amp; Permission Management</p>
    </div>

    <div class="rounded-2xl bg-white p-8 shadow-2xl">
      <h2 class="mb-6 text-lg font-semibold text-slate-800">Sign in to your account</h2>

      <?php if ($err = flash('login_err')): ?>
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= e($err) ?></div>
      <?php endif; ?>
      <?php if ($ok = flash('loggedout')): ?>
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= e($ok) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= url('index.php?action=login') ?>" class="space-y-5">
        <?= csrf_field() ?>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-600">Username</label>
          <input name="username" required autofocus value="<?= old('username') ?>"
                 class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                 placeholder="e.g. admin">
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-600">Password</label>
          <input type="password" name="password" required
                 class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                 placeholder="••••••••">
        </div>
        <button class="w-full rounded-lg bg-brand-600 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-brand-700 active:scale-[.99]">
          Sign in
        </button>
      </form>

      <div class="mt-6 rounded-lg bg-slate-50 p-3 text-xs text-slate-500">
        <p class="mb-1 font-semibold text-slate-600">Demo accounts</p>
        <p><b>admin</b> / admin123 — Super Admin (all access)</p>
        <p><b>officer</b> / 123456 — Officer of DA (Security &amp; Access only)</p>
        <p><b>entry</b> / 123456 — Data Entry (read-only)</p>
      </div>
    </div>
  </div>
</div>
