<?php
session_start();$_SESSION["MINIADM"]=true;

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");


if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["parameters-backup"])){parameters_backup();exit;}
if(isset($_POST["BackupSargUseNas"])){parameters_backup_save();exit;}
if(isset($_POST["EnableSargGenerator"])){parameters_save();exit;}
if(isset($_GET["section-events"])){section_events();exit;}
if(isset($_GET["events-search"])){events_search();exit;}
if(isset($_POST["run-compile"])){task_run_sarg();exit;}

if(isset($_GET["freeweb-section"])){freeweb_section();exit;}
if(isset($_GET["freeweb-search"])){freeweb_search();exit;}
if(isset($_GET["freeweb-create-js"])){freeweb_create_js();exit;}
if(isset($_POST["freeweb-create"])){freeweb_create();exit;}
if(isset($_POST["freeweb-delete"])){freeweb_delete();exit;}

if(isset($_GET["logrotate"])){section_logrotate();exit;}
if(isset($_GET["search-logrotate"])){logrotate_search();exit;}
if(isset($_GET["restore-filename-js"])){sarg_restore_js();exit;}
if(isset($_POST["restore-filename"])){sarg_restore_perform();exit;}
tabs();

function freeweb_create_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$ask=$tpl->javascript_parse_text("{webserver}");
	header("content-type: application/x-javascript");
	$html="

	var xAsk$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}
	ExecuteByClassName('SearchFunction');
}


function Ask$t(){
var serv=prompt('$ask ?','sarg.domain.tld');
if(!serv){return;}
var XHR = new XHRConnection();
XHR.appendData('freeweb-create',serv);
XHR.sendAndLoad('$page', 'POST',xAsk$t);

}
	
Ask$t();";

	echo $html;

}

function sarg_restore_js(){
	$filename=$_GET["restore-filename-js"];
	$storeid=$_GET["storeid"];
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$ask=$tpl->javascript_parse_text("{reprocess_this_file}");
	header("content-type: application/x-javascript");
	$html="
	
	var xAsk$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}
	ExecuteByClassName('SearchFunction');
	}
	
	
	function Ask$t(){
	if(!confirm('$ask ? $filename ($storeid)')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('restore-filename','$filename');
	XHR.appendData('storeid','$storeid');
	XHR.sendAndLoad('$page', 'POST',xAsk$t);
	
	}
	
	Ask$t();";
	
	echo $html;
	
}

function sarg_restore_perform(){
	$filename=$_POST["restore-filename"];
	$storeid=$_POST["storeid"];	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?sarg-restore=$storeid");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{task_restore_launched_explain}",1);
}

function freeweb_create(){
	$f=new freeweb($_POST["freeweb-create"]);
	$f->servername=$_POST["freeweb-create"];
	$f->groupware="SARG";
	$f->CreateSite();

}
function freeweb_delete(){
	$f=new freeweb($_POST["freeweb-delete"]);
	$sql="INSERT INTO drupal_queue_orders(`ORDER`,`servername`) VALUES('DELETE_FREEWEB','{$_POST["freeweb-delete"]}')";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("drupal.php?perform-orders=yes");
}
function task_run_sarg(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?sarg-run=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{apply_upgrade_help}");

}

function tabs(){
	$sock=new sockets();
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	
	if($users->PROXYTINY_APPLIANCE){$explain=$tpl->_ENGINE_parse_body("<div class=text-info>{why_arg_tiny}</div>");}
	$array["{general_settings}"]="$page?parameters=yes";
	$array["{backup}"]="$page?parameters-backup=yes";
	$array["{webservers}"]="$page?freeweb-section=yes";
	$array["{source_logs}"]="$page?logrotate=yes";
	$array["{events}"]="$page?section-events=yes";
	echo $explain.$boot->build_tab($array);
}


