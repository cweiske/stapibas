

CREATE TABLE `feedentries` (
  `fe_id` int(11) NOT NULL AUTO_INCREMENT,
  `fe_f_id` int(11) NOT NULL,
  `fe_url` varchar(2048) CHARACTER SET utf8 NOT NULL,
  `fe_updated` datetime NOT NULL,
  `fe_needs_update` tinyint(1) NOT NULL,
  PRIMARY KEY (`fe_id`),
  UNIQUE KEY `fe_id` (`fe_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE `feedentryurls` (
  `feu_id` int(11) NOT NULL AUTO_INCREMENT,
  `feu_fe_id` int(11) NOT NULL,
  `feu_url` varchar(2048) CHARACTER SET utf8 NOT NULL,
  `feu_active` tinyint(1) NOT NULL COMMENT 'if the url still exists in the entry',
  `feu_pinged` tinyint(1) NOT NULL,
  `feu_updated` datetime NOT NULL,
  `feu_error` tinyint(1) NOT NULL,
  `feu_error_code` varchar(6) NOT NULL,
  `feu_error_message` varchar(4096) NOT NULL,
  `feu_tries` tinyint(4) NOT NULL,
  `feu_retry` tinyint(1) NOT NULL,
  PRIMARY KEY (`feu_id`),
  UNIQUE KEY `feu_id` (`feu_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE `feeds` (
  `f_id` int(11) NOT NULL AUTO_INCREMENT,
  `f_url` varchar(2048) CHARACTER SET utf8 NOT NULL,
  `f_updated` datetime NOT NULL,
  `f_needs_update` tinyint(1) NOT NULL,
  PRIMARY KEY (`f_id`),
  UNIQUE KEY `f_id` (`f_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE `linkbackcontent` (
  `lc_id` int(11) NOT NULL AUTO_INCREMENT,
  `lc_l_id` int(11) NOT NULL,
  `lc_mime_type` varchar(32) NOT NULL,
  `lc_fulltext` text NOT NULL,
  `lc_detected_type` varchar(16) NOT NULL,
  PRIMARY KEY (`lc_id`),
  UNIQUE KEY `lc_id` (`lc_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE `linkbacks` (
  `l_id` int(11) NOT NULL AUTO_INCREMENT,
  `l_source` varchar(1024) CHARACTER SET utf8 NOT NULL,
  `l_target` varchar(1024) CHARACTER SET utf8 NOT NULL,
  `l_time` datetime NOT NULL,
  `l_client_ip` varchar(40) CHARACTER SET utf8 NOT NULL,
  `l_client_agent` varchar(128) CHARACTER SET utf8 NOT NULL,
  `l_client_referer` varchar(1024) CHARACTER SET utf8 NOT NULL,
  `l_needs_review` tinyint(1) NOT NULL,
  `l_use` tinyint(1) NOT NULL,
  `l_needs_update` tinyint(1) NOT NULL,
  PRIMARY KEY (`l_id`),
  UNIQUE KEY `l_id` (`l_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE `linkbacktargets` (
  `lt_id` int(11) NOT NULL AUTO_INCREMENT,
  `lt_url` varchar(2048) NOT NULL,
  PRIMARY KEY (`lt_id`),
  UNIQUE KEY `lt_id` (`lt_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='List of pages that may receive linkbacks';


CREATE TABLE `rbookmarks` (
  `rb_id` int(11) NOT NULL AUTO_INCREMENT,
  `rb_l_id` int(11) NOT NULL,
  `rb_lc_id` int(11) NOT NULL,
  `rb_target` varchar(2048) NOT NULL,
  `rb_source` varchar(2048) NOT NULL,
  `rb_source_title` varchar(256) NOT NULL,
  `rb_count` int(11) NOT NULL,
  PRIMARY KEY (`rb_id`),
  UNIQUE KEY `rb_id` (`rb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bookmarks, extracted from linkbackcontent';


CREATE TABLE `rcomments` (
  `rc_id` int(11) NOT NULL AUTO_INCREMENT,
  `rc_l_id` int(11) NOT NULL,
  `rc_lc_id` int(11) NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bookmarks, extracted from linkbackcontent';


CREATE TABLE `rlinks` (
  `rl_id` int(11) NOT NULL AUTO_INCREMENT,
  `rl_l_id` int(11) NOT NULL,
  `rl_lc_id` int(11) NOT NULL,
  `rl_target` varchar(2048) NOT NULL,
  `rl_source` varchar(2048) NOT NULL,
  `rl_title` varchar(256) NOT NULL,
  `rl_updated` datetime NOT NULL,
  `rl_author_name` varchar(32) NOT NULL,
  `rl_author_url` varchar(2048) NOT NULL,
  `rl_author_image` varchar(2048) NOT NULL,
  PRIMARY KEY (`rl_id`),
  UNIQUE KEY `rb_id` (`rl_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Bookmarks, extracted from linkbackcontent';


