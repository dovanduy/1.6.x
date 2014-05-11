<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.pdns.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
$GLOBALS["SHOWKEYS"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
if(preg_match("#--showkeys#",implode(" ",$argv))){$GLOBALS["SHOWKEYS"]=true;}


if($argv[1]=="--mysql"){checkMysql();exit;}
if($argv[1]=="--poweradmin"){poweradmin();exit;}
if($argv[1]=="--dnsseck"){dnsseck();exit;}
if($argv[1]=="--reload"){reload_service();exit;}
if($argv[1]=="--rebuild-database"){rebuild_database();exit;}
if($argv[1]=="--replic-artica"){replic_artica_servers();exit;}
if($argv[1]=="--allow-recursion"){allow_recursion();exit;}
if($argv[1]=="--start-recursor"){start_recursor();exit;}
if($argv[1]=="--stop-recursor"){stop_recursor();exit;}
if($argv[1]=="--listen-ips"){listen_ips();exit;}
if($argv[1]=="--wizard-on"){wizard_on();exit;}


function poweradmin(){
if(!is_file("/usr/share/poweradmin/index.php")){
	echo "Starting......: ".date("H:i:s")." PowerAdmin is not installed\n";
	return;
}

$q=new mysql();
$unix=new unix();
$f[]="<?php";
$f[]="\$db_host		= '$q->mysql_server';";
$f[]="\$db_user		= '$q->mysql_admin';";
$f[]="\$db_pass		= '$q->mysql_password';";
$f[]="\$db_name		= 'powerdns';";
$f[]="\$db_port		= '$q->mysql_port';";
$f[]="\$db_type		= 'mysql';";
$f[]="\$iface_lang		= 'en_EN';";
$f[]="\$cryptokey		= '".$unix->hostname_g()."';";
$f[]="\$session_key		= '". $unix->hostname_g()."';";
$f[]="\$password_encryption	= 'md5';	// or md5salt";
$f[]="\$iface_style		= 'example';";
$f[]="\$iface_rowamount	= 50;";
$f[]="\$iface_expire	= 1800;";
$f[]="\$iface_zonelist_serial	= false;";
$f[]="\$iface_title = 'Poweradmin For Artica';";
$f[]="\$password_encryption='md5';";
$f[]="\$dns_ttl		= 86400;";
$f[]="\$dns_fancy	= false;";
$f[]="\$dns_strict_tld_check	= true;";
$f[]="\$dns_hostmaster		= 'hostmaster.example.net';";
$f[]="\$dns_ns1		= 'ns1.example.net';";
$f[]="\$dns_ns2		= 'ns2.example.net';";
$f[]="\$syslog_use  = True;";
$f[]="\$syslog_ident = 'poweradmin';";
$f[]="\$syslog_facility = LOG_USER;";
$f[]="?>";

$sql="DELETE FROM users WHERE id=1";
$q->QUERY_SQL($sql,"powerdns");
$ldap=new clladp();
$pass=md5($ldap->ldap_password);

$sql="SELECT password,fullname,email FROM `users` WHERE id=1";
$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"powerdns"));
if($ligne["password"]<>null){
	$sql="UPDATE `users` SET `username`= '$ldap->ldap_admin',`password`='$pass' ,`perm_templ`=1,`active`=1 WHERE id=1";
	
}else{
	$sql="INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `email`, `description`, `perm_templ`, `active`) VALUES
	(1, '$ldap->ldap_admin', '$pass', 'Administrator', 'admin@example.net', 'Administrator with full rights.', 1, 1);";
}
$q->QUERY_SQL($sql,"powerdns");
if(!$q->ok){echo "Starting......: ".date("H:i:s")." PowerAdmin $ldap->ldap_admin failed $q->mysql_error\n";}else{
	echo "Starting......: ".date("H:i:s")." PowerAdmin $ldap->ldap_admin ok\n";
}

@file_put_contents("/usr/share/poweradmin/inc/config.inc.php", @implode("\n", $f));	
echo "Starting......: ".date("H:i:s")." PowerAdmin config.inc.php done\n";
if(is_dir("/usr/share/poweradmin/install")){shell_exec("/bin/rm -rf /usr/share/poweradmin/install >/dev/null 2>&1");}

}

function rebuild_database($nollop=false){
	$unix=new unix();
	$MYSQL_DATA_DIR=$unix->MYSQL_DATA_DIR();
	echo "Starting......: ".date("H:i:s")." PowerDNS destroy database and recreate it\n";
	$q=new mysql();
	$q->DELETE_DATABASE("powerdns");
	$rm=$unix->find_program("rm");
	if(is_dir("$MYSQL_DATA_DIR/powerdns")){
		echo "Starting......: ".date("H:i:s")." PowerDNS removing $MYSQL_DATA_DIR/powerdns\n";
		shell_exec("$rm -rf $MYSQL_DATA_DIR/powerdns");
	}
	checkMysql($nollop);
	shell_exec("/etc/init.d/artica-postfix restart pdns");
}

