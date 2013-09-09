<?php
if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}
if($GLOBALS["VERBOSE"]){print "Starting......: artica-executor instantiate instantiate class class.ini-frame.inc...\n";}
include_once(dirname(__FILE__).'/class.ini-frame.inc');
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.ini.inc");
include_once(dirname(__FILE__)."/class.hd.inc");
if($GLOBALS["VERBOSE"]){print "Starting......: artica-executor instantiate instantiate classes done...\n";}
$GLOBALS["SSLKEY_PATH"]="/etc/ssl/certs/apache";
$GLOBALS["MAX_RECUSIVE_DIRS"]=400;
$GLOBALS["RECURSVIVE_INC"]=0;
if(!isset($GLOBALS["DEBUG_GREP"])){$GLOBALS["DEBUG_GREP"]=false;}
define( 'RE_IP_BYTE', '(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])');
define( 'RE_IP_ADD' , RE_IP_BYTE . '\.' . RE_IP_BYTE . '\.' . RE_IP_BYTE . '\.' . RE_IP_BYTE );
define( 'RE_IP_PREFIX', '(3[0-2]|[12]?\d)');
define( 'RE_IP_BLOCK', RE_IP_ADD . '\/' . RE_IP_PREFIX);
define( 'RE_IPV6_WORD', '([0-9A-Fa-f]{1,4})' );
define( 'RE_IPV6_GAP', ':(?:0+:)*(?::(?:0+:)*)?' );
define( 'RE_IPV6_V4_PREFIX', '0*' . RE_IPV6_GAP . '(?:ffff:)?' );
define( 'RE_IPV6_PREFIX', '(12[0-8]|1[01][0-9]|[1-9]?\d)');
define( 'RE_IPV6_ADD', '(:(:' . RE_IPV6_WORD . '){1,7}|' . RE_IPV6_WORD . '(:{1,2}' . RE_IPV6_WORD . '|::$){1,7})' );
define( 'RE_IPV6_BLOCK', RE_IPV6_ADD . '\/' . RE_IPV6_PREFIX );
define( 'IP_ADDRESS_STRING','(?:' .RE_IP_ADD . '(\/' . RE_IP_PREFIX . '|)'.'|'.RE_IPV6_ADD . '(\/' . RE_IPV6_PREFIX . '|)' .')' );


class unix {
	public $mysql_error=null;
	var $proc_status;
	
	function unix(){
		@date_default_timezone_set($this->getLocalTimezone());
	}
	



	PUBLIC function LOCATE_SYSLOG_INITD(){
			if(is_file("/etc/init.d/rsyslog")){return "/etc/init.d/rsyslog";}
			if(is_file("/etc/init.d/syslog")){return "/etc/init.d/syslog";}
			if(is_file("/etc/init.d/sysklogd")){return "/etc/init.d/sysklogd";}
	}
	
	
	private function getLocalTimezone(){
		if(isset($GLOBALS["GET_TIME_ZONE"])){if($GLOBALS["GET_TIME_ZONE"]<>null){return $GLOBALS["GET_TIME_ZONE"];}}
		if(function_exists("debug_mem")){debug_mem();}else{if($GLOBALS["VERBOSE"]){print "Starting......: debug_mem no such function...\n";}}
		$page=$this->CurrentPageName();
		if($page=="exec.artica-filter.php"){return;}
		
		
		$timezones=trim(@file_get_contents('/etc/artica-postfix/settings/Daemons/timezones'));
		if($timezones<>null){$GLOBALS["GET_TIME_ZONE"]=$timezones;return $GLOBALS["GET_TIME_ZONE"];}
	
		
		$iTime = time();
		$arr = localtime($iTime);
		$arr[5] += 1900;
		$arr[4]++;
		$iTztime = gmmktime($arr[2], $arr[1], $arr[0], $arr[4], $arr[3], $arr[5]);
		$offset = doubleval(($iTztime-$iTime)/(60*60));
		$zonelist =
		array('Kwajalein' => -12.00,'Pacific/Midway' => -11.00,'Pacific/Honolulu' => -10.00,'America/Anchorage' => -9.00,'America/Los_Angeles' => -8.00,'America/Denver' => -7.00,'America/Tegucigalpa' => -6.00,'America/New_York' => -5.00,'America/Caracas' => -4.30,'America/Halifax' => -4.00,'America/St_Johns' => -3.30,'America/Argentina/Buenos_Aires' => -3.00,'America/Sao_Paulo' => -3.00,'Atlantic/South_Georgia' => -2.00,'Atlantic/Azores' => -1.00,'Europe/Dublin' => 0,'Europe/Belgrade' => 1.00,'Europe/Minsk' => 2.00,'Asia/Kuwait' => 3.00,'Asia/Tehran' => 3.30,'Asia/Muscat' => 4.00,'Asia/Yekaterinburg' => 5.00,'Asia/Kolkata' => 5.30,'Asia/Katmandu' => 5.45,'Asia/Dhaka' => 6.00,'Asia/Rangoon' => 6.30,'Asia/Krasnoyarsk' => 7.00,'Asia/Brunei' => 8.00,'Asia/Seoul' => 9.00,'Australia/Darwin' => 9.30,'Australia/Canberra' => 10.00,'Asia/Magadan' => 11.00,'Pacific/Fiji' => 12.00,'Pacific/Tongatapu' => 13.00);
		$index = array_keys($zonelist, $offset);
		if(sizeof($index)!=1){return;}
		$GLOBALS["GET_TIME_ZONE"]=$index[0];
		@file_put_contents('/etc/artica-postfix/settings/Daemons/timezones', $index[0]);
		$GLOBALS["GET_TIME_ZONE"]= $index[0];
		return $GLOBALS["GET_TIME_ZONE"];
	}	
	
	
	private function CurrentPageName(){
		$phpPage=basename($_SERVER["SCRIPT_FILENAME"]);
		return $phpPage;}
	
	public function StringToGrep($search){
		$search=str_replace(".","\.",$search);
		$search=str_replace("*",".*?",$search);
		$search=str_replace("(","\(",$search);
		$search=str_replace(")","\)",$search);
		$search=str_replace("[","\[",$search);
		$search=str_replace("]","\]",$search);
		return $search;
	}
	
	
	PUBLIC function LOCATE_SSHD_PID_PATH(){
		if(is_file("/var/run/sshd.pid")){return "/var/run/sshd.pid";}
		if(is_file("/var/run/sshd.init.pid")){return "/var/run/sshd.init.pid";}
	}
	
	PUBLIC function LOCATE_SSHD_CONFIG_PATH(){
		if(is_file("/etc/ssh/sshd_config")){return "/etc/ssh/sshd_config";}
		
	}	
	
	
public function LOCATE_VBoxHeadless(){
	if(is_file("/usr/lib/virtualbox/VBoxHeadless")){return "/usr/lib/virtualbox/VBoxHeadless";}
	if(is_file("/usr/local/lib/virtualbox/VBoxHeadless")){return "/usr/local/lib/virtualbox/VBoxHeadless";}
	}	
	
	public function LOCATE_CYRRECONSTRUCT(){
		if(is_file("/usr/sbin/cyrreconstruct")){return "/usr/sbin/cyrreconstruct";}
		if(is_file("/usr/lib/cyrus/bin/reconstruct")){return "/usr/lib/cyrus/bin/reconstruct";}
		if(is_file("/usr/lib/cyrus-imapd/reconstruct")){return "/usr/lib/cyrus-imapd/reconstruct";}
		$this->events("Unable to stat cyrus reconstruct tool");
	}
	
	public function LOCATE_ROUNDCUBE_WEBFOLDER(){
		if(is_file("/usr/share/roundcube/index.php")){return "/usr/share/roundcube";}
	    if(is_file("/usr/share/roundcubemail/index.php")){return "/usr/share/roundcubemail";}
 	}
	
	public function LOCATE_SLPAD_PATH(){
		if(is_file("/usr/sbin/slapd")){return "/usr/sbin/slapd";}
		if(is_file("/usr/lib/openldap/slapd")){return "/usr/lib/openldap/slapd";}
		return $this->find_program("slpad");
	}
	
	public function LOCATE_SLPAD_CONF(){
		if(is_file('/etc/ldap/slapd.conf')){return '/etc/ldap/slapd.conf';}
		if(is_file('/etc/openldap/slapd.conf')){return '/etc/openldap/slapd.conf';}
		if(is_file('/etc/openldap/ldap.conf')){return '/etc/openldap/ldap.conf';}
		if(is_file('/opt/artica/etc/openldap/slapd.conf')){return '/opt/artica/etc/openldap/slapd.conf';}
		if(is_file('/usr/local/etc/openldap/slapd.conf')){return '/usr/local/etc/openldap/slapd.conf';} 
	}	
	
	public function LOCATE_SIEVEC(){
		if(is_file("/usr/sbin/sievec")){return "/usr/sbin/sievec";}
		if(is_file("/usr/lib/cyrus/bin/sievec")){return "/usr/lib/cyrus/bin/sievec";}
		if(is_file("/usr/lib/cyrus-imapd/sievec")){return "/usr/lib/cyrus-imapd/sievec";}
		$this->events("Unable to stat cyrus sievec tool");
	}
	
	public function LOCATE_CLAMDCONF(){
		if(is_file("/etc/clamav/clamd.conf")){return "/etc/clamav/clamd.conf";}
		if(is_file("/etc/clamd.conf")){return "/etc/clamd.conf";}
		if(is_file("/usr/local/etc/clamav/clamd.conf")){return "/usr/local/etc/clamav/clamd.conf";}
		if(is_file("/usr/local/etc/clamd.conf")){return "/usr/local/etc/clamd.conf";}
		}
		
	public function APACHE_LOCATE_PHP_FPM(){
		$phpfpm=$this->find_program('php5-fpm');
		if(is_file($phpfpm)){return $phpfpm;}
		$phpfpm=$this->find_program('php-fpm');
		return $phpfpm;
	}
	
	public function CPU_NUMBER(){
		
		if(is_file("/etc/artica-postfix/CPU_NUMBER")){
			$time=$this->file_time_min("/etc/artica-postfix/CPU_NUMBER");
			if($time<60){return @file_get_contents("/etc/artica-postfix/CPU_NUMBER");}
		}
		
		if(isset($GLOBALS["CPU_NUMBER"])){return $GLOBALS["CPU_NUMBER"];}
		$cat=$this->find_program("cat");
		$grep=$this->find_program("grep");
		$cut=$this->find_program("cut");
		$wc=$this->find_program("wc");
		$cmd="$cat /proc/cpuinfo |$grep \"model name\" |$cut -d: -f2|$wc -l 2>&1";
		$GLOBALS["CPU_NUMBER"]=exec($cmd);
		writelogs_framework("$cmd ->{$GLOBALS["CPU_NUMBER"]}",__FUNCTION__,__FILE__,__LINE__);
		if(!is_numeric($GLOBALS["CPU_NUMBER"])){$GLOBALS["CPU_NUMBER"]=1;}
		if($GLOBALS["CPU_NUMBER"]==0){$GLOBALS["CPU_NUMBER"]=1;}
		@unlink("/etc/artica-postfix/CPU_NUMBER");
		@file_put_contents("/etc/artica-postfix/CPU_NUMBER", $GLOBALS["CPU_NUMBER"]);
		return $GLOBALS["CPU_NUMBER"];
	}	
		
	public function LOCATE_AWSTATS_BIN(){
		$f[]="/usr/local/awstats/cgi-bin/awstats.pl";
		$f[]="/usr/local/awstats/wwwroot/cgi-bin/awstats.pl";
		$f[]="/usr/lib/cgi-bin/awstats.pl";
		$f[]="/usr/share/awstats/wwwroot/cgi-bin/awstats.pl";
		$f[]="/var/www/awstats/awstats.pl";
		$f[]="/srv/www/cgi-bin/awstats.pl";
		$f[]="/opt/artica/awstats/wwwroot/cgi-bin/awstats.pl";
		$f[]="/root/awstats-6.3/wwwroot/cgi-bin/awstats.pl";
		$f[]="/home/apache/cgi-bin/awstats.pl";
		$f[]="/srv/www/cgi-bin/awstats/awstats.pl";
		while (list ($num, $path) = each ($f) ){if(is_file($path)){return $path;}}			
	}
	private function LOCATE_AWSTATS_POSSIBLE_DIRS(){
		$f[]="/usr/local/awstats";
		$f[]="/usr/share/awstats";
		$f[]="/root/awstats-6.3";
		$f[]="/srv/www/ispcp/engine/awstats";
		return $f;
	}
	
	
	public function LOCATE_AWSTATS_BUILDSTATICPAGES_BIN(){
		$f[]="/usr/local/awstats/tools/awstats_buildstaticpages.pl";
		$f[]="/usr/share/awstats/tools/awstats_buildstaticpages.pl";
		$f[]="/root/awstats-6.3/tools/awstats_buildstaticpages.pl";
		$f[]="/usr/share/awstats/awstats_buildstaticpages.pl";
		$f[]="/usr/share/doc/awstats/examples/awstats_buildstaticpages.pl";
		$f[]="/srv/www/ispcp/engine/awstats/awstats_buildstaticpages.pl";
		while (list ($num, $path) = each ($f) ){if(is_file($path)){return $path;}}
		
		
	}
	public function LOCATE_AWSTATS_PLUGINS_DIR(){
		$possibledirs=$this->LOCATE_AWSTATS_POSSIBLE_DIRS();
		while (list ($num, $path) = each ($possibledirs) ){
			if(is_dir($path."/plugins")){return $path."/plugins";}
		}
	}
	
	
	
	public function LOCATE_VBOX_ADDITIONS_PID(){	
		if(is_file("/var/run/vboxadd-service")){return "/var/run/vboxadd-service";}
		if(is_file("/var/lock/subsys/vboxadd-service")){return "/var/lock/subsys/vboxadd-service";}
		if(is_file("/var/run/vboxadd-service")){return "/var/run/vboxadd-service";}
		if(is_file("/var/run/vboxadd-service.pid")){return "/var/run/vboxadd-service.pid";}
	}
	
	public function RECONFIGURE_SQUID(){
		$php5=$this->LOCATE_PHP5_BIN();
		$nohup=$this->find_program("nohup");
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --reload-squid >/dev/null 2>&1");
		
	}
	
	public function isDirInFsTab($directory){
		$directoryRegex=$directory;
		$directoryRegex=str_replace("/", "\/", $directoryRegex);
		$directoryRegex=str_replace(".", "\.", $directoryRegex);
		$f=explode("\n", @file_get_contents("/etc/fstab"));
		while (list ($index, $val) = each ($f) ){
			if(preg_match("#^(.+)\s+$directoryRegex#", $val,$re)){
				if($GLOBALS["VERBOSE"]){echo "$directory must be mounted on {$re[1]}\n";}
				return $re[1];
			}
			}
			return null;
	}
	
	
	public function PIDOF_BY_PORT($port){
		$fuser=$this->find_program("fuser");
		$PIDLINE=null;
		$RES=array();
		exec("$fuser $port/tcp 2>&1",$results);
		while (list ($index, $val) = each ($results) ){
			if(preg_match("#tcp:\s+(.+)#", $val,$re)){
				if($GLOBALS["VERBOSE"]){echo "PIDOF_BY_PORT: Found {$re[1]}\n";}
				$PIDLINE=$re[1];
			}
		}
		
		if($PIDLINE<>null){
			$tr=explode(" ",$PIDLINE);
			while (list ($a, $b) = each ($tr) ){
				$b=trim($b);
				if($b==null){continue;}
				if(!is_numeric($b)){continue;}
				if($b<5){continue;}
				$RES[$b]=$b;
			}
		}
		return $RES;
	}
	
	
	public function NGINX_COMPILE_PARAMS(){
		if(isset($GLOBALS[__CLASS__.".".__FUNCTION__])){return $GLOBALS[__CLASS__.".".__FUNCTION__];}
		$nginx=$this->find_program("nginx");
		if(!is_file($nginx)){return;}
		exec("$nginx -V 2>&1",$results);
		
		$NOT["CONFIGURE"]=true; // 	1
		$NOT["ARGUMENTS:"]=true; // 	1
		$NOT["PREFIX"]=true; // 	/etc/nginx
		$NOT["SBIN-PATH"]=true; // 	/usr/sbin/nginx
		$NOT["CONF-PATH"]=true; // 	/etc/nginx/nginx.conf
		$NOT["ERROR-LOG-PATH"]=true; // 	/var/log/nginx/error.log
		$NOT["HTTP-LOG-PATH"]=true; // 	/var/log/nginx/access.log
		$NOT["PID-PATH"]=true; // 	/var/run/nginx.pid
		$NOT["LOCK-PATH"]=true; // 	/var/run/nginx.lock
		$NOT["HTTP-CLIENT-BODY-TEMP-PATH"]=true; // 	/var/cache/nginx/client_temp
		$NOT["HTTP-PROXY-TEMP-PATH"]=true; // 	/var/cache/nginx/proxy_temp
		$NOT["HTTP-FASTCGI-TEMP-PATH"]=true; // 	/var/cache/nginx/fastcgi_temp
		$NOT["HTTP-UWSGI-TEMP-PATH"]=true; // 	/var/cache/nginx/uwsgi_temp
		$NOT["HTTP-SCGI-TEMP-PATH"]=true; // 	/var/cache/nginx/scgi_temp
		$NOT["USER"]=true; // 	nginx
		$NOT["GROUP"]=true; //
		$NOT["WITH-HTTP_GUNZIP_MODULE"]=true; //
		
		while (list ($key, $value) = each ($results) ){
			if(preg_match("#nginx version: (.*?)\/([0-9\.]+)#", $value,$re)){
				$ARRAY["DEF"]["VENDOR"]=$re[1];
				$ARRAY["DEF"]["VERSION"]=$re[2];
				continue;}
			if(preg_match("#TLS SNI support enabled#", $value,$re)){$ARRAY["DEF"]["TLS"]=true;continue;}
			if(preg_match("#configure arguments:\s+(.*)#",$value,$re)){
				$f=explode(" ",$value);
				while (list ($a, $b) = each ($f) ){
		
					if(preg_match("#add-module=(.+)#",$b,$re)){
						$module=basename($re[1]);
						
						$ARRAY["MODULES"][$module]=$module;continue;
					}
		
					$b=str_replace("--", "", $b);
		
					if(preg_match("#(.+?)=(.+)#", $b,$ri)){
						if(isset($NOT[strtoupper($b)])){continue;}
						$ARRAY["ARGS"][strtoupper($ri[1])]=trim($ri[2]);
						continue;
					}
					if(isset($NOT[strtoupper($b)])){continue;}
					$ARRAY["ARGS"][strtoupper($b)]=true;
				}
					
			}
		}
		$GLOBALS[__CLASS__.".".__FUNCTION__]=$ARRAY;
		return $ARRAY;
	}
	

	public function InterfaceToIP($eth){
		include_once("/usr/share/artica-postfix/ressources/class.system.nics.inc");
	if($eth==null){return array("IP"=>null,"NETMASK"=>null,"NETWORK"=>null,"IPADDR"=>null);}
	$nics=new system_nic($eth);
	if($nics->IPADDR<>null){
		$PR=explode(".",$nics->IPADDR);
		
		
		return array("IP"=>$nics->IPADDR,"IPADDR"=>$nics->IPADDR,
				"NETMASK"=>$nics->NETMASK,
				"NETWORK"=>"{$PR[0]}.{$PR[1]}.{$PR[2]}.0"
				);		
	}
	$unix=new unix();
	$arrayTCP=$unix->NETWORK_ALL_INTERFACES();
	$ALLARRAY=$arrayTCP[$eth];
	
	$PR=explode(".",$ALLARRAY["IPADDR"]);
	
	return array("IP"=>$ALLARRAY["IPADDR"],"NETMASK"=>$ALLARRAY["NETMASK"]
			,"NETWORK"=>"{$PR[0]}.{$PR[1]}.{$PR[2]}.0","IPADDR"=>$ALLARRAY["IPADDR"],
			
			);
}
	
	
	public function CreateUnixUser($username,$groupname=null,$displayname=null){
		if($this->UnixUserExists($username)){return true;}
		include_once(dirname(__FILE__)."/class.settings.inc");
		if(!isset($GLOBALS["CLASS_USERSMENUS"])){$user=new usersMenus();$GLOBALS["CLASS_USERSMENUS"]=$user;}else{$user=$GLOBALS["CLASS_USERSMENUS"];}
		
		if($groupname==null){$groupname=$username;}
		if($displayname==null){$displayname=$username;}
		
		if(!$this->UnixGroupExists($groupname)){
			$groupadd=$this->find_program("groupadd");
			$cmd="$groupadd $groupname >/dev/null 2>&1";
			if($GLOBALS["VERBOSE"]){echo "CreateUnixUser() $user->LinuxDistriCode \"$cmd\"\n";}
			shell_exec($cmd);	
			if(!$this->UnixGroupExists($groupname)){return false;}
		}
		
		$cmd=null;
		$useradd=$this->find_program("useradd");
		if($user->LinuxDistriCode=="CENTOS"){
			$cmd="$useradd -m -g $groupname -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
		}
		if($user->LinuxDistriCode=="SUSE"){
			$cmd="$useradd -m -g $groupname -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
		}		
		if($user->LinuxDistriCode=="FEDORA"){
			$cmd="$useradd -m -g $groupname -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
		}
		if($user->LinuxDistriCode=="MANDRAKE"){
			$cmd="$useradd -m -g $groupname -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
		}						
		if($cmd==null){
			$cmd="useradd -m -g $groupname -G adm -d /home/$username -s /bin/bash -c \"$displayname\" $username >/dev/null 2>&1";
		}
		
		
		
		if($GLOBALS["VERBOSE"]){echo "CreateUnixUser() $user->LinuxDistriCode \"$cmd\"\n";}
		shell_exec($cmd);	
		if(!$this->UnixUserExists($username)){
		if($user->LinuxDistriCode=="CENTOS"){
			$cmd="$useradd -m -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
		}
		if($user->LinuxDistriCode=="SUSE"){
			$cmd="$useradd -m -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
		}		
		if($user->LinuxDistriCode=="FEDORA"){
			$cmd="$useradd -m -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
		}
		if($user->LinuxDistriCode=="MANDRAKE"){
			$cmd="$useradd -m -d /home/$username -c \"$displayname\" $username >/dev/null 2>&1";
		}						
		if($cmd==null){
			$cmd="useradd -m --d /home/$username -s /bin/bash -c \"$displayname\" $username >/dev/null 2>&1";
		}
			if($GLOBALS["VERBOSE"]){echo "CreateUnixUser() $user->LinuxDistriCode \"$cmd\"\n";}
			shell_exec($cmd);
		}
		
		return $this->UnixUserExists($username);
		
		
	}
	
	public function SystemUserExists($username){
		$userinfo = posix_getpwnam($username);
		if(!isset($userinfo["uid"])){return false;}
		if(!is_numeric($userinfo["uid"])){return false;}
		if($userinfo["uid"]<1){return false;}
		return true;
	}
	
	public function SystemUserGetuid($username){
		$userinfo = posix_getpwnam($username);
		if(!isset($userinfo["uid"])){return null;}
		if(!is_numeric($userinfo["uid"])){return null;}
		if($userinfo["uid"]<1){return null;}
		return $userinfo["uid"];
	}

	public function SystemGroupUid($groupname){
		$userinfo = posix_getgrnam($groupname);
		if(!isset($userinfo["gid"])){return null;}
		if(!is_numeric($userinfo["gid"])){return null;}
		if($userinfo["gid"]<1){return null;}
		return $userinfo["gid"];
	}	
	
	
	public function SystemCreateUser($username,$group=null,$xshell=null,$sHome=null){
		
		if(!$this->SystemUserExists($username)){
			$useradd=$this->find_program("useradd");
			if(!is_file($useradd)){return;}
			if($xshell==null){$xshell="/bin/sh";}
			if($group<>null){
				if(!$this->SystemGroupExists($group)){
					if($GLOBALS["VERBOSE"]){echo "Creating Group `$group`...\n";}
					if($this->SystemCreateGroup($group)){return false;}}
			}
			$cmd[]=$useradd;
			if(strlen($xshell)>2){$cmd[]="-s $xshell";}
			if(strlen($sHome)>2){$cmd[]="-d \"$sHome\"";}
			if($group<>null){$cmd[]="-g $group";}
			
			
			$cmd[]=$username;
			$cmd_finale=@implode(" ", $cmd);
			if($GLOBALS["VERBOSE"]){echo $cmd_finale."\n";}
			exec("$cmd_finale 2>&1",$results);
			if($GLOBALS["VERBOSE"]){echo @implode("\n", $results);}
		}
		
		if($group<>null){
			if(!$this->SystemGroupExists($group)){if($this->SystemCreateGroup($group)){return false;}}
			if(!$this->SystemIsUserIngroup($username,$group)){
				$usermod=$this->find_program("usermod");
      			shell_exec("$usermod -a -G $group $username >/dev/null 2>&1 &");
       			shell_exec("$usermod -A $group $username >/dev/null 2>&1 &");
       			shell_exec("$usermod --append $group $username >/dev/null 2>&1 &");
			}
		}
	}
	
	public function SystemCreateGroup($group){
		$groupadd=$this->find_program("groupadd");
		if(!is_file($groupadd)){return false;}
		if($GLOBALS["VERBOSE"]){echo "Create group $groupadd $group\n";}
		shell_exec("$groupadd $group");
		return $this->SystemGroupExists($group);
	}
	
	public function SystemGroupExists($groupname){
		$userinfo = posix_getgrnam($groupname);
		if(!isset($userinfo["gid"])){return false;}
		if(!is_numeric($userinfo["gid"])){return false;}
		if($userinfo["gid"]<1){return false;}
		return true;		
	}
	
	public function SystemIsUserIngroup($username,$groupname){
		$userinfo = posix_getgrnam($groupname);
		while (list ($num, $user) = each ($userinfo["members"]) ){
			if($user==$username){return true;}
			
		}
		return false;
		
	}
	
	
	public function JAVA_HOME_GET(){
		$f=explode("\n", @file_get_contents("/etc/environment"));
		while (list ($num, $val) = each ($f) ){
			if(preg_match("#JAVA_HOME=(.+)#", $val,$re)){
				$re[1]=str_replace('"', "", $re[1]);
				if(is_dir($re[1])){return $re[1];}
			}
		}
		
	}
	
	public function MYSQL_REPAIR_TABLE_FILE_NOT_FOUND($file,$db,$table){
		$Dir=dirname($file);
		if($table==null){return;}
		$FF=array();
		$files=$this->DirFiles("$Dir","$table-[0-9]+\.BAK$");
		while (list ($filename, $f) = each ($files) ){
			if(!preg_match("#$table-([0-9]+)\.BAK$#", $filename,$re)){continue;}
			$FF[$re[1]]=$filename;
		}
		
		krsort($FF);
		while (list ($index, $filename) = each ($FF) ){$tt[]=$filename;}
		$lastfile=$tt[0];
		$L[]="last backuped file=\"$lastfile\"<br>\n";
		
		
		if(!is_file($file)){
			if($lastfile<>null){
				$L[]="replace backuped file=$Dir/$lastfile to $file<br>\n";
				@copy("$Dir/$lastfile", $file);
			}
		}
		if(is_file($file)){
			@chmod($file, 0755);
			@chown($file, "mysql");
			@chgrp($file, "mysql");
			$L[]="$file apply permissions done...<br>\n";
		}else{
			$fileTMD=str_replace(".MYD", ".TMD", $file);
			if(is_file($fileTMD)){@copy($fileTMD,$file);@chown($file, "mysql");@chgrp($file, "mysql");}else{
			$L[]="$file no such file....<br>\n";
			}
		}		
		return @implode("", $L);
		
	}
	
	
	
	
	public function JAVA_HOME_SET($dir){
		$f=explode("\n", @file_get_contents("/etc/environment"));
		while (list ($num, $val) = each ($f) ){
			if(preg_match("#JAVA_HOME=(.+)#", $val,$re)){
				$re[1]=str_replace('"', "", $re[1]);
				if($re[1]==$dir){return;}
				$f[$num]="JAVA_HOME=\"$dir\"";
				@file_put_contents("/etc/environment", @implode("\n", $f));
				return;
			}
		}
		
		$f[]="JAVA_HOME=\"$dir\"";
		@file_put_contents("/etc/environment", @implode("\n", $f));
		
	}	
	
	public function UnixGroupExists($groupname){
		$getent=$this->find_program("getent");
		exec("$getent group $groupname 2>&1",$results);
		while (list ($num, $val) = each ($results) ){
			if(preg_match("#^$groupname#", $val)){
				if($GLOBALS["VERBOSE"]){echo "UnixGroupExists() $val Match\n";}
				return true;}else{if($GLOBALS["VERBOSE"]){echo "UnixGroupExists() $val no match\n";}}
		}
		if($GLOBALS["VERBOSE"]){echo "UnixGroupExists() group:$groupname does not exists in database\n";}	
		return false;
		
	}
	
	public function UnixUserExists($username){
		
		$getent=$this->find_program("getent");
		$cmd="$getent shadow $username 2>&1";
		if($GLOBALS["VERBOSE"]){echo "UnixUserExists() \"$cmd\"\n";}
		exec($cmd,$results);
		while (list ($num, $val) = each ($results) ){
			if(preg_match("#^$username#", $val)){
				if($GLOBALS["VERBOSE"]){echo "UnixUserExists() $val Match\n";}
				return true;}
				if($GLOBALS["VERBOSE"]){echo "UnixUserExists() $val No match\n";}
		}
		if($GLOBALS["VERBOSE"]){echo "UnixUserExists() user:$username does not exists in database\n";}	
		return false;
	}
	
	public function file_ext($path){
		$path_info = pathinfo($path);
		return $path_info['extension'];
		
	}
	
	public function GetMyHostId(){
		
		$hostid=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/HOSTID"));
		if($hostid<>null){return $hostid;}
	
		
		$hostidbin=$this->find_program("hostid");
		if(is_file($hostidbin)){
			exec("$hostidbin 2>&1",$results);
			$hostid=md5(trim(@implode("", $results).time()));
			if($hostid<>"00000000"){
				@file_put_contents("/etc/artica-postfix/settings/Daemons/HOSTID", $hostid);
				return $hostid;
			}
		}
	
		$hostid=md5(time());
		@file_put_contents("/etc/artica-postfix/settings/Daemons/HOSTID", $hostid);
		return $hostid;
	}	
	
	
	public function SquidStatsApplianceUri(){
		$RemoteStatisticsApplianceSettings=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/RemoteStatisticsApplianceSettings")));
		if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
		if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
		$REMOTE_SSERVER=$RemoteStatisticsApplianceSettings["SERVER"];
		$REMOTE_SPORT=$RemoteStatisticsApplianceSettings["PORT"];
		$REMOTE_SSL=$RemoteStatisticsApplianceSettings["SSL"];
		if($REMOTE_SSL==1){$refix="https";}else{$refix="http";}
		return "$refix://$REMOTE_SSERVER:$REMOTE_SPORT";		
		
	}
	
	
	public function IsSquidTaskCanBeExecuted(){
		if(!class_exists("settings_inc")){include_once(dirname(__FILE__)."/class.settings.inc");}
		$users=new settings_inc();
		$sock=new sockets();
		$update=true;
		$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
		$CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
		if(!is_numeric($CategoriesRepositoryEnable)){$CategoriesRepositoryEnable=0;}
		if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
		if($EnableWebProxyStatsAppliance==1){return true;}	
		$CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
		if($CategoriesRepositoryEnable==1){return true;}
		if(!$users->SQUID_INSTALLED){$update=false;}
		return $update;
	}
	
	public function squidclient_builduri(){
		
		$squidbin=$this->LOCATE_SQUID_BIN();
		if(!is_file($squidbin)){return;}
		
		$squidclient=$this->find_program("squidclient");
		if($squidclient==null){
			writelogs_framework("Unable to stat squidclient !!",__FUNCTION__,__LINE__);;
			return;
		}
		
		if(!is_file("/etc/artica-postfix/settings/Daemons/SquidMgrListenPort")){
			$php5=$this->LOCATE_PHP5_BIN();
			shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
			shell_exec("/etc/init.d/squid restart");
		}
		
		if(!is_file("/etc/artica-postfix/settings/Daemons/SquidMgrListenPort")){
			$http_port=$this->squid_get_alternate_port();
		}else{
			$http_port=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidMgrListenPort"));
		}
		if(!is_numeric($http_port)){
			$http_port=$this->squid_get_alternate_port();
		}
		$interface="127.0.0.1";
		if(preg_match("#([0-9\.]+):([0-9]+)#",$http_port,$re)){$interface=$re[1];$http_port=$re[2];}
		if($interface=="0.0.0.0"){$interface="127.0.0.1";}
		$cmd="$squidclient -l 127.0.0.1 -h $interface -p $http_port mgr";
		writelogs_framework("$cmd",__FUNCTION__,__LINE__);
		return $cmd;
	}	
	
