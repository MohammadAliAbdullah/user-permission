<?php
/**
 * BNSIS — Dynamic User Role & Permission Management (Raw PHP front controller)
 * Routes: index.php?action=login|logout|dashboard|users|roles|permissions|modules|activity
 * All forms post back here with an additional &do=<store|update|delete|toggle|save>.
 */
declare(strict_types=1);

require __DIR__ . '/../config/settings.php';
require APP_ROOT . '/config/links.php';
require APP_ROOT . '/app/helpers/Auth.php';

Auth::start();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'dashboard');
$do     = strtolower((string)($_GET['do'] ?? ''));
$page   = max(1, (int)($_GET['page'] ?? 1));

/* ------------------------------------------------- helpers ----- */

/** Load sa_users row + merged role ids for the users form. */
function load_user_for_form(int $id): array
{
    $user = Database::selectOne(
        'SELECT USER_ID, FULL_NAME, USERNAME, EMAIL, ORG_ID, USERGRP_ID, USERLVL_ID, ACTIVE_STATUS
         FROM sa_users WHERE USER_ID = ?', [$id]) ?? [];
    $assignedRoles = array_map('intval', array_column(
        Database::select('SELECT USERGRP_ID FROM user_roles WHERE USER_ID = ? AND ACTIVE_STATUS = 1', [$id]),
        'USERGRP_ID'));
    return [$user, $assignedRoles];
}

/** Sync user_roles (multi-role). Primary group always included. */
function sync_user_roles(int $userId, int $primaryGrp, array $extraRoleIds, int $orgId): void
{
    Database::statement('DELETE FROM user_roles WHERE USER_ID = ?', [$userId]);
    $ids = array_unique(array_filter(array_merge([$primaryGrp], $extraRoleIds), fn($r) => (int)$r > 0));
    foreach ($ids as $rid) {
        Database::statement(
            'INSERT INTO user_roles (USER_ID, USERGRP_ID, ORG_ID, ACTIVE_STATUS, CRE_BY, CRE_DT)
             VALUES (?,?,?,?,?,NOW())',
            [$userId, (int)$rid, $orgId, 1, (int)(Auth::user()['USER_ID'] ?? 1)]);
    }
}

/** Validate + persist a user from $_POST. Returns error list. */
function save_user(array $post, bool $isEdit): array
{
    $errors = [];
    $name  = trim((string)($post['FULL_NAME'] ?? ''));
    $uname = trim((string)($post['USERNAME'] ?? ''));
    $email = trim((string)($post['EMAIL'] ?? ''));
    $pw    = (string)($post['password'] ?? '');
    if ($name === '')  $errors[] = 'Full name is required.';
    if ($uname === '') $errors[] = 'Username is required.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email address is invalid.';
    if (!$isEdit && strlen($pw) < 6) $errors[] = 'Password must be at least 6 characters.';

    $dup = Database::selectOne(
        'SELECT USER_ID FROM sa_users WHERE USERNAME = ?' . ($isEdit ? ' AND USER_ID <> ?' : ''),
        $isEdit ? [$uname, (int)$post['id']] : [$uname]);
    if ($dup) $errors[] = 'That username is already taken.';

    if ($errors) return $errors;

    $grp    = (int)($post['USERGRP_ID'] ?? 0);
    $lvl    = (int)($post['USERLVL_ID'] ?? 0) ?: null;
    $active = (int)($post['ACTIVE_STATUS'] ?? 1);
    $uid    = (int)Auth::user()['USER_ID'];
    $orgId  = (int)($post['ORG_ID'] ?? Auth::user()['ORG_ID'] ?? 1);

    if ($isEdit) {
        $id = (int)$post['id'];
        if ($pw !== '') {
            Database::statement(
                'UPDATE sa_users SET FULL_NAME=?, USERNAME=?, EMAIL=?, USERGRP_ID=?, USERLVL_ID=?,
                 ACTIVE_STATUS=?, USERPW=MD5(?), UPD_BY=?, UPD_DT=NOW() WHERE USER_ID=?',
                [$name, $uname, $email, $grp, $lvl, $active, $pw, $uid, $id]);
        } else {
            Database::statement(
                'UPDATE sa_users SET FULL_NAME=?, USERNAME=?, EMAIL=?, USERGRP_ID=?, USERLVL_ID=?,
                 ACTIVE_STATUS=?, UPD_BY=?, UPD_DT=NOW() WHERE USER_ID=?',
                [$name, $uname, $email, $grp, $lvl, $active, $uid, $id]);
        }
        sync_user_roles($id, $grp, array_map('intval', (array)($post['roles'] ?? [])), $orgId);
        Auth::log('update', 'users', "Updated user #{$id} ({$uname})");
    } else {
        $id = Database::insertGetId(
            'INSERT INTO sa_users (FULL_NAME, USERNAME, USERPW, EMAIL, ORG_ID, USERGRP_ID, USERLVL_ID,
                                   ACTIVE_STATUS, CRE_BY, CRE_DT)
             VALUES (?,?,?,?,?,?,?,?,?,NOW())',
            [$name, $uname, md5($pw), $email, $orgId, $grp, $lvl, $active, $uid]);
        sync_user_roles($id, $grp, array_map('intval', (array)($post['roles'] ?? [])), $orgId);
        Auth::log('create', 'users', "Created user #{$id} ({$uname})");
    }
    return [];
}

