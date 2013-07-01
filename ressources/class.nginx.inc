<?php
	include_once(dirname(__FILE__).'/class.squid.inc');
class nginx{
	private $servername=null;
	private $ssl=0;
	private $squidR;
	private $sslcertificate=null;
	private $proxy_to_host=null;
	private $poolid=0;
	private $cache_peer_id=0;
	private $proxy_to_port=null;
	private $proxy_suffix=null;
	private $proxy_scheme=null;
	private $HostPortLNK=null;
	private $forceddomain=null;
	private $NginxProxyStorePath=null;
	private $host_port=0;
	private $storeid=0;
	private $host_ipaddr=null;
	private $proxy_disabled=0;
	private $DocumentRoot=null;
	private $IndexFile=null;
	private $IsAFreeWeb=false;
	private $APACHE_USER;
	private $APACHE_SRC_GROUP;
	private $CompileParams=array();
	private $ServersAliases=array();
	private $OWA=false;
	function nginx($servername=null){
		$this->unix=new unix();
		if(is_numeric($servername)){
			$this->host_port=$servername;
			
			
		}else{
			if($servername<>null){$this->servername=$servername;}
		}
		$this->squidR=new squidbee();
		$sock=new sockets();
		
		$this->NginxProxyStorePath=$sock->GET_INFO("NginxProxyStorePath");
		if($this->NginxProxyStorePath==null){$this->NginxProxyStorePath="/home/nginx";}
		$this->APACHE_USER=$this->unix->APACHE_SRC_ACCOUNT();
		$this->APACHE_SRC_GROUP=$this->unix->APACHE_SRC_GROUP();
		if($this->APACHE_USER==null){$this->APACHE_USER="www-data";}
		if($this->APACHE_SRC_GROUP==null){$this->APACHE_SRC_GROUP="www-data";}
		if(class_exists("unix")){$this->LoadParams();}
	}
	
	
	public function set_ssl($value=null){
		if(!is_numeric($value)){$value=1;}
		$this->ssl=$value;
	}
	
	public function set_ssl_certificate($sslcertificate=null){
		if($sslcertificate==null){
			if($this->sslcertificate<>null){return;}
		}
		$this->sslcertificate=$sslcertificate;
	}
	
	public function set_proxy_destination($hostname=null){
		$this->proxy_to_host=$hostname;
		
		if(strpos(" $hostname", "://")){
			$array=parse_url($hostname);
			if(isset($array["port"])){
				$this->proxy_to_port=$array["port"];
			}
			
			if(isset($array["scheme"])){
				$this->proxy_scheme=$array["scheme"];
			}
			
			if(isset($array["path"])){
				$this->proxy_suffix=$array["path"];
			}
			
		}
		if(!is_numeric($this->proxy_to_port)){$this->proxy_to_port=0;}
		
		
		if($this->proxy_to_port==0){
			if($this->proxy_scheme<>null){
				if($this->proxy_scheme=="http"){
					$this->proxy_to_port=80;
				}
				if($this->proxy_scheme=="https"){
					$this->proxy_to_port=443;
				}				
			}
			
		}
	}
	