	public function tune2fs_values($dev){
		$tune2fs=$this->find_program("tune2fs");
		$cmd="$tune2fs -l $dev 2>&1";
		exec($cmd,$results);
		$array=array();
		
		while (list ($num, $line) = each ($results)){
			
			if(preg_match("#Bad magic number#", $line)){
				$array["ERROR"]=true;
				$array["ERROR_TEXT"]=$line;
			}
				
			
			if(!preg_match("#^(.+?):\s+(.+)#i",$line,$re)){continue;}
			$key=trim(strtoupper($re[1]));
			$key=str_replace(" ", "_", $key);
			$value=$re[2];
			$array[$key]=$value;
		}
		
		$results=array();
		$df=$this->find_program("df");
		exec("df -i $dev 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#^.*?\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%\s+(.+)#", $ligne,$re)){
				$array["INODES_MAX"]=$re[1];
				$array["INODES_USED"]=$re[2];
				$array["INODES_AVAILABLE"]=$re[3];
				$array["INODES_POURC"]=$re[4];
				break;
			}
		}
		$BLKID=$this->BLKID_INFOS($dev);
		while (list ($num, $ligne) = each ($BLKID) ){
			$array[$num]=$ligne;
		}
		
		return $array;
	}
	


	public function sysctl($key,$value){
		$keyS=str_replace(".", "\.", $key);
		$t=false;
		$f=explode("\n",@file_get_contents("/etc/sysctl.conf"));
		while (list ($num, $val) = each ($f) ){
			if(substr($val, 0,1)=="#"){continue;}
			if(preg_match("#^$keyS.*?=#", $val)){
				$f[$num]="$key = $value";
				$t=true;
				break;
			}
		}
		
		if(!$t){
			$f[]="$key = $value";
		}
		reset($f);
		@file_put_contents("/etc/sysctl.conf", implode("\n", $f));
		
	}
	
	
	
	
	
	public function LOCATE_CLAMDSOCKET(){
		$f=@explode("\n",@file_get_contents($this->LOCATE_CLAMDCONF()));
		while (list ($num, $val) = each ($f) ){
			if(preg_match("#LocalSocket\s+(.+)#",$val,$re)){
				return trim($re[1]);
			}
		}
	}
	
	public function POLICYD_WEIGHT_GET($key){
		$f=@explode("\n",@file_get_contents($this->LOCATE_POLICYDWEIGHT_CONF()));
		while (list ($num, $val) = each ($f) ){
			if(preg_match("#\$$key.*?=(.+?);#",$val)){
				return trim($re[1]);
			}
		}
	}
	
	public function SQUID_GET_DATADIR(){
		$unix=new unix();
		$squidbin=$this->find_program("squid3");
		if(!is_file($squidbin)){$squidbin=$this->find_program("squid");}
		exec("$squidbin -v",$res);
		$line=@implode(" ",$res);
		if(preg_match("#--datadir=(.+?)'#",$line,$re)){return trim($re[1]);}
	}
	
	
	public function LOCATE_POLICYDWEIGHT_CONF(){
		    $l[]='/etc/policyd-weight.conf';
    		$l[]='/etc/postfix/policyd-weight.cf';	
    		$l[]='/usr/local/etc/policyd-weight.conf';

    		while (list ($num, $path) = each ($l) ){
    			if(is_file($path)){return $path;}
    		}
		
	}
	
	
	function SQUID_CACHE_FROM_SQUIDCONF(){
		$array=array();
		$SQUID_CONFIG_PATH=$this->SQUID_CONFIG_PATH();
	
		$f=explode("\n",@file_get_contents($SQUID_CONFIG_PATH));
		while (list ($num, $line) = each ($f)){
			if(preg_match("#cache_dir\s+(.+?)\s+(.+?)\s+#",$line,$re)){
				$array[trim($re[2])]=trim($re[1]);
			}
	
		}
		
		return $array;
	}

	function SQUID_CACHE_FROM_SQUIDCONF_FULL(){
		$array=array();
		$SQUID_CONFIG_PATH=$this->SQUID_CONFIG_PATH();
	
		$f=explode("\n",@file_get_contents($SQUID_CONFIG_PATH));
		while (list ($num, $line) = each ($f)){
			if(preg_match("#cache_dir\s+(.+?)\s+(.+?)\s+#",$line,$re)){
				$array[trim($re[2])]=$line;
			}
	
		}
	
		return $array;
	}	
	
	
	public function LOCATE_STUNNEL(){
		$bins[]="stunnel5";
		$bins[]="stunnel4";
		$bins[]="stunnel3";
		while (list ($num, $prog) = each ($bins)){
			$a=null;
			$a=$this->find_program($prog);
			if($a<>null){return $a;}
		}
		
	}
	public function LOCATE_STUNNEL_INIT(){
		$bins[]="stunnel";
		$bins[]="stunnel5";
		$bins[]="stunnel4";
		$bins[]="stunnel3";
		while (list ($num, $prog) = each ($bins)){
			$a=null;
			if(is_file("/etc/init.d/$prog")){return "/etc/init.d/$prog";}
			
		}
	
	}	
	
private function icmpChecksum($data){
	if (strlen($data)%2)
	$data .= "\x00";
	 
	$bit = unpack('n*', $data);
	$sum = array_sum($bit);
	 
	while ($sum >> 16)
	$sum = ($sum >> 16) + ($sum & 0xffff);
	 
	return pack('n*', ~$sum);
}	


function PROCESS_UPTIME($pid){
	$PROC_STAT=$this->LOCATE_PROCSTAT();
	if(strlen($PROC_STAT)>3){
 		exec("$PROC_STAT $pid 2>&1",$results);
	    while (list ($num, $ligne) = each ($results) ){
	    	if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
	    		$tt=$this->time_duration($re[1]);
	    		$tt=str_replace(" Months,","M",$tt);
	    		$tt=str_replace(" days,","d",$tt);
	    		$tt=str_replace(" hours,","h",$tt);
	    		$tt=str_replace(" minutes,","mn",$tt);
	    		$tt=str_replace(" seconds","s",$tt);
	    		$tt=str_replace(" hour,","h",$tt);
	    		$tt=str_replace(" minute,","mn",$tt);
	    		$tt=str_replace(" second","s",$tt);    		
	    		$me="uptime=$tt";
	    		break;
	    	}
	    		
	    	}
	}

	return $me;
	
}

public function PingHost($cible,$nocache=false){
	
	$cachefile="/etc/artica-postfix/pinghosts.cache";
	if(!$nocache){
		$cache=unserialize(@file_get_contents($cachefile));
		$current_time=time();
		if(is_array($cache)){
			if(count($cache)>700){@unlink($cachefile);$cache=array();}	
			if(isset($cache[$cible])){
				$old_time=$cache[$cible]["time"];
				if(!is_numeric($old_time)){$old_time=0;}
				$difference = ($current_time - $old_time); 
				if($difference<30){return $cache[$cible]["result"];}
				
			}
		}
	}
	
	
	
	$timeout=1;
	$type= "\x08";
	$code= "\x00";
	$checksum= "\x00\x00";
	$identifier = "\x00\x00";
	$seqNumber = "\x00\x00";
	$data= "Scarface";
	$package = $type.$code.$checksum.$identifier.$seqNumber.$data;
	//$checksum = $this->icmpChecksum($package); // Calculate the checksum
	
    $package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
    $socket  = socket_create(AF_INET, SOCK_RAW, 1);
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
	socket_connect($socket, $cible, null);
	$ts = microtime(true);
    socket_send($socket, $package, strLen($package), 0);
    if (socket_read($socket, 255)){$result = true;}else{ $result = false;}
	socket_close($socket);
	if(!$result){
		if($GLOBALS["VERBOSE"]){echo "Ping Method 1 -> $cible FALSE\n";}
		$result=$this->PingHostCMD($cible);
	}else{
		if($GLOBALS["VERBOSE"]){echo "Ping Method 1 -> $cible TRUE\n";}
	}
	$cache[$cible]["result"]=$result;
	$cache[$cible]["time"]=time();
	if(!$result){writelogs_framework("$cible -> FALSE",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}else{writelogs_framework("$cible -> TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	if(!$nocache){
		writelogs_framework("Saving : $cachefile",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		@file_put_contents($cachefile, serialize($cache));
	}
		return $result;
	}
	
	function NETWORK_REPORT(){
		
		$results[]="Report....:";
		$ifconfig=$this->find_program("ifconfig");
		exec("$ifconfig -a 2>&1",$results);
		$ip=$this->find_program("ip");
		exec("$ip link show 2>&1",$results);
		exec("$ip addr 2>&1",$results);
		$results[]="Routes....:";
		exec("$ip route 2>&1",$results);
		$results[]="DNS.......:\n";
		$results[]=@file_get_contents("/etc/resolv.conf");
		$results[]="TESTS pings......:";
		$ARRAY=$this->NETWORK_ALL_INTERFACES();
		while (list ($interface, $ligne) = each ($ARRAY) ){
			if($ligne["GATEWAY"]<>null){
				$results[]=$this->report_ping($interface,$ligne["GATEWAY"]);
			}
			
		}
		
		return @implode("\r\n", $results);
	}

	
	private function report_ping($from,$to){
		$array[]="Ping from $from -> $to";
		$ping=$this->find_program("ping");
		exec("$ping -I $from -c 2 -i 0 $to 2>&1",$array);
		return @implode("\r\n", $array);
		
	}
	
	
	public function KAV4PROXY_GET_VALUE($key,$value){
		    $ini=new iniFrameWork($this->KAV4PROXY_GET_CONF_PATH());
		    return $ini->get($key,$value);
		
	}	
	
	public function PingHostCMD($cible){
		if(isset($GLOBALS["PingHostCMD"][$cible])){return $GLOBALS["PingHostCMD"][$cible];}
		$ping=$this->find_program("ping");
		exec("$ping -v -c1 -t3 -W1 -w1 -p ff $cible 2>&1",$results);
		while (list ($num, $val) = each ($results) ){
			if(preg_match("#, ([0-9]+)\% packet loss#", $val,$re)){
				if($re[1]==0){$GLOBALS["PingHostCMD"][$cible]=true;
				if($GLOBALS["VERBOSE"]){echo "Ping Method 2 -> $cible TRUE\n";}
				return true;
				}else{
					if($GLOBALS["VERBOSE"]){echo "Ping Method 2 -> $cible FALSE\n";}
					$GLOBALS["PingHostCMD"][$cible]=false;
					return false;}
			}
		}
		
	}
	
	


	public function KAV4PROXY_GET_CONF_PATH(){
		if(is_file('/etc/opt/kaspersky/kav4proxy.conf')){return '/etc/opt/kaspersky/kav4proxy.conf';}
		if(is_file('/opt/kaspersky/kav4proxy/etc/opt/kaspersky/kav4proxy.conf')){return '/opt/kaspersky/kav4proxy/etc/opt/kaspersky/kav4proxy.conf';}
	}
	
	public function KAV_MILTER_PATTERN_FILE(){
		if(is_file("/var/db/kav/5.6/kavmilter/bases/master.xml")){return "/var/db/kav/5.6/kavmilter/bases/master.xml";}
		if(is_file("/var/db/kav/5.6/kavmilter/bases/kavset.xml")){return "/var/db/kav/5.6/kavmilter/bases/kavset.xmll";}
		if(is_file("/var/db/kav/5.6/kavmilter/bases/av-i386-0607g.xml")){return "/var/db/kav/5.6/kavmilter/bases/av-i386-0607g.xml";}
	}
	
	public function KAV4PROXY_PATTERN_FILE(){
		return $this->KAV4PROXY_GET_VALUE("path","BasesPath");
	}
	
	
	
	public function LOCATE_SMBD_PID(){
		if(is_file("/var/run/smbd.pid")){return "/var/run/smbd.pid";}
		if(is_file("/var/run/samba/smbd.pid")){return "/var/run/samba/smbd.pid";}
		
	}
	public function LOCATE_NMBD_PID(){
		if(is_file("/var/run/nmbd.pid")){return "/var/run/nmbd.pid";}
		if(is_file("/var/run/samba/nmbd.pid")){return "/var/run/samba/nmbd.pid";}
		
	}	
	public function LOCATE_WINBINDD_PID(){
		if(is_file("/var/run/winbindd.pid")){return "/var/run/winbindd.pid";}
		if(is_file("/var/run/samba/winbindd.pid")){return "/var/run/samba/winbindd.pid";}
		
	}	

	public function LOCATE_CYRUS_IPURGE(){
		if(is_file("/usr/sbin/ipurge")){return "/usr/sbin/ipurge";}
		if(is_file("/usr/lib/cyrus/bin/ipurge")){return "/usr/lib/cyrus/bin/ipurge";}
		if(is_file("/usr/lib/cyrus-imapd/ipurge")){return "/usr/lib/cyrus-imapd/ipurge";}
		$this->events("Unable to stat cyrus ipurge tool");
	}	

	
	public function LOCATE_CYRQUOTA(){
		if(is_file("/usr/sbin/cyrquota")){return "/usr/sbin/cyrquota";}
		if(is_file("/usr/lib/cyrus/bin/quota")){return "/usr/lib/cyrus/bin/quota";}
		if(is_file("/usr/lib/cyrus-imapd/quota")){return "/usr/lib/cyrus-imapd/quota";}
		$this->events("Unable to stat cyrus cyrquota tool");
	}
	
	public function LOCATE_CYRUS_PIDPATH(){
		if(is_file("/var/run/cyrus-master.pid")){return "/var/run/cyrus-master.pid";}
		if(is_file("/var/run/cyrmaster.pid")){return "/var/run/cyrmaster.pid";}
		if(is_file("/var/run/cyrus.pid")){return "/var/run/cyrus.pid";}
		$this->events("Unable to stat cyrus pid path");
	}

	public function KAV4PROXY_PATTERN(){
		exec("/usr/share/artica-postfix/bin/artica-update --kav4proxy--pattern",$results);
		return trim(@implode("",$results));
		
	}
	

	
	public function LOCATE_MILTER_GREYLIST_PID(){
		if(is_file("/var/run/milter-greylist/milter-greylist.pid")){return "/var/run/milter-greylist/milter-greylist.pid";}
	}
	
	public function IS_OPENLDAP_RUNNING(){
		$pid_path=$this->LOCATE_SLAPD_PID_PATH();
		$pidnum=$this->get_pid_from_file($pid_path);
		if($GLOBALS["DEBUG"]){echo __FUNCTION__." pid num: $pidnum\n";}
		return $this->process_exists($pidnum);
	}
	
	private function LOCATE_SLAPD_PID_PATH(){
		if(isset($GLOBALS[__FUNCTION__])){if($GLOBALS[__FUNCTION__]<>null){return $GLOBALS[__FUNCTION__];}}
		$path=$this->LOCATE_SLAPD_CONF_PATH();
		$f=explode("\n",@file_get_contents($path));
		while (list ($num, $line) = each ($f)){
			if(preg_match("#pidfile\s+(.+)#",$line,$re)){
				if($GLOBALS["DEBUG"]){echo __FUNCTION__." pid path: $path\n";}
				$GLOBALS[__FUNCTION__]=trim($re[1]);
				return trim($re[1]);
			}else{
			
			}
		}
		
	}
	
	private function LOCATE_SLAPD_CONF_PATH(){
		if(isset($GLOBALS[__FUNCTION__])){if($GLOBALS[__FUNCTION__]<>null){return $GLOBALS[__FUNCTION__];}}
		$f[]="/etc/ldap/slapd.conf";
		$f[]="/etc/openldap/slapd.conf";
		$f[]="/usr/local/etc/openldap/slapd.conf";
		while (list ($num, $path) = each ($f)){
			if(is_file($path)){
				if($GLOBALS["DEBUG"]){echo __FUNCTION__." path: $path\n";}
				$GLOBALS[__FUNCTION__]=$path;
				return $path;
			}
		}
		
		
	}
	
	private function VERBOSE_COMMAND_LINE($pid){
		return @file_get_contents("/proc/$pid/cmdline");
	}
	
	private function LOCATE_PROCSTAT(){
		$procstat=$this->find_program("procstat");
		if(is_file($procstat)){return $procstat;}
		if(is_file("/usr/share/artica-postfix/bin/procstat")){return "/usr/share/artica-postfix/bin/procstat";}
		$gcc=$this->find_program("gcc");
		if(!is_file($gcc)){return;}
		shell_exec("$gcc -o /usr/share/artica-postfix/bin/procstat /usr/share/artica-postfix/bin/install/procstat.c");
		if(is_file("/usr/share/artica-postfix/bin/procstat")){return "/usr/share/artica-postfix/bin/procstat";}
	}
	
	Public function GetSingleMemoryOf($pid){
		if($pid<1){
			if($GLOBALS["DEBUG"]){echo __FUNCTION__." bad pid \"$pid\"\n";}
			return null;
		}
		$rss=$this->PROCESS_MEMORY($pid,true);
		$vm=$this->PROCESS_CACHE_MEMORY($pid,true);
		if($this->process_exists($pid)){$l[]="installed=1";$l[]="running=1";}
		$l[]="master_pid=$pid";	
   	 	$l[]="master_memory=$rss";
    	$l[]="master_cached_memory=$vm";
    	$l[]="processes_number=1";
    	
    	if(!is_file("/usr/share/artica-postfix/bin/procstat")){
    		$gcc=$this->find_program("gcc");
    		if(!is_file($gcc)){return;}
    		shell_exec("$gcc -o /usr/share/artica-postfix/bin/procstat /usr/share/artica-postfix/bin/install/procstat.c");
    	} 	
    	
    	if(!is_file("/usr/share/artica-postfix/bin/procstat")){
    		$this->events("Unable to compile procstat !!! in " .__CLASS__."/".__FUNCTION__." Line".__LINE__);
    	}
		
  	exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
    while (list ($num, $ligne) = each ($results) ){
    	if(preg_match("#GLIBC.+?not found#",$ligne)){
    		@unlink("/usr/share/artica-postfix/bin/procstat");
    		return;
    	}
    	
    	if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
    		$tt=$this->time_duration($re[1]);
    		$tt=str_replace(" Months,","M",$tt);
    		$tt=str_replace(" days,","d",$tt);
    		$tt=str_replace(" hours,","h",$tt);
    		$tt=str_replace(" minutes,","mn",$tt);
    		$tt=str_replace(" seconds","s",$tt);
    		$tt=str_replace(" hour,","h",$tt);
    		$tt=str_replace(" minute,","mn",$tt);
    		$tt=str_replace(" second","s",$tt);    		
    		$l[]="uptime=$tt";}
    		
    	}
    $l[]="";	
    return implode("\n",$l);return;    	
    	
		
	}
	
function THREAD_COMMAND_SET($zcommands){
	  $md5command=md5(trim($zcommands));
	  $FileDataCommand=@file_get_contents('/etc/artica-postfix/background');
	  $tbl=explode("\n",$FileDataCommand);
	 
	if(is_array($tbl)){
		while (list ($num, $ligne) = each ($tbl) ){
			if($ligne==null){continue;}
			 $mdtmp=md5(trim($ligne));
			 if($md5command==$mdtmp){
			 	writelogs_framework("`$zcommands` already scheduled as `$md5command`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			 	return false;
			 }
			}
		reset($tbl);
		}
		
	writelogs_framework("Schedule this command `$zcommands`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	$tbl[]=$zcommands;
	@file_put_contents('/etc/artica-postfix/background',implode("\n",$tbl));
}	

public function GetMemoriesOfChild($pid){
	$pidsrc=$pid;
	if($pid<1){
		if($GLOBALS["DEBUG"]){echo __FUNCTION__." bad pid \"$pid\"\n";}return null;}


		$rss=$this->PROCESS_MEMORY($pid,true);
		$vm=$this->PROCESS_CACHE_MEMORY($pid,true);


		if($count==0){$count=1;}
		if($this->process_exists($pid)){$l[]="installed=1";$l[]="running=1";}
		$l[]="application_installed=1";
		$l[]="master_pid=$pid";
		$l[]="master_memory=$rss";
		$l[]="master_cached_memory=$vm";
		$l[]="processes_number=$count";


		exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
				$tt=$this->time_duration($re[1]);
				$tt=str_replace(" Months,","M",$tt);
				$tt=str_replace(" days,","d",$tt);
				$tt=str_replace(" hours,","h",$tt);
				$tt=str_replace(" minutes,","mn",$tt);
				$tt=str_replace(" seconds","s",$tt);
				$tt=str_replace(" hour,","h",$tt);
				$tt=str_replace(" minute,","mn",$tt);
				$tt=str_replace(" second","s",$tt);
				$l[]="uptime=$tt";}

		}
		$l[]="";
		return implode("\n",$l);return;

}
	
	
public function GetMemoriesOf($pid,$unique=false){
	$pidsrc=$pid;
	if($pid<1){
	if($GLOBALS["DEBUG"]){echo __FUNCTION__." bad pid \"$pid\"\n";}
		return null;
	}
	$pid=$this->PROCESS_PPID($pid);
	if($GLOBALS["VERBOSE"]){echo "Father of $pidsrc = $pid\n";}
	
	$rss=$this->PROCESS_MEMORY($pid,true);
	$vm=$this->PROCESS_CACHE_MEMORY($pid,true);
	if(!$unique){
		$cmd=$this->find_program("pgrep")." -P $pid";
		if($GLOBALS["DEBUG"]){echo __FUNCTION__." $cmd\n";}
		$mpid=getmypid();
		exec($cmd,$results);
		$count=1;
		while (list ($num, $ligne) = each ($results) ){
			$ligne=trim($ligne);
			if($ligne<50){continue;}
			if($ligne==$mpid){continue;}
			$count=$count+1;
			$rss0=$this->PROCESS_MEMORY($ligne,true);
			$vm0=$this->PROCESS_CACHE_MEMORY($ligne,true);
			$rss=$rss+$rss0;
			$vm=$vm+$vm0;		
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."PID $ligne \"$count\" rss:$rss0 vm:$vm0 Process: \"". $this->VERBOSE_COMMAND_LINE($ligne)."\"\n";}
			
			$cmd=$this->find_program("pgrep")." -P $ligne 2>&1";
			if($GLOBALS["DEBUG"]){echo __FUNCTION__." $cmd\n";}
			$results2=array();
			exec($cmd,$results2);
			while (list ($a, $b) = each ($results2) ){
				$b=trim($b);
				if($b<50){continue;}
				if($b==$mpid){continue;}
				$count=$count+1;
				$rss0=$this->PROCESS_MEMORY($b,true);
				$vm0=$this->PROCESS_CACHE_MEMORY($b,true);
				$rss=$rss+$rss0;
				$vm=$vm+$vm0;		
				if($GLOBALS["DEBUG"]){echo __FUNCTION__."PID $b \"$count\" rss:$rss0 vm:$vm0 Process: \"". $this->VERBOSE_COMMAND_LINE($b)."\"\n";}
			}
			
			
		}
	}
	if($count==0){$count=1;}
	if($this->process_exists($pid)){$l[]="installed=1";$l[]="running=1";}
	$l[]="application_installed=1";
	$l[]="master_pid=$pid";	
    $l[]="master_memory=$rss";
    $l[]="master_cached_memory=$vm";
    $l[]="processes_number=$count";
    
    
    exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
    while (list ($num, $ligne) = each ($results) ){
    	if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
    		$tt=$this->time_duration($re[1]);
    		$tt=str_replace(" Months,","M",$tt);
    		$tt=str_replace(" days,","d",$tt);
    		$tt=str_replace(" hours,","h",$tt);
    		$tt=str_replace(" minutes,","mn",$tt);
    		$tt=str_replace(" seconds","s",$tt);
    		$tt=str_replace(" hour,","h",$tt);
    		$tt=str_replace(" minute,","mn",$tt);
    		$tt=str_replace(" second","s",$tt);    		
    		$l[]="uptime=$tt";}
    		
    	}
    $l[]="";	
    return implode("\n",$l);return;
	
}

function PROCESS_TTL_TEXT($pid){
	if(!is_file("/usr/share/artica-postfix/bin/procstat")){return null;}
    exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
    while (list ($num, $ligne) = each ($results) ){
    	if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
    		$tt=$this->time_duration($re[1]);
    		$tt=str_replace(" Months,","M",$tt);
    		$tt=str_replace(" days,","d",$tt);
    		$tt=str_replace(" hours,","h",$tt);
    		$tt=str_replace(" minutes,","mn",$tt);
    		$tt=str_replace(" seconds","s",$tt);
    		$tt=str_replace(" hour,","h",$tt);
    		$tt=str_replace(" minute,","mn",$tt);
    		$tt=str_replace(" second","s",$tt);    		
    		return $tt;
    		};
    		
    	}
   }
   
   
   FUNCTION MYSQL_DATA_DIR(){
   		$sock=new sockets();
   		$ChangeMysqlDir=$sock->GET_INFO("ChangeMysqlDir");
		if($ChangeMysqlDir==null){$ChangeMysqlDir="/var/lib/mysql";}
   		return $ChangeMysqlDir;
   }


function PROCESS_TTL($pid){
	if($pid<2){return 0;}
	exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
			$seconds=$re[1];
			return round(($seconds/60));
		}
	}
}

function vhosts_BuildCertificate($hostname){
	
	if($hostname=="__default__"){$hostnameText=$this->hostname_g();}else{$hostnameText=$hostname;}
	
	$dir=$GLOBALS["SSLKEY_PATH"];
	if(is_file("$dir/$hostname.crt")){
		if(is_file("$dir/$hostname.key")){return true;}
	}
	@mkdir($dir,0600,true);
	
	$sock=new sockets();
	$CertificateMaxDays=$sock->GET_INFO('CertificateMaxDays');
	if($CertificateMaxDays==null){$CertificateMaxDays=730;}
	if(!class_exists("Bs_IniHandler")){
		$ini=new iniFrameWork();
	}else{
		$ini=new Bs_IniHandler();
	}
	
	$ini->loadFile("/etc/artica-postfix/ssl.certificate.conf");
	$conf="/etc/ssl/certs/apache/$hostname.conf";
	$openssl=$this->find_program("openssl");
	echo "Starting......: Certificate \"$conf\" ". count($ini->_params) ." Keys\n";
	
	unset($ini->_params["HOSTS_ADDONS"]);
	$ini->_params["default_db"]["default_days"]=$CertificateMaxDays;
	$ini->_params["server_policy"]["commonName"]=$hostnameText;
	$ini->_params["user_policy"]["commonName"]=$hostnameText;
	$ini->_params["default_ca"]["commonName"]=$hostnameText;
	$ini->_params["default_ca"]["commonName_value"]=$hostnameText;
	$ini->_params["policy_match"]["commonName"]=$hostnameText;
	$ini->_params["policy_anything"]["commonName"]=$hostnameText;
	reset($ini->_params);
	$ini->saveFile($conf);
	$cmd="$openssl req -new -x509 -batch -config $conf -nodes -out $dir/$hostname.crt -keyout $dir/$hostname.key -days $CertificateMaxDays 2>&1";
	exec($cmd,$results);
	if($GLOBALS["VERBOSE"]){
		echo "$cmd\n".@implode("\n",$results)."\n";
	}
	
}

function getLOCALEUSED(){
	$locale=$this->find_program("env");
	writelogs_framework("$locale 2>&1",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
	exec("$locale 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		writelogs_framework("$ligne",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		if(preg_match("#LANG=(.+)#",$ligne,$re)){return trim($re[1]);}
	}
	
	shell_exec("LANG=en_US.utf8");
	return "en_US.utf8";
}


function SSHD_GET_FINGERPRINT($path){
	$path=$this->shellEscapeChars($path);
	$sshkeygen=$this->find_program("ssh-keygen");
	$maincmd="$sshkeygen -lf $path";

	writelogs_framework("$maincmd 2>&1" ,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	exec($maincmd,$results);
	while (list ($num, $ligne) = each ($results) ){
		writelogs_framework("$ligne" ,__FUNCTION__,__FILE__,__LINE__);
		if(preg_match("#^[0-9]+\s+(.+?)\s+#",$ligne,$re)){
			writelogs_framework("FOUND {$re[1]}" ,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return $re[1];
		}
	}
}

function KERNEL_CONFIG(){
	$uname=$this->find_program("uname");
	if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."/".__LINE__." uname= $uname\n";}
	exec("$uname -r 2>&1",$results);
	$kernelver=trim(@implode("", $results));
	if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."/".__LINE__." kernelver = $kernelver\n";}
	$file="/boot/config-$kernelver";
	if(!is_file($file)){if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."/".__LINE__." $file no such file\n";}}
	$t=file($file);
	while (list ($num, $ligne) = each ($t) ){
		if(preg_match("#^([A-Z_\-]+)=(.+)#", $ligne,$re)){
			if(trim($re[2])=="y"){$re[2]=1;}
			if(trim($re[2])=="m"){$re[2]=1;}
			$Array[trim($re[1])]=trim($re[2]);
		}
	}
	
	return $Array;
	
}

function KERNEL_ARCH(){
	$uname=$this->find_program("uname");
	if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."/".__LINE__." uname= $uname\n";}
	$results=exec("$uname -r 2>&1");
	if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."/".__LINE__." $results\n";}	
	if(preg_match("#i[0-9]86#", $results)){return 32;}
	if(preg_match("#686-bigmem#", $results)){return 32;}
	if(preg_match("#x86_64#", $results)){return 64;}
	if(preg_match("#-amd64#", $results)){return 64;}
	
	$results=exec("$uname -a 2>&1");
	if(preg_match("#i[0-9]86#", $results)){return 32;}
	if(preg_match("#686-bigmem#", $results)){return 32;}
	if(preg_match("#x86_64#", $results)){return 64;}
	if(preg_match("#-amd64#", $results)){return 64;}	
	
}

function MYSQL_BIN_PARSE_ERROR($text){
	if(is_file($text)){$text=@file_get_contents($text);}
	$f=explode("\n", $text);
	while (list ($num, $ligne) = each ($f) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#already exists#", $ligne)){continue;}
		if($GLOBALS["VERBOSE"]){echo "MYSQL_BIN_PARSE_ERROR:: `$ligne`\n";}
		if(preg_match("#: Got errno [0-9]+ on write#",$ligne)){
			$this->mysql_error=$ligne;
			return true;
		}
		
		if(preg_match("#Got error:\s+([0-9]+):", $ligne)){
			$this->mysql_error=$ligne;
			return true;
		}		
		
		if(preg_match("#^ERROR\s+([0-9]+)#", $ligne)){
			$this->mysql_error=$ligne;
			return true;
		}
		
	}
	
	return false;
	
}

function RESTART_SYSLOG_FORMAIL($onlysyslog=false){
	$this->RESTART_SYSLOG(true);
	shell_exec("/etc/init.d/artica-postfix restart postfix-logger");
	
}

