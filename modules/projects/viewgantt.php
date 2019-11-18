<?php /* TASKS $Id$ */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

require_once DP_BASE_DIR . '/modules/projects/frappegantt.php';

Gantt::WriteHeader();

// Import css for flatpickr
echo ('<link rel="stylesheet" href="' . $base . 'lib/flatpickr/flatpickr.min.css">');

global $AppUI, $company_id, $dept_ids, $department, $min_view, $m, $a, $user_id, $tab;
global $m_orig, $a_orig;

$min_view = defVal($min_view, false);
$project_id = intval(dPgetParam($_GET, 'project_id', 0));
$user_id = intval(dPgetParam($_GET, 'user_id', $AppUI->user_id));
// sdate and edate passed as unix time stamps
$sdate = dPgetCleanParam($_POST, 'sdate', 0);
$edate = dPgetCleanParam($_POST, 'edate', 0);
$showInactive = (int)dPgetParam($_POST, 'showInactive', '0');
$showLabels = (int)dPgetParam($_POST, 'showLabels', '0');
$sortTasksByName = (int)dPgetParam($_POST, 'sortTasksByName', '0');
$showAllGantt = (int)dPgetParam($_POST, 'showAllGantt', '0');
$showTaskGantt = (int)dPgetParam($_POST, 'showTaskGantt', '0');
$addPwOiD = (int)dPgetParam($_POST, 'add_pwoid', isset($addPwOiD) ? $addPwOiD : 0);
$m_orig = $m;
$a_orig = $a;

//if set GantChart includes user labels as captions of every GantBar
if ($showLabels!='0') {
	$showLabels='1';
}
if ($showInactive!='0') {
	$showInactive='1';
}

if ($showAllGantt!='0') {
	$showAllGantt='1';
}

if (isset($_POST['show_owner'])) {
    $showOwner = $_POST['show_owner'];
}

if (isset($_POST['department'])) {
    $showDepartment = $_POST['department'];
}
$showDepartment = preg_replace('/company\_/', "", $showDepartment);

if (isset($_POST['proFilter'])) {
	$AppUI->setState('ProjectIdxFilter',  $_POST['proFilter']);
}
$proFilter = (($AppUI->getState('ProjectIdxFilter') !== NULL) 
              ? $AppUI->getState('ProjectIdxFilter') : '-1');


$projectStatus = dPgetSysVal('ProjectStatus');
$projFilter = arrayMerge(array('-1' => 'All Projects', '-2' => 'All w/o in progress', 
                               '-3' => (($AppUI->user_id == $user_id) ? 'My projects' 
                                        : "User's projects")), $projectStatus);
if (!(empty($projFilter_extra))) {
	$projFilter = arrayMerge($projFilter, $projFilter_extra);
}
natsort($projFilter);


// months to scroll
$scroll_date = 1;

$display_option = dPgetCleanParam($_POST, 'display_option', 'this_month');

// format dates
$df = $AppUI->getPref('SHDATEFORMAT');

if ($display_option == 'custom') {
	// custom dates
	$start_date = intval($sdate) ? new CDate($sdate) : new CDate();
	$end_date = intval($edate) ? new CDate($edate) : new CDate();
} else {
	// month
	$start_date = new CDate();
	$start_date->day = 1;
   	$end_date = new CDate($start_date);
    $end_date->addMonths($scroll_date);
}

// setup the title block
if (!@$min_view) {
	$titleBlock = new CTitleBlock('Gantt Chart', 'applet3-48.png', $m, "$m.$a");
	$titleBlock->addCrumb(('?m=' . $m), 'projects list');
	$titleBlock->show();
}

?>

<script  language="javascript">

function scrollPrev() {
	f = document.editFrm;
<?php
$new_start = new CDate($start_date);	
$new_start->day = 1;
$new_end = new CDate($end_date);
$new_start->addMonths(-$scroll_date);
$new_end->addMonths(-$scroll_date);

echo "f.sdate.value='".$new_start->format(FMT_TIMESTAMP_DATE)."';";
echo "f.edate.value='".$new_end->format(FMT_TIMESTAMP_DATE)."';";
?>
	document.editFrm.display_option.value = 'custom';
	f.submit()
}

function scrollNext() {
	f = document.editFrm;
<?php
$new_start = new CDate($start_date);
$new_start->day = 1;
$new_end = new CDate($end_date);	
$new_start->addMonths($scroll_date);
$new_end->addMonths($scroll_date);
echo "f.sdate.value='" . $new_start->format(FMT_TIMESTAMP_DATE) . "';";
echo "f.edate.value='" . $new_end->format(FMT_TIMESTAMP_DATE) . "';";
?>
	document.editFrm.display_option.value = 'custom';
	f.submit()
}

function showThisMonth() {
	document.editFrm.display_option.value = "this_month";
	document.editFrm.submit();
}

function showFullProject() {
	document.editFrm.display_option.value = "all";
	document.editFrm.submit();
}
</script>
<table class="tbl" style="table-layout:fixed" width="100%" border="0" cellpadding="4" cellspacing="0" summary="projects view gantt">
<tr>
	<td>
		<form name="editFrm" method="post" action="?<?php 
foreach ($_GET as $key => $val) {
	$url_query_string .= (($url_query_string) ? '&amp;' : '') . $key . '=' . $val;
}
echo ($url_query_string);
?>">
		<input type="hidden" name="display_option" value="<?php echo $display_option;?>" />
		<table border="0" cellpadding="4" cellspacing="0" class="tbl" summary="select dates for graphs">
		<tr>
			<td align="left" valign="top" width="20">
<?php if ($display_option != "all") { ?>
				<a href="javascript:scrollPrev()">
				<img src="./images/prev.gif" width="16" height="16" alt="<?php 
	echo $AppUI->_('previous');?>" border="0" />
				</a>
<?php } ?>
			</td>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('From');?>:</td>
			<td align="left" nowrap="nowrap">
				<input type="text" name="sdate" class="flatpickr flatpickr-input text dpDateField" id="sdate"
				value="<?php echo $start_date->format(FMT_DATE_HTML5);?>">
			</td>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('To');?>:</td>
			<td align="left" nowrap="nowrap">
				<input type="text" name="edate" class="flatpickr flatpickr-input text dpDateField" id="edate" 
				value="<?php echo $end_date->format(FMT_DATE_HTML5);?>">
			</td>
			<script type="text/javascript">
				flatpickr("#sdate", {
					dateFormat: "Y-m-d"
				});
				flatpickr("#edate", {
					dateFormat: "Y-m-d"
				});
			</script>
			<td valign="top">
				<?php 
echo arraySelect($projFilter, 'proFilter', 'size="1" class="text"', $proFilter, true);?>
			</td>
			<td align="left">
				<input type="button" class="button" value="<?php 
echo $AppUI->_('submit');?>" onclick='document.editFrm.display_option.value="custom";submit();' />
			</td>
			<td align="right" valign="top" width="20">
<?php if ($display_option != "all") { ?>
			<a href="javascript:scrollNext()">
				<img src="./images/next.gif" width="16" height="16" alt="<?php 
echo $AppUI->_('next');?>" border="0" />
			</a>
<?php } ?>
			</td>
		</tr>
		<tr>
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

		<?php Gantt::Projects($showOwner, $showDepartment)->render(); ?>
		</table>
	</td>
</tr>
</table>

