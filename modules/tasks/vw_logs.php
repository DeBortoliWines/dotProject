<?php /* TASKS $Id$ */
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

global $AppUI, $task_id, $df, $m;

if (!(getPermission('task_log', 'access'))) {
	$AppUI->redirect('m=public&a=access_denied');
}

$problem = intval(dPgetParam($_GET, 'problem', null));
// get sysvals
$taskLogReference = dPgetSysVal('TaskLogReference');
$taskLogReferenceImage = dPgetSysVal('TaskLogReferenceImage');

?>
<script language="javascript" >
<?php
// security improvement:
// some javascript functions may not appear on client side in case of user not having write permissions
// else users would be able to arbitrarily run 'bad' functions
$canView = getPermission('task_log', 'view');
$canEdit = getPermission('task_log', 'edit');
$canDelete = getPermission('task_log', 'delete');

if ($canDelete) {
?>
function delIt2(id) {
	if (confirm("<?php echo $AppUI->_('doDelete', UI_OUTPUT_JS).' '.$AppUI->_('Task Log', UI_OUTPUT_JS).'?'; ?>")) {
		document.frmDelete2.task_log_id.value = id;
		document.frmDelete2.submit();
	}
}
<?php } ?>

</script>

<form name="frmDelete2" action="?m=tasks" method="post">
	<input type="hidden" name="dosql" value="do_updatetask">
	<input type="hidden" name="del" value="1" />
	<input type="hidden" name="task_log_id" value="0" />
</form>
<table border="0" cellpadding="2" cellspacing="1" width="100%" class="tbl">

<tr>
	<th></th>
	<th><?php echo $AppUI->_('Date'); ?></th>
	<th title="<?php echo $AppUI->_('Reference'); ?>"><?php echo $AppUI->_('Ref'); ?></th>
	<th width="100"><?php echo $AppUI->_('Summary'); ?></th>
	<th><?php echo $AppUI->_('URL'); ?></th>
	<th width="100"><?php echo $AppUI->_('User'); ?></th>
	<th width="100"><?php echo $AppUI->_('Hours'); ?></th>
	<th width="100"><?php echo $AppUI->_('Cost Code'); ?></th>
	<th width="100%"><?php echo $AppUI->_('Comments'); ?></th>
	<th></th>
</tr>
<?php

// Pull the task comments
$q = new DBQuery;
$q->addTable('task_log', 'tl');
$q->addQuery('tl.*, u.user_username, bc.billingcode_name as task_log_costcode');
$q->leftJoin('billingcode','bc','bc.billingcode_id = tl.task_log_costcode');
$q->leftJoin('users', 'u', 'u.user_id = tl.task_log_creator');
$q->addWhere('task_log_task=' . $task_id . (($problem) ? ' AND task_log_problem > 0' : ''));
$q->addOrder('tl.task_log_date');
 
$logs = (($canView) ? $q->loadList() : array());

$s = '';
$hrs = 0; 
foreach ($logs as $row) {
    $task_log_date = intval($row['task_log_date']) ? new CDate($row['task_log_date']) : null;
    $style = $row['task_log_problem'] ? 'background-color: ##cc6666; color: #ffffff' : '';
?>
<tr bgcolor='white' valign='top'>
    <td>
        <?php if ($canEdit) { ?>
        <a href="?m=tasks&amp;a=view&amp;task_id=<?php echo $task_id;?>&tab=<?php echo (($tab == -1) ? $AppUI->getState('TaskLogVwTab') : '1'); ?>&amp;task_log_id=<?php echo @$row['task_log_id']; ?>#log">
            <?php echo dPshowImage('./images/icons/stock_edit-16.png', 16, 16, ''); ?>
        </a>
        <?php } ?>
    </td>
    <td nowrap='nowrap'>
        <?php echo (($task_log_date) ? $task_log_date->format($df) : '-'); ?>
    </td>
    <?php 
    $reference_image = '-';
    if ($row['task_log_reference'] > 0) {
        if (isset($taskLogReferenceImage[$row['task_log_reference']])) {
            $reference_image = dPshowImage(
                $taskLogReferenceImage[$row['task_log_reference']], 16, 16,
                $taskLogReference[$row['task_log_reference']],
                $taskLogReference[$row['task_log_reference']]
            );
        } else if (isset($taskLogReference[$row['task_log_reference']])) {
            $reference_image = $taskLogReference[$row['task_log_reference']];
        }
    }
    ?>
    <td align='center' valign='middle'>
        <?php echo $reference_image; ?>
    </td>
    <td width='30%' style='<?php echo $style; ?>'>
        <?php echo $AppUI->___(@$row['task_log_name']); ?>
    </td>
    <?php if (!empty(row['task_log_related_url'])) { ?>
    <td>
        <a href="<?php echo @$row['task_log_related_url']; ?>" title="<?php echo @$row['task_log_related_url']; ?>">
            <?php echo $AppUI->_('URL'); ?>
        </a>
    </td>
    <?php } else {?>
    <td></td>
    <?php } ?>
    <td width="100">
        <?php echo $AppUI->___($row['user_username']); ?>
    </td>
    <?php
    $minutes = (int) (($row['task_log_hours'] - ((int) $row['task_log_hours'])) * 60);
	$minutes = ((mb_strlen($minutes) == 1) ? ('0' . $minutes) : $minutes);
    ?>
    <td width="100" align="right">
        <?php echo sprintf('%.2f', $row['task_log_hours']); ?>
        <br>
        (<?php echo ((int)$row['task_log_hours'] . ':' . $minutes); ?>)
    </td>
    <td width="100">
        <?php echo $AppUI->___($row['task_log_costcode']); ?>
    </td>
    <td>
        <a name="tasklog<?php echo @$row['task_log_id']; ?>"></a>
        <?php 
		$desc = filter_xss($row['task_log_description'], $defined_allowed_tags=array('div', 'a', 'em', 'p', 'strong', 'cite', 'code', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'table', 'tr', 'td', 'tbody', 'thead', 'br', 'b', 'i', 'img'));
		// echo $desc;
		$index = array_search($row, $logs);
        ?>
		<div class="tasklog-content">
			<?php echo $desc; ?>
		</div>
        <!-- <script>document.write(handleImages('<?php echo $desc; ?>', '<?php echo $index; ?>'));</script> -->
    </td>
    <td>
        <?php if ($canDelete) { ?>
        <a href="javascript:delIt2(<?php echo $row['task_log_id']; ?>);" title="<?php echo $AppUI->_('delete log'); ?>">
            <?php echo dPshowImage('./images/icons/stock_delete-16.png', 16, 16, ''); ?>
        </a>
        <?php } ?>
    </td>
</tr>
<?php } ?>
<tr bgcolor="white" valign="top">
    <td colspan="6" align="right">
        <?php echo $AppUI->_('Total Hours'); ?> =
    </td>
    <td align="right">
        <?php echo sprintf('%.2f', $hrs); ?>
    </td>
    <td align="right" colspan="3">
        <form action="?m=tasks&amp;a=view&amp;tab=1&amp;task_id=<?php echo $task_id; ?>" method="post">
            <?php if(getPermission('tasks', 'edit', $task_id)) { ?>
            <input type="submit" class="button" value="<?php echo $AppUI->_('new log'); ?>"/>
            <?php } ?>
        </form>
    </td>
