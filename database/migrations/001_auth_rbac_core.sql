-- =====================================================================
-- Migration 001 : Auth & RBAC core for bnsis_db
-- Dynamic User Role & Permission Management Module
-- ADDITIVE ONLY: creates new tables, adds compatible columns/indexes,
-- seeds roles/permissions and backfills links. Never deletes data.
-- Idempotent: safe to run multiple times.
-- =====================================================================
CREATE DATABASE IF NOT EXISTS `bnsis_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bnsis_db`;

-- ---------------------------------------------------------------
-- 1. Roles (maps onto legacy sa_user_group; USERGRP_ID kept as PK)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `USERGRP_ID` int NOT NULL AUTO_INCREMENT,
  `ORG_ID` int DEFAULT 1,
  `USERGRP_NAME` varchar(50) DEFAULT NULL,
  `DESCRIPTION` varchar(255) DEFAULT NULL COMMENT 'role purpose shown in UI',
  `IS_SYSTEM` tinyint(1) DEFAULT '0' COMMENT 'system roles cannot be deleted',
  `ACTIVE_STATUS` tinyint(1) DEFAULT '1',
  `CRE_BY` int unsigned DEFAULT NULL,
  `CRE_DT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UPD_BY` int unsigned DEFAULT NULL,
  `UPD_DT` datetime DEFAULT NULL,
  PRIMARY KEY (`USERGRP_ID`),
  KEY `IDX_ROLE_ORG` (`ORG_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Dynamic roles (evolved from sa_user_group)';

-- Seed / sync roles from legacy group table (INSERT IGNORE keeps ids stable)
INSERT IGNORE INTO `roles`
  (USERGRP_ID, ORG_ID, USERGRP_NAME, DESCRIPTION, IS_SYSTEM, ACTIVE_STATUS, CRE_BY, CRE_DT)
SELECT g.USERGRP_ID, COALESCE(g.ORG_ID,1), g.USERGRP_NAME,
       CONCAT('Migrated from user group #', g.USERGRP_ID), 0, COALESCE(g.ACTIVE_STATUS,1), 1, NOW()
FROM `sa_user_group` g;

-- Ensure a Super Admin role exists (id 99 = bypass-all flag used by app)
INSERT IGNORE INTO `roles` (USERGRP_ID, ORG_ID, USERGRP_NAME, DESCRIPTION, IS_SYSTEM, ACTIVE_STATUS, CRE_BY, CRE_DT)
VALUES (99, 1, 'Super Admin', 'Unrestricted system owner - bypasses all permission checks', 1, 1, 1, NOW());

-- ---------------------------------------------------------------
-- 2. Permissions (CRUD actions per module link)
--    Maps onto sa_module_links.CREATE/READ/UPDATE/DELETE flags.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
  `PERMISSION_ID` int NOT NULL AUTO_INCREMENT,
  `LINK_ID` int NOT NULL COMMENT 'FK -> sa_module_links.LINK_ID',
  `ACTION_KEY` varchar(20) NOT NULL COMMENT 'create|read|update|delete',
  `DISPLAY_NAME` varchar(150) DEFAULT NULL,
  `ACTIVE_STATUS` tinyint(1) DEFAULT '1',
  `CRE_DT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`PERMISSION_ID`),
  UNIQUE KEY `UQ_PERM_LINK_ACTION` (`LINK_ID`,`ACTION_KEY`),
  CONSTRAINT `FK_PERM_LINK` FOREIGN KEY (`LINK_ID`) REFERENCES `sa_module_links`(`LINK_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Granular CRUD permissions derived from module links';

-- Backfill permissions from every active module link
INSERT IGNORE INTO `permissions` (LINK_ID, ACTION_KEY, DISPLAY_NAME)
SELECT l.LINK_ID, a.ACTION_KEY, CONCAT(l.LINK_NAME,' - ', UPPER(a.ACTION_KEY))
FROM `sa_module_links` l
JOIN (SELECT 'create' ACTION_KEY UNION SELECT 'read' UNION SELECT 'update' UNION SELECT 'delete') a
WHERE l.ACTIVE_STATUS = 1;

-- ---------------------------------------------------------------
-- 3. Role <-> Permission assignment
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `ROLE_PERM_ID` int NOT NULL AUTO_INCREMENT,
  `USERGRP_ID` int NOT NULL,
  `PERMISSION_ID` int NOT NULL,
  `ACTIVE_STATUS` tinyint(1) DEFAULT '1',
  `CRE_BY` int unsigned DEFAULT NULL,
  `CRE_DT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ROLE_PERM_ID`),
  UNIQUE KEY `UQ_ROLE_PERM` (`USERGRP_ID`,`PERMISSION_ID`),
  KEY `IDX_RP_PERM` (`PERMISSION_ID`),
  CONSTRAINT `FK_RP_ROLE` FOREIGN KEY (`USERGRP_ID`) REFERENCES `roles`(`USERGRP_ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_RP_PERM` FOREIGN KEY (`PERMISSION_ID`) REFERENCES `permissions`(`PERMISSION_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Permissions granted to roles';

-- ---------------------------------------------------------------
-- 4. User <-> Role assignment (many users may share a role)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_roles` (
  `USER_ROLE_ID` int NOT NULL AUTO_INCREMENT,
  `USER_ID` bigint NOT NULL,
  `USERGRP_ID` int NOT NULL,
  `DEFAULT_FG` tinyint(1) DEFAULT '1' COMMENT 'primary role of the user',
  `ACTIVE_STATUS` tinyint(1) DEFAULT '1',
  `CRE_BY` int unsigned DEFAULT NULL,
  `CRE_DT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`USER_ROLE_ID`),
  UNIQUE KEY `UQ_USER_ROLE` (`USER_ID`,`USERGRP_ID`),
  KEY `IDX_UR_ROLE` (`USERGRP_ID`),
  CONSTRAINT `FK_UR_USER` FOREIGN KEY (`USER_ID`) REFERENCES `sa_users`(`USER_ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_UR_ROLE` FOREIGN KEY (`USERGRP_ID`) REFERENCES `roles`(`USERGRP_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Users assigned to roles';

-- Backfill from sa_users.USERGRP_ID so no user loses their current role
INSERT IGNORE INTO `user_roles` (USER_ID, USERGRP_ID, DEFAULT_FG, ACTIVE_STATUS, CRE_BY)
SELECT u.USER_ID, u.USERGRP_ID, 1, COALESCE(u.ACTIVE_STATUS,1), 1
FROM `sa_users` u WHERE u.USERGRP_ID IS NOT NULL;

-- ---------------------------------------------------------------
-- 5. Audit log
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `LOG_ID` bigint NOT NULL AUTO_INCREMENT,
  `USER_ID` bigint DEFAULT NULL,
  `USERNAME` varchar(50) DEFAULT NULL,
  `ACTION` varchar(60) NOT NULL COMMENT 'login, create_user, update_role ...',
  `MODULE_LINK` varchar(120) DEFAULT NULL,
  `DETAILS` text,
  `IP_ADDRESS` varchar(45) DEFAULT NULL,
  `CRE_DT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`LOG_ID`),
  KEY `IDX_LOG_USER` (`USER_ID`),
  KEY `IDX_LOG_DT` (`CRE_DT`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Admin activity trail';

-- ---------------------------------------------------------------
-- 6. Compatible column additions on legacy tables (nullable, no data loss)
-- ---------------------------------------------------------------
SET @c := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA='bnsis_db' AND TABLE_NAME='sa_users' AND COLUMN_NAME='LAST_LOGIN_DT')=0,
   "ALTER TABLE `sa_users` ADD COLUMN `LAST_LOGIN_DT` datetime NULL", "SELECT 'sa_users.LAST_LOGIN_DT exists'");
