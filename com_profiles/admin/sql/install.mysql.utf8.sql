CREATE TABLE IF NOT EXISTS `#__profiles` (
	`id`         INT(11)          NOT NULL AUTO_INCREMENT,
	`name`       VARCHAR(255)     NOT NULL DEFAULT '',
	`alias`      VARCHAR(400)     NOT NULL DEFAULT '',
	`about`      LONGTEXT         NOT NULL DEFAULT '',
	`status`     VARCHAR(255)     NOT NULL DEFAULT '',
	`state`      TINYINT(3)       NOT NULL DEFAULT '0',
	`in_work`    TINYINT(3)       NOT NULL DEFAULT '0',
	`type`       VARCHAR(255)     NOT NULL DEFAULT '',
	`created`    DATETIME         NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modified`   DATETIME         NOT NULL DEFAULT '0000-00-00 00:00:00',
	`avatar`     TINYINT(3)       NOT NULL DEFAULT '0',
	`portfolio`  MEDIUMTEXT       NOT NULL DEFAULT '',
	`contacts`   MEDIUMTEXT       NOT NULL DEFAULT '',
	`requisites` MEDIUMTEXT       NOT NULL DEFAULT '',
	`params`     TEXT             NOT NULL DEFAULT '',
	`access`     INT(10)          NOT NULL DEFAULT '0',
	`hits`       INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`region`     CHAR(7)          NOT NULL DEFAULT '*',
	`notes`      LONGTEXT         NOT NULL DEFAULT '',
	`item_tags`  TEXT             NOT NULL DEFAULT '',
	UNIQUE KEY `id` (`id`)
)
	ENGINE = MyISAM
	DEFAULT CHARSET = utf8
	AUTO_INCREMENT = 0;
CREATE TABLE IF NOT EXISTS `#__profiles_categories` (
	`id`          INT(11)      NOT NULL AUTO_INCREMENT,
	`parent_id`   INT(11)      NOT NULL DEFAULT '0',
	`lft`         INT(11)      NOT NULL DEFAULT '0',
	`rgt`         INT(11)      NOT NULL DEFAULT '0',
	`level`       INT(10)      NOT NULL DEFAULT '0',
	`path`        VARCHAR(400) NOT NULL DEFAULT '',
	`title`       VARCHAR(255) NOT NULL DEFAULT '',
	`alias`       VARCHAR(400) NOT NULL DEFAULT '',
	`description` MEDIUMTEXT   NOT NULL DEFAULT '',
	`state`       TINYINT(3)   NOT NULL DEFAULT '0',
	`access`      INT(10)      NOT NULL DEFAULT '0',
	`params`      TEXT         NOT NULL DEFAULT '',
	`metadata`    MEDIUMTEXT   NOT NULL DEFAULT '',
	`items_tags`  TEXT         NOT NULL DEFAULT '',
	UNIQUE KEY `id` (`id`)
)
	ENGINE = MyISAM
	DEFAULT CHARSET = utf8
	AUTO_INCREMENT = 0;