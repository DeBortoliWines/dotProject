<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
global $AppUI;
require_once $AppUI->getModuleClass('contacts');
require_once $AppUI->getModuleClass('admin');
require_once $AppUI->getSystemClass('dp');
require_once DP_BASE_DIR ."/modules/system/roles/roles.class.php";


class CLDAPExtended extends CDpObject {
		//Default values are test purpose only. It is overwrite on constructor.
		//replace such values from some configuration data
		var $ldap_host;
		var $ldap_port;
		var $ldap_version;
		//credatials for login
		var $ldap_dn;
		var $ldap_password;
		var $ldap_search_user;
		
		var $ldap_dp_role_prefix;
		var $ldap_variable_for_retrieve_roles_list;
		var $ldap_template_role_for_copy_permissions;
		var $ldap_query_for_select_dotproject_groups; 
		var $ldap_enable_synchronization; 
		var $ldap_users_to_synchronize; 
		
		function __construct() {
			parent::__construct('ldap_extended', 'ldap_extended_id');
			global $dPconfig;			
			$this->ldap_host = $dPconfig['ldap_host'];//"ldap.forumsys.com";
			$this->ldap_port = $dPconfig['ldap_port'];//"389"
			$this->ldap_version= $dPconfig['ldap_version'];//"3"
			$this->ldap_dn =  $dPconfig['ldap_base_dn'];// "cn=read-only-admin,dc=example,dc=com"
			$this->ldap_search_user= $dPconfig['ldap_search_user'];//"admin"
			$this->ldap_password = $dPconfig['ldap_search_pass'];//"password"
			$this->ldap_enable_synchronization = $dPconfig['ldap_enable_role_creation'];
			$this->ldap_users_to_synchronize=$dPconfig['ldap_users_to_synchronize']=="Global"?false:true;
			$this->ldap_variable_for_retrieve_roles_list=strtolower($dPconfig['ldap_variable_for_retrieve_roles_list']);//memberof 
			$this->ldap_template_role_for_copy_permissions=$dPconfig['ldap_template_role_for_copy_permissions'];//normal
			$this->ldap_query_for_select_dotproject_groups=$dPconfig['ldap_query_for_select_dotproject_groups'];//(&(objectclass=posixGroup)(cn=DP_*))
			
			$this->ldap_dp_role_prefix=""; //DP_
			
			$posCN=strpos($this->ldap_query_for_select_dotproject_groups,"cn=",0);
			$posWildCard=strpos($this->ldap_query_for_select_dotproject_groups,"*",$posCN);
			if($posCN!==false && $posWildCard!==false){ 
					$len=$posWildCard-($posCN+3);
					$this->ldap_dp_role_prefix=substr ($this->ldap_query_for_select_dotproject_groups, $posCN+3, $len);
			} 

			
			//create config in case does not exist
			/*
			if(!isset($dPconfig['ldap_dp_role_prefix'])){
				$q = new DBQuery();
				$q->addTable('config');
				$q->addInsert('config_name', 'ldap_dp_role_prefix');
				$q->addInsert('config_value', 'DP_');
				$q->addInsert('config_group', 'ldap');
				$q->addInsert('config_type', 'text');
				$q->exec();
				$q->clear();
			}
			*/
			
			
			if(!isset($dPconfig['ldap_enable_role_creation'])){
				$q = new DBQuery();
				$q->addTable('config');
				$q->addInsert('config_name', 'ldap_enable_role_creation');
				$q->addInsert('config_value', '');
				$q->addInsert('config_group', 'LDAP_role_creation');
				$q->addInsert('config_type', 'checkbox');
				$q->exec();
				$q->clear();
			}
			
			if(!isset($dPconfig['ldap_variable_for_retrieve_roles_list'])){
				$q = new DBQuery();
				$q->addTable('config');
				$q->addInsert('config_name', 'ldap_variable_for_retrieve_roles_list');
				$q->addInsert('config_value', 'memberof');
				$q->addInsert('config_group', 'LDAP_role_creation');//ldap
				$q->addInsert('config_type', 'select');
				$q->exec();
				$q->clear();
			}
			
			
			//add field for user select just current user (single user) or all users (global)
			if(!isset($dPconfig['ldap_users_to_synchronize'])){
				$q = new DBQuery();
				$q->addTable('config');
				$q->addInsert('config_name', 'ldap_users_to_synchronize');
				$q->addInsert('config_value', 'memberof');
				$q->addInsert('config_group', 'LDAP_role_creation');//ldap
				$q->addInsert('config_type', 'select');
				$q->exec();
				$q->clear();
				
				
				//fix options to select
				
				$q = new DBQuery();
				$q->addQuery("config_id");
				$q->addTable("config");
				$q->addWhere("config_name='ldap_users_to_synchronize'");
				$sql = $q->prepare();
				$records= db_loadList($sql);
				$configId=NULL;
				foreach($records as $record){
					$configId=$record[0];
				}
			
				$q = new DBQuery();
				$q->addQuery("config_list_id");
				$q->addTable("config_list");
				$q->addWhere("config_id=$configId");
				$sql = $q->prepare();
				$records= db_loadList($sql);
			
				if(sizeof($records)==0){
					$q = new DBQuery();
					$q->addTable('config_list');
					$q->addInsert('config_id', $configId);
					$q->addInsert('config_list_name', 'Single/Current');
					$q->exec();
					$q->clear();
					
					$q = new DBQuery();
					$q->addTable('config_list');
					$q->addInsert('config_id', $configId);
					$q->addInsert('config_list_name', 'Global');
					$q->exec();
					$q->clear();
				}
					
			}
			
			//fix retrieve_roles_list select for config
			$q = new DBQuery();
			$q->addQuery("config_id");
			$q->addTable("config");
			$q->addWhere("config_name='ldap_variable_for_retrieve_roles_list'");
			$sql = $q->prepare();
			$records= db_loadList($sql);
			$configId=NULL;
			foreach($records as $record){
				$configId=$record[0];
			}
			//fix type to select
			
			$q = new DBQuery();
			$q->addQuery("config_id");
			$q->addTable("config");
			$q->addWhere("config_name='ldap_variable_for_retrieve_roles_list' and config_type='select'");
			$sql = $q->prepare();
			$records= db_loadList($sql);
			if(sizeof($records)==0){			
				$q = new DBQuery();
				$q->addTable('config');
				$q->addUpdate('config_type', 'select');
				$q->addWhere("config_name='ldap_variable_for_retrieve_roles_list'");
				$q->exec();
				$q->clear();
			}
			
			//fix options to select
			$q = new DBQuery();
			$q->addQuery("config_list_id");
			$q->addTable("config_list");
			$q->addWhere("config_id=$configId");
			$sql = $q->prepare();
			$records= db_loadList($sql);
			if(sizeof($records)==0){
				$q = new DBQuery();
				$q->addTable('config_list');
				$q->addInsert('config_id', $configId);
				$q->addInsert('config_list_name', 'memberof');
				$q->exec();
				$q->clear();
				
				$q = new DBQuery();
				$q->addTable('config_list');
				$q->addInsert('config_id', $configId);
				$q->addInsert('config_list_name', 'groupMembership');
				$q->exec();
				$q->clear();
			}
			
			
			if(!isset($dPconfig['ldap_template_role_for_copy_permissions'])){
				$q = new DBQuery();
				$q->addTable('config');
				$q->addInsert('config_name', 'ldap_template_role_for_copy_permissions');
				$q->addInsert('config_value', 'normal');
				$q->addInsert('config_group', 'LDAP_role_creation');//ldap
				$q->addInsert('config_type', 'text');
				$q->exec();
				$q->clear();
			}
			
			if(!isset($dPconfig['ldap_query_for_select_dotproject_groups'])){
				$q = new DBQuery();
				$q->addTable('config');
				$q->addInsert('config_name', 'ldap_query_for_select_dotproject_groups');
				$q->addInsert('config_value', '(&(objectclass=posixGroup)(cn=DP_*))');
				$q->addInsert('config_group', 'LDAP_role_creation');//ldap
				$q->addInsert('config_type', 'text');
				$q->exec();
				$q->clear();
			}
			
			
			
		}
		
		
		public function printLDAPParameters(){
			echo "<br /><pre>";
			print_r($this);
			echo "</pre><br />";
		}
		
		
		public function deleteRolesNotOnLDAPAnymore($userName,$dpRolesPrefix){
			global $debugMode;
			$userRoles=$this->getUserDotProjectRoles($userName);
			foreach($userRoles as $role){
				if( strpos($role,$dpRolesPrefix) !== FALSE){
					if($debugMode){
						echo "Removing LDAP role ($role) from user ($userName)";
					}
					$this->deleteRoleFromUser($userName,$role);
				}
			}		
		}
		
		
		public function getUserDotProjectRoles($userName){
			/*
			SELECT aro.name, group_id, aro_id, g.name FROM dotproject_ldap.dotp_gacl_groups_aro_map aro_map
			inner join dotp_gacl_aro aro on aro.id = aro_map.aro_id 
			inner join dotp_gacl_aro_groups g on g.id = aro_map.group_id
			where aro.name="gauss"
			*/

			$q = new DBQuery();
			$q->addQuery("aro.name, group_id, aro_id, g.name");
			$q->addTable("gacl_groups_aro_map","aro_map");
			
			$q->addJoin('gacl_aro', 'aro', 'aro.id = aro_map.aro_id');
			$q->addJoin('gacl_aro_groups', 'g', 'g.id = aro_map.group_id');
			
			$q->addWhere("aro.name = '"  . stripslashes($userName) . "'");

			$sql = $q->prepare();
			$records= db_loadList($sql);
			$roles=array();
			foreach($records as $record){
				array_push($roles,$record[3]);
			}
			return $roles;
		}
		
		
    //SELECT table_name,update_time FROM information_schema.tables where TABLE_SCHEMA = "dotproject_ldap" order by update_time desc
	//roleA: new created role; roleB: default role for copying permissions
	function copyRolePermissions($roleA,$roleB){
		//SELECT * FROM dotproject_ldap.dotp_gacl_aro_groups; id => roleName (name)
	    //SELECT * FROM dotproject_ldap.dotp_gacl_aro_groups_map => groupId => ACL Id
		//inserir novas linhas nesta tabela dotp_gacl_aro_groups_map  
	   //SELECT * FROM dotproject_ldap.dotp_dotpermissions; Espera-se que não precise fazer nada
		
		
		$q = new DBQuery();
		$q->addQuery("id");
		$q->addTable("gacl_aro_groups");
		$q->addWhere("value = '"  . $roleA . "'");
		$sql = $q->prepare();
		$records= db_loadList($sql);
		$idRoleA=0;
		foreach($records as $record){
			$idRoleA=$record[0];
		}
		//echo "Role A: ". $idRoleA;
		$q = new DBQuery();
		$q->addQuery("id");
		$q->addTable("gacl_aro_groups");
		$q->addWhere("value = '"  . $roleB . "'");
		$sql = $q->prepare();
		$records= db_loadList($sql);
		$idRoleB=0;
		foreach($records as $record){
			$idRoleB=$record[0];
		}
		//echo "Role B: ". $idRoleB;
		//SELECT acl FROM dotproject_ldap.dotp_gacl_aro_groups_map where group_id=
		
		$q = new DBQuery();
		$q->addQuery("acl_id");
		$q->addTable("gacl_aro_groups_map");
		$q->addWhere("group_id = "  . $idRoleB . "");
		$sql = $q->prepare();
		$records= db_loadList($sql);
		$idRoleB=0;
		foreach($records as $record){
			$acl_id=$record[0];
			$q = new DBQuery();
			$q->addTable('gacl_aro_groups_map');
			$q->addInsert('acl_id', $acl_id);
			$q->addInsert('group_id', $idRoleA);
			$q->exec();
			//echo "<br/>permission inserted<br/>";
		}
	}
	
