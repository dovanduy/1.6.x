<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');

$unix=new unix();

$GLOBALS["SHOW_COMPILE_ONLY"]=false;
$GLOBALS["NO_COMPILE"]=false;
$GLOBALS["REPOS"]=false;
if($argv[1]=='--compile'){$GLOBALS["SHOW_COMPILE_ONLY"]=true;}
if(preg_match("#--no-compile#", @implode(" ", $argv))){$GLOBALS["NO_COMPILE"]=true;}
if(preg_match("#--verbose#", @implode(" ", $argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--repos#", @implode(" ", $argv))){$GLOBALS["REPOS"]=true;}
if(preg_match("#--force#", @implode(" ", $argv))){$GLOBALS["FORCE"]=true;}

$GLOBALS["URISRC"]="ftp://ftp.porcupine.org/mirrors/postfix-release/experimental";

if($argv[1]=="--factorize"){factorize($argv[2]);exit;}
if($argv[1]=="--serialize"){serialize_tests();exit;}
if($argv[1]=="--latests"){latests();exit;}
if($argv[1]=="--latest"){echo "Latest:". latests()."\n";exit;}
if($argv[1]=="--create-package"){create_package();exit;}
if($argv[1]=="--parse-install"){parse_install($argv[2]);exit;}
if($argv[1]=="--compile-options"){echo "\n".GetCompilationOption()."\n";die();}



$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");

  if(is_file('/usr/include/openssl/ssl.h')){$include_openssl='/usr/include/openssl';}
  if(is_file('/usr/include/sasl/sasl.h')){$include_sasl='/usr/include/sasl';}
  if(is_file('/usr/include/cdb.h')){$include_cdb='/usr/include';}
  
  if(!is_file("/usr/include/db.h")){
  	 echo "include DB?.....: /usr/include/db.h no such file, apt-get install libdb-dev ?\n";
  	 return;
  }
  
  echo "include CDB.....: $include_cdb\n";
  echo "include SASL....: $include_sasl\n";
  echo "include OPENSSL.: $include_openssl\n";



//http://ftp.samba.org/pub/samba/stable/


$dirsrc="postfix-0.0.0";
$Architecture=Architecture();

if(!$GLOBALS["NO_COMPILE"]){
	$v=latests();
	if(preg_match("#postfix-(.+?)#", $v,$re)){$dirsrc=$re[1];}
	system_admin_events("Downloading lastest file $v, working directory $dirsrc ...",__FUNCTION__,__FILE__,__LINE__);
}

if(!$GLOBALS["FORCE"]){
	if(is_file("/root/$v")){if($GLOBALS["REPOS"]){echo "No updates...\n";die();}}
}

if(is_dir("/root/postfix-builder")){shell_exec("$rm -rf /root/postfix-builder");}
chdir("/root");
if(!$GLOBALS["NO_COMPILE"]){
	if(is_dir("/root/$dirsrc")){shell_exec("/bin/rm -rf /root/$dirsrc");}
	@mkdir("/root/$dirsrc");
	if(!is_file("/root/$v")){
		echo "Downloading $v ...\n";
		shell_exec("$wget {$GLOBALS["URISRC"]}/$v");
		if(!is_file("/root/$v")){echo "Downloading failed...\n";die();}
	}
	
	shell_exec("$tar -xf /root/$v -C /root/$dirsrc/");
	chdir("/root/$dirsrc");
	if(!is_file("/root/$dirsrc/Makefile")){
		echo "/root/$dirsrc/Makefile no such file\n";
		$dirs=$unix->dirdir("/root/$dirsrc");
		while (list ($num, $ligne) = each ($dirs) ){if(!is_file("$ligne/Makefile")){echo "$ligne/Makefile no such file\n";}else{
			chdir("$ligne");echo "Change to dir $ligne\n";
			$SOURCE_DIRECTORY=$ligne;
			$SOURCESOURCE_DIRECTORY=$ligne;
			break;}}
	}
	
}

$SOURCE_DIRECTORY2=dirname($SOURCE_DIRECTORY);
echo "Source directory: $SOURCE_DIRECTORY ($SOURCE_DIRECTORY2)\n";




chdir($SOURCE_DIRECTORY);
if(is_file("$SOURCE_DIRECTORY/autogen.sh")){echo "Executing autogen.sh\n";exec("./autogen.sh",$results);while (list ($num, $ligne) = each ($results) ){echo "autogen.sh::".$ligne."\n";}}else{echo "$SOURCE_DIRECTORY/autogen.sh no such file\n";}

shell_exec("make tidy");
shell_exec('/bin/mv /usr/sbin/sendmail /usr/sbin/sendmail.OFF >/dev/null');
shell_exec('/bin/mv /usr/bin/newaliases /usr/bin/newaliases.OFF >/dev/null');
shell_exec('/bin/mv /usr/bin/mailq /usr/bin/mailq.OFF >/dev/null');
shell_exec('/bin/chmod 755 /usr/sbin/sendmail.OFF /usr/bin/newaliases.OFF /usr/bin/mailq.OFF >/dev/null');



shell_exec("useradd postfix");
shell_exec("groupadd postdrop");

$configure=GetCompilationOption();

if($GLOBALS["SHOW_COMPILE_ONLY"]){echo $configure."\n";die();}

echo "Executing `$configure`\n";

if(!$GLOBALS["NO_COMPILE"]){
	
	echo "configuring...\n";
	system($configure);
	echo "make...\n";
	system("make upgrade");
	echo "make non-interactive-package...\n";
	echo "Make non-interactive-package\n";
	system("make non-interactive-package /");
	echo "Done...\n";
}
	echo "Creating package...\n";
	$package=create_package();
	

	
	echo "package created was \"$package\"\n";
	
	
	if(is_file("/root/ftp-password")){
		echo "Uploading $package...\n";
		shell_exec("curl -T $package ftp://www.artica.fr/download/ --user ".@file_get_contents("/root/ftp-password"));
		if(is_file("/root/rebuild-artica")){shell_exec("$wget \"".@file_get_contents("/root/rebuild-artica")."\" -O /tmp/rebuild.html");}
		
	}

	
		
	
	

function POSTFIX_VERSION(){
	exec("/usr/sbin/postconf -h mail_version 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#^([0-9\.\-]+)#", $ligne,$re)){
			return $re[1];
		}
		
	}
	
}

function GetCompilationOption(){
	
 if(is_file('/usr/include/openssl/ssl.h')){$include_openssl='/usr/include/openssl';}
  if(is_file('/usr/include/sasl/sasl.h')){$include_sasl='/usr/include/sasl';}
  if(is_file('/usr/include/cdb.h')){$include_cdb='/usr/include';}
  
  if(!is_file("/usr/include/db.h")){
  	 echo "include DB?.....: /usr/include/db.h no such file, apt-get install libdb-dev ?\n";
  	 
  }
  
  echo "include CDB.....: $include_cdb\n";
  echo "include SASL....: $include_sasl\n";
  echo "include OPENSSL.: $include_openssl\n";
  
  
  $cmd[]='/usr/bin/make makefiles CCARGS="-I/usr/include/libmilter -I/usr/include -I/usr/local/include -I/usr/include/sm/os';
  $cmd[]=' -DMAX_DYNAMIC_MAPS';
  $cmd[]=' -DMYORIGIN_FROM_FILE';
  $cmd[]=' -D_LARGEFILE_SOURCE';
  $cmd[]=' -D_FILE_OFFSET_BITS=64';
  $cmd[]=' -DHAS_LDAP';
  $cmd[]=' -DHAS_SSL -I'.$include_openssl;
  $cmd[]=' -DUSE_SASL_AUTH -I'.$include_sasl;
  $cmd[]=' -DUSE_CYRUS_SASL';
  $cmd[]=' -DUSE_TLS"';
  $cmd[]=' DEBUG=';
  $cmd[]=' AUXLIBS="-L/lib -L/usr/local/lib -L/usr/lib/libmilter -L/usr/lib -lldap -L/usr/lib -llber -lssl -lcrypto -lsasl2" OPT="-O2"';  	
  return @implode(" ", $cmd);
}




function create_package(){
	
	if(is_dir("/root/postfix-builder")){
		echo "Cleaning /root/postfix-builder\n";
		shell_exec("/bin/rm -rf /root/postfix-builder");
	}
	
$f[]="/usr/libexec/postfix/anvil";
$f[]="/usr/libexec/postfix/bounce";
$f[]="/usr/libexec/postfix/cleanup";
$f[]="/usr/libexec/postfix/discard";
$f[]="/usr/libexec/postfix/dnsblog";
$f[]="/usr/libexec/postfix/error";
$f[]="/usr/libexec/postfix/flush";
$f[]="/usr/libexec/postfix/local";
$f[]="/usr/libexec/postfix/master";
$f[]="/usr/libexec/postfix/oqmgr";
$f[]="/usr/libexec/postfix/pickup";
$f[]="/usr/libexec/postfix/pipe";
$f[]="/usr/libexec/postfix/post-install";
$f[]="/usr/libexec/postfix/postfix-files";
$f[]="/usr/libexec/postfix/postfix-script";
$f[]="/usr/libexec/postfix/postfix-wrapper";
$f[]="/usr/libexec/postfix/postmulti-script";
$f[]="/usr/libexec/postfix/postscreen";
$f[]="/usr/libexec/postfix/proxymap";
$f[]="/usr/libexec/postfix/qmgr";
$f[]="/usr/libexec/postfix/qmqpd";
$f[]="/usr/libexec/postfix/scache";
$f[]="/usr/libexec/postfix/showq";
$f[]="/usr/libexec/postfix/smtp";
$f[]="/usr/libexec/postfix/smtpd";
$f[]="/usr/libexec/postfix/spawn";
$f[]="/usr/libexec/postfix/tlsproxy";
$f[]="/usr/libexec/postfix/tlsmgr";
$f[]="/usr/libexec/postfix/trivial-rewrite";
$f[]="/usr/libexec/postfix/verify";
$f[]="/usr/libexec/postfix/virtual";
$f[]="/usr/libexec/postfix/nqmgr";
$f[]="/usr/libexec/postfix/lmtp";
$f[]="/usr/sbin/postalias";
$f[]="/usr/sbin/postcat";
$f[]="/usr/sbin/postconf";
$f[]="/usr/sbin/postfix";
$f[]="/usr/sbin/postkick";
$f[]="/usr/sbin/postlock";
$f[]="/usr/sbin/postlog";
$f[]="/usr/sbin/postmap";
$f[]="/usr/sbin/postmulti";
$f[]="/usr/sbin/postsuper";
$f[]="/usr/sbin/postdrop";
$f[]="/usr/sbin/postqueue";
$f[]="/usr/sbin/sendmail";
$f[]="/usr/bin/newaliases";
$f[]="/usr/bin/mailq";
$f[]="/etc/postfix/LICENSE";
$f[]="/etc/postfix/TLS_LICENSE";
$f[]="/etc/postfix/access";
$f[]="/etc/postfix/aliases";
$f[]="/etc/postfix/bounce.cf.default";
$f[]="/etc/postfix/canonical";
$f[]="/etc/postfix/generic";
$f[]="/etc/postfix/header_checks";
$f[]="/etc/postfix/main.cf.default";
$f[]="/etc/postfix/makedefs.out";
$f[]="/etc/postfix/relocated";
$f[]="/etc/postfix/transport";
$f[]="/etc/postfix/virtual";
$f[]="/usr/local/man/man1/mailq.1";
$f[]="/usr/local/man/man1/newaliases.1";
$f[]="/usr/local/man/man1/postalias.1";
$f[]="/usr/local/man/man1/postcat.1";
$f[]="/usr/local/man/man1/postconf.1";
$f[]="/usr/local/man/man1/postdrop.1";
$f[]="/usr/local/man/man1/postfix.1";
$f[]="/usr/local/man/man1/postkick.1";
$f[]="/usr/local/man/man1/postlock.1";
$f[]="/usr/local/man/man1/postlog.1";
$f[]="/usr/local/man/man1/postmap.1";
$f[]="/usr/local/man/man1/postmulti.1";
$f[]="/usr/local/man/man1/postqueue.1";
$f[]="/usr/local/man/man1/postsuper.1";
$f[]="/usr/local/man/man1/sendmail.1";
$f[]="/usr/local/man/man5/access.5";
$f[]="/usr/local/man/man5/aliases.5";
$f[]="/usr/local/man/man5/body_checks.5";
$f[]="/usr/local/man/man5/bounce.5";
$f[]="/usr/local/man/man5/canonical.5";
$f[]="/usr/local/man/man5/cidr_table.5";
$f[]="/usr/local/man/man5/generic.5";
$f[]="/usr/local/man/man5/header_checks.5";
$f[]="/usr/local/man/man5/ldap_table.5";
$f[]="/usr/local/man/man5/master.5";
$f[]="/usr/local/man/man5/memcache_table.5";
$f[]="/usr/local/man/man5/mysql_table.5";
$f[]="/usr/local/man/man5/sqlite_table.5";
$f[]="/usr/local/man/man5/nisplus_table.5";
$f[]="/usr/local/man/man5/pcre_table.5";
$f[]="/usr/local/man/man5/pgsql_table.5";
$f[]="/usr/local/man/man5/postconf.5";
$f[]="/usr/local/man/man5/postfix-wrapper.5";
$f[]="/usr/local/man/man5/regexp_table.5";
$f[]="/usr/local/man/man5/relocated.5";
$f[]="/usr/local/man/man5/tcp_table.5";
$f[]="/usr/local/man/man5/transport.5";
$f[]="/usr/local/man/man5/virtual.5";
$f[]="/usr/local/man/man8/bounce.8";
$f[]="/usr/local/man/man8/cleanup.8";
$f[]="/usr/local/man/man8/anvil.8";
$f[]="/usr/local/man/man8/defer.8";
$f[]="/usr/local/man/man8/discard.8";
$f[]="/usr/local/man/man8/dnsblog.8";
$f[]="/usr/local/man/man8/error.8";
$f[]="/usr/local/man/man8/flush.8";
$f[]="/usr/local/man/man8/lmtp.8";
$f[]="/usr/local/man/man8/local.8";
$f[]="/usr/local/man/man8/master.8";
$f[]="/usr/local/man/man8/oqmgr.8";
$f[]="/usr/local/man/man8/pickup.8";
$f[]="/usr/local/man/man8/pipe.8";
$f[]="/usr/local/man/man8/postscreen.8";
$f[]="/usr/local/man/man8/proxymap.8";
$f[]="/usr/local/man/man8/qmgr.8";
$f[]="/usr/local/man/man8/qmqpd.8";
$f[]="/usr/local/man/man8/scache.8";
$f[]="/usr/local/man/man8/showq.8";
$f[]="/usr/local/man/man8/smtp.8";
$f[]="/usr/local/man/man8/smtpd.8";
$f[]="/usr/local/man/man8/spawn.8";
$f[]="/usr/local/man/man8/tlsproxy.8";
$f[]="/usr/local/man/man8/tlsmgr.8";
$f[]="/usr/local/man/man8/trace.8";
$f[]="/usr/local/man/man8/trivial-rewrite.8";
$f[]="/usr/local/man/man8/verify.8";
$f[]="/usr/local/man/man8/virtual.8";
$f[]="/usr/lib/libutil.a";
$f[]="/usr/lib/libglobal.a";
$f[]="/usr/lib/libdns.a";
$f[]="/usr/lib/libtls.a";
$f[]="/usr/lib/libxsasl.a";
$f[]="/usr/lib/libmilter.a";
$f[]="/usr/lib/libmaster.a";
$f[]="/usr/share/doc/mailgraph";
$f[]="/usr/share/doc/mailgraph/README.Debian";
$f[]="/usr/share/doc/mailgraph/README";
$f[]="/usr/share/doc/mailgraph/copyright";
$f[]="/usr/share/doc/mailgraph/changelog.Debian.gz";
$f[]="/usr/share/doc/mailgraph/changelog.gz";
$f[]="/usr/share/doc/mailgraph/README.fetchmail";
$f[]="/usr/share/doc/queuegraph";
$f[]="/usr/share/doc/queuegraph/copyright";
$f[]="/usr/share/doc/queuegraph/changelog.Debian.gz";
$f[]="/usr/share/doc/sanitizer";
$f[]="/usr/share/doc/sanitizer/README.Debian";
$f[]="/usr/share/doc/sanitizer/sanitizer.html";
$f[]="/usr/share/doc/sanitizer/examples";
$f[]="/usr/share/doc/sanitizer/examples/sanitizer.cfg1";
$f[]="/usr/share/doc/sanitizer/examples/sanitizer.maildrop";
$f[]="/usr/share/doc/sanitizer/examples/sanitizer.cfg2";
$f[]="/usr/share/doc/sanitizer/examples/procmailrc";
$f[]="/usr/share/doc/sanitizer/copyright";
$f[]="/usr/share/doc/sanitizer/CREDITS";
$f[]="/usr/share/doc/sanitizer/changelog.Debian.gz";
$f[]="/usr/share/doc/sanitizer/changelog.gz";
$f[]="/usr/share/doc/sanitizer/README.sanitizer";
$f[]="/usr/share/man/man1/sanitizer.1.gz";
$f[]="/usr/share/man/man1/simplify.1.gz";
$f[]="/usr/share/perl5/Anomy";
$f[]="/usr/share/perl5/Anomy/Sanitizer";
$f[]="/usr/share/perl5/Anomy/Sanitizer/FProt.pm";
$f[]="/usr/share/perl5/Anomy/Sanitizer/FileTypes.pm";
$f[]="/usr/share/perl5/Anomy/Sanitizer/Scoring.pm";
$f[]="/usr/share/perl5/Anomy/Sanitizer/MacroScanner.pm";
$f[]="/usr/share/perl5/Anomy/HTMLCleaner.pm";
$f[]="/usr/share/perl5/Anomy/Log.pm";
$f[]="/usr/share/perl5/Anomy/MIMEStream.pm";
$f[]="/usr/share/perl5/Anomy/Sanitizer.pm";
$f[]="/usr/share/doc-base/sanitizer";
$f[]="/usr/share/queuegraph/count.sh";
$f[]="/usr/share/sanitizer/contrib";
$f[]="/usr/share/sanitizer/contrib/zip_script";
$f[]="/usr/share/sanitizer/contrib/zip_policy.pl";
$f[]="/usr/share/sanitizer/contrib/postfix.txt";
$f[]="/usr/share/sanitizer/contrib/check_for_virus";
$f[]="/usr/share/sanitizer/contrib/sendmail-m4.txt";
$f[]="/usr/share/sanitizer/contrib/sanitizer.procmail";
$f[]="/usr/share/sanitizer/contrib/anomy.m4";
$f[]="/usr/share/sanitizer/contrib/tnef2multipart.pl";
$f[]="/usr/share/sanitizer/testcases";
$f[]="/usr/share/sanitizer/testcases/sanitizer.logging.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.rev1_58.t";
$f[]="/usr/share/sanitizer/testcases/mime.types";
$f[]="/usr/share/sanitizer/testcases/sanitizer.defaults.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.plugin.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.msg-crlf.t";
$f[]="/usr/share/sanitizer/testcases/README";
$f[]="/usr/share/sanitizer/testcases/sanitizer.uu-rfc822.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.tnef.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.exchange.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.rev1_64.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.boundary.t";
$f[]="/usr/share/sanitizer/testcases/tests.conf.SAMPLE";
$f[]="/usr/share/sanitizer/testcases/testall.sh";
$f[]="/usr/share/sanitizer/testcases/sanitizer.bad_html.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.rev1_60.t";
$f[]="/usr/share/sanitizer/testcases/rot13";
$f[]="/usr/share/sanitizer/testcases/sanitizer.mime_depth.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.filenames.hlp";
$f[]="/usr/share/sanitizer/testcases/sanitizer.force_hdr.t";
$f[]="/usr/share/sanitizer/testcases/simplify.multipart.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.fprotd.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.forwarded.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.partial.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.base64.t";
$f[]="/usr/share/sanitizer/testcases/results.def";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.rev1_60.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.appledouble.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.force_hdr.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.plugin.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.pgptext.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.rev1_75.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.partial.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.msg-crlf.ok.rot13";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.forwarded.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.exchange.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.rev1_58.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.logging.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.uu-rfc822.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.filenames.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.tnef.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.rev1_71.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.rev1_64.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.base64.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.boundary.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.mime_depth.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.rfc822.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.bad_html.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/simplify.multipart.ok";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.fprotd.ok.rot13";
$f[]="/usr/share/sanitizer/testcases/results.def/sanitizer.defaults.ok";
$f[]="/usr/share/sanitizer/testcases/sanitizer.filenames.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.appledouble.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.rev1_75.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.rfc822.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.rev1_71.t";
$f[]="/usr/share/sanitizer/testcases/sanitizer.pgptext.t";
$f[]="/usr/bin/simplify";
$f[]="/usr/bin/sanitizer";
$f[]="/usr/lib/cgi-bin/queuegraph.cgi";
$f[]="/usr/lib/cgi-bin/mailgraph.cgi";
$f[]="/usr/sbin/mailgraph";
$f[]="/var/cache/queuegraph";
$f[]="/var/lib/queuegraph";
$f[]="/etc/init.d/mailgraph";
$f[]="/etc/cron.d/queuegraph";
$f[]="/usr/local/bin/milter-greylist";
$f[]="/usr/local/etc/mail/greylist.conf";
$f[]="/usr/bin/sa-awl";
$f[]="/usr/bin/sa-check_spamd";
$f[]="/usr/bin/sa-compile";
$f[]="/usr/bin/sa-learn";
$f[]="/usr/bin/sa-update";
$f[]="/usr/bin/spamassassin";
$f[]="/usr/sbin/spamd";
$f[]="/usr/bin/spamassassin";
$f[]="/usr/bin/sa-check_spamd";
$f[]="/usr/bin/sa-learn";
$f[]="/usr/bin/sa-compile";
$f[]="/usr/bin/sa-awl";
$f[]="/usr/bin/sa-update";
$f[]="/usr/local/sbin/amavisd";
$f[]="/usr/local/lib/libzmq.a";
$f[]="/usr/local/lib/libzmq.la";
$f[]="/usr/local/lib/libzmq.so";
$f[]="/usr/local/lib/libzmq.so.1";
$f[]="/usr/local/lib/libzmq.so.1.0.1";
$f[]="/usr/local/include/zmq.h";
$f[]="/usr/local/include/zmq.hpp";
$f[]="/usr/local/include/zmq_utils.h";
$f[]="/usr/local/include/GeoIP.h ";
$f[]="/usr/local/include/GeoIPCity.h ";
$f[]="/usr/local/include/GeoIPUpdate.h";
$f[]="/usr/bin/sa-check_spamd";
$f[]="/usr/local/bin/ripmime";
$f[]="/usr/local/bin/mimedefang-multiplexor";
$f[]="/etc/mail/mimedefang-filter";
$f[]="/etc/mail/mimedefang-ip-key";
$f[]="/usr/local/bin/md-mx-ctrl";
$f[]="/usr/local/bin/mimedefang";
$f[]="/usr/local/bin/watch-mimedefang";
$f[]="/usr/local/bin/watch-multiple-mimedefangs.tcl";
$f[]="/usr/local/bin/mimedefang-util";
$f[]="/usr/local/bin/mimedefang.pl";
$f[]="/usr/bin/spamassassin";
$f[]="/usr/bin/spamc";
$f[]="/usr/bin/spamd";

mkdir('/root/postfix-builder/var/amavis/dspam',0755,true);
mkdir('/root/postfix-builder/usr/share/spamassassin',0755,true);
mkdir('/root/postfix-builder/etc/spamassassin',0755,true);
mkdir('/root/postfix-builder/var/lib/spamassassin',0755,true);
mkdir('/root/postfix-builder/var/spool/postfix/spamass',0755,true);
	

	while (list ($num, $ligne) = each ($f) ){
		$ligne=trim($ligne);
		if(is_dir($ligne)){echo "$ligne is a directory, skip\n";continue;}
		if(!is_file($ligne)){echo "\"$ligne\" no such file\n";continue;}
		$dir=dirname($ligne);
		echo "Installing $ligne in /root/postfix-builder$dir/\n";
		if(!is_dir("/root/postfix-builder$dir")){@mkdir("/root/postfix-builder$dir",0755,true);}
		shell_exec("/bin/cp -fd $ligne /root/postfix-builder$dir/");
		
	}
	@mkdir("/root/postfix-builder/etc/postfix",0755,true);
	@mkdir("/root/postfix-builder/var/spool/postfix",0755,true);
	shell_exec("/bin/cp -rfd /usr/libexec/postfix/* /root/postfix-builder/usr/libexec/postfix/");
	shell_exec("/bin/cp -rfd /etc/postfix/* /root/postfix-builder/etc/postfix/");
	shell_exec("/bin/cp -rfd /var/spool/postfix/* /root/postfix-builder/var/spool/postfix/");
	
$f=array();
$f[]="/etc/spamassassin";
$f[]="/usr/local/bin";
$f[]="/usr/local/sbin";
$f[]="/usr/local/lib";
$f[]="/usr/local/share/GeoIP";
$f[]="/usr/local/share/perl";
$f[]="/usr/local/lib/perl";
$f[]="/usr/share/perl/5.10.1/Mail";
$f[]="/usr/share/perl5/Mail";
$f[]="/usr/lib/perl/5.10.1/auto/Mail/SpamAssassin";
while (list ($num, $directory) = each ($f) ){
	$directory=trim($directory);
	if(!is_dir($directory)){echo "\"$directory\" no such directory\n";continue;}
	echo "installing \"$directory\"\n";
	$nextdir="/root/postfix-builder$directory";
	if(!is_dir($nextdir)){@mkdir($nextdir,0755,true);}
	shell_exec("/bin/cp -rfd $directory/* $nextdir/");
	
}	
$f=array();
$f[]="/usr/lib/perl5/NetAddr";
$f[]="/usr/share/spamassassin";
$f[]="/usr/local/lib/perl";
$f[]="/var/lib/spamassassin";	
$f[]="/usr/share/perl/5.10.1/Mail";
$f[]="/usr/share/perl5/Mail";
$f[]="/usr/lib/perl5/NetAddr";
$f[]="/usr/lib/perl5/auto/NetAddr";
while (list ($num, $directory) = each ($f) ){
	$directory=trim($directory);
	if(!is_dir($directory)){echo "\"$directory\" no such directory\n";continue;}
	
	$directoryTMP=dirname($directory);
	$nextdir="/root/postfix-builder$directoryTMP";
	if(!is_dir($nextdir)){@mkdir($nextdir,0755,true);}
	echo "/bin/cp -rfvd $directory $nextdir/\n";
	shell_exec("/bin/cp -rfvd $directory $nextdir/");
	
}	



	echo "Creating package done....\n";
	
	
	
	
	$POSTFIX_VERSION=POSTFIX_VERSION();
	$Architecture=Architecture();
	if($Architecture==64){$Architecture="x64";}
	if($Architecture==32){$Architecture="i386";}
	$nextfile="postfixp-$POSTFIX_VERSION-$Architecture-tar.gz";
	
	echo "Destination file: $nextfile\n";
	echo "chdir -> /root/postfix-builder\n";
	chdir("/root/postfix-builder");
	if(is_file("/root/$nextfile")){
		echo "Delete /root/$nextfile\n";
		@unlink("/root/$nextfile");
	}
	echo "tar -czf /root/$nextfile *\n";
	shell_exec("tar -czf /root/$nextfile *");
	return "/root/$nextfile";
}


	

function parse_install($filename){
	if(!is_file($filename)){echo "$filename no such file\n";return;}
	$f=file($filename);
	
	while (list ($num, $ligne) = each ($f) ){
		if(preg_match("#Installing (.+?)\s+as\s+(.+)#", $ligne,$re)){
			$re[1]=str_replace("///", "/", $re[2]);
			$target[]=$re[1];
			continue;
		}
		
		if(preg_match("#install\s+-c\s+.+?\/(.+?)\s+(.+)#", $ligne,$re)){
			$filename=$re[2]."/".basename($re[1]);
			$filename=str_replace("///", "/", $filename);
			$filename=str_replace("//", "/", $filename);
			$target[]=trim($filename);
		}
		
	}
	
	while (list ($num, $ligne) = each ($target) ){
		$dir=dirname($ligne);
		$mkdirs[trim($dir)]=true;
		$files[trim($ligne)]=true;
		
	}
	while (list ($num, $ligne) = each ($mkdirs) ){
		$tt="/root/samba-builder/$num";
		$tt=str_replace("//", "/", $tt);
		echo "@mkdir('$tt',0755,true);\n";
	}

	while (list ($num, $ligne) = each ($files) ){
			echo "\$f[]=\"$num\";\n";
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


function latests(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	shell_exec("$wget ftp://ftp.porcupine.org/mirrors/postfix-release/experimental/ -O /tmp/index.html");
	$f=explode("\n",@file_get_contents("/tmp/index.html"));
	//postfix-2.10-20120617
	while (list ($num, $line) = each ($f)){
		if(preg_match("#<a href=\".*?postfix-(.+?)\.tar\.gz#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "FOUND: {$re[1]} -> ". basename($re[1])."\n";}
			$ve=basename($re[1]);
			$SourceFile="$ve.tar.gz";
			if(strpos($re[1], "/")>0){$base=dirname($re[1]);}
			
			if(preg_match("#^([0-9]+)\.([0-9]+)-([0-9]+)#", $ve,$ri)){
				if(strlen($ri[2])==1){$ri[2]="{$ri[2]}0";}
				if(strlen($ri[3])==1){$ri[3]="{$ri[3]}0";}
				$ve="{$ri[1]}.{$ri[2]}.{$ri[3]}";
				
			}
			
			
			$ve=str_replace(".", "", $ve);
			$ve=str_replace("-", "", $ve);
			$versions[$ve]=$SourceFile;
		if($GLOBALS["VERBOSE"]){echo "$ve -> $file ({$ri[1]}.{$ri[2]}.{$ri[3]})\n";}
		}
	}
	
	krsort($versions);
	while (list ($num, $filename) = each ($versions)){
		$vv[]=$filename;
	}
	
	echo "Found latest file version: `{$vv[0]}` on base=$base\n";
	return $vv[0];
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










