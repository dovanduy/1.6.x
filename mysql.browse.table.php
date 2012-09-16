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
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["popup-start"])){popup_start();exit;}
	if(isset($_GET["rows-list"])){rows_list_json();exit;}
	if(isset($_GET["sql-query"])){sql_query_form();exit;}
	if(isset($_GET["preperf-sql"])){sql_query_js();exit;}
	if(isset($_POST["OPTIMIZE"])){OPTIMIZE();exit;}
js();	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{browse_mysql_server}',"mysql.index.php");
	$tables_list=$tpl->_ENGINE_parse_body('{tables_list}',"mysql.index.php");
	$prefix=str_replace(".","_",$page);
	if(!is_numeric($_GET["instance-id"])){$_GET["instance-id"]=0;}
	
if((is_numeric($_GET["instance-id"]) && $_GET["instance-id"]>0)){
		$q2=new mysql_multi($_GET["instance-id"]);
		$mmultiTitle="$q2->MyServer&raquo;";
	}	
	
	$html="
	Loadjs('js/base64.js');
	YahooWin6('910','$page?popup=yes&table={$_GET["table"]}&database={$_GET["database"]}&instance-id={$_GET["instance-id"]}','$mmultiTitle$title::{$_GET["database"]}::{$_GET["table"]}');
	";
	
echo $html;	
}

function popup(){
	$page=CurrentPageName();
	$t=time();
	$html="
	
	<input type='hidden' id='sql-query-$t' value=''>
	<div id='$t'></div>
	
	<script>
		LoadAjax('$t','$page?popup-start=yes&t=$t&table={$_GET["table"]}&database={$_GET["database"]}&instance-id={$_GET["instance-id"]}');
		
		function SqlQuery$t(){
			YahooWinBrowse(600,'$page?sql-query=yes&t=$t&table={$_GET["table"]}&database={$_GET["database"]}&instance-id={$_GET["instance-id"]}','SQL');
		
		}
		
		
	</script>
	";
	
	echo $html;
	
	
}

