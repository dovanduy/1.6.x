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
writelogs_framework("unable to unserstand ".@implode("&",$a),__FUNCTION__,__FILE__,__LINE__);



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
	exec("/usr/bin/check-bios-nx --verbose 2>&1",$results);
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