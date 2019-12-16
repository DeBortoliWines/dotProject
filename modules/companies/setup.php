<?php

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Companies';
$config['mod_version'] = '1.0.0';
$config['mod_directory'] = 'companies';
$config['mod_setup_class'] = 'Companies';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Companies';
$config['mod_ui_icon'] = 'handshake.png';
$config['mod_description'] = 'A module for company management';
$config['mod_config'] = true;

if (@$a == 'setup')
    echo dPshowModuleConfig($config);

class Companies {
    function configure() {
        global $AppUI;
        $AppUI->redirect('m=companies&a=configure');
        return true;
    }
}