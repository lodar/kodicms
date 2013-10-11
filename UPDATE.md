### 6.0.0

 * ALTER TABLE  `pages` ADD  `meta_title` VARCHAR( 255 ) NOT NULL DEFAULT  '' AFTER  `breadcrumb`;
 * ALTER TABLE  `pages` CHANGE  `keywords`  `meta_keywords` VARCHAR( 255 ) NOT NULL DEFAULT  '';
 * ALTER TABLE  `pages` CHANGE  `description`  `meta_description` TEXT;

<pre>
CREATE TABLE IF NOT EXISTS `TABLE_PREFIX_roles_permissions` (
  `role_id` int(5) unsigned NOT NULL,
  `action` varchar(255) NOT NULL,
  UNIQUE KEY `role_id` (`role_id`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `TABLE_PREFIX_roles_permissions` ADD CONSTRAINT `roles_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `TABLE_PREFIX_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
</pre>

###  6.3.14

<pre>
	CREATE TABLE IF NOT EXISTS `hybrid_tags` (
	  `field_id` int(11) unsigned NOT NULL,
	  `doc_id` int(11) NOT NULL,
	  `tag_id` int(11) unsigned NOT NULL,
	  KEY `tag_id` (`tag_id`),
	  KEY `field_id` (`field_id`,`doc_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
<pre>

### 6.4.20

 * Метод Model_Widget_Decorator::load_template_data изменен на backend_data. Необходимо переименовать в своих виджетах.
 * Если используются виджеты, наследуемые от Model_Widget_Decorator_Pagination, в backend шаблоне больше не нужны поля `list_offset` и `list_size`

### 6.5.21

 * ALTER TABLE  `pages` ADD  `robots` VARCHAR( 100 ) NOT NULL DEFAULT  'INDEX,FOLLOW' AFTER  `meta_description`;
