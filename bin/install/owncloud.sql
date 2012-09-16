-- phpMyAdmin SQL Dump
-- version 3.3.9.2
-- http://www.phpmyadmin.net
--
-- Serveur: 127.0.0.1:3306
-- Généré le : Jeu 13 Septembre 2012 à 00:43
-- Version du serveur: 5.1.63
-- Version de PHP: 5.3.3-7+squeeze14

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `owncloud`
--

-- --------------------------------------------------------

--
-- Structure de la table `oc_appconfig`
--

CREATE TABLE IF NOT EXISTS `oc_appconfig` (
  `appid` varchar(255) NOT NULL DEFAULT '',
  `configkey` varchar(255) NOT NULL DEFAULT '',
  `configvalue` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Contenu de la table `oc_appconfig`
--

INSERT INTO `oc_appconfig` (`appid`, `configkey`, `configvalue`) VALUES
('core', 'installedat', '1347489471.52'),
('core', 'lastupdatedat', '1347489471.52'),
('calendar', 'installed_version', '0.6'),
('core', 'remote_calendar', 'calendar/appinfo/remote.php'),
('core', 'remote_caldav', 'calendar/appinfo/remote.php'),
('core', 'public_calendar', 'calendar/share.php'),
('core', 'public_caldav', 'calendar/share.php'),
('calendar', 'types', ''),
('calendar', 'enabled', 'yes'),
('gallery', 'installed_version', '0.5.1'),
('core', 'public_gallery', 'gallery/sharing.php'),
('gallery', 'types', ''),
('gallery', 'enabled', 'yes'),
('user_migrate', 'installed_version', '0.1'),
('user_migrate', 'types', ''),
('user_migrate', 'enabled', 'yes'),
('contacts', 'installed_version', '0.2.4'),
('core', 'remote_contacts', 'contacts/appinfo/remote.php'),
('core', 'remote_carddav', 'contacts/appinfo/remote.php'),
('contacts', 'types', ''),
('contacts', 'enabled', 'yes'),
('files_odfviewer', 'installed_version', '0.1'),
('files_odfviewer', 'types', ''),
('files_odfviewer', 'enabled', 'yes'),
('files_archive', 'installed_version', '0.2'),
('files_archive', 'types', 'filesystem'),
('files_archive', 'enabled', 'yes'),
('files_imageviewer', 'installed_version', '1.0'),
('files_imageviewer', 'types', ''),
('files_imageviewer', 'enabled', 'yes'),
('files_versions', 'installed_version', '1.0.2'),
('files_versions', 'types', 'filesystem'),
('files_versions', 'enabled', 'yes'),
('files_sharing', 'installed_version', '0.3.2'),
('core', 'public_files', 'files_sharing/public.php'),
('core', 'public_webdav', 'files_sharing/public.php'),
('files_sharing', 'types', 'filesystem'),
('files_sharing', 'enabled', 'yes'),
('media', 'installed_version', '0.4.1'),
('core', 'remote_ampache', 'media/remote.php'),
('media', 'types', ''),
('media', 'enabled', 'yes'),
('admin_migrate', 'installed_version', '0.1'),
('admin_migrate', 'types', ''),
('admin_migrate', 'enabled', 'yes'),
('files_texteditor', 'installed_version', '0.3'),
('files_texteditor', 'types', ''),
('files_texteditor', 'enabled', 'yes'),
('files', 'installed_version', '1.1.5'),
('core', 'remote_files', 'files/appinfo/remote.php'),
('core', 'remote_webdav', 'files/appinfo/remote.php'),
('core', 'remote_filesync', 'files/appinfo/filesync.php'),
('files', 'types', 'filesystem'),
('files', 'enabled', 'yes'),
('files_pdfviewer', 'installed_version', '0.1'),
('files_pdfviewer', 'types', ''),
('files_pdfviewer', 'enabled', 'yes'),
('core', 'remote_core.css', '/core/minimizer.php'),
('core', 'remote_core.js', '/core/minimizer.php'),
('core', 'backgroundjobs_task', ''),
('core', 'global_cache_gc_lastrun', '1347489478'),
('core', 'backgroundjobs_step', 'regular_tasks');

-- --------------------------------------------------------

--
-- Structure de la table `oc_calendar_calendars`
--

CREATE TABLE IF NOT EXISTS `oc_calendar_calendars` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` varchar(255) DEFAULT NULL,
  `displayname` varchar(100) DEFAULT NULL,
  `uri` varchar(255) DEFAULT NULL,
  `active` int(11) NOT NULL DEFAULT '1',
  `ctag` int(10) unsigned NOT NULL DEFAULT '0',
  `calendarorder` int(10) unsigned NOT NULL DEFAULT '0',
  `calendarcolor` varchar(10) DEFAULT NULL,
  `timezone` mediumtext,
  `components` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Contenu de la table `oc_calendar_calendars`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_calendar_objects`
--

CREATE TABLE IF NOT EXISTS `oc_calendar_objects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `calendarid` int(10) unsigned NOT NULL DEFAULT '0',
  `objecttype` varchar(40) NOT NULL DEFAULT '',
  `startdate` datetime DEFAULT '0000-00-00 00:00:00',
  `enddate` datetime DEFAULT '0000-00-00 00:00:00',
  `repeating` int(11) DEFAULT NULL,
  `summary` varchar(255) DEFAULT NULL,
  `calendardata` longtext,
  `uri` varchar(255) DEFAULT NULL,
  `lastmodified` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Contenu de la table `oc_calendar_objects`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_calendar_repeat`
--

CREATE TABLE IF NOT EXISTS `oc_calendar_repeat` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `eventid` int(10) unsigned NOT NULL DEFAULT '0',
  `calid` int(10) unsigned NOT NULL DEFAULT '0',
  `startdate` datetime DEFAULT '0000-00-00 00:00:00',
  `enddate` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Contenu de la table `oc_calendar_repeat`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_calendar_share_calendar`
--

CREATE TABLE IF NOT EXISTS `oc_calendar_share_calendar` (
  `owner` varchar(255) NOT NULL DEFAULT '',
  `share` varchar(255) NOT NULL DEFAULT '',
  `sharetype` varchar(6) NOT NULL DEFAULT '',
  `calendarid` bigint(20) unsigned NOT NULL DEFAULT '0',
  `permissions` tinyint(4) NOT NULL DEFAULT '0',
  `active` int(11) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Contenu de la table `oc_calendar_share_calendar`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_calendar_share_event`
--

CREATE TABLE IF NOT EXISTS `oc_calendar_share_event` (
  `owner` varchar(255) NOT NULL DEFAULT '',
  `share` varchar(255) NOT NULL DEFAULT '',
  `sharetype` varchar(6) NOT NULL DEFAULT '',
  `eventid` bigint(20) unsigned NOT NULL DEFAULT '0',
  `permissions` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Contenu de la table `oc_calendar_share_event`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_contacts_addressbooks`
--

CREATE TABLE IF NOT EXISTS `oc_contacts_addressbooks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` varchar(255) NOT NULL DEFAULT '',
  `displayname` varchar(255) DEFAULT NULL,
  `uri` varchar(200) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `ctag` int(10) unsigned NOT NULL DEFAULT '1',
  `active` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Contenu de la table `oc_contacts_addressbooks`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_contacts_cards`
--

CREATE TABLE IF NOT EXISTS `oc_contacts_cards` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `addressbookid` int(10) unsigned NOT NULL DEFAULT '0',
  `fullname` varchar(255) DEFAULT NULL,
  `carddata` longtext,
  `uri` varchar(200) DEFAULT NULL,
  `lastmodified` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Contenu de la table `oc_contacts_cards`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_fscache`
--

CREATE TABLE IF NOT EXISTS `oc_fscache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(512) NOT NULL DEFAULT '',
  `path_hash` varchar(32) NOT NULL DEFAULT '',
  `parent` bigint(20) NOT NULL DEFAULT '0',
  `name` varchar(300) NOT NULL DEFAULT '\n	   ',
  `user` varchar(64) NOT NULL DEFAULT '\n	   ',
  `size` bigint(20) NOT NULL DEFAULT '0',
  `ctime` bigint(20) NOT NULL DEFAULT '0',
  `mtime` bigint(20) NOT NULL DEFAULT '0',
  `mimetype` varchar(96) NOT NULL DEFAULT '\n	   ',
  `mimepart` varchar(32) NOT NULL DEFAULT '\n	   ',
  `encrypted` tinyint(4) NOT NULL DEFAULT '0',
  `versioned` tinyint(4) NOT NULL DEFAULT '0',
  `writable` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fscache_path_hash_index` (`path_hash`),
  KEY `parent_index` (`parent`),
  KEY `name_index` (`name`),
  KEY `parent_name_index` (`parent`,`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Contenu de la table `oc_fscache`
--



-- --------------------------------------------------------

--
-- Structure de la table `oc_gallery_sharing`
--

CREATE TABLE IF NOT EXISTS `oc_gallery_sharing` (
  `token` varchar(64) NOT NULL DEFAULT '',
  `gallery_id` int(11) NOT NULL DEFAULT '0',
  `recursive` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Contenu de la table `oc_gallery_sharing`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_groups`
--

CREATE TABLE IF NOT EXISTS `oc_groups` (
  `gid` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`gid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Contenu de la table `oc_groups`
--

INSERT INTO `oc_groups` (`gid`) VALUES
('admin');

-- --------------------------------------------------------

--
-- Structure de la table `oc_group_admin`
--

CREATE TABLE IF NOT EXISTS `oc_group_admin` (
  `gid` varchar(64) NOT NULL DEFAULT '',
  `uid` varchar(64) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Contenu de la table `oc_group_admin`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_group_user`
--

CREATE TABLE IF NOT EXISTS `oc_group_user` (
  `gid` varchar(64) NOT NULL DEFAULT '',
  `uid` varchar(64) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `oc_locks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` varchar(200) DEFAULT NULL,
  `owner` varchar(100) DEFAULT NULL,
  `timeout` int(10) unsigned DEFAULT NULL,
  `created` bigint(20) DEFAULT NULL,
  `token` varchar(100) DEFAULT NULL,
  `scope` tinyint(4) DEFAULT NULL,
  `depth` tinyint(4) DEFAULT NULL,
  `uri` longtext,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Contenu de la table `oc_locks`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_media_albums`
--

CREATE TABLE IF NOT EXISTS `oc_media_albums` (
  `album_id` int(11) NOT NULL AUTO_INCREMENT,
  `album_name` varchar(200) NOT NULL DEFAULT '',
  `album_artist` int(11) NOT NULL DEFAULT '0',
  `album_art` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`album_id`),
  KEY `album_name_index` (`album_name`),
  KEY `album_artist_index` (`album_artist`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Contenu de la table `oc_media_albums`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_media_artists`
--

CREATE TABLE IF NOT EXISTS `oc_media_artists` (
  `artist_id` int(11) NOT NULL AUTO_INCREMENT,
  `artist_name` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`artist_id`),
  UNIQUE KEY `artist_name` (`artist_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Contenu de la table `oc_media_artists`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_media_sessions`
--

CREATE TABLE IF NOT EXISTS `oc_media_sessions` (
  `session_id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(64) NOT NULL DEFAULT '',
  `user_id` varchar(64) NOT NULL DEFAULT '',
  `start` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
  PRIMARY KEY (`session_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Contenu de la table `oc_media_sessions`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_media_songs`
--

CREATE TABLE IF NOT EXISTS `oc_media_songs` (
  `song_id` int(11) NOT NULL AUTO_INCREMENT,
  `song_name` varchar(200) NOT NULL DEFAULT '',
  `song_artist` int(11) NOT NULL DEFAULT '0',
  `song_album` int(11) NOT NULL DEFAULT '0',
  `song_path` varchar(200) NOT NULL DEFAULT '',
  `song_user` varchar(64) NOT NULL DEFAULT '0',
  `song_length` int(11) NOT NULL DEFAULT '0',
  `song_track` int(11) NOT NULL DEFAULT '0',
  `song_size` int(11) NOT NULL DEFAULT '0',
  `song_playcount` int(11) NOT NULL DEFAULT '0',
  `song_lastplayed` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`song_id`),
  KEY `song_album_index` (`song_album`),
  KEY `song_artist_index` (`song_artist`),
  KEY `song_name_index` (`song_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Contenu de la table `oc_media_songs`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_media_users`
--

CREATE TABLE IF NOT EXISTS `oc_media_users` (
  `user_id` varchar(64) NOT NULL DEFAULT '0',
  `user_password_sha256` varchar(64) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Contenu de la table `oc_media_users`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_pictures_images_cache`
--

CREATE TABLE IF NOT EXISTS `oc_pictures_images_cache` (
  `uid_owner` varchar(64) NOT NULL DEFAULT '',
  `path` varchar(256) NOT NULL DEFAULT '',
  `width` int(11) NOT NULL DEFAULT '0',
  `height` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Contenu de la table `oc_pictures_images_cache`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_preferences`
--

CREATE TABLE IF NOT EXISTS `oc_preferences` (
  `userid` varchar(255) NOT NULL DEFAULT '',
  `appid` varchar(255) NOT NULL DEFAULT '',
  `configkey` varchar(255) NOT NULL DEFAULT '',
  `configvalue` longtext
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Contenu de la table `oc_preferences`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_properties`
--

CREATE TABLE IF NOT EXISTS `oc_properties` (
  `userid` varchar(200) NOT NULL DEFAULT '',
  `propertypath` varchar(255) NOT NULL DEFAULT '',
  `propertyname` varchar(255) NOT NULL DEFAULT '',
  `propertyvalue` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Contenu de la table `oc_properties`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_queuedtasks`
--

CREATE TABLE IF NOT EXISTS `oc_queuedtasks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app` varchar(255) NOT NULL DEFAULT '',
  `klass` varchar(255) NOT NULL DEFAULT '',
  `method` varchar(255) NOT NULL DEFAULT '',
  `parameters` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Contenu de la table `oc_queuedtasks`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_share`
--

CREATE TABLE IF NOT EXISTS `oc_share` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `share_type` tinyint(4) NOT NULL DEFAULT '0',
  `share_with` varchar(255) DEFAULT NULL,
  `uid_owner` varchar(255) NOT NULL DEFAULT '',
  `parent` int(11) DEFAULT NULL,
  `item_type` varchar(64) NOT NULL DEFAULT '',
  `item_source` varchar(255) DEFAULT NULL,
  `item_target` varchar(255) DEFAULT NULL,
  `file_source` int(11) DEFAULT NULL,
  `file_target` varchar(512) DEFAULT NULL,
  `permissions` tinyint(4) NOT NULL DEFAULT '0',
  `stime` bigint(20) NOT NULL DEFAULT '0',
  `accepted` tinyint(4) NOT NULL DEFAULT '0',
  `expiration` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Contenu de la table `oc_share`
--


-- --------------------------------------------------------

--
-- Structure de la table `oc_users`
--

CREATE TABLE IF NOT EXISTS `oc_users` (
  `uid` varchar(64) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