function RESTART_SYSLOG($onlysyslog=false){
			$nohup=$this->find_program("nohup");
			if(is_file("/etc/init.d/rsyslog")){
				if($GLOBALS["VERBOSE"]){echo "/etc/init.d/rsyslog restart\n";}
				shell_exec("/etc/init.d/rsyslog restart");
			}
				
				
				
			if(is_file("/etc/init.d/syslog")){
				if($GLOBALS["VERBOSE"]){system($this->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.initslapd.php --rsyslogd-init ");}else{
					shell_exec($this->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.initslapd.php --rsyslogd-init >/dev/null 2>&1");
				}
				if($GLOBALS["VERBOSE"]){echo "/etc/init.d/syslog restart\n";}
				@chmod("/etc/init.d/syslog", 0755);
				shell_exec("$nohup /etc/init.d/syslog restart >/dev/null 2>&1 &");
			}
			
			
			
			if(is_file("/etc/init.d/sysklogd")){
				if($GLOBALS["VERBOSE"]){echo "/etc/init.d/sysklogd restart\n";}
				shell_exec("$nohup /etc/init.d/sysklogd restart >/dev/null 2>&1 &");
			}
			if(is_file("/etc/init.d/syslog-ng")){
				if($GLOBALS["VERBOSE"]){echo "/etc/init.d/syslog-ng restart\n";}
				shell_exec("$nohup /etc/init.d/syslog-ng restart >/dev/null 2>&1 &");
			}
			if($onlysyslog){return;}

			shell_exec("/etc/init.d/artica-postfix restart sysloger");
			shell_exec("/etc/init.d/artica-postfix restart postfix-logger");
			shell_exec("/etc/init.d/artica-postfix restart squid-tail");
			shell_exec("/etc/init.d/artica-postfix restart ocsweb");
			shell_exec("/etc/init.d/artica-postfix restart apachesrc");
			ufdbguard_admin_events("Restarting udbguard-tail after cleaning log files",__FUNCTION__,__FILE__,__LINE__,"system");
			shell_exec("/etc/init.d/ufdb-tail restart");	
	
}

function send_email_events($subject,$text,$context,$date=null,$attached_files=array(),$recipient=null){
	
	$arr_APP=array(
		"APP_ARTICA_BACKGROUND"=>"Artica Background Executor",
		"APP_OPENEMM_SENDMAIL"=>"SendMail for OpenEMM service",
		"APP_OPENEMM"=>"OpenEMM service",
		"APP_TOMCAT"=>"Tomcat Web server",
		"APP_GREENSQL"=>"GreenSQL Firewall",
		"APP_ZARAFA_WEB"=>"Zarafa web server",
		"APP_ZARAFA_SERVER"=>"Zarafa server",
		"APP_ZARAFA_SPOOLER"=>"Zarafa Spooler",
		"APP_ZARAFA_DAGENT"=>"Zarafa Delivery Agent",
		"APP_ZARAFA_GATEWAY"=>"Zarafa Gateway",			
		"APP_ZARAFA_MONITOR"=>"Zarafa Monitor",	
		"APP_ZARAFA_ICAL"=>"iCal, CalDav Gateway",
		"APP_ZARAFA_LICENSED"=>"Zarafa license service",
		"APP_ZARAFA"=>"Zarafa Mail server",
		"APP_SNORT"=>"Intrusion Detection System (Snort)",
		"APP_OCSI"=>"OCS Inventory",
		"APP_ARTICA_AUTH_TAIL"=>"Authentication Watchdog",
		"APP_VNSTAT"=>"VNStat",
		"APP_POSTFWD2"=>"Postfix Postfwd2",
		"APP_VMTOOLS"=>"VMWare tools",
		"APP_IETD"=>"iSCSI service",
		"APP_GREYHOLE"=>"Greyhole Samba relatime Backup",
		"APP_AUTOFS"=>"AutoFS, Automount service",
		"APP_MUNIN"=>"Munin Statistics Client",
		"APP_BACKUPPC"=>"BackupPC Daemon",
		"APP_STUNNEL"=>"sTunnel daemon",
		
		"APP_UFDBGUARD_TAIL"=>"UfdbGuard Watchdog",
		"APP_OPENVPN"=>"OpenVPN Server",
		"OPENVPN_SERVER"=>"OpenVPN server",
		"APP_VBOXADDITIONS"=>"VirtualBox guest additions",
		
		"APP_ARTICA_EXECUTOR"=>"Artica Executor Daemon",
		"APP_GLUSTER"=>"Gluster Cluster daemon",
		"APP_APACHE_SRC"=>"Apache source Daemon",
		"APP_CLUEBRINGER"=>"ClueBringer (Policyd V2)",
		"APP_DDCLIENT"=>"Dynamic DNS Client",
		"APP_SQUID_CLAMAV_TAIL"=>"squidclamav RealTime log",
		"APP_ARTICA_SQUID_TAIL"=>"Squid RealTime log",
		"APP_CROSSROADS"=>"Crossroads Load balancing service",
		"APP_TFTPD"=>"Trivial File Transfer Protocol service",
		"APP_POLICYD_WEIGHT"=>"Policy Daemon for Postfix",
		"APP_VIRTUALBOX_WEBSERVICE"=>"VirtualBox Web service",
		"APP_ARTICA_POLICY"=>"Artica-Policy service",
		"APP_MILTER_DKIM"=>"Milter-DKIM service",
		"APP_DROPBOX"=>"DropBox service",
		"APP_ARTICA_SQUIDGUARDTAIL"=>"squidGuard watchdog daemon",
		"APP_UFDBGUARD"=>"UfdbGuard Filtering service",
		"APP_OPENDKIM"=>"OpenDKIM Filter service",
		"APP_SQUIDGUARD_HTTP"=>"SquidGuard HTTP service",
		"APP_DNSMASQ"=>"DNS forwarder caching",
		"APP_SMARTMONTOOLS"=>"SMART hard disk monitoring",
		"APP_AUDITD"=>"Auditd Daemon",
		"APP_OPENSSH"=>"OpenSSH server",
		"APP_KAV4FS"=>"Kaspersky For File server",
		"APP_MLDONKEY"=>"MLDonkey server",
		"APP_DHCP"=>"DHCP Server",
		"APP_ARTICA_NOTIFIER"=>"Artica SMTP Notifier",
		"APP_GROUPWARE_APACHE"=>"Apache for groupware applications",
		"APP_ROUNDCUBE"=>"Web service For RoundCube",
		"APP_ZABBIX_SERVER"=>"Zabbix server",
		"APP_MYSQL_ARTICA"=>"Mysql server",
		"APP_ARTICA_MYSQMAIL"=>"Postfix Monitor",
		"APP_ARTICA_DANSGUARDIAN_TAIL"=>"DansGuardian Monitor",
		"APP_ARTICA_WATCHDOG"=>"Artica Watchdog",
		"APP_ASSP"=>"Anti-Spam SMTP Proxy",
		"APP_BOA"=>"Boa Daemon",
		"APP_CLAMAV"=>"ClamAV Daemon",
		"APP_CUPS"=>"Printer service",
		"APP_CYRUS"=>"Cyrus-imap service",
		"APP_DANSGUARDIAN"=>"DansGuardian",
		"APP_FETCHMAIL_LOGGER"=>"Fetchmail Monitor",
		"APP_FRAMEWORK"=>"Artica FrameWork",
		"APP_FETCHMAIL"=>"Fetchmail Daemon",
		"APP_MAILMAN"=>"Mailman mailinglist Daemon",
		"APP_FRESHCLAM"=>"ClamAV Updater",
		"APP_KAS3_MILTER"=>"Kaspersky Anti-Spam hook",
		"APP_KAS3"=>"Kaspersky Anti-Spam service",
		"APP_KAVMILTER"=>"Kaspersky Anti-virus For Mail",
		"APP_KRETRANSLATOR_HTTPD"=>"Kaspersky Antivirus updater web service",
		"APP_LDAP"=>"OpenLDAP service",
		"APP_LIGHTTPD"=>"Artica Web server",
		"APP_MONIT"=>"Watchdog system",
		"APP_RSYNC"=>"Rsync service",
		"APP_PDNS_RECURSOR"=>"PowerDNS external query ",
		"APP_PDNS"=>"PowerDNS service",
		"APP_POSTFIX"=>"Postfix",
		"APP_SAMBA_NMBD"=>"Samba NetBIOS name service",
		"APP_SAMBA_WINBIND"=>"Samba Name Service Switch",
		"APP_SAMBA_SMBD"=>"Samba Filesharing and printing services",
		"APP_SASLAUTHD"=>"System Authentication",
		"APP_SPAMASSASSIN"=>"SpamAssassin",
		"APP_SQUID"=>"Proxy service (squid)",
		"APP_SYSLOGER"=>"Artica System monitor",
		"APP_KAV4PROXY"=>"Kaspersky Antivirus For Squid",
		"APP_C_ICAP"=>"C-ICAP For Squid",
		"APP_SCANNED_ONLY"=>"Samba Antivirus Scanner",
		"APP_PUREFTPD"=>"FTP Service",
		"APP_MILTERGREYLIST"=>"Greylist service for Postfix",
		"APP_AMAVISD_MILTER"=>"Amavis listener milter",
		"APP_AMAVISD_NEW"=>"Amavis Daemon",
		"APP_CLAMAV_MILTER"=>"ClamAV For Postfix",
		"APP_MAILSPY"=>"MailSpy",
		"APP_GROUPWARE_APACHE"=>"Apache for Groupwares",
		"APP_ARTICA"=>"Artica service",
		
		
	);
	

	while (list ($tok, $service_name) = each ($arr_APP) ){
		$subject=str_replace($tok,$service_name,$subject);
		$text=str_replace($tok,$service_name,$text);
	}
	include_once("/usr/share/artica-postfix/ressources/class.users.menus.inc");	
	if(!isset($GLOBALS["CLASS_USERSMENUS"])){$users=new usersMenus();$GLOBALS["CLASS_USERSMENUS"]=$users;}else{$users=$GLOBALS["CLASS_USERSMENUS"];}
	$internal_load=null;
	
	if($date==null){
		$date=date('Y-m-d H:i:s');
		$datesubject=date('H:i:s');
	}else{
		$datesubject=$date;
	}
	
	if(function_exists("sys_getloadavg")){
		$array_load=sys_getloadavg();
		$internal_load=" - system load: {$array_load[0]}";
	}
	
	$filename=basename(__FILE__);
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		$function="MAIN";
		$line=0;
		if(isset($trace[1]["file"])){
			$filename=basename($trace[1]["file"]);
		}
		if(isset($trace[1]["function"])){
			$function=" - function {$trace[1]["function"]}()";
		} 
		if(isset($trace[1]["line"])){
			$function=$function." line {$trace[1]["line"]}";
		}
	}
	
	$ini=new iniFrameWork();
	
	if($GLOBALS["VERBOSE"]){echo "$subject\n$text\n";}
	$textNocrlf=str_replace("\n", "<br>", $text);
	$ini->set('LOG','processname',$filename);
	$ini->set('LOG','date',$date);
	$ini->set('LOG','context',$context);
	$ini->set('LOG','subject',"[$datesubject]: [ARTICA]: ($users->hostname) : $subject$internal_load");
	$ini->set('LOG','recipient',$recipient);
	$ini->set('LOG','text',$textNocrlf.$internal_load.$function);
	$filename="/var/log/artica-postfix/events/".md5(serialize($ini->_params));
	if(is_file($filename)){return null;}
	$ini->saveFile($filename);
	$datas=@file_get_contents($filename);
	$datas=$datas."\n<text>$text</text>\n";
	$datas=$datas."\n<attachedfiles>". base64_encode(serialize($attached_files))."</attachedfiles>\n";
	
	
	if($GLOBALS["VERBOSE"]){echo strlen($datas)." bytes file $filename text\n";}
	
	@file_put_contents($filename,$datas);
	@mkdir("/usr/share/artica-postfix/ressources/logs/jGrowl",0775,true);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/jGrowl/".basename($filename).".txt",$datas);
	@chmod("/usr/share/artica-postfix/ressources/logs/jGrowl/".basename($filename).".txt",0777);
	}

private function time_duration($seconds, $use = null, $zeros = false){
    // Define time periods
    $periods = array (
        'years'     => 31556926,
        'Months'    => 2629743,
        'weeks'     => 604800,
        'days'      => 86400,
        'hours'     => 3600,
        'minutes'   => 60,
        'seconds'   => 1
        );

    // Break into periods
    $seconds = (float) $seconds;
    $segments = array();
    foreach ($periods as $period => $value) {
        if ($use && strpos($use, $period[0]) === false) {
            continue;
        }
        $count = floor($seconds / $value);
        if ($count == 0 && !$zeros) {
            continue;
        }
        $segments[strtolower($period)] = $count;
        $seconds = $seconds % $value;
    }

    // Build the string
    $string = array();
    foreach ($segments as $key => $value) {
        $segment_name = substr($key, 0, -1);
        $segment = $value . ' ' . $segment_name;
        if ($value != 1) {
            $segment .= 's';
        }
        $string[] = $segment;
    }

    return implode(', ', $string);
}

public function GetVersionOf($name){
	if(!isset($GLOBALS["version-of-$name"])){$GLOBALS["version-of-$name"]=null;}
	if($GLOBALS["version-of-$name"]<>null){return $GLOBALS["version-of-$name"];}
	exec("/usr/share/artica-postfix/bin/artica-install --export-version $name",$results);
	$version=trim(implode("",$results));
	$version=trim(implode("",$results));
	$GLOBALS["version-of-$name"]=$version;
	return $version;	
}

  function unix_memory_usage() {
        $mem_usage = memory_get_usage(true);
       
        if ($mem_usage < 1024)
            echo $mem_usage." bytes";
        elseif ($mem_usage < 1048576)
            return round($mem_usage/1024,2)." kilobytes";
        else
            return round($mem_usage/1048576,2)." megabytes";
           
        
    } 

	
	public function DANSGUARDIAN_CATEGORIES(){
			$path="/etc/dansguardian/lists/blacklists";
			$unix=new unix();
			$dirs=$unix->dirdir($path);
			while (list ($num, $line) = each ($dirs)){
				
				
			}
		}
		
		
public function shellEscapeChars($path){
		$path=str_replace(" ","\ ",$path);
		$path=str_replace('$','\$',$path);
		$path=str_replace("&","\&",$path);
		$path=str_replace("?","\?",$path);
		$path=str_replace("#","\#",$path);
		$path=str_replace("[","\[",$path);
		$path=str_replace("]","\]",$path);
		$path=str_replace("{","\{",$path);
		$path=str_replace("}","\}",$path);
		$path=str_replace("*","\*",$path);
		$path=str_replace('"','\\"',$path);
		$path=str_replace("'","\\'",$path);
		$path=str_replace("(","\(",$path);
		$path=str_replace(")","\)",$path);
		$path=str_replace("<","\<",$path);				
		$path=str_replace(">","\>",$path);
		$path=str_replace("!","\!",$path);
		$path=str_replace("+","\+",$path);
		$path=str_replace(";","\;",$path);
		return $path;	
}

	function SLAPD_CONF_PATH(){
		if(is_file('/etc/ldap/slapd.conf')){return '/etc/ldap/slapd.conf';}
		if(is_file('/etc/openldap/slapd.conf')){return '/etc/openldap/slapd.conf';}
		if(is_file('/etc/openldap/ldap.conf')){return '/etc/openldap/slapd.conf';}
		if(is_file('/opt/artica/etc/openldap/slapd.conf')){return '/opt/artica/etc/openldap/slapd.conf';}
		if(is_file('/usr/local/etc/openldap/slapd.conf')){return '/usr/local/etc/openldap/slapd.conf';}
	   return '/etc/ldap/slapd.conf';
	}
	function SLAPD_PID_PATH(){
	  if(is_file('/var/run/slapd/slapd.pid')){return '/var/run/slapd/slapd.pid';}
	  if(is_file('/var/run/openldap/slapd.pid')){ return '/var/run/openldap/slapd.pid';}
	
	  $conffile=$this->SLAPD_CONF_PATH();
	  if(!is_file($conffile)){return "/var/run/slapd/slapd.pid";}
	 
	  $f=file($conffile);
	  while (list ($num, $line) = each ($f)){
	 	 if(preg_match('#pidfile\s+(.+)#',$line,$re)){return trim($re[1]);}
	  }
	  
		 return('/var/run/slapd/slapd.pid');
	}		
		
	public function APACHE_GROUPWARE_ACCOUNT(){
		$tbl=explode("\n",@file_get_contents("/usr/local/apache-groupware/conf/apache-groupware.conf"));
		while (list ($num, $line) = each ($tbl)){
			if(preg_match("#User\s+(.+)#",$line,$re)){
				return "{$re[1]}:{$re[1]}";
			}
			
		}
		
	}
	
	public function IS_IPADDR_EXISTS($ipaddr){
		$ipaddr=trim($ipaddr);
		if($ipaddr==null){return false;}
		if(!isset($GLOBALS["IS_IPADDR_EXISTS_ARRAY"])){
			$ifconfig=$this->find_program("ifconfig");
			exec("$ifconfig -a 2>&1",$GLOBALS["IS_IPADDR_EXISTS_ARRAY"]);
		}
		$ipaddrregex=str_replace(".", "\.", $ipaddr);
		reset($GLOBALS["IS_IPADDR_EXISTS_ARRAY"]);
		while (list ($num, $line) = each ($GLOBALS["IS_IPADDR_EXISTS_ARRAY"])){
			if(preg_match("#$ipaddrregex#",$line)){return true;}
		}
		return false;
		
	}
	
	
	public function APACHE_MODULES_PATH(){
		
		$DIRS[]="/usr/lib/apache2/modules";
		$DIRS[]="/usr/lib/apache/modules";
		$DIRS[]="/usr/lib64/apache/modules";
		$DIRS[]="/usr/lib64/apache2/modules";
		$DIRS[]="/usr/lib64/httpd/modules";
		$DIRS[]="/usr/lib/httpd/modules";
		
		$f[]="mod_cgi.so";
		$f[]="mod_perl.so";
		$f[]="mod_alias.so";
		
		while (list ($num, $directory) = each ($DIRS)){
			reset($f);
			while (list ($num, $filename) = each ($f)){
				if($GLOBALS["VERBOSE"]){echo "APACHE_MODULES_PATH:: Checking \"$directory/$filename\"\n";}
				if(is_file("$directory/$filename")){
					if($GLOBALS["VERBOSE"]){echo "APACHE_MODULES_PATH:: Found \"$directory\"\n";}
					return $directory;}
			}
		}
		
	}
	
	public function APACHE_SRC_ACCOUNT(){
		if(is_file("/etc/artica-postfix/settings/Daemons/ApacheSRCAccount")){
			$data=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ApacheSRCAccount"));
			if(strlen($data)>3){return $data;}
		}
		
		
		$httpd_conf=$this->LOCATE_APACHE_CONF_PATH();
		$envars=dirname($httpd_conf)."/envvars";
		if(is_file($envars)){
			$tbl=explode("\n",@file_get_contents($envars));
			while (list ($num, $line) = each ($tbl)){
				if(preg_match("#export APACHE_RUN_USER=(.+)#",$line,$re)){
					return trim($re[1]);
				}
			}
		}
		if(is_file($httpd_conf)){
			$tbl=explode("\n",@file_get_contents($httpd_conf));
			while (list ($num, $line) = each ($tbl)){if(preg_match("#^User\s+(.+)#",$line,$re)){
				if(strpos($re[1], '"')==0){return trim("{$re[1]}");}
				}
			}
		}
		
		if(is_file("/etc/apache2/uid.conf")){
			$tbl=explode("\n",@file_get_contents("/etc/apache2/uid.conf"));
			while (list ($num, $line) = each ($tbl)){if(preg_match("#^User\s+(.+)#",$line,$re)){return trim("{$re[1]}");}}	
		}
		
		$tbl=explode("\n",@file_get_contents("/etc/passwd"));
		while (list ($num, $line) = each ($tbl)){if(preg_match("#^(.+?):x:[0-9]+:[0-9]+:(.+?):\/var\/www:#",$line,$re)){return trim("{$re[1]}");}}
		
		
		
	}	
	
	public function APACHE_SRC_GROUP(){
		if(is_file("/etc/artica-postfix/settings/Daemons/ApacheSRCGroup")){
			$data=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ApacheSRCGroup"));
			if(strlen($data)>3){
				if($data=='${APACHE_RUN_GROUP}'){$data="www-data";}
				return $data;}
		}		
		
		$httpd_conf=$this->LOCATE_APACHE_CONF_PATH();
		if(is_file($httpd_conf)){
			$tbl=explode("\n",@file_get_contents($httpd_conf));
			while (list ($num, $line) = each ($tbl)){if(preg_match("#^Group\s+(.+)#",$line,$re)){
				if(strpos($re[1], '"')==0){return trim("{$re[1]}");}
				
			}
			}
		}
		
		if(is_file("/etc/apache2/uid.conf")){
			$tbl=explode("\n",@file_get_contents("/etc/apache2/uid.conf"));
			while (list ($num, $line) = each ($tbl)){if(preg_match("#^Group\s+(.+)#",$line,$re)){return trim("{$re[1]}");}}	
		}
		
		$tbl=explode("\n",@file_get_contents("/etc/passwd"));
		while (list ($num, $line) = each ($tbl)){if(preg_match("#^(.+?):x:[0-9]+:[0-9]+:(.+?):\/var\/www:#",$line,$re)){return trim("{$re[1]}");}}
				

		return $this->APACHE_SRC_ACCOUNT();
		
	}
	
	function GetAvailableListenIP(){
		if(isset($GLOBALS["GetAvailableListenIP"])){return $GLOBALS["GetAvailableListenIP"];}
		$unix=new unix();
		$ifconfig=$unix->find_program("ifconfig");
		exec("$ifconfig -a 2>&1",$results);
		while (list ($num, $line) = each ($results)){
			if(preg_match("#inet addr.*?([0-9\.]+)#", $line,$re)){
					
				if($GLOBALS["VERBOSE"]){echo "Starting......: available network: {$re[1]}\n";}
				$GLOBALS["GetAvailableListenIP"][$re[1]]=$re[1];
				continue;
			}
			if(preg_match("#inet6 addr:\s+(.*?)\/#", $line,$re)){
				if($GLOBALS["VERBOSE"]){echo "Starting......: available network: {$re[1]}\n";}
				$GLOBALS["GetAvailableListenIP"][$re[1]]=$re[1];
				continue;
			}
	
			if($GLOBALS["VERBOSE"]){echo "GetAvailableListenIP() no match `$line`\n";}
		}
		if(count($GLOBALS["GetAvailableListenIP"])==0){echo "Starting......: No IP ????\n";}
		return $GLOBALS["GetAvailableListenIP"];
	
	}

	public function SQUID_GET_LISTEN_PORT(){
		$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
		while (list ($num, $line) = each ($f)){
			if(preg_match("#http_port\s+[0-9\.]+:([0-9]+)#", $line,$re)){return $re[1];}
			if(preg_match("#http_port\s+([0-9]+)#", $line,$re)){return $re[1];}
		}
		
	}
	
	public function SQUID_GET_LISTEN_SSL_PORT(){
		$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
		while (list ($num, $line) = each ($f)){
			if(preg_match("#https_port\s+[0-9\.]+:([0-9]+)#", $line,$re)){return $re[1];}
			if(preg_match("#https_port\s+([0-9]+)#", $line,$re)){return $re[1];}
		}
	
	}	
	
	public function APACHE_GET_LISTEN_PORT(){
		$f=explode("\n",@file_get_contents("/etc/apache2/ports.conf"));
		while (list ($num, $line) = each ($f)){
			if(preg_match("#Listen\s+[0-9\.]+:([0-9]+)#", $line,$re)){return $re[1];}
			if(preg_match("#Listen\s+([0-9]+)#", $line,$re)){return $re[1];}
		}
	}
	
	public function SQUID_GET_LISTEN_SSLPORT(){
		$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
		while (list ($num, $line) = each ($f)){
			if(preg_match("#https_port\s+[0-9\.]+:([0-9]+)#", $line,$re)){return $re[1];}
			if(preg_match("#https_port\s+([0-9]+)#", $line,$re)){return $re[1];}
		}
	
	}	
	
	
	public function IsSquidReverse(){
		if(isset($GLOBALS["SQUID_IsSquidReverse"])){return $GLOBALS["SQUID_IsSquidReverse"];}
		if(is_file("/etc/artica-postfix/SQUID_REVERSE_APPLIANCE")){$GLOBALS["SQUID_IsSquidReverse"]=true;return true;}
		
		
		
		$squid=$this->LOCATE_SQUID_BIN();
		if(!is_file($squid)){$GLOBALS["SQUID_IsSquidReverse"]=false;return false;}
		$SquidActHasReverse=trim(file_get_contents("/etc/artica-postfix/settings/Daemons/SquidActHasReverse"));
		if(!is_numeric($SquidActHasReverse)){$GLOBALS["SQUID_IsSquidReverse"]=false;return false;}
		if($SquidActHasReverse==1){$GLOBALS["SQUID_IsSquidReverse"]=true;return true;}
	}
	
	public function isNGnx(){
		if(isset($GLOBALS["isNGnx"])){return $GLOBALS["isNGnx"];}
		$nginx=$this->find_program("nginx");
	
		if(!is_file($nginx)){$GLOBALS["isNGnx"]=false;return $GLOBALS["isNGnx"];}
		$sock=new sockets();
		$EnableNginx=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableNginx"));
		if(!is_numeric($EnableNginx)){$EnableNginx=1;}
		if($EnableNginx==1){
			$GLOBALS["isNGnx"]=true;
			return $GLOBALS["isNGnx"];
		}
			
		$GLOBALS["isNGnx"]=false;
		return $GLOBALS["isNGnx"];
	
	}	
	
	
	private function debug($text){
		
		if(!$GLOBALS["VERBOSE"]){return;}
		$trace=@debug_backtrace();
		if(isset($trace[1])){
			$function="{$trace[1]["function"]}()";
			$line="{$trace[1]["line"]}";
		}
		
		echo __CLASS__."/$function [$line]: $text\n";
	}
	
	
	function dskspace_bytes($dir_name){
		
		$du=$this->find_program("du");
		if(is_file($du)){
			exec("$du -bs $dir_name 2>&1",$results);
			while (list ($num, $ligne) = each ($results) ){
				$ligne=trim($ligne);
				if($ligne==null){continue;}
				if(preg_match("#^([0-9]+)\s+#",$ligne,$re)){return $re[1];}
		
		
			}
		
		
		}		
		
		
        $dir_size =0;
           if (is_dir($dir_name)) {
               if ($dh = opendir($dir_name)) {
                  while (($file = readdir($dh)) !== false) {
                  		if(is_link($dir_name."/".$file)){continue;}
                        if($file !="." && $file != ".."){
                              if(is_file($dir_name."/".$file)){
                                   $dir_size += filesize($dir_name."/".$file);
                             }
                             /* check for any new directory inside this directory */
                             if(is_dir($dir_name."/".$file)){
                                $dir_size +=  $this->dskspace_bytes($dir_name."/".$file);
                              }
                           }
                     }
             }
       }
		closedir($dh);
		return $dir_size;
	}
	
	
	
	public function NETWORK_ALL_INTERFACES($OnlyIPS=false){
		include_once(dirname(__FILE__)."/class.tcpip-parser.inc");
		$ip=$this->find_program("ip");
		
		if(!is_file($ip)){
			$this->debug("IP no such binary");
			return array();}
		exec("$ip addr show 2>&1",$results);
		$this->debug("$ip addr show");
		while (list ($num, $ligne) = each ($results) ){
			if(trim($ligne)==null){continue;}
			if(preg_match("#[0-9]+:\s+(.+?):\s+<#", $ligne,$re)){
				$Interface=$re[1];
				$this->debug("****** Found Interface:$Interface *******");
				$array[$re[1]]["INTERFACE"]=$re[1];
				if(preg_match("#PROMISC#", $ligne)){$array[$Interface]["IPADDR"]="0.0.0.0";}
				if(preg_match("#POINTOPOINT#", $ligne)){$array[$Interface]["MAC"]="00:00:00:00:00:00";}
				continue;
			}
			
			
			if(preg_match("#inet\s+([0-9\.]+)\/([0-9]+)\s+brd\s+([0-9\.]+)\s+scope global\s+([a-z0-9]+):([0-9]+)#", $ligne,$re)){
				$array["{$re[4]}:{$re[5]}"]["INTERFACE"]="{$re[4]}:{$re[5]}";
				$array["{$re[4]}:{$re[5]}"]["IPADDR"]=$re[1];
				$array["{$re[4]}:{$re[5]}"]["CDIR"]=$re[1]."/".$re[2];
				$array["{$re[4]}:{$re[5]}"]["SCOPE"]=$re[3];
				continue;
				
			}
			
	
			
			if(preg_match("#inet\s+([0-9\.]+)\/([0-9]+)\s+brd\s+([0-9\.]+)\s+#", $ligne,$re)){
				$IPTABLE[$re[1]]=true;
				$array[$Interface]["IPADDR"]=$re[1];
				$array[$Interface]["CDIR"]=$re[1]."/".$re[2];
				$array[$Interface]["SCOPE"]=$re[3];
				$tcp=new Unixipv4($re[1], $re[2]);
				$parseCIDR=$tcp->netmask();
				$array[$Interface]["NETMASK"]=$parseCIDR;
				continue;
			}
			
			if(preg_match("#inet\s+([0-9\.]+)\/([0-9]+)\s+scope\s+global\s+#", $ligne,$re)){
				$IPTABLE[$re[1]]=true;
				$array[$Interface]["IPADDR"]=$re[1];
				$array[$Interface]["CDIR"]=$re[1]."/".$re[2];
				$array[$Interface]["SCOPE"]="0.0.0.0/0";
				$tcp=new Unixipv4($re[1], $re[2]);
				$parseCIDR=$tcp->netmask();
				$array[$Interface]["NETMASK"]=$parseCIDR;
				continue;
			}
			
			
			
			if(preg_match("#link\/ether\s+([0-9a-z:]+)\s+#", $ligne,$re)){
				
				$array[$Interface]["MAC"]=$re[1];
				continue;
			}
		
			if(preg_match("#inet6\s+(.+)\/([0-9]+)\s+#", $ligne,$re)){
				$array[$Interface]["v6"]=$re[1];
				$array[$Interface]["v6CDIR"]=$re[1]."/".$re[2];
				continue;
			}
			
			if(preg_match("#state UP#", $ligne,$re)){
				$array[$Interface]["STATE"]="yes";
			}
			if(preg_match("#state DOWN#", $ligne,$re)){
				$array[$Interface]["STATE"]="no";
			}	

			
			$this->debug("No match `$ligne`");
		
		}
		
		if($OnlyIPS){return $IPTABLE;}
		$results=array();
		exec("$ip route list 2>&1",$results);
		
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#default via\s+([0-9\.]+)\s+dev\s+([0-9A-Za-z\-\_]+)#", $ligne,$re)){
				$Interface=$re[2];
				if($Interface==null){continue;}
				$array[$Interface]["GATEWAY"]=$re[1];
					
			}
		}
		

		return $array;
		
	}
	
	
	function NETWORK_DEBIAN_FILCONF($eth){
		$f=explode("\n",@file_get_contents('/etc/network/interfaces'));
		$ttr["address"]="IPADDR";
		$ttr["netmask"]="NETMASK";
		$ttr["gateway"]="GATEWAY";
		$ttr["broadcast"]="BROADCAST";
		$ttr["network"]="NETWORK";
		$ttr["metric"]="METRIC";
		
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match("#iface\s+(.+?)\s+.*?(static|dhcp)#", $ligne,$re)){
				$interface=$re[1];
				$outputz[$interface]["BOOTPROTO"]=$re[2];
				$outputz[$interface]="METHOD=debian";
				$outputz[$interface]="DEVICE=$interface";
				continue;
			}
		
			if(preg_match("#(.+?)\s+(.+)#", $ligne,$re)){
				$key=strtolower(trim($re[1]));
				if(isset($ttr[$key])){
					$outputz[$interface][$ttr[$key]]=trim($re[2]);
					continue;
				}
					
				$outputz[$interface][$key]=$re[2];
		
			}
		
		}
		
		while (list ($num, $ligne) = each ($outputz[$eth]) ){
			$return[]="$num=$ligne";
		}
		return @implode("\n",$return);
	}
	
	
	function APACHE_ListenDefaultAddress(){
		if(isset($GLOBALS["APACHE_ListenDefaultAddress"])){return $GLOBALS["APACHE_ListenDefaultAddress"];}
		if($this->isNGnx()){$GLOBALS["APACHE_ListenDefaultAddress"]="127.0.0.1";return "127.0.0.1";}
		if($this->IsSquidReverse()){$GLOBALS["APACHE_ListenDefaultAddress"]="127.0.0.1";return "127.0.0.1";}
		
		$GLOBALS["APACHE_ListenDefaultAddress"]=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/FreeWebListen"));
		if($GLOBALS["APACHE_ListenDefaultAddress"]==null){$GLOBALS["APACHE_ListenDefaultAddress"]="*";}
		if($GLOBALS["APACHE_ListenDefaultAddress"]<>"*"){
			if(is_numeric($GLOBALS["APACHE_ListenDefaultAddress"])){
				if($GLOBALS["VERBOSE"]){echo "Starting......: Apache WARNING `{$GLOBALS["APACHE_ListenDefaultAddress"]}` is not a valid network address...\n";}
				$GLOBALS["APACHE_ListenDefaultAddress"]="*";
			}
		}
	
		if($GLOBALS["APACHE_ListenDefaultAddress"]=="0.0.0.0"){return $GLOBALS["APACHE_ListenDefaultAddress"];}
		if($GLOBALS["APACHE_ListenDefaultAddress"]=="*"){return $GLOBALS["APACHE_ListenDefaultAddress"];}
		$GetAvailableListenIP=$this->GetAvailableListenIP();
		if(!isset($GetAvailableListenIP[$GLOBALS["APACHE_ListenDefaultAddress"]])){
			if($GLOBALS["VERBOSE"]){echo "Starting......: Apache WARNING `{$GLOBALS["APACHE_ListenDefaultAddress"]}` does not exists...\n";}
			$GLOBALS["APACHE_ListenDefaultAddress"]="*";
			return $GLOBALS["APACHE_ListenDefaultAddress"];
		}
			return $GLOBALS["APACHE_ListenDefaultAddress"];
	}

	public function APACHE_BIN_PATH(){
		$bin=$this->find_program("apache2");
		if(is_file($bin)){return $bin;}
		$bin=$this->find_program("apache");
		if(is_file($bin)){return $bin;}
		$bin=$this->find_program("httpd");
		if(is_file($bin)){return $bin;}
	}
	
	
	public function APACHE_PID_PATH(){
		$httpd_conf=$this->LOCATE_APACHE_CONF_PATH();
		if(!is_file($httpd_conf)){return null;}
		$f=file($httpd_conf);
		$PidFile=null;
		while (list ($num, $line) = each ($f)){
			if(preg_match("#^PidFile\s+(.+)#", $line,$re)){
				$PidFile=trim($re[1]);
				break;
			}
		}
		
		if($PidFile==null){
			$PidFile=$this->APACHE_DEFAULT_PIDLOG();
		}
		
		
		if($PidFile=='${APACHE_PID_FILE}'){
			if(is_file("/etc/apache2/envvars")){
				$f=file("/etc/apache2/envvars");
				while (list ($num, $line) = each ($f)){
					if(preg_match("#^export APACHE_PID_FILE=(.+)#",$line,$re)){
						$PidFile=trim($re[1]);
						$PidFile=str_replace('$SUFFIX','',$PidFile);
						break;
					}
				}
			}
		}
		if($PidFile=="run/httpd.pid"){$PidFile="/var/run/httpd.pid";}
		return $PidFile;
	}
function GET_PERFS($value){
	if(!class_exists("Bs_IniHandler")){
		$ini=new iniFrameWork("/etc/artica-postfix/performances.conf");
	}else{
		$ini=new Bs_IniHandler("/etc/artica-postfix/performances.conf");
	}
	return $ini->_params["PERFORMANCES"][$key];
	
}
	
