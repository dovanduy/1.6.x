<?php
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");

if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--stop"){stop();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--dictionary"){dictionary();exit;}


function build(){
	
	$unix=new unix();
	$sock=new sockets();
	$checkrad=$unix->find_program("checkrad");
	$EnableMikrotik=$sock->GET_INFO("EnableMikrotik");
	echo "Starting FreeRadius.............: checkrad: `$checkrad`\n";
	$ListenIP=$sock->GET_INFO("FreeRadiusListenIP");
	$FreeRadiusListenPort=$sock->GET_INFO("FreeRadiusListenPort");
	if($ListenIP==null){$ListenIP="*";}
	if(!is_numeric($FreeRadiusListenPort)){$FreeRadiusListenPort=1812;}
	echo "Starting FreeRadius.............: Listen addr: `$ListenIP:$FreeRadiusListenPort`\n";
	$IsMySQL=IsMySQL();	$UseMySQL=false;
	if($IsMySQL>0){$UseMySQL=true;}
	
	$f[]="prefix = /usr";
	$f[]="exec_prefix = /usr";
	$f[]="sysconfdir = /etc";
	$f[]="localstatedir = /var";
	$f[]="sbindir = \${exec_prefix}/sbin";
	$f[]="logdir = /var/log/freeradius";
	$f[]="raddbdir = /etc/freeradius";
	$f[]="radacctdir = \${logdir}/radacct";
	$f[]="name = freeradius";
	$f[]="confdir = \${raddbdir}";
	$f[]="run_dir = \${localstatedir}/run/\${name}";
	$f[]="db_dir = \${raddbdir}";
	$f[]="libdir = /usr/lib/freeradius";
	$f[]="pidfile = /var/run/freeradius/freeradius.pid";
	$f[]="user = root";
	$f[]="group = root";
	$f[]="max_request_time = 30";
	$f[]="cleanup_delay = 5";
	$f[]="max_requests = $FreeRadiusListenPort";
	$f[]="listen {";
	$f[]="	type = auth";
	$f[]="	port = 1812";
	$f[]="	ipaddr = $ListenIP";
	$f[]="#	clients = per_socket_clients";
	$f[]="}";
	$f[]="";
	$f[]="";
	$f[]="listen {";
	$f[]="	port = 0";
	$f[]="	type = acct";
	$f[]="	ipaddr = $ListenIP";
	$f[]="#	interface = eth0";
	$f[]="#	clients = per_socket_clients";
	$f[]="}";
	$f[]="";
	$f[]="";
	$f[]="hostname_lookups = no";
	$f[]="allow_core_dumps = no";
	$f[]="regular_expressions	= yes";
	$f[]="extended_expressions	= yes";
	$f[]="";
	$f[]="log {";
	$f[]="	destination = syslog";
	$f[]="	file = \${logdir}/radius.log";
	$f[]="	syslog_facility = daemon";
	$f[]="	stripped_names = no";
	$f[]="	auth = yes";
	$f[]="	auth_badpass = yes";
	$f[]="	auth_goodpass = no";
	$f[]="}";
	$f[]="";
	$f[]="checkrad = $checkrad";
	$f[]="";
	$f[]="security {";
	$f[]="	max_attributes = 200";
	$f[]="	reject_delay = 1";
	$f[]="	status_server = yes";
	$f[]="}";
	$f[]="";
	$f[]="proxy_requests  = yes";
	$f[]="\$INCLUDE proxy.conf";
	$f[]="\$INCLUDE clients.conf";
	$f[]="";
	$f[]="thread pool {";
	$f[]="	start_servers = 5";
	$f[]="	max_servers = 32";
	$f[]="	min_spare_servers = 3";
	$f[]="	max_spare_servers = 10";
	$f[]="	max_requests_per_server = 0";
	$f[]="}";
	
	$f[]="pap {";
	$f[]="\tencryption_scheme = clear";
	$f[]="}";
	
	$f[]="";
	$f[]="modules {";
	$f[]="	\$INCLUDE \${confdir}/modules/";
	$f[]="	\$INCLUDE eap.conf";
	if($UseMySQL){
		$f[]="	\$INCLUDE sql.conf";
	}
	$f[]="#	\$INCLUDE sql/mysql/counter.conf";
	$f[]="#	\$INCLUDE sqlippool.conf";
	$f[]="}";
	$f[]="";
	$f[]="";
	$f[]="instantiate {";
	$f[]="	expr";
	$f[]="#	daily";
	$f[]="	expiration";
	$f[]="	logintime";
	$f[]="}";
	$f[]="";
	$f[]="\$INCLUDE policy.conf";
	$f[]="\$INCLUDE sites-enabled/";	
	//$f[]="\$INCLUDE dictionary";
	
	dictionary();
	echo "Starting FreeRadius.............: /etc/freeradius/radiusd.conf done...\n";
	@mkdir("/etc/freeradius",0755,true);
	@file_put_contents("/etc/freeradius/radiusd.conf", @implode("\n", $f));
	eap();
	pap();
	proxy();
	ntlm_auth();
	build_sql_connections();
	module_ldap();
	inner_tunnel();
	site_default();
	confusers();
	clients();
	mschap();
	microtik();
	
}

function freeradius_pid(){
	$unix=new unix();
	
	$pidfile="/var/run/freeradius/freeradius.pid";
	
	$oldpid=$unix->get_pid_from_file($pidfile);
	if(!$unix->process_exists($oldpid)){
		$freeradius=$unix->find_program("freeradius");
		$oldpid=$unix->PIDOF_PATTERN($freeradius);
	}
	return $oldpid;
}


function dictionary(){
	
	$prefix="/usr/share/freeradius";
	
	$unix=new unix();
	$files=$unix->DirFiles($prefix,"^dictionary\.rfc3*");
	
	$f[]="# -*- text -*-";
	$f[]="#";
	$f[]="# Version \$Id$";
	$f[]="#";
	$f[]="#	DO NOT EDIT THE FILES IN THIS DIRECTORY";
	$f[]="#";
	$f[]="#	The files in this directory are maintained and updated by";
	$f[]="#	the FreeRADIUS project.  Newer releases of software may update";
	$f[]="#	or change these files.";
	$f[]="#";
	$f[]="#	Use the main dictionary file (usually /etc/raddb/dictionary)";
	$f[]="#	for local system attributes and \$INCLUDEs.";
	$f[]="#";
	$f[]="#";
	$f[]="#";
	$f[]="#	This file contains dictionary translations for parsing";
	$f[]="#	../..";
	
	$f[]="\$INCLUDE  /usr/share/freeradius/dictionary.freeradius.internal";
	if(is_file("/usr/share/freeradius/dictionary.chillispot")){
		$f[]="\$INCLUDE  /usr/share/freeradius/dictionary.chillispot";
	}
	
	while (list ($num, $filename) = each ($files) ){
		if($filename=="dictionary.zyxel"){continue;}
		if($filename=="dictionary.columbia_university"){continue;}
		if($filename=="dictionary.rfc5090"){continue;}
		$f[]="\$INCLUDE /usr/share/freeradius/$filename";
		
	}
	
	echo "Starting FreeRadius.............: /usr/share/freeradius/dictionary (".count($files)." items)\n";
	@file_put_contents("/usr/share/freeradius/dictionary", @implode("\n", $f)."\n");
}


