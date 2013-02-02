<?php
$GLOBALS["SCHEDULE_ID"]=0;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

run();
function run(){
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	$EnableZarafaSalearnSchedule=$sock->GET_INFO("EnableZarafaSalearnSchedule");
	if(!is_numeric($EnableZarafaSalearnSchedule)){$EnableZarafaSalearnSchedule=0;}
	
	if($EnableZarafaSalearnSchedule==0){
		system_admin_events("Leanring SPAM is disabled, aborting", __FUNCTION__, __FILE__, __LINE__, "mailbox");
		return;
	}
	
	if($unix->process_exists($pid)){
		$pidtime=$unix->PROCCESS_TIME_MIN($pid);
		system_admin_events("Could not start task, process $pid already running since {$pidtime}Mn", __FUNCTION__, __FILE__, __LINE__, "mailbox");
		return;
	}
	$t1=time();
	$ZarafaIMAPEnable=$sock->GET_INFO("ZarafaIMAPEnable");
	$ZarafaIMAPPort=$sock->GET_INFO("ZarafaIMAPPort");
	if(!is_numeric($ZarafaIMAPEnable)){$ZarafaIMAPEnable=1;}
	if(!is_numeric($ZarafaIMAPPort)){$ZarafaIMAPPort=143;}
	$ZarafaGatewayBind=$sock->GET_INFO("ZarafaGatewayBind");
	if(trim($ZarafaGatewayBind)==null){$ZarafaGatewayBind="localhost";}
	
	system_admin_events("sa-learn starting to $ZarafaGatewayBind:$ZarafaIMAPPort server...", __FUNCTION__, __FILE__, __LINE__, "mailbox");
	
	@file_put_contents($pidfile, getmypid());
	@file_put_contents($pidtime, time());
	
	if($ZarafaIMAPEnable==0){
		system_admin_events("sa-learn has been canceled due to disabled IMAP service (ZarafaIMAPEnable)", __FUNCTION__, __FILE__, __LINE__, "mailbox");
		return;	
	}
	$t1=time();
	$c=0;
	$ldap=new clladp();
	$suffix=$ldap->suffix;	
	$arr=array("uid");
	$sr = @ldap_search($ldap->ldap_connection,"dc=organizations,$suffix",'(objectclass=userAccount)',$arr);
		if ($sr) {
			$hash=ldap_get_entries($ldap->ldap_connection,$sr);
			for($i=0;$i<$hash["count"];$i++){
				$user=new user($hash[$i]["uid"][0]);
				$c++;
				buildPerlScript($hash[$i]["uid"][0],$user->password,$ZarafaGatewayBind,$ZarafaIMAPPort);
				if(system_is_overloaded(dirname(__FILE__))){
					system_admin_events("sa-learn has been canceled due to overloaded system", __FUNCTION__, __FILE__, __LINE__, "mailbox");
					return;
				}
				sleep(1);
			}
		}
		
	$took=$unix->distanceOfTimeInWords($t1,time());
	system_admin_events("sa-learn on $c mailboxe(s) done took $took", __FUNCTION__, __FILE__, __LINE__, "mailbox");
}




