<?php

// Disallow direct access to this file for security reasons
// TODO: Language File

if (!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.");
}

$plugins->add_hook('global_start', 'ip_history_logs_record_ip');
$plugins->add_hook("admin_tools_menu_logs", "ip_history_logs_admin_menu");
$plugins->add_hook("admin_tools_action_handler", "ip_history_logs_admin_action_handler");
$plugins->add_hook("admin_tools_permissions", "ip_history_logs_admin_permissions");

function ip_history_logs_info()
{
	return array(
		"name" => "IP History",
		"description" => "This keeps a record of a users IP history as they use the website. This is useful for auditing fraud/ban evaders/general/when people start using VPNS/Proxys during their user activity. It can record every instance of a users IP when it changes and how often, the page they were viewing and their useragent.",
		"website" => "https://github.com/JeremyCrookshank/IP_History_Logs",
		"author" => "Jeremy Crookshank",
		"authorsite" => "https://github.com/JeremyCrookshank/IP_History_Logs",
		"version" => "1.3.0",
		"guid" => "",
		"compatibility" => "*"
	);
}

function ip_history_logs_admin_menu($sub_menu)
{
	// We can add ours to the bottom of logs list
	$key = count($sub_menu) * 10 + 10;
    $sub_menu[$key] = array('id' => 'ip_history_logs', 'title' => 'IP History Logs', 'link' => 'index.php?module=tools-ip_history_logs');
    return $sub_menu;
}

function ip_history_logs_admin_action_handler($actions)
{
    $actions['ip_history_logs'] = array('active' => 'ip_history_logs', 'file' => 'ip_history_logs.php');
    return $actions;
}

// Add Permissions to view
function ip_history_logs_admin_permissions($admin_permissions)
{
    $admin_permissions['ip_history_logs'] = "Ability to view Users IP History";
    return $admin_permissions;
}

function ip_history_logs_record_ip()
{

	// Restore Visitors IP if using CloudFlare
	if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
  	$_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
	}

	//Get basic session infomation
	global $db, $mybb;
	$ip = my_inet_pton(filter_input(INPUT_SERVER, 'REMOTE_ADDR'));
	$user = $mybb->user['uid'];
	$useragent = $db->escape_string($_SERVER['HTTP_USER_AGENT']);
	$page = basename($_SERVER['REQUEST_URI']);

	//Make sure they're logged in
	if ($user != 0) {
		if ($mybb->settings['enable_uniqueIP'] == 1 || $mybb->settings['enable_uniqueUA'] == 1 ) {
			$ip_event = array(
				"ip" => $ip,
				"uid" => $user,
				"createdate" => (int) TIME_NOW
			);

			// Optionals
			if ($mybb->settings['enable_useragentrecord'] == 1) {
				$ip_event["useragent"] = $useragent;
			}

			if ($mybb->settings['enable_pagerecording'] == 1) {
				$ip_event["page"] = $db->escape_string($page);
			}

			// Check the user hasn't had this IP/UA before

			$query = $db->simple_select("ip_history", "COUNT(*) as 'unique'", "IP='$ip' AND uid='$user'", array());
			$uniqueIP = $db->fetch_field($query, "unique");
			$query = $db->simple_select("ip_history", "COUNT(*) as 'unique'", "useragent='$useragent' AND uid='$user'", array());
			$uniqueUA = $db->fetch_field($query, "unique");
			if ($uniqueIP == 0 || $uniqueUA == 0) {
				$db->insert_query("ip_history", $ip_event);
			}
			else {
				return;
			}

			// Else we want to track all user activity

		}
		else {
			$ip_event = array(
				"ip" => $ip,
				"uid" => $user
			);

			// Optionals

			if ($mybb->settings['enable_useragentrecord'] == 1) {
				$ip_event["useragent"] = $db->escape_string($useragent);
			}

			if ($mybb->settings['enable_pagerecording'] == 1) {
				$ip_event["page"] = $page;
			}

			$db->insert_query("ip_history", $ip_event);
		}
	}
}

