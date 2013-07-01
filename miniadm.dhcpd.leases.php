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
	$t=time();
	$rescan=$tpl->_ENGINE_parse_body("{rescan}");
	$button=button($rescan,"rescan$t()",16);
	$OPTIONS["BUTTONS"][]=$button;
	
	
	$SearchQuery=$boot->SearchFormGen("hostname,ipaddr,mac","search-records",null,$OPTIONS);	
	echo $SearchQuery."
			<script>
				function rescan$t(){
					Loadjs('dhcpd.leases.php?action-rescan=yes');
				}
			</script>
			
			";
	
}
function list_nets(){


	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];

	$search='%';
	$table="dhcpd_leases";
	$database='artica_backup';
	$page=1;
	$FORCE_FILTER="";
	$ORDER="ORDER BY hostname";

	if(!$q->TABLE_EXISTS($table, $database)){ throw new Exception("$table, No such table...",500);}
	if($q->COUNT_ROWS($table,'artica_backup')==0){throw new Exception("No data...",500);}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery("search-records");

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	
	$results = $q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){senderror($q->mysql_error);}
	
	if(mysql_num_rows($results)==0){
		senderror("no data");
	}
	$sock=new sockets();
	$cmp=new computers();
	$boot=new boostrap_form();
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5(serialize($ligne));
		$ligne["hostname"]=trim($ligne["hostname"]);
		if($ligne["mac"]==null){continue;}
		$ligne["starts"]=time_to_date(strtotime($ligne["starts"]),true);
		$ligne["ends"]=time_to_date(strtotime($ligne["ends"]),true);
		$ligne["cltt"]=time_to_date(strtotime($ligne["cltt"]),true);
		$ligne["tstp"]=time_to_date(strtotime($ligne["tstp"]),true);
		
		
		$tooltip="<ul style=font-size:11px><li>start {$ligne["starts"]}</li>
		<li>cltt:{$ligne["cltt"]}</li> 
		<li>tstp:{$ligne["tstp"]}</li></ul>";
		$js="zBlur();";
		$href=null;
		$uid=null;
		$uid=$cmp->ComputerIDFromMAC($ligne["mac"]);
		if($uid<>null){
				
			$js=MEMBER_JS($uid,1,1);
			$href="<a href=\"javascript:blur()\" OnClick=\"javascript:$js\" style='font-size:12px;text-decoration:underline'>";
			$uid="<div style='font-size:12px'><i>($uid)</i></div>";
		}

		if($ligne["hostname"]==null){$ligne["hostname"]="&nbsp;";}
		if($ligne["ipaddr"]==null){$ligne["ipaddr"]="&nbsp;";}
		if($ligne["mac"]==null){$ligne["mac"]="&nbsp;";}
		
		
		$link=$boot->trswitch($js);
		$tr[]="
		<tr id='$id'>
		<td $link><i class='icon-globe'></i>&nbsp;{$ligne["hostname"]}</a>$uid$tooltip</td>
		<td $link nowrap><i class='icon-info-sign'></i>&nbsp;{$ligne["ipaddr"]}</td>
		<td $link nowrap><i class='icon-star'></i>&nbsp;{$ligne["mac"]}</td>
		<td $link nowrap><i class='icon-time'></i>&nbsp;{$ligne["starts"]}</td>
		<td $link nowrap><i class='icon-time'></i>&nbsp;{$ligne["ends"]}</td>
		
		</tr>";		


	}
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{hostname}</th>
					<th>{ipaddr}</th>
					<th>{ComputerMacAddress}</th>
					<th>Starts</th>
					<th>END</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
				</table>
				";

}