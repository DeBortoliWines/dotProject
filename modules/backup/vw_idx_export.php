
<form action="?m=backup" method="post" style="display:inline">
    <input type="hidden" name="dosql" value="do_export">

    <select name="project_id" >
      <?php
      $q = new DBQuery();
      $q->addQuery('project_id, project_name');
      $q->addTable('projects');
      $sql = $q->prepare();
      $projects = db_loadList($sql);
      $q->clear();
      foreach($projects as $project ){
          ?>
          <option value="<?php echo $project["project_id"] ?>">
            <?php echo $project["project_name"] ?>
          </option>
          <?php
      }
      ?>
    </select> 
    <input type="submit" value="Export" />
</form>