	private function LoadParams(){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.":Loading\n";}
		$unix=new unix();
		$this->CompileParams=$unix->NGINX_COMPILE_PARAMS();
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.":Done...\n";}
	}
	
	public function set_proxy_port($port=null){
		if(!is_numeric($port)){return;}
		if($port==0){return;}
		if($port<0){return;}
		$this->proxy_to_port=$port;
	}	
	public function set_forceddomain($domainname=null){
		$this->forceddomain=$domainname;
	}
	public function set_storeid($id=null){
		if(!is_numeric($id)){return;}
		if($id==0){return;}
		$this->storeid=$id;
	}

	public function set_proxy_disabled(){
		$this->proxy_disabled=1;
	}
	
	public function set_DocumentRoot($directory){
		$this->DocumentRoot=$directory;
	}	
	
	public function set_index_file($indexfile){
		$this->IndexFile=$indexfile;
	}
	
	public function set_freeweb(){
		$this->IsAFreeWeb=true;
	}
	
	public function set_cache_peer_id($ID=0){
		if(!is_numeric($ID)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, set_cache_peer_id($ID) : False\n";}
			return;}
		if($ID>0){
			$this->cache_peer_id=$ID;
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, set_cache_peer_id($ID) : True\n";}
			return;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, set_cache_peer_id($ID) : False\n";}
	}
	
	public function set_listen_port($port=0){
		if(!is_numeric($port)){return;}
		if($port>0){$this->host_port=$port;}
	}
	
	public function set_poolid($poolid=0){
		if(!is_numeric($poolid)){return;}
		if($poolid>0){$this->poolid=$poolid;}
	}
	
	public function set_servers_aliases($array){
		if(!is_array($array)){return;}
		if(count($array)==0){return;}
		while (list ($index, $value) = each ($array) ){
			if(!is_numeric($index)){$newarray[$index]=true;continue;}
			if(!preg_match("#.*?\..*?$#", $value)){continue;}
			$newarray[$value]=true;
			
		}
		while (list ($hostname, $value) = each ($newarray) ){
			$this->ServersAliases[]=$hostname;
		}
		
	}
	
	public function set_listen_ip($ipaddr){
		$net =new IP();
		if(!$net->isValid($ipaddr)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx,$ipaddr not a valid ip address\n";}
			return;
		}
		
		$this->host_ipaddr=$ipaddr;
		
	}
	
	public function set_owa($value){
		if($value==1){
			$this->OWA=true;
			$this->set_ssl();
		}
		
	}
	
	private function isFreeWebSSL(){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT useSSL FROM freeweb WHERE servername='$this->servername'","artica_backup"));
		if($ligne["useSSL"]==1){return true;}
		return false;
	}
	
	public function FillHostnameAsIP(){
		if(isset($GLOBALS["FillHostnameAsIP"])){
			$this->servername=$GLOBALS["FillHostnameAsIP"];
			return $GLOBALS["FillHostnameAsIP"];}
		$ifconfig_all_ips=$this->unix->ifconfig_all_ips();
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, ".count($ifconfig_all_ips)." network addresses\n";}
		while (list ($a, $b) = each ($ifconfig_all_ips) ){$ips[]=$a;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, host:{$ips[0]}\n";}
		$GLOBALS["FillHostnameAsIP"]=$ips[0];
		$this->servername=$ips[0];		
		return $GLOBALS["FillHostnameAsIP"];
	}
	
	private function IsSubstitutions(){
		if(isset($GLOBALS["IsSubstitutions"])){return $GLOBALS["IsSubstitutions"];}
		$GLOBALS["IsSubstitutions"]=false;
		$ARRAY=$this->CompileParams;
		while (list ($a, $b) = each ($ARRAY["MODULES"]) ){
			if(preg_match("#http_substitutions_filter#", $a)){
				$GLOBALS["IsSubstitutions"]=true;
				break;
			}
		
		}
		return $GLOBALS["IsSubstitutions"];
	}
	
	public function build_proxy(){
		@chmod(0755,"/etc/nginx/sites-enabled");
		if($this->sslcertificate<>null){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, using certificate $this->sslcertificate, turn to ssl\n";}
			$this->ssl=1;
		}
		
		$unix=new unix();
		if($this->servername==null){$this->servername=$this->FillHostnameAsIP();}
		if($this->ssl==1){
			$filename="/etc/nginx/sites-enabled/freewebs-ssl-$this->servername.$this->host_port.conf";
			$data=$this->ssl_proxy();
			
		}else{
			$filename="/etc/nginx/sites-enabled/freewebs-$this->servername.$this->host_port.conf";
			$data=$this->http_proxy();
			
		}
		if($GLOBALS["VERBOSE"]){
			echo "\n$data\n";
			file_put_contents($filename,$data);
		}else{
			@file_put_contents($filename,$data);
		}
		
		$size=filesize($filename);
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx,".$filename." done.($size bytes)\n";}
		
		
	}
	private function keys_zone($storeid=null){
		if(!is_numeric($storeid)){return null;}
		if($storeid==0){return null;}
		
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT keys_zone FROM nginx_caches WHERE ID='$storeid'"));
		if($ligne["keys_zone"]<>null){
			return "\t\tproxy_cache {$ligne["keys_zone"]};";
		}
	}
	
	private function proxy_pass($cache_peer_id=null,$asregex=false,$folderid=0){
		$q=new mysql_squid_builder();
		$suffix=null;
		
		
		
		if($folderid>0){
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_dirs WHERE folderid='$folderid'"));
			$hostweb=$ligne["hostweb"];
			if($ligne["local"]==0){
				if(substr($hostweb, 0,1)=="/"){$suffix=$hostweb;}
					if(strpos(" $hostweb", "http")>0){
							$f[]="\t\tproxy_pass $hostweb;";
							if($ligne["ForceRedirect"]==1){$f[]="\t\tproxy_redirect  $hostweb/   /;";}
					}
			}
			
			return @implode("\n", $f);
		}
		
		if(!is_numeric($cache_peer_id)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, $this->servername: [$cache_peer_id]: aborting\n";}
			return null;
		}
		
		if($cache_peer_id==0){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, $this->servername: [$cache_peer_id]: aborting\n";}
			return null;
		}		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_sources WHERE ID='$cache_peer_id'"));
		$ipaddr=$ligne["ipaddr"];
		$ssl=$ligne["ssl"];
		$port=$ligne["port"];
		if(!is_numeric($port)){$port=80;}
		$http_prefix="https";
		if($port==80){$http_prefix="http";}
		if($ssl==0){$http_prefix="http";}
		if($ipaddr==null){return null;}
		$slash="/";
		if($asregex){$slash=null;}
		$f[]="\t\tproxy_pass {$http_prefix}://$ipaddr:$port$suffix;";
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, $this->servername: [$cache_peer_id]: ForceRedirect = {$ligne["ForceRedirect"]}\n";}
		if($ligne["ForceRedirect"]==1){
			$f[]="\t\tproxy_redirect   {$http_prefix}://$ipaddr:$port$suffix/   /;";
		}
		return @implode("\n", $f);
		
	}
	
	private function forcedomain($cache_peer_id=null){
		if(!is_numeric($cache_peer_id)){return null;}
		if($cache_peer_id==0){return null;}
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT forceddomain FROM reverse_sources WHERE ID='$cache_peer_id'"));
		return trim($ligne["forceddomain"]);
	}
	
	private function ParseHostHeader($HostHeader){
		if(strpos(" $HostHeader", "/")>0){
			if(substr($HostHeader, 0,1)=="/"){return null;}
			
			if(strpos(" $HostHeader", "http")>0){
				$urlEXt=parse_url($HostHeader);
				return $urlEXt["host"];
			}
		}

		if(substr($HostHeader, 0,1)=="."){$HostHeader=substr($HostHeader,1,strlen($HostHeader));}
		return $HostHeader;
		
	}
	
	private function IsForceRedirect(){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ForceRedirect FROM reverse_sources WHERE ID='$this->cache_peer_id'"));
		if($ligne["ForceRedirect"]==0){return false;}
		return true;
		
	}
	
	
	private function error_pages(){
		if($this->OWA){
		$f[]="\terror_page 500 502 503 504 /exch500.html;";
		$f[]="\tlocation = /exch500.html {";
		$f[]="\t\troot /usr/share/artica-postfix/ressources/templates/default;";
		$f[]="\t}";
		$f[]="\tlocation = /exchange2010.png	{";
		$f[]="\t\troot /usr/share/artica-postfix/ressources/templates/default/img;";
		$f[]="\t}	
		";
			return @implode("\n", $f);
		}
		
	}
	
	
	private function http_servername_aliases(){
		$q=new mysql_squid_builder();
		$sql="SELECT alias FROM nginx_aliases WHERE servername='$this->servername' ORDER BY alias";
		$results=$q->QUERY_SQL($sql);
		$f=array();
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$ALI[$ligne["alias"]]=$ligne["alias"];
		}
		
		if(count($this->ServersAliases)>0){
			while (list ($index, $alias) = each ($this->ServersAliases) ){
				$ALI[$alias]=$alias;
			}
		}
			
		if(count($ALI)==0){return null;}
		
		while (list ($index, $alias) = each ($ALI) ){$f[]=$alias;}
		
		
		if(count($f)>0){return " ".@implode(" ", $f);}
	}
	
	
	private function http_locations_proxy($ligne){
		$directory=trim(stripslashes($ligne["directory"]));
		$firstPat=substr($directory, 0,1);
		$lastPat=substr($directory, strlen($directory)-1,1);
		if($firstPat<>"~"){
			if($firstPat<>'/'){$directory="/$directory";}
			if($lastPat<>'/'){$directory="$directory/";}
		}else{
			$asregex=true;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, $this->servername: $directory [$firstPat]\n";}
			
		$HostHeader=null;
		if($ligne["hostweb"]<>null){$HostHeader=$this->ParseHostHeader($ligne["hostweb"]);}
		
		
		
		$proxy_cache=$this->keys_zone($ligne["storeid"]);
		$proxy_pass=$this->proxy_pass($ligne["cache_peer_id"],$asregex,$ligne["folderid"]);
		$forcedomain=$this->forcedomain($ligne["cache_peer_id"]);
		if($forcedomain<>null){$HostHeader=$forcedomain;}
		$dirpat=$directory;
		if(preg_match("#\/(.+?)\/#", $directory,$re)){$dirpat=$re[1];}
			
		
		$f[]="location $directory {";
		//$f[]="\t\tproxy_redirect off;";
		if(!$asregex){
			$f[]="\t\trewrite ^$directory(.*)$ /$1 break;";
		}
		$replace_rules=$this->replace_rules_build($ligne["replaceid"]);
		if($replace_rules<>null){
			$f[]="\t\tproxy_set_header Accept-Encoding \"\";";
			$f[]=$replace_rules;
		}
		if($HostHeader<>null){$f[]="\t\tproxy_set_header Host $HostHeader;";}
		$f[]="\t\tproxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;";
		$f[]="\t\tproxy_hide_header \"Vary\";";
		$f[]="\t\tadd_header \"Vary\" \"Accept-Encoding\";";
		$f[]="\t\tproxy_set_header X-Forwarded-Ssl on;";
		if($proxy_cache<>null){$f[]=$proxy_cache;}
		if($proxy_pass<>null){$f[]=$proxy_pass;}
		
		//$f[]="\t\tproxy_pass http://$HostHeader/;";
		$f[]="\t\tproxy_pass_header Date;";
		$f[]="\t\tproxy_pass_header Server;";
		$f[]="}";
		$f[]="";		
		return @implode("\n", $f);
		
	}
	private function http_locations_local($ligne){
		$directory=trim(stripslashes($ligne["directory"]));
		$destination=$ligne["localdirectory"];
		$f[]="location $directory {";
		$f[]="\t\troot\t$destination;";
		$f[]="}";
		$f[]="";
		return @implode("\n", $f);
	}
	
	private function http_locations($servername){
		$q=new mysql_squid_builder();
		$f=array();
		$sql="SELECT * FROM reverse_dirs WHERE servername='$this->servername' AND enabled=1 ORDER BY directory";
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$asregex=false;
			$directory=trim($ligne["directory"]);
			
			if(isset($ALREADY[$directory])){if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, $this->servername: $directory already set\n";}continue;}
			$ALREADY[$directory]=true;
			if($directory==null){continue;}
			
			if($ligne["local"]==0){
				$f[]=$this->http_locations_proxy($ligne);
			}else{
				$f[]=$this->http_locations_local($ligne);
			}
			

			
		}
		
		return @implode("\n", $f);
		
		
	}
	
	
	
	private function http_proxy(){
		$unix=new unix();
		$ServersAliases=null;
		if(trim($this->servername)==null){$this->FillHostnameAsIP();}
		@mkdir("/var/log/apache2/$this->servername",0755,true);
		$unix->chown_func($this->APACHE_USER,$this->APACHE_SRC_GROUP,"/var/log/apache2/$this->servername");	
		
		$HostHeader="\$host";
		if($this->forceddomain<>null){$HostHeader="$this->forceddomain";}
		if($this->host_port==0){$this->host_port=80;}
		$HostIPADDR=$this->MyHostnameListen();
		$f[]="server {";
		$f[]="\tlisten {$HostIPADDR}$this->host_port;";
		$server_name_text=$this->CheckServerName($this->servername);
		$ServersAliases=$this->http_servername_aliases();
		
		if($this->servername<>null){$f[]="\tserver_name $server_name_text{$ServersAliases};";}
		

		
		
		$f[]="\taccess_log  /var/log/apache2/$this->servername/nginx.access.log aws_log;";
		$f[]="\terror_log  /var/log/apache2/$this->servername/nginx.error.log warn;";	

		if($this->OWA){
			$f[]="rewrite ^/$ http://$server_name_text$this->HostPortLNK/owa permanent;";
		}
		
		$f[]=$this->ROOT_PROXY();
		$f[]=$this->error_pages();
		$f[]="}";		

		
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, $this->servername:".count($f)." parameters\n";}
		return @implode("\n", $f);
		
	}
	
	private function CheckServerName($servername){
		$servername=trim(strtolower($servername));
		if(substr($servername, 0,1)=="."){$servername=substr($servername,1,strlen($servername));}
		return $servername;
	}
	
	private function MyHostnameListen(){
		$HostIPADDR=null;
		if(!isset($GLOBALS["ALL_IPS_GET_ARRAY"])){$IP=new networking();$GLOBALS["ALL_IPS_GET_ARRAY"]=$IP->ALL_IPS_GET_ARRAY();}
		
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $this->servername)){
			if(isset($GLOBALS["ALL_IPS_GET_ARRAY"][$this->servername])){
				$HostIPADDR="$this->servername:";
			}
			
		}	
		if($this->host_ipaddr<>null){
			if(isset($GLOBALS["ALL_IPS_GET_ARRAY"][$this->host_ipaddr])){
				$HostIPADDR=$this->host_ipaddr.":";
			}
		}
		
		if($this->host_port>0){$HostPort=$this->host_port;}
		$this->HostPortLNK=$HostPort;
		if($this->HostPortLNK==443){$HostIPADDR=null;$this->HostPortLNK=null;}else{$this->HostPortLNK=":$this->HostPortLNK";}		
		if($this->HostPortLNK==80){$HostIPADDR=null;$this->HostPortLNK=null;}else{$this->HostPortLNK=":$this->HostPortLNK";}
		if(preg_match("#:([0-9]+)$#", $this->HostPortLNK,$re)){$this->HostPortLNK=":".$re[1];}
		
		return $HostIPADDR;
	}
	
	
	private function ssl_proxy(){
		$unix=new unix();
		$HostHeader="\$host";
		$ServersAliases=null;
		if($this->servername==null){$this->FillHostnameAsIP();}
		if($this->host_port==0){$this->host_port=443;}
		$HostIPADDR=$this->MyHostnameListen();
		$AsProxy=true;
		if($this->proxy_disabled==1){$AsProxy=false;}
		
		
		
		@mkdir("/var/log/apache2/$this->servername",0755,true);
		$unix->chown_func($this->APACHE_USER,$this->APACHE_SRC_GROUP,"/var/log/apache2/$this->servername");
		if(count($this->ServersAliases)>0){$ServersAliases=" ".@implode(" ", $this->ServersAliases);}
		
		$server_name_text=$this->CheckServerName($this->servername);
		$f[]="server {";
		$f[]="\tlisten {$HostIPADDR}$this->host_port;";
		if($this->servername<>null){$f[]="\tserver_name $server_name_text{$ServersAliases};";}
		
		if($this->OWA){
			$f[]="\trewrite ^/$ https://$this->servername$this->HostPortLNK/owa/auth/logon.aspx?replaceCurrent=1&url=https%3a%2f%2f$this->servername%2fowa%2f permanent;";
		}
		

		
		$f[]="\taccess_log  /var/log/apache2/$this->servername/nginx.access.log aws_log;";
		$f[]="\terror_log  /var/log/apache2/$this->servername/nginx.error.log warn;";
		
		if($this->DocumentRoot<>null){
			$f[]="\troot   $this->DocumentRoot;";
			if($this->IndexFile<>null){ $f[]="\tindex $this->IndexFile;";}
			$f[]="\tlocation ~* ^.+.(jpg|jpeg|gif|css|png|js|ico|html|xml|txt)$ {";
			$f[]="\t\taccess_log        off;";
			$f[]="\t\texpires           max;";
			$f[]="\t}";
		}
		
		$f[]="\tssl on;";
		$f[]=$this->squidR->SaveCertificate($this->sslcertificate,false,true);
		$f[]="\tssl_session_timeout  5m;";
		$f[]="\tssl_protocols  SSLv3 TLSv1;";
		$f[]="\tssl_ciphers HIGH:!aNULL:!MD5;";
		$f[]="\tssl_prefer_server_ciphers   on;";
		
		

		if(!$AsProxy){
			$php_fpm=$this->php_fpm();
			$f[]=$php_fpm;
		}else{
			$f[]=$this->ROOT_PROXY();
		}
		$f[]=$this->error_pages();
		$f[]="}";
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, $this->servername: ".count($f)." parameters\n";}
		return @implode("\n", $f);
		
	}
	
	private function QuoteStringSpaces($string){
		if(strpos($string," ")==0){return $string;}
		$string=str_replace("'", "\'", $string);
		return "'$string'";
	}
	
	private function replace_rules_single_domain(){
		if(!$this->IsSubstitutions()){return null;}
		$q=new mysql_squid_builder();
		$f=array();
		$sql="SELECT * FROM nginx_replace_www WHERE servername='$this->servername'";
		$results=$q->QUERY_SQL($sql);
		if(mysql_num_rows($results)==0){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, $this->servername: no main replace rule\n";}
		}
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$ligne["stringtosearch"]=stripslashes($ligne["stringtosearch"]);
			$ligne["stringtosearch"]=$this->QuoteStringSpaces($ligne["stringtosearch"]);
				
			$ligne["replaceby"]=stripslashes($ligne["replaceby"]);
			$ligne["replaceby"]=$this->QuoteStringSpaces($ligne["replaceby"]);

			$f[]="\t\tsubs_filter {$ligne["stringtosearch"]}  {$ligne["replaceby"]} {$ligne["tokens"]};";
			
		}
		
		
		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT replaceid FROM reverse_www WHERE servername='$this->servername'"));
		
		if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, *** FATAL *** $q->mysql_error\n";}}
		if(!is_numeric($ligne["replaceid"])){$ligne["replaceid"]=0;}
		if($ligne["replaceid"]>0){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, $this->servername: no replace rule ({$ligne["replaceid"]})\n";}
			if($GLOBALS["VERBOSE"]){echo "SELECT replaceid FROM reverse_www WHERE servername='$this->servername'\n";}
			$a=$this->replace_rules_build($ligne["replaceid"]);
			if($a<>null){$f[]=$a;}
		}
		
		if(count($f)>0){
			return @implode("\n", $f);
		}
		 
		
	}
	
	private function replace_rules_build($id){
		if(!is_numeric($id)){return;}
		if($id==0){return;}
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL("SELECT * FROM nginx_replace WHERE groupid='$id'");
		$t=array();
		
		if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, *** FATAL *** $q->mysql_error\n";}}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, $this->servername: Replace Group id:$id ". mysql_num_rows($results)." rules\n";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			
		if(trim($ligne["stringtosearch"])==null){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, $this->servername: Replace rule id:{$ligne["ID"]} no search pattern\n";}
			return;}
			$ligne["stringtosearch"]=stripslashes($ligne["stringtosearch"]);
			$ligne["stringtosearch"]=str_replace("\"", '\"', $ligne["stringtosearch"]);
			
			$ligne["replaceby"]=stripslashes($ligne["replaceby"]);
			$ligne["replaceby"]=str_replace("\"", '\"', $ligne["replaceby"]);
			if($GLOBALS["VERBOSE"]){echo "\"{$ligne["stringtosearch"]}\"\n";}
			$t[]="\t\tsubs_filter \"{$ligne["stringtosearch"]}\" \"{$ligne["replaceby"]}\" gi;";
		}
		
		if(count($t)==0){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, $this->servername: Replace Group id:$id no rule\n";}
			return null;}
		return @implode("\n", $t);		
		
		
		
		
	}
	private function ChekPool($ReverseProxyPattern){
		$q=new mysql_squid_builder();
		$http="http";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT poolname FROM nginx_pools WHERE ID='$this->poolid'"));
		$upstream_name=$ligne["poolname"];
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, Load-balancing: [$this->poolid] \"$upstream_name\"\n";}
		
		if(!isset($GLOBALS["upstream_available"][$upstream_name])){return $ReverseProxyPattern;}
		if($this->OWA){$http="https";}
		return "$http://$upstream_name";
		
	}
	
	private function ROOT_PROXY(){
		$HostHeader="\$host";
		$PortTXT=null;
		if($this->forceddomain<>null){$HostHeader="$this->forceddomain";}
		$http_prefix="http";
		if($this->proxy_to_port==80){$http_prefix="http";$this->proxy_to_port=null;}
		if($this->proxy_to_port==443){$http_prefix="https";$this->proxy_to_port=null;}
		if($this->proxy_scheme<>null){$http_prefix=$this->proxy_scheme;}
		if($this->IsAFreeWeb){
			$this->proxy_to_host="127.0.0.1";
			if($this->isFreeWebSSL()){$http_prefix="https";$this->proxy_to_port=447;}
			
		}
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, $this->servername Proxy to: $this->proxy_to_host, suffix $this->proxy_suffix\n";} 
		
		if($this->proxy_to_port<>null){
			$PortTXT=":$this->proxy_to_port";
		}
		
		$ReverseProxyPattern="{$http_prefix}://$this->proxy_to_host$PortTXT$this->proxy_suffix";
		if($this->poolid>0){
			$ReverseProxyPattern=$this->ChekPool($ReverseProxyPattern);
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, $this->servername source id: $this->cache_peer_id\n";}
		
		if($this->cache_peer_id>0){
			if($this->IsForceRedirect()){
				$proxy_redirect="proxy_redirect   $ReverseProxyPattern/ /;";
			}
		}
		
		
		$f[]="\tproxy_cache_key \$scheme://\$host\$uri;";
		$f[]="";
		if($this->OWA){	$f[]="\tproxy_hide_header \"Vary\";";}
		if($this->OWA){	$f[]="\tadd_header \"Vary\" \"Accept-Encoding\";";}
		if($this->OWA){	$f[]="\tproxy_redirect off;";}
		if($this->OWA){	$f[]="\tproxy_buffering off;";}
		if($this->OWA){	$f[]="\tproxy_read_timeout 3600;";}
		if($this->OWA){	$f[]="\tproxy_pass_header Date;";}
		if($this->OWA){	$f[]="\tproxy_pass_header Server;";}
		if($this->OWA){	$f[]="\tproxy_set_header Connection \"\";";}
		if($this->OWA){	$f[]="\tproxy_set_header Accept-Encoding \"\";";}
		if($this->OWA){	$f[]="\tproxy_set_header X-Forwarded-Proto off;";}
		if($this->OWA){	$f[]="\tadd_header Front-End-Https on;";}
		if($this->OWA){	$f[]="\tproxy_http_version 1.1;";}

		$f[]="\tproxy_set_header Host $HostHeader;";
		$f[]="\tproxy_set_header\tX-Forwarded-For\t\$proxy_add_x_forwarded_for;";
		$f[]="\tproxy_set_header\tX-Real-IP\t\$remote_addr;";
		if(!$this->OWA){
			if($this->ssl==1){$f[]="\tproxy_set_header X-Forwarded-Ssl on;";}
		}	


		
		
		$f[]="\tlocation /nginx_status {";
		$f[]="\t\tstub_status on;";
		$f[]="\t\taccess_log   off;";
		$f[]="\t\tallow 127.0.0.1;";
		$f[]="\t\tdeny all;";
		$f[]="\t\t}";
		
		
		
		if($this->OWA){
			//$f[]="\tproxy_set_header		Destination \$http_destination;";
			//$f[]="\tproxy_http_version 1.1;";
			$f[]="\tproxy_set_header Accept-Encoding \"\";";
			$f[]="\tlocation ~* ^/Autodiscover { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/exchange { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/ecp { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/Rpc { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/owa {";
			$f[]="\t\tproxy_pass $ReverseProxyPattern;"; 
			$f[]="\t}";
			$f[]="\tlocation ~* ^/exchweb  { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/public   { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/Public   { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/OAB   { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/mailarchiver { proxy_pass $ReverseProxyPattern; }";
			$f[]="\tlocation ~* ^/Microsoft-Server-ActiveSync { proxy_pass $ReverseProxyPattern; }";	
			$f[]="location ~*/$ {	return 301 https://$this->servername$this->HostPortLNK/owa/auth/logon.aspx?replaceCurrent=1&url=https%3a%2f%2f$this->servername$this->HostPortLNK%2fowa%2f; }";
			$f[]=$this->http_locations($this->servername);
			return @implode("\n", $f);
		}
		
		
		$subs_filter_types=false;
		$f[]=$this->http_locations($this->servername);
		$f[]="\tlocation / {";
		if($this->forceddomain<>null){
			if($this->IsSubstitutions()){
				$f[]="\t\tproxy_set_header Accept-Encoding \"\";";
				$f[]="\t\tsubs_filter_types text/html text/css text/xml;";
				$subs_filter_types=true;
				$f[]="\t\tsubs_filter $this->proxy_to_host $this->servername gi;";
				if(preg_match("#^www\.(.+)#", $this->proxy_to_host,$re)){
					$f[]="\t\tsubs_filter {$re[1]} $this->servername;";
				}
			}
		}
		
		$replace_rules=$this->replace_rules_single_domain();
		
		
		if($replace_rules<>null){
			if(!$subs_filter_types){
				$f[]="\t\tproxy_set_header Accept-Encoding \"\";";
				$f[]="\t\tsubs_filter_types text/html text/css text/xml;";				
			}
			$f[]=$replace_rules;
		}
		$proxy_cache=$this->keys_zone($this->storeid);
		if($proxy_cache<>null){$f[]=$proxy_cache;}
		$f[]="\t\tproxy_pass $ReverseProxyPattern;";
		
		if($proxy_redirect<>null){$f[]="\t\t$proxy_redirect";}
		$f[]="\t}";
		$f[]="";
		
		return @implode("\n", $f);
		
	}
	
	private function spawn_fcgi_parameters(){
		$unix=new unix();
		$spawn_fcgi=$unix->find_program("spawn-fcgi");
		
		
	}
	
	
	private function spawn_fcgi(){
		$unix=new unix();
		$spawn_fcgi=$unix->find_program("spawn-fcgi");
		if(!is_file($spawn_fcgi)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, FATAL no spawn-php can be enabled !\n";}
			return false;
		}
		$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();
		
		if(!is_file($phpcgi)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, FATAL no php-cgi can be enabled !\n";}
			return false;			
		}
		
		$unix->chmod_func(0777, "/var/run");
		
		$pid=$unix->get_pid_from_file("/var/run/spawn-fcgi.pid");
		$f[]=$spawn_fcgi;
		$f[]="-s /var/run/php-fcgi.sock";
		$f[]="-u www-data -g www-data -C 3";
		$f[]="-f $phpcgi";
		$f[]="-P /var/run/spawn-fcgi.pid";
		if(!$unix->process_exists($pid)){
			shell_exec(@implode(" ", $f));
			sleep(1);
		}
		$pid=$unix->get_pid_from_file("/var/run/spawn-fcgi.pid");
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, PHP cgi spawned pid:$pid\n";}
			return true;
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, PHP cgi spawned failed\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: `".@implode(" ", $f)."`\n";}
		}
		
		return true;
		
	}
	
	private function spawn_fcgi_config(){
		if(!$this->spawn_fcgi()){return null;}
		
		$this->fastcgi_params();
		$index="index.php";
		if($this->IndexFile<>null){ $index=$this->IndexFile;}		
		$f[]="\tlocation ~ [^/]\.php(/|\$) {";
		$f[]="\t\tfastcgi_split_path_info ^(.+?\.php)(/.*)\$;";
		$f[]="\t\tif (!-f \$document_root\$fastcgi_script_name) {";
		$f[]="\t\t\treturn 404;";
		$f[]="\t\t}";
		$f[]=" ";
		$f[]="\t\tfastcgi_pass unix:/var/run/php-fcgi.sock;";
		$f[]="\t\tfastcgi_index $index;";
		$f[]="\t\tinclude fastcgi_params;";
		$f[]="\t}";
		
		return @implode("\n", $f);		

		
		
	}
	
	private function php_fpm(){
		$unix=new unix();
		$sock=new sockets();
		$phpfpm=$unix->APACHE_LOCATE_PHP_FPM();
		$EnablePHPFPM=$sock->GET_INFO("EnablePHPFPM");
		if(!is_numeric($EnablePHPFPM)){$EnablePHPFPM=1;}
		if(!is_file($phpfpm)){$EnablePHPFPM=0;}
		if($EnablePHPFPM==0){return $this->spawn_fcgi_config();}
		$this->fastcgi_params();
		$index="index.php";
		if($this->IndexFile<>null){ $index=$this->IndexFile;}
			$f[]="\tlocation ~ [^/]\.php(/|\$) {";
			$f[]="\t\tfastcgi_split_path_info ^(.+?\.php)(/.*)\$;";
			$f[]="\t\tif (!-f \$document_root\$fastcgi_script_name) {";
			$f[]="\t\t\treturn 404;";
			$f[]="\t\t}";
			$f[]=" ";
			$f[]="\t\tfastcgi_pass unix:/var/run/php-fpm.sock;";
			$f[]="\t\tfastcgi_index $index;";
			$f[]="\t\tinclude fastcgi_params;";
			$f[]="\t}";	

		return @implode("\n", $f);
		
	}
	
	private function fastcgi_params(){
		$f[]="fastcgi_param   QUERY_STRING            \$query_string;";
		$f[]="fastcgi_param   REQUEST_METHOD          \$request_method;";
		$f[]="fastcgi_param   CONTENT_TYPE            \$content_type;";
		$f[]="fastcgi_param   CONTENT_LENGTH          \$content_length;";
		$f[]=" ";
		$f[]="fastcgi_param   SCRIPT_FILENAME         \$document_root\$fastcgi_script_name;";
		$f[]="fastcgi_param   SCRIPT_NAME             \$fastcgi_script_name;";
		$f[]="fastcgi_param   PATH_INFO               \$fastcgi_path_info;";
		$f[]="fastcgi_param   REQUEST_URI             \$request_uri;";
		$f[]="fastcgi_param   DOCUMENT_URI            \$document_uri;";
		$f[]="fastcgi_param   DOCUMENT_ROOT           \$document_root;";
		$f[]="fastcgi_param   SERVER_PROTOCOL         \$server_protocol;";
		$f[]=" ";
		$f[]="fastcgi_param   GATEWAY_INTERFACE       CGI/1.1;";
		$f[]="fastcgi_param   SERVER_SOFTWARE         nginx/\$nginx_version;";
		$f[]=" ";
		$f[]="fastcgi_param   REMOTE_ADDR             \$remote_addr;";
		$f[]="fastcgi_param   REMOTE_PORT             \$remote_port;";
		$f[]="fastcgi_param   SERVER_ADDR             \$server_addr;";
		$f[]="fastcgi_param   SERVER_PORT             \$server_port;";
		$f[]="fastcgi_param   SERVER_NAME             \$server_name;";
		$f[]=" ";
		$f[]="fastcgi_param   HTTPS                   \$server_https;";
		$f[]=" ";
		$f[]="# PHP only, required if PHP was built with --enable-force-cgi-redirect";
		$f[]="fastcgi_param   REDIRECT_STATUS         200;";
		@file_put_contents("/etc/nginx/fastcgi_params", @implode("\n", $f));
		$f=array();
		$f[]="fastcgi_split_path_info ^(.+\.php)(.*)$;";
		$f[]="fastcgi_param PATH_TRANSLATED \$document_root\$fastcgi_path_info;";
		@file_put_contents("/etc/nginx/fastcgi_params2", @implode("\n", $f));		
	}
	
	
	
}