	public function createDPRole($roleName){ 
		$role = new CRole();
		$role->role_name = $roleName;
		$role->role_description = $roleName;
		$role->store();
		return $role->role_id;
	}
		
	public function getDotProjectRoles(){
		$q = new DBQuery();
		$q->addQuery("value");
		$q->addTable("gacl_aro_groups");
		$sql = $q->prepare();
		$records= db_loadList($sql);
		$roles=array();
		foreach($records as $record){
			array_push($roles,$record[0]);
		}
		return $roles;
	}
	

	
	public function getDotProjectUsers(){
		$q = new DBQuery();
		$q->addQuery("user_username");
		$q->addTable("users");
		$sql = $q->prepare();
		$records= db_loadList($sql);
		$users=array();
		foreach($records as $record){
			array_push($users,$record[0]);
		}
		return $users;
	}
	
	
	public function deleteRoleFromUser($user_name,$role_name){
		
		global $AppUI;
		$perms =& $AppUI->acl();
		
		$userIdPermissions=-1;
		$user_id=-1;
		$q = new DBQuery();
		$q->addQuery("id,value");
		$q->addTable("gacl_aro");
		$q->addWhere("name = '"  . stripslashes($user_name) . "'");
		$sql = $q->prepare();
		$records= db_loadList($sql);
		
		foreach($records as $record){
			$userIdPermissions= $record[0];
			$user_id=$record[1];
		}
		 
		 if($userIdPermissions != -1){
			
			$q = new DBQuery();
			$q->addQuery("id");
			$q->addTable("gacl_aro_groups");
			$q->addWhere("name = '"  . stripslashes($role_name) . "' or value='".stripslashes($role_name)."'");
			$sql = $q->prepare();
			//echo $sql; 
			$records= db_loadList($sql);
			foreach($records as $record){
				$role_id= $record[0];
				$perms->deleteUserRole($role_id, $user_id);	
				//echo "Role " . $role_id ." deleted for user(" . $user_name . ")". $userIdPermissions;
			 }	 
				 			
		}else{
			//echo "Roles NOT deleted for user(" . $user_name . ")". $userIdPermissions;
		}	
	}
	
	
	public function getRoleId($role_name){
		$groupdId = -1;
		$q = new DBQuery();
		$q->addQuery("id");
		$q->addTable("gacl_aro_groups");
		$q->addWhere("name = '"  . stripslashes($role_name) . "' or value='".stripslashes($role_name)."'");
		$sql = $q->prepare();
		$records= db_loadList($sql);
		foreach($records as $record){
			$groupdId= $record[0];
		 }
		 return $groupdId;
	}
	
