<?php
function tableInsert($table, $keyCol=1, $keyVal=1)
{
  global $file_type;
  $out = "";

	$q = new DBQuery;
	$q->addQuery('*');
	$q->addTable($table);
	$q->addWhere("$keyCol='$keyVal'");
	$list = $q->loadList();
//  $list = db_loadList("SELECT * FROM $table WHERE $keyCol='$keyVal'");
  if ($file_type == 'csv')
  {
    $out = headerList($list[0]) . "\n";
    foreach($list as $row)
      $out .= bodyList($row) . "\n";
  }
  else if ($file_type == 'sql')
  {
    foreach ($list as $row)
    	$out = valuesList($table, $row);
  }
	else if ($file_type == 'xml')
	{
		$out = "<$table>";
		if (is_array($list))
			foreach($list as $row)
				$out .= xmlList($row);
		$out .= "</$table>";
	}

  return $out;
}

function dumpAll()
{
  global $dPconfig;
  $alltables = mysql_list_tables($dPconfig['dbname']);

  while ($row = mysql_fetch_row($alltables))
    $output .= tableInsert($row[0]) . '
';

  return $output;
}

function dumpTasks($project=-1, $task=-1)
{
  $output = "";
  $sql = "SELECT * FROM tasks";
  if ($project != -1)
  {
    $sql .= " WHERE task_project=$project";
    $output .= "#task_project#\n";
  }
  else if ($task != -1)
    $sql .= " WHERE task_id=$task";

  // Used for dynamic ID setting.
  $tasks = db_loadList($sql);
  foreach ($tasks as $task)
  {
    $output .= valuesList("tasks", $task);

    $output .= "#dependencies_task_id#\n";
    $output .= tableInsert("task_dependencies", "dependencies_task_id", $task['task_id']);
    $output .= "#task_log_task#\n";
    $output .= tableInsert("task_log", "task_log_task", $task['task_id']);
  }

  return $output;
}

function dumpForums($project=-1, $forum=-1)
{
  $output = "";
  $sql = "SELECT * FROM forums";
  if ($forum != -1)
  {
    $sql .= " WHERE forum_project='$row[project_id]'";
    $output .= "#forum_project#\n";
  }

  $forums = db_loadList($sql);
  foreach ($forums as $forum)
  {
    $output .= valuesList("forums", $forum);

    $output .= "#message_forum#\n";
    $output .= tableInsert("forum_messages", "message_forum", $forum['forum_id']);
    // Doesn't make sense - users/forums don't exist
    // $output .= tableInsert("forum_watch", "watch_forum", $forum['forum_id']);
  }

  return $output;
}

function dumpProject($project=-1)
{
  $output = "";
  $sql = "SELECT * FROM projects";
 	if ($project != -1)
   	$sql .= " WHERE project_id=$project";

  $rows = db_loadList($sql);
 	foreach ($rows as $row)
 	{
   //TODO: if parent company doesn't exist, create it "INSERT INTO companies WHERE company_id='$row[project_company]'"
   //TODO: Check if helpdesk and other modules exists, and insert their tables as well.
    $output .= valuesList('projects', $row);
   	$output .= dumpTasks($row['project_id']);
 	  $output .= dumpForums($row['project_id'], -1);
    $output .= tableInsert("files", "file_project", $row['project_id']);
 	  $output .= tableInsert("events", "event_project", $row['project_id']);
  }

	return $output;
}



function dumpCompanies($company)
{
$output = "";
  $sql = "SELECT * FROM companies";
  if ($company != -1)
    $sql .= " WHERE company_id=$company";

  $rows = db_loadList($sql);
  foreach ($rows as $row)
    $output .= valuesList("companies", $row); 

  $output .= "#project_company#\n";
  $sql = "SELECT * FROM projects";
  if ($company != -1)
    $sql .= " WHERE project_company=$company";

  $rows = db_loadList($sql);
  foreach ($rows as $row)
    $output .= dumpProject($row['project_id']);

  return $output;
}
?>