function parameters_backup(){
	$users=new usersMenus();
	$tpl=new templates();
	if(!$users->SARG_INSTALLED){echo $tpl->_ENGINE_parse_body("<p class=text-error>{SARG_NOT_INSTALLED}</p>");}
	$sock=new sockets();
	$tpl=new templates();
	$boot=new boostrap_form();
	$EnableSargGenerator=$sock->GET_INFO("EnableSargGenerator");
	if(!is_numeric($EnableSargGenerator)){$EnableSargGenerator=0;}
	
	
	$boot->set_formdescription("{sarg_backup_nfs_explain}");
	
	$BackupSargUseNas=$sock->GET_INFO("BackupSargUseNas");
	$BackupSargNASIpaddr=$sock->GET_INFO("BackupSargNASIpaddr");
	$BackupSargNASFolder=$sock->GET_INFO("BackupSargNASFolder");
	$BackupSargNASUser=$sock->GET_INFO("BackupSargNASUser");
	$BackupSargNASPassword=$sock->GET_INFO("BackupSargNASPassword");
	if(!is_numeric($BackupSargUseNas)){$BackupSargUseNas=0;}	

	$boot->set_spacertitle("{NAS_storage}");
	$boot->set_checkbox("BackupSargUseNas", "{use_remote_nas}", $BackupSargUseNas,
			array("DISABLEALL"=>true));	
	$boot->set_field("BackupSargNASIpaddr", "{hostname}", $BackupSargNASIpaddr);
	$boot->set_field("BackupSargNASFolder", "{shared_folder}", $BackupSargNASFolder,array("ENCODE"=>true));
	$boot->set_field("BackupSargNASUser","{username}", $BackupSargNASUser,array("ENCODE"=>true));
	$boot->set_fieldpassword("BackupSargNASPassword","{password}", $BackupSargNASPassword,array("ENCODE"=>true));
	
	$boot->set_button("{apply}");
	
	if(!$users->AsWebStatisticsAdministrator){
		$boot->set_form_locked();
	}
	
	if($EnableSargGenerator==0){
		$boot->set_form_locked();
	}
	
	echo $boot->Compile();	
	
}

function parameters_backup_save(){
	if(isset($_POST["BackupSargNASFolder"])){$_POST["BackupSargNASFolder"]=url_decode_special_tool($_POST["BackupSargNASFolder"]);}
	if(isset($_POST["BackupSargNASUser"])){$_POST["BackupSargNASUser"]=url_decode_special_tool($_POST["BackupSargNASUser"]);}
	if(isset($_POST["BackupSargNASPassword"])){$_POST["BackupSargNASPassword"]=url_decode_special_tool($_POST["BackupSargNASPassword"]);}
	
	
	$sock=new sockets();
	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
	}
	$sock->getFrameWork("squid.php?sarg-conf=yes");
}


