CREATE TABLE IF NOT EXISTS `#__profiles_sections` (
	`id`        INT(11)      NOT NULL AUTO_INCREMENT,
	`title`     VARCHAR(255) NOT NULL DEFAULT '',
	`parent_id` INT(11)      NOT NULL DEFAULT '0',
	`lft`       INT(11)      NOT NULL DEFAULT '0',
	`rgt`       INT(11)      NOT NULL DEFAULT '0',
	`level`     INT(10)      NOT NULL DEFAULT '0',
	`path`      VARCHAR(400) NOT NULL DEFAULT '',
	`alias`     VARCHAR(400) NOT NULL DEFAULT '',
	`state`     TINYINT(3)   NOT NULL DEFAULT '0',
	`access`    INT(10)      NOT NULL DEFAULT '0',
	`metadata`  MEDIUMTEXT   NOT NULL DEFAULT '',
	`item_tags` MEDIUMTEXT   NOT NULL DEFAULT '',
	UNIQUE KEY `id` (`id`)
)
	ENGINE = MyISAM
	DEFAULT CHARSET = utf8
	AUTO_INCREMENT = 0;