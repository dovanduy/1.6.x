<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');

/*
 * Haarp ./configure --prefix=/usr CXX=g++-4.4
 * 
 * 


unset($argv[0]);
$unix=new unix();
$php5=$unix->LOCATE_PHP5_BIN();
shell_exec("$php5 ".dirname(__FILE__)."/compile-squid34.php ".@implode(" ",$argv));
die();
 */
/* CICAP
 * ./configure --enable-static --prefix=/usr --includedir="\${prefix}/include" --enable-large-files --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/c-icap"
 * MODULES
 * ./configure --enable-static --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/c-icap" --with-clamav
 * 
 Contrack tools
 
git clone git://git.netfilter.org/libnfnetlink
cd /root/libnfnetlink
./autogen.sh
./configure --prefix=/usr
make && make install
cd /root
git clone git://git.netfilter.org/libmnl
cd /root/libmnl/ 
./autogen.sh
./configure --prefix=/usr
make && make install
cd /root/
git clone git://git.netfilter.org/libnetfilter_conntrack
cd /root/libnetfilter_conntrack
./autogen.sh
./configure --prefix=/usr
make && make install
cd /root/
wget http://www.netfilter.org/projects/libnetfilter_cttimeout/files/libnetfilter_cttimeout-1.0.0.tar.bz2
tar -xf libnetfilter_cttimeout-1.0.0.tar.bz2
cd /root/libnetfilter_cttimeout-1.0.0
./configure --prefix=/usr
make && make install
cd /root/
wget http://www.netfilter.org/projects/libnetfilter_cthelper/files/libnetfilter_cthelper-1.0.0.tar.bz2
tar -xf libnetfilter_cthelper-1.0.0.tar.bz2
cd /root/libnetfilter_cthelper-1.0.0
./configure --prefix=/usr
make && make install
cd /root/
wget http://www.netfilter.org/projects/libnetfilter_queue/files/libnetfilter_queue-1.0.2.tar.bz2
tar -xf libnetfilter_queue-1.0.2.tar.bz2
cd /root/libnetfilter_queue-1.0.2
./configure --prefix=/usr
make && make install
cd /root/

cd /root/
 wget http://www.netfilter.org/projects/conntrack-tools/files/conntrack-tools-1.4.2.tar.bz2
 tar -xf conntrack-tools-1.4.2.tar.bz2
 
  
wget http://ftp.de.debian.org/debian/pool/main/s/squid/squid_2.7.STABLE9.orig.tar.gz
tar -xf squid_2.7.STABLE9.orig.tar.gz 
cd squid-2.7.STABLE9/
./configure --prefix=/usr --exec_prefix=/usr --bindir=/usr/sbin --sbindir=/usr/sbin --libexecdir=/usr/lib/squid27 --sysconfdir=/etc/squid27 --localstatedir=/var/spool/squid27 --datadir=/usr/share/squid27 --with-pthreads --enable-async-io --enable-storeio=ufs,aufs,coss,diskd,null --enable-ssl --enable-linux-netfilter --enable-arp-acl --enable-epoll --enable-removal-policies=lru,heap --enable-snmp --enable-delay-pools --enable-htcp --enable-cache-digests --enable-referer-log --enable-useragent-log --enable-auth="basic,digest,ntlm,negotiate" --enable-negotiate-auth-helpers=squid_kerb_auth --enable-carp --enable-follow-x-forwarded-for --with-large-files --with-maxfd=65536 --build x86_64-linux-gnu --program-suffix=27

./configure --prefix=/usr --exec_prefix=/usr --bindir=/usr/sbin --sbindir=/usr/sbin --libexecdir=/usr/lib/squid27 --sysconfdir=/etc/streamcache --localstatedir=/var/spool/squid27 --datadir=/usr/share/squid27 --with-pthreads --enable-async-io --enable-storeio=ufs,aufs,coss,diskd,null --enable-ssl --enable-linux-netfilter --enable-arp-acl --enable-epoll --enable-removal-policies=lru,heap --enable-snmp --enable-delay-pools --enable-htcp --enable-cache-digests --enable-referer-log --enable-useragent-log --enable-auth="basic,digest,ntlm,negotiate" --enable-negotiate-auth-helpers=squid_kerb_auth --enable-carp --enable-follow-x-forwarded-for --with-large-files --with-maxfd=65536 --build x86_64-linux-gnu --program-suffix=cache --program-prefix=stream
 
 
 */



