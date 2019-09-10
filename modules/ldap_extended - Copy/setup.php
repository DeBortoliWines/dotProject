<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

$config = array();
$config['mod_name'] = 'ldap_extended';
$config['mod_version'] = '1.0';
$config['mod_directory'] = 'ldap_extended';
$config['mod_setup_class'] = 'CSetupLDAPExtended'; 
$config['mod_type'] = 'user';
$config['mod_config'] = false;
$config['mod_ui_name'] = 'LDAP Extended';
$config['mod_ui_icon'] = 'applet3-48.png';
$config['mod_description'] = "Bind groups from LDAP with dotProject roles.";

if (@$a == 'setup') {
	echo dPshowModuleConfig($config);
}

class CSetupLDAPExtended {

     function install() {
		 require_once DP_BASE_DIR ."/modules/ldap_extended/ldap_extended.class.php";
		 $ldapExt= new CLDAPExtended();
		 return true;
 }


   function remove() { 
  	/*
	$q = new DBQuery();
  	$q->dropTable('ldap_extended');
  	$q->exec();
	*/
	return true;
	
 }
 
  function upgrade($version = 'all') {
	return true;
  }
  
  function configure() {
	return true;
  }  

}
