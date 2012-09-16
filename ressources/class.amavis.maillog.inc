<?php
include_once(dirname(__FILE__)."/class.maillog.tools.inc");

class amavis_maillog_buffer{
	var $buffer=null;
	
	
	function amavis_maillog_buffer($buffer){
		$this->buffer=$buffer;
	}
	
	private function syslog_intern($text){
			if(function_exists("WriteToSyslogMail")){WriteToSyslogMail($text, "artica-watchdog");}
	}
	
	public function parse(){
		$buffer=$this->buffer;
		if(!isset($GLOBALS["maillog_tools"])){$GLOBALS["maillog_tools"]=new maillog_tools();}
		$maillog_tools=$GLOBALS["maillog_tools"];
		if(strpos($buffer,"AM.PDP  /var/amavis")>0){return true;}	
	
		if(preg_match("#\[[0-9]+\]:\s+\([0-9]+\)\s+Checking:.+?AM\.PDP-SOCK#",$buffer)){return true;}
		if(preg_match("#\)\s+Blocked SPAM, AM\.PDP-SOCK\s+\[(.+?)\].+?<(.*?)>.+?<(.+?)>.+?Queue-ID:\s+(.+?),.+?size:\s+([0-9]+)#",$buffer,$re)){$maillog_tools->amavis_spam($re[4],$re[1],$re[2],$re[3],$re[5],"SPAM");return true;}
		if(preg_match("#\)\s+Passed\s+BAD-HEADER,.+?\[(.+?)\].+?<(.*?)>.+?<(.+?)>.+?\s+Queue-ID:\s+(.+?),.+?size:\s+([0-9]+)#",$buffer,$re)){$maillog_tools->amavis_spam($re[4],$re[1],$re[2],$re[3],$re[5],"Sended");return true;}
		if(preg_match("#\)\s+Blocked SPAMMY,.+?\[(.+?)\].+?<(.*?)>.+?<(.+?)>.+?,\s+Queue-ID:\s+(.+?),.+?size:\s+([0-9]+)#",$buffer,$re)){$maillog_tools->amavis_spam($re[4],$re[1],$re[2],$re[3],$re[5],"SPAMMY");return true;}
		if(preg_match("#\)\s+Passed CLEAN,.+?\[(.+?)\].+?<(.*?)>.+?<(.+?)>.+?\s+Queue-ID:\s+(.+?),.+?size:\s+([0-9]+)#",$buffer,$re)){$maillog_tools->amavis_spam($re[4],$re[1],$re[2],$re[3],$re[5],"Sended");return true;}
		if(preg_match("#smtp.+?status=deferred.+?connect.+?\[127\.0\.0\.1\]:10024: Connection refused#",$buffer,$re)){$maillog_tools->AmavisConfigErrorInPostfix($buffer);return true;}

		if(preg_match("#amavis\[.*?]: Net::Server: Accept failed with [0-9]+ tries left: Invalid argument",$buffer,$re)){
			$timefile="/etc/artica-postfix/croned.1/amavis.net.server.error";
			$this->events("Net::Server: Accept failed",__LINE__);
			if($this->file_time_min($timefile)>3){
				$this->syslog_intern("Net::Server: Accept failed !! restart Amavisd-new daemon");
				$this->email_events("Amavis service fatal error","Amavis claim \n$buffer\nArtica has decided to restart amavis service.","postfix");				
				shell_exec($GLOBALS["CLASS_UNIX"]->find_program("nohup")." /etc/init.d/artica-postfix restart amavis &");
				@file_put_contents($timefile,time());				
				return true;			
			}
		}
		
		
		
		if(preg_match("#amavis\[.+?connect to.+?\/clamav\.sock\s+failed, attempt.+?Can't connect to UNIX socket#",$buffer,$re)){
			$timefile="/etc/artica-postfix/croned.1/amavis.clamav.socket.error";
			$this->events("Amavis:: clamav socket error",__LINE__);
			if($this->file_time_min($timefile)>3){
				$this->syslog_intern("Clamav daemon seems not running: Amavis claims it, restart clamav");
				shell_exec($GLOBALS["CLASS_UNIX"]->find_program("nohup")." /etc/init.d/artica-postfix restart clamd &");
				@file_put_contents($timefile,time());				
				return true;	
			}
		}
		
		
		if(preg_match("#TROUBLE in pre_loop_hook: TEMPBASE directory is not writable:\s+(.+?)\s+#", $buffer,$re)){
			$timefile="/etc/artica-postfix/croned.1/".__CLASS__.".".__FUNCTION__.".TEMPBASE.error";
			if($this->file_time_min($timefile)>10){
				$this->events("Amavis:: TEMPBASE directory `{$re[1]}` is not writable",__LINE__);
				@mdkir($re[1],0755,true);
				shell_exec($GLOBALS["CLASS_UNIX"]->find_program("chown")." postfix:postfix {$re[1]}");
				@file_put_contents($timefile,time());				
				return true;	
			}
			$this->events("Amavis:: TEMPBASE directory `{$re[1]}` is not writable ",__LINE__);
			return true;
		}		
		

		if(preg_match("#amavis.+?:.+?_DIE: Suicide in child_init_hook: BDB no db#i",$buffer,$re)){
			$timefile="/etc/artica-postfix/croned.1/".__CLASS__.".".__FUNCTION__.".BDB.no.dbS.error";
			if($this->file_time_min($timefile)>10){
				$this->events("Amavis:: BDB no dbS !!! remove /var/amavis/db/*",__LINE__);
				shell_exec($GLOBALS["CLASS_UNIX"]->find_program("rm")." -rf /var/amavis/db/*");
				$this->email_events("Suicide in child_init_hook: BDB no dbS",
				"Amavis claim \n$buffer\nArtica has deleted the /var/amavis/db content directory","postfix");
				@file_put_contents($timefile,time());				
				return true;	
			}
			$this->events("Amavis:: BDB no dbS !!! ",__LINE__);
			return true;
		}		
		

		if(preg_match("#amavis.+?:.+?_DIE:\s+Can.+?locate.+?.+?body_[0-9]+\.pm\s+in\s+@INC#",$buffer,$re)){$this->SpamAssassin_error_saupdate($buffer);return true;	}		


	// ----------------------------------------------------------------------------------------------------------------
	
	}
	
	private function SpamAssassin_error_saupdate($buffer){
		$file="/etc/artica-postfix/cron.1/".__CLASS__.".".__FUNCTION__;
		$timeFile=$this->file_time_min($file);
		if($timeFile<15){
			$this->events("*** $buffer ****");
			$this->events("Spamassassin no operations, blocked by timefile $timeFile Mn ($file)!!!");
			return null;
		}	
		$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
		$this->events("Spamassassin error time:$timeFile Mn!!!");
		$this->email_events("SpamAssassin error Regex","SpamAssassin claim \"$buffer\", Artica will run /usr/bin/sa-update to fix it",'postfix');
		$cmd="$nohup /usr/share/artica-postfix/bin/artica-update --spamassassin --force >/dev/null 2>&1 &";
		shell_exec($cmd);
		@unlink($file);
		@file_put_contents($file,time());	
		if(!is_file($file)){$this->events("error writing time file:$file");}	
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
		if(!is_file($path)){if($GLOBALS["VERBOSE"]){echo "file_time_min() -> unable to stat $path\n";}return 100000;}
		$last_modified = filemtime($path);
	 	$data1 = $last_modified;
	 	$data2 = time();
		$difference = ($data2 - $data1); 	 
		return round($difference/60);	 
	}	
	
	
}