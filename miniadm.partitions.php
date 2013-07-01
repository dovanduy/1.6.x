<?php
session_start();
$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");


if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){
	writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);
	header("location:miniadm.logon.php");}
BuildSessionAuth();
if($_SESSION["uid"]=="-100"){
	writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);
	header("location:admin.index.php");
	die();
	}
	
main_page();


function main_page(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$SYSTEMS_ALL_PARTITIONS=unserialize(base64_decode($sock->getFrameWork("system.php?SYSTEMS_ALL_PARTITIONS=yes")));
	while (list ($partition, $array) = each ($SYSTEMS_ALL_PARTITIONS) ){
		$SIZE=$array["SIZE"];
		$USED=$array["USED"];
		$AIVA=$array["AIVA"];
		$POURC=$array["POURC"];
		$MOUNTED=$array["MOUNTED"];
		$ico="64-hd.png";
		if($partition=="tmpfs"){
			$ico="bg_memory-64.png";
			
		}
		$purc_inodes=null;
		$purc=null;
		$INODES_POURC=$array["VALUES"]["INODES_POURC"];
		//print_r($array["VALUES"]);
		
		$color="#5DD13D";
		if($INODES_POURC>40){$color="#F59C44";}
		if($INODES_POURC>90){$color="#D32D2D";}
		
		if($INODES_POURC>0){
			$purc_inodes=pourcentage_basic($INODES_POURC, $color, "{$INODES_POURC}%");
		}
		
		
		$color="#5DD13D";
		if($POURC>40){$color="#F59C44";}
		if($POURC>90){$color="#D32D2D";}
		
		$purc=pourcentage_basic($POURC, $color, "$USED/$SIZE ($POURC%)");
		
		$tr[]="
		<tr>
		<td width=1% nowrap><img src='img/$ico'></td>
		<td style='font-size:18px' width=1% nowrap>$partition ($SIZE)</td>
		<td style='font-size:18px'>$MOUNTED</td>
		<td style='font-size:18px' width=1% nowrap>$purc_inodes</td>
		<td style='font-size:18px' width=1% nowrap>$AIVA<div style='font-size:10px;font-weight:bold'>$USED ($POURC%)</div></td>
		<td style='font-size:18px' width=1% nowrap>$purc</td>
		</tr>
		";
		
	}
	
	echo $tpl->_ENGINE_parse_body("
	
			<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th colspan=2>{partition}</th>
					<th >{mounted}</th>
					<th >Inodes</th>
					<th >{available}</th>
					<th >{use}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";	
	
}
