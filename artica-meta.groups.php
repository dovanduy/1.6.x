<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');


$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();

}

if(isset($_GET["new-group-js"])){new_group_js();exit;}
if(isset($_POST["new-group"])){new_group_save();exit;}
if(isset($_GET["group-js"])){group_js();exit;}
if(isset($_GET["group-tab"])){group_tab();exit;}
if(isset($_GET["group-popup"])){group_popup();exit;}
if(isset($_POST["groupname"])){group_save();exit;}
if(isset($_GET["delete-group-js"])){group_delete_js();exit;}
if(isset($_POST["delete-group"])){group_delete();exit;}
if(isset($_GET["search"])){search();exit;}

page();


function group_delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$text=$tpl->javascript_parse_text("{delete}");
	
	$q=new mysql_meta();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM metagroups WHERE ID={$_GET["delete-group-js"]}"));
	$groupname=$ligne["groupname"];
	$t=time();
	echo "
var xAdd$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#ARTICA_META_GROUP_TABLE').flexReload();
}
function Add$t(){
	if(!confirm('$text $groupname ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-group', '{$_GET["delete-group-js"]}');
	XHR.sendAndLoad('$page', 'POST',xAdd$t);
}
Add$t();";
}

function  group_delete(){
	$gpid=$_POST["delete-group"];
	$q=new mysql_meta();
	$q->QUERY_SQL("DELETE FROM metagroups_link WHERE gpid='$gpid'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM metagroups WHERE ID='$gpid'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}


function new_group_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$text=$tpl->javascript_parse_text("{groupname}");
	$t=time();
echo "
var xAdd$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#ARTICA_META_GROUP_TABLE').flexReload();
	
}
function Add$t(){
	var group=prompt('$text ?');
	if(!group){return;}
	var XHR = new XHRConnection();
	XHR.appendData('new-group', group);
	XHR.sendAndLoad('$page', 'POST',xAdd$t);
}

Add$t();";

}

function group_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_meta();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM metagroups WHERE ID={$_GET["ID"]}"));
	$ligne["groupname"]=$tpl->javascript_parse_text($ligne["groupname"]);
	echo "YahooWin(990,'$page?group-tab=yes&ID={$_GET["ID"]}','{$ligne["groupname"]}')";
}

function new_group_save(){
	$_POST["new-group"]=mysql_escape_string2($_POST["new-group"]);
	$sql="INSERT IGNORE INTO metagroups (groupname) VALUES('{$_POST["new-group"]}')";
	$q=new mysql_meta();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}

function group_tab(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_meta();
	$ID=$_GET["ID"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM metagroups WHERE ID={$_GET["ID"]}"));
	$ligne["groupname"]=$tpl->javascript_parse_text($ligne["groupname"]);	
	
	
	$array["group-popup"]=$ligne["groupname"];
	$array["hosts"]='{hosts}';
	$array["policies"]='{policies}';
	$array["categories"]='{Webfiltering_categories}';
	
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="hosts"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.group.hosts.php?ID=$ID\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="policies"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.group.policies.php?ID=$ID\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="categories"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.group.categories.php?ID=$ID\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}		
	
		
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&ID=$ID\"><span style='font-size:18px'>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "meta_group_tab");	
	
}

function group_save(){
	$_POST["groupname"]=mysql_escape_string2(url_decode_special_tool($_POST["groupname"]));
	$sql="UPDATE metagroups SET groupname='{$_POST["groupname"]}' WHERE ID={$_POST["ID"]}";
	$q=new mysql_meta();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}

function group_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_meta();
	$ID=$_GET["ID"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM metagroups WHERE ID={$_GET["ID"]}"));
	$groupname=$tpl->javascript_parse_text($ligne["groupname"]);
	$t=time();
	$html="<div style='font-size:22px;margin-bottom:20px'>&nbsp;&laquo;&nbsp;$groupname&nbsp;&raquo;</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>".
	
	Field_text_table("groupname-$t", "{groupname}",$ligne["groupname"],22,null,350).
	Field_button_table_autonome("{apply}","Save$t()",32)."</table>
	<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#ARTICA_META_GROUP_TABLE').flexReload();
}

