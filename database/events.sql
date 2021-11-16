/*
Navicat MySQL Data Transfer

Source Server         : Localhost
Source Server Version : 50726
Source Host           : localhost:3306
Source Database       : rexx_system

Target Server Type    : MYSQL
Target Server Version : 50726
File Encoding         : 65001

Date: 2021-11-16 23:52:17
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `events`
-- ----------------------------
DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
  `participation_id` int(10) NOT NULL,
  `employee_name` varchar(100) DEFAULT NULL,
  `employee_mail` varchar(100) DEFAULT NULL,
  `event_id` int(10) DEFAULT NULL,
  `event_name` varchar(100) DEFAULT NULL,
  `participation_fee` decimal(10,2) DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `version` varchar(15) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`participation_id`),
  KEY `EMP_NAME_INDEX` (`employee_name`) USING HASH,
  KEY `EVENT_NAME_INDEX` (`event_name`) USING HASH,
  KEY `EVENT_DATE_INDEX` (`event_date`) USING HASH
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
