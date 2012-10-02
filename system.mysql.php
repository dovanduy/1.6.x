<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',1);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.httpd.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.mysql-server.inc');
	include_once('ressources/class.mysql-multi.inc');
	
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsSystemAdministrator){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die();
	}	
	
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["members"])){members();exit;}
	if(isset($_GET["members-list"])){members_list();exit;}
	if(isset($_GET["members-delete"])){members_delete();exit;}
	if(isset($_GET["members-add"])){members_add_popup();exit;}
	if(isset($_GET["members-save"])){members_add_save();exit;}
	if(isset($_GET["mysql-dir"])){mysql_dir_popup();exit;}
	if(isset($_POST["ChangeMysqlDir"])){mysql_dir_save();exit;}
	if(isset($_GET["mysql-status"])){mysql_status();exit;}
	
	if(isset($_GET["selectDB-js"])){selectDB_js();exit;}
	if(isset($_GET["selectDB-popup"])){selectDB_popup();exit;}
	if(isset($_GET["selectDB-list"])){selectDB_list();exit;}
	if(isset($_POST["selectDB-save"])){selectDB_save();exit;}
	
	if(isset($_GET["Add-DB-js"])){Add_DB_js();exit;}
	if(isset($_POST["Add-DB-save"])){Add_DB_save();exit;}
	if(isset($_GET["text-status"])){text_status();exit;}
	
js();

function js(){
	$page=CurrentPageName();
echo "
		document.getElementById('BodyContent').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
		$('#BodyContent').load('$page?tabs=yes&tabsize={$_GET["tabsize"]}');"	;
}
function Add_DB_js(){
	$uriPlus=null;
	if(is_numeric($_GET["instance-id"])){
		$appendData="XHR.appendData('instance-id','{$_GET["instance-id"]}');";
	}	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$give_the_database_name=$tpl->javascript_parse_text("{give_the_database_name}");
	$html="
	var x_AddDBForm= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		if(document.getElementById('mysql-members-id')){LoadMysqlMembers();}
		RefreshTable$t();
	}		
	
	function AddDBForm(){
			var value=prompt('$give_the_database_name');
			if(value){
				var XHR = new XHRConnection();
				XHR.appendData('Add-DB-save',value);
				$appendData
				XHR.sendAndLoad('$page', 'POST',x_AddDBForm);
			}
	}
	AddDBForm();";
				
 echo $html;
}

function Add_DB_save(){
	$q=new mysql();
	if((is_numeric($_POST["instance-id"]) && $_POST["instance-id"]>0)){$q=new mysql_multi($_POST["instance-id"]);}
	$sql="CREATE DATABASE `{$_POST["Add-DB-save"]}`";		
	if(!$q->EXECUTE_SQL($sql)){echo "{$_POST["Add-DB-save"]}\n\n$q->mysql_error";return;}
}

function selectDB_js(){
	$uriPlus=null;
	if(is_numeric($_GET["instance-id"])){
		$uriPlus="&instance-id={$_GET["instance-id"]}";
	}	
	$page=CurrentPageName();
	$tpl=new templates();
	$title="{database_privileges}: {$_GET["host"]}/{$_GET["user"]}";
	$title=$tpl->_ENGINE_parse_body($title);
	echo "YahooWin4(600,'$page?selectDB-popup=yes&host={$_GET["host"]}&user={$_GET["user"]}&instance-id={$_GET["instance-id"]}&t={$_GET["t"]}','$title');";
}

function selectDB_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=$_GET["t"];
	$t=time();
	if(!is_numeric($_GET["instance-id"])){$_GET["instance-id"]=0;}

	$databases=$tpl->_ENGINE_parse_body("{databases}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$new_database=$tpl->_ENGINE_parse_body("{new_database}");
	$html="
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
	
<script>
var rowSquidTask='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?selectDB-list=yes&search=yes&host={$_GET["host"]}&user={$_GET["user"]}&instance-id={$_GET["instance-id"]}',
	dataType: 'json',
	colModel : [
		{display: '$enabled', name : 'enable', width : 62, sortable : true, align: 'center'},
		{display: '$databases', name : 'TaskType', width : 475, sortable : false, align: 'left'},
	],
buttons : [
	{name: '$new_database', bclass: 'add', onpress : AddNewDatabase$t},
	
		],	
	searchitems : [
		{display: '$databases', name : 'database'},
		],
	sortname: 'ID',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 150,
	showTableToggleBtn: false,
	width: 580,
	height: 350,
	singleSelect: true
	
	});   
});		