function sql_query_js(){
	$tpl=new templates();
	$sql=trim(base64_decode($_GET["sql"]));
	$sqlorg=$sql;
	$t=$_GET["t"];	
	$table=$_GET["table"];
	$database=$_GET["database"];	
	$q=new mysql();
	$page=CurrentPageName();
	$instance_id=$_GET["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}
	
	if(!preg_match("#^SELECT#i", $sql,$re)){
		echo "alert('ONLY `SELECT` QUERY IS ALLOWED');";
		return;
	}
	
	if(preg_match("#(.*?)LIMIT\s+#is", $sql,$re)){
		$sql=$re[1];
		$sqlorg=$sql;
		$_GET["sql"]=base64_encode($sql);
	}
	if(preg_match("#ORDER BY (.+?) (asc|desc)#is", $sql,$re)){
		$sql=str_ireplace("ORDER BY {$re[1]} {$re[2]}", "", $sql);
		$sqlorg=$sql;
		$_GET["sql"]=base64_encode($sql);
	}	
	
	if($instance_id>0){
		$q=new mysql_multi($instance_id);
	}
	$results=$q->QUERY_SQL($sql." LIMIT 0,1",$database);
	
	
	
	if(!$q->ok){
		
		$q->mysql_error=str_replace("'", "`", $q->mysql_error);
		$q->mysql_error=str_replace("\n", "\\n", $q->mysql_error);
		echo "alert('$q->mysql_error\\n$sql')";
		return;
	}
	$len = mysql_num_fields($results);
	$sizeF=round(820/$len);
	for ($i = 0; $i < $len; $i++) {
		$name = mysql_field_name($results, $i);
		$ff[]=$name;
		$fields[$name]=true;
		$cols[]="{display: '{$name}', name : '$name', width : $sizeF, sortable : true, align: 'left'}";
		$ss[]="{display: '$name', name : '$name'}";		
	} 	
	
	
	$QUERY_ARRAY=array(
		"FF"=>$ff,"fields"=>$fields,"cols"=>$cols,"ss"=>$ss
	
	);

	$QUERY_ARRAY_ENCODED=base64_encode(serialize($QUERY_ARRAY));
	echo "
	document.getElementById('sql-query-$t').value='{$_GET["sql"]}';
	LoadAjax('$t','$page?popup-start=yes&t=$t&table={$_GET["table"]}&database={$_GET["database"]}&instance-id={$_GET["instance-id"]}&QUERY_ARRAY_ENCODED=$QUERY_ARRAY_ENCODED&sql={$_GET["sql"]}');";
	
	
	
	
	
	
}

function OPTIMIZE(){
	$page=CurrentPageName();
	$tpl=new templates();
	$table=$_POST["OPTIMIZE"];
	$database=$_POST["database"];		
	$q=new mysql();
	$instance_id=$_POST["instance_id"];
	if(!is_numeric($instance_id)){$instance_id=0;}
	if($instance_id>0){$q=new mysql_multi($instance_id);}
	
	
	$sql="OPTIMIZE TABLE `$table`";
	$q->QUERY_SQL("OPTIMIZE TABLE $table",$database);
	if(!$q->ok){echo $q->mysql_error."\n$sql";}
	echo $tpl->javascript_parse_text("{success}");
}


function sql_query_form(){
	$page=CurrentPageName();
	$tpl=new templates();
	$table=$_GET["table"];
	$database=$_GET["database"];	
	$instance_id=$_GET["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}
	$q=new mysql();
	if($instance_id>0){$q=new mysql_multi($instance_id);}
	$results=$q->QUERY_SQL("SHOW COLUMNS FROM $table",$database);
	
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$ff[]=$ligne["Field"];
			
		}	
	
	$t=$_GET["t"];
	$html="
	<div><code style='font-size:14px;'>Table &laquo;$table&raquo; fields:". @implode(", ", $ff)."</code></div>
	<textarea id='pattern-sql-$t' style='font-size:16px;margin-top:10px;margin-bottom:10px;
		font-family:\"Courier New\",Courier,monospace;padding:3px;border:3px solid #5A5A5A;font-weight:bolder;color:#5A5A5A;
		width:100%;height:120px;overflow:auto'>{$ligne["pattern"]}
	</textarea>
	
	<div style='width:100%;text-align:right'>". button("{submit}","PerformSqlQuery$t()",16)."</div>
	
	<script>
		function LoadSql$t(){
		var MySQL=Base64.decode(document.getElementById('sql-query-$t').value);
		var xdefault='SELECT * FROM  {$_GET["table"]}';
		if(MySQL.length<5){MySQL=xdefault;}
		document.getElementById('pattern-sql-$t').value=MySQL;
		
		}
		
		function PerformSqlQuery$t(){
			var MySQL=Base64.encode(document.getElementById('pattern-sql-$t').value);
			if(MySQL.length<5){return;}
			Loadjs('$page?preperf-sql=yes&sql='+MySQL+'&table={$_GET["table"]}&database={$_GET["database"]}&instance-id={$_GET["instance-id"]}&t=$t');
		}
		
		
		LoadSql$t();
	</script>
	
		";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function popup_start(){
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$table=$_GET["table"];
	$database=$_GET["database"];
	$instance_id=$_GET["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}
	$t=$_GET["t"];
	$startpoint=null;
	$q=new mysql();
	if($instance_id>0){$q=new mysql_multi($instance_id);}
	
	if(!isset($_GET["QUERY_ARRAY_ENCODED"])){
		$results=$q->QUERY_SQL("SHOW COLUMNS FROM $table",$database);
		$sizeF=round(820/mysql_num_rows($results));
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$ff[]=$ligne["Field"];
			if($ligne["Key"]=="PRI"){$startpoint=$ligne["Field"];}
			$fields[$ligne["Field"]]=true;
			$cols[]="{display: '{$ligne["Field"]} ({$ligne["Key"]})', name : '{$ligne["Field"]}', width : $sizeF, sortable : true, align: 'left'}";
			$ss[]="{display: '{$ligne["Field"]}', name : '{$ligne["Field"]}'}";
		}
		
	}else{
		$QUERY_ARRAY_ENCODED=unserialize(base64_decode($_GET["QUERY_ARRAY_ENCODED"]));
		$ff=$QUERY_ARRAY_ENCODED["FF"];
		$fields=$QUERY_ARRAY_ENCODED["fields"];
		$cols=$QUERY_ARRAY_ENCODED["cols"];
		$ss=$QUERY_ARRAY_ENCODED["ss"];
	}
	
	if($startpoint==null){$startpoint=$ff[0];}
	$countdefileds=count($ff);
	$fieldsEncoded=base64_encode(serialize($fields));
	$buttons="
	buttons : [
	{name: '<b>SQL</b>', bclass: 'SSQL', onpress :SqlQuery$t},
	{name: '<b>OPTIMIZE</b>', bclass: 'SSQLOptiz', onpress :SqlOptimize$t},
	],";
	
	$html="
	<center id='anim-$t'></center>
	<table class='mysql-table-$t' style='display: none' id='mysql-table-$t' style='width:100%;margin:-10px'></table>
<script>
memedb='';
$(document).ready(function(){
$('#mysql-table-$t').flexigrid({
	url: '$page?rows-list=yes&t=$t&table={$_GET["table"]}&database={$_GET["database"]}&instance-id={$_GET["instance-id"]}&fields=$fieldsEncoded&sql={$_GET["sql"]}',
	dataType: 'json',
	colModel : [
		". @implode("\n,", $cols)."
	],
	
	$buttons

	searchitems : [
		". @implode("\n,", $ss)."
		
		],
	sortname: '$startpoint',
	sortorder: 'desc',
	usepager: true,
	title: '{$_GET["database"]}::{$_GET["table"]} ($countdefileds fields)',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 897,
	height: 550,
	singleSelect: true
	
	});   
});

	var x_SqlOptimize$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert('\"'+results+'\"');}
			document.getElementById('anim-$t').innerHTML='';
		}		
