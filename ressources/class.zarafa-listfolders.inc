<?php
class mapi_listfolder{
	private $supported_classes = array ("IPF.Note" => "SYNC_FOLDER_TYPE_USER_MAIL","IPF.Task" => "SYNC_FOLDER_TYPE_USER_TASK","IPF.Appointment" => "SYNC_FOLDER_TYPE_USER_APPOINTMENT","IPF.Contact" => "SYNC_FOLDER_TYPE_USER_CONTACT","IPF.StickyNote" => "SYNC_FOLDER_TYPE_USER_NOTE");
	public $FOLDERS=array();
	public $error;
	public $session;
	private $public_store;
	public function Listfolders($user, $pass,$mapi,$Public=false){
		
		$zarafaAdmin = $this->listfolders_zarafa_admin_setup($mapi, $user, $pass);
		return $this->listfolders_getlist($this->public_store, $this->session, $user,$Public);
	}



	private function listfolders_zarafa_admin_setup ($mapi, $user, $pass,$Public=false) {
	    $this->session = @mapi_logon_zarafa($user, $pass, $mapi);
	
	    if (!$this->session) {
	        $this->error="User $mapi@$user could not login. Errorcode: 0x". sprintf("%x", mapi_last_hresult()) . "\n";
	        if($GLOBALS["VERBOSE"]){echo $this->error."\n";}
	        return false;
	    }
	
	    $stores = @mapi_getmsgstorestable($this->session);
	    $storeslist = @mapi_table_queryallrows($stores);
		$this->public_store = @mapi_openmsgstore($this->session, $storeslist[1][PR_ENTRYID]);
		return true;
	}
	
	private function listfolders_getlist_public($adminStore, $session, $user) {
		$folder = @mapi_msgstore_openentry($this->public_store);
		$h_table = @mapi_folder_gethierarchytable($folder, CONVENIENT_DEPTH);
		$subfolders = @mapi_table_queryallrows($h_table, array(PR_ENTRYID, PR_DISPLAY_NAME, PR_CONTAINER_CLASS, PR_SOURCE_KEY));
		foreach($subfolders as $folder) {
			if($GLOBALS["VERBOSE"]){echo "PR_CONTAINER_CLASS: {$folder[PR_CONTAINER_CLASS]} PR_DISPLAY_NAME: {$folder[PR_DISPLAY_NAME]}<br>\n";}
			if (isset($folder[PR_CONTAINER_CLASS]) && array_key_exists($folder[PR_CONTAINER_CLASS], $this->supported_classes)) {
			if($GLOBALS["VERBOSE"]){echo "{$folder[PR_DISPLAY_NAME]} - {$folder[PR_SOURCE_KEY]}<br\n";}		
			$this->FOLDERS[$folder[PR_DISPLAY_NAME]]=array("ID"=>bin2hex($folder[PR_SOURCE_KEY]),"TYPE"=> $this->supported_classes[$folder[PR_CONTAINER_CLASS]]);}
		}
		
		return true;		
		
	}


	private function listfolders_getlist ($adminStore, $session, $user,$Public=false) {
	   	if($Public){return $this->listfolders_getlist_public();}
	    if (strtoupper($user) == 'SYSTEM') {
	        // Find the public store store
	        $storestables = @mapi_getmsgstorestable($session);
	        $result = @mapi_last_hresult();
	
	        if ($result == NOERROR){
	            $rows = @mapi_table_queryallrows($storestables, array(PR_ENTRYID, PR_MDB_PROVIDER));
	
	            foreach($rows as $row) {
	                if (isset($row[PR_MDB_PROVIDER]) && $row[PR_MDB_PROVIDER] == ZARAFA_STORE_PUBLIC_GUID) {
	                    if (!isset($row[PR_ENTRYID])) {
	                        $this->error="Public folder are not available.";
	                        if($GLOBALS["VERBOSE"]){echo $this->error."\n";}
	                        return false;
	                    }
	                    $entryid = $row[PR_ENTRYID];
	                    break;
	                }
	            }
	        }
	    }
	    else
	    $entryid = @mapi_msgstore_createentryid($adminStore, $user);
	    $userStore = @mapi_openmsgstore($session, $entryid);
	    $hresult = mapi_last_hresult();
	
	   
	    if($hresult != NOERROR) {
	        $this->error="Could not open store for '$user'";
	        if($GLOBALS["VERBOSE"]){echo $this->error."\n";}
	        return false;
	    }
	
	    $folder = @mapi_msgstore_openentry($userStore);
	    $h_table = @mapi_folder_gethierarchytable($folder, CONVENIENT_DEPTH);
	    $subfolders = @mapi_table_queryallrows($h_table, array(PR_ENTRYID, PR_DISPLAY_NAME, PR_CONTAINER_CLASS, PR_SOURCE_KEY));
	
	   
	    foreach($subfolders as $folder) {
	    	
	    	if($GLOBALS["VERBOSE"]){
	    		echo "PR_CONTAINER_CLASS: {$folder[PR_CONTAINER_CLASS]} PR_DISPLAY_NAME: {$folder[PR_DISPLAY_NAME]}<br>\n";
	    		
	    	}
	    	
	        if (isset($folder[PR_CONTAINER_CLASS]) && array_key_exists($folder[PR_CONTAINER_CLASS], $this->supported_classes)) {
	            if($GLOBALS["VERBOSE"]){echo "{$folder[PR_DISPLAY_NAME]} - {$folder[PR_SOURCE_KEY]}<br\n";}
	        	
	        	$this->FOLDERS[$folder[PR_DISPLAY_NAME]]=array("ID"=>bin2hex($folder[PR_SOURCE_KEY]),
	            "TYPE"=> $this->supported_classes[$folder[PR_CONTAINER_CLASS]]);		
	           
	        }
	    }
	
	
	    return true;
	}
	
	

}
?>