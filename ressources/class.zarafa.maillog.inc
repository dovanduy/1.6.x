<?php


class zarafa_maillog_buffer{
	var $buffer=null;
	
	
	function zarafa_maillog_buffer($buffer){
		$this->buffer=$buffer;
		if(!isset($GLOBALS["maillog_tools"])){$GLOBALS["maillog_tools"]=new maillog_tools();}
		if(!isset($GLOBALS["CLASS_UNIX"])){if(class_exists("unix")){$GLOBALS["CLASS_UNIX"]=new unix();}}
		if(!isset($GLOBALS["NOHUP_PATH"])){$GLOBALS["NOHUP_PATH"]=$GLOBALS["CLASS_UNIX"]->find_program("nohup");}
		if(!isset($GLOBALS["NETSTAT_PATH"])){$GLOBALS["NETSTAT_PATH"]=$GLOBALS["CLASS_UNIX"]->find_program("netstat");}
		if(!isset($GLOBALS["PHP5_BIN"])){$GLOBALS["PHP5_BIN"]=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();}
		if(!isset($GLOBALS["ZARAFA_ADMIN"])){$GLOBALS["PHP5_BIN"]=$GLOBALS["CLASS_UNIX"]->find_program("zarafa-admin");}
	}
	
	public function parse(){
		$buffer=$this->buffer;
		
		if(preg_match("#zarafa-server.+?SQL Failed: Can't create table '\./zarafa/(.+?)\.frm'#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-server.tablefailed".md5($re[1]);
		$timefile=$this->file_time_min($file);
		if($timefile>10){
			$this->email_events("Zarafa server SQL issue unable to create [{$re[1]}] table",
			"zarafa-server claim \n$buffer\nThere is an SQL issue\nplease Check Artica Technology support service.","mailbox");
			@file_put_contents($file,"#");
			}else{$this->events("Zarafa-server SQL issue {$re[1]} {$timefile}Mn/5Mn");}
		return true;	
	}
	
	if(preg_match("#zarafa-dagent\[.+?Requested e-mail address\s+'(.+?)' does not resolve a user#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-dagent-.".md5($re[1]);
		$timefile=$this->file_time_min($file);
		if($timefile>10){
			$this->email_events("Zarafa server mailbox issue for {$re[1]}",
			"zarafa-server claim \n$buffer\nCheck if the mailbox of {$re[1]} exists and it's organization is correctly enabled as a Zarafa organization ","mailbox");
			@file_put_contents($file,"#");
			}else{$this->events("Zarafa server mailbox issue for {$re[1]} {$timefile}Mn/5Mn");}
		return true;
		
	}
	
	if(preg_match("#zarafa-server\[.*?Unable to connect to database: Access denied for user#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-server.mysqluserfailed";
		$timefile=$this->file_time_min($file);
		if($timefile>10){
			$this->email_events("Zarafa server SQL issue Access denied",
			"zarafa-server claim \n$buffer\nThere is an SQL issue\nArtica will try to reconfigure the Zarafa server config","mailbox");
			$cmd="{$GLOBALS["NOHUP_PATH"]} /usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure --start-daemon >/dev/null 2>&1 &";
			$this->events("$cmd");
			shell_exec_maillog($cmd);			
			@file_put_contents($file,"#");
			}
			else{$this->events("Zarafa-server SQL issue Access denied {$timefile}Mn/5Mn");}
		return true;
	}

	if(preg_match("#zarafa-server\[.+?Can't update the database#i", $buffer)){
		$file="/etc/artica-postfix/croned.1/zarafa-server.table.".md5("Can't update the database");
		$timefile=$this->file_time_min($file);
		if($timefile>10){
			$this->email_events("Zarafa server SQL issue database definitively crashed",
			"zarafa-server claim \n$buffer\nThere is an SQL issue\nArtica will delete the entire Zarafa Database and rebuild a new one...","mailbox");
			@file_put_contents($file,"#");			
			shell_exec("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.zarafa.build.stores.php --remove-database >/dev/null 2>&1 &");
		}
		return true;
	}
	
	if(preg_match("#zarafa-server\[.+?Unable to find company id for object\s+(.+)#", $buffer)){
		$file="/etc/artica-postfix/croned.1/zarafa-server.table.".md5("Unable to find company id for object{$re[1]}");
		$timefile=$this->file_time_min($file);
		if($timefile>10){
			$this->email_events("Zarafa store issue for member {$re[1]}",
			"zarafa-server claim \n$buffer\nThere is an SQL issue\nArtica will run \"{$GLOBALS["ZARAFA_ADMIN"]} -l\"
			in order to force zarafa server to parse stores","mailbox");
			@file_put_contents($file,"#");			
			shell_exec("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["ZARAFA_ADMIN"]} -l >/dev/null 2>&1 &");
		}
		return true;
	}
	
	
	if(preg_match("#zarafa-server\[.+?Create table:\s+(.+)#i", $buffer)){
		$this->email_events("Zarafa server SQL creating new table zarafa.{$re[1]}",$buffer,"mailbox");
		return true;
	}
	
	if(preg_match("#zarafa-server\[.+?Finished create store.*?storeid=(.+?),#i", $buffer)){
		$this->email_events("Zarafa new store created ID:{$re[1]}",$buffer,"mailbox");
		return true;
	}
	
	
	if(preg_match("#[0-9a-z]+: SQL Failed: Table 'zarafa\.(.+?)' doesn't exist#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-server.table.{$re[1]}.failed.".md5($re[1]);
		$timefile=$this->file_time_min($file);
		if($timefile>10){
			$this->email_events("Zarafa server SQL issue table [{$re[1]}] doesn't exist",
			"zarafa-server claim \n$buffer\nThere is an SQL issue\nYou should delete the zarafa database trough Artica tools console in order to rebuild correctly the database.","mailbox");
			@file_put_contents($file,"#");
			}else{$this->events("Zarafa-server SQL issue {$re[1]} {$timefile}Mn/5Mn");}
		return true;
	}
	 
	
	
	if(preg_match("#zarafa-dagent\[.+?Unable to bind to port\s+([0-9]+)#", $buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-dagent.Unable.to.bind.to.port.{$re[1]}";
		$timefile=$this->file_time_min($file);
		if($timefile>5){
			exec("{$GLOBALS["NETSTAT_PATH"]} -tlnp 2>&1",$results);
			$this->email_events("Zarafa Unable to bind to port {$re[1]}",
			"zarafa-dagent claim \n$buffer\nThere is an listen port issue\nplease Check this report in order to see if there is an another process that listen this port\n".@implode("\n", $results),"mailbox");
			@file_put_contents($file,"#");
			}else{$this->events("zarafa-dagent Port issue {$re[1]} {$timefile}Mn/5Mn");}
		return true;
	}
	
	
	if(preg_match("#zarafa-server.+?Error while connecting to indexer#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-server.Error.while.connecting.to.indexer";
		$timefile=$this->file_time_min($file);
		if($timefile>5){
			shell_exec("{$GLOBALS["NOHUP_PATH"]} /etc/init.d/zarafa-search start >/dev/null 2>&1 &");
			$this->email_events("Zarafa Error while connecting to indexer",
			"zarafa-server claim \n$buffer\nthe zarafa indexer service was started","mailbox");
			@file_put_contents($file,"#");
			}else{$this->events("zarafa-indexer service issue {$timefile}Mn/5Mn");}
		return true;	
	}
	
	
	if(preg_match("#zarafa-server.+?SQL Failed:(.+)#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-server.".md5($re[1]);
		$timefile=$this->file_time_min($file);
		if($timefile>10){
			$this->email_events("Zarafa server SQL issue",
			"zarafa-server claim \n$buffer\nThere is an SQL issue\nplease Check Artica Technology support service.","mailbox");
			@file_put_contents($file,"#");
			}else{$this->events("Zarafa-server SQL issue {$re[1]} {$timefile}Mn/5Mn");}
		return true;	
	}		
		
	$this->events("Not scanned \"$buffer\"");	
	
	}
	private function events($text,$line){
		$filename=basename(__FILE__);
		$logFile="/var/log/artica-postfix/postfix-logger.debug";
		if(!isset($GLOBALS["CLASS_UNIX"])){return;}
		$GLOBALS["CLASS_UNIX"]->events("$filename $text (line $line)",$logFile);		
	}

   private function email_events($subject,$text,$context){
	 if(!isset($GLOBALS["CLASS_UNIX"])){return;}
	 $GLOBALS["CLASS_UNIX"]->send_email_events($subject,$text,$context);
	}	
	
	private function file_time_min($path){
		if(!is_file($path)){return 100000;}
		 $last_modified = filemtime($path);
		 $data1 = $last_modified;
		 $data2 = time();
		$difference = ($data2 - $data1); 	 
		return round($difference/60);	 
	}	
		
		
}