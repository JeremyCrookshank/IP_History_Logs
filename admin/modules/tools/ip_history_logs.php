<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$page->add_breadcrumb_item("IP History", "index.php?module=tools-ip_history_logs");
$sub_tabs['ip_history_logs'] = array(
	'title' => 'IP History Logs',
	'link' => "index.php?module=tools-ip_history_logs",
	'description' => ''
);

$plugins->run_hooks("admin_tools_ip_history_logs_begin");
$page->output_header('IP History Logs');
$plugins->run_hooks("admin_tools_ip_history_logs_view");

?>


<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/zf-6.4.3/jq-3.3.1/dt-1.10.18/datatables.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/v/zf-6.4.3/jq-3.3.1/dt-1.10.18/datatables.min.js"></script>


<script>
$(document).ready(function() {
    $('.HistoryTable').DataTable();
    $('.HistoryTable').removeClass("dataTable");
} );
</script>

 <style>
.HistoryTable {
 margin: 0, 0 !important; 
}

.menu .active>a {
    background: inherit !important;
}

.menu {
    display: initial !important;
}

</style>
	<?php

	// TODO
	// Query Max for large forums & Query Filters - Done
	// Add Language File instead of hard coding.
	
	// Default Query Params
	$where = 'WHERE 1=1';
	$prunewhere = '1=1';
	$limit = 1500;

	function isBinary($str) {
    return preg_match('~[^\x20-\x7E\t\r\n]~', $str) > 0;
	}

	// This will allow us to handle old IP's(text) and binary ones
	function HandleIP($IP) {
	    global $db;
		if (isBinary($IP)) {
		return my_inet_ntop($db->unescape_binary($IP));
		} else {
			return $IP;
		}
	}
	
	// If we're searching for a userid
	if($mybb->input['uid'])
	{
		$where .= " AND ip.uid='".$mybb->get_input('uid', MyBB::INPUT_INT)."'";
		$prunewhere .= " AND uid='".$mybb->get_input('uid', MyBB::INPUT_INT)."'";
	}
	
	// If we're pruning a user/all users
	if($mybb->input['action'] == "prune" && $mybb->request_method == "post") 
	{
 		$query = $db->delete_query("ip_history", $prunewhere);
 		$num_deleted = $db->affected_rows();
 		log_admin_action($num_deleted);
		flash_message('Deleted '.$num_deleted.' Rows', 'success');
		admin_redirect("index.php?module=tools-ip_history_logs");
	}

	// Max number of results
	if($mybb->input['limit'])
	{	
		$limit = $mybb->get_input('limit', MyBB::INPUT_INT);
	}
	
	// Order by user/date
	switch($mybb->input['sortby'])
	{
		case "username":
			$sortby = "u.username";
			break;
		case "uid":
			$sortby = "ip.uid";
			break;
		case "page":
			$sortby = "ip.page";
			break;
		default:
			$sortby = "ip.createdate";
	}
	
	$order = 'asc';
	if($mybb->input['order'] != "asc")
	{
		$order = "desc";
	}
		
	// Have to use this instead as MyBB Simple Select as it doesn't support inner joins
	$query = $db->query("SELECT u.username, ip.uid, ip.ip, ip.page, ip.useragent, ip.createdate FROM ".TABLE_PREFIX."ip_history ip 
	INNER JOIN ".TABLE_PREFIX."users u ON (ip.uid = u.uid) 
	{$where}
	ORDER BY {$sortby} {$order}
	LIMIT {$limit}
	");

	$table = new Table();
	$table->construct_header("Username", array('class' => "align_center")); 
	$table->construct_header("UserID", array('class' => "align_center")); 
	$table->construct_header("Page");
	$table->construct_header("Useragent",  array('class' => "align_center"));
	$table->construct_header("IP",  array('class' => "align_center"));
	$table->construct_header("Date",  array('class' => "align_center"));
	// Output Every row from DB
while($section = $db->fetch_array($query))
	{
	    $username = htmlspecialchars_uni($section['username']);
		$table->construct_cell("<a target='_blank' href=\"{$mybb->settings['bburl']}/member.php?action=profile&uid={$section['uid']}\">$username</a>", array("class" => "align_center", "width" => '60'));
		$table->construct_cell("<a target='_blank' href=\"{$mybb->settings['bburl']}/member.php?action=profile&uid={$section['uid']}\">{$section['uid']}</a>", array("class" => "align_center", "width" => '60'));
		$table->construct_cell("<strong><a href=\"{$mybb->settings['bburl']}/{$section['page']}\">{$section['page']}</a></strong>");
		$table->construct_cell(htmlspecialchars_uni($section['useragent']));
		$table->construct_cell(HandleIP($section['ip']), array("class" => "align_center", "width" => '90'));
		$table->construct_cell(my_date('relative',$section['createdate'], '', 2), array("class" => "align_center", "width" => '90'));
		$table->construct_row();
	}

	// If we have no data yet.
	if($table->num_rows()  == 0)
	{
		$table->construct_cell("No Data", array('colspan' => 4));
		$table->construct_row();
	}

	// Allows me to attach a class so I can have DataTables
	$table->output("IP History Logs", 1, "HistoryTable", false);
	
		$sort_by = array(
		'date' => 'Date',
		'username' => 'Username',
		'page' => 'Page',
		'uid' => 'UserId'
	);

	$order_array = array(
		'asc' => 'Ascending',
		'desc' => 'Descending'
	);

	
	// Adapted Fetch filter options from https://github.com/mybb/mybb/blob/5a836f408162dbf981b94c49e9204ad64280ef7a/admin/modules/tools/modlog.php
	$sortbysel[$mybb->input['sortby']] = "selected=\"selected\"";
	$ordersel[$mybb->input['order']] = "selected=\"selected\"";
	$user_options[''] = 'All Users';
	$user_options['0'] = '----------';
	$query = $db->query("
		SELECT DISTINCT ip.uid, u.username
		FROM ".TABLE_PREFIX."ip_history ip
		LEFT JOIN ".TABLE_PREFIX."users u ON (ip.uid=u.uid)
		ORDER BY u.username ASC
	");
	while($user = $db->fetch_array($query))
	{
		// Deleted Users
		if(!$user['username'])
		{
			$user['username'] = htmlspecialchars_uni($lang->na_deleted);
		}
		$selected = '';
		if($mybb->input['uid'] == $user['uid'])
		{
			$selected = "selected=\"selected\"";
		}
		$user_options[$user['uid']] = htmlspecialchars_uni($user['username']);
	}
	
	$form = new Form("index.php?module=tools-ip_history_logs", "post");
	$form_container = new FormContainer('Filter IP History');
	$form_container->output_row('Username', "", $form->generate_select_box('uid', $user_options, $mybb->input['uid'], array('id' => 'uid')), 'uid');
	$form_container->output_row('Sort By', "", $form->generate_select_box('sortby', $sort_by, $mybb->input['sortby'], array('id' => 'sortby')), 'sortby');
	$form_container->output_row('Order', "", $form->generate_select_box('order', $order_array, $mybb->input['order'], array('id' => 'order')), 'order');
	$form_container->output_row('Limit', "", $form->generate_numeric_field('limit', $limit, array('id' => 'limit', 'min' => 1)), 'limit');
	$form_container->end();
	$buttons[] = $form->generate_submit_button('Filter IP History');
	$form->output_submit_wrapper($buttons);
	$form->end();

?>

</br>
<?php

	$prune = new Form("index.php?module=tools-ip_history_logs&amp;action=prune", "post");
	$form_container = new FormContainer('Prune IP History');
	$form_container->output_row('Username', "", $prune->generate_select_box('uid', $user_options, $mybb->input['uid'], array('id' => 'uid')), 'uid');
	$form_container->end();
	$prunebtn[] = $prune->generate_submit_button('Prune');
	$prune->output_submit_wrapper($prunebtn);
	$prune->end();

	$page->output_footer();


	


