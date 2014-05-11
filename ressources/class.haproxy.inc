<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).  '/class.mysql.inc');
class haproxy{
	var $mode=array(0=>"tcp",1=>"http");
	
	
	function haproxy(){
		
	}
	
	function buildconf(){
		$f[]="global";
		$f[]="\tlog	127.0.0.1 local0";
		$f[]="\tmaxconn	4000";
		$f[]="\tuid	0";
		$f[]="\tgid	0";
		$f[]="\tchroot	/tmp";
		$f[]="\tpidfile /var/run/haproxy.pid";
		$f[]="\tstats socket /var/run/haproxy.stat mode 600 level admin";
		$f[]="\tnbproc\t1";
		$f[]="\tdaemon";
		$f[]="#\tdebug";
		$f[]="#\tquiet";	
		$f[]="";
		$f[]="defaults";
		$f[]="\tlog\tglobal";
		$f[]="\tmode\thttp";
		$f[]="\toption\tdontlognull";
		$f[]="\toption\tlogasap";
		$f[]="\tretries\t3";
		$f[]="\toption\tredispatch";
		$f[]="\tmaxconn\t4000";
		$f[]="\tcontimeout\t5000";
		$f[]="\tclitimeout\t50000";
		$f[]="\tsrvtimeout\t50000";		
		$f[]="";
		$sql="SELECT * FROM haproxy WHERE enabled=1";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building configuration failed $q->mysql_error\n";return;}}
		if(mysql_num_rows($results)==0){if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} No backend defined\n";return;}}
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$options=array();
			$transparent_text=null;
			$transparent=$ligne["transparent"];
			$tunnel_mode=$ligne["tunnel_mode"];
			$dispatch_mode=$ligne["dispatch_mode"];
			if(!is_numeric($transparent)){$transparent=0;}
			if(!is_numeric($tunnel_mode)){$tunnel_mode=0;}
			if($tunnel_mode==1){
					$dispatch_mode="leastconn";
					//$ligne["loadbalancetype"]=0;
			}
			$MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
			if(!is_numeric($MainConfig["UseCookies"])){$MainConfig["UseCookies"]=0;}
			if($ligne["loadbalancetype"]==0){$options[]="\toption\ttcplog";$MainConfig["UseCookies"]=0;}
			$backends=$this->build_backends($ligne["servicename"],$MainConfig["UseCookies"]);
			if($backends==null){continue;}
			$loadbalancetype=$ligne["loadbalancetype"];
			
			if(!is_numeric($MainConfig["http-use-proxy-header"])){$MainConfig["http-use-proxy-header"]=1;}
			if(!is_numeric($MainConfig["forwardfor"])){$MainConfig["forwardfor"]=1;}
			if(!is_numeric($MainConfig["originalto"])){$MainConfig["originalto"]=1;}
			
			
			
			
			
			
			if($MainConfig["UseSMTPProto"]==1){
				 if($MainConfig["postfix-send-proxy"]==1){
					if($MainConfig["smtpchk_EHLO"]<>null){
						$options[]="\toption\tsmtpchk\tHELO {$MainConfig["smtpchk_EHLO"]}";
					}else{
						$options[]="\toption\tsmtpchk\tNOOP";
					}
				 }
			}
			
			//$options[]="\toption\tforwardfor\theader\tX-Client";
			if($transparent==1){$ligne["listen_ip"]="127.0.0.1";}
			$f[]="listen {$ligne["servicename"]} {$ligne["listen_ip"]}:{$ligne["listen_port"]}";
			$f[]="\tmode ". $this->mode[$ligne["loadbalancetype"]];
			$options[]="\toption\thttplog";
			if($dispatch_mode<>null){
				$f[]="\tbalance $dispatch_mode";
			}
			
			$Cookies_options="nocache";
			
			if($loadbalancetype==1){
				
				if($tunnel_mode==0){
					
					$options[]="\toption\thttp-server-close";
					
				}else{
					
				}
				if($MainConfig["http-use-proxy-header"]==1){$options[]="\toption\thttp-use-proxy-header";}
				if($MainConfig["forwardfor"]==1){$options[]="\toption\tforwardfor";}
				if($MainConfig["originalto"]==1){$options[]="\toption\toriginalto";}
				
				 
				
			}
			
			if($transparent==1){
					$f[]="\toption\ttransparent";
					$f[]="\tsource\t0.0.0.0 usesrc clientip";
					$Cookies_options="insert nocache indirect";
			
			}
			if(count($options)>0){$f[]=@implode("\n", $options)."\n";}
			
			if($MainConfig["contimeout"]>0){$f[]="\tcontimeout\t{$MainConfig["contimeout"]}";}
			if($MainConfig["srvtimeout"]>0){$f[]="\tsrvtimeout\t{$MainConfig["srvtimeout"]}";}
			if($MainConfig["clitimeout"]>0){$f[]="\tclitimeout\t{$MainConfig["clitimeout"]}";}
			if($MainConfig["retries"]>0){$f[]="\tretries\t{$MainConfig["retries"]}";}
			if($MainConfig["UseCookies"]==1){$f[]="\tcookie SERVERID $Cookies_options";}
			
			
			
			//$f[]="\tsource 0.0.0.0 usesrc clientip\n";
			$f[]=$backends;
		}
		
