<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}


include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');

if(isset($_GET["computer-search"])){computers_search();exit;}

section_search();



function section_search(){
	$boot=new boostrap_form();
	echo $boot->SearchFormGen("NAME,IPADDRESS,MACADDR,LASTDATE","computer-search");

}
function computers_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$boot=new boostrap_form();
	$q=new mysql();
	$sock=new sockets();
	$fontsize="14px";
	$page=1;
	if(!$q->DATABASE_EXISTS("ocsweb")){$sock->getFrameWork("services.php?mysql-ocs=yes");}
	if(!$q->TABLE_EXISTS("hardware", "ocsweb")){$sock->getFrameWork("services.php?mysql-ocs=yes");}
	if(!$q->TABLE_EXISTS("networks", "ocsweb",true)){$sock->getFrameWork("services.php?mysql-ocs=yes");}

	

	$EnableScanComputersNet=$sock->GET_INFO("EnableScanComputersNet");
	if(!is_numeric($EnableScanComputersNet)){$EnableScanComputersNet=0;}
	if(!$q->FIELD_EXISTS("networks", "isActive", "ocsweb")){$q->QUERY_SQL("ALTER TABLE `networks` ADD `isActive` SMALLINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `isActive` ) ","ocsweb");}

	$ORDER=$boot->TableOrder(array("NAME"=>"ASC"));

	
	$searchstring=string_to_flexquery("computer-search");
	

	
	$table="(SELECT networks.MACADDR,networks.IPADDRESS,
			hardware.OSNAME,
			hardware.LASTDATE,
			hardware.NAME,
			hardware.IPADDR,
			hardware.IPSRC
			FROM networks,hardware WHERE networks.HARDWARE_ID=hardware.ID) as t";
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql,"ocsweb");
	if(!$q->ok){echo $q->mysql_error."<br>$sql\n";}
	
	$computer=new computers();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["MACADDR"]=="unknown"){continue;}
		$uid=null;
		$OSNAME=null;
		if($ligne["OSNAME"]=="Unknown"){$ligne["OSNAME"]=null;}
		$color="#7D7D7D";
		$md=md5($ligne["MACADDR"]);
		$uri=strtolower($ligne["NAME"]);
		$uid=$computer->ComputerIDFromMAC($ligne["MACADDR"]);
		$view="&nbsp;";
		$jsfiche=MEMBER_JS($uid,1,1);
		$js=null;
		if($uid<>null){
			$js=$boot->trswitch($jsfiche);

		}
		


		if($ligne["OSNAME"]<>null){$OSNAME="<div style='font-size:9px'><i>{$ligne["OSNAME"]}</i></div>";}
		$isActive="img/unknown24.png";

		if($EnableScanComputersNet==1){if($ligne["isActive"]==1){$isActive="img/ok24.png";}else{$isActive="img/danger24.png";}}
		if(!IsPhysicalAddress($ligne["MACADDR"])){if($_GET["CorrectMac"]==1){continue;}}
		$AlreadyMAC[$ligne["MACADDR"]]=true;
		$zdate=null;
		if(isset($ligne["zDate"])){$zdate="<div style='font-size:11px;color:#7D7D7D'>{$ligne["zDate"]}</div>";}
		
		
		$tr[]="
		<tr>
			<td style='font-size:18px' nowrap  width=1% $js>{$ligne["LASTDATE"]}</td>
			<td style='font-size:18px' nowrap $js>{$ligne["NAME"]}</td>
			<td style='font-size:18px' nowrap width=1% $js>{$ligne["IPADDRESS"]}</td>
			<td style='font-size:18px' nowrap width=1% $js>{$ligne["MACADDR"]}</td>
			<td style='font-size:18px' nowrap width=1% $js><img src='$isActive'></td>			
				
		</tr>";


	}

	echo $boot->TableCompile(
			array("LASTDATE"=>"{date}",
					"NAME"=>"{hostname}",
					"IPADDRESS"=>"{ipaddr}",
					"MACADDR"=>"{MAC}"
					
					),
			$tr
	);
	



}