$unix=new unix();
$GLOBALS["SHOW_COMPILE_ONLY"]=false;
$GLOBALS["NO_COMPILE"]=false;
$GLOBALS["REPOS"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if($argv[1]=='--compile'){$GLOBALS["SHOW_COMPILE_ONLY"]=true;$GLOBALS["NO_COMPILE"]=true;}
if(preg_match("#--no-compile#", @implode(" ", $argv))){$GLOBALS["NO_COMPILE"]=true;}
if(preg_match("#--verbose#", @implode(" ", $argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--repos#", @implode(" ", $argv))){$GLOBALS["REPOS"]=true;}
if(preg_match("#--force#", @implode(" ", $argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=="--cross-packages"){crossroads_package();exit;}
if($argv[1]=="--factorize"){factorize($argv[2]);exit;}
if($argv[1]=="--serialize"){serialize_tests();exit;}
if($argv[1]=="--latests"){latests();exit;}
if($argv[1]=="--error-txt"){error_txt();exit;}
if($argv[1]=="--c-icap"){package_c_icap();exit;}
if($argv[1]=="--ufdb"){package_ufdbguard();exit;}
if($argv[1]=="--ecapclam"){ecap_clamav();exit;}
if($argv[1]=="--package"){create_package();exit;}
if($argv[1]=="--c-icap-remove"){die();exit;}
if($argv[1]=="--msmtp"){package_msmtp();exit;}

if($argv[1]=="--nginx"){package_nginx();exit;}
if($argv[1]=="--nginx-compile"){nginx_compile();exit;}


if($argv[1]=="--dnsmasqver"){dnsmasq_lastver();exit;}
if($argv[1]=="--dnsmasq"){dnsmasq_compile();exit;}



$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");
$dirsrc="squid-3.3.0.0";
$Architecture=Architecture();

if(!$GLOBALS["NO_COMPILE"]){
	
	$v=latests();
	if(preg_match("#squid-(.+?)-#", $v,$re)){$dirsrc=$re[1];}
	system_admin_events("Downloading lastest file $v, working directory $dirsrc ...",__FUNCTION__,__FILE__,__LINE__);
}

if(!$GLOBALS["FORCE"]){
	if(is_file("/root/$v")){if($GLOBALS["REPOS"]){echo "No updates...\n";die();}}
}

if(is_dir("/root/squid-builder")){shell_exec("$rm -rf /root/squid-builder");}
chdir("/root");
if(!$GLOBALS["NO_COMPILE"]){
	
	if(!is_file("/usr/include/libecap/common/libecap.h")){
		shell_exec("$wget http://www.measurement-factory.com/tmp/ecap/libecap-0.2.0.tar.gz -O /root/libecap-0.2.0.tar.gz");
		shell_exec("$tar -xf /root/libecap-0.2.0.tar.gz -C /root/");
		chdir("/root/libecap-0.2.0");
		shell_exec("./configure --prefix=/usr");
		shell_exec("make");
		shell_exec("make install");
		if(!is_file("/usr/include/libecap/common/libecap.h")){
			echo "libecap.h failed\n";
			die();
		}
	}
	
	
	if(is_dir("/root/$dirsrc")){
		echo "Removing /root/$dirsrc\n";
		shell_exec("/bin/rm -rf /root/$dirsrc");
		sleep(1);
	}
	@mkdir("/root/$dirsrc");
	if(!is_file("/root/$v")){
		system_admin_events("Detected new version $v", __FUNCTION__, __FILE__, __LINE__, "software");
		echo "Downloading $v ...\n";
		shell_exec("$wget http://www.squid-cache.org/Versions/v3/3.3/$v");
		if(!is_file("/root/$v")){
			system_admin_events("Downloading failed", __FUNCTION__, __FILE__, __LINE__, "software");
			echo "Downloading failed...\n";die();}
	}
	
	shell_exec("$tar -xf /root/$v -C /root/$dirsrc/");
	chdir("/root/$dirsrc");
	if(!is_file("/root/$dirsrc/configure")){
		echo "/root/$dirsrc/configure no such file\n";
		$dirs=$unix->dirdir("/root/$dirsrc");
		while (list ($num, $ligne) = each ($dirs) ){if(!is_file("$ligne/configure")){echo "$ligne/configure no such file\n";}else{
			chdir("$ligne");
			echo "[OK]: Change to dir $ligne\n";
			$SOURCE_DIRECTORY=$ligne;
			break;}}
	}
	
}

$Architecture=Architecture();
$CFLAGS[]="#!/bin/sh";
$CFLAGS[]="PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
$CFLAGS[]="CFLAGS=\"-g -O2 -fPIE -fstack-protector -DNUMTHREADS=128 --param=ssp-buffer-size=4 -Wformat -Werror=format-security -Wall\"";
$CFLAGS[]="CXXFLAGS=\"-g -O2 -fPIE -fstack-protector --param=ssp-buffer-size=4 -Wformat -Werror=format-security\"";
$CFLAGS[]="CPPFLAGS=\"-D_FORTIFY_SOURCE=2\" LDFLAGS=\"-fPIE -pie -Wl,-z,relro -Wl,-z,now\"";
$CFLAGS[]="echo \$CFLAGS";
$CFLAGS[]="echo \$CXXFLAGS";
$CFLAGS[]="echo \$CPPFLAGS";
$CFLAGS[]="";
@file_put_contents("/tmp/flags.sh", @implode("\n", $CFLAGS));
$CFLAGS=array();
@chmod("/tmp/flags.sh",0755);
system("/tmp/flags.sh");


$cmds[]="--prefix=/usr";
if($Architecture==64){
	$cmds[]="--build=x86_64-linux-gnu";
}

$cmds[]="--includedir=\${prefix}/include";
$cmds[]="--mandir=\${prefix}/share/man";
$cmds[]="--infodir=\${prefix}/share/info";
$cmds[]="--localstatedir=/var";
$cmds[]="--libexecdir=\${prefix}/lib/squid3";
$cmds[]="--disable-maintainer-mode";
$cmds[]="--disable-dependency-tracking";
$cmds[]="--srcdir=.";
$cmds[]="--datadir=/usr/share/squid3"; 
$cmds[]="--sysconfdir=/etc/squid3";
$cmds[]="--enable-gnuregex";
$cmds[]="--enable-removal-policy=heap"; 
$cmds[]="--enable-follow-x-forwarded-for"; 
$cmds[]="--enable-cache-digests"; 
$cmds[]="--enable-http-violations"; 
$cmds[]="--enable-removal-policies=lru,heap"; 
$cmds[]="--enable-arp-acl";
$cmds[]="--enable-truncate";
$cmds[]="--with-large-files";
$cmds[]="--with-pthreads";
$cmds[]="--enable-esi"; 
$cmds[]="--enable-storeio=aufs,diskd,ufs,rock"; 
$cmds[]="--enable-x-accelerator-vary";
$cmds[]="--with-dl";
$cmds[]="--enable-linux-netfilter"; 
$cmds[]="--enable-wccpv2"; 
$cmds[]="--enable-eui"; 
$cmds[]="--enable-auth";
$cmds[]="--enable-auth-basic"; 
$cmds[]="--enable-snmp";
$cmds[]="--enable-icmp"; 
$cmds[]="--enable-auth-digest"; 
$cmds[]="--enable-log-daemon-helpers";
$cmds[]="--enable-url-rewrite-helpers";
$cmds[]="--enable-auth-ntlm";
$cmds[]="--with-default-user=squid";
$cmds[]="--enable-icap-client"; 
$cmds[]="--enable-cache-digests"; 
$cmds[]="--enable-poll";
$cmds[]="--enable-epoll";
$cmds[]="--enable-async-io=128";
$cmds[]="--enable-zph-qos";
$cmds[]="--enable-delay-pools";
$cmds[]="--enable-http-violations";
$cmds[]="--enable-url-maps";
$cmds[]="--enable-ecap";
$cmds[]="--enable-ssl"; 
$cmds[]="--enable-ssl-crtd";
$cmds[]="--enable-xmalloc-statistics";
$cmds[]="--with-filedescriptors=32768";
//$cmds[]="--disable-ipv6";


//CPPFLAGS="-I../libltdl"



$configure="./configure ". @implode(" ", $cmds);
if($GLOBALS["VERBOSE"]){echo "\n\n$configure\n\n";}

if($GLOBALS["SHOW_COMPILE_ONLY"]){echo $configure."\n";die();}
if(!$GLOBALS["NO_COMPILE"]){
	
	echo "configuring...\n";
	system($configure);
	echo "make...\n";
	if($GLOBALS["VERBOSE"]){system("make");}
	if(!$GLOBALS["VERBOSE"]){system("make");}
	system_admin_events("Installing the new squid-cache $v version", __FUNCTION__, __FILE__, __LINE__, "software");
	echo "make install...\n";
	
	$unix=new unix();
	$squid3=$unix->find_program("squid3");
	if(is_file($squid3)){@unlink($squid3);}
	echo "Removing squid last install\n";
	remove_squid();
	echo "Make install\n";
	if($GLOBALS["VERBOSE"]){system("make install");}
	if(!$GLOBALS["VERBOSE"]){shell_exec("make install");}	
	
}
if(!is_file("/usr/sbin/squid")){
	system_admin_events("Installing the new squid-cache $v failed", __FUNCTION__, __FILE__, __LINE__, "software");
	echo "Failed\n";}
	
@mkdir("/usr/share/squid3/errors/templates",0755,true);
if(!$GLOBALS["NO_COMPILE"]){shell_exec("/bin/rm -rf /usr/share/squid3/errors/templates/*");}
if(!$GLOBALS["NO_COMPILE"]){echo "Copy templates from $SOURCE_DIRECTORY/errors/templates...\n";}
if(!$GLOBALS["NO_COMPILE"]){shell_exec("/bin/cp -rf $SOURCE_DIRECTORY/errors/templates/* /usr/share/squid3/errors/templates/");}
shell_exec("/bin/chown -R squid:squid /usr/share/squid3");
create_package($t);	
	
function DebianVersion(){
	
	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];	
	
}

function dnsmasq_compile(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	

$dnsmasq_lastver=dnsmasq_lastver();
if($dnsmasq_lastver==null){echo "No version...\n";return;}	
if(!is_file("/usr/include/dbus-1.0/dbus/dbus.h")){shell_exec("apt-get install -y libdbus-1-dev");}
if(!is_file("/usr/include/libnetfilter_conntrack/libnetfilter_conntrack.h")){shell_exec("apt-get install -y libnetfilter-conntrack-dev");}

echo "downloading $dnsmasq_lastver\n";
if(!is_file("/root/$dnsmasq_lastver")){
	shell_exec("$wget http://www.thekelleys.org.uk/dnsmasq/$dnsmasq_lastver -O /root/$dnsmasq_lastver");
}
$f[]="make install-i18n";
$f[]="PREFIX=/usr"; 
$f[]="CFLAGS=\"-g -O2 -fstack-protector --param=ssp-buffer-size=4 -Wformat -Werror=format-security -D_FORTIFY_SOURCE=2 -Wall -W\"";
$f[]="LDFLAGS=\"-Wl,-z,relro\""; 
$f[]="COPTS=\" -DHAVE_DBUS -DHAVE_CONNTRACK\" CC=gcc";

$dir=str_replace(".tar.gz", "", $dnsmasq_lastver);
if(is_dir("/root/$dir")){shell_exec("$rm -rf /root/$dir");}
shell_exec("$tar -xf /root/$dnsmasq_lastver -C /root/");

if(!is_dir("/root/$dir")){
	echo "/root/$dir no such dir\n";
	return;
}


@chdir("/root/$dir");
$cmd=@implode(" ", $f);
echo "$cmd\n";
shell_exec($cmd);
@chmod("/usr/sbin/dnsmasq",0755);

	
}

function dnsmasq_lastver(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	shell_exec("$wget http://www.thekelleys.org.uk/dnsmasq/ -O /root/dnsmasq.html");
	$f=explode("\n",@file_get_contents("/root/dnsmasq.html"));
	while (list ($num, $ligne) = each ($f) ){
		if(!preg_match("#href=.*?dnsmasq-([0-9\.]+).tar.gz#",$ligne,$re));
		$binver=str_replace(".", "", trim($re[1]));
		$R[$binver]="dnsmasq-{$re[1]}.tar.gz";
			
		
		
		
	}
	krsort($R);
	while (list ($num, $ligne) = each ($R) ){
		return $ligne;
	}
}


function create_package($t){
$unix=new unix();	
$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");
$Architecture=Architecture();
$version=squid_version();
$debian=DebianVersion();

shell_exec("wget http://www.articatech.net/download/anthony-icons.tar.gz -O /tmp/anthony-icons.tar.gz");
@mkdir("/usr/share/squid3/icons",0755,true);
shell_exec("tar -xf /tmp/anthony-icons.tar.gz -C /usr/share/squid3/icons/");
shell_exec("/bin/chown -R squid:squid /usr/share/squid3/icons/");
echo "Removing /root/squid-builder\n";
sleep(1);
shell_exec("/bin/rm -rf /root/squid-builder");
mkdir("/root/squid-builder/usr/share/squid3",0755,true);
mkdir("/root/squid-builder/usr/lib",0755,true);
mkdir("/root/squid-builder/usr/lib32",0755,true);
mkdir("/root/squid-builder/etc/squid3",0755,true);
mkdir("/root/squid-builder/lib/squid3",0755,true);
mkdir("/root/squid-builder/usr/sbin",0755,true);
mkdir("/root/squid-builder/usr/bin",0755,true);
mkdir("/root/squid-builder/usr/share/squid-langpack",0755,true);
mkdir("/root/squid-builder/usr/share/sarg/images",0755,true);
mkdir("/root/squid-builder/usr/etc",0755,true);
mkdir("/root/squid-builder/opt/kaspersky/KasperskyUpdateUtility",0755,true);
mkdir("/root/squid-builder/usr/share/nmap",0755,true);
mkdir("/root/squid-builder/usr/share/squid27",0755,true);
mkdir("/root/squid-builder/usr/lib/squid27",0755,true);
mkdir("/root/squid-builder/etc/squid27",0755,true);
mkdir("/root/squid-builder/etc/streamcache",0755,true);
mkdir("/root/squid-builder/usr/include/libecap/common",0755,true);
mkdir("/root/squid-builder/usr/include/libecap/adapter",0755,true);
mkdir("/root/squid-builder/usr/include/libecap/host",0755,true);
mkdir("/root/squid-builder/usr/include/jasper",0755,true);
mkdir("/root/squid-builder/usr/share/man/man1",0755,true);

shell_exec("$cp -rf /usr/share/squid3/* /root/squid-builder/usr/share/squid3/");
if(!$GLOBALS["NO_COMPILE"]){shell_exec("/bin/cp -rf /usr/share/squid3/errors/templates/* /root/squid-builder/usr/share/squid3/errors/templates/");}
shell_exec("$cp -rf /etc/squid3/* /root/squid-builder/etc/squid3/");
shell_exec("$cp -rf /lib/squid3/* /root/squid-builder/lib/squid3/");
shell_exec("$cp -rf /usr/share/squid-langpack/* /root/squid-builder/usr/share/squid-langpack/");
shell_exec("$cp -rf /usr/sbin/squid /root/squid-builder/usr/sbin/squid");
shell_exec("$cp -rf /usr/bin/purge /root/squid-builder/usr/bin/purge");
shell_exec("$cp -rf /usr/bin/squidclient /root/squid-builder/usr/bin/squidclient");
shell_exec("$cp -rf /usr/bin/mysar /root/squid-builder/usr/bin/mysar");
shell_exec("$cp -rf /usr/sbin/vnstatd /root/squid-builder/usr/sbin/vnstatd");
shell_exec("$cp -rf /usr/bin/pactester /root/squid-builder/usr/bin/pactester");
shell_exec("$cp -rf /usr/lib/libpacparser.so.1 /root/squid-builder/usr/lib/libpacparser.so.1");
shell_exec("$cp -rf /usr/share/sarg/images/* /root/squid-builder/usr/share/sarg/images/");
shell_exec("$cp -rfd /opt/kaspersky/﻿KasperskyUpdateUtility/* /root/squid-builder/opt/kaspersky/﻿KasperskyUpdateUtility/");

shell_exec("$cp -fd /usr/bin/nping /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/bin/ncat /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/bin/nmapfe /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/bin/zenmap /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/bin/nmap /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/share/nmap/nmap.dtd /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/share/nmap/nmap-mac-prefixes /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/share/nmap/nmap-os-db /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/share/nmap/nmap-payloads /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/share/nmap/nmap-protocols /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/share/nmap/nmap-rpc /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/share/nmap/nmap-service-probes /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/share/nmap/nmap-services /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/share/nmap/nmap.xsl /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/share/nmap/nse_main.lua /root/squid-builder/usr/bin/");
shell_exec("$cp -rfd /usr/share/nmap/* /root/squid-builder/usr/share/nmap/");

shell_exec("$cp -rfd /usr/include/libecap/common/* /root/squid-builder/usr/include/libecap/common/");
shell_exec("$cp -rfd /usr/include/libecap/adapter/* /root/squid-builder/usr/include/libecap/adapter/");
shell_exec("$cp -rfd /usr/include/libecap/host/* /root/squid-builder/usr/include/libecap/host/");
shell_exec("$cp -rfd /usr/include/jasper/* /root/squid-builder/usr/include/jasper/");

shell_exec("$cp -fd /usr/lib/libecap.so.2.0.0 /root/squid-builder/usr/lib/libecap.so.2.0.0");
shell_exec("$cp -fd /usr/lib/libecap.so.2 /root/squid-builder/usr/lib/libecap.so.2");
shell_exec("$cp -fd /usr/lib/libecap.so /root/squid-builder/usr/lib/libecap.so");
shell_exec("$cp -fd /usr/lib/libecap.la /root/squid-builder/usr/lib/libecap.la");

shell_exec("$cp -fd /usr/lib/libgif.so.6.0.1 /root/squid-builder/usr/lib/");
shell_exec("$cp -fd /usr/lib/libgif.so.6 /root/squid-builder/usr/lib/");
shell_exec("$cp -fd /usr/lib/libgif.so /root/squid-builder/usr/lib/");
shell_exec("$cp -fd /usr/lib/libgif.la /root/squid-builder/usr/lib/");
shell_exec("$cp -fd /usr/lib/libgif.a /root/squid-builder/usr/lib/");

shell_exec("$cp -fd /usr/include/gif_lib.h /root/squid-builder/usr/include/");
shell_exec("$cp -fd /usr/lib/libgif.a /root/squid-builder/usr/lib/");
shell_exec("$cp -fd /usr/lib/libgif.la /root/squid-builder/usr/lib/");
shell_exec("$cp -fd /usr/lib/libgif.so /root/squid-builder/usr/lib/");
shell_exec("$cp -fd /usr/lib/libungif.a /root/squid-builder/usr/lib/");
shell_exec("$cp -fd /usr/lib/libungif.la /root/squid-builder/usr/lib/");
shell_exec("$cp -fd /usr/lib/libungif.so /root/squid-builder/usr/lib/");
shell_exec("$cp -fd /usr/lib/libjasper.la /root/squid-builder/usr/lib/");
shell_exec("$cp -fd /usr/lib/libjasper.a /root/squid-builder/usr/lib/");
shell_exec("$cp -fd /usr/bin/jasper /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/bin/imgcmp /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/bin/imginfo /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/bin/tmrdemo /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/bin/ziproxylogtool  /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /usr/bin/ziproxy  /root/squid-builder/usr/bin/");
shell_exec("$cp -fd /root/squid-builder/usr/share/man/man1/ziproxy.1 /root/squid-builder/usr/share/man/man1/");
shell_exec("$cp -fd /root/squid-builder/usr/share/man/man1/ziproxylogtool.1 /root/squid-builder/usr/share/man/man1/");
shell_exec("$cp -fd /etc/squid3/README_ZIPPROXY /root/squid-builder/etc/squid3/");
shell_exec("$cp -fd /usr/lib/libecap.so /root/squid-builder/usr/lib/");
shell_exec("$cp -fd /usr/lib/libecap.la /root/squid-builder/usr/lib/");
shell_exec("$cp -fd /usr/lib/libecap.a /root/squid-builder/usr/lib/");
shell_exec("$cp -fd /usr/lib/ecap_adapter_gzip.la /root/squid-builder/usr/lib/");
shell_exec("$cp -fd /usr/lib/ecap_adapter_gzip.so /root/squid-builder/usr/lib/");
shell_exec("$cp -fd /usr/lib/ecap_adapter_gzip.a /root/squid-builder/usr/lib/");

shell_exec("$cp -rfd /usr/share/squid27/* /root/squid-builder/usr/share/squid27/");
shell_exec("$cp -rfd /usr/lib/squid27/* /root/squid-builder/usr/lib/squid27/");
shell_exec("$cp -rfd /etc/squid27/* /root/squid-builder/etc/squid27/");
shell_exec("$cp -rfd /etc/streamcache/* /root/squid-builder/etc/streamcache/*");

$f[]="/usr/bin/bwm-ng";
$f[]="/usr/share/info/msmtp.info";
$f[]="/etc/ld.so.conf.d/KasperskyUpdateUtility.conf";
$f[]="/usr/bin/msmtp";
$f[]="/usr/sbin/dnsmasq";
$f[]="/usr/share/gettext/po";
$f[]="/usr/etc/css.tpl";
$f[]="/usr/etc/exclude_codes";
$f[]="/usr/bin/sarg";
$f[]="/usr/sbin/cntlm";
$f[]="/usr/lib/libmysqlclient.so.18";
$f[]="/usr/lib/libmysqlclient.so.18.0.0";
$f[]="/usr/lib/libicapapi.so.3.0.1";
$f[]="/usr/lib/libicapapi.so.3.0.2";
$f[]="/usr/lib/libicapapi.so.3.0.3";
$f[]="/usr/lib/libicapapi.so.3";
$f[]="/usr/bin/ufdbguardd";
$f[]="/usr/bin/ufdbgclient";
$f[]="/usr/bin/ufdb-pstack";
$f[]="/usr/bin/ufdbConvertDB";
$f[]="/usr/bin/ufdbGenTable";
$f[]="/usr/bin/ufdbAnalyse";
$f[]="/usr/bin/ufdbhttpd";
$f[]="/usr/bin/ufdbUpdate";
$f[]="/etc/init.d/ufdb";
$f[]="/usr/lib32/libglib-2.0.so.0";
$f[]="/usr/lib32/liblzma.so.5";
$f[]="/usr/lib32/libpcre.so.3";
$f[]="/usr/lib32/libQtCLucene.so.4";
$f[]="/usr/lib32/libQtCLucene.so.4.8.2";
$f[]="/usr/lib32/libQtCore.so.4.8";
$f[]="/usr/lib32/libQtXml.so.4";
$f[]="/usr/lib32/libQtXml.so.4.8.2";
$f[]="/usr/lib32/libxml2.so.2.8.0";
$f[]="/usr/lib32/libglib-2.0.so.0.3200.4";
$f[]="/usr/lib32/liblzma.so.5.0.0";
$f[]="/usr/lib32/libpcre.so.3.13.1";
$f[]="/usr/lib32/libQtCLucene.so.4.8";
$f[]="/usr/lib32/libQtCore.so.4";
$f[]="/usr/lib32/libQtCore.so.4.8.2";
$f[]="/usr/lib32/libQtXml.so.4.8";
$f[]="/usr/lib32/libxml2.so.2";
$f[]="/usr/sbin/RunCache27";
$f[]="/usr/sbin/squidclient27";
$f[]="/usr/sbin/cossdump27";
$f[]="/usr/sbin/squid27";
$f[]="/usr/sbin/streamRunCachecache";
$f[]="/usr/sbin/streamsquidcache";
$f[]="/usr/local/sbin/vsftpd";
$f[]="/usr/local/man/man8/vsftpd.8";
$f[]="/usr/man/man8/vsftpd.8";
$f[]="/usr/share/man/man5/vsftpd.conf.5";
$f[]="/usr/share/man/man8/vsftpd.8";

//  mkdir -p /var/spool/squid27/cache && mkdir /var/spool/squid27/logs && chown -R nobody:nobody /var/spool/squid27 && squid27 -z
// pour démarer squid27 : /usr/sbin/squid27


/*
$f[]="/usr/lib/libnfnetlink.la";
$f[]="/usr/lib/libnfnetlink.so.0.2.0";
$f[]="/usr/lib/libnfnetlink.so.0";
$f[]="/usr/lib/libnfnetlink.so";
$f[]="/usr/lib/libnfnetlink.a";
$f[]="/usr/lib/libmnl.so.0.1.0";
$f[]="/usr/lib/libmnl.la";
$f[]="/usr/lib/libmnl.so.0";
$f[]="/usr/lib/libnetfilter_conntrack.so.3.5.0";
$f[]="/usr/lib/libnetfilter_conntrack.so.3";
$f[]="/usr/lib/libnetfilter_conntrack.so";
$f[]="/usr/lib/libnetfilter_conntrack.la";
$f[]="/usr/lib/libnetfilter_cttimeout.so.1.0.0";
$f[]="/usr/lib/libnetfilter_cttimeout.so";
$f[]="/usr/lib/libnetfilter_cttimeout.so.1";
$f[]="/usr/lib/libnetfilter_cttimeout.la";
$f[]="/usr/lib/libnetfilter_cthelper.so.0.0.0";
$f[]="/usr/lib/libnetfilter_cthelper.so.0";
$f[]="/usr/lib/libnetfilter_cthelper.so";
$f[]="/usr/lib/libnetfilter_cthelper.la";
$f[]="/usr/lib/libnetfilter_queue.so.1.3.0";
$f[]="/usr/lib/libnetfilter_queue.so";
$f[]="/usr/lib/libnetfilter_queue.so.1";
$f[]="/usr/lib/libnetfilter_queue.la";
$f[]="/usr/sbin/conntrack";
$f[]="/usr/sbin/conntrackd";
$f[]="/usr/sbin/nfct";
$f[]="/usr/lib/conntrack-tools/ct_helper_ftp.la";
$f[]="/usr/lib/conntrack-tools/ct_helper_ftp.so";
$f[]="/usr/lib/conntrack-tools/ct_helper_rpc.la";  
$f[]="/usr/lib/conntrack-tools/ct_helper_rpc.so";
$f[]="/usr/lib/conntrack-tools/ct_helper_tns.la";
$f[]="/usr/lib/conntrack-tools/ct_helper_tns.so";
*/

//ldconfig -n /usr/lib/conntrack-tools

while (list ($num, $ligne) = each ($f) ){
	if(is_dir($ligne)){
		shell_exec("/bin/cp -rfd $ligne/* /root/squid-builder$ligne/");
		continue;
	}
	
	if(!is_file($ligne)){echo "$ligne no such file\n";continue;}
	$dir=dirname($ligne);
	echo "Installing $ligne in /root/squid-builder$dir/\n";
	if(!is_dir("/root/squid-builder$dir")){@mkdir("/root/squid-builder$dir",0755,true);}
	shell_exec("/bin/cp -fd $ligne /root/squid-builder$dir/");

}
package_redemption();


$CICAP=c_icap_array();
while (list ($num, $filename) = each ($CICAP) ){
	if(is_dir($filename)){
		@mkdir("/root/squid-builder$filename",0755,true);
		shell_exec("/bin/cp -rfd $filename/* /root/squid-builder$filename/");
		continue;
	}
	$dir=dirname($filename);
	echo "Installing $filename in /root/squid-builder$dir/\n";
	if(!is_dir("/root/squid-builder$dir")){@mkdir("/root/squid-builder$dir",0755,true);}
	shell_exec("/bin/cp -fd $filename /root/squid-builder$dir/");
}

echo "Debian Version: $debian\n";
echo "Compile SARG....\n";
compile_sarg();

if($debian>6){
	$debianv="-debian$debian";
}
if($Architecture==64){$Architecture="x64";}
if($Architecture==32){$Architecture="i386";}
echo "Compile Arch $Architecture v:$version Debian v:$debian \n";
chdir("/root/squid-builder");

$TARGET_TGZ="/root/squid32-$Architecture$debianv-$version.tar.gz";

$version=squid_version();
echo "Compressing $TARGET_TGZ....\n";
shell_exec("$tar -czf $TARGET_TGZ *");

if(!is_file($TARGET_TGZ)){
	echo "$TARGET_TGZ no such file ???\n";
	return;
}

system_admin_events("$TARGET_TGZ  ready...",__FUNCTION__,__FILE__,__LINE__);
if(is_file("/root/ftp-password")){
	echo "$TARGET_TGZ is now ready to be uploaded\n";
	shell_exec("curl -T $TARGET_TGZ ftp://articatech.net/download/ --user ".@file_get_contents("/root/ftp-password"));
	system_admin_events("Uploading $TARGET_TGZ done.",__FUNCTION__,__FILE__,__LINE__);
	if(is_file("/root/rebuild-artica")){shell_exec("$wget \"".@file_get_contents("/root/rebuild-artica")."\" -O /tmp/rebuild.html");}
	
}	

shell_exec("/etc/init.d/artica-postfix restart squid-cache");	
$took=$unix->distanceOfTimeInWords($t,time(),true);
system_admin_events("Installing the new squid-cache $version success took:$took", __FUNCTION__, __FILE__, __LINE__, "software");	
}

function compile_sarg(){

mkdir("/root/squid-builder/usr/bin",0755,true);
mkdir("/root/squid-builder/usr/share/locale",0755,true);

	
$f[]="/usr/bin/sarg";
$f[]="/usr/share/locale/bg/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/ca/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/cs/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/de/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/el/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/es/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/fr/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/hu/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/id/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/it/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/ja/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/lv/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/nl/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/pl/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/pt/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/ro/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/ru/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/sk/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/sr/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/tr/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/zh_CN/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/uk/LC_MESSAGES/sarg.mo";
$f[]="/usr/etc/sarg.conf";
$f[]="/usr/etc/user_limit_block";
$f[]="/usr/etc/exclude_codes";

	while (list ($num, $ligne) = each ($f) ){
		if(!is_file($ligne)){echo "$ligne no such file\n";continue;}
		$dir=dirname($ligne);
		echo "Installing $ligne in /root/squid-builder$dir/\n";
		if(!is_dir("/root/squid-builder$dir")){@mkdir("/root/squid-builder$dir",0755,true);}
		shell_exec("/bin/cp -fd $ligne /root/squid-builder$dir/");
		
	}

$f=array();
$f[]="/usr/share/sarg/fonts";
$f[]="/usr/share/sarg/images";

while (list ($num, $dir) = each ($f) ){
	if(!is_dir("/root/squid-builder$dir")){@mkdir("/root/squid-builder$dir",0755,true);}
	echo "Installing $dir/* in /root/squid-builder$dir/\n";
	shell_exec("/bin/cp -rfdv $dir/* /root/squid-builder$dir/");
}


	
}



function Architecture(){
	$unix=new unix();
	$uname=$unix->find_program("uname");
	exec("$uname -m 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#i[0-9]86#", $val)){return 32;}
		if(preg_match("#x86_64#", $val)){return 64;}
	}
}

function squid_version(){
	exec("/usr/sbin/squid -v 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Squid Cache: Version\s+(.+)#", $val,$re)){
			return trim($re[1]);
		}
	}
	
}

function latests(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	echo "Downloading latests index file Versions/v3/3.3/\n";
	@unlink("/tmp/index.html");
	shell_exec("$wget http://www.squid-cache.org/Versions/v3/3.3/ -O /tmp/index.html");
	
	$CURMAIN=0;
	$f=explode("\n",@file_get_contents("/tmp/index.html"));
	while (list ($num, $line) = each ($f)){
		if(preg_match("#<a href=\"squid-(.+?)\.tar\.gz#", $line,$re)){
			$ve=$re[1];
			
			if(preg_match("#^([0-9\.]+)-#", $ve,$ri)){
				$MAIN_VERSION=str_replace(".", "", $ri[1]);
			}else{
				$MAIN_VERSION=str_replace(".", "", $ve);
			}
			
			if($MAIN_VERSION>=$CURMAIN){
				$CURMAIN=$MAIN_VERSION;
			}else{
				continue;
			}
			
			if($GLOBALS["VERBOSE"]){echo  "$ve = MAIN=$MAIN_VERSION\n";}
			$STT=explode(".", $ve);
			$CountDeSTT=count($STT);
			if($CountDeSTT<4){$ve="{$ve}.00";}
			$veOrg=$ve;
			$ve=str_replace(".", "", $ve);
			$ve=str_replace("-", "", $ve);
			if($GLOBALS["VERBOSE"]){echo "Add version $veOrg -> `$ve`\n";}
			$file="squid-{$re[1]}.tar.gz";
			$versions[$ve]=$file;
		if($GLOBALS["VERBOSE"]){echo "$ve -> $file $CountDeSTT points\n";}
		}else{
			
		}
		
	}
	
	krsort($versions);
	while (list ($num, $filename) = each ($versions)){
		$vv[]=$filename;
	}
	
	echo "Found latest file version: `{$vv[0]}`\n";
	return $vv[0];
}


function crossroads_package(){
$Architecture=Architecture();	
if($Architecture==64){$Architecture="x64";}
if($Architecture==32){$Architecture="i386";}
$unix=new unix();
$tar=$unix->find_program("tar");
$f[]="/usr/sbin/xrctl";
$f[]="/usr/share/man/man1/xr.1";
$f[]="/usr/share/man/man1/xrctl.1";
$f[]="/usr/share/man/man5/xrctl.xml.5";
$f[]="/usr/sbin/xr";
@mkdir("/root/crossroads",0755,true);
while (list ($num, $file) = each ($f)){
	$dir=dirname($file);
	@mkdir("/root/crossroads$dir",0755,true);
	@copy($file, "/root/crossroads$file");

}
	chdir("/root/crossroads");
	shell_exec("$tar -czf crossroads-$Architecture.tar.gz *");

	
}


function factorize($path){
	$f=explode("\n",@file_get_contents($path));
	while (list ($num, $val) = each ($f)){
		$newarray[$val]=$val;
		
	}
	while (list ($num, $val) = each ($newarray)){
		echo "$val\n";
	}
	
}

function serialize_tests(){
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]="this is the text";
	$array["function"]="this is the function";
	$array["file"]="this is the process";
	$array["line"]="this is the line";
	$array["category"]="this is the category";
	$serialize=serialize($array);
	echo $serialize;
	
}