/* ------------------------------------------------- login ------- */

if ($action === 'login') {
    if (Auth::check()) redirect('index.php?action=dashboard');
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $res = Auth::attempt(trim((string)($_POST['username'] ?? '')), (string)($_POST['password'] ?? ''));
        if ($res['ok']) {
            flash('welcome', 'Welcome back, ' . Auth::user()['FULL_NAME'] . '!');
            redirect('index.php?action=dashboard');
        }
        $errors[] = $res['msg'];
    }
    view('auth/login', ['title' => 'Sign in', 'errors' => $errors], APP_ROOT . '/app/views/layouts/admin.php');
    exit;
}

if ($action === 'logout') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') { verify_csrf(); }
    Auth::logout();
    redirect('index.php?action=login');
}

/* Everything below requires authentication */
Auth::requireLogin();

$roleNames = array_map(fn($r) => $r['USERGRP_NAME'], Database::select(
    'SELECT r.USERGRP_NAME FROM roles r JOIN user_roles ur ON ur.USERGRP_ID = r.USERGRP_ID
     WHERE ur.USER_ID = ? AND ur.ACTIVE_STATUS = 1 ORDER BY r.USERGRP_NAME',
    [(int)Auth::user()['USER_ID']]));
$menu = Auth::menu();

/* ------------------------------------------------- dashboard --- */

