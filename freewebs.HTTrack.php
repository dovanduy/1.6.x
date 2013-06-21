<?php
	session_start();
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');

	
	

	$user=new usersMenus();
	if(!GetPrivs()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
if(isset($_GET["items"])){items();exit;}
if(isset($_POST["item-enable"])){item_enable();exit;}
if(isset($_GET["item-id"])){item_popup();exit;}
if(isset($_POST["ID"])){item_save();exit;}
if(isset($_POST["delete-item"])){item_delete();exit;}
if(isset($_POST["item-run"])){item_run();exit;}
if(isset($_POST["exec"])){execute();exit;}
table();
function GetPrivs(){
	$users=new usersMenus();
	if($users->AsSystemWebMaster){return true;}
}
function table(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	if(!$users->HTTRACK_INSTALLED){
		echo $tpl->_ENGINE_parse_body(
		"<center style='margin:90px'>
				<table style='width:99%' class=form>
				<tr>
					<td width=1% valign='top'><img src='img/error-128.png'></td>
					<td valign='top' style='font-size:18px;font-family:Arial,Tahoma'>{ERROR_HTTRACK_NOT_INSTALLED}</td>
				</tr>
				</table>
			</center>
		</center>");
		return;
		
	}
	
	

	$TB_HEIGHT=400;
	$TB_WIDTH=790;

	$new_entry=$tpl->javascript_parse_text("{new_website}");
	$t=time();
	$run=$tpl->_ENGINE_parse_body("{run}");
	$lang=$tpl->_ENGINE_parse_body("{language}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$website=$tpl->_ENGINE_parse_body("{websites}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$execute=$tpl->_ENGINE_parse_body("{execute}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	
	
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : ItemNew$t},
	{name: '$execute', bclass: 'ReConf', onpress : ItemExec$t},
	{name: '$events', bclass: 'Script', onpress : ItemEvents$t},
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	
	],	";
	
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$website', name : 'sitename', width :578, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width :110, sortable : true, align: 'center'},
		{display: '$run', name : 'run', width :31, sortable : false, align: 'center'},
		{display: '$enable', name : 'enabled', width :40, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width :31, sortable : false, align: 'center'}
	],
	$buttons

	searchitems : [
		{display: '$website', name : 'sitename'},
	],
	sortname: 'sitename',
	sortorder: 'asc',
	usepager: true,
	title: 'WebCopy',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 875,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});
function ItemShow$t(id){
	YahooWin5('670','$page?item-id='+id+'&t=$t','WebCopy:'+id);
}

function ItemEvents$t(){
	Loadjs('squid.update.events.php?table=system_admin_events&category=webcopy');
}
function ItemHelp$t(){
	s_PopUpFull('http://mail-appliance.org/index.php?cID=263','1024','900');
}

var x_ItemDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}	
	$('#row'+mem$t).remove();
}

function ItemDelete$t(id){
	mem$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('delete-item',id);
    XHR.sendAndLoad('$page', 'POST',x_ItemDelete$t);	
	}
	

function ItemNew$t(){
	title='$new_entry';
	YahooWin5('670','$page?item-id=0&t=$t','WebCopy::'+title);
}
var x_ItemExec$t=function (obj) {
	var results=obj.responseText;
	if (results.length>3){alert(results);return;}
	$('#flexRT$t').flexReload();
}
var x_ItemExec2$t=function (obj) {
	var results=obj.responseText;
	if (results.length>3){alert(results);}
	$('#flexRT$t').flexReload();
}
var x_ItemSilent$t=function (obj) {
	var results=obj.responseText;
	if (results.length>3){alert(results);return;}
	
}
function ItemExec$t(){
	var XHR = new XHRConnection();
	XHR.appendData('exec','yes');
    XHR.sendAndLoad('$page', 'POST',x_ItemExec2$t);	
}
function ItemRun$t(ID,imgid){
	mem$t=imgid;
	var XHR = new XHRConnection();
	XHR.appendData('item-run',ID);
	document.getElementById(imgid).src='/ajax-menus-loader.gif';
    XHR.sendAndLoad('$page', 'POST',x_ItemExec2$t);	
}
function ItemEnable$t(id){
	var value=0;
	if(document.getElementById('enable-'+id).checked){value=1;}
	var XHR = new XHRConnection();
	XHR.appendData('item-enable',id);
	XHR.appendData('value',value);
    XHR.sendAndLoad('$page', 'POST',x_ItemSilent$t);
}
	
