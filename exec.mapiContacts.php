<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.mapi-zarafa.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');


if($argv[1]=="--sent"){inject_blacklists($argv[2]);die();}

start();


function start(){
	$sock=new sockets();
	$ZarafaAdbksWhiteTask=$sock->GET_INFO("ZarafaAdbksWhiteTask");
	$ZarafaWhiteSentItems=$sock->GET_INFO("ZarafaWhiteSentItems");
	$ZarafaJunkItems=$sock->GET_INFO("ZarafaJunkItems");
	if(!is_numeric($ZarafaAdbksWhiteTask)){$ZarafaAdbksWhiteTask=0;}
	if(!is_numeric($ZarafaWhiteSentItems)){$ZarafaWhiteSentItems=1;}
	if(!is_numeric($ZarafaJunkItems)){$ZarafaJunkItems=0;}
	if($ZarafaAdbksWhiteTask==0){return;}
	$q=new mysql();
	$q->BuildTables();
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if($unix->process_exists($unix->get_pid_from_file($pidfile))){
		system_admin_events("Already exists in memory, aborting task", __FUNCTION__, __FILE__, __LINE__, "zarafa");
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$ZarafaServerListenIP=$sock->GET_INFO("ZarafaServerListenIP");
	if($ZarafaServerListenIP==null){$ZarafaServerListenIP="127.0.0.1";}
	if($ZarafaServerListenIP=="0.0.0.0"){$ZarafaServerListenIP="127.0.0.1";}
	
	$ldap=new clladp();
	$ous=$ldap->hash_get_ou(true);
	$countDeOu=count($ous);
	system_admin_events("Parsing $countDeOu organization(s)", __FUNCTION__, __FILE__, __LINE__, "zarafa");
	$GLOBALS["ITEMSC"]=0;
	
	while (list ($ou, $none) = each ($ous) ){
		$users=$ldap->hash_users_ou($ou);
		$CountDeUsers=count($users);
		system_admin_events("$ou $CountDeUsers users", __FUNCTION__, __FILE__, __LINE__, "zarafa");
		if(!is_array($users)){continue;}
		if(count($users)==0){continue;}
		while (list ($uid, $none2) = each ($users) ){
			if(trim($uid)==null){continue;}
			import_contacts($uid,$ZarafaServerListenIP);
			if($ZarafaWhiteSentItems==1){import_sentitems($uid);}
			if($ZarafaJunkItems==1){inject_blacklists($uid);}
		}
		
	}
	
	system_admin_events("Adding {$GLOBALS["ITEMSC"]} contacts in white list", __FUNCTION__, __FILE__, __LINE__, "zarafa");
	$EnableAmavisDaemon=$sock->GET_INFO("EnableAmavisDaemon");
	if(!is_numeric($EnableAmavisDaemon)){$EnableAmavisDaemon=0;}
	$php5=$unix->LOCATE_PHP5_BIN();
	$users=new usersMenus();
	if($users->AMAVIS_INSTALLED){
		if($EnableAmavisDaemon==1){
			shell_exec("$php5 ". dirname(__FILE__)."/exec.amavis.php >/dev/null 2>&1");
			shell_exec("/etc/init.d/amavis reload");
		}
	}
	if($users->MILTERGREYLIST_INSTALLED){
		shell_exec("$php5 ". dirname(__FILE__)."/exec.milter-greylist.php >/dev/null 2>&1");
	}
}

function import_contacts($uid,$listenip){
	$zarafa=new mapizarafa();
	if(!$zarafa->Connect($uid)){
		system_user_events($uid, $zarafa->error, __FUNCTION__, __FILE__, __LINE__, "contacts");
		return;
	}
	inject_contacts($uid,$zarafa->HashGetContacts());
	
	
	
	
	
	//	print_r(getPublicContactFolders($session,$publicstore));

}

function import_sentitems($uid){
	$zarafa=new mapizarafa();
	if(!$zarafa->Connect($uid)){
		system_user_events($uid, $zarafa->error, __FUNCTION__, __FILE__, __LINE__, "contacts");
		return;
	}
	
	$array=$zarafa->GetRecipientsFromFolder("Sent Items");
	if(count($array)>0){
		inject_sentitems($uid,$array);
	}
	
	
}
function inject_sentitems($uid,$array){

	while (list ($index, $emailAddress_str) = each ($array) ){


		$md5=md5("$emailAddress_str$uid");
		$f[]="('$emailAddress_str','$uid','1','$md5','1')";

		
	}

	if(count($f)>0){
		$q=new mysql();
		system_user_events($uid,count($f)." are added to the whitelist database from sent items..", __FUNCTION__, __FILE__, __LINE__, "whitelist");
		$sql="INSERT IGNORE INTO contacts_whitelist (`sender`,`uid`,`manual`,`md5`,`enabled`) VALUES ".@implode(",", $f);
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			system_admin_events("Fatal: $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "zarafa");
			return;
		}
			
		$GLOBALS["ITEMSC"]=$GLOBALS["ITEMSC"]+count($f);
	}

}

function inject_blacklists($uid){
	
	
	$zarafa=new mapizarafa();
	
	if(!$zarafa->Connect($uid)){
		system_user_events($uid, $zarafa->error, __FUNCTION__, __FILE__, __LINE__, "contacts");
		return;
	}	
	
	$array=$zarafa->GetSendersFromFolder("Junk E-mail");
	if(count($array)>0){
		inject_blacklists_tomysql($uid,$array);
	}	
	
}

function inject_blacklists_tomysql($uid,$contacts){
	
	$q=new mysql();
	
	if(!$q->FIELD_EXISTS("contacts_blacklist","Junk","artica_backup")){
		$sql="ALTER TABLE `contacts_blacklist` ADD `Junk` smallint( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `Junk` )";
		$q->QUERY_SQL($sql,'artica_backup');
	}
	
	while (list ($emailAddress_str, $none) = each ($contacts) ){
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT uid FROM `contacts_whitelist` WHERE sender='$emailAddress_str'","artica_backup"));
		if($ligne2["uid"]<>null){continue;}
		
		$md5=md5("$emailAddress_str$uid");
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT uid FROM `contacts_blacklist` WHERE md5='$md5'","artica_backup"));
		if($ligne2["uid"]<>null){
			if($GLOBALS["VERBOSE"]){echo "$md5 $emailAddress_str Already added in contacts_blacklist for [{$ligne2["uid"]}]\n";}
			continue;}
		
		$f[]="('$emailAddress_str','$uid','$md5','1','1')";
		if($GLOBALS["VERBOSE"]){echo "$uid -> $emailAddress_str $md5\n";}
	}
	
	if(count($f)>0){
		system_user_events($uid,count($f)." are added to the blacklist database..", __FUNCTION__, __FILE__, __LINE__, "blacklist");
		$sql="INSERT IGNORE INTO contacts_blacklist (`sender`,`uid`,`md5`,`enabled`,`Junk`) VALUES ".@implode(",", $f);
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo "$q->mysql_error\n";}
			system_admin_events("Fatal: $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "zarafa");
			return;
		}
			
		$GLOBALS["ITEMSC"]=$GLOBALS["ITEMSC"]+count($f);
	}	
	
}


