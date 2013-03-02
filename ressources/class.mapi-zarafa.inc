<?php
include_once("/usr/share/php/mapi/mapi.util.php");
include_once("/usr/share/php/mapi/mapidefs.php");
include_once("/usr/share/php/mapi/mapicode.php");
include_once("/usr/share/php/mapi/mapitags.php");
include_once("/usr/share/php/mapi/mapiguid.php");



include('/usr/share/php/mapi/class.recurrence.php');
include('/usr/share/php/mapi/class.freebusypublish.php');
include_once(dirname(__FILE__).'/class.templates.inc');
include_once(dirname(__FILE__).'/class.ini.inc');
include_once(dirname(__FILE__).'/class.os.system.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__).'/class.user.inc');
include_once(dirname(__FILE__).'/class.zarafa-listfolders.inc');


class mapizarafa{
	private $ZarafaServerListenIP=null;
	private $zarafaServer=null;
	private $session;
	private $storetable;
	private $default_store_entry_id;
	public  $storesize;
	private $default_publicstore_entry_id;
	private $default_store;
	private $default_publicstore;
	private $publicstore;
	public $error;
	public $Folders=array();
	
	public function mapizarafa(){
		$this->load();
		$this->mapidefs();
		
	}
	
	private function load(){
		$sock=new sockets();
		$this->ZarafaServerListenIP=$sock->GET_INFO("ZarafaServerListenIP");
		if($this->ZarafaServerListenIP==null){$this->ZarafaServerListenIP="127.0.0.1";}
		$this->zarafaServer = "http://$this->ZarafaServerListenIP:236/zarafa";
	}
	
	
	public function list_folders($uid,$Public=false){
	
		$mapi_listfolder=new mapi_listfolder();
		$ct=new user($uid);
		
		if(!$mapi_listfolder->Listfolders($uid, $ct->password, $this->zarafaServer,$Public)){
			$this->error=$mapi_listfolder->error;
			return false;
		}
		$this->Folders=$mapi_listfolder->FOLDERS;
		if($GLOBALS["VERBOSE"]){echo "list_folders($uid) ".count($this->Folders)." entries<br>\n";}
		return true;
	}
	
	
	public function Connect($uid,$password=null){
		if($password==null){
			$ct=new user($uid);
			$password=$ct->password;
		}
		
		$this->session = @mapi_logon_zarafa($uid, $password, $this->zarafaServer);
		if(!$this->session) { 
			$error_text=mapi_last_hresult();
			$error="Connect::[".__LINE__."]::mapi_logon_zarafa() Unable to open session $uid@$this->zarafaServer $error_text<br>\n";
			$this->error=$error; return false;
		}
		
		$this->storetable = mapi_getmsgstorestable($this->session);
		if(!$this->storetable) {
			$resultErr = @mapi_last_hresult();
			$error="Unable to open message stores table $uid@$this->zarafaServer $resultErr\n";
			$this->error=$this->error=$error; 
			return false;
		}
		
		$storeslist = mapi_table_queryallrows($this->storetable, array(PR_ENTRYID, PR_MDB_PROVIDER,PR_DEFAULT_STORE));
		$this->publicstore = mapi_openmsgstore($this->session, $storeslist[1][PR_ENTRYID]);
		//$defaultstore = mapi_openmsgstore($session, $storeslist[1][PR_ENTRYID]);		
		
		$msgstores = mapi_table_queryallrows($this->storetable, array(PR_DEFAULT_STORE, PR_ENTRYID));
		foreach ($msgstores as $row) {
			if($GLOBALS["VERBOSE"]){
				echo "$msgstores: \$row[PR_MDB_PROVIDER] = {$row[PR_MDB_PROVIDER]}<br>\n";
				
			}
			
			if ($row[PR_MDB_PROVIDER] == ZARAFA_STORE_PUBLIC_GUID){
				if($GLOBALS["VERBOSE"]){echo "Found Public store....<br>\n";}
				$this->default_publicstore_entry_id = $row[PR_ENTRYID];
							
			}
			
			
			if($row[PR_DEFAULT_STORE]) {
				$this->default_store_entry_id = $row[PR_ENTRYID];
				$props = mapi_getprops($this->default_store_entry_id , array(PR_MESSAGE_SIZE_EXTENDED));
				$this->storesize=$props[PR_MESSAGE_SIZE_EXTENDED];
			}
		}		
		$this->default_store = mapi_openmsgstore($this->session, $this->default_store_entry_id );
		
		if(!$this->default_store){
			$error="mapi_openmsgstore return false;\n";
			if($GLOBALS["VERBOSE"]){echo $error;}
			$this->error=$this->error=$error;
			return;
		}		
		
		return true;
		
	}
	
public function stores_size(){
	$session = $this->session;
	$storesTable = mapi_getmsgstorestable($session);
	$stores = mapi_table_queryallrows($storesTable, array(PR_ENTRYID, PR_DEFAULT_STORE, PR_MDB_PROVIDER));
	
	for($i=0;$i<count($stores); $i++){
		if (isset($stores[$i][PR_MDB_PROVIDER]) && $stores[$i][PR_MDB_PROVIDER] == ZARAFA_STORE_PUBLIC_GUID){
			$pubStoreEntryId = $stores[$i][PR_ENTRYID];
		}
		if (isset($stores[$i][PR_DEFAULT_STORE]) && $stores[$i][PR_DEFAULT_STORE]){
			$defaultStoreEntryId = $stores[$i][PR_ENTRYID];
		}
	}
	
	if (!isset($defaultStoreEntryId)){
		$this->error="Default store not found";
		return false;
	}
	$default_store = mapi_openmsgstore($session, $pubStoreEntryId);
	
	if (mapi_last_hresult()!=0){
		$error_text=mapi_last_hresult();
		$this->error=$error_text;
	}
	
	// get public store
	if (!isset($pubStoreEntryId)){
		$this->error="No public found";
		return false;
	}
	$public_store = mapi_openmsgstore($session, $pubStoreEntryId);
	
	if (mapi_last_hresult()!=0){
		$error_text=mapi_last_hresult();
		$this->error=$error_text;
		return false;
	}
	
	
	// open addressbook
	$ab = mapi_openaddressbook($session);
	if (mapi_last_hresult()!=0){
		$error_text=mapi_last_hresult();
		$this->error=$error_text;
		return false;
	}
	
	$entryid = mapi_ab_getdefaultdir($ab);
	$dir = mapi_ab_openentry($ab,$entryid);
	$table = mapi_folder_getcontentstable($dir);
	
	// restrict table on only MAPI_MAILUSER accounts
	mapi_table_restrict($table, array(RES_PROPERTY, array(RELOP=>RELOP_EQ, ULPROPTAG=>PR_OBJECT_TYPE, VALUE=>array(PR_OBJECT_TYPE=>MAPI_MAILUSER))));
	
	// sort table on display name
	mapi_table_sort($table, array(PR_DISPLAY_NAME=>TABLE_SORT_ASCEND));
	
	$users = mapi_table_queryrows($table, array(PR_ACCOUNT, PR_ENTRYID, PR_DISPLAY_NAME), 0, mapi_table_getrowcount($table));
	if (mapi_last_hresult()!=0){
		$error_text=mapi_last_hresult();
		$this->error=$error_text;
		return false;
	}
	
	// output table
	$size=0;
	for($i=0;$i<sizeof($users);$i++){
		// open store of the user
		$store_entryid = mapi_msgstore_createentryid($default_store, $users[$i][PR_ACCOUNT]);
		if (mapi_last_hresult()!=0){
			$error_text=mapi_last_hresult();
			$this->error=$error_text;
			return false;
		}
		$store = mapi_openmsgstore($session, $store_entryid);
		if (mapi_last_hresult()!=0){
			$error_text=mapi_last_hresult();
			$this->error=$error_text;
			return false;
		}
	
		// get store size
		$props = mapi_getprops($store, array(PR_MESSAGE_SIZE_EXTENDED));
		if (isset($props[PR_MESSAGE_SIZE_EXTENDED])){
			$sname=$users[$i][PR_DISPLAY_NAME];
			$size=$size + $props[PR_MESSAGE_SIZE_EXTENDED];
			$hash[$sname]=sprintf("%2.2f MB",($props[PR_MESSAGE_SIZE_EXTENDED]/1024/1024));
		}
	}
	
	
	// get size of public store
	$props = mapi_getprops($public_store, array(PR_MESSAGE_SIZE_EXTENDED));
	if (isset($props[PR_MESSAGE_SIZE_EXTENDED])){
		$size=$size+ $props[PR_MESSAGE_SIZE_EXTENDED];
		$hash["Public Store"]=sprintf("%2.2f MB",($props[PR_MESSAGE_SIZE_EXTENDED]/1024/1024));
	}
	$hash["Total"]=$size;
	return $hash;
	
	
	
	
}
	
	
	
	
	public function HashGetContacts(){
			$contacts=getPrivateContactFolders($this->session,$this->default_store);
			if(isset($contacts[0]["contacts"])){return $contacts[0]["contacts"];}
			return array();
	}
	
	private function GetFolder_PR_ENTRYID($folder_to_process){
		

		if(!$this->default_store){
			$error="default_store return false;\n";
			if($GLOBALS["VERBOSE"]){echo $error;}
			$this->error=$this->error=$error;
			return;
		}
		
		$root = mapi_msgstore_openentry($this->default_store);
		
		if(!$root){
			$error="mapi_msgstore_openentry return false;\n";
			if($GLOBALS["VERBOSE"]){echo $error;}
			$this->error=$this->error=$error;
			return;
		}		
		
		$folders = mapi_folder_gethierarchytable($root, CONVENIENT_DEPTH);
		if(!$folders){
			$error="mapi_folder_gethierarchytable return false;\n";
			if($GLOBALS["VERBOSE"]){echo $error;}
			$this->error=$this->error=$error;
			return;		
		}
		while(1) {
			$rows = mapi_table_queryrows($folders, array(PR_DISPLAY_NAME, PR_FOLDER_TYPE, PR_ENTRYID), 0, 100);
			if(count($rows) == 0){break;}
		
			foreach($rows as $row) {
				// skip searchfolders
				if(isset($row[PR_FOLDER_TYPE]) && $row[PR_FOLDER_TYPE] == FOLDER_SEARCH) continue;
		
				// operate only on folders, whose name is specified in the config section.
				// Like 'Sent Objects'.
				if($GLOBALS["VERBOSE"]){echo "Search `$folder_to_process` <>`{$row[PR_DISPLAY_NAME]}`\n";}
				if( $row[PR_DISPLAY_NAME] == $folder_to_process ) {
					if($GLOBALS["VERBOSE"]){echo "FOUND `$folder_to_process` ==`{$row[PR_DISPLAY_NAME]}`\n";}
					return $row[PR_ENTRYID];
					break;
				}
			}
		}		
	}
	
	
	public function GetRecipientsFromFolder($folder_to_process){
		$PR_ENTRYID=$this->GetFolder_PR_ENTRYID($folder_to_process);
		if(empty($PR_ENTRYID)){return array();}
		return $this->parse_messages_rcpt($PR_ENTRYID);
		return array();
		
	}
	
	public function GetSendersFromFolder($folder_to_process){
		$PR_ENTRYID=$this->GetFolder_PR_ENTRYID($folder_to_process);
		if(empty($PR_ENTRYID)){
			if($GLOBALS["VERBOSE"]){echo "GetFolder_PR_ENTRYID($folder_to_process)  return empty...\n";}
			return array();}
		return $this->parse_messages_senders($PR_ENTRYID);
		return array();
				
		
	}
	
	private function parse_messages_senders($PR_ENTRYID){
		$folder = mapi_msgstore_openentry($this->default_store, $PR_ENTRYID);
		if(!$folder) {
			$error="Unable to open folder.\n";
			$this->error=$this->error=$error;
			return array();
		}
		$table = mapi_folder_getcontentstable($folder);
		
		if(!$table) {$error="Unable to open table.\n";
		$this->error=$this->error=$error;
		return array();
		}
		
		$result = mapi_table_sort( $table, array( PR_CLIENT_SUBMIT_TIME => TABLE_SORT_DESCEND ) );
		if(!$result) {
			$error="Unable to open table.\n";
			$this->error=$this->error=$error;
			return array();
		}	

		
		if($GLOBALS["VERBOSE"]){echo "parse_messages_senders -> LOOP\n";}
		
		while(1) {
			// query messages from folders content table
			$filters=array(PR_MESSAGE_SIZE, PR_CLIENT_SUBMIT_TIME, PR_MESSAGE_RECIPIENTS,PR_EMAIL_ADDRESS, PR_BODY, PR_HTML, PR_ENTRYID, PR_SUBJECT , PR_SMTP_ADDRESS);
			$filters=array(PR_SENT_REPRESENTING_EMAIL_ADDRESS);
			$rows = mapi_table_queryrows($table,$filters,0,150);
			if(count($rows) == 0){ break;}
		
			// we got the messages
			foreach($rows as $row) {
				$sender     =strtolower($row[PR_SENT_REPRESENTING_EMAIL_ADDRESS]);
				if(trim($sender)==null){continue;}
				if(!$this->ValidateMail($sender)){continue;}
				$array[$sender]=$sender;
			}
		}
		if($GLOBALS["VERBOSE"]){echo "parse_messages_senders -> LOOP END ". count($array)." entries...\n";}
		return $array;
		
	}
	
	private function getEmailAddressFromEntryID($entryid) {
			  $mailuser = mapi_ab_openentry($this->session, $entryid);
			if(!$mailuser){return false;}
			$abprops = mapi_getprops($mailuser, array(PR_SMTP_ADDRESS, PR_EMAIL_ADDRESS));
			if(isset($abprops[PR_SMTP_ADDRESS]))
				return $abprops[PR_SMTP_ADDRESS];
				else if(isset($abprops[PR_EMAIL_ADDRESS]))
				return $abprops[PR_EMAIL_ADDRESS];
				else
					return false;
			}	
	
	
	private function parse_messages_rcpt($PR_ENTRYID){
		$folder = mapi_msgstore_openentry($this->default_store, $PR_ENTRYID);
		if(!$folder) {
			$error="Unable to open folder.\n";
			$this->error=$this->error=$error;
			return array();
		}
		$table = mapi_folder_getcontentstable($folder);
		
		if(!$table) {$error="Unable to open table.\n";
			$this->error=$this->error=$error;
			return array(); 
		}
		
		$result = mapi_table_sort( $table, array( PR_CLIENT_SUBMIT_TIME => TABLE_SORT_DESCEND ) );
		if(!$result) {
			$error="Unable to open table.\n";
			$this->error=$this->error=$error;
			return array();
		}
		
		while(1) {
			// query messages from folders content table
			$filters=array(PR_MESSAGE_SIZE, PR_CLIENT_SUBMIT_TIME, PR_MESSAGE_RECIPIENTS,PR_EMAIL_ADDRESS, PR_BODY, PR_HTML, PR_ENTRYID, PR_SUBJECT , PR_SMTP_ADDRESS);
			$filters=array(PR_ENTRYID);
			$rows = mapi_table_queryrows($table,$filters,0,150);
			if(count($rows) == 0){ break;}
		
			// we got the messages
			foreach($rows as $row) {
				
				$eid     = $row[PR_ENTRYID];
				$eids[]=$eid;
			}
			
			
			
		}
			
		$array=array();
			while (list ($messageCount, $eid) = each ($eids) ){
				$message = mapi_msgstore_openentry($this->default_store, $eid);
				$recipTable = mapi_message_getrecipienttable ($message);
				$oldRecipients = mapi_table_queryallrows($recipTable, array(PR_ENTRYID, PR_DISPLAY_NAME, PR_EMAIL_ADDRESS, PR_RECIPIENT_ENTRYID, PR_RECIPIENT_TYPE, PR_SEND_INTERNET_ENCODING, PR_SEND_RICH_INFO, PR_RECIPIENT_DISPLAY_NAME, PR_ADDRTYPE, PR_DISPLAY_TYPE, PR_RECIPIENT_TRACKSTATUS, PR_RECIPIENT_FLAGS, PR_ROWID));
				$c=0;
				while (list ($key, $line) = each ($oldRecipients) ){
				$c++;
				if($line[PR_ADDRTYPE]<>"SMTP"){continue;}
				$line[PR_EMAIL_ADDRESS]=strtolower($line[PR_EMAIL_ADDRESS]);
				if(!$this->ValidateMail($line[PR_EMAIL_ADDRESS])){continue;}
				
				if($GLOBALS["VERBOSE"]){echo "array[{$line[PR_EMAIL_ADDRESS]}]\n";}
				$array[$line[PR_EMAIL_ADDRESS]]=$line[PR_EMAIL_ADDRESS];
	
			}
		}
			
		return $array;	
		
		
	}
	
	private function ValidateMail($emailAddress_str) {
		$emailAddress_str=trim(strtolower($emailAddress_str));
		$regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
		if (preg_match($regex, $emailAddress_str)) {return true;}
		return false;
	}	
	
	private function mapidefs(){
		define("ZARAFA_SERVICE_GUID"                     , makeguid("{3C253DCA-D227-443C-94FE-425FAB958C19}"));    // default store
		define("ZARAFA_STORE_PUBLIC_GUID"                , makeguid("{D47F4A09-D3BD-493C-B2FC-3C90BBCB48D4}"));    // public store
		define('RES_PROPERTY'                            , 4);
		define('RELOP_EQ'                                , 4);
		define('VALUE'                                   , 0);        // propval
		define('RELOP'                                   , 1);        // compare method
		define('ULPROPTAG'                               , 6);        // property
		define('MV_FLAG'                                 , 0x1000);
		define('PT_STRING8'                              , 30);    /* Null terminated 8-bit character string */
		define('PT_TSTRING'                              , PT_STRING8);
		define('PT_LONG'                                 ,  3);    /* Signed 32-bit value */
	
		define('PT_BINARY'                               , 258);   /* Uninterpreted (counted byte array) */
		define('PT_MV_LONG'                              , (MV_FLAG | PT_LONG));
		define('DEL_ASSOCIATED'                          , 0x00000008);
	
		define('PT_BOOLEAN'                              , 11);    /* 16-bit boolean (non-zero true) */
		define('PT_SYSTIME'                              , 64);    /* FILETIME 64-bit int w/ number of 100ns periods since Jan 1,1601 */
		define('PT_MV_STRING8'                           ,(MV_FLAG | PT_STRING8));
		define('PT_MV_BINARY'                            ,(MV_FLAG | PT_BINARY));
	
		define('PSETID_Address'                          , makeguid("{00062004-0000-0000-C000-000000000046}"));
		define('PSETID_Common'                           , makeguid("{00062008-0000-0000-C000-000000000046}"));
		define('PS_PUBLIC_STRINGS'                       , makeguid("{00020329-0000-0000-C000-000000000046}"));
		define('STORE_UNICODE_OK'                        ,0x00040000); // The message store supports properties containing Unicode characters.
	}
	
}