function ntlm_auth(){
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($EnableKerbAuth==0){return;}
	$unix=new unix();
	$ntlm_auth=$unix->find_program("ntlm_auth");
	if(!is_file($ntlm_auth)){return null;}
	
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domaindow=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$kinitpassword=$array["WINDOWS_SERVER_PASS"];
	$workgroup=strtoupper($array["ADNETBIOSDOMAIN"]);	
	
	
	$f[]="#";
	$f[]="#  For testing ntlm_auth authentication with PAP.";
	$f[]="#";
	$f[]="#  If you have problems with authentication failing, even when the";
	$f[]="#  password is good, it may be a bug in Samba:";
	$f[]="#";
	$f[]="#	https://bugzilla.samba.org/show_bug.cgi?id=6563";
	$f[]="#";
	$f[]="exec ntlm_auth {";
	$f[]="	wait = yes";
	$f[]="	program = \"$ntlm_auth --request-nt-key --domain=$workgroup --username=%{mschap:User-Name} --password=%{User-Password}\"";
	$f[]="}";
	$f[]="";	
	
	@mkdir("/etc/freeradius/modules",0755,true);
	@file_put_contents("/etc/freeradius/modules/ntlm_auth", @implode("\n", $f));
	echo "Starting FreeRadius.............: /etc/freeradius/modules/ntlm_auth done...\n";
}

function mschap(){
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($EnableKerbAuth==0){return;}
	$unix=new unix();
	$ntlm_auth=$unix->find_program("ntlm_auth");
	if(!is_file($ntlm_auth)){return null;}
	
	$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domaindow=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$kinitpassword=$array["WINDOWS_SERVER_PASS"];
	$workgroup=strtoupper($array["ADNETBIOSDOMAIN"]);
		
	$f[]="mschap {";
	$f[]="	#use_mppe = no";
	$f[]="	#require_encryption = yes";
	$f[]="	#require_strong = yes";
	$f[]="	#with_ntdomain_hack = no";
	if($EnableKerbAuth==1){
		$f[]="	ntlm_auth = \"$ntlm_auth --request-nt-key --username=%{mschap:User-Name:-None} --domain=%{%{mschap:NT-Domain}:-$workgroup} --challenge=%{mschap:Challenge:-00} --nt-response=%{mschap:NT-Response:-00}\"";
	}
	$f[]="}";
	
	@mkdir("/etc/freeradius/modules",0755,true);
	@file_put_contents("/etc/freeradius/modules/mschap", @implode("\n", $f));
	echo "Starting FreeRadius.............: /etc/freeradius/modules/mschap done...\n";	
}

function pap(){
	$f[]="# -*- text -*-";
	$f[]="#";
	$f[]="#  \$Id\$";
	$f[]="";
	$f[]="# PAP module to authenticate users based on their stored password";
	$f[]="#";
	$f[]="#  Supports multiple encryption/hash schemes.  See \"man rlm_pap\"";
	$f[]="#  for details.";
	$f[]="#";
	$f[]="#  The \"auto_header\" configuration item can be set to \"yes\".";
	$f[]="#  In this case, the module will look inside of the User-Password";
	$f[]="#  attribute for the headers {crypt}, {clear}, etc., and will";
	$f[]="#  automatically create the attribute on the right-hand side,";
	$f[]="#  with the correct value.  It will also automatically handle";
	$f[]="#  Base-64 encoded data, hex strings, and binary data.";
	$f[]="pap {";
	$f[]="	auto_header = yes";
	$f[]="}";
	$f[]="";
		
	@mkdir("/etc/freeradius/modules",0755,true);
	@file_put_contents("/etc/freeradius/modules/pap", @implode("\n", $f));
	echo "Starting FreeRadius.............: /etc/freeradius/modules/pap done...\n";	
}


function eap(){
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	
	$default_eap_type="md5";
	$timer_expire=60;
	$ignore_unknown_eap_types="no";
	
	$ttls_default_eap_type="md5";
	$ttls_copy_request_to_tunnel="no";
	$ttls_use_tunneled_reply="no";
	
	$peap_default_eap_type="mschapv2";
	$peap_copy_request_to_tunnel="no";
	$peap_use_tunneled_reply="no";
	
	
	if($EnableKerbAuth==1){
		echo "Starting FreeRadius.............: Active Directory configuration available...\n";
		$default_eap_type="ttls";
		$ttls_default_eap_type="mschapv2";
		$ttls_copy_request_to_tunnel="yes";
		$ttls_use_tunneled_reply="yes";
		$peap_copy_request_to_tunnel="yes";
		$peap_use_tunneled_reply="yes";		
	}
	
	$f[]="	eap {";
	$f[]="			default_eap_type = $default_eap_type";
	$f[]="			timer_expire     = $timer_expire";
	$f[]="			cisco_accounting_username_bug = no";
	$f[]="			ignore_unknown_eap_types=$ignore_unknown_eap_types";
	$f[]="			max_sessions = 4096";
	$f[]="			md5 {";
	$f[]="			}";
	$f[]="";
	$f[]="		leap {";
	$f[]="		}";
	$f[]="";
	$f[]="		gtc {";
	$f[]="";
	$f[]="			auth_type = PAP";
	$f[]="		}";
	$f[]="";
	$f[]="		tls {";
	$f[]="	";
	$f[]="			certdir = \${confdir}/certs";
	$f[]="			cadir = \${confdir}/certs";
	$f[]="			private_key_password = whatever";
	$f[]="			private_key_file = \${certdir}/server.key";
	$f[]="			certificate_file = \${certdir}/server.pem";
	$f[]="			CA_file = \${cadir}/ca.pem";
	$f[]="			dh_file = \${certdir}/dh";
	$f[]="			random_file = /dev/urandom";
	$f[]="		#	fragment_size = 1024";
	$f[]="		#	include_length = yes";
	$f[]="		#	check_crl = yes";
	$f[]="			CA_path = \${cadir}";
	$f[]="		#       check_cert_issuer = \"/C=GB/ST=Berkshire/L=Newbury/O=My Company Ltd\"";
	$f[]="		#	check_cert_cn = %{User-Name}";
	$f[]="		#";
	$f[]="			cipher_list = \"DEFAULT\"";
	$f[]="			make_cert_command = \"\${certdir}/bootstrap\"";
	$f[]="			cache {";
	$f[]="			      enable = no";
	$f[]="			      lifetime = 24 # hours";
	$f[]="			      max_entries = 255";
	$f[]="			}";
	$f[]="			verify {";
	$f[]="		#     		tmpdir = /tmp/radiusd";
	$f[]="		#    		client = \"/path/to/openssl verify -CApath \${..CA_path} %{TLS-Client-Cert-Filename}\"";
	$f[]="			}";
	$f[]="		}";
	$f[]="";
	$f[]="		ttls {";
	$f[]="			default_eap_type = $ttls_default_eap_type";
	$f[]="			copy_request_to_tunnel = $ttls_copy_request_to_tunnel";
	$f[]="			use_tunneled_reply = $ttls_use_tunneled_reply";
	$f[]="			virtual_server = \"inner-tunnel\"";
	$f[]="		#	include_length = yes";
	$f[]="		}";
	$f[]="";
	$f[]="		peap {";
	$f[]="			default_eap_type = $peap_default_eap_type";
	$f[]="			copy_request_to_tunnel = $peap_copy_request_to_tunnel";
	$f[]="			use_tunneled_reply = $peap_use_tunneled_reply";
	$f[]="		#	proxy_tunneled_request_as_eap = yes";
	$f[]="			virtual_server = \"inner-tunnel\"";
	$f[]="		}";
	$f[]="";
	$f[]="		mschapv2 {";
	$f[]="		}";
	$f[]="	}";
	$f[]="";

	@file_put_contents("/etc/freeradius/eap.conf", @implode("\n", $f));
	echo "Starting FreeRadius.............: /etc/freeradius/eap.conf done...\n";
	
}