var x_SelectDBEN= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		$('#mysql-users-$tt').flexReload();
		
	}		
	
	
	function RefreshTable$t(){
		$('#$t').flexReload();
	 }	
	
	function SelectDBEN(database,md){
			var value=0;
			if(document.getElementById(md).checked){value=1;}
			var XHR = new XHRConnection();
			XHR.appendData('selectDB-save','yes');
			XHR.appendData('database',database);
			XHR.appendData('user','{$_GET["user"]}');
			XHR.appendData('host','{$_GET["host"]}');
			XHR.appendData('enable',value);
			XHR.appendData('instance-id','{$_GET["instance-id"]}');
			XHR.sendAndLoad('$page', 'POST',x_SelectDBEN);
	}
	
	function AddNewDatabase$t(){
		Loadjs('$page?Add-DB-js=yes&instance-id={$_GET["instance-id"]}&t=$t')
	}
	
		
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function selectDB_list(){
	
	
	$dbs=members_list_access_db($_GET["user"],$_GET["host"],$_GET["instance-id"]);
	$search=null;
	
	if($_POST["query"]<>null){
		$search=$_POST["query"];
		$search="*$search*";
		$search=str_replace("**", "*", $search);
		$search=str_replace("**", "*", $search);
		$search=str_replace("*", ".*?", $search);
	}
	
	$q=new mysql();
	
	
	if((is_numeric($_GET["instance-id"]) && $_GET["instance-id"]>0)){
		$q=new mysql_multi($_GET["instance-id"]);
		
	}	
	

	$data = array();
	$data['page'] = 1;$data['total'] = 0;$data['rows'] = array();	
		
	$array=$q->DATABASE_LIST();
	if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));echo json_encode($data);return;}
	$data['total']=count($array);
	$classtr=null;
	$t=time();
	
	$c=0;
	while (list ($key, $line) = each ($array) ){
		if($search<>null){if(!preg_match("#$search#i", $key)){continue;}}
		$database=$key;
		$value=0;
		if($dbs[$database]<>null){$value=1;}
		$c++;
		$md5=md5($database);
		
		$enable=Field_checkbox($md5, 1,$value,"SelectDBEN('$database','$md5')");
		
		$data['rows'][] = array(
		'id' => "DatabaseMysql".$md5,
		'cell' => array($enable,"<span style='font-size:16px'>$database</span>")
		);		
		
		}

	echo json_encode($data);	
	
}

