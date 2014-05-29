<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");

if(isset($_GET["db-trash"])){db_trash();exit;}
if(isset($_GET["build-init"])){build_init();exit;}
if(isset($_GET["install-zpush"])){zpush_install();exit;}
if(isset($_GET["restore-process-array"])){restore_process_array();exit;}
if(isset($_GET["webapp-version"])){webapp_version();exit;}
if(isset($_GET["webaccess-version"])){webaccess_version();exit;}
if(isset($_GET["audit-log"])){audit_log();exit;}
if(isset($_GET["locales"])){locales();exit;}
if(isset($_GET["foldersnames"])){foldersnames();exit;}
if(isset($_GET["zarafa-user-create-store"])){zarafa_user_create_store();exit;}
if(isset($_GET["DbAttachConverter"])){DbAttachConverter();exit;}
if(isset($_GET["mbx-infos"])){mbx_infos();exit;}
if(isset($_GET["csv-export"])){csv_export();exit;}
if(isset($_GET["removeidb"])){removeidb();exit;}
if(isset($_GET["zarafa-orphan-kill"])){orphan_delete();exit();}
if(isset($_GET["zarafa-orphan-link"])){orphan_link();exit();}
if(isset($_GET["zarafa-orphan-scan"])){orphan_scan();exit();}
if(isset($_GET["getversion"])){getversion();exit();}
if(isset($_GET["restart"])){restart();exit();}
if(isset($_GET["status"])){status();exit();}
if(isset($_GET["CopyToPublic"])){CopyToPublic();exit();}
if(isset($_GET["unhook-store"])){unhook_store();exit();}
if(isset($_GET["softdelete"])){softdelete();exit();}
if(isset($_GET["multi-restart"])){multi_restart();exit();}
if(isset($_GET["multi-service"])){multi_service();exit;}
if(isset($_GET["delete-instance"])){multi_delete();exit;}
if(isset($_GET["restart-ical"])){restart_ical();exit;}
if(isset($_GET["mailboxes-ou-lang"])){mailboxes_scan_ou();exit;}
if(isset($_GET["relinkto"])){relinkto();exit;}
if(isset($_GET["restart-dagent"])){restart_dagent();exit;}
if(isset($_GET["restart-search"])){restart_search();exit;}
if(isset($_GET["restart-server"])){restart_zarafaserver();exit;}
if(isset($_GET["restart-gateway"])){restart_zarafagateway();exit;}
if(isset($_GET["run-backup"])){run_backup();exit;}
if(isset($_GET["backup-scan-dirs"])){run_backup_scandirs();exit;}
if(isset($_GET["backup-remove-dirs"])){run_backup_remove_dirs();exit;}
if(isset($_GET["reload-mailboxes-force"])){mailboxes_scan_all();exit;}
if(isset($_GET["recover-last"])){recover_last();exit;}
if(isset($_GET["import-contacts"])){import_csv_contacts();exit;}
if(isset($_GET["ChangeMysqlDir-zarafa"])){ChangeMysqlDir_zarafa();exit;}
if(isset($_GET["ChangeMysqlDir-articadb"])){ChangeMysqlDir_zarafadb();exit;}
if(isset($_GET["zarafadb-restore"])){zarafadb_restore();exit;}
if(isset($_GET["zarafadb-processlist"])){zarafadb_processlist();exit;}
if(isset($_GET["artica-dbsize"])){zarafadb_getsize();exit;}
if(isset($_GET["zarafadb-killthread"])){zarafadb_killthread();exit;}
if(isset($_GET["users-count"])){zarafa_admin_userscount();exit;}
if(isset($_GET["reload"])){zarafa_reload();exit;}
if(isset($_GET["zarafa-stats-system"])){zarafa_stats_system();exit;}
if(isset($_GET["zarafadb-restart"])){zarafadb_restart();exit;}
if(isset($_GET["zpush-version"])){zpush_version();exit;}
if(isset($_GET["uncompress-webaccess"])){uncompress_webaccess();exit;}
if(isset($_GET["uncompress-webapp"])){uncompress_webapp();exit;}

