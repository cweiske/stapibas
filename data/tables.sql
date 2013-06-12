CREATE TABLE IF NOT EXISTS `pingbacks` (
  `p_id` int(11) NOT NULL AUTO_INCREMENT,
  `p_source` varchar(1024) NOT NULL,
  `p_target` varchar(1024) NOT NULL,
  `p_time` datetime NOT NULL,
  `p_client_ip` varchar(40) NOT NULL,
  `p_client_agent` varchar(128) NOT NULL,
  `p_client_referer` varchar(1024) NOT NULL,
  PRIMARY KEY (`p_id`),
  UNIQUE KEY `p_id` (`p_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `feeds` (
  `f_id` int(11) NOT NULL AUTO_INCREMENT,
  `f_url` varchar(2048) NOT NULL,
  `f_updated` datetime NOT NULL,
  `f_needs_update` tinyint(4) NOT NULL,
  PRIMARY KEY (`f_id`),
  UNIQUE KEY `f_id` (`f_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