if ($action === 'dashboard' || $action === '') {
    $stats = [
        ['label' => 'Users',      'value' => (int)Database::selectOne('SELECT COUNT(*) c FROM sa_users')['c'],
         'icon' => '👤', 'bg' => 'bg-brand-50',   'hint' => 'Accounts in bnsis_db'],
        ['label' => 'Roles',      'value' => (int)Database::selectOne('SELECT COUNT(*) c FROM roles WHERE ACTIVE_STATUS=1')['c'],
         'icon' => '🛡️', 'bg' => 'bg-emerald-50', 'hint' => 'Active roles'],
        ['label' => 'Permissions','value' => (int)Database::selectOne('SELECT COUNT(*) c FROM role_permissions WHERE ACTIVE_STATUS=1')['c'],
         'icon' => '🔑', 'bg' => 'bg-amber-50',   'hint' => 'Grants across roles'],
        ['label' => 'Module Links','value' => (int)Database::selectOne('SELECT COUNT(*) c FROM sa_module_links WHERE ACTIVE_STATUS=1')['c'],
         'icon' => '📦', 'bg' => 'bg-sky-50',     'hint' => 'Menu entries managed'],
    ];

    $uid = (int)Auth::user()['USER_ID'];
    $myAccess = Database::select(
        'SELECT m.MODULE_NAME, l.LINK_ID,
                MAX(p.ACTION_KEY = "create") AS `create`, MAX(p.ACTION_KEY = "read") AS `read`,
                MAX(p.ACTION_KEY = "update") AS `update`, MAX(p.ACTION_KEY = "delete") AS `delete`
         FROM role_permissions rp
         JOIN permissions p   ON p.PERMISSION_ID = rp.PERMISSION_ID AND p.ACTIVE_STATUS = 1
         JOIN sa_module_links l ON l.LINK_ID = p.LINK_ID
         JOIN sa_modules m      ON m.MODULE_ID = l.MODULE_ID
         WHERE rp.USERGRP_ID IN (SELECT USERGRP_ID FROM user_roles WHERE USER_ID = ? AND ACTIVE_STATUS = 1)
           AND rp.ACTIVE_STATUS = 1
         GROUP BY m.MODULE_NAME, l.LINK_ID ORDER BY m.MODULE_NAME, l.SL_NO', [$uid]);
    // normalise keys expected by the view (create/read/update/delete ints)
    $myAccess = array_map(function ($r) {
        return ['MODULE_NAME' => $r['MODULE_NAME'], 'create' => (int)$r['create'], 'read' => (int)$r['read'],
                'update' => (int)$r['update'], 'delete' => (int)$r['delete']];
    }, $myAccess);

    $logs = Database::select(
        'SELECT USERNAME, ACTION, MODULE_LINK, DETAILS, CRE_DT FROM activity_logs
         ORDER BY LOG_ID DESC LIMIT 8');

    view('dashboard/index', [
        'title' => 'Dashboard', 'active' => 'dashboard', 'currentLink' => LINK_DASHBOARD,
        'menu' => $menu, 'roleNames' => $roleNames, 'stats' => $stats, 'myAccess' => $myAccess, 'logs' => $logs,
    ]);
    exit;
}

/* ------------------------------------------------- users ------- */

if ($action === 'users') {
    $allRoles = Database::select('SELECT USERGRP_ID, USERGRP_NAME FROM roles WHERE ACTIVE_STATUS = 1 ORDER BY USERGRP_NAME');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        Auth::authorize(LINK_USERS, $do === 'delete' ? 'delete' : ($do === 'update' || $do === 'toggle' ? 'update' : 'create'));

        if ($do === 'store' || $do === 'update') {
            $errors = save_user($_POST, $do === 'update');
            if ($errors) {
                set_old($_POST);
                $mode = $do === 'update' ? 'edit' : 'create';
                $user = $mode === 'edit' ? load_user_for_form((int)$_POST['id'])[0] : [];
                view('users/form', [
                    'title' => 'Users', 'active' => 'users', 'currentLink' => LINK_USERS, 'menu' => $menu,
                    'roleNames' => $roleNames, 'mode' => $mode, 'user' => $user,
                    'assignedRoles' => $mode === 'edit' ? load_user_for_form((int)$_POST['id'])[1] : [],
                    'allRoles' => $allRoles, 'levels' => Database::select('SELECT * FROM sa_ug_level'),
                    'errors' => $errors,
                ]);
                exit;
            }
            clear_old();
            flash('saved', $do === 'update' ? 'User updated successfully.' : 'User created successfully.');
            redirect('index.php?action=users');
        }

        if ($do === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int)Auth::user()['USER_ID']) { flash('self', 'You cannot disable your own account.', 'error'); redirect('index.php?action=users'); }
            Database::statement('UPDATE sa_users SET ACTIVE_STATUS = IF(ACTIVE_STATUS=1,0,1), UPD_BY=?, UPD_DT=NOW() WHERE USER_ID=?',
                [(int)Auth::user()['USER_ID'], $id]);
            Auth::log('toggle', 'users', "Toggled active status of user #{$id}");
            flash('toggled', 'User status updated.');
            redirect('index.php?action=users');
        }

        if ($do === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int)Auth::user()['USER_ID']) { flash('self', 'You cannot delete your own account.', 'error'); redirect('index.php?action=users'); }
            Database::statement('DELETE FROM user_roles WHERE USER_ID = ?', [$id]);
            Database::statement('DELETE FROM sa_users WHERE USER_ID = ?', [$id]);
            Auth::log('delete', 'users', "Deleted user #{$id}");
            flash('deleted', 'User deleted.');
            redirect('index.php?action=users');
        }
    }

    // GET: list / create-form / edit-form
    if (($mode = $_GET['mode'] ?? '') === 'create' || $mode === 'edit') {
        Auth::authorize(LINK_USERS, $mode === 'edit' ? 'update' : 'create');
        $user = $assignedRoles = [];
        if ($mode === 'edit') {
            Auth::authorize(LINK_USERS, 'update');
            [$user, $assignedRoles] = load_user_for_form((int)($_GET['id'] ?? 0));
            if (!$user) { flash('nf', 'User not found.', 'error'); redirect('index.php?action=users'); }
        }
        view('users/form', [
            'title' => 'Users', 'active' => 'users', 'currentLink' => LINK_USERS, 'menu' => $menu,
            'roleNames' => $roleNames, 'mode' => $mode, 'user' => $user, 'assignedRoles' => $assignedRoles,
            'allRoles' => $allRoles, 'levels' => Database::select('SELECT * FROM sa_ug_level'), 'errors' => [],
        ]);
        exit;
    }

    Auth::authorize(LINK_USERS, 'read');
    $q     = trim((string)($_GET['q'] ?? ''));
    $roleF = (int)($_GET['role'] ?? 0);
    $perPage = 10;
    $where = ['1=1']; $params = [];
    if ($q !== '')     { $where[] = '(FULL_NAME LIKE ? OR USERNAME LIKE ? OR EMAIL LIKE ?)'; $params = array_merge($params, ["%$q%", "%$q%", "%$q%"]); }
    if ($roleF > 0)    { $where[] = 'USERGRP_ID = ?'; $params[] = $roleF; }
    $W = implode(' AND ', $where);

    $totalUsers = (int)Database::selectOne("SELECT COUNT(*) c FROM sa_users WHERE $W", $params)['c'];
    $offset = ($page - 1) * $perPage;
    $users = Database::select(
        "SELECT u.USER_ID, u.FULL_NAME, u.USERNAME, u.EMAIL, u.ACTIVE_STATUS, u.LAST_LOGIN_DT,
                COALESCE(NULLIF(GROUP_CONCAT(DISTINCT r.USERGRP_NAME ORDER BY r.USERGRP_NAME SEPARATOR ','),''), '') AS ROLES
         FROM sa_users u
         LEFT JOIN user_roles ur ON ur.USER_ID = u.USER_ID AND ur.ACTIVE_STATUS = 1
         LEFT JOIN roles r       ON r.USERGRP_ID = ur.USERGRP_ID
         WHERE $W
         GROUP BY u.USER_ID ORDER BY u.USER_ID ASC LIMIT $perPage OFFSET $offset", $params);

    view('users/index', [
        'title' => 'Users', 'active' => 'users', 'currentLink' => LINK_USERS, 'menu' => $menu,
        'roleNames' => $roleNames, 'users' => $users, 'allRoles' => $allRoles,
        'totalUsers' => $totalUsers, 'page' => $page, 'perPage' => $perPage,
    ]);
    exit;
}