</script>";
	
	echo $html;		
}	

function items(){
	
$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	
	$search='%';
	$table="httrack_sites";
	$database='artica_backup';
	$page=1;
	$FORCE_FILTER="";
	
	if(!$q->TABLE_EXISTS($table, $database)){$q->BuildTables();}
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table, No such table...",0);}
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No data...",0);}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	//id 	domain_id 	name 	type 	content 	ttl 	prio 	change_date 	ordername 	auth
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$sock=new sockets();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=$ligne["ID"];
		$articasrv=null;
		$delete=imgsimple("delete-24.png",null,"ItemDelete$t('$id')");
		if($ligne["depth"]==0){$ligne["depth"]=$tpl->_ENGINE_parse_body("{unlimited}");}
		$ligne["maxsitesize"]=FormatBytes($ligne["maxsitesize"]);
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$enabled=Field_checkbox("enable-$id", 1,$ligne["enabled"],"ItemEnable$t($id)");
		$run=imgsimple("24-run.png",null,"ItemRun$t($id,'imgW-$id')",null,"imgW-$id");
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
		"<a href=\"javascript:blur();\" OnClick=\"javascript:ItemShow$t($id);\" style='font-size:16px;text-decoration:underline'>{$ligne["sitename"]}</a><div>{$ligne["workingdir"]} {$ligne["minrate"]}Kb/s MAX:{$ligne["maxsitesize"]}</div>",
		"<span style='font-size:18px;'>{$ligne["size"]}</span>",
		$run,	
		$enabled,
		$delete )
		);
	}
	
	
echo json_encode($data);		
	
}