	public function addRoleToUser($user_name, $role_name){
		
		global $AppUI,$debugMode,$dPconfig;
		//SELECT group_id, aro_id FROM dotproject_ldap.dotp_gacl_groups_aro_map;
		//SELECT id, name FROM dotproject_ldap.dotp_gacl_aro;
		//SELECT id,name,value FROM dotproject_ldap.dotp_gacl_aro_groups;
		
		$groupdId=$this->getRoleId($role_name);
		if($groupdId==-1){		
			//$ldapExt->createDPRole($role_name);
			$this->createDPRole($role_name);
			$groupdId = $this->getRoleId($role_name);
			if($debugMode){
				echo "<br />CREATED ROLE: $role_name (ID: $groupdId) <br/>";
			}
		
			$defaultPermissions=$dPconfig['ldap_template_role_for_copy_permissions'];
			//echo "defaultValue:".$defaultPermissions;
			//die();
			if($defaultPermissions==null || $defaultPermissions==""){
				//$defaultPermissions="normal";
				$msg="No default role configured on dotProject System Configuration";
				$_SESSION["error_msg"]=$msg;
				//echo "No default role set!!";
			    return false;
			}else{
				$this->copyRolePermissions($role_name,$defaultPermissions);
			}
			
			
		}
		 
			
		$userIdPermissions=-1;
		$user_id=-1;
		$q = new DBQuery();
		$q->addQuery("id,value");
		$q->addTable("gacl_aro");
		$q->addWhere("name = '"  . stripslashes($user_name) . "'");
		$sql = $q->prepare();
		$records= db_loadList($sql);
		foreach($records as $record){
			$userIdPermissions= $record[0];
			$user_id=$record[1];
		 }
		
		if($userIdPermissions != -1 && $groupdId != -1){
			/*
			$q = new DBQuery();
			$q->addTable('gacl_groups_aro_map');
			$q->addInsert('group_id', $groupdId);
			$q->addInsert('aro_id', $userIdPermissions);
			$q->exec();
			$q->clear();
			*/
			
			$perms =& $AppUI->acl();
			$user_role=$groupdId;
			if ($perms->insertUserRole($user_role, $user_id)) {
				//$AppUI->setMsg('added', UI_MSG_OK, true);
				$public_contact=true;
				if ($public_contact) {
					// Mark contact as public
					$obj = new CUser();
					$contact = new CContact();
					$obj->load($user_id);
					if ($contact->load($obj->user_contact)) {
						$contact->contact_private = 0;
						$contact->store();
					}
				}
			} else {
				$AppUI->setMsg('failed to add role', UI_MSG_ERROR);
			}
		}	 
	}
	
