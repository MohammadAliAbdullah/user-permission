-- bnsis_permission full schema (generated Oct 11 2026)
/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.18-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: bnsis_permission
-- ------------------------------------------------------
-- Server version	10.11.18-MariaDB-0+deb12u1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `LOG_ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `USER_ID` bigint(20) DEFAULT NULL,
  `USERNAME` varchar(50) DEFAULT NULL,
  `ACTION` varchar(60) NOT NULL COMMENT 'login, create_user, update_role ...',
  `MODULE_LINK` varchar(120) DEFAULT NULL,
  `DETAILS` text DEFAULT NULL,
  `IP_ADDRESS` varchar(45) DEFAULT NULL,
  `CRE_DT` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`LOG_ID`),
  KEY `IDX_LOG_USER` (`USER_ID`),
  KEY `IDX_LOG_DT` (`CRE_DT`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Admin activity trail';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `PERMISSION_ID` int(11) NOT NULL AUTO_INCREMENT,
  `LINK_ID` int(11) NOT NULL COMMENT 'FK -> sa_module_links.LINK_ID',
  `ACTION_KEY` varchar(20) NOT NULL COMMENT 'create|read|update|delete',
  `DISPLAY_NAME` varchar(150) DEFAULT NULL,
  `ACTIVE_STATUS` tinyint(1) DEFAULT 1,
  `CRE_DT` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`PERMISSION_ID`),
  UNIQUE KEY `UQ_PERM_LINK_ACTION` (`LINK_ID`,`ACTION_KEY`),
  CONSTRAINT `FK_PERM_LINK` FOREIGN KEY (`LINK_ID`) REFERENCES `sa_module_links` (`LINK_ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1058 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Granular CRUD permissions derived from module links';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `ROLE_PERM_ID` int(11) NOT NULL AUTO_INCREMENT,
  `USERGRP_ID` int(11) NOT NULL,
  `PERMISSION_ID` int(11) NOT NULL,
  `ACTIVE_STATUS` tinyint(1) DEFAULT 1,
  `CRE_BY` int(10) unsigned DEFAULT NULL,
  `CRE_DT` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ROLE_PERM_ID`),
  UNIQUE KEY `UQ_ROLE_PERM` (`USERGRP_ID`,`PERMISSION_ID`),
  KEY `IDX_RP_PERM` (`PERMISSION_ID`),
  CONSTRAINT `FK_RP_PERM` FOREIGN KEY (`PERMISSION_ID`) REFERENCES `permissions` (`PERMISSION_ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_RP_ROLE` FOREIGN KEY (`USERGRP_ID`) REFERENCES `roles` (`USERGRP_ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2443 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Permissions granted to roles';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `USERGRP_ID` int(11) NOT NULL AUTO_INCREMENT,
  `ORG_ID` int(11) DEFAULT 1,
  `USERGRP_NAME` varchar(50) DEFAULT NULL,
  `DESCRIPTION` varchar(255) DEFAULT NULL COMMENT 'role purpose shown in UI',
  `IS_SYSTEM` tinyint(1) DEFAULT 0 COMMENT 'system roles cannot be deleted',
  `ACTIVE_STATUS` tinyint(1) DEFAULT 1,
  `CRE_BY` int(10) unsigned DEFAULT NULL,
  `CRE_DT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPD_BY` int(10) unsigned DEFAULT NULL,
  `UPD_DT` datetime DEFAULT NULL,
  PRIMARY KEY (`USERGRP_ID`),
  KEY `IDX_ROLE_ORG` (`ORG_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Dynamic roles (evolved from sa_user_group)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sa_module_links`
--

DROP TABLE IF EXISTS `sa_module_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sa_module_links` (
  `LINK_ID` int(11) NOT NULL AUTO_INCREMENT,
  `PR_LINK_ID` int(11) DEFAULT NULL,
  `LINK_NAME` varchar(60) NOT NULL,
  `LINK_NAME_BN` varchar(500) DEFAULT NULL,
  `SA_MLINK_PAGES` varchar(10) DEFAULT NULL,
  `MODULE_ID` int(11) DEFAULT NULL,
  `URL_URI` varchar(200) DEFAULT NULL,
  `LINK_DESC` varchar(100) DEFAULT NULL,
  `SL_NO` tinyint(4) NOT NULL,
  `CREATE` tinyint(1) DEFAULT 0,
  `READ` tinyint(1) DEFAULT 0,
  `UPDATE` tinyint(1) DEFAULT 0,
  `DELETE` tinyint(1) DEFAULT 0,
  `STATUS` tinyint(4) DEFAULT NULL,
  `ACTIVE_STATUS` tinyint(1) DEFAULT 1,
  `CRE_BY` int(10) unsigned DEFAULT NULL,
  `CRE_DT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPD_BY` int(10) unsigned DEFAULT NULL,
  `UPD_DT` datetime DEFAULT NULL,
  PRIMARY KEY (`LINK_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=306 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='All application pages link entry here ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sa_modules`
--

DROP TABLE IF EXISTS `sa_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sa_modules` (
  `MODULE_ID` int(11) NOT NULL AUTO_INCREMENT,
  `MODULE_NAME` varchar(70) NOT NULL,
  `SHORT_NAME` char(10) NOT NULL,
  `MODULE_NAME_BN` varchar(500) DEFAULT NULL,
  `SL_NO` tinyint(4) NOT NULL,
  `MODULE_ICON` varchar(100) DEFAULT NULL,
  `ACTIVE_STATUS` tinyint(1) DEFAULT 1,
  `CRE_BY` int(10) unsigned DEFAULT NULL,
  `CRE_DT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPD_BY` int(10) unsigned DEFAULT NULL,
  `UPD_DT` datetime DEFAULT NULL,
  PRIMARY KEY (`MODULE_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='DGDP Modules list entry here ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sa_org_mlinks`
--

DROP TABLE IF EXISTS `sa_org_mlinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sa_org_mlinks` (
  `SA_MLINKS_ID` int(11) NOT NULL AUTO_INCREMENT,
  `SA_MLINK_NAME` varchar(60) DEFAULT NULL,
  `SA_MLINK_PAGES` varchar(10) DEFAULT NULL,
  `SA_MODULE_ID` int(11) DEFAULT NULL,
  `ORG_ID` int(11) DEFAULT NULL,
  `LINK_ID` int(11) DEFAULT NULL,
  `URL_URI` varchar(200) DEFAULT NULL,
  `CREATE` tinyint(1) DEFAULT 0,
  `READ` tinyint(1) DEFAULT 0,
  `UPDATE` tinyint(1) DEFAULT 0,
  `DELETE` tinyint(1) DEFAULT 0,
  `STATUS` tinyint(4) DEFAULT NULL,
  `ACTIVE_STATUS` tinyint(1) DEFAULT 1,
  `CRE_BY` int(10) unsigned DEFAULT NULL,
  `CRE_DT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPD_BY` int(10) unsigned DEFAULT NULL,
  `UPD_DT` datetime DEFAULT NULL,
  PRIMARY KEY (`SA_MLINKS_ID`),
  KEY `FK02_ORG_ID` (`ORG_ID`),
  KEY `FK01_SA_MODULE_ID` (`SA_MODULE_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=250 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='DGDP organizational modules wise link assign ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sa_org_modules`
--

DROP TABLE IF EXISTS `sa_org_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sa_org_modules` (
  `SA_MODULE_ID` int(11) NOT NULL AUTO_INCREMENT,
  `SA_MODULE_NAME` varchar(70) NOT NULL,
  `ORG_ID` int(11) DEFAULT NULL,
  `DEFAULT_FLAG` tinyint(1) DEFAULT 1,
  `MODULE_ID` int(11) DEFAULT NULL,
  `ACTIVE_STATUS` tinyint(1) DEFAULT 1,
  `CRE_BY` int(10) unsigned DEFAULT NULL,
  `CRE_DT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPD_BY` int(10) unsigned DEFAULT NULL,
  `UPD_DT` datetime DEFAULT NULL,
  PRIMARY KEY (`SA_MODULE_ID`),
  KEY `FK01_ORG_ID` (`ORG_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='DGDP organizational wise existing modules assign ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sa_organizations`
--

DROP TABLE IF EXISTS `sa_organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sa_organizations` (
  `ORG_ID` int(11) NOT NULL AUTO_INCREMENT,
  `ORG_NAME` varchar(100) NOT NULL,
  `PARENT_ORG_ID` int(11) DEFAULT NULL,
  `ORG_TYPE_ID` int(11) DEFAULT NULL,
  `SLOGAN` varchar(200) DEFAULT NULL,
  `ABBR` varchar(8) DEFAULT NULL,
  `DSCP` text DEFAULT NULL,
  `ESTB_DT` date DEFAULT NULL,
  `ADDR1` varchar(160) DEFAULT NULL,
  `ADDR2` varchar(160) DEFAULT NULL,
  `ZIP_CODE` varchar(8) DEFAULT NULL,
  `TEL` varchar(80) DEFAULT NULL,
  `PHONE` varchar(80) DEFAULT NULL,
  `FAX` varchar(80) DEFAULT NULL,
  `EMAIL` varchar(40) DEFAULT NULL,
  `WEBSITE` varchar(40) DEFAULT NULL,
  `LOGO` varchar(100) DEFAULT NULL,
  `ACTIVE_STATUS` tinyint(1) DEFAULT 1,
  `CRE_BY` int(10) unsigned DEFAULT NULL,
  `CRE_DT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPD_BY` int(10) unsigned DEFAULT NULL,
  `UPD_DT` datetime DEFAULT NULL,
  PRIMARY KEY (`ORG_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='DGDP organizational basic information ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sa_ug_level`
--

DROP TABLE IF EXISTS `sa_ug_level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sa_ug_level` (
  `UG_LEVEL_ID` int(11) NOT NULL AUTO_INCREMENT,
  `USERGRP_ID` int(11) DEFAULT NULL,
  `ORG_ID` int(11) DEFAULT NULL,
  `UGLEVE_NAME` varchar(50) DEFAULT NULL,
  `ORDER_SL` tinyint(4) DEFAULT NULL,
  `ACTIVE_STATUS` tinyint(1) DEFAULT 1,
  `CRE_BY` int(10) unsigned DEFAULT NULL,
  `CRE_DT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPD_BY` int(10) unsigned DEFAULT NULL,
  `UPD_DT` datetime DEFAULT NULL,
  PRIMARY KEY (`UG_LEVEL_ID`),
  KEY `FK04_ORG_ID` (`ORG_ID`),
  KEY `FK01_USERGRP_ID` (`USERGRP_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Organization wise user level define for access application ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sa_uglw_mlink`
--

DROP TABLE IF EXISTS `sa_uglw_mlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sa_uglw_mlink` (
  `SA_UGLWM_LINK` int(11) NOT NULL AUTO_INCREMENT,
  `SA_MLINKS_ID` int(11) DEFAULT NULL,
  `ORG_ID` int(11) DEFAULT NULL,
  `USER_ID` bigint(20) DEFAULT NULL,
  `USERGRP_ID` int(11) DEFAULT NULL,
  `UG_LEVEL_ID` int(11) DEFAULT NULL,
  `SA_MODULE_ID` int(11) DEFAULT NULL,
  `LINK_ID` int(11) DEFAULT NULL,
  `CREATE` tinyint(1) DEFAULT 0,
  `READ` tinyint(1) DEFAULT 0,
  `UPDATE` tinyint(1) DEFAULT 0,
  `DELETE` tinyint(1) DEFAULT 0,
  `STATUS` tinyint(4) DEFAULT NULL,
  `URL_URI` varchar(200) DEFAULT NULL,
  `ACTIVE_STATUS` tinyint(1) DEFAULT 1,
  `CRE_BY` int(10) unsigned DEFAULT NULL,
  `CRE_DT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPD_BY` int(10) unsigned DEFAULT NULL,
  `UPD_DT` datetime DEFAULT NULL,
  PRIMARY KEY (`SA_UGLWM_LINK`),
  KEY `FK01_USER_ID` (`USER_ID`),
  KEY `FK02_SA_MODULE_ID` (`SA_MODULE_ID`),
  KEY `FK01_SA_MLINKS_ID` (`SA_MLINKS_ID`),
  KEY `FK02_USERGRP_ID` (`USERGRP_ID`),
  KEY `FK01_UG_LEVEL_ID` (`UG_LEVEL_ID`),
  KEY `FK05_ORG_ID` (`ORG_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=1025 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='User level wise module link define ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sa_ugw_mlink`
--

DROP TABLE IF EXISTS `sa_ugw_mlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sa_ugw_mlink` (
  `SA_UGWM_LINK` int(11) NOT NULL AUTO_INCREMENT,
  `SA_MODULE_ID` int(11) DEFAULT NULL,
  `SA_MLINKS_ID` int(11) DEFAULT NULL,
  `LINK_ID` int(11) DEFAULT NULL,
  `USERGRP_ID` int(11) DEFAULT NULL,
  `URL_URI` varchar(200) DEFAULT NULL,
  `ORG_ID` int(11) DEFAULT NULL,
  `ACTIVE_STATUS` tinyint(1) DEFAULT 1,
  `CRE_BY` int(10) unsigned DEFAULT NULL,
  `CRE_DT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPD_BY` int(10) unsigned DEFAULT NULL,
  `UPD_DT` datetime DEFAULT NULL,
  PRIMARY KEY (`SA_UGWM_LINK`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='User group wise modules assign ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sa_user_group`
--

DROP TABLE IF EXISTS `sa_user_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sa_user_group` (
  `USERGRP_ID` int(11) NOT NULL AUTO_INCREMENT,
  `ORG_ID` int(11) DEFAULT NULL,
  `USERGRP_NAME` varchar(50) DEFAULT NULL,
  `ACTIVE_STATUS` tinyint(1) DEFAULT 1,
  `CRE_BY` int(10) unsigned DEFAULT NULL,
  `CRE_DT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPD_BY` int(10) unsigned DEFAULT NULL,
  `UPD_DT` datetime DEFAULT NULL,
  PRIMARY KEY (`USERGRP_ID`),
  KEY `FK03_ORG_ID` (`ORG_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Application user divided into multiple group ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sa_user_wmgl`
--

DROP TABLE IF EXISTS `sa_user_wmgl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sa_user_wmgl` (
  `USRWMGL_ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `USER_ID` bigint(20) NOT NULL,
  `ORG_ID` int(11) NOT NULL,
  `USERGRP_ID` int(11) NOT NULL,
  `USERLVL_ID` int(11) NOT NULL,
  `DEFAULT_FG` tinyint(1) DEFAULT 0,
  `ACTIVE_STATUS` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`USRWMGL_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Application user wise link assign ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sa_users`
--

DROP TABLE IF EXISTS `sa_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sa_users` (
  `USER_ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `FULL_NAME` varchar(100) DEFAULT NULL,
  `USERNAME` varchar(16) DEFAULT NULL,
  `USERPW` varchar(60) NOT NULL,
  `EFECT_FROM_DT` date DEFAULT NULL COMMENT 'PASSWORD effective from date',
  `EXPR_DT` date DEFAULT NULL COMMENT 'PASSWORD effective expire date',
  `ORG_ID` int(11) DEFAULT NULL,
  `USERGRP_ID` int(11) DEFAULT NULL,
  `USERLVL_ID` int(11) DEFAULT NULL,
  `USER_ENTITY` varchar(4) DEFAULT 'U',
  `EMAIL` varchar(40) DEFAULT NULL,
  `USERIMG` varchar(100) DEFAULT NULL,
  `ACTIVE_STATUS` tinyint(1) DEFAULT 1,
  `CRE_DT` timestamp NOT NULL DEFAULT current_timestamp(),
  `CRE_BY` int(10) unsigned DEFAULT NULL,
  `UPD_DT` datetime DEFAULT NULL,
  `UPD_BY` int(10) unsigned DEFAULT NULL,
  `LAST_LOGIN_DT` datetime DEFAULT NULL,
  PRIMARY KEY (`USER_ID`),
  KEY `sa_users_ibfk_1` (`USERLVL_ID`),
  KEY `sa_users_ibfk_2` (`USERGRP_ID`),
  KEY `sa_users_ibfk_3` (`ORG_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=905 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Basic information of application user ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roles` (
  `USER_ROLE_ID` int(11) NOT NULL AUTO_INCREMENT,
  `USER_ID` bigint(20) NOT NULL,
  `USERGRP_ID` int(11) NOT NULL,
  `DEFAULT_FG` tinyint(1) DEFAULT 1 COMMENT 'primary role of the user',
  `ACTIVE_STATUS` tinyint(1) DEFAULT 1,
  `CRE_BY` int(10) unsigned DEFAULT NULL,
  `CRE_DT` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`USER_ROLE_ID`),
  UNIQUE KEY `UQ_USER_ROLE` (`USER_ID`,`USERGRP_ID`),
  KEY `IDX_UR_ROLE` (`USERGRP_ID`),
  CONSTRAINT `FK_UR_ROLE` FOREIGN KEY (`USERGRP_ID`) REFERENCES `roles` (`USERGRP_ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_UR_USER` FOREIGN KEY (`USER_ID`) REFERENCES `sa_users` (`USER_ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=138 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Users assigned to roles';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `userdaogroup`
--

DROP TABLE IF EXISTS `userdaogroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `userdaogroup` (
  `DAOGroupID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ACTIVE_STATUS` int(11) DEFAULT 1,
  `CRE_BY` int(11) DEFAULT NULL,
  `CRE_DT` datetime DEFAULT current_timestamp(),
  `UPD_BY` int(11) DEFAULT NULL,
  `UPD_DT` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usergroups`
--

DROP TABLE IF EXISTS `usergroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usergroups` (
  `GroupID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ACTIVE_STATUS` int(11) DEFAULT 1,
  `CRE_BY` int(11) DEFAULT NULL,
  `CRE_DT` datetime DEFAULT current_timestamp(),
  `UPD_BY` int(11) DEFAULT NULL,
  `UPD_DT` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `UserID` int(11) NOT NULL AUTO_INCREMENT,
  `LoginID` varchar(50) NOT NULL,
  `UserName1` varchar(100) NOT NULL,
  `Password` varchar(100) NOT NULL,
  `OwnerID` int(11) NOT NULL,
  `IsActive` int(11) DEFAULT 0,
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-10 16:31:56