function remove_squid(){
$bins[]="/usr/sbin/squid3";
$bins[]="/usr/sbin/squid";
$bins[]="/usr/share/man/man8/squid3.8.gz";
$bins[]="/usr/sbin/squid";
$bins[]="/usr/bin/purge";
$bins[]="/usr/bin/squidclient";

while (list ($num, $filename) = each ($bins)){
	if(is_file($filename)){
		echo "Remove $filename\n";
		@unlink($filename);
	}
	
}

$dirs[]="/etc/squid3";
$dirs[]="/lib/squid3"; 
$dirs[]="/usr/lib/squid3"; 
$dirs[]="/lib64/squid3"; 
$dirs[]="/usr/lib64/squid3"; 
$dirs[]="/usr/share/squid3"; 

while (list ($num, $filename) = each ($dirs)){
	if(is_dir($filename)){
		echo "Remove $filename\n";
		shell_exec("/bin/rm -rf $filename");
	}
	
}
	
	
}

function package_ufdbguard(){
	
shell_exec("/bin/rm -rf /root/ufdbGuard-compiled");
	
$f[]="/usr/bin/ufdbguardd";
$f[]="/usr/bin/ufdbgclient";
$f[]="/usr/bin/ufdb-pstack";
$f[]="/usr/bin/ufdbConvertDB";
$f[]="/usr/bin/ufdbGenTable";
$f[]="/usr/bin/ufdbAnalyse";
$f[]="/usr/bin/ufdbhttpd";
$f[]="/usr/bin/ufdbUpdate";
$f[]="/etc/init.d/ufdb";	
$base="/root/ufdbGuard-compiled";
while (list ($num, $filename) = each ($f)){
	$dirname=dirname($filename);
	if(!is_dir("$base/$dirname")){@mkdir("$base/$dirname",0755,true);}
	shell_exec("/bin/cp -f $filename $base/$dirname/");
	
}

$Architecture=Architecture();
$version=ufdbguardVersion();
chdir("/root/ufdbGuard-compiled");
shell_exec("tar -czf ufdbGuard-$Architecture-$version.tar.gz *");
shell_exec("/bin/cp ufdbGuard-$Architecture-$version.tar.gz /root/");
echo "/root/ufdbGuard-$Architecture-$version.tar.gz done";

	
}

