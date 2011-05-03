/*
MySQL Backup
Source Server Version: 5.1.45
Source Database: test
Date: 7.04.2011 19:40:15
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
--  Table structure for `journal`
-- ----------------------------
DROP TABLE IF EXISTS `journal`;
CREATE TABLE `journal` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `refID` bigint(20) NOT NULL,
  `refTypeID` int(11) NOT NULL,
  `ownerName1` varchar(255) NOT NULL,
  `ownerID1` int(11) NOT NULL,
  `ownerName2` varchar(255) NOT NULL,
  `ownerID2` int(11) NOT NULL,
  `argName1` varchar(255) NOT NULL,
  `argID1` int(11) NOT NULL,
  `amount` decimal(20,2) NOT NULL,
  `balance` decimal(20,2) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `corpTax` decimal(5,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`id`),
  UNIQUE KEY `refID` (`refID`) USING BTREE,
  KEY `date` (`date`) USING BTREE,
  KEY `refTypeID` (`refTypeID`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `rats`
-- ----------------------------
DROP TABLE IF EXISTS `rats`;
CREATE TABLE `rats` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `journal_id` bigint(10) unsigned NOT NULL,
  `rat` bigint(20) NOT NULL,
  `amount` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `journal_rats` (`journal_id`),
  KEY `rat_id` (`rat`),
  CONSTRAINT `journal_rats` FOREIGN KEY (`journal_id`) REFERENCES `journal` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Records 
-- ----------------------------