</tr>
</table>
<table>
<tr>
	<td><?php echo $AppUI->_('Key'); ?>:</td>
	<td>&nbsp; &nbsp;</td>
	<td bgcolor="#ffffff">&nbsp; &nbsp;</td>
	<td>=<?php echo $AppUI->_('Normal Log'); ?></td>
	<td bgcolor="#CC6666">&nbsp; &nbsp;</td>
	<td>=<?php echo $AppUI->_('Problem Report'); ?></td>
</tr>
</table>
<style>
.tasklog-content img {
  border-radius: 5px;
  cursor: pointer;
  transition: 0.3s;
}

.tasklog-content img:hover {
	opacity: 0.7;
}

/* The Modal (background) */
#tasklog-modal {
  display: none; /* Hidden by default */
  position: fixed; /* Stay in place */
  z-index: 1; /* Sit on top */
  padding-top: 100px; /* Location of the box */
  left: 0;
  top: 0;
  width: 100%; /* Full width */
  height: 100%; /* Full height */
  overflow: auto; /* Enable scroll if needed */
  background-color: rgb(0,0,0); /* Fallback color */
  background-color: rgba(0,0,0,0.9); /* Black w/ opacity */
}

#tasklog-modal #tasklog-modal-image {
  margin: auto;
  display: block;
  object-fit: contain;
  max-width: 90%;
  max-height: 85%;
}

/* Caption of Modal Image (Image Text) - Same Width as the Image */
#tasklog-modal #tasklog-modal-caption {
  margin: auto;
  display: block;
  width: 80%;
  max-width: 700px;
  text-align: center;
  color: #ccc;
  padding: 10px 0;
  height: 150px;
}

/* Add Animation - Zoom in the Modal */
#tasklog-modal #tasklog-modal-image, #tasklog-modal #tasklog-modal-caption { 
  animation-name: zoom;
  animation-duration: 0.6s;
}

@keyframes zoom {
  from {transform:scale(0)} 
  to {transform:scale(1)}
}

/* The Close Button */
#tasklog-modal #tasklog-modal-close {
  position: absolute;
  top: 15px;
  right: 35px;
  color: #f1f1f1;
  font-size: 40px;
  font-weight: bold;
  transition: 0.3s;
}

#tasklog-modal #tasklog-modal-close:hover,
#tasklog-modal #tasklog-modal-close:focus {
  color: #bbb;
  text-decoration: none;
  cursor: pointer;
}

/* 100% Image Width on Smaller Screens */
@media only screen and (max-width: 700px){
  #tasklog-modal #tasklog-modal-image {
    width: 100%;
  }
}


.ql-size-large {
    font-size: 1.5em;
}
.ql-size-small {
    font-size: 0.75em;
}
.ql-size-huge {
    font-size: 2.5em;
}
.ql-font-monospace {
    font-family: Monaco, Courier New, monospace;
}
.ql-font-serif {
    font-family: Georgia, Times New Roman, serif;
}
.ql-align-center {
    text-align: center;
}
.ql-align-right {
    text-align: right;
}
.ql-align-justify {
    text-align: justify;
}
.tasklog-content img {
	max-width: 30%;
	max-height: 100%;
}
</style>

<div id="tasklog-modal" class="modal">
  <span id="tasklog-modal-close">&times;</span>
  <img id="tasklog-modal-image">
  <div id="tasklog-modal-caption"></div>
</div>

<script>
(function() {
	var modal = document.getElementById('tasklog-modal');
	var modalImg = document.getElementById('tasklog-modal-image');
	var modalSpan = document.getElementById('tasklog-modal-close');

	var imgs = document.querySelectorAll('.tasklog-content img');
	for(var i = 0; i < imgs.length; i++) {
		imgs[i].addEventListener('click', function(e) {
			
			modal.style.display = 'block';
			modalImg.src = e.target.src;
		})
	}
	modalSpan.onclick = function() {
		modal.style.display = 'none';
	}
})();
</script>