if(isset($_GET["spooler-restart"])){spooler_restart();exit;}





while (list ($num, $ligne) = each ($_GET) ){$a[]="$num=$ligne";}
writelogs_framework("unable to unserstand ".@implode("&",$a),__FUNCTION__,__FILE__,__LINE__);

function orphan_link(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$zarafa_admin=$unix->find_program("zarafa-admin");
	$cmd="$zarafa_admin --hook-store {$_GET["zarafa-orphan-link"]} -u {$_GET["uid"]}";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.zarafa.build.stores.php --exoprhs --nomail >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function multi_restart(){
	$id=$_GET["instance-id"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.zarafa-multi.php --restart $id >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
	
}
function multi_service(){
	$id=$_GET["instance-id"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.zarafa-multi.php --{$_GET["action"]} $id 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function multi_delete(){
	$id=$_GET["instance-id"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.zarafa-multi.php --delete $id 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function run_backup(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.zarafa-backup.php --exec 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
}

function run_backup_scandirs(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.zarafa-backup.php --dirs --verbose 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function run_backup_remove_dirs(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.zarafa-backup.php --remove-dirs --verbose 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}

function softdelete(){
	$softdelete=$_GET["softdelete"];
	if(!is_numeric($softdelete)){$softdelete=0;}
	if($softdelete<1){return;}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$zarafa_admin=$unix->find_program("zarafa-admin");	
	$cmd="$nohup $zarafa_admin --purge-softdelete $softdelete >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function unhook_store(){
	$unix=new unix();
	$uid=trim($_GET["unhook-store"]);
	$nohup=$unix->find_program("nohup");
	$zarafa_admin=$unix->find_program("zarafa-admin");
	$cmd="$zarafa_admin --unhook-store \"{$_GET["unhook-store"]}\"";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
	if($_GET["ZarafaCopyToPublicAfter"]<>null){
		exec("$zarafa_admin --list-orphan 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#\s+(.+?)\s+$uid\s+[0-9]+#i", $ligne,$re)){$storeUnhooked=$re[1];break;}
		}
		writelogs_framework("storeUnhooked = $storeUnhooked -> {$_GET["ZarafaCopyToPublicAfter"]}",__FUNCTION__,__FILE__,__LINE__);
		if($storeUnhooked<>null){
			$cmd="$zarafa_admin --hook-store $storeUnhooked -u \"{$_GET["ZarafaCopyToPublicAfter"]}\" --copyto-public 2>&1";
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
			exec($cmd,$results);
			writelogs_framework(@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__);
		}
	}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.zarafa.build.stores.php --exoprhs --nomail";
	$unix->THREAD_COMMAND_SET($cmd);	
}

function orphan_scan(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.zarafa.build.stores.php --exoprhs --nomail >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function locales(){
	$unix=new unix();
	$locale=$unix->find_program("locale");
	exec("$locale -a 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function foldersnames(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$zarafa=$unix->find_program("zarafa-server");
	exec("$zarafa -V 2>&1",$results);
	$major=6;
	$instance_id=$_GET["instance-id"];
	$socket=null;
	if(!is_numeric($instance_id)){$instance_id=0;}
	if($instance_id>0){$socket=" --socket=$instance_id";}	
	if(trim($_GET["lang"])==null){return;}
	
	while (list ($num, $ligne) = each ($results) ){
		
		if(preg_match("#Product version:\s+([0-9]+),#", $ligne,$re)){$major=$re[1];break;}
	}
	
	writelogs_framework("zarafa-server version $major.x",__FUNCTION__,__FILE__,__LINE__);
	
	if($major==6){
		writelogs_framework("-> exec.zarafa6.foldersnames.php",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$php5 /usr/share/artica-postfix/exec.zarafa6.foldersnames.php {$_GET["uid"]} {$_GET["lang"]}$socket");
	}
	if($major==7){
		writelogs_framework("-> exec.zarafa7.foldersnames.php",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$php5 /usr/share/artica-postfix/exec.zarafa7.foldersnames.php {$_GET["uid"]} {$_GET["lang"]}$socket");
	}	
	
	
}
function zarafa_user_create_store(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	if(strlen($nohup)>3){$nohup="$nohup ";}
	$zarafa_admin=$unix->find_program("zarafa-admin");
	$instance_id=$_GET["instance-id"];
	$socket=null;
	if(!is_numeric($instance_id)){$instance_id=0;}
	if($instance_id>0){$socket=" -h file:///var/run/zarafa-$instance_id";}
	
	
	$langcmd=null;
	if(trim($_GET["lang"])<>null){$langcmd=" --lang {$_GET["lang"]} ";}
	
	$cmd="$nohup $zarafa_admin$socket --create-store {$_GET["zarafa-user-create-store"]}$langcmd >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	
}

function DbAttachConverter(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$perl=$unix->find_program("perl");
	$sqladm=base64_decode($_GET["sqladm"]);
	$sqlpass=base64_decode($_GET["mysqlpass"]);
	$sqlpass=$unix->shellEscapeChars($sqlpass);
	$path=$_GET["path"];
	if(!is_dir($path)){@mkdir($path,644,true);}
	$cmd="$nohup $perl /usr/share/doc/zarafa/db-convert-attachments-to-files $sqladm $sqlpass zarafa $path delete >/dev/null 2>&1 &";	
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function mbx_infos(){
	$unix=new unix();
	$zarafa_admin=$unix->find_program("zarafa-admin");
	$instance_id=$_GET["instance-id"];
	$socket=null;
	if(!is_numeric($instance_id)){$instance_id=0;}
	if($instance_id>0){$socket=" -h file:///var/run/zarafa-$instance_id";}	
	
	$cmd="$zarafa_admin$socket --details {$_GET["mbx-infos"]} 2>&1";
	exec($cmd,$results);
	
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function orphan_delete(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$zarafa_admin=$unix->find_program("zarafa-admin");
	$cmd="$zarafa_admin --remove-store {$_GET["zarafa-orphan-kill"]}";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.zarafa.build.stores.php --exoprhs --nomail";
	$unix->THREAD_COMMAND_SET($cmd);
}

function csv_export(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");	
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.zarafa.contacts-zarafa.php --export-zarafa {$_GET["uid"]} >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --zarafa --nowachdog";
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
	
}

function restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");	
	$cmd=trim("$nohup /etc/init.d/artica-postfix restart zarafa >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}
function restart_ical(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");	
	$cmd=trim("$nohup /etc/init.d/artica-postfix restart zarafa-ical >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	$cmd=trim("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}
function mailboxes_scan_ou(){
	$unix=new unix();
	$ou=$_GET["ou"];
	$nohup=$unix->find_program("nohup");	
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.zarafa-migrate.php --mailboxes-ou-lang $ou >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function mailboxes_scan_all(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.zarafa.build.stores.php --users-status --force >/dev/null 2>&1");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}
function recover_last(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.mysql.start.php --recover >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}


function CopyToPublic(){
	$unix=new unix();
	$array=unserialize(base64_decode($_GET["CopyToPublic"]));
	$zarafa_admin=$unix->find_program("zarafa-admin");
	$cmd="$zarafa_admin --hook-store {$array["storeid"]} -u {$array["uid"]} --copyto-public 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	//$results[]=$cmd;
	exec($cmd,$results);
	echo "<articadatascgi>
	". base64_encode(@implode("\n",$results))."</articadatascgi>";
	
}
function relinkto(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$EXECRELINK=true;
	$from=$_GET["from"];
	$to=$_GET["to"];
	shell_exec("$php /usr/share/artica-postfix/exec.zarafa.build.stores.php --relink-to \"$from\" \"$to\"");
}

function removeidb(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");	
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.zarafa.build.stores.php --remove-database >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function getversion(){
	$unix=new unix();
	$zarafa_server=$unix->find_program("zarafa-server");
	if(strlen($zarafa_server)<5){return null;}
	exec("$zarafa_server -V 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#Product version:\s+([0-9,]+)#", $ligne,$re)){
			$version=trim($re[1]);
			$version=str_replace(",", ".", $version);
			echo "<articadatascgi>$version</articadatascgi>";
			return;
		}
	}
}

function restart_dagent(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.initdzarafa.php");
	shell_exec("/usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure");	
	$cmd=trim("$nohup /etc/init.d/zarafa-dagent start >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
	
}
function restart_zarafaserver(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.initdzarafa.php");
	shell_exec("/usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure");	
	shell_exec("$nohup /etc/init.d/artica-postfix restart zarafa-server >/dev/null 2>&1 &");	
}
function restart_zarafagateway(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.initdzarafa.php");
	shell_exec("/usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure");	
	shell_exec("$nohup /etc/init.d/artica-postfix restart zarafa-gateway >/dev/null 2>&1 &");	
}




function restart_search(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.initdzarafa.php");
	shell_exec("/usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure");
	shell_exec("/etc/init.d/zarafa-search restart >/dev/null 2>&1");
	shell_exec("$nohup /etc/init.d/artica-postfix restart zarafa-server >/dev/null 2>&1 &");	
}
function import_csv_contacts(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$uid=base64_decode($_GET["uid"]);
	$filename=base64_decode($_GET["filename"]);
	if(!is_file($filename)){echo "<articadatascgi>".basename($filename)." No such file</articadatascgi>";return;}
	$cmd="$nohup $php /usr/share/artica-postfix/exec.csv2contacts.php \"$uid\" \"$filename\" >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	echo "<articadatascgi><div style='font-size:16px'><strong>{success}:".basename($filename)."</strong> {has_been_sent_to_import_task}</div></articadatascgi>";
}
function audit_log(){
	$unix=new unix();
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$search=trim(base64_decode($_GET["filter"]));
	$maillog="/var/log/auth.log";
	$prefix="$grep -i -E '\s+zarafa\-(spooler|server|gateway|dagent|license)\[' $maillog|";
	$max=500;
	if(isset($_GET["rp"])){$max=$_GET["rp"];}
	
	if($search<>null){
		$search=$unix->StringToGrep($search);
		$cmd="$prefix$grep -i -E '$search' |$tail -n $max 2>&1";
	
	}else{
		$cmd="$prefix$tail -n $max 2>&1";
	}
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}
function ChangeMysqlDir_zarafa(){
	$default="/home/zarafa-db";
	if(is_link($default)){
		$default=trim(@readlink($default));
		writelogs_framework("/home/zarafa-db -> `$default`",__FUNCTION__,__FILE__,__LINE__);
		echo  "<articadatascgi>". trim(base64_encode($default))."</articadatascgi>";
		return;
	}
	
	echo  "<articadatascgi>". base64_encode($default)."</articadatascgi>";
	
	
}

function ChangeMysqlDir_zarafadb(){
	$unix=new unix();
	$dir=base64_decode($_GET["dir"]);
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$dir=$unix->shellEscapeChars($dir);
	$cmd="$nohup $php /usr/share/artica-postfix/exec.zarafa-db.php --changemysqldir $dir >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function zarafadb_restore(){
	$unix=new unix();
	$logfile="/usr/share/artica-postfix/ressources/logs/web/zarafa_restore_task.log";
	$dir=base64_decode($_GET["zarafadb-restore"]);
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$dir=$unix->shellEscapeChars($dir);
	
	$oldpid=$unix->PIDOF_PATTERN("exec.zarafa-db.php --restorefrom");
	if($unix->process_exists($oldpid)){
		return;
	}
	
	@unlink($logfile);
	@file_put_contents($logfile, "Please, wait, task will running...\n");
	@chmod("$logfile",0775);
	$cmd="$nohup $php /usr/share/artica-postfix/exec.zarafa-db.php --restorefrom $dir >>$logfile 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function zarafadb_getsize(){
	$unix=new unix();
	$dir=base64_decode($_GET["dir"]);
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.zarafa-db.php --databasesize --force 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function zarafadb_processlist(){
	$unix=new unix();
	$mysqladmin=$unix->find_program("mysqladmin");
	$cmd="$mysqladmin --socket /var/run/mysqld/zarafa-db.sock -u root processlist 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	
	while (list ($num, $ligne) = each ($results) ){
		if(!preg_match("#^\|\s+([0-9]+)\s+\|(.*?)\|(.*?)\|(.*?)\|(.*?)\|(.*?)\|(.*?)\|(.*?)\|#", $ligne,$re)){continue;}
		if(preg_match("#show processlist#", $re[8])){continue;}
		$array[$re[1]]=array("USER"=>trim($re[2]),"HOST"=>trim($re[3]),"DB"=>trim($re[4]),"COMMAND"=>trim($re[5]),"TIME"=>trim($re[6]),"STATE"=>trim($re[7]),"INFO"=>trim($re[8]),);

	}
	echo  "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}
function zarafadb_killthread(){
	$pid=$_GET["zarafadb-killthread"];
	if(!is_numeric($pid)){return;}
	$unix=new unix();
	$mysqladmin=$unix->find_program("mysqladmin");
	$cmd="$mysqladmin --socket /var/run/mysqld/zarafa-db.sock -u root kill $pid 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
}

function zarafa_admin_userscount(){
	$unix=new unix();
	$zarafa_admin=$unix->find_program("zarafa-admin");
	$cmd="$zarafa_admin --user-count 2>&1";
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#is not running#", $ligne)){
			$array["STATUS"]=false;
			$array["ERROR"]=$ligne;
			break;
		}
		
		
		if(preg_match("#\s+Active\s+([0-9a-z\s]+)\s+([0-9a-z\s]+)\s+([0-9a-z\s]+)#i",$ligne,$re)){
			$array["ACTIVE"]["ALLOWED"]=$re[1];
			$array["ACTIVE"]["USED"]=$re[2];
			$array["ACTIVE"]["AVAILABLE"]=$re[3];
			continue;
		}
		
		
		if(preg_match("#Total\s+([0-9]+)#", $ligne,$re)){
			$array["STATUS"]=true;
			$array["TOTAL"]=$re[1];
			break;
		}
		
	}
	echo  "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}

function zarafa_reload(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$php5 /usr/share/artica-postfix/exec.initdzarafa.php");
	shell_exec("$nohup /etc/init.d/zarafa-server reload >/dev/null 2>&1 &");
	
}

function zarafa_stats_system(){
	$unix=new unix();
	$zarafa_stats=$unix->find_program("zarafa-stats");
	exec("zarafa-stats --system 2>&1",$results);
	echo  "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function zarafadb_restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup /usr/share/artica-postfix/exec.zarafa-db.php --restart >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function zpush_version(){
	$tr=explode("\n",@file_get_contents("/usr/share/z-push/version.php"));
	while (list ($num, $ligne) = each ($tr) ){
		if(preg_match("#ZPUSH_VERSION.*?([0-9\.\-]+)#", $ligne,$re)){
			echo  "<articadatascgi>". base64_encode($re[1])."</articadatascgi>";
			return;
		}
	}
	echo  "<articadatascgi>". base64_encode("0.00")."</articadatascgi>";
}


function zarafa_hash(){
	if(isset($_GET["rebuild"])){@unlink("/etc/artica-postfix/zarafa-export.db");}
	if(!is_file("/etc/artica-postfix/zarafa-export.db")){
		$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.zarafa.build.stores.php --export-hash";
		shell_exec($cmd);
	}
	echo "<articadatascgi>". @file_get_contents("/etc/artica-postfix/zarafa-export.db")."</articadatascgi>";
}
function webaccess_version(){
	echo  "<articadatascgi>". base64_encode(@file_get_contents("/usr/share/zarafa-webaccess/VERSION"))."</articadatascgi>";
}
function webapp_version(){
	echo  "<articadatascgi>". base64_encode(@file_get_contents("/usr/share/zarafa-webapp/version"))."</articadatascgi>";
}

function uncompress_webaccess(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$tar=$unix->find_program("tar");
	$filename=$_GET["uncompress-webaccess"];
	$nohup=$unix->find_program("nohup");
	$FilePath="/usr/share/artica-postfix/ressources/conf/upload/$filename";

	if(!is_file($FilePath)){
		echo "<articadatascgi>".base64_encode(serialize(array("R"=>false,"T"=>"{failed}: $FilePath no such file")))."</articadatascgi>";
		return;
	}
	@mkdir("/usr/share/zarafa-webaccess",0755,true);
	$cmd="$tar -xf $FilePath -C /usr/share/zarafa-webaccess/";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	$VERSION=@file_get_contents("/usr/share/zarafa-webaccess/VERSION");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.freeweb.php --reconfigure-webaccess >/dev/null 2>&1 &");
	echo "<articadatascgi>".base64_encode(serialize(array("R"=>true,"T"=>"{success}: v.$VERSION")))."</articadatascgi>";
}
function uncompress_webapp(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$tar=$unix->find_program("tar");
	$filename=$_GET["uncompress-webapp"];
	$nohup=$unix->find_program("nohup");	
	$FilePath="/usr/share/artica-postfix/ressources/conf/upload/$filename";
	if(!is_file($FilePath)){
		echo "<articadatascgi>".base64_encode(serialize(array("R"=>false,"T"=>"{failed}: $FilePath no such file")))."</articadatascgi>";
		return;
	}
	
	@mkdir("/usr/share/zarafa-webapp",0755,true);
	$cmd="$tar -xf $FilePath -C /";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	$VERSION=@file_get_contents("/usr/share/zarafa-webapp/version");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.freeweb.php --reconfigure-webapp >/dev/null 2>&1 &");
	echo "<articadatascgi>".base64_encode(serialize(array("R"=>true,"T"=>"{success}: v.$VERSION")))."</articadatascgi>";
	
}

function restore_process_array(){
	$unix=new unix();
	$pid=$unix->PIDOF_PATTERN("exec.zarafa-db.php --restorefrom");
	if($unix->process_exists($pid)){
		
		$WORKDIR=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/ZarafaDedicateMySQLWorkDir"));
		if($WORKDIR==null){$WORKDIR="/home/zarafa-db";}
		
		$time=$unix->PROCCESS_TIME_MIN($pid);
		$ARRAY["PID"]=$pid;
		$ARRAY["TIME"]=$time;
		$ARRAY["SIZE"]=$unix->DIRSIZE_KO("$WORKDIR/data/zarafa");
		echo "<articadatascgi>".base64_encode(serialize($ARRAY))."</articadatascgi>";
	}
	
	
}



function spooler_restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	shell_exec("/usr/share/artica-postfix/bin/artica-install --zarafa-reconfigure");
	shell_exec("$nohup /etc/init.d/zarafa-spooler restart >/dev/null 2>&1 &");
}

function zpush_install(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");	
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php5 /usr/share/artica-postfix/compile-zpush.php >/usr/share/artica-postfix/ressources/logs/web/zpush-install.log 2>&1 &");
	
}

function build_init(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.initdzarafa.php --lang >/dev/null 2>&1 &");
}

function db_trash(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	@unlink("/usr/share/artica-postfix/ressources/logs/web/zarafatrash_reconfigure.txt");
	@touch("/usr/share/artica-postfix/ressources/logs/web/zarafatrash_reconfigure.txt");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/zarafatrash_reconfigure.txt", 0777);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.zarafa-db.php --trash >/usr/share/artica-postfix/ressources/logs/web/zarafatrash_reconfigure.txt 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}