		$f[]="listen admin_page 127.0.0.1:64741";
        $f[]="\tmode http";
        $f[]="\tstats uri /stats";
        
        // 127.0.0.1:64741/stats;csv
        
		return @implode("\n", $f);
		
	}
	
	
	private function build_backends($servicename,$UseCookies=0){
		
		$sql="SELECT * FROM haproxy_backends WHERE enabled=1 AND servicename='$servicename'";
		$q=new mysql();
		if(!is_numeric($UseCookies)){$UseCookies=0;}
		$results=$q->QUERY_SQL($sql,'artica_backup');
		if(mysql_num_rows($results)==0){return;}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$check_params=null;
			$maxconn=null;
			$MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
			if(!is_numeric($MainConfig["postfix-send-proxy"])){$MainConfig["postfix-send-proxy"]=0;}
			if( ($MainConfig["inter"]>0) && ($MainConfig["fall"]>0) && ($MainConfig["rise"]>0) ){
				$check_params=" inter {$MainConfig["inter"]} rise {$MainConfig["rise"]} fall {$MainConfig["fall"]}";
			}
			
			$servMD=md5("{$ligne["listen_ip"]}:{$ligne["listen_port"]}");
			if($UseCookies==1){$UseCookiesText=" cookie $servMD";}
			if($MainConfig["maxconn"]>0){$maxconn=" maxconn $maxconn";}
			if($MainConfig["postfix-send-proxy"]==1){$smtp_proxy=" send-proxy";}
			$t[]="\tserver {$ligne["backendname"]} {$ligne["listen_ip"]}:{$ligne["listen_port"]}$smtp_proxy$UseCookiesText weight {$ligne["bweight"]}$maxconn check $check_params".$this->outgoing_address($ligne["localInterface"]);
			
			
				
			
		}
		return @implode("\n",$t)."\n";
		
	}
	
	private function outgoing_address($localInterface=null){
		if($localInterface==null){return;}
		
		if(!class_exists("unix")){
			return " source [of $localInterface] usesrc clientip";
		}
		
		if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){
			$unix=new unix();
			$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();
		}
		
		$IPADDR=$GLOBALS["NETWORK_ALL_INTERFACES"][$localInterface]["IPADDR"];
		if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $localInterface = $IPADDR\n";}
		if($IPADDR==null){return;}
		if($IPADDR=="0.0.0.0"){return;}
		if($IPADDR=="127.0.0.1"){return;}
		return " source $IPADDR";
		
		
		
	}
	
	
	
	
}


class haproxy_multi{
	var $servicename;
	var $listen_ip=null;
	var $listen_port=null;
	var $loadbalancetype=0;
	var $dispatch_mode=null;
	var $MainConfig=array();
	var $enabled=0;
	var $transparent=0;
	var $transparentsrcport=0;
	var $tunnel_mode=0;
	function haproxy_multi($servicename){
		
		if($servicename<>null){
			$this->servicename=$servicename;
			$this->load();
		}
	}
	
	
	private function load(){
		$sql="SELECT * from haproxy WHERE servicename='$this->servicename'";
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$this->listen_ip=$ligne["listen_ip"];
		$this->listen_port=$ligne["listen_port"];
		$this->enabled=$ligne['enabled'];
		$this->loadbalancetype=$ligne["loadbalancetype"];
		$this->dispatch_mode=$ligne["dispatch_mode"];
		$this->transparent=$ligne["transparent"];
		$this->transparentsrcport=$ligne["transparentsrcport"];
		$this->MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
		$this->tunnel_mode=$ligne["tunnel_mode"];
		if(!is_numeric($this->tunnel_mode)){$this->tunnel_mode=0;}
	}
	
	
	public function save(){
		$this->servicename=trim(strtolower($this->servicename));
		$listenaddrport="$this->listen_ip:$this->listen_port";
		$this->servicename=replace_accents($this->servicename);
		$this->servicename=str_replace(" ", "_", $this->servicename);
		$this->servicename=str_replace("-", "_", $this->servicename);
		$sql="SELECT servicename from haproxy WHERE servicename='$this->servicename'";
		$MainConfig=base64_encode(serialize($this->MainConfig));
		$q=new mysql();
		
		if(!$q->FIELD_EXISTS("haproxy", "tunnel_mode", "artica_backup")){		
			$sql2="ALTER TABLE `haproxy` ADD `tunnel_mode` SMALLINT( 1 ) NOT NULL DEFAULT '0',
					ADD INDEX (`tunnel_mode`)"; 
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){writelogs("$q->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}			
		}
		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(trim($ligne["servicename"])==null){
			$sql="INSERT IGNORE INTO haproxy (servicename,listen_ip,listen_port,ipaddrport,loadbalancetype,dispatch_mode,transparent,transparentsrcport,tunnel_mode) 
			VALUES ('$this->servicename','$this->listen_ip','$this->listen_port','$listenaddrport','$this->loadbalancetype','$this->dispatch_mode','$this->transparent','$this->transparentsrcport','$this->tunnel_mode')";
			
		}else{
			$sql="UPDATE haproxy SET 
				listen_ip='$this->listen_ip',
				listen_port='$this->listen_port',
				ipaddrport='$listenaddrport',
				enabled='$this->enabled',
				loadbalancetype='$this->loadbalancetype',
				dispatch_mode='$this->dispatch_mode',
				transparent='$this->transparent',
				tunnel_mode=$this->tunnel_mode,
				transparentsrcport='$this->transparentsrcport',
				MainConfig='$MainConfig'
				WHERE servicename='$this->servicename'
				";
		}
		
		$q=new mysql();
		if(!$q->TABLE_EXISTS("haproxy", "artica_backup")){$q->BuildTables();}
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $q->mysql_error\n";
		writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		return;}
		$this->CheckService();
	}
	
