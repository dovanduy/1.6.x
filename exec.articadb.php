<?php
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/framework/class.mysql.inc");

if($argv[1]=="--build"){build();exit;}

function build(){
	if(!is_dir("/opt/articatech/data/catz")){return;}
$f[]="[client] ";
$f[]="#password	= your_password ";
$f[]="#port		= 3306 ";
$f[]="socket		= /var/run/articadb.sock ";
$f[]=" ";
$f[]=" ";
$f[]="[mysqld] ";
$f[]="#port		= 3306 ";
$f[]="socket		= /var/run/articadb.sock ";
$f[]="skip-external-locking";
$f[]="skip-networking ";
$f[]="skip-innodb";
$f[]="skip-slave-start";
$f[]="default-storage-engine = MYISAM ";
$f[]="default_tmp_storage_engine = MYISAM ";
$f[]="key_buffer_size = 16K ";
$f[]="max_allowed_packet = 1M ";
$f[]="table_open_cache = 4 ";
$f[]="sort_buffer_size = 64K ";
$f[]="read_buffer_size = 256K ";
$f[]="read_rnd_buffer_size = 256K ";
$f[]="net_buffer_length = 2K ";
$f[]="thread_stack = 128K ";
$f[]="server-id	= 1 ";
$f[]="#log-bin=mysql-bin ";
$f[]="#binlog_format=mixed ";
$f[]="#binlog_direct_non_transactional_updates=TRUE ";
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
@file_put_contents("/opt/articatech/my.cnf", @implode("\n", $f));

$q=new mysql();
if($q->DATABASE_EXISTS("catz")){$q->DELETE_DATABASE("catz");}
echo "Starting......: ArticaDBst configuration done...\n";

}