function checkMysql($nollop=false){
	$unix=new unix();
	
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($unix->file_time_min($timefile)<1){
		echo "Starting......: ".date("H:i:s")." PowerDNS need at least 1mn, aborting\n";
		return;
	}
	
	
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$passwdcmdline=null;
	$mysql=$unix->find_program("mysql");
	$q=new mysql();
	
	if(!$q->TestingConnection(true)){
		echo "Starting......: ".date("H:i:s")." PowerDNS creating, MySQL seems not ready..\n";
		return;
	}
	
	forward_zones();
	
	
	if(!$q->DATABASE_EXISTS("powerdns")){
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'powerdns' database\n";
		if(!$q->CREATE_DATABASE("powerdns")){echo "Starting......: ".date("H:i:s")." PowerDNS creating 'powerdns' database failed\n"; return;}
	}

	echo "Starting......: ".date("H:i:s")." PowerDNS 'powerdns' database OK\n";

$f["cryptokeys"]=true;
$f["domainmetadata"]=true;
$f["domains"]=true;
$f["perm_items"]=true;
$f["perm_templ"]=true;
$f["perm_templ_items"]=true;
$f["records"]=true;
$f["supermasters"]=true;
$f["tsigkeys"]=true;
$f["users"]=true;
$f["zones"]=true;
$f["zone_templ"]=true;
$f["zone_templ_records"]=true;
$resultTables=true;
while (list ($tablename, $line2) = each ($f) ){
	if(!$q->TABLE_EXISTS($tablename, "powerdns")){echo "Starting......: ".date("H:i:s")." PowerDNS Table `$tablename` failed...\n";$resultTables=false;continue;}
	echo "Starting......: ".date("H:i:s")." PowerDNS Table `$tablename` OK...\n";
}

if($resultTables){
	echo "Starting......: ".date("H:i:s")." PowerDNS pass tests Success...\n";
	return true;
}
$dumpfile="/usr/share/artica-postfix/bin/install/pdns/powerdns.sql";
if(!is_file($dumpfile)){
	echo "Starting......: ".date("H:i:s")." PowerDNS /usr/share/artica-postfix/bin/install/pdns/powerdns.sql no such file...\n";
	return;
}

echo "Starting......: ".date("H:i:s")." PowerDNS installing database...\n";
if($q->mysql_password<>null){$passwdcmdline=" -p$q->mysql_password";}
$cmd="$mysql -B -u $q->mysql_admin$passwdcmdline --database=powerdns -E < $dumpfile >/dev/null 2>&1";
shell_exec($cmd);
reset($f);

$resultTables=true;
while (list ($tablename, $line2) = each ($f) ){
	if(!$q->TABLE_EXISTS($tablename, "powerdns")){echo "Starting......: ".date("H:i:s")." PowerDNS Table `$tablename` failed...\n";$resultTables=false;continue;}
	echo "Starting......: ".date("H:i:s")." PowerDNS Table `$tablename` OK...\n";
}
if($resultTables){echo "Starting......: ".date("H:i:s")." PowerDNS Success...\n";return true;}




	if(!$q->TABLE_EXISTS("domains", "powerdns")){
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'domains' table\n";
		$sql="CREATE TABLE IF NOT EXISTS domains (
			 id		 INT auto_increment,
			 name		 VARCHAR(255) NOT NULL,
			 master		 VARCHAR(128) DEFAULT NULL,
			 last_check	 INT DEFAULT NULL,
			 type		 VARCHAR(6) NOT NULL,
			 notified_serial INT DEFAULT NULL, 
			 account         VARCHAR(40) DEFAULT NULL,
			 primary key (id)
			) Engine=InnoDB;";
			$q->QUERY_SQL($sql,"powerdns");
			if(!$q->ok){echo "Starting......: ".date("H:i:s")." PowerDNS creating 'domains' table FAILED\n";}else{return;}
			echo "Starting......: ".date("H:i:s")." PowerDNS table 'domains' Success\n";
		}else{
			echo "Starting......: ".date("H:i:s")." PowerDNS table 'domains' Success\n";
			$q->QUERY_SQL("CREATE UNIQUE INDEX name_index ON domains(name);","powerdns");
		}
		
	

	if(!$q->TABLE_EXISTS("records", "powerdns")){
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'records' table\n";
		$sql="CREATE TABLE IF NOT EXISTS records (
			  id              INT auto_increment,
			  domain_id       INT DEFAULT NULL,
			  name            VARCHAR(255) DEFAULT NULL,
			  type            VARCHAR(10) DEFAULT NULL,
			  content         VARCHAR(255) DEFAULT NULL,
			  ttl             INT DEFAULT NULL,
			  prio            INT DEFAULT NULL,
			  change_date     INT DEFAULT NULL,
			  explainthis     VARCHAR(255) DEFAULT NULL,
			  primary key(id)
			)Engine=InnoDB;";
			$q->QUERY_SQL($sql,"powerdns");
			if(!$q->ok){
				echo "Starting......: ".date("H:i:s")." PowerDNS creating 'records' table FAILED\n";
				return;
			}
			
			$q->QUERY_SQL("CREATE INDEX rec_name_index ON records(name);","powerdns");
			$q->QUERY_SQL("CREATE INDEX nametype_index ON records(name,type);","powerdns");
			$q->QUERY_SQL("CREATE INDEX domain_id ON records(domain_id);","powerdns");
			$q->QUERY_SQL("alter table records add ordername VARCHAR(255);","powerdns");
			$q->QUERY_SQL("alter table records add auth bool;","powerdns");
			$q->QUERY_SQL("create index orderindex on records(ordername);","powerdns");
			$q->QUERY_SQL("alter table records change column type type VARCHAR(10);","powerdns");			
			echo "Starting......: ".date("H:i:s")." PowerDNS creating 'records' table success\n";
			
		}else{
			echo "Starting......: ".date("H:i:s")." PowerDNS creating 'records' table success\n";
	
		}
		
	


	if(!$q->TABLE_EXISTS("supermasters", "powerdns")){
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'supermasters' table\n";
		$sql="CREATE TABLE IF NOT EXISTS supermasters (
				  ip VARCHAR(25) NOT NULL, 
				  nameserver VARCHAR(255) NOT NULL, 
				  account VARCHAR(40) DEFAULT NULL
				) Engine=InnoDB;";
		$q->QUERY_SQL($sql,"powerdns");
		if(!$q->ok){
			echo "Starting......: ".date("H:i:s")." PowerDNS creating 'supermasters' table FAILED\n";
			return;
		}
			$q->QUERY_SQL("CREATE INDEX rec_name_index ON records(name);","powerdns");
			$q->QUERY_SQL("CREATE INDEX nametype_index ON records(name,type);","powerdns");
			$q->QUERY_SQL("CREATE INDEX domain_id ON records(domain_id);","powerdns");
			echo "Starting......: ".date("H:i:s")." PowerDNS creating 'supermasters' table success\n";
	}else{
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'supermasters' table success\n";
	}
	
	
	
	
	if(!$q->TABLE_EXISTS("domainmetadata", "powerdns")){
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'domainmetadata' table\n";
		$sql="CREATE TABLE IF NOT EXISTS domainmetadata (
			 id              INT auto_increment,
			 domain_id       INT NOT NULL,
			 kind            VARCHAR(16),
			 content        TEXT,
			 primary key(id)
			);";
		$q->QUERY_SQL($sql,"powerdns");
		if(!$q->ok){
			echo "Starting......: ".date("H:i:s")." PowerDNS creating 'domainmetadata' table FAILED\n";
			return;
		}
			echo "Starting......: ".date("H:i:s")." PowerDNS creating 'domainmetadata' table success\n";
			$q->QUERY_SQL("create index domainmetaidindex on domainmetadata(domain_id);","powerdns");  
	}else{
		echo "Starting......: ".date("H:i:s")." PowerDNS 'domainmetadata' table success\n";
	}

	
	
	
	if(!$q->TABLE_EXISTS("cryptokeys", "powerdns")){
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'cryptokeys' table\n";
		$sql="CREATE TABLE IF NOT EXISTS cryptokeys (
			 id             INT auto_increment,
			 domain_id      INT NOT NULL,
			 flags          INT NOT NULL,
			 active         BOOL,
			 content        TEXT,
			 primary key(id)
			); ";
	$q->QUERY_SQL($sql,"powerdns");
		if(!$q->ok){
			echo "Starting......: ".date("H:i:s")." PowerDNS creating 'cryptokeys' table FAILED\n";
			return;
		}
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'cryptokeys' table success\n";
		$q->QUERY_SQL("create index domainidindex on cryptokeys(domain_id);","powerdns");
	}else{
			
		echo "Starting......: ".date("H:i:s")." PowerDNS 'cryptokeys' table success\n";
	}
		
			

	if(!$q->TABLE_EXISTS("tsigkeys", "powerdns")){
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'tsigkeys' table\n";
		$sql="CREATE TABLE IF NOT EXISTS tsigkeys (
			 id             INT auto_increment,
			 name           VARCHAR(255), 
			 algorithm      VARCHAR(255),
			 secret         VARCHAR(255),
			 primary key(id)
			);";
		$q->QUERY_SQL($sql,"powerdns");
		if(!$q->ok){
			echo "Starting......: ".date("H:i:s")." PowerDNS creating 'tsigkeys' table FAILED\n";
			return;
		}
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'tsigkeys' table success\n";
		$q->QUERY_SQL("create unique index namealgoindex on tsigkeys(name, algorithm);","powerdns");
		
	}else{
		
		echo "Starting......: ".date("H:i:s")." PowerDNS 'tsigkeys' table success\n";
	}



	if(!$q->TABLE_EXISTS("users", "powerdns")){
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'users' table\n";
		$sql="CREATE TABLE IF NOT EXISTS `users` ( `id` int(11) NOT NULL AUTO_INCREMENT, `username` varchar(16) NOT NULL DEFAULT '0', `password` varchar(34) NOT NULL DEFAULT '0', `fullname` varchar(255) NOT NULL DEFAULT '0', `email` varchar(255) NOT NULL DEFAULT '0', `description` varchar(1024) NOT NULL DEFAULT '0', `perm_templ` tinyint(4) NOT NULL DEFAULT '0', `active` tinyint(4) NOT NULL DEFAULT '0', PRIMARY KEY (`id`))"; 
		$q->QUERY_SQL($sql,"powerdns");
		if(!$q->ok){ echo "Starting......: ".date("H:i:s")." PowerDNS creating 'users' table FAILED\n"; return; }
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'users' table success\n";
	}else{
		echo "Starting......: ".date("H:i:s")." PowerDNS 'users' table success\n";
		
	}
	
	
	if(!$q->TABLE_EXISTS("perm_items", "powerdns")){
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'perm_items' table\n";
		$sql="CREATE TABLE IF NOT EXISTS `perm_items` ( `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(64) NOT NULL DEFAULT '0', `descr` varchar(1024) NOT NULL DEFAULT '0', PRIMARY KEY (`id`) ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=62 ;";
		$q->QUERY_SQL($sql,"powerdns");
		if(!$q->ok){ echo "Starting......: ".date("H:i:s")." PowerDNS creating 'perm_items' table FAILED\n"; return; }
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'perm_items' table success\n";
		$sql="INSERT INTO `perm_items` (`id`, `name`, `descr`) VALUES (41, 'zone_master_add', 'User is allowed to add new master zones.'), (42, 'zone_slave_add', 'User is allowed to add new slave zones.'), (43, 'zone_content_view_own', 'User is allowed to see the content and meta data of zones he owns.'), (44, 'zone_content_edit_own', 'User is allowed to edit the content of zones he owns.'), (45, 'zone_meta_edit_own', 'User is allowed to edit the meta data of zones he owns.'), (46, 'zone_content_view_others', 'User is allowed to see the content and meta data of zones he does not own.'), (47, 'zone_content_edit_others', 'User is allowed to edit the content of zones he does not own.'), (48, 'zone_meta_edit_others', 'User is allowed to edit the meta data of zones he does not own.'), (49, 'search', 'User is allowed to perform searches.'), (50, 'supermaster_view', 'User is allowed to view supermasters.'), (51, 'supermaster_add', 'User is allowed to add new supermasters.'), (52, 'supermaster_edit', 'User is allowed to edit supermasters.'), (53, 'user_is_ueberuser', 'User has full access. God-like. Redeemer.'), (54, 'user_view_others', 'User is allowed to see other users and their details.'), (55, 'user_add_new', 'User is allowed to add new users.'), (56, 'user_edit_own', 'User is allowed to edit their own details.'), (57, 'user_edit_others', 'User is allowed to edit other users.'), (58, 'user_passwd_edit_others', 'User is allowed to edit the password of other users.'), (59, 'user_edit_templ_perm', 'User is allowed to change the permission template that is assigned to a user.'), (60, 'templ_perm_add', 'User is allowed to add new permission templates.'), (61, 'templ_perm_edit', 'User is allowed to edit existing permission templates.');";
		$q->QUERY_SQL($sql,"powerdns");
	}else{
		
		echo "Starting......: ".date("H:i:s")." PowerDNS 'perm_items' table success\n";
	}
	
	
	if(!$q->TABLE_EXISTS("perm_templ", "powerdns")){
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'perm_templ' table\n";
		$sql="CREATE TABLE IF NOT EXISTS `perm_templ` ( `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(128) NOT NULL DEFAULT '0', `descr` varchar(1024) NOT NULL DEFAULT '0', PRIMARY KEY (`id`) ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;";
		$q->QUERY_SQL($sql,"powerdns");
		if(!$q->ok){
			echo "Starting......: ".date("H:i:s")." PowerDNS creating 'perm_templ' table FAILED\n";
		}else{
			$sql="INSERT INTO `perm_templ` (`id`, `name`, `descr`) VALUES (1, 'Administrator', 'Administrator template with full rights.');";
			$q->QUERY_SQL($sql,"powerdns");
		}
	}
	
	if(!$q->TABLE_EXISTS("perm_templ_items", "powerdns")){
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'perm_templ_items' table\n";
		$sql="CREATE TABLE IF NOT EXISTS `perm_templ_items` ( `id` int(11) NOT NULL AUTO_INCREMENT, `templ_id` int(11) NOT NULL DEFAULT '0', `perm_id` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`) ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=250 ;";
		$q->QUERY_SQL($sql,"powerdns");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." PowerDNS creating 'perm_templ_items' table FAILED\n";return;}
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'perm_templ_items' table success\n";
		$sql="INSERT INTO `perm_templ_items` (`id`, `templ_id`, `perm_id`) VALUES (249, 1, 53);";
		$q->QUERY_SQL($sql,"powerdns");
	}else{
		echo "Starting......: ".date("H:i:s")." PowerDNS 'perm_templ_items' table success\n";
		
	}

	if(!$q->TABLE_EXISTS("zones", "powerdns")){
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'zones' table\n";
		$sql="CREATE TABLE IF NOT EXISTS `zones` ( `id` int(11) NOT NULL AUTO_INCREMENT, `domain_id` int(11) NOT NULL DEFAULT '0', `owner` int(11) NOT NULL DEFAULT '0', `comment` varchar(1024) DEFAULT '0', `zone_templ_id` int(11) NOT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";	
		$q->QUERY_SQL($sql,"powerdns");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." PowerDNS creating 'zones' table FAILED\n";return;}
	}else{
		echo "Starting......: ".date("H:i:s")." PowerDNS 'zones' table success\n";
		
	}
	
	if(!$q->TABLE_EXISTS("zone_templ", "powerdns")){
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'zone_templ' table\n";
		$sql="CREATE TABLE IF NOT EXISTS `zone_templ` ( `id` bigint(20) NOT NULL AUTO_INCREMENT, `name` varchar(128) NOT NULL DEFAULT '0', `descr` varchar(1024) NOT NULL DEFAULT '0', `owner` bigint(20) NOT NULL DEFAULT '0', PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";	
		$q->QUERY_SQL($sql,"powerdns");
		if(!$q->ok){
			echo "Starting......: ".date("H:i:s")." PowerDNS creating 'zone_templ' table FAILED\n";
			return;
		}
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'zone_templ' table success\n";
	}else{
		echo "Starting......: ".date("H:i:s")." PowerDNS 'zone_templ' table success\n";	
	}	
	
