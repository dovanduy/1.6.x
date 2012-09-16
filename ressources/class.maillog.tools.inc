<?php
class maillog_tools{
	var $buffer=null;
	
	
	function maillog_tools(){
		if(!isset($GLOBALS["ActAsSMTPGatewayStatistics"])){$sock=new sockets();$GLOBALS["ActAsSMTPGatewayStatistics"]=$sock->GET_INFO("ActAsSMTPGatewayStatistics");if(!is_numeric($GLOBALS["ActAsSMTPGatewayStatistics"])){$GLOBALS["ActAsSMTPGatewayStatistics"]=0;}}
	}
	
	public function Postfix_Addconnection_error($hostname,$ip,$error_text){
		if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
		$time=time();
	
		if($hostname==null){if($ip<>null){$hostname=gethostbyaddr($ip);}}
		
		if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.#",$ip)){
			$ip=gethostbyname($ip);
			if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.#",$ip)){if($hostname<>null){$ip=gethostbyname($hostname);}}
			if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.#",$ip)){return null;}
		}
		
		$array=array("HOSTNAME"=>$hostname,"IP"=>$ip,"TIME"=>$time,"error"=>$error_text);
		$ser=serialize($array);
		@file_put_contents("/var/log/artica-postfix/smtp-connections/". md5($ser).".err",$ser);
		
	}	
	
	
	
public function amavis_spam($postfix_id,$smtp_sender,$from,$to,$size,$action){
	if($GLOBALS["ActAsASyslogSMTPClient"]==1){return;}
	if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
	$file="/var/log/artica-postfix/RTM/$postfix_id.msg";
	$ini=new Bs_IniHandler($file);
	$ini->set("TIME","message-id","$postfix_id");
	$ini->set("TIME","time_end",date("Y-m-d H:i:s"));
	$ini->set("TIME","bounce_error",$action);
	$ini->set("TIME","size",$size);
	if($to<>null){$ini->set("TIME","mailto",$to);}
	if($from<>null){$ini->set("TIME","mailfrom","$from");}
	if($smtp_sender<>null){$ini->set("TIME","server_from","$smtp_sender");}
	if($action<>"Sended"){
		$ini->set("TIME","delivery_success","no");
		$this->Postfix_Addconnection_error(null,$smtp_sender,"SPAM");
	}else{
		$ini->set("TIME","delivery_success","yes");
		
	}
	$ini->saveFile($file);
}

public function event_message_reject_hostname($reject,$from,$to=null,$server){
	if($GLOBALS["ActAsASyslogSMTPClient"]==1){return;}
	if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
	
	$to=str_replace("|","",$to);
	$to=trim($to);
	if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.#",$server)){
		$hostname=gethostbyaddr($server);
		$this->Postfix_Addconnection_error($hostname,$server,$reject);
	}
	
	
	$file="/var/log/artica-postfix/RTM/".md5(date("Y-m-d H:i:s").$server.$from).".msg";
	events("$reject: $server from=<$from>< to=<$to> in line ".__LINE__." event: <".basename($file).">");
	
		$ini=new Bs_IniHandler($file);
		$ini->set("TIME","smtp_sender",$server);	
		if($to<>null){$ini->set("TIME","mailto",$to);}
		if($from<>null){$ini->set("TIME","mailfrom",$from);}
		$ini->set("TIME","bounce_error",$reject);
		$ini->set("TIME","time_connect",date("Y-m-d H:i:s"));
		$ini->set("TIME","delivery_success","no");
		$ini->saveFile($file);
		if(!is_file($file)){events("$reject:".basename($file)." error writing in line ".__LINE__);}
}

public function event_messageid_rejected($msg_id_postfix,$error,$server=null,$to=null){
	if($GLOBALS["ActAsASyslogSMTPClient"]==1){return;}
	if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
	$file="/var/log/artica-postfix/RTM/$msg_id_postfix.msg";
	$ini=new Bs_IniHandler($file);
	if($server<>null){$ini->set("TIME","smtp_sender",$server);}
	if($to<>null){$ini->set("TIME","mailto",$to);}
	$ini->set("TIME","delivery_success","no");
	$ini->set("TIME","bounce_error",$error);
	$ini->set("TIME","time_connect",date("Y-m-d H:i:s"));
	$ini->saveFile($file);		
}

public function AmavisConfigErrorInPostfix($buffer){
	if(!isset($GLOBALS["ActAsSMTPGatewayStatistics"])){$sock=new sockets();$GLOBALS["ActAsSMTPGatewayStatistics"]=$sock->GET_INFO("ActAsSMTPGatewayStatistics");if(!is_numeric($GLOBALS["ActAsSMTPGatewayStatistics"])){$GLOBALS["ActAsSMTPGatewayStatistics"]=0;}}
	$file="/etc/artica-postfix/cron.1/".__FUNCTION__;
	$timeFile=file_time_min($file);
	if($timeFile<15){
		events("*** $buffer ****");
		events("amavisd-new socket no operations, blocked by timefile $timeFile Mn!!!");
		return null;}
		if($GLOBALS["ActAsSMTPGatewayStatistics"]==0){	
			events("amavisd-new socket error time:$timeFile Mn!!!");
			email_events("amavisd-new socket error","Postfix claim \"$buffer\", Artica will reload Postfix and compile new Postfix settings",'postfix');
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["PHP5_BIN"]} ".dirname(__FILE__)."/exec.postfix.maincf.php --reconfigure");
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/etc/init.d/artica-postfix restart amavis');
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET('/usr/share/artica-postfix/bin/artica-install --postfix-reload');
		}
	@unlink($file);
	@file_put_contents($file,"#");	
	if(!is_file($file)){
		events("error writing time file:$file");
	}
}
private function shell_exec_maillog($cmd){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==1){
		events("`$cmd` will not be executed ActAsSMTPGatewayStatistics is enabled" );
		return;
	}
	shell_exec($cmd);
	events("EXEC:`$cmd`" );
}
	
	
}