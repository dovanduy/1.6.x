<?php
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.mount.inc");
include_once(dirname(__FILE__) . '/ressources/class.autofs.inc');

if(posix_getuid()<>0){
	$user=new usersMenus();
	if($user->AsDnsAdministrator==false){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die();exit();
	}
}

if(isset($_GET["items"])){items();exit;}
if(isset($_GET["item-id"])){item_popup();exit;}
if(isset($_POST["ID"])){item_save();exit;}
if(isset($_POST["delete-item"])){item_delete();exit;}
if(isset($_POST["exec"])){execute();exit;}
table();



function table(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=400;
	$TB_WIDTH=790;

	$new_entry=$tpl->_ENGINE_parse_body("{new_directory}");
	$t=time();
	$volumes=$tpl->_ENGINE_parse_body("{volumes}");
	$lang=$tpl->_ENGINE_parse_body("{language}");
	$ipaddr=$tpl->_ENGINE_parse_body("{addr}");
	$directories=$tpl->_ENGINE_parse_body("{directories}");
	$depth=$tpl->_ENGINE_parse_body("depth");
	$execute=$tpl->_ENGINE_parse_body("{execute}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewXapianDir2$t},
	{name: '$execute', bclass: 'ReConf', onpress : XapianExec$t},
	{name: '$events', bclass: 'Script', onpress : XapianEvents$t},
	
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
		{display: '$directories', name : 'directory', width :363, sortable : true, align: 'left'},
		{display: '$size', name : 'DatabaseSize', width :77, sortable : true, align: 'left'},
		{display: '$depth', name : 'depth', width :64, sortable : true, align: 'center'},
		{display: '$lang', name : 'lang', width :135, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width :31, sortable : false, align: 'center'},
		
		 	

	],
	$buttons

	searchitems : [
		{display: '$directories', name : 'directory'},
	],
	sortname: 'directory',
	sortorder: 'asc',
	usepager: true,
	title: 'Xapian Desktop - $directories',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 790,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

	var x_XapianRecordDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}	
		$('#row'+mem$t).remove();
	}

function XapianRecordDelete$t(id){
	mem$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('delete-item',id);
    XHR.sendAndLoad('$page', 'POST',x_XapianRecordDelete$t);	
	}
function XapianEvents$t(){
		Loadjs('squid.update.events.php?table=system_admin_events&category=xapian');
}
	
function XapianDir$t(id){
	YahooWin5('682','$page?item-id='+id+'&t=$t','Xapian Desktop:'+id);
}
function NewXapianDir2$t(){
	
	title='$new_entry';
	YahooWin5('682','$page?item-id=0&t=$t','Xapian Desktop:'+title);
}
var x_XapianExec$t=function (obj) {
	var results=obj.responseText;
	if (results.length>0){alert(results);return;}
	$('#flexRT$t').flexReload();
}

function XapianExec$t(){
	var XHR = new XHRConnection();
	XHR.appendData('exec','yes');
    XHR.sendAndLoad('$page', 'POST',x_XapianExec$t);	
}
	
</script>";
	
	echo $html;		
}	

function items(){
	
$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$tSource=$_GET["t"];
	
	$search='%';
	$table="xapian_folders";
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
	$autofs=new autofs();
	$autofs->automounts_Browse();	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=$ligne["ID"];
		$articasrv=null;
		$address=null;
		$delete=imgsimple("delete-24.png",null,"XapianRecordDelete$tSource('$id')");
		if($ligne["depth"]==0){$ligne["depth"]=$tpl->_ENGINE_parse_body("{unlimited}");}
		$autmountdn=$ligne["autmountdn"];
		$lastscan=strtotime($ligne["ScannedTime"]);
		$t=time();
		$took=distanceOfTimeInWords($lastscan,$t,true);
		if($ligne["WebCopyID"]>0){
			$address="<div style='font-size:14px;font-weight:bold'>".WebCopyIDAddresses($ligne["WebCopyID"])."</div>";
			$ligne["directory"]=WebCopyIDDirectory($ligne["WebCopyID"]);
		}
		
		
		if($autmountdn<>null){
			$autmountdn_array=$autofs->hash_by_dn[$autmountdn];
			$ligne["directory"]="/automounts/{$autmountdn_array["FOLDER"]}";
			$autmountdn_infos=$autmountdn_array["INFOS"];
			$BaseUrl=$autmountdn_infos["BROWSER_URI"];
			$address="<div style='font-size:14px;font-weight:bold'>$BaseUrl</div>";
		}
			
		$size=FormatBytes($ligne["DatabaseSize"]/1024);		
		
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
		"<a href=\"javascript:blur();\" OnClick=\"javascript:XapianDir$tSource($id);\" 
		style='font-size:16px;text-decoration:underline'>{$ligne["directory"]}</a>$address<div>Scanned:$took indexed:{$ligne["indexed"]}</div>",
		"<span style='font-size:16px;'>$size</span>",
		"<span style='font-size:16px;'>{$ligne["depth"]}</span>",
		"<span style='font-size:16px;'>{$ligne["lang"]}</span>",
		$delete )
		);
	}
	
	
