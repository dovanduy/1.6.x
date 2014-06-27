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
		if(!isset($GLOBALS["ZARAFA_ADMIN"])){$GLOBALS["ZARAFA_ADMIN"]=$GLOBALS["CLASS_UNIX"]->find_program("zarafa-admin");}
		if(!isset($GLOBALS["myisamchk"])){$GLOBALS["myisamchk"]=$GLOBALS["CLASS_UNIX"]->find_program("myisamchk");}
		if(!isset($GLOBALS["MYSQL_DATA_DIR"])){$GLOBALS["MYSQL_DATA_DIR"]=$GLOBALS["CLASS_UNIX"]->MYSQL_DATA_DIR();}
		if(!isset($GLOBALS["FUSER_BIN"])){$GLOBALS["FUSER_BIN"]=$GLOBALS["CLASS_UNIX"]->find_program("fuser");}
		
		
		
		$unix=new unix();
		
	}
	
	
	
	public function parse(){
		$buffer=$this->buffer;
		
		if(preg_match('#IMAP Login from\s+(.*?)\s+for user\s+(.+)#',$buffer,$re)){
			$service="imap";
			$server_ip=trim($re[1]);
			$server=null;
			$user=trim($re[2]);
			$this->conx($service,$server,$server_ip,$user);
			return true;
		}
		
	if(preg_match("#Starting zarafa-(.*?)\s+version\s+(.+?)\s+#", $buffer,$re)){
		system_admin_events("Zarafa process - {$re[1]} version {$re[2]} was started",__FUNCTION__,__FILE__,__LINE__,"maiboxes");
		return;
		
	}
	
	if(preg_match("#zarafa-server.*?Startup succeeded on pid ([0-9]+)#", $buffer,$re)){
		system_admin_events("Zarafa Server successfully started PID - {$re[1]}",__FUNCTION__,__FILE__,__LINE__,"mailboxes");
		return;
	}
	
	if(preg_match("#zarafa-server.*?SQL \[([0-9]+)\] info: Try to reconnect#", $buffer,$re)){
		$errorNum=$re[1];
		$file="/etc/artica-postfix/croned.1/zarafa-server.error.$errorNum";
		$timefile=$this->file_time_min($file);
		if($timefile>5){
			system_admin_events("Zarafa Server MySQL Error ID $errorNum",__FUNCTION__,__FILE__,__LINE__);
		}
		return;
		
	}
		
	if(preg_match("#zarafa-server\[.+?Unable to start server on port\s+([0-9]+): Address already in use#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-server.Unable.to.start.server.on.port.{$re[1]}";
		$timefile=$this->file_time_min($file);
		if($timefile>5){
			exec("{$GLOBALS["FUSER_BIN"]} {$re[1]}/tcp 2>&1",$resultsCMD);
			while (list ($index, $val) = each ($resultsCMD) ){
				if(preg_match("#tcp:\s+(.+)#", $val,$ri)){
					$PIDLINE[]="Listen on {$re[1]}: PID: {$ri[1]}: ".@file_get_contents("/proc/{$ri[1]}/cmdline");;
				}
			}
			$resultsCMD=array();
			$this->email_events("Zarafa Unable to start server on port {$re[1]}","zarafa-server claim \n$buffer\n".@implode("\n", $PIDLINE),"mailbox");
			$PIDLINE=array();
			@unlink($file);@file_put_contents($file,time());
		}
		return true;
	}
	
	if(preg_match("#zarafa-dagent.*?Unable to login for user\s+(.+?), error code: 0x8004010F#", $buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-0x8004010F.Unable.to.login.{$re[1]}";
		$timefile=file_time_min($file);
		if($timefile>5){
			$this->events("Create Store for {$re[1]}", __LINE__);
			$this->email_events("Zarafa Agent issue for {$re[1]} -> Create store",
			"zarafa-dagent claim \n$buffer\nArtica send a store creation for this user","mailbox");
			@unlink($file);@file_put_contents($file,"#");
			$this->events("Create Store {$GLOBALS["NOHUP_PATH"]} {$GLOBALS["ZARAFA_ADMIN"]} --create-store \"{$re[1]}\"", __LINE__);
			shell_exec("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["ZARAFA_ADMIN"]} --create-store \"{$re[1]}\" >/dev/null 2>&1 &");
		}
		return true;	
	}
	
	
	if(preg_match("#zarafa-gateway.+?Unable to negotiate SSL connection#", $buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-gateway.Unable.to.negotiate.SSL.connection";
		$timefile=file_time_min($file);
		if($timefile>10){
			$this->email_events("Zarafa IMAP/POP3 SSL issue",
			"zarafa-gateway claim \n$buffer\nThere is an SSL issue\nplease Check Artica Technology support service.","mailbox");
			@unlink($file);@file_put_contents($file,"#");
		}else{$this->events("Zarafa IMAP/POP3 Unable to negotiate SSL connection {$timefile}Mn/5Mn");}
		return true;
	}	
		
	if(preg_match("#zarafa-server.*?Error while querying indexer on.*?0x80040401#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-server.querying.indexer.failed.0x80040401";
		$timefile=$this->file_time_min($file);
		if($timefile>5){
			$this->email_events("Zarafa search issue for querying indexer",
			"zarafa-server claim \n$buffer\nZarafa search service will be restarted","mailbox");
			@unlink($file);@file_put_contents($file,time());
			shell_exec("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.zarafa-search.php --restart byWatchdog >/dev/null 2>&1 &");
		}else{$this->events("Zarafa search issue issue 0x80040401 {$timefile}Mn/5Mn");}
		return true;
	}
		
	if(preg_match("#zarafa-server.+?SQL Failed: Can't create table '\./zarafa/(.+?)\.frm'#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-server.tablefailed".md5($re[1]);
		$timefile=$this->file_time_min($file);
		if($timefile>10){
			$this->email_events("Zarafa server SQL issue unable to create [{$re[1]}] table",
			"zarafa-server claim \n$buffer\nThere is an SQL issue\nplease Check Artica Technology support service.","mailbox");
			@unlink($file);@file_put_contents($file,time());
			}else{$this->events("Zarafa-server SQL issue {$re[1]} {$timefile}Mn/5Mn");}
		return true;	
	}
	
	if(preg_match("#zarafa-server.*?SQL.*?Table\s+'\.(.+?)\/(.+?)'\s+is marked as crashed and should#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-server.table.crash".md5($re[1].$re[2]);
		$timefile=$this->file_time_min($file);
		if($timefile>10){
			$this->email_events("Zarafa server SQL issue on [{$re[2]}] table in {$re[1]} database",
			"zarafa-server claim \n$buffer\nThere is an SQL issue\nArtica will try to repair this table","mailbox");
			@file_put_contents($file,time());
			shell_exec("{$GLOBALS["myisamchk"]} --safe-recover {$GLOBALS["MYSQL_DATA_DIR"]}/{$re[1]}/{$re[2]}.MYI 2>&1 &");
		}else{$this->events("Zarafa-server SQL issue {$re[2]} {$timefile}Mn/5Mn");}
		return true;
	}
	

	
	if(preg_match("#SQL.*?Failed: Cannot load from mysql\.proc\. The table is probably corrupted#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-server.mysql.proc.crash";
		$timefile=$this->file_time_min($file);
		if($timefile>10){
			$this->email_events("Zarafa server SQL issue on [mysql.proc] table",
			"zarafa-server claim \n$buffer\nThere is an MySQL issue\nArtica will try to repair this table","mailbox");
			@unlink($file);	@file_put_contents($file,time());
			shell_exec("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.mysql.build.php --mysql-upgrade >/dev/null 2>&1 &");
		}else{$this->events("Zarafa-server SQL issue but do nothing... {$timefile}Mn/5Mn");}
		return true;
	}	

	
	if(preg_match("#zarafa-licensed.*?You are over your user limit#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-server.licenseover";
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/zarafa.notify.licenseover", $buffer);
		@chmod("/usr/share/artica-postfix/ressources/logs/zarafa.notify.licenseover", 0777);
		$timefile=$this->file_time_min($file);
		if($timefile>10){
			$this->email_events("Zarafa server license issue",
			"zarafa-server claim \n$buffer\nPlease check your license status","mailbox");
			@unlink($file);	@file_put_contents($file,time());
			}else{$this->events("Zarafa server license issue \"You are over your user limit\" {$timefile}Mn/5Mn");}
		return true;
	}
	
	
	
	if(preg_match("#zarafa-dagent\[.+?Requested e-mail address\s+'(.+?)' does not resolve a user#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-dagent-.".md5($re[1]);
		$timefile=$this->file_time_min($file);
		if($timefile>10){
			$this->email_events("Zarafa server mailbox issue for {$re[1]}",
			"zarafa-server claim \n$buffer\nCheck if the mailbox of {$re[1]} exists and it's organization is correctly enabled as a Zarafa organization ","mailbox");
			@unlink($file);@file_put_contents($file,time());
			}else{$this->events("Zarafa server mailbox issue for {$re[1]} {$timefile}Mn/5Mn");}
		return true;
		
	}
	
	
	if(preg_match("#zarafa-server\[.*?Cannot instantiate user plugin: Not a valid configuration file#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-server-user-plugin";
		$timefile=$this->file_time_min($file);
		if($timefile>10){
			$this->email_events("Zarafa server User plugin issue",
			"zarafa-server claim \n$buffer\nCheck Artica will reconfigure the zarafa service","mailbox");
			$cmd="{$GLOBALS["NOHUP_PATH"]} /usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure --start-daemon >/dev/null 2>&1 &";
			$this->events($cmd);
			shell_exec($cmd);			
			@unlink($file);@file_put_contents($file,time());
			}else{$this->events("Zarafa server User plugin issue {$timefile}Mn/5Mn");}
		return true;
		
	}
	
	if(preg_match("#zarafa-server\[.*?Unable to start server on port\s+(.*?):\s+Cannot assign requested address#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/Cannot-assign-requested-address";
		$timefile=$this->file_time_min($file);
		if($timefile>10){
			$this->email_events("Zarafa server wrong listen address",
			"zarafa-server claim \n$buffer\nThe service will back to listen 127.0.0.1 address.","mailbox");
			@file_put_contents("/etc/artica-postfix/settings/Daemons/ZarafaServerListenIP", "127.0.0.1");
			$cmd="{$GLOBALS["NOHUP_PATH"]} /usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure --start-daemon >/dev/null 2>&1 &";
			$this->events($cmd);
			shell_exec($cmd);
			@unlink($file);@file_put_contents($file,time());
			}else{$this->events("Cannot assign requested address {$timefile}Mn/5Mn");}
		return true;
		
	}
	
	if(preg_match("#Config error: option.*?cannot be overridden.*?different group#", $buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-propmap.error-1";
		$timefile=$this->file_time_min($file);
		if($timefile>5){
			$this->email_events("Zarafa server Fatal propmap issue !!!",
			"zarafa-server claim \n$buffer\nArtica will, move the ldap.propmap.cfg to /etc/zarafa/ldap.propmap.ORG","mailbox");
			@file_put_contents($file,time());
			if(!is_file("/etc/zarafa/ldap.propmap.ORG")){@copy("/etc/zarafa/ldap.propmap.cfg", "/etc/zarafa/ldap.propmap.ORG");}
			@file_put_contents("/etc/zarafa/ldap.propmap.cfg", "#$buffer\n#error, this empty file replace ldap.propmap.ORG to fix the issue");
			}else{$this->events("Zarafa server Fatal propmap issue {$timefile}Mn/5Mn");}
		return true;		
	}
	
	if(preg_match("#zarafa-server\[.*?Config error: option '.*?'\s+cannot be overridden \(different group\)\!#", $buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-propmap.error";
		$timefile=$this->file_time_min($file);
		if($timefile>5){
			$this->email_events("Zarafa server Fatal propmap issue !!!",
			"zarafa-server claim \n$buffer\nArtica will, move the ldap.propmap.cfg to /etc/zarafa/ldap.propmap.ORG","mailbox");
			@file_put_contents($file,time());
			if(!is_file("/etc/zarafa/ldap.propmap.ORG")){@copy("/etc/zarafa/ldap.propmap.cfg", "/etc/zarafa/ldap.propmap.ORG");}
			@file_put_contents("/etc/zarafa/ldap.propmap.cfg", "#$buffer\n#error, this empty file replace ldap.propmap.ORG to fix the issue");
			}else{$this->events("Zarafa server Fatal propmap issue {$timefile}Mn/5Mn");}
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
			@unlink($file);@file_put_contents($file,time());
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
			@unlink($file);@file_put_contents($file,time());			
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
			@unlink($file);@file_put_contents($file,time());			
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
			@file_put_contents("/usr/share/artica-postfix/ressources/logs/zarafa.notify.MySQLIssue", $buffer);
			@chmod( "/usr/share/artica-postfix/ressources/logs/zarafa.notify.MySQLIssue",0777);
			$this->email_events("Zarafa server SQL issue table [{$re[1]}] doesn't exist",
			"zarafa-server claim \n$buffer\nThere is an SQL issue\nYou should delete the zarafa database trough Artica tools console in order to rebuild correctly the database.","mailbox");
			@unlink($file);@file_put_contents($file,time());
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
			@unlink($file);@file_put_contents($file,time());
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
			@unlink($file);@file_put_contents($file,time());
			}else{$this->events("zarafa-indexer service issue {$timefile}Mn/5Mn");}
		return true;	
	}
	
	
	if(preg_match("#zarafa-server.+?SQL Failed:(.+)#",$buffer,$re)){
		$file="/etc/artica-postfix/croned.1/zarafa-server.".md5($re[1]);
		$timefile=$this->file_time_min($file);
		if($timefile>10){
			$this->email_events("Zarafa server SQL issue",
			"zarafa-server claim \n$buffer\nThere is an SQL issue\nplease Check Artica Technology support service.","mailbox");
			@unlink($file);@file_put_contents($file,time());
			}else{$this->events("Zarafa-server SQL issue {$re[1]} {$timefile}Mn/5Mn");}
		return true;	
	}		
		
	$this->events("Not scanned \"$buffer\"",__LINE__);	
	
	}
	
	private function conx($service,$hostname,$ip,$user){
		$time=time();
		if(!isset($GLOBALS["CLASS_POSTFIX_SQL"])){$GLOBALS["CLASS_POSTFIX_SQL"]=new mysql_postfix_builder();}
		
		$fam=new familysite();
		if($hostname==null){$hostname=$fam->GetComputerName($ip);}
		$curdate=date("YmdH");
		$tablename="{$curdate}_hcnx";
		$zDate=date("Y-m-d H:i:s");
		$GLOBALS["CLASS_POSTFIX_SQL"]->postfix_buildhour_connections();
		$domain=$fam->GetFamilySites($hostname);
		$zmd5=md5("$time$hostname$ip");
		$tablename="{$curdate}_hmbx";
		$this->events("$service-connection: $user / $hostname / $domain - > $ip");
	
		$sql="INSERT IGNORE INTO `$tablename` (`zmd5`,`zDate`,`mbx_service`,`hostname`,`ipaddr`,`uid`,`imap_server`,`domain`)
		VALUES('$zmd5','$zDate','$service','$hostname','$ip','$user','{$GLOBALS["MYHOSTNAME"]}','$domain')";
		$GLOBALS["CLASS_POSTFIX_SQL"]->QUERY_SQL($sql);
	}
	
	private function events($text,$line){
		$filename=basename(__FILE__);
		$logFile="/var/log/artica-postfix/zarafa-logger.debug";
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