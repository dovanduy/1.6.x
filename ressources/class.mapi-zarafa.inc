<?php
include_once("/usr/share/php/mapi/mapi.util.php");
include_once("/usr/share/php/mapi/mapidefs.php");
include_once("/usr/share/php/mapi/mapicode.php");
include_once("/usr/share/php/mapi/mapitags.php");
include_once("/usr/share/php/mapi/mapiguid.php");
include_once(dirname(__FILE__).'/class.templates.inc');
include_once(dirname(__FILE__).'/class.ini.inc');
include_once(dirname(__FILE__).'/class.os.system.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__).'/class.user.inc');

class mapizarafa{
	private $ZarafaServerListenIP=null;
	private $zarafaServer=null;
	private $session;
	private $storetable;
	private $default_store_entry_id;
	private $default_store;
	private $publicstore;
	public $error;
	
	
	public function mapizarafa(){
		$this->load();
		
	}
	
	private function load(){
		$sock=new sockets();
		$this->ZarafaServerListenIP=$sock->GET_INFO("ZarafaServerListenIP");
		if($this->ZarafaServerListenIP==null){$this->ZarafaServerListenIP="127.0.0.1";}
		$this->zarafaServer = "http://$this->ZarafaServerListenIP:236/zarafa";
	}
	
	
	public function Connect($uid){
		$ct=new user($uid);
		$this->session = mapi_logon_zarafa($uid, $ct->password, $this->zarafaServer);
		if(!$this->session) { 
			$error="Unable to open session $uid@$this->zarafaServer\n";
			$this->error=$error; return false;
		}
		
		$this->storetable = mapi_getmsgstorestable($this->session);
		if(!$this->storetable) {
			$error="Unable to open message stores table $uid@$this->zarafaServer\n";
			$this->error=$this->error=$error; 
			return false;
		}
		
		$storeslist = mapi_table_queryallrows($this->storetable, array(PR_ENTRYID, PR_MDB_PROVIDER));
		$this->publicstore = mapi_openmsgstore($this->session, $storeslist[1][PR_ENTRYID]);
		//$defaultstore = mapi_openmsgstore($session, $storeslist[1][PR_ENTRYID]);		
		
		$msgstores = mapi_table_queryallrows($this->storetable, array(PR_DEFAULT_STORE, PR_ENTRYID));
		foreach ($msgstores as $row) {
			if($row[PR_DEFAULT_STORE]) {
				$this->default_store_entry_id = $row[PR_ENTRYID];
		
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
	
}