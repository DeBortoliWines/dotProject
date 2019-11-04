<?php



$canRead = !getDenyRead($m);

$canEdit = !getDenyEdit($m);



if (!$canRead) 

  $AppUI->redirect("m=public&a=access_denied");



$AppUI->savePlace();



$tab = dPgetParam($_GET, "tab", 0);

$AppUI->setState("BackupIdxTab", $tab);



//TODO Create an image

//$titleBlock = new CTitleBlock('Backup', 'backup.png', $m, "$m.$a");

//$titleBlock->show();



$tabBox = new CTabBox("?m=$m", "{$dPconfig['root_dir']}/modules/$m/", $tab);

$tabBox->add('vw_idx_import', $AppUI->_('Import'));

if ($canEdit)

  $tabBox->add('vw_idx_export', $AppUI->_('Export'));



$tabBox->show();



?>

