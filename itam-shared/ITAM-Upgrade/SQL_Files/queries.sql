UPDATE `ap_settings` SET `itauditmachine_version` = "9.9.3";
ALTER TABLE `ap_forms` ADD COLUMN `logic_poam_enable` TINYINT(1) DEFAULT 0 NOT NULL AFTER `logic_email_enable`;
CREATE TABLE `ap_poam_logic`( `id` INT(11) NOT NULL AUTO_INCREMENT, `form_id` INT(11) NOT NULL, `element_name` VARCHAR(50) NOT NULL, `rule_keyword` VARCHAR(255) NOT NULL, `target_template_id` INT(11) NOT NULL, `target_tab` VARCHAR(255) NOT NULL, PRIMARY KEY (`id`) ) ENGINE=INNODB CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE `ap_template_document_creation` ADD COLUMN `isPOAM` TINYINT(1) DEFAULT 0 NOT NULL AFTER `isZip`;
ALTER TABLE `ap_element_options` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE `ap_form_template` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE `ap_page_logic_conditions` CHANGE `target_page_id` `target_page_id` VARCHAR(15) CHARSET utf8 COLLATE utf8_unicode_ci DEFAULT '' NOT NULL, CHANGE `element_name` `element_name` VARCHAR(50) CHARSET utf8 COLLATE utf8_unicode_ci DEFAULT '' NOT NULL, CHANGE `rule_condition` `rule_condition` VARCHAR(15) CHARSET utf8 COLLATE utf8_unicode_ci DEFAULT 'is' NOT NULL, CHANGE `rule_keyword` `rule_keyword` VARCHAR(255) CHARSET utf8 COLLATE utf8_unicode_ci NULL;