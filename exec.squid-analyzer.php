#!/usr/bin/php -q
<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="InfluxDB Daemon";
$GLOBALS["PROGRESS"]=false;
$GLOBALS["MIGRATION"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;
$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--migration#",implode(" ",$argv),$re)){$GLOBALS["MIGRATION"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.influx.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');



if($argv[1]=="--build"){build();exit;}


function build(){
	
	$sock=new sockets();
	$SquidAnalyzerPath=$sock->GET_INFO("SquidAnalyzerPath");
	if($SquidAnalyzerPath==null){$SquidAnalyzerPath="/home/squid/htmlreports";}
	@mkdir("$SquidAnalyzerPath",0755,true);
	
	$f[]="#####";
	$f[]="# This file is the default configuration file for SquidAnalyzer";
	$f[]="# Edit it to match your needs and copy it under /etc/squidanalyzer.conf";
	$f[]="#####";
	$f[]="";
	$f[]="# Path where SquidAnalyzer should dump all HTML and images files.";
	$f[]="# Choose a path that can be read by a Web browser";
	$f[]="Output	/home/squid/htmlreports";
	$f[]="";
	$f[]="# The URL of the SquidAnalyzer javascript, HTML and images files.";
	$f[]="WebUrl	/";
	$f[]="";
	$f[]="# Set the path to the Squid log file";
	$f[]="LogFile	/var/log/squid/access.log";
	$f[]="";
	$f[]="# If you want to use DNS name instead of client Ip address as username enable";
	$f[]="# this directive. When you don't have authentication, the username is set to";
	$f[]="# the client ip address, this allow you to use the DNS name instead.";
	$f[]="# Note that you must have a working DNS resolution and that it can really slow";
	$f[]="# down the generation of reports.";
	$f[]="UseClientDNSName	1";
	$f[]="";
	$f[]="# If you have enabled UseClientDNSName and have lot of ip addresses that do";
	$f[]="# not resolve you may want to increase the DNS lookup timeout. By default";
	$f[]="# SquidAnalyzer will stop to lookup a DNS name after 0.0001 second (100 ms).";
	$f[]="DNSLookupTimeout	0.0001";
	$f[]="";
	$f[]="# Set the file containing network alias name. Network are";
	$f[]="# show as Ip addresses so if you want to display name instead";
	$f[]="# create a file with this format :";
	$f[]="# LOCATION_NAME	IP_NETWORK_ADDRESS";
	$f[]="# Separator must be a tabulation";
	$f[]="NetworkAlias	/etc/network-aliases";
	$f[]="";
	$f[]="# Set the file containing user alias name. If you don't have auth_proxy";
	$f[]="# enable user are seen as Ip addresses, or if you want to replace login";
	$f[]="# name by full user name, create a file with this format :";
	$f[]="# FULL_USERNAME	IP_ADDRESS || LOGIN_NAME";
	$f[]="# Separator must be a tabulation";
	$f[]="UserAlias	/etc/user-aliases";
	$f[]="";
	$f[]="# How do we sort Network, User and Url report screen";
	$f[]="# Value can be: bytes, hits or duration. Default is bytes.";
	$f[]="OrderNetwork	bytes";
	$f[]="OrderUser	bytes";
	$f[]="OrderUrl	bytes";
	$f[]="";
	$f[]="# How do we sort Mime types report screen";
	$f[]="# Value can be: bytes or hits. Default is bytes.";
	$f[]="OrderMime	bytes";
	$f[]="";
	$f[]="# Should we display user URL details. This will show all URL read";
	$f[]="# by user. Take care to have enougth space disk for large user.";
	$f[]="UrlReport	1";
	$f[]="";
	$f[]="# Enable this directive if you don't want the tree Top URL and Domain tables.";
	$f[]="# You will jus have the table of Url/Domain ordere per hits then you can still";
	$f[]="# sort the URL/Domain order by clicking on each column";
	$f[]="UrlHitsOnly	0";
	$f[]="";
	$f[]="# Should we display user details. This will show statistics";
	$f[]="# per user.";
	$f[]="UserReport	1";
	$f[]="";
	$f[]="# Run in quiet mode or print debug information";
	$f[]="QuietMode	1";
	$f[]="";
	$f[]="# Cost of the bandwith per Mb. If you want to generate invoice per Mb";
	$f[]="# for bandwith traffic this can help you. Value 0 mean no cost.";
	$f[]="CostPrice	0";
	$f[]="";
	$f[]="# Currency of the bandwith cost";
	$f[]="Currency	&euro;";
	$f[]="";
	$f[]="# Top number of url to show";
	$f[]="TopNumber	100";
	$f[]="";
	$f[]="# Path to the file containing client ip addresses, network ip address,";
	$f[]="# and/or auth login to exclude from report";
	$f[]="Exclude	/etc/excluded";
	$f[]="";
	$f[]="# Path to the file containing client ip addresses, network ip address,";
	$f[]="# and/or auth login to include into the report. Other entries will be";
	$f[]="# excluded by default.";
	$f[]="Include	/etc/included";
	$f[]="";
	$f[]="# Translation Lang	/etc/lang/en_US.txt,";
	$f[]="# en_US.txt, ru_RU.txt, uk_UA.txt, cs_CZ.txt, pl_PL.txt and de_DE.txt).";
	$f[]="# Default to:";
	$f[]="#Lang	/etc/lang/en_US.txt";
	$f[]="";
	$f[]="# Date format used to display date (year = %y, month = %m and day = %d)";
	$f[]="# You can also use %M to replace month by its 3 letters abbreviation.";
	$f[]="DateFormat	%y-%m-%d";
	$f[]="";
	$f[]="# Set this to 1 if you want to anonymize all user login. The username";
	$f[]="# will be replaced by an unique id that change at each squid-analyzer";
	$f[]="# run. Default disable.";
	$f[]="AnonymizeLogin	0";
	$f[]="";
	$f[]="# Adds peer cache hit (CD_SIBLING_HIT) to be taken has local cache hit.";
	$f[]="# Enabled by default, you must disabled it if you don't want to report";
	$f[]="# peer cache hit onto your stats.";
	$f[]="SiblingHit	1";
	$f[]="";
	$f[]="# Set the default unit for transfert size. Default is BYTES, other possible";
	$f[]="# values are KB, MB and GB";
	$f[]="TransfertUnit	BYTES";
	$f[]="";
	$f[]="# Minimum percentage of data in pie's graphs to not be placed in the others item.";
	$f[]="MinPie		2";
	$f[]="";
	$f[]="# Set this to your locale to display generated date in your language. Default";
	$f[]="# is to use strftime. If you want date in German for example, set it to de_DE.";
	$f[]="# For french, fr_FR should do the work.";
	$f[]="#Locale		en_US";
	$f[]="";
	$f[]="# By default SquidAnalyzer is saving current collected statistics each time";
	$f[]="# a new hour is found in log file. Most of the time this is enough but if";
	$f[]="# you have huge log file and don't have enough memory this will slow down the";
	$f[]="# parser by forcing Perl to use temporaries files. Use lower value following";
	$f[]="# your memory and the size of your log file, on very huge log file with lot of";
	$f[]="# requests/seconde a value of 30 minutes (1800) or less should help.";
	$f[]="WriteDelay	3600";
	$f[]="";
	$f[]="# Use this directive to show the top N users that look at an URL or a domain.";
	$f[]="# Set it to 0 to disable this feature.";
	$f[]="TopUrlUser	10";
	$f[]="";
	$f[]="# This directive allow you to replace the SquidAnalyze logo by your custom";
	$f[]="# logo. The default value is defined as follow:";
	$f[]="# <a href=\"\$self->{WebUrl}\">";
	$f[]="# <img src=\"\$self->{WebUrl}images/logo-squidanalyzer.png\" title=\"SquidAnalyzer \$VERSION\" border=\"0\">";
	$f[]="# </a> SquidAnalyzer";
	$f[]="# Feel free to define your own header but take care to not break current design.";
	$f[]="CustomHeader	<a href=\"http://my.isp.dom/\"><img src=\"http://my.isp.dom/logo.png\" title=\"My ISP link\" border=\"0\" width=\"100\" height=\"110\"></a> My ISP Company";
	$f[]="";
	$f[]="# This directive allow exclusion of some unwanted methods in report statistics";
	$f[]="# like HEAD, POST, CONNECT, etc. Can be a comma separated list of methods.";
	$f[]="ExcludedMethods	HEAD";
	$f[]="";
	$f[]="# This directive allow exclusion of some unwanted mimetypes in report statistics";
	$f[]="# like text/html, text/plain, or more generally text/*, etc. Can be a comma separated";
	$f[]="# list of perl regular expression.";
	$f[]="#ExcludedMimes	text/.*,image/.*";
	$f[]="";
	$f[]="# This directive allow exclusion of some unwanted codes in report statistics";
	$f[]="# like TCP_DENIED/403 which are generated when a user accesses a page the first";
	$f[]="# time without authentication. Can be a comma separated list of methods.";
	$f[]="ExcludedCodes	TCP_DENIED/403";
	@file_put_contents("/etc/squidanalyzer.conf", @implode("\n", $f));



}