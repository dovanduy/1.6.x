<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/class.amavis.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');

$amavis=new amavis();
$amavis->Save();
$amavis->SaveToServer();

$samba=new samba();
$samba->SaveToLdap();

$squid=new squidbee();
$squid->SaveToLdap();
$squid->SaveToServer();


$users=new usersMenus();
if($users->POSTFIX_INSTALLED){
	echo "Restarting Postfix service...\n";
	system('/etc/init.d/postfix restart');
}
if($users->SAMBA_INSTALLED){
	echo "Restarting Samba service...\n";
	system('/etc/init.d/artica-postfix restart samba');
}
if($users->cyrus_imapd_installed){
	echo "Restarting Cyrus service...\n";
	system('/usr/share/artica-postfix/bin/artica-install --cyrus-checkconfig');
	system('/etc/init.d/cyrus-imapd restart');
}
echo "Restarting SASL AUTHD service...\n";
system('/etc/init.d/artica-postfix restart saslauthd');
if($users->ZARAFA_INSTALLED){
	system('/etc/init.d/artica-postfix restart zarafa');
}
system('/etc/init.d/artica-status reload');
system('/etc/init.d/artica-postfix restart arkeia');
system('/etc/init.d/artica-postfix restart artica-back');
system('/etc/init.d/artica-postfix restart artica-exec');
system('/usr/share/artica-postfix/bin/artica-install --nsswitch');


?>