	//based on: https://samjlevy.com/php-ldap-membership/
	//http://www.forumsys.com/tutorials/integration-how-to/ldap/online-ldap-test-server/)
	//To be utilized in LDAP when ismemberof (posix) is available
	public  function get_groups($user) {
		global $debugMode;
		// Active Directory server
		$ldap_host = $this->ldap_host;//replace for a dynamic value
		$ldap_port=$this->ldap_port;
		
	 
		// Active Directory user for querying
		$query_user = $user."@".$ldap_host;
		$password = $this->ldap_password;
		
		// Active Directory DN, base path for our querying user
		$ldap_dn =$this->ldap_dn;
		$ldap_search_user = $ldapExt->ldap_search_user;

		// Connect to AD
		//$ldap = ldap_connect($ldap_host,$ldap_port) or die("Could not connect to LDAP");
		$ldap = ldap_connect($ldap_host,$ldap_port) or die("Could not connect to LDAP");
		ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $this->ldap_version);
		ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

		if(ldap_bind($ldap,$ldap_search_user,$password)){
			if($debugMode){echo "<br />Bind LDAP successfully.<br />";}
		}else if (ldap_bind($ldap)) {
			if($debugMode){echo "<br />Bind LDAP successfully.<br />";}
		}else{
			if($debugMode){echo "Could not bind to LDAP";}
		}
		// Search AD based on filter eg "DP_*" "DP_it" -> "it" role
		try{
			$results = @ldap_search($ldap,$ldap_dn,"(uid=$user)",array($this->ldap_variable_for_retrieve_roles_list));//"memberof"
			$entries = @ldap_get_entries($ldap, $results);
		}catch(Exception $e){
			
		}
		// No information found, bad user
		//print_r($entries);
		if($entries['count'] == 0){
			if($debugMode){echo "<br />No group found querying for memberof attribute.<br />";}
			return false;
		} 
		
