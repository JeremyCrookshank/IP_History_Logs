<?php
/**
 * IP History Log
 * Optional Automatic Log Cleaning Task
 */

function task_ip_history_logs($task)
{
	global $mybb, $db;

	// Delete IP Logs every 365 days by default or 0 for never.
	if((int)$mybb->settings['ip_history_rem_date'] > 0)
	{
		$datelimit = TIME_NOW-((int)$mybb->settings['ip_history_rem_date']*60*60*24);
		// Select all Ids which exceed cut off date.
		$query = $db->simple_select("ip_history", "id", "date < '".(int)$datelimit."'");
		while($history = $db->fetch_array($query))
		{
			$db->delete_query("ip_history", "id='{$history['id']}'");

		}
	}

	add_task_log($task, "Automatic IP History Cleaned");
}