function selectDB_save(){
	if(!isset($_POST["instance-id"])){$_POST["instance-id"]=0;}
	if(!is_numeric($_POST["instance-id"])){$_POST["instance-id"]=0;}
	
	
	 $fADD[]='INSERT INTO `db` (`Host`, `Db`, `User`, `Select_priv`, `Insert_priv`, `Update_priv`, `Delete_priv`, `Create_priv`, `Drop_priv`, `Grant_priv`,';
     $fADD[]='`References_priv`, `Index_priv`, `Alter_priv`, `Create_tmp_table_priv`, `Lock_tables_priv`, `Create_view_priv`, `Show_view_priv`,';
     $fADD[]=' `Create_routine_priv`, `Alter_routine_priv`, `Execute_priv`, `Event_priv`, `Trigger_priv`) VALUES ';
     $fADD[]='("'.$_POST["host"].'", "'.$_POST["database"].'", "'.$_POST["user"].'", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "Y", "N", "N", "Y", "Y")'; 
	 $sqladd=@implode(" ", $fADD);
	 
	 $sql_delete="DELETE FROM `db` WHERE 
	 `Host`='{$_POST["host"]}' AND `Db`='{$_POST["database"]}' AND `User`='{$_POST["user"]}'";
	 
	 $sql=$sqladd;
	 if($_POST["enable"]==0){$sql=$sql_delete;}
	 
	 writelogs("Instance: {$_POST["instance-id"]} user={$_POST["user"]} and host={$_POST["host"]}",__FUNCTION__,__FILE__,__LINE__);
	 $q=new mysql(); 
	 if($_POST["instance-id"]>0){$q=new mysql_multi($_POST["instance-id"]);}	
	
	 $q->QUERY_SQL($sql,"mysql");
	 if(!$q->ok){
	 	 writelogs("Instance: {$_POST["instance-id"]} $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
	 	echo $q->mysql_error;
	 	return;
	 }
	 
	 $q->FLUSH_PRIVILEGES();
	 
	 
}


	
function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$array["popup"]='MySQL';
	$array["parameters"]='{mysql_settings}';
	
	$array["members"]='{mysql_users}';
	$array["ssl"]='{ssl}';
	$array["globals"]='{globals_values}';
	$array["events"]='{events}';
	
	if($users->MYSQLD_MULTI_INSTALLED){
		$array["mysql-multi"]='{multiples_mysql}';
	}
	
	if($_GET["tabsize"]>10){$tabsize="style='font-size:{$_GET["tabsize"]}px'";}
	
	
	if($users->APP_GREENSQL_INSTALLED){
		$array["greensql"]='GreenSQL';
	}
	
	if(count($array)>6){$tabsize="style='font-size:12px'";}
	if(count($array)>7){$tabsize="style='font-size:12px'";}
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="greensql"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"greensql.php\"><span $tabsize>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="parameters"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"mysql.settings.php?inline=yes\"><span $tabsize>$ligne</span></a></li>\n");
			continue;			
			
		}
		if($num=="mysql-multi"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"mysql.multi.php\"><span $tabsize>$ligne</span></a></li>\n");
			continue;			
			
		}		
		
		if($num=="ssl"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.mysql.ssl.php\"><span $tabsize>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="globals"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.mysql.globals.php\"><span $tabsize>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.mysql.events.php\"><span $tabsize>$ligne</span></a></li>\n");
			continue;
		}			
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span $tabsize>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_mysql style='width:100%;'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
		  $(document).ready(function() {
			$(\"#main_config_mysql\").tabs();});
		</script>";		
}

function popup(){
		$artica=new artica_general();
		$tpl=new templates();
	    $page=CurrentPageName();
		if(preg_match('#(.+?):(.*)#',$artica->MysqlAdminAccount,$re)){
			$rootm=$re[1];
			$pwd=$re[2];
		}
		
		//$p=Paragraphe('folder-64-backup.png','{mysql_database}','{mysql_database_text}',"javascript:Loadjs('mysql.index.php')",null);
		//$i=Buildicon64('DEF_ICO_MYSQL_PWD');
		$j=Buildicon64('DEF_ICO_MYSQL_CLUSTER');
		$browse=Buildicon64("DEF_ICO_MYSQL_BROWSE");
		$changep=Buildicon64("DEF_ICO_MYSQL_USER");
		$mysqlrepair=Paragraphe('mysql-repair-64.png','{mysql_repair}','{mysql_repair_text}',"javascript:YahooWin(400,'mysql.index.php?repair-databases=yes')",null);
		
		$mysqlcheck=Paragraphe('compile-database-64.png','{mysql_defrag}','{mysql_defrag_text}',"javascript:Loadjs('mysql.optimize.php')",null);
		$mysqlRoot=Paragraphe('members-priv-64.png','{chgroot_password}','{change_root_password_text}',"javascript:Loadjs('mysql.password.php?root=yes')",null);
		
		//YahooWin(400,'artica.performances.php?main_config_mysql=yes');

		//$mysqlperformances=Paragraphe('mysql-execute-64.png','{mysql_database}','{mysql_performance_level_text}',"javascript:YahooWin(400,'artica.performances.php?main_config_mysql=yes');",null);
		$mysqlperformances=Paragraphe('folder-64-backup.png','{mysql_database}',
		'{mysql_performance_level_text}',"javascript:Loadjs('mysql.settings.php');",null);
		$mysql_benchmark=Paragraphe('mysql-benchmark-64.png','{mysql_benchmark}','{mysql_benchmark_text}',"javascript:YahooWin3(400,'artica.performances.php?MysqlTestsPerfs=yes','{mysql_benchmark}');",null);
		//$mysql_audit=Paragraphe('mysql-audit-64.png','{mysql_audit}','{mysql_audit_text}',"javascript:YahooWin3(600,'artica.settings.php?mysql-audit=yes');",null);
		$movefolder=Paragraphe('folder-64.png','{storage_directory}',
		'{change_mysql_directory_text}',"javascript:YahooWin3(405,'$page?mysql-dir=yes','{storage_directory}');",null);
		
		$mysql_appliance=Paragraphe("www-web-search-64.png","{statistics_appliance}","{statistics_appliance_text}","javascript:Loadjs('statistics.appliance.php')");
		
		$tmpfile=Paragraphe("bg_memory-64.png", "{mysql_tmp_mem}", "{mysql_tmp_mem_text}","javascript:Loadjs('system.mysql.tmpdir.php')");
		
		$tr[]=$p;
		$tr[]=$tmpfile;
		$tr[]=$mysqlcheck;
		$tr[]=$mysqlrepair;
		//$tr[]=$mysqlperformances;
		$tr[]=$i;
		$tr[]=$mysqlRoot;
		$tr[]=$changep;
		$tr[]=$browse;
		$tr[]=$movefolder;
		$tr[]=$j;
		$tr[]=$mysql_benchmark;
		$tr[]=$mysql_audit;
		$tr[]=$mysql_appliance;

	$tables[]="<table style='width:470px'><tr>";
	$t=0;
	while (list ($key, $line) = each ($tr) ){
			$line=trim($line);
			if($line==null){continue;}
			$t=$t+1;
			$tables[]="<td valign='top'>$line</td>";
			if($t==2){$t=0;$tables[]="</tr><tr>";}
			}
	
	if($t<2){
		for($i=0;$i<=$t;$i++){
			$tables[]="<td valign='top'>&nbsp;</td>";				
		}
	}	
	
$t=time();	
$html="
<center>
<div style='width:720px'>
<table style='width:99%' class=form>
<tr>
<td valign='top' width=1%><div id='mysql-status' style='width:250px'></div></td>
<td valign='top' width=99%><div id='$t'></div>
	". implode("\n",$tables)."
	</td>
	</tr>
</table>
	
	</div>
</center>
	<script>
	LoadAjax('mysql-status','$page?mysql-status=yes');
	LoadAjax('$t','$page?text-status=yes');
	</script>
	
	";

	$tpl=new templates();
	$datas=$tpl->_ENGINE_parse_body($html,"postfix.plugins.php");	
	echo $datas;
	
	
}

function mysql_status(){
	$users=new usersMenus();
	
	
	$phpmyadminsecure=Paragraphe("Firewall-Secure-64-grey.png", "{protect_phpmyadmin}", "{protect_phpmyadmin_text}","javascript:Loadjs('phpmyadmin.protect.php')",null,220);
	
	
	
	if($users->phpmyadmin_installed){
		$phpmyadminsecure=Paragraphe("Firewall-Secure-64.png", "{protect_phpmyadmin}", "{protect_phpmyadmin_text}","javascript:Loadjs('phpmyadmin.protect.php')",null,220);
	}
	
	$graphs=Paragraphe("statistics-64.png", "{mysql_graphs}", "{mysql_graphs_text}","javascript:Loadjs('system.mysql.graphs.php')",null,220);
	$specific=Paragraphe("script-64.png", "{mysql_perso_conf}", "{mysql_perso_conf_text}","javascript:Loadjs('system.mysql.perso.php')",null,220);
	$refresh="<div style='text-align:right;margin-top:8px'>".imgtootltip("refresh-24.png","{refresh}","RefreshTab('main_config_mysql')")."</div>";
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();	
	$datas=$sock->getFrameWork("services.php?mysql-status=yes");
	writelogs(strlen($datas)." bytes for mysql status",__CLASS__,__FUNCTION__,__FILE__,__LINE__);
	$ini->loadString(base64_decode($datas));
	$status=DAEMON_STATUS_ROUND("ARTICA_MYSQL",$ini,null,0)."<br>".DAEMON_STATUS_ROUND("MYSQL_CLUSTER_MGMT",$ini,null,0).$refresh."<br><center>$specific<br>$graphs<br>$phpmyadminsecure</center>";
	echo $tpl->_ENGINE_parse_body($status);	
	
}


function members(){
	
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$add_user=$tpl->javascript_parse_text("{add_user}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$databases=$tpl->_ENGINE_parse_body("{databases}");	
	$member=$tpl->_ENGINE_parse_body("{member}");
	$delete_alert=$tpl->javascript_parse_text("{delete}");
	$t=time();
	
	
	$q=new mysql();
	$uriPlus=null;
	if(is_numeric($_GET["instance-id"])){
		$uriPlus="&instance-id={$_GET["instance-id"]}";
		$q=new mysql_multi($_GET["instance-id"]);
		$mmultiTitle="$q->MyServer&raquo;";		
	}
	$title=$tpl->javascript_parse_text("{hostname} ($q->mysql_server:$q->mysql_port)/{username}");	
	if(!is_numeric($_GET["instance-id"])){$_GET["instance-id"]=0;}


	$buttons="
	buttons : [
		{name: '<b>$add_user</b>', bclass: 'add', onpress : AddMysqlUser$t},
	],";

	$html="
	<table class='mysql-users-$t' style='display: none' id='mysql-users-$t' style='width:100%;margin:-10px'></table>
<script>
memedb$t='';
$(document).ready(function(){
$('#mysql-users-$t').flexigrid({
	url: '$page?tables-list=yes&members-list=yes&t=$t$uriPlus',
	dataType: 'json',

	colModel : [
		{display: '$member', name : 'User', width : 417, sortable : true, align: 'left'},
		{display: '$databases', name : 'database', width :336, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'del', width :31, sortable : true, align: 'left'},
	],	
	
	$buttons

	searchitems : [
		{display: '$member', name : 'User'},
		
		],
	sortname: 'User',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 845,
	height: 400,
	singleSelect: true
	
	});   
});

	function AddMysqlUser$t(){
		YahooWin('443','$page?members-add=yes&instance-id={$_GET["instance-id"]}&t=$t','$add_user');
	}

	var x_DeleteMysqlUser= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		$('#row'+memedb$t).remove();
	}		
	
	function DeleteMysqlUser$t(arra,user,md){
		memedb$t=md;
		if(confirm('$delete_alert '+user+' ?')){
			var XHR = new XHRConnection();
			XHR.appendData('members-delete',arra);
			XHR.appendData('instance-id','{$_GET["instance-id"]}');
			XHR.sendAndLoad('$page', 'GET',x_DeleteMysqlUser);
			}
	}		

</script>
";	
echo $html;
	
	
}



function members_list(){
	$page=1;
	$MyPage=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();	
	$sock=new sockets();	
	$q=new mysql();
	$table="user";
	$tableOrg=$table;
	$database="mysql";
	$instance_id=$_GET["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}	
	if($instance_id>0){$q=new mysql_multi($instance_id);}	
	$FORCE_FILTER=1;
	$t=$_GET["t"];
	
	
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("$table/$database is empty");}
	
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
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error <strong>$sql</strong>");}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error <strong>$sql</strong>");}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE  $FORCE_FILTER $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("Query return empty array, $sql, ($q->mysql_error)");}
	
	$mysqlD=new mysqlserver();
	$griseNoIp=false;
	if($mysqlD->main_array["skip_name_resolve"]=="yes"){$griseNoIp=true;}	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$password=$ligne["Password"];
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$array=array("host"=>$ligne["Host"],"user"=>$ligne["User"]);
		$databaseText=null;
		
		$dbs=members_list_access_db($ligne["User"],$ligne["Host"],$_GET["instance-id"]);
		if(count($dbs)==0){
			$databaseText="<span style='color:#9B9999'>{no_database_selected}</span>";
		}else{
			while (list ($key, $line) = each ($dbs) ){
				$databaseText=$databaseText."$key, ";
			}
		}
		
	$color="black";
	if($griseNoIp){
		if($ligne["Host"]<>"%"){
			if($ligne["Host"]<>"localhost"){
				if(!preg_match("#^[0-9\%]+\.[0-9\%]+\.[0-9\%]+#", $ligne["Host"])){
				$color="#9B9999";
				}
			}
		}
	}
	$ligne["Host"]=str_replace("%","{all}",$ligne["Host"]);
	$databaseText=$tpl->_ENGINE_parse_body($databaseText);
	$md5S=md5("{$ligne["User"]}@{$ligne["Host"]}$databaseText");
	$delete=imgsimple("delete-32.png","{delete}","DeleteMysqlUser$t('". base64_encode(serialize($array))."','{$ligne["User"]}@{$ligne["Host"]}','$md5S')");	
	
	
	$data['rows'][] = array(
		'id' => $md5S,
		'cell' => array(
					"<code style='font-size:14px;color:$color'>{$ligne["User"]}@{$ligne["Host"]}</code>",
					"<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?selectDB-js=yes&host={$ligne["Host"]}&user={$ligne["User"]}&instance-id={$_GET["instance-id"]}&t=$t')\"
			style='font-size:12px;font-weight:bold;text-decoration:underline;color:$color'
			>$databaseText</a>",
			$delete
			)
		);		
		

		}

	echo json_encode($data);		

	
}