		// Get groups and primary group token
		$output = $entries[0][strtolower($this->ldap_variable_for_retrieve_roles_list)];//memberof
		$token = $entries[0]['primarygroupid'][0];
		
		// Remove extraneous first entry
		if($output!= null && $output != "" && is_array($output)){
			array_shift($output);
		}
		
		// We need to look up the primary group, get list of all groups
		//$results2 = ldap_search($ldap,$ldap_dn,"(objectcategory=group)",array("distinguishedname","primarygrouptoken"));
		$results2 = ldap_search($ldap,$ldap_dn,$this->ldap_query_for_select_dotproject_groups,array("distinguishedname","primarygrouptoken"));//"(&(objectclass=posixGroup)(cn=DP_*))"
		if($debugMode){
			echo "<br />Filtered Group Search:<br />";
			print_r($results2); 	
			//to-do: format results2 to contains a single field with $e['distinguishedname'][0] 	
			echo "<br />";
		}
		//$entries2 = ldap_get_entries($ldap, $results2);
		//
		//// Remove extraneous first entry
		//array_shift($entries2);
		//
		//// Loop through and find group with a matching primary group token
		//foreach($entries2 as $e) {
		//	if($e['primarygrouptoken'][0] == $token) {
		//		// Primary group found, add it to output array
		//		$output[] = $e['distinguishedname'][0];
		//		// Break loop
		//		break;
		//	}
		//}
	 
