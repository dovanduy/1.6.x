<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");die();}

if(isset($_GET["search-dabatase-js"])){search_database_js();exit;}
if(isset($_GET["search-dabatase-popup"])){search_database_popup();exit;}
if(isset($_POST["QUERY_SYSLOG_DATE"])){search_database_popup_save();exit;}
if(isset($_GET["search-database"])){search_database();exit;}
page();

function search_database_popup(){
	$boot=new boostrap_form();
	$q=new mysql_storelogs();
	
	if(!isset($_SESSION["QUERY_SYSLOG_HOST_DAY_FIELDY"])){
		$sql="SELECT DATE_FORMAT(filetime,'%Y-%m-%d') as filetime FROM files_info GROUP BY filetime ORDER BY filetime DESC";
		$results=$q->QUERY_SQL($sql);


		$dayz[null]="{select}";

		while ($ligne = mysql_fetch_assoc($results)) {
			$time=time_to_date(strtotime($ligne["filetime"]." 00:00:00"));
			$dayz[$ligne["filetime"]]=$time;
		}
		$_SESSION["QUERY_SYSLOG_HOST_DAY_FIELDY"]=serialize($dayz);
	}




	if(!isset($_SESSION["QUERY_SYSLOG_HOST_FIELDZ"])){
		$sql="SELECT hostname FROM files_info GROUP BY hostname ORDER BY hostname ASC";
		$results=$q->QUERY_SQL($sql);


		$hostz[null]="{select}";
		while ($ligne = mysql_fetch_assoc($results)) {
			$hostz[$ligne["hostname"]]=$ligne["hostname"];
		}

		$_SESSION["QUERY_SYSLOG_HOST_FIELDZ"]=serialize($hostz);

	}

	$LIMITS[50]=50;
	$LIMITS[250]=250;
	$LIMITS[500]=250;
	$LIMITS[1000]=1000;
	$LIMITS[2000]=2000;


	if(!isset($_SESSION["QUERY_SYSLOG_LIMIT"])){$_SESSION["QUERY_SYSLOG_LIMIT"]=250;}
	if(!is_numeric($_GET["xtime"])){
			$boot->set_list("QUERY_SYSLOG_DATE", "{date}", unserialize($_SESSION["QUERY_SYSLOG_HOST_DAY_FIELDY"]),$_SESSION["QUERY_SYSLOG_DATE"]);
	}
	$boot->set_list("QUERY_SYSLOG_HOST", "{hostname}", unserialize($_SESSION["QUERY_SYSLOG_HOST_FIELDZ"]),$_SESSION["QUERY_SYSLOG_HOST"]);
	$boot->set_field("QUERY_SYSLOG_FILE", "{filename}", $_SESSION["QUERY_SYSLOG_FILE"]);
	$boot->set_list("QUERY_SYSLOG_LIMIT", "{rows}", $LIMITS,$_SESSION["QUERY_SYSLOG_LIMIT"]);
	$boot->set_button("{search}");
	$boot->set_CloseYahoo("YahooWin2");
	$boot->set_formdescription("{advanced_search_explain}");
	$boot->set_RefreshSearchs();
	echo $boot->Compile();

}
function search_database_popup_save(){
	while (list ($key, $value) = each ($_POST) ){
		$_SESSION[$key]=$value;
	}
}
function page(){
	$tpl=new templates();
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		echo $tpl->_ENGINE_parse_body("<p class=text-error>{this_feature_is_disabled_corp_license}</p>");
		die();
	}
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$tpl=new templates();
	$LINKS["LINKS"][]=array("LABEL"=>"{advanced_search}","JS"=>"Loadjs('$page?search-dabatase-js=yes&xtime={$_GET["xtime"]}')");
	echo 
	$tpl->_ENGINE_parse_body("<p>{source_logs_squid_text}</p>").	
	$boot->SearchFormGen("filename,hostname,filetime","search-database","&xtime={$_GET["xtime"]}",$LINKS);
	
}
function search_database(){
	$page=1;
	$MyPage=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_storelogs();
	
	if(!$q->BD_CONNECT()){senderror($q->mysql_error);}
	if($q->start_error<>null){senderror($q->start_error);}
	
	$table="files_info";
	$tableOrg=$table;
	$database=$q->database;
	$t=time();
	$delete_alert=$tpl->javascript_parse_text("{delete_this_item}");
	
	$MySQLType=$tpl->_ENGINE_parse_body($q->MYSQLTypeText);
	//if(!$q->TABLE_EXISTS($table,$database)==0){senderror("{table_does_not_exists}: <strong>$database/$table</strong> $MySQLType: `$q->SocketName`!");}

	if($q->COUNT_ROWS($table,$database)==0){senderror("$table/$database is empty");}
	$searchstring=string_to_flexquery("search-database");


	$limit="LIMIT 0,250";

	if(is_numeric($_GET["xtime"])){
		$WHERE1=" (DATE_FORMAT(filetime,'%Y-%m-%d')='".date("Y-m-d")."') AND";
		unset($_SESSION["QUERY_SYSLOG_DATE"]);
	}
	
	
	if($_SESSION["QUERY_SYSLOG_LIMIT"]>0){
		$limit="LIMIT 0,{$_SESSION["QUERY_SYSLOG_LIMIT"]}";
	}
	$filters=array();
	$filters[]=SearchToSql("DATE_FORMAT(filetime,'%Y-%m-%d')",$_SESSION["QUERY_SYSLOG_DATE"]);
	$filters[]=SearchToSql("filename",$_SESSION["QUERY_SYSLOG_FILE"]);
	$filters[]=SearchToSql("hostname",$_SESSION["QUERY_SYSLOG_HOST"]);

	$table="(SELECT `filename`,`taskid`,`filesize`,`filetime`,`hostname` FROM $table
			WHERE $WHERE1 (`filename` LIKE 'auth.log%') OR (filename LIKE 'squid-access%')) as t";


	$sql="SELECT *  FROM $table WHERE 1 $searchstring ".@implode(" ", $filters)." ORDER BY `filetime` DESC LIMIT 0,250";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){senderror("$q->mysql_error");}
	if(mysql_num_rows($results)==0){senderror("Query return empty array");}
	$boot=new boostrap_form();
	while ($ligne = mysql_fetch_assoc($results)) {
		$md5S=md5(serialize($ligne));
		$filename=$ligne["filename"];
		$hostname=$ligne["hostname"];
		$storeid=$ligne["storeid"];
		$taskid=$ligne["taskid"];
		$filesize=FormatBytes($ligne["filesize"]/1024);
		$filetime=$ligne["filetime"];
		$delete=imgsimple("delete-32.png",null,"Delete$t('$storeid','$md5S')");
		$action="&nbsp;";
		if(preg_match("#auth\.log-.*?#", $ligne["filename"])){
			$action=imgsimple("32-import.png",null,"Loadjs('squid.restoreSource.php?filename={$ligne["filename"]}&storeid=$storeid')");

		}

		$download="<a href=\"$MyPage?download=$storeid&filename={$ligne["filename"]}&storeid=$storeid\"><img src='img/arrow-down-32.png'></a>";


		$js="Loadjs('logrotate.php?log-js=yes&filename=$filename&storeid=$storeid&t=1368560783');";
		$trlink=$boot->trswitch($js);
		$tr[]="
		<tr id='$md5S'>
		<td nowrap $trlink>$filetime</td>
		<td nowrap $trlink>$filename</td>
		<td nowrap $trlink>$hostname</td>
		<td nowrap $trlink>$filesize</td>
		<td width=1% align=center>$download</td>
		<td width=1% align=center>$action</td>
		<td width=1% align=center>$delete</td>
		</tr>
		";
	}

	echo $tpl->_ENGINE_parse_body("

			<table class='table table-bordered table-hover'>

			<thead>
				<tr>
					<th>{date}</th>
					<th>{filename}</th>
					<th>{hostname}</th>
					<th>{size}</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>
			<script>
			var memedb$t='';
			var xDelete$t= function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);return;}
			$('#'+memedb$t).remove();
}

			function Delete$t(ID,md){
			memedb$t=md;
			if(confirm('$delete_alert '+ID+' ?')){
			var XHR = new XHRConnection();
			XHR.appendData('syslog-delete',ID);
			XHR.sendAndLoad('$MyPage', 'POST', xDelete$t);
}
}
			</script>";

}