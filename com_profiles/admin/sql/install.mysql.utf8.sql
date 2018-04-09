CREATE TABLE IF NOT EXISTS `#__profiles` (
  `id`          INT(11)          NOT NULL,
  `name`        VARCHAR(255)     NOT NULL DEFAULT '',
  `alias`       VARCHAR(400)     NOT NULL DEFAULT '',
  `about`       LONGTEXT         NOT NULL DEFAULT '',
  `status`      VARCHAR(255)     NOT NULL DEFAULT '',
  `contacts`    MEDIUMTEXT       NOT NULL DEFAULT '',
  `avatar`      TEXT             NOT NULL DEFAULT '',
  `header`      TEXT             NOT NULL DEFAULT '',
  `notes`       LONGTEXT         NOT NULL DEFAULT '',
  `created`     DATETIME         NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified`    DATETIME         NOT NULL DEFAULT '0000-00-00 00:00:00',
  `attribs`     TEXT             NOT NULL DEFAULT '',
  `metakey`     MEDIUMTEXT       NOT NULL DEFAULT '',
  `metadesc`    MEDIUMTEXT       NOT NULL DEFAULT '',
  `hits`        INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `region`      CHAR(7)          NOT NULL DEFAULT '*',
  `metadata`    MEDIUMTEXT       NOT NULL DEFAULT '',
  `tags_search` MEDIUMTEXT       NOT NULL DEFAULT '',
  `tags_map`    LONGTEXT         NOT NULL DEFAULT '',
  UNIQUE KEY `id` (`id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 0;

CREATE TABLE IF NOT EXISTS `#__user_phones` (
  `user_id` INT(10)      NOT NULL,
  `code`    CHAR(7)      NOT NULL DEFAULT '',
  `number`  VARCHAR(100) NOT NULL DEFAULT '',
  UNIQUE KEY `user_id` (`user_id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 0;

CREATE TABLE IF NOT EXISTS `#__user_socials` (
  `user_id`   INT(10)      NOT NULL,
  `provider`  VARCHAR(150) NOT NULL DEFAULT '',
  `social_id` VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`user_id`, `social_id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 0;