function inject_contacts($uid,$contacts){
	
	while (list ($index, $array) = each ($contacts) ){
		
		$emailAddress_str=$array["email1address"];
		$emailAddress_str=trim(strtolower($emailAddress_str));
		if($emailAddress_str==null){continue;}
		if(!ValidateMail($emailAddress_str)){
			if($GLOBALS["VERBOSE"]){echo "inject_contacts($uid,...) -> ValidateMail($emailAddress_str) -> `FALSE`\n";}
			continue;
		}
		$md5=md5("$emailAddress_str$uid");
		$f[]="('$emailAddress_str','$uid','$md5','1')";
		
		$emailAddress_str=$array["email2address"];
		$emailAddress_str=trim(strtolower($emailAddress_str));
		if($emailAddress_str==null){continue;}
		if(!ValidateMail($emailAddress_str)){
			if($GLOBALS["VERBOSE"]){echo "inject_contacts($uid,...) -> ValidateMail($emailAddress_str) -> `FALSE`\n";}
			continue;
		}
		
		$md5=md5("$emailAddress_str$uid");
		$f[]="('$emailAddress_str','$uid','$md5','1')";

		$emailAddress_str=$array["email3address"];
		$emailAddress_str=trim(strtolower($emailAddress_str));
		if($emailAddress_str==null){continue;}
		if(!ValidateMail($emailAddress_str)){
			if($GLOBALS["VERBOSE"]){echo "inject_contacts($uid,...) -> ValidateMail($emailAddress_str) -> `FALSE`\n";}
			continue;
		}
		
		$md5=md5("$emailAddress_str$uid");
		$f[]="('$emailAddress_str','$uid','$md5','1')";
		}
		
		if(count($f)>0){
			$sql="DELETE FROM `contacts_whitelist` WHERE uid='$uid' AND manual=0 AND enabled=1";
			$q=new mysql();
			$q->QUERY_SQL($sql,"artica_backup");
			system_user_events($uid,count($f)." are added to the whitelist database..", __FUNCTION__, __FILE__, __LINE__, "whitelist");
			$sql="INSERT IGNORE INTO contacts_whitelist (`sender`,`uid`,`md5`,`enabled`) VALUES ".@implode(",", $f);
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){
				system_admin_events("Fatal: $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "zarafa");
				return;
			}
			
			$GLOBALS["ITEMSC"]=$GLOBALS["ITEMSC"]+count($f);
		}
		
}

