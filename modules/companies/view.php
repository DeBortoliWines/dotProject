<?php /* COMPANIES $Id$ */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$company_id = intval(dPgetParam($_GET, 'company_id', 0));

// check permissions for this record
$canRead = getPermission($m, 'view', $company_id);
$canEdit = getPermission($m, 'edit', $company_id);


if (!$canRead) {
	$AppUI->redirect('m=public&a=access_denied');
}

// retrieve any state parameters
if (isset($_GET['tab'])) {
	$AppUI->setState('CompVwTab', $_GET['tab']);
}
$tab = (($AppUI->getState('CompVwTab') !== NULL) ? $AppUI->getState('CompVwTab') : 0);

// check if this record has dependencies to prevent deletion
$msg = '';
$obj = new CCompany();
$canDelete = $obj->canDelete($msg, $company_id);

// load the record data
$q  = new DBQuery;
$q->addTable('companies', 'co');
$q->addQuery('co.*');
$q->addQuery('con.contact_first_name');
$q->addQuery('con.contact_last_name');
$q->addJoin('users', 'u', 'u.user_id = co.company_owner');
$q->addJoin('contacts', 'con', 'u.user_contact = con.contact_id');
$q->addWhere('co.company_id = '.$company_id);
$sql = $q->prepare();
$q->clear();

$obj = null;
if (!db_loadObject($sql, $obj)) {
	$AppUI->setMsg('Company');
	$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
	$AppUI->redirect();
} else {
	$AppUI->savePlace();
}

// load the list of project statii and company types
$pstatus = dPgetSysVal('ProjectStatus');
$types = dPgetSysVal('CompanyType');

// setup the title block
$titleBlock = new CTitleBlock('View Company', 'handshake.png', $m, "$m.$a");
if ($canEdit) {
	$titleBlock->addCell();
	$titleBlock->addCell(('<input type="submit" class="button" value="' . $AppUI->_('new company') 
	                      . '" />'), '', '<form action="?m=companies&amp;a=addedit" method="post">', 
	                     '</form>');
	$titleBlock->addCell(('<input type="submit" class="button" value="' . $AppUI->_('new project') 
	                      . '" />'), '', 
	                     ('<form action="?m=projects&amp;a=addedit&amp;company_id=' 
	                      . dPformSafe($company_id) . '" method="post">'), '</form>');
}
$titleBlock->addCrumb('?m=companies', 'company list');
if ($canEdit) {
	$titleBlock->addCrumb(('?m=companies&amp;a=addedit&amp;company_id=' . $company_id), 
	                      'edit this company');
	if ($canDelete) {
		$titleBlock->addCrumbDelete('delete company', $canDelete, $msg);
	}
}
$titleBlock->show();
?>
<script language="javascript" >
<?php
// security improvement:
// some javascript functions may not appear on client side
// in case of user not having write permissions
// else users would be able to arbitrarily run 'bad' functions
if ($canDelete) {
?>
function delIt() {
	if (confirm("<?php echo ($AppUI->_('doDelete') . ' ' . $AppUI->_('Company') . '?'); ?>")) {
		document.frmDelete.submit();
	}
}
<?php } ?>
</script>

<?php if ($canDelete) {
?>
<form name="frmDelete" action="./index.php?m=companies" method="post">
	<input type="hidden" name="dosql" value="do_company_aed" />
	<input type="hidden" name="del" value="1" />
	<input type="hidden" name="company_id" value="<?php echo dPformSafe($company_id); ?>" />
</form>
<?php } ?>

<table border="0" cellpadding="4" cellspacing="0" width="100%" class="std">
<tr>
	<td valign="top" width="50%">
		<strong><?php echo $AppUI->_('Details'); ?></strong>
		<table cellspacing="1" cellpadding="2" width="100%">
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Company'); ?>:</td>
			<td class="hilite" width="100%"><?php echo htmlspecialchars($obj->company_name); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Owner'); ?>:</td>
			<td class="hilite" width="100%"><?php 
echo (htmlspecialchars($obj->contact_first_name) . '&nbsp;' 
      . htmlspecialchars($obj->contact_last_name)); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Email'); ?>:</td>
			<td class="hilite" width="100%"><?php 
echo htmlspecialchars($obj->company_email); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Phone'); ?>:</td>
			<td class="hilite"><?php echo htmlspecialchars(@$obj->company_phone1); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Phone'); ?>2:</td>
			<td class="hilite"><?php echo htmlspecialchars(@$obj->company_phone2); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Fax'); ?>:</td>
			<td class="hilite"><?php echo htmlspecialchars(@$obj->company_fax); ?></td>
		</tr>
		<tr valign="top">
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Address'); ?>:</td>
			<td class="hilite">
