<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");



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
if(isset($_GET["reload-mailboxes-force"])){mailboxes_scan_all();exit;}

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
	$cmd="$php5 /usr/share/artica-postfix/exec.zarafa.build.stores.php --exoprhs --nomail";
	$unix->THREAD_COMMAND_SET($cmd);
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
		if(preg_match("Product version:\s+([0-9]+),#", $re)){$major=$re[1];break;}
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
	$cmd="$nohup $zarafa_admin$socket $langcmd--create-store {$_GET["zarafa-user-create-store"]} >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec(trim($cmd));	
	
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
	$cmd=trim("$nohup /etc/init.d/artica-postfix restart artica-status >/dev/null 2>&1 &");
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