function ValidateMail($emailAddress_str) {
	$emailAddress_str=trim(strtolower($emailAddress_str));
	$regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/'; 
	if (preg_match($regex, $emailAddress_str)) {return true;}
	return false;
}
/**
 * Get the private contact folder of all users
 */

function getPrivateContactFolders($session,$defaultstore) {

	

	$addrbook = mapi_openaddressbook($session);
	$addr_entryid = mapi_ab_getdefaultdir($addrbook);
	$abcontainer = mapi_ab_openentry($addrbook,$addr_entryid);
	$contentstable = mapi_folder_getcontentstable($abcontainer);
	
	// restrict table on only MAPI_MAILUSER accounts
	mapi_table_restrict($contentstable, array(RES_PROPERTY, array(RELOP=>RELOP_EQ, ULPROPTAG=>PR_OBJECT_TYPE, VALUE=>array(PR_OBJECT_TYPE=>MAPI_MAILUSER))));

	// sort table on display name
	mapi_table_sort($contentstable, array(PR_DISPLAY_NAME=>TABLE_SORT_ASCEND));

	$users = mapi_table_queryrows($contentstable, array(PR_ACCOUNT, PR_ENTRYID, PR_DISPLAY_NAME), 0, mapi_table_getrowcount($contentstable));
	$contactArray = array();
	for($i=0;$i<sizeof($users);$i++) {
		$store_entryid = mapi_msgstore_createentryid($defaultstore, $users[$i][PR_ACCOUNT]);
		$store = mapi_openmsgstore($session, $store_entryid);
		$rootcontainer = mapi_msgstore_openentry($store);
		if($rootcontainer) {
			$props = mapi_getprops($rootcontainer, array(PR_IPM_CONTACT_ENTRYID));
			if(isset($props[PR_IPM_CONTACT_ENTRYID])) {
				$entryid = $props[PR_IPM_CONTACT_ENTRYID];
				$folder=mapi_msgstore_openentry($store,$entryid);
				if($folder) {
					$table=mapi_folder_getcontentstable($folder);
					$totalrow=mapi_table_getrowcount($table);
					$rows = array();
					$contacts = array();
					$properties = getContactProperties($defaultstore);
					if ($totalrow>0) {
						$rows = mapi_table_queryrows($table, $properties,0,$totalrow);
						for($j=0;$j<sizeof($rows);$j++) {
							$rows[$j][268370178] = md5($rows[$j][268370178]);
						}
						for($k=0;$k<sizeof($rows);$k++) {
							// do not add private contacts
							if (!(array_key_exists(-2119827445, $rows[$k]))  || (array_key_exists(-2119827445, $rows[$k]) && $rows[$k][-2119827445] != 1)) {
								foreach ($rows[$k] as $key => $value) {
									$attribute = mapKey($key);
									if ($attribute != "")
										$contacts[$k][$attribute] = $value;
								}
							}
						}
						$contactArray[] = array("username" => $users[$i][PR_ACCOUNT], "contacts" => $contacts);
					}
				}
			}
		}

	}
 //	print_r($contactArray);
	return $contactArray;
}


/**
 * Get all public contact folders
 */

