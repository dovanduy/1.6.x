<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");

if($argv[1]=="--build"){build();exit;}
build();
function build(){
	$sock=new sockets();
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	if(!is_dir("$ArticaDBPath/data/catz")){return;}
$f[]="[client] ";
$f[]="#password	= your_password ";
$f[]="#port		= 3306 ";
$f[]="socket		= /var/run/mysqld/articadb.sock ";
$f[]=" ";
$f[]=" ";
$f[]="[mysqld] ";
$f[]="#port		= 3306 ";
$f[]="socket		= /var/run/mysqld/articadb.sock ";
$f[]="skip-external-locking";
$f[]="skip-networking ";
$f[]="skip-innodb";
$f[]="skip-slave-start";
$f[]="default-storage-engine = MYISAM ";
$f[]="default_tmp_storage_engine = MYISAM ";
$f[]="key_buffer_size = 16M ";
$f[]="max_allowed_packet = 1M ";
$f[]="query_cache_type = 1";
$f[]="query_cache_size = 35M";
$f[]="max_heap_table_size=40M";
$f[]="tmp_table_size=8M";
$f[]="table_open_cache = 180 ";
$f[]="sort_buffer_size = 256K ";
$f[]="read_buffer_size = 1M ";
$f[]="read_rnd_buffer_size = 256K ";
$f[]="net_buffer_length = 128K ";
$f[]="thread_stack = 128K ";
$f[]="thread_cache_size=8";
$f[]="table_open_cache=70";
$f[]="max_connections=20";
$f[]="server-id	= 1 ";
$f[]="#log-bin=mysql-bin ";
$f[]="#binlog_format=mixed ";
$f[]="#binlog_direct_non_transactional_updates=TRUE ";
$f[]="tmpdir=$ArticaDBPath/tmp";
$f[]="open_files_limit=2048";
$f[]=" ";
$f[]=" ";
$f[]="[mysqldump] ";
$f[]="quick ";
$f[]="max_allowed_packet = 16M ";
$f[]=" ";
$f[]="[mysql] ";
$f[]="no-auto-rehash ";
$f[]="#safe-updates ";
$f[]=" ";
$f[]="[myisamchk] ";
$f[]="key_buffer_size = 8M ";
$f[]="sort_buffer_size = 8M ";
$f[]=" ";
$f[]="[mysqlhotcopy] ";
$f[]="interactive-timeout ";
$f[]="";
@file_put_contents("$ArticaDBPath/my.cnf", @implode("\n", $f));
@mkdir("$ArticaDBPath/mysql/etc",0755,true);
@mkdir("$ArticaDBPath/tmp",0755,true);
shell_exec("/bin/ln -sf $ArticaDBPath/data $ArticaDBPath/mysql/data");
shell_exec("/bin/ln -sf $ArticaDBPath/my.cnf $ArticaDBPath/mysql/etc/my.cnf");
@unlink("$ArticaDBPath/data/data");
$q=new mysql();
if($q->DATABASE_EXISTS("catz")){$q->DELETE_DATABASE("catz");}
echo "Starting......: ".date("H:i:s")." ArticaDBst configuration done...\n";

}