echo json_encode($data);		
	
}

function WebCopyIDAddresses($ID){
	$q=new mysql();
	$sql="SELECT useSSL,servername FROM freeweb WHERE WebCopyID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["servername"]<>null){
		$method="http";
		if($ligne["useSSL"]==1){$method="https";}
		return "$method://{$ligne["servername"]}";
	}
	
	$sql="SELECT sitename FROM httrack_sites WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	return $ligne["sitename"];
	
}
function WebCopyIDDirectory($ID){
	$q=new mysql();
	$sql="SELECT workingdir,sitename FROM httrack_sites WHERE ID=$ID";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$parsed_url=parse_url($ligne["sitename"]);
	$ligne["sitename"]="{$parsed_url["host"]}";	
	return $ligne["workingdir"]."/{$ligne["sitename"]}";
	
}

function item_popup(){
	$ldap=new clladp();
	$tpl=new templates();
	$page=CurrentPageName();
	
	$id=$_GET["item-id"];
	if(!is_numeric($id)){$id=0;}
	$t=$_GET["t"];
	$bname="{add}";

	$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM=$tpl->javascript_parse_text("{ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM}");
	
	$q=new mysql();
	if($id>0){
		$bname="{apply}";
		$sql="SELECT * FROM xapian_folders WHERE ID=$id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$directory=$ligne["directory"];
		$depth=$ligne["depth"];
		$maxsize=$ligne["maxsize"];
		$samplsize=$ligne["sample-size"];
		$lang=$ligne["lang"];
		$DiplayFullPath=$ligne["DiplayFullPath"];
		$AllowDownload=$ligne["AllowDownload"];
		$WebCopyID=$ligne["WebCopyID"];
		$autmountdn=$ligne["autmountdn"];
	}
	
	
	
	if($lang==null){$lang="english";}
	if(!is_numeric($samplsize)){$samplsize=512;}
	if(!is_numeric($maxsize)){$maxsize=60;}
	if(!is_numeric($depth)){$depth=0;}
	
	
	$l["none"]="none";
	$l["danish"]="danish";
	$l["dutch"]="dutch";
	$l["english"]="english";
	$l["finnish"]="finnish";
	$l["french"]="french";
	$l["german"]="german";
	$l["german2"]="german2";
	$l["hungarian"]="hungarian";
	$l["italian"]="italian";
	$l["kraaij_pohlmann"]="kraaij_pohlmann";
	$l["lovins"]="lovins";
	$l["norwegian"]="norwegian";
	$l["porter"]="porter";
	$l["portuguese"]="portuguese";
	$l["romanian"]="romanian";
	$l["russian"]="russian";
	
	$WebCopyCount=$q->COUNT_ROWS("httrack_sites", "artica_backup");
	if($WebCopyCount>0){
		$sql="SELECT ID,sitename FROM httrack_sites";
		$results_webcopy = $q->QUERY_SQL($sql,"artica_backup");
		$WebCopyHash[0]="{none}";
		while ($ligneWebCopy = mysql_fetch_assoc($results_webcopy)) {
			
			$WebCopyHash[$ligneWebCopy["ID"]]=$ligneWebCopy["sitename"];
		}
		
		$WebCopyTR="<tr>
				<td class=legend nowrap style='font-size:14px'>WebCopy:</td>
				<td>". Field_array_Hash($WebCopyHash, "WebCopyID-$t",$WebCopyID,"WebCopyCheck$t()",null,0,"font-size:14px")."</td>
				<td></td>
			</tr>";			
			
		
	}
	$autofs=new autofs();
	$autofs->automounts_Browse();
	if(count($autofs->hash_by_dn)>0){
	while (list ($dn, $dnarr) = each ($autofs->hash_by_dn) ){
		$autofsDNS[null]="{none}";
		$InfosDN=$dnarr["INFOS"]["FS"];
		$autofsDNS[$dn]=$dnarr["FOLDER"]." ($InfosDN)";
	}
	
	
		$autofsTR="<tr>
				<td class=legend nowrap style='font-size:14px'>{automount}:</td>
				<td>". Field_array_Hash($autofsDNS, "autmountdn-$t",$autmountdn,"autmountdnCheck$t()",null,0,"font-size:14px")."</td>
				<td></td>
			</tr>";	
		
	}
	
	

$language=Field_array_Hash($l,"lang-$t",$lang,null,null,0,"font-size:14px");

$html="		
<div id='anime-$t'></div>
<table style='width:99%' class=form>
<tr>	
	<td class=legend style='font-size:14px' nowrap>{directory}:</strong></td>
	<td align=left>". Field_text("directory-$t",$directory,"width:280px;font-size:14px","script:SaveDirXapCheck(event)")."</strong></td>
	<td width=1%>". button("{browse}","javascript:Loadjs('browse-disk.php?field=directory-$t&replace-start-root=0');")."</td>
<tr>
$WebCopyTR
$autofsTR
<tr>
	<td class=legend style='font-size:14px' nowrap>{depth}:</strong></td>
	<td align=left style='font-size:14px'>". Field_text("depth-$t",$depth,"width:60px;font-size:14px","script:SaveDirXapCheck(event)")."&nbsp;{levels}</strong></td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px' nowrap>{maxsize}:</strong></td>
	<td align=left style='font-size:14px'>". Field_text("maxsize-$t",$maxsize,"width:60px;font-size:14px","script:SaveDirXapCheck(event)")."&nbsp;M</strong></td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px' nowrap>{sample-size}:</strong></td>
	<td align=left style='font-size:14px'>". Field_text("samplsize-$t",$samplsize,"width:60px;font-size:14px","script:SaveDirXapCheck(event)")."&nbsp;Bytes</strong></td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px' nowrap>{DiplayFullPath}:</strong></td>
	<td align=left style='font-size:14px'>". Field_checkbox("DiplayFullPath", 1,$DiplayFullPath)."</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px' nowrap>{AllowPublicDownload}:</strong></td>
	<td align=left style='font-size:14px'>". Field_checkbox("AllowDownload", 1,$AllowDownload)."</td>
	<td>&nbsp;</td>
</tr>

<tr>
	<td class=legend style='font-size:14px' nowrap>{language}:</strong></td>
	<td align=left>$language</strong></td>
	<td>&nbsp;</td>
</tr>
<tr>	
	<td colspan=3 align='right'><hr>". button("$bname","SaveDirXap$t();","18px")."</td>
<tr>
</table>
<script>

		function SaveDirXapCheck(e){
			SaveDNSCheckFields();
			if(checkEnter(e)){SaveDirXap$t();return;}
		}
		

		var x_SaveDirXap$t=function (obj) {
			var results=obj.responseText;
			document.getElementById('anime-$t').innerHTML='';
			if (results.length>3){alert(results);return;}
			$('#flexRT$t').flexReload();
		}

		function WebCopyCheck$t(){
			if(!document.getElementById('WebCopyID-$t')){return;}
			var sid=document.getElementById('WebCopyID-$t').value;
			if(sid>0){
				document.getElementById('directory-$t').disabled=true;
				document.getElementById('DiplayFullPath').disabled=true;
				document.getElementById('AllowDownload').disabled=true;
				if(document.getElementById('autmountdn-$t')){document.getElementById('autmountdn-$t').disabled=true;}
			}else{
				document.getElementById('directory-$t').disabled=false;
				document.getElementById('DiplayFullPath').disabled=false;
				document.getElementById('AllowDownload').disabled=false;		
				if(document.getElementById('autmountdn-$t')){document.getElementById('autmountdn-$t').disabled=false;}		
			}
		}
		
		function autmountdnCheck$t(){
			if(!document.getElementById('autmountdn-$t')){return;}
			var sid=document.getElementById('autmountdn-$t').value;
			if(sid.length>0){
				document.getElementById('directory-$t').disabled=true;
				document.getElementById('DiplayFullPath').disabled=true;
				document.getElementById('AllowDownload').disabled=true;
				if(document.getElementById('WebCopyID-$t')){document.getElementById('WebCopyID-$t').disabled=true;}
			}else{
				document.getElementById('directory-$t').disabled=false;
				document.getElementById('DiplayFullPath').disabled=false;
				document.getElementById('AllowDownload').disabled=false;		
				if(document.getElementById('WebCopyID-$t')){document.getElementById('WebCopyID-$t').disabled=false;}		
			}
		}
		
		function SaveDirXap$t(){
			var ok=1;
			WebCopyID=0;
			var directory=document.getElementById('directory-$t').value;
			if(document.getElementById('WebCopyID-$t')){WebCopyID=document.getElementById('WebCopyID-$t').value;}
			if(document.getElementById('autmountdn-$t')){autmountdn=document.getElementById('autmountdn-$t').value;}
			if(WebCopyID>0){directory='';}
			if(WebCopyID==0){if(directory.length==0){ok=0;}}
			if(autmountdn.length>1){if(directory.length==0){ok=1;directory='';}}
			if(ok==0){alert('$ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM');return;}
			var XHR = new XHRConnection();
			var pp=encodeURIComponent(document.getElementById('directory-$t').value);
			XHR.appendData('ID','$id');
			XHR.appendData('directory',pp);
			XHR.appendData('depth',document.getElementById('depth-$t').value);
			XHR.appendData('lang',document.getElementById('lang-$t').value);
			XHR.appendData('maxsize',document.getElementById('maxsize-$t').value);
			XHR.appendData('samplsize',document.getElementById('samplsize-$t').value);
			if(document.getElementById('WebCopyID-$t')){XHR.appendData('WebCopyID',document.getElementById('WebCopyID-$t').value);}
			if(document.getElementById('autmountdn-$t')){XHR.appendData('autmountdn',document.getElementById('autmountdn-$t').value);}
			if(document.getElementById('DiplayFullPath').checked){XHR.appendData('DiplayFullPath',1);}else{XHR.appendData('DiplayFullPath',0);}
			if(document.getElementById('AllowDownload').checked){XHR.appendData('AllowDownload',1);}else{XHR.appendData('AllowDownload',0);}
			AnimateDiv('anime-$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveDirXap$t);
		
		}
		
		WebCopyCheck$t();
		autmountdnCheck$t();
</script>

";	
					
					
	echo $tpl->_ENGINE_parse_body($html);	
}

function item_save(){
	$ID=$_POST["ID"];
	$_POST["directory"]=url_decode_special_tool($_POST["directory"]);
	if(!isset($_POST["WebCopyID"])){$_POST["WebCopyID"]=0;}
	$_POST["autmountdn"]=addslashes($_POST["autmountdn"]);
	if($_POST["directory"]==null){$_POST["directory"]=time();}
	
	
	if($ID==0){
		$sql="INSERT INTO xapian_folders (directory,depth,lang,maxsize,`sample-size`,AllowDownload,DiplayFullPath,WebCopyID,autmountdn) 
		VALUES ('{$_POST["directory"]}','{$_POST["depth"]}','{$_POST["lang"]}','{$_POST["maxsize"]}',
		'{$_POST["samplsize"]}',{$_POST["AllowDownload"]},{$_POST["DiplayFullPath"]},{$_POST["WebCopyID"]},'{$_POST["autmountdn"]}')";
		
		
	}else{
		$sql="UPDATE xapian_folders SET depth='{$_POST["depth"]}',lang='{$_POST["lang"]}',
		maxsize='{$_POST["maxsize"]}',`sample-size`='{$_POST["samplsize"]}',
		AllowDownload={$_POST["AllowDownload"]},
		DiplayFullPath={$_POST["DiplayFullPath"]},
		WebCopyID={$_POST["WebCopyID"]},
		autmountdn='{$_POST["autmountdn"]}'
		WHERE ID='$ID'";
	}
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	
	
}

function item_delete(){
	$q=new mysql();
	
	$sql="SELECT DatabasePath FROM xapian_folders WHERE ID={$_POST["delete-item"]}";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["DatabasePath"]<>null){
		$DatabasePath=base64_encode($ligne["DatabasePath"]);
		$sock=new sockets();
		$sock->getFrameWork("xapian.php?DeleteDatabasePath=$DatabasePath");
	}
	
	
	
	
	$q->QUERY_SQL("DELETE FROM xapian_folders WHERE ID='{$_POST["delete-item"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function execute(){
	$sock=new sockets();
	$sock->getFrameWork("xapian.php?exec-mysql=yes");
	
}


