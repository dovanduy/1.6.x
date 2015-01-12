<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.mysql.builder.inc');
include_once('ressources/class.system.nics.inc');
$usersmenus=new usersMenus();
if(!$usersmenus->AsSystemAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}


if(isset($_GET["containers-items"])){qos_containers_items();exit;}
if(isset($_GET["container-js"])){qos_containers_js();exit;}
if(isset($_GET["container-popup"])){qos_containers_popup();exit;}
if(isset($_POST["name"])){qos_containers_save();exit;}
if(isset($_GET["move-item-js"])){move_items_js();exit;}
if(isset($_POST["move-item"])){move_items();exit;}
if(isset($_GET["container-tab"])){qos_containers_tab();exit;}
if(isset($_GET["container-status"])){qos_containers_status();exit;}
if(isset($_GET["container-status-frame"])){qos_containers_status_frame();exit;}

qos_containers();

function move_items_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$t=time();

	$html="
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	$('#TABLEAU_MAIN_QOS_CONTAINERS').flexReload();
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('move-item','{$_GET["ID"]}');
	XHR.appendData('t','{$_GET["t"]}');
	XHR.appendData('dir','{$_GET["dir"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

Save$t();

";

echo $html;

}

function qos_containers_tab(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$fontsize=18;
	$ID=$_GET["ID"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT eth FROM `qos_containers` WHERE ID='$ID'","artica_backup"));
	
	$eth=$ligne["eth"];
	$p=new system_nic();
	$eth=$p->NicToOther($eth);
	
	$array["container-popup"]="{Q.O.S} mark $ID";
	$array["container-status"]="{status} $eth";
	
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t&ID=$ID&eth=$eth\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}
	
	
	
	$html=build_artica_tabs($html,'main_qos_eth'.$eth)."<script>// LeftDesign('qos-256-white.png');</script>";
	
	echo $html;	
	
}

function qos_containers_status(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$eth=$_GET["eth"];

	echo "
	<div id='qos-container-status-$eth'></div>
	<div style='width:100%;text-align:right'>". imgtootltip("refresh-32.png",null,"ChargeQOsStatus$eth()")."</div>
	<script>
		function ChargeQOsStatus$eth(){
		LoadAjaxSilent('qos-container-status-$eth','$page?container-status-frame=$eth');
		}
		
		ChargeQOsStatus$eth();
	</script>
	";
	
}

function qos_containers_status_frame(){
	$sock=new sockets();
	$eth=$_GET["container-status-frame"];
	$filename="/usr/share/artica-postfix/ressources/logs/web/qos-$eth.status";
	$sock->getFrameWork("system.php?qos-status=yes&eth=$eth");
	$data=@file_get_contents($filename);
	echo "	<textarea id='qos-$eth' style='font-family:Courier New;
	font-weight:bold;width:100%;height:620px;border:5px solid #8E8E8E;
	overflow:auto;font-size:12px !important;width:99%;height:390px'>$data</textarea>
	";
	
}

function move_items(){
	$q=new mysql();
	$ID=$_POST["move-item"];
	$t=$_POST["t"];
	$dir=$_POST["dir"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT prio,eth FROM qos_containers WHERE ID='$ID'","artica_backup"));
	if(!$q->ok){echo "Line:".__LINE__.":$sql\n".$q->mysql_error;}
	
	$eth=$ligne["eth"];


	$CurrentOrder=$ligne["prio"];

			if($dir==0){
			$NextOrder=$CurrentOrder-1;
			}else{
				$NextOrder=$CurrentOrder+1;
			}

			$sql="UPDATE qos_containers SET prio=$CurrentOrder WHERE prio='$NextOrder' AND eth='$eth'";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo  "Line:".__LINE__.":$sql\n".$q->mysql_error;}


			$sql="UPDATE qos_containers SET prio=$NextOrder WHERE ID='$ID'";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo  "Line:".__LINE__.":$sql\n".$q->mysql_error;}

			$results=$q->QUERY_SQL("SELECT ID FROM qos_containers ORDER by prio AND eth='$eth'","artica_backup");
			if(!$q->ok){echo "Line:".__LINE__.":".$q->mysql_error;}
			$c=1;
			while ($ligne = mysql_fetch_assoc($results)) {
				$ID=$ligne["ID"];
				$sql="UPDATE qos_containers SET prio=$c WHERE ID='$ID'";
				$q->QUERY_SQL($sql,"artica_backup");
				if(!$q->ok){echo "Line:".__LINE__.":$sql\n".$q->mysql_error;}
				$c++;
			}


}


function qos_containers_js(){

	$ID=intval($_GET["ID"]);
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();

	if($ID==0){
		$title=$tpl->_ENGINE_parse_body("{new_container}");
		echo "YahooWin3('700','$page?container-popup=yes&ID=$ID','$title');";
	}else{
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `name` FROM `qos_containers` WHERE ID='$ID'","artica_backup"));
		$title=utf8_decode($ligne["name"]);
		echo "YahooWin3('700','$page?container-tab=yes&ID=$ID','$title');";
	}
	

}


