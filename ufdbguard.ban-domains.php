<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}



if(isset($_GET["bannedextensionlist-table"])){table();exit;}
if(isset($_GET["list"])){listext();exit;}
if(isset($_POST["bannedextensionlist-default"])){bannedextensionlist_default();exit;}
if(isset($_POST["bannedextensionlist-enable"])){bannedextensionlist_enable();exit;}
if(isset($_POST["bannedextensionlist-delete"])){bannedextensionlist_delete();exit;}
if(isset($_GET["bannedextensionlist-add-popup"])){bannedextensionlist_add_popup();exit;}
if(isset($_POST["bannedextensionlist-add"])){bannedextensionlist_add();exit;}

popup();


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
$html="<div class=explain>{bannedextensionlistdomain_explain}</div>
<div id='bannedextensionlistDoms-div'></div>
<script>
	function RefreshBannedextensionlist(){
		$('#bannedextensionlistDoms-table').remove();
		LoadAjax('bannedextensionlistDoms-div','$page?bannedextensionlist-table=yes&ID={$_GET["ID"]}');
	}
	
	RefreshBannedextensionlist();
</script>";
echo $tpl->_ENGINE_parse_body($html);
}
function table(){
	$ID=$_GET["ID"];
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$extension=$tpl->_ENGINE_parse_body("{extension}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$category=$tpl->_ENGINE_parse_body("{category}");	
	$delete=$tpl->_ENGINE_parse_body("{delete}");	
	$files_restrictions=$tpl->_ENGINE_parse_body("{files_restrictions}");
	$add=$tpl->_ENGINE_parse_body("{add}:{domain_extension}");
	
	$TB_WIDTH=878;
	$disable_all=Field_checkbox("disable_{$ligne["zmd5"]}", 1,$ligne["enabled"],"bannedextensionlist_enable('{$ligne["zmd5"]}')");
	$t=time();
	$html="
	<table class='bannedextensionlistDoms-table' style='display: none' id='bannedextensionlistDoms-table' style='width:99%'></table>
<script>
var bannedextensionlist_KEY='';
$(document).ready(function(){
$('#bannedextensionlistDoms-table').flexigrid({
	url: '$page?list=yes&RULEID=$ID&t=$t',
	dataType: 'json',
	colModel : [
		
		{display: '$extension', name : 'ext', width : 180, sortable : true, align: 'left'},
		{display: '$description', name : 'description', width : 565, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none2', width : 30, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none3', width : 30, sortable : false, align: 'left'},
	],
buttons : [
		{name: '$add', bclass: 'add', onpress : AddNewExtension$t},
		{separator: true},

		
		],	
	searchitems : [
		{display: '$extension', name : 'ext'},
		{display: '$description', name : 'description'},
		],
	sortname: 'ext',
	sortorder: 'asc',
	usepager: true,
	title: '$files_restrictions',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: 250,
	singleSelect: true
	
	});   
});
function AddNewExtension$t() {
	YahooWin6('400','$page?bannedextensionlist-add-popup=yes&ID=$ID&t=$t','$add');
	
}

	var x_bannedextensionlist_AddDefault$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		YahooWin6Hide();
		RefreshBannedextensionlist();
    }	  

function bannedextensionlist_AddDefault$t(){
      var XHR = new XHRConnection();
      XHR.appendData('bannedextensionlist-default','$ID');
      AnimateDiv('annedextensionlist-div');
      XHR.sendAndLoad('$page', 'POST',x_bannedextensionlist_AddDefault$t);
      
      }

var x_bannedextensionlist_enable$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);RefreshBannedextensionlist();}
}	        
      
function bannedextensionlist_enable$t(md5){
	 var XHR = new XHRConnection();
	 XHR.appendData('bannedextensionlist-key',md5);
	 if(document.getElementById('disable_'+md5).checked){XHR.appendData('bannedextensionlist-enable','1');}else{XHR.appendData('bannedextensionlist-enable','0');}
	 XHR.sendAndLoad('$page', 'POST',x_bannedextensionlist_enable$t);
}

var x_bannedextensionlist_delete$t=function(obj){
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#row'+bannedextensionlist_KEY).remove();
}

