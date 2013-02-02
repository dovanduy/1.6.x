<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-multi.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-server.inc');



if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
start($argv[1],$argv[2]);

function start($database,$table){
	$unix=new unix();
	if($database==null){WriteIsamLogs("Requested myismamchk database:$database, table: $table -> database is null");return;}
	if($table==null){WriteIsamLogs("Requested myismamchk database:$database, table: $table -> table is null");return;}	
	$pidfile="/etc/artica-postfix/".basename(__FILE__).".".md5("$database$table").".pid";
	$oldpid=@file_get_contents("$database$table");
	if($unix->process_exists($oldpid)){
		$timepid=$unix->PROCCESS_TIME_MIN($oldpid);
		WriteIsamLogs("Already PID $oldpid running since {$timepid}mn, aborting");
		return;
	}
	@file_put_contents($pidfile, getmypid());
	WriteIsamLogs("Requested myismamchk database:$database, table: $table");
	$pgrep=$unix->find_program("pgrep");
	$myisamchk=$unix->find_program("myisamchk");
	$touch=$unix->find_program("touch");
	
	exec("$pgrep -l -f \"$myisamchk.*?$table\" 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#pgrep#", $line)){continue;}
		if(preg_match("#([0-9]+)\s+#", $line,$re)){
			$timepid=$unix->PROCCESS_TIME_MIN($re[1]);
			WriteIsamLogs("Already PID $line running since {$timepid}mn, aborting");
			return;
		}
		
	}
	
	$MYSQL_DATADIR=$unix->MYSQL_DATADIR();
	
	if(is_file("$MYSQL_DATADIR/$database/$table.TMD")){
		WriteIsamLogs("remove $MYSQL_DATADIR/$database/$table.TMD");
		@unlink("$MYSQL_DATADIR/$database/$table.TMD");
	}
	
	if(!is_file("$MYSQL_DATADIR/$database/$table.MYD")){
		
		WriteIsamLogs("$touch $MYSQL_DATADIR/$database/$table.MYD");
		shell_exec("$touch $MYSQL_DATADIR/$database/$table.MYD");
	
	}
	
	if(!is_file("$MYSQL_DATADIR/$database/$table.MYI")){
		WriteIsamLogs("$MYSQL_DATADIR/$database/$table.MYI no such file");
		return;
	
	}
	$results=array();
	WriteIsamLogs("$myisamchk --safe-recover $MYSQL_DATADIR/$database/$table.MYI");
	exec("$myisamchk --safe-recover $MYSQL_DATADIR/$database/$table.MYI 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		WriteIsamLogs("$line");
	}
	
	
}

function WriteIsamLogs($text){
	$unix=new unix();
	$pid=getmypid();
	$unix->events(date("m-d H:i:s")." [$pid]: $text","/var/log/myisamchk.log");
	
	
}