function qos_containers_items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";

	$t=$_GET["t"];
	$search='%';
	$table="qos_containers";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();


	if($searchstring<>null){
		$search=$_POST["query"];
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"$database"));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	if(!is_numeric($rp)){$rp=50;}
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);



	$data = array();
	$data['page'] = $page;
	$data['total'] = $total+1;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error,1);}


	if(mysql_num_rows($results)==0){

		json_error_show("????");
		return;
	}


	if($searchstring==null){

		$data['total']=$data['total']+$array[0];
		$data['rows']=$array[1]["rows"];
	}

	$fontsize=16;

	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";


		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-js=yes&ID={$ligne["ID"]}&t=$t');");

		$lsprime="javascript:Loadjs('$MyPage?container-js=yes&ID={$ligne["ID"]}')";



		$enabled=$ligne["enabled"];
		$icon="ok24.png";
		if($enabled==0){$icon="ok24-grey.png";$color="#8a8a8a";}
		
		$nic=new system_nic($ligne["eth"]);
		if($nic->QOS==0){
			$icon="ok24-grey.png";
			$color="#8a8a8a";
		}
		
		$QOSMAX=intval($ligne["QOSMAX"]);
		if($QOSMAX<10){$QOSMAX=100;}
		$style="style='font-size:{$fontsize}px;color:$color;'";
		$js="<a href=\"javascript:blur();\" OnClick=\"$lsprime;\"
		style='font-size:{$fontsize}px;color:$color;text-decoration:underline'>";


		$ligne["name"]=utf8_encode($ligne["name"]);
		$up=imgsimple("arrow-up-32.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=0&t={$_GET["t"]}')");
		$down=imgsimple("arrow-down-32.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=1&t={$_GET["t"]}')");
		
		

		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span $style>$js{$ligne["prio"]}</a></span>",
						"<span $style>{$js}{$ligne["eth"]}</a></span>",
						"<span $style>{$js}{$ligne["name"]}</a></span>",
						"<span $style>{$js}{$ligne["rate"]}{$ligne["rate_unit"]}</a></span>",
						"<span $style>{$js}{$ligne["ceil"]}{$ligne["ceil_unit"]}</a></span>",
						"<span $style>{$js}<img src='img/$icon'></a></span>",
						$up,$down,$delete


				)
		);

	}


	echo json_encode($data);

}



function tabs(){

		$tpl=new templates();
		$users=new usersMenus();
		$page=CurrentPageName();
		$fontsize=18;
	
		$array["main"]="{Q.O.S}";
		$array["interfaces"]="{network_interfaces}";
		$array["containers"]="{containers}";
	
		$t=time();
		while (list ($num, $ligne) = each ($array) ){
	
			if($num=="containers"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.mainrules.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
				continue;
	
			}
	
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
		}
	
	
	
		$html=build_artica_tabs($html,'main_qos_center',1020)."<script>LeftDesign('qos-256-white.png');</script>";
	
		echo $html;
}