function buildPerlScript($username,$password,$imap,$port){
	$unix=new unix();
	$sock=new sockets();
	$ZarafaLearnDebug=$sock->GET_INFO("ZarafaLearnDebug");
	if(!is_numeric($ZarafaLearnDebug)){$ZarafaLearnDebug=0;}
	$salearnbin=$unix->find_program("sa-learn");
	$perl=$unix->find_program("perl");
	$t1=time();
	if(strlen($salearnbin)<3){return false;}
	$f[]="#!$perl";
	$f[]="#";
	$f[]="# Process mail from imap server shared folder 'Public folders/Spam' & 'Public folders/Non-spam'";
	$f[]="# through spamassassin sa-learn";
	$f[]="# Original by dmz [at] dmzs.com - March 19, 2004";
	$f[]="# Modified by Pete Donnell, Kitson Consulting Ltd, March 5th 2010 - processed Non-spam messages are not deleted any more";
	$f[]="# http://www.dmzs.com/tools/files/spam.phtml";
	$f[]="# http://www.dmzs.com/tools/files/spam/DMZS-sa-learn.pl";
	$f[]="# LGPL";
	$f[]="";
	$f[]="use Mail::IMAPClient;";
	$f[]="";
	$f[]="my \$debug=$ZarafaLearnDebug;";
	$f[]="my \$salearn;";
	$f[]="";
	$f[]="my \$imap = Mail::IMAPClient->new( Server=> '$imap:$port',";
	$f[]="                                  User => '$username',";
	$f[]="                                  Password => '$password',";
	$f[]="                                  Debug => \$debug);";
	$f[]="";
	$f[]="if (!defined(\$imap)) { die \"IMAP Login Failed\"; }";
	$f[]="";
	$f[]="# If debugging, print out the total counts for each mailbox";
	$f[]="if (\$debug) {";
	$f[]=" my \$spamcount = \$imap->message_count('Junk E-mail');";
	$f[]=" print \$spamcount, \" Spam to process\n\";";
	$f[]="";
	$f[]=" my \$nonspamcount = \$imap->message_count('Non-spam');";
	$f[]=" print \$nonspamcount, \" Non-spam to process\n\" if \$debug;";
	$f[]="}";
	$f[]="";
	$f[]="# Process the spam mailbox";
	$f[]="\$imap->select('Junk E-mail');";
	$f[]="my @msgs = \$imap->search(\"ALL\");";
	$f[]="for (my \$i=0;\$i <= \$#msgs; \$i++)";
	$f[]="{";
	$f[]=" # I put it into a file for processing, doing it into a perl var & piping through sa-learn just didn't seem to work";
	$f[]=" \$imap->message_to_file(\"/tmp/salearn\",\$msgs[\$i]);";
	$f[]="";
	$f[]=" # execute sa-learn w/data";
	$f[]=" if (\$debug) { \$salearn = `$salearnbin -D --no-sync  --spam /tmp/salearn`; } ";
	$f[]=" else { \$salearn = `$salearnbin --no-sync  --spam /tmp/salearn`; }";
	$f[]=" print \"-------\nSpam: \",\$salearn,\"\n-------\n\" if \$debug;";
	$f[]="";
	$f[]=" # delete processed message";
	$f[]=" \$imap->delete_message(\$msgs[\$i]);";
	$f[]=" unlink(\"/tmp/salearn\");";
	$f[]="}";
	$f[]="\$imap->expunge();";
	$f[]="\$imap->close();";
	$f[]="";
	$f[]="# Process the not-spam mailbox";
	$f[]="\$imap->select('Non-spam');";
	$f[]="my @msgs = \$imap->search(\"ALL\");";
	$f[]="for (my \$i=0;\$i <= \$#msgs; \$i++)";
	$f[]="{";
	$f[]=" \$imap->message_to_file(\"/tmp/salearn\",\$msgs[\$i]);";
	$f[]=" # execute sa-learn w/data";
	$f[]=" if (\$debug) { \$salearn = `$salearnbin -D --no-sync  --ham /tmp/salearn`; }";
	$f[]=" else { \$salearn = `$salearnbin --no-sync  --ham /tmp/salearn`; }";
	$f[]=" print \"-------\nNotSpam: \",\$salearn,\"\n-------\n\" if \$debug; ";
	$f[]="";
	$f[]=" unlink(\"/tmp/salearn\");";
	$f[]="}";
	$f[]="\$imap->close();";
	$f[]="";
	$f[]="\$imap->logout();";
	$f[]="";
	$f[]="# integrate learned stuff";
	$f[]="my \$sarebuild = `/usr/bin/sa-learn --sync`;";
	$f[]="print \"-------\nRebuild: \",\$sarebuild,\"\n-------\n\" if \$debug;\n";	
	
	@file_put_contents("/tmp/sa-learn.$t1.pl", @implode("\n", $f));
	chmod("/tmp/sa-learn.$t1.pl", 0777);
	exec("/tmp/sa-learn.$t1.pl 2>&1",$results);
	@unlink("/tmp/sa-learn.$t1.pl");
	$took=$unix->distanceOfTimeInWords($t1,time());
	system_admin_events("$username mailbox executed ($took)\n".@implode("\n",$results), __FUNCTION__, __FILE__, __LINE__, "mailbox");
	return true;		
	
	
}