function SaveCHK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}
	

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID',  '$ID');
	XHR.appendData('groupname',  encodeURIComponent(document.getElementById('groupname-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function page(){


	$page=CurrentPageName();
	$tpl=new templates();

	$t=time();
	$new_group=$tpl->javascript_parse_text("{new_group}");
	$groups=$tpl->javascript_parse_text("{groups2}");
	$memory=$tpl->javascript_parse_text("{memory}");
	$load=$tpl->javascript_parse_text("{load}");
	$version=$tpl->javascript_parse_text("{version}");
	$servername=$tpl->javascript_parse_text("{servername2}");
	$status=$tpl->javascript_parse_text("{status}");
	$events=$tpl->javascript_parse_text("{events}");
	$global_whitelist=$tpl->javascript_parse_text("{whitelist} (Meta)");
	$policies=$tpl->javascript_parse_text("{policies}");
	$packages=$tpl->javascript_parse_text("{packages}");
	$transparent=$tpl->javascript_parse_text("{transparent}");
	$switch=$tpl->javascript_parse_text("{switch}");
	$new_server=$tpl->javascript_parse_text("{new_server}");
	$hosts=$tpl->javascript_parse_text("{hosts}");
	$t=time();
	$delete="{display: 'delete', name : 'icon3', width : 35, sortable : false, align: 'left'},";
	$categorysize=387;
	$tag=$tpl->javascript_parse_text("{tag}");

	$q=new mysql_meta();
	
	$buttons="	
	buttons : [
		{name: '<storng style=font-size:18px>$new_group</strong>', bclass: 'add', onpress : NewGroup$t},
	],";

	
	
	$html="
	
	<table class='ARTICA_META_GROUP_TABLE' style='display: none' id='ARTICA_META_GROUP_TABLE' style='width:1200px'></table>
	<script>
$(document).ready(function(){
	$('#ARTICA_META_GROUP_TABLE').flexigrid({
	url: '$page?search=yes',
	dataType: 'json',
	colModel : [
	{display: 'status', name : 'icon1', width : 70, sortable : false, align: 'center'},
	{display: '$groups', name : 'groupname', width : 540, sortable : true, align: 'left'},
	{display: '$hosts', name : 'hosts', width : 70, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'settings', width : 70, sortable : false, align: 'center'},
	{display: '$transparent', name : 'transparent', width : 98, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 70, sortable : false, align: 'center'},

	],
	$buttons
	searchitems : [
	{display: '$groups', name : 'groupname'},
	
	],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:22px>Meta Server: $groups</strong>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true

});
});

	function NewGroup$t(){
		Loadjs('$page?new-group-js=yes');
	}

</script>";
	echo $html;
}
function search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_meta();
	$table="metagroups";

	if(!$q->TABLE_EXISTS($table)){
		$sql="CREATE TABLE IF NOT EXISTS `metagroups` (
				`ID` INT(10) NOT NULL AUTO_INCREMENT,
				`groupname` varchar(90) NOT NULL,
				`CountHosts` smallint(3) NOT NULL DEFAULT 0,
				PRIMARY KEY (`ID`),
				KEY `groupname` (`groupname`),
				KEY `CountHosts` (`CountHosts`)
				) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo json_error_show($q->mysql_error,1);}
	}
	$searchstring=string_to_flexquery();
	$page=1;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}


	if($searchstring<>null){
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
		$total = $ligne["tcount"];

	}else{
		$total = $q->COUNT_ROWS($table);
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";



	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql);

	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}


	if(mysql_num_rows($results)==0){json_error_show("no data",1);}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$fontsize="22";
	$style=" style='font-size:{$fontsize}px'";
	$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline'";
	$free_text=$tpl->javascript_parse_text("{free}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
	$orders_text=$tpl->javascript_parse_text("{orders}");
	$directories_monitor=$tpl->javascript_parse_text("{directories_monitor}");
	

	while ($ligne = mysql_fetch_assoc($results)) {
		$LOGSWHY=array();
		$overloaded=null;
		$loadcolor="black";
		$StatHourColor="black";
		
		$ColorTime="black";
		$groupname=$ligne["groupname"];
		
		$icon_warning_32="warning32.png";
		$icon_red_32="32-red.png";
		$icon="ok-32.png";
		
		
		$urijs="Loadjs('$MyPage?group-js=yes&ID={$ligne["ID"]}')";
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$urijs\" $styleHref>";
		
		$orders=imgtootltip("48-settings.png",null,"Loadjs('artica-meta.menus.php?gpid={$ligne["ID"]}');");
		$transparent=imgtootltip("ok-pass-48.png",null,"Loadjs('artica-meta.squidtransparent-white.php?gpid={$ligne["ID"]}');");
		
		
		
		$delete=imgtootltip("delete-32.png",null,"Loadjs('$MyPage?delete-group-js={$ligne["ID"]}')");
		
		
		$count=$q->group_count($ligne["ID"]);
		
		
		$cell=array();
		$cell[]="<center><img src=\"img/$icon\"></center>";
		$cell[]="<span $style>$link$groupname</a></span>";
		$cell[]="<center $style>$link$count</a></center>";
		$cell[]="<center $style>$orders</a></center>";
		$cell[]="<center $style>$transparent</a></center>";
		$cell[]="<center $style>$delete</a></center>";

		$data['rows'][] = array(
		'id' => $ligne['uuid'],
		'cell' => $cell
		);
	}


	echo json_encode($data);
}