function members_list_access_db($user,$server,$instance_id=0){
	$dbs=array();
	$sql="SELECT `Db` FROM `db` WHERE `User`='$user' AND `Host`='$server'";
	$q=new mysql();
	writelogs("Instance: {$_GET["instance-id"]} user=$user and host=$server",__FUNCTION__,__FILE__,__LINE__);
	if($instance_id>0){$q=new mysql_multi($instance_id);}
	
	$results=$q->QUERY_SQL($sql,"mysql");
	
	if(!$q->ok){echo $q->mysql_error;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$dbs[$ligne["Db"]]=$ligne["Db"];
		
	}
	
	return $dbs;
	
}

function members_delete(){
	
	$array=unserialize(base64_decode($_GET["members-delete"]));
	if(!is_array($array)){return;}
	$sql="DROP USER '{$array["user"]}'@'{$array["host"]}';";
	$q=new mysql();
	if((is_numeric($_GET["instance-id"]) && $_GET["instance-id"]>0)){$q=new mysql_multi($_GET["instance-id"]);}	
	
	if(!$q->EXECUTE_SQL($sql)){
		echo "user:{$array["user"]}\nHost:{$array["host"]}\n\n$q->mysql_error";
	}
	
}


function members_add_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];

	$fieldserver= Field_text("servername",$servername,"font-size:16px;padding:3px");
	$mysqlD=new mysqlserver();
	
	if($mysqlD->main_array["skip_name_resolve"]=="yes"){
		$fieldserver=field_ipv4("servername", $servername,"font-size:16px;");
	}
	
	
	$html="
	<div id='memberdiv$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px;'>{server}:</td>
		<td>$fieldserver</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px;'>{username}:</td>
		<td>". Field_text("username",$username,"font-size:16px;padding:3px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px;'>{password}:</td>
		<td>". Field_password("password",$password,"font-size:16px;padding:3px")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{add}","EditMysqlUser()",16)."</td>
	</tr>
	</table>
	
	<script>
	var x_EditMysqlUser= function (obj) {
		var results=obj.responseText;
		document.getElementById('memberdiv$t').innerHTML='';
		if(results.length>0){alert(results);return;}
		$('#mysql-users-$t').flexReload();
		if(document.getElementById('mysqlS-member-$t')){
			$('#mysqlS-member-$t').flexReload();
		}
		
		
		YahooWinHide();
	}		
	
	function EditMysqlUser(){
		var XHR = new XHRConnection();
		XHR.appendData('instance-id','{$_GET["instance-id"]}');
		XHR.appendData('members-save','yes');
		XHR.appendData('servername',document.getElementById('servername').value);
		XHR.appendData('username',document.getElementById('username').value);
		XHR.appendData('password',base64_encode(document.getElementById('password').value));
		AnimateDiv('memberdiv$t');
		XHR.sendAndLoad('$page', 'GET',x_EditMysqlUser);
	}		
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function members_add_save(){
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."<br>";}
	$server=trim($_GET["servername"]);
	$username=trim($_GET["username"]);
	$password=trim(base64_decode($_GET["password"]));
	if($server=="*"){$server="%";}
	if($GLOBALS["VERBOSE"]){echo __LINE__." ->mysql()<br>";}
	$q=new mysql();
	if((is_numeric($_GET["instance-id"]) && $_GET["instance-id"]>0)){$q=new mysql_multi($_GET["instance-id"]);}	
	
	$OrginalPassword=$q->mysql_password;
	$sql="SELECT User FROM user WHERE Host='$server' AND User='$username'";
	if($GLOBALS["VERBOSE"]){echo $sql."<br>";}
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"mysql"));
	if($GLOBALS["VERBOSE"]){echo "User:{$ligne["User"]}<br>";}
	if(trim($ligne["User"])==null){
		$sql="CREATE USER '$username'@'$server' IDENTIFIED BY '$password';";
		if($GLOBALS["VERBOSE"]){echo $sql."<br>";}
		if(!$q->EXECUTE_SQL($sql)){
			$q->mysql_admin="root";
			$q->mysql_password=$OrginalPassword;
			if(!$q->EXECUTE_SQL($sql)){
				$q->mysql_admin="root";
				$q->mysql_password=null;	
				if(!$q->EXECUTE_SQL($sql)){
					echo "CREATE USER user:$username\nHost:$server\n\n$q->mysql_error";return;}
				}			
			}
			
	}
	
	$sql="GRANT ALL PRIVILEGES ON * . * TO '$username'@'$server' IDENTIFIED BY '$password' WITH GRANT OPTION MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0";
	if($GLOBALS["VERBOSE"]){echo $sql."<br>";}
	if(!$q->EXECUTE_SQL($sql)){
		$q->mysql_admin="root";
		$q->mysql_password=$OrginalPassword;
		$q->ok=true;
		if(!$q->EXECUTE_SQL($sql)){
			$q->mysql_admin="root";
			$q->mysql_password=null;
			$q->ok=true;	
			if(!$q->EXECUTE_SQL($sql)){
				echo "user GRANT ALL PRIVILEGES ON:$username\nHost:$server\n\n$q->mysql_error";return;
			}
		}			
	}
		
		
		
		
	
}