function parameters(){
	$users=new usersMenus();
	$tpl=new templates();
	if(!$users->SARG_INSTALLED){
		echo $tpl->_ENGINE_parse_body("<p class=text-error>{SARG_NOT_INSTALLED}</p>");
	}
	
	$sock=new sockets();
	$tpl=new templates();
	$EnableSargGenerator=$sock->GET_INFO("EnableSargGenerator");
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	$SargConfig=unserialize(base64_decode($sock->GET_INFO("SargConfig")));
	$SargConfig=SargDefault($SargConfig);
	if(!is_numeric($EnableSargGenerator)){$EnableSargGenerator=0;}
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");
	if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
	$warn_squid_restart=$tpl->javascript_parse_text("{warn_squid_restart}");
	$page=CurrentPageName();
	$array[]="Bulgarian_windows1251";
	$array[]="Catalan";
	$array[]="Czech";
	$array[]="Dutch";
	$array[]="English";
	$array[]="French";
	$array[]="German";
	$array[]="Greek";
	$array[]="Hungarian";
	$array[]="Indonesian";
	$array[]="Italian";
	$array[]="Japanese";
	$array[]="Latvian";
	$array[]="Polish";
	$array[]="Portuguese";
	$array[]="Romanian";
	$array[]="Russian_koi8";
	$array[]="Russian_UFT-8";
	$array[]="Russian_windows1251";
	$array[]="Serbian";
	$array[]="Slovak";
	$array[]="Spanish";
	$array[]="Turkish";
	while (list ($key, $line) = each ($array) ){$langs[$line]=$line;}
	
	$overwrite_report=array("ignore"=>"{sarg_ignore}","ip"=>"{sarg_ip}","everybody"=>"{sarg_everybody}");
	$sort_order=array("A"=>"{ascendent}","D"=>"{descendent}");
	
	$sarg_date_format=array(
			"e"=>"European=dd/mm/yy",
			"u"=>"American=mm/dd/yy",
			"w"=>"Weekly=yy.ww"
	);
	

$boot=new boostrap_form();
$boot->set_checkbox("EnableSargGenerator", "{EnableSargGenerator}", $EnableSargGenerator,
		array("DISABLEALL"=>true,"TOOLTIP"=>"{EnableSargGenerator_explain}"));
if(!$users->PROXYTINY_APPLIANCE){
	$boot->set_checkbox("DisableArticaProxyStatistics", "{DisableArticaProxyStatistics}", 
			$DisableArticaProxyStatistics);
}else{
	$boot->set_hidden("DisableArticaProxyStatistics", 1);
}


$LASTLOGS[30]="1 {month}";
$LASTLOGS[60]="2 {months}";	
$LASTLOGS[90]="3 {months}";
$LASTLOGS[120]="4 {months}";
$LASTLOGS[150]="5 {months}";
$LASTLOGS[360]="1 {year}";

$boot->set_field("SargOutputDir", "{directory}", $SargOutputDir,array("TOOLTIP"=>"{SargOutputDir_explain}","BROWSE"=>true));
$boot->set_list("language", "{language}", $langs,$SargConfig["language"]);
$boot->set_field("title", "{sarg_title}", $SargConfig["title"]);
$boot->set_checkbox("graphs", "{enable_graphs}", $SargConfig["graphs"]);
$boot->set_checkbox("user_ip", "{sarg_user_ip}", $SargConfig["user_ip"]);
$boot->set_checkbox("resolve_ip", "{sarg_resolve_ip}", $SargConfig["resolve_ip"]);
$boot->set_checkbox("long_url", "{sarg_long_url}", $SargConfig["long_url"]);
$boot->set_list("records_without_userid", "{sarg_records_without_userid}", $overwrite_report,$SargConfig["records_without_userid"]);
$boot->set_field("topsites_num", "{sarg_topsites_num}", $SargConfig["topsites_num"]);
$boot->set_field("topuser_num", "{sarg_topuser_num}", $SargConfig["topuser_num"],array("TOOLTIP"=>"{sarg_topuser_exp}"));
$boot->set_list("topsites_sort_order", "{topsites_sort_order}", $sort_order,$SargConfig["topsites_sort_order"]);
$boot->set_list("index_sort_order", "{index_sort_order}", $sort_order,$SargConfig["index_sort_order"]);
$boot->set_list("date_format", "{sarg_date_format}", $sarg_date_format,$SargConfig["date_format"]);
$boot->set_list("lastlog", "{sarg_lastlog}", $LASTLOGS,$SargConfig["lastlog"]);
$boot->set_button("{apply}");
if(!$users->AsWebStatisticsAdministrator){$boot->set_form_locked();}
echo $boot->Compile();


}
function parameters_save(){
	$sock=new sockets();
	if(isset($_GET["RESTART_SQUID"])){$sock->getFrameWork("cmd.php?squid-restart=yes");}
	
	if($_POST["SargOutputDir"]=="/etc"){$_POST["SargOutputDir"]="/var/www/html/squid-reports";}
	if($_POST["SargOutputDir"]=="/var/www"){$_POST["SargOutputDir"]="/var/www/html/squid-reports";}
	if($_POST["SargOutputDir"]=="/usr/share/artica-postfix"){$_POST["SargOutputDir"]="/var/www/html/squid-reports";}
	if($_POST["SargOutputDir"]==null){$_POST["SargOutputDir"]="/var/www/html/squid-reports";}
	
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	$sock->SET_INFO("EnableSargGenerator",$_POST["EnableSargGenerator"]);
	$sock->SET_INFO("DisableArticaProxyStatistics",$_POST["DisableArticaProxyStatistics"]);
	$sock->SET_INFO("SargOutputDir",$_POST["SargOutputDir"]);
	
	
	
	if($_POST["DisableArticaProxyStatistics"]<>$DisableArticaProxyStatistics){$sock->getFrameWork("cmd.php?restart-artica-maillog=yes");}
	$tpl=new templates();
	$page=CurrentPageName();
	$SargConfig=unserialize(base64_decode($sock->GET_INFO("SargConfig")));
	$SargConfig=SargDefault($SargConfig);
	while (list ($key, $line) = each ($_POST) ){
		$SargConfig[$key]=$line;
	
	}
	$SargConfig=SargDefault($SargConfig);
	$sock->SaveConfigFile(base64_encode(serialize($SargConfig)),"SargConfig");
	$sock->getFrameWork("squid.php?test-sarg=yes");
	$sock->getFrameWork("squid.php?sarg-conf=yes");
}

