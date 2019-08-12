<?php
//https://docs.microsoft.com/en-us/office-project/xml-data-interchange/task-elements-and-xml-structure?view=project-client-2016
if (!defined("DP_BASE_DIR")) {
    die("You should not access this file directly.");
}

require_once (DP_BASE_DIR . '/modules/tasks/tasks.class.php');
require_once (DP_BASE_DIR . '/modules/projects/projects.class.php');
require_once (DP_BASE_DIR . '/modules/companies/companies.class.php');
require_once (DP_BASE_DIR . '/modules/admin/admin.class.php');

$project_id=$_POST["project_id"];


$project = new CProject();
$project->load($project_id);

$company= new CCompany();
$company->load($project->project_company);
//echo $project->project_name;
//echo $project->project_company;
//echo $company->company_name;
$user = new  CUser();
$user->load($project->project_owner);
//SimpleXMLElement 

$project_xml= simplexml_load_string('<Project></Project>');
// Modify a node

$project_xml->Name=$project->project_name;
$project_xml->UID = $project->project_id;
$project_xml->Company = $company->company_name;
$project_xml->StartDate = $project->project_start_date;
$project_xml->FinishDate = $project->project_end_date;
$project_xml->Manager = $user->user_username;

$q = new DBQuery();
$q->addQuery('task_id');
$q->addTable('tasks');
$q->addWhere('task_project =' . $project_id);

$sql = $q->prepare();
$tasks = db_loadList($sql);
$q->clear();

$i=0;
foreach($tasks as $task ){
    $taskObj=new CTask();
    $taskObj->load($task['task_id']);
    $project_xml->Tasks->Task[$i]->ID=$taskObj->task_id;
    $project_xml->Tasks->Task[$i]->UID=$taskObj->task_id;
    $project_xml->Tasks->Task[$i]->Name=$taskObj->task_name;
    $project_xml->Tasks->Task[$i]->Start=$taskObj->task_start_date;
    $project_xml->Tasks->Task[$i]->Finish=$taskObj->task_end_date;
    $project_xml->Tasks->Task[$i]->Duration=$taskObj->task_duration;
    $project_xml->Tasks->Task[$i]->Milestone=$taskObj->task_milestone;
    $project_xml->Tasks->Task[$i]->PercentComplete=$taskObj->task_percent_complete;
	$project_xml->Tasks->Task[$i]->Priority=$taskObj->task_priority;
	$project_xml->Tasks->Task[$i]->Cost= $taskObj->task_target_budget;
	
	
	
	$sql="SELECT dependencies_req_task_id FROM dotp_task_dependencies where dependencies_task_id=". $task['task_id'];
	$dependencies = db_loadList($sql);
	$j=0;
	foreach($dependencies as $dep){
		$project_xml->Tasks->Task[$i]->PredecessorLink[$j]->PredecessorUID= $dep["dependencies_req_task_id"];
		$project_xml->Tasks->Task[$i]->PredecessorLink[$j]->Type="FS";
		$project_xml->Tasks->Task[$i]->PredecessorLink[$j]->CrossProject="0";
		$project_xml->Tasks->Task[$i]->PredecessorLink[$j]->LinkLag="0";
		$j++;
	}
	
	$user = new  CUser();
	$user->load($taskObj->task_owner);
	$project_xml->Tasks->Task[$i]->Contact= $user->user_username;
	
    $i++;
}
// Saving the whole modified XML to a new filename
$fileName='project_'.$project_id.'_export.xml';
$project_xml->asXml($fileName);
echo "<script>window.open('$fileName');</script>";

?>