function mysql_dir_popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$ChangeMysqlDir=$sock->GET_INFO("ChangeMysqlDir");
	if($ChangeMysqlDir==null){$ChangeMysqlDir="/var/lib/mysql";}
	
	$html="
	<div id='ChangeMysqlDirDiv'>
	<div class=explain>{ChangeMysqlDir_explain}</div>
	<p>&nbsp;</p>
	<table style='width:100%'>
	<tr>
		<td class=legend>{directory}:</td>
		<td>". Field_text("ChangeMysqlDir",$ChangeMysqlDir,"font-size:16px;padding:3px;width:220px")."</td>
		<td><input type='button' value='{browse}...' OnClick=\"Loadjs('SambaBrowse.php?no-shares=yes&field=ChangeMysqlDir')\"></td>
	</tr>
	<tr>
		<td colspan=3 align='right'>
			<hr>". button("{apply}","SaveChangeMysqlDir()")."</td>
	</tr>
	</table>
	<script>
		var x_SaveChangeMysqlDir= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			YahooWin3Hide();
		}
				
		function SaveChangeMysqlDir(){
			var XHR = new XHRConnection();
			XHR.appendData('ChangeMysqlDir',document.getElementById('ChangeMysqlDir').value);
			document.getElementById('ChangeMysqlDirDiv').innerHTML='<center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center>';
			XHR.sendAndLoad('$page', 'POST',x_SaveChangeMysqlDir);	
		}	
	</script>
	</div>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function mysql_dir_save(){
	$sock=new sockets();
	$sock->SET_INFO("ChangeMysqlDir",$_POST["ChangeMysqlDir"]);
	$sock->getFrameWork("cmd.php?ChangeMysqlDir=yes");
}
function text_status(){
	$q=new mysql();
	$sql="SELECT SUM(dbsize) as tsize FROM mysqldbs";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$size=FormatBytes($ligne["tsize"]/1024);
	
	$mysql=new mysqlserver();
	$html="<div style='font-size:16px'>v. $mysql->mysql_version_string ($mysql->mysqlvbin) {size}:$size</div>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}
?>