class nginx_upstream{
	public $upstream_available=array();
	
	public function build(){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM nginx_pools ORDER BY poolname";
		$results=$q->QUERY_SQL($sql);
		if(mysql_num_rows($results)==0){return;}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: [INIT]: nginx, Load-balancing [{$ligne["poolname"]}]\n";}
			$upstream_name=$ligne["poolname"];
			$keepaliveTXT=null;
			$poolid=$ligne["ID"];
			if(!is_numeric($ligne["keepalive"])){$ligne["keepalive"]=0;}
			$data=$this->upstreams_server($poolid,$ligne["hashtype"]);
			if($data==null){continue;}
			$this->upstream_available[$upstream_name]=true;
			$GLOBALS["upstream_available"][$upstream_name]=true;
			if($ligne["keepalive"]>0){$keepaliveTXT=" keepalive {$ligne["keepalive"]}";}
			$f[]="upstream \"$upstream_name\" {";
			$f[]=$data;
			if($ligne["hashtype"]<>null){$f[]="{$ligne["hashtype"]}";}
			if($keepaliveTXT<>null){$f[]="$keepaliveTXT";}
			$f[]="}";
			
			
		}
		
		if(count($f)>0){return @implode("\n", $f);}
		
	}
	
	private function buildsource($sourceid){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_sources WHERE ID='$sourceid'"));
		if($ligne["enabled"]==0){return;}
		
		$port=$ligne["port"];
		$servername=$ligne["servername"];
		$ipaddr=$ligne["ipaddr"];
		$ssl=$ligne["ssl"];
		if(!is_numeric($port)){$port=0;}
		if($port==0){if($ssl==1){$port=443;}}
		
		if($port>0){return "$ipaddr:$port";}
		return $ipaddr;
		
	}
	
	private function upstreams_server($poolid,$hashtype){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM nginx_pools_list WHERE poolid=$poolid ORDER BY zorder DESC";
		$results=$q->QUERY_SQL($sql);
		if(mysql_num_rows($results)==0){return;}

		$Weight=0;
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$sourceid=$ligne["sourceid"];
			$lineServer=$this->buildsource($sourceid);
			if($lineServer==null){continue;}
			$Weight++;
			$max_fails=$ligne["max_fails"];
			$fail_timeout=$ligne["fail_timeout"];
			$max_failsTXT=null;
			$fail_timeoutTXT=null;
			$WeightTXT=null;
			if(($hashtype==null) OR ($hashtype=="least_conn")){
				$WeightTXT=" weight=$Weight";
				if($max_fails>0){$max_failsTXT=" max_fails=$max_fails";$WeightTXT=null;}
				if($fail_timeout>0){$fail_timeoutTXT=" fail_timeout={$fail_timeout}s";$WeightTXT=null;}
				$f[]="\tserver $lineServer$WeightTXT$max_failsTXT$fail_timeoutTXT;";
			}
			
			if($hashtype=="ip_hash"){
				$f[]="\tserver $lineServer {$ligne["backuptype"]};";
			}
			
		}

		if(count($f)>0){
			return @implode("\n", $f);
		}
	
	}

}
