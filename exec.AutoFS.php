<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NORELOAD"]=false;
$GLOBALS["PROGRESS"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.autofs.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");


if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--no-reload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}


if($argv[1]=="--count"){Autocount();die();}
if($argv[1]=="--davfs"){davfs();die();}
if($argv[1]=="--default"){autofs_default();die();}
if($argv[1]=="--checks"){Checks();die();}
if($argv[1]=="--restart-progress"){$GLOBALS["PROGRESS"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);restart_progress();die();}




function build_progress_rs($text,$pourc){
	if(!$GLOBALS["PROGRESS"]){return;}
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/autofs.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function restart_progress(){
	
	build_progress_rs("{checking_configuration}",10);
	Checks();
	build_progress_rs("{stopping_service}",35);
	system("/etc/init.d/autofs stop");
	build_progress_rs("{starting_service}",40);
	system("/etc/init.d/autofs start");
	build_progress_rs("{restarting_artica_status}",50);
	system("/etc/init.d/artica-status restart --force");
	build_progress_rs("{done}",100);
}


function Checks(){
	$GLOBALS["NORELOAD"]=true;
	$unix=new unix();
	
	if(!is_file("/usr/lib/x86_64-linux-gnu/autofs/lookup_ldap.so")){
		build_progress_rs("{install} autofs-ldap",15);
		$unix->DEBIAN_INSTALL_PACKAGE("autofs-ldap");
	}
	
	if(!is_file("/usr/lib/x86_64-linux-gnu/autofs/lookup_ldap.so")){
		build_progress_rs("{install} autofs-ldap {failed}",110);
		return;
	}
	if(!$unix->is_socket("/var/run/slapd/slapd.sock")){
		build_progress_rs("{restarting_service} OpenLDAP",15);
		system("/etc/init.d/slapd restart");
		
	}else{
		if($GLOBALS["PROGRESS"]){
			build_progress_rs("{restarting_service} OpenLDAP",15);
			system("/etc/init.d/slapd restart");
			
		}
	}
	
	
	$curlftpfs=$unix->find_program("curlftpfs");
	$fusermount=$unix->find_program("fusermount");
	if(is_file($curlftpfs)){
		if(!is_file("/sbin/mount.curl")){
			build_progress_rs("/sbin/mount.curl",15);
			$curlftpfsZ[]="#! /bin/sh";
			$curlftpfsZ[]="$curlftpfs $1 $2 -o $5,disable_eprt";
			$curlftpfsZ[]="";
			@file_put_contents("/sbin/mount.curl", @implode("\n", $curlftpfsZ));
			@chmod("/sbin/mount.curl",0755);
		}
		
		if(!is_file("/sbin/umount.curl")){
			build_progress_rs("/sbin/umount.curl",15);
			$curlftpfsZ=array();
			$curlftpfsZ[]="#! /bin/sh";
			$curlftpfsZ[]="$fusermount -u $1";
			$curlftpfsZ[]="";
			@file_put_contents("/sbin/umount.curl", @implode("\n", $curlftpfsZ));
			@chmod("/sbin/umount.curl",0755);
		}
		
		
	}
	
	
	
	
	
	
	$ldap=new clladp();
	$data="<?xml version=\"1.0\" ?>
         <autofs_ldap_sasl_conf
                 usetls=\"no\"
                 tlsrequired=\"no\"
                 authrequired=\"yes\"
                 authtype=\"PLAIN\"
                 user=\"$ldap->ldap_admin\"
                 secret=\"$ldap->ldap_password\"
         />";
	
	@file_put_contents("/etc/autofs_ldap_auth.conf", $data);
	
	if(is_file("/etc/autofs_ldap_auth.conf")){
		
		@chmod("/etc/autofs_ldap_auth.conf", 0600);
		@chown("/etc/autofs_ldap_auth.conf", "root");
		@chgrp("/etc/autofs_ldap_auth.conf", "root");
	}
	
	build_progress_rs("{checking_configuration}",15);
	$auto=new autofs();
	build_progress_rs("{checking_configuration}",20);
	autofs_default();
	build_progress_rs("{checking_configuration}",25);
	Autocount();
	build_progress_rs("{checking_configuration}",30);
	davfs();
	
}



$ldap=new clladp();
$suffix="dc=organizations,$ldap->suffix";
$filter="(&(ObjectClass=SharedFolders)(SharedFolderList=*))";
$attr=array("gidNumber");

$sr =@ldap_search($ldap->ldap_connection,$suffix,$filter,$attr);
		$hash=ldap_get_entries($ldap->ldap_connection,$sr);
		for($i=0;$i<$hash["count"];$i++){
		$gpid=$hash[$i][strtolower("gidNumber")][0];
			$auto=new autofs();
			$auto->AutofsSharedDir($gpid);
			
		}


function Autocount(){
	if(isset($GLOBALS["AutocountEXec"])){return;}
	$GLOBALS["AutocountEXec"]=true;
	$auto=new autofs();
	$hash=$auto->automounts_Browse();
	$sock=new sockets();
	$count=count($hash);
	echo "Starting......: ".date("H:i:s")." AutoFS $count mounted directories\n";
	$sock->SET_INFO("AutoFSCountDirs",$count);
}

function davfs(){
	Autocount();
$f=array();	
$f[]="# davfs2 configuration file 2009-04-12";
$f[]="# version 9";
$f[]="# ------------------------------------";
$f[]="";
$f[]="# Copyright (C) 2006, 2007, 2008, 2009 Werner Baumann";
$f[]="";
$f[]="# Copying and distribution of this file, with or without modification, are";
$f[]="# permitted in any medium without royalty provided the copyright notice";
$f[]="# and this notice are preserved.";
$f[]="";
$f[]="";
$f[]="# Please read the davfs2.conf (5) man page for a description of the";
$f[]="# configuration options and syntax rules.";
$f[]="";
$f[]="";
$f[]="# Available options and default values";
$f[]="# ====================================";
$f[]="";
$f[]="# General Options";
$f[]="# ---------------";
$f[]="";
$f[]="# dav_user        davfs2            # system wide config file only";
$f[]="# dav_group       davfs2            # system wide config file only";
$f[]="# ignore_home                       # system wide config file only";
$f[]="# kernel_fs       fuse";
$f[]="# buf_size        16                 # KiByte";
$f[]="";
$f[]="# WebDAV Related Options";
$f[]="# ----------------------";
$f[]="";
$f[]="use_proxy       1                 # system wide config file only";
$f[]="# proxy                             # system wide config file only";
$f[]="# servercert";
$f[]="# clientcert";
$f[]="ask_auth        0";
$f[]="# use_locks       1";
$f[]="# lock_owner      <user-name>";
$f[]="# lock_timeout    1800              # seconds";
$f[]="# lock_refresh    60                # seconds";
$f[]="# use_expect100   0";
$f[]="# if_match_bug    0";
$f[]="# drop_weak_etags 0";
$f[]="# allow_cookie    0";
$f[]="# precheck        1";
$f[]="# ignore_dav_header 0";
$f[]="# server_charset";
$f[]="# connect_timeout 10                # seconds";
$f[]="# read_timeout    30                # seconds";
$f[]="# retry           30                # seconds";
$f[]="# max_retry       300               # seconds";
$f[]="# add_header";
$f[]="";
$f[]="# Cache Related Options";
$f[]="# ---------------------";
$f[]="";
$f[]="# backup_dir      lost+found";
$f[]="# cache_dir       /var/cache/davfs2 # system wide cache";
$f[]="#                 ~/.davfs2/cache   # per user cache";
$f[]="# cache_size      50                # MiByte";
$f[]="# table_size      1024";
$f[]="# dir_refresh     60                # seconds";
$f[]="# file_refresh    1                 # second";
$f[]="# delay_upload    10";
$f[]="# gui_optimize    0";
$f[]="";
$f[]="# Debugging Options";
$f[]="# -----------------";
$f[]="";
$f[]="# debug           # possible values: config, kernel, cache, http, xml,";
$f[]="                  #      httpauth, locks, ssl, httpbody, secrets, most";
$f[]="";
echo "Starting......: ".date("H:i:s")." AutoFS davfs2.conf done\n";
@file_put_contents("/etc/davfs2/davfs2.conf",@implode("\n",$f));

$f=array();
	$q=new mysql();
	$sql="SELECT * FROM automount_davfs";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["local_dir"]==null){continue;}
		if($ligne["user"]==null){continue;}
		if($ligne["password"]==null){continue;}
		$c++;
		$f[]="{$ligne["uri"]}\t{$ligne["user"]}\t{$ligne["password"]}";
		
	}
	$f[]="";