function SargDefault($SargConfig){
	if($SargConfig["report_type"]==null){$SargConfig["report_type"]="topusers topsites sites_users users_sites date_time denied auth_failures site_user_time_date downloads";}
	if(!is_numeric($SargConfig["topuser_num"])){$SargConfig["topuser_num"]=0;}
	if(!is_numeric($SargConfig["long_url"])){$SargConfig["long_url"]=0;}
	if(!is_numeric($SargConfig["graphs"])){$SargConfig["graphs"]=1;}
	if(!is_numeric($SargConfig["user_ip"])){$SargConfig["user_ip"]=1;}
	if(!is_numeric($SargConfig["topsites_num"])){$SargConfig["topsites_num"]=100;}
	if(!is_numeric($SargConfig["topuser_num"])){$SargConfig["topuser_num"]=0;}
	if(!is_numeric($SargConfig["lastlog"])){$SargConfig["lastlog"]=90;}
	if($SargConfig["topsites_sort_order"]==null){$SargConfig["topsites_sort_order"]="D";}
	if($SargConfig["index_sort_order"]==null){$SargConfig["index_sort_order"]="D";}
	if($SargConfig["topsites_num"]<2){$SargConfig["topsites_num"]=100;}
	if($SargConfig["date_format"]==null){$SargConfig["date_format"]="e";}
	if($SargConfig["language"]==null){$SargConfig["language"]="English";}
	if($SargConfig["title"]==null){$SargConfig["title"]="Squid User Access Reports";}
	if($SargConfig["records_without_userid"]==null){$SargConfig["records_without_userid"]="ip";}
	return $SargConfig;
}
function section_events(){
	$boot=new boostrap_form();
	$users=new usersMenus();
	$tpl=new templates();
	if($users->AsWebStatisticsAdministrator){
		$rescan=$tpl->_ENGINE_parse_body("{RUN_COMPILATION_SARG}");
		$button=button($rescan,"RunSarg()",16);
		$OPTIONS["BUTTONS"][]=$button;
		
	}
	
	
	
	$form=$boot->SearchFormGen("search","events-search",null,$OPTIONS);
	echo $form;	
}
function events_search(){
	$sock=new sockets();
	$boot=new boostrap_form();
	$tpl=new templates();
	$rp=$_GET["rp"];
	if(!is_numeric($rp)){$rp=250;}
	$results=array();
	if(is_file("/usr/bin/sarg")){exec("/usr/bin/sarg -v",$results);}
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#SARG Version:(.+)#i", $ligne,$re)){$sargv=$ligne;}
	}
	
	$search=urlencode($_GET["events-search"]);
	$content=unserialize(base64_decode($sock->getFrameWork("squid.php?sarg-log=yes&rp=$rp&search=$search")));
	$boot=new boostrap_form();
	$c=0;
	krsort($content);
	$today=date("Y-m-d");
	while (list ($num, $ligne) = each ($content) ){
		
	
		if(preg_match("#^(.+?)\s+(.*?)\s+\[([0-9]+)\](.*?)$#", $ligne,$re)){
			$date=$re[1]." ".$re[2];
			$pid=$re[3];
			$ligne=$re[4];
		}
		
		$class=LineToClass($ligne);
		//$link=$boot->trswitch($jslink);
		$ligne=$tpl->javascript_parse_text("$ligne");
		$date=str_replace($today, "", $date);
		$ligne=utf8_decode($ligne);
		$tr[]="
		<tr class='$class'>
		<td style='font-size:12px;' width=1% nowrap><i class='icon-time'></i>&nbsp;$date</a></td>
		<td style='font-size:12px;' width=1% nowrap>$pid</td>
		<td style='font-size:12px;'width=99%>$ligne</td>
		</tr>";
		
		
	}
	
	$page=CurrentPageName();
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered'>
	
			<thead>
				<tr>
					<th>{date}</th>
					<th>&nbsp;</th>
					<th>{events}&nbsp;&nbsp;$sargv</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>