function proxy(){
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}

	
	$f[]="proxy server {";
	$f[]="default_fallback = no";
	$f[]="";
	$f[]="}";
	$f[]="";
	$f[]="home_server localhost {";
	$f[]="	type = auth";
	$f[]="	ipaddr = 127.0.0.1";
	$f[]="	# virtual_server = foo";
	$f[]="	port = 1812";
	$f[]="	secret = testing123";
	$f[]="#	src_ipaddr = 127.0.0.1";
	$f[]="	require_message_authenticator = yes";
	$f[]="	response_window = 20";
	$f[]="#	no_response_fail = no";
	$f[]="	zombie_period = 40";
	$f[]="	revive_interval = 120";
	$f[]="	status_check = status-server";
	$f[]="	# username = \"test_user_please_reject_me\"";
	$f[]="	# password = \"this is really secret\"";
	$f[]="	check_interval = 30";
	$f[]="	num_answers_to_alive = 3";
	$f[]="	coa {";
	$f[]="		irt = 2";
	$f[]="		mrt = 16";
	$f[]="		mrc = 5";
	$f[]="		mrd = 30";
	$f[]="	}";
	$f[]="}";
	$f[]="";
	$f[]="home_server_pool my_auth_failover {";
	$f[]="	type = fail-over";
	$f[]="	#virtual_server = pre_post_proxy_for_pool";
	$f[]="	home_server = localhost";
	$f[]="	#fallback = virtual.example.com";
	$f[]="}";
	$f[]="";
	$f[]="realm example.com {";
	$f[]="	auth_pool = my_auth_failover";
	$f[]="#	acct_pool = acct";
	$f[]="}";
	$f[]="";
	$f[]="";
	$f[]="realm LOCAL {";
	$f[]="}";
	$f[]="";
	$f[]="";

	@file_put_contents("/etc/freeradius/proxy.conf", @implode("\n", $f));
	echo "Starting FreeRadius.............: /etc/freeradius/proxy.conf done...\n";
}

function IsMySQL(){
	if(is_file("/usr/sbin/chilli")){return 1;}
	
	$q=new mysql();
	$c=0;
	
	
	$sql="SELECT ID FROM freeradius_db WHERE connectiontype='mysql_local' and `enabled`=1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(mysql_num_rows($results)>0){$c++;}
	return $c;
}

function build_sql_connections(){
	if(!is_file("/usr/sbin/chilli")){
		if(IsMySQL()==0){return;}
	}
	$q=new mysql();
	$f[]="# -*- text -*-";
	$f[]="##";
	$f[]="## sql.conf -- SQL modules";
	$f[]="##";
	$f[]="##	\$Id\$";
	$f[]="";
	$f[]="######################################################################";
	$f[]="#";
	$f[]="#  Configuration for the SQL module";
	$f[]="#";
	$f[]="#  The database schemas and queries are located in subdirectories:";
	$f[]="#";
	$f[]="#	sql/DB/schema.sql	Schema";
	$f[]="#	sql/DB/dialup.conf	Basic dialup (including policy) queries";
	$f[]="#	sql/DB/counter.conf	counter";
	$f[]="#	sql/DB/ippool.conf	IP Pools in SQL";
	$f[]="#	sql/DB/ippool.sql	schema for IP pools.";
	$f[]="#";
	$f[]="#  Where \"DB\" is mysql, mssql, oracle, or postgresql.";
	$f[]="#";
	$f[]="";
	$f[]="sql {";
	$f[]="	#";
	$f[]="	#  Set the database to one of:";
	$f[]="	#";
	$f[]="	#	mysql, mssql, oracle, postgresql";
	$f[]="	#";
	$f[]="	database = \"mysql\"";
	$f[]="";
	$f[]="	#";
	$f[]="	#  Which FreeRADIUS driver to use.";
	$f[]="	#";
	$f[]="	driver = \"rlm_sql_\${database}\"";
	$f[]="";
	$f[]="	# Connection info:";
	$f[]="	server = \"$q->mysql_server\"";
	$f[]="	port = $q->mysql_port";
	$f[]="	login = \"$q->mysql_admin\"";
	$f[]="	password = \"$q->mysql_password\"";
	$f[]="	radius_db = \"artica_backup\"";
	$f[]="	acct_table1 = \"radacct\"";
	$f[]="	acct_table2 = \"radacct\"";
	$f[]="";
	$f[]="	# Allow for storing data after authentication";
	$f[]="	postauth_table = \"radpostauth\"";
	$f[]="";
	$f[]="	authcheck_table = \"radcheck\"";
	$f[]="	authreply_table = \"radreply\"";
	$f[]="";
	$f[]="	groupcheck_table = \"radgroupcheck\"";
	$f[]="	groupreply_table = \"radgroupreply\"";
	$f[]="";
	$f[]="	# Table to keep group info";
	$f[]="	usergroup_table = \"radusergroup\"";
	$f[]="";
	$f[]="	# If set to 'yes' (default) we read the group tables";
	$f[]="	# If set to 'no' the user MUST have Fall-Through = Yes in the radreply table";
	$f[]="	# read_groups = yes";
	$f[]="";
	$f[]="	# Remove stale session if checkrad does not see a double login";
	$f[]="	deletestalesessions = yes";
	$f[]="";
	$f[]="	# Print all SQL statements when in debug mode (-x)";
	$f[]="	sqltrace = no";
	$f[]="	sqltracefile = \${logdir}/sqltrace.sql";
	$f[]="";
	$f[]="	# number of sql connections to make to server";
	$f[]="	num_sql_socks = 5";
	$f[]="";
	$f[]="	# number of seconds to dely retrying on a failed database";
	$f[]="	# connection (per_socket)";
	$f[]="	connect_failure_retry_delay = 60";
	$f[]="";
	$f[]="	# lifetime of an SQL socket.  If you are having network issues";
	$f[]="	# such as TCP sessions expiring, you may need to set the socket";
	$f[]="	# lifetime.  If set to non-zero, any open connections will be";
	$f[]="	# closed \"lifetime\" seconds after they were first opened.";
	$f[]="	lifetime = 0";
	$f[]="";
	$f[]="	# Maximum number of queries used by an SQL socket.  If you are";
	$f[]="	# having issues with SQL sockets lasting \"too long\", you can";
	$f[]="	# limit the number of queries performed over one socket.  After";
	$f[]="	# \"max_qeuries\", the socket will be closed.  Use 0 for \"no limit\".";
	$f[]="	max_queries = 0";
	$f[]="";
	$f[]="	# Set to 'yes' to read radius clients from the database ('nas' table)";
	$f[]="	# Clients will ONLY be read on server startup.  For performance";
	$f[]="	# and security reasons, finding clients via SQL queries CANNOT";
	$f[]="	# be done \"live\" while the server is running.";
	$f[]="	# ";
	$f[]="	#readclients = yes";
	$f[]="";
	$f[]="	# Table to keep radius client info";
	$f[]="	nas_table = \"nas\"";
	$f[]="";
	$f[]="	# Read driver-specific configuration";
	$f[]="	\$INCLUDE sql/\${database}/dialup.conf";
	$f[]="}";
	$f[]="sqlcounter noresetBytecounter {
counter-name = Total-Max-Octets
check-name = Max-Octets
reply-name = ChilliSpot-Max-Total-Octets
sqlmod-inst = sql
key = User-Name
reset = never
query = \"SELECT (SUM(AcctInputOctets)+SUM(AcctOutputOctets)) FROM radacct WHERE UserName='%{%k}'\"
}\n";	
	@file_put_contents("/etc/freeradius/sql.conf", @implode("\n", $f));
	
}


