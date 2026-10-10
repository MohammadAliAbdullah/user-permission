<?php
/**
 * Module-link identifiers used by the Dynamic RBAC admin panel.
 * These correspond to rows in sa_module_links (see migration 002) so that
 * permission checks & menu visibility are fully data-driven.
 */
declare(strict_types=1);

define('LINK_DASHBOARD',   300); // Security & Access > Admin Dashboard
define('LINK_USERS',       301); // Security & Access > Users
define('LINK_ROLES',       302); // Security & Access > Roles
define('LINK_PERMS',       303); // Security & Access > Role Permissions
define('LINK_MODULES_MGMT',304); // Security & Access > Modules & Links
define('LINK_ACTIVITY',    305); // Security & Access > Activity Log

/** All links managed by this module (used for seeding / validation). */
const ADMIN_LINKS = [LINK_DASHBOARD, LINK_USERS, LINK_ROLES, LINK_PERMS, LINK_MODULES_MGMT, LINK_ACTIVITY];