if(!$q->TABLE_EXISTS("zone_templ_records", "powerdns")){
		echo "Starting......: ".date("H:i:s")." PowerDNS creating 'zone_templ_records' table\n";
		$sql="CREATE TABLE IF NOT EXISTS `zone_templ_records` (
		  `id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `zone_templ_id` bigint(20) NOT NULL DEFAULT '0',
		  `name` varchar(255) NOT NULL DEFAULT '0',
		  `type` varchar(6) NOT NULL DEFAULT '0',
		  `content` varchar(255) NOT NULL DEFAULT '0',
		  `ttl` bigint(20) NOT NULL DEFAULT '0',
		  `prio` bigint(20) NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";	
		$q->QUERY_SQL($sql,"powerdns");
		if(!$q->ok){
			echo "Starting......: ".date("H:i:s")." PowerDNS creating 'zone_templ_records' table FAILED\n";
		}
	}

	if(!$q->TABLE_EXISTS("domainmetadata", "powerdns")){
		$q->QUERY_SQL("create table domainmetadata ( id INT auto_increment, domain_id INT NOT NULL, kind VARCHAR(16), content TEXT, primary key(id) );","powerdns");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." PowerDNS patching database/domainmetadata failed $q->mysql_error\n"; return;}
		echo "Starting......: ".date("H:i:s")." PowerDNS patching database/domainmetadata success\n";
		$q->QUERY_SQL("create index domainmetaidindex on domainmetadata(domain_id);","powerdns");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." PowerDNS patching database/domainmetadata failed $q->mysql_error\n"; }
		}
		else{
			echo "Starting......: ".date("H:i:s")." PowerDNS patching database/domainmetadata OK\n";
		}
	 

	
if(!$q->TABLE_EXISTS("cryptokeys", "powerdns")){
	
	$q->QUERY_SQL("create table cryptokeys (
	id             INT auto_increment,
 	domain_id      INT NOT NULL,
	flags          INT NOT NULL,
 	active         BOOL,
 	content        TEXT,
	primary key(id)
	);","powerdns");               
	if(!$q->ok){
		echo "Starting......: ".date("H:i:s")." PowerDNS patching database/cryptokeys failed $q->mysql_error\n";
	}else{
		echo "Starting......: ".date("H:i:s")." PowerDNS patching database/cryptokeys success\n";
		$q->QUERY_SQL("create index domainidindex on cryptokeys(domain_id);","powerdns");
		if(!$q->ok){
			echo "Starting......: ".date("H:i:s")." PowerDNS patching database/cryptokeys failed $q->mysql_error\n";
		}
	}
	
}else{
	echo "Starting......: ".date("H:i:s")." PowerDNS patching database/cryptokeys OK\n";
}	




if($q->TABLE_EXISTS("records", "powerdns")){
	if(!$q->FIELD_EXISTS("records","ordername","powerdns") ){
		$q->QUERY_SQL("alter table records add ordername  VARCHAR(255)","powerdns");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." PowerDNS patching database/records failed $q->mysql_error\n";}
		$q->QUERY_SQL("create index orderindex on records(ordername)","powerdns");
	}
	if(!$q->FIELD_EXISTS("records","auth","powerdns") ){
		$q->QUERY_SQL("alter table records add auth bool","powerdns");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." PowerDNS patching database/records failed $q->mysql_error\n";}
		
	}
	
	$q->QUERY_SQL("alter table records change column type type VARCHAR(10);","powerdns");
	
}           

if(!$q->TABLE_EXISTS("tsigkeys", "powerdns")){
	$q->QUERY_SQL("create table tsigkeys (
		 id             INT auto_increment,
		 name           VARCHAR(255), 
		 algorithm      VARCHAR(50),
		 secret         VARCHAR(255),
		 primary key(id)
		);","powerdns");               
	if(!$q->ok){
		echo "Starting......: ".date("H:i:s")." PowerDNS patching database/tsigkeys failed $q->mysql_error\n";
	}else{
		echo "Starting......: ".date("H:i:s")." PowerDNS patching database/tsigkeys success\n";
		$q->QUERY_SQL("create unique index namealgoindex on tsigkeys(name, algorithm);","powerdns");
		if(!$q->ok){
			echo "Starting......: ".date("H:i:s")." PowerDNS patching database/tsigkeys failed $q->mysql_error\n";
		}
	}
	
}else{
	echo "Starting......: ".date("H:i:s")." PowerDNS patching database/tsigkeys OK\n";
}	
	

echo "Starting......: ".date("H:i:s")." PowerDNS Mysql done...\n";
poweradmin();
}

function dnsseck(){
	
	$unix=new unix();
	$pdnssec=$unix->find_program("pdnssec");
	if(!is_file($pdnssec)){echo "Starting......: ".date("H:i:s")." PowerDNS pdnssec no such binary !!!\n";return;}
	$sql="SELECT id,name FROM domains";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'powerdns');
	if(!$q->ok){echo "$q->mysql_error\n";}
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		echo "Starting......: ".date("H:i:s")." PowerDNS pdnssec checking zone {$ligne["name"]}\n"; 
		if(!dnsseck_is_crypto($ligne["id"])){
			echo "Starting......: ".date("H:i:s")." PowerDNS pdnssec securing zone {$ligne["name"]}\n";
			shell_exec2("$pdnssec add-zone-key {$ligne["name"]} ksk >/dev/null 2>&1");
			shell_exec2("$pdnssec set-presigned {$ligne["name"]} >/dev/null 2>&1");
			
			
			if(!dnsseck_is_crypto($ligne["id"],$ligne["name"])){
				echo "Starting......: ".date("H:i:s")." PowerDNS pdnssec securing zone {$ligne["name"]} Failed\n";
				continue;
			}
		}
		
		$DOMAINSZ[$ligne["name"]]=true;
		shell_exec2("$pdnssec secure-zone {$ligne["name"]} >/dev/null 2>&1");
			
		
		
		
	}
	
	
	if(count($DOMAINSZ)>0){			
		
		while (list ($domain, $line2) = each ($DOMAINSZ) ){
			shell_exec2("$pdnssec rectify-zone $domain >/dev/null 2>&1");	
			shell_exec2("$pdnssec set-nsec3 $domain '1 1 1 ab' >/dev/null 2>&1");
		}
		
		
		reset($DOMAINSZ);
		
		while (list ($domain, $none) = each ($DOMAINSZ) ){
			$zones=array();
			$ok=false;
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." PowerDNS Execute `$pdnssec show-zone $domain 2>&1` in order to see results\n";}
			exec("$pdnssec show-zone $domain 2>&1",$zones);
			while (list ($num1, $line2) = each ($zones) ){
				if(preg_match("#Zone has.+?semantics#", $line2)){
					echo "Starting......: ".date("H:i:s")." PowerDNS pdnssec checking zone $domain OK\n";
					$ok=true;
					break;
				}
			}
			if(!$ok){echo "Starting......: ".date("H:i:s")." PowerDNS pdnssec checking zone $domain not secure...\n";}
		}
		
	}
	
	shell_exec2("$pdnssec rectify-all-zones >/dev/null 2>&1"); 

}
function shell_exec2($cmd){
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." PowerDNS Execute `$cmd`\n";}
	shell_exec($cmd);
	
}


function dnsseck_is_crypto($id,$domain=null){
	$q=new mysql();
	$mres=false;
	$sql="SELECT id,content FROM cryptokeys WHERE domain_id=$id";
	
	$results=$q->QUERY_SQL($sql,'powerdns');
	if(!$q->ok){echo "$q->mysql_error\n";}
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		
		if(strlen($ligne["content"])>50){
			if($GLOBALS["SHOWKEYS"]){
				echo "Here it is the crypted key for domain `$domain` ID:$id\n";
				echo "***************************************\n";
				echo $ligne["content"]."\n";
				echo "***************************************\n\n";
				
			}
			echo "Starting......: ".date("H:i:s")." PowerDNS pdnssec securing zone Already done with key [{$ligne["id"]}] ". strlen($ligne["content"]). " bytes\n";
			$mres=true;
		}
	}
return $mres;
}

function reload_service(){
	$sock=new sockets();
	$DisablePowerDnsManagement=$sock->GET_INFO("DisablePowerDnsManagement");
	$EnablePDNS=$sock->GET_INFO("EnablePDNS");
	if(!is_numeric($DisablePowerDnsManagement)){$DisablePowerDnsManagement=0;}
	if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
	$DHCPDEnableCacheDNS=$sock->GET_INFO("DHCPDEnableCacheDNS");
	if(!is_numeric($DHCPDEnableCacheDNS)){$DHCPDEnableCacheDNS=0;}
	if($DHCPDEnableCacheDNS==1){$EnablePDNS=0;}
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$pdns_server_bin=$unix->find_program("pdns_server");
	$pdns_recursor_bin=$unix->find_program("pdns_recursor");
	if($DisablePowerDnsManagement==1){echo "Starting......: ".date("H:i:s")." PowerDNS [reload]: management by artica is disabled\n";return;}
	if($EnablePDNS==0){echo "Starting......: ".date("H:i:s")." PowerDNS [reload]: is disabled EnablePDNS=$EnablePDNS\n";return;}
	if(!is_file($pdns_server_bin)){echo "Starting......: ".date("H:i:s")." PowerDNS [reload]: reloading pdns_server no such binary\n";return;}
		
	if(is_file($pdns_recursor_bin)){
		$recursor_pid=$unix->PIDOF($pdns_recursor_bin);
		if($unix->process_exists("$recursor_pid")){
			echo "Starting......: ".date("H:i:s")." PowerDNS [reload]: recursor pid $recursor_pid\n";
			shell_exec("$kill -HUP $recursor_pid");	
		}else{
			echo "Starting......: ".date("H:i:s")." PowerDNS [reload]: recursor not running failed\n";
		}
	}
	
	$pdns_server_pid=$unix->PIDOF($pdns_server_bin);
	if($unix->process_exists("$pdns_server_pid")){
		echo "Starting......: ".date("H:i:s")." PowerDNS [reload]: reloading pdns_server pid $pdns_server_pid\n";
		shell_exec("$kill -HUP $pdns_server_pid");	
	}else{
			echo "Starting......: ".date("H:i:s")." PowerDNS [reload]: pdns_server not running failed\n";
		}	
}


function create_rrd(){
$f[]="rrdtool create pdns_recursor.rrd -s 60";
$f[]="DS:questions:COUNTER:600:0:100000";
$f[]="DS:tcp-questions:COUNTER:600:0:100000";
$f[]="DS:cache-entries:GAUGE:600:0:U";
$f[]="DS:packetcache-entries:GAUGE:600:0:U";
$f[]="DS:throttle-entries:GAUGE:600:0:U";
$f[]="DS:concurrent-queries:GAUGE:600:0:50000";
$f[]="DS:noerror-answers:COUNTER:600:0:100000";
$f[]="DS:nxdomain-answers:COUNTER:600:0:100000";
$f[]="DS:servfail-answers:COUNTER:600:0:100000";
$f[]="DS:tcp-outqueries:COUNTER:600:0:100000";
$f[]="DS:outgoing-timeouts:COUNTER:600:0:100000";
$f[]="DS:throttled-out:COUNTER:600:0:100000";
$f[]="DS:nsspeeds-entries:GAUGE:600:0:U";
$f[]="DS:negcache-entries:GAUGE:600:0:U";
$f[]="DS:all-outqueries:COUNTER:600:0:100000";
$f[]="DS:cache-hits:COUNTER:600:0:100000";
$f[]="DS:cache-misses:COUNTER:600:0:100000";
$f[]="DS:packetcache-hits:COUNTER:600:0:100000";
$f[]="DS:packetcache-misses:COUNTER:600:0:100000";
$f[]="DS:answers0-1:COUNTER:600:0:100000";
$f[]="DS:answers1-10:COUNTER:600:0:100000";
$f[]="DS:answers10-100:COUNTER:600:0:100000";
$f[]="DS:answers100-1000:COUNTER:600:0:100000";
$f[]="DS:answers-slow:COUNTER:600:0:100000";
$f[]="DS:udp-overruns:COUNTER:600:0:100000";
$f[]="DS:qa-latency:GAUGE:600:0:10000000";
$f[]="DS:user-msec:COUNTER:600:0:64000";
$f[]="DS:uptime:GAUGE:600:0:U";
$f[]="DS:unexpected-packets:COUNTER:600:0:1000000";
$f[]="DS:resource-limits:COUNTER:600:0:1000000";
$f[]="DS:over-capacity-drops:COUNTER:600:0:1000000";
$f[]="DS:client-parse-errors:COUNTER:600:0:1000000";
$f[]="DS:server-parse-errors:COUNTER:600:0:1000000";
$f[]="DS:unauthorized-udp:COUNTER:600:0:1000000";
$f[]="DS:unauthorized-tcp:COUNTER:600:0:1000000";
$f[]="DS:sys-msec:COUNTER:600:0:6400";
$f[]="RRA:AVERAGE:0.5:1:9600 ";
$f[]="RRA:AVERAGE:0.5:4:9600";
$f[]="RRA:AVERAGE:0.5:24:6000 ";
$f[]="RRA:MAX:0.5:1:9600 ";
$f[]="RRA:MAX:0.5:4:9600";
$f[]="RRA:MAX:0.5:24:6000";
	
$f=array();
$f[]="#!/usr/bin/env bash";
$f[]="SOCKETDIR=/var/run/";
$f[]="TSTAMP=\$(date +%s)";
$f[]="";
$f[]="OS=`uname`";
$f[]="if [ \"\$OS\" == \"Linux\" ]";
$f[]="then";
$f[]="#    echo \"Using Linux netstat directive\"";
$f[]="    NETSTAT_GREP=\"packet receive error\"";
$f[]="elif [ \"\$OS\" == \"FreeBSD\" ]";
$f[]="then";
$f[]="#    echo \"Using FreeBSD netstat directive\"";
$f[]="    NETSTAT_GREP=\"dropped due to full socket buffers\"";
$f[]="else";
$f[]="    echo \"Unsupported OS found, please report to the PowerDNS team.\"";
$f[]="    exit 1";
$f[]="fi";
$f[]="";
$f[]="";
$f[]="VARIABLES=\"questions                    \ ";
$f[]="           tcp-questions                \ ";
$f[]="           cache-entries                \ ";
$f[]="           packetcache-entries          \ ";
$f[]="           concurrent-queries           \ ";
$f[]="	   nxdomain-answers             \ ";
$f[]="           noerror-answers              \ ";
$f[]="	   servfail-answers             \ ";
$f[]="           tcp-outqueries               \ ";
$f[]="	   outgoing-timeouts            \ ";
$f[]="           nsspeeds-entries             \ ";
$f[]="           negcache-entries             \ ";
$f[]="           all-outqueries               \ ";
$f[]="           throttled-out                \ ";
$f[]="	   packetcache-hits             \ ";
$f[]="           packetcache-misses           \ ";
$f[]="	   cache-hits                   \ ";
$f[]="           cache-misses                 \ ";
$f[]="           answers0-1                   \ ";
$f[]="           answers1-10                  \ ";
$f[]="           answers10-100                \ ";
$f[]="           answers100-1000              \ ";
$f[]="           answers-slow                 \ ";
$f[]=" 	   qa-latency                   \ ";
$f[]="           throttle-entries             \ ";
$f[]="           sys-msec user-msec           \ ";
$f[]="           unauthorized-udp             \ ";
$f[]="           unauthorized-tcp             \ ";
$f[]="           client-parse-errors          \ ";
$f[]="	   server-parse-errors          \ ";
$f[]="           uptime unexpected-packets    \ ";
$f[]="           resource-limits              \ ";
$f[]="           over-capacity-drops\"";
$f[]="";
$f[]="UVARIABLES=\$(echo \$VARIABLES | tr '[a-z]' '[A-Z]' | tr - _ )";
$f[]="";
$f[]="rec_control --socket-dir=\$SOCKETDIR  GET \$VARIABLES |";
$f[]="(";
$f[]="  for a in \$UVARIABLES";
$f[]="  do";
$f[]="	  read \$a";
$f[]="  done";
$f[]="  rrdtool update pdns_recursor.rrd  \ ";
$f[]="	-t \"udp-overruns:\"\$(for a in \$VARIABLES ";
$f[]="	do";
$f[]="		echo -n \$a:";
$f[]="	done | sed 's/:\$//' ) \ ";
$f[]="\$TSTAMP\$(";
$f[]="	echo -n : ";
$f[]="	netstat -s | grep \"\$NETSTAT_GREP\" | awk '{printf \$1}' ";
$f[]="	for a in \$UVARIABLES";
$f[]="	do";
$f[]="		echo -n :\${!a}";
$f[]="	done";
$f[]="	)";
$f[]=")";
$f[]="";	
$f=array();
$f[]="#!/bin/bash";
$f[]="WWWPREFIX=. ";
$f[]="WSIZE=800";
$f[]="HSIZE=250";
$f[]="";
$f[]="# only recent rrds offer slope-mode:";
$f[]="GRAPHOPTS=--slope-mode";
$f[]="";
$f[]="function makeGraphs()";
$f[]="{";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/questions-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-t \"Questions and answers per second\" \ ";
$f[]="	-v \"packets\" \ ";
$f[]="	DEF:questions=pdns_recursor.rrd:questions:AVERAGE  \ ";
$f[]="        DEF:nxdomainanswers=pdns_recursor.rrd:nxdomain-answers:AVERAGE \ ";
$f[]="        DEF:noerroranswers=pdns_recursor.rrd:noerror-answers:AVERAGE \ ";
$f[]="        DEF:servfailanswers=pdns_recursor.rrd:servfail-answers:AVERAGE \ ";
$f[]="        LINE1:questions#0000ff:\"questions/s\"\ ";
$f[]="        AREA:noerroranswers#00ff00:\"noerror answers/s\"  \ ";
$f[]="        STACK:nxdomainanswers#ffa500:\"nxdomain answers/s\"\ ";
$f[]="        STACK:servfailanswers#ff0000:\"servfail answers/s\" ";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/tcp-questions-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-t \"TCP questions and answers per second, unauthorized packets/s\" \ ";
$f[]="	-v \"packets\" \ ";
$f[]="	DEF:tcpquestions=pdns_recursor.rrd:tcp-questions:AVERAGE  \ ";
$f[]="	DEF:unauthudp=pdns_recursor.rrd:unauthorized-udp:AVERAGE  \  ";
$f[]="	DEF:unauthtcp=pdns_recursor.rrd:unauthorized-tcp:AVERAGE  \ ";
$f[]="        LINE1:tcpquestions#0000ff:\"tcp questions/s\" \ ";
$f[]="	LINE1:unauthudp#ff0000:\"udp unauth/s\"  \ ";
$f[]="        LINE1:unauthtcp#00ff00:\"tcp unauth/s\" ";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/packet-errors-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-t \"Packet errors per second\" \ ";
$f[]="	-v \"packets\" \ ";
$f[]="	DEF:clientparseerrors=pdns_recursor.rrd:client-parse-errors:AVERAGE  \  ";
$f[]="	DEF:serverparseerrors=pdns_recursor.rrd:server-parse-errors:AVERAGE  \ ";
$f[]="	DEF:unexpected=pdns_recursor.rrd:unexpected-packets:AVERAGE  \ ";
$f[]="	DEF:udpoverruns=pdns_recursor.rrd:udp-overruns:AVERAGE  \ ";
$f[]="        LINE1:clientparseerrors#0000ff:\"bad packets from clients\" \ ";
$f[]="        LINE1:serverparseerrors#00ff00:\"bad packets from servers\" \ ";
$f[]="        LINE1:unexpected#ff0000:\"unexpected packets from servers\" \ ";
$f[]="        LINE1:udpoverruns#ff00ff:\"udp overruns from remotes\"       ";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/limits-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-t \"Limitations per second\" \ ";
$f[]="	-v \"events\" \ ";
$f[]="	DEF:resourcelimits=pdns_recursor.rrd:resource-limits:AVERAGE  \ ";
$f[]="	DEF:overcapacities=pdns_recursor.rrd:over-capacity-drops:AVERAGE  \ ";
$f[]="        LINE1:resourcelimits#ff0000:\"outqueries dropped because of resource limits\" \ ";
$f[]="        LINE1:overcapacities#0000ff:\"questions dropped because of mthread limit\"      ";
$f[]="";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/latencies-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-t \"Questions answered within latency\" \ ";
$f[]="	-v \"questions\" \ ";
$f[]="	DEF:questions=pdns_recursor.rrd:questions:AVERAGE  \ ";
$f[]="        DEF:answers00=pdns_recursor.rrd:packetcache-hits:AVERAGE \ ";
$f[]="        DEF:answers01=pdns_recursor.rrd:answers0-1:AVERAGE \ ";
$f[]="        DEF:answers110=pdns_recursor.rrd:answers1-10:AVERAGE \ ";
$f[]="        DEF:answers10100=pdns_recursor.rrd:answers10-100:AVERAGE \ ";
$f[]="        DEF:answers1001000=pdns_recursor.rrd:answers100-1000:AVERAGE \ ";
$f[]="        DEF:answersslow=pdns_recursor.rrd:answers-slow:AVERAGE \ ";
$f[]="        LINE1:questions#0000ff:\"questions/s\" \ ";
$f[]="        AREA:answers00#00ff00:\"<<1 ms\" \ ";
$f[]="        STACK:answers01#00fff0:\"<1 ms\" \ ";
$f[]="        STACK:answers110#0000ff:\"<10 ms\" \ ";
$f[]="        STACK:answers10100#ff9900:\"<100 ms\" \ ";
$f[]="        STACK:answers1001000#ffff00:\"<1000 ms\" \ ";
$f[]="        STACK:answersslow#ff0000:\">1000 ms\"       ";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/qoutq-\$2.png -w \$WSIZE -h \$HSIZE -l 0 \ ";
$f[]="	-t \"Questions/outqueries per second\" \ ";
$f[]="	-v \"packets\" \ ";
$f[]="	DEF:questions=pdns_recursor.rrd:questions:AVERAGE  \ ";
$f[]="        DEF:alloutqueries=pdns_recursor.rrd:all-outqueries:AVERAGE \ ";
$f[]="        LINE1:questions#ff0000:\"questions/s\"\ ";
$f[]="        LINE1:alloutqueries#00ff00:\"outqueries/s\" ";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/qa-latency-\$2.png -w \$WSIZE -h \$HSIZE -l 0 \ ";
$f[]="	-t \"Questions/answer latency in milliseconds\" \ ";
$f[]="	-v \"msec\" \ ";
$f[]="	DEF:qalatency=pdns_recursor.rrd:qa-latency:AVERAGE  \ ";
$f[]="	CDEF:mqalatency=qalatency,1000,/ \ ";
$f[]="        LINE1:mqalatency#ff0000:\"questions/s\" ";
$f[]="";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/timeouts-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-t \"Outqueries/timeouts per second\" \ ";
$f[]="	-v \"events\" \ ";
$f[]="	DEF:alloutqueries=pdns_recursor.rrd:all-outqueries:AVERAGE  \ ";
$f[]="        DEF:outgoingtimeouts=pdns_recursor.rrd:outgoing-timeouts:AVERAGE \ ";
$f[]="        DEF:throttledout=pdns_recursor.rrd:throttled-out:AVERAGE \ ";
$f[]="        LINE1:alloutqueries#ff0000:\"outqueries/s\"\ ";
$f[]="        LINE1:outgoingtimeouts#00ff00:\"outgoing timeouts/s\"\ ";
$f[]="        LINE1:throttledout#0000ff:\"throttled outqueries/s\" ";
$f[]="	";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/caches-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-t \"Cache sizes\" \ ";
$f[]="	-v \"entries\" \  ";
$f[]="	DEF:cacheentries=pdns_recursor.rrd:cache-entries:AVERAGE  \ ";
$f[]="	DEF:packetcacheentries=pdns_recursor.rrd:packetcache-entries:AVERAGE  \ ";
$f[]="	DEF:negcacheentries=pdns_recursor.rrd:negcache-entries:AVERAGE  \ ";
$f[]="	DEF:nsspeedsentries=pdns_recursor.rrd:nsspeeds-entries:AVERAGE  \ ";
$f[]="	DEF:throttleentries=pdns_recursor.rrd:throttle-entries:AVERAGE  \ ";
$f[]="        LINE1:cacheentries#ff0000:\"cache entries\" \ ";
$f[]="        LINE1:packetcacheentries#ffff00:\"packet cache entries\" \ ";
$f[]="        LINE1:negcacheentries#0000ff:\"negative cache entries\" \ ";
$f[]="        LINE1:nsspeedsentries#00ff00:\"NS speeds entries\" \ ";
$f[]="        LINE1:throttleentries#00fff0:\"throttle map entries\" ";
$f[]="        ";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/caches2-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-t \"Cache sizes\" \ ";
$f[]="	-v \"entries\" \ ";
$f[]="	DEF:negcacheentries=pdns_recursor.rrd:negcache-entries:AVERAGE  \ ";
$f[]="	DEF:nsspeedsentries=pdns_recursor.rrd:nsspeeds-entries:AVERAGE  \ ";
$f[]="	DEF:throttleentries=pdns_recursor.rrd:throttle-entries:AVERAGE  \ ";
$f[]="        LINE1:negcacheentries#0000ff:\"negative cache entries\" \ ";
$f[]="        LINE1:nsspeedsentries#00ff00:\"NS speeds entries\" \ ";
$f[]="        LINE1:throttleentries#ffa000:\"throttle map entries\" ";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/load-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-v \"MThreads\" \ ";
$f[]="	-t \"Concurrent queries\" \ ";
$f[]="	DEF:concurrentqueries=pdns_recursor.rrd:concurrent-queries:AVERAGE  \ ";
$f[]="        LINE1:concurrentqueries#0000ff:\"concurrent queries\" ";
$f[]="        ";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/hitrate-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-v \"percentage\" \ ";
$f[]="	-t \"cache hits\" \ ";
$f[]="	DEF:cachehits=pdns_recursor.rrd:cache-hits:AVERAGE  \ ";
$f[]="	DEF:cachemisses=pdns_recursor.rrd:cache-misses:AVERAGE  \ ";
$f[]="	DEF:packetcachehits=pdns_recursor.rrd:packetcache-hits:AVERAGE  \ ";
$f[]="	DEF:packetcachemisses=pdns_recursor.rrd:packetcache-misses:AVERAGE  \ ";
$f[]="	CDEF:perc=cachehits,100,*,cachehits,cachemisses,+,/ \ ";
$f[]="	CDEF:packetperc=packetcachehits,100,*,packetcachehits,packetcachemisses,+,/ \ ";
$f[]="        LINE1:perc#0000ff:\"percentage cache hits\"  \  ";
$f[]="        LINE1:packetperc#ff00ff:\"percentage packetcache hits\"  \ ";
$f[]="        COMMENT:\"\l\" \ ";
$f[]="        COMMENT:\"Cache hits \" \ ";
$f[]="        GPRINT:perc:AVERAGE:\"avg %-3.1lf%%\t\" \  ";
$f[]="        GPRINT:perc:LAST:\"last %-3.1lf%%\t\" \ ";
$f[]="        GPRINT:perc:MAX:\"max %-3.1lf%%\" \ ";
$f[]="        COMMENT:\"\l\" \ ";
$f[]="        COMMENT:\"Pkt hits   \" \ ";
$f[]="        GPRINT:packetperc:AVERAGE:\"avg %-3.1lf%%\t\" \ ";
$f[]="        GPRINT:packetperc:LAST:\"last %-3.1lf%%\t\" \ ";
$f[]="        GPRINT:packetperc:MAX:\"max %-3.1lf%%\" \ ";
$f[]="        COMMENT:\"\l\" ";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/cpuload-\$2.png -w \$WSIZE -h \$HSIZE -l 0\  ";
$f[]="	-v \"percentage\" \ ";
$f[]="	-t \"cpu load\" \ ";
$f[]="	DEF:usermsec=pdns_recursor.rrd:user-msec:AVERAGE \ ";
$f[]="	DEF:sysmsec=pdns_recursor.rrd:sys-msec:AVERAGE \ ";
$f[]="	DEF:musermsec=pdns_recursor.rrd:user-msec:MAX \ ";
$f[]="	DEF:msysmsec=pdns_recursor.rrd:sys-msec:MAX \ ";
$f[]="	CDEF:userperc=usermsec,10,/ \ ";
$f[]="	CDEF:sysperc=sysmsec,10,/ \ ";
$f[]="	CDEF:totmperc=usermsec,sysmsec,+,10,/ \  ";
$f[]="        LINE1:totmperc#ffff00:\"max cpu use\" \ ";
$f[]="        AREA:userperc#ff0000:\"user cpu percentage\" \ ";
$f[]="        STACK:sysperc#00ff00:\"system cpu percentage\" \ ";
$f[]="        COMMENT:\"\l\" \ ";
$f[]="        COMMENT:\"System cpu \" \ ";
$f[]="        GPRINT:sysperc:AVERAGE:\"avg %-3.1lf%%\t\" \ ";
$f[]="        GPRINT:sysperc:LAST:\"last %-3.1lf%%\t\" \ ";
$f[]="        GPRINT:sysperc:MAX:\"max %-3.1lf%%\t\" \ ";
$f[]="        COMMENT:\"\l\" \ ";
$f[]="        COMMENT:\"User cpu   \" \ ";
$f[]="        GPRINT:userperc:AVERAGE:\"avg %-3.1lf%%\t\" \ ";
$f[]="        GPRINT:userperc:LAST:\"last %-3.1lf%%\t\" \ ";
$f[]="        GPRINT:userperc:MAX:\"max %-3.1lf%%\" \ ";
$f[]="        COMMENT:\"\l\"        ";
$f[]="";
$f[]="";
$f[]="}";
$f[]="	";
$f[]="makeGraphs 6h 6h";
$f[]="makeGraphs 24h day";
$f[]="#makeGraphs 7d week";
$f[]="#makeGraphs 1m month";
$f[]="#makeGraphs 1y year";
}

function replic_artica_servers(){
	
	$me=basename(__FILE__);
	$unix=new unix();
	$pidpath="/etc/artica-postfix/pids/$me.pid";
	$oldpid=$unix->get_pid_from_file($pidpath);
	if($unix->process_exists($oldpid,$me)){
		system_admin_events("Task $oldpid already executed...", __FUNCTION__, __FILE__, __LINE__, "pdns");
		die();
	}
	
	@file_put_contents($pidpath, getmypid());	
	
		$q=new mysql();
		$sql="SELECT * FROM pdns_replic";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			system_admin_events($q->mysql_error, __FUNCTION__, __FILE__, __LINE__, "pdns");
			return;
		}

	while ($ligne = mysql_fetch_assoc($results)) {	
		$hostname=$ligne["hostname"];
		$port=$ligne["host_port"];
		$datas=unserialize(base64_decode($ligne["host_cred"]));
		$username=$datas["username"];
		$password=$datas["password"];
		replic_artica_servers_perform("$hostname:$port",$username,$password);
	}
	
}
function replic_artica_servers_perform($host,$username,$password){
	$unix=new unix();
	$curl=new ccurl("https://$host/exec.gluster.php");
	$curl->parms["PDNS_REPLIC"]=base64_encode(serialize(array("username"=>$username,"password"=>md5($password))));
	if(!$curl->get()){
		system_admin_events("Error while fetching $host with $curl->error", __FUNCTION__, __FILE__, __LINE__, "pdns");
		return;
	}
	
	if(preg_match("#<ERROR>(.*?)</ERROR>#is", $curl->data,$re)){
		system_admin_events("Connection error while fetching $host {$re[1]}", __FUNCTION__, __FILE__, __LINE__, "pdns");
	}
	
	if(!preg_match("#<REPLIC>(.*?)</REPLIC>#is",$curl->data,$re)){
		system_admin_events("Protocol error while fetching $host", __FUNCTION__, __FILE__, __LINE__, "pdns");
		return;		
	}
	
	$datas=unserialize(base64_decode($re[1]));
	system_admin_events("Received ". count($datas) . " from $host", __FUNCTION__, __FILE__, __LINE__, "pdns");
	
	$sql="DELETE FROM records WHERE articasrv='$host'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"powerdns");
	if(!$q->ok){
		system_admin_events($q->mysql_error." For Host $host", __FUNCTION__, __FILE__, __LINE__, "pdns");
		return;
	}
	$t=time();
	$pdns=new pdns();
	$pdns->articasrv=$host;
	while (list ($ip, $hostname) = each ($datas) ){
		if(strpos($hostname, ".")>0){
			$tr=explode(".", $hostname);
			$hostname=strtolower($tr[0]);
			unset($tr[0]);
			$pdns->domainname=strtolower(@implode(".", $tr));
		}
		
		$pdns->EditIPName($hostname, $ip, "A");
	}
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	system_admin_events("Success update ". count($datas) . " records from $host took:$took", __FUNCTION__, __FILE__, __LINE__, "pdns");
	

}

function forward_zones(){
	$unix=new unix();
	shell_exec(trim($unix->find_program("nohup")." ". $unix->LOCATE_PHP5_BIN(). " /usr/share/artica-postfix/exec.initslapd.php --pdns-recursor >/dev/null 2>&1 &"));
	
	
	$q=new mysql();
	$sql="SELECT * FROM pdns_fwzones";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){return;}	
	$ZONES=array();
	$ZONES_RECUSRIVE=array();
	@unlink("/etc/powerdns/forward-zones-file");
	@unlink("/etc/powerdns/forward-zones-recurse");

	while ($ligne = mysql_fetch_assoc($results)) {
		$hostname=$ligne["hostname"].":".$ligne["port"];
		$zone=$ligne["zone"];
		if($zone=="*"){$zone=".";}
		$recursive=$ligne["recursive"];
		if($recursive==1){
			$ZONES_RECUSRIVE[$zone][$hostname]=true;
			continue;
		}
		$ZONES[$zone][$hostname]=true;
		
	}
	
	
	if(count($ZONES)>0){
		$t=array();
		while (list ($zone, $array) = each ($ZONES) ){
			if(count($array)==0){continue;}
			$z=array();
			while (list ($hostname, $none) = each ($array) ){if(trim($hostname)==null){continue;}$z[]=$hostname;}
			echo "Starting......: ".date("H:i:s")." PowerDNS Forward zone $zone -> ".@implode(",",$z)."\n";
			$t[]="$zone=".@implode(",",$z);
		}
		if(count($t)>0){
			@file_put_contents("/etc/powerdns/forward-zones-file", @implode("\n", $t));
		}
	}
	
	if(count($ZONES_RECUSRIVE)>0){
		$t=array();
		while (list ($zone, $array) = each ($ZONES_RECUSRIVE) ){
			if(count($array)==0){continue;}
			$z=array();
			
			while (list ($hostname, $none) = each ($array) ){
				if(trim($hostname)==null){continue;}
				$z[]=$hostname;}
			echo "Starting......: ".date("H:i:s")." PowerDNS Forward recursive zone $zone -> ".@implode(",",$z)."\n";
			$t[]="$zone=".@implode(",",$z);
		}
		
		if(count($t)>0){
			@file_put_contents("/etc/powerdns/forward-zones-file", @implode(";", $t));
		}
	}	
}

function allow_recursion(){
	$q=new mysql();
	@unlink("/etc/powerdns/allow_recursion.txt");
	$sql="SELECT * FROM pdns_restricts";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$addr=trim($ligne["address"]);
		if($addr==null){continue;}
		$f[]=$addr;
	}
	
	if(count($f)>0){
		@file_put_contents("/etc/powerdns/allow_recursion.txt", @implode(",", $f));
	}
	
}

function stop_recursor(){
	$sock=new sockets();
	$unix=new unix();
	$DisablePowerDnsManagement=$sock->GET_INFO("DisablePowerDnsManagement");
	
	if(!is_numeric($DisablePowerDnsManagement)){$DisablePowerDnsManagement=0;}
	$nohup=$unix->find_program("nohup");
	$recursorbin=$unix->find_program("pdns_recursor");
	$kill=$unix->find_program("kill");
	if($DisablePowerDnsManagement==1){
		echo "Stopping......: ".date("H:i:s")."PowerDNS Recursor DisablePowerDnsManagement=$DisablePowerDnsManagement, aborting task\n";
		return;
	}

	if(!is_file($recursorbin)){
		echo "Stopping......: ".date("H:i:s")."PowerDNS Recursor Not installed, aborting task\n";
	}	
	
	$pid=pdns_recursor_pid();
	if(!$unix->process_exists($pid)){
		echo "Stopping......: ".date("H:i:s")."PowerDNS Recursor Already stopped\n";
		return;
	}
	
	$pidtime=$unix->PROCCESS_TIME_MIN($pid);
	echo "Stopping......: ".date("H:i:s")."PowerDNS Recursor pid $pid running since {$pidtime}mn\n";
	shell_exec("$kill $pid");
	sleep(1);
	$pid=pdns_recursor_pid();
	if($unix->process_exists($pid)){
		for($i=0;$i<5;$i++){
			echo "Stopping......: ".date("H:i:s")."PowerDNS Recursor waiting pid $pid top stop ".($i+1)."/5\n";
			shell_exec("$kill $pid");
			$pid=pdns_recursor_pid();
			if(!$unix->process_exists($pid)){break;}
		}
	}	
	
	$pid=pdns_recursor_pid();
	if($unix->process_exists($pid)){
		echo "Stopping......: ".date("H:i:s")."PowerDNS Recursor force killing pid $pid\n";
		shell_exec("$kill -9 $pid");
		if($unix->process_exists($pid)){
			for($i=0;$i<5;$i++){
				echo "Stopping......: ".date("H:i:s")."PowerDNS Recursor waiting pid $pid top stop ".($i+1)."/5\n";
				shell_exec("$kill -9 $pid");
				$pid=pdns_recursor_pid();
				if(!$unix->process_exists($pid)){break;}
				}
			}
	}
	
	$pid=pdns_recursor_pid();
	if($unix->process_exists($pid)){
		echo "Stopping......: ".date("H:i:s")."PowerDNS Recursor Failed to stop\n";
	}else{
		echo "Stopping......: ".date("H:i:s")."PowerDNS Recursor success\n";
	}
	
}

function start_recursor(){
	$sock=new sockets();
	$unix=new unix();
	$DisablePowerDnsManagement=$sock->GET_INFO("DisablePowerDnsManagement");
	$PowerDNSRecursorQuerLocalAddr=$sock->GET_INFO("PowerDNSRecursorQuerLocalAddr");
	$EnablePDNS=$sock->GET_INFO("EnablePDNS");
	if(!is_numeric($DisablePowerDnsManagement)){$DisablePowerDnsManagement=0;}
	$EnableChilli=0;
	$chilli=$unix->find_program("chilli");
	if(is_file($chilli)){
		$EnableChilli=$sock->GET_INFO("EnableChilli");
		if(!is_numeric($EnableChilli)){$EnableChilli=0;}
	}
	
	if($EnableChilli==1){
		echo "Stopping......: ".date("H:i:s")."PowerDNS Recursor HotSpot is enabled...\n";
		stop_recursor();
		return;
	}
	
	
	if($PowerDNSRecursorQuerLocalAddr==null){
		$net=new networking();
		$net->ifconfig("eth0");
		if($net->tcp_addr<>null){
			if($net->tcp_addr<>"0.0.0.0"){
				$PowerDNSRecursorQuerLocalAddr=$net->tcp_addr;
			}
		}
		
	}
	
	
	
	if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
	$nohup=$unix->find_program("nohup");
	$recursorbin=$unix->find_program("pdns_recursor");
	
	if(!is_file($recursorbin)){
		echo "Starting......: ".date("H:i:s")." PowerDNS Recursor Not installed, aborting task\n";
	}
	$pid=pdns_recursor_pid();
	if($unix->process_exists($pid)){
		$pidtime=$unix->PROCCESS_TIME_MIN($pid);
		echo "Starting......: ".date("H:i:s")." PowerDNS Recursor Already running PID $pid since {$pidtime}mn\n";
		return;
	}
	
	if($DisablePowerDnsManagement==1){
		echo "Starting......: ".date("H:i:s")." PowerDNS Recursor DisablePowerDnsManagement=$DisablePowerDnsManagement, aborting task\n";
		return;
	}
	if($EnablePDNS==0){
		echo "Starting......: ".date("H:i:s")." PowerDNS Recursor service is disabled, aborting task\n";
		stop_recursor();
		return;
	}	
	
	$trace=null;$quiet="yes";
	$PowerDNSLogLevel=$sock->GET_INFO("PowerDNSLogLevel");
	$PowerDNSLogsQueries=$sock->GET_INFO("PowerDNSLogsQueries");
	if(!is_numeric($PowerDNSLogLevel)){$PowerDNSLogLevel=1;}
	
	$query_local_address=" --query-local-address=$PowerDNSRecursorQuerLocalAddr";

	
	if ($PowerDNSLogLevel>8){$trace=' --trace';}
	if ($PowerDNSLogsQueries==1){$quiet='no';}
	
	echo "Starting......: ".date("H:i:s")." PowerDNS Recursor Network card to send queries $PowerDNSRecursorQuerLocalAddr\n";
	echo "Starting......: ".date("H:i:s")." PowerDNS Recursor Log level [$PowerDNSLogLevel]\n";
	echo "Starting......: ".date("H:i:s")." PowerDNS Recursor Verify MySQL DB...\n";
	checkMysql();
	
	@mkdir("/var/run/pdns",0755,true);
	
	$cmdline="$nohup $recursorbin --daemon --export-etc-hosts --socket-dir=/var/run/pdns --quiet=$quiet --config-dir=/etc/powerdns{$trace} $query_local_address >/dev/null 2>&1 &";
	shell_exec($cmdline);
	sleep(1);
	$pid=pdns_recursor_pid();
	if(!$unix->process_exists($pid)){
		for($i=0;$i<5;$i++){
			echo "Starting......: ".date("H:i:s")." PowerDNS Recursor waiting ".($i+1)."/5\n";
			$pid=pdns_recursor_pid();
			if($unix->process_exists($pid)){break;}
		}
	}
	
	$pid=pdns_recursor_pid();
	if(!$unix->process_exists($pid)){
		echo "Starting......: ".date("H:i:s")." PowerDNS Recursor failed\n";
		echo "Starting......: ".date("H:i:s")." PowerDNS Recursor \"$cmdline\"\n";
	}else{
		echo "Starting......: ".date("H:i:s")." PowerDNS Recursor success PID $pid\n";
	}
	
}

function pdns_recursor_pid(){
	$unix=new unix();
	$pid=trim(@file_get_contents("/var/run/pdns/pdns_recursor.pid"));
	if($unix->process_exists($pid)){return $pid;}
	$recursorbin=$unix->find_program("pdns_recursor");
	return $unix->PIDOF($recursorbin);
	
}

function listen_ips(){
	$sock=new sockets();
	$unix=new unix();
	$PowerDNSListenAddr=$sock->getFrameWork("PowerDNSListenAddr");
	$t=array();
	$ipA=explode("\n", $PowerDNSListenAddr);
	while (list ($line2,$ip) = each ($ipA) ){
		if(trim($ip)==null){continue;}
		if(!$unix->isIPAddress($ip)){continue;}
		$t[$ip]=$ip;
	}
	
	if(count($t)==0){
		$ips=new networking();
		$ipz=$ips->ALL_IPS_GET_ARRAY();
		while (list ($ip, $line2) = each ($ipz) ){
			$t[$ip]=$ip;
		
		}
		
	}
	
	unset($t["127.0.0.1"]);
	while (list ($a,$b) = each ($t) ){
		$f[]=$a;
		
		
	}
	
	@file_put_contents("/etc/powerdns/iplist", @implode(",", $f));
	
}

function wizard_on(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$q=new mysql();
	$sql="SELECT * FROM pdns_fwzones";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo "MySQL error $q->mysql_error\n";
	}

	
	while ($ligne = mysql_fetch_assoc($results)) {
		if(!is_numeric($ligne["port"])){$ligne["port"]=53;}
		if($ligne["port"]==0){$ligne["port"]=53;}
		$hostname=$ligne["hostname"].":".$ligne["port"];
		$zone=$ligne["zone"];
		$ID=$ligne["ID"];
		echo "Zone $zone -> $hostname\n";
		
		
	}
	
	echo "[A]................: Add a new ISP DNS server\n";
	echo "[B]................: Save and Exit\n";
	echo "[Q]................: Exit\n";
	
	$line = strtoupper(trim(fgets(STDIN)));
	
	if($line=="A"){
		echo "Give the address of your DNS server:\n";
		$server=trim(fgets(STDIN));
		if($server<>null){
			$sql="INSERT IGNORE INTO pdns_fwzones (zone,hostname,port) VALUES ('*','$server',53)";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo $q->mysql_error."\nEnter key to exit\n";
				$line = strtoupper(trim(fgets(STDIN)));
			}
		}
		wizard_on();
		return;
	}
	
	
	if($line=="B"){
		$sock=new sockets();
		echo "Enable the PowerDNS system...\n";
		$sock->SET_INFO("EnablePDNS", 1);
		echo "Apply settings...\n";
		shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php --pdns");
		shell_exec("$php5 /usr/share/artica-postfix/exec.pdns_server.php --restart");
		shell_exec("/etc/init.d/pdns-recursor restart");
		die();
	}
	
	if($line=="Q"){die();}
	
	wizard_on();
	return;
	
	
	
	
	
}


?>