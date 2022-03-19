-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2022-02-21 08:32:43
-- 服务器版本： 10.4.22-MariaDB
-- PHP 版本： 7.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `mycloud`
--

-- --------------------------------------------------------

--
-- 表的结构 `mc_accounts`
--

CREATE TABLE `mc_accounts` (
  `uid` varchar(32) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nickname` varchar(32) DEFAULT '无名氏',
  `level` int(4) NOT NULL,
  `telephone` varchar(32) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `salt` varchar(32) NOT NULL,
  `lastlogintime` timestamp(4) NULL DEFAULT NULL,
  `createtime` varchar(128) NOT NULL,
  `logintoken` varchar(128) DEFAULT NULL,
  `tokenexpiretime` timestamp(4) NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `mc_accounts`
--



--
-- 转储表的索引
--

--
-- 表的索引 `mc_accounts`
--
ALTER TABLE `mc_accounts`
  ADD PRIMARY KEY (`uid`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
