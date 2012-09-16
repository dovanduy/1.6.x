<?php
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');

$unix=new unix();
$GLOBALS["SHOW_COMPILE_ONLY"]=false;
$GLOBALS["NO_COMPILE"]=false;
$GLOBALS["REPOS"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if($argv[1]=='--compile'){$GLOBALS["SHOW_COMPILE_ONLY"]=true;}
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




$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");

//http://www.squid-cache.org/Versions/v3/3.2/squid-3.2.0.13.tar.gz


$dirsrc="squid-3.2.0.16";
$Architecture=Architecture();

if(!$GLOBALS["NO_COMPILE"]){$v=latests();
	if(preg_match("#squid-(.+?)-#", $v,$re)){$dirsrc=$re[1];}
	system_admin_events("Downloading lastest file $v, working directory $dirsrc ...",__FUNCTION__,__FILE__,__LINE__);
}

if(!$GLOBALS["FORCE"]){
	if(is_file("/root/$v")){if($GLOBALS["REPOS"]){echo "No updates...\n";die();}}
}

if(is_dir("/root/squid-builder")){shell_exec("$rm -rf /root/squid-builder");}
chdir("/root");
if(!$GLOBALS["NO_COMPILE"]){
	if(is_dir("/root/$dirsrc")){shell_exec("/bin/rm -rf /root/$dirsrc");}
	@mkdir("/root/$dirsrc");
	if(!is_file("/root/$v")){
		system_admin_events("Detected new version $v", __FUNCTION__, __FILE__, __LINE__, "software");
		echo "Downloading $v ...\n";
		shell_exec("$wget http://www.squid-cache.org/Versions/v3/3.2/$v");
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
			chdir("$ligne");echo "Change to dir $ligne\n";
			$SOURCE_DIRECTORY=$ligne;
			break;}}
	}
	
}

$cmds[]="--prefix=/usr";
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
$cmds[]="--enable-async-io";
$cmds[]="--enable-delay-pools";
$cmds[]="--enable-http-violations";
//$cmds[]="--enable-ecap";
$cmds[]="--enable-ssl"; 
$cmds[]="--enable-ssl-crtd";
$cmds[]="CFLAGS=\"-O3 -pipe -fomit-frame-pointer -funroll-loops -ffast-math -fno-exceptions\""; 

//CPPFLAGS="-I../libltdl"



$configure="./configure ". @implode(" ", $cmds);

