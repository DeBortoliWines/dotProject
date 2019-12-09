<?php

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Tasks';
$config['mod_version'] = '1.0';
$config['mod_directory'] = 'tasks';
$config['mod_setup_class'] = 'Tasks';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Tasks';
$config['mod_ui_icon'] = 'applet-48.png';
$config['mod_description'] = 'A module for task management';
$config['mod_config'] = true;

if (@$a == 'setup')
    echo dPshowModuleConfig($config);

class Tasks {
    function upgrade() {
        return null;
    }
    
    function configure() {		// configure this module
        global $AppUI;
        $AppUI->redirect('m=tasks&a=configure');	// load module specific configuration page
        return true;
    }
}