function SqlOptimize$t(){
	if(confirm('OPTIMIZE: {$_GET["database"]}/{$_GET["table"]} ?')){
		var XHR = new XHRConnection();
		XHR.appendData('OPTIMIZE','{$_GET["table"]}');
		XHR.appendData('database','{$_GET["database"]}');
		XHR.appendData('instance-id','{$_GET["instance-id"]}');
		AnimateDiv('anim-$t');
		XHR.sendAndLoad('$page', 'POST',x_SqlOptimize$t);			
	
	}
}


</script>";
	
	echo $html;	
	
}	

function rows_list_json(){
	$search=$_GET["search"];
	$MyPage=CurrentPageName();
	$page=1;
	$users=new usersMenus();
	$tpl=new templates();	
	$sock=new sockets();	
	$q=new mysql();
	$table=$_GET["table"];
	$tableOrg=$table;
	$database=$_GET["database"];
	$fields=unserialize(base64_decode($_GET["fields"]));
	$sqlQuery=base64_decode($_GET["sql"]);
	$instance_id=$_GET["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}	
	if($instance_id>0){$q=new mysql_multi($instance_id);}
	
	if(!is_array($fields)){json_error_show("Fields is empty {$_GET["fields"]}");}
	
	$t=$_GET["t"];
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("$table is empty");}
if($sqlQuery<>null){
		$table="($sqlQuery) as tt";
	}
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
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		if($total==0){json_error_show("No rows $sql");}
		
	}else{
		if($sqlQuery<>null){
			$sql="SELECT COUNT(*) as TCOUNT FROM $table";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
			$total = $ligne["TCOUNT"];
		}else{
			$total = $q->COUNT_ROWS($table,$database);
		}
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	
	if($sqlQuery<>null){
		$sql=$sqlQuery." $searchstring $FORCE_FILTER $ORDER $limitSql";
	}else{
		$sql="SELECT *  FROM $tableOrg WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	}
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("Query return empty array, $sql, ($q->mysql_error)");}
	$ldap=new clladp();
	while ($ligne = mysql_fetch_assoc($results)) {
		reset($fields);
		$cells=array();
		while (list ($key, $line) = each ($fields) ){
			
			$cellencoded=base64_encode($ligne[$key]);
			$cells[]="<a href=\"javascript:blur();\" Onclick=\"javascript:Loadjs('admin.index.php?json-error-js=$cellencoded');\">{$ligne[$key]}</a>";
			
		}
		
		
			$data['rows'][] = array(
				'id' => md5(serialize($ligne)),
				'cell' => $cells
				);		
		

		}

	echo json_encode($data);		
}	