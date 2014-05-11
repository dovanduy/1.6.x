<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($argv[1]=="verbose"){echo "Verbosed\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.computers.inc');
	include_once('ressources/class.mysql-server.inc');
	include_once('ressources/class.mysql-multi.inc');
		
	
	$user=new usersMenus();
	if(!$GLOBALS["EXECUTED_AS_ROOT"]){
	if(($user->AsSystemAdministrator==false) OR ($user->AsSambaAdministrator==false)) {
		$tpl=new templates();
		$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		$text=replace_accents(html_entity_decode($text));
		echo "alert('$text');";
		exit;
		}
	}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["params"])){params();exit;}
	if(isset($_POST["database"])){save();exit;}
	if(isset($_GET["containers"])){containers_table();exit;}
	if(isset($_GET["containers-items"])){containers_items();exit;}
	if(isset($_POST["remove-file"])){containers_delete();exit;}
	

js();	

		
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$database=$_GET["database"];
	$title=$tpl->_ENGINE_parse_body("{backup}::$database","mysql.index.php");
	
	if((is_numeric($_GET["instance-id"]) && $_GET["instance-id"]>0)){
		$TitleInstance=$tpl->_ENGINE_parse_body("{instance}:{$_GET["instance-id"]}&raquo;{backup}::$database");
	}
	
	echo "YahooWin6('650','$page?tabs=yes&instance-id={$_GET["instance-id"]}&database=$database','$title');";
	
	
}