echo "Starting......: ".date("H:i:s")." AutoFS secrets file with $c credential(s) done\n";
@file_put_contents("/etc/davfs2/secrets",@implode("\n",$f));

if(!$GLOBALS["NORELOAD"]){
	$unix=new unix();
	if(is_file("/usr/bin/service")){shell_exec("/usr/bin/service autofs reload");return;}
	shell_exec("/etc/init.d/autofs reload");
}

}
function autofs_default(){
	if(!is_file("/etc/default/autofs")){return;}
	$ldap=new clladp();
	$sock=new sockets();
	$EnableAutoFSDebug=intval($sock->GET_INFO("EnableAutoFSDebug"));
	$f[]="# Define default options for autofs.";
	$f[]="#";
	$f[]="# MASTER_MAP_NAME - default map name for the master map.";
	$f[]="#";
	$f[]="MASTER_MAP_NAME=\"/etc/auto.master\"";
	$f[]="TIMEOUT=300";
	$f[]="NEGATIVE_TIMEOUT=60";
	$f[]="#UMOUNT_WAIT=12";
	$f[]="BROWSE_MODE=\"yes\"";
	$f[]="#MOUNT_NFS_DEFAULT_PROTOCOL=3";
	$f[]="#APPEND_OPTIONS=\"yes\"";
	$f[]="# LOGGING - set default log level none, verbose or debug";
	$f[]="#";
	
	$ldapi="ldapi://". urlencode("/var/run/slapd/slapd.sock");
	
	$f[]="#LOGGING=\"debug\"";
	$f[]="LDAP_URI=\"$ldapi\"";
	$f[]="LDAP_TIMEOUT=-1";
	$f[]="# LDAP_NETWORK_TIMEOUT - set the network response timeout (default 8).";
	$f[]="#LDAP_NETWORK_TIMEOUT=8";
	$f[]="SEARCH_BASE=\"ou=mounts,$ldap->suffix\"";
	$f[]="LDAPBASE=\"ou=mounts,$ldap->suffix\"";
	$f[]="DEFAULT_AUTH_CONF_FILE=\"/etc/autofs_ldap_auth.conf\"";
	$f[]="MASTER_MAP_NAME=\"ou=auto.master,ou=mounts,$ldap->suffix\"";
	$f[]="DEFAULT_MAP_OBJECT_CLASS=\"automountMap\"";
	$f[]="DEFAULT_ENTRY_OBJECT_CLASS=\"automount\"";
	$f[]="DEFAULT_MAP_ATTRIBUTE=\"ou\"";
	$f[]="DEFAULT_ENTRY_ATTRIBUTE=\"cn\"";
	$f[]="DEFAULT_VALUE_ATTRIBUTE=\"automountInformation\"";
	$f[]="USE_MISC_DEVICE=\"yes\"";
	$f[]="#MAP_HASH_TABLE_SIZE=1024";
	if($EnableAutoFSDebug==1){
		$f[]="OPTIONS=\"-d -v\"";
	}else{
		$f[]="#OPTIONS=\"-d -v\"";
	}
	$f[]="#";
	$f[]="";

	echo "Starting......: ".date("H:i:s")." /etc/default/autofs done\n";
	@file_put_contents("/etc/default/autofs", @implode("\n", $f));
	
}







?>