	public function DeleteService(){
		$q=new mysql();
		$sql="DELETE FROM haproxy_backends WHERE servicename='$this->servicename'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $q->mysql_error\n";return;}
		$sql="DELETE FROM haproxy WHERE servicename='$this->servicename'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}		
		$this->CheckService();
	}
	
	private function CheckService(){
		$sock=new sockets();
		$sock->getFrameWork("services.php?reload-haproxy=yes");		
	}
	
}

class haproxy_backends{
	var $backendname=null;
	var $servicename;
	var $listen_ip=null;
	var $listen_port=null;
	var $bweight=1;
	var $dispatch_mode=null;
	var $enabled=0;
	var $MainConfig=array();
	var $localInterface=null;
	function haproxy_backends($servicename,$backendname){
		
		if($servicename<>null){
			$this->servicename=$servicename;
			if($backendname<>null){
				$this->backendname=$backendname;
				$this->load();
			}
		}
	}
	
	
	private function load(){
		$sql="SELECT * from haproxy_backends WHERE servicename='$this->servicename' AND backendname='$this->backendname'";
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$this->listen_ip=$ligne["listen_ip"];
		$this->listen_port=$ligne["listen_port"];
		$this->enabled=$ligne['enabled'];
		$this->bweight=$ligne["bweight"];
		$this->localInterface=$ligne["localInterface"];
		$this->MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
		
	}
	
	
	public function save(){
		$this->backendname=trim(strtolower($this->backendname));
		$this->backendname=replace_accents($this->backendname);
		$this->backendname=str_replace(" ", "_", $this->backendname);
		$this->backendname=str_replace("-", "_", $this->backendname);
		$sql="SELECT backendname from haproxy_backends WHERE servicename='$this->servicename' AND backendname='$this->backendname'";
		$MainConfig=base64_encode(serialize($this->MainConfig));
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(trim($ligne["backendname"])==null){
			$sql="INSERT IGNORE INTO haproxy_backends (servicename,backendname,listen_ip,listen_port,bweight,localInterface) 
			VALUES ('$this->servicename','$this->backendname','$this->listen_ip','$this->listen_port','$this->bweight','$this->localInterface')";
			
		}else{
			$sql="UPDATE haproxy_backends SET 
				listen_ip='$this->listen_ip',
				listen_port='$this->listen_port',
				bweight='$this->bweight',
				enabled='$this->enabled',
				localInterface='$this->localInterface',
				MainConfig='$MainConfig'
				WHERE servicename='$this->servicename' AND  backendname='$this->backendname'
				";
		}
		
		$q=new mysql();
		if(!$q->TABLE_EXISTS("haproxy", "artica_backup")){$q->BuildTables();}
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n".__CLASS__."\n$sq\n";return;}
		$this->CheckService();
	}
	
	public function DeleteBackend(){
		$q=new mysql();
		$sql="DELETE FROM haproxy_backends WHERE servicename='$this->servicename' AND  backendname='$this->backendname'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}		
		$this->CheckService();
	}
	
	private function CheckService(){
		$sock=new sockets();
		$sock->getFrameWork("services.php?reload-haproxy=yes");
	}
	
}