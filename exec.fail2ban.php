<?php
if(is_file("/usr/bin/cgclassify")){if(is_dir("/cgroups/blkio/php")){shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:php ".getmypid());}}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");


if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--build"){build();exit;}


function install(){
	$unix=new unix();
	
	$timefile="/etc/artica-postfix/pids/".__FILE__.".time";
	
	if($unix->file_time_min($timefile)<240){
		
		return;
	}
	
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$bin=$unix->find_program("fail2ban-server");
	if(!is_file($bin)){
		$unix->DEBIAN_INSTALL_PACKAGE("fail2ban");
	}
	$bin=$unix->find_program("fail2ban-server");
	
	$fail2ban=new fail2ban();
	
	
	if(is_file($bin)){
		$fail2ban->buildinit();
		build();
	}
}

function build(){
	
	$unix=new unix();
	$f[]="# Fail2Ban configuration file";
	$f[]="#";
	$f[]="# Author: Cyril Jaquier";
	$f[]="#";
	$f[]="# \$Revision: 629 \$";
	$f[]="#";
	$f[]="";
	$f[]="[Definition]";
	$f[]="";
	$f[]="# Option:  loglevel";
	$f[]="# Notes.:  Set the log level output.";
	$f[]="#          1 = ERROR";
	$f[]="#          2 = WARN";
	$f[]="#          3 = INFO";
	$f[]="#          4 = DEBUG";
	$f[]="# Values:  NUM  Default:  3";
	$f[]="#";
	$f[]="loglevel = 3";
	$f[]="";
	$f[]="# Option:  logtarget";
	$f[]="# Notes.:  Set the log target. This could be a file, SYSLOG, STDERR or STDOUT.";
	$f[]="#          Only one log target can be specified.";
	$f[]="# Values:  STDOUT STDERR SYSLOG file  Default:  /var/log/fail2ban.log";
	$f[]="#";
	$f[]="logtarget = /var/log/fail2ban.log";
	$f[]="";
	$f[]="# Option: socket";
	$f[]="# Notes.: Set the socket file. This is used to communicate with the daemon. Do";
	$f[]="#         not remove this file when Fail2ban runs. It will not be possible to";
	$f[]="#         communicate with the server afterwards.";
	$f[]="# Values: FILE  Default:  /var/run/fail2ban/fail2ban.sock";
	$f[]="#";
	$f[]="socket = /var/run/fail2ban/fail2ban.sock";
	$f[]="";
	@file_put_contents("/etc/fail2ban/fail2ban.conf", @implode("\n", $f));
	$f=array();
	
	$f[]="# Fail2Ban configuration file.";
	$f[]="#";
	$f[]="# This file was composed for Debian systems from the original one";
	$f[]="#  provided now under /usr/share/doc/fail2ban/examples/jail.conf";
	$f[]="#  for additional examples.";
	$f[]="#";
	$f[]="# To avoid merges during upgrades DO NOT MODIFY THIS FILE";
	$f[]="# and rather provide your changes in /etc/fail2ban/jail.local";
	$f[]="#";
	$f[]="# Author: Yaroslav O. Halchenko <debian@onerussian.com>";
	$f[]="#";
	$f[]="# \$Revision: 281 \$";
	$f[]="#";
	$f[]="";
	$f[]="# The DEFAULT allows a global definition of the options. They can be override";
	$f[]="# in each jail afterwards.";
	$f[]="";
	$f[]="[DEFAULT]";
	$f[]="";
	$f[]="# \"ignoreip\" can be an IP address, a CIDR mask or a DNS host";
	$f[]="ignoreip = 127.0.0.1";
	$f[]="bantime  = 600";
	$f[]="maxretry = 3";
	$f[]="";
	$f[]="# \"backend\" specifies the backend used to get files modification. Available";
	$f[]="# options are \"gamin\", \"polling\" and \"auto\".";
	$f[]="# yoh: For some reason Debian shipped python-gamin didn't work as expected";
	$f[]="#      This issue left ToDo, so polling is default backend for now";
	$f[]="backend = polling";
	$f[]="";
	$f[]="#";
	$f[]="# Destination email address used solely for the interpolations in";
	$f[]="# jail.{conf,local} configuration files.";
	$f[]="destemail = root@localhost";
	$f[]="";
	$f[]="#";
	$f[]="# ACTIONS";
	$f[]="#";
	$f[]="";
	$f[]="# Default banning action (e.g. iptables, iptables-new,";
	$f[]="# iptables-multiport, shorewall, etc) It is used to define ";
	$f[]="# action_* variables. Can be overriden globally or per ";
	$f[]="# section within jail.local file";
	$f[]="banaction = iptables-multiport";
	$f[]="";
	$f[]="# email action. Since 0.8.1 upstream fail2ban uses sendmail";
	$f[]="# MTA for the mailing. Change mta configuration parameter to mail";
	$f[]="# if you want to revert to conventional 'mail'.";
	$f[]="mta = sendmail";
	$f[]="";
	$f[]="# Default protocol";
	$f[]="protocol = tcp";
	$f[]="";
	$f[]="#";
	$f[]="# Action shortcuts. To be used to define action parameter";
	$f[]="";
	$f[]="# The simplest action to take: ban only";
	$f[]="action_ = %(banaction)s[name=%(__name__)s, port=\"%(port)s\", protocol=\"%(protocol)s]";
	$f[]="";
	$f[]="# ban & send an e-mail with whois report to the destemail.";
	$f[]="action_mw = %(banaction)s[name=%(__name__)s, port=\"%(port)s\", protocol=\"%(protocol)s]";
	$f[]="              %(mta)s-whois[name=%(__name__)s, dest=\"%(destemail)s\", protocol=\"%(protocol)s]";
	$f[]="";
	$f[]="# ban & send an e-mail with whois report and relevant log lines";
	$f[]="# to the destemail.";
	$f[]="action_mwl = %(banaction)s[name=%(__name__)s, port=\"%(port)s\", protocol=\"%(protocol)s]";
	$f[]="               %(mta)s-whois-lines[name=%(__name__)s, dest=\"%(destemail)s\", logpath=%(logpath)s]";
	$f[]=" ";
	$f[]="# Choose default action.  To change, just override value of 'action' with the";
	$f[]="# interpolation to the chosen action shortcut (e.g.  action_mw, action_mwl, etc) in jail.local";
	$f[]="# globally (section [DEFAULT]) or per specific section ";
	$f[]="action = %(action_)s";
	$f[]="";
	$f[]="#";
	$f[]="# JAILS";
	$f[]="#";
	$f[]="";
	$f[]="# Next jails corresponds to the standard configuration in Fail2ban 0.6 which";
	$f[]="# was shipped in Debian. Enable any defined here jail by including";
	$f[]="#";
	$f[]="# [SECTION_NAME] ";
	$f[]="# enabled = true";
	$f[]="";
	$f[]="#";
	$f[]="# in /etc/fail2ban/jail.local.";
	$f[]="#";
	$f[]="# Optionally you may override any other parameter (e.g. banaction,";
	$f[]="# action, port, logpath, etc) in that section within jail.local";
	$f[]="";
	$f[]="[ssh]";
	$f[]="";
	$f[]="enabled = true";
	$f[]="port	= ssh";
	$f[]="filter	= sshd";
	$f[]="logpath  = /var/log/auth.log";
	$f[]="maxretry = 6";
	$f[]="";
	$f[]="# Generic filter for pam. Has to be used with action which bans all ports";
	$f[]="# such as iptables-allports, shorewall";
	$f[]="[pam-generic]";
	$f[]="";
	$f[]="enabled = false";
	$f[]="# pam-generic filter can be customized to monitor specific subset of 'tty's";
	$f[]="filter	= pam-generic";
	$f[]="# port actually must be irrelevant but lets leave it all for some possible uses";
	$f[]="port = all";
	$f[]="banaction = iptables-allports";
	$f[]="port     = anyport";
	$f[]="logpath  = /var/log/auth.log";
	$f[]="maxretry = 6";
	$f[]="";
	$f[]="[xinetd-fail]";
	$f[]="";
	$f[]="enabled   = false";
	$f[]="filter    = xinetd-fail";
	$f[]="port      = all";
	$f[]="banaction = iptables-multiport-log";
	$f[]="logpath   = /var/log/daemon.log";
	$f[]="maxretry  = 2";
	$f[]="";
	$f[]="";
	$f[]="[ssh-ddos]";
	$f[]="";
	$f[]="enabled = false";
	$f[]="port    = ssh";
	$f[]="filter  = sshd-ddos";
	$f[]="logpath  = /var/log/auth.log";
	$f[]="maxretry = 6";
	$f[]="";
	$f[]="#";
	$f[]="# HTTP servers";
	$f[]="#";
	$f[]="";
	$f[]="[apache]";
	$f[]="";
	$f[]="enabled = false";
	$f[]="port	= http,https";
	$f[]="filter	= apache-auth";
	$f[]="logpath = /var/log/apache*/*error.log";
	$f[]="maxretry = 6";
	$f[]="";
	$f[]="# default action is now multiport, so apache-multiport jail was left";
	$f[]="# for compatibility with previous (<0.7.6-2) releases";
	$f[]="[apache-multiport]";
	$f[]="";
	$f[]="enabled   = false";
	$f[]="port	  = http,https";
	$f[]="filter	  = apache-auth";
	$f[]="logpath   = /var/log/apache*/*error.log";
	$f[]="maxretry  = 6";
	$f[]="";
	$f[]="[apache-noscript]";
	$f[]="";
	$f[]="enabled = false";
	$f[]="port    = http,https";
	$f[]="filter  = apache-noscript";
	$f[]="logpath = /var/log/apache*/*error.log";
	$f[]="maxretry = 6";
	$f[]="";
	$f[]="[apache-overflows]";
	$f[]="";
	$f[]="enabled = false";
	$f[]="port    = http,https";
	$f[]="filter  = apache-overflows";
	$f[]="logpath = /var/log/apache*/*error.log";
	$f[]="maxretry = 2";
	$f[]="";
	$f[]="#";
	$f[]="# FTP servers";
	$f[]="#";
	$f[]="";
	$f[]="[vsftpd]";
	$f[]="";
	$f[]="enabled  = false";
	$f[]="port	 = ftp,ftp-data,ftps,ftps-data";
	$f[]="filter   = vsftpd";
	$f[]="logpath  = /var/log/vsftpd.log";
	$f[]="# or overwrite it in jails.local to be";
	$f[]="# logpath = /var/log/auth.log";
	$f[]="# if you want to rely on PAM failed login attempts";
	$f[]="# vsftpd's failregex should match both of those formats";
	$f[]="maxretry = 6";
	$f[]="";
	$f[]="";
	$f[]="[proftpd]";
	$f[]="";
	$f[]="enabled  = false";
	$f[]="port	 = ftp,ftp-data,ftps,ftps-data";
	$f[]="filter   = proftpd";
	$f[]="logpath  = /var/log/proftpd/proftpd.log";
	$f[]="maxretry = 6";
	$f[]="";
	$f[]="";
	$f[]="[wuftpd]";
	$f[]="";
	$f[]="enabled  = false";
	$f[]="port	 = ftp,ftp-data,ftps,ftps-data";
	$f[]="filter   = wuftpd";
	$f[]="logpath  = /var/log/auth.log";
	$f[]="maxretry = 6";
	$f[]="";
	$f[]="";
	$f[]="#";
	$f[]="# Mail servers";
	$f[]="#";
	$f[]="";
	$f[]="[postfix]";
	$f[]="";
	$postconf=$unix->find_program("postconf");
	if(is_file($postconf)){
	$f[]="enabled  = true";
	}else{
	$f[]="enabled  = false";
	}
	$f[]="port	 = smtp,ssmtp";
	$f[]="filter   = postfix";
	$f[]="logpath  = /var/log/mail.log";
	$f[]="";
	$f[]="";
	$f[]="[couriersmtp]";
	$f[]="";
	$f[]="enabled  = false";
	$f[]="port	 = smtp,ssmtp";
	$f[]="filter   = couriersmtp";
	$f[]="logpath  = /var/log/mail.log";
	$f[]="";
	$f[]="";
	$f[]="#";
	$f[]="# Mail servers authenticators: might be used for smtp,ftp,imap servers, so";
	$f[]="# all relevant ports get banned";
	$f[]="#";
	$f[]="";
	$f[]="[courierauth]";
	$f[]="";
	$f[]="enabled  = false";
	$f[]="port	 = smtp,ssmtp,imap2,imap3,imaps,pop3,pop3s";
	$f[]="filter   = courierlogin";
	$f[]="logpath  = /var/log/mail.log";
	$f[]="";
	$f[]="";
	$f[]="[sasl]";
	$f[]="";
	$f[]="enabled  = false";
	$f[]="port	 = smtp,ssmtp,imap2,imap3,imaps,pop3,pop3s";
	$f[]="filter   = sasl";
	$f[]="# You might consider monitoring /var/log/warn.log instead";
	$f[]="# if you are running postfix. See http://bugs.debian.org/507990";
	$f[]="logpath  = /var/log/mail.log";
	$f[]="";
	$f[]="";
	$f[]="# DNS Servers";
	$f[]="";
	$f[]="";
	$f[]="# These jails block attacks against named (bind9). By default, logging is off";
	$f[]="# with bind9 installation. You will need something like this:";
	$f[]="#";
	$f[]="# logging {";
	$f[]="#     channel security_file {";
	$f[]="#         file \"/var/log/named/security.log\" versions 3 size 30m;";
	$f[]="#         severity dynamic;";
	$f[]="#         print-time yes;";
	$f[]="#     };";
	$f[]="#     category security {";
	$f[]="#         security_file;";
	$f[]="#     };";
	$f[]="# };";
	$f[]="#";
	$f[]="# in your named.conf to provide proper logging";
	$f[]="";
	$f[]="# !!! WARNING !!!";
	$f[]="#   Since UDP is connectionless protocol, spoofing of IP and immitation";
	$f[]="#   of illegal actions is way too simple.  Thus enabling of this filter";
	$f[]="#   might provide an easy way for implementing a DoS against a chosen";
	$f[]="#   victim. See";
	$f[]="#    http://nion.modprobe.de/blog/archives/690-fail2ban-+-dns-fail.html";
	$f[]="#   Please DO NOT USE this jail unless you know what you are doing.";
	$f[]="#[named-refused-udp]";
	$f[]="#";
	$f[]="#enabled  = false";
	$f[]="#port     = domain,953";
	$f[]="#protocol = udp";
	$f[]="#filter   = named-refused";
	$f[]="#logpath  = /var/log/named/security.log";
	$f[]="";
	$f[]="[named-refused-tcp]";
	$f[]="";
	$f[]="enabled  = false";
	
	$f[]="port     = domain,953";
	$f[]="protocol = tcp";
	$f[]="filter   = named-refused";
	$f[]="logpath  = /var/log/named/security.log";
	$f[]="";
	@file_put_contents("/etc/fail2ban/jail.conf", @implode("\n", $f));
	
	
	$f[]="";
	$f[]="# Fail2Ban configuration file";
	$f[]="#";
	$f[]="# Author: Cyril Jaquier";
	$f[]="#";
	$f[]="# \$Revision: 728 \$";
	$f[]="#";
	$f[]="";
	$f[]="[Definition]";
	$f[]="";
	$f[]="# Option:  failregex";
	$f[]="# Notes.:  regex to match the password failures messages in the logfile. The";
	$f[]="#          host must be matched by a group named \"host\". The tag \"<HOST>\" can";
	$f[]="#          be used for standard IP/hostname matching and is only an alias for";
	$f[]="#          (?:::f{4,6}:)?(?P<host>[\w\-.^_]+)";
	$f[]="# Values:  TEXT";
	$f[]="#";
	$f[]="failregex = reject: RCPT from (.*)\[<HOST>\]: 55[0-9]";
	$f[]="\tNOQUEUE: milter-reject: RCPT from (.*)\[<HOST>\]: 55[0-9]";
	$f[]="\tNOQUEUE: reject: RCPT from (.*)\[<HOST>\]: 55[0-9] .*?Service unavailable; Client host.*?blocked using";
	$f[]="\twarning: hostname (.*) does not resolve to address <HOST>: Name or service not known";
	$f[]="# Option:  ignoreregex";
	$f[]="# Notes.:  regex to ignore. If this regex matches, the line is ignored.";
	$f[]="# Values:  TEXT";
	$f[]="#";
	$f[]="";	
	@file_put_contents("/etc/fail2ban/filter.d/postfix.conf", @implode("\n", $f));
	
	system("/etc/init.d/fail2ban reload");
	
}