public function EXEC_NICE(){
	$tmp=null;
	$useIonice=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/useIonice"));
	$useNice=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/useNice"));
	$ArticaInCgroups=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaInCgroups"));
	$ArticaCgroup=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaCgroup"));
	$cgroupsEnabled=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/cgroupsEnabled"));
	
	if(!is_numeric($ArticaInCgroups)){$ArticaInCgroups=0;}
	if(!is_numeric($useIonice)){$useIonice=1;}
	if(!is_numeric($useNice)){$useNice=1;}
	if(!is_numeric($cgroupsEnabled)){$cgroupsEnabled=0;}	
	
	if($GLOBALS["VERBOSE"]){echo "EXEC_NICE: cgroupsEnabled=$cgroupsEnabled, ArticaInCgroups=$ArticaInCgroups ArticaCgroup=$ArticaCgroup\n";}
	
	if($ArticaCgroup==null){$ArticaInCgroups=0;}
	if($cgroupsEnabled==0){$ArticaInCgroups=0;}
	if($ArticaInCgroups==1){
		if(is_dir("/cgroups/cpuacct/$ArticaCgroup")){
			$cgexec=$this->find_program("cgexec");
			if(is_file($cgexec)){
				return "$cgexec -g *:$ArticaCgroup ";
			}
		}else{
			if($GLOBALS["VERBOSE"]){echo "EXEC_NICE: /cgroups/cpuacct/$ArticaCgroup no such directory...\n";}
		}
	}
	
	if(!class_exists("Bs_IniHandler")){
		$ini=new iniFrameWork("/etc/artica-postfix/performances.conf");
	}else{
		$ini=new Bs_IniHandler("/etc/artica-postfix/performances.conf");
	}
	
	
	if(isset($ini->_params)){
		if(isset($ini->_params["PERFORMANCES"]['ProcessNice'])){
			$tmp=$ini->_params["PERFORMANCES"]['ProcessNice'];
		}
	}
	if($tmp==null){$tmp=19;}
	if(!is_numeric($tmp)){$tmp=19;}
	if($useIonice==1){
		$ionice=$this->find_program("ionice");
		if(trim($ionice)<>null){$ionice="$ionice -c2 -n7 ";}
	}
	
	if($useNice==1){
		$nice=$this->find_program("nice");
		if(trim($nice)<>null){$nice="$nice --adjustment=$tmp ";}
	}
	if(!isset($ionice)){$ionice=null;}
	return "$ionice$nice";    	
}	
		
		
	public function LIGHTTPD_PORT($conf=null){
		if($conf==null){$conf=$this->LIGHTTPD_CONF_PATH();}
		$tbl=explode("\n",@file_get_contents($conf));
		while (list ($num, $line) = each ($tbl)){
			if(preg_match("#^server\.port.*?=.*?([0-9]+)#",$line,$re)){
				return $re[1];
			}
		}
		
	}
	
	public function LIGHTTPD_USER($conf=null){
		if($conf==null){$conf=$this->LIGHTTPD_CONF_PATH();}
		$tbl=explode("\n",@file_get_contents($conf));
		while (list ($num, $line) = each ($tbl)){
			if(preg_match("#^server\.username.*?=(.+?)$#",$line,$re)){
				$re[1]=str_replace("\"","",$re[1]);
				return trim($re[1]);
			}
		}
		
	}
	
	private function LIGHTTPD_CONF_PATH(){
		if(is_file('/etc/lighttpd/lighttpd.conf')){return '/etc/lighttpd/lighttpd.conf';}
		if(is_file('/usr/local/etc/lighttpd/lighttpd.conf')){return '/usr/local/etc/lighttpd/lighttpd.conf';}
		
	}
	
	public function MEMORY_OF($pid){
		$datas=explode("\n",@file_get_contents("/proc/$pid/status"));
		while (list ($index, $line) = each ($datas)){
		 if(preg_match("#^VmSize.+?([0-9]+)#",$line,$re)){
		 	return $re[1];
		 }
		}
		
	}
	
	public function PPID_OF($pid){
		$datas=explode("\n",@file_get_contents("/proc/$pid/status"));
		while (list ($index, $line) = each ($datas)){
		 if(preg_match("#^PPid.+?([0-9]+)#i",$line,$re)){
		 	if($re[1]<3){return $pid;}
		 	return $re[1];
		 }
		}
		
		return $pid;
		
	}

	public function GET_WIRELESS_CARD(){
		
		$t=explode("\n",@file_get_contents("/proc/net/wireless"));
		while (list ($index, $line) = each ($t)){
			if(preg_match("#(.+?):\s+([0-9]+)#",$line,$re)){return $re[1];}
		}
		
		$iwconfig=$this->find_program("iwconfig");
		if($iwconfig==null){
			if($GLOBALS["VERBOSE"]){echo "Unable to find iwconfig...\n";}
			return;
		}
		
		exec("$iwconfig 2>&1",$results);
		
		while (list ($index, $line) = each ($results)){
			if(preg_match("#^(.+?)\s+.+?ESSID#",$line,$re)){
				return $re[1];
			}else{
				if($GLOBALS["VERBOSE"]){echo "$line NO MATCH...\n";}
			}
		}
		
	}
	
	public function LOCATE_NET_BIN_PATH(){
		$net=$this->find_program("net");
		if(is_file($net)){return $net;}
		$net=$this->find_program("net.samba3");
		if(is_file($net)){return $net;}
	}
	
	public function FULL_HOSTNAME(){
	if(isset($GLOBALS["FULL_HOSTNAME"])){return $GLOBALS["FULL_HOSTNAME"];}
	if(is_file("/etc/artica-postfix/FULL_HOSTNAME")){
		if($this->file_time_min("/etc/artica-postfix/FULL_HOSTNAME")<10){
			$GLOBALS["FULL_HOSTNAME"]=@file_get_contents("/etc/artica-postfix/FULL_HOSTNAME");
			return $GLOBALS["FULL_HOSTNAME"];
		}
	}
	$ypdomainname=$this->find_program("ypdomainname");
	$hostname=$this->find_program("hostname");
	$sysctl=$this->find_program("sysctl");
	if($ypdomainname<>null){
		exec("$ypdomainname 2>&1",$results);
		$domain=trim(@implode(" ",$results));
		
	}else{
		exec("$sysctl -n kernel.domainname 2>&1",$results);
		$domain=trim(@implode(" ",$results));
		
	}
	unset($results);
	exec("$hostname -s 2>&1",$results);
	$host=trim(@implode(" ",$results));
	writelogs_framework("$hostname -s -> $host",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	unset($results);
	
	if(preg_match("#not set#",$domain)){$domain=null;}
	if(preg_match("#\(none#",$domain)){$domain=null;}
	if($domain==null){
		
		exec("$hostname -d 2>&1",$results);
		$domain=trim(@implode(" ",$results));
		if(strpos($domain, "not known")){$domain=null;}
		
		writelogs_framework("$hostname -d -> $domain",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		if($domain<>null){
			if($ypdomainname<>null){shell_exec("$ypdomainname $domain");}
		}
	}
	
	if(strlen($domain)>0){$host="$host.$domain";}
	writelogs_framework("Final = $host ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	$host=str_replace('.(none)',"",$host);
	if(preg_match("#(.+?)\.hostname:#", $host,$re)){$host=$re[1];}	
	$GLOBALS["FULL_HOSTNAME"]=$host;
	@unlink("/etc/artica-postfix/FULL_HOSTNAME");
	@file_put_contents("/etc/artica-postfix/FULL_HOSTNAME", $GLOBALS["FULL_HOSTNAME"]);
	return $GLOBALS["FULL_HOSTNAME"];	
	}
	
	
	public FUNCTION GET_LOCAL_SID(){
		if(!isset($GLOBALS[__FUNCTION__])){$GLOBALS[__FUNCTION__]=null;}
		if($GLOBALS[__FUNCTION__]<>null){return $GLOBALS[__FUNCTION__];}
		if(!isset($_SESSION[__CLASS__])){$_SESSION[__CLASS__][__FUNCTION__]=null;}
		
		if($_SESSION[__CLASS__][__FUNCTION__]<>null){return $_SESSION[__CLASS__][__FUNCTION__];}
		if(is_file("/etc/artica-postfix/LOCAL_SID")){
			$filtime=$this->file_time_min("/etc/artica-postfix/LOCAL_SID");
			if($filtime<520){return @file_get_contents("/etc/artica-postfix/LOCAL_SID");}
		}		
		$net=$this->LOCATE_NET_BIN_PATH();
		if(strlen($net)<3){return null;}
		
		exec("$net getlocalsid 2>&1",$results);
		while (list ($index, $line) = each ($results)){
			if(preg_match("#is:\s+(.+)#",$line,$re)){
				$GLOBALS[__FUNCTION__]=trim($re[1]);
				$_SESSION[__CLASS__][__FUNCTION__]=trim($re[1]);
				@file_put_contents("/etc/artica-postfix/LOCAL_SID",$re[1]);
				writelogs_framework("SID:{$re[1]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return trim($re[1]);
			}
			
			if(preg_match("#Can.+?t fetch domain SID for#",$line,$re)){
				$date=time();
				$newsid='S-1-5-21-2596694876-1976058220-'.$date; 
				exec("$net setlocalsid $newsid",$rr);
				while (list ($a, $b) = each ($rr)){writelogs_framework($b,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				$GLOBALS[__FUNCTION__]=$newsid;
				$_SESSION[__CLASS__][__FUNCTION__]=trim($newsid);
				writelogs_framework("SID:$newsid",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				@file_put_contents("/etc/artica-postfix/LOCAL_SID",$newsid);
				return trim($newsid);
			}
		}
	}
	
	
	public function COUNT_LINES_OF_FILE($path){
		$wc=$this->find_program("wc");
		if($wc==null){
			$array=explode("\n",@file_get_contents("$path"));
			return count($array);
			
		}
		
		exec("$wc -l $path 2>&1",$results);
		$line=@implode(" ",$results);
		if(preg_match("#^([0-9]+)\s+#",$line,$re)){
			return $re[1];
		}
		
	}
	
	private function NETWORK_ADD_ROUTE_REDHAT($nic,$array){
		$count=0;
	if(is_array($array)){
	   	 while (list ($ip, $ip_array) = each ($array) ){
	   	 	$f[]="GATEWAY$count={$ip_array["GATEWAY"]}";
	   	 	$f[]="NETMASK$count={$ip_array["NETMASK"]}";
	   	 	$f[]="ADDRESS$count=$ip";
	   	 	$suse[]="$ip {$ip_array["GATEWAY"]} {$ip_array["NETMASK"]} $nic";
	   	}
	   }		
	  if(is_dir("/etc/sysconfig/network-scripts")){
	  	@file_put_contents("/etc/sysconfig/network-scripts/route-$nic",@implode("\n",$f)."\n");
	  	@chmod("/etc/sysconfig/network-scripts/route-$nic",0777);
	  }
	  if(is_dir("/etc/sysconfig/network/scripts")){
	  	@file_put_contents("/etc/sysconfig/network/scripts/ifroute-$nic",@implode("\n",$suse)."\n");
	  	@chmod("/etc/sysconfig/network/scripts/ifroute-$nic",0777);
	  	shell_exec("ln -s /etc/sysconfig/network/scripts/ifup-route /etc/sysconfig/network/if-up.d/ifup-route >/dev/null");
	  }	  
		
	}
	
	public function NETWORK_ADD_ROUTE($nic,$array){
		if(!is_file("/etc/network/interfaces")){$this->NETWORK_ADD_ROUTE_REDHAT($nic,$array);return;}
		$start=false;
		$newf=array();
		$f=explode("\n",@file_get_contents("/etc/network/interfaces"));
		while (list ($num, $ligne) = each ($f) ){
			if(trim($ligne)==null){unset($f[$num]);continue;}
			if(preg_match("#^iface\s+$nic#",$ligne)){$start=true;$newf[]=$ligne;unset($f[$num]);continue;}
			if($start){
				if(preg_match("#up route add -net#",$ligne)){unset($f[$num]);continue;}
				if(preg_match("#down route del#",$ligne)){unset($f[$num]);continue;}
				$newf[]=$ligne;
				unset($f[$num]);
				if(preg_match("#^iface#",$ligne)){break;}
			}
			
		}
		
	   if(is_array($array)){
	   	 while (list ($ip, $ip_array) = each ($array) ){
	   		$newf[]="\tup route add -net $ip netmask {$ip_array["NETMASK"]} gw {$ip_array["GATEWAY"]}";
	   		$newf[]="\tdown route del -net $ip netmask {$ip_array["NETMASK"]} gw {$ip_array["GATEWAY"]}";
	   	}
	   }
		
	   @file_put_contents("/etc/artica-postfix/routes-interfaces-$nic",@implode("\n",$newf));
	   $new_interface=@implode("\n",$f)."\n".@implode("\n",$newf)."\n";
	   @file_put_contents("/etc/network/interfaces",$new_interface);
		
	}

	public function NETWORK_SAVE_DEBIAN_NIC($array){
		
	}
	
	public function NETWORK_DEBIAN_PARSE_ARRAY($tmpfile=null){
	$file="/etc/network/interfaces";
		if(is_file($tmpfile)){$file=$tmpfile;}
		if($GLOBALS["VERBOSE"]){echo "Parse $file..\n";}
		$f=explode("\n",@file_get_contents($file));
		$nic=array();
		while (list ($num, $ligne) = each ($f) ){
			if(trim($ligne)==null){unset($f[$num]);continue;}
			if(preg_match("#auto\s+(.+?)\s+(.+)$#",$ligne,$re)){
				$iface=trim($re[1]);
				$nic[$iface]["AUTO"]=trim($re[2]);
				continue;
			}
			
			if(preg_match("#iface\s+(.+?)inet\s+(.+)$#",$ligne,$re)){
				$iface=trim($re[1]);
				$nic[$iface]["BOOTPROTO"]=trim($re[2]);
				continue;
			}
			
			if(preg_match("#address\s+([0-9\.]+)$#",$ligne,$re)){$nic[$iface]["IPADDR"]=trim($re[1]);continue;}
			if(preg_match("#gateway\s+([0-9\.]+)$#",$ligne,$re)){$nic[$iface]["GATEWAY"]=trim($re[1]);continue;}
			if(preg_match("#\s+netmask\s+([0-9\.]+)$#",$ligne,$re)){$nic[$iface]["NETMASK"]=trim($re[1]);continue;}
			if(preg_match("#^netmask\s+([0-9\.]+)$#",$ligne,$re)){$nic[$iface]["NETMASK"]=trim($re[1]);continue;}
			if(preg_match("#broadcast\s+([0-9\.]+)$#",$ligne,$re)){$nic[$iface]["BROADCAST"]=trim($re[1]);continue;}
			if(preg_match("#bridge_ports\s+(.+)$#",$ligne,$re)){$nic[$iface]["BRIDGE_PORTS"]=trim($re[1]);continue;}
			if(preg_match("#bridge_stp\s+(.+)$#",$ligne,$re)){$nic[$iface]["BRIDGE_STP"]=trim($re[1]);continue;}
			if(preg_match("#bridge_maxwait\s+(.+)$#",$ligne,$re)){$nic[$iface]["BRIDGE_MAXWAIT"]=trim($re[1]);continue;}
			if(preg_match("#dns-search\s+(.+)$#",$ligne,$re)){$nic[$iface]["DNS_SEARCH"]=trim($re[1]);continue;}
			if(preg_match("#vlan_raw_device\s+(.+)$#",$ligne,$re)){$nic[$iface]["VLAN_ROW_DEVICE"]=trim($re[1]);continue;}
			if(preg_match("#mtu\s+(.+)$#",$ligne,$re)){$nic[$iface]["MTU"]=trim($re[1]);continue;}
			
			
			
			if(preg_match("#dns-nameservers\s+(.+)$#",$ligne,$re)){
				if(preg_match("#([0-9\.]+)\s+([0-9\.]+)#",$re[1],$ri)){
					$nic[$iface]["DNS1"]=$ri[1];
					$nic[$iface]["DNS2"]=$ri[2];
				}else{
					$nic[$iface]["DNS1"]=trim($re[1]);}
				continue;
				}

 			if(preg_match("#up\s+route\s+add.+?net\s+([0-9\.]+)\s+netmask\s+([0-9\.]+)\s+gw\s+([0-9\.]+)#",$ligne,$re)){
 				$nic[$iface]["ROUTES"][$re[1]]=array("NETMASK"=>$re[2],"GATEWAY"=>$re[3]);
 				continue;
 			}			
				
				
			}
		return $nic;
		
	}
	
	public function NETWORK_DEBIAN_SAVE($eth,$arrayToSave=array(),$norestart=false){
		$array_eth=$this->NETWORK_DEBIAN_PARSE_ARRAY();
		
		writelogs_framework("Modify $eth configuration with an array of ". count($arrayToSave) . " elements",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		if(count($arrayToSave)>0){
			while (list ($a, $b) = each ($arrayToSave) ){
				writelogs_framework("Modify $eth configuration with $a=$b",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			}
		}
		
		
		if($arrayToSave["UNSET"]){unset($array_eth[$eth]);}
		if(isset($arrayToSave["AUTO"])){$array_eth[$eth]["AUTO"]=$arrayToSave["AUTO"];}
		if(isset($arrayToSave["BRIDGE_PORTS"])){$array_eth[$eth]["BRIDGE_PORTS"]=$arrayToSave["BRIDGE_PORTS"];}
		if(isset($arrayToSave["BRIDGE_STP"])){$array_eth[$eth]["BRIDGE_STP"]=$arrayToSave["BRIDGE_STP"];}
		if(isset($arrayToSave["BRIDGE_MAXWAIT"])){$array_eth[$eth]["BRIDGE_MAXWAIT"]=$arrayToSave["BRIDGE_MAXWAIT"];}
		if(isset($arrayToSave["DNS_SEARCH"])){$array_eth[$eth]["DNS_SEARCH"]=$arrayToSave["DNS_SEARCH"];}
		if(isset($arrayToSave["VLAN_ROW_DEVICE"])){$array_eth[$eth]["VLAN_ROW_DEVICE"]=$arrayToSave["VLAN_ROW_DEVICE"];}
		if(isset($arrayToSave["MTU"])){$array_eth[$eth]["MTU"]=$arrayToSave["MTU"];}
		if(isset($arrayToSave["IPADDR"])){if($arrayToSave["IPADDR"]<>null){$array_eth[$eth]["IPADDR"]=$arrayToSave["IPADDR"];}}
		if(isset($arrayToSave["NETMASK"])){if($arrayToSave["NETMASK"]<>null){$array_eth[$eth]["NETMASK"]=$arrayToSave["NETMASK"];}}
		if(isset($arrayToSave["BOOTPROTO"])){if($arrayToSave["BOOTPROTO"]<>null){$array_eth[$eth]["BOOTPROTO"]=$arrayToSave["BOOTPROTO"];}}
		if(isset($arrayToSave["GATEWAY"])){if($arrayToSave["GATEWAY"]<>null){$array_eth[$eth]["GATEWAY"]=$arrayToSave["GATEWAY"];}}
		if(isset($arrayToSave["BROADCAST"])){if($arrayToSave["BROADCAST"]<>null){$array_eth[$eth]["BROADCAST"]=$arrayToSave["BROADCAST"];}}
		if(isset($arrayToSave["DNS1"])){if($arrayToSave["DNS1"]<>null){$array_eth[$eth]["DNS1"]=$arrayToSave["DNS1"];}}
		if(isset($arrayToSave["DNS2"])){if($arrayToSave["DNS2"]<>null){$array_eth[$eth]["DNS2"]=$arrayToSave["DNS2"];}}
		if(is_array($arrayToSave["ROUTES"])){$array_eth[$eth]["ROUTES"]=$arrayToSave["ROUTES"];}	

		if(isset($array_eth["lo"]["AUTO"])){$AUTOLO=" ".$array_eth["lo"]["AUTO"];}
		$f[]="auto lo$AUTOLO";
		$f[]="iface lo inet loopback";
		
		unset($array_eth["lo"]);
		$array=array();
		
		while (list ($eth, $array) = each ($array_eth) ){
			if(!isset($array["GATEWAY"])){$array["GATEWAY"]=null;}
			
			writelogs_framework("$eth BOOTPROTO= {$array["BOOTPROTO"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			writelogs_framework("$eth address = {$array["IPADDR"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			
			$f[]="";
			$f[]="auto $eth";
			$f[]="iface $eth inet {$array["BOOTPROTO"]}";
			if($array["BOOTPROTO"]=="dhcp"){continue;}
			
	        $f[]="\taddress {$array["IPADDR"]}";
	        if($array["GATEWAY"]<>null){$f[]="\tgateway {$array["GATEWAY"]}";}
	        $f[]="\tnetmask {$array["NETMASK"]}";
	        if($array["BROADCAST"]<>null){$f[]="\tbroadcast {$array["BROADCAST"]}";}
	        if(isset($array["BRIDGE_PORTS"])){$f[]="\tbridge_ports {$array["BRIDGE_PORTS"]}";}
			if(isset($array["BRIDGE_STP"])){$f[]="\tbridge_stp {$array["BRIDGE_STP"]}";}
			if(isset($array["BRIDGE_MAXWAIT"])){$f[]="\tbridge_maxwait {$array["BRIDGE_MAXWAIT"]}";}
			if(isset($array["DNS_SEARCH"])){$f[]="\tdns-search {$array["DNS_SEARCH"]}";}
			if(isset($array["VLAN_ROW_DEVICE"])){$f[]="\tvlan_raw_device {$array["VLAN_ROW_DEVICE"]}";}
			if(isset($array["MTU"])){$f[]="\tmtu {$array["MTU"]}";}
			
			
	        
	        $dns="{$array["DNS1"]} {$array["DNS2"]}";
	        if(trim($dns)<>null){$f[]="\tdns-nameservers $dns";}
	        if(is_array($array["ROUTES"])){
	         	while (list ($ip, $ip_array) = each ($array["ROUTES"]) ){
	   				$f[]="\tup route add -net $ip netmask {$ip_array["NETMASK"]} gw {$ip_array["GATEWAY"]}";
	   				$f[]="\tdown route del -net $ip netmask {$ip_array["NETMASK"]} gw {$ip_array["GATEWAY"]}";
	   			}	
	        }
	        
		}
		$f[]="";
		@file_put_contents("/etc/network/interfaces",@implode("\n",$f));
		if(is_file("/etc/network/if-up.d/sendmail")){@unlink("/etc/network/if-up.d/sendmail");}
		if(is_file("/etc/network/if-up.d/mountnfs")){@unlink("/etc/network/if-up.d/mountnfs");}
		
		
		
		if(!$norestart){$this->NETWORK_DEBIAN_RESTART();}			
	}
	
public function NETWORK_DEBIAN_RESTART(){
	return;
	$nohup=$this->find_program("nohup");
	if(is_file("/etc/init.d/networking")){
		shell_exec("/etc/init.d/networking stop");
		shell_exec("/etc/init.d/networking start");
	}
	if(is_file("/etc/init.d/network")){
		shell_exec("/etc/init.d/network stop");
		shell_exec("/etc/init.d/network start");
	}
	
}

public function NETWORK_REDHAT_RESTART(){
	
	if(is_file("/etc/init.d/NetworkManager")){
		$this->NETWORK_REDHAT_NETWORKMANAGER_DISABLE();
	}
	
	$nohup=$this->find_program("nohup");
	if(is_file("/etc/init.d/network")){shell_exec(trim("$nohup /etc/init.d/network stop  && /etc/init.d/network start >/dev/null 2>&1 &"));}
}

public function KILL_PROCESS($pid,$signal=0){
	$pid=intval($pid);
	if($pid==0){return;}
	if($signal==null){$signal=15;}
	if($signal==0){$signal=15;}
	if(is_numeric($signal)){posix_kill($pid,$signal);return;}
	
	$arg["HUP"]=1;
	$arg["USR1"]=10;
	$arg["USR2"]=12;
	$arg["TERM"]=15;
	$arg["WINCH"]=28;
	$arg["KILL"]=9;
	if(isset($arg[$signal])){
		posix_kill($pid,$arg[$signal]);
	}

}




function proc_exec($cmd){
	$BUF_SIZE=1024;
	$FD_WRITE=0 ;      # stdin
	$FD_READ=1;
	$FD_ERR= 2;        # stderr	
	$errbuf=null;
	$first_exitcode=null;
	$array=array();
    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w")
    );

    $ptr = proc_open($cmd, $descriptorspec, $pipes, NULL, $_ENV);
    if (!is_resource($ptr)){return array();}

    while (($buffer = fgets($pipes[$FD_READ], $BUF_SIZE)) != NULL || ($errbuf = fgets($pipes[$FD_ERR], $BUF_SIZE)) != NULL) {
        if (!isset($flag)) {
            $pstatus = proc_get_status($ptr);
            $first_exitcode = $pstatus["exitcode"];
            $flag = true;
        }
        if (strlen($buffer)){
        	$buffer=str_replace("\n", "", $buffer);
        	$buffer=str_replace("\r", "", $buffer);
            $array[]=$buffer;
        }
        
        if (strlen($errbuf)){
            //echo "ERR: " . $errbuf;
        }
    }

    foreach ($pipes as $pipe){
        fclose($pipe);
    }

    
    $pstatus = proc_get_status($ptr);
    if (!strlen($pstatus["exitcode"]) || $pstatus["running"]) {
        
        if ($pstatus["running"])
            proc_terminate($ptr);
        $ret = proc_close($ptr);
    } else {
        if ((($first_exitcode + 256) % 256) == 255
                && (($pstatus["exitcode"] + 256) % 256) != 255)
            $ret = $pstatus["exitcode"];
        elseif (!strlen($first_exitcode))
            $ret = $pstatus["exitcode"];
        elseif ((($first_exitcode + 256) % 256) != 255)
            $ret = $first_exitcode;
        else
            $ret = 0; 
        proc_close($ptr);
    }

    return $array;
}





private function NETWORK_REDHAT_NETWORKMANAGER_DISABLE(){
	$cacheFile="/etc/artica-postfix/redhat.network.manager.disabled";
	if(is_file($cacheFile)){return;}
	$chkconfig=$this->find_program("chkconfig");
	$service=$this->find_program("service");
	if(!is_file($chkconfig)){return;}
	shell_exec("$chkconfig NetworkManager off");
	shell_exec("$chkconfig network on");
	shell_exec("$service NetworkManager stop");
	shell_exec("$service network start");
	@file_put_contents("$cacheFile",time());
	}
	
	
	public function CACHE_VERSIONS($KEY){
		if(isset($GLOBALS["CACHE_VERSIONS"][$KEY])){return $GLOBALS["CACHE_VERSIONS"][$KEY];} 
		if(!is_file("/usr/share/artica-postfix/ressources/logs/global.versions.conf")){return null;}
		
		if(!isset($GLOBALS["CACHE_VERSIONS"]["global.versions.conf"])){$GLOBALS["CACHE_VERSIONS"]["global.versions.conf"]=file("/usr/share/artica-postfix/ressources/logs/global.versions.conf");}
		
		while (list ($num, $val) = each ($GLOBALS["CACHE_VERSIONS"]["global.versions.conf"])){
			if(preg_match('#\[$KEY\]\s+"(.+?)"#',$val,$re)){$GLOBALS["CACHE_VERSIONS"][$KEY]=$re[1];return $re[1];}
		}
		
	}

	
	public function LOCATE_SQUID_CONF(){
		   if(is_file('/etc/squid3/squid.conf')){ return '/etc/squid3/squid.conf';}
	   	   if(is_file('/opt/artica/etc/squid.conf')){ return '/opt/artica/etc/squid.conf';}
	   	   if(is_file('/etc/squid/squid.conf')){ return '/etc/squid/squid.conf';}
			}
	
	public function LOCATE_DHCPD_PID_PATH(){
		
		   if(is_file('/var/run/dhcpd.pid')){ return '/var/run/dhcpd.pid';}
	   	   if(is_file('/var/run/dhcpd/dhcpd.pid')){ return '/var/run/dhcpd/dhcpd.pid';}
	   	   if(is_file('/var/run/dhcp3-server/dhcpd.pid')){ return '/var/run/dhcp3-server/dhcpd.pid';}
			}	
	
	public function LOCATE_PURE_FTPD_PID_PATH(){
		if(is_file('/var/run/pure-ftpd/pure-ftpd.pid')){return "/var/run/pure-ftpd/pure-ftpd.pid";}
		if(is_file('/var/run/pure-ftpd.pid')){return "/var/run/pure-ftpd.pid";}
		}
	
	
	 public function SQUID_CONFIG_PATH(){
	   if(is_file("/etc/squid3/squid.conf")){return '/etc/squid3/squid.conf';}
	   if(is_file('/opt/artica/etc/squid.conf')){return '/opt/artica/etc/squid.conf';}
	   if(is_file('/etc/squid/squid.conf')){return '/etc/squid/squid.conf';}
	   return "/etc/squid3/squid.conf";
	}	
	
	public function BetterFS(){
		if(!$this->IsExt4()){return "ext3";}
		if(is_file($this->find_program("mkfs.ext4"))){return "ext4";}
		if(is_file($this->find_program("mkfs.ext3"))){return "ext3";}
		return "ext3";
	}
	
	public function IsExt4(){
		$f=explode("\n",@file_get_contents("/proc/filesystems"));
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match("#.*?\s+ext4#",$ligne)){return true;}
		}
		return false;
	}

	public function hostname_g(){
		$uname=posix_uname();
 		return $uname["nodename"]; 
		}
	public function hostname_simple(){
		$uname=posix_uname();
 		$host=$uname["nodename"]; 
 		if(strpos($host, ".")>0){$tt=explode(".",$host);$host=$tt[0];}
 		return $host;
		}		
		
	function BACKUPPC_locate_config_path(){
		if(is_file("/etc/backuppc/config.pl")){return "/etc/backuppc";}
		if(is_file("/etc/BackupPC/config.pl")){return "/etc/BackupPC";}
	
	}
	
	
	
	
	function chmod_alldirs($bit,$dir){
		$dirz=explode("/",$dir);
		$dirStart="/{$dirz[0]}";
		if($dirStart=="/"){$dirStart=null;}
		for($i=1;$i<count($dirz);$i++){
			$dirStart=$dirStart."/".$dirz[$i];
			if(!is_dir($dirStart)){@mkdir($dirStart,$bit,true);continue;}
			@chmod($dirStart, $bit);
		
		}
		
		
	}
	
	
	function chown_func($user,$group=null,$dir){
		if($group=='${APACHE_RUN_GROUP}'){$group="www-data";}
		if(isset($GLOBALS["chown_func"][md5("$user$group$dir")])){return;}
		$dir=trim($dir);
		$recursive=false;
		
		
		
		if(substr($dir, strlen($dir)-1,1)=="*"){
			$dir=substr($dir, 0,strlen($dir)-1);
			$recursive=true;
		}
		
		if(!is_dir($dir)){
			if(!is_file($dir)){
				return;
			}
		}
		
		if(strpos("/", $group)){
			if((is_file($group)) OR is_dir(is_file($group)) ){$dir=$group;$group=$user;}
		}
		
		if(preg_match("#(.*?):(.+)#", $user,$re)){
			$user=$re[1];
			$group=$re[2];
		}
		
		if($recursive){
			$array=$this->dirdir($dir);
			chown($dir, $user);
			chgrp($dir, $group);	
			
			while (list ($directory, $ligne) = each ($array) ){
				chown($directory, $user);
				chgrp($directory, $group);				
			}
			
			$array=$this->DirFiles($dir);
			while (list ($filesname, $ligne) = each ($array) ){
				$finale="$dir/$filesname";
				$finale=str_replace("//", "/", $finale);
				chown("$finale", $user);
				chgrp("$finale", $group);				
			}			
			
		}else{		
			chown($dir, $user);
			chgrp($dir, $group);
		}
		
		$GLOBALS["chown_func"][md5("$user$group$dir")]=true;
		
	}
	
	
	
	
	
	function chmod_func($bit,$dir){
		if(isset($GLOBALS["chmod_func"][md5("$bit$dir")])){return;}
		$dir=trim($dir);
		$recursive=false;
		
		
		
		if(substr($dir, strlen($dir)-1,1)=="*"){
			$dir=substr($dir, 0,strlen($dir)-1);
			$recursive=true;
		}
		
		if(!is_dir($dir)){if(!is_file($dir)){return;}}	
			
	if($recursive){
			$dir=substr($dir, 0,strlen($dir)-1);
			@chmod("$dir", $bit);
			$array=$this->dirdir($dir);
			while (list ($directory, $ligne) = each ($array) ){
				@chmod($directory, $bit);
							
			}
			
			$array=$this->DirFiles($dir);
			while (list ($filesname, $ligne) = each ($array) ){
				$finale="$dir/$filesname";
				$finale=str_replace("//", "/", $finale);				
				@chmod("$finale", $bit);
								
			}			
			
	}else{
		@chmod("$dir", $bit);
	}		
					
		$GLOBALS["chmod_func"][md5("$bit$dir")]=true;
		
	}
	
	function PEAR_INSTALL_CHECK($packagename){
		$pear=$this->find_program("pear");
		if(strlen($pear)<5){return false;}
		exec("$pear list 2>&1",$array);
		while (list ($num, $ligne) = each ($array) ){
			if(preg_match("#^$packagename\s+#",$ligne,$re)){
				return true;
			}
		}
		return false;
	}
	

	function BACKUPPC_GET_CONFIG_INFOS($key){
		$f=explode("\n",@file_get_contents($this->BACKUPPC_locate_config_path()."/config.pl"));
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match("#Conf\{$key\}.*?'(.+?)';#i",$ligne,$re)){
				$re[1]=trim($re[1]);
				$re[2]=str_replace("'","",$re[1]);
				return $re[1];
			}
		}
		
	}
	
	function BACKUPPC_GET_CGIBIN_PATH(){
		if(is_file("/usr/share/backuppc/cgi-bin/index.cgi")){return "/usr/share/backuppc/cgi-bin/index.cgi";}
		if(is_file("/usr/share/backuppc/cgi-bin/BackupPC_Admin")){return "/usr/share/backuppc/cgi-bin/BackupPC_Admin";}
		if(is_file("/usr/share/BackupPC/sbin/BackupPC_Admin")){return "/usr/share/BackupPC/sbin/BackupPC_Admin";}
		}
		
	function BACKUPPC_GET_IMG_DIR(){
		if(is_dir("/usr/share/backuppc/image")){return "/usr/share/backuppc/image";}
		if(is_dir("/usr/share/BackupPC/html")){return "/usr/share/BackupPC/html";}
		
		}
		
	function ZARAFA_VERSION(){
		$func=__CLASS__.'/'.__FUNCTION__;
		if(strlen($GLOBALS["ZARAFA_VERSION"])>5){return $GLOBALS["ZARAFA_VERSION"];}
		$zarafa_server=$this->find_program("zarafa-server");
		if(!is_file($zarafa_server)){
			if($GLOBALS["VERBOSE"]){echo "$func:: zarafa-server no such file\n";}
			return;
		}
		exec("$zarafa_server -V 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#Product version:\s+([0-9,\.]+)#",$ligne,$re)){
				$re[1]=trim($re[1]);
				$re[1]=str_replace(",",".",$re[1]);
				$GLOBALS["ZARAFA_VERSION"]=$re[1];
				return $re[1];
			}else{
				if($GLOBALS["VERBOSE"]){echo "$func:: $ligne no match\n";}
			}
			
		}
		
	}
	
	function ASSP_VERSION(){
		$func=__CLASS__.'/'.__FUNCTION__;
		if(strlen($GLOBALS["ASSP_VERSION"])>2){return $GLOBALS["ASSP_VERSION"];}
		$results=@explode("\n",@file_get_contents("/usr/share/assp/assp.pl"));
		if($GLOBALS["VERBOSE"]){echo count($results)." lines to parse\n";}
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#our\s+\$version[\s=]+.+?([0-9\.]+)#",$ligne,$re)){
				$re[1]=trim($re[1]);
				if($GLOBALS["VERBOSE"]){echo "$func:: match $ligne\n";}
				$GLOBALS["ASSP_VERSION"]=$re[1];
				return $re[1];
			}
			
			if(preg_match('#our.*?\$version\s+.*?([0-9\.]+)#',$ligne,$re)){
				$re[1]=trim($re[1]);
				$GLOBALS["ASSP_VERSION"]=$re[1];
				if($GLOBALS["VERBOSE"]){echo "$func:: match $ligne\n";}
				return $re[1];
			}
			
		}
		
		
	}
	
	public function clean_EtcHosts(){ return;	}	
	
	
	public function get_EtcHostsByName($hostname){
		$hostname=str_replace(".", "\.", $hostname);
		$f=explode("\n",@file_get_contents("/etc/hosts"));
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match("#^([0-9\.]+).*?$hostname#", $ligne,$re)){
				return $re[1];
			}
		}
	}

	public function del_EtcHostsByName($hostname){}	
	public function del_EtcHosts($ip){}
	public function add_EtcHosts($hostname,$ip){}
		
		
		
	public function DISK_LIST(){
		$tmp=$this->FILE_TEMP();
		$results=array();
		exec($this->find_program("df") ." -P -h -B G",$returns);
		$pattern="#(.+)\s+([0-9]+)([A-Z])\s+([0-9]+)([A-Z])\s+([0-9]+)([A-Z])\s+([0-9]+)\%\s+(.+?)$#";
		while (list ($num, $ligne) = each ($returns) ){
			if(!preg_match($pattern,$ligne,$re)){continue;}
			$results[trim($re[1])]=array("SIZE"=>$re[2],"UNIT"=>$re[3],"USED"=>$re[4],"DISP"=>$re[6],"POURC"=>trim($re[8]));
			
		}
		
		return $results;
	}
	
public function file_size($path){

	exec($this->find_program("stat")." $path ",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#Size:\s+([0-9]+)\s+Blocks#",$line,$re)){
			return $re[1];break;
		}
	}
	return 0;
}