<?php if (!empty($obj->company_country)) { ?>
				<span style="float: right"><a href="http://maps.google.com/maps?q=<?php 
echo dPformSafe(@$obj->company_address1, DP_FORM_URI); ?>+<?php 
echo dPformSafe(@$obj->company_address2, DP_FORM_URI); ?>+<?php 
echo dPformSafe(@$obj->company_city, DP_FORM_URI); ?>+<?php 
echo dPformSafe(@$obj->company_state, DP_FORM_URI); ?>+<?php 
echo dPformSafe(@$obj->company_zip, DP_FORM_URI); ?>+<?php 
echo dPformSafe(@$obj->company_country, DP_FORM_URI); ?>" target="_blank">
				<?php 
echo dPshowImage('./images/googlemaps.gif', 55, 22, 'Find It on Google');
?>
<?php } ?>
				</a></span>
				<?php
echo (htmlspecialchars(@$obj->company_address1) 
      . (($obj->company_address2) ? '<br />' : '') . htmlspecialchars($obj->company_address2) 
      . (($obj->company_city) ? '<br />' : '') . htmlspecialchars($obj->company_city) 
      . (($obj->company_state) ? ', ' : '') . htmlspecialchars($obj->company_state) 
      . (($obj->company_zip) ? ' ' : '') . htmlspecialchars($obj->company_zip));
?>
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('URL'); ?>:</td>
			<td class="hilite">
				<a href="http://<?php 
echo dPformSafe(@$obj->company_primary_url, DP_FORM_URI); ?>" target="Company"><?php 
echo htmlspecialchars(@$obj->company_primary_url); ?></a>
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Type'); ?>:</td>
			<td class="hilite"><?php echo $AppUI->_($types[@$obj->company_type]); ?></td>
		</tr>
		</table>

	</td>
	<td width="50%" valign="top">
		<strong><?php echo $AppUI->_('Description'); ?></strong>
		<table cellspacing="0" cellpadding="2" border="0" width="100%" summary="company description">
		<tr>
			<td class="hilite">
			<div class="company-content">
						<?php 
						echo filter_xss($obj->company_description, $defined_allowed_tags=array('div', 'p', 'span', 'h1', 'h2', 'u', 's', 'a', 'em', 'strong', 'cite', 'code', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'table', 'tr', 'td', 'tbody', 'thead', 'br', 'b', 'i', 'img'));
						?>
					</div>
			</td>
		</tr>
		
		</table>
		<?php
			require_once($AppUI->getSystemClass('CustomFields'));
			$custom_fields = New CustomFields($m, $a, $obj->company_id, 'view');
			$custom_fields->printHTML();
		?>
	</td>
</tr>
</table>

<?php
// tabbed information boxes
$moddir = DP_BASE_DIR . '/modules/companies/';
$tabBox = new CTabBox(('?m=companies&amp;a=view&amp;company_id=' . $company_id), '', $tab);
$tabBox->add($moddir . 'vw_active', 'Active Projects');
$tabBox->add($moddir . 'vw_archived', 'Archived Projects');
$tabBox->add($moddir . 'vw_depts', 'Departments');
$tabBox->add($moddir . 'vw_users', 'Users');
$tabBox->add($moddir . 'vw_contacts', 'Contacts');
$tabBox->loadExtras($m);
$tabBox->loadExtras($m, 'view');
$tabBox->show();

?>
<style>
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

</style>

<style>
.company-content img {
  border-radius: 5px;
  cursor: pointer;
  transition: 0.3s;
  max-width: 30%;
  max-height: 100%;
}

.company-content img:hover {
	opacity: 0.7;
}

/* The Modal (background) */
#company-modal {
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

#company-modal #company-modal-image {
  margin: auto;
  display: block;
  object-fit: contain;
  max-width: 90%;
  max-height: 85%;
}

/* Caption of Modal Image (Image Text) - Same Width as the Image */
#company-modal #company-modal-caption {
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
#company-modal #company-modal-image, #company-modal #company-modal-caption { 
  animation-name: zoom;
  animation-duration: 0.6s;
}

@keyframes zoom {
  from {transform:scale(0)} 
  to {transform:scale(1)}
}

/* The Close Button */
#company-modal #company-modal-close {
  position: absolute;
  top: 15px;
  right: 35px;
  color: #f1f1f1;
  font-size: 40px;
  font-weight: bold;
  transition: 0.3s;
}

#company-modal #company-modal-close:hover,
#company-modal #company-modal-close:focus {
  color: #bbb;
  text-decoration: none;
  cursor: pointer;
}

/* 100% Image Width on Smaller Screens */
@media only screen and (max-width: 700px){
  #company-modal #company-modal-image {
    width: 100%;
  }
}
</style>

<div id="company-modal" class="modal">
  <span id="company-modal-close">&times;</span>
  <img id="company-modal-image">
  <div id="company-modal-caption"></div>
</div>

<script>
(function() {
	var modal = document.getElementById('company-modal');
	var modalImg = document.getElementById('company-modal-image');
	var modalSpan = document.getElementById('company-modal-close');

	var imgs = document.querySelectorAll('.company-content img');
	for(var i = 0; i < imgs.length; i++) {
		imgs[i].addEventListener('click', function(e) {
			
			modal.style.display = 'block';
			modalImg.src = e.target.src;
		})
	}
	modalSpan.addEventListener('click', function() {
		modal.style.display = 'none';
	});
})();
</script>