function item_popup(){
	$ldap=new clladp();
	$tpl=new templates();
	$page=CurrentPageName();
	
	$id=$_GET["item-id"];
	if(!is_numeric($id)){$id=0;}
	$t=$_GET["t"];
	$bname="{add}";
	$browse=button("{browse}","javascript:Loadjs('browse-disk.php?field=workingdir-$t&replace-start-root=0');");
	$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM=$tpl->javascript_parse_text("{ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM}");
	
	$q=new mysql();
	if($id>0){
		$bname="{apply}";
		$sql="SELECT * FROM httrack_sites WHERE ID=$id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$workingdir=$ligne["workingdir"];
		$sitename=$ligne["sitename"];
		$minrate=$ligne["minrate"];
		$maxfilesize=$ligne["maxfilesize"];
		$maxsitesize=$ligne["maxsitesize"];
		$lang=$ligne["lang"];
		$browse=null;
	}
	
	
	
	if($lang==null){$lang="english";}
	if(!is_numeric($minrate)){$minrate=512;}
	if(!is_numeric($maxfilesize)){$maxfilesize=512;}
	if(!is_numeric($maxsitesize)){$maxsitesize=5000;}
	


$html="		
<div id='anime-$t'></div>
<div style='width:95%' class=form>
<table >
<tr>	
	<td class=legend style='font-size:14px' nowrap>{website}:</strong></td>
	<td align=left>". Field_text("sitename-$t",$sitename,"width:280px;font-size:14px","script:FormCheck$t(event)")."</strong></td>
	<td width=1%>&nbsp;</td>
<tr>
<tr>	
	<td class=legend style='font-size:14px' nowrap>{directory}:</strong></td>
	<td align=left>". Field_text("workingdir-$t",$workingdir,"width:280px;font-size:14px","script:FormCheck$t(event)")."</strong></td>
	<td width=1%>$browse</td>
<tr>

<tr>
	<td class=legend style='font-size:14px' nowrap>{MaxRateBw}:</strong></td>
	<td align=left style='font-size:14px'>". Field_text("minrate-$t",$minrate,"width:60px;font-size:14px","script:FormCheck$t(event)")."&nbsp;KB/s</strong></td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px' nowrap>{maxfilesize}:</strong></td>
	<td align=left style='font-size:14px'>". Field_text("maxfilesize-$t",$maxfilesize,"width:60px;font-size:14px","script:FormCheck$t(event)")."&nbsp;KB</strong></td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px' nowrap>{maxsitesize}:</strong></td>
	<td align=left style='font-size:14px'>". Field_text("maxsitesize-$t",$maxsitesize,"width:60px;font-size:14px","script:FormCheck$t(event)")."&nbsp;KB</strong></td>
	<td>&nbsp;</td>
</tr>
<tr>	
	<td colspan=3 align='right'><hr>". button("$bname","SaveForm$t();","18px")."</td>
<tr>
</table>
</div>
<script>

		function FormCheck$t(e){
			if(checkEnter(e)){SaveForm$t();return;}
		}
		

		var x_SaveForm$t=function (obj) {
			var results=obj.responseText;
			document.getElementById('anime-$t').innerHTML='';
			if (results.length>3){alert(results);return;}
			$('#flexRT$t').flexReload();
			ExecuteByClassName('SearchFunction');
		}				
		
		function SaveForm$t(){
			var ok=1;
			var workingdir=document.getElementById('workingdir-$t').value;
			var sitename=document.getElementById('sitename-$t').value;
			
			if(workingdir.length==0){ok=0;}
			if(sitename.length==0){ok=0;}
			if(ok==0){alert('$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM');return;}
			var XHR = new XHRConnection();
			var pp=encodeURIComponent(document.getElementById('workingdir-$t').value);
			XHR.appendData('ID','$id');
			XHR.appendData('workingdir',pp);
			XHR.appendData('sitename',document.getElementById('sitename-$t').value);
			XHR.appendData('minrate',document.getElementById('minrate-$t').value);
			XHR.appendData('maxfilesize',document.getElementById('maxfilesize-$t').value);
			XHR.appendData('maxsitesize',document.getElementById('maxsitesize-$t').value);
			AnimateDiv('anime-$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveForm$t);
		
		}
		
		function FormCheckFields$t(){
			var ID=$id;
			if($id>0){
				document.getElementById('sitename-$t').disabled=true;
				document.getElementById('workingdir-$t').disabled=true;
			}
		}
		FormCheckFields$t();
</script>

";	
					
					
	echo $tpl->_ENGINE_parse_body($html);	
}

function item_save(){
	
	$ID=$_POST["ID"];
	$_POST["workingdir"]=url_decode_special_tool($_POST["workingdir"]);
	
	$parsed_url=parse_url($_POST["sitename"]);
	if(isset($parsed_url['port'])){$port=":{$parsed_url['port']}";}
	$_POST["sitename"]="{$parsed_url["scheme"]}://{$parsed_url["host"]}$port";
	if($ID==0){
		$sql="INSERT IGNORE INTO httrack_sites (workingdir,sitename,minrate,maxfilesize,`maxsitesize`,enabled) 
		VALUES ('{$_POST["workingdir"]}','{$_POST["sitename"]}','{$_POST["minrate"]}','{$_POST["maxfilesize"]}',
		'{$_POST["maxsitesize"]}',1)";
		
		
	}else{
		$sql="UPDATE httrack_sites SET minrate='{$_POST["minrate"]}',maxfilesize='{$_POST["maxfilesize"]}',
		maxsitesize='{$_POST["maxsitesize"]}',workingdir='{$_POST["workingdir"]}'
		WHERE ID='$ID'";
		
		
	}

	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo trim("Mysql Error:".$q->mysql_error);}
	
	
	
}

function item_enable(){
	$sql="UPDATE httrack_sites SET enabled='{$_POST["value"]}' WHERE ID='{$_POST["item-enable"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo trim("Mysql Error:".$q->mysql_error);}	
	
}

function item_delete(){
	$q=new mysql();
	$sock=new sockets();

	
	$sql="SELECT servername FROM freeweb WHERE WebCopyID='{$_POST["delete-item"]}'";
	$ss=0;
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){echo trim("Mysql Error:".$q->mysql_error);return;}
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("INSERT INTO drupal_queue_orders(`ORDER`,`servername`) VALUES('DELETE_FREEWEB','{$ligne["servername"]}')","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$ss++;
	}
	if($ss>0){$sock->getFrameWork("drupal.php?perform-orders=yes");}
	
	$q->QUERY_SQL("DELETE FROM httrack_sites WHERE ID='{$_POST["delete-item"]}'","artica_backup");
	if(!$q->ok){echo trim("Mysql Error:".$q->mysql_error);}	

}

function execute(){
	$sock=new sockets();
	$sock->getFrameWork("xapian.php?httptrack=yes");
	$tpl=new templates();	
	echo $tpl->javascript_parse_text("{operation_in_background}");
}

function item_run(){
	$sock=new sockets();
	$sock->getFrameWork("xapian.php?httptrack-id={$_POST["item-run"]}");	
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{operation_in_background}");
}
?>

	