public function file_size_human($path){

	exec($this->find_program("du")." -h $path ",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#^([0-9A-Z,]+)\s+#",$line,$re)){
			return $re[1];break;
		}
	}
	return 0;
}

public function file_extension($filename){
	return pathinfo($filename, PATHINFO_EXTENSION);
}

public function compress($source,$dest){
		if(!function_exists("gzopen")){
			$called=null;if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
			$this->_syslog("FATAL!! gzopen no such function ! $called in ".__FUNCTION__." line ".__LINE__, basename(__FILE__));
			return false;
		}
	    $mode='wb9';
	    $error=false;
	    if(is_file($dest)){@unlink($dest);}
	    $fp_out=gzopen($dest,$mode);
	    if(!$fp_out){return;}
	    $fp_in=fopen($source,'rb');
	    if(!$fp_in){return;}
	    while(!feof($fp_in)){gzwrite($fp_out,fread($fp_in,1024*512));}
	    fclose($fp_in);
	    gzclose($fp_out);
		return true;
	}
	

	
public function uncompress($srcName, $dstName) {
		if(!function_exists("gzopen")){
			$called=null;if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
			$this->_syslog("FATAL!! gzopen no such function ! $called in ".__FUNCTION__." line ".__LINE__, basename(__FILE__));
			return false;
		}
		$dir=dirname($dstName);
		if(!is_dir($dir)){if($GLOBALS["VERBOSE"]){echo "$dir no such directory\n";}return false;}
		$dstNameTMP="$dstName.tests";
	    $sfp = gzopen($srcName, "rb");
	    if(!$sfp){if($GLOBALS["VERBOSE"]){echo "gzopen($srcName) return false\n";}return false;}
	    $fp = fopen($dstNameTMP, "w");
		if(!$sfp){if($GLOBALS["VERBOSE"]){echo "fopen($dstNameTMP) Permission denied.\n";}return false;}
	    
	    while ($string = gzread($sfp, 4096)) {fwrite($fp, $string, strlen($string));}
	    gzclose($sfp);
	    fclose($fp);
	    $size=$this->file_size($dstNameTMP);
	    if($size==0){
	    	@unlink($dstNameTMP);
	    	return false;
	    }
	    @unlink($dstName);
	    @copy($dstNameTMP, $dstName);
	    @unlink($dstNameTMP);
	    return true;
	    
} 

function squid_version(){
	$squidbin=$this->LOCATE_SQUID_BIN();
	if(!is_file($squidbin)){return "0.0.0";}
	exec("$squidbin -v 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Squid Cache: Version\s+(.+)#", $val,$re)){
			return trim($re[1]);
		}
	}

}
function samba_version(){
	
	$winbind=$this->find_program("smbd");
	exec("$winbind -V 2>&1",$results);
	if(preg_match("#Version\s+([0-9\.]+)#i", @implode("", $results),$re)){
		return $re[1];
	}
}

public function Winbindd_privileged_SQUID(){
	
	$chmod=$this->find_program("chmod");
	$setfacl=$this->find_program("setfacl");
	$nohup=$this->find_program("nohup");
	
	$possibleDirsACLS[]="/var/lib/samba";
	$possibleDirsACLS[]="/var/run/samba";
	$possibleDirsACLS[]="/var/cache/samba";
	
	
		while (list ($num, $Directory) = each ($possibleDirsACLS) ){
			if(!is_dir($Directory)){continue;}
			shell_exec("$nohup $setfacl -R -m u:squid:rwx $Directory >/dev/null 2>&1 &");
			shell_exec("$nohup $setfacl -R -m g:squid:rwx $Directory >/dev/null 2>&1 &");
			
			if(is_dir("$Directory/winbindd_privileged")){
				shell_exec("$chmod 0750 $Directory/winbindd_privileged >/dev/null");
				
				if(strlen($setfacl)>5){
					shell_exec("$setfacl -m u:squid:rx $Directory/winbindd_privileged >/dev/null 2>&1");
				}
				
				$stat=$this->alt_stat("$Directory/winbindd_privileged/pipe");
				if(isset($stat["owner"])){
					shell_exec("$chmod 1777 $Directory/winbindd_privileged/pipe >/dev/null");
				}
			}

			
			if(!is_dir("$Directory/smb_krb5")){continue;}
			if(strlen($setfacl)>5){
				shell_exec("$nohup $setfacl -R -m u:squid:rwx $Directory/smb_krb5 >/dev/null 2>&1 &");
				shell_exec("$nohup $setfacl -R -m g:squid:rwx $Directory/smb_krb5 >/dev/null 2>&1 &");
			}
			
		}
		
}