function ufdbguardVersion(){
	exec("/root/ufdbGuard-compiled/usr/bin/ufdbguardd -v 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#ufdbguardd:\s+([0-9\.]+)#", $line,$re)){return $re[1];}
	}
	
	
}

function c_icap_array(){
	
	// ./configure --with-c-icap=/usr/lib/c_icap/
	
$f[]="/usr/bin/c-icap";
$f[]="/usr/bin/c-icap-client";
$f[]="/usr/bin/c-icap-config";
$f[]="/usr/bin/c-icap-libicapapi-config";
$f[]="/usr/bin/c-icap-mkbdb";
$f[]="/usr/bin/c-icap-mods-sguardDB";
$f[]="/usr/bin/c-icap-stretch";
$f[]="/usr/lib/c_icap/bdb_tables.a";
$f[]="/usr/lib/c_icap/bdb_tables.la";
$f[]="/usr/lib/c_icap/bdb_tables.so";
$f[]="/usr/lib/c_icap/dnsbl_tables.a";
$f[]="/usr/lib/c_icap/dnsbl_tables.la";
$f[]="/usr/lib/c_icap/dnsbl_tables.so";
$f[]="/usr/lib/c_icap/ldap_module.a";
$f[]="/usr/lib/c_icap/ldap_module.la";
$f[]="/usr/lib/c_icap/ldap_module.so";
$f[]="/usr/lib/c_icap/srv_echo.a";
$f[]="/usr/lib/c_icap/srv_echo.la";
$f[]="/usr/lib/c_icap/srv_echo.so";
$f[]="/usr/lib/c_icap/sys_logger.a";
$f[]="/usr/lib/c_icap/sys_logger.la";
$f[]="/usr/lib/c_icap/sys_logger.so";
$f[]="/usr/lib/c_icap/srv_content_filtering.la";
$f[]="/usr/lib/c_icap/srv_content_filtering.so";
$f[]="/usr/lib/libicapapi.la";
$f[]="/usr/lib/libicapapi.so";
$f[]="/usr/lib/libicapapi.so.0";
$f[]="/usr/lib/libicapapi.so.0.0.7";
$f[]="/usr/lib/libicapapi.so.0.0.1";
$f[]="/usr/lib/libicapapi.so.2" ;     
$f[]="/usr/lib/libicapapi.so.2.0.2";
$f[]="/usr/lib/libicapapi.so.2.0.3";
$f[]="/usr/lib/libicapapi.so.2.0.5";
$f[]="/usr/share/c_icap/templates/srv_content_filtering/en/BLOCK";
$f[]="/usr/share/c_icap/templates/srv_url_check/en/DENY";
$f[]="/usr/share/c_icap/templates/virus_scan/en/VIR_MODE_HEAD";  
$f[]="/usr/share/c_icap/templates/virus_scan/en/VIR_MODE_PROGRESS";  
$f[]="/usr/share/c_icap/templates/virus_scan/en/VIR_MODE_TAIL";
$f[]="/usr/share/c_icap/templates/virus_scan/en/VIR_MODE_VIRUS_FOUND";  
$f[]="/usr/share/c_icap/templates/virus_scan/en/VIRUS_FOUND";
$f[]="/usr/share/man/man8/c-icap.8";
$f[]="/usr/share/man/man8/c-icap-client.8";
$f[]="/usr/share/man/man8/c-icap-config.8";
$f[]="/usr/share/man/man8/c-icap-libicapapi-config.8";
$f[]="/usr/share/man/man8/c-icap-mkbdb.8";
$f[]="/usr/share/man/man8/c-icap-stretch.8";
$f[]="/etc/c-icap.conf";
$f[]="/etc/srv_url_check.conf";
$f[]="/etc/virus_scan.conf";
$f[]="/etc/c-icap.magic.default";
$f[]="/etc/c-icap.magic";
$f[]="/usr/lib/c_icap/bdb_tables.a";
$f[]="/usr/lib/c_icap/dnsbl_tables.a";
$f[]="/usr/lib/c_icap/ldap_module.a";
$f[]="/usr/lib/c_icap/srv_clamav.a";
$f[]="/usr/lib/c_icap/srv_echo.a";
$f[]="/usr/lib/c_icap/srv_url_check.a";
$f[]="/usr/lib/c_icap/sys_logger.a";
$f[]="/usr/lib/c_icap/bdb_tables.la";
$f[]="/usr/lib/c_icap/dnsbl_tables.la";
$f[]="/usr/lib/c_icap/ldap_module.la";
$f[]="/usr/lib/c_icap/srv_clamav.la";
$f[]="/usr/lib/c_icap/srv_echo.la";
$f[]="/usr/lib/c_icap/srv_url_check.la";
$f[]="/usr/lib/c_icap/sys_logger.la";
$f[]="/usr/lib/c_icap/bdb_tables.so";
$f[]="/usr/lib/c_icap/dnsbl_tables.so";
$f[]="/usr/lib/c_icap/ldap_module.so";
$f[]="/usr/lib/c_icap/srv_clamav.so";
$f[]="/usr/lib/c_icap/srv_echo.so";
$f[]="/usr/lib/c_icap/srv_url_check.so";
$f[]="/usr/lib/c_icap/sys_logger.so";
$f[]="/usr/lib/c_icap/virus_scan.a";
$f[]="/usr/lib/c_icap/virus_scan.la";
$f[]="/etc/srv_url_check.conf.default";
$f[]="/etc/srv_url_check.conf";
$f[]="/etc/srv_clamav.conf.default";
$f[]="/etc/srv_clamav.conf";
$f[]="/usr/local/bin/fhs_judge"; 
$f[]="/usr/local/bin/fhs_learn"; 
$f[]="/usr/local/bin/fhs_makepreload"; 
$f[]="/usr/local/bin/fnb_judge"; 
$f[]="/usr/local/bin/fnb_learn"; 
$f[]="/usr/local/bin/fnb_makepreload";
$f[]="/usr/lib/c_icap/srv_classify.la";
$f[]="/usr/lib/c_icap/srv_classify.so";
$f[]="/usr/lib/c_icap/bdb_tables.a";
$f[]="/usr/lib/c_icap/dnsbl_tables.a";
$f[]="/usr/lib/c_icap/ldap_module.a";
$f[]="/usr/lib/c_icap/libbz2.so.1.0.4";
$f[]="/usr/lib/c_icap/srv_echo.so";
$f[]="/usr/lib/c_icap/srv_ex206.so";
$f[]="/usr/lib/c_icap/srv_url_check.so";
$f[]="/usr/lib/c_icap/sys_logger.so";
$f[]="/usr/lib/c_icap/virus_scan.so";
$f[]="/usr/lib/c_icap/bdb_tables.la";
$f[]="/usr/lib/c_icap/dnsbl_tables.la";
$f[]="/usr/lib/c_icap/ldap_module.la";
$f[]="/usr/lib/c_icap/srv_echo.a";
$f[]="/usr/lib/c_icap/srv_ex206.a";
$f[]="/usr/lib/c_icap/srv_url_check.a";
$f[]="/usr/lib/c_icap/sys_logger.a";
$f[]="/usr/lib/c_icap/virus_scan.a";
$f[]="/usr/lib/c_icap/bdb_tables.so";
$f[]="/usr/lib/c_icap/dnsbl_tables.so";
$f[]="/usr/lib/c_icap/ldap_module.so";
$f[]="/usr/lib/c_icap/srv_echo.la";
$f[]="/usr/lib/c_icap/srv_ex206.la";
$f[]="/usr/lib/c_icap/srv_url_check.la";
$f[]="/usr/lib/c_icap/sys_logger.la";
$f[]="/usr/lib/c_icap/virus_scan.la";
$f[]="/usr/lib/c_icap/clamav_mod.a";
$f[]="/usr/lib/c_icap/clamav_mod.la";
$f[]="/usr/lib/c_icap/clamav_mod.so";
$f[]="/usr/lib/c_icap/clamd_mod.a";
$f[]="/usr/lib/c_icap/clamd_mod.la";
$f[]="/usr/lib/c_icap/clamd_mod.so";
$f[]="/usr/sbin/openvpn";
$f[]="/usr/lib/openvpn/plugins/openvpn-plugin-auth-pam.so";
$f[]="/usr/lib/openvpn/plugins/openvpn-plugin-auth-pam.la";
$f[]="/usr/lib/openvpn/plugins/openvpn-plugin-down-root.so";
$f[]="/usr/lib/openvpn/plugins/openvpn-plugin-down-root.la";
$f[]="/usr/bin/memcached";

return $f;	
	
}

