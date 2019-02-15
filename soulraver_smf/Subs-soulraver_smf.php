<?php
/* this is very silly of them to have everywhere */
if (!defined('SMF')) {
	die('Hacking attempt...');
}

/* integrate_general_mod_settings */
function soulraver_smf_configs($config_vars) {
	global $modSettings, $txt;

	if (isset($config_vars[0])) {
		$config_vars[] = array('title', 'soulraver_smf');
	}

	if (empty($modSettings['md_title'])) {
		updateSettings(array('soulraver_smf' => $txt['soulraver_smf']));
	}

	$config_vars[] = array('select', 'enable_spoiler', $txt['toggle_enabled']);
	$config_vars[] = array('select', 'enable_roll', $txt['toggle_enabled']);
	
}

/* integrate_load_theme */
function soulraver_smf_load_theme() {
	global $context, $settings;

	if (isset($_REQUEST['sa']) && $_REQUEST['sa'] == 'showoperations') {
		return;
	}

	/* TODO: Verify that this is actually relevant to loading the custom css */
	loadTemplate(false, 'soulraver_smf');

	if (!in_array($context['current_action'], array('helpadmin', 'printpage')) && (defined('WIRELESS') && !WIRELESS)) {
		/* If we had custom javascript it would be injected into the page here. */
		//$context['insert_after_template'] .= '';
	}
}

/* integrate_bbc_buttons */
function soulraver_smf_add_buttons($buttons) {
	global $txt;
	
	$buttons[count($buttons) - 1][] = array(
		'image'       => 'zomg_spoiler',
		'code'        => 'spoiler',
		'before'      => '[spoiler]',
		'after'       => '[/spoiler]',
		'description' => $txt['spoiler']
	);
	
	//$buttons[count($buttons) - 1][] = array(
	//	'image'       => 'mythic_die',
	//	'code'        => 'roll',
	//	'before'      => '[roll]',
	//	'after'       => '[/roll]',
	//	'description' => $txt['roll']
	//);
	
}