public function _syslog($text,$file,$error=false){
	if(!function_exists("syslog")){return;}
	$file=basename($file);
	if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
	openlog($file, LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
}	

public function is_socket($socketpath){
	$stat=$this->alt_stat($socketpath);
	$type=$stat["filetype"]["type"];
	if($type=="socket"){return true;}
	return false;
}
	
public function alt_stat($file) {
	 $ss=@stat($file);
	 if(!$ss) return false; //Couldnt stat file
	 
	 $ts=array(
	  0140000=>'ssocket',
	  0120000=>'llink',
	  0100000=>'-file',
	  0060000=>'bblock',
	  0040000=>'ddir',
	  0020000=>'cchar',
	  0010000=>'pfifo'
	 );
	 
	 $p=$ss['mode'];
	 $t=decoct($ss['mode'] & 0170000); // File Encoding Bit
	 
	 $str =(array_key_exists(octdec($t),$ts))?$ts[octdec($t)]{0}:'u';
	 $str.=(($p&0x0100)?'r':'-').(($p&0x0080)?'w':'-');
	 $str.=(($p&0x0040)?(($p&0x0800)?'s':'x'):(($p&0x0800)?'S':'-'));
	 $str.=(($p&0x0020)?'r':'-').(($p&0x0010)?'w':'-');
	 $str.=(($p&0x0008)?(($p&0x0400)?'s':'x'):(($p&0x0400)?'S':'-'));
	 $str.=(($p&0x0004)?'r':'-').(($p&0x0002)?'w':'-');
	 $str.=(($p&0x0001)?(($p&0x0200)?'t':'x'):(($p&0x0200)?'T':'-'));
 
		 $s=array(
		 'perms'=>array(
		  'umask'=>sprintf("%04o",@umask()),
		  'human'=>$str,
		  'octal1'=>sprintf("%o", ($ss['mode'] & 000777)),
		  'octal2'=>sprintf("0%o", 0777 & $p),
		  'decimal'=>sprintf("%04o", $p),
		  'fileperms'=>@fileperms($file),
		  'mode1'=>$p,
		  'mode2'=>$ss['mode']),
		 
		 'owner'=>array(
		  'fileowner'=>$ss['uid'],
		  'filegroup'=>$ss['gid'],
		  'owner'=>
		  (function_exists('posix_getpwuid'))?
		  @posix_getpwuid($ss['uid']):'',
		  'group'=>
		  (function_exists('posix_getgrgid'))?
		  @posix_getgrgid($ss['gid']):''
		  ),
		 
		 'file'=>array(
		  'filename'=>$file,
		  'realpath'=>(@realpath($file) != $file) ? @realpath($file) : '',
		  'dirname'=>@dirname($file),
		  'basename'=>@basename($file)
		  ),
		
		 'filetype'=>array(
		  'type'=>substr($ts[octdec($t)],1),
		  'type_octal'=>sprintf("%07o", octdec($t)),
		  'is_file'=>@is_file($file),
		  'is_dir'=>@is_dir($file),
		  'is_link'=>@is_link($file),
		  'is_readable'=> @is_readable($file),
		  'is_writable'=> @is_writable($file)
		  ),
		 
		 'device'=>array(
		  'device'=>$ss['dev'], //Device
		  'device_number'=>$ss['rdev'], //Device number, if device.
		  'inode'=>$ss['ino'], //File serial number
		  'link_count'=>$ss['nlink'], //link count
		  'link_to'=>($s['type']=='link') ? @readlink($file) : ''
		  ),
		 
		 'size'=>array(
		  'size'=>$ss['size'], //Size of file, in bytes.
		  'blocks'=>$ss['blocks'], //Number 512-byte blocks allocated
		  'block_size'=> $ss['blksize'] //Optimal block size for I/O.
		  ),
		 
		 'time'=>array(
		  'mtime'=>$ss['mtime'], //Time of last modification
		  'atime'=>$ss['atime'], //Time of last access.
		  'ctime'=>$ss['ctime'], //Time of last status change
		  'accessed'=>@date('Y M D H:i:s',$ss['atime']),
		  'modified'=>@date('Y M D H:i:s',$ss['mtime']),
		  'created'=>@date('Y M D H:i:s',$ss['ctime'])
		  ),
		 );

 return $s;
}	
	
	private function MapperDetect($dev){
	$mapper=@readlink($dev);
	if($mapper==null){return $dev;}
	$mapper=str_replace("../mapper","/dev/mapper",$mapper);
	return $mapper;
	}
	
	private function folder_to_regex($path){
		$path=str_replace("/","\/",$path);
		$path=str_replace(".","\.",$path);
		$path=str_replace("$","\$",$path);
		$path=str_replace("[","\[",$path);		
		$path=str_replace("]","\]",$path);
		$path=str_replace("(","\(",$path);
		$path=str_replace(")","\)",$path);
		$path=str_replace("?","\?",$path);
		$path=str_replace("!","\!",$path);
		$path=str_replace("{","\{",$path);
		$path=str_replace("}","\}",$path);
		return $path;
	}
	
	function LESSFS_ARRAY(){
		$datas=explode("\n",@file_get_contents("/proc/mounts"));
		$array=array();
		while (list ($num, $ligne) = each ($datas) ){
		 if(preg_match("#^lessfs\s+(.+?)\s+fuse\.lessfs\s+#",$ligne,$re)){$array[trim($re[1])]=true;}
		}
		return $array;
		
	}
	
	public function DISK_MOUNTED($dev){
		$dev=$this->MapperDetect($dev);
		$datas=explode("\n",@file_get_contents("/proc/mounts"));
		$dev=$this->folder_to_regex($dev);
		
		while (list ($num, $ligne) = each ($datas) ){
			if(preg_match("#$dev#",$ligne)){
				writelogs_framework("\"$dev\"=$ligne",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return true;
			}else{
				
			}
		}
	return false;}
	
	public function MOUNTED_PATH($dev){
		$dev=$this->MapperDetect($dev);
		$dev=$this->folder_to_regex($dev);
		$datas=explode("\n",@file_get_contents("/proc/mounts"));
		while (list ($num, $ligne) = each ($datas) ){
			if(preg_match("#^$dev\s+(.+?)\s+(btrfs|ext|rw)#",$ligne,$re)){
				return trim($re[1]);	
			}
		}
	}
	
	public function MOUNTED_DIR($path){
		$path=$this->MapperDetect($path);
		$path=$this->folder_to_regex($path);
		$datas=explode("\n",@file_get_contents("/proc/mounts"));
		while (list ($num, $ligne) = each ($datas) ){
			if(!preg_match("#^(.*?)\s+$path\s+(btrfs|ext|rw)#",$ligne,$re)){
				if($GLOBALS["VERBOSE"]){echo "Line: ".__LINE__.":: $ligne -> ^(.*?)\s+$path\s+(btrfs|ext|rw) NOT match ".__FUNCTION__."\n";}
				continue;
			}
			return trim($re[1]);
		}
	}	
	
	public function LSOF_PIDS($directory){
		$lsof=$this->find_program("lsof");
		exec("$lsof $directory 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#^.+?\s+([0-9]+)#",$ligne,$re)){
				$pids[]=$re[1];
			}
		}
		return $pids;
	}
	
	
	public function MOUNTED_TMPFS_MEM($directory){
		$datas=explode("\n",@file_get_contents("/proc/mounts"));
		while (list ($num, $ligne) = each ($datas) ){
			if(preg_match("#^tmpfs\s+(.+?)\s+tmpfs\s+.+?size=([0-9]+)k\s+#",$ligne,$re)){
				$tmpfs[trim($re[1])]=round($re[2]/1024);
				if($GLOBALS["VERBOSE"]){echo "MOUNTED_TMPFS_MEM:: FOUND {$re[1]} For ".round($re[2]/1024)."M\n";}
				continue;
			}
			if(preg_match("#^tmpfs\s+(.*?)\s+tmpfs\s+.*?,size=([0-9]+)k,#", $ligne,$re)){
				$tmpfs[trim($re[1])]=round($re[2]/1024);
				if($GLOBALS["VERBOSE"]){echo "MOUNTED_TMPFS_MEM:: FOUND {$re[1]} For ".round($re[2]/1024)."M\n";}
				continue;				
			}
		}
		if($tmpfs[trim($directory)]==null){$tmpfs[trim($directory)]=0;}
		return $tmpfs[trim($directory)];
		
	}
	
	public function PROCCESS_TIME_MIN($pid){
		$sec=null;
		if($pid<5){return 0;}
		if($pid==null){return 0;}
		exec("/usr/share/artica-postfix/bin/procstat $pid 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#start_time:.+?\(([0-9]+)#",$ligne,$re)){
				$sec=$re[1];
			}
			
			
		}
		
		if($sec==null){$sec=0;}
		if($sec>0){$sec=$sec/60;return trim(round($sec));}
		return 0;
	}
	
	public function PROCESS_MEMORY($pid,$noMo=false){
		//$PPID=$this->PROCESS_PPID();
		//if($PPID>1)
		
		if(!is_file("/proc/$pid/status")){return 0;}
		$f=@explode("\n",@file_get_contents("/proc/$pid/status"));
		$pattern="#^VmRSS:\s+([0-9]+)#";
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match($pattern,$ligne,$re)){
				if($noMo){return $re[1];}
				return round($re[1]/1024,2);
			}
		}
		
	}
	
	public function PROCESS_CACHE_MEMORY($pid,$noMo=false){
		//$PPID=$this->PROCESS_PPID();
		//if($PPID>1)
		
		if(!is_file("/proc/$pid/status")){return 0;}
		$f=@explode("\n",@file_get_contents("/proc/$pid/status"));
		$pattern="#^VmSize:\s+([0-9]+)#";
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match($pattern,$ligne,$re)){
				if($noMo){return $re[1];}
				return round($re[1]/1024,2);
			}
		}
		
	}	
	
	private function PROCESS_PPID($pid){
		$pid=trim($pid);
		$pid=str_replace("\r", "", $pid);
		$pid=str_replace("\n", "", $pid);
		if(!is_file("/proc/$pid/status")){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: unable to stat `/proc/$pid/status`\n";}
		return 0;}
		$f=@explode("\n",@file_get_contents("/proc/$pid/status"));
		$pattern="#^PPid:\s+([0-9]+)#";
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match($pattern,$ligne,$re)){
				if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: Found {$re[1]}\n";}
				if(trim($re[1])<50){return $pid;}
				return trim($re[1]);
			}
		}
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: not Found return $pid\n";}
		return $pid;
		
	}
	
	
	public function PROCESS_STATUS($pid){
		$datas=explode("\n",@file_get_contents("/proc/$pid/status"));
		if(!is_array($datas)){return array();}
		while (list ($num, $ligne) = each ($datas) ){
			if(preg_match("#State:\s+([A-Z])\s+#",$ligne,$re)){
				$STATUS=$re[1];
			}
			if(preg_match("#PPid:\s+([0-9]+)#",$ligne,$re)){
				$PPID=$re[1];
			}			
		}
		if($STATUS==null){return array();}
		return array($STATUS,$PPID);
	}
	
	
	public function SPAMASSASSIN_LOCAL_CF(){
		if(is_file("/etc/spamassassin/local.cf")){return('/etc/spamassassin/local.cf');}
		if(is_file("/etc/mail/spamassassin/local.cf")){return('/etc/mail/spamassassin/local.cf');}
		if(is_file("/opt/artica/etc/spamassassin/local.cf")){return('/opt/artica/etc/spamassassin/local.cf');}
	    @mkdir('/etc/spamassassin');
		shell_exec('/bin/touch /etc/spamassassin/local.cf');
		return '/etc/spamassassin/local.cf';
		}
		
		
	public function LOCATE_maillogconvert(){
		$f[]="/usr/local/awstats/tools/maillogconvert.pl";
		$f[]="/usr/bin/maillogconvert.pl";
		$f[]="/usr/share/awstats/maillogconvert.pl";
		$f[]="/usr/share/doc/awstats/examples/maillogconvert.pl";
		$f[]="/var/www/cgi-bin/awstats/maillogconvert.pl";
		$f[]="/usr/share/artica-postfix/bin/maillogconvert.pl";
		while (list ($num, $filename) = each ($f) ){if(is_file($filename)){return $filename;}}
	}
		
	public function get_pid_from_file($file){
		$datas=trim(@file_get_contents($file));
		if(preg_match("#([0-9]+)#",trim($datas),$re)){return $re[1];}
	}
	
	
	public function HostToIp($hostname){
		if(trim($hostname)==null){return;}
		$hostname=str_replace('$','',$hostname);
		$ip=gethostbyname($hostname);
		writelogs_framework("$hostname=$ip",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		if($ip<>$hostname){
			if(preg_match("#([0-9]+)\.([0-9]+).([0-9]+)\.([0-9]+)#",$ip)){return $ip;}
		}
		$nmblookup=$this->find_program("nmblookup");
		writelogs_framework("nmblookup=$nmblookup",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);

		if($nmblookup==null){return;}
		if(preg_match("#([0-9]+)\.([0-9]+).([0-9]+)\.([0-9]+)#",$hostname)){return;}
		$cmd="$nmblookup $hostname";
		exec($cmd,$results);
	
		while (list ($num, $ligne) = each ($results) ){
			writelogs_framework("$ligne",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			if(preg_match("#^([0-9\.]+)\s+#",$ligne,$re)){return $re[1];}
		}		
		
	}
	public function IpToHostname($ipaddr){
		$ipaddr=trim($ipaddr);
		if(!$this->isIPAddress($ipaddr)){return;}		
		if(isset($GLOBALS["IPNAMES"][$ipaddr])){return $GLOBALS["IPNAMES"][$ipaddr];}
	
		if(count($GLOBALS["IPNAMES"])>1500){$GLOBALS["IPNAMES"]=array();}
		$hostres=gethostbyaddr($ipaddr);
		if(!$this->isIPAddress($hostres)){
			$GLOBALS["IPNAMES"][$ipaddr]=$hostres;
			return $hostres;
		}
		$GLOBALS["IPNAMES"][$ipaddr]=$ipaddr;
		return $ipaddr;
	}	
	
	
	public function IpToMac($ipaddr){
		$ipaddr=trim($ipaddr);
		if(!$this->isIPAddress($ipaddr)){return;}
		
		$ttl=date('YmdH');
		if(count($GLOBALS["CACHEARP"])>3){unset($GLOBALS["CACHEARP"]);}
		if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}
		
		if(!isset($GLOBALS["SBIN_ARP"])){$GLOBALS["SBIN_ARP"]=$this->find_program("arp");}
		if(!isset($GLOBALS["SBIN_ARPING"])){$GLOBALS["SBIN_ARPING"]=$this->find_program("arping");}
		
		if(strlen($GLOBALS["SBIN_ARPING"])>3){
			$cmd="{$GLOBALS["SBIN_ARPING"]} $ipaddr -c 1 -r 2>&1";
			exec($cmd,$results);
			while (list ($num, $line) = each ($results)){
				if(preg_match("#^([0-9a-zA-Z\:]+)#", $line,$re)){
					if(!$this->IsvalidMAC($re[1])){continue;}
					$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
					return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
				}
			}
		}
		
		
		$results=array();
			
		if(strlen($GLOBALS["SBIN_ARP"])<4){return;}
		if(!isset($GLOBALS["SBIN_PING"])){$GLOBALS["SBIN_PING"]=$this->find_program("ping");}
		if(!isset($GLOBALS["SBIN_NOHUP"])){$GLOBALS["SBIN_NOHUP"]=$this->find_program("nohup");}
		
		$cmd="{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1";
		
		exec($cmd,$results);
		while (list ($num, $line) = each ($results)){
			if(preg_match("#^[0-9\.]+\s+.+?\s+([0-9a-z\:]+)#", $line,$re)){
				if($re[1]=="no"){continue;}
				if(!$this->IsvalidMAC($re[1])){continue;}
				$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
				return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
			}
			
		}
		
		if(!isset($GLOBALS["PINGEDHOSTS"][$ipaddr])){
			shell_exec("{$GLOBALS["SBIN_NOHUP"]} {$GLOBALS["SBIN_PING"]} $ipaddr -c 3 >/dev/null 2>&1 &");
			$GLOBALS["PINGEDHOSTS"][$ipaddr]=true;
		}
			
		
	}
	
	public static function isIPAddress( $ip ) {
		if ( !$ip ) return false;
		if ( is_array( $ip ) ) {
			return;
		}
		// IPv6 IPs with two "::" strings are ambiguous and thus invalid
		return preg_match( '/^' . IP_ADDRESS_STRING . '$/', $ip) && ( substr_count($ip, '::') < 2 );
	}	
	private static function IsvalidMAC($MAC){
		$MAC=str_replace(":", "-", $MAC);
		If(strlen($MAC) > 17) return false;
		If($MAC == "") return false;
		If (!preg_match("#^[0-9A-Z]+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)$#i",$MAC)) return false;
		$Array=explode("-",$MAC);
		If(strlen($Array[0]) != 2) return false;
		If(strlen($Array[1]) != 2) return false;
		If(strlen($Array[2]) != 2) return false;
		If(strlen($Array[3]) != 2) return false;
		If(strlen($Array[4]) != 2) return false;
		If(strlen($Array[5]) != 2) return false;
		return true;
	}	
	
	
	public function AddFSTab($dev,$mount){
		$hd=new hd($dev);
		$hd->AddFSTab($mount);
	}
	
	public function DelFSTab($dev){
		if($dev==null){return null;}
		 $datas=explode("\n",@file_get_contents("/etc/fstab"));
		 while (list ($num, $ligne) = each ($datas) ){
		 	if($ligne==null){continue;}
		 	if(preg_match("#(.+?)\s+(.+?)\s+#",$ligne)){
		 		if(trim(strtolower($dev))==trim(strtolower($re[1]))){
		 			shell_exec($this->find_program("umount")." -f $dev");
		 			unset($datas[$num]);
		 			@rmdir(trim($re[2]));
		 		}
		 	}
		 }
		 
	if(!file_put_contents("/etc/fstab",implode("\n",$datas))){
			writelogs_framework("Unable to save /etc/fstab",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}	 
	
	
	}
	
	public function GetFSTabMountPoint($dev){
	if($dev==null){return null;}
	$dev=$this->MapperDetect($dev);
	$array=array();
	$dev=str_replace("/","\/",$dev);
	$datas=explode("\n",@file_get_contents("/etc/fstab"));
	while (list ($num, $ligne) = each ($datas) ){
		 	if($ligne==null){continue;}
		 	if(preg_match("#$dev\s+(.+?)\s+#",$ligne,$re)){
		 			$array[]=trim($re[1]);
		 			continue;
		 		//	writelogs_framework("#$dev\s+(.+?)\s+# success => $ligne",__FUNCTION__,__FILE__,__LINE__);
		 		}else{
		 		//	writelogs_framework("#$dev\s+(.+?)\s+# failed => $ligne",__FUNCTION__,__FILE__,__LINE__);
		 		}
		 	}
	
	return $array;
	
	}
	
	public function TOTAL_MEMORY_MB(){
		if(isset($GLOBALS["TOTAL_MEMORY_MB"])){return $GLOBALS["TOTAL_MEMORY_MB"];}
		$unix=new unix();
		$free=$unix->find_program("free");
		exec("$free -m 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#Mem:\s+([0-9]+)\s+#",$ligne,$re)){
				$GLOBALS["TOTAL_MEMORY_MB"]=$re[1];
				return $re[1];
			}
		}
		
	}

	public function TOTAL_MEMORY_MB_USED(){
		$unix=new unix();
		$free=$unix->find_program("free");
		exec("$free -m 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#Mem:\s+([0-9]+)\s+([0-9]+)#",$ligne,$re)){
				return $re[2];
			}
		}
		
	}
	public function TOTAL_MEMORY_MB_FREE(){
		$unix=new unix();
		$free=$unix->find_program("free");
		exec("$free -m 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#Mem:\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)#",$ligne,$re)){
				return $re[3];
			}
		}
	
	}
	public function DIRECTORY_FREEM($dir){
		$df=$this->find_program("df");
		exec("$df -B MB $dir",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#^\/.*?[0-9]+MB\s+[0-9]+MB\s+([0-9]+)MB\s+#", $ligne,$re)){return $re[1];}
			
		}
		
	}

	public function DF_SATUS_K($dir){
		$df=$this->find_program("df");
		//1K-blocks      Used Available Use%
		exec("$df -B K $dir 2>&1",$results);
		$pattern="#^\/.*?([0-9]+)K\s+([0-9]+)K\s+([0-9]+)K\s+([0-9\.]+)%#";
		if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."] $df -B K $dir 2>&1 = ".count($results)." rows\n";}
		while (list ($num, $ligne) = each ($results) ){
			
		if(preg_match("#^Filesystem#", $ligne)){continue;}
			
		if(!preg_match("$pattern", $ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo __CLASS__."/".__FUNCTION__."] $ligne -> No match $pattern\n";}
			continue;}
			return array(
						"SIZE"=>$re[1],
						"USED"=>$re[2],
						"AIVA"=>$re[3],
						"POURC"=>$re[4]
				);
		}
	
	}	
	
	
	
	public function process_exists($pid,$filename=null){
		$pid=trim($pid);
		$pid=trim($pid);
		$pid=str_replace("\r", "", $pid);
		$pid=str_replace("\n", "", $pid);		
		$filename=trim($filename);
		$cmdline=null;
		if($pid==null){return false;}
		if(!is_numeric($pid)){return false;}
		if($this->PID_IS_CHROOTED($pid)){
			if($GLOBALS["VERBOSE"]){echo "PID_IS_CHROOTED = TRUE RETURN FALSE\n";}
			return false;
		}
		
		
		if($pid<5){
			if($GLOBALS["VERBOSE"]){echo "$pid < 5 (strange ? aborting)...\n";}
			return false;}
		
		
		
		if(is_file("/proc/$pid/exe")){
			$cmdline=var_export(@file_get_contents("/proc/$pid/cmdline"),true);
			if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) /proc/$pid/status exists cmdline=`$cmdline` Line:".__LINE__."\n";}
			if(preg_match("#'-bash'#",$cmdline)){
				if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) --> $cmdline -> STOPPED Line:".__LINE__."\n";}
				return false;
			}
			
			
			if(is_file("/proc/$pid/status")){$this->proc_status=@file_get_contents("/proc/$pid/status");}
			
			$status=$this->PROCESS_STATUS($pid);
			if($filename<>null){
				if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) filename: `$filename`, PROCESS_STATUS:$status '{$status[0]}' Line:".__LINE__."\n";}
			}
			if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) PROCESS_STATUS:$status '{$status[0]}' Line:".__LINE__."\n";} 
			if($status[0]=="Z"){
				
				if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) -> Z = SOMBIE ::: Line:".__LINE__."\n";}
				return false;}
			if($filename<>null){
				if($cmdline==null){
					if(is_file("/proc/$pid/cmdline")){
						$cmdline=var_export(@file_get_contents("/proc/$pid/cmdline"),true);
					}
				}
				$filename=str_replace(".", "\.", $filename);
				$filename=str_replace("/", "\/", $filename);
				if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) cmdline: $cmdline Line:".__LINE__."\n";}
				if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) preg_match #$filename# in \"$cmdline\" Line:".__LINE__."\n";}
				if(preg_match("#$filename#",$cmdline)){if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) preg_match #$filename# SUCCESS Line:".__LINE__."\n";}return true;}else{if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) preg_match #$filename# FAILED Line:".__LINE__."\n";}return false;}
			}
			
			if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) -->RUNNING Line:".__LINE__."\n";}
			return true;
		}else{
			if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) /proc/$pid/exe no such file Line:".__LINE__."\n";}
		}
		
		if(is_file("/proc/$pid/cmdline")){
			if(is_file("/proc/$pid/status")){$this->proc_status=@file_get_contents("/proc/$pid/status");}
			$status=$this->PROCESS_STATUS($pid);
			
			if($status[0]=="Z"){
				return false;
			}			
			
			if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) filename: $filename Line:".__LINE__."\n";}	
			$cmdline=@file_get_contents("/proc/$pid/cmdline");
			if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) cmdline: `$cmdline` Line:".__LINE__."\n";}
			
			if(preg_match("#-bash$#",$cmdline)){
				if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) --> $cmdline -> STOPPED Line:".__LINE__."\n";}
				return false;				
			}
			$filename=str_replace("/", '\/', $filename);
			if(preg_match("#$filename#i",$cmdline)){
					if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) -->matches $filename -> RUNNING Line:".__LINE__."\n";}
					return true;
			}else{
				if($GLOBALS["VERBOSE"]){echo "unix/process_exists($pid) --> NO matches $filename -> STOPPED Line:".__LINE__."\n";}
				return false;
			}
			return true;
		}
		
		return false;
	}
	
	public function file_time_min($path){
		if(!is_dir($path)){
			if(!is_file($path)){return 100000;}
			}
	 		$last_modified = filemtime($path);
	 		$data1 = $last_modified;
			$data2 = time();
			$difference = ($data2 - $data1); 	 
			return round($difference/60);	 
		}
		
	public function file_time_sec($path){
		if(!is_dir($path)){
			if(!is_file($path)){return 100000;}
			}
	 		$last_modified = filemtime($path);
	 		$data1 = $last_modified;
			$data2 = time();
			$difference = ($data2 - $data1); 	 
			return round($difference);	 
		}
		
		
	public function file_chmoded($path){
		$array=$this->alt_stat2($path);
		if(isset($array["perms"]["octal1"])){
			return $array["perms"]["octal1"];
		}
		}		

		
	public function alt_stat2($file) {
		 
		 clearstatcache();
		 $ss=@stat($file);
		 if(!$ss) return false; //Couldnt stat file
		 
		 $ts=array(
		  0140000=>'ssocket',
		  0120000=>'llink',
		  0100000=>'-file',
		  0060000=>'bblock',
		  0040000=>'ddir',
		  0020000=>'cchar',
		  0010000=>'pfifo'
		 );
		 
		 $p=$ss['mode'];
		 $t=decoct($ss['mode'] & 0170000); // File Encoding Bit
		 
		 $str =(array_key_exists(octdec($t),$ts))?$ts[octdec($t)]{0}:'u';
		 $str.=(($p&0x0100)?'r':'-').(($p&0x0080)?'w':'-');
		 $str.=(($p&0x0040)?(($p&0x0800)?'s':'x'):(($p&0x0800)?'S':'-'));
		 $str.=(($p&0x0020)?'r':'-').(($p&0x0010)?'w':'-');
		 $str.=(($p&0x0008)?(($p&0x0400)?'s':'x'):(($p&0x0400)?'S':'-'));
		 $str.=(($p&0x0004)?'r':'-').(($p&0x0002)?'w':'-');
		 $str.=(($p&0x0001)?(($p&0x0200)?'t':'x'):(($p&0x0200)?'T':'-'));
		 
		 $s=array(
		 'perms'=>array(
		  'umask'=>sprintf("%04o",@umask()),
		  'human'=>$str,
		  'octal1'=>sprintf("%o", ($ss['mode'] & 000777)),
		  'octal2'=>sprintf("0%o", 0777 & $p),
		  'decimal'=>sprintf("%04o", $p),
		  'fileperms'=>@fileperms($file),
		  'mode1'=>$p,
		  'mode2'=>$ss['mode']),
		 
		 'owner'=>array(
		  'fileowner'=>$ss['uid'],
		  'filegroup'=>$ss['gid'],
		  'owner'=>
		  (function_exists('posix_getpwuid'))?
		  @posix_getpwuid($ss['uid']):'',
		  'group'=>
		  (function_exists('posix_getgrgid'))?
		  @posix_getgrgid($ss['gid']):''
		  ),
		 
		 'file'=>array(
		  'filename'=>$file,
		  'realpath'=>(@realpath($file) != $file) ? @realpath($file) : '',
		  'dirname'=>@dirname($file),
		  'basename'=>@basename($file)
		  ),
		
		 'filetype'=>array(
		  'type'=>substr($ts[octdec($t)],1),
		  'type_octal'=>sprintf("%07o", octdec($t)),
		  'is_file'=>@is_file($file),
		  'is_dir'=>@is_dir($file),
		  'is_link'=>@is_link($file),
		  'is_readable'=> @is_readable($file),
		  'is_writable'=> @is_writable($file)
		  ),
		 
		 'device'=>array(
		  'device'=>$ss['dev'], //Device
		  'device_number'=>$ss['rdev'], //Device number, if device.
		  'inode'=>$ss['ino'], //File serial number
		  'link_count'=>$ss['nlink'], //link count
		  'link_to'=>($s['type']=='link') ? @readlink($file) : ''
		  ),
		 
		 'size'=>array(
		  'size'=>$ss['size'], //Size of file, in bytes.
		  'blocks'=>$ss['blocks'], //Number 512-byte blocks allocated
		  'block_size'=> $ss['blksize'] //Optimal block size for I/O.
		  ),
		 
		 'time'=>array(
		  'mtime'=>$ss['mtime'], //Time of last modification
		  'atime'=>$ss['atime'], //Time of last access.
		  'ctime'=>$ss['ctime'], //Time of last status change
		  'accessed'=>@date('Y M D H:i:s',$ss['atime']),
		  'modified'=>@date('Y M D H:i:s',$ss['mtime']),
		  'created'=>@date('Y M D H:i:s',$ss['ctime'])
		  ),
		 );
		 
		 clearstatcache();
		 return $s;
		}		

	PUBLIC function LOCATE_SENDMAIL_PATH(){
		$path=$this->find_program("sendmail");
		if(is_file($path)){return $path;}
		if(is_file("/usr/lib/sendmail")){return "/usr/lib/sendmail";}	
		
	}
	
	public function Ufdbguard_remote_srvc_bool(){
		$datas=unserialize(@file_get_contents("/etc/artica-postfix/settings/Daemons/ufdbguardConfig"));	
		if(!isset($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
		if(!isset($datas["remote_port"])){$datas["remote_port"]=3977;}
		if(!isset($datas["remote_server"])){$datas["remote_server"]=null;}
		if(!is_numeric($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
		if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}
		if($datas["remote_port"]==null){$datas["UseRemoteUfdbguardService"]=0;}
		if($datas["UseRemoteUfdbguardService"]==1){return true;}	
		return false;
	}	
	
	
	public function LOCATE_APACHE_BIN_PATH(){
		if(isset($GLOBALS[__FUNCTION__])){
			if($GLOBALS[__FUNCTION__]<>null){return $GLOBALS[__FUNCTION__];}
		}
		$f=$this->find_program("apache2");
		if(is_file($f)){$GLOBALS[__FUNCTION__]=$f;return $f;}
		$f=$this->find_program("apache");
		if(is_file($f)){$GLOBALS[__FUNCTION__]=$f;return $f;}
		
		$f=$this->find_program("httpd2");
		if(is_file($f)){$GLOBALS[__FUNCTION__]=$f;return $f;}				
		
		$f=$this->find_program("httpd");
		if(is_file($f)){$GLOBALS[__FUNCTION__]=$f;return $f;}	
		
		if(is_file('/usr/local/obm-apache/bin/obm-apache')){return '/usr/local/obm-apache/bin/obm-apache';}
		if(is_file('/usr/local/apache-groupware/bin/apache-groupware')){return '/usr/local/apache-groupware/bin/apache-groupware';} 		
	}
	
	public function  SQUID_INSTALLED(){
		if(is_file($this->LOCATE_SQUID_BIN())){return true;}
		return false;
	}
	
	public function LOCATE_SQUID_BIN(){
		if(isset($GLOBALS["UNIX_LOCATE_SQUID_BIN"])){return $GLOBALS["UNIX_LOCATE_SQUID_BIN"];}
		$GLOBALS["UNIX_LOCATE_SQUID_BIN"]=$this->find_program("squid3");
		if(!is_file($GLOBALS["UNIX_LOCATE_SQUID_BIN"])){$GLOBALS["UNIX_LOCATE_SQUID_BIN"]=$this->find_program("squid");}
		return $GLOBALS["UNIX_LOCATE_SQUID_BIN"];
		
	}
	
	private function APACHE_DEFAULT_PIDLOG(){
		if(isset($GLOBALS["APACHE_DEFAULT_PIDLOG"])){return $GLOBALS["APACHE_DEFAULT_PIDLOG"];}
		$bin=$this->LOCATE_APACHE_BIN_PATH();
		if(strlen($bin)<3){
			if($GLOBALS["VERBOSE"]){echo "\n\nWARNING !!! LOCATE_APACHE_BIN_PATH return a non value !!\n";}
			return;
		}
		
		exec("$bin -V 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match('#DEFAULT_PIDLOG="(.+?)"#',$ligne,$re)){$GLOBALS["APACHE_DEFAULT_PIDLOG"]=$re[1];return $GLOBALS["APACHE_DEFAULT_PIDLOG"];}
		}
		
	}
	
	public function LOCATE_APACHE_CONF_PATH(){
		$bin=$this->LOCATE_APACHE_BIN_PATH();
		if(strlen($bin)<3){
			if($GLOBALS["VERBOSE"]){echo "\n\nWARNING !!! LOCATE_APACHE_BIN_PATH return a non value !!\n";}
			return;
		}
		
		
		exec("$bin -V 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match('#HTTPD_ROOT="(.+?)"#',$ligne,$re)){$r["HTTPD_ROOT"]=$re[1];}
			if(preg_match('#SERVER_CONFIG_FILE="(.+?)"#',$ligne,$re)){$r["SERVER_CONFIG_FILE"]=$re[1];}
		}
		
		chdir("/root");
		if(is_file($r["SERVER_CONFIG_FILE"])){
			$GLOBALS[__FUNCTION__]=$r["SERVER_CONFIG_FILE"];
			return $GLOBALS[__FUNCTION__];
		}
			
		if($GLOBALS["VERBOSE"]){echo "UNIX/LOCATE_APACHE_CONF_PATH -> \"{$r["HTTPD_ROOT"]}/{$r["SERVER_CONFIG_FILE"]}\"\n";}
		
		if(file_exists("{$r["HTTPD_ROOT"]}/{$r["SERVER_CONFIG_FILE"]}")){
			if(isset($r["HTTPD_ROOT"])){$GLOBALS[__FUNCTION__]="{$r["HTTPD_ROOT"]}/{$r["SERVER_CONFIG_FILE"]}";}
			if(isset($GLOBALS[__FUNCTION__])){$GLOBALS[__FUNCTION__]=str_replace("//","/",$GLOBALS[__FUNCTION__]);}
		}
		if($GLOBALS[__FUNCTION__]=="conf/httpd.conf"){$GLOBALS[__FUNCTION__]="/etc/httpd/conf/httpd.conf";}
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		
		
	}
	
	public function LOCATE_APACHE_PID_PATH(){
		if($GLOBALS[__FUNCTION__]<>null){return $GLOBALS[__FUNCTION__];}
		$bin_path=$this->LOCATE_APACHE_BIN_PATH();
		exec("$bin_path -V 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match('#HTTPD_ROOT="(.+?)"#',$ligne,$re)){
				$httpd_root=$re[1];
			}
			
			if(preg_match('#DEFAULT_PIDLOG="(.+?)"#',$ligne,$re)){$DEFAULT_PIDLOG=$re[1];}
		}
		
		if(!is_file($DEFAULT_PIDLOG)){
			if(is_file("$httpd_root/$DEFAULT_PIDLOG")){$DEFAULT_PIDLOG="$httpd_root/$DEFAULT_PIDLOG";}
		}
		$GLOBALS[__FUNCTION__]=$DEFAULT_PIDLOG;
		return $GLOBALS[__FUNCTION__];
		
	}
	
	public function LOCATE_APACHE_VERSION(){
		if($GLOBALS[__FUNCTION__]<>null){return $GLOBALS[__FUNCTION__];}
		$bin_path=$this->LOCATE_APACHE_BIN_PATH();
		if($GLOBALS["VERBOSE"]){echo "LOCATE_APACHE_VERSION : \"$bin_path\"\n";}
		exec("$bin_path -v 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match('#^Server version:.+?([0-9\.]+)#',$ligne,$re)){
				$GLOBALS[__FUNCTION__]=$re[1];
				return $GLOBALS[__FUNCTION__];
			}
			if($GLOBALS["VERBOSE"]){echo "NO MATCH:: \"^Server version:.+?([0-9\.]+)\" -> \"$ligne\"\n";}
		}
		
	}	
	

	public function IMAPD_GET($key){
		if($GLOBALS["IMAPD_GET"]==null){
			$GLOBALS["IMAPD_GET"]=@file_get_contents("/etc/imapd.conf");
		}
		$datas=explode("\n",$GLOBALS["IMAPD_GET"]);
		while (list ($num, $ligne) = each ($datas) ){
			if(preg_match("#^$key:(.+)#",$ligne,$re)){
				return trim($re[1]);
			}
		}
	}
	
public function ServicesCMDArray(){
		$f[]="ldap";
		$f[]="saslauthd";
		$f[]="imap";
		$f[]="kav6";
		$f[]="squid";
		$f[]="dansgardian";
		$f[]="boa";
		$f[]="ftp";
		$f[]="mysql";
		$f[]="apache";
		$f[]="spamd";
		$f[]="clamd";
		$f[]="freshclam";
		$f[]="mgreylist";
		$f[]="daemon";
		$f[]="clammilter";
		$f[]="postfix";
		$f[]="dnsmasq";
		$f[]="stunnel";
		$f[]="postfix";
		$f[]="mailgraph";
		$f[]="mimedefang";
		$f[]="roundcube";
		$f[]="kav4samba";
		$f[]="bind9";
		$f[]="obm";
		$f[]="yorel";
		$f[]="p3scan";
		$f[]="syslogng";
		$f[]="mailarchive";
		$f[]="bogom";
		$f[]="collectd";
		$f[]="mysql";
		$f[]="fetchmail";
		$f[]="mailspy";
		$f[]="amavis";
		$f[]="retranslator";
		$f[]="spfmilter";
		$f[]="dotclear";
		$f[]="jcheckmail";
		$f[]="mailman";
		$f[]="kas3";
		$f[]="dhcp";
		$f[]="cicap";
		$f[]="openvpn";
		$f[]="postfix-logger";
		$f[]="dansguardian-tail";
		$f[]="apache-groupware";
		$f[]="cups";
		$f[]="dstat";
		$f[]="dstat-top-mem";
		$f[]="dstat-top-cpu";
		$f[]="rsync";
		$f[]="policydw";
		$f[]="autofs";
		$f[]="mysql-cluster";
		$f[]="assp";
		$f[]="pdns";
		$f[]="gluster";
		$f[]="gluster-cli";
		$f[]="sysloger";
		$f[]="zabbix";
		$f[]="smartd";
		return $f;	
}


public function monit_array(){
	exec("/usr/share/artica-postfix/bin/artica-install --monit-status",$array);
	if(!is_array($array)){return array();}
	while (list ($num, $ligne) = each ($array) ){
		if(preg_match("#Process\s+'(.+)'#",$ligne,$re)){
			$product=$re[1];
			$ARR[$product]["monit"]=true;
			continue;
		}
		
	if(preg_match("#status\s+(.+)#",$ligne,$re)){
			$ARR[$product]["status"]=$re[1];
			continue;
		}

		
		if(preg_match("#monitoring status\s+(.+)#",$ligne,$re)){
			$ARR[$product]["monitoring status"]=$re[1];
			continue;
		}

		if(preg_match("#uptime\s+(.+)#",$ligne,$re)){
			$ARR[$product]["uptime"]=$re[1];
			continue;
		}		
		if(preg_match("#children\s+(.+)#",$ligne,$re)){
			$ARR[$product]["children"]=$re[1];
			continue;
		}
	
		
		if(preg_match("#memory kilobytes total\s+(.+)#",$ligne,$re)){
			$ARR[$product]["memory kilobytes total"]=$re[1];
			continue;
		}
		
		if(preg_match("#^pid\s+([0-9]+)$#",trim($ligne),$re)){
			$ARR[$product]["pid"]=$re[1];
			continue;
		}		

		if(preg_match("#memory percent total\s+(.+)#",$ligne,$re)){
			$ARR[$product]["memory percent total"]=$re[1];
			continue;
		}
			if(preg_match("#cpu percent total\s+(.+)#",$ligne,$re)){
			$ARR[$product]["cpu percent total"]=$re[1];
			continue;
		}			
	}
	
	return $ARR;
	
}
	
	
public function IsProtectedDirectory($folder,$nopreg=false){
	if($folder==null){return true;}
	if($folder=="/"){return true;}
	$l["/cdrom"]=true;
	$l["/sys"]=true;
	$l["/tmp"]=true;
	$l["/initrd"]=true;
	$l["/dev"]=true;
	$l["/etc"]=true;
	$l["/boot"]=true;
	$l["/var/lib"]=true;
	$l["/sbin"]=true;
	$l["/lib"]=true;
	$l["/bin"]=true;
	$l["/usr/libexec"]=true;
	$l["/usr/sbin"]=true;
	$l["/usr/bin"]=true;	
	$l["/usr/include"]=true;	
	$l["/usr/local"]=true;	
	$l["/usr/src"]=true;		
	$l["/usr/usr"]=true;
	$l["/usr/X11R6"]=true;
	$l["/usr/lib"]=true;
	$l["/usr/lib64"]=true;
	$l["/usr/share/artica-postfix"]=true;
	$l["/usr/src"]=true;	
	$l["/srv"]=true;
	$l["/var/log"]=true;
	$l["/var/db"]=true;
	$l["/var/lib"]=true;
	$l["/var/lock"]=true;
	$l["/var/mail"]=true;
	$l["/var/milter-greylist"]=true;
	$l["/var/spool"]=true;
	$l["/var/opt"]=true;
	$l["/usr/share"]=true;
	$l["/opt"]=true;
	$l["/var/cache"]=true;
	$l["/var/local"]=true;
	$l["/var/run"]=true;
	$l["/var/tmp"]=true;
	$l["/var/webmin"]=true;
	$l["/lost+found"]=true;
	
	if($nopreg){
		$l["/home"]=true;
		if(isset($l[$folder])){return true;}
		return false;
	}
	
	while (list ($num, $ligne) = each ($l) ){
		$p=str_replace("/","\/",$num);
		$p=str_replace(".","\.",$p);
		if(preg_match("#$p#",$folder)){
			writelogs("$folder match $p -> protected directory...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			writelogs_framework("$folder match $p -> protected directory...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return true;}
	}
}

	
	public function LOCATE_HEAD(){
		return $this->find_program("head");
		$this->events("Unable to stat head !");
		
	}

	public function LOCATE_DB_RECOVER(){
		$file=$this->find_program("db4.10_recover");
		if(strlen($file)>3){return $file;}			
		$file=$this->find_program("db4.9_recover");
		if(strlen($file)>3){return $file;}		
		$file=$this->find_program("db4.8_recover");
		if(strlen($file)>3){return $file;}
		$file=$this->find_program("db4.7_recover");
		if(strlen($file)>3){return $file;}		
		$file=$this->find_program("db4.6_recover");
		if(strlen($file)>3){return $file;}	
		$file=$this->find_program("db4.5_recover");
		if(strlen($file)>3){return $file;}						
		$file=$this->find_program("db_recover");
		if(strlen($file)>3){return $file;}

		
	}
	
	public function SLAPD_INITD_PATH(){
	   if(is_file('/etc/init.d/ldap')){return '/etc/init.d/ldap';}
	   if(is_file('/etc/init.d/slapd')){return '/etc/init.d/slapd';}
	   if(is_file('/usr/local/etc/rc.d/slapd')){return '/usr/local/etc/rc.d/slapd';}
	   return "/etc/init.d/slapd";
	}	
	
	public function LOCATE_DB_ARCHIVE(){
		$file=$this->find_program("db4.10_archive");
		if(strlen($file)>3){return $file;}			
		$file=$this->find_program("db4.9_archive");
		if(strlen($file)>3){return $file;}		
		$file=$this->find_program("db4.8_archive");
		if(strlen($file)>3){return $file;}
		$file=$this->find_program("db4.7_archive");
		if(strlen($file)>3){return $file;}		
		$file=$this->find_program("db4.6_archive");
		if(strlen($file)>3){return $file;}	
		$file=$this->find_program("db4.5_archive");
		if(strlen($file)>3){return $file;}						
		$file=$this->find_program("db_archive");
		if(strlen($file)>3){return $file;}

		
	}	

	public function LOCATE_DB_STAT(){
		$file=$this->find_program("db4.10_stat");
		if(strlen($file)>3){return $file;}			
		$file=$this->find_program("db4.9_stat");
		if(strlen($file)>3){return $file;}				
		$file=$this->find_program("db4.8_stat");
		if(strlen($file)>3){return $file;}
		$file=$this->find_program("db4.7_stat");
		if(strlen($file)>3){return $file;}		
		$file=$this->find_program("db4.6_stat");
		if(strlen($file)>3){return $file;}
		$file=$this->find_program("db4.5_stat");
		if(strlen($file)>3){return $file;}						
		$file=$this->find_program("db_stat");
		if(strlen($file)>3){return $file;}

		
	}		

	
	
	public function LOCATE_SYSLOG_PATH(){
		if(is_file("/etc/artica-postfix/settings/Daemons/syslog_path")){
			$datas=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/syslog_path"));
			if(!is_file($datas)){$datas="/var/log/syslog";}
			
		}
		return $datas;
		
	}
	
	public function LOCATE_DU(){
		return $this->find_program("du");
		$this->events("Unable to stat du !");
		
	}

	public function FILE_TEMP(){
		if(function_exists("sys_get_temp_dir")){
			return tempnam(sys_get_temp_dir(), 'artica-php');
		}
		return "/tmp/artica-php-".md5(date("Y-m-d H:i:s"));
		
	}
	
	public function LINUX_CODE_NAME(){
		$f=@file_get_contents("/etc/artica-postfix/settings/Daemons/LinuxDistributionCodeName");
		if(trim($f)<>null){return trim($f);}
		$distriname=$this->LINUX_DISTRIBUTION();
		if(preg_match("#Ubuntu#i", $distriname)){return "UBUNTU";}
		if(preg_match("#debian#i", $distriname)){return "DEBIAN";}
		if(preg_match("#suse#i", $distriname)){return "SUSE";}
		if(preg_match("#Fedora#i", $distriname)){return "FEDORA";}
		if(preg_match("#CentOS#i", $distriname)){return "CENTOS";}
		
		
	}
	
	
	
	public function LINUX_VERS(){
		$distriname=$this->LINUX_DISTRIBUTION();
		if(preg_match("#([0-9]+)\.([0-9]+)#", $distriname,$re)){
			return array(intval($re[1]),intval($re[2]));
		}
		
		return array(0,0);
		
	}
	
	
	public function LINUX_DISTRIBUTION(){
		
		$f=@file_get_contents("/etc/artica-postfix/settings/Daemons/LinuxDistributionFullName");
		if(trim($f)<>null){return $f;}
		
		if(is_file("/etc/SuSE-release")){
			$content=@file_get_contents("/etc/SuSE-release");
			if(preg_match("#([0-9]+)\.([0-9]+)#is", $content)){
				return "openSUSE {$re[1]}.{$re[2]}";
			}
		}
	
		if(is_file("/etc/lsb-release")){
			if(!is_file("/etc/redhat-release")){
				$content=explode("\n",@file_get_contents("/etc/lsb-release"));
				while (list ($num, $val) = each ($content) ){
					if(preg_match("#DISTRIB_ID=(.+)#", $val,$re)){$distri_provider=$re[1];}
					if(preg_match("#DISTRIB_RELEASE=([0-9\.]+)#", $val,$re)){$distri_ver=$re[1];}
					if(preg_match("#DISTRIB_CODENAME=(.+)#", $val,$re)){$distri_name=$re[1];}
					
				}
				return "$distri_provider $distri_ver $distri_name";
				
				
			}
		}
	  
		if(is_file("/etc/debian_version")){
			$content=explode("\n",@file_get_contents("/etc/debian_version"));
			while (list ($num, $val) = each ($content) ){
				if(preg_match("#([0-9\.]+)#", $val,$re)){return "Debian {$re[1]} Gnu-linux";}
				if(preg_match("#squeeze\/sid#", $val)){return "Debian 6.0 Gnu-linux";}
			}
		}
	
		if(is_file("/etc/redhat-release")){
			$content=explode("\n",@file_get_contents("/etc/redhat-release"));
			while (list ($num, $val) = each ($content) ){
				if(preg_match("#Fedora Core release\s+([0-9]+)#",$val,$re)){return "Fedora Core release {$re[1]}";}
				if(preg_match("#Fedora release\s+([0-9]+)#",$val,$re)){return "Fedora release {$re[1]}";}
				if(preg_match("#Scientific Linux release\s+([0-9\.]+)#",$val,$re)){return "Scientific Linux release {$re[1]}";}
				if(preg_match("#Mandriva Linux release\s+([0-9\.]+)#",$val,$re)){return "Mandriva Linux release {$re[1]}";}
				if(preg_match("#CentOS\s+.*?release\s+([0-9\.]+)#",$val,$re)){return "CentOS release {$re[1]}";}
				
			}
			
		}
	}
//##############################################################################	
	
	
	public function KERNEL_VERSION_BIN(){
		$cmd=$this->find_program("uname")." -r";
		exec($cmd,$datas);
	if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)#",$datas[0],$re)){
			if(strlen($re[2])==1){$re[2]='0'.$re[2];}
			if(strlen($re[3])==1){$re[3]='0'.$re[3];}
			$kernel=$re[1].$re[2].$re[3];
			
		}
		
		return $kernel;
	}
	
	
	public function  miltergreylist_addacl($complete_string){
	   $ConfigurationFile=$this->MILTER_GREYLIST_CONF_PATH();
	   if(!is_file($ConfigurationFile)){return null;}
		$datas=@file_get_contents($ConfigurationFile);
		$tbl=explode("\n",$datas);
		while (list ($num, $ligne) = each ($tbl) ){
	   		if(strtolower(trim($ligne))==strtolower(trim($complete_string))){
	   			return ;
	   		}
		}
	  
	  $tbl[]=$complete_string;
	  @file_put_contents($ConfigurationFile,implode("\n",$tbl)."\n");
	  
	}
	
	public function miltergreylist_cleanconf(){
		$ConfigurationFile=$this->MILTER_GREYLIST_CONF_PATH();
		$datas=@file_get_contents($ConfigurationFile);
		$tbl=explode("\n",$datas);
		while (list ($num, $ligne) = each ($tbl) ){
			if(trim($ligne)==null){unset($tbl[$num]);}
			if(substr($ligne,0,1)=="#"){unset($tbl[$num]);}
		}
		
		$tbl[]="\n";
		file_put_contents($ConfigurationFile,implode("\n",$tbl));
		
	 	$this->miltergreylist_check();
	}
	
	private function miltergreylist_check(){
		$ConfigurationFile=$this->MILTER_GREYLIST_CONF_PATH();
		$MILTER_GREYLIST_BIN_PATH=$this->MILTER_GREYLIST_BIN_PATH();
		$tmpf=$this->FILE_TEMP();
		for($i=0;$i<50;$i++){
			exec("$MILTER_GREYLIST_BIN_PATH -f $ConfigurationFile -c >$tmpf 2>&1");
			$dd=@file_get_contents($tmpf);
			
			@unlink($tmpf);
			if(preg_match("#config error at line\s+([0-9]+)#is",$dd,$ri)){
				if(!$this->miltergreylist_removeline($ri[1])){break;}
				continue;
			}
			
			else{break;}
		}
	}
	
	private function miltergreylist_removeline($line_number){
		 $ConfigurationFile=$this->MILTER_GREYLIST_CONF_PATH();
		 if(!is_file($ConfigurationFile)){return null;}
		 $datas=@file_get_contents($ConfigurationFile);
		 $tbl=explode("\n",$datas);
		 if(count($tbl)==$line_number){
		 	$tbl[]="";
		 	file_put_contents($ConfigurationFile,implode("\n",$tbl));
		 	return false;
		 }
		 echo "Starting......: milter-greylist Config...: error, removing  \"{$tbl[$line_number]}\" line $line_number";
		 unset($tbl[$line_number]);
		 file_put_contents($ConfigurationFile,implode("\n",$tbl));
		 return true;
	}
	
	
	
	public function miltergreylist_buildacl($pattern){
	 	if(preg_match("#(.+?)@(.+)#",$pattern,$re)){
	 		$part=trim($re[1]);
	 		$domain=trim($re[2]);
	 	}
	   	 $part=str_replace('*','',$part);
	 	
	 	 if(strlen($part)==0){ 
	           $suffix='acl whitelist domain';
	           $domain=str_replace("","@",$domain);
	           if(substr($domain, 0,1)=="."){$domain=substr($domain, 1,strlen($domain));}
	           $pattern=trim($domain);
	           if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $domain)){
	           		$suffix='acl whitelist addr';
	           }
	           
	           
	 	 }else{
	 	 	$suffix="acl whitelist from";
	 	 }
	   
		if(strpos($pattern,'*')>0){
	        $pattern=str_replace('.','\.',$pattern);
	        $pattern=str_replace('*','.*',$pattern);
	       	$pattern="/$pattern/";
		}
	     
		return "$suffix $pattern";
	}
	
 
	public function MILTER_GREYLIST_CONF_PATH(){
		if(is_file('/etc/milter-greylist/greylist.conf')){return '/etc/milter-greylist/greylist.conf';}
		if(is_file('/etc/mail/greylist.conf')){return '/etc/mail/greylist.conf';}
		if(is_file('/opt/artica/etc/milter-greylist/greylist.conf')){return '/opt/artica/etc/milter-greylist/greylist.conf';}
		return '/etc/mail/greylist.conf';
	}
	
	public function MILTER_GREYLIST_BIN_PATH(){
		if(is_file('/usr/local/bin/milter-greylist')){return '/usr/local/bin/milter-greylist';}
		if(is_file('/usr/sbin/milter-greylist')){return '/usr/sbin/milter-greylist';}
		if(is_file('/usr/bin/milter-greylist')){return '/usr/bin/milter-greylist';}	
		}
	
	public function DIRPART_INFO($directory){
		$df=$this->find_program("df");
		exec("$df $directory 2>&1",$results);
		while (list ($index, $line) = each ($results) ){
			if(preg_match("#(.*?)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)%\s+(.*)#",$line,$re)){
				$array["DEV"]=$re[1];
				$array["TOT"]=$re[2]*1024;
				$array["USED"]=$re[3]*1024;
				$array["AIV"]=$re[4]*1024;
				$array["POURC"]=$re[5];
				$array["MOUNT"]=$re[6];
				return $array;
			}
		}
	}
	
	public function DIRSIZE_KO($directory){
		if(is_link($directory)){$dir=readlink($directory);}
		$res=$this->dskspace_bytes($directory);
		if($res==0){return 0;}
		return round($res/1024);
	}

	public function DIRSIZE_BYTES($directory){
		if(is_link($directory)){$dir=readlink($directory);}
		return $this->dskspace_bytes($directory);
	}	
	
	public function LOCATE_PHP5_BIN(){
	  if(isset($GLOBALS["LOCATE_PHP5_BIN"])){if(strlen($GLOBALS["LOCATE_PHP5_BIN"])>3){return $GLOBALS["LOCATE_PHP5_BIN"];}}
	  
	  if(is_file('/opt/articatech/bin/php')){$GLOBALS["LOCATE_PHP5_BIN"]='/opt/articatech/bin/php';return '/opt/articatech/bin/php';}
	  if(is_file('/usr/bin/php5')){$GLOBALS["LOCATE_PHP5_BIN"]='/usr/bin/php5';return '/usr/bin/php5';}
	  if(is_file('/usr/bin/php')){$GLOBALS["LOCATE_PHP5_BIN"]='/usr/bin/php';return '/usr/bin/php';}
	  if(is_file('/usr/local/apache-groupware/php5/bin/php')){$GLOBALS["LOCATE_PHP5_BIN"]='/usr/bin/php5';return '/usr/local/apache-groupware/php5/bin/php';}
	  
	  $php5=$this->find_program("php");
	  if(is_file($php5)){$GLOBALS["LOCATE_PHP5_BIN"]=$php5;return $php5;}
	  $php5=$this->find_program("php5");
	  if(is_file($php5)){$GLOBALS["LOCATE_PHP5_BIN"]=$php5;return $php5;}	 	  
	  $this->events("Unable to stat php5 !");
	  
	}
	public function LOCATE_CUPS_ACCEPT(){
 		if(is_file('/usr/sbin/cupsaccept')){return '/usr/sbin/cupsaccept';}
    	if(is_file('/usr/sbin/accept')){return '/usr/sbin/accept';}
	}
 //############################################################################# 

public function getmodpathfromconf(){
	if(is_dir("/etc/apache2/mods-enabled")){return "/etc/apache2";}
	if(is_dir("/etc/httpd/mods-enabled")){return "/etc/httpd";}
	if(is_dir("/usr/local/etc/httpd/mods-enabled")){return "/usr/local/etc/httpd";}
	if(is_dir("/usr/local/etc/apache2/mods-enabled")){return "/usr/local/etc/apache2";}
	$httpdconf=$this->LOCATE_APACHE_CONF_PATH();
	if($httpdconf=="/etc/httpd/conf/httpd.conf"){return "/etc/httpd";}
	if($httpdconf=="/usr/local/etc/httpd/conf/httpd.conf"){return "/usr/local/etc/httpd";}
	return dirname($httpdconf);
}	


public function APACHE_DIR_SITES_ENABLED(){
	if(is_dir("/etc/apache2/sites-enabled")){return '/etc/apache2/sites-enabled';}
	$httpdconf=$this->LOCATE_APACHE_CONF_PATH();
	$DAEMON_PATH=$this->getmodpathfromconf();
	return $DAEMON_PATH."/sites-enabled";
	$mods_enabled=$DAEMON_PATH."/mods-enabled";	
	
}

public function LIGHTTPD_PHP5_CGI_BIN_PATH(){
	if(isset($GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"])){return $GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"];}
	$GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"]=$this->find_program("php-fcgi");
	if(!is_file($GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"])){$GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"]=$this->find_program("php-cgi");}
	return $GLOBALS["LIGHTTPD_PHP5_CGI_BIN_PATH"];
	
}

public function GetUniqueID(){
	$uuid=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SYSTEMID"));
	if($uuid==null){
		$uuid=$this->gen_uuid();
		@file_put_contents("/etc/artica-postfix/settings/Daemons/SYSTEMID",$uuid);
	}
	
	return $uuid;	
	
}

private function gen_uuid(){
    return sprintf(
        '%08x-%04x-%04x-%02x%02x-%012x',
        mt_rand(),
        mt_rand(0, 65535),
        bindec(substr_replace(
            sprintf('%016b', mt_rand(0, 65535)), '0100', 11, 4)
        ),
        bindec(substr_replace(sprintf('%08b', mt_rand(0, 255)), '01', 5, 2)),
        mt_rand(0, 255),
        mt_rand()
    );
}

public function ifconfig_all_ips(){
	$ri=array();
	$cmd=$this->find_program("ifconfig")." -a 2>&1";
	exec($cmd,$results);
	while (list ($index, $line) = each ($results) ){
		if($GLOBALS["VERBOSE"]){echo "Found \"$line\"\n";}
		if(preg_match("#inet (adr|addr|Adresse):([0-9\.]+)#",$line,$re)){
			if(trim($re[2])==null){
				if($GLOBALS["VERBOSE"]){echo "ERROR \"$line\" re[2] = NULL\n";}
				continue;
			}
			if($GLOBALS["VERBOSE"]){echo "SUCCESS \"[{$re[2]}]\"\n";}
			$ri[$re[2]]=$re[2];
			continue;
		}
		if($GLOBALS["VERBOSE"]){echo "No match 1 \"$line\"\n";}
		if(preg_match("#inet end\.:\s+([0-9\.]+)#", $line,$re)){
			$ri[$re[1]]=$re[1];
			continue;
		}
		
		
	}
	
	if(count($ri)==0){
		if($GLOBALS["VERBOSE"]){echo "count = 0 -> Next...\n";}
		$ifconfigs=$this->ifconfig_interfaces_list();
		unset($ifconfigs["lo"]);
		while (list ($index, $ipaddr) = each ($ifconfigs) ){
			$ri[$ipaddr]=$ipaddr;
		}
	}
	
	return $ri;
	
	
}



public function ifconfig_interfaces_list(){
	$ri=array();
	$cmd=$this->find_program("ip")." -o addr sho 2>&1";
	exec($cmd,$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#^[0-9]+:\s+(.*?)\s+inet\s+([0-9\.]+)\/#", $line,$re)){
			if(preg_match("#(.+?)@(.+)#", $re[1],$rz)){$re[1]=$rz[1];}
			$ri[$re[1]]=$re[2];
			continue;
		}
		
	}	
	

	
	return $ri;
}

public function LOCATE_APACHE_CTL(){
	$apachectl=$this->find_program("apachectl");
	if(is_file($apachectl)){return $apachectl;}
	$apachectl=$this->find_program("apache2ctl");
	if(!is_file($apachectl)){$apachectl=$this->find_program("apachectl");}
	if(is_file($apachectl)){return $apachectl;}	
}

public function SAMBA_GetNetAdsInfos(){
	$unix=new unix();
	$net=$this->LOCATE_NET_BIN_PATH();
	if(!is_file($net)){return array();}
	exec("$net ads info 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#^(.+?):(.+)#",trim($line),$re)){
			$array[trim($re[1])]=trim($re[2]);
		}
	}
	
	if(!isset($array["KDC server"])){$array["KDC server"]=null;}
	return $array;
}