<script>
		var x_RunSarg= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			
		}
				
		function RunSarg(){
			var XHR = new XHRConnection();
			XHR.appendData('run-compile','yes');	
			XHR.sendAndLoad('$page', 'POST',x_RunSarg);	
		}
</script>										
					
";	
	
}

function freeweb_section(){
	//personal_categories
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$boot=new boostrap_form();
	$button=button("{add_a_web_service}","Loadjs('$page?freeweb-create-js=yes')",16);
	$button_edit=null;
	$EXPLAIN["BUTTONS"][]=$button;
	$SearchQuery=$boot->SearchFormGen("servername","freeweb-search",null,$EXPLAIN);
	echo $tpl->_ENGINE_parse_body($SearchQuery);

}


function freeweb_search(){
	$q=new mysql();
	$searchstring=string_to_flexquery();

	$q->QUERY_SQL("DELETE FROM freeweb WHERE servername=''","artica_backup");

	$sql="SELECT * FROM freeweb WHERE groupware='SARG' $searchstring";

	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){senderror($q->mysql_error);}
	$tpl=new templates();
	$deleteTXT=$tpl->javascript_parse_text("{delete}");
	$t=time();
	if(mysql_num_rows($results)==0){senderror("No data");}

	$boot=new boostrap_form();
	$page=CurrentPageName();
	$t=time();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$md=md5(serialize($ligne));

		$servername=$ligne["servername"];
		$delete=imgtootltip("delete-64.png",null,"Delete$t('$servername','$md')");
		$tr[]="
		<tr id='$md'>
		<td width=1% nowrap $jsedit style='vertical-align:middle'><img src='img/webfilter-64.png'></td>
		<td width=80% $jsedit style='vertical-align:middle'><span style='font-size:18px;font-weight:bold'>$servername</span></td>
		<td width=1% nowrap style='vertical-align:middle'>$delete</td>
		</tr>
		";

	}
	$page=CurrentPageName();
	$freeweb_compile_background=$tpl->javascript_parse_text("{freeweb_compile_background}");
	$reset_admin_password=$tpl->javascript_parse_text("{reset_admin_password}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");
	echo $tpl->_ENGINE_parse_body("

				<table class='table table-bordered table-hover'>

			<thead>
				<tr>
					<th colspan=2>{servername2}</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>").@implode("", $tr)."</tbody></table>
			 <script>
			 var FreeWebIDMEM$t='';

			 var xDelete$t=function (obj) {
			 var results=obj.responseText;
			 if(results.length>10){alert(results);return;}
			 $('#'+FreeWebIDMEM$t).remove();
}

function Delete$t(id,md){
FreeWebIDMEM$t=md;
if(confirm('$deleteTXT')){
var XHR = new XHRConnection();
XHR.appendData('freeweb-delete',id);
XHR.sendAndLoad('$page', 'POST',xDelete$t);
}
}
</script>";
}

function section_logrotate(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$tpl=new templates();
	//$LINKS["LINKS"][]=array("LABEL"=>"{advanced_search}","JS"=>"Loadjs('$page?search-dabatase-js=yes&xtime={$_GET["xtime"]}')");
	echo
	$tpl->_ENGINE_parse_body("<p>{source_logs_squid_text}</p>").
	$boot->SearchFormGen("filename,hostname,filetime","search-logrotate","&xtime={$_GET["xtime"]}",$LINKS);
		
	
}
function logrotate_search(){
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
	$searchstring=string_to_flexquery("search-logrotate");


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

	$table="(SELECT `filename`,`taskid`,`filesize`,`filetime`,`hostname`,`storeid` FROM $table
	WHERE $WHERE1 (`filename` LIKE '%sarg%') ) as t";


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
		if(preg_match("#sarg\.log#", $ligne["filename"])){
			$action=imgsimple("32-import.png",null,"Loadjs('$MyPage?restore-filename-js={$ligne["filename"]}&storeid=$storeid')");

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