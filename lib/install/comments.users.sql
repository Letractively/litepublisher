  `id` int(10) unsigned NOT NULL auto_increment,
  `avatar` int(10) unsigned NOT NULL default '0',
  `trust` int(10) unsigned NOT NULL default '0',
  `name` varchar(64) NOT NULL,
  `email` varchar(64) NOT NULL,
  `url` varchar(255) NOT NULL,
  `cookie` varchar(32) NOT NULL,
  `ip` varchar(15) NOT NULL default '',

  PRIMARY KEY  (`id`),
  KEY `cookie` (`cookie`),
  KEY `email` (`email`),
  KEY `url` (`url`)