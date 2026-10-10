-- =====================================================================
-- Migration 002 : Register the RBAC admin panel pages as module links so
-- menu visibility & CRUD permissions for the new screens are data-driven.
-- Idempotent (INSERT IGNORE on explicit LINK_IDs).
-- =====================================================================
USE `bnsis_db`;

INSERT IGNORE INTO `sa_module_links`
  (LINK_ID, PR_LINK_ID, LINK_NAME, LINK_NAME_BN, SA_MLINK_PAGES, MODULE_ID, URL_URI, LINK_DESC, SL_NO,
   `CREATE`, `READ`, `UPDATE`, `DELETE`, STATUS, ACTIVE_STATUS, CRE_BY, CRE_DT)
VALUES
  (300, NULL, 'Admin Dashboard',    'Admin Dashboard', 'I,V,U,D,S', 1, 'index.php?action=dashboard',   'RBAC overview dashboard', 20, 0,1,0,0, 1, 1, 1, NOW()),
  (301, NULL, 'Users',              'Users',           'I,V,U,D,S', 1, 'index.php?action=users',       'Dynamic user management', 21, 1,1,1,1, 1, 1, 1, NOW()),
  (302, NULL, 'Roles',              'Roles',           'I,V,U,D,S', 1, 'index.php?action=roles',       'Role management (CRUD)',  22, 1,1,1,1, 1, 1, 1, NOW()),
  (303, NULL, 'Role Permissions',   'Role Permissions','I,V,U,D,S', 1, 'index.php?action=permissions', 'Assign CRUD perms to roles', 23, 1,1,1,1, 1, 1, 1, NOW()),
  (304, NULL, 'Modules & Links',    'Modules & Links', 'I,V,U,D,S', 1, 'index.php?action=modules',     'Module / link catalogue', 24, 1,1,1,1, 1, 1, 1, NOW()),
  (305, NULL, 'Activity Log',       'Activity Log',    'I,V,U,D,S', 1, 'index.php?action=activity',    'Audit trail viewer',      25, 0,1,0,1, 1, 1, 1, NOW());

-- Backfill granular permission rows for the new links
INSERT IGNORE INTO `permissions` (LINK_ID, ACTION_KEY, DISPLAY_NAME)
SELECT l.LINK_ID, a.ACTION_KEY, CONCAT(l.LINK_NAME,' - ', UPPER(a.ACTION_KEY))
FROM `sa_module_links` l
JOIN (SELECT 'create' ACTION_KEY UNION SELECT 'read' UNION SELECT 'update' UNION SELECT 'delete') a
WHERE l.LINK_ID BETWEEN 300 AND 305;

-- Grant the new links to System Admin (1) and Super Admin (99) roles
INSERT IGNORE INTO `role_permissions` (USERGRP_ID, PERMISSION_ID, CRE_BY)
SELECT r.USERGRP_ID, p.PERMISSION_ID, 1
FROM `roles` r JOIN `permissions` p ON p.LINK_ID BETWEEN 300 AND 305
WHERE r.USERGRP_ID IN (1, 99);

-- Officers of DA get dashboard + users read access by default (demo scope)
INSERT IGNORE INTO `role_permissions` (USERGRP_ID, PERMISSION_ID, CRE_BY)
SELECT 3, p.PERMISSION_ID, 1 FROM `permissions` p
WHERE p.LINK_ID IN (300, 301) AND p.ACTION_KEY = 'read';

-- Data Entry role: dashboard read only
INSERT IGNORE INTO `role_permissions` (USERGRP_ID, PERMISSION_ID, CRE_BY)
SELECT 2, p.PERMISSION_ID, 1 FROM `permissions` p
WHERE p.LINK_ID = 300 AND p.ACTION_KEY = 'read';