function qos_containers_popup(){
	
	$ID=intval($_GET["ID"]);
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	
	$btname="{apply}";
	$results=$q->QUERY_SQL("SELECT Interface,QOSMAX FROM nics WHERE QOS=1 ORDER BY Interface","artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$HASH[$ligne["Interface"]]=$ligne["Interface"]." {$ligne["QOSMAX"]}Mib";
		
	}
	
	if($ID==0){
		$btname="{add}";
		$title=$tpl->_ENGINE_parse_body("{new_container}");
	}else{
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM `qos_containers` WHERE ID='$ID'","artica_backup"));
		$title=utf8_decode($ligne["name"]);
	
	}
	
	$UNITS["mbit"]="Megabits {per} {second}";
	$UNITS["kbit"]="Kilobits {per} {second}";
	
	   
	
	    
	
	
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	if($ID==0){$ligne["enabled"]=1;}
	$t=time();
	
	$html="<div style='width:98%' class=form>
	<div style='font-size:26px;margin-bottom:30px'>$title</div>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px;vertical-align=middle'>{enabled}:</td>
		<td>". Field_checkbox("enabled-$t",1,$ligne["enabled"])."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px;vertical-align=middle'>{container}:</td>
		<td colspan=2>". Field_text("name-$t",$ligne["name"],"font-size:18px;width:100%")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px;vertical-align=middle'>{interface}:</td>
		<td colspan=2>". Field_array_Hash($HASH,"eth-$t",$ligne["eth"],"style:font-size:18px;width:100%")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px;vertical-align=middle'>{guaranteed_rate}:</td>
		<td style='font-size:18px;vertical-align=middle'>". Field_text("rate-$t",$ligne["rate"],"font-size:18px;width:100%")."</td>
		<td style='font-size:18px;vertical-align=middle'>". Field_array_Hash($UNITS,"rate_unit-$t",$ligne["rate_unit"],"style:font-size:18px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px;vertical-align=middle'>{bandwith}:</td>
		<td style='font-size:18px;vertical-align=middle'>". Field_text("ceil-$t",$ligne["ceil"],"font-size:18px;width:100%")."</td>
		<td style='font-size:18px;vertical-align=middle' width=1% nowrap>". Field_array_Hash($UNITS,"ceil_unit-$t",$ligne["rate_unit"],"style:font-size:18px")."</td>
	</tr>											
</table>
	<div style='margin-top:50px;text-align:right'><hr>". button("$btname","Save$t()",40)."</div></div>
<script>
var xSave$t= function (obj) {
	var ID=$ID;
	var results=obj.responseText;
	if(results.length>5){alert(results);return;}
	$('#TABLEAU_MAIN_QOS_CONTAINERS').flexReload();
	if(ID==0){ YahooWin3Hide();}
}

function Save$t(){
	var XHR = new XHRConnection();
	enabled=0;
	XHR.appendData('ID',$ID);
	XHR.appendData('name',document.getElementById('name-$t').value);
	XHR.appendData('eth',document.getElementById('eth-$t').value);
	XHR.appendData('rate',document.getElementById('rate-$t').value);
	XHR.appendData('rate_unit',document.getElementById('rate_unit-$t').value);
	XHR.appendData('ceil',document.getElementById('ceil-$t').value);
	XHR.appendData('ceil_unit',document.getElementById('ceil_unit-$t').value);
	if(document.getElementById('enabled-$t').checked){enabled=1;}
	XHR.appendData('enabled',document.getElementById('enabled-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}
</script>	
";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function qos_containers_save(){
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	$_POST["name"]=replace_accents($_POST["name"]);
	
	
$table="qos_containers";
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";
	
	}
	$eth=$_POST["eth"];
	if($ID>0){
		$sql="UPDATE $table SET ".@implode(",", $edit)." WHERE ID='$ID'";
	}else{
		$sql="INSERT IGNORE INTO $table (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	}
	
	$q=new mysql_builder();
	$q->CheckTables_qos();
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$results=$q->QUERY_SQL("SELECT ID FROM qos_containers ORDER by prio AND eth='$eth'","artica_backup");
	if(!$q->ok){echo "Line:".__LINE__.":".$q->mysql_error;}
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$sql="UPDATE qos_containers SET prio=$c WHERE ID='$ID'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Line:".__LINE__.":$sql\n".$q->mysql_error;}
		$c++;
	}
	
}

function qos_containers(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$title=$tpl->javascript_parse_text("{Q.O.S}: {interfaces}");
	$t=time();
	$type=$tpl->javascript_parse_text("{type}");
	$nic_bandwith=$tpl->javascript_parse_text("{nic_bandwith}");
	$guaranteed_rate=$tpl->javascript_parse_text("{guaranteed_rate}");
	$ceil=$tpl->javascript_parse_text("{ceil}");
	$nic=$tpl->javascript_parse_text("{nic}");
	$rulename=$tpl->javascript_parse_text("{container}");
	$title=$tpl->javascript_parse_text("{Q.O.S}: {containers}");
	$new_route=$tpl->javascript_parse_text("{new_route}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$order=$tpl->javascript_parse_text("{order}");
	$bandwith=$tpl->javascript_parse_text("{bandwith}");
	$new_container=$tpl->javascript_parse_text("{new_container}");
	// 	$sql="INSERT INTO nic_routes (`type`,`gateway`,`pattern`,`zmd5`,`nic`)
	// VALUES('$type','$gw','$pattern/$cdir','$md5','$route_nic');";
//{name: '$apply', bclass: 'apply', onpress : Apply$t},
	$buttons="
	buttons : [
	{name: '$new_container', bclass: 'add', onpress : Add$t},
	


	],";
	
	$html="
	
	<table class='TABLEAU_MAIN_QOS_CONTAINERS' style='display: none' id='TABLEAU_MAIN_QOS_CONTAINERS' style='width:100%'></table>
<script>
	var rowid=0;
	$(document).ready(function(){
	$('#TABLEAU_MAIN_QOS_CONTAINERS').flexigrid({
	url: '$page?containers-items=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$order', name : 'prio', width : 75, sortable : true, align: 'center'},
	{display: '$nic', name : 'eth', width : 75, sortable : true, align: 'left'},
	{display: '$rulename', name : 'name', width : 211, sortable : true, align: 'left'},
	{display: '$guaranteed_rate', name : 'rate', width : 146, sortable : true, align: 'left'},
	{display: '$bandwith', name : 'ceil', width : 134, sortable : true, align: 'left'},
	{display: '$enabled', name : 'enabled', width : 50, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'up', width :55, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'down', width :55, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'delete', width :55, sortable : true, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$rulename', name : 'name'},
	],
	sortname: 'prio',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:22px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});


function Add$t(){
	Loadjs('$page?container-js=yes&ID=0');

}
</script>
";
echo $html;

}