function ip_history_logs_install()
{
	global $db, $mybb;
	// Optimised for storing and retrieving IPV4 & IPV6 in an efficent manner
	// Storing date in UTC now for efficency and greate compatibility with older MYSQL DB's
	$db->write_query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."ip_history` (
      ipid int(10) UNSIGNED NOT NULL auto_increment,
      uid int(10) UNSIGNED NOT NULL DEFAULT '0',
      ip VARBINARY(16) NOT NULL,
      page VARCHAR(512) NULL,
	  useragent varchar(255) NULL,
	  createdate int(10) UNSIGNED NOT NULL DEFAULT '0',
      PRIMARY KEY  (`ipid`)
    ) ENGINE=MyISAM  
      COLLATE=utf8_general_ci
	  DEFAULT CHARSET=utf8;
     ");
	 
	 // Allow us to search for existing IP addresses faster
	$db->write_query("CREATE INDEX IDX_IP ON " . TABLE_PREFIX . "ip_history (ip);");


	$setting_group = array(
		'name' => 'ip_history_logs',
		'title' => 'IP History Settings',
		'description' => 'Adjust the IP History settings',
		'disporder' => 5, // The order your setting group will display
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $setting_group);
	$setting_array = array(
		'enable_pagerecording' => array(
			'title' => 'Enable Page Recording',
			'description' => 'Do you want to record the page they visit?',
			'optionscode' => 'yesno',
			'value' => 1,
			'disporder' => 1
		) ,
		'enable_uniqueIP' => array(
			'title' => 'Record Unique IP',
			'description' => 'Record only unique IPs for users. Unticking this may cause a large amount of data due to every time they view a page.',
			'optionscode' => 'yesno',
			'value' => 1,
			'disporder' => 2
		) ,
		'enable_useragentrecord' => array(
			'title' => 'Record User Agent',
			'description' => 'Record the user agent for each record.',
			'optionscode' => 'yesno',
			'value' => 1,
			'disporder' => 3
		) ,
		'ip_history_rem_date' => array(
			'title' => 'Log Removal Days',
			'description' => 'How often should logs be cleared? - 0 For never.',
			'optionscode' => 'numeric',
			'value' => 365,
			'disporder' => 4
		) ,
		
			'enable_uniqueUA' => array(
			'title' => 'Record User Agent',
			'description' => "Record the users unique user agent every time it changes.",
			'optionscode' => 'yesno',
			'value' => 1,
			'disporder' => 5
		) ,
		
	);
	foreach($setting_array as $name => $setting) {
		$setting['name'] = $name;
		$setting['gid'] = $gid;
		$db->insert_query('settings', $setting);
	}

	rebuild_settings();
}

function ip_history_logs_is_installed()
{
	global $db;
	if ($db->table_exists("ip_history")) {
		return true;
	}

	return false;
}

function ip_history_logs_uninstall()
{
	global $db;
	$db->write_query("DROP TABLE " . TABLE_PREFIX . "ip_history");
	$db->delete_query('settings', "name IN ('enable_pagerecording','enable_uniqueIP','enable_useragentrecord', 'ip_history_rem_date' , 'enable_uniqueUA')");
	$db->delete_query('settinggroups', "name = 'ip_history_logs'");
	rebuild_settings();
}
function ip_history_logs_activate()
{
	global $db;
	require_once MYBB_ROOT."inc/functions_task.php";
	$new_task = array(
		"title"			=> "IP History Log Removal",
		"description"	=> "Automatically remove IP history logs based on days elapsed. Configured in settings.",
		"file"			=> "ip_history",
		"minute"		=> "0",
		"hour"			=> "0",
		"day"			=> "*",
		"month"			=> "*",
		"weekday"		=> "*",
		"enabled"		=> 1,
		"logging"		=> 1,
		"locked"		=> 0
	);

	$new_task['nextrun'] = fetch_next_run($new_task);
	$db->insert_query("tasks", $new_task);
}

function ip_history_logs_deactivate()
{
}