function bannedextensionlist_delete$t(md5){
	bannedextensionlist_KEY=md5;
	var XHR = new XHRConnection();
	XHR.appendData('bannedextensionlist-delete',md5);
	XHR.sendAndLoad('$page', 'POST',x_bannedextensionlist_delete$t);
}

</script>	";
echo $tpl->_ENGINE_parse_body($html);
}
function listext(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	$t=$_GET["t"];
	$search='%';
	$table="webfilter_bannedextsdoms";
	$page=1;
	$ORDER="ORDER BY ext ASC";
	$FORCE_FILTER=" AND ruleid={$_GET["RULEID"]}";
	
	$total=0;
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables();}
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql,"artica_backup");
	$divstart="<span style='font-size:14px;font-weight:bold'>";
	$divstop="</div>";
	$noneTXT=$tpl->_ENGINE_parse_body("{none}");
	while ($ligne = mysql_fetch_assoc($results)) {
		$img="img/ext/def_small.gif";
		if(file_exists("img/ext/{$ligne['ext']}_small.gif")){$img="img/ext/{$ligne['ext']}_small.gif";}
		$disable=Field_checkbox("disable_{$ligne["zmd5"]}", 1,$ligne["enabled"],"bannedextensionlist_enable$t('{$ligne["zmd5"]}')");
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['ext']}","bannedextensionlist_delete$t('{$ligne["zmd5"]}')");
		
		
	$data['rows'][] = array(
		'id' => $ligne['zmd5'],
	'cell' => array(
		"<strong style='font-family:Courier New;font-size:14px'>http://*domain.<span style='color:#A40000'>{$ligne['ext']}</span>/...</strong>",
		"<span style='font-size:13px'>{$ligne['description']}</span>",
		"<div style='margin-top:5px'>$disable</div>",$delete)
		);
		
	}
	
	// http://*domain.<span style='color:#A40000'>
	
echo json_encode($data);	
	
	
}
function bannedextensionlist_add(){
	$extension=strtolower(trim($_POST["bannedextensionlist-add"]));
	$description=addslashes($_POST["description"]);
	$ID=$_POST["ID"];
	if(substr($extension,0,1)=='.'){$extension=substr($extension, 1,strlen($extension));}
	$extension=str_replace("*",'',$extension);
	$md5=md5("$ID$extension");
	$q=new mysql_squid_builder();
	$sql="INSERT INTO webfilter_bannedextsdoms (enabled,zmd5,ext,description,ruleid) VALUES(1,'$md5','$extension','$description',$ID);";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
	
}

function bannedextensionlist_enable(){
	$q=new mysql_squid_builder();
	$sql="UPDATE webfilter_bannedextsdoms SET enabled={$_POST["bannedextensionlist-enable"]} 
	WHERE zmd5='{$_POST["bannedextensionlist-key"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
}

function bannedextensionlist_delete(){
	$q=new mysql_squid_builder();
	$sql="DELETE FROM webfilter_bannedextsdoms WHERE zmd5='{$_POST["bannedextensionlist-delete"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
}

function bannedextensionlist_add_popup(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
	<td class=legend style='font-size:14px'>{domain_extension}:</strong></td>
	<td>" . Field_text('extension_pattern',null,'width:60px;font-size:14px',null,null,null,false,"ext_enter(event)")."</td>
	</tr>
	<tr>
	<td class=legend style='font-size:14px'>{description}:</strong></td>
	<td>" . Field_text('extension_description',null,'width:99%;font-size:14px',null,null,null,false,"ext_enter(event)")."</td>
	</tr>
	<tr>
	<td colspan=2 align='right'><hr>". button("{add_extension}","bannedextension_listadd()",14)."</td>
	</tr>
	</tbody>
	</table>
	</div>
	<script>
function bannedextension_listadd(){
      var XHR = new XHRConnection();
      XHR.appendData('ID','$ID');
      XHR.appendData('bannedextensionlist-add',document.getElementById('extension_pattern').value);
      XHR.appendData('description',document.getElementById('extension_description').value);
      AnimateDiv('$t');   
      XHR.sendAndLoad('$page', 'POST',x_bannedextensionlist_AddDefault$t);        
      }  

     function ext_enter(e){
     	if(checkEnter(e)){bannedextension_listadd();}
     }
	
</script>	
	
	";
	
echo $tpl->_ENGINE_parse_body($html);
}