if($GLOBALS["SHOW_COMPILE_ONLY"]){echo $configure."\n";die();}
if(!$GLOBALS["NO_COMPILE"]){
	
	echo "configuring...\n";
	shell_exec($configure);
	echo "make...\n";
	shell_exec("make");
	system_admin_events("Installing the new squid-cache $v version", __FUNCTION__, __FILE__, __LINE__, "software");
	echo "make install...\n";
	
	$unix=new unix();
	$squid3=$unix->find_program("squid3");
	if(is_file($squid3)){@unlink($squid3);}
	remove_squid();
	echo "Make install\n";
	shell_exec("make install");
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
	


function create_package($t){
$unix=new unix();	
$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");
$Architecture=Architecture();
$version=squid_version();

shell_exec("wget http://www.artica.fr/download/anthony-icons.tar.gz -O /tmp/anthony-icons.tar.gz");
@mkdir("/usr/share/squid3/icons",0755,true);
shell_exec("tar -xf /tmp/anthony-icons.tar.gz -C /usr/share/squid3/icons/");
shell_exec("/bin/chown -R squid:squid /usr/share/squid3/icons/");

mkdir("/root/squid-builder/usr/share/squid3",0755,true);
mkdir("/root/squid-builder/etc/squid3",0755,true);
mkdir("/root/squid-builder/lib/squid3",0755,true);
mkdir("/root/squid-builder/usr/sbin",0755,true);
mkdir("/root/squid-builder/usr/bin",0755,true);
mkdir("/root/squid-builder/usr/share/squid-langpack",0755,true);

shell_exec("$cp -rf /usr/share/squid3/* /root/squid-builder/usr/share/squid3/");
if(!$GLOBALS["NO_COMPILE"]){shell_exec("/bin/cp -rf /usr/share/squid3/errors/templates/* /root/squid-builder/usr/share/squid3/errors/templates/");}
shell_exec("$cp -rf /etc/squid3/* /root/squid-builder/etc/squid3/");
shell_exec("$cp -rf /lib/squid3/* /root/squid-builder/lib/squid3/");
shell_exec("$cp -rf /usr/share/squid-langpack/* /root/squid-builder/usr/share/squid-langpack/");
shell_exec("$cp -rf /usr/sbin/squid /root/squid-builder/usr/sbin/squid");
shell_exec("$cp -rf /usr/bin/purge /root/squid-builder/usr/bin/purge");
shell_exec("$cp -rf /usr/bin/squidclient /root/squid-builder/usr/bin/squidclient");
echo "Compile SARG....\n";
compile_sarg();

if($Architecture==64){$Architecture="x64";}
if($Architecture==32){$Architecture="i386";}
echo "Compile Arch $Architecture v:$version\n";
chdir("/root/squid-builder");

$version=squid_version();
echo "Compressing....\n";
shell_exec("$tar -czf squid32-$Architecture-$version.tar.gz *");
system_admin_events("/root/squid-builder/squid32-$Architecture-$version.tar.gz  ready...",__FUNCTION__,__FILE__,__LINE__);
if(is_file("/root/ftp-password")){
	echo "/root/squid-builder/squid32-$Architecture-$version.tar.gz is now ready to be uploaded\n";
	shell_exec("curl -T /root/squid-builder/squid32-$Architecture-$version.tar.gz ftp://www.artica.fr/download/ --user ".@file_get_contents("/root/ftp-password"));
	system_admin_events("Uploading squid32-$Architecture-$version.tar.gz done.",__FUNCTION__,__FILE__,__LINE__);
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
	shell_exec("$wget http://www.squid-cache.org/Versions/v3/3.2/ -O /tmp/index.html");
	$f=explode("\n",@file_get_contents("/tmp/index.html"));
	while (list ($num, $line) = each ($f)){
		if(preg_match("#<a href=\"squid-(.+?)\.tar\.gz#", $line,$re)){
			$ve=$re[1];
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


function error_txt(){
$f[]="#rebuilded error template by script";	
$f[]="name: SQUID_X509_V_ERR_DOMAIN_MISMATCH";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"Certificate does not match domainname\"";
$f[]="";
$f[]="name: X509_V_ERR_UNABLE_TO_GET_ISSUER_CERT";
$f[]="detail: \"SSL Certficate error: certificate issuer (CA) not known: %ssl_ca_name\"";
$f[]="descr: \"Unable to get issuer certificate\"";
$f[]="";
$f[]="name: X509_V_ERR_UNABLE_TO_GET_CRL";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"Unable to get certificate CRL\"";
$f[]="";
$f[]="name: X509_V_ERR_UNABLE_TO_DECRYPT_CERT_SIGNATURE";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"Unable to decrypt certificate's signature\"";
$f[]="";
$f[]="name: X509_V_ERR_UNABLE_TO_DECRYPT_CRL_SIGNATURE";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"Unable to decrypt CRL's signature\"";
$f[]="";
$f[]="name: X509_V_ERR_UNABLE_TO_DECODE_ISSUER_PUBLIC_KEY";
$f[]="detail: \"Unable to decode issuer (CA) public key: %ssl_ca_name\"";
$f[]="descr: \"Unable to decode issuer public key\"";
$f[]="";
$f[]="name: X509_V_ERR_CERT_SIGNATURE_FAILURE";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"Certificate signature failure\"";
$f[]="";
$f[]="name: X509_V_ERR_CRL_SIGNATURE_FAILURE";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"CRL signature failure\"";
$f[]="";
$f[]="name: X509_V_ERR_CERT_NOT_YET_VALID";
$f[]="detail: \"SSL Certficate is not valid before: %ssl_notbefore\"";
$f[]="descr: \"Certificate is not yet valid\"";
$f[]="";
$f[]="name: X509_V_ERR_CERT_HAS_EXPIRED";
$f[]="detail: \"SSL Certificate expired on: %ssl_notafter\"";
$f[]="descr: \"Certificate has expired\"";
$f[]="";
$f[]="name: X509_V_ERR_CRL_NOT_YET_VALID";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"CRL is not yet valid\"";
$f[]="";
$f[]="name: X509_V_ERR_CRL_HAS_EXPIRED";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"CRL has expired\"";
$f[]="";
$f[]="name: X509_V_ERR_ERROR_IN_CERT_NOT_BEFORE_FIELD";
$f[]="detail: \"SSL Certificate has invalid start date (the 'not before' field): %ssl_subject\"";
$f[]="descr: \"Format error in certificate's notBefore field\"";
$f[]="";
$f[]="name: X509_V_ERR_ERROR_IN_CERT_NOT_AFTER_FIELD";
$f[]="detail: \"SSL Certificate has invalid expiration date (the 'not after' field): %ssl_subject\"";
$f[]="descr: \"Format error in certificate's notAfter field\"";
$f[]="";
$f[]="name: X509_V_ERR_ERROR_IN_CRL_LAST_UPDATE_FIELD";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"Format error in CRL's lastUpdate field\"";
$f[]="";
$f[]="name: X509_V_ERR_ERROR_IN_CRL_NEXT_UPDATE_FIELD";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"Format error in CRL's nextUpdate field\"";
$f[]="";
$f[]="name: X509_V_ERR_OUT_OF_MEM";
$f[]="detail: \"%ssl_error_descr\"";
$f[]="descr: \"Out of memory\"";
$f[]="";
$f[]="name: X509_V_ERR_DEPTH_ZERO_SELF_SIGNED_CERT";
$f[]="detail: \"Self-signed SSL Certificate: %ssl_subject\"";
$f[]="descr: \"Self signed certificate\"";
$f[]="";
$f[]="name: X509_V_ERR_SELF_SIGNED_CERT_IN_CHAIN";
$f[]="detail: \"Self-signed SSL Certificate in chain: %ssl_subject\"";
$f[]="descr: \"Self signed certificate in certificate chain\"";
$f[]="";
$f[]="name: X509_V_ERR_UNABLE_TO_GET_ISSUER_CERT_LOCALLY";
$f[]="detail: \"SSL Certficate error: certificate issuer (CA) not known: %ssl_ca_name\"";
$f[]="descr: \"Unable to get local issuer certificate\"";
$f[]="";
$f[]="name: X509_V_ERR_UNABLE_TO_VERIFY_LEAF_SIGNATURE";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"Unable to verify the first certificate\"";
$f[]="";
$f[]="name: X509_V_ERR_CERT_CHAIN_TOO_LONG";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"Certificate chain too long\"";
$f[]="";
$f[]="name: X509_V_ERR_CERT_REVOKED";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"Certificate revoked\"";
$f[]="";
$f[]="name: X509_V_ERR_INVALID_CA";
$f[]="detail: \"%ssl_error_descr: %ssl_ca_name\"";
$f[]="descr: \"Invalid CA certificate\"";
$f[]="";
$f[]="name: X509_V_ERR_PATH_LENGTH_EXCEEDED";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"Path length constraint exceeded\"";
$f[]="";
$f[]="name: X509_V_ERR_INVALID_PURPOSE";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"Unsupported certificate purpose\"";
$f[]="";
$f[]="name: X509_V_ERR_CERT_UNTRUSTED";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"Certificate not trusted\"";
$f[]="";
$f[]="name: X509_V_ERR_CERT_REJECTED";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"Certificate rejected\"";
$f[]="";
$f[]="name: X509_V_ERR_SUBJECT_ISSUER_MISMATCH";
$f[]="detail: \"%ssl_error_descr: %ssl_ca_name\"";
$f[]="descr: \"Subject issuer mismatch\"";
$f[]="";
$f[]="name: X509_V_ERR_AKID_SKID_MISMATCH";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"Authority and subject key identifier mismatch\"";
$f[]="";
$f[]="name: X509_V_ERR_AKID_ISSUER_SERIAL_MISMATCH";
$f[]="detail: \"%ssl_error_descr: %ssl_ca_name\"";
$f[]="descr: \"Authority and issuer serial number mismatch\"";
$f[]="";
$f[]="name: X509_V_ERR_KEYUSAGE_NO_CERTSIGN";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"Key usage does not include certificate signing\"";
$f[]="";
$f[]="name: X509_V_ERR_APPLICATION_VERIFICATION";
$f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
$f[]="descr: \"Application verification failure\";\n";
@file_put_contents("/usr/share/squid3/errors/templates/error-details.txt", @implode("\n", $f));

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


function package_c_icap(){
$f[]="/usr/bin/c-icap";
$f[]="/usr/bin/c-icap-client";
$f[]="/usr/bin/c-icap-config";
$f[]="/usr/bin/c-icap-libicapapi-config";
$f[]="/usr/bin/c-icap-mkbdb";
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
$f[]="/usr/lib/libicapapi.la";
$f[]="/usr/lib/libicapapi.so";
$f[]="/usr/lib/libicapapi.so.0";
$f[]="/usr/lib/libicapapi.so.0.0.7";
$f[]="/usr/share/man/man8/c-icap.8";
$f[]="/usr/share/man/man8/c-icap-client.8";
$f[]="/usr/share/man/man8/c-icap-config.8";
$f[]="/usr/share/man/man8/c-icap-libicapapi-config.8";
$f[]="/usr/share/man/man8/c-icap-mkbdb.8";
$f[]="/usr/share/man/man8/c-icap-stretch.8";
$f[]="/etc/c-icap.conf";
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
$f[]="/etc/srv_url_check.conf.default";
$f[]="/etc/srv_url_check.conf";
$f[]="/etc/srv_clamav.conf.default";
$f[]="/etc/srv_clamav.conf";

$base="/root/c-icap";
while (list ($num, $filename) = each ($f)){
	$dirname=dirname($filename);
	if(!is_dir("$base/$dirname")){@mkdir("$base/$dirname",0755,true);}
	shell_exec("/bin/cp -f $filename $base/$dirname/");
	
}	
	
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
	shell_exec("wget http://www.artica.fr/download/squid-ecap-av-1.0.3.tar.bz2");
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