/* ------------------------------------------------- roles ------- */

if ($action === 'roles') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        Auth::authorize(LINK_ROLES, $do === 'delete' ? 'delete' : ($do === 'update' ? 'update' : 'create'));

        if ($do === 'store' || $do === 'update') {
            $name = trim((string)($_POST['USERGRP_NAME'] ?? ''));
            $desc = trim((string)($_POST['DESCRIPTION'] ?? ''));
            $act  = (int)($_POST['ACTIVE_STATUS'] ?? 1);
            $errors = [];
            if ($name === '') $errors[] = 'Role name is required.';
            $dup = Database::selectOne('SELECT USERGRP_ID FROM roles WHERE USERGRP_NAME = ?' .
                ($do === 'update' ? ' AND USERGRP_ID <> ?' : ''),
                $do === 'update' ? [$name, (int)$_POST['id']] : [$name]);
            if ($dup) $errors[] = 'A role with that name already exists.';

            if ($errors) {
                set_old($_POST);
                view('roles/form', [
                    'title' => 'Roles', 'active' => 'roles', 'currentLink' => LINK_ROLES, 'menu' => $menu,
                    'roleNames' => $roleNames, 'mode' => $do === 'update' ? 'edit' : 'create',
                    'role' => $do === 'update' ? (Database::selectOne('SELECT * FROM roles WHERE USERGRP_ID=?', [(int)$_POST['id']]) ?? []) : [],
                    'errors' => $errors,
                ]);
                exit;
            }
            if ($do === 'update') {
                $id = (int)$_POST['id'];
                $sys = (int)(Database::selectOne('SELECT IS_SYSTEM FROM roles WHERE USERGRP_ID=?', [$id])['IS_SYSTEM'] ?? 0);
                Database::statement('UPDATE roles SET USERGRP_NAME=?, DESCRIPTION=?, ACTIVE_STATUS=?, UPD_BY=?, UPD_DT=NOW() WHERE USERGRP_ID=?',
                    [$name, $desc, $sys === 1 ? 1 : $act, (int)Auth::user()['USER_ID'], $id]);
                // keep legacy group table in sync
                Database::statement('UPDATE sa_user_group SET USERGRP_NAME=?, ACTIVE_STATUS=? WHERE USERGRP_ID=?', [$name, $act, $id]);
                Auth::log('update', 'roles', "Updated role #{$id} ({$name})");
            } else {
                $id = Database::insertGetId(
                    'INSERT INTO roles (ORG_ID, USERGRP_NAME, DESCRIPTION, IS_SYSTEM, ACTIVE_STATUS, CRE_BY, CRE_DT)
                     VALUES (?,?,?,?,?, ?,NOW())',
                    [(int)(Auth::user()['ORG_ID'] ?? 1), $name, $desc, 0, $act, (int)Auth::user()['USER_ID']]);
                Database::statement(
                    'INSERT IGNORE INTO sa_user_group (USERGRP_ID, ORG_ID, USERGRP_NAME, ACTIVE_STATUS, CRE_BY, CRE_DT)
                     VALUES (?,?,?,?,?,NOW())', [$id, (int)(Auth::user()['ORG_ID'] ?? 1), $name, $act, (int)Auth::user()['USER_ID']]);
                Auth::log('create', 'roles', "Created role #{$id} ({$name})");
            }
            clear_old();
            flash('saved', 'Role saved successfully.');
            redirect('index.php?action=roles');
        }

        if ($do === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $role = Database::selectOne('SELECT * FROM roles WHERE USERGRP_ID=?', [$id]);
            if (!$role) { flash('nf', 'Role not found.', 'error'); redirect('index.php?action=roles'); }
            if ((int)$role['IS_SYSTEM'] === 1) { flash('sys', 'System roles cannot be deleted.', 'error'); redirect('index.php?action=roles'); }
            $inUse = (int)Database::selectOne('SELECT COUNT(*) c FROM user_roles WHERE USERGRP_ID=?', [$id])['c'];
            if ($inUse > 0) { flash('used', "Cannot delete — {$inUse} user(s) still assigned to this role.", 'error'); redirect('index.php?action=roles'); }
            Database::statement('DELETE FROM role_permissions WHERE USERGRP_ID=?', [$id]);
            Database::statement('DELETE FROM roles WHERE USERGRP_ID=?', [$id]);
            Database::statement('DELETE FROM sa_user_group WHERE USERGRP_ID=?', [$id]);
            Auth::log('delete', 'roles', "Deleted role #{$id} ({$role['USERGRP_NAME']})");
            flash('deleted', 'Role deleted.');
            redirect('index.php?action=roles');
        }
    }

    if (($mode = $_GET['mode'] ?? '') === 'create' || $mode === 'edit') {
        Auth::authorize(LINK_ROLES, $mode === 'edit' ? 'update' : 'create');
        $role = [];
        if ($mode === 'edit') {
            $role = Database::selectOne('SELECT * FROM roles WHERE USERGRP_ID=?', [(int)($_GET['id'] ?? 0)]) ?? [];
            if (!$role) { flash('nf', 'Role not found.', 'error'); redirect('index.php?action=roles'); }
        }
        view('roles/form', [
            'title' => 'Roles', 'active' => 'roles', 'currentLink' => LINK_ROLES, 'menu' => $menu,
            'roleNames' => $roleNames, 'mode' => $mode, 'role' => $role, 'errors' => [],
        ]);
        exit;
    }

    Auth::authorize(LINK_ROLES, 'read');
    $roles = Database::select(
        'SELECT r.*, 
                (SELECT COUNT(*) FROM user_roles ur WHERE ur.USERGRP_ID = r.USERGRP_ID AND ur.ACTIVE_STATUS = 1) AS USER_CNT,
                (SELECT COUNT(*) FROM role_permissions rp WHERE rp.USERGRP_ID = r.USERGRP_ID AND rp.ACTIVE_STATUS = 1) AS PERM_CNT,
                (SELECT COUNT(DISTINCT p.LINK_ID) FROM role_permissions rp
                   JOIN permissions p ON p.PERMISSION_ID = rp.PERMISSION_ID
                  WHERE rp.USERGRP_ID = r.USERGRP_ID AND rp.ACTIVE_STATUS = 1) AS MODULE_CNT
         FROM roles r ORDER BY r.IS_SYSTEM DESC, r.USERGRP_NAME');
    view('roles/index', [
        'title' => 'Roles', 'active' => 'roles', 'currentLink' => LINK_ROLES, 'menu' => $menu,
        'roleNames' => $roleNames, 'roles' => $roles,
    ]);
    exit;
}

