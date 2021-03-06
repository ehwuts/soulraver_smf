<?php
/* this is very silly of them to have everywhere but whee consistency */
if (!defined('SMF')) {
	die('Hacking attempt...');
}

/* integrate_general_mod_settings */
function soulraver_smf_configs($config_vars) {
	global $modSettings, $txt;
	
	/* don't actually register the configs since it's not respected */
	//if (isset($config_vars[0])) {
	//	$config_vars[] = array('title', 'soulraver_smf');
	//}

	//if (empty($modSettings['md_title'])) {
	//	updateSettings(array('soulraver_smf' => $txt['soulraver_smf']));
	//}

	//$config_vars[] = array('select', 'enable_spoiler', $txt['toggle_enabled']);
	//$config_vars[] = array('select', 'enable_roll', $txt['toggle_enabled']);
	
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
/* aside from the function name and our modified values for $row_position, $col_position, and $newbuttons
 * https://github.com/SimpleMachines/SMF-Customization-Team-mods/blob/ce8d9b3c4439ec0f50a0731da12313bfa3304f88/BBC_Overline/Subs-Overlinebbc.php#L42
 * and is @copyright 2011 Simple Machines and licensed under the 3-clause BSD as described at http://www.simplemachines.org/about/smf/license.php
 */
function soulraver_smf_add_buttons(&$buttons) {
	global $txt;
	
	$row_position = 2; // Which row are we adding the buttons on (starting at 1)
	$col_position = 15; // which col are we inserting the buttons
	
	// Define the new buttons
	$newbuttons = array(
		array(
			'image'       => 'zomg_spoilers',
			'code'        => 'spoiler',
			'before'      => '[spoiler]',
			'after'       => '[/spoiler]',
			'description' => $txt['spoiler']
		),
		
		array(
			'image'       => 'mythic_die',
			'code'        => 'roll',
			'before'      => '[roll]',
			'after'       => '[/roll]',
			'description' => $txt['roll']
		)
	);
	
	// Move from x,y coordinates to array values
	$row_position--;
	$col_position--;
	
	// Get the individual button rows
	foreach ($buttons as $sub_buttons)
		$button_row[] = $sub_buttons;
		
	// set the row bounds, not less than 0, not more than an extra row
	$total_rows = count($buttons);
	$row_position = min(max($row_position,0),$total_rows);
	
	// If the user specified a new row add one
	if ($row_position == $total_rows)
	{
		$button_row[$row_position] = array();
		$total_rows++;
	}
	// Set the col bounds, not less than 0 not more than the number of columns in this row
	$total_cols = count($button_row[$row_position]);
	$col_position = min(max($col_position,0),$total_cols);
		
	// Insert the new buttons in to the row and col specified
	array_splice($button_row[$row_position], $col_position, $total_cols, array_merge($newbuttons, array_slice($button_row[$row_position], $col_position))); 
	// join the button array back together
	$buttons = array();
	for ($i = 0; $i < $total_rows; $i++)
		$buttons[] = $button_row[$i];
	return;
}

/* integrate_bbc_codes */
function soulraver_smf_add_codes(&$codes) {
	global $txt;
	
	$codes[] = array(
		'tag'         => 'spoiler',
		'type'        => 'unparsed_content',
		'content'     => '$1',
		'validate'    => function (&$tag, &$data, $disabled) {
			global $txt;
			$label = empty($txt['spoiler']) ? 'Spoiler*' : $txt['spoiler'];
			
			$data = soulraver_spoiler_main($label) . $data . soulraver_spoiler_tail();
		},
		'block_level' => true
	);
	$codes[] = array(
		'tag'         => 'spoiler',
		'type'        => 'unparsed_equals',
		'before'      => '$1',
		'after'       => soulraver_spoiler_tail(),
		'validate'    => function (&$tag, &$data, $disabled) {
			global $txt;
			$label = empty($data) ? (empty($txt['spoiler']) ? 'Spoiler*' : $txt['spoiler']) : $data;
			
			$data = soulraver_spoiler_main($label);
		},
		'block_level' => true
	);
}

function soulraver_spoiler_main($label) {
	return '<label class="sr-smf-toggle-wrap"><div class="sr-smf-toggle-label">' . $label . '</div>' .
			'<input type="checkbox" checked="checked" class="sr-smf-toggle-check" value="1">' .
			'<div class="sr-smf-toggle-content">';
}
function soulraver_spoiler_tail() {
	return '</div></label><br>';
}

function soulraver_dice_everything($message) {
	global $txt;
	
	$regex_outer = '\\[roll(?:=(\w+)(?:,(>|<|>=|<=|=)?\+?([-]?\d+))?)?\]([^\[]+)\[\/roll\]/ig';
	$regex_dice_groups = '/([+-])?(?:(\d+)m)?(\d*)d(F|\d+)(?:([+-])([\d]+))?((?:(?:v[\d]*)|(?:\^[\d]*)|(?:k[\d]*)|(?:r[\d]*)|(?:\*(?:[\d]+(?:\.[\d]+)?))|(?:\/(?:[\d]+(?:\.[\d]+))?)|(?:e[\d]*)|(?:s[\d]*)|c|a|(?:x[\d]*)|(?:u[\d]*))*)/g';
	$regex_dice_group_modifiers = '/([dv\^kr\*\/escax])(\d+(?:\.\d+)?)?(?: |$)/g';
	
	preg_match_all($regex_outer, $output, $matches);
	
	// TODO: find the doc with all the different processing descriptors
	for ($i = 0; $i < count($matches); $i++) {
		$input = $matches[0][$i];
		$title = empty($matches[1][$i]) ? $txt['roll'] : $matches[1][$i];
		$direction = empty($matches[2][$i]) ? '>' : $matches[2][$i];
		$target = $matches[3][$i];
		$dice_groups = $matches[4][$i];
		$total = 0;
		
		$output = '';
		
		preg_match_all($regex_dice_groups, $dice_groups, $matches_dice);
		
		$breakdown = '';
		for ($j = 0; $j < count($matches_dice); $j++) {
			/* $matches_dice
			 * 0 => full match
			 * 1 => optional +- sign for entire group
			 * 2 => optional count for 'm' 
			 * 3 => optional #d
			 * 4 => d(#) or dF
			 * 5 => sign for optional [6]
			 * 6 => optional flat numeric adjustment
			 * 7 => optional group modifiers
			*/
			
			
		}
		
		if ($breakdown != '') {
			$csstotal = '';
			if ($target !== '') {
				if (($direction == '>' && $total > $target) ||
					($direction == '>=' && $total >= $target) ||
					($direction == '<' && $total < $target) ||
					($direction == '<=' && $total <= $target)) {
					$total = 'Success';
				} else {
					$total = 'Failure';
				}
				
				$csstotal = ' class="' . ($total == 'Success' ? 'roll_success' : 'roll_failure') . '"';
			}
			
			$summary = $title . ' ' . '<strong' . $csstotal . '>' . $total . '</strong>';
			$output = soulraver_spoiler_main($summary) . $breakdown . soulraver_spoiler_tail();		
		}
		
		if ($output != '') {
			$message = substr_replace($message, $output, strpos($input), count($input));
		}
	}
	
	$message .= 'loltest';
	return $message;
}
?>