function getPublicContactFolders($session,$publicstore) {

	
	
	$pub_folder=mapi_msgstore_openentry($publicstore);
	$h_table=mapi_folder_gethierarchytable($pub_folder, CONVENIENT_DEPTH);
	$contact_properties = getContactProperties($publicstore);

	$subfolders = mapi_table_queryallrows($h_table, array(PR_ENTRYID,PR_DISPLAY_NAME,PR_DISPLAY_TYPE,PR_CONTAINER_CLASS,PR_SUBFOLDERS));	
	$pub_list2 = array();
	$contacts = array();
	foreach($subfolders as $folder) {
	
		// check if folder contains PR_CONTAINER_CLASS and if its a contact
		if (isset($folder[907214878]) && $folder[907214878] == "IPF.Contact" && $folder[805371934] != "Kontakte") {
			$entryid = $folder[268370178];
			$pub_folder2=mapi_msgstore_openentry($publicstore,$entryid);
			$pub_table2 = mapi_folder_getcontentstable($pub_folder2);
			$pub_list2 = mapi_table_queryallrows($pub_table2,$contact_properties);
			for($j=0;$j<sizeof($pub_list2);$j++) {
				$pub_list2[$j][268370178] = md5($pub_list2[$j][268370178]);
			}
			for($k=0;$k<sizeof($pub_list2);$k++) {
						foreach ($pub_list2[$k] as $key => $value) {
							$attribute = mapKey($key);
							if ($attribute != "")
								$contacts[$k][$attribute] = $value;
						}
				}
			//$contactFolders[$folder[805371934]] = $pub_list2;
			$contactFolders[] = array("foldername" => $folder[805371934], "contacts" => $contacts);
		}	
	}
 	//print_r($contactFolders);	
	return $contactFolders;
}


