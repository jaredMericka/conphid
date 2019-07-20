-- v0.5b

ALTER TABLE `conphid`.`posts`
ADD COLUMN `deleted` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '' AFTER `user_hash`;

-- Remember to add cooldowns to users, too.

-- v0.6b
ALTER TABLE `conphid`.`posts`
ADD COLUMN `hidden` TINYINT NOT NULL DEFAULT 0 COMMENT '' AFTER `deleted`;

ALTER TABLE `conphid`.`threads`
CHANGE COLUMN `mod_hash` `mod_hash` CHAR(32) NULL DEFAULT NULL COMMENT '' AFTER `level`,
ADD COLUMN `burn`		INT(11) UNSIGNED NULL DEFAULT NULL COMMENT '' AFTER `hidden`,
ADD COLUMN `anonymous`	TINYINT(1) NOT NULL DEFAULT 0 COMMENT '' AFTER `burn`,
ADD COLUMN `singleton`	TINYINT(1) NOT NULL DEFAULT 0 COMMENT '' AFTER `anonymous`,
ADD COLUMN `indexed`	TINYINT(1) NOT NULL DEFAULT 0 COMMENT '' AFTER `singleton`,
ADD COLUMN `doc`		TINYINT(1) NOT NULL DEFAULT 0 COMMENT '' AFTER `indexed`;