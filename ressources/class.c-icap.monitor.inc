<?php
include_once(dirname(__FILE__)."/class.influx.inc");
class c_icap_monitor{
	
	
	function c_icap_monitor(){
		if(!isset($GLOBALS["SQUID_CLAMAV_ISSUE"])){$GLOBALS["SQUID_CLAMAV_ISSUE"]=0;}
		if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
		
		
	}
	
	
	public function parse($buffer){
		
		if(strpos($buffer,"recomputing istag")>0){return true;}
		
		if(strpos($buffer,"Setting antivirus default engine: clamd")>0){
			squid_admin_mysql(1, "C-ICAP reloaded successfully with ClamAV daemon", "$buffer",__FILE__,__LINE__);
			return true;
		}
		
		if(preg_match("#C-ICAP::.*?Registry.*?virus_scan::engines.*?does not exist#",$buffer)){
			if(preg_match("#create it#", $buffer)){return true;}
			$file="/etc/artica-postfix/croned.1/virus_scan.engines.does.not.exist";
			if(IfFileTime($file,2)){
				squid_admin_mysql(0, "C-ICAP issue, Antivirus engine is not linked [action=restart ICAP/ClamAV]", "$buffer\nArtica will reconfigure the ClamAV daemon",__FILE__,__LINE__);
				events("virus_scan::engines does not exists -> operation!");
				@chmod("/var/run/clamav/clamav.sock", 0777);
				if(!is_file("/bin/restart-cicap-all.sh")){
					$AA=array();
					$AA[]="#!/bin/sh";
					$AA[]="/etc/init.d/clamav-daemon restart";
					$AA[]="/etc/init.d/c-icap restart\n";
					@file_put_contents("/bin/restart-cicap-all.sh", @implode("\n", $AA));
					@chmod("/bin/restart-cicap-all.sh", 0755);
				}
				shell_exec("{$GLOBALS["nohup"]} /bin/restart-cicap-all.sh >/dev/null 2>&1 &");
			}
			return true;
		}
		
		
		if(preg_match("#C-ICAP::.*?Antivirus engine is not available#",$buffer)){
			$file="/etc/artica-postfix/croned.1/Antivirus.engine.is.not.available";
			if(IfFileTime($file,2)){
				squid_admin_mysql(1, "C-ICAP issue, Antivirus engine is not available", "$buffer\nArtica will restart the ClamAV daemon",__FILE__,__LINE__);
				
				if(!is_file("/bin/restart-cicap-all.sh")){
					$AA=array();
					$AA[]="#!/bin/sh";
					$AA[]="/etc/init.d/clamav-daemon restart";
					$AA[]="/etc/init.d/c-icap restart\n";
					@file_put_contents("/bin/restart-cicap-all.sh", @implode("\n", $AA));
					@chmod("/bin/restart-cicap-all.sh", 0755);
				}
				shell_exec("{$GLOBALS["nohup"]} /bin/restart-cicap-all.sh >/dev/null 2>&1 &");
			}
			return true;
		}
		
		
		if(preg_match("#C-ICAP::.*?clamd_connect: Can not connect to clamd server#",$buffer)){
			$file="/etc/artica-postfix/croned.1/clamd_connect.Can.not.connect.to.clamd.server";
			if(IfFileTime($file,2)){
				squid_admin_mysql(1, "C-ICAP issue, clamd socket unavailable", "Artica will restart the ClamAV daemon",__FILE__,__LINE__);
				shell_exec("{$GLOBALS["nohup"]} /etc/init.d/clamav-daemon restart >/dev/null 2>&1 &");
			}
			return true;
		}
		
		
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
			if($Clienthostname==null){$Clienthostname=$local_ip;}	
			$public_ip=gethostbyname($www);
			$time=time();
			$q=new influx();
			$line="$time:::$user:::C-ICAP ClamAV:::Antivirus ClamAV:::$public_ip:::Security issue:::THREAT $virus DETECTED:::$Clienthostname:::$www:::$local_ip";
			$q->insert_ufdb($line);
			
			
		
			
			
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
			if($Clienthostname==null){$Clienthostname=$local_ip;}

			
			$public_ip=gethostbyname($www);
			$time=time();
			$q=new influx();
			$line="$time:::$user:::C-ICAP ClamAV:::Antivirus ClamAV:::$public_ip:::Security issue:::THREAT $virus DETECTED:::$Clienthostname:::$www:::$local_ip";
			$q->insert_ufdb($line);
				
			
			return true;
		}		
		
		events("C-ICAP: Not filtered $buffer");
		
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