<?php

  global $AppUI, $canRead, $canEdit, $m;
  if (!$canRead)
    $AppUI->redirect("m=public&a=access_denied");
  if (dPgetParam($_POST, "submit")) {

		$filename = $_FILES['upload_file']['tmp_name'];
		$fileext = substr($filename, -4);
    $file = fopen($filename, "r");
    $filedata = fread($file, $_FILES['upload_file']['size']);

    require_once (DP_BASE_DIR . '/modules/tasks/tasks.class.php');
    require_once (DP_BASE_DIR . '/modules/projects/projects.class.php');
    require_once (DP_BASE_DIR . '/modules/companies/companies.class.php');
    require_once (DP_BASE_DIR . '/modules/admin/admin.class.php');

    $project_xml= simplexml_load_string($filedata);

    $project = new CProject();
   // $project->project_id=-1;
    $project->project_name = $project_xml->Name ."(Imported)";
    $project->project_start_date = $project_xml->StartDate."" ;
    $project->project_end_date = $project_xml->FinishDate."";
    $project->project_company=1;
    $project->project_company_internal=1;
    $project->project_department=1;
    $project->project_owner=1;
    $project->project_type=1;
    //print_r($project);
    //$project->store(); 
    $ret = db_insertObject('projects', $project, 'project_id');
    foreach( $project_xml->Tasks->Task as $task){
        $taskObj=new CTask();
       
        $taskObj->task_project=$project->project_id."";
        $taskObj->task_name=$task->Name."";
        $taskObj->task_start_date =  $task->Start."";
        $taskObj->task_end_date = $task->Finish."";
        $taskObj->task_duration = $task->Duration."";
        $taskObj->task_milestone = $task->Milestone."";
        $taskObj->task_percent_complete = $task->PercentComplete."";
       db_insertObject('tasks', $taskObj, 'task_id');
      // echo $taskObj->task_name;
    }
    //echo "id: ".$project->project_id;
    echo $AppUI->_('Project imported!');
    ?>
    <br />
    <a href="index.php?m=projects&a=view&project_id=<?php echo $project->project_id ?>"> Click here to open the imported project</a>
    <?php
   die();
    fclose($file);



		if ($fileext == '.sql');

		{

			$sql = explode(';', $filedata);

			foreach($sql as $insert)

		    db_exec($insert);

			$error = db_error();

		}



    if (isset($error))

 	    echo $AppUI->_('Failure') . $error;

    else

 	    echo $AppUI->_('Success');

	 }



?>



<form enctype="multipart/form-data" action="index.php?m=backup" method="post">

  <input type="file" name="upload_file" />

  <input type="hidden" name="MAX_FILE_SIZE" value="8388608" />

  <input type="submit" name="submit" value="<?php echo $AppUI->_("Import Data"); ?>" />

</form>

