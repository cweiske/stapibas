

CREATE TABLE `feedentries` (
  `fe_id` int(11) NOT NULL AUTO_INCREMENT,
  `fe_f_id` int(11) NOT NULL,
  `fe_url` varchar(2048) CHARACTER SET utf8 NOT NULL,
  `fe_updated` datetime NOT NULL,
  `fe_needs_update` tinyint(1) NOT NULL,
  PRIMARY KEY (`fe_id`),
  UNIQUE KEY `fe_id` (`fe_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;


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


CREATE TABLE `feeds` (
  `f_id` int(11) NOT NULL AUTO_INCREMENT,
  `f_url` varchar(2048) CHARACTER SET utf8 NOT NULL,
  `f_updated` datetime NOT NULL,
  `f_needs_update` tinyint(1) NOT NULL,
  PRIMARY KEY (`f_id`),
  UNIQUE KEY `f_id` (`f_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;


CREATE TABLE `pingbackcontent` (
  `pc_id` int(11) NOT NULL AUTO_INCREMENT,
  `pc_p_id` int(11) NOT NULL,
  `pc_mime_type` varchar(32) NOT NULL,
  `pc_fulltext` text NOT NULL,
  `pc_detected_type` varchar(16) NOT NULL,
  PRIMARY KEY (`pc_id`),
  UNIQUE KEY `pc_id` (`pc_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE `pingbacks` (
  `p_id` int(11) NOT NULL AUTO_INCREMENT,
  `p_source` varchar(1024) CHARACTER SET latin1 NOT NULL,
  `p_target` varchar(1024) CHARACTER SET latin1 NOT NULL,
  `p_time` datetime NOT NULL,
  `p_client_ip` varchar(40) CHARACTER SET latin1 NOT NULL,
  `p_client_agent` varchar(128) CHARACTER SET latin1 NOT NULL,
  `p_client_referer` varchar(1024) CHARACTER SET latin1 NOT NULL,
  `p_needs_review` tinyint(1) NOT NULL,
  `p_use` tinyint(1) NOT NULL,
  `p_needs_update` tinyint(1) NOT NULL,
  PRIMARY KEY (`p_id`),
  UNIQUE KEY `p_id` (`p_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE `pingbacktargets` (
  `pt_id` int(11) NOT NULL AUTO_INCREMENT,
  `pt_url` varchar(2048) NOT NULL,
  PRIMARY KEY (`pt_id`),
  UNIQUE KEY `pt_id` (`pt_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='List of pages that may receive pingbacks';


CREATE TABLE `rbookmarks` (
  `rb_id` int(11) NOT NULL AUTO_INCREMENT,
  `rb_p_id` int(11) NOT NULL,
  `rb_pc_id` int(11) NOT NULL,
  `rb_target` varchar(2048) NOT NULL,
  `rb_source` varchar(2048) NOT NULL,
  `rb_source_title` varchar(256) NOT NULL,
  `rb_count` int(11) NOT NULL,
  PRIMARY KEY (`rb_id`),
  UNIQUE KEY `rb_id` (`rb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bookmarks, extracted from pingbackcontent';


CREATE TABLE `rcomments` (
  `rc_id` int(11) NOT NULL AUTO_INCREMENT,
  `rc_p_id` int(11) NOT NULL,
  `rc_pc_id` int(11) NOT NULL,
  `rc_target` varchar(2048) NOT NULL,
  `rc_source` varchar(2048) NOT NULL,
  `rc_title` varchar(256) NOT NULL,
  `rc_updated` datetime NOT NULL,
  `rc_author_name` varchar(32) NOT NULL,
  `rc_author_url` varchar(2048) NOT NULL,
  `rc_author_image` varchar(2048) NOT NULL,
  `rc_content` text NOT NULL,
  PRIMARY KEY (`rc_id`),
  UNIQUE KEY `rb_id` (`rc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bookmarks, extracted from pingbackcontent';


CREATE TABLE `rlinks` (
  `rl_id` int(11) NOT NULL AUTO_INCREMENT,
  `rl_p_id` int(11) NOT NULL,
  `rl_pc_id` int(11) NOT NULL,
  `rl_target` varchar(2048) NOT NULL,
  `rl_source` varchar(2048) NOT NULL,
  `rl_title` varchar(256) NOT NULL,
  `rl_updated` datetime NOT NULL,
  `rl_author_name` varchar(32) NOT NULL,
  `rl_author_url` varchar(2048) NOT NULL,
  `rl_author_image` varchar(2048) NOT NULL,
  PRIMARY KEY (`rl_id`),
  UNIQUE KEY `rb_id` (`rl_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Bookmarks, extracted from pingbackcontent';


