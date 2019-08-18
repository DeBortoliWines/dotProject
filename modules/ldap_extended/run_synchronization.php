<?php
global $dPconfig, $AppUI;
$syncType=$dPconfig['ldap_variable_for_retrieve_roles_list'];
$runEnabled=$dPconfig['ldap_enable_role_creation'];
$debugMode=false;
global $username;
$_SESSION["error_msg"]="";
if(strtolower($runEnabled) == "true" || strtolower($runEnabled) == 1 ){ 
	if(strtolower($syncType)=="memberof"){ 
		require_once DP_BASE_DIR ."/modules/ldap_extended/do_ldap_memberof_based.php";
	}else{
		require_once DP_BASE_DIR ."/modules/ldap_extended/do_ldap_group_membership_based.php"; 
	}
	//user feedback
	if(isset($_SESSION["error_msg"]) && $_SESSION["error_msg"]!=""){
			$AppUI->setMsg($AppUI->_($_SESSION["error_msg"],UI_OUTPUT_HTML), UI_MSG_ERROR,true);
	}else{
			$AppUI->setMsg($AppUI->_($msg,"LDAP permissions updated."), UI_MSG_OK, false);
	}
	$AppUI->redirect();
}else{
	$msg="LDAP synchronization is not enabled. Enable that using the dotProject configuration variables painel, in admin screen.";
	echo $msg;
	$AppUI->setMsg($AppUI->_($msg,UI_OUTPUT_HTML), UI_MSG_ERROR, true);
}

?>