public function LOCATE_STUNNEL_BIN(){
return $this->LOCATE_STUNNEL();	
	
}


	public function PATH_LDAP_DIRECTORY_DATA(){
		$file=file_get_contents("/etc/ldap/slapd.conf");
		$datas=explode("\n",$file);
		if(!is_array($datas)){
			$this->events("Unable to stat /etc/ldap/slpad.conf !");
			return null;}
		while (list ($num, $ligne) = each ($datas) ){
			if(preg_match("#^directory\s+(.+)#",$ligne,$re)){
				return trim($re[1]);
			}
		}
		$this->events("Unable to stat directory in /etc/ldap/slpad.conf ! in ".count($datas)." rows");
	}
	
	public function FILE_IS_LINK($path){
		$path=rtrim($path, '/');
		$stat=$this->find_program("stat");
		exec("$stat $path 2>&1",$datas);
		while (list ($num, $ligne) = each ($datas) ){$ligne=trim($ligne);if($ligne==null){continue;}if(preg_match("#^File:.+?\->\s+.*?#", trim($ligne))){return true;}}
		return false;
	}
	
	public function FILE_REALPATH($path){
		$orgpath=$path;
		$path=rtrim($path, '/');
		$stat=$this->find_program("stat");
		exec("$stat $path 2>&1",$datas);
			while (list ($num, $ligne) = each ($datas) ){
				$ligne=trim($ligne);
				if($ligne==null){continue;}
				if(preg_match("#^File:.+?\->\s+.*?#", trim($ligne))){
					$f=explode("->", $ligne);
					$realpth=trim($f[1]);
					if(substr($realpth, 0,1)=="`"){$realpth=substr($realpth, 1,strlen($realpth));}
					if(substr($realpth, strlen($realpth)-1,1)=="'"){$realpth=substr($realpth, 0,strlen($realpth)-1);}
					return trim($realpth);
				}
			}
		return trim($orgpath);
	}
	
	function LDAP_SCHEMA_PATH(){
	  	if(is_dir('/etc/ldap/schema')){return '/etc/ldap/schema';}
	    if(is_dir('/etc/openldap/schema')){return '//etc/openldap/schema';}
	    if(is_dir('/opt/artica/etc/openldap/schema')){return '//opt/artica/etc/openldap/schema';}
	}	
	
	
	public function DHCPD_BIN_PATH(){
		$dhcpd=$this->find_program("dhcpd");
		if(is_file($dhcpd)){return $dhcpd;}
		$dhcpd=$this->find_program("dhcpd3");
		if(is_file($dhcpd)){return $dhcpd;}
	}
	
	
	public function find_program($strProgram) {
	  $key=md5($strProgram);
	  if(!isset($_SESSION["find_program"])){$_SESSION["find_program"]=array();}
	  if(isset($GLOBALS["find_program"][$key])){return $GLOBALS["find_program"][$key];}
	  if(function_exists("apc_store")){$value=trim(apc_fetch("LOCATE_PROGRAM_$key"));if($value<>null){if(basename($value)==$strProgram){$GLOBALS["find_program"][$key]=trim($value);return trim($value);}}}
	  if(!isset($GLOBALS["find_program"]["$key"])){$GLOBALS["find_program"]["$key"]=null;}
	  if(isset($_SESSION["find_program"]["$key"])){if($_SESSION["find_program"]["$key"]<>null){return $_SESSION["find_program"]["$key"];}}
	  if(isset($GLOBALS["find_program"]["$key"])){if($GLOBALS["find_program"]["$key"]<>null){return $GLOBALS["find_program"]["$key"];}	}
	  $value=trim($this->internal_find_program($strProgram));
	  if(function_exists("apc_store")){apc_store("LOCATE_PROGRAM_$key",$value);$GLOBALS["find_program"]["$key"]=$value;return $value;}	
	  $_SESSION["find_program"]["$key"]=$value;
	  $GLOBALS["find_program"]["$key"]=$value;
      return $value;
		
	}
	
	public function LOCATE_mysqld_bin(){
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		$bin=$this->find_program('mysqld');
		if(is_file($bin)){$GLOBALS[__FUNCTION__]=$bin;return $bin;}
		if(is_file("/opt/artica/mysql/libexec/mysqld")){$GLOBALS[__FUNCTION__]="/opt/artica/mysql/libexec/mysqld";return "/opt/artica/mysql/libexec/mysqld";}
		if(is_file("/usr/libexec/mysqld")){$GLOBALS[__FUNCTION__]="/usr/libexec/mysqld";return "/usr/libexec/mysqld";}

	}
//##############################################################################
	
	
	public function BLKID_INFOS($dev){
		$blkid=$this->find_program("blkid");
		$df=$this->find_program("df");
		if(!is_file($blkid)){return array("UUID"=>"blkid No such binary");}
		$cmd="$blkid \"$dev\" 2>&1";
		exec($cmd,$results);
		$imploded=trim(@implode("", $results));
		$f=explode(" ",$imploded );
		while (list ($num, $ligne) = each ($f) ){if(preg_match('#(.+?)="(.+?)"#', $ligne,$re)){$array[strtoupper($re[1])]=$re[2];}}
		$results=array();
		$devRgx=str_replace("/", "\/", $dev);
		$devRgx=str_replace(".", "\.", $devRgx);
		exec("$df -h \"$dev\" 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#$devRgx\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%\s+(.+)#", $ligne,$re)){
				$array["SIZE"]=$re[1];
				$array["USED"]=$re[2];
				$array["AIVA"]=$re[3];
				$array["POURC"]=$re[4];
				$array["MOUNTED"]=$re[5];
			}
		}
		return $array;		
		
	}
	
	public function SYSTEMS_ALL_PARTITIONS(){
		$df=$this->find_program("df");
		exec("$df -h 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#(.+?)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%\s+(.+)#", $ligne,$re)){
				$array[$re[1]]["SIZE"]=$re[2];
				$array[$re[1]]["USED"]=$re[3];
				$array[$re[1]]["AIVA"]=$re[4];
				$array[$re[1]]["POURC"]=$re[5];
				$array[$re[1]]["MOUNTED"]=$re[6];
				$array[$re[1]]["VALUES"]=$this->tune2fs_values($re[1]);
				
			}
		}
		return $array;
	
	}	
	
	public function SYSTEM_GET_MEMORY_MB(){
		$unix=new unix();
		$free=$unix->find_program("free");
		exec("$free -m 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){if(preg_match("#Mem:\s+([0-9]+)#", $ligne,$re)){return $re[1];}}
		return 0;
	}

	function SYSTEM_GET_SWAP_MB(){
		$unix=new unix();
		$free=$unix->find_program("free");
		exec("$free -m 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){if(preg_match("#Swap:\s+([0-9]+)#", $ligne,$re)){return $re[1];}}
		return 0;
	}	
	
	function system_mysql_events($subject,$text,$file,$context,$line=null){
		$file=basename($file);
		if(!is_numeric($line)){$line=0;}
		if($GLOBALS["VERBOSE"]){echo "$subject $text script:$file in line $line\n";}
		if(!class_exists("mysql")){include_once("/usr/share/artica-postfix/ressources/class.mysql.inc");}
		if(!class_exists("mysql")){if($GLOBALS["VERBOSE"]){echo "mysql no such class!\n";}return;}
		if(!isset($GLOBALS["MYHOSTNAME"])){$GLOBALS["MYHOSTNAME"]=$this->hostname_g();}
		$file=basename($file);
		$subject=addslashes($subject);
		$text=addslashes($text);
		$sql="INSERT IGNORE INTO events (zDate,hostname,process,text,context,sended,content)
		VALUES(NOW(),'{$GLOBALS["MYHOSTNAME"]}','$file','$subject','$context','1','$text')";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){if(function_exists("writelogs")){writelogs("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}}
	}	
	
	
	public function UFDBGUARDD_MINOR(){
		if(isset($GLOBALS["UFDBGUARDD_MINOR"])){return $GLOBALS["UFDBGUARDD_MINOR"];}
		$this->UFDBGUARDD_MAJOR();
		return $GLOBALS["UFDBGUARDD_MINOR"];
	}
	
	public function UFDBGUARDD_MAJOR(){
		if(!isset($GLOBALS["UFDBGUARDD_MAJOR"])){$GLOBALS["UFDBGUARDD_MAJOR"]=null;}
		if(is_numeric($GLOBALS["UFDBGUARDD_MAJOR"])){if($GLOBALS["UFDBGUARDD_MAJOR"]>0){return $GLOBALS["UFDBGUARDD_MAJOR"];}}
		$ufdbguarddd=$this->find_program("ufdbguardd"); 
		exec("$ufdbguarddd -v 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){if(preg_match("#^ufdbguardd:\s+([0-9]+)\.([0-9]+)#",$ligne,$re)){$major=$re[1];$minor=$re[2];break;}}
		$GLOBALS["UFDBGUARDD_MAJOR"]=$major;$GLOBALS["UFDBGUARDD_MINOR"]=$minor;	
		return $GLOBALS["UFDBGUARDD_MAJOR"];	
	}
	
	private function internal_find_program($strProgram){
	  global $addpaths;	
	  $arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin',
	  		 '/usr/local/sbin','/usr/kerberos/bin','/usr/libexec');
	  if (function_exists("is_executable")) {
	    foreach($arrPath as $strPath) {$strProgrammpath = $strPath . "/" . $strProgram;if (is_executable($strProgrammpath)) {return $strProgrammpath;}}
	  	} else {
	   	return strpos($strProgram, '.exe');
	  }
	}
	
	public function POSTCONF_SET($key,$value){
	$value=str_replace('$','\$',$value);
	if($GLOBALS["MEM_POSTCONF"]<>null){$postconf=$GLOBALS["MEM_POSTCONF"];}else{$postconf=$this->find_program("postconf");$GLOBALS["MEM_POSTCONF"]=$postconf;}
		shell_exec("$postconf -e \"$key = $value\" >/dev/null 2>&1");
	}
	
	
	public function POSTCONF_GET($value){
		if(!isset($GLOBALS["MEM_POSTCONF"])){$GLOBALS["MEM_POSTCONF"]=null;}
		if($GLOBALS["MEM_POSTCONF"]<>null){$postconf=$GLOBALS["MEM_POSTCONF"];}else{$postconf=$this->find_program("postconf");$GLOBALS["MEM_POSTCONF"]=$postconf;}
		if($GLOBALS["DEBUG"]){echo "POSTCONF_GET:: $postconf -h $value\n";}
		exec("$postconf -h $value 2>&1",$results);
		$v=array();
		while (list ($num, $line) = each ($results)){if(preg_match("#warning:#", $line)){continue;}$v[]=$line;}		
		return trim(implode("",$v));
		
	}
	
	public function POSTCONF_MULTI_GET($instance,$value){
		if($GLOBALS["MEM_postmulti"]<>null){$postmulti=$GLOBALS["MEM_postmulti"];}else{$postmulti=$this->find_program("postmulti");$GLOBALS["MEM_postmulti"]=$postmulti;}	
		if($GLOBALS["DEBUG"]){echo "POSTCONF_MULTI_GET:: postmulti=$postmulti\n";}
		$cmd="$postmulti -i postfix-$instance -x postconf -h $value 2>&1";
		if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
		exec($cmd,$results);
		$v=array();
		while (list ($num, $line) = each ($results)){
			if(preg_match("#warning:#", $line)){continue;}
			$v[]=$line;
		}
		$vFinal=@implode("", $v);
		if($GLOBALS["DEBUG"]){writelogs_framework("$cmd -> $vFinal",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		return $vFinal;
	}

	public function POSTCONF_MULTI_SET($instance,$key,$value){
		$value=str_replace('$','\$',$value);
		if($GLOBALS["MEM_postmulti"]<>null){$postmulti=$GLOBALS["MEM_postmulti"];}else{$postmulti=$this->find_program("postmulti");$GLOBALS["MEM_postmulti"]=$postmulti;}
		$cmd="$postmulti -i postfix-$instance -x postconf -e \"$key=$value\" >/dev/null 2>&1";
		if($GLOBALS["DEBUG"]){echo "POSTCONF_MULTI_SET:: $cmd\n";}
		shell_exec("$postmulti -i postfix-$instance -x postconf -e \"$key=$value\" >/dev/null 2>&1");
	}		
	
	public function POSTFIX_MULTI_PID($instance){
		if($GLOBALS["DEBUG"]){echo "POSTFIX_MULTI_PID:: $instance\n";}
		$path=$this->POSTCONF_MULTI_GET($instance,"queue_directory")."/pid/master.pid";
		$data=trim(@file_get_contents($path));
		return trim($data);
	}	
	
	public function POSTFIX_PID(){
		$path=$this->POSTCONF_GET("queue_directory")."/pid/master.pid";
		$GLOBALS["DEBUG_LOGS"][]="Pid path : $path";
		if($GLOBALS["DEBUG"]){echo "POSTFIX_PID:: $path\n";}
		$data=trim(@file_get_contents($path));
		$data=trim($data);
		$GLOBALS["DEBUG_LOGS"][]="Pid content : $data";
		if(preg_match("#([0-9]+)#",$data,$re)){$data=$re[1];}
		if($GLOBALS["DEBUG"]){echo "POSTFIX_PID:: $data\n";}
		return $data;
	}
	
	public function POSTFIX_MASTER_BIN_PATH(){
		$path=trim($this->POSTCONF_GET("daemon_directory")."/master");
		$GLOBALS["DEBUG_LOGS"][]="Bin path : $path";
		if($GLOBALS["DEBUG"]){echo "POSTFIX_PID:: $path\n";}
		return $path;
	}	
	
	
	
	
	
	public function POSTFIX_VERSION(){return $this->POSTCONF_GET("mail_version");}
	
	public function LOCATE_POSTFIX_DAEMON_DIRECTORY(){
		if(isset($GLOBALS[__FUNCTION__])){
			if(strlen($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		}
		$locate1=$this->LOCATE_POSTFIX_DAEMON_DIRECTORY_1();
		$locate2=$this->LOCATE_POSTFIX_DAEMON_DIRECTORY_2();
		$locate3=$this->LOCATE_POSTFIX_DAEMON_DIRECTORY_3();
		if($locate2<>null){if($locate1<>$locate2){$locate1=$locate2;}}
		if($locate3<>null){if($locate1<>$locate3){$locate1=$locate3;}}
		$GLOBALS[__FUNCTION__]=$locate1;
		return $locate1;
	}
	
	
	private function LOCATE_POSTFIX_DAEMON_DIRECTORY_1(){
		if(is_file('/usr/lib/postfix/master')){return ('/usr/lib/postfix');}
		if(is_file('/usr/lib64/postfix/master')){return ('/usr/lib64/postfix');}
		if(is_file('/usr/libexec/postfix/master')){return ('/usr/libexec/postfix');}
		if(is_file('/usr/local/libexec/postfix/master')){return ('/usr/local/libexec/postfix');}
		
	}
	
	private function LOCATE_POSTFIX_DAEMON_DIRECTORY_2(){
		if(is_file('/usr/lib/postfix/postscreen')){return ('/usr/lib/postfix');}
		if(is_file('/usr/libexec/postfix/postscreen')){return ('/usr/libexec/postfix');}
		if(is_file('/usr/local/libexec/postfix/postscreen')){return ('/usr/local/libexec/postfix');}
		
	}	
	
	private function LOCATE_POSTFIX_DAEMON_DIRECTORY_3(){
		if(is_file('/usr/lib/postfix/postmulti-script')){return ('/usr/lib/postfix');}
		if(is_file('/usr/libexec/postfix/postmulti-script')){return ('/usr/libexec/postfix');}
		if(is_file('/usr/local/libexec/postfix/postmulti-script')){return ('/usr/local/libexec/postfix');}
	}

	public function LOCATE_POSTFIX_POSTSCREEN(){
		$f=$this->LOCATE_POSTFIX_DAEMON_DIRECTORY_2();
		if(is_file($f)){return $f;}
		if(is_file('/usr/lib/postfix/postscreen')){return ('/usr/lib/postfix/postscreen');}
		if(is_file('/usr/lib64/postfix/postscreen')){return ('/usr/lib64/postfix/postscreen');}
		if(is_file('/usr/libexec/postfix/postscreen')){return ('/usr/libexec/postscreen');}
		if(is_file('/usr/local/libexec/postfix/postscreen')){return ('/usr/local/libexec/postfix/postscreen');}
	}	
	
	
	
	public function IPTABLES_DELETE_REGEX_ENTRIES($regexPat){
		if(trim($regexPat)==null){return;}
		$iptables_save=$this->find_program("iptables-save");
		$iptables_restore=$this->find_program("iptables-restore");
		$cmd="$iptables_save > /etc/artica-postfix/iptables.conf";
		shell_exec($cmd);
		$data=file_get_contents("/etc/artica-postfix/iptables.conf");
		$datas=explode("\n",$data);
		if($GLOBALS["VERBOSE"]){echo __CLASS__.'/'.__FUNCTION__." iptables.conf: ". count($data)." lines L.". __LINE__."\n";}
		$pattern="#$regexPat#";	
		$count=0;
		while (list ($num, $ligne) = each ($datas) ){
			if($ligne==null){continue;}
			if(preg_match($pattern,$ligne)){
				if($GLOBALS["VERBOSE"]){echo __CLASS__.'/'.__FUNCTION__." Delete \"$ligne\" L.". __LINE__."\n";}		
				$count++;continue;
			}
			$conf=$conf . $ligne."\n";
		}

		file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
		if($count>0){
			$cmd="$iptables_restore < /etc/artica-postfix/iptables.new.conf";
			if($GLOBALS["VERBOSE"]){echo __CLASS__.'/'.__FUNCTION__."$cmd L.". __LINE__."\n";}
			shell_exec("$cmd");
		}
		
		
	}
	
	
	public function LOCATE_SENDMAIL_PID_PATH(){
		if(is_file("/var/run/sendmail.pid")){return "/var/run/sendmail.pid";}
	}
	
	
	
	public function events($text,$logFile=null,$phplog=false,$sourcefunction=null,$sourceline=null,$sourcefile=null){
		$maxSize=900000;
		$mem=null;
		$internal_load=0;
		$pid=getmypid();
		if($logFile<>null){
			$logbasename=basename($logFile);
			if($logbasename=="squid.watchdog.log"){$maxSize=900000;}
		}
		if($sourcefile<>null){$sourcefile=basename($sourcefile);}
		$date=date("Y-m-d H:i:s");
		if(function_exists("debug_backtrace")){
			$trace=debug_backtrace();
			if(isset($trace[1])){
				if($sourcefile==null){$sourcefile=basename($trace[1]["file"]);}
				if($sourcefunction==null){$sourcefunction=$trace[1]["function"];}
				if($sourceline==null){$sourceline=$trace[1]["line"];}
			}
			
		}
		
		if(function_exists("memory_get_usage")){$mem=round(((memory_get_usage()/1024)/1000),2);}
		if(function_exists("sys_getloadavg")){$array_load=sys_getloadavg();$internal_load=$array_load[0];	}		
	
		

		$line="$date [$pid] $sourcefile: $text  - function:$sourcefunction in line:$sourceline Mem:{$mem}MB Load:$internal_load\n";
		if($logFile==null){$logFile="/var/log/artica-postfix/syslog.log";}
		
		
		$size=@filesize($logFile);
		if($size>$maxSize){@unlink($logFile);}
		
		$h = @fopen($logFile, 'a');
	
		
		if($GLOBALS["VERBOSE"]){echo $line;}
		if($phplog){error_log("$line");}	
		@fwrite($h,$line);
		@fclose($h);
	}	

	public function dirdir($path){
		$array=array();
		foreach (glob("$path/*",GLOB_ONLYDIR) as $directory) {
			$directory=utf8_decode($directory);
			$directory=str_replace('//', "/", $directory);
			$array[$directory]=$directory;
		}
		
		return $array;
	}
	
	
	public function COUNT_FILES($directory){
		if (!$handle = opendir($directory)) {return 0;}
		$c=0;
		while (false !== ($filename = readdir($handle))) {
			if($filename=="."){continue;}
			if($filename==".."){continue;}
			$path = $directory.'/'.$filename;
			if(is_dir($path)){continue;}
			$c++;
		}	
		return $c;
	}
	
public	function getDirectories($directory){
	$GLOBALS["RECURSVIVE_INC"]=$GLOBALS["RECURSVIVE_INC"]+1;
	if($GLOBALS["MAX_RECUSIVE_DIRS"]<50){$GLOBALS["MAX_RECUSIVE_DIRS"]=400;}
	if($GLOBALS["RECURSVIVE_INC"]>$GLOBALS["MAX_RECUSIVE_DIRS"]){
		$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}	
		error_log("PHP Fatal: cannot parse directory [instance {$GLOBALS["RECURSVIVE_INC"]}], maximum recursive of {$GLOBALS["MAX_RECUSIVE_DIRS"]} has been reached $called");
		return;
	}
	$array_items = array();
	if ($handle = opendir($directory)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				if (is_dir($directory. "/" . $file)) {
						$newarrayDirectories= $this->getDirectories($directory. "/" . $file);
						if(is_array($newarrayDirectories)){$array_items = array_merge($array_items,$newarrayDirectories);}
						$file = $directory . "/" . $file;
						$array_items[] = preg_replace("/\/\//si", "/", $file);
				} else {
					//$file = $directory . "/" . $file;
					//$array_items[] = preg_replace("/\/\//si", "/", $file);
				}
			}
		}
		closedir($handle);
	}
	return $array_items;
	

} 

function DirRecursiveFiles($dir,$pattern=null){
    $Npattern=str_replace(".", "\.", $pattern);
    $Npattern=str_replace("*", ".*?", $Npattern);	
    $files = Array();
    $file_tmp= glob($dir.'*',GLOB_MARK | GLOB_NOSORT);

    foreach($file_tmp as $item){
        if(substr($item,-1)!=DIRECTORY_SEPARATOR){
        	if($pattern<>null){
        		if(!is_file($item)){continue;}
        		if(!preg_match("#$Npattern$#", $item)){continue;}
        	}
            $files[] = $item;
        }else{
            $files = array_merge($files,$this->DirRecursiveFiles($item,$pattern));
        }
    }

    return $files;
}
	
function DirFiles($path,$regexsearch=null){
	$dir_handle = @opendir($path);
	$array=array();
	if(!$dir_handle){
		return array();
	}
	$count=0;	
	while ($file = readdir($dir_handle)) {
	  if($file=='.'){continue;}
	  if($file=='..'){continue;}
	  if(is_dir("$path/$file")){continue;}
	 	if($regexsearch<>null){if(!preg_match("#$regexsearch#i", $file)){continue;}}
			$array[$file]=$file;
			continue;
		
		
	  }
	
	@closedir($dir_handle);
	return $array;
}

	function GLFS_ismounted($path){
		$pathString=str_replace("/","\/",$path);
		$pathString=str_replace(".","\.",$pathString);
		$f=explode("\n",@file_get_contents("/proc/mounts"));
		while (list ($index, $line) = each ($f) ){
			if(preg_match("#$pathString\s+fuse\.glusterfs#",$line)){
				return true;
			}
			
		}
		
	}



	public function dir_count_files_recursive($path) {
	    $files = 0;
	    $dir = opendir($path);
	    if (!$dir){return 0;}
	    while (($file = readdir($dir)) !== false) {
	        if ($file[0] == '.'){ continue; }
	        if (is_dir($path.$file)){        
	            $files += $this->dir_count_files_recursive($path.$file.DIRECTORY_SEPARATOR);
	        }
	        else {
	            $files++;
	        }
	    }    
	    closedir($dir);
	    return $files;
		}

public function LOCATE_SQUID_PID(){
	$pid_filename=null;
	if(is_file("/var/run/squid/squid.pid")){return "/var/run/squid/squid.pid";}
	$f=explode("\n",@file_get_contents($this->SQUID_CONFIG_PATH()));
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#pid_filename\s+(.+)#",$ligne,$re)){
			$pid_filename=trim($re[1]);
		}
	}
	
	if($pid_filename<>null){
		if(!is_file($pid_filename)){
			if(is_file("/var/run/squid.pid")){return "/var/run/squid.pid";}
		}
	}
	
}


	public function PIDOF($binpath,$noppid=false){
		
		$cmd=$this->find_program('pidof')." -s $binpath";
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: $cmd\n";}
		$re=$this->proc_exec($cmd);
		if(is_array($re)){
		while (list ($num, $ligne) = each ($re) ){	
			if(preg_match("#[0-9]+#",$ligne)){
				$pid=trim($ligne);
				if($this->PID_IS_CHROOTED($pid)){continue;}
				if($noppid){
					if($GLOBALS["VERBOSE"]){echo "PIDOF -> $pid\n";}
					return $pid;}
				
				return $this->PROCESS_PPID($pid);
			}
		}
		}
		
	}
	
	public function PID_IS_CHROOTED($pid){
		return false;
		if(is_file("/proc/user_beancounters")){return false;}
		if($this->find_program("cgconfigparser")){return false;}
		$pid=trim($pid);
		if(!is_numeric($pid)){return;}
		if(is_file("/etc/artica-postfix/AS_VPS_CLIENT")){return false;}
		if(!is_file("/proc/$pid/cgroup")){return false;}
		if(preg_match("#[0-9]+.+?:/(.+?)$#",@file_get_contents("/proc/$pid/cgroup"),$re)){
			$vps=trim($re[1]);
			if(strlen($vps)>1){return true;}
		}
		return false;
	}
	
	public function PIDOF_PATTERN($pattern){
		$pattern=str_replace("/","\/",$pattern);
		$pattern=$this->find_program('pgrep')." -l -f \"$pattern\" 2>&1";
		if($GLOBALS["VERBOSE"]){echo "PIDOF_PATTERN:: $pattern\n";}
		exec($pattern,$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#pgrep\s+#",$ligne)){continue;}
			if($GLOBALS["VERBOSE"]){echo "PIDOF_PATTERN:: $ligne\n";}
			if(preg_match("#^([0-9]+)\s+#",$ligne,$re)){return $re[1];}
		}
		
	}

	
	public function PIDOF_PATTERN_ALL($pattern){
		$mypid=getmypid();
		$LIST=array();
		$pattern=str_replace("/","\/",$pattern);
		$pattern=$this->find_program('pgrep')." -l -f \"$pattern\" 2>&1";
		if($GLOBALS["VERBOSE"]){echo "PIDOF_PATTERN:: $pattern\n";}
		exec($pattern,$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#pgrep\s+#",$ligne)){continue;}		
			if(!preg_match("#^([0-9]+)\s+#",$ligne,$re)){continue;}
			if($GLOBALS["VERBOSE"]){echo "PIDOF_PATTERN_ALL():: Found $pid \"$ligne\"\n";}
			$pid=$re[1];
			if($pid==$mypid){continue;}
			$LIST[$pid]=$pid;
				
		}
		return $LIST;
	
	}	
	
	public function PROCESS_GET_USER($pid){
		$filename="/proc/$pid/status";
		if(!is_file($filename)){return null;}
		$f=explode("\n", @file_get_contents($filename));
		while (list ($num, $ligne) = each ($f) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			if(!preg_match("#Uid:\s+([0-9]+)#", $ligne,$re)){continue;}
			if($re[1]==0){return "root";}
			return $this->USER_ID_FROM_USERNAME($re[1]);
		}
		
	}
	
	public function USER_ID_FROM_USERNAME($id){
		$filename="/etc/shadow";
		$f=explode("\n", @file_get_contents($filename));
		while (list ($num, $ligne) = each ($f) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			if(!preg_match("#^(.*?):.*?:$id#",$ligne,$re)){continue;}
			return $re[1];
		}
	}
	
	
	public function PROCESS_TIME_TO_LIVE($pid){
		exec("/usr/share/artica-postfix/bin/procstat",$array);
		while (list ($num, $ligne) = each ($array) ){
			if(preg_match("#start_time:.+?\((.+?\.#",$ligne,$re)){
				$mins=($re[1]/60);
				return $mins;
			}
		}
	}
	
	
	public function POSTFIX_MULTI_INSTANCES_LIST(){
		if($GLOBALS["MEM_postmulti"]<>null){$postmulti=$GLOBALS["MEM_postmulti"];}else{$postmulti=$this->find_program("postmulti");$GLOBALS["MEM_postmulti"]=$postmulti;}
		if(strlen($postmulti)<3){return null;}
		exec("$postmulti -l 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#^(.+?)\s+([a-z\-])\s+(.+)#",$ligne,$re)){
				$re[1]=trim($re[1]);
				if(preg_match("#postfix-(.+)#",$re[1],$ri)){
					$instance[$ri[1]]=$ri[1];
				}
			}
		}
		
		return $instance;
	}
	
	public function ISMemoryHiger1G(){
		if(isset($GLOBALS["ISMemoryHiger1G"])){return $GLOBALS["ISMemoryHiger1G"];}
		$ini=new iniFrameWork();
		$ini->loadFile("/etc/artica-postfix/performances.conf");
		$DisableFollowServiceHigerThan1G=$ini->get("PERFORMANCES","DisableFollowServiceHigerThan1G");
		if(!is_numeric($DisableFollowServiceHigerThan1G)){$DisableFollowServiceHigerThan1G=0;}
		if($DisableFollowServiceHigerThan1G==1){
			$GLOBALS["ISMemoryHiger1G"]=true;
			return true;
		}
		$mem=$this->MEM_TOTAL_INSTALLEE();
		if($mem>1012024){			
			$GLOBALS["ISMemoryHiger1G"]=true;
			return true;
		}
		$GLOBALS["ISMemoryHiger1G"]=false; 
		return false;
	}
	
	
	public function MEM_TOTAL_INSTALLEE(){
		if(isset($GLOBALS["MEM_TOTAL_INSTALLEE"])){return $GLOBALS["MEM_TOTAL_INSTALLEE"];}
		$results=1012029;
		$filename="/proc/meminfo";
		$f=explode("\n", @file_get_contents($filename));
		while (list ($num, $ligne) = each ($f) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			if(!preg_match("#MemTotal:\s+([0-9]+)#",$ligne,$re)){continue;}
			$results=$re[1];
			break;
		}
		$GLOBALS["MEM_TOTAL_INSTALLEE"]=$results;
		return $results;
	}
	
	public function squid_get_alternate_port(){
		$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match("#(transparent|tproxy|intercept)#i", trim($ligne))){continue;}
			if(preg_match("#http_port\s+([0-9]+)$#", trim($ligne),$re)){return $re[1];}
			if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)$#", trim($ligne),$re)){return "{$re[1]}:{$re[2]}";}
			
			if(preg_match("#http_port\s+([0-9]+)\s+#", trim($ligne),$re)){return $re[1];}
			if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)\s+#", trim($ligne),$re)){return "{$re[1]}:{$re[2]}";}			
		}
		
	}
	
	public function squid_locate_pinger(){
		return $this->squid_locate_generic_bin("pinger");
		
	}
	
	
	public function squid_locate_generic_bin($program){
	$possibleDirs[]="/lib/squid3";
	$possibleDirs[]="/lib64/squid3";
	$possibleDirs[]="/lib/squid";
	$possibleDirs[]="/lib64/squid";
	$possibleDirs[]="/usr/lib/squid";
	$possibleDirs[]="/usr/lib64/squid";
	$possibleDirs[]="/usr/local/squid/libexec";
	while (list ($num, $directory) = each ($possibleDirs) ){
		if(is_file("$directory/$program")){return "$directory/$program";}
	}
	
}