function build_ldap_connections(){
	if(isset($GLOBALS["build_ldap_connections"])){return $GLOBALS["build_ldap_connections"];}
	$sock=new sockets();
	$q=new mysql();
	$FreeRadiusEnableLocalLdap=$sock->GET_INFO("FreeRadiusEnableLocalLdap");
	if(!is_numeric($FreeRadiusEnableLocalLdap)){$FreeRadiusEnableLocalLdap=1;}	
	
	if(CoovaChilliADEnabled()){
		$f[]="\tChilliAD";
		$f[]="\tif (notfound) {";
		$f[]="\t\tChilliAD";
		$f[]="\t}";
		$f[]="\tif (reject) {";
		$f[]="\t\tChilliAD";
		$f[]="\t}";
	
	}
	
	
	if($FreeRadiusEnableLocalLdap==1){
		$f[]="\tldap0";
		$sql="SELECT ID FROM freeradius_db WHERE connectiontype='ldap' and `enabled`=1";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		while ($ligne = mysql_fetch_assoc($results)) {
			$f[]="\tif (notfound) {";
			$f[]="\t\tldap{$ligne["ID"]}";
			$f[]="\t}";
			$f[]="\tif (reject) {";
			$f[]="\t\tldap{$ligne["ID"]}";
			$f[]="\t}";
		}
		
		$sql="SELECT ID FROM freeradius_db WHERE connectiontype='ad' and `enabled`=1";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		while ($ligne = mysql_fetch_assoc($results)) {
			$f[]="\tif (notfound) {";
			$f[]="\t\tldap{$ligne["ID"]}";
			$f[]="\t}";
			$f[]="\tif (reject) {";
			$f[]="\t\tldap{$ligne["ID"]}";
			$f[]="\t}";
		}		
		
		
		
	}else{
		$sql="SELECT ID FROM freeradius_db WHERE connectiontype='ldap' and `enabled`=1";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		while ($ligne = mysql_fetch_assoc($results)) {$TR[]="ldap{$ligne["ID"]}";}
		
		$sql="SELECT ID FROM freeradius_db WHERE connectiontype='ad' and `enabled`=1";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		while ($ligne = mysql_fetch_assoc($results)) {$TR[]="ldap{$ligne["ID"]}";}		
		
		$f[]="\t{$TR[0]}";
		if(count($TR)>0){
			while (list ($num, $ldapid) = each ($TR) ){
				$f[]="\tif (notfound) {";
				$f[]="\t\t$ldapid";
				$f[]="\t}";
				$f[]="\tif (reject) {";
				$f[]="\t\t$ldapid";
				$f[]="\t}";
			}
	
		}
	}
	


	$GLOBALS["build_ldap_connections"]=@implode("\n", $f);
	return $GLOBALS["build_ldap_connections"];
	
}

function inner_tunnel(){
	$sock=new sockets();
	$q=new mysql();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	$isLDAP=isLDAP();

	$FreeRadiusEnableLocalLdap=$sock->GET_INFO("FreeRadiusEnableLocalLdap");
	if(!is_numeric($FreeRadiusEnableLocalLdap)){$FreeRadiusEnableLocalLdap=1;}
	$IsMySQL=IsMySQL();	$UseMySQL=false;
	if($IsMySQL>0){$UseMySQL=true;}
	
	
	$f[]="server inner-tunnel {";
	$f[]="";
	$f[]="listen {";
	$f[]="       ipaddr = 127.0.0.1";
	$f[]="       port = 18120";
	$f[]="       type = auth";
	$f[]="}";
	$f[]="";
	$f[]="";
	$f[]="authorize {";
	$f[]="	chap";
	$f[]="	mschap";
	if($UseMySQL){
		$f[]="\tsql {";
      	$f[]="\t\tok  = return";
     	$f[]="\t}";
     	$f[]="noresetBytecounter";
	}
	
	
	
	$f[]="#	unix";
	$f[]="#	IPASS";
	$f[]="	suffix";
	$f[]="#	ntdomain";
	$f[]="	update control {";
	$f[]="	       Proxy-To-Realm := LOCAL";
	$f[]="	}";
	$f[]="";
	$f[]="	eap {";
	$f[]="		ok = return";
	$f[]="	}";
	$f[]="";
	$f[]="	files";
	$f[]="#	sql";
	$f[]="#	etc_smbpasswd";
	if($isLDAP){$f[]=build_ldap_connections();}
		
	
	$f[]="#	daily";
	$f[]="#	checkval";
	$f[]="	expiration";
	$f[]="	logintime";
	$f[]="	pap";
	$f[]="}";
	$f[]="";
	$f[]="";
	$f[]="authenticate {";
	$f[]="\tAuth-Type PAP {\n\t\tpap\n\t}";
	$f[]="\tAuth-Type CHAP {\n\t\tchap\n\t}";
	$f[]="\tAuth-Type MS-CHAP {\n\t\tmschap\n\t}";
	
	if($isLDAP){
		$f[]="\tAuth-Type LDAP {";
		
		if(CoovaChilliADEnabled()){
			$f[]="\t\tChilliAD{";
			$f[]="\t\t\treject = 1";
			$f[]="\t\t\tok = return";
			$f[]="\t\t}";			
			
		}
		
		if($FreeRadiusEnableLocalLdap==1){
			$f[]="\t\tldap0{";
			$f[]="\t\t\treject = 1";
			$f[]="\t\t\tok = return";
			$f[]="\t\t}";
		}
		$sql="SELECT ID FROM freeradius_db WHERE connectiontype='ldap' and `enabled`=1";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		while ($ligne = mysql_fetch_assoc($results)) {
			$f[]="\t\tldap{$ligne["ID"]}{";
			$f[]="\t\t\treject = 1";
			$f[]="\t\t\tok = return";
			$f[]="\t\t}";			
		}
		$f[]="\t}";
	}
	$f[]="	unix";
	if($EnableKerbAuth==1){$f[]="	ntlm_auth";}

	$f[]="	eap";
	$f[]="}";
	$f[]="";
	$f[]="session {";
	$f[]="	radutmp";
	if($UseMySQL){
		$f[]="	sql";
	}
	$f[]="}";
	$f[]="";
	$f[]="";
	$f[]="post-auth {";
	$f[]="#	reply_log";
	if($UseMySQL){
		$f[]="	sql";
	}
	$f[]="#	sql_log";
	$f[]="#	ldap";
	$f[]="	Post-Auth-Type REJECT {";
	$f[]="		# log failed authentications in SQL, too.";
	$f[]="#		sql";
	$f[]="		attr_filter.access_reject";
	$f[]="	}";
	$f[]="";
	$f[]="";
	$f[]="}";
	$f[]="";
	$f[]="pre-proxy {";
	$f[]="#	attr_rewrite";
	$f[]="#	files";
	$f[]="#	attr_filter.pre-proxy";
	$f[]="#	pre_proxy_log";
	$f[]="}";
	$f[]="";
	$f[]="post-proxy {";
	$f[]="#	post_proxy_log";
	$f[]="#	attr_rewrite";
	$f[]="#	attr_filter.post-proxy";
	$f[]="	eap";
	$f[]="}";
	$f[]="";
	$f[]="}";
	$f[]="";	
	@mkdir("/etc/freeradius/sites-enabled",0755,true);
	@file_put_contents("/etc/freeradius/sites-enabled/inner-tunnel", @implode("\n", $f));
	echo "Starting FreeRadius.............: /etc/freeradius/sites-enabled/inner-tunnel done...\n";	
}

