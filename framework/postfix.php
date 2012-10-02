<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");



if(isset($_GET["mastercf"])){master_cf();exit;}
if(isset($_GET["RunSaUpd"])){RunSaUpd();exit;}
if(isset($_GET["postfix-instances-list"])){postfix_instances_list();exit;}
if(isset($_GET["postfix-reconfigure-transport"])){postfix_reconfigure_transport();exit;}


if(isset($_GET["instance-delete"])){postfix_instance_delete();exit;}
if(isset($_GET["postsuper-remove-all"])){postfix_remove_all_queues();exit;}
if(isset($_GET["postfix-debug-peer-list"])){postfix_debug_peer_list();exit;}
if(isset($_GET["EnableStopPostfix"])){EnableStopPostfix();exit;}
if(isset($_GET["smtp-adv-start"])){ExecuteAdvancedRouting();exit;}
if(isset($_GET["multibubble"])){multibubble();exit;}
if(isset($_GET["reconfigure-all-instances"])){multiple_reconfigure_all();exit;}
if(isset($_GET["reconfigure-single-instance"])){multiple_reconfigure_single();exit;}
if(isset($_GET["isp-adv-remount"])){isp_adv_remount();exit;}
if(isset($_GET["query-maillog"])){query_maillog();exit();}
if(isset($_GET["mgreylist-srv"])){milter_greylist_service_debug();exit();}
if(isset($_GET["mgreylist-config"])){milter_greylist_config();exit();}
if(isset($_GET["transactions-order"])){transaction_search_postfixid();exit;}
if(isset($_GET["reconfigure-mailman"])){reconfigure_mailman();exit;}
if(isset($_GET["mailbox-transport"])){mailbox_transport();exit;}
if(isset($_GET["mailbox-transport-maps"])){mailbox_transport_maps();exit;}
if(isset($_GET["milters"])){build_milters();exit;}
if(isset($_GET["restart-mailarchiver"])){restart_mailarchiver();exit;}
if(isset($_GET["mailarchiver-status"])){mailarchiver_status();exit;}


if(isset($_GET["happroxy"])){happroxy();exit;}

while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);


function master_cf(){
	$servername=$_GET["instance"];
	if($servername=="master"){$path="/etc/postfix/master.cf";}else{$path="/etc/postfix-$servername/master.cf";}
	echo "<articadatascgi>". base64_encode(@file_get_contents($path))."</articadatascgi>";	
	
}

