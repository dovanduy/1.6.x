<?php

class c_icap_monitor{
	
	
	function c_icap_monitor(){
		if(!isset($GLOBALS["SQUID_CLAMAV_ISSUE"])){$GLOBALS["SQUID_CLAMAV_ISSUE"]=0;}
		if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
		
		
	}
	
	
	public function parse($buffer){
		
		
		
		if(preg_match("#C-ICAP:.*?[0-9]+\/[0-9]+,\s+Cannot write to file: No space left on device#",$buffer,$re)){
			$file="/etc/artica-postfix/croned.1/cicap.connections.No.space.left.on.device";
			if(IfFileTime($file,2)){
				squid_admin_mysql(0, "C-ICAP issue, no space left", "Artica will remove the content of the C-ICAP temporary directory, see next event",__FILE__,__LINE__);
				shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.c-icap.php --purge --force --all >/dev/null 2>&1 &");
			}
			return true;
		}
		
		
		if(preg_match("#C-ICAP:.*?main proc, clamd_connect: Can not connect to clamd server on (.*?)#",$buffer,$re)){
			$file="/etc/artica-postfix/croned.1/cicap.clamd_connect.clamd.server";
			if(IfFileTime($file,2)){
				squid_admin_mysql(0, "C-ICAP issue, no space left", "Artica will remove the content of the C-ICAP temporary directory, see next event",__FILE__,__LINE__);
				shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.c-icap.php --purge --force --all >/dev/null 2>&1 &");
			}
			return true;
		}
		
		
		if(preg_match("#C-ICAP.*?clamd_scan.*?Error scanning file.*?create new file ERROR#",$buffer,$re)){
			$file="/etc/artica-postfix/croned.1/cicap.clamav.issue";
			$GLOBALS["SQUID_CLAMAV_ISSUE"]++;
			if(IfFileTime($file,1)){
				if($GLOBALS["SQUID_CLAMAV_ISSUE"]<4){
					squid_admin_mysql(0, "C-ICAP issue, Error scanning file {$GLOBALS["SQUID_CLAMAV_ISSUE"]}/3", "After 3 events, the C-ICAP daemon will be turned to off",__FILE__,__LINE__);
					return true;
				}
		
				squid_admin_mysql(0, "C-ICAP issue, Error scanning file {$GLOBALS["SQUID_CLAMAV_ISSUE"]}/3", "The C-ICAP daemon will be turned to off",__FILE__,__LINE__);
				@file_put_contents("/etc/artica-postfix/settings/Daemons/CicapEnabled",0);
				shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.squid.php --build --force --syslog >/dev/null 2>&1 &");
				shell_exec("{$GLOBALS["nohup"]} /etc/init.d/artica-status reload >/dev/null 2>&1");
			}
			return true;
		}
		
		if(preg_match("#kernel:.*?c-icap.*?segfault.*?error#",$buffer,$re)){
			squid_admin_mysql(0, "C-ICAP crash, kernel segfault", "$buffer\C-ICAP service will be restarted",__FILE__,__LINE__);
			shell_exec("{$GLOBALS["nohup"]} /etc/init.d/c-icap restart --syslog >/dev/null 2>&1 &");
			return true;
		}	
		
		
		if(preg_match("#C-ICAP.*?Unable to find specified template#i", $buffer)){
			$file="/etc/artica-postfix/croned.1/cicap_templates";
			events("Not template for C-ICAP...");
			if(IfFileTime($file,10)){
				$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["LOCATE_PHP5_BIN"]} /usr/share/artica-postfix/exec.c-icap.php --template >/dev/null 2>&1 &");
				events("$cmd");
				WriteFileCache($file);
			}
			
			return true;
		
		}		

		
		if(preg_match("#C-ICAP.*?general.*?VIRUS DETECTED: (.+?)\s+, http client ip: (.+?),\s+http user: (.*?), http url: (.+)#",$buffer,$re)){
			$user=trim($re[3]);
			if($user=="-"){$user=null;}
			$local_ip=$re[2];
			$virus=$re[1];
			$uri=$re[4];
			$uri=str_replace("#012", "", $uri);
			$uri=trim($uri);
			$array=parse_url($uri);
			$www=$array["host"];
			if(strpos($www, ":")>0){$t=explode(":", $www);$www=$t[0];}
			if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
			if(strpos($www,"/")>0){$tb=explode("/",$www);$www=$tb[0];}
			$MAC=$GLOBALS["CLASS_UNIX"]->IpToMac($local_ip);
			$Clienthostname=$GLOBALS["CLASS_UNIX"]->IpToHostname($local_ip);
		
			$array["uid"]=$user;
			$array["uri"]=$uri;
			$array["MAC"]=$MAC;
			$array["TIME"]=time();
			$array["category"]="C-ICAP ClamAV";
			$array["rulename"]="Antivirus ClamAV";
			$array["public_ip"]=gethostbyname($www);
			$array["blocktype"]="Security issue";
			$array["why"]="THREAT $virus DETECTED";
			$array["hostname"]=$Clienthostname;
			$array["website"]=$www;
			$array["client"]=$local_ip;
			$serialize=serialize($array);
			$md5=md5($serialize);
			if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard-blocks")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard-blocks");}
			@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard-blocks/$md5.sql",$serialize);
			$this->eventsAuth("[CLAMAV]: blocked THREAT $virus DETECTED IN $uri");
			return true;
		}
		
		if(preg_match("#C-ICAP.*?VIRUS DETECTED:\s+(.+?)\s+,\s+.*?ip:\s+(.+?),\s+.*?user:\s+(.+?),\s+.*?url:\s+(.+)#",$buffer,$re)){
			$user=trim($re[3]);
			if($user=="-"){$user=null;}
			$local_ip=$re[2];
			$virus=$re[1];
			$uri=$re[4];
			$uri=str_replace("#012", "", $uri);
			$uri=trim($uri);
			$array=parse_url($uri);
			$www=$array["host"];
			if(strpos($www, ":")>0){$t=explode(":", $www);$www=$t[0];}
			if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
			if(strpos($www,"/")>0){$tb=explode("/",$www);$www=$tb[0];}
			$MAC=$GLOBALS["CLASS_UNIX"]->IpToMac($local_ip);
			$Clienthostname=$GLOBALS["CLASS_UNIX"]->IpToHostname($local_ip);
		
			$array["uid"]=$user;
			$array["uri"]=$uri;
			$array["MAC"]=$MAC;
			$array["TIME"]=time();
			$array["category"]="C-ICAP ClamAV";
			$array["rulename"]="Antivirus ClamAV";
			$array["public_ip"]=gethostbyname($www);
			$array["blocktype"]="Security issue";
			$array["why"]="THREAT $virus DETECTED";
			$array["hostname"]=$Clienthostname;
			$array["website"]=$www;
			$array["client"]=$local_ip;
			$serialize=serialize($array);
			$md5=md5($serialize);
			if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard-blocks")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard-blocks");}
			@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/ufdbguard-blocks/$md5.sql",$serialize);
			$this->eventsAuth("[CLAMAV]: blocked THREAT $virus DETECTED IN $uri");
			return true;
		}		
		
		
		
	return false;}
	
	private function eventsAuth($text){
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/auth-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
		@fclose($f);
	}	
	
	
}