function module_ldap(){
	$ldap=new clladp();
	$q=new mysql();
	$sock=new sockets();
	$FreeRadiusEnableLocalLdap=$sock->GET_INFO("FreeRadiusEnableLocalLdap");
	if(!is_numeric($FreeRadiusEnableLocalLdap)){$FreeRadiusEnableLocalLdap=1;}	
	
	if(CoovaChilliADEnabled()){
		$ChilliConf=CoovaChilliConf();
		$LDAP_FILTER="(&(sAMAccountname=%{Stripped-User-Name:-%{User-Name}})(objectClass=person))";
		if(!is_numeric($ChilliConf["AD_PORT"])){$ChilliConf["AD_PORT"]=389;}
		$LDAP_SERVER=$ChilliConf["AD_SERVER"];
		$LDAP_PORT=$ChilliConf["AD_PORT"];
		$LDAP_SUFFIX=$ChilliConf["AD_SUFFIX"];
		$LDAP_DN=$ChilliConf["AD_DN"];
		$LDAP_PASSWORD=$ChilliConf["AD_PASS"];
			
		$f[]="ldap ChilliAD {";
		$f[]="        server = \"$LDAP_SERVER\"";
		$f[]="        port = \"$LDAP_PORT\"";
		$f[]="        basedn = \"$LDAP_SUFFIX\"";
		$f[]="        filter = \"$LDAP_FILTER\"";
		$f[]="        identity    = \"\"";
		$f[]="        password = \"\"";
		$f[]="        groupname_attribute = cn";
		$f[]="        groupmembership_filter = \"(|(&(objectClass=group)(member=%Ldap-UserDn}))(&(objectClass=top)(uniquemember=%{Ldap-UserDn})))\"";
		$f[]="        groupmembership_attribute = memberOf";
		$f[]="        access_attr_used_for_allow = yes";
		$f[]="        ldap_connections_number = 5";
		$f[]="        chase_referrals = yes";
		$f[]="        ldap_debug = 5";
		$f[]="        ldap_connections_number = 5";
		$f[]="        compare_check_items = no";
		$f[]="        do_xlat = yes";
		$f[]="        set_auth_type = yes";
		$f[]="        rebind = yes";
		$f[]="        timeout = 4";
		$f[]="        timelimit = 3";
		$f[]="        net_timeout = 1";
		$f[]="        tls {";
		$f[]="                start_tls = no";
		$f[]="        }";
		$f[]="        dictionary_mapping = \${confdir}/ldap.attrmap";
		$f[]="        edir_account_policy_check = no";
		$f[]="}\n";
			
	
	
	
	}	
	
	
	if($FreeRadiusEnableLocalLdap==1){
		
		$f[]="ldap ldap0 {";
		$f[]="        server = \"$ldap->ldap_host\"";
		$f[]="        basedn = \"dc=organizations,$ldap->suffix\"";
		$f[]="        filter = \"(uid=%{%{Stripped-User-Name}:-%{User-Name}})\"";
		$f[]="        ldap_connections_number = 5";
		$f[]="        timeout = 4";
		$f[]="        timelimit = 3";
		$f[]="        net_timeout = 1";
		$f[]="        tls {";
		$f[]="                start_tls = no";
		$f[]="        }";
		$f[]="        dictionary_mapping = \${confdir}/ldap.attrmap";
		$f[]="        password_attribute = userPassword";
		$f[]="        edir_account_policy_check = no";
		$f[]="        access_attr_used_for_allow = no";
		$f[]="}\n";
		
	}
	
	$sql="SELECT ID,params FROM freeradius_db WHERE connectiontype='ldap' and `enabled`=1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
			$array=unserialize(base64_decode($ligne["params"]));
			if($array["LDAP_FILTER"]==null){$array["LDAP_FILTER"]="(uid=%{%{Stripped-User-Name}:-%{User-Name}})";}
			if($array["PASSWORD_ATTRIBUTE"]==null){$array["PASSWORD_ATTRIBUTE"]="userPassword";}
			if(!is_numeric($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}	
			$LDAP_SERVER=$array["LDAP_SERVER"];
			$LDAP_PORT=$array["LDAP_PORT"];
			$LDAP_SUFFIX=$array["LDAP_SUFFIX"];
			$LDAP_FILTER=$array["LDAP_FILTER"];
			$LDAP_DN=$array["LDAP_DN"];
			$LDAP_PASSWORD=$array["LDAP_PASSWORD"];
			$PASSWORD_ATTRIBUTE=$array["PASSWORD_ATTRIBUTE"];
			$ACCESS_ATTRIBUTE=$array["ACCESS_ATTRIBUTE"];
					
			$f[]="ldap ldap{$ligne["ID"]} {";
			$f[]="        server = \"$LDAP_SERVER\"";
			$f[]="        port = \"$LDAP_PORT\"";
			$f[]="        basedn = \"$LDAP_SUFFIX\"";
			$f[]="        filter = \"$LDAP_FILTER\"";
			$f[]="        identity    = \"$LDAP_DN\"";
			$f[]="        password = \"$LDAP_PASSWORD\"";	
			$f[]="        ldap_connections_number = 5";
			$f[]="        timeout = 4";
			$f[]="        timelimit = 3";
			$f[]="        net_timeout = 1";
			$f[]="        tls {";
			$f[]="                start_tls = no";
			$f[]="        }";
			$f[]="        dictionary_mapping = \${confdir}/ldap.attrmap";
			$f[]="        password_attribute = $PASSWORD_ATTRIBUTE";
			if($ACCESS_ATTRIBUTE<>null){
				$f[]="        access_attr = \"$ACCESS_ATTRIBUTE\"";
				$f[]="        access_attr_used_for_allow = yes";
			}
			$f[]="        edir_account_policy_check = no";
			$f[]="}\n";
						
			
		}
		$sql="SELECT ID,params FROM freeradius_db WHERE connectiontype='ad' and `enabled`=1";
		$results = $q->QUERY_SQL($sql,"artica_backup");		
		while ($ligne = mysql_fetch_assoc($results)) {
			$array=unserialize(base64_decode($ligne["params"]));
			$array["LDAP_FILTER"]="(&(sAMAccountname=%{Stripped-User-Name:-%{User-Name}})(objectClass=person))";
			$ADGROUP=trim($array["ADGROUP"]);
			if(!is_numeric($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
			$LDAP_SERVER=$array["LDAP_SERVER"];
			$LDAP_PORT=$array["LDAP_PORT"];
			$LDAP_SUFFIX=$array["LDAP_SUFFIX"];
			$LDAP_FILTER=$array["LDAP_FILTER"];
			$LDAP_DN=$array["LDAP_DN"];
			$LDAP_PASSWORD=$array["LDAP_PASSWORD"];
			$PASSWORD_ATTRIBUTE=$array["PASSWORD_ATTRIBUTE"];
			$ACCESS_ATTRIBUTE=$array["ACCESS_ATTRIBUTE"];
				
			$f[]="ldap ldap{$ligne["ID"]} {";
			$f[]="        server = \"$LDAP_SERVER\"";
			$f[]="        port = \"$LDAP_PORT\"";
			$f[]="        basedn = \"$LDAP_SUFFIX\"";
			$f[]="        filter = \"$LDAP_FILTER\"";
			$f[]="        identity    = \"$LDAP_DN\"";
			$f[]="        password = \"$LDAP_PASSWORD\"";	
			$f[]="        groupname_attribute = cn";
			$f[]="        groupmembership_filter = \"(|(&(objectClass=group)(member=%Ldap-UserDn}))(&(objectClass=top)(uniquemember=%{Ldap-UserDn})))\"";
			$f[]="        groupmembership_attribute = memberOf";
			$f[]="        ldap_connections_number = 5";
			$f[]="        chase_referrals = yes";
			$f[]="        rebind = yes";
			$f[]="        timeout = 4";
			$f[]="        timelimit = 3";
			$f[]="        net_timeout = 1";
			$f[]="        tls {";
			$f[]="                start_tls = no";
			$f[]="        }";
			$f[]="        dictionary_mapping = \${confdir}/ldap.attrmap";
			if($ACCESS_ATTRIBUTE<>null){
				$f[]="        access_attr = \"$ACCESS_ATTRIBUTE\"";
				$f[]="        access_attr_used_for_allow = yes";
			}
			$f[]="        edir_account_policy_check = no";
			$f[]="}\n";
		
								
		}


	
		
		
	
	@mkdir("/etc/freeradius/modules",0755,true);
	@file_put_contents("/etc/freeradius/modules/ldap", @implode("\n", $f));
	echo "Starting FreeRadius.............: /etc/freeradius/modules/ldap done...\n";
	
	
}
function CoovaChilliADEnabled(){
	if(!CoovaChilliEnabled()){return false;}
	$ChilliConf=CoovaChilliConf();
	if($ChilliConf["EnableActiveDirectory"]==1){return true;}
	return false;
}



function CoovaChilliEnabled(){
	if(isset($GLOBALS["ChilliEnabled"])){return $GLOBALS["ChilliEnabled"];}
	$unix=new unix();
	$chilli=$unix->find_program("chilli");
	if(!is_file($chilli)){$GLOBALS["ChilliEnabled"]=false;return;}
	$sock=new sockets();
	$EnableChilli=$sock->GET_INFO("EnableChilli");
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}
	if($EnableChilli==1){$GLOBALS["ChilliEnabled"]=true;return true;}
	$GLOBALS["ChilliEnabled"]=false;
	return false;
}
function CoovaChilliConf(){
	if(isset($GLOBALS["ChilliConf"])){return $GLOBALS["ChilliConf"];}
	$ChilliConf=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/ChilliConf")));
	$GLOBALS["ChilliConf"]=$ChilliConf;
	return $GLOBALS["ChilliConf"];
}
 


function clients(){
	$ldap=new clladp();
	$f[]="client localhost {";
	$f[]="	ipaddr = 127.0.0.1";
	$f[]="#	netmask = 32";
	$f[]="	secret		= $ldap->ldap_password";
	$f[]=" 	shortname	= localhost";
	$f[]="	nastype     = other	# localhost isn't usually a NAS...";
	$f[]="#	login       = !root";
	$f[]="#	password    = someadminpas";
	$f[]="#	virtual_server = home1";
	$f[]="#	coa_server = coa";
	$f[]="}";
	
	if(CoovaChilliEnabled()){
		$ChilliConf=CoovaChilliConf();
		$sock=new sockets();
		$nics=new system_nic($ChilliConf["HS_WANIF"]);
		if($nics->IPADDR<>null){$ListenIP=$nics->IPADDR;}
		$f[]="client localhost{$ChilliConf["HS_WANIF"]} {";
		$f[]="	ipaddr = $ListenIP";
		$f[]="	secret		= $ldap->ldap_password";
		$f[]=" 	shortname	= localhost{$ChilliConf["HS_WANIF"]}";
		$f[]="	nastype     = other	# localhost isn't usually a NAS...";
		$f[]="}";
		
		
	}
	
	
	$f[]="";
	$f[]="#client 192.168.0.0/24 {";
	$f[]="#	secret		= testing123-1";
	$f[]="#	shortname	= private-network-1";
	$f[]="#}";
	$f[]="#";
	$f[]="#client 192.168.0.0/16 {";
	$f[]="#	secret		= testing123-2";
	$f[]="#	shortname	= private-network-2";
	$f[]="#}";
	$f[]="";
	$f[]="";
	$f[]="#client 10.10.10.10 {";
	$f[]="#	# secret and password are mapped through the \"secrets\" file.";
	$f[]="#	secret      = testing123";
	$f[]="#	shortname   = liv1";
	$f[]="#       # the following three fields are optional, but may be used by";
	$f[]="#       # checkrad.pl for simultaneous usage checks";
	$f[]="#	nastype     = livingston";
	$f[]="#	login       = !root";
	$f[]="#	password    = someadminpas";
	$f[]="#}";
	$f[]="";
	
	$q=new mysql();
	$sql="SELECT * FROM freeradius_clients WHERE `enabled`=1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$f[]="client {$ligne["ipaddr"]} {";
		$f[]="\tsecret      = {$ligne["secret"]}";
		$f[]="\tshortname   = {$ligne["shortname"]}";
		$f[]="\tnastype     = {$ligne["nastype"]}";
		$f[]="}\n";
	}	

	@mkdir("/etc/freeradius/modules",0755,true);
	@file_put_contents("/etc/freeradius/clients.conf", @implode("\n", $f));
	echo "Starting FreeRadius.............: /etc/freeradius/clients.conf done...\n";	
	
}


function confusers(){
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	$isLDAP=isLDAP();
	@file_put_contents("/etc/freeradius/users", "\n");
	return;
	$f[]="";
	if($isLDAP==1){	$f[]="DEFAULT Auth-Type = LDAP\n\t\tFall-Through = 0";}
	if($EnableKerbAuth==1){	$f[]="DEFAULT Auth-Type = ntlm_auth\n\t\tFall-Through = 1";}
	$sql="SELECT ID,params FROM freeradius_db WHERE connectiontype='ad' and `enabled`=1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$array=unserialize(base64_decode($ligne["params"]));
		$ADGROUP=trim($array["ADGROUP"]);
		if($ADGROUP<>null){
			$ADGROUP=str_replace(",", ";", $ADGROUP);
			if(strpos(" $ADGROUP", ";")>0){
				$ADGROUPTR=explode(";", $ADGROUP);
				while (list ($num, $gpname) = each ($ADGROUPTR) ){if($gpname==null){continue;}$f[]="DEFAULT Ldap-Group == \"$gpname\"\n\tFall-Through = yes";}
			}else{
				$f[]="DEFAULT Ldap-Group == \"$ADGROUP\"\n\tFall-Through = yes";
			}
		}
	}
	
	$f[]="DEFAULT Auth-Type = Reject";
	$f[]="\tFall-Through = 1\n";
	@mkdir("/etc/freeradius/",0755,true);
	@file_put_contents("/etc/freeradius/users", @implode("\n", $f));
	echo "Starting FreeRadius.............: /etc/freeradius/users done...\n";	
}

