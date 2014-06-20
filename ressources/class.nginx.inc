<?php
	include_once(dirname(__FILE__).'/class.squid.inc');
	include_once(dirname(__FILE__).'/class.nginx-sources.inc');
	include_once(dirname(__FILE__).'/class.tcpip.inc');
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
	private $proxy_cache_valid=4320;
	private $NginxProxyStorePath=null;
	private $RedirectQueries=null;
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
	private $BuildFramework=false;
	private $SargDir=null;
	private $OWA=false;
	private $EnableHSTS=0;
	private $RestrictIFrames=0;
	private $XSSBrowser=0;
	private $EnableCSP=0;
	private $ClassFreeWeb=null;
	private $default_server=0;
	private $ssl_protocols=null;
	private $ssl_ciphers=null;
	private $LimitCnx=0;
	private $LimitReqs=0;
	public $NoErrorPages=false;
	public $proxy_read_timeout=300;
	public $proxy_send_timeout=300;
	public $proxy_buffering=1;
	public $proxy_cache_min_uses=1;
	public $proxy_buffer_size=8;
	public $proxy_buffers="16 32k";
	public $file_settings_path;
	public $BackupBefore=false;
	
	function nginx($servername=null){
		$this->unix=new unix();
		if(preg_match("#([0-9\.]+):([0-9]+)#", $servername,$re)){$servername=$re[1];$this->host_port=$re[2];}
		
		if(is_numeric($servername)){
			$this->host_port=$servername;
			if($GLOBALS["VERBOSE"]){echo "[DEBUG]: '$servername'-> host_port:'$servername'\n";}
			
		}else{
			if($servername<>null){$this->servername=$servername;}
			
		}
		$this->squidR=new squidbee();
		$sock=new sockets();
		$this->ClassFreeWeb=new freeweb($this->servername);
		$this->IndexFile=$this->ClassFreeWeb->DirectoryIndex();
		$this->NginxProxyStorePath=$sock->GET_INFO("NginxProxyStorePath");
		if($this->NginxProxyStorePath==null){$this->NginxProxyStorePath="/home/nginx";}
		$this->APACHE_USER=$this->unix->APACHE_SRC_ACCOUNT();
		$this->APACHE_SRC_GROUP=$this->unix->APACHE_SRC_GROUP();
		if($this->APACHE_USER==null){$this->APACHE_USER="www-data";}
		if($this->APACHE_SRC_GROUP==null){$this->APACHE_SRC_GROUP="www-data";}
		if(class_exists("unix")){$this->LoadParams();}
		$this->LoadParams();
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
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT start_directory FROM reverse_www WHERE servername='$this->servername'"));
		if($ligne["start_directory"]<>null){$this->proxy_suffix="/{$ligne["start_directory"]}/";}
		
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
		$key="$this->servername:$this->host_port";
		if(isset($GLOBALS[__CLASS__][__FUNCTION__][$key])){return;}
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.":Loading\n";}
		$unix=new unix();
		$this->CompileParams=$unix->NGINX_COMPILE_PARAMS();
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.":Done...\n";}
		$this->build_file_path();
		if($this->servername<>null){
			
			$q=new mysql_squid_builder();
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$this->servername'"));
			$this->proxy_read_timeout=$ligne["proxy_read_timeout"];
			$this->proxy_send_timeout=$ligne["proxy_send_timeout"];
			$this->proxy_cache_valid=$ligne["proxy_cache_valid"];
			$this->proxy_buffering=$ligne["proxy_buffering"];
			$this->proxy_cache_min_uses=$ligne["proxy_cache_min_uses"];
			$this->proxy_buffer_size=$ligne["proxy_buffer_size"];
			$this->proxy_cache_min_uses=$ligne["proxy_cache_min_uses"];
			$this->proxy_buffers=$ligne["proxy_buffers"];
			$this->default_server=$ligne["default_server"];
			$this->EnableHSTS=$ligne["EnableHSTS"];
			$this->RestrictIFrames=$ligne["RestrictIFrames"];
			$this->XSSBrowser=$ligne["XSSBrowser"];
			$this->EnableCSP=$ligne["EnableCSP"];
			$this->ssl_protocols=$ligne["ssl_protocols"];
			$this->ssl_ciphers=$ligne["ssl_ciphers"];
			$this->LimitCnx=$ligne["LimitCnx"];
			$this->LimitReqs=$ligne["LimitReqs"];
			if($this->ssl_protocols==null){$this->ssl_protocols="TLSv1 TLSv1.1 TLSv1.2";}
			if($this->ssl_ciphers==null){$this->ssl_ciphers="ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK";}
			if(!is_numeric($this->proxy_cache_valid)){$this->proxy_cache_valid=4320;}
			
		$GLOBALS[__CLASS__][__FUNCTION__][$key]=true;
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername/".__LINE__." default_server = {$ligne["default_server"]}\n";}
			
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, no servername aborting LoadParams()\n";}
		}
		
		if($this->IsMiscModule()){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername MISC module OK\n";}
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername MISC module Failed\n";}
		}
		
	}
	
	public function set_proxy_port($port=null){
		if(!is_numeric($port)){return;}
		if($port==0){return;}
		if($port<0){return;}
		$this->proxy_to_port=$port;
	}	
	
	public function set_RedirectQueries($url=null){
		$this->RedirectQueries=$url;
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
	
	public function SargDir(){
		$sock=new sockets();
		$SargOutputDir=$sock->GET_INFO("SargOutputDir");
		if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
		$this->SargDir=$SargOutputDir;
	}
	
	public function set_freeweb(){
		$this->IsAFreeWeb=true;
	}
	
	public function set_framework(){
		$this->BuildFramework=true;
	}	
	
	public function set_cache_peer_id($ID=0){
		if(!is_numeric($ID)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, set_cache_peer_id($ID) : False\n";}
			return;}
		if($ID>0){
			$this->cache_peer_id=$ID;
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, set_cache_peer_id($ID) : True\n";}
			return;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, set_cache_peer_id($ID) : False\n";}
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
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,$ipaddr not a valid ip address\n";}
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
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, ".count($ifconfig_all_ips)." network addresses\n";}
		while (list ($a, $b) = each ($ifconfig_all_ips) ){$ips[]=$a;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, host:{$ips[0]}\n";}
		$GLOBALS["FillHostnameAsIP"]=$ips[0];
		$this->servername=$ips[0];		
		return $GLOBALS["FillHostnameAsIP"];
	}
	
	public function IsSubstitutions(){
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
	
	private function IsLdapModule(){
		if(isset($GLOBALS["IsLdapModule"])){return $GLOBALS["IsLdapModule"];}
		$GLOBALS["IsLdapModule"]=false;
		$ARRAY=$this->CompileParams;
		while (list ($a, $b) = each ($ARRAY["MODULES"]) ){
			if(preg_match("#http_auth_ldap_module#", $a)){
				$GLOBALS["IsLdapModule"]=true;
				break;
			}
	
		}
		return $GLOBALS["IsLdapModule"];
	}
	private function IsMiscModule(){
		if(isset($GLOBALS["IsMiscModule"])){return $GLOBALS["IsMiscModule"];}
		$ARRAY=$this->CompileParams;
		$GLOBALS["IsMiscModule"]=false;
		while (list ($a, $b) = each ($ARRAY["MODULES"]) ){
			if(preg_match("#misc-nginx-module#", $a)){
				$GLOBALS["IsMiscModule"]=true;
				break;
			}
		
		}
		return $GLOBALS["IsMiscModule"];
	}
	
	
	private function IsAutheRequestsModule(){
		if(isset($GLOBALS["IsAutheRequestsModule"])){return $GLOBALS["IsAutheRequestsModule"];}
		$GLOBALS["IsAutheRequestsModule"]=false;
		$ARRAY=$this->CompileParams;
		while (list ($a, $b) = each ($ARRAY["MODULES"]) ){
			if(preg_match("#auth-request-nginx-module#", $a)){
				$GLOBALS["IsAutheRequestsModule"]=true;
				break;
			}
	
		}
		return $GLOBALS["IsAutheRequestsModule"];
	}	
	
	public function webdav_containers(){
		return null;
		if(isset($GLOBALS["webdav_containers"])){return $GLOBALS["webdav_containers"];}
		$unix=new unix();
		$NGINX_COMPILE_PARAMS=$unix->NGINX_COMPILE_PARAMS();
		
		if(!isset($NGINX_COMPILE_PARAMS["ARGS"]["WITH-HTTP_DAV_MODULE"])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, WebDav not implemented.\n";}
			$GLOBALS["webdav_containers"]="# WebDav not implemented\n";return null;
			return;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, WebDav implemented.\n";}
		$freeweb=new freeweb();
		$ls=$unix->find_program("ls");
		$sql="SELECT * FROM users_containers WHERE created=1 AND onerror=0 AND webdav=1";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, ".mysql_num_rows($results)." WebDav items...\n";}
		$apacheusername=$unix->APACHE_SRC_ACCOUNT();
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
				$ID=$ligne["container_id"];
				$MountedPath="/media/artica_containers/membersdisks/disk$ID";
				$RootDir="/media/artica_containers/membersdisks";
				shell_exec("$ls $MountedPath/*");
				if(!is_dir("$MountedPath/WebShared")){@mkdir("$MountedPath/WebShared",0755,true);}
				@chown("$MountedPath/WebShared", $apacheusername);
				@mkdir("/etc/htpass",0755,true);
				echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, WebDav `$MountedPath`\n";
				$f[]="\tlocation ~ /disk$ID/(.*?)$ {";
				$f[]="\t\tautoindex on;";
				$f[]="\t\talias\t$MountedPath/WebShared/$1;";
				//$f[]="\t\troot $MountedPath/WebShared$1;";
				$f[]="\t\tdav_methods PUT DELETE MKCOL COPY MOVE;";
				$f[]="\t\tcreate_full_put_path  on;";
				$f[]="\t\tclient_body_temp_path           $MountedPath/WebShared/.tmp;";
				$f[]="\t\tdav_access                      user:rw  group:r  all:r;";
				$f[]="\t\tauth_basic \"{$ligne["container_name"]}\";";
				$f[]="\t\tauth_basic_user_file /etc/htpass/disk$ID;";
				$f[]="\t}";
			}
		
			$GLOBALS["webdav_containers"]=@implode("\n",$f);
			return $GLOBALS["webdav_containers"];
	}
	
	
	private function build_file_path(){
		if($this->servername==null){$this->servername=$this->FillHostnameAsIP();}
		$suffix=$this->servername;
		$suffix=str_replace("unix:", "unix-", $suffix);
		$suffix=str_replace("/", "", $suffix);
		if($this->ssl==1){
			$this->file_settings_path="/etc/nginx/sites-enabled/freewebs-ssl-$suffix.$this->host_port.conf";
		}else{
			$this->file_settings_path="/etc/nginx/sites-enabled/freewebs-$suffix.$this->host_port.conf";
		}
		
		
	}

	
	public function build_proxy(){
		@chmod("/etc/nginx/sites-enabled",0755);
		$this->LoadParams();
		if($this->sslcertificate<>null){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, using certificate $this->sslcertificate, turn to ssl\n";}
			$this->ssl=1;
		}
		
		$unix=new unix();
		if($this->servername==null){$this->servername=$this->FillHostnameAsIP();}
		
		if(isset($GLOBALS["NGINX_SERVERS_NAME"][$this->servername])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername already set\n";}
			return;
		}
		
		$suffix=$this->servername;
		$suffix=str_replace("unix:", "unix-", $suffix);
		$suffix=str_replace("/", "", $suffix);
		
		if($this->ssl==1){
			$data=$this->ssl_proxy();
			
		}else{
			$data=$this->http_proxy();
			
		}
		

		if($GLOBALS["VERBOSE"]){echo "\n$data\n";}
		
		@mkdir("/etc/nginx/sites-enabled-backuped",0755,true);
		
		if($this->BackupBefore){
			if(is_file($this->file_settings_path)){
				$file=basename($this->file_settings_path);
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, backup old file before\n";}
				@copy("/etc/nginx/nginx.conf","/etc/nginx/nginx.bak");
				@copy($this->file_settings_path, "/etc/nginx/sites-enabled-backuped/$file");
			}
		}

		@file_put_contents($this->file_settings_path,$data);
		$size=filesize($this->file_settings_path);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx,$this->file_settings_path done.($size bytes)\n";}
		
		if($this->BackupBefore){
			if(!$this->TestTheWholeConfig()){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, recover old config..\n";}
				$file=basename($this->file_settings_path);
				@copy("/etc/nginx/sites-enabled-backuped/$file",$this->file_settings_path);
				@copy("/etc/nginx/nginx.bak","/etc/nginx/nginx.conf");
			}
		}
		
		
		
	}
	
	public function TestTheWholeConfig(){
		$unix=new unix();
		$nginxbin=$unix->find_program("nginx");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, testing configuration with $nginxbin...\n";}
		$unix=new unix();
		
		exec("$nginxbin -c /etc/nginx/nginx.conf -t 2>&1",$testsR);
		$tests=false;
		while (list ($index, $line) = each ($testsR)){
			if(preg_match("#\[warn\]\s+(.+)#", $line,$re)){$line=$re[1];}
			if(preg_match("#nginx:\s+(.+)#", $line,$re)){$line=$re[1];}
			
			if(preg_match("#invalid URL prefix in\s+(.*?):([0-9]+)#",$line,$re)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $line\n";}
				$filename=$re[1];
				$filenameBase=basename($filename);
				$lineError=$re[2];
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, copy $filenameBase to /root\n";}
				@unlink("/root/$filenameBase");
				@copy($filename,"/root/$filenameBase");
				continue;
			}
			
			
			if(preg_match("#invalid number of arguments in \"(.+?)\" directive in (.+?):([0-9]+)#", $line,$re)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $line\n";}
				$arguments=$re[1];
				$filename=$re[2];
				$filenameBase=basename($filename);
				$lineError=$re[3];
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: !!!! ERROR $arguments LINE $lineError !!!!\n";}
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, copy $filenameBase to /root\n";}
				@unlink("/root/$filenameBase");
				@copy($filename,"/root/$filenameBase");
				continue;
			}			
			
			
			if(preg_match("#a duplicate default server for (.+?)\s+in\s+(.+?):#", $line,$re)){
				$filename=$re[2];
				$filenameBase=basename($filename);
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, duplicate default server {$re[1]} remove $filenameBase\n";}
				@unlink($filename);
				return $this->TestTheWholeConfig();
			}
			
			if(preg_match("#invalid URL prefix in\s+(.+?):#", $line,$re)){
				$filename=$re[1];
				$filenameBase=basename($filename);
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, invalid URL prefix remove $filenameBase\n";}
				@unlink($filename);
				return $this->TestTheWholeConfig();
			}
			

		
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $line\n";}
			if(preg_match("#configuration file.*?test is successful#", $line)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, testing configuration Success...\n";}
				$tests=true;
				break;
			}
		
		}
		return $tests;
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
	
	private function proxy_buffers_check($buffers){
		
		$logprefix="Starting......: ".date("H:i:s")." [INIT]: Buffers   : $this->servername:$this->host_port: ";
		if(!preg_match("#([0-9\.]+)\s+([0-9\.]+)#", $buffers,$re)){
			if($GLOBALS["OUTPUT"]){echo "$logprefix proxy_buffers.............: 128 16k\n";}
			if($GLOBALS["OUTPUT"]){echo "$logprefix proxy_busy_buffers_size...: 64k\n";}
			if($GLOBALS["OUTPUT"]){echo "$logprefix proxy_temp_file_write_size: 64k\n";}
			$f[]="\t\tproxy_buffers      128 16k;";
			$f[]="\t\tproxy_busy_buffers_size 64k;";
			$f[]="\t\tproxy_temp_file_write_size 64k;";
			return @implode("\n", $f);
		}
		$f[]="\t\tproxy_buffers      {$re[1]} {$re[2]}k;";
		$buffers=($re[1]-1)*$re[2];
		if($GLOBALS["OUTPUT"]){echo "$logprefix proxy_busy_buffers_size {$buffers}k;\n";}
		$f[]="\t\tproxy_busy_buffers_size {$buffers}k;";
		$f[]="\t\tproxy_temp_file_write_size {$buffers}k;";
		
		if($GLOBALS["OUTPUT"]){echo "$logprefix proxy_buffers.............: {$re[1]} {$re[2]}\n";}
		if($GLOBALS["OUTPUT"]){echo "$logprefix proxy_busy_buffers_size...: {$buffers}k\n";}
		if($GLOBALS["OUTPUT"]){echo "$logprefix proxy_temp_file_write_size: {$buffers}k\n";}
		
		/*Directive proxy_temp_file_write_size basically tells nginx how 
many data it can flush to disk at once when writing temporary 
file. It may be used to prevent nginx worker process from blocking 
for too long in io path.
*/
		
	}
	
	private function proxy_parameters($cache_peer_id=0){
		$rev=new nginx_sources($cache_peer_id);
		$logprefix="Starting......: ".date("H:i:s")." [INIT]: proxy_pass: $this->servername:$this->host_port: ";
		$f[]="\t\tproxy_read_timeout    {$rev->proxy_read_timeout}s;";
		$f[]="\t\tproxy_send_timeout	{$rev->proxy_send_timeout}s;";
		
		
		
		if($this->proxy_buffering==1){
			$f[]="\t\tproxy_buffering       on;";
			$f[]="\t\tproxy_cache_min_uses $this->proxy_cache_min_uses;";
			if($this->proxy_buffer_size>0){
				$f[]="\t\tproxy_buffer_size       {$this->proxy_buffer_size}k;";
			}else{
				$f[]="#\t\tproxy_buffer_size == 0";
			}
			
			$f[]=$this->proxy_buffers_check($this->proxy_buffers);
		}else{
			$f[]="#\t\tproxy_buffering  == 0";
		}
		
		
		
		return @implode("\n", $f)."\n";
	}
	
	private function strip_slashes($directory){
		if(preg_match("#^\/(.+?)\/$#",$directory,$re)){return $re[1];}
		if(preg_match("#^\/(.+?)$#",$directory,$re)){return $re[1];}
		if(preg_match("#^(.+?)\/$#",$directory,$re)){return $re[1];}
		return $directory;
		
	}
	
	private function proxy_pass_pattern($pattern){
		
		if(!preg_match("#^http(.*?):#",$pattern)){
			$pattern="http://$pattern";
		}
		
		$urlP=parse_url($pattern);
		return "{$urlP["scheme"]}://{$urlP["host"]}";
		
		
		
		
	}
	
	
	
	private function proxy_pass($cache_peer_id=null,$asregex=false,$folderid=0,$directory=null){
		$q=new mysql_squid_builder();
		$suffix=null;
		$http_prefix=null;
		$logprefix="Starting......: ".date("H:i:s")." [INIT]: proxy_pass: $this->servername:$this->host_port: Proxy:[$folderid]";
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT start_directory FROM reverse_www WHERE servername='$this->servername'"));
		$start_directory=$ligne["start_directory"];
		if($start_directory<>null){$start_directory="$start_directory/";}
		if($GLOBALS["OUTPUT"]){echo "$logprefix start_directory `$start_directory`\n";}
		
		$f[]="# proxy_pass() Line:".__LINE__." cache_peer_id=$cache_peer_id; folderid=$folderid";
		
		
		if($folderid>0){
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_dirs WHERE folderid='$folderid'"));
			$hostweb=$ligne["hostweb"];
			$f[]="# proxy_pass() Line:".__LINE__." folderid=$folderid hostweb=`$hostweb`,Local={$ligne["local"]}";
			
			if($hostweb<>null){
				if($GLOBALS["OUTPUT"]){echo "hostweb = `$hostweb`\n";}
				if($ligne["local"]==0){
					if(substr($hostweb, 0,1)=="/"){$suffix=$hostweb;}
					$hostweb=$this->proxy_pass_pattern($hostweb);
					$f[]="\t\tproxy_pass $hostweb;";	
					if($ligne["ForceRedirect"]==1){$f[]="\t\tproxy_redirect  $hostweb/   /;";}
					
				}
				
				return @implode("\n", $f);
			}
		}
		
		$f[]="# proxy_pass() Line:".__LINE__." cache_peer_id=$cache_peer_id; folderid=0";
		
		if(!is_numeric($cache_peer_id)){
			if($GLOBALS["OUTPUT"]){echo "$logprefix cache_peer_id is not numeric, aborting\n";}
			return null;
		}
		
		if($cache_peer_id==0){
			if($GLOBALS["OUTPUT"]){echo "$logprefix cache_peer_id = 0 aborting\n";}
			return null;
		}		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_sources WHERE ID='$cache_peer_id'"));
		$ipaddr=trim($ligne["ipaddr"]);
		if(preg_match("#^(http|ftp|https|ftps|smtp|smtps):\/\/(.+)#", $ipaddr,$re)){
			$ipaddr=$re[2];
			$http_prefix=$re[1];
		}
		
		
		$ssl=$ligne["ssl"];
		$port=$ligne["port"];
		if(!is_numeric($port)){$port=80;}
		$port_suffix=":$port";
		if($http_prefix==null){
			$http_prefix="https";
			if($port==80){$http_prefix="http";$port_suffix=null;}
			if($ssl==0){$http_prefix="http";$port_suffix=null;}
		}
		
		
		if($ipaddr==null){return null;}
		$slash="/";
		$directoryStriped=null;
		
		if($directory<>null){
			$directoryStriped=$this->strip_slashes($directory)."/";
			if($GLOBALS["OUTPUT"]){echo "$logprefix $directory = \"$directoryStriped\"\n";}
		}
		if($asregex){$slash=null;}
		
		
		
		if($GLOBALS["OUTPUT"]){echo "$logprefix Pass to = `{$http_prefix}://$ipaddr$port_suffix$suffix`\n";}
		
		if($this->IsSubstitutions()){
			if($directory<>null){
				if(strpos(" $directory", "~")==0){
					
					$f[]="\t\tsubs_filter ". str_replace(".", "\.", $ipaddr)." $directory gi;";
				}
			}else{
				$f[]="\t\tsubs_filter ". str_replace(".", "\.", $ipaddr)." $this->servername;";
			}
		}
		
		
		$proxy_pass_suffix=$suffix.$start_directory;
		$proxy_pass_suffix=str_replace("//", "/", $proxy_pass_suffix);
		if($GLOBALS["OUTPUT"]){echo "$logprefix Suffix = `$proxy_pass_suffix`\n";}
		
		$f[]="\t\tproxy_pass {$http_prefix}://$ipaddr$port_suffix$proxy_pass_suffix;";
		
		if($GLOBALS["OUTPUT"]){echo "$logprefix ForceRedirect = {$ligne["ForceRedirect"]}\n";}
		if($ligne["ForceRedirect"]==1){
			$f[]="\t\tproxy_redirect   {$http_prefix}://$ipaddr$port_suffix$suffix/   /$directoryStriped;";
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
	
	private function isDebug(){
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("reverse_www", "debug")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `debug` smallint(1) NOT NULL DEFAULT 0");return false;}
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `debug` FROM reverse_www WHERE servername='$this->servername'"));
		if($ligne["debug"]==1){return true;}
		return false;
	}
	
	
	private function AUTHENTICATOR_DIRECTORY($authenticator_id,$directoryname){
		if(!is_numeric($authenticator_id)){return;}
		if($authenticator_id==0){return;}
		$q=new mysql_squid_builder();
		$directoryname=$this->strip_slashes($directoryname);
		$sock=new sockets();
		$NginxAuthPort=$sock->GET_INFO("NginxAuthPort");
		if($NginxAuthPort==null){$NginxAuthPort="unix:/var/run/nginx-authenticator.sock";}
		
		if(preg_match("#^unix#", $NginxAuthPort)){
			$array["REMOTE_FRAMEWORK"]="http://$NginxAuthPort:/authenticator.php";
		}else{
			$array["REMOTE_FRAMEWORK"]="http://$NginxAuthPort/authenticator.php";
		}
		
		$groupes=$this->AUTHENTICATOR_GROUPS($authenticator_id);
		$f[]="\n\t\tlocation = /$directoryname/ARTICAAUTHENTICATOR$authenticator_id {";
		$f[]="\t\t\tproxy_pass {$array["REMOTE_FRAMEWORK"]}?uri=\$request_uri$groupes&servername=\$server_name;";
		$f[]="\t\t}\n";
		return @implode("\n", $f);
	}
	
	
	private function AUTHENTICATOR_PROXY(){
		$q=new mysql_squid_builder();
		$sock=new sockets();
		
		
		if(!$q->FIELD_EXISTS("reverse_www","webauth")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD webauth TEXT");}
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `webauth` FROM reverse_www WHERE servername='$this->servername'"));
		$array=unserialize(base64_decode($ligne["webauth"]));
		if(!$this->IsAutheRequestsModule()){$array["USE_AUTHENTICATOR"]=0;}
		
		if(!is_numeric($array["USE_AUTHENTICATOR"])){$array["USE_AUTHENTICATOR"]=0;}
		if(!is_numeric($array["AUTHENTICATOR_RULEID"])){$array["AUTHENTICATOR_RULEID"]=0;}
		if($array["AUTHENTICATOR_RULEID"]==0){$array["USE_AUTHENTICATOR"]=0;}
		

		if($array["USE_AUTHENTICATOR"]==0){return null;}
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM authenticator_rules WHERE ID='{$array["AUTHENTICATOR_RULEID"]}'"));
		if($ligne["enabled"]==0){$array["USE_AUTHENTICATOR"]=0;}
		
		
		
		if($array["USE_AUTHENTICATOR"]==0){return null;}
		$NginxAuthPort=$sock->GET_INFO("NginxAuthPort");
		if($NginxAuthPort==null){
			$NginxAuthPort="unix:/var/run/nginx-authenticator.sock";
			$sock->SET_INFO("NginxAuthPort",$NginxAuthPort);
		}
		
		
		
		if(preg_match("#^unix#", $NginxAuthPort)){
			$array["REMOTE_FRAMEWORK"]="http://$NginxAuthPort:/authenticator.php";
		}else{
			$array["REMOTE_FRAMEWORK"]="http://$NginxAuthPort/authenticator.php";
		}
		
		$array["LDAP_BANNER"]=urlencode(base64_encode($array["LDAP_BANNER"]));
		
		$groupes=$this->AUTHENTICATOR_GROUPS($array["AUTHENTICATOR_RULEID"]);

		$f[]="\tlocation = /ARTICAAUTHENTICATOR {";
		$f[]="\t\tproxy_pass {$array["REMOTE_FRAMEWORK"]}?uri=\$request_uri$groupes&servername=\$server_name&banner={$array["LDAP_BANNER"]};";
		$f[]="\t}";
		return @implode("\n", $f);
				
		
	}
	
	
	private function AUTHENTICATOR_GROUPS($ruleid){
		$q=new mysql_squid_builder();
		
		$sql="SELECT cachetime FROM authenticator_rules WHERE ID='$ruleid'";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
		$cachetime=$ligne["cachetime"];
		$sesskey=time();
		
		$sql="SELECT
		authenticator_sourceslnk.ID,
		authenticator_sourceslnk.zorder,
		authenticator_sourceslnk.groupid,
		authenticator_groups.groupname,
		authenticator_groups.group_type,
		authenticator_groups.enabled
		FROM authenticator_sourceslnk,authenticator_groups
		WHERE authenticator_sourceslnk.ruleid='$ruleid'
		AND authenticator_sourceslnk.groupid=authenticator_groups.ID
		AND authenticator_groups.enabled=1
		ORDER BY zorder";
		
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){
			echo "\n   ***** \nStarting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername FATAL $q->mysql_error\n   ***** \n";
		}
		
		echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Authenticator rule $ruleid ". mysql_num_rows($results)." groups\n";
		
		while ($ligne = mysql_fetch_assoc($results)) {
			echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Authenticator {$ligne["groupid"]} = {$ligne["group_type"]}\n";
			$GPS[$ligne["ID"]]=$ligne["group_type"];
		}
		
		$sql="SELECT
		authenticator_authlnk.ID,
		authenticator_authlnk.zorder,
		authenticator_auth.groupname,
		authenticator_auth.group_type,
		authenticator_authlnk.groupid
		FROM authenticator_authlnk,authenticator_auth
		WHERE authenticator_authlnk.ruleid='$ruleid' 
		AND authenticator_authlnk.groupid=authenticator_auth.ID";
		
		$results=$q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$DES[$ligne["ID"]]=$ligne["group_type"];
		}
		
		$gps_encoded=base64_encode(serialize($GPS));
		$des_encoded=base64_encode(serialize($DES));
		return "&ruleid=$ruleid&sesskey=$sesskey&gps=$gps_encoded&des=$des_encoded&cachetime=$cachetime";
	}
	
	
	private function AUTHENTICATOR_BASE(){
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("reverse_www","webauth")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD webauth TEXT");}
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `webauth` FROM reverse_www WHERE servername='$this->servername'"));
		$array=unserialize(base64_decode($ligne["webauth"]));
		if(!$this->IsAutheRequestsModule()){$array["USE_AUTHENTICATOR"]=0;}
		
		if(!is_numeric($array["USE_AUTHENTICATOR"])){$array["USE_AUTHENTICATOR"]=0;}
		if(!is_numeric($array["USE_REMOTE_FRAMEWORK"])){$array["USE_REMOTE_FRAMEWORK"]=0;}
		if(!isset($array["REMOTE_FRAMEWORK"])){$array["REMOTE_FRAMEWORK"]="https://articaserver:9000/authenticator.php";}
		
		
		if($array["USE_AUTHENTICATOR"]==1){
			$f[]="\t\tauth_request /ARTICAAUTHENTICATOR;";
			$f[]="\t\tauth_request_set \$username \$upstream_http_x_username;";
			$f[]="\t\tadd_header X-Set-Username \$username;";
			return @implode("\n", $f);
		}
	}
	
	private function LDAP_server_auth(){
		return null;
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("reverse_www","webauth")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD webauth TEXT");}
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `webauth` FROM reverse_www WHERE servername='$this->servername'"));
		$array=unserialize(base64_decode($ligne["webauth"]));
		
		
		if(!$this->IsLdapModule()){$array["ENABLE_LDAP_AUTH"]=0;}
			
				
		
		if(!isset($array["ENABLE_LDAP_AUTH"])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername LDAP Authentication not set.\n";}
			return null;
		}
		
		if(!is_numeric($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
		if($array["LDAP_PORT"]<>389){$ldapport=":{$array["LDAP_PORT"]}";}
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername LDAP {$array["LDAP_SERVER"]}$ldapport\n";}
		$f[]="\tauth_ldap_url ldap://{$array["LDAP_SERVER"]}$ldapport/{$array["LDAP_SUFFIX"]}?{$array["LDAP_USER_ATTR"]}?sub?(objectClass={$array["LDAP_OBJCLASS_ATTR"]});";
		if($array["LDAP_DN"]<>null){
			$array["LDAP_PASSWORD"]=str_replace("$", "\$", $array["LDAP_PASSWORD"]);
			$f[]="\tauth_ldap_binddn {$array["LDAP_DN"]};";
			$f[]="\tauth_ldap_binddn_passwd {$array["LDAP_PASSWORD"]};";
		}
		
		if($array["LDAP_GROUP_ATTR_ISDN"]==1){
			$f[]="\tauth_ldap_group_attribute_is_dn on;";
		}else{
			$f[]="\tauth_ldap_group_attribute_is_dn off;";
		}
		$f[]="\tauth_ldap_group_attribute {$array["LDAP_GROUP_ATTR"]};";
		return @implode("\n", $f);
		
	}
	
	private function LDAP_require(){
		return null;
		if(!$this->IsLdapModule()){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername LDAP Authentication not compiled.\n";}return null;}
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("reverse_www","webauth")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD webauth TEXT");}
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `webauth` FROM reverse_www WHERE servername='$this->servername'"));
		$array=unserialize(base64_decode($ligne["webauth"]));
		if(!isset($array["ENABLE_LDAP_AUTH"])){return null;}
		if(!is_numeric($array["LDAP_REQUIRE_VALID"])){$array["LDAP_REQUIRE_VALID"]=1;}
		if($array["LDAP_BANNER"]==null){$array["LDAP_BANNER"]="Please login";}
		if($array["LDAP_REQUIRE_VALID"]==1){
			$f[]="\t\tauth_ldap \"{$array["LDAP_BANNER"]}\";";
			$f[]="\t\tauth_ldap_require valid_user;";
			$f[]="\t\tauth_ldap_satisfy any;";
			return @implode("\n", $f);
		}
		
	}
	
	
	
	private function error_pages(){
		if($this->OWA){
		$f[]="\terror_page 500 502 503 504 /exch500.html;";
		$f[]="\tlocation = /exch500.html {";
		$f[]="\t\troot /usr/share/artica-postfix/ressources/templates/default;";
		$f[]="\t}";
		$f[]="\tlocation = /exchange2010.png	{";
		$f[]="\t\troot /usr/share/artica-postfix/ressources/templates/default/img;";
		$f[]="\t}\n";
		
		}
		
		return @implode("\n", $f);
	}
	
	private function error_pages_default(){
		$squid_reverse=new squid_reverse();
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$this->servername'"));
		if($ligne["ArticaErrors"]==0){return null;}
		
		while (list ($key, $value) = each ($squid_reverse->errors_page) ){
			$f[]="\terror_page $value = @backendErrors{$value};";
			$f[]="\t\tlocation @backendErrors{$value} {";
			$f[]="\t\tinternal;";
			$f[]="\t\tset \$a \$request_uri;";
			if($this->IsMiscModule()){
				$f[]="\t\tset_encode_base64 \$a;";
			}
			$f[]="\t\tproxy_pass  http://unix:/var/run/nginx-authenticator.sock:/authenticator.php?error-page={$value}&servername=$this->servername&localport=$this->host_port&ipaddr=\$remote_addr&uencode=\$a;";
			$f[]="\t}";
		}
		$f[]="";
		return @implode("\n", $f);
	}
	
	private function header_csp(){
		
		if(!isset($GLOBALS["ALL_WEBSITES"])){
			$sql="SELECT * FROM `reverse_www` WHERE `enabled`=1";
			$q=new mysql_squid_builder();
			$results=$q->QUERY_SQL($sql);
			while ($ligne = mysql_fetch_assoc($results)) {
				$ligne["servername"]=trim($ligne["servername"]);
				$GLOBALS["ALL_WEBSITES"]["https://{$ligne["servername"]}"]=true;
				$GLOBALS["ALL_WEBSITES"]["http://{$ligne["servername"]}"]=true;
			}
			$q=new mysql();
			$sql="SELECT * FROM freeweb WHERE `enabled`=1";
			$results=$q->QUERY_SQL($sql,"artica_backup");
			while ($ligne = mysql_fetch_assoc($results)) {
				$ligne["servername"]=trim($ligne["servername"]);
				$GLOBALS["ALL_WEBSITES"]["https://{$ligne["servername"]}"]=true;
				$GLOBALS["ALL_WEBSITES"]["http://{$ligne["servername"]}"]=true;
			}
			
			$GLOBALS["ALL_WEBSITES"]["https://fonts.googleapis.com"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://ssl.google-analytics.com"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://assets.zendesk.com"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://connect.facebook.net"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://s-static.ak.facebook.com"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://assets.zendesk.com"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://themes.googleusercontent.com"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://assets.zendesk.com"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://www.facebook.com"]=true;
			$GLOBALS["ALL_WEBSITES"]["https://s-static.ak.facebook.com"]=true; 
			$GLOBALS["ALL_WEBSITES"]["https://tautt.zendesk.com"]=true;
		}
		reset($GLOBALS["ALL_WEBSITES"]);
		while (list ($site, $value) = each ($GLOBALS["ALL_WEBSITES"]) ){
			$t[]=$site;
		}
		
		$f[]=@implode($t," ")."; img-src 'self' ; style-src 'self' 'unsafe-inline' ".@implode($t," ")." ; object-src 'none'\";";
		
		
		return "add_header Content-Security-Policy \"default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' $websites; img-src 'self' $websites; style-src 'self' 'unsafe-inline' $websites; font-src 'self' $websites; frame-src $websites; object-src 'none'\";";
		
		
	}
	
	private function limit_bandwitdth(){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT limit_rate,limit_rate_after FROM reverse_www WHERE servername='$this->servername'"));
		if($ligne["ArticaErrors"]==0){return null;}
		
		
		if($ligne["limit_rate"]==0){return;}
		$f[]="\t\tlimit_rate_after {$ligne["limit_rate_after"]}mb;";
		$limit_rate=$ligne["limit_rate"]*1024;
		$f[]="\t\tlimit_rate {$limit_rate}k;";
		return @implode("\n", $f);
		
	}
	
	
	private function error_pages_perso(){
		$squid_reverse=new squid_reverse();
		$q=new mysql_squid_builder();
		$f[]="\t\tproxy_intercept_errors on;";
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$this->servername'"));
		if($ligne["ArticaErrors"]==0){return null;}
		
		
		
		while (list ($key, $value) = each ($squid_reverse->errors_page) ){
			$params="?error-page=$value&error-ID={$ligne[$value]}&servername=$this->servername&localport=$this->host_port&uri=\$request_uri";
			$f[]="\t\terror_page $value /nginx.error.php$params;";
		}
		$f[]="";
		return @implode("\n", $f);
	}
	
	
	private function error_pages_perso_framework(){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$this->servername'"));
		if($ligne["ArticaErrors"]==0){return null;}
		
		$f[]="\tlocation /nginx.error.php {";
		$f[]="\tproxy_pass http://unix:/var/run/nginx-authenticator.sock:/authenticator.php;";
		//$f[]=$this->php5_params("/usr/share/artica-postfix");
		$f[]="\t}\n";
		return @implode("\n", $f);		
		
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
		
		while (list ($index, $alias) = each ($ALI) ){
			$ServersAliasesTMP=trim(strtolower($alias));
			if(isset($GLOBALS["NGINX_SERVERS_NAME"][$ServersAliasesTMP])){continue;}
			$GLOBALS["NGINX_SERVERS_NAME"][$ServersAliasesTMP]=true;
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername, Alias: $ServersAliasesTMP...\n";}
			$f[]=$ServersAliasesTMP;
		}
		
		
		if(count($f)>0){return " ".@implode(" ", $f);}
	}
	
	
	private function translate_destdir($directory){
		$asregex=false;
		$directory=trim(stripslashes($directory));
		$Asfile=false;
		if(preg_match("#\.[a-z0-9]+$#", $directory)){
			$Asfile=true;
			
		}
		
		
		
		$firstPat=substr($directory, 0,1);
		$lastPat=substr($directory, strlen($directory)-1,1);
		if($firstPat<>"~"){
			if($firstPat<>'/'){$directory="/$directory";}
			if(!$Asfile){
				if($lastPat<>'/'){$directory="$directory/";}
			}
		}else{
			$asregex=true;
		}
		
		return array($directory,$asregex,$Asfile);
		
	}
	
	public function groupware_zarafa_Frontend(){
		if(!$this->spawn_fcgi()){return null;}
		$free=new freeweb($this->servername);
		$rootdir=$free->WORKING_DIRECTORY;
		$php_values=$free->php_values(true);
		$port=80;
		if($this->ssl==1){$port=443;}
		$f[]="server {";
		$f[]="        listen $port;";
		$f[]="        server_name $this->servername;";
		$f[]="        access_log  /var/log/apache2/$this->servername/access.log;";
		$f[]="        error_log   /var/log/apache2/$this->servername/error.log;";
		$f[]="        index index.php;";
		$f[]="        root $rootdir/;";
		if($this->ssl==1){
			$this->LoadParams();
			$f[]="\tssl on;";
			$f[]=$this->squidR->SaveCertificate($this->sslcertificate,false,true);
			$f[]="\tssl_session_timeout  5m;";
			$f[]="\tssl_protocols  $this->ssl_protocols;";
			$f[]="\tssl_ciphers '$this->ssl_ciphers';";
			$f[]="\tssl_prefer_server_ciphers   on;";
		}
		

		$f[]="        location / {";
		$f[]="                try_files \$uri \$uri/ /index.php;";
		$f[]="        }";
		$f[]="";
		$f[]="location ~ \.php$ {";
		$f[]="                try_files \$uri =404;";
		$f[]="                fastcgi_buffers 8 16k;";
		$f[]="                fastcgi_buffer_size 32k;";
		$f[]="                fastcgi_read_timeout 300;";
		$f[]="                fastcgi_connect_timeout 300;";
		$f[]="                fastcgi_send_timeout 300;";
		$f[]="                fastcgi_index  index.php;";
		$f[]="                fastcgi_param   SCRIPT_FILENAME         \$document_root\$fastcgi_script_name;";
		$f[]="                fastcgi_param   SCRIPT_NAME             \$fastcgi_script_name;";
		$f[]="                fastcgi_param   PATH_INFO               \$fastcgi_path_info;";
		$f[]="                fastcgi_param   REQUEST_URI             \$request_uri;";
		$f[]="                fastcgi_param   DOCUMENT_URI            \$document_uri;";
		$f[]="                fastcgi_param   DOCUMENT_ROOT           \$document_root;";
		$f[]="                fastcgi_param   SERVER_PROTOCOL         \$server_protocol;";
		$f[]="                fastcgi_param   GATEWAY_INTERFACE       CGI/1.1;";
		$f[]="                fastcgi_param   SERVER_SOFTWARE         nginx/\$nginx_version;";
		$f[]="                fastcgi_param   REMOTE_ADDR             \$remote_addr;";
		$f[]="                fastcgi_param   REMOTE_PORT             \$remote_port;";
		$f[]="                fastcgi_param   SERVER_ADDR             \$server_addr;";
		$f[]="                fastcgi_param   SERVER_PORT             \$server_port;";
		$f[]="                fastcgi_param   SERVER_NAME             \$server_name;";
		$f[]="                fastcgi_param   HTTPS                   \$server_https;";
		$f[]="				  fastcgi_pass 	   unix:/var/run/php-fpm-zarafa.sock;";
		$f[]="				  include fastcgi_params;";
		$f[]="        }";
		$f[]="}";
		
		$filename="/etc/nginx/sites-enabled/freewebs-ssl-$this->servername.$port.conf";
		@file_put_contents($filename, @implode("\n", $f));
		
	}	
	
	private function http_locations_authenticator($ligne){
		$pageid=$ligne["webpageid"];
		$directoryZ=$this->translate_destdir($ligne["directory"]);
		$directory=$directoryZ[0];
		$asregex=$directoryZ[1];
		$AsFile=$directoryZ[2];
		$logprefix="Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: Page ID: $pageid";
		if($GLOBALS["OUTPUT"]){echo "$logprefix [{$ligne["directory"]}]\n";}
		$proxy_cache=$this->keys_zone($ligne["storeid"]);
		$dirpat=$this->strip_slashes($directory);
		
		$directory_name=$directory;
		if(substr($directory_name, 0,1)<>"/"){$directory_name="/$directory_name";}
		$f[]="location $directory_name {";
		
		if($ligne["folderid"]>0){
			$f[]=$this->replace_folder_rules($ligne["folderid"]);
		}
		$replace_rules=$this->replace_rules_build($ligne["replaceid"]);
		if($replace_rules<>null){
			$f[]="\t\tproxy_set_header Accept-Encoding \"\";";
			$f[]=$replace_rules;
		}
		$f[]="\t\tproxy_pass http://unix:/var/run/nginx-authenticator.sock:/authenticator.php?pageid=$pageid;";
		$f[]="}";
		$f[]="";
		@mkdir("/home/reverse_pages_content",0777);
		@chmod("/home/reverse_pages_content",0777);
		return @implode("\n", $f);
		
	}
	
private function http_location_cache_params($ligne){

	$logprefix="Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port LOCATION CACHE:";
	if(!is_numeric($ligne["NoCache"])){$ligne["NoCache"]=0;}
	if(!is_numeric($ligne["proxy_cache_min_uses"])){$ligne["proxy_cache_min_uses"]=1;}
	if(!is_numeric($ligne["proxy_cache_valid"])){$ligne["proxy_cache_valid"]=4320;}
	if(!preg_match("#([0-9]+)\s+([0-9]+)([a-z]+)#", $ligne["proxy_buffers"])){$ligne["proxy_buffers"]="8 8k";}
	if(!is_numeric($ligne["proxy_buffering"])){$ligne["proxy_buffering"]=1;}
	if(!is_numeric($ligne["proxy_buffer_size"])){$ligne["proxy_buffer_size"]=8;}
	if(!is_numeric($rev->proxy_read_timeout)){$rev->proxy_read_timeout=300;}
	if(!is_numeric($rev->proxy_send_timeout)){$rev->proxy_send_timeout=300;}
	
	$f[]="\t\tproxy_cache_use_stale  error timeout invalid_header updating http_500 http_502 http_503 http_504;";
	
	
	$f[]="\t\tproxy_read_timeout    {$rev->proxy_read_timeout}s;";
	$f[]="\t\tproxy_send_timeout	{$rev->proxy_send_timeout}s;";
	
	if($ligne["NoCache"]==1){
		$f[]="\t\tproxy_cache_bypass 1;";
		return @implode("\n", $f);
	}
	
	
	if($ligne["proxy_buffering"]==1){
		$f[]="\t\tproxy_buffering on;";
	}else{
		$f[]="\t\tproxy_buffering off;";
	}
	if($ligne["proxy_buffering"]==1){
		if($ligne["proxy_buffer_size"]>0){
			if($GLOBALS["OUTPUT"]){echo "$logprefix proxy_buffers.............: [{$ligne["proxy_buffers"]}]\n";}
			$f[]=$this->proxy_buffers_check($ligne["proxy_buffers"]);
		}
	}
	if($ligne["proxy_cache_valid"]>0){
		$f[]="\t\tproxy_cache_valid {$ligne["proxy_cache_valid"]}m;";
		$f[]="\t\texpires {$ligne["proxy_cache_valid"]}m;";
	}
	//$f[]="\t\tproxy_cache_revalidate  on;";
	return @implode("\n", $f);
}
	
	
	private function http_locations_proxy($ligne){
		$directoryZ=$this->translate_destdir($ligne["directory"]);
		$directory=$directoryZ[0];
		$asregex=$directoryZ[1];
		$AsFile=$directoryZ[2];
		
		$logprefix="Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: Proxy: $directory";
		
		if($GLOBALS["OUTPUT"]){echo "$logprefix [{$ligne["directory"]}]\n";}
			
		$HostHeader=null;
		if($ligne["hostweb"]<>null){$HostHeader=$this->ParseHostHeader($ligne["hostweb"]);}
		
		
		
		$proxy_cache=$this->keys_zone($ligne["storeid"]);
		if(!is_numeric($ligne["limit_rate"])){$ligne["limit_rate"]=0;}
		if($GLOBALS["OUTPUT"]){echo "$logprefix storeid=[{$ligne["storeid"]}] [".__LINE__."]\n";}
		if($GLOBALS["OUTPUT"]){echo "$logprefix cache_peer_id=[{$ligne["cache_peer_id"]}] [".__LINE__."]\n";}
		if($GLOBALS["OUTPUT"]){echo "$logprefix folderid=[{$ligne["folderid"]}] [".__LINE__."]\n";}
		if($GLOBALS["OUTPUT"]){echo "$logprefix authenticator=[{$ligne["authenticator"]}] [".__LINE__."]\n";}
		
		
		
		$proxy_pass=$this->proxy_pass($ligne["cache_peer_id"],$asregex,$ligne["folderid"],$ligne["directory"]);
		
		$forcedomain=$this->forcedomain($ligne["cache_peer_id"]);
		if($forcedomain<>null){$HostHeader=$forcedomain;}
		$dirpat=$this->strip_slashes($directory);
		
		
		$directory_name=$directory;
		if(substr($directory_name, 0,1)<>"/"){$directory_name="/$directory_name";}
		
		
		$f[]="location $directory_name {";
		
		$f[]="# Bandwitdth Limitation: {$ligne["limit_rate"]}Mb/s after {$ligne["limit_rate_after"]}MB ".__CLASS__."/".__LINE__;
		if($ligne["limit_rate"]>0){
			$f[]="limit_rate_after {$ligne["limit_rate_after"]}mb;";
			$limit_rate=$ligne["limit_rate"]*1024;
			$f[]="limit_rate {$limit_rate}k;";
		}
		
		
		
		
		//$f[]="\t\tproxy_redirect off;";
		if(!$asregex){
		if(!$AsFile){
				$f[]="\t\trewrite ^$directory(.*) /$1 break;";
			}else{
				$f[]="\t\trewrite ^$directory /$1 break;";
			}
		}
		if($ligne["folderid"]>0){
			$f[]=$this->replace_folder_rules($ligne["folderid"]);
		}
		$AUTHENTICATOR_DIRECTORY=$this->AUTHENTICATOR_DIRECTORY($ligne["authenticator"],$ligne["directory"]);
		
		
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
		if($AUTHENTICATOR_DIRECTORY<>null){
		$f[]="\t\tproxy_intercept_errors on;";
		$f[]="\t\terror_page 403 /nginx.error.php?results-page=yes&ruleid={$ligne["authenticator"]}&uri=\$request_uri;";
		$f[]="\t\tauth_request /$dirpat/ARTICAAUTHENTICATOR{$ligne["authenticator"]};";
		$f[]="\t\tauth_request_set \$username \$upstream_http_x_username;";
		}
		
		$f[]=$this->http_location_cache_params($ligne);
		$f[]="\t\tproxy_pass_header Date;";
		$f[]="\t\tproxy_pass_header Server;";
		
		if($proxy_cache<>null){$f[]=$proxy_cache;}
		if($proxy_pass<>null){$f[]=$proxy_pass;}
		if($AUTHENTICATOR_DIRECTORY<>null){
			$f[]=$AUTHENTICATOR_DIRECTORY;
		}
		
		$f[]="}";
		$f[]="";		
		return @implode("\n", $f);
		
	}
	private function http_locations_local($ligne){
		$directory=trim(stripslashes($ligne["directory"]));
		$destination=$ligne["localdirectory"];
		$directory_name=$directory;
		if(substr($directory_name, 0,1)<>"/"){$directory_name="/$directory_name";}
		if(preg_match("#.+?\.[a-z]+$#", $destination)){$destination=dirname($destination);}
		if(!is_numeric($ligne["limit_rate"])){$ligne["limit_rate"]=0;}
		
		
		
		$f[]="\tlocation $directory_name {";
		$f[]="\t\troot\t$destination;";
		$f[]="# Bandwitdth Limitation: {$ligne["limit_rate"]}Mb/s after {$ligne["limit_rate_after"]}MB ".__CLASS__."/".__LINE__;
		if($ligne["limit_rate"]>0){
			$f[]="\t\tlimit_rate_after {$ligne["limit_rate_after"]}mb;";
			$limit_rate=$ligne["limit_rate"]*1024;
			$f[]="\t\tlimit_rate {$limit_rate}k;";
		}		
		
		
		$f[]="\t}";
		
		$f[]="";
		return @implode("\n", $f);
	}
	
	

	
	private function http_locations($servername){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $servername, checking LOCATIONS...\n";}
		$f=array();
		if($this->SargDir<>null){
			
			$f[]="\tlocation ~ /sarg.css$ {";
			$f[]="\t\talias\t$this->SargDir/sarg.css;";
			$f[]="\t}";
			$f[]="";
			$f[]="\tlocation ~ /pattern.png$ {";
			$f[]="\t\talias\t$this->SargDir/pattern.png;";
			$f[]="\t}";
			$f[]="\tlocation ~ /logo.gif$ {";
			$f[]="\t\talias\t$this->SargDir/logo.gif;";
			$f[]="\t}";	
			$f[]="\tlocation ~ /sarg/index.html$ {";
			$f[]="\t\talias\t$this->SargDir/index.html;";
			$f[]="\t}";	
			$f[]="\tlocation ~ /ui-bg_highlight.png$ {";
			$f[]="\t\talias\t/usr/share/artica-postfix/css/images/ui-bg_highlight.png;";
			$f[]="\t}";	
			$f[]="";			
			$f[]="\tlocation ~ /sarg/(.*?)$ {";
			$f[]="\t\tautoindex on;";
			$f[]="\t\talias\t$this->SargDir/$1;";
			$f[]="\t}";
			$f[]="";
		}
		
		
		$f[]=$this->reverse_dirs();
		$f[]=$this->webdav_containers();
		return @implode("\n", $f);
		
		
	}
	
	
	private function reverse_dirs(){
		$q=new mysql_squid_builder();
		
		$sql="SELECT * FROM reverse_dirs WHERE servername='$this->servername' AND enabled=1 ORDER BY directory";
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$asregex=false;
			$directory=trim($ligne["directory"]);
				
			if(isset($ALREADY[$directory])){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: $directory already set\n";}continue;}
			$ALREADY[$directory]=true;
			if($directory==null){continue;}
				
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: [$directory] Is local ={$ligne["local"]}\n";}
			$f[]=$this->reverse_dirs_pattern($ligne);
		}

		return @implode("\n", $f);
	}
	private function reverse_dirs_pattern($ligne){
		if($ligne["webpageid"]>0){
			return $this->http_locations_authenticator($ligne);
		}
		if($ligne["local"]==1){
			return $this->http_locations_local($ligne);
		}
		return $this->http_locations_proxy($ligne);
	}
	
	
	private function http_proxy(){
		$unix=new unix();
		$ServersAliases=null;
		$server_name=null;
		$ErrorLogLevel="warn";
		if(trim($this->servername)==null){$this->FillHostnameAsIP();}
		if($this->default_server==1){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername/".__LINE__." IS A DEFAULT SERVER..\n";}
			$default_server=" default_server";
		}
		
		
		$HostHeader="\$host";
		if(trim($this->forceddomain<>null)){$HostHeader="$this->forceddomain";}
		if($this->host_port==0){$this->host_port=80;}
		$HostIPADDR=$this->MyHostnameListen();
		
		$AsProxy=true;
		
		if($GLOBALS["VERBOSE"]){echo "[DEBUG]: proxy_disabled='$this->proxy_disabled'\n";}
		
		if($this->proxy_disabled==1){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: It is not a reverse Proxy\n";}
			$AsProxy=false;
		}		
		
		
		
		$f[]="server {";
		$f[]="\tlisten {$HostIPADDR}$this->host_port{$default_server};";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername/".__LINE__." listen {$HostIPADDR}$this->host_port{$default_server}\n";}
		$server_name_text=$this->CheckServerName($this->servername);
		$ServersAliases=$this->http_servername_aliases();
		
		$server_name=trim("$server_name_text{$ServersAliases}");
		if($server_name<>null){$f[]="\tserver_name $server_name;";}
		if($this->isDebug()){$ErrorLogLevel="debug";}

		$LDAP_server_auth=$this->LDAP_server_auth();
		if($LDAP_server_auth<>null){$f[]=$LDAP_server_auth;}
		$Logsuffix=$this->servername;
		$Logsuffix=str_replace("unix:", "unix-", $Logsuffix);
		$Logsuffix=str_replace("/", "", $Logsuffix);
		
		// LOGS
		$f[]="# Access logs Line ".__LINE__;
		$access_log="/var/log/apache2/$Logsuffix/nginx.access.log";
		
		@mkdir("/var/log/apache2/$Logsuffix",0755,true);
		$unix->chown_func($this->APACHE_USER,$this->APACHE_SRC_GROUP,"/var/log/apache2/$Logsuffix");
		if(preg_match("#^unix:#", $this->servername)){
			$access_log="/dev/null";
			$ErrorLogLevel="crit";
		}
			
		
		$f[]="\taccess_log  $access_log aws_log;";
		$f[]="\terror_log  /var/log/apache2/$Logsuffix/nginx.error.log $ErrorLogLevel;";
		
		
		if($this->OWA){
			$f[]="rewrite ^/$ http://$server_name_text$this->HostPortLNK/owa permanent;";
		}
		
		if($this->RedirectQueries<>null){
			$f[]="return 301 $this->RedirectQueries\$request_uri;";
			$f[]="}";
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: ".count($f)." parameters\n";}
			return @implode("\n", $f);
				
		}
		$f[]=$this->general_security();
		$f[]=$this->error_pages_default();
		$f[]=$this->AntiExploits();
		
		if($this->DocumentRoot<>null){
			$f[]="\troot   $this->DocumentRoot;";
			if($this->IndexFile<>null){ $f[]="\tindex $this->IndexFile;";}
			$f[]="\tlocation ~* ^.+.(jpg|jpeg|gif|css|png|js|ico|html|xml|txt)$ {";
			//$f[]="\t\taccess_log        off;";
			$f[]="\t\texpires           max;";
			$f[]="\t}";
		}		
		
		if(!$AsProxy){
			$f[]=$this->ROOT_WEBSERVER();
			
		}else{
			$f[]=$this->ROOT_PROXY();
		}
		if(!$this->NoErrorPages){
			$f[]=$this->error_pages();
			$f[]=$this->error_pages_perso_framework();
		}		

		$f[]="}";		

		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port:".count($f)." parameters\n";}
		return @implode("\n", $f);
		
	}
	
	private function CheckServerName($servername){
		if($this->BuildFramework){return null;}
		$servername=trim(strtolower($servername));
		if(preg_match("#^unix:#", $servername)){return null;}
		if(substr($servername, 0,1)=="."){$servername=substr($servername,1,strlen($servername));}
		return $servername;
	}
	
	private function MyHostnameListen(){
		$HostIPADDR=null;
		if(preg_match("#^unix:#", $this->servername)){
			$this->host_port=$this->servername;
			return null;
		}
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
	
	
	private function AntiExploits(){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM nginx_exploits WHERE servername='$this->servername'";
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}
		$results=$q->QUERY_SQL($sql);
		$f=array();
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		if(mysql_num_rows($results)==0){return;}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			if($GLOBALS["VERBOSE"]){echo "nginx_exploits: gpid:{$ligne["groupid"]}\n";}
			$groupid=$ligne["groupid"];
			$rules=$this->AntiExploits_items($groupid);
			if($rules<>null){$f[]=$rules;}
		}
			
		if(count($f)>0){return @implode("\n" ,$f);}
	}
	
	private function AntiExploits_items($groupid){
		$q=new mysql_squid_builder();
		$A="$";
		$f=array();
		$sql="SELECT * FROM nginx_exploits_items WHERE groupid=$groupid AND `enabled`=1";
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		if(mysql_num_rows($results)==0){return;}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$neg=null;
			if($ligne["reverse"]==1){$neg="!";}
			$ligne["pattern"]=str_replace('"', '\"', $ligne["pattern"]);
			$f[]="\tif ($A{$ligne["token"]} {$neg}~ \"{$ligne["pattern"]}\") {  return 403; }";
   
		}
		
		if(count($f)>0){return @implode("\n" ,$f);}
		
	}
	
	private function general_security(){
		$servername=$this->servername;
		$ZoneName=str_replace(".", "", $servername);
		$ZoneName=str_replace("-", "", $servername);
		$ZoneName=str_replace("_", "", $servername);
		$f=array();
		if($this->LimitCnx>0){$f[]="\tlimit_conn LimitCnx $this->LimitCnx;";}
		if($this->EnableHSTS==1){$f[]="\tadd_header Strict-Transport-Security \"max-age=31536000\";";}
		if($this->RestrictIFrames==1){$f[]="\tadd_header X-Frame-Options SAMEORIGIN;";}
		if($this->XSSBrowser==1){$f[]="\tadd_header X-XSS-Protection \"1; mode=block\";";}
		if($this->EnableCSP==1){$f[]=$this->header_csp();}
		
		if($this->LimitReqs>0){
			$f[]="\tlimit_req   zone=$ZoneName  burst=2  nodelay;";
		}
		if(count($f)>0){return @implode("\n", $f);}
		
	}
	
	
	private function ssl_proxy(){
		$unix=new unix();
		$HostHeader="\$host";
		$ServersAliases=null;
		$server_name=null;
		$ErrorLogLevel="warn";
		if($this->servername==null){$this->FillHostnameAsIP();}
		if($this->host_port==0){$this->host_port=443;}
		$HostIPADDR=$this->MyHostnameListen();
		if($this->default_server==1){$default_server=" default_server";}
		if($this->isDebug()){$ErrorLogLevel="debug";}
		$AsProxy=true;
		
		if($GLOBALS["VERBOSE"]){echo "[DEBUG]: default_server='$this->default_server'\n";}
		if($GLOBALS["VERBOSE"]){echo "[DEBUG]: proxy_disabled='$this->proxy_disabled'\n";}
		
		if($this->proxy_disabled==1){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: It not a reverse Proxy\n";}
			$AsProxy=false;
		}
		
		$ServersAliases=$this->http_servername_aliases();
		
		@mkdir("/var/log/apache2/$this->servername",0755,true);
		$unix->chown_func($this->APACHE_USER,$this->APACHE_SRC_GROUP,"/var/log/apache2/$this->servername");
		
		
		
		$server_name_text=$this->CheckServerName($this->servername);
		$f[]="server {";
		$f[]="\tlisten {$HostIPADDR}$this->host_port{$default_server};";
		$f[]="\tset \$cache_uri \$request_uri;";
		$f[]="\tif (\$request_method = POST) {";
		$f[]="\t\tset \$cache_uri 'null cache';";
		$f[]="\t}";
		
		$server_name=trim("$server_name_text{$ServersAliases}");
		if($GLOBALS["VERBOSE"]){echo "[DEBUG]: $this->servername:$this->host_port server_name='$server_name'\n";}
		if($GLOBALS["VERBOSE"]){echo "[DEBUG]: $this->servername:$this->host_port OWA='$this->OWA'\n";}
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: server_name=\"$server_name\"\n";}
		if($server_name<>null){$f[]="\tserver_name $server_name_text{$ServersAliases};";}
		
		if($this->OWA){$f[]="\trewrite ^/$ https://$this->servername$this->HostPortLNK/owa/auth/logon.aspx?replaceCurrent=1&url=https%3a%2f%2f$this->servername%2fowa%2f permanent;";}
		
		$f[]="\taccess_log  /var/log/apache2/$this->servername/nginx.access.log aws_log;";
		$f[]="\terror_log  /var/log/apache2/$this->servername/nginx.error.log $ErrorLogLevel;";
		if($this->RedirectQueries<>null){
			$f[]="return 301 $this->RedirectQueries\$request_uri;";
			$f[]="}";
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: ".count($f)." parameters\n";}
			return @implode("\n", $f);
			
		}
		$f[]=$this->general_security();
		$f[]=$this->error_pages_default();
		$f[]=$this->AntiExploits();
		
		
		$LDAP_server_auth=$this->LDAP_server_auth();
		if($LDAP_server_auth<>null){$f[]=$LDAP_server_auth;}
		
		if(!$AsProxy){
			$f[]=$this->ROOT_WEBSERVER();
		}else{
			$f[]=$this->ROOT_PROXY();
		}		
		
		
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
		$f[]="\tssl_session_cache shared:SSL:50m;";
		$f[]="\tssl_session_timeout  5m;";
		$f[]="\tssl_protocols  $this->ssl_protocols;";
		$f[]="\tssl_ciphers '$this->ssl_ciphers';";
		$f[]="\tssl_prefer_server_ciphers   on;";
		
		


		$f[]=$this->error_pages();
		$f[]=$this->error_pages_perso_framework();
		$f[]="}";
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: ".count($f)." parameters\n";}
		return @implode("\n", $f);
		
	}
	
	
	private function ROOT_WEBSERVER(){
		$f[]=$this->php_fpm();
		$f[]=$this->http_locations($this->servername);
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
		if(!$q->FIELD_EXISTS("nginx_replace_www", "zorder")){
			$q->QUERY_SQL("ALTER TABLE `nginx_replace_www` ADD `zorder` INT( 10 ) NOT NULL DEFAULT '0' ,ADD INDEX ( `zorder` )");
			if(!$q->ok){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: $q->mysql_error\n";}
				return null;
			}
		}
		
		if(!$q->FIELD_EXISTS("nginx_replace_www", "AsRegex")){
			$q->QUERY_SQL("ALTER TABLE `nginx_replace_www` ADD `AsRegex` smallint( 1 ) NOT NULL DEFAULT '0' ,ADD INDEX ( `AsRegex` )");
			if(!$q->ok){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: $q->mysql_error\n";}
				return null;
			}
		}		
		
		
		$f=array();
		$sql="SELECT * FROM nginx_replace_www WHERE servername='$this->servername' ORDER BY zorder";
		$results=$q->QUERY_SQL($sql);
		
		
		
		if(!$q->ok){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: $q->mysql_error\n";}
			return null;
		}
		
		if(@mysql_num_rows($results)==0){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: no main replace rule\n";}
		}
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$ligne["stringtosearch"]=stripslashes($ligne["stringtosearch"]);
			$AsRegex=$ligne["AsRegex"];
			if($AsRegex==0){$ligne["stringtosearch"]=$this->PatternToRegex($ligne["stringtosearch"]);}
			
			
			$ligne["stringtosearch"]=$this->QuoteStringSpaces($ligne["stringtosearch"]);
				
			$ligne["replaceby"]=stripslashes($ligne["replaceby"]);
			$ligne["replaceby"]=$this->QuoteStringSpaces($ligne["replaceby"]);

			$f[]="\t\tsubs_filter {$ligne["stringtosearch"]}  {$ligne["replaceby"]} {$ligne["tokens"]};";
			
		}
		
		
		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT replaceid FROM reverse_www WHERE servername='$this->servername'"));
		
		if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, *** FATAL *** $q->mysql_error\n";}}
		if(!is_numeric($ligne["replaceid"])){$ligne["replaceid"]=0;}
		if($ligne["replaceid"]>0){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: no replace rule ({$ligne["replaceid"]})\n";}
			if($GLOBALS["VERBOSE"]){echo "SELECT replaceid FROM reverse_www WHERE servername='$this->servername'\n";}
			$a=$this->replace_rules_build($ligne["replaceid"]);
			if($a<>null){$f[]=$a;}
		}
		
		if(count($f)>0){
			return @implode("\n", $f);
		}
		 
		
	}
	
	private function PatternToRegex($pattern){
		
		if(isset($GLOBALS["PatternToRegex"][$pattern])){return $GLOBALS["PatternToRegex"][$pattern];}
		$pattern=trim($pattern);
		$patternSrc=$pattern;
		$pattern=str_replace("\\", "\\\\", $pattern);
		$pattern=str_replace(".", "\.", $pattern);

		$pattern=str_replace("/", "\/", $pattern);
		$pattern=str_replace("(", "\(", $pattern);
		$pattern=str_replace(")", "\)", $pattern);
		$pattern=str_replace("[", "\[", $pattern);
		$pattern=str_replace("]", "\]", $pattern);
		$pattern=str_replace("+", "\+", $pattern);
		$pattern=str_replace("{", "\{", $pattern);
		$pattern=str_replace("}", "\}", $pattern);
		$pattern=str_replace("=", "\=", $pattern);
		$pattern=str_replace("!", "\!", $pattern);
		$pattern=str_replace("?", "\?", $pattern);
		$pattern=str_replace("|", "\|", $pattern);
		$pattern=str_replace("$", "\$", $pattern);
	
		$pattern=str_replace("^", "\^", $pattern);
		
		$pattern=str_replace("\n", "\s+", $pattern);
		$pattern=str_replace("\t", "\s+", $pattern);
		$pattern=str_replace("  ", "\s+", $pattern);
		$pattern=str_replace("\s+\s+", "\s+", $pattern);
		$pattern=str_replace("\s+\s+", "\s+", $pattern);
		$pattern=str_replace("*", ".*?", $pattern);
	
		$GLOBALS["PatternToRegex"][$pattern]=$pattern;
		return $pattern;
	}
	
	private function replace_rules_build($id){
		if(!is_numeric($id)){return;}
		if($id==0){return;}
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL("SELECT * FROM nginx_replace WHERE groupid='$id'");
		$t=array();
		
		if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, *** FATAL *** $q->mysql_error\n";}}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: Replace Group id:$id ". mysql_num_rows($results)." rules\n";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			
			$AsRegex=$ligne["AsRegex"];
			
		if(trim($ligne["stringtosearch"])==null){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: Replace rule id:{$ligne["ID"]} no search pattern\n";}
			return;}
			$ligne["stringtosearch"]=stripslashes($ligne["stringtosearch"]);
			if($AsRegex==0){$ligne["stringtosearch"]=$this->PatternToRegex($ligne["stringtosearch"]);}
			$ligne["stringtosearch"]=str_replace("\"", '\"', $ligne["stringtosearch"]);
			
			
			$ligne["replaceby"]=stripslashes($ligne["replaceby"]);
			$ligne["replaceby"]=str_replace("\"", '\"', $ligne["replaceby"]);
			if($GLOBALS["VERBOSE"]){echo "\"{$ligne["stringtosearch"]}\"\n";}
			$t[]="\t\tsubs_filter \"{$ligne["stringtosearch"]}\" \"{$ligne["replaceby"]}\" gi;";
		}
		
		if(count($t)==0){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: Replace Group id:$id no rule\n";}
			return null;}
		return @implode("\n", $t);		
	}
	
	private function replace_folder_rules($folderid){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: Folder ID:$folderid [".__LINE__."]\n";}
		if(!is_numeric($folderid)){return;}
		if($folderid==0){return;}
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL("SELECT * FROM nginx_replace_folder WHERE folderid='$folderid' ORDER BY zorder");
		$t=array();
		if(!$q->ok){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, *** FATAL *** $q->mysql_error\n";}}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port: Replace Folder id:$folderid ". mysql_num_rows($results)." rules\n";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$AsRegex=$ligne["AsRegex"];
			$ligne["stringtosearch"]=stripslashes($ligne["stringtosearch"]);
			if($AsRegex==0){$ligne["stringtosearch"]=$this->PatternToRegex($ligne["stringtosearch"]);}
			$ligne["stringtosearch"]=str_replace("\"", '\"', $ligne["stringtosearch"]);
			$ligne["replaceby"]=stripslashes($ligne["replaceby"]);
			$ligne["replaceby"]=str_replace("\"", '\"', $ligne["replaceby"]);
			if($GLOBALS["VERBOSE"]){echo "\"{$ligne["stringtosearch"]}\"\n";}
			$f[]="\t\tsubs_filter {$ligne["stringtosearch"]}  {$ligne["replaceby"]} {$ligne["tokens"]};";
		}
		
		
		return @implode("\n",$f);
		
	}
	
	
	
	private function ChekPool($ReverseProxyPattern){
		$q=new mysql_squid_builder();
		$http="http";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT poolname FROM nginx_pools WHERE ID='$this->poolid'"));
		$upstream_name=$ligne["poolname"];
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Load-balancing: [$this->poolid] \"$upstream_name\"\n";}
		
		if(!isset($GLOBALS["upstream_available"][$upstream_name])){return $ReverseProxyPattern;}
		if($this->OWA){$http="https";}
		return "$http://$upstream_name";
		
	}
	
	private function proxy_cache_bypass(){
		$f[]="\t\tproxy_no_cache \$cookie_nocache \$arg_nocache \$arg_comment;";
		$f[]="\t\tproxy_no_cache \$http_pragma \$http_authorization;";
		$f[]="\t\tproxy_cache_bypass \$cookie_nocache \$arg_nocache \$arg_comment;";
		$f[]="\t\tproxy_cache_bypass \$http_pragma \$http_authorization;";
		return @implode("\n",$f);
	}
	
	
	
	private function ROOT_PROXY(){
		$IpClass=new IP();
		$HostHeader="\$host";
		$this->forceddomain=trim($this->forceddomain);
		$PortTXT=null;
		if($this->forceddomain<>null){$HostHeader="$this->forceddomain";}
		if($this->default_server==1){
			$HostHeader="\$host";
			$this->forceddomain=$HostHeader;
		}
		$proxy_to_host=$this->proxy_to_host;
		$proxy_ssl_session_reuse=null;
		$http_prefix=null;
		if(preg_match("#^(http|ftp|https|ftps|smtp|smtps):\/\/(.+)#", $proxy_to_host,$re)){
			$proxy_to_host=$re[2];
			$http_prefix=$re[1];
		}
		
		if($http_prefix==null){
			$http_prefix="http";
			if($this->proxy_to_port==80){$http_prefix="http";$this->proxy_to_port=null;}
			if($this->proxy_to_port==443){$http_prefix="https";$this->proxy_to_port=null;}
			if($this->proxy_scheme<>null){$http_prefix=$this->proxy_scheme;}
		}
		if($this->IsAFreeWeb){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername is a freeweb\n";}
			$this->proxy_to_host="127.0.0.1";
			$proxy_to_host=$this->proxy_to_host;
			if($this->isFreeWebSSL()){
				$http_prefix="http";
				$this->proxy_to_port=82;
				$proxy_ssl_session_reuse="off";
			}
			
		}
		
		if(preg_match("#([0-9\.]+):([0-9]+)#",$proxy_to_host,$re)){
			$proxy_to_host=$re[1];
			$this->proxy_to_port=$re[2];
		}
		
		
		if(!$IpClass->isValid($proxy_to_host)){
			$tcp=gethostbyname($proxy_to_host);
			if(!$IpClass->isValid($tcp)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $proxy_to_host -> 127.0.0.1 [".__LINE__."]\n";}
				$proxy_to_host="127.0.0.1";
				if($this->proxy_to_port==80){$this->proxy_to_port=82;}
				if($this->proxy_to_port==443){$this->proxy_to_port=447;}
			}
		}
		
		
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port Proxy to: '$proxy_to_host', suffix '$this->proxy_suffix'\n";} 
		
		if(is_numeric($this->proxy_to_port)){
			$PortTXT=":$this->proxy_to_port";
		}
		
		$this->proxy_suffix=str_replace("//", "/", $this->proxy_suffix);
		$ReverseProxyPattern="{$http_prefix}://$proxy_to_host$PortTXT$this->proxy_suffix";
		if($this->poolid>0){
			$ReverseProxyPattern=$this->ChekPool($ReverseProxyPattern);
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, $this->servername:$this->host_port source id: $this->cache_peer_id\n";}
		
		
		if(substr($ReverseProxyPattern, strlen($ReverseProxyPattern)-1,1)=="/"){
			$ReverseProxyPattern=substr($ReverseProxyPattern, 0,strlen($ReverseProxyPattern)-1);
		}
		
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
		if($this->OWA){	$f[]="\tproxy_read_timeout 3600s;";}
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
			$f[]=$this->proxy_cache_bypass();
			
			if($this->ssl==1){$f[]="\tproxy_set_header X-Forwarded-Ssl on;";}
		}	
		if($proxy_ssl_session_reuse<>null){
			$f[]="\tproxy_ssl_session_reuse\t$proxy_ssl_session_reuse;";
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
		$f[]=$this->limit_bandwitdth();
		$f[]=$this->error_pages_perso();
		$AUTHENTICATOR_BASE=$this->AUTHENTICATOR_BASE();
		if($AUTHENTICATOR_BASE<>null){$f[]=$AUTHENTICATOR_BASE;}
		$LDAP_require=$this->LDAP_require();
		if($LDAP_require<>null){$f[]=$LDAP_require;}
		
		
		
		if($this->forceddomain<>null){
			if($this->IsSubstitutions()){
				$ToReplacewww=$this->servername;	
				if($this->default_server==1){$ToReplacewww="\$host";}
				$f[]="\t\tproxy_set_header Accept-Encoding \"\";";
				
				$subs_filter_types=true;
				$f[]="\t\tsubs_filter ".$this->PatternToRegex($proxy_to_host)." $ToReplacewww gi;";
				if(preg_match("#^www\.(.+)#", $this->proxy_to_host,$re)){
					$f[]="\t\tsubs_filter ".$this->PatternToRegex($re[1])." $ToReplacewww;";
				}
			}
			


			
			
		}else{
			$this->forceddomain="\$host";
		}
		
		
		
		$f[]=$this->proxy_cache_bypass();
		$f[]="\t\tproxy_set_header\tX-Real-IP\t\$remote_addr;";
		$f[]="\t\tproxy_set_header Host $this->forceddomain;";
		$f[]="\t\tproxy_set_header\tX-Forwarded-For\t\$proxy_add_x_forwarded_for;";
		//$f[]="\t\tproxy_cache_revalidate  on; 1.5.7";
		$f[]="\t\tproxy_cache_use_stale  error timeout invalid_header updating http_500 http_502 http_503 http_504;";
		$f[]="\t\tproxy_cache_valid {$this->proxy_cache_valid}m;";
		$f[]="\t\texpires {$this->proxy_cache_valid}m;";
		
		$replace_rules=$this->replace_rules_single_domain();
		
		
		if($replace_rules<>null){
				$f[]="\t\tproxy_set_header Accept-Encoding \"\";";
				$f[]=$replace_rules;
		}
		$proxy_cache=$this->keys_zone($this->storeid);
		if($proxy_cache<>null){$f[]=$proxy_cache;}
		$f[]=$this->proxy_parameters($this->cache_peer_id);
		

		$f[]="\t\tproxy_pass $ReverseProxyPattern;";
		
		if($proxy_redirect<>null){$f[]="\t\t$proxy_redirect";}
		$f[]="\t}";
		
		$AUTHENTICATOR_PROXY=$this->AUTHENTICATOR_PROXY();
		if($AUTHENTICATOR_PROXY<>null){$f[]=$AUTHENTICATOR_PROXY;}
		$f[]="";
		
		return @implode("\n", $f);
		
	}
	
	private function spawn_fcgi_parameters(){
		$unix=new unix();
		$spawn_fcgi=$unix->find_program("spawn-fcgi");
	}
	
	private function spawn_fcgi_debian_fix(){
		$unix=new unix();
		$aptget=$unix->find_program("apt-get");
		if(!is_file($aptget)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Operating system not supported\n";}
			return;
		}
		$cmd="DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" --force-yes --yes install spawn-fcgi";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, installing spawn-fcgi...\n";}
		shell_exec($cmd);
		
	}
	
	
	private function spawn_fcgi(){
		$unix=new unix();
		$spawn_fcgi=$unix->find_program("spawn-fcgi");
		if(!is_file($spawn_fcgi)){
			$this->spawn_fcgi_debian_fix();
		}
		$spawn_fcgi=$unix->find_program("spawn-fcgi");
		if(!is_file($spawn_fcgi)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, FATAL no spawn-php can be enabled !\n";}
			return false;
		}
		$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();
		
		if(!is_file($phpcgi)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, FATAL no php-cgi can be enabled !\n";}
			return false;			
		}
		
		
		$pid=$unix->get_pid_from_file("/var/run/spawn-fcgi.pid");
		
		if(!$unix->process_exists($pid)){
			shell_exec("/etc/init.d/php5-fcgi start");
			sleep(1);
		}
		$pid=$unix->get_pid_from_file("/var/run/spawn-fcgi.pid");
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, PHP cgi spawned pid:$pid\n";}
			return true;
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, PHP cgi spawned failed\n";}
			
		}
		
		return true;
		
	}
	
	public function PHP_ENGINE($defaultfile=null){
		if($defaultfile==null){$defaultfile="index.php";}
		$this->IndexFile=$defaultfile;
		return $this->php_fpm();
	}
	
	
	private function spawn_fcgi_config($root=null){
		if(!$this->spawn_fcgi()){return null;}
		
		$this->fastcgi_params();
		$index="index.php";
		if($this->IndexFile<>null){ $index=$this->IndexFile;}	
		if(strpos($index, " ")>0){
			$indexR=explode(" ", $index);
			$index=$indexR[0];
		}
		
		$f[]="\tlocation ~ [^/]\.php(/|\$) {";
		if($root<>null){$f[]="\t\troot\t$root;";}
		$f[]="\t\tindex\t$index;";
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
	
	
	private function php5_params($root){
		$unix=new unix();
		$sock= new sockets();
		$phpfpm=$unix->APACHE_LOCATE_PHP_FPM();
		$EnablePHPFPM=$sock->GET_INFO("EnablePHPFPM");
		if(!is_file($phpfpm)){$EnablePHPFPM=0;}
		$EnableArticaApachePHPFPM=$sock->GET_INFO("EnableArticaApachePHPFPM");
		if(!is_numeric($EnableArticaApachePHPFPM)){$EnableArticaApachePHPFPM=0;}
		if($EnableArticaApachePHPFPM==0){$EnablePHPFPM=0;}
		
		
		if($EnablePHPFPM==1){
			return "\t\troot $root;\n\t\tfastcgi_pass unix:/var/run/php-fpm.sock;\n\t\tinclude fastcgi_params;\n";
		}
		
		return "\t\troot $root;\n\t\tfastcgi_pass unix:/var/run/php-fcgi.sock;\n\t\tinclude fastcgi_params;\n";
	}
	

	
	public function php_fpm($Forceindex=null,$root=null,$Disable_phpfpm=0){
		$unix=new unix();
		$sock=new sockets();
		$index="index.php";
		if($this->IndexFile<>null){ $index=$this->IndexFile;}
		if($Forceindex<>null){$index=$Forceindex;}
		
		$phpfpm=$unix->APACHE_LOCATE_PHP_FPM();
		$EnablePHPFPM=$sock->GET_INFO("EnablePHPFPM");
		if(!is_numeric($EnablePHPFPM)){$EnablePHPFPM=0;}
		if(!is_file($phpfpm)){$EnablePHPFPM=0;}
		
		if($Disable_phpfpm==1){
			$results=$this->spawn_fcgi_config($root);
			if($results<>null){return $results;}
		}
		
		if($EnablePHPFPM==0){return $this->spawn_fcgi_config();}
		$this->fastcgi_params();
		
		
		
		if(strpos($index, " ")>0){
			$indexR=explode(" ", $index);
			$index=$indexR[0];
		}
		
			$f[]="\tlocation ~ [^/]\.php(/|\$) {";
			$f[]="\t\tindex\t$index;";
			if($root<>null){$f[]="\t\troot\t$root;";}
			$f[]="\t\tfastcgi_split_path_info ^(.+?\.php)(/.*)\$;";
			$f[]="\t\tif (!-f \$document_root\$fastcgi_script_name) {";
			$f[]="\t\t\treturn 404;";
			$f[]="\t\t}";
			$f[]=" ";
			$f[]="\t\tfastcgi_buffers 8 16k;";
			$f[]="\t\tfastcgi_buffer_size 32k;";
			$f[]="\t\tfastcgi_read_timeout 300;";
			$f[]="\t\tfastcgi_connect_timeout 300;";
			$f[]="\t\tfastcgi_send_timeout 300;";
			if($this->BuildFramework){
				
				$f[]="\t\tfastcgi_pass unix:/var/run/php-fpm-framework.sock;";
			}else{
				$f[]="\t\tfastcgi_pass unix:/var/run/php-fpm.sock;";
			}
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
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx, Load-balancing [{$ligne["poolname"]}]\n";}
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
