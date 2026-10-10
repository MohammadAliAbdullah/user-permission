<?php
/**
 * page.php?link=<LINK_ID> — dynamic router for module links.
 * Admin-panel links (300-305) map to index.php actions; anything else
 * renders a permission-aware placeholder so new links are immediately usable.
 */
declare(strict_types=1);

require __DIR__ . '/../config/settings.php';
require APP_ROOT . '/config/links.php';
require APP_ROOT . '/app/helpers/Auth.php';

Auth::start();
Auth::requireLogin();

$linkId = (int)($_GET['link'] ?? 0);
$link = Database::selectOne(
    'SELECT l.*, m.MODULE_NAME FROM sa_module_links l
     JOIN sa_modules m ON m.MODULE_ID = l.MODULE_ID
     WHERE l.LINK_ID = ? AND l.ACTIVE_STATUS = 1', [$linkId]);

$roleNames = array_map(fn($r) => $r['USERGRP_NAME'], Database::select(
    'SELECT r.USERGRP_NAME FROM roles r JOIN user_roles ur ON ur.USERGRP_ID = r.USERGRP_ID
     WHERE ur.USER_ID = ? AND ur.ACTIVE_STATUS = 1 ORDER BY r.USERGRP_NAME',
    [(int)Auth::user()['USER_ID']]));
$menu = Auth::menu();

if (!$link) {
    view('components/forbidden', [
        'title' => 'Not found', 'menu' => $menu, 'roleNames' => $roleNames,
        'currentLink' => $linkId, 'linkId' => $linkId, 'action' => 'read',
    ]);
    exit;
}

// Gate: must have at least READ on this link
if (!\Auth::canAny($linkId)) {
    http_response_code(403);
    view('components/forbidden', [
        'title' => 'Access denied', 'menu' => $menu, 'roleNames' => $roleNames,
        'currentLink' => $linkId, 'linkId' => $linkId, 'action' => 'read',
    ]);
    exit;
}

// Map the RBAC admin links onto their real screens
$actionMap = [
    LINK_DASHBOARD => 'dashboard', LINK_USERS => 'users', LINK_ROLES => 'roles',
    LINK_PERMS => 'permissions', LINK_MODULES_MGMT => 'modules', LINK_ACTIVITY => 'activity',
];
if (isset($actionMap[$linkId])) {
    redirect('index.php?action=' . $actionMap[$linkId]);
}

view('partials/generic_page', [
    'title' => $link['LINK_NAME'], 'active' => '', 'currentLink' => $linkId,
    'menu' => $menu, 'roleNames' => $roleNames, 'link' => $link,
]);