function isLDAP(){
	$sock=new sockets();
	$FreeRadiusEnableLocalLdap=$sock->GET_INFO("FreeRadiusEnableLocalLdap");
	if(!is_numeric($FreeRadiusEnableLocalLdap)){$FreeRadiusEnableLocalLdap=1;}	
	if($FreeRadiusEnableLocalLdap==1){return true;}
	$sql="SELECT COUNT(ID) as tcount FROM freeradius_db WHERE connectiontype='ldap' AND `enabled`=1";
	$q=new mysql();
	$ligne=mysql_fetch_array(
			$q->QUERY_SQL($sql,"artica_backup")
	);
	if($ligne["tcount"]>0){return true;}
	
	$sql="SELECT COUNT(ID) as tcount FROM freeradius_db WHERE connectiontype='ad' AND `enabled`=1";
	$q=new mysql();
	$ligne=mysql_fetch_array(
			$q->QUERY_SQL($sql,"artica_backup")
	);
	if($ligne["tcount"]>0){return true;}	
	if(CoovaChilliADEnabled()){return true;}
	return false;
}

function site_default(){
	@unlink("/etc/freeradius/sites-enabled/default");
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	$isLDAP=isLDAP();
	$FreeRadiusEnableLocalLdap=$sock->GET_INFO("FreeRadiusEnableLocalLdap");
	if(!is_numeric($FreeRadiusEnableLocalLdap)){$FreeRadiusEnableLocalLdap=1;}	
	$q=new mysql();
	$IsMySQL=IsMySQL();	$UseMySQL=false;
	if($IsMySQL>0){$UseMySQL=true;}
		
	$f[]="authorize {";
	$f[]="	preprocess";
	$f[]="#	auth_log";
	if($UseMySQL){
		$f[]="\tsql {";
      	$f[]="\t\tok  = return";
     	$f[]="\t}";
     	$f[]="noresetBytecounter";
	}
	$f[]="	chap";
	$f[]="	mschap";
	$f[]="	digest";
	$f[]="#	wimax";
	$f[]="#	IPASS";
	$f[]="	suffix";
	$f[]="#	ntdomain";
	$f[]="	eap {";
	$f[]="		ok = return";
	$f[]="	}";
	$f[]="";
	$f[]="#	unix";
	$f[]="	files";
	$f[]="#	etc_smbpasswd";
	if($isLDAP){$f[]=build_ldap_connections();}
	$f[]="#	daily";
	$f[]="#	checkval";
	$f[]="	expiration";
	$f[]="	logintime";
	$f[]="	pap";
	$f[]="";
	$f[]="}";
	$f[]="";
	$f[]="";
	$f[]="authenticate {";
	$f[]="	Auth-Type PAP {\n\t\tpap\n\t}";
	$f[]="	Auth-Type CHAP {\n\t\tchap\n\t}";
	$f[]="	Auth-Type MS-CHAP {\n\t\tmschap\n\t}";
	if($isLDAP){
		$f[]="\tAuth-Type LDAP {";
		
		if(CoovaChilliADEnabled()){
			$f[]="\t\tChilliAD{";
			$f[]="\t\t\treject = 1";
			$f[]="\t\t\tok = return";
			$f[]="\t\t}";
				
		}
		
		
		if($FreeRadiusEnableLocalLdap==1){
			$f[]="\t\tldap0{";
			$f[]="\t\t\treject = 1";
			$f[]="\t\t\tok = return";
			$f[]="\t\t}";
		}
		$sql="SELECT ID FROM freeradius_db WHERE connectiontype='ldap' and `enabled`=1";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		while ($ligne = mysql_fetch_assoc($results)) {
			$f[]="\t\tldap{$ligne["ID"]}{";
			$f[]="\t\t\treject = 1";
			$f[]="\t\t\tok = return";
			$f[]="\t\t}";			
		}
		
		$sql="SELECT ID FROM freeradius_db WHERE connectiontype='ad' and `enabled`=1";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		while ($ligne = mysql_fetch_assoc($results)) {
			$f[]="\t\tldap{$ligne["ID"]}{";
			$f[]="\t\t\treject = 1";
			$f[]="\t\t\tok = return";
			$f[]="\t\t}";
		}		
	
		$f[]="\t}";
	}
	if($EnableKerbAuth==1){$f[]="	ntlm_auth";}	
	$f[]="	digest";
	$f[]="#	pam";
	$f[]="	unix";
	$f[]="	eap";
	$f[]="}";
	$f[]="";
	$f[]="";
	$f[]="#";
	$f[]="#  Pre-accounting.  Decide which accounting type to use.";
	$f[]="#";
	$f[]="preacct {";
	$f[]="	preprocess";
	$f[]="	acct_unique";
	$f[]="#	IPASS";
	$f[]="	suffix";
	$f[]="#	ntdomain";
	$f[]="";
	$f[]="	files";
	$f[]="}";
	$f[]="";
	$f[]="accounting {";
	$f[]="	detail";
	$f[]="#	daily";
	$f[]="	unix";
	$f[]="	radutmp";
	$f[]="#	sradutmp";
	$f[]="#	main_pool";
	if($UseMySQL){
		$f[]="	sql";
	}
	$f[]="#	sql_log";
	$f[]="#	pgsql-voip";
	$f[]="	exec";
	$f[]="	attr_filter.accounting_response";
	$f[]="}";
	$f[]="session {";
	$f[]="	radutmp";
	if($UseMySQL){
		$f[]="	sql";
	}
	$f[]="}";
	$f[]="";
	$f[]="";
	$f[]="post-auth {";
	$f[]="#	reply_log";
	if($UseMySQL){
		$f[]="	sql";
	}
	$f[]="#	sql_log";
	$f[]="#	ldap";
	$f[]="	exec";
	$f[]="#	wimax";
	$f[]="	Post-Auth-Type REJECT {";
	$f[]="#		sql";
	$f[]="		attr_filter.access_reject";
	$f[]="	}";
	$f[]="}";
	$f[]="";
	$f[]="pre-proxy {";
	$f[]="#	attr_rewrite";
	$f[]="#	files";
	$f[]="#	attr_filter.pre-proxy";
	$f[]="#	pre_proxy_log";
	$f[]="}";
	$f[]="";
	$f[]="post-proxy {";
	$f[]="#	post_proxy_log";
	$f[]="#	attr_rewrite";
	$f[]="#	attr_filter.post-proxy";
	$f[]="	eap";
	$f[]="}";
	$f[]="";	
	@mkdir("/etc/freeradius/sites-enabled");
	@file_put_contents("/etc/freeradius/sites-enabled/default", @implode("\n", $f));
	echo "Starting FreeRadius.............: /etc/freeradius/sites-enabled/default done...\n";	
}



