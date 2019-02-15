<?php

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF')) {
	require_once(dirname(__FILE__) . '/SSI.php');
} elseif (!defined('SMF')) {
	die '<b>Error:</b> Cannot install - please verify you put this file in the same place as SMF\'s SSI.php.';
}

if (SMF === 'SSI') {
	db_extend('packages');
}

$hooks = array(
	'integrate_pre_include' => '$sourcedir/Subs-soulraver_smf.php',
	'integrate_general_mod_settings' => 'soulraver_smf_configs',
	'integrate_load_theme' => 'soulraver_smf_load_theme',
	'integrate_bbc_buttons' => 'soulraver_smf_add_buttons',
	'integrate_bbc_codes'   => 'soulraver_smf_add_codes'
);

if (!empty($context['uninstalling'])) {
	$call = 'remove_integration_function';
} else {
	$call = 'add_integration_function';
}

foreach ($hooks as $hook => $function) {
	$call($hook, $function);
}

?>