function containers_table(){
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=450;
	$TB_WIDTH=600;
	$uid=$_GET["uid"];
		
	$t=time();
	$filename=$tpl->javascript_parse_text("{filename}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$account=$tpl->_ENGINE_parse_body("{account}");
//	$title=$tpl->_ENGINE_parse_body("$attachments_storage {items}:&nbsp;&laquo;$size&raquo;");
	$filessize=$tpl->_ENGINE_parse_body("{filesize}");
	$action_delete_rule=$tpl->javascript_parse_text("{action_delete_rule}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$error_want_operation=$tpl->javascript_parse_text("{error_want_operation}");
	$events=$tpl->javascript_parse_text("{events}");
	$category=$tpl->javascript_parse_text("{category}");
	$title=$tpl->javascript_parse_text("{video_title}");
	$size=$tpl->javascript_parse_text("{size}");
	$duration=$tpl->javascript_parse_text("{duration}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$remove=$tpl->javascript_parse_text("{remove}");
	
	$buttons="
	
	// mysqldb_backup_containers (`md5`,`fullpath`,`duration`,`zDate`,`size`)
	
	
	buttons : [
	
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	
	],	";
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?containers-items=yes&t=$t&instance-id={$_GET["instance-id"]}&database={$_GET["database"]}',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width :82, sortable : true, align: 'left'},
		{display: '$filename', name : 'fullpath', width :180, sortable : true, align: 'left'},	
		{display: '$duration', name : 'duration', width :118, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width :93, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width :38, sortable : true, align: 'center'},
		
	
	],
	$buttons

	searchitems : [
		{display: '$filename', name : 'fullpath'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=\"title-$t\"></span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function ItemHelp$t(){
	s_PopUpFull('http://mail-appliance.org/index.php?cID=371','1024','900');
}

	var x_FileNameMsqlDelete= function (obj) {
	 		text=obj.responseText;
	 		if(text.length>3){alert(text);return;}
	 		$('#row'+mem$t).remove();
	 		
		}

function FileNameMsqlDelete(filename,md){
	mem$t=md;
	if(confirm('$remove\\n'+filename+' ?')){
			var XHR = new XHRConnection();
			XHR.appendData('remove-file',filename);	
			XHR.sendAndLoad('$page', 'POST',x_FileNameMsqlDelete);
		}

}


</script>";
	
	echo $html;
}

function containers_delete(){
	$filename=$_POST["remove-file"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM mysqldb_backup_containers WHERE fullpath='$filename'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?file-remove=".base64_encode($filename));
}

function containers_items(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$users=new usersMenus();
	$sock=new sockets();
	
	$database=$_GET["database"];
	$instance_id=$_GET["instance-id"];	
	$md5=md5("$database$instance_id");
	
	$FORCE_FILTER=" AND `md5`='$md5'";
	$search='%';
	$table="mysqldb_backup_containers";
	$database="artica_backup";
	$page=1;
	
	
	if(!$q->TABLE_EXISTS("mysqldb_backup_containers","artica_backup")){$q->check_storage_table(true);}
	if(!$q->TABLE_EXISTS("mysqldb_backup_containers", "artica_backup")){json_error_show("$database: $table doesn't exists...");}
	if($q->COUNT_ROWS("mysqldb_backup_containers", "artica_backup")==0){json_error_show("$database: $table: No data");}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	

	while ($ligne = mysql_fetch_assoc($results)) {
	$zmd5=md5(serialize($ligne));
	
	$color="black";
	$delete=imgsimple("delete-24.png",null,"FileNameMsqlDelete('{$ligne["fullpath"]}','$zmd5')");
	$size=$sock->getFrameWork("cmd.php?filesize=".base64_encode($ligne["fullpath"]));
	if($size>0){	
		if($size<>$ligne["size"]){
			$ligne["size"]=$size;
			$q->QUERY_SQL("UPDATE mysqldb_backup_containers SET `size`='$size' WHERE fullpath='{$ligne["fullpath"]}'","artica_backup");
		}
	}else{
		$color="#787373";
	}
	
	
	$mountpoint=urlencode(dirname($ligne["fullpath"]));
	
	$urljsSIT="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:Loadjs('tree.php?mount-point=$mountpoint');\"
	style='font-size:14px;text-decoration:underline;color:$color'>";
	
	
	$ligne["size"]=FormatBytes($ligne["size"]/1024);
	$ligne["fullpath"]=basename($ligne["fullpath"]);
	
	
	
	$data['rows'][] = array(
		'id' => "$zmd5",
		'cell' => array(
			"<span style='font-size:14px;color:$color'>$urljsSIT{$ligne["zDate"]}</a></span>",
			"<span style='font-size:14px;color:$color'>$urljsSIT{$ligne["fullpath"]}</a></span>",
			"<span style='font-size:14px;color:$color'>$urljsSIT{$ligne["duration"]}</span>",
			"<span style='font-size:14px;color:$color'>{$ligne["size"]}</span>",
			"<span style='font-size:14px;color:$color'>$delete</span>",
			)
		);
	}
	
	
echo json_encode($data);		
		
	
}


function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$database=$_GET["database"];
	if(!is_numeric($_GET["instance-id"])){$_GET["instance-id"]=0;}
	$instance_id=$_GET["instance-id"];
	$array["params"]="{backup_parameters}";
	if(!$q->TABLE_EXISTS("mysqldb_backup_containers","artica_backup")){$q->check_storage_table(true);}
	if(!$q->TABLE_EXISTS("mysqldb_backup_containers","artica_backup")){echo "mysqldb_backup_containers no such table....\n";}
	$md5=md5("$database$instance_id");
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM mysqldb_backup_containers WHERE `md5`='$md5'","artica_backup"));
	if(!$q->ok){
		echo $q->mysql_error."<hr>";
	}
	if($ligne["tcount"]>0){
		$array["containers"]="{$ligne["tcount"]} {containers}";
	}
	
	while (list ($num, $ligne) = each ($array) ){
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&instance-id={$_GET["instance-id"]}&database=$database\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_mysql_backup style='width:100%;font-size:14px'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_mysql_backup').tabs();
				});
		</script>";			
}

function params(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$database=$_GET["database"];
	$instance_id=$_GET["instance-id"];
	$md5=md5("$database$instance_id");
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM mysqldb_backup WHERE `md5`='$md5'","artica_backup"));
	$t=time();
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=0;}
	if(!is_numeric($ligne["compress"])){$ligne["compress"]=1;}
	if(!is_numeric($ligne["MaxDay"])){$ligne["MaxDay"]=90;}
	$md5=md5("$database$instance_id");
	$html="
	<div id='$t'>MD:$md5</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{enabled}:</td>
		<td>". Field_checkbox("enabled-$t",1,$ligne["enabled"],"CheckEnabled$t()")."</td>
		<td width=1%>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{compress_container}:</td>
		<td>". Field_checkbox("compress-$t",1,$ligne["compress"])."</td>
		<td width=1%>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{backup_directory}:</td>
		<td>". Field_text("targetDir-$t",$ligne["targetDir"],"width:220px")."</td>
		<td width=1%>". button("{browse}","Loadjs('browse-disk.php?field=targetDir-$t')")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{MaxDays}:</td>
		<td>". Field_text("MaxDay-$t",$ligne["MaxDay"],"width:90px")."</td>
		<td width=1% style='font-size:14px'>&nbsp;{days}</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","Save$t()","18px")."</td>
	</tr>	
</table>

<script>
	function CheckEnabled$t(){
		document.getElementById('compress-$t').disabled=true;
		document.getElementById('targetDir-$t').disabled=true;
		document.getElementById('MaxDay-$t').disabled=true;
		if(document.getElementById('enabled-$t').checked){
			document.getElementById('compress-$t').disabled=false;
			document.getElementById('targetDir-$t').disabled=false;
			document.getElementById('MaxDay-$t').disabled=false;		
		}
	
	
	}
	
	var x_Save$t= function (obj) {
	 	text=obj.responseText;
	 	document.getElementById('$t').innerHTML='';
	 	if(text.length>3){alert(text);return;}
	 	
		}

	function Save$t(){
		var compress=0;
		var enabled=0;
		if(document.getElementById('compress-$t').checked){compress=1;}
		if(document.getElementById('enabled-$t').checked){enabled=1;}
		  var XHR = new XHRConnection();
		  XHR.appendData('database','$database');
		  XHR.appendData('instance-id','$instance_id');
		  XHR.appendData('compress',compress);
		  XHR.appendData('enabled',enabled);
		  XHR.appendData('targetDir',document.getElementById('targetDir-$t').value);
		  XHR.appendData('MaxDay',document.getElementById('MaxDay-$t').value);
		  AnimateDiv('$t');
		  XHR.sendAndLoad('$page', 'POST',x_Save$t);
	}


 CheckEnabled$t();
</script>


";

	echo $tpl->_ENGINE_parse_body($html);
	
}

function save(){
	$database=$_POST["database"];
	$instance_id=$_POST["instance-id"];
	$md5=md5("$database$instance_id");
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM mysqldb_backup WHERE `md5`='$md5'","artica_backup"));	
	if($ligne["md5"]<>null){
		$sql="UPDATE mysqldb_backup SET `compress`={$_POST["compress"]},
		MaxDay={$_POST["MaxDay"]},
		enabled={$_POST["enabled"]},
		targetDir='{$_POST["targetDir"]}' WHERE `md5`='$md5'";
		
		
	}else{
		$sql="INSERT INTO mysqldb_backup ( `md5`,`enabled`,`compress`,`MaxDay`,`targetDir`,`database`,`InstanceID`)
		VALUES ('$md5','{$_POST["enabled"]}','{$_POST["compress"]}','{$_POST["MaxDay"]}','{$_POST["targetDir"]}','{$_POST["database"]}','$instance_id')";
		
	}
	
	$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){echo $q->mysql_error;}
	
	
}