private function squid_is31(){
	if(isset($GLOBALS["squid_is31"])){return $GLOBALS["squid_is31"];}
	$root_squid_version=$this->squid_version();
	if($GLOBALS["VERBOSE"]){echo "Version: $root_squid_version\n";}
	$data=null;
	$GLOBALS["squid_is31"]=false;
	$VER=explode(".",$root_squid_version);
	if($VER[0]<4){
		if($VER[1]<2){
			if($GLOBALS["VERBOSE"]){echo "$root_squid_version -> is 3.1.x\n";}
			$GLOBALS["squid_is31"]=true;return true;}
	}
	return false;
	
}
	private function GET_INFO($key){
		
		return trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/$key"));
		
		
	}
	
	private function squid_get_internal_addrport(){
		$SquidMgrListenPort=trim($this->GET_INFO("SquidMgrListenPort"));
		if( !is_numeric($SquidMgrListenPort) OR ($SquidMgrListenPort==0) ){
			$SquidBinIpaddr=$this->GET_INFO("SquidBinIpaddr");
			if($SquidBinIpaddr==null){$SquidBinIpaddr="127.0.0.1";}
			$http_port=$this->squid_get_alternate_port();
	
			if(preg_match("#(.+?):([0-9]+)#", $http_port,$re)){
				$SquidBinIpaddr=$re[1];
				if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
				$http_port=$re[2];
			}
	
		}else{
			$SquidBinIpaddr="127.0.0.1";
			$http_port=$SquidMgrListenPort;
		}
	
		return array($SquidBinIpaddr,$http_port);
	}	
	
	
	private function squidclient_http($cmd){
		$t0=time();
		include_once("/usr/share/artica-postfix/ressources/class.ccurl.inc");
		$PP=$this->squid_get_internal_addrport();
		writelogs_framework("http://{$PP[0]}:{$PP[1]}/squid-internal-mgr/$cmd",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$curl=new ccurl("http://{$PP[0]}:{$PP[1]}/squid-internal-mgr/$cmd",true);
		$curl->CURLOPT_NOPROXY=$PP[0];
		$curl->ArticaProxyServerEnabled=="no";
		$curl->interface="127.0.0.1";
		$curl->Timeout=5;
		$curl->UseDirect=true;
		if(!$curl->get()){
			writelogs_framework("$curl->error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return;}
		
			
		writelogs_framework(strlen($curl->data)." bytes lenght",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		return $curl->data;
		
		
	}
	
	public function squidclient($cmd){
		if($this->squid_is31()){
			if($GLOBALS["VERBOSE"]){echo "Only squid 3.1x = > \"$cmd\"...\n";}
			return $this->squidclient_cmd($cmd);
		}
		writelogs_framework("squidclient_http($cmd)",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		return $this->squidclient_http($cmd);
		
	}
	
	
	public function isSquidNTLM(){
		$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
		while (list ($num, $ligne) = each ($f) ){
			if(preg_match("#^auth_param\s+ntlm\s+program#", $ligne)){return true;}
		}
		return false;
		
	}

	private function squidclient_cmd($cmd){
	
		if(!isset($GLOBALS["SQUIDCLIENT"])){
			$PP=$this->squid_get_internal_addrport();
			$squidclient=$this->find_program("squidclient");
			$GLOBALS["SQUIDCLIENT"]="$squidclient -l 127.0.0.1 -T 5 -h 127.0.0.1 -p {$PP[1]} mgr";
		}
	
		exec($GLOBALS["SQUIDCLIENT"].":$cmd 2>&1",$results);
		if($GLOBALS["VERBOSE"]){echo $GLOBALS["SQUIDCLIENT"].":$cmd ". count($results)." lines\n";}
		return @implode("\n", $results);
	
	}
	
	
	public function squid_get_system_info(){
	
			$results=explode("\n",$this->squidclient("info"));
			
			$path=null;		
		
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#^(.*?):$#", trim($ligne),$re)){
				$title=$re[1];
				continue;
			}
		
			if(preg_match("#\s+(.*?):\s+(.+)#", $ligne,$re)){
				$sub=$re[1];
				$values=trim($re[2]);
			}
			if(strpos($ligne, ':')==0){
				if(preg_match("#\s+([0-9]+)\s+(.+)#", $ligne,$re)){
					$sub=trim($re[2]);
					$values=trim($re[1]);
				}
			}
		
			if($title==null){continue;}
			if($sub==null){continue;}
			$ARRAY[$title][$sub]=$values;
		
		
		}

		return $ARRAY;
		
	}
	
	
	public function squid_get_cache_infos(){
		$path=null;
		$array=array();
	$results=explode("\n",$this->squidclient("storedir"));
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#Connection refused#",$line)){
			writelogs_framework($line,__FUNCTION__,__LINE__);;
			return;
		}
		
		if(preg_match("#Store Directory\s+\#([0-9]+).+?:\s+(.+)#",$line,$re)){
			if($GLOBALS["VERBOSE"]){echo "Store Directory -> {$re[2]}\n";}
			$path=trim($re[2]);
			$array[$path]["index"]=$re[1];
			continue;
		}
		
		if($path==null){continue;}
		
		if(preg_match("#Maximum Size:\s+([0-9]+)#",$line,$re)){
		if($GLOBALS["VERBOSE"]){echo "$path -> Maximum Size:{$re[1]}\n";}
			$array[$path]["MAX"]=$re[1];
		}
		
		if(preg_match("#Current Size:\s+([0-9]+)#",$line,$re)){
			if($GLOBALS["VERBOSE"]){echo "$path ->Current Size:{$re[1]}\n";}
			$array[$path]["CURRENT"]=$re[1];
		}		

		if(preg_match("#Percent Used:\s+([0-9\.]+)#",$line,$re)){
			if($GLOBALS["VERBOSE"]){echo "$path ->Percent Used:{$re[1]}\n";}
			$array[$path]["POURC"]=$re[1];
		}

		if(preg_match("#ERR_ACCESS_DENIED#",$line)){
			writelogs_framework("ACCESS DENIED",__FUNCTION__,__LINE__);;
			break;
		}
		
		
	}		
		return $array;
		
	}
	
	
	public function MYSQL_SOCKET_PATH(){
		$cnf=$this->MYSQL_MYCNF_PATH();
		$ini=new iniFrameWork($cnf);
		$return=$ini->get("mysqld","socket");
		if(trim($return==null)){return "/var/run/mysqld/mysqld.sock";}
	}
	
	public function MYSQL_MYCNF_PATH(){
		if(is_file('/etc/mysql/my.cnf')){return('/etc/mysql/my.cnf');}
  		if(is_file('/etc/my.cnf')){return '/etc/my.cnf';}

	} 
	
	public function MYSQL_DATADIR(){
		return $this->MYSQL_DATA_DIR();

	} 	
	
	
	
	public function FSTAB_GETDEV($targetPointPath){
		$datas=explode("\n",@file_get_contents("/etc/fstab"));
		while (list ($num, $ligne) = each ($datas) ){
			if(strpos($ligne,$targetPointPath)>0){
				if(preg_match("#(.+?)\s+#",$ligne,$re)){
				return $re[1];
			}}
		}
		
	}
	
	
	public function FSTAB_QUOTA($dev,$enabled=0){
		$rows=explode("\n",@file_get_contents("/etc/fstab"));
		$dev_pattern=str_replace("/","\/",$dev);
		if(!is_array($rows)){writelogs_framework("no rows, abort",__FUNCTION__,__FILE__);return ;}
		while (list ($index, $line) = each ($rows) ){
		if(preg_match("#$dev_pattern\s+(.+?)\s+(.*?)\s+(.*?)\s+(.+)#",$line,$re)){
					$row_index=$index;
					$mount_path=$re[1];
					$type=$re[2];
					$option=$re[3];
					$other=$re[4];
					break;
				}
			}
		if($mount_path==null){return;}
		$options=explode(",",$option);
		while (list ($i, $token) = each ($options) ){$tokens[$token]=true;}
		writelogs_framework("OPTIONS=$option mount on $mount_path",__FUNCTION__,__FILE__);
		unset($tokens["usrquota"]);
		
		if($enabled==0){
			unset($tokens["usrquota"]);
			unset($tokens["usrjquota=aquota.user"]);
			unset($tokens["grpjquota=aquota.group"]);
			unset($tokens["jqfmt=vfsv"]);
			unset($tokens["jqfmt=vfsv0"]);		
			
		}else{
			$tokens["usrjquota=aquota.user"]=true;
			$tokens["grpjquota=aquota.group"]=true;
			$tokens["jqfmt=vfsv0"]=true;
		}
		while (list ($token,$i) = each ($tokens) ){$final[]=$token;}
		$new_options=implode(",",$final);
		writelogs_framework("$dev\t$mount_path\t$type\t$new_options\t$other",__FUNCTION__,__FILE__,__LINE__);
		reset($rows);
		$rows[$row_index]="$dev\t$mount_path\t$type\t$new_options\t$other";
		@file_put_contents("/etc/fstab",@implode("\n",$rows));
		$mount=$this->find_program("mount");
		writelogs_framework("$mount -o remount $dev",__FUNCTION__,__FILE__,__LINE__);
		exec("$mount -o remount $dev");	
		$quotacheck=$this->find_program("quotacheck");
		if(is_file($quotacheck)){shell_exec("$quotacheck -afgum &");}
		$quotaon=$this->find_program("quotaon");
		if(is_file($quotaon)){shell_exec("$quotaon -avug &");}
		
				
	}
	
	
	public function grep_php($filename,$maxnumLines=0,$pattern=null){
		if($pattern==null){return $this->tail_php($filename,$maxnumLines);}
		if(!is_file($filename)){
			if($GLOBALS["DEBUG_GREP"]){echo "$filename no such file\n";}
			return array();
		}
		$lines=array();
		$fp = @fopen($filename, "r");
		if(!$fp){
			if($GLOBALS["DEBUG_GREP"]){echo "$filename BAD FD\n";}
			return array();}
		
		$t=array();
		while(!feof($fp)){
			$line = trim(fgets($fp, 4096));
			$line=str_replace("\r\n", "", $line);
			$line=str_replace("\n", "", $line);
			$line=str_replace("\r", "", $line);
			if(!preg_match("#$pattern#i", $line)){continue;}
			$t[]=$line;
		}
		
		if(count($t)==0){return array();}
		$RESULTS=array();
		if($maxnumLines>0){
			rsort($t);
			
			for($i=0;$i<$maxnumLines;$i++){
				if($GLOBALS["DEBUG_GREP"]){echo "{$t[$i]} OK\n";}
				$RESULTS[]=$t[$i];
			}
			
		}
		
		
		@fclose($fp);
		return $RESULTS;
	}
	
	
	function tail_php($file, $numLines = 100,$pattern=null){
		if($pattern<>null){return $this->grep_php($file,$numLines,$pattern);}
		$fp = fopen($file, "r");
		$chunk = 4096;
		$fs = sprintf("%u", filesize($file));
		$max = (intval($fs) == PHP_INT_MAX) ? PHP_INT_MAX : filesize($file);
		$f=array();
		for ($len = 0; $len < $max; $len += $chunk) {
			$seekSize = ($max - $len > $chunk) ? $chunk : $max - $len;
	
			fseek($fp, ($len + $seekSize) * -1, SEEK_END);
			$data = fread($fp, $seekSize);
			$f[]=$data;
			if (substr_count($data, "\n") >= $numLines + 1) {
				preg_match("!(.*?\n){".($numLines)."}$!", $data, $match);
				fclose($fp);
				return $f;
			}
		}
		fclose($fp);
		return $f;
	}	
	
	
	
	
	
	public function CHANGE_HOSTNAME($hostname){
		if(trim($hostname)==null){return;}
		@unlink("/etc/artica-postfix/FULL_HOSTNAME");
		
		if(strpos($hostname, ".")>0){
			$tbl=explode(".",$hostname);
			$strippedHost=$tbl[0];
			unset($tbl[0]);
			$domainName=@implode(".", $tbl);
		}else{
			$strippedHost=$hostname;
		}
		
		$DisableEtcHosts=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableEtcHosts"));
		if(!is_numeric($DisableEtcHosts)){$DisableEtcHosts=0;}

		
		$domainName=str_replace("(", "", $domainName);
		$domainName=str_replace(")", "", $domainName);
		if($domainName==null){$domainName="localhost.localdomain";}
		
		
		@file_put_contents("/etc/hostname", $hostname);
		@file_put_contents("/etc/HOSTNAME", $hostname);
		@file_put_contents("/etc/mailname", $hostname);
		
		$sysctl=$this->find_program("sysctl");
		$hostnamebin=$this->find_program("hostname");
		$nohup=$this->find_program("nohup");
		if(strlen($sysctl)>3){
			shell_exec("$sysctl -w kernel.domainname=$domainName >/dev/null 2>&1");
			shell_exec("$sysctl -w kernel.hostname=$strippedHost >/dev/null 2>&1");
			shell_exec("$sysctl -p >/dev/null 2>&1");
		}
		if(strlen($hostnamebin)>3){
			shell_exec("$hostnamebin $hostname  >/dev/null 2>&1");
		}
		
		
		if(is_file("/etc/init.d/hostname.sh")){shell_exec("/etc/init.d/hostname.sh stop && /etc/init.d/hostname.sh start");}
   		if(is_file("/usr/share/artica-postfix/bin/artica-install")){shell_exec("$nohup /usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus >/dev/null 2>&1 &");}
	}	
	
	
	public function GET_QUOTA_MOUNTED(){
		$mount=$this->find_program("mount");
		exec("$mount 2>&1",$results);
		while (list ($i, $line) = each ($results) ){
			if(!preg_match("#(.+?)\s+on\s+(.+?)\s+.+?usrjquota#",$line,$re)){continue;}
			$rr[$re[2]]=true;
		}
		
		return $rr;
		
	}
	
function uptime(){

     $e=file('/proc/uptime');
     preg_match('#(.*) (.*)#', $e[0], $e);
     $t=time();
     $Since=$t-intval($e[1]);
     if($GLOBALS["VERBOSE"]){echo "First integer {$e[1]} -> ".intval($e[1])." ($Since)\n";}     
     return $this->distanceOfTimeInWords($Since,time());
     
   }	
   
   function TARGZ_TEST_CONTAINER($container){
	   	$size=@filesize($container);
	   	if($size<100){return false;}
		$tar=$this->find_program("tar");
	   	exec("$tar ztvf $container 2>&1");
	   	while (list ($a, $b) = each ($resultsZ)){
	   		if(preg_match("#does not look like a tar#", $b)){
	   			if($GLOBALS["VERBOSE"]){echo "tar $container failed $b\n";}
	   			return;
	   		}
	   	
	   		if(preg_match("#tar: Error#", $b)){
	   			if($GLOBALS["VERBOSE"]){echo "tar $container failed $b\n";}
	   			return;
	   		}
	   	}

   	return true;
   	
   }
	
	
function distanceOfTimeInWords($fromTime, $toTime = 0, $showLessThanAMinute = true) {
	    $distanceInSeconds = round(abs($toTime - $fromTime));
	    $distanceInMinutes = round($distanceInSeconds / 60);
	       
	        if ( $distanceInMinutes <= 1 ) {
	            if ( !$showLessThanAMinute ) {
	                return ($distanceInMinutes == 0) ? 'less than a minute' : '1 {minute}';
	            } else {
	                if ( $distanceInSeconds < 5 ) {
	                    return '{lessthan} 5 {seconds} ('.$distanceInSeconds.'s)';
	                }
	                if ( $distanceInSeconds < 10 ) {
	                    return '{lessthan} 10 {seconds} ('.$distanceInSeconds.'s)';
	                }
	                if ( $distanceInSeconds < 20 ) {
	                    return '{lessthan} 20 {seconds} ('.$distanceInSeconds.'s) ';
	                }
	                if ( $distanceInSeconds < 40 ) {
	                    return '{abouttime} {halfaminute} ('.$distanceInSeconds.'s)';
	                }
	                if ( $distanceInSeconds < 60 ) {
	                    return '{lessthanaminute}';
	                }
	               
	                return '1 minute';
	            }
	        }
	        if ( $distanceInMinutes < 45 ) {
	            return $distanceInMinutes . ' {minutes}';
	        }
	        if ( $distanceInMinutes < 90 ) {
	            return '{abouttime} 1 {hour}';
	        }
	        if ( $distanceInMinutes < 1440 ) {
	            return '{abouttime} ' . round(floatval($distanceInMinutes) / 60.0) . ' {hours}';
	        }
	        if ( $distanceInMinutes < 2880 ) {
	            return '1 {day}';
	        }
	        if ( $distanceInMinutes < 43200 ) {
	            return '{abouttime} ' . round(floatval($distanceInMinutes) / 1440) . ' {days}';
	        }
	        if ( $distanceInMinutes < 86400 ) {
	            return '{abouttime} 1 {month}';
	        }
	        if ( $distanceInMinutes < 525600 ) {
	            return round(floatval($distanceInMinutes) / 43200) . ' {months}';
	        }
	        if ( $distanceInMinutes < 1051199 ) {
	            return '{abouttime} 1 {year}';
	        }
	       
	        return 'over ' . round(floatval($distanceInMinutes) / 525600) . ' {years}';
	}	
	
	
	public function FSTAB_ACL($dev,$aclenabled=0){
	
			$rows=explode("\n",@file_get_contents("/etc/fstab"));
			
			$dev_pattern=str_replace("/","\/",$dev);
			if(!is_array($rows)){writelogs_framework("no rows, abort",__FUNCTION__,__FILE__);return ;}
			while (list ($index, $line) = each ($rows) ){
				if(preg_match("#$dev_pattern\s+(.+?)\s+(.*?)\s+(.*?)\s+(.+)#",$line,$re)){
					$row_index=$index;
					$mount_path=$re[1];
					$type=$re[2];
					$option=$re[3];
					$other=$re[4];
					break;
				}
			}
			
			
			if($mount_path==null){return;}
			$options=explode(",",$option);
			
			while (list ($i, $token) = each ($options) ){$tokens[$token]=true;}
			
			writelogs_framework("OPTIONS=$option mount on $mount_path",__FUNCTION__,__FILE__);
			if($aclenabled==0){
				unset($tokens["acl"]);
				unset($tokens["user_xattr"]);
				$tune2fs_cmd="-acl";
			}else{
				$tokens["acl"]=true;
				$tokens["user_xattr"]=true;
				$tune2fs_cmd="+acl";
			}
			while (list ($token,$i) = each ($tokens) ){$final[]=$token;}	
			
			$new_options=implode(",",$final);
			writelogs_framework("$dev\t$mount_path\t$type\t$new_options\t$other",__FUNCTION__,__FILE__,__LINE__);
			reset($rows);
			$rows[$row_index]="$dev\t$mount_path\t$type\t$new_options\t$other";
			@file_put_contents("/etc/fstab",@implode("\n",$rows));
			$mount=$this->find_program("mount");
			exec("$mount -o remount $dev");	
			$tune2fs=$this->find_program("tune2fs");
			if($tune2fs<>null){
				exec("$tune2fs -o user_xattr,acl $dev 2>&1",$rs);
				writelogs_framework("$tune2fs -o user_xattr,acl $dev",__FUNCTION__,__FILE__,__LINE__);
				writelogs_framework(implode(",",$rs),__FUNCTION__,__FILE__,__LINE__);
				
			}
			
			}
	
	
	public function DISK_GET_TYPE($dev_path){
		$dev_path=str_replace("/","\/",$dev_path);
		$blkid=$this->find_program("blkid");
		$tmp=$this->FILE_TEMP();
		shell_exec("$blkid >$tmp 2>&1");
		$datas=explode("\n",@file_get_contents($tmp));
		@unlink($tmp);
		while (list ($num, $ligne) = each ($datas) ){
			if(preg_match("#^$dev_path.+?TYPE=\"(.+?)\"#",$ligne,$re)){
				return $re[1];
			}
			
		}
	}

}

function BuildingExecStatus($text,$pourc){
	$ini=new iniFrameWork("/usr/share/artica-postfix/ressources/logs/exec.status.ini");
	$ini->set("status","text","$text");
	$ini->set("status","pourc","$pourc");
	$ini->saveFile("/usr/share/artica-postfix/ressources/logs/exec.status.ini");
	@chmod("/usr/share/artica-postfix/ressources/logs/exec.status.ini",0755);
	error_log(__FUNCTION__.":: status $pourc% $text");
	
}
function BuildingExecRightStatus($text,$pourc){
	$file="/usr/share/artica-postfix/ressources/logs/exec-right.status.ini";
	$ini=new iniFrameWork($file);
	$ini->set("status","text","$text");
	$ini->set("status","pourc","$pourc");
	$ini->saveFile($file);
	@chmod($file,0755);	
}


function cpulimit($forced=0){
	return;
	$unix=new unix();
	if($GLOBALS["cpulimit_path"]==null){
		$GLOBALS["cpulimit_path"]=$unix->find_program("cpulimit");
	}
	if($GLOBALS["cpulimit_path"]==null){return null;}
	if($forced==0){
		$cpu=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/cpulimit"));
		if($cpu==null){$cpu=0;}
	}else{
		$cpu=$forced;
	}
	if($cpu==0){return null;}
	$pid=getmypid();
	$cmd="{$GLOBALS["cpulimit_path"]} -p $pid -l $cpu -z >>/var/log/cpulimit 2>&1 &";
	if($GLOBALS["VERBOSE"]){writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);}
	shell_exec($cmd);
	
}


function GET_INFO_DAEMON($key){
   	if(function_exists("apc_store")){$value=apc_fetch($key);if($value<>null){return trim($value);}}	
	if($GLOBALS["GET_INFO_DAEMON"][$key]<>null){return $GLOBALS["GET_INFO_DAEMON"][$key];}
	$result=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/$key"));
	$GLOBALS["GET_INFO_DAEMON"][$key]=$result;
	if(function_exists("apc_store")){apc_store($key, $result);}	
	return $result;
}

function cpulimitProcessName($processName){
	$unix=new unix();
	$cpulimit=$unix->find_program("cpulimit");
	if($cpulimit==null){return null;}
	$cpu=GET_INFO_DAEMON('cpulimit');
	if($cpu==null){$cpu=0;}
	if($cpu==0){return null;}
	$cmd="$cpulimit -e $processName -l $cpu &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function cpulimitProcessNameKill($processName){
	$unix=new unix();
	$cpulimit=$unix->find_program("cpulimit");
	if($cpulimit==null){return null;}	
	$cmd=$unix->find_program("pgrep")." -l -f \"$cpulimit -e $processName\"";
	exec($cmd,$results);
	if(!is_array($results)){return null;}
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#([0-9]+)\s+(.+)#",$ligne,$re)){
			if(preg_match("#cpulimit#",$re[2])){
				if($re[1]>2){shell_exec($unix->find_program("kill")," -9 {$re[1]}");}	
			}
		}
	}
	
}


function writelogs_framework($text=null,$function=null,$file_source=null,$line=null){
	if($file_source==null){$file_source= __FILE__ ;}
	if(posix_getuid()==0){$prefix="[DAEMON]";}else{$prefix="[CONSOLE]";}
	if(function_exists("getmypid")){$pid=getmypid();}
	$date=date('H:i:s');
	if($line<>null){$line=" line $line";}
	$file_source=str_replace("/usr/share/artica-postfix/","",$file_source);
	$file_source=str_replace("/home/dtouzeau/developpement/artica-postfix/","",$file_source);
	if($GLOBALS["VERBOSE"]){echo "[$pid] $prefix::$function:: $text in $file_source$line\n";}
	$text="$date [$pid] $prefix::$function:: $text in $file_source$line";
	writeFrameOtherlogs('/var/log/artica-postfix/framework.debug',$text);
}
function writeFrameOtherlogs($file,$text=null){
			@mkdir(dirname($file));
		    $logFile=$file;
		    if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   		if (is_file($logFile)) { 
   			$size=filesize($logFile);
		    	if($size>100000){unlink($logFile);}
   		}
		 $logFile=str_replace("//","/",$logFile);
		$f = @fopen($logFile, 'a');
		
		@fwrite($f, "$text\n");
		@fclose($f);
}


class services_status{
	
	function services_status(){
		
	}
	
	public function artica_backup_status(){
		$unix=new unix();
		$pid=$unix->PIDOF("artica-backup");
		
		
	}
	
}


class kav4fsUnix{
	var	$MAIN=array();
	var $TASKS=array();
	
	
	function kav4fsUnix(){
		exec('/opt/kaspersky/kav4fs/bin/kav4fs-control --get-app-settings 2>&1',$results);
		writelogs_framework(count($results)." rows for --get-app-setting",__CLASS__."/".__FUNCTION__,__FILE__);
		$ini=new iniFrameWork();
		$ini->loadString("[INFOS]".@implode("\n",$results));
		$this->MAIN=$ini->_params["INFOS"];
	}
	
	
	function GetTaskList(){
		exec('/opt/kaspersky/kav4fs/bin/kav4fs-control --get-task-list 2>&1',$results);
		
		if(preg_match_all('#Name:\s+(.+?)\s+Id:\s+(.+?)\s+Class:\s+(.+?)\s+State:\s+([a-zA-Z0-9]+)#is',
		@implode("\n",$results),$re)){
			while (list ($index, $ID) = each ($re[2]) ){
				if(preg_match("#([0-9]+)\s+Runtime ID:\s+([0-9]+)#is",$ID,$ri)){
					$ID=$ri[1];
					$RUNTIMEID=$ri[2];
				}
				$array[$ID]=array(
					"NAME"=>$re[1][$index],"RUNTIMEID"=>$RUNTIMEID,
					"CLASS"=>$re[3][$index],"STATE"=>$re[4][$index]);
				
				$classes[$re[3][$index]][]=$ID;
			}
		
		}
		
		$this->TASKS["LIST"]=$array;
		$this->TASKS["CLASSES"]=$classes;
		
	}
	
	
}

class WakeOnLanClass{
 private $nic;
 var $error=array();
 	public function wake($mac){
 		$this->nic = fsockopen("udp://255.255.255.255", 9,$errno, $errstr);
 		if( !$this->nic ){
 			fclose($this->nic);
 			$this->error[]="fsockopen:\nERROR $errno \nudp 255.255.255.255\n {failed} $errstr";
 			return false;
 		}
 		else{
 			fwrite($this->nic, $this->pacquet($mac));
 			fclose($this->nic);
 			$this->error[]="{success}";
 			return true;
 		}
 }

 private function pacquet($Mac){
 	$packet = "";
 		for($i = 0; $i < 6; $i++){$packet .= chr(0xFF);}
 		for ($j = 0; $j < 16; $j++){
 		for($i = 0; $i < 12; $i=$i + 2){$packet .= chr(hexdec(substr($Mac, $i, 2)));}
 	}
 	return $packet;
 	}
 } 

class debuglogs{
	function debuglogs(){}
	
	public function events($text,$logFile,$phplog=false){
		$unix=new unix();
		$unix->events($text,$logFile,$phplog);
	}
	
}


function ini_set_verbosed(){
			ini_set('html_errors',0);
			ini_set('display_errors', 1);
			ini_set('error_reporting', E_ALL);
			ini_set('error_prepend_string','');
			ini_set('error_append_string','');	
}
function system_admin_events($text=null,$function=null,$file=null,$line=null,$category=null,$taskid=0){
	if($text==null){return;}
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){$file=basename($trace[1]["file"]);}
			if($function==null){$function=$trace[1]["function"];}
			if($line==0){$line=$trace[1]["line"];}
		}
			
	}
	
	$mem=round(((memory_get_usage()/1024)/1000),2);
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";
	if($taskid>0){$GLOBALS["SCHEDULE_ID"]=$taskid;}
	if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file ".basename($file)."\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["category"]=$category;
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	if(isset($GLOBALS["ARGVS"])){$array["ARGVS"]=$GLOBALS["ARGVS"];}else{$array["ARGVS"]=@implode(" ",$argv);}
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("/var/log/artica-postfix/system_admin_events")){@mkdir("/var/log/artica-postfix/system_admin_events",0755,true);}
	@file_put_contents("/var/log/artica-postfix/system_admin_events/$md5.log", $serialize);
}
function system_failover_events($text=null,$function=null,$file=null,$line=null,$category=null,$taskid=0){
	if($text==null){return;}
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){$file=basename($trace[1]["file"]);}
			if($function==null){$function=$trace[1]["function"];}
			if($line==0){$line=$trace[1]["line"];}
		}
			
	}

	$mem=round(((memory_get_usage()/1024)/1000),2);
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";
	if($taskid>0){$GLOBALS["SCHEDULE_ID"]=$taskid;}
	if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file ".basename($file)."\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["category"]=$category;
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	if(isset($GLOBALS["ARGVS"])){$array["ARGVS"]=$GLOBALS["ARGVS"];}else{$array["ARGVS"]=@implode(" ",$argv);}
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("/var/log/artica-postfix/system_failover_events")){@mkdir("/var/log/artica-postfix/system_failover_events",0755,true);}
	@file_put_contents("/var/log/artica-postfix/system_failover_events/$md5.log", $serialize);
}
function rotate_events($text=null,$function=null,$file=null,$line=null,$category=null,$taskid=0){
	if($text==null){return;}
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){$file=basename($trace[1]["file"]);}
			if($function==null){$function=$trace[1]["function"];}
			if($line==0){$line=$trace[1]["line"];}
		}
			
	}

	$mem=round(((memory_get_usage()/1024)/1000),2);
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";
	if($taskid>0){$GLOBALS["SCHEDULE_ID"]=$taskid;}
	if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file ".basename($file)."\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["category"]=$category;
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	if(isset($GLOBALS["ARGVS"])){$array["ARGVS"]=$GLOBALS["ARGVS"];}else{$array["ARGVS"]=@implode(" ",$argv);}
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("/var/log/artica-postfix/rotate_events")){@mkdir("/var/log/artica-postfix/rotate_events",0755,true);}
	@file_put_contents("/var/log/artica-postfix/rotate_events/$md5.log", $serialize);
}

function squid_admin_mysql($severity,$subject,$text,$file=null,$line=0){
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){
				$file=basename($trace[1]["file"]);
			}
			$function=$trace[1]["function"];
			if($line==0){
				$line=$trace[1]["line"];
			}
		}
			
	}
	if(!is_numeric($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["subject"]=$subject;
	$array["text"]=$text;
	$array["severity"]=$severity;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("/var/log/artica-postfix/squid_admin_mysql")){@mkdir("/var/log/artica-postfix/squid_admin_mysql",0755,true);}
	@file_put_contents("/var/log/artica-postfix/squid_admin_mysql/$md5.log", $serialize);
	
}

function squid_admin_notifs($text,$function,$file,$line,$category,$taskid=0){

	$mem=round(((memory_get_usage()/1024)/1000),2);
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";
	if($taskid>0){$GLOBALS["SCHEDULE_ID"]=$taskid;}
	if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file ".basename($file)."\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["pid"]=getmypid();
	$array["category"]=$category;
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	if(isset($GLOBALS["ARGVS"])){$array["ARGVS"]=$GLOBALS["ARGVS"];}else{$array["ARGVS"]=@implode(" ",$argv);}
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("/var/log/artica-postfix/squid_admin_notifs")){@mkdir("/var/log/artica-postfix/squid_admin_notifs",0755,true);}
	@file_put_contents("/var/log/artica-postfix/squid_admin_notifs/$md5.log", $serialize);
	$UfdbguardSMTPNotifs=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/UfdbguardSMTPNotifs")));
	if(!isset($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
	if(!is_numeric($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
	if($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]==0){
		$subject=substr($text, 0,80);
		$unix=new unix();
		$text=$text."Date: {$array["zdate"]}\nFunction:{$array["function"]}\nFile:{$array["file"]}\nLine:{$array["line"]}\n";
		$unix->send_email_events("[Proxy Service]: $subject", "$text", "system");
		
	}
	
	
	$unix=new unix();
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
	
	
	
	
	
}
function system_user_events($uid,$text,$function,$file,$line,$category,$taskid=0){
	$mem=round(((memory_get_usage()/1024)/1000),2);
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";
	if($taskid>0){$GLOBALS["SCHEDULE_ID"]=$taskid;}
	if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file ".basename($file)."\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["uid"]=$uid;
	$array["category"]=$category;
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("/var/log/artica-postfix/system_users_events")){@mkdir("/var/log/artica-postfix/system_users_events",0755,true);}
	@file_put_contents("/var/log/artica-postfix/system_users_events/$md5.log", $serialize);	
	
}

function ufdbguard_admin_events($text,$function,$file=null,$line=0,$category=null,$taskid=0){
	
	
	$date=date("Y-m-d H:i:s");
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			if($file==null){$file=basename($trace[1]["file"]);}
			if($function==null){$function=$trace[1]["function"];}
			if($line==0){$line=$trace[1]["line"];}
		}
			
	}	
	
	if($file=="exec.sarg.php"){$category="sarg";}

	if($category=="sarg"){
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		$GLOBALS["CLASS_UNIX"]->events($text,"/var/log/sarg-exec.log",false,$function,$line,$file);
	}
	
	
	if(basename($file)=="exec.squid.stats.php"){
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=@getmypid();}
		if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
		$array_load=sys_getloadavg();
		$internal_load=$array_load[0];
		
		if($GLOBALS["AS_ROOT"]){
			$date=@date("H:i:s");
			$logFile="/var/log/artica-squid-stats.log";
			if(is_file($logFile)){
				$size=filesize($logFile);
				if($size>5000000){unlink($logFile);}
			}
			$me="exec.squid.stats.php";
			$f = fopen($logFile, 'a');
			fwrite($f, "$date $me"."[".$GLOBALS["MYPID"]."/$internal_load]:$category::$function::$line: $text\n");
			fclose($f);
		}
	}
	$mem=round(((memory_get_usage()/1024)/1000),2);
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$text=$text."\nMemory used for this script:{$mem}M; System Load: $internal_load";	
	
	if(!isset($GLOBALS["SCHEDULE_ID"])){$GLOBALS["SCHEDULE_ID"]=0;}
	if($taskid>0){$GLOBALS["SCHEDULE_ID"]=$taskid;}
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file $file\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["category"]=$category;
	$array["TASKID"]=$GLOBALS["SCHEDULE_ID"];
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("/var/log/artica-postfix/ufdbguard_admin_events")){@mkdir("/var/log/artica-postfix/ufdbguard_admin_events",0755,true);}
	@file_put_contents("/var/log/artica-postfix/ufdbguard_admin_events/$md5.log", $serialize);
	
	$update["exec.update.squid.tlse.php"]=true;
	$update["exec.update.blacklist.instant.php"]=true;
	$update["exec.squid.blacklists.php"]=true;
	
	if($file=="exec.squidlogs.purge.php"){
		if(!is_dir("/var/log/artica-postfix/squid-stats-purge")){@mkdir("/var/log/artica-postfix/squid-stats-purge",0755,true);}
		@file_put_contents("/var/log/artica-postfix/squid-stats-purge/$md5.log", $serialize);
	}
	
	if(!isset($update[basename($file)])){return;}
	if($update[basename($file)]){
		if(!is_dir("/var/log/artica-postfix/ufdbguard_update_events")){@mkdir("/var/log/artica-postfix/ufdbguard_update_events",0755,true);}
		@file_put_contents("/var/log/artica-postfix/ufdbguard_update_events/$md5.log", $serialize);
	
	}	
	
	
}
function mysql_admin_events($text,$function,$file,$line,$category=null){
	$file=basename($file);
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file $file\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["category"]=$category;
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("/var/log/artica-postfix/mysql_admin_events")){@mkdir("/var/log/artica-postfix/mysql_admin_events",0755,true);}
	@file_put_contents("/var/log/artica-postfix/mysql_admin_events/$md5.log", $serialize);
	
	
}
function update_events($text,$function,$file,$line,$category=null){
	$file=basename($file);
	if($GLOBALS["VERBOSE"]){echo "$function:: $text in line $line in file $file\n";}
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]=$text;
	$array["function"]=$function;
	$array["file"]=basename($file);
	$array["line"]=$line;
	$array["category"]=$category;
	$serialize=serialize($array);
	$md5=md5($serialize);
	if(!is_dir("/var/log/artica-postfix/update_admin_events")){@mkdir("/var/log/artica-postfix/update_admin_events",0755,true);}
	@file_put_contents("/var/log/artica-postfix/update_admin_events/$md5.log", $serialize);
}


?>