function c_cicap_remove(){
	die();

}


function package_c_icap(){
$f=c_icap_array();

$base="/root/c-icap-export";
shell_exec("/bin/rm -rf $base");
@mkdir($base);
while (list ($num, $filename) = each ($f)){
	$dirname=dirname($filename);
	if(!is_dir("$base/$dirname")){@mkdir("$base/$dirname",0755,true);}
	if(is_file($filename)){
		echo "Copy $filename into $base$dirname\n";
		shell_exec("/bin/cp -f $filename $base$dirname/");
	}
	
}
$C_ICAP_VERSION=C_ICAP_VERSION();
$Architecture=Architecture();
echo "C-icap version $C_ICAP_VERSION ($Architecture)\n";
mkdir("/root/c-icap/usr/share/c_icap",0755,true);
mkdir("/root/c-icap/usr/include/c_icap",0755,true);
shell_exec("/bin/cp -rf /usr/share/c_icap/* /root/c-icap/usr/share/c_icap/");
shell_exec("/bin/cp -rf /usr/include/c_icap/* /root/c-icap/usr/include/c_icap/");
//error while loading shared libraries: libbz2.so.1.0
shell_exec("/bin/cp /lib/libbz2.so.1.0.4 /usr/lib/c_icap/");

chdir($base);
@unlink("/root/c-icap-$C_ICAP_VERSION-$Architecture.tar.gz");
shell_exec("/bin/tar -czf /root/c-icap-$C_ICAP_VERSION-$Architecture.tar.gz *");
echo "/root/c-icap-$C_ICAP_VERSION-$Architecture.tar.gz\n";
}

function C_ICAP_VERSION(){
	
	$results=exec("/usr/bin/c-icap-config --version");
	preg_match("#([0-9\.]+)#", $results,$re);
	return $re[1];
}


function ecap_clamav(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");	
	chdir("/root");
	shell_exec("$rm -rf /root/libecap-0.2.0 >/dev/null 2>&1");
	@unlink("/root/libecap-0.2.0.tar.gz");
	echo "Download libecap-0.2.0.tar.gz\n";
	shell_exec("wget http://www.measurement-factory.com/tmp/ecap/libecap-0.2.0.tar.gz");
	echo "extracting libecap-0.2.0.tar.gz\n";
	shell_exec("$tar -xf libecap-0.2.0.tar.gz");
	if(!is_dir("/root/libecap-0.2.0")){echo "Failed\n";return;}
	chdir("/root/libecap-0.2.0");
	echo "Configuring....\n";
	shell_exec("./configure --prefix=/usr --includedir=\"\${prefix}/include\" --mandir=\"\${prefix}/share/man\" --infodir=\"\${prefix}/share/info\" --sysconfdir=/etc --localstatedir=/var --libexecdir=\"\${prefix}/lib\"");
	if(!is_file("/root/libecap-0.2.0/Makefile")){echo "Failed\n";return;}
	echo "Make....\n";
	shell_exec("make");
	shell_exec("make install");
	mkdir("/root/ecapav/usr/include/libecap/common",0755,true);
	mkdir("/root/ecapav/usr/include/libecap/adapter",0755,true);
	mkdir("/root/ecapav/usr/include/libecap/host",0755,true);
	mkdir("/root/ecapav/usr/lib",0755,true);
	mkdir("/root/ecapav/usr/libexec/squid",0755,true);
	
	shell_exec("$cp -a /usr/include/libecap/common/* /root/ecapav/usr/include/libecap/common/");
	shell_exec("$cp -a /usr/include/libecap/adapter/* /root/ecapav/usr/include/libecap/adapter/");
	shell_exec("$cp -a /usr/include/libecap/host/* /root/ecapav/usr/include/libecap/host/");
	shell_exec("$cp -a /usr/lib/libecap.so.2.0.0 /root/ecapav/usr/lib/libecap.so.2.0.0");
	shell_exec("$cp -a /usr/lib/libecap.so.2 /root/ecapav/usr/lib/libecap.so.2");
	shell_exec("$cp -a /usr/lib/libecap.so /root/ecapav/usr/lib/libecap.so");
	shell_exec("$cp -a /usr/lib/libecap.la /root/ecapav/usr/lib/libecap.la");
	
	
	chdir("/root");
	echo "Download squid-ecap-av-1.0.3.tar.bz2\n";
	@unlink("/root/squid-ecap-av-1.0.3.tar.bz2");
	shell_exec("wget http://www.articatech.net/download/squid-ecap-av-1.0.3.tar.bz2");
	echo "extracting squid-ecap-av-1.0.3.tar.bz2\n";
	shell_exec("$tar -xf squid-ecap-av-1.0.3.tar.bz2");
	if(!is_dir("/root/squid-ecap-av-1.0.3")){echo "Failed\n";return;}
	chdir("/root/squid-ecap-av-1.0.3");
	echo "cmake\n";
	shell_exec("cmake -DCMAKE_INSTALL_PREFIX=/usr");
	echo "Make....\n";
	shell_exec("make");
	echo "Make install\n";
	shell_exec("make install");
	shell_exec("$cp -a /usr/libexec/squid/ecap_adapter_av.so /root/ecapav/usr/libexec/squid/ecap_adapter_av.so");
	
}
function package_msmtp_version(){
	exec("/root/msmtp-compiled/usr/bin/msmtp --version 2>&1",$results);
	while (list ($num, $line) = each ($results)){
		if(preg_match("#msmtp version.*?([0-9\.]+)#", $line,$re)){return $re[1];}
	}


}