function start(){
	$unix=new unix();
	$sock=new sockets();
	$EnableFreeRadius=$sock->GET_INFO("EnableFreeRadius");
	$EnableChilli=$sock->GET_INFO("EnableChilli");
	
	
	if(!is_numeric($EnableFreeRadius)){$EnableFreeRadius=0;}
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}
	if($EnableChilli==1){$EnableFreeRadius=1;$sock->SET_INFO("EnableFreeRadius",1);}
	if($EnableFreeRadius==0){
		echo "Starting FreeRadius.............: service is disabled\n";
		stop();
		return;
	}
	$pid=freeradius_pid();
	
	if($unix->process_exists($pid)){
		$pidtime=$unix->PROCCESS_TIME_MIN($pid);
		echo "Starting FreeRadius.............: Already running pid $pid since {$pidtime}mn\n";
		return;
	}	
	$freeradius=$unix->find_program("freeradius");
	if(!is_file($freeradius)){
		echo "Starting FreeRadius.............: failed, freeradius, no such binary...\n";
	}
	echo "Starting FreeRadius.............: Building configuration...\n";
	build();	
	
	$freeradius_version=freeradius_version();
	echo "Starting FreeRadius.............: daemon version $freeradius_version\n";
	if(CoovaChilliADEnabled()){
		$ChilliConf=CoovaChilliConf();
		if($ChilliConf["RADIUS_IF"]<>null){
			$array=$unix->InterfaceToIP($ChilliConf["RADIUS_IF"]);
			$ip=$array["IP"];
			$f[]="-i $ip -p 1812";
		}
		
		
	}
	$f[]="-d /etc/freeradius -n radiusd";
	$cmdline="$freeradius ".@implode(" ", $f);
	shell_exec($cmdline);

	for($i=1;$i<11;$i++){
		echo "Starting FreeRadius.............: waiting $i/10\n";
		sleep(1);
		$pid=freeradius_pid();
		if($unix->process_exists($pid)){echo "Starting FreeRadius.............: Success PID $pid\n";return;}
	}	
	$pid=freeradius_pid();
	if($unix->process_exists($pid)){
		echo "Starting FreeRadius.............: Success PID $pid\n";
		return;
	}else{
		echo "Starting FreeRadius.............: Failed\n";
		echo "Starting FreeRadius.............: $cmdline\n";
	}	
	
}

