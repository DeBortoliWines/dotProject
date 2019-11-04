
<table class="tbl" style="width:100%">
  <thead>
            <tr>
				<th colspan="2">Project name</th>
				
			</tr>
	</thead>
  <?php
      $q = new DBQuery();
      $q->addQuery('project_id, project_name');
      $q->addTable('projects');
      $sql = $q->prepare();
      $projects = db_loadList($sql);
      $q->clear();
      foreach($projects as $project ){
  ?>

	<tbody>
	<tr>
		<td><?php echo $project["project_name"] ?> (id:<?php echo $project["project_id"] ?> )</td>
		<td>
			<form action="?m=export_import" method="post" style="display:inline">
				<input type="hidden" name="dosql" value="do_export">
				<input name="project_id" type="hidden" value="<?php echo $project["project_id"] ?>" />		
				<input type="submit" value="Export" />
			</form>
		</td>
	</tr>
<?php
	   }
      ?>
	  </tr>
	  </tbody>
</table>
   