		//print_r($output);
		return $output;
	}
	
	/**
	parameter group: its identification on LDAP as : "ou=mathematicians,dc=example,dc=com"
	**/
	
	public function getUsersByGroup($group) {
		global $debugMode;
		$users= array();//return variable. Here will be added all users found on this group
		// Connect to AD
		$ldap = ldap_connect($this->ldap_host,$this->ldap_port) or die("Could not connect to LDAP");
		ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $this->ldap_version);///must be version 3
		ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
		if($debugMode){
			echo "<br/>Variables:";
			echo "<br/>ldap_host: {$this->ldap_host}";
			echo "<br/>ldap_port: {$this->ldap_port}";
			echo "<br/>ldap_version: {$this->ldap_version}";
			echo "<br/>ldap_dn: {$this->ldap_dn}";
			echo "<br/>ldap_pasSword: {$this->ldap_password}";
		}
		if(@ldap_bind($ldap,$ldapExt->ldap_search_user,$this->ldap_password)){
			if($debugMode){echo "<br />Bind LDAP successfully.<br />";}
		}else if (@ldap_bind($ldap)) { 
			if($debugMode){echo "<br />Bind LDAP successfully.<br />";}
		}else{
			if($debugMode){die("Could not bind to LDAP");}
		} 
		
		//search for queried group
		// Search AD
		if($debugMode){echo "<br />Looking for LDAP group  \"" . $group . "\"<br />";}
		$results = @ldap_search($ldap,$group,"(cn=*)" );
		if($results!=""){
			$entries = ldap_get_entries($ldap, $results);
			
			// No information found, bad user
			if($entries['count'] >0){
			// Get groups and primary group token
				$output = $entries[0]['uniquemember'];		
				foreach ($output as $user){
					$commaPos=strpos($user,",");
					$userName=substr($user,4,$commaPos-4);
					array_push($users,$userName);
				}
			} 
		}else{
			if($debugMode){echo "<br />Group \"".$group ."\" not found on LDAP<br />";}
		}
		return $users;
	}
	
	function EventQueue_runSynchronization(){
			$syncType=$dPconfig['ldap_variable_for_retrieve_roles_list'];
			if(strtolower($syncType)=="memberof"){ 
				require_once DP_BASE_DIR ."/modules/ldap_extended/do_ldap_memberof_based.php";
			}else{
				require_once DP_BASE_DIR ."/modules/ldap_extended/do_ldap_group_membership_based.php";
			}
		
	}
	
	public function isModuleActive(){
		$q = new DBQuery();
		$q->addQuery("mod_id");
		$q->addTable("modules");
		$q->addWhere("mod_name='ldap_extended' and mod_active=1");
		$sql = $q->prepare();
		$records= db_loadList($sql);
		$isModActive=false;
		foreach($records as $record){
			$isModActive=true;
		 }
		 return $isModActive;
	}
	
}
