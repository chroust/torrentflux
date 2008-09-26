-- phpMyAdmin SQL Dump
-- version 2.11.6
-- http://www.phpmyadmin.net
--
-- 主機: localhost
-- 建立日期: Sep 22, 2008, 10:11 PM
-- 伺服器版本: 5.0.45
-- PHP 版本: 5.2.3-1ubuntu6.4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- 資料庫: `torrentflux`
--

-- --------------------------------------------------------

--
-- 資料表格式： `tf_cookies`
--

CREATE TABLE IF NOT EXISTS `tf_cookies` (
  `cid` int(10) NOT NULL auto_increment,
  `uid` int(10) NOT NULL,
  `host` varchar(255) default NULL,
  `data` varchar(255) default NULL,
  PRIMARY KEY  (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- 列出以下資料庫的數據： `tf_cookies`
--


-- --------------------------------------------------------

--
-- 資料表格式： `tf_links`
--

CREATE TABLE IF NOT EXISTS `tf_links` (
  `lid` int(10) NOT NULL auto_increment,
  `url` varchar(255) NOT NULL default '',
  `sitename` varchar(255) NOT NULL default 'Old Link',
  `sort_order` tinyint(3) unsigned default '0',
  PRIMARY KEY  (`lid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- 列出以下資料庫的數據： `tf_links`
--


-- --------------------------------------------------------

--
-- 資料表格式： `tf_log`
--

CREATE TABLE IF NOT EXISTS `tf_log` (
  `cid` int(14) NOT NULL auto_increment,
  `user_id` varchar(32) NOT NULL default '',
  `file` varchar(200) NOT NULL default '',
  `action` varchar(200) NOT NULL default '',
  `ip` varchar(15) NOT NULL default '',
  `ip_resolved` varchar(200) NOT NULL default '',
  `user_agent` varchar(200) NOT NULL default '',
  `time` varchar(14) NOT NULL default '0',
  PRIMARY KEY  (`cid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=261 ;

--
-- 列出以下資料庫的數據： `tf_log`
--


-- --------------------------------------------------------

--
-- 資料表格式： `tf_messages`
--

CREATE TABLE IF NOT EXISTS `tf_messages` (
  `mid` int(10) NOT NULL auto_increment,
  `to_user` varchar(32) NOT NULL default '',
  `from_user` varchar(32) NOT NULL default '',
  `message` text,
  `IsNew` int(11) default NULL,
  `ip` varchar(15) NOT NULL default '',
  `time` varchar(14) NOT NULL default '0',
  `force_read` tinyint(1) default '0',
  PRIMARY KEY  (`mid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- 列出以下資料庫的數據： `tf_messages`
--


-- --------------------------------------------------------

--
-- 資料表格式： `tf_queue`
--

CREATE TABLE IF NOT EXISTS `tf_queue` (
  `qid` int(10) NOT NULL auto_increment,
  `torrentid` int(10) NOT NULL,
  `uid` int(10) NOT NULL,
  PRIMARY KEY  (`qid`),
  UNIQUE KEY `torrentid` (`torrentid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- 列出以下資料庫的數據： `tf_queue`
--


-- --------------------------------------------------------

--
-- 資料表格式： `tf_rss`
--

CREATE TABLE IF NOT EXISTS `tf_rss` (
  `rid` int(10) NOT NULL auto_increment,
  `url` varchar(255) NOT NULL default '',
  `timestamp` int(10) NOT NULL,
  `uid` int(10) NOT NULL,
  `lastitem` varchar(300) NOT NULL,
  `autostart` enum('1','0') NOT NULL,
  `title` varchar(100) NOT NULL,
  PRIMARY KEY  (`rid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- 列出以下資料庫的數據： `tf_rss`
--


-- --------------------------------------------------------

--
-- 資料表格式： `tf_settings`
--

CREATE TABLE IF NOT EXISTS `tf_settings` (
  `tf_key` varchar(255) NOT NULL default '',
  `tf_value` text NOT NULL,
  UNIQUE KEY `tf_key` (`tf_key`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- 列出以下資料庫的數據： `tf_settings`
--

INSERT INTO `tf_settings` (`tf_key`, `tf_value`) VALUES
('path', '/path/to/download/dir'),
('btphpbin', '/path/to/btphptornado.py'),
('btshowmetainfo', '/path/to/btshowmetainfo.py'),
('advanced_start', '1'),
('max_upload_rate', '10'),
('max_download_rate', '0'),
('max_uploads', '4'),
('minport', '49300'),
('maxport', '49320'),
('rerequest_interval', '1800'),
('cmd_options', ''),
('enable_search', '1'),
('enable_file_download', '1'),
('enable_view_nfo', '1'),
('package_type', 'zip'),
('show_server_load', '1'),
('loadavg_path', '/proc/loadavg'),
('days_to_keep', '30'),
('minutes_to_keep', '3'),
('rss_cache_min', '20'),
('page_refresh', '10'),
('default_theme', 'G4E'),
('default_language', 'lang-english.php'),
('debug_sql', '1'),
('torrent_dies_when_done', 'False'),
('sharekill', '150'),
('tfQManager', '/path/to/tfQManager.py'),
('AllowQueing', '1'),
('maxServerThreads', '99'),
('sleepInterval', '10'),
('debugTorrents', '0'),
('pythonCmd', '/usr/bin/python'),
('searchEngine', 'PirateBay'),
('TorrentSpyGenreFilter', 'a:3:{i:0;s:2:"11";i:1;s:1:"6";i:2;s:1:"7";}'),
('TorrentBoxGenreFilter', 'a:3:{i:0;s:1:"0";i:1;s:1:"9";i:2;s:2:"10";}'),
('TorrentPortalGenreFilter', 'a:3:{i:0;s:1:"0";i:1;s:1:"6";i:2;s:2:"10";}'),
('enable_maketorrent', '0'),
('btmakemetafile', '/path/to/btmakemetafile.py'),
('enable_torrent_download', '1'),
('enable_file_priority', '1'),
('security_code', '0'),
('continue', 'configSettings'),
('searchEngineLinks', 'a:6:{s:6:"Google";s:10:"google.com";s:7:"isoHunt";s:11:"isohunt.com";s:8:"mininova";s:12:"mininova.org";s:9:"PirateBay";s:16:"thepiratebay.org";s:10:"TorrentBox";s:14:"torrentbox.com";s:13:"TorrentPortal";s:17:"torrentportal.com";}'),
('Confirm_Del_tor', '1'),
('speed_in_title', '1'),
('display_tor_search', '1'),
('show_ser_load', '0'),
('show_disk_space', '0'),
('refresh_interval', '5'),
('auto_start', '1'),
('smart_select', '1'),
('enable_file_dl', '1'),
('enable_dl_compress_dir', '1'),
('allow_text_preview', '0'),
('allow_pic_preview', '0'),
('allow_video_preview', '0'),
('limitminport', '49300'),
('limitmaxport', '49305'),
('force_dl_in_home_dir', '1'),
('crypto_allowed', '1'),
('crypto_only', '1'),
('maxServerDlThreads', '99'),
('totalupload', '21.4'),
('totaldownload', '23.7'),
('totalseed', '7'),
('totalpeers', '63'),
('totalfinished', '2'),
('totaldownloading', '4'),
('totalactive', '5'),
('totalinactive', '1'),
('global_transferlimit_period', '0'),
('global_finished_command', ''),
('global_transferlimit_number', '0'),
('maxServerSeedThreads', '99'),
('maxUserThreads', '99'),
('totalseeding', '1');

-- --------------------------------------------------------

--
-- 資料表格式： `tf_torrents`
--

CREATE TABLE IF NOT EXISTS `tf_torrents` (
  `id` mediumint(8) NOT NULL auto_increment,
  `file_name` varchar(100) NOT NULL,
  `torrent` varchar(50) NOT NULL,
  `hash` varchar(100) NOT NULL,
  `owner_id` int(10) NOT NULL,
  `rate` smallint(5) NOT NULL,
  `drate` smallint(5) NOT NULL,
  `superseeder` tinyint(1) NOT NULL,
  `runtime` tinyint(1) NOT NULL,
  `maxuploads` smallint(5) NOT NULL,
  `minport` mediumint(6) NOT NULL,
  `maxport` mediumint(6) NOT NULL,
  `rerequest` mediumint(6) NOT NULL,
  `sharekill` mediumint(6) NOT NULL,
  `prio` varchar(500) NOT NULL,
  `location` varchar(100) NOT NULL,
  `statusid` tinyint(1) NOT NULL,
  `estTime` varchar(50) NOT NULL,
  `timeStarted` int(10) NOT NULL,
  `endtime` varchar(20) NOT NULL,
  `percent_done` float NOT NULL,
  `down_speed` float NOT NULL,
  `up_speed` float NOT NULL,
  `size` varchar(10) NOT NULL,
  `seeds` varchar(10) NOT NULL,
  `peers` smallint(5) NOT NULL,
  `uptotal` varchar(10) NOT NULL,
  `downtotal` varchar(10) NOT NULL,
  `haspid` tinyint(1) NOT NULL,
  `speedlog` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=263 ;

--
-- 列出以下資料庫的數據： `tf_torrents`
--

-- --------------------------------------------------------

--
-- 資料表格式： `tf_users`
--

CREATE TABLE IF NOT EXISTS `tf_users` (
  `uid` int(10) NOT NULL auto_increment,
  `user_id` varchar(32) NOT NULL default '',
  `password` varchar(34) NOT NULL default '',
  `hits` int(10) NOT NULL default '0',
  `last_visit` varchar(14) NOT NULL default '0',
  `time_created` varchar(14) NOT NULL default '0',
  `user_level` tinyint(1) NOT NULL default '0',
  `hide_offline` tinyint(1) NOT NULL default '0',
  `theme` varchar(100) NOT NULL default 'mint',
  `language_file` varchar(60) default 'lang-english.php',
  `newpm` tinyint(1) NOT NULL,
  `allow_view_other_torrent` tinyint(1) NOT NULL,
  `totaltorrent` smallint(5) NOT NULL,
  `runningtorrent` tinyint(3) NOT NULL,
  `activetorrent` tinyint(3) NOT NULL,
  `downloadingtorrent` tinyint(3) NOT NULL,
  `seedingtorrent` tinyint(3) NOT NULL,
  `DownloadSpeed` mediumint(7) NOT NULL,
  `UploadSpeed` mediumint(7) NOT NULL,
  `torrentlimit_period` smallint(3) NOT NULL,
  `torrentlimit_number` smallint(3) NOT NULL,
  `transferlimit_period` smallint(3) NOT NULL,
  `transferlimit_number` smallint(5) NOT NULL,
  `space_limit` mediumint(8) NOT NULL,
  `maxActiveTorrent` tinyint(3) NOT NULL,
  `maxDownloadTorrent` tinyint(3) NOT NULL,
  `maxSeedTorrent` tinyint(3) NOT NULL,
  PRIMARY KEY  (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=34 ;

--
-- 列出以下資料庫的數據： `tf_users`
--

INSERT INTO `tf_users` (`uid`, `user_id`, `password`, `hits`, `last_visit`, `time_created`, `user_level`, `hide_offline`, `theme`, `language_file`, `newpm`, `allow_view_other_torrent`, `totaltorrent`, `runningtorrent`, `activetorrent`, `downloadingtorrent`, `seedingtorrent`, `DownloadSpeed`, `UploadSpeed`, `torrentlimit_period`, `torrentlimit_number`, `transferlimit_period`, `transferlimit_number`, `space_limit`, `maxActiveTorrent`, `maxDownloadTorrent`, `maxSeedTorrent`) VALUES
(1, 'root',  MD5( 'root' ), 272154, '1222085251', '1212757861', 2, 0, 'BlueFlux', 'lang-english.php', 0, 0, 104, 6, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)
-- --------------------------------------------------------

--
-- 資料表格式： `tf_xfer`
--

CREATE TABLE IF NOT EXISTS `tf_xfer` (
  `user` mediumint(8) NOT NULL,
  `date` date NOT NULL default '0000-00-00',
  `download` int(10) NOT NULL default '0',
  `upload` int(10) NOT NULL default '0',
  PRIMARY KEY  (`user`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- 列出以下資料庫的數據： `tf_xfer`
--

