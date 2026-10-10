<?php
/**
 * Authentication + RBAC core.
 * Permission model:  user -> roles (user_roles) -> permissions (role_permissions)
 *                    permission = (LINK_ID, action) where action in create|read|update|delete
 * Legacy compatibility: sa_users / sa_module_links / sa_modules are reused as-is.
 */
declare(strict_types=1);

require_once APP_ROOT . '/app/helpers/Database.php';

final class Auth
{
    public const SUPER_ADMIN_ROLE = 99;   // role id that bypasses all checks
    private static ?array $permCache = null;

    /* ---------------- session ---------------- */

    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            ]);
        }
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    /** Gate a page: must be logged in. */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('login', 'Please sign in to continue.', 'error');
            redirect('index.php?action=login');
        }
    }

    /* ---------------- login / logout ---------------- */

    public static function attempt(string $username, string $password): array
    {
        $u = Database::selectOne(
            'SELECT USER_ID, FULL_NAME, USERNAME, USERPW, ORG_ID, USERGRP_ID, ACTIVE_STATUS, EMAIL
             FROM sa_users WHERE USERNAME = ? ORDER BY ACTIVE_STATUS DESC LIMIT 1',
            [$username]
        );
        if (!$u || !hash_equals(strtolower((string)$u['USERPW']), md5($password))) {
            return ['ok' => false, 'msg' => 'Invalid username or password.'];
        }
        if ((int)$u['ACTIVE_STATUS'] !== 1) {
            return ['ok' => false, 'msg' => 'This account is disabled. Contact an administrator.'];
        }
        session_regenerate_id(true);
        unset($u['USERPW']);
        $_SESSION['user'] = $u;
        self::$permCache = null;
        Database::statement('UPDATE sa_users SET LAST_LOGIN_DT = NOW() WHERE USER_ID = ?', [$u['USER_ID']]);
        self::log('login', 'auth', "User {$u['USERNAME']} signed in");
        return ['ok' => true];
    }

    public static function logout(): void
    {
        if (self::check()) {
            self::log('logout', 'auth', 'User signed out');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /* ---------------- roles & permissions ---------------- */

    /** All active role ids attached to current user. */
    public static function roleIds(?int $userId = null): array
    {
        $uid = $userId ?? (int)(self::user()['USER_ID'] ?? 0);
        $rows = Database::select(
            'SELECT ur.USERGRP_ID FROM user_roles ur
             JOIN roles r ON r.USERGRP_ID = ur.USERGRP_ID AND r.ACTIVE_STATUS = 1
             WHERE ur.USER_ID = ? AND ur.ACTIVE_STATUS = 1',
            [$uid]
        );
        return array_map(fn($r) => (int)$r['USERGRP_ID'], $rows);
    }

    public static function isSuperAdmin(): bool
    {
        return in_array(self::SUPER_ADMIN_ROLE, self::roleIds(), true);
    }

    /**
     * Flat set of granted actions keyed "linkId:action".
     * Cached per request. Super Admin gets wildcard '*'.
     */
    private static function permissions(): array
    {
        if (self::$permCache !== null) {
            return self::$permCache;
        }
        $uid = (int)(self::user()['USER_ID'] ?? 0);
        if ($uid <= 0) {
            return self::$permCache = [];
        }
        $rows = Database::select(
            'SELECT DISTINCT rp.LINK_ID, p.ACTION_KEY
             FROM role_permissions rp
             JOIN permissions p ON p.PERMISSION_ID = rp.PERMISSION_ID AND p.ACTIVE_STATUS = 1
             WHERE rp.USERGRP_ID IN (SELECT USERGRP_ID FROM user_roles WHERE USER_ID = ? AND ACTIVE_STATUS = 1)
               AND rp.ACTIVE_STATUS = 1',
            [$uid]
        );
        $set = [];
        foreach ($rows as $r) {
            $set[$r['LINK_ID'] . ':' . $r['ACTION_KEY']] = true;
        }
        return self::$permCache = $set;
    }

    /** Core gate: can current user perform $action on module link $linkId? */
    public static function can(int $linkId, string $action): bool
    {
        if (!self::check()) {
            return false;
        }
        if (self::isSuperAdmin()) {
            return true;
        }
        return isset(self::permissions()[$linkId . ':' . strtolower($action)]);
    }

    /** Does the user have ANY of these actions on the link (menu visibility)? */
    public static function canAny(int $linkId, array $actions = ['create', 'read', 'update', 'delete']): bool
    {
        if (self::isSuperAdmin()) {
            return true;
        }
        foreach ($actions as $a) {
            if (self::can($linkId, $a)) {
                return true;
            }
        }
        return false;
    }

    /** Abort with 403 page when gate fails. */
    public static function authorize(int $linkId, string $action): void
    {
        if (!self::can($linkId, $action)) {
            http_response_code(403);
            view('components/forbidden', ['linkId' => $linkId, 'action' => $action]);
            exit;
        }
    }

    /* ---------------- dynamic menu ---------------- */

    /**
     * Menu tree: modules -> links (parent/child) filtered by permissions.
     * Links whose URL_URI is '#' act as pure categories.
     */
    public static function menu(): array
    {
        $modules = Database::select(
            'SELECT m.MODULE_ID, m.MODULE_NAME, m.MODULE_ICON
             FROM sa_modules m
             JOIN sa_org_modules om ON om.MODULE_ID = m.MODULE_ID AND om.ACTIVE_STATUS = 1
             WHERE m.ACTIVE_STATUS = 1
             GROUP BY m.MODULE_ID ORDER BY m.SL_NO');

        $links = Database::select(
            'SELECT LINK_ID, PR_LINK_ID, LINK_NAME, MODULE_ID, URL_URI
             FROM sa_module_links WHERE ACTIVE_STATUS = 1 ORDER BY SL_NO, LINK_NAME');

        $byParent = [];
        foreach ($links as $l) {
            $byParent[(int)($l['PR_LINK_ID'] ?? 0)][] = $l;
        }

        $build = function (int $parentId) use (&$build, &$byParent): array {
            $out = [];
            foreach ($byParent[$parentId] ?? [] as $l) {
                $children = $build((int)$l['LINK_ID']);
                $isCategory = ($l['URL_URI'] === '#' || $l['URL_URI'] === null || $l['URL_URI'] === '');
                if ($isCategory && !$children) {
                    continue; // empty category hidden
                }
                $visible = $isCategory ? (bool)$children : self::canAny((int)$l['LINK_ID']);
                if (!$visible) {
                    continue;
                }
                $out[] = ['link' => $l, 'children' => $children];
            }
            return $out;
        };

        $tree = [];
        foreach ($modules as $m) {
            $items = array_values(array_filter(
                $build(0),
                fn($n) => (int)$n['link']['MODULE_ID'] === (int)$m['MODULE_ID']
            ));
            // also collect top-level links for this module (PR_LINK_ID NULL handled as 0 above)
            if ($items) {
                $tree[] = ['module' => $m, 'items' => $items];
            }
        }
        return $tree;
    }

    /* ---------------- audit log ---------------- */

    public static function log(string $action, string $moduleLink, string $details): void
    {
        try {
            Database::statement(
                'INSERT INTO activity_logs (USER_ID, USERNAME, ACTION, MODULE_LINK, DETAILS, IP_ADDRESS)
                 VALUES (?,?,?,?,?,?)',
                [
                    (int)(self::user()['USER_ID'] ?? 0) ?: null,
                    self::user()['USERNAME'] ?? 'guest',
                    $action, $moduleLink, mb_substr($details, 0, 60000),
                    $_SERVER['REMOTE_ADDR'] ?? null,
                ]
            );
        } catch (Throwable) { /* logging must never break the app */ }
    }
}
