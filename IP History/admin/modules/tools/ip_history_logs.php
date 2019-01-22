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
 #inner {
 	display:flex;
 }
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
	// Query Max for large forums & Query Filters
	$query = $db->simple_select("ip_history", "*", "", array('order_by' => "date"));
	$table = new Table();
	$table->construct_header("UserID", array('class' => "align_center")); 
	$table->construct_header("Page");
	$table->construct_header("Useragent",  array('class' => "align_center"));
	$table->construct_header("IP",  array('class' => "align_center"));
	$table->construct_header("Date",  array('class' => "align_center"));
	// Output Every row from DB
while($section = $db->fetch_array($query))
	{
		$table->construct_cell("<a href=\"/user-{$section['uid']}.html\">{$section['uid']}</a>", array("class" => "align_center", "width" => '60'));
		$table->construct_cell("<strong><a href=\"/{$section['page']}\">{$section['page']}</a></strong>");
		$table->construct_cell($section['useragent']);
		$table->construct_cell($section['ip'], array("class" => "align_center", "width" => '90'));
		$table->construct_cell($section['date'], array("class" => "align_center", "width" => '90'));

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
	?>
</div>
</div>
	<?php

