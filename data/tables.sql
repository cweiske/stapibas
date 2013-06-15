

DROP TABLE IF EXISTS `feedentries`;
CREATE TABLE `feedentries` (
  `fe_id` int(11) NOT NULL AUTO_INCREMENT,
  `fe_f_id` int(11) NOT NULL,
  `fe_url` varchar(2048) CHARACTER SET utf8 NOT NULL,
  `fe_updated` datetime NOT NULL,
  `fe_needs_update` tinyint(1) NOT NULL,
  PRIMARY KEY (`fe_id`),
  UNIQUE KEY `fe_id` (`fe_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `feedentryurls`;
CREATE TABLE `feedentryurls` (
  `feu_id` int(11) NOT NULL AUTO_INCREMENT,
  `feu_fe_id` int(11) NOT NULL,
  `feu_url` varchar(2048) CHARACTER SET utf8 NOT NULL,
  `feu_active` tinyint(1) NOT NULL COMMENT 'if the url still exists in the entry',
  `feu_pinged` tinyint(1) NOT NULL,
  `feu_updated` datetime NOT NULL,
  `feu_error` tinyint(1) NOT NULL,
  `feu_error_code` varchar(6) NOT NULL,
  `feu_error_message` varchar(128) NOT NULL,
  `feu_tries` tinyint(4) NOT NULL,
  `feu_retry` tinyint(1) NOT NULL,
  PRIMARY KEY (`feu_id`),
  UNIQUE KEY `feu_id` (`feu_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `feeds`;
CREATE TABLE `feeds` (
  `f_id` int(11) NOT NULL AUTO_INCREMENT,
  `f_url` varchar(2048) CHARACTER SET utf8 NOT NULL,
  `f_updated` datetime NOT NULL,
  `f_needs_update` tinyint(1) NOT NULL,
  PRIMARY KEY (`f_id`),
  UNIQUE KEY `f_id` (`f_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `pingbacks`;
CREATE TABLE `pingbacks` (
  `p_id` int(11) NOT NULL AUTO_INCREMENT,
  `p_source` varchar(1024) CHARACTER SET latin1 NOT NULL,
  `p_target` varchar(1024) CHARACTER SET latin1 NOT NULL,
  `p_time` datetime NOT NULL,
  `p_client_ip` varchar(40) CHARACTER SET latin1 NOT NULL,
  `p_client_agent` varchar(128) CHARACTER SET latin1 NOT NULL,
  `p_client_referer` varchar(1024) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`p_id`),
  UNIQUE KEY `p_id` (`p_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