function freeradius_version(){
	$unix=new unix();
	$freeradius=$unix->find_program("freeradius");
	exec("$freeradius -v 2>&1",$results);
	while (list ($dir, $val) = each ($results) ){
		if(!preg_match("#Version ([0-9\.]+)#", $val,$re)){continue;}
		return $re[1];
	}
	
}

function stop(){
	$unix=new unix();
	echo "Stopping FreeRadius.............: find binaries daemons\n";
	$pidof=$unix->find_program("pidof");
	$kill=$unix->find_program("kill");

	
	$pid=freeradius_pid();
	if(!$unix->process_exists($pid)){
		echo "Stopping FreeRadius.............: Already stopped\n";
		return;
	}
	
	$pidtime=$unix->PROCCESS_TIME_MIN($pid);
	echo "Stopping FreeRadius.............: PID $pid since {$pidtime}mn\n";
	shell_exec("$kill $pid >/dev/null 2>&1");
	
	for($i=1;$i<11;$i++){
		echo "Stopping FreeRadius.............: waiting PID: $pid $i/10\n";
		sleep(1);
		$pid=freeradius_pid();
		if(!$unix->process_exists($pid)){
			echo "Stopping FreeRadius.............: Stopped\n";
			return;
		}
	}
	
	$pid=freeradius_pid();
	if(!$unix->process_exists($pid)){
		echo "Stopping FreeRadius.............: Stopped\n";
		return;
	}else{
		echo "Stopping FreeRadius.............: Failed\n";
	}	

}


function microtik(){
	
	if(is_file("/usr/share/freeradius/dictionary.mikrotik")){return;}
	
	$f[]="# MikroTik vendor specific dictionary";
	$f[]="# Copyright (C) 2003-2009 MikroTikls, SIA";
	$f[]="#";
	$f[]="# You may freely redistribute and use this software or any part of it in source";
	$f[]="# and/or binary forms, with or without modification for any purposes without";
	$f[]="# limitations, provided that you respect the following statement:";
	$f[]="#";
	$f[]="# This software is provided 'AS IS' without a warranty of any kind, expressed or";
	$f[]="# implied, including, but not limited to, the implied warranty of";
	$f[]="# merchantability and fitness for a particular purpose. In no event shall";
	$f[]="# MikroTikls SIA be liable for direct or indirect, incidental, consequential or";
	$f[]="# other damages that may result from the use of this software, including, but";
	$f[]="# not limited to, loss of data, time and (or) profits.";
	$f[]="#";
	$f[]="# \$Id: dictionary.mikrotik,v 1.6 2007/06/15 08:25:04 aland Exp \$";
	$f[]="#";
	$f[]="# MikroTik Attributes                                                ";
	$f[]="";
	$f[]="VENDOR          Mikrotik                        14988";
	$f[]="";
	$f[]="BEGIN-VENDOR    Mikrotik";
	$f[]="";
	$f[]="ATTRIBUTE       Mikrotik-Recv-Limit                     1       integer";
	$f[]="ATTRIBUTE       Mikrotik-Xmit-Limit                     2       integer";
	$f[]="";
	$f[]="# this attribute is unused";
	$f[]="ATTRIBUTE       Mikrotik-Group                          3       string";
	$f[]="";
	$f[]="ATTRIBUTE       Mikrotik-Wireless-Forward               4       integer";
	$f[]="ATTRIBUTE       Mikrotik-Wireless-Skip-Dot1x            5       integer";
	$f[]="ATTRIBUTE       Mikrotik-Wireless-Enc-Algo              6       integer";
	$f[]="ATTRIBUTE       Mikrotik-Wireless-Enc-Key               7       string";
	$f[]="ATTRIBUTE       Mikrotik-Rate-Limit                     8       string";
	$f[]="ATTRIBUTE       Mikrotik-Realm                          9       string";
	$f[]="ATTRIBUTE       Mikrotik-Host-IP                        10      ipaddr";
	$f[]="ATTRIBUTE       Mikrotik-Mark-Id                        11      string";
	$f[]="ATTRIBUTE       Mikrotik-Advertise-URL                  12      string";
	$f[]="ATTRIBUTE       Mikrotik-Advertise-Interval             13      integer";
	$f[]="ATTRIBUTE       Mikrotik-Recv-Limit-Gigawords           14      integer";
	$f[]="ATTRIBUTE       Mikrotik-Xmit-Limit-Gigawords           15      integer";
	$f[]="# MikroTik Values";
	$f[]="";
	$f[]="VALUE   Mikrotik-Wireless-Enc-Algo      No-encryption           0";
	$f[]="VALUE   Mikrotik-Wireless-Enc-Algo      40-bit-WEP              1";
	$f[]="VALUE   Mikrotik-Wireless-Enc-Algo      104-bit-WEP             2";
	$f[]="";
	$f[]="END-VENDOR      Mikrotik";
	@mkdir("/usr/share/freeradius",0755);
	@file_put_contents("/usr/share/freeradius/dictionary.mikrotik", @implode("\n", $f));	
}
