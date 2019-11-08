<?php
require_once DP_BASE_DIR . '/modules/projects/frappegantt.php';

global $project_id, $m;
global $st_projects_arr;


$df = $AppUI->getPref('SHDATEFORMAT');
$projectPriority = dPgetSysVal( 'ProjectPriority' );
$projectStatus = dPgetSysVal( 'ProjectStatus' );

$scroll_date = 1;
$display_option = dPgetCleanParam($_POST, 'display_option', 'this_month');

Gantt::ProjectTasks($project_id)->render();
?>

<form name="editFrm" method="post" action="?<?php echo ('m=' . $m . '&amp;a=' . $a . '&amp=tab=' . $tab . '&amp;project_id=' . $project_id); ?>">
    <input type="hidden" name="display_option" value="<?php echo $display_option;?>" />
    <table border="0" cellpadding="4" cellspacing="0" style="table-layout:fixed;">
        <tr>
            <td align="left" valign="top" width="20">
                <?php if ($display_option != "all") { ?>
                <a href="javascript:scrollPrev()">
                    <img src="./images/prev.gif" width="16" height="16" alt="<?php echo $AppUI->_('previous');?> border="0">
                </a>
                <?php } ?>
            </td>
            <td align="right" nowrap="nowrap"><?php echo $AppUI->_('From');?>:</td>
            <td align="left" nowrap="nowrap">
                <input type="hidden" name="sdate" value="<?php echo $start_date->format(FMT_TIMESTAMP_DATE);?>" />
                <input type="text" class="text" name="show_sdate" value="<?php echo $start_date->format($df);?>" size="12" disabled="disabled" />
                <a href="javascript:popCalendar('sdate')">
		            <img src="./images/calendar.gif" width="24" height="12" alt="" border="0" />
                </a>
            </td>
            <td align="right" nowrap="nowrap"><?php echo $AppUI->_('To');?>:</td>

            <td align="left" nowrap="nowrap">
		        <input type="hidden" name="edate" value="<?php echo $end_date->format(FMT_TIMESTAMP_DATE);?>" />
		        <input type="text" class="text" name="show_edate" value="<?php echo $end_date->format($df);?>" size="12" disabled="disabled" />
		        <a href="javascript:popCalendar('edate')">
		            <img src="./images/calendar.gif" width="24" height="12" alt="" border="0" />
		        </a>
	            <td align="left">
		            <input type="button" class="button" value="<?php echo $AppUI->_('submit');?>" onclick='javascript:document.editFrm.display_option.value="custom";submit();'>
                </td>
            </td>

            <td align="right" valign="top" width="20">
                <?php if ($display_option != "all") { ?>
	            <a href="javascript:scrollNext()">
	  	            <img src="./images/next.gif" width="16" height="16" alt="<?php echo $AppUI->_('next');?>" border="0" />
	            </a>
                <?php } ?>
            </td>
        </tr>
        <?php if ($a == 'todo') { ?>
        <tr>
	        <td align="center" valign="bottom" nowrap="nowrap" colspan="7">
		        <input type="hidden" name="show_form" value="1" />
		        <table width="100%" border="0" cellpadding="1" cellspacing="0" style="table-layout:fixed;">
			        <tr>
			            <td align="center" valign="bottom" nowrap="nowrap">
				            <input type="checkbox" name="showPinned" id="showPinned" <?php echo $showPinned ? 'checked="checked"' : ''; ?> />
				            <label for="showPinned"><?php echo $AppUI->_('Pinned Only'); ?></label>
			            </td>
                        <td align="center" valign="bottom" nowrap="nowrap">
                            <input type="checkbox" name="showArcProjs" id="showArcProjs" <?php echo $showArcProjs ? 'checked="checked"' : ''; ?> />
                            <label for="showArcProjs"><?php echo $AppUI->_('Archived Projects'); ?></label>
                        </td>
                        <td align="center" valign="bottom" nowrap="nowrap">
                            <input type="checkbox" name="showHoldProjs" id="showHoldProjs" <?php echo $showHoldProjs ? 'checked="checked"' : ''; ?> />
                            <label for="showHoldProjs"><?php echo $AppUI->_('Projects on Hold'); ?></label>
                        </td>
                        <td align="center" valign="bottom" nowrap="nowrap">
                            <input type="checkbox" name="showDynTasks" id="showDynTasks" <?php echo $showDynTasks ? 'checked="checked"' : ''; ?> />
                            <label for="showDynTasks"><?php echo $AppUI->_('Dynamic Tasks'); ?></label>
                        </td>
                        <td align="center" valign="bottom" nowrap="nowrap">
                            <input type="checkbox" name="showLowTasks" id="showLowTasks" <?php echo $showLowTasks ? 'checked="checked"' : ''; ?> />
                            <label for="showLowTasks"><?php echo $AppUI->_('Low Priority Tasks'); ?></label>
                        </td>
			        </tr>
		        </table>
	        </td>
        </tr>
        <?php } ?>
            <td align="center" valign="bottom" colspan="12"><?php
                if ($display_option != "this_month") {
                    echo "<a href='javascript:showThisMonth()'>" . $AppUI->_('show this month') . "</a>";
                } else {
                    echo "<strong>" . $AppUI->_('show this month') . "</strong>";
                }

                echo " : ";
            
                if ($display_option != "all") {
                    echo "<a href='javascript:showFullProject()'>" . $AppUI->_('show all') . "</a>";
                } else {
                    echo "<strong>" . $AppUI->_('show all') . "</strong>";
                }
            ?><br />
            </td>
        </tr>
    </table>
</form>
<table cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="table-layout: fixed;">
    <tr>
        <?php
            if ($a != "todo") {
                $q = new DBQuery;
                $q->addTable("tasks");
                $q->addQuery("COUNT(*) AS N");
                $q->addWhere("task_project=" . $project_id);
                $cnt = $q->loadList();
                $q->clear();
            } else {
                $cnt[0]["N"] = ((empty($tasks)) ? 0 : 1);
            }
            if ($cnt[0]['N'] > 0) {
                $src = ('?m=tasks&amp;a=gantt&amp;suppressHeaders=1&amp;project_id=' . $project_id 
	                . (($display_option == 'all') ? '' : ('&amp;start_date=' . $start_date->format('%Y-%m-%d') 
	                . '&amp;end_date=' . $end_date->format('%Y-%m-%d'))) . "&width='" 
			        . "+((navigator.appName=='Netscape'?window.innerWidth:document.body.offsetWidth)*0.95)" 
			        . "+'&amp;showLabels=" . $showLabels . '&amp;showWork=' . $showWork 
	                . '&amp;sortByName=' . $sortByName . '&amp;showPinned=' . $showPinned 
	                . '&amp;showArcProjs=' . $showArcProjs . '&amp;showHoldProjs=' . $showHoldProjs 
	                . '&amp;showDynTasks=' . $showDynTasks . '&amp;showLowTasks=' . $showLowTasks 
	                . '&amp;caller=' . $a . '&amp;user_id=' . $user_id);
            }

<script>console.log(<?php echo $project_id;?>);</script>
<!-- <table width="100%" border="0" cellpadding="5" cellspacing="1">
<tr>
    <td align="center" colspan="20">
<?php
      $src = "?m=projectdesigner&a=gantt&suppressHeaders=1&showLabels=1&proFilter=&showInactive=1showAllGantt=1&project_id=$project_id&width=' + ((navigator.appName=='Netscape'?window.innerWidth:document.body.offsetWidth)*0.90) + '";      
      echo "<script>document.write('<img src=\"$src\">')</script>";
?>
</td>
</table> -->
