--
-- Structure de la table `stats_serverlist`
--

CREATE TABLE IF NOT EXISTS `stats_serverlist` (
  `server_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `server_address` varchar(25) NOT NULL,
  `server_firstSeen` int(10) unsigned NOT NULL,
  `server_fails` tinyint(4) NOT NULL DEFAULT '0',
  `server_lastFail` int(10) unsigned NOT NULL DEFAULT '0',
  `server_disabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`server_id`),
  UNIQUE KEY `server_adress` (`server_address`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