function getContactProperties($mystore) {
						
	$properties = array (
		"entryid"			        	=> PR_ENTRYID,
		"body"				        	=> PR_BODY,
		"anniversary"			    	=> PR_WEDDING_ANNIVERSARY,
		"assistantname"		    		=> PR_ASSISTANT,
		"assistnamephonenumber"			=> PR_ASSISTANT_TELEPHONE_NUMBER,
		"birthday"				        => PR_BIRTHDAY,
		"businessphonenumber"			=> PR_OFFICE_TELEPHONE_NUMBER,
		"business2phonenumber"			=> PR_BUSINESS2_TELEPHONE_NUMBER,
		"businessfaxnumber"		    	=> PR_BUSINESS_FAX_NUMBER,
		"carphonenumber"		    	=> PR_CAR_TELEPHONE_NUMBER,
		"categories"			    	=> "PT_MV_STRING8:{00020329-0000-0000-C000-000000000046}:Keywords", //categories
		"companyname"			    	=> PR_COMPANY_NAME,
		"department"			    	=> PR_DEPARTMENT_NAME,
		"displayname"			    	=> PR_DISPLAY_NAME,
		"email1address"			    	=> "PT_STRING8:{00062004-0000-0000-C000-000000000046}:0x8083", //email1address
		"email2address"			    	=> "PT_STRING8:{00062004-0000-0000-C000-000000000046}:0x8093", //email2address
		"email3address"			    	=> "PT_STRING8:{00062004-0000-0000-C000-000000000046}:0x80A3", //email3address
		"fileas"			          	=> "PT_STRING8:{00062004-0000-0000-C000-000000000046}:0x8005", //fileas
		"firstname"			    	    => PR_GIVEN_NAME,
		"home2phonenumber"			    => PR_HOME2_TELEPHONE_NUMBER,
		"homecity"			        	=> PR_HOME_ADDRESS_CITY,
		"homecountry"		    		=> PR_HOME_ADDRESS_COUNTRY,
		"homepostalcode"		    	=> PR_HOME_ADDRESS_POSTAL_CODE,
		"homestate"			        	=> PR_HOME_ADDRESS_STATE_OR_PROVINCE,
		"homestreet"			    	=> PR_HOME_ADDRESS_STREET,
		"homefaxnumber"			    	=> PR_HOME_FAX_NUMBER,
		"homephonenumber"		    	=> PR_HOME_TELEPHONE_NUMBER,
		"jobtitle"			        	=> PR_PROFESSION,
		"lastname"			        	=> PR_SURNAME,
		"mailingaddresstype"			=> "PT_LONG:{00062004-0000-0000-C000-000000000046}:0x8022",
		"middlename"		    		=> PR_MIDDLE_NAME,
		"mobilephonenumber"	    		=> PR_CELLULAR_TELEPHONE_NUMBER,
		"othercity"			        	=> PR_OTHER_ADDRESS_CITY,
		"othercountry"			    	=> PR_OTHER_ADDRESS_COUNTRY,
		"otherpostalcode"		    	=> PR_OTHER_ADDRESS_POSTAL_CODE,
		"otherstate"			    	=> PR_OTHER_ADDRESS_STATE_OR_PROVINCE,
		"otherstreet"			    	=> PR_OTHER_ADDRESS_STREET,
		"pagernumber"		    		=> PR_PAGER_TELEPHONE_NUMBER,
		"private"				        => "PT_BOOLEAN:{00062008-0000-0000-C000-000000000046}:0x8506",
		"radiophonenumber"		    	=> PR_RADIO_TELEPHONE_NUMBER,
		"salutation"		    		=> PR_DISPLAY_NAME_PREFIX,
		"spouse"				        => PR_SPOUSE_NAME,
		"suffix"        				=> PR_GENERATION,
		"title"			        		=> PR_TITLE,
		"webpage"			        	=> PR_BUSINESS_HOME_PAGE,
		"webpage"			        	=> "PT_STRING8:{00062004-0000-0000-C000-000000000046}:0x802B", //webpage
		"homeaddress"	    			=> "PT_STRING8:{00062004-0000-0000-C000-000000000046}:0x801A", //homeaddress
		"businessaddress"	    		=> "PT_STRING8:{00062004-0000-0000-C000-000000000046}:0x801B", //businessaddress
		"otheraddress"			    	=> "PT_STRING8:{00062004-0000-0000-C000-000000000046}:0x801C", //otheraddress
		"businessstreet"    			=> "PT_STRING8:{00062004-0000-0000-C000-000000000046}:0x8045", //business street
		"businesscity"		    		=> "PT_STRING8:{00062004-0000-0000-C000-000000000046}:0x8046", //business city
		"businessstate"			    	=> "PT_STRING8:{00062004-0000-0000-C000-000000000046}:0x8047", //business state
		"businesspostalcode"		   	=> "PT_STRING8:{00062004-0000-0000-C000-000000000046}:0x8048", //business zip
		"businesscountry"	    		=> "PT_STRING8:{00062004-0000-0000-C000-000000000046}:0x8049", //business country
		"imaddress"			        	=> "PT_STRING8:{00062004-0000-0000-C000-000000000046}:0x8062", //imaddress
		"lastmodificationtime"			=> PR_LAST_MODIFICATION_TIME,
		"sourcekey"		    	    	=> PR_SOURCE_KEY,
		"deleted"		         		=> MAPI_ACCESS_DELETE
	);

	return getPropIdsFromStrings($mystore, $properties);
}



/**
 * Map mapiKey to string representation (mapiTag)
 */


function mapKey($key) {

	switch ($key) {
		case 268370178:
			return "uid";
			break;
		case 805371934:
			return "displayName";
			break;
		case 974192670:
			return "surname";
			break;
		case 973471774:
			return "givenname";
			break;
		case 974913566:
			return "mobile";
			break;
		case -2127364066:
			return "email1address";
			break;
		case -2126315490:
			return "email2address";
			break;
		case -2125266914:
			return "email3address";
			break;
		case 973602846:
			return "telephoneNumber";
			break;
		case 975437854:
			return "facsimileTelephoneNumber";
			break;
		case 974520350:
			return "o";
			break;
		case 974651422:
			return "ou";
			break;
		case -2131427298:
			return "street";
			break;
		case -2131361762:
			return "postalAddress";
			break;
		case -2131361762:
			return "postalCode";
			break;
		case -2134245346:
			return "homePostalAddress";
			break;
		case 977600542:
			return "title";
			break;
		case 973668382:
			return "homePhone";
			break;
		case 268435486:
			return "description";
			break;
		case 976224286:
			return "secretary";
			break;
		case 975241246:
			return "pager";
			break;
		case 974585886:
			return "employeeType";
			break;
	}

}

/**
 * Begin the HTTP listener service and exit.
 */
?>