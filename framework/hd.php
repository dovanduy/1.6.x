<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.hd.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");



if(isset($_GET["uuid-from-dev"])){uuid_from_dev();exit;}
if(isset($_GET["cpu-check-nx"])){check_nx();exit;}
if(isset($_GET["fstab-add"])){fstab_add();exit;}
if(isset($_GET["mountlist"])){mountlist();exit;}


while (list ($num, $ligne) = each ($_GET) ){$a[]="$num=$ligne";}
writelogs_framework("Unable to understand ".@implode("&",$a),__FUNCTION__,__FILE__,__LINE__);



function uuid_from_dev(){
	$unix=new unix();
	$dev=$_GET["uuid-from-dev"];
	$hd=new hd($dev);
	echo "<articadatascgi>".base64_encode($hd->uuid_from_dev())."</articadatascgi>";
}

function check_nx(){
	$unix=new unix();
	$check=$unix->find_program("check-bios-nx");
	if(strlen($check)<5){return;}
	exec("$check --verbose 2>&1",$results);
	echo "<articadatascgi>".base64_encode(@implode("\n",$results))."</articadatascgi>";	
}
function fstab_add(){
	$dev=$_GET["dev"];
	$mount=$_GET["mount"];
	$unix=new unix();
	writelogs_framework("Add Fstab $dev -> $mount ",__FUNCTION__,__FILE__,__LINE__);
	$unix->AddFSTab($dev,$mount);

}
function mountlist(){
	$unix=new unix();
	$mount=$unix->find_program("mount");
	exec("$mount -l 2>&1",$results);
	echo "<articadatascgi>".base64_encode(@implode("\n",$results))."</articadatascgi>";
}

function unlink_disk(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.system.build-partition.php --unlink \"{$_GET["unlink-disk"]}\"";
	writelogs_framework($cmd,__FUNCTION__,__FILE__);
	NOHUP_EXEC($cmd);
}
function NOHUP_EXEC($cmdline){
	$cmdline=str_replace(">/dev/null 2>&1 &", "", $cmdline);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmdfinal="$nohup $cmdline >/dev/null 2>&1 &";
	writelogs_framework("$cmdfinal",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmdfinal);
}