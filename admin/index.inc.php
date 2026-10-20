<?php
if(!defined('CC_DS')) die('Access Denied');

// Create table to store FraudLabs Pro result
if(!$GLOBALS['db']->misc("SHOW TABLES LIKE 'plugin_fraudlabspro';")) {
	$GLOBALS['db']->misc("
	CREATE TABLE IF NOT EXISTS plugin_fraudlabs_pro (
		order_id VARCHAR(18) NOT NULL,
		raw_data TEXT NOT NULL,
		PRIMARY KEY (order_id)
	) COLLATE='utf8_unicode_ci';");
}

if(!$module_config = $GLOBALS['config']->get('FraudLabsPro')) {
	// Set the default config into the CubeCart_config table
	$GLOBALS['config']->set('FraudLabsPro', '', array (
		'api_key' 	=> '',
	));
}

$module	= new Module(__FILE__, $_GET['module'], 'admin/index.tpl', true, false);
$module->fetch();
$page_content = $module->display();