function package_msmtp(){
	$base="/root/msmtp-compiled";
	shell_exec("/bin/rm -rf $base");
	
	/* git clone git://git.code.sf.net/p/msmtp/code msmtp
	 *./configure  --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/gsasl"  --disable-dependency-tracking
	 */
	

	$f[]="/usr/share/info/msmtp.info";
	$f[]="/usr/bin/msmtp";
	$f[]="/usr/share/gettext/po";

	while (list ($num, $filename) = each ($f)){

		if(is_dir($filename)){
			@mkdir("$base/$filename",0755,true);
			shell_exec("/bin/cp -rf $filename/ $base/$filename/");
			continue;
		}

		$dirname=dirname($filename);
		if(!is_dir("$base/$dirname")){@mkdir("$base/$dirname",0755,true);}
		shell_exec("/bin/cp -f $filename $base/$dirname/");

	}

	$Architecture=Architecture();
	$version=package_msmtp_version();
	chdir($base);
	shell_exec("tar -czf msmtp-$Architecture-$version.tar.gz *");
	shell_exec("/bin/cp msmtp-$Architecture-$version.tar.gz /root/");
	echo "/root/msmtp-$Architecture-$version.tar.gz done\n";

}

function package_redemption(){
	
/* apt-get instal boost-build libboost-dev  libboost-test-dev libboost-program-options-dev libssl-dev
wget https://github.com/wallix/redemption/archive/master.zip
mkdir redemption && cp master.zip redemption/
cd redemption && unzip master.zip
cd redemption-master
bjam	
*/
	
	$f["/usr/local/share/rdpproxy"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_16.fv1"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b.png"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_24.fv1"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b.bmp"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_10.fv1"]=true;
	$f["/usr/local/share/rdpproxy/ad24b-xxx.bmp"]=true;
	$f["/usr/local/share/rdpproxy/wablogoblue.png"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b-redemption.bmp"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_18.fv1"]=true;
	$f["/usr/local/share/rdpproxy/ad24b.bmp"]=true;
	$f["/usr/local/share/rdpproxy/Philips_PM5544_192.bmp"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_14.fv1"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b-redemption.png"]=true;
	$f["/usr/local/share/rdpproxy/ad160-24b.bmp"]=true;
	$f["/usr/local/share/rdpproxy/Philips_PM5544_640.png"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_13.fv1"]=true;
	$f["/usr/local/share/rdpproxy/ad24b.png"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b.jpg"]=true;
	$f["/usr/local/share/rdpproxy/Philips_PM5544_640.bmp"]=true;
	$f["/usr/local/share/rdpproxy/sans-10.fv1"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_12.fv1"]=true;
	$f["/usr/local/share/rdpproxy/ad8b.png"]=true;
	$f["/usr/local/share/rdpproxy/ad8b.bmp"]=true;
	$f["/usr/local/bin/rdpproxy"]=true;
	$f["/usr/local/bin/redrec"]=true;
	$f["/etc/rdpproxy"]=true;
	$f["/etc/rdpproxy/rsakeys.ini"]=true;
	$f["/etc/rdpproxy/rdpproxy.ini"]=true;
	$f["/etc/rdpproxy/dh1024.pem"]=true;
	$f["/etc/rdpproxy/rdpproxy-cert.pem"]=true;
	$f["/etc/rdpproxy/dh2048.pem"]=true;
	$f["/etc/rdpproxy/rdpproxy-key.pem"]=true;
	$f["/etc/rdpproxy/rdpproxy.crt"]=true;
	$f["/etc/rdpproxy/rdpproxy.key"]=true;
	$f["/etc/rdpproxy/rdpproxy.p12"]=true;
	$f["/usr/local/share/rdpproxy/cursor0.cur"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b-wallix.bmp"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_11.fv1"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_20.fv1"]=true;
	$f["/usr/local/share/rdpproxy/cursor1.cur"]=true;
	$f["/usr/local/share/rdpproxy/FreeSans.ttf"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_32.fv1"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_48.fv1"]=true;
	
	
	$base="/root/squid-builder";
	@mkdir("$base",0755,true);
	
	while (list ( $filename,$none) = each ($f)){
		if(is_file($filename)){
			$dirname=dirname($filename);
			$newbase="$base/$dirname";
			@mkdir("$newbase",0755,true);
			echo "Copy $filename to $newbase\n";
			shell_exec("/bin/cp $filename $newbase/");
		}
		
	}
	@mkdir("$base/etc/rdpproxy/tools",0755,true);
	shell_exec("/bin/cp -rfv /root/redemption/redemption-master/tools $base/etc/rdpproxy/tools/");
	

}


function package_freerdp(){
/* uri: https://github.com/FreeRDP/FreeRDP/zipball/master
apt-get install libpcsclite-dev libasound2-dev libxtst-dev
cmake -DCMAKE_BUILD_TYPE=Debug -DWITH_DEBUG_CERTIFICATE=ON -DWITH_DEBUG_CHANNELS=ON WITH_DEBUG_CLIPRDR=ON -DWITH_DEBUG_DVC=ON -DWITH_DEBUG_GDI=ON -DWITH_DEBUG_KBD=ON -DWITH_DEBUG_LICENSE=ON -DWITH_DEBUG_NEGO=ON -DWITH_DEBUG_NLA=ON -DWITH_DEBUG_NTLM=ON -DWITH_DEBUG_ORDERS=ON -DWITH_DEBUG_RAIL=ON -DWITH_DEBUG_RDP=ON -DWITH_DEBUG_REDIR=ON -DWITH_DEBUG_RFX=ON -DWITH_DEBUG_SCARD=ON -DWITH_DEBUG_SVC=ON -DWITH_DEBUG_TRANSPORT=ON -DWITH_DEBUG_TSG=ON -DWITH_DEBUG_WND=ON -DWITH_DEBUG_X11=ON -DWITH_DEBUG_X11_CLIPRDR=ON -DWITH_DEBUG_X11_LOCAL_MOVESIZE=ON -DWITH_DEBUG_XV=ON -DWITH_FFMPEG=OFF -DWITH_JPEG=ON -DWITH_MANPAGES=ON -DWITH_PCSC=ON -DWITH_PROFILER=ON -DWITH_PULSEAUDIO=ON -DWITH_SERVER=ON -DWITH_SSE2=ON -DWITH_SSE2_TARGET=ON -DWITH_THIRD_PARTY=ON -DWITH_ALSA=ON -DWITH_XTEST=ON .
make
make install
*/
	
	$f["/usr/local/include/winpr/crt.h"]=true;
	$f["/usr/local/include/winpr/stream.h"]=true;
	$f["/usr/local/include/winpr/winhttp.h"]=true;
	$f["/usr/local/include/winpr/print.h"]=true;
	$f["/usr/local/include/winpr/nt.h"]=true;
	$f["/usr/local/include/winpr/interlocked.h"]=true;
	$f["/usr/local/include/winpr/spec.h"]=true;
	$f["/usr/local/include/winpr/sam.h"]=true;
	$f["/usr/local/include/winpr/ndr.h"]=true;
	$f["/usr/local/include/winpr/timezone.h"]=true;
	$f["/usr/local/include/winpr/winsock.h"]=true;
	$f["/usr/local/include/winpr/cmdline.h"]=true;
	$f["/usr/local/include/winpr/pipe.h"]=true;
	$f["/usr/local/include/winpr/security.h"]=true;
	$f["/usr/local/include/winpr/io.h"]=true;
	$f["/usr/local/include/winpr/wtsapi.h"]=true;
	$f["/usr/local/include/winpr/handle.h"]=true;
	$f["/usr/local/include/winpr/input.h"]=true;
	$f["/usr/local/include/winpr/rpc.h"]=true;
	$f["/usr/local/include/winpr/collections.h"]=true;
	$f["/usr/local/include/winpr/platform.h"]=true;
	$f["/usr/local/include/winpr/pool.h"]=true;
	$f["/usr/local/include/winpr/error.h"]=true;
	$f["/usr/local/include/winpr/windows.h"]=true;
	$f["/usr/local/include/winpr/memory.h"]=true;
	$f["/usr/local/include/winpr/file.h"]=true;
	$f["/usr/local/include/winpr/heap.h"]=true;
	$f["/usr/local/include/winpr/asn1.h"]=true;
	$f["/usr/local/include/winpr/schannel.h"]=true;
	$f["/usr/local/include/winpr/config.h"]=true;
	$f["/usr/local/include/winpr/winpr.h"]=true;
	$f["/usr/local/include/winpr/wlog.h"]=true;
	$f["/usr/local/include/winpr/sspi.h"]=true;
	$f["/usr/local/include/winpr/sysinfo.h"]=true;
	$f["/usr/local/include/winpr/string.h"]=true;
	$f["/usr/local/include/winpr/wtypes.h"]=true;
	$f["/usr/local/include/winpr/midl.h"]=true;
	$f["/usr/local/include/winpr/tchar.h"]=true;
	$f["/usr/local/include/winpr/thread.h"]=true;
	$f["/usr/local/include/winpr/path.h"]=true;
	$f["/usr/local/include/winpr/bcrypt.h"]=true;
	$f["/usr/local/include/winpr/endian.h"]=true;
	$f["/usr/local/include/winpr/crypto.h"]=true;
	$f["/usr/local/include/winpr/ntlm.h"]=true;
	$f["/usr/local/include/winpr/registry.h"]=true;
	$f["/usr/local/include/winpr/dsparse.h"]=true;
	$f["/usr/local/include/winpr/synch.h"]=true;
	$f["/usr/local/include/winpr/credentials.h"]=true;
	$f["/usr/local/include/winpr/sspicli.h"]=true;
	$f["/usr/local/include/winpr/environment.h"]=true;
	$f["/usr/local/include/winpr/library.h"]=true;
	$f["/usr/local/include/winpr/credui.h"]=true;
	$f["/usr/local/lib/libwinpr-timezone.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-timezone.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-timezone.so"]=true;
	$f["/usr/local/lib/libwinpr-timezone.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sspi.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sspi.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-sspi.so"]=true;
	$f["/usr/local/lib/libwinpr-sspi.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-interlocked.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-interlocked.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-interlocked.so"]=true;
	$f["/usr/local/lib/libwinpr-interlocked.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-synch.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-synch.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-synch.so"]=true;
	$f["/usr/local/lib/libwinpr-synch.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-input.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-input.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-input.so"]=true;
	$f["/usr/local/lib/libwinpr-input.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-handle.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-handle.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-handle.so"]=true;
	$f["/usr/local/lib/libwinpr-handle.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-dsparse.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-dsparse.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-dsparse.so"]=true;
	$f["/usr/local/lib/libwinpr-dsparse.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-rpc.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-rpc.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-rpc.so"]=true;
	$f["/usr/local/lib/libwinpr-rpc.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-credentials.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-credentials.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-credentials.so"]=true;
	$f["/usr/local/lib/libwinpr-credentials.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-environment.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-environment.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-environment.so"]=true;
	$f["/usr/local/lib/libwinpr-environment.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-pipe.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-pipe.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-pipe.so"]=true;
	$f["/usr/local/lib/libwinpr-pipe.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-thread.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-thread.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-thread.so"]=true;
	$f["/usr/local/lib/libwinpr-thread.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-error.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-error.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-error.so"]=true;
	$f["/usr/local/lib/libwinpr-error.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sysinfo.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sysinfo.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-sysinfo.so"]=true;
	$f["/usr/local/lib/libwinpr-sysinfo.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-crypto.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-crypto.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-crypto.so"]=true;
	$f["/usr/local/lib/libwinpr-crypto.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-pool.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-pool.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-pool.so"]=true;
	$f["/usr/local/lib/libwinpr-pool.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-crt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-crt.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-crt.so"]=true;
	$f["/usr/local/lib/libwinpr-crt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-asn1.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-asn1.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-asn1.so"]=true;
	$f["/usr/local/lib/libwinpr-asn1.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-file.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-file.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-file.so"]=true;
	$f["/usr/local/lib/libwinpr-file.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-wtsapi.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-wtsapi.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-wtsapi.so"]=true;
	$f["/usr/local/lib/libwinpr-wtsapi.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-io.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-io.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-io.so"]=true;
	$f["/usr/local/lib/libwinpr-io.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-winhttp.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-winhttp.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-winhttp.so"]=true;
	$f["/usr/local/lib/libwinpr-winhttp.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-path.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-path.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-path.so"]=true;
	$f["/usr/local/lib/libwinpr-path.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-heap.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-heap.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-heap.so"]=true;
	$f["/usr/local/lib/libwinpr-heap.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-library.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-library.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-library.so"]=true;
	$f["/usr/local/lib/libwinpr-library.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-com.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-com.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-com.so"]=true;
	$f["/usr/local/lib/libwinpr-com.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-credui.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-credui.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-credui.so"]=true;
	$f["/usr/local/lib/libwinpr-credui.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-registry.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-registry.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-registry.so"]=true;
	$f["/usr/local/lib/libwinpr-registry.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-winsock.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-winsock.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-winsock.so"]=true;
	$f["/usr/local/lib/libwinpr-winsock.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-bcrypt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-bcrypt.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-bcrypt.so"]=true;
	$f["/usr/local/lib/libwinpr-bcrypt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-utils.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-utils.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-utils.so"]=true;
	$f["/usr/local/lib/libwinpr-utils.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-nt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-nt.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-nt.so"]=true;
	$f["/usr/local/lib/libwinpr-nt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sspicli.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sspicli.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-sspicli.so"]=true;
	$f["/usr/local/lib/libwinpr-sspicli.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-makecert-tool.a"]=true;
	$f["/usr/local/include/freerdp/types.h"]=true;
	$f["/usr/local/include/freerdp/api.h"]=true;
	$f["/usr/local/include/freerdp/peer.h"]=true;
	$f["/usr/local/include/freerdp/dvc.h"]=true;
	$f["/usr/local/include/freerdp/svc.h"]=true;
	$f["/usr/local/include/freerdp/rail.h"]=true;
	$f["/usr/local/include/freerdp/secondary.h"]=true;
	$f["/usr/local/include/freerdp/window.h"]=true;
	$f["/usr/local/include/freerdp/primitives.h"]=true;
	$f["/usr/local/include/freerdp/primary.h"]=true;
	$f["/usr/local/include/freerdp/pointer.h"]=true;
	$f["/usr/local/include/freerdp/input.h"]=true;
	$f["/usr/local/include/freerdp/extension.h"]=true;
	$f["/usr/local/include/freerdp/error.h"]=true;
	$f["/usr/local/include/freerdp/message.h"]=true;
	$f["/usr/local/include/freerdp/scancode.h"]=true;
	$f["/usr/local/include/freerdp/settings.h"]=true;
	$f["/usr/local/include/freerdp/altsec.h"]=true;
	$f["/usr/local/include/freerdp/constants.h"]=true;
	$f["/usr/local/include/freerdp/client.h"]=true;
	$f["/usr/local/include/freerdp/addin.h"]=true;
	$f["/usr/local/include/freerdp/event.h"]=true;
	$f["/usr/local/include/freerdp/listener.h"]=true;
	$f["/usr/local/include/freerdp/graphics.h"]=true;
	$f["/usr/local/include/freerdp/freerdp.h"]=true;
	$f["/usr/local/include/freerdp/update.h"]=true;
	$f["/usr/local/include/freerdp/cache"]=true;
	$f["/usr/local/include/freerdp/cache/offscreen.h"]=true;
	$f["/usr/local/include/freerdp/cache/nine_grid.h"]=true;
	$f["/usr/local/include/freerdp/cache/bitmap.h"]=true;
	$f["/usr/local/include/freerdp/cache/pointer.h"]=true;
	$f["/usr/local/include/freerdp/cache/brush.h"]=true;
	$f["/usr/local/include/freerdp/cache/cache.h"]=true;
	$f["/usr/local/include/freerdp/cache/glyph.h"]=true;
	$f["/usr/local/include/freerdp/cache/palette.h"]=true;
	$f["/usr/local/include/freerdp/codec"]=true;
	$f["/usr/local/include/freerdp/codec/rfx.h"]=true;
	$f["/usr/local/include/freerdp/codec/nsc.h"]=true;
	$f["/usr/local/include/freerdp/codec/bitmap.h"]=true;
	$f["/usr/local/include/freerdp/codec/mppc_enc.h"]=true;
	$f["/usr/local/include/freerdp/codec/dsp.h"]=true;
	$f["/usr/local/include/freerdp/codec/jpeg.h"]=true;
	$f["/usr/local/include/freerdp/codec/mppc_dec.h"]=true;
	$f["/usr/local/include/freerdp/codec/color.h"]=true;
	$f["/usr/local/include/freerdp/codec/audio.h"]=true;
	$f["/usr/local/include/freerdp/crypto"]=true;
	$f["/usr/local/include/freerdp/crypto/per.h"]=true;
	$f["/usr/local/include/freerdp/crypto/der.h"]=true;
	$f["/usr/local/include/freerdp/crypto/certificate.h"]=true;
	$f["/usr/local/include/freerdp/crypto/ber.h"]=true;
	$f["/usr/local/include/freerdp/crypto/er.h"]=true;
	$f["/usr/local/include/freerdp/crypto/crypto.h"]=true;
	$f["/usr/local/include/freerdp/crypto/tls.h"]=true;
	$f["/usr/local/include/freerdp/gdi"]=true;
	$f["/usr/local/include/freerdp/gdi/region.h"]=true;
	$f["/usr/local/include/freerdp/gdi/16bpp.h"]=true;
	$f["/usr/local/include/freerdp/gdi/line.h"]=true;
	$f["/usr/local/include/freerdp/gdi/bitmap.h"]=true;
	$f["/usr/local/include/freerdp/gdi/drawing.h"]=true;
	$f["/usr/local/include/freerdp/gdi/32bpp.h"]=true;
	$f["/usr/local/include/freerdp/gdi/shape.h"]=true;
	$f["/usr/local/include/freerdp/gdi/pen.h"]=true;
	$f["/usr/local/include/freerdp/gdi/dc.h"]=true;
	$f["/usr/local/include/freerdp/gdi/8bpp.h"]=true;
	$f["/usr/local/include/freerdp/gdi/brush.h"]=true;
	$f["/usr/local/include/freerdp/gdi/gdi.h"]=true;
	$f["/usr/local/include/freerdp/gdi/clipping.h"]=true;
	$f["/usr/local/include/freerdp/gdi/palette.h"]=true;
	$f["/usr/local/include/freerdp/locale"]=true;
	$f["/usr/local/include/freerdp/locale/timezone.h"]=true;
	$f["/usr/local/include/freerdp/locale/keyboard.h"]=true;
	$f["/usr/local/include/freerdp/locale/locale.h"]=true;
	$f["/usr/local/include/freerdp/rail"]=true;
	$f["/usr/local/include/freerdp/rail/rail.h"]=true;
	$f["/usr/local/include/freerdp/rail/window.h"]=true;
	$f["/usr/local/include/freerdp/rail/icon.h"]=true;
	$f["/usr/local/include/freerdp/rail/window_list.h"]=true;
	$f["/usr/local/include/freerdp/utils"]=true;
	$f["/usr/local/include/freerdp/utils/tcp.h"]=true;
	$f["/usr/local/include/freerdp/utils/uds.h"]=true;
	$f["/usr/local/include/freerdp/utils/bitmap.h"]=true;
	$f["/usr/local/include/freerdp/utils/signal.h"]=true;
	$f["/usr/local/include/freerdp/utils/pcap.h"]=true;
	$f["/usr/local/include/freerdp/utils/rail.h"]=true;
	$f["/usr/local/include/freerdp/utils/time.h"]=true;
	$f["/usr/local/include/freerdp/utils/svc_plugin.h"]=true;
	$f["/usr/local/include/freerdp/utils/list.h"]=true;
	$f["/usr/local/include/freerdp/utils/passphrase.h"]=true;
	$f["/usr/local/include/freerdp/utils/stopwatch.h"]=true;
	$f["/usr/local/include/freerdp/utils/msusb.h"]=true;
	$f["/usr/local/include/freerdp/utils/debug.h"]=true;
	$f["/usr/local/include/freerdp/utils/profiler.h"]=true;
	$f["/usr/local/include/freerdp/utils/event.h"]=true;
	$f["/usr/local/include/freerdp/client"]=true;
	$f["/usr/local/include/freerdp/client/tsmf.h"]=true;
	$f["/usr/local/include/freerdp/client/cliprdr.h"]=true;
	$f["/usr/local/include/freerdp/client/cmdline.h"]=true;
	$f["/usr/local/include/freerdp/client/channels.h"]=true;
	$f["/usr/local/include/freerdp/client/drdynvc.h"]=true;
	$f["/usr/local/include/freerdp/client/rdpei.h"]=true;
	$f["/usr/local/include/freerdp/client/file.h"]=true;
	$f["/usr/local/include/freerdp/client/disp.h"]=true;
	$f["/usr/local/include/freerdp/server"]=true;
	$f["/usr/local/include/freerdp/server/cliprdr.h"]=true;
	$f["/usr/local/include/freerdp/server/rdpsnd.h"]=true;
	$f["/usr/local/include/freerdp/server/audin.h"]=true;
	$f["/usr/local/include/freerdp/server/channels.h"]=true;
	$f["/usr/local/include/freerdp/server/drdynvc.h"]=true;
	$f["/usr/local/include/freerdp/server/rdpdr.h"]=true;
	$f["/usr/local/include/freerdp/channels"]=true;
	$f["/usr/local/include/freerdp/channels/rdpsnd.h"]=true;
	$f["/usr/local/include/freerdp/channels/channels.h"]=true;
	$f["/usr/local/include/freerdp/channels/rdpdr.h"]=true;
	$f["/usr/local/include/freerdp/channels/wtsvc.h"]=true;
	$f["/usr/local/lib/libfreerdp-utils.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-utils.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-utils.so"]=true;
	$f["/usr/local/lib/libfreerdp-utils.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-common.so.1.1.0-beta1"]=true;
	$f["/usr/local/lib/libfreerdp-common.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-common.so"]=true;
	$f["/usr/local/lib/libfreerdp-common.so.1.1.0-beta1"]=true;
	$f["/usr/local/lib/libfreerdp-gdi.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-gdi.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-gdi.so"]=true;
	$f["/usr/local/lib/libfreerdp-gdi.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-rail.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-rail.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-rail.so"]=true;
	$f["/usr/local/lib/libfreerdp-rail.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-cache.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-cache.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-cache.so"]=true;
	$f["/usr/local/lib/libfreerdp-cache.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-codec.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-codec.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-codec.so"]=true;
	$f["/usr/local/lib/libfreerdp-codec.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-crypto.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-crypto.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-crypto.so"]=true;
	$f["/usr/local/lib/libfreerdp-crypto.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-locale.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-locale.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-locale.so"]=true;
	$f["/usr/local/lib/libfreerdp-locale.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-primitives.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-primitives.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-primitives.so"]=true;
	$f["/usr/local/lib/libfreerdp-primitives.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-core.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-core.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-core.so"]=true;
	$f["/usr/local/lib/libfreerdp-core.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-client.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-client.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-client.so"]=true;
	$f["/usr/local/lib/libfreerdp-client.so.1.1.0"]=true;
	$f["/usr/local/lib/libxfreerdp-client.so.1.1.0"]=true;
	$f["/usr/local/lib/libxfreerdp-client.so.1.1"]=true;
	$f["/usr/local/lib/libxfreerdp-client.so"]=true;
	$f["/usr/local/lib/libxfreerdp-client.so.1.1.0"]=true;
	$f["/usr/local/bin/xfreerdp"]=true;
	$f["/usr/local/bin/xfreerdp"]=true;
	$f["/usr/local/lib/libfreerdp-server.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-server.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-server.so"]=true;
	$f["/usr/local/lib/libfreerdp-server.so.1.1.0"]=true;
	$f["/usr/local/lib/libxfreerdp-server.so.1.1.0"]=true;
	$f["/usr/local/lib/libxfreerdp-server.so.1.1"]=true;
	$f["/usr/local/lib/libxfreerdp-server.so"]=true;
	$f["/usr/local/lib/libxfreerdp-server.so.1.1.0"]=true;
	$f["/usr/local/bin/xfreerdp-server"]=true;
	$f["/usr/local/bin/xfreerdp-server"]=true;	
	$f["/etc/ld.so.conf.d/freerdp.conf"]=true;	
	
}

function nginx_compile(){
	$f[]="./configure";
	$f[]="--with-luajit ";
	$f[]="--sbin-path=/usr/sbin/nginx ";
	$f[]="--prefix=/usr/share/nginx ";
	$f[]="--conf-path=/etc/nginx/nginx.conf ";
	$f[]="--error-log-path=/var/log/nginx/error.log ";
	$f[]="--http-client-body-temp-path=/var/lib/nginx/body ";
	$f[]="--http-fastcgi-temp-path=/var/lib/nginx/fastcgi ";
	$f[]="--http-log-path=/var/log/nginx/access.log ";
	$f[]="--http-proxy-temp-path=/var/lib/nginx/proxy ";
	$f[]="--http-scgi-temp-path=/var/lib/nginx/scgi ";
	$f[]="--http-uwsgi-temp-path=/var/lib/nginx/uwsgi ";
	$f[]="--lock-path=/var/lock/nginx.lock ";
	$f[]="--pid-path=/var/run/nginx.pid";
	$f[]="--with-pcre-jit";
	$f[]="--with-debug";
	$f[]="--with-http_addition_module ";
	$f[]="--with-http_dav_module ";
	$f[]="--with-http_geoip_module ";
	$f[]="--with-http_gzip_static_module ";
	$f[]="--with-http_image_filter_module ";
	$f[]="--with-http_realip_module ";
	$f[]="--with-http_stub_status_module ";
	$f[]="--with-http_ssl_module ";
	$f[]="--with-http_sub_module";
	$f[]="--with-http_xslt_module";
	$f[]="--with-ipv6";
	$f[]="--with-mail";
	$f[]="--with-mail_ssl_module";
	$f[]="--with-http_realip_module";
	$f[]="--with-http_addition_module";
	$f[]="--with-http_xslt_module";
	$f[]="--with-http_image_filter_module";
	$f[]="--with-http_geoip_module";
	$f[]="--with-http_sub_module";
	$f[]="--with-http_dav_module";
	$f[]="--with-http_flv_module";
	$f[]="--with-http_gzip_static_module";
	$f[]="--with-http_random_index_module";
	$f[]="--with-http_secure_link_module";
	$f[]="--with-http_degradation_module";
	$f[]="--with-http_stub_status_module";
	echo @implode(" ", $f);
	
}

function package_nginx(){
	/*
	 * 
	 * 
	 * http://openresty.org/#Download
cd /root
wget http://openresty.org/download/ngx_openresty-1.2.8.6.tar.gz
tar -xf ngx_openresty-1.2.8.6.tar.gz
cd ngx_openresty-1.2.7.8
git config --global http.proxy http://192.168.1.245:3140

cd ngx_openresty-1.2.8.6/bundle
git clone git://github.com/yaoweibin/ngx_http_substitutions_filter_module.git
mv ngx_http_substitutions_filter_module ngx_http_substitutions_filter_module-1.0

git clone https://github.com/kvspb/nginx-auth-ldap.git  
mv nginx-auth-ldap ngx_http_auth_ldap_module-1.0

cd ngx_openresty-1.2.8.6/bundle
git clone  https://github.com/yaoweibin/nginx_tcp_proxy_module.git 
cd ...
patch -p1 < bundle/nginx_tcp_proxy_module/tcp.patch

wget http://mdounin.ru/hg/ngx_http_auth_request_module/archive/a29d74804ff1.tar.gz
tar -xf a29d74804ff1.tar.gz
mv ngx_http_auth_request_module-a29d74804ff1 ngx_http_auth_request_module-1.0



git clone https://github.com/pagespeed/ngx_pagespeed.git
mv nginx-auth-ldap ngx_http_auth_ldap_module-1.0


// MArche pas
wget https://github.com/downloads/SpiderLabs/ModSecurity/modsecurity-apache_2.7.1.tar.gz
tar -xf modsecurity-apache_2.7.1.tar.gz
cd modsecurity-apache_2.7.1
./configure --enable-standalone-module --prefix=/usr
make 
make install
cp -rfv /modsecurity-apache_2.7.1/nginx/modsecurity /root/ngx_openresty-1.4.3.9/bundle/
mv modsecurity ngx_http_modsecurity-1.0
//

 *****
dans configure
[http_substitutions_filter_module=>'ngx_http_substitutions_filter_module'],
[http_auth_ldap_module=>'ngx_http_auth_ldap_module'],


// marche pas pour l'instant [http_modsecurity=>'ngx_http_modsecurity'],


// ne semble nécéssaire à partir de la v 1.3.9
[http_auth_request_module=>'ngx_http_auth_request_module'],

*****
* Inutile de rajouter l'option du module dans la ligne de commande.


	 */
	$base="/root/nginx-compiled";
	shell_exec("/bin/rm -rf $base");
	shell_exec("/bin/rm -rf /etc/nginx/sites-enabled/*");
	$f[]="/usr/share/nginx";
	$f[]="/usr/sbin/nginx";
	$f[]="/etc/nginx";
	$f[]="/var/lib/nginx";
	$f[]="/usr/lib/libxslt.a";
	$f[]="/usr/lib/libxslt.la";
	$f[]="/usr/lib/libxslt.so";
	$f[]="/usr/lib/libxslt.so.1";
	$f[]="/usr/lib/libxslt.so.1.1.26";
	$f[]="/usr/lib32/libxslt.so.1";
	$f[]="/usr/lib32/libxslt.so.1.1.26";
	$f[]="/usr/lib/mod_security2.so";
	$f[]="/usr/lib/mod_security2.la";
	$f[]="/usr/lib/mod_security2.a";
	$f[]="/usr/lib/standalone.so";
	$f[]="/usr/lib/standalone.la";
	$f[]="/usr/lib/standalone.a";

	$Debian=DebianVersion();
	$Architecture=Architecture();
	$version=package_nginx_version();	
	
	while (list ($num, $filename) = each ($f)){
	
		if(is_dir($filename)){
			@mkdir("$base/$filename",0755,true);
			echo "/bin/cp -rf $filename/* $base$filename/\n";
			shell_exec("/bin/cp -rf $filename/* $base$filename/");
			continue;
		}
	
		
		if(is_file($filename)){
			$dirname=dirname($filename);
			if(!is_dir("$base/$dirname")){@mkdir("$base/$dirname",0755,true);}
			echo "/bin/cp -f $filename $base$dirname/\n";
			shell_exec("/bin/cp -f $filename $base/$dirname/");
		}
	
	}

	chdir($base);
	if(is_file("$base/nginx-$Architecture-$version.tar.gz")){
		@unlink("$base/nginx-$Architecture-$version.tar.gz");
	}
	
	$filename="nginx-debian{$Debian}-$Architecture-$version.tar.gz";
	shell_exec("/bin/rm -rf $base/etc/nginx/sites-enabled/*");
	shell_exec("tar -czf $filename *");
	shell_exec("/bin/cp $filename /root/");
	echo "/root/$filename done\n";	
	
}
function package_nginx_version(){
	
	$unix=new unix();
	$nginx=$unix->find_program("nginx");
	if(!is_file($nginx)){return;}
	$php5=$unix->LOCATE_PHP5_BIN();
	exec("$nginx -V 2>&1",$results);
	
	while (list ($key, $value) = each ($results) ){
		if(preg_match("#nginx version: .*?\/([0-9\.]+)#", $value,$re)){return $re[1];}
		if(preg_match("#TLS SNI support enabled#", $value,$re)){$ARRAY["DEF"]["TLS"]=true;continue;}
	}	
}