function postfix_reconfigure_transport(){
	$hostname=$_GET["hostname"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
		
	if($hostname=="master"){
		$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.hashtables.php --transport --reload >/dev/null 2>&1 &";
	}else{
		$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"$hostname\" >/dev/null 2>&1 &";
	}
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}

function ExecuteAdvancedRouting(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.smtp-senderadv.php >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}
function isp_adv_remount(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.smtp-senderadv.php --remount >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function restart_mailarchiver(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup /etc/init.d/artica-postfix restart mailarchiver >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}


function multibubble(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.multi.bubble.php >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function multiple_reconfigure_all(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.multi.bubble.php --reconfigure-all >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
}
function multiple_reconfigure_single(){
	$hostname=$_GET["hostname"];
	if(trim($hostname)==null){return;}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"$hostname\" >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}
function build_milters(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --milters >/dev/null 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);	
	$cmd=trim("$nohup /etc/init.d/artica-postfix restart artica-status >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);		
		
}
function mailarchiver_status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --mailarchiver --nowachdog";
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
}

function happroxy(){
	$hostname=$_GET["hostname"];
	if(trim($hostname)==null){return;}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	if($hostname=="master"){
		$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --loadbalance >/dev/null 2>&1 &";	
		shell_exec($cmd);
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
		return;		
	}
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"$hostname\" >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}




function RunSaUpd(){
	$statusFileContent="ressources/logs/sa-update-status.txt";
	@file_put_contents($statusFileContent, "{scheduled}\n");
	shell_exec("/bin/chmod 777 $statusFileContent");
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.spamassassin.php --sa-update >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function postfix_debug_peer_list(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();	

	if($_GET["hostname"]<>null){
		if($_GET["hostname"]=="master"){
			$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix.maincf.php --debug-peer-list >/dev/null 2>&1 &";
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmd);
		}else{
			$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"{$_GET["hostname"]}\" >/dev/null 2>&1 &";
			shell_exec($cmd);
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		}
		return;
	}
	$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix.maincf.php --debug-peer-list >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function postfix_instance_delete(){
	$unix=new unix();
	$postmulti=$unix->find_program("postmulti");
	$instance="postfix-{$_GET["instance-delete"]}";
	$cmd="$postmulti -i $instance -p stop";
	$results=array();
	exec($cmd,$results);
	writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	$cmd="$postmulti -i $instance -e disable";
	$results=array();
	exec($cmd,$results);
	writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);	
	$rm=$unix->find_program("rm");
	$directory="/var/spool/$instance";
	if(is_dir($directory)){
		$cmd="$rm -rf $directory";
		$results=array();
		exec($cmd,$results);
		writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);			
	}
	
}

function postfix_instances_list(){
	$unix=new unix();
	$search=trim(strtolower($_GET["search"]));
	if(strlen($search)>0){
		$grep=$unix->find_program("grep");
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*?", $search);
		$searcchmd="|$grep -E '$search.*?\s+'";
	}
	
	$postmulti=$unix->find_program("postmulti");
	$cmd="$postmulti -l$searcchmd 2>&1";
	exec($cmd,$results);
	writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function postfix_remove_all_queues(){
	$hostname=trim($_GET["hostname"]);
	if($hostname==null){$hostname="master";}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$postsuper=$unix->find_program("postsuper");
	$conf="/etc/postfix";
	if($hostname<>"master"){$conf=" -c /etc/postfix-$hostname";}
	$cmd=trim("$postsuper$conf -d ALL >/dev/null 2>&1");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.watchdog.postfix.queue.php >/dev/null 2>&1 &");
	
}

function query_maillog(){
	$unix=new unix();
	$maillog=$_GET["maillog"];
	if($maillog==null){echo "<articadatascgi>". base64_encode(serialize(array()))."</articadatascgi>";return;}
	$maillogSecond=$maillog;
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$search=trim(base64_decode($_GET["filter"]));
	
	$emails=unserialize(base64_decode($_GET["emails"]));
	
	if(count($emails)>0){
		$searchEmails="(".@implode("|", $emails).")";
		$searchEmails=str_replace(".", "\.", $searchEmails);
		$searchEmails=".*?$searchEmails";
	}
	
	if(isset($_GET["zarafa-filter"])){
		if($_GET["zarafa-filter"]=="yes"){
			$_GET["prefix"]="\s+zarafa\-(spooler|server|gateway|dagent|license)$searchEmails";
		}
	}
	
	if(isset($_GET["mimedefang-filter"])){
		if($_GET["mimedefang-filter"]=="yes"){
			$_GET["prefix"]="\s+mimedefang(\-multiplexor|\[)$searchEmails";
		}
	}	
	
	if(isset($_GET["prefix"])){
		$prefix="$grep -i -E '{$_GET["prefix"]}\[$searchEmails' $maillog|";
		$maillogSecond=null;
	}
	

	
	
	$max=500;
	if(isset($_GET["rp"])){$max=$_GET["rp"];}
	
	if($search<>null){
			$search=str_replace(".","\.",$search);
			$search=str_replace("*",".*?",$search);
			$search=str_replace("(","\(",$search);
			$search=str_replace(")","\)",$search);
			$search=str_replace("[","\[",$search);
			$search=str_replace("]","\]",$search);	
			if($searchEmails<>null){
				$cmd="$prefix$grep -i -E '$searchEmails' $maillog|$grep -E '$search'|$tail -n $max 2>&1";
			}else{
				$cmd="$prefix$grep -i -E '$search' $maillogSecond|$tail -n $max 2>&1";
			}
		
	}else{
		if($prefix<>null){
			$cmd="$prefix$tail -n $max 2>&1";
		}else{
			if($searchEmails<>null){
				$cmd="$grep -i -E '$searchEmails' $maillog|$tail -n $max 2>&1";
			}else{
				$cmd="$tail -n $max $maillog 2>&1";
			}
			
		}
	}
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	
	
}

function milter_greylist_service_debug(){
	if(isset($_GET["hostname"])){
		if($_GET["hostname"]<>"master"){
			$cmdp=" --hostname={$_GET["hostname"]} --ou=\"{$_GET["ou"]}\"";
		}
	}
	
	$what=$_GET["what"];
	if($what=="stop"){$what=" --stop";}
	if($what=="start"){$what=" --start";}
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.php$what$cmdp --who=WebInterface >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function milter_greylist_config(){
	if(isset($_GET["hostname"])){
		if($_GET["hostname"]<>"master"){
			writelogs_framework("[{$_GET["hostname"]}] -> \"/etc/milter-greylist/{$_GET["hostname"]}/greylist.conf\"",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>". base64_encode(serialize(file("/etc/milter-greylist/{$_GET["hostname"]}/greylist.conf")))."</articadatascgi>";
			return;
		}
	}
	
	writelogs_framework("[{$_GET["hostname"]}] -> \"/etc/milter-greylist/greylist.conf\"",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize(file("/etc/milter-greylist/greylist.conf")))."</articadatascgi>";
	
}


function EnableStopPostfix(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$value=$_GET["value"];
	if($value==1){
		$cmd=trim("$nohup /etc/init.d/artica-postfix stop postfix >/dev/null &");
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
		
		$cmd=trim("$nohup /etc/init.d/artica-postfix stop amavis >/dev/null &");
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);	

		$cmd=trim("$nohup /etc/init.d/artica-postfix stop mgreylist >/dev/null &");
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);		
		
	}else{
		$cmd=trim("$nohup /etc/init.d/artica-postfix start postfix >/dev/null &");
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);	

		$cmd=trim("$nohup /etc/init.d/artica-postfix start amavis >/dev/null &");
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);

		$cmd=trim("$nohup /etc/init.d/artica-postfix start mgreylist >/dev/null &");
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);			
		
	}
	$cmd=trim("$nohup /etc/init.d/artica-postfix restart artica-status >/dev/null &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function transaction_search_postfixid(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();		
	$msgid=$_GET["transactions-order"];
	$php5=$unix->LOCATE_PHP5_BIN();
	$id=$_GET["id"];
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.postfix.finder.php --transaction-find $msgid $id >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
}
function reconfigure_mailman(){
	$hostname=$_GET["hostname"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.hashtables.php --mailman >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	
}

function mailbox_transport(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();	
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}

	if($_GET["hostname"]=="master"){
			$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix.maincf.php --imap-sockets >/dev/null 2>&1 &";
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmd);
			return;
		}else{
			$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"{$_GET["hostname"]}\" >/dev/null 2>&1 &";
			shell_exec($cmd);
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
			return;
		}
}
function mailbox_transport_maps(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();	
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}

	if($_GET["hostname"]=="master"){
			$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix.hashtables.php --mailbox-transport-maps >/dev/null 2>&1 &";
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmd);
			return;
		}else{
			$cmd="$nohup $php /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"{$_GET["hostname"]}\" >/dev/null 2>&1 &";
			shell_exec($cmd);
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
			return;
		}	
}

die();