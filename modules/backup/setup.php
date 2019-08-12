<?php
/**
 *  Name: Backup
 *  Directory: backup
 *  Version 1.0
 *  Type: user
 *  UI Name: Import/Export
 *  UI Icon: ?
 */

$config = array();
$config['mod_name'] = 'Backup';               // name the module
$config['mod_version'] = '1.0';               // add a version number
$config['mod_directory'] = 'backup';          // tell dotProject where to find this module
$config['mod_setup_class'] = 'CSetupBackup';  // the name of the PHP setup class (used below)
$config['mod_type'] = 'user';                   // 'core' for modules distributed with dP by standard, 'user' for additional modules from dotmods
$config['mod_ui_name'] = 'Import/Export';            // the name that is shown in the main menu of the User Interface
$config['mod_ui_icon'] = 'communicate.gif';     // name of a related icon
$config['mod_description'] = 'Import/Export data from projects';     // some description of the module
$config['mod_config'] = false;                   // show 'configure' link in viewmods


if (@$a == 'setup') {
        echo dPshowModuleConfig( $config );
}

// TODO: To be completed later as needed.
class CSetupBackup {

  function configure() { return true; }

  function remove() { return null; }
  
  function upgrade($old_version) { return true; }

  function install() { return null; }

}
?>
