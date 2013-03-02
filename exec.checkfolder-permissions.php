<?php
$GLOBALS["FORCE"]=false;$GLOBALS["REINSTALL"]=false;
$GLOBALS["NO_HTTPD_CONF"]=false;
$GLOBALS["NO_HTTPD_RELOAD"]=false;
if(is_array($argv)){
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--reinstall#",implode(" ",$argv))){$GLOBALS["REINSTALL"]=true;}
	if(preg_match("#--no-httpd-conf#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_CONF"]=true;}
	if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_RELOAD"]=true;}
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["posix_getuid"]=0;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');

$sock=new sockets();
if(is_file("/usr/share/artica-postfix/class.users.menus.inc")){@unlink("/usr/share/artica-postfix/class.users.menus.inc");}
$f=file("/etc/lighttpd/lighttpd.conf");
while (list ($num, $line) = each ($f) ){
	if(preg_match("#server\.username.*?\"(.+?)\"#", $line,$re)){
		$username=$re[1];
		continue;
	}
	
	if(preg_match("#server\.groupname.*?\"(.+?)\"#", $line,$re)){
		$groupname=$re[1];
		continue;
	}	
	
	if($groupname<>null){
		if($username<>null){break;}
	}
	
}

$f=array();
$unix=new unix();
$php5=$unix->LOCATE_PHP5_BIN();
if($GLOBALS["VERBOSE"]){echo "lighttpd user: $username:$groupname\n";}
if(is_dir("/etc/resolvconf")){
	shell_exec("$php5 ".basename(__FILE__)."/exec.virtuals-ip.php --resolvconf >/dev/null 2>&1 &");
	if(!is_dir("/etc/resolvconf/run/interface")){@mkdir("/etc/resolvconf/run/interface",0755,true);}

}
if(!is_dir("/var/log/btmp")){@mkdir("/var/log/btmp",0755,true);}

$sock=new sockets();
  $f[]='ressources';
  $f[]='ressources/sessions';
  $f[]='ressources/web';
  $f[]='ressources/web/logs';
  $f[]='ressources/logs/web/queue/sessions';
  $f[]='framework'; 
  $f[]='ressources/userdb';
  $f[]='ressources/conf';
  $f[]='ressources/conf/kasDatas';
  $f[]='ressources/logs';
  $f[]='ressources/profiles';
  $f[]='ressources/profiles/icons';
  $f[]='ressources/sessions/SessionData';
  $f[]='computers/ressources/sessions/SessionData';
  $f[]='computers/ressources/logs';
  $f[]='ressources/conf/upload';
  $f[]='computers/ressources/profiles';
  $f[]='ressources/conf/upload';
  
  
  $artica_path=dirname(__FILE__);
  if($username==null){
  	$LighttpdUserAndGroup=$sock->GET_INFO('LighttpdUserAndGroup');
  	$LighttpdUserAndGroup=str_replace('lighttpd:lighttpd:lighttpd','lighttpd:lighttpd',$LighttpdUserAndGroup);
  	$LighttpdUserAndGroup=str_replace('www-data:www-data:www-data','www-data:www-data',$LighttpdUserAndGroup);   
	if(!preg_match("#(.+?):(.+)#", $LighttpdUserAndGroup,$re)){$username="www-data";$groupname="www-data";}
  }
  
  $unix->chmod_func(0755, "$artica_path/*");
  $unix->chmod_func(0777, "$artica_path/ressources/conf/upload");
  $unix->chmod_func(0777, "$artica_path/ressources/conf/upload/*");
  
  if($username==null){$username="www-data";}
  if($groupname==null){$groupname="www-data";}
  
  $unix->chown_func($username,$groupname, "$artica_path/*");
  
  
  	@chown("$artica_path", $username);
  	@chgrp("$artica_path", $groupname);  
  	@chmod("$artica_path",0755);
  while (list ($num, $dir) = each ($f) ){
  	if(!is_dir("$artica_path/$dir")){@mkdir("$artica_path/$dir",0755,true);}
  	@chown("$artica_path/$dir", $username);
  	@chgrp("$artica_path/$dir", $groupname);
  	@chmod("$artica_path/$dir",0755);
  	
  }
  
  @mkdir("/var/log/artica-postfix/ufdbguard-blocks",0777,true);
  @chmod("/var/log/artica-postfix/ufdbguard-blocks", 0777);  
  
  @mkdir("/opt/artica/var/rrd/yorel",0755,true);
  @chown("/opt/artica/var/rrd/yorel", $username);
  @chgrp("/opt/artica/var/rrd/yorel", $groupname);  
  
$f=array();

$f[]='/usr/share/zarafa-webaccess/*';
$f[]='/var/lib/zarafa-webaccess/tmp/*';
while (list ($num, $dir) = each ($f) ){
	$unix->chown_func($username, $groupname,$dir);
	$unix->chmod_func(0755,$dir);
}  
  

$f=array();
$f[]='conf';
$f[]='install';
$f[]='conf/kasDatas';
$f[]='logs';
$f[]='profiles';
$f[]='sessions';
$f[]='sessions/SessionData';
$f[]='userdb';
$f[]='databases';



  while (list ($num, $dir) = each ($f) ){
  	$dirname="$artica_path/computers/ressources/$dir";
  	if(!is_dir($dirname)){@mkdir($dirname,0755,true);}
  	@chown($dirname, $username);
  	@chgrp($dirname, $groupname);
  	@chmod("$dirname",0755);
  	
  	$dirname="$artica_path/user-backup/ressources/$dir";
  	if(!is_dir($dirname)){@mkdir($dirname,0755,true);}
  	@chown($dirname, $username);
  	@chgrp($dirname, $groupname);
  	@chmod("$dirname",0755); 	

  	$dirname="$artica_path/ressources/$dir";
  	if(!is_dir($dirname)){@mkdir($dirname,0755,true);}
  	@chown($dirname, $username);
  	@chgrp($dirname, $groupname);
  	@chmod("$dirname",0755);   	
  	
  }
  
  
$f=array();
$f[]='/ressources/conf';
$f[]='/ressources/logs';
$f[]='/var/run/lighttpd';
$f[]='/ressources/databases';
$f[]='/ressources/install';
$f[]='/ressources/profiles';
$f[]='/ressources/sessions';
$f[]='/computers/ressources/sessions';
$f[]='/computers/ressources/logs';
$f[]="/ressources/isoqlog";




  while (list ($num, $dir) = each ($f) ){
  	$unix->chown_func($username, $groupname,"$artica_path$dir/*");
  }

$ToCreateDefault[]="/opt/artica/amavis-hooks";
$ToCreateDefault[]="/opt/artica/philesight";
$ToCreateDefault[]="/opt/artica/share/www/jpegPhoto";
  while (list ($num, $dir) = each ($ToCreateDefault) ){
  	if(!is_dir($dir)){@mkdir($dir,0755,true);}
  	
  }
$unix->chown_func("postfix", "postfix","/opt/artica/amavis-hooks");
$unix->chown_func("root", "root","/etc/cron.d/*");
$unix->chown_func($username, $groupname,"$artica_path/ressources/userdb/*");
$unix->chown_func($username, $groupname,"$artica_path/*");
$unix->chown_func($username, $groupname,"/var/lib/php5/*");
$unix->chown_func($username, $groupname,"/opt/artica/share/www/jpegPhoto/*");


$unix->chmod_func(0640,"etc/cron.d");
$unix->chmod_func(0755,"$artica_path/ressources/databases");
$unix->chmod_func(0777,"$artica_path/ressources/userdb");
$unix->chmod_func(0777,"$artica_path/ressources/logs");
$unix->chmod_func(0777,"/var/lib/php5/*");
$unix->chmod_func(0755,"/var/log/squid/access.log");

if(is_file('/var/run/memcached.sock')){@chmod("/var/run/memcached.sock",0777);}
if(is_dir("/etc/ssh")){$unix->chmod_func(0640,"etc/ssh");}
if(is_dir("/usr/share/pommo")){
	$unix->chown_func($username, $groupname,"/usr/share/pommo/*");
	$unix->chmod_func(0755,"/usr/share/pommo/*");
}

  if(is_dir("/usr/share/zarafa-webaccess")){@mkdir("/var/lib/zarafa-webaccess/tmp",0755,true);}   
  $unix->chmod_func(0777,"/usr/share/artica-postfix/user-backup/ressources/conf");
  if(!is_dir("/usr/share/artica-postfix/ressources/logs/web/queue/sessions")){
  @mkdir("/usr/share/artica-postfix/ressources/logs/web/queue/sessions",0755,true);}
  $unix->chmod_func(0755,"/usr/share/artica-postfix/bin/*");
  $unix->chmod_func(0644,"/usr/share/artica-postfix/bin/install/amavis/check-external-users.conf");
  $unix->chown_func("root","root", "/usr/share/artica-postfix/bin/*");
  $unix->chown_func("root","root", "/usr/share/artica-postfix/bin/install/amavis");
  $unix->chown_func($username,$groupname, "/var/lib/php/session/*");
  $unix->chown_func($username,$groupname, "/var/lib/php5/*");
  $unix->chown_func($username,$groupname, "/var/lighttpd/upload");
  $unix->chown_func("mysql","mysql", "/var/lib/mysql/*");
  $unix->chown_func("mysql","mysql", "/var/lib/mysql/apachelogs/*");
  $unix->chown_func("mysql","mysql", "/var/lib/mysql/artica_backup/*");
  $unix->chown_func("mysql","mysql", "/var/lib/mysql/artica_events/*");
  $unix->chown_func("mysql","mysql", "/var/lib/mysql/blackboxes/*");
  $unix->chown_func("mysql","mysql", "/var/lib/mysql/ocsweb/*");
  $unix->chown_func("mysql","mysql", "/var/lib/mysql/policyd/*");
  $unix->chown_func("mysql","mysql", "/var/lib/mysql/postfilter/*");
  $unix->chown_func("mysql","mysql", "/var/lib/mysql/postfixlog/*");
  $unix->chown_func("mysql","mysql", "/var/lib/mysql/powerdns/*");
  $unix->chown_func("mysql","mysql", "/var/lib/mysql/roundcubemail/*");
  $unix->chown_func("mysql","mysql", "/var/lib/mysql/snort/*");
  $unix->chown_func("mysql","mysql", "/var/lib/mysql/squidlogs/*");
  $unix->chown_func("mysql","mysql", "/var/lib/mysql/sugarcrm/*");
   
$postconf=$unix->find_program("postconf");
$ln=$unix->find_program("ln");

if(is_file($postconf)){
	if(!is_dir("/var/lib/postfix")){@mkdir("/var/lib/postfix",0755,true);}
	if(!is_dir("/var/spool/postfix/var")){@mkdir("/var/spool/postfix/var",0755,true);}
	if(!is_dir("/opt/artica/mimedefang-hooks")){@mkdir("/opt/artica/mimedefang-hooks",0755,true);}
	if(!is_dir("/var/mail/artica-wbl")){@mkdir("/var/mail/artica-wbl",0755,true);}
	$unix->chown_func("postfix", "postfix","/var/lib/postfix/*");
	$unix->chown_func("postfix", "postfix","/opt/artica/mimedefang-hooks/*");
	$unix->chown_func("mail", "root","/var/mail/artica-wbl/*");
	shell_exec("$ln -s --force /var/run /var/spool/postfix/var/run");
	if(is_dir('/var/run/saslauthd')){if(is_file('/var/run/saslauthd/mux')){$unix->chown_func('postfix','mail','/var/run/saslauthd');}}
	$CopyToDomainSpool=$sock->GET_INFO('CopyToDomainSpool');
	if(strlen($CopyToDomainSpool)==0){$CopyToDomainSpool='/var/spool/artica/copy-to-domain';}
	@mkdir($CopyToDomainSpool,0755,true);
	$unix->chown_func("postfix", "postfix","$CopyToDomainSpool/*");
	if(is_file('/var/spool/postfix/var/run/amavisd-milter/amavisd-milter.sock')){$unix->chown_func("postfix", "postfix","/var/spool/postfix/var/run/amavisd-milter/amavisd-milter.sock");}
	if(is_file('/usr/local/etc/altermime-disclaimer.txt')){$unix->chown_func("postfix", "postfix","/usr/local/etc/altermime-disclaimer.txt");}


}

$MySQLTMPDIR=trim($sock->GET_INFO("MySQLTMPDIR"));
if($MySQLTMPDIR<>null){
	if($MySQLTMPDIR<>"/tmp"){
		if(strlen($MySQLTMPDIR)>3){
			if(!is_dir("MySQLTMPDIR")){@mkdir($MySQLTMPDIR,0777);}
			$unix->chmod_func(0777, $MySQLTMPDIR);
			$unix->chown_func("mysql","mysql", $MySQLTMPDIR);
		}
	}
}

foreach (glob("/usr/share/artica-postfix/*") as $filename) {
	if(is_numeric(basename($filename))){@unlink($filename);}
}






?>