/* ------------------------------------------------- permissions - */

if ($action === 'permissions') {
    Auth::authorize(LINK_PERMS, 'read');
    $roles = Database::select('SELECT USERGRP_ID, USERGRP_NAME FROM roles ORDER BY IS_SYSTEM DESC, USERGRP_NAME');
    $selectedRoleId = (int)($_GET['role'] ?? $_POST['role'] ?? ($roles[0]['USERGRP_ID'] ?? 0));
    $selectedRole = Database::selectOne('SELECT * FROM roles WHERE USERGRP_ID = ?', [$selectedRoleId])
                  ?? ['USERGRP_NAME' => 'Unknown'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $do === 'save') {
        verify_csrf();
        Auth::authorize(LINK_PERMS, 'update');
        $granted = (array)($_POST['perms'] ?? []);

        // ensure permission rows exist for every link/action pair touched
        Database::statement(
            'INSERT IGNORE INTO permissions (LINK_ID, ACTION_KEY, DISPLAY_NAME)
             SELECT l.LINK_ID, a.k, CONCAT(l.LINK_NAME, " - ", UPPER(a.k))
             FROM sa_module_links l
             JOIN (SELECT "create" k UNION SELECT "read" UNION SELECT "update" UNION SELECT "delete") a
             WHERE l.ACTIVE_STATUS = 1');

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            // wipe current grants for this role, then re-insert checked ones
            Database::statement('DELETE rp FROM role_permissions rp
                                 JOIN permissions p ON p.PERMISSION_ID = rp.PERMISSION_ID
                                 WHERE rp.USERGRP_ID = ?', [$selectedRoleId]);
            $ins = $pdo->prepare('INSERT INTO role_permissions (USERGRP_ID, PERMISSION_ID, CRE_BY, CRE_DT, ACTIVE_STATUS)
                                  VALUES (?,?,?,NOW(),1)');
            $count = 0;
            foreach ($granted as $linkId => $actions) {
                foreach ((array)$actions as $a) {
                    if (!in_array($a, ['create', 'read', 'update', 'delete'], true)) continue;
                    $pid = Database::selectOne('SELECT PERMISSION_ID FROM permissions WHERE LINK_ID=? AND ACTION_KEY=?',
                        [(int)$linkId, $a])['PERMISSION_ID'] ?? null;
                    if ($pid) { $ins->execute([$selectedRoleId, $pid, (int)Auth::user()['USER_ID']]); $count++; }
                }
            }
            // mirror onto legacy sa_uglw_mlink so older screens stay consistent
            Database::statement('DELETE FROM sa_uglw_mlink WHERE USERGRP_ID = ?', [$selectedRoleId]);
            $mir = $pdo->prepare('INSERT INTO sa_uglw_mlink (USERGRP_ID, LINK_ID, `CREATE`, `READ`, `UPDATE`, `DELETE`, STATUS, ACTIVE_STATUS, CRE_BY, CRE_DT)
                                 VALUES (?,?,?,?,?,?,1,1,?,NOW())');
            foreach ($granted as $linkId => $actions) {
                $acts = array_intersect((array)$actions, ['create', 'read', 'update', 'delete']);
                $mir->execute([$selectedRoleId, (int)$linkId,
                    (int)in_array('create', $acts), (int)in_array('read', $acts),
                    (int)in_array('update', $acts), (int)in_array('delete', $acts),
                    (int)Auth::user()['USER_ID']]);
            }
            $pdo->commit();
        } catch (Throwable $ex) {
            $pdo->rollBack();
            flash('err', 'Could not save permissions: ' . $ex->getMessage(), 'error');
            redirect('index.php?action=permissions&role=' . $selectedRoleId);
        }
        Auth::log('update', 'permissions', "Role #{$selectedRoleId}: {$count} permission grant(s) saved");
        flash('saved', "Saved {$count} permission grant(s) for " . $selectedRole['USERGRP_NAME'] . '.');
        redirect('index.php?action=permissions&role=' . $selectedRoleId);
    }

    // Build matrix grouped by module
    $rows = Database::select(
        'SELECT l.LINK_ID, l.LINK_NAME, l.URL_URI, m.MODULE_NAME,
                COALESCE(MAX(CASE WHEN p.ACTION_KEY="create" THEN 1 END),0) AS `create`,
                COALESCE(MAX(CASE WHEN p.ACTION_KEY="read"   THEN 1 END),0) AS `read`,
                COALESCE(MAX(CASE WHEN p.ACTION_KEY="update" THEN 1 END),0) AS `update`,
                COALESCE(MAX(CASE WHEN p.ACTION_KEY="delete" THEN 1 END),0) AS `delete`
         FROM sa_module_links l
         JOIN sa_modules m ON m.MODULE_ID = l.MODULE_ID
         LEFT JOIN permissions p        ON p.LINK_ID = l.LINK_ID AND p.ACTIVE_STATUS = 1
         LEFT JOIN role_permissions rp  ON rp.PERMISSION_ID = p.PERMISSION_ID
                                        AND rp.USERGRP_ID = ? AND rp.ACTIVE_STATUS = 1
         WHERE l.ACTIVE_STATUS = 1
         GROUP BY l.LINK_ID, l.LINK_NAME, l.URL_URI, m.MODULE_NAME
         ORDER BY m.SL_NO, l.SL_NO, l.LINK_NAME', [$selectedRoleId]);

    $matrix = [];
    foreach ($rows as $r) { $matrix[$r['MODULE_NAME']][] = $r; }

    view('permissions/index', [
        'title' => 'Permissions', 'active' => 'permissions', 'currentLink' => LINK_PERMS, 'menu' => $menu,
        'roleNames' => $roleNames, 'roles' => $roles, 'selectedRoleId' => $selectedRoleId,
        'selectedRole' => $selectedRole, 'matrix' => $matrix,
    ]);
    exit;
}

/* ------------------------------------------------- modules ----- */

if ($action === 'modules') {
    Auth::authorize(LINK_MODULES_MGMT, 'read');
    $modules   = Database::select('SELECT * FROM sa_modules WHERE ACTIVE_STATUS = 1 ORDER BY SL_NO');
    $moduleNames = array_combine(array_column($modules, 'MODULE_ID'), array_column($modules, 'MODULE_NAME'));
    $allLinks  = Database::select('SELECT LINK_ID, LINK_NAME FROM sa_module_links WHERE ACTIVE_STATUS = 1 ORDER BY LINK_NAME');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $uid = (int)Auth::user()['USER_ID'];

        if ($do === 'delete') {
            Auth::authorize(LINK_MODULES_MGMT, 'delete');
            $id = (int)($_POST['id'] ?? 0);
            if (in_array($id, ADMIN_LINKS, true)) { flash('sys', 'Core admin links cannot be deleted.', 'error'); redirect('index.php?action=modules'); }
            Database::statement('DELETE FROM sa_uglw_mlink WHERE LINK_ID = ?', [$id]);
            Database::statement('DELETE rp FROM role_permissions rp JOIN permissions p ON p.PERMISSION_ID=rp.PERMISSION_ID WHERE p.LINK_ID = ?', [$id]);
            Database::statement('DELETE FROM permissions WHERE LINK_ID = ?', [$id]);
            Database::statement('DELETE FROM sa_module_links WHERE LINK_ID = ?', [$id]);
            Auth::log('delete', 'modules', "Deleted module link #{$id}");
            flash('deleted', 'Link deleted.');
            redirect('index.php?action=modules');
        }

        if ($do === 'store' || $do === 'update') {
            Auth::authorize(LINK_MODULES_MGMT, $do === 'update' ? 'update' : 'create');
            $name = trim((string)($_POST['LINK_NAME'] ?? ''));
            $errors = [];
            if ($name === '') $errors[] = 'Link name is required.';
            $moduleId = (int)($_POST['MODULE_ID'] ?? 0);
            if ($moduleId <= 0) $errors[] = 'Choose a module.';
            $pr = ($_POST['PR_LINK_ID'] ?? '') !== '' ? (int)$_POST['PR_LINK_ID'] : null;
            $flags = ['CREATE' => 0, 'READ' => 0, 'UPDATE' => 0, 'DELETE' => 0];
            foreach (array_keys($flags) as $k) { $flags[$k] = !empty($_POST['flags'][$k]) ? 1 : 0; }

            if ($errors) {
                set_old($_POST);
                view('modules/form', [
                    'title' => 'Modules', 'active' => 'modules', 'currentLink' => LINK_MODULES_MGMT,
                    'menu' => $menu, 'roleNames' => $roleNames,
                    'mode' => $do === 'update' ? 'edit' : 'create',
                    'link' => $do === 'update' ? (Database::selectOne('SELECT * FROM sa_module_links WHERE LINK_ID=?', [(int)$_POST['id']]) ?? []) : [],
                    'modules' => $modules, 'allLinks' => $allLinks, 'errors' => $errors,
                ]);
                exit;
            }

            $fields = 'LINK_NAME=?, LINK_NAME_BN=?, PR_LINK_ID=?, MODULE_ID=?, URL_URI=?, SL_NO=?, ACTIVE_STATUS=?,
                      `CREATE`=?, `READ`=?, `UPDATE`=?, `DELETE`=?, STATUS=1';
            $vals = [$name, $name, $pr, $moduleId, trim((string)($_POST['URL_URI'] ?? '#')),
                     (int)($_POST['SL_NO'] ?? 99), (int)($_POST['ACTIVE_STATUS'] ?? 1),
                     $flags['CREATE'], $flags['READ'], $flags['UPDATE'], $flags['DELETE']];

            if ($do === 'update') {
                $id = (int)$_POST['id'];
                Database::statement("UPDATE sa_module_links SET $fields, UPD_BY=?, UPD_DT=NOW() WHERE LINK_ID=?",
                    array_merge($vals, [$uid, $id]));
                Auth::log('update', 'modules', "Updated link #{$id} ({$name})");
            } else {
                $id = Database::insertGetId(
                    "INSERT INTO sa_module_links (LINK_NAME, LINK_NAME_BN, PR_LINK_ID, MODULE_ID, URL_URI, SL_NO,
                        ACTIVE_STATUS, `CREATE`, `READ`, `UPDATE`, `DELETE`, STATUS, CRE_BY, CRE_DT)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())",
                    array_merge($vals, [$uid]));
                // auto-create granular permission rows for the new link
                Database::statement(
                    'INSERT IGNORE INTO permissions (LINK_ID, ACTION_KEY, DISPLAY_NAME)
                     SELECT ?, a.k, CONCAT(?, " - ", UPPER(a.k))
                     FROM (SELECT "create" k UNION SELECT "read" UNION SELECT "update" UNION SELECT "delete") a',
                    [$id, $name]);
                Auth::log('create', 'modules', "Created link #{$id} ({$name})");
            }
            clear_old();
            flash('saved', 'Module link saved. Grant roles access from Permissions.');
            redirect('index.php?action=modules');
        }
    }

    if (($mode = $_GET['mode'] ?? '') === 'create' || $mode === 'edit') {
        Auth::authorize(LINK_MODULES_MGMT, $mode === 'edit' ? 'update' : 'create');
        $link = [];
        if ($mode === 'edit') {
            $link = Database::selectOne('SELECT * FROM sa_module_links WHERE LINK_ID=?', [(int)($_GET['id'] ?? 0)]) ?? [];
            if (!$link) { flash('nf', 'Link not found.', 'error'); redirect('index.php?action=modules'); }
        }
        view('modules/form', [
            'title' => 'Modules', 'active' => 'modules', 'currentLink' => LINK_MODULES_MGMT,
            'menu' => $menu, 'roleNames' => $roleNames, 'mode' => $mode, 'link' => $link,
            'modules' => $modules, 'allLinks' => $allLinks, 'errors' => [],
        ]);
        exit;
    }

    $links = Database::select('SELECT * FROM sa_module_links WHERE ACTIVE_STATUS = 1 ORDER BY MODULE_ID, SL_NO, LINK_ID');
    view('modules/index', [
        'title' => 'Modules', 'active' => 'modules', 'currentLink' => LINK_MODULES_MGMT,
        'menu' => $menu, 'roleNames' => $roleNames, 'modules' => $modules,
        'moduleNames' => $moduleNames, 'links' => $links,
    ]);
    exit;
}

/* ------------------------------------------------- activity ---- */

if ($action === 'activity') {
    Auth::authorize(LINK_ACTIVITY, 'read');
    $perPage = 25;
    $offset  = ($page - 1) * $perPage;
    $total   = (int)Database::selectOne('SELECT COUNT(*) c FROM activity_logs')['c'];
    $logs    = Database::select(
        'SELECT LOG_ID, USERNAME, ACTION, MODULE_LINK, DETAILS, IP_ADDRESS, CRE_DT
         FROM activity_logs ORDER BY LOG_ID DESC LIMIT ' . $perPage . ' OFFSET ' . $offset);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $do === 'purge') {
        Auth::authorize(LINK_ACTIVITY, 'delete');
        verify_csrf();
        Database::statement('DELETE FROM activity_logs WHERE CRE_DT < DATE_SUB(NOW(), INTERVAL 30 DAY)');
        Auth::log('delete', 'activity', 'Purged activity logs older than 30 days');
        flash('purged', 'Old activity logs purged.');
        redirect('index.php?action=activity');
    }
    view('activity/index', [
        'title' => 'Activity Log', 'active' => 'activity', 'currentLink' => LINK_ACTIVITY,
        'menu' => $menu, 'roleNames' => $roleNames, 'logs' => $logs, 'total' => $total,
        'page' => $page, 'perPage' => $perPage,
    ]);
    exit;
}

/* ------------------------------------------------- fallback ---- */
http_response_code(404);
view('components/forbidden', ['linkId' => 0, 'action' => htmlspecialchars($action)]);