PREPARE s FROM @c; EXECUTE s; DEALLOCATE PREPARE s;

-- NOTE: legacy data contains duplicate USERNAME values (e.g. 'Moinul'), so a
-- UNIQUE index cannot be enforced without touching existing rows. The
-- application layer guards uniqueness on the new module's forms instead.

-- ---------------------------------------------------------------
-- 7. Grant ALL permissions to System Admin + Super Admin roles
-- ---------------------------------------------------------------
INSERT IGNORE INTO `role_permissions` (USERGRP_ID, PERMISSION_ID, CRE_BY)
SELECT r.USERGRP_ID, p.PERMISSION_ID, 1
FROM `roles` r JOIN `permissions` p
WHERE r.USERGRP_ID IN (1, 99);

-- ---------------------------------------------------------------
-- 8. Demo credentials (only when absent). Passwords are MD5 to match
--    legacy hashes already present in this database.
--    admin / admin123        (System Administrator + Super Admin)
--    officer / 123456        (Officer of DA - Security&Access rights)
--    entry   / 123456        (Data Entry User - read-only)
-- ---------------------------------------------------------------
INSERT IGNORE INTO `sa_users`
  (USER_ID, FULL_NAME, USERNAME, USERPW, EFECT_FROM_DT, ORG_ID, USERGRP_ID, USERLVL_ID, USER_ENTITY, EMAIL, ACTIVE_STATUS, CRE_BY, CRE_DT)
VALUES
  (901, 'System Owner (Admin)', 'admin',   MD5('admin123'), CURDATE(), 1, 1, 1, 'U', 'admin@bnsis.local',   1, 1, NOW()),
  (902, 'Demo Officer',         'officer', MD5('123456'),   CURDATE(), 1, 3, 4, 'U', 'officer@bnsis.local', 1, 1, NOW()),
  (903, 'Demo Data Entry',      'entry',   MD5('123456'),   CURDATE(), 1, 2, 3, 'U', 'entry@bnsis.local',   1, 1, NOW());

INSERT IGNORE INTO `user_roles` (USER_ID, USERGRP_ID, DEFAULT_FG, ACTIVE_STATUS, CRE_BY) VALUES
  (901, 1, 1, 1, 1),
  (901, 99, 0, 1, 1),
  (902, 3, 1, 1, 1),
  (903, 2, 1, 1, 1);

-- Give "Officer of DA" (role 3) Security&Access module permissions (demo scope)
INSERT IGNORE INTO `role_permissions` (USERGRP_ID, PERMISSION_ID, CRE_BY)
SELECT 3, p.PERMISSION_ID, 1 FROM `permissions` p
JOIN `sa_module_links` l ON l.LINK_ID = p.LINK_ID
WHERE l.MODULE_ID = 1;

-- Give "Data Entry User" (role 2) read-only on everything
INSERT IGNORE INTO `role_permissions` (USERGRP_ID, PERMISSION_ID, CRE_BY)
SELECT 2, p.PERMISSION_ID, 1 FROM `permissions` p WHERE p.ACTION_KEY = 'read';
