<?php
/**
 * App-wide constants & bootstrap helpers (Raw PHP, no framework).
 */
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('BASE_URL', rtrim(getenv('BASE_URL') ?: '', '/')); // e.g. '' when served from public/

/* ---- mbstring polyfill (for PHP builds without the mbstring extension) ---- */
if (!function_exists('mb_substr')) {
    function mb_substr(string $s, int $start, ?int $length = null, string $encoding = 'UTF-8'): string
    {
        return substr($s, $start, $length ?? PHP_INT_MAX);
    }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper(string $s, string $encoding = 'UTF-8'): string { return strtoupper($s); }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $s, string $encoding = 'UTF-8'): int { return strlen($s); }
}

/** URL helper that respects an optional BASE_URL prefix. */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** CSRF token create/get. */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrf_token(), (string)$sent)) {
        http_response_code(419);
        exit('CSRF token mismatch. Go back and try again.');
    }
}

/** Flash messages. */
function flash(string $key, ?string $msg = null, string $type = 'success'): mixed
{
    if ($msg !== null) {
        $_SESSION['_flash'][$type][$key] = $msg;
        return null;
    }
    $val = $_SESSION['_flash'][$type][$key] ?? null;
    unset($_SESSION['_flash'][$type][$key]);
    return $val;
}

function old(string $key, mixed $default = ''): mixed
{
    return htmlspecialchars((string)($_SESSION['_old'][$key] ?? $default), ENT_QUOTES, 'UTF-8');
}

function set_old(array $data): void { $_SESSION['_old'] = $data; }
function clear_old(): void { unset($_SESSION['_old']); }

/** Escape shorthand. */
function e(mixed $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Redirect & stop. */
function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/** Render a view inside the admin layout (or standalone for login). */
function view(string $template, array $data = [], string $layout = APP_ROOT . '/app/views/layouts/admin.php'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require APP_ROOT . '/app/views/' . $template . '.php';
    $__content = ob_get_clean();
    require $layout;
}
