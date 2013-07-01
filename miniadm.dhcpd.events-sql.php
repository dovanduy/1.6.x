<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
if(!$_SESSION["ASDCHPAdmin"]){header("location:miniadm.index.php");die();}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
if(isset($_GET["search-records"])){list_nets();exit;}

page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$boot=new boostrap_form();
	$rescan=$tpl->_ENGINE_parse_body("{rescan}");
	$button=button($rescan,"rescan$t()",16);
	//$OPTIONS["BUTTONS"][]=$button;
	
	
	$SearchQuery=$boot->SearchFormGen("description","search-records",null,array());	
	echo $SearchQuery;
	
}
function list_nets(){


	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];

	$search='%';
	$table="dhcpd_logs";
	$database='artica_events';
	$page=1;
	$FORCE_FILTER="";
	$ORDER="ORDER BY zDate DESC";

	if(!$q->TABLE_EXISTS($table, $database)){ throw new Exception("$table, No such table...",500);}
	if($q->COUNT_ROWS($table,$database)==0){throw new Exception("No data...",500);}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery("search-records");

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER limit 0,250";
	
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){senderror($q->mysql_error,1);}
	
	if(mysql_num_rows($results)==0){
		senderror("no data");
	}
	$sock=new sockets();
	$cmp=new computers();
	$boot=new boostrap_form();
	$computers=new computers();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$uid=null;
		$mac=null;
		if(preg_match("#to\s+([0-9a-z:]+)\s+via#",$ligne["description"],$re)){$mac=$re[1];}
		if(preg_match("#from\s+([0-9a-z:]+)\s+via#",$ligne["description"],$re)){$mac=$re[1];}
		$js="zBlur();";
		if($mac<>null){
			$uid=$computers->ComputerIDFromMAC($mac);
			if($uid<>null){
				$js=MEMBER_JS($uid,1,1);
				$ligne["description"]=str_replace($mac,"<strong><i class='icon-info-sign'></i>&nbsp;$mac</strong></strong>",$ligne["description"]);
			}
		}
		

		$link=$boot->trswitch($js);
		$tr[]="
		<tr id='$id'>
		<td nowrap $link><i class='icon-time'></i>&nbsp;{$ligne["zDate"]}</td>
		<td $link ><i class='icon-info-sign'></i>&nbsp;{$ligne["description"]}</td>
		</tr>";		


	}
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{zDate}</th>
					<th>{events}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
				</table>
				";

}