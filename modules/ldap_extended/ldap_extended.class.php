<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
global $AppUI;
require_once $AppUI->getModuleClass('contacts');
require_once $AppUI->getModuleClass('admin');
require_once $AppUI->getSystemClass('dp');


class CLDAPExtended extends CDpObject {
		//replace such values from some configuration data
		//credentials
		var $ldap_host = "ldap.forumsys.com";
		var $ldap_port=389;
		//credatials for login
		var $ldap_dn = "cn=read-only-admin,dc=example,dc=com";//replace dynamic
		var $password = "password";//replace for a dynamic value
		
		
		function __construct() {
			parent::__construct('ldap_extended', 'ldap_extended_id');
		}
	
	
	
/*
  var $ldap_extended_id = NULL;
  var $user_id = NULL;
	


	
	
 
	function check() {
	// ensure the integrity of some variables
		$this->ldap_extended_id = intval($this->ldap_extended_id);

		return NULL; // object is ok
	}

	function delete($oid = NULL, $history_desc = '', $history_proj = 0) {
		global $dPconfig;
	
	}
	*/	
	//SELECT * FROM   information_schema.tables WHERE  TABLE_SCHEMA = 'dotproject_ldap' order by UPDATE_TIME desc LIMIT 0, 1000
	
	
	
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
			echo $sql; 
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
	
	public function addRoleToUser($user_name, $role_name){
	
		global $AppUI;
		//SELECT group_id, aro_id FROM dotproject_ldap.dotp_gacl_groups_aro_map;
		//SELECT id, name FROM dotproject_ldap.dotp_gacl_aro;
		//SELECT id,name,value FROM dotproject_ldap.dotp_gacl_aro_groups;
		
		$groupdId=-1;
		$q = new DBQuery();
		$q->addQuery("id");
		$q->addTable("gacl_aro_groups");
		$q->addWhere("name = '"  . stripslashes($role_name) . "' or value='".stripslashes($role_name)."'");
		$sql = $q->prepare();
		$records= db_loadList($sql);
		foreach($records as $record){
			$groupdId= $record[0];
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
				$AppUI->setMsg('added', UI_MSG_OK, true);
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
	public static function get_groups($user) {
		// Active Directory server
		$ldap_host = "ldap.forumsys.com";//replace for a dynamic value
		$ldap_port=389;
		
	 
		// Active Directory user for querying
		$query_user = $user."@".$ldap_host;
		$password = "password";//replace for a dynamic value
		
		// Active Directory DN, base path for our querying user
		//$ldap_dn = "cn=read-only-admin,dc=example,dc=com";//replace dynamic
		
		$ldap_dn = "uid=galieleo,dc=example,dc=com";
		//$ldap_dn = "ou=mathematicians,dc=example,dc=com";

		// Connect to AD
		//$ldap = ldap_connect($ldap_host,$ldap_port) or die("Could not connect to LDAP");
		$ldap = ldap_connect($ldap_host,$ldap_port) or die("Could not connect to LDAP");
		ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
		
		if(ldap_bind($ldap,$ldap_dn,$password)){
			echo "Bind LDAP successfully.";
		}else{
			die("Could not bind to LDAP");
		} 
		
		// Search AD
		$attr = array("OU","CN","DC");
		//$ldap_dn_group = "ou=mathematicians,dc=example,dc=com";
		$results = ldap_search($ldap,$ldap_dn,"(cn=*)" );
		
		
		//$results = ldap_search($ldap,$ldap_dn,"(uid=gauss)",  $attr);	
		
		
		
		//$results = ldap_search($ldap,$ldap_dn,"(samaccountname=$user)",array("memberof","primarygroupid")); //samaccountname	
		$entries = ldap_get_entries($ldap, $results);		
		?>
		<pre>
		<?php print_r($entries ); ?>
		</pre>
		<?php
		
		// No information found, bad user
		if($entries['count'] == 0){
			echo "user not found...";
			return false;
		} 
		
		// Get groups and primary group token
		$output = $entries[0]['memberof'];
		$token = $entries[0]['primarygroupid'][0];
		
		// Remove extraneous first entry
		array_shift($output);
		
		// We need to look up the primary group, get list of all groups
		$results2 = ldap_search($ldap,$ldap_dn,"(objectcategory=group)",array("distinguishedname","primarygrouptoken"));
		$entries2 = ldap_get_entries($ldap, $results2);
		
		// Remove extraneous first entry
		array_shift($entries2);
		echo "3";
		// Loop through and find group with a matching primary group token
		foreach($entries2 as $e) {
			if($e['primarygrouptoken'][0] == $token) {
				// Primary group found, add it to output array
				$output[] = $e['distinguishedname'][0];
				// Break loop
				break;
			}
		}
		
		return $output;
	}
	
	/**
	paramter group: its identification on LDAP as : "ou=mathematicians,dc=example,dc=com"
	**/
	public function getUsersByGroup($group) {
		// Active Directory server

		// Connect to AD
		$ldap = ldap_connect($this->ldap_host,$this->ldap_port) or die("Could not connect to LDAP");
		ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
		
		if(ldap_bind($ldap,$this->ldap_dn,$this->password)){
			//echo "Bind LDAP successfully.";
		}else{
			die("Could not bind to LDAP");
		} 
		
		// Search AD
		$results = ldap_search($ldap,$group,"(cn=*)" );
		$entries = ldap_get_entries($ldap, $results);		
		//print_r($entries );

		$users= array();
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
		return $users;
	}
	
	
}