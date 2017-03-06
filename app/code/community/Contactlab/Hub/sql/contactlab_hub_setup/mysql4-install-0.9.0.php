<?php
$installer = $this;

$installer->startSetup();

$installer->run("
CREATE TABLE IF NOT EXISTS {$installer->getTable('contactlab_hub/event')} (
  `entity_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_bin NOT NULL DEFAULT '',
  `model` varchar(100) COLLATE utf8_bin NOT NULL DEFAULT '',
  `event_data` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `store_id` int(11) NOT NULL,
  `session_id` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `env_user_agent` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `env_remote_ip` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `env_params` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `hub_event` text COLLATE utf8_bin,
  `identity_email` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `need_update_identity` tinyint(4) DEFAULT '0',
  `status` int(4) NOT NULL DEFAULT '0',
  `exported_date` datetime DEFAULT NULL,
  `profile_source` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`entity_id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_identity_email` (`identity_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
");

$installer->run("
CREATE TABLE IF NOT EXISTS {$installer->getTable('contactlab_hub/previouscustomers')} (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `is_customer` tinyint(1) NOT NULL DEFAULT '0',
  `subscriber_id` int(11) DEFAULT NULL,
  `is_subscribed` tinyint(1) NOT NULL DEFAULT '0',
  `prefix` varchar(10) COLLATE utf8_bin DEFAULT NULL,
  `firstname` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `middlename` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `lastname` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `suffix` varchar(10) COLLATE utf8_bin DEFAULT NULL,
  `dob` datetime DEFAULT NULL,
  `gender` varchar(1) COLLATE utf8_bin DEFAULT NULL,
  `email` varchar(100) COLLATE utf8_bin NOT NULL DEFAULT '',
  `taxvat` varchar(100) COLLATE utf8_bin DEFAULT '',
  `remote_ip` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `billing_prefix` varchar(10) COLLATE utf8_bin DEFAULT NULL,
  `billing_firstname` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `billing_middlename` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `billing_lastname` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `billing_suffix` varchar(10) COLLATE utf8_bin DEFAULT NULL,
  `billing_country_id` int(11) DEFAULT NULL,
  `billing_region_id` int(11) DEFAULT NULL,
  `billing_region` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `billing_postcode` varchar(10) COLLATE utf8_bin DEFAULT NULL,
  `billing_city` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `billing_street` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `billing_telephone` varchar(20) COLLATE utf8_bin DEFAULT NULL,
  `billing_fax` varchar(20) COLLATE utf8_bin DEFAULT NULL,
  `billing_company` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `shipping_prefix` varchar(10) COLLATE utf8_bin DEFAULT NULL,
  `shipping_firstname` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `shipping_middlename` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `shipping_lastname` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `shipping_suffix` varchar(10) COLLATE utf8_bin DEFAULT NULL,
  `shipping_country_id` int(11) DEFAULT NULL,
  `shipping_region_id` int(11) DEFAULT NULL,
  `shipping_region` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `shipping_postcode` varchar(10) COLLATE utf8_bin DEFAULT NULL,
  `shipping_city` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `shipping_street` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `shipping_telephone` varchar(20) COLLATE utf8_bin DEFAULT NULL,
  `shipping_fax` varchar(20) COLLATE utf8_bin DEFAULT NULL,
  `shipping_company` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `store_id` int(11) NOT NULL,
  `store_name` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `website_id` int(11) NOT NULL,
  `website_name` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `group_id` int(11) NOT NULL,
  `group_name` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `language` varchar(20) COLLATE utf8_bin NOT NULL DEFAULT '',
  `extra_properties` text COLLATE utf8_bin,
  `is_exported` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
");

$installer->run("
CREATE TABLE IF NOT EXISTS {$installer->getTable('contactlab_hub/abandoned_carts')} (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) unsigned NOT NULL,
  `store_id` int(11) unsigned NOT NULL,
  `email` varchar(100) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `abandoned_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `remote_ip` varchar(255) NOT NULL DEFAULT '',
  `is_exported` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$subscribersTable = $installer->getTable('newsletter/subscriber');
$installer->getConnection()
->addColumn($subscribersTable,'created_at', array(
		'type'      => Varien_Db_Ddl_Table::TYPE_DATETIME,
		'nullable'  => true,
		'comment'   => 'Created At'
));

$installer->getConnection()
->addColumn($subscribersTable,'last_subscribed_at', array(
		'type'      => Varien_Db_Ddl_Table::TYPE_DATETIME,
		'nullable'  => true,
		'comment'   => 'Last Subscribed At'
));


$installer->endSetup();