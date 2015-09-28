<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.tcpip.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.squid.inc');


$user=new usersMenus();
if($user->AsSquidAdministrator==false){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}

if(isset($_GET["liste-rules"])){list_rules();exit;}
if(isset($_POST["new-rule"])){new_rule();exit;}
if(isset($_POST["MaxSizeBytes"])){rule_save();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-tab"])){rule_tabs();exit;}
if(isset($_GET["rule-parameters"])){rule_parameters();exit;}
if(isset($_GET["rule-filestypes"])){rule_files_types();exit;}
if(isset($_POST["MIME-ID"])){rule_files_types_save();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}
if(isset($_POST["delete-rule"])){delete_rule();exit;}
if(isset($_POST["default-rules"])){create_default_rules();exit;}
table();

function rule_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM artica_caches WHERE ID='$ID'","artica_backup"));
	$html="YahooWin3('890','$page?rule-tab=yes&ID=$ID','{$ligne["rulename"]}')";
	echo $html;	
}
function rule_delete_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=time();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM artica_caches WHERE ID='$ID'","artica_backup"));
	$rulename=$tpl->javascript_parse_text("{delete} {$ligne["rulename"]} ?");
	
echo "
var xNewRule$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	$('#squid_enforce_rules_table').flexReload();
}

function NewRule$t(){
	if(!confirm('$rulename')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-rule','$ID');
	XHR.sendAndLoad('$page', 'POST',xNewRule$t);	
}
	NewRule$t();	
		
";	
	
}

function delete_rule(){
	
	$ID=$_POST["delete-rule"];
	
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("artica_caches","MarkToDelete","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `MarkToDelete` smallint(1) NOT NULL DEFAULT 0, ADD INDEX(MarkToDelete)";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	
	$q->QUERY_SQL("DELETE FROM artica_caches_sizes WHERE ruleid=$ID");
	$q->QUERY_SQL("UPDATE artica_caches SET MarkToDelete=1, enabled=0 WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?hypercache-delete=yes");
}


function rule_tabs(){	
	$tpl=new templates();
	$page=CurrentPageName();
	
	$array["rule-parameters"]='{parameters}';
	$array["rule-filestypes"]='{file_types}';
	$array["rule-domains"]='{others_domains}';
	
	
	
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="rule-domains"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.artica-rules.domains.php?ID={$_GET["ID"]}\"
			style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
	
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&ID={$_GET["ID"]}\"
		style='font-size:18px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_artica_enforce_rule-{$_GET["ID"]}");
	
}

function rule_parameters(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM artica_caches WHERE ID='$ID'","artica_backup"));
	$t=time();
	
	if($ligne["MaxSizeBytes"]==0){$ligne["MaxSizeBytes"]=3145728000;}
	
	$ligne["MaxSizeBytes"]=$ligne["MaxSizeBytes"]/1024;
	$ligne["MaxSizeBytes"]=$ligne["MaxSizeBytes"]/1024;
	
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>". 
	Field_checkbox_table("enabled-$t", "{enabled}",$ligne["enabled"]).
	Field_text_table("rulename-$t", "{rulename}",$ligne["rulename"],18,null,250).
	Field_text_table("sitename-$t", "{sitename}",$ligne["sitename"],18,null,250).
	Field_text_table("MaxSizeBytes-$t", "{max_size} MB",$ligne["MaxSizeBytes"],18,null,250).
	Field_button_table_autonome("{apply}", "Save$t",26)."
	</table>
</div>		
<script>
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	$('#squid_enforce_rules_table').flexReload();
}
	
function Save$t(){
	var XHR = new XHRConnection();
	var EnableSquidCacheBoosters=0;
	if(document.getElementById('enabled-$t').checked){
	XHR.appendData('enabled',1);
	}else{XHR.appendData('enabled',0);}
	
	XHR.appendData('ID','{$_GET["ID"]}');
	XHR.appendData('MaxSizeBytes',document.getElementById('MaxSizeBytes-$t').value)
	XHR.appendData('rulename',encodeURIComponent(document.getElementById('rulename-$t').value));
	XHR.appendData('sitename',encodeURIComponent(document.getElementById('rulename-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);						
}
</script>		
";

	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function rule_save(){
	$q=new mysql_builder();
	$_POST["rulename"]=mysql_escape_string2(url_decode_special_tool($_POST["rulename"]));
	if(!$q->FIELD_EXISTS("artica_caches","MaxSizeBytes","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `MaxSizeBytes` BIGINT UNSIGNED NOT NULL DEFAULT '3145728000'";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	if(!$q->FIELD_EXISTS("artica_caches","FileTypes","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `FileTypes` TEXT";
		$q->QUERY_SQL($sql,"artica_backup");
	}

	if(!$q->FIELD_EXISTS("artica_caches","OtherDomains","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `OtherDomains` TEXT";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	if(!$q->FIELD_EXISTS("artica_caches","MarkToDelete","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `MarkToDelete` smallint(1) NOT NULL DEFAULT 0, ADD INDEX(MarkToDelete)";
		$q->QUERY_SQL($sql,"artica_backup");
	}	
	
	
	$_POST["sitename"]=mysql_escape_string2(url_decode_special_tool($_POST["sitename"]));
	
	$q->QUERY_SQL("UPDATE artica_caches
			SET MaxSizeBytes='{$_POST["MaxSizeBytes"]}',
			`rulename`='{$_POST["rulename"]}',
			`sitename`='{$_POST["sitename"]}',
			`enabled`='{$_POST["enabled"]}'
			WHERE ID={$_POST["ID"]}
		
			
			");
	
	
	if(!$q->ok){echo $q->mysql_error;}
}

function rule_files_types(){
	
	$MimeArray=MimeArray();
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=time();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `FileTypes` FROM artica_caches WHERE ID='$ID'","artica_backup"));
	
	$FileTypes=unserialize($ligne["FileTypes"]);
	
	
	while (list ($mime, $ligne) = each ($MimeArray) ){
		$FT=explode("/",$mime);
		$FT[0]=trim($FT[0]);
		if($FT[0]==null){continue;}
		$MIMETT[$FT[0]]=true;
		$FTMIME[$FT[0]][]=$mime;
	}
	
	krsort($MIMETT);
	while (list ($mimeF, $ligne) = each ($MIMETT) ){
		if(trim($mimeF)==null){continue;}
		if(count($FTMIME[$mimeF])==0){continue;}
		$mainid=md5($mimeF.$t);

		
		
		ksort($FTMIME[$mimeF]);
		$htmlez=array();
		$c=0;
		while (list ($mimeF2, $mime) = each ($FTMIME[$mimeF]) ){
			$mimeEnc=md5("$mime-$t");
			if(!isset($FileTypes[$mime])){$FileTypes[$mime]=0;}
			$htmlez[]="\t".Field_checkbox_table($mimeEnc, $mime,$FileTypes[$mime])."\n";
			$scripts[]="if(document.getElementById('$mimeEnc').checked){XHR.appendData('$mime',1);}else{XHR.appendData('$mime',0);}";
			$c++;
		}
		if(count($htmlez)==0){continue;}
		
		
		$INVIS[]="document.getElementById('$mainid').style.display='none';";
		$htmle[]="
		<div>
			<a href=\"javascript:blur();\" 
			OnClick=\"javascript:Switch$t('$mainid');\" 
			style='font-size:30px;text-decoration:underline'>$mimeF</a>
		</div>
		<div id='$mainid' style='display:none'>
			<table style='width:100%'>".@implode("\n",$htmlez)."</table>
		</div>";
	}
	
	$html="
<div style='width:98%' class=form>
	".
		@implode("\n",$htmle).
		"<table style='width:100%'>".
		Field_button_table_autonome("{apply}", "Save$t()",26)."
		</table>
</div>
<script>
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	$('#squid_enforce_rules_table').flexReload();
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('MIME-ID','{$_GET["ID"]}');
	".@implode("\n", $scripts)."
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}


function Switch$t(mainid){
	".@implode("\n", $INVIS)."
	document.getElementById(mainid).style.display='block';
}
	

</script>
	";	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function rule_files_types_save(){
	
	$ID=$_POST["MIME-ID"];
	unset($_POST["MIME-ID"]);
	while (list ($mime, $value) = each ($_POST) ){
		if($value==0){continue;}
		$f[$mime]=$value;
	}
	
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("artica_caches","FileTypes","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `FileTypes` TEXT";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	if(!$q->FIELD_EXISTS("artica_caches","OtherDomains","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `OtherDomains` TEXT";
		$q->QUERY_SQL($sql,"artica_backup");
	}	
	
	$final=mysql_escape_string2(serialize($f));
	$sql="UPDATE artica_caches SET FileTypes='$final' WHERE ID=$ID";
	$q->QUERY_SQL($sql);	
	if(!$q->ok){echo $q->mysql_error."\n**********\n$sql\n";}
	
	echo $sql;
}



function table(){
	$error=null;
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("artica_caches")){create_table();}
	
	$hits=$tpl->javascript_parse_text("{hits}");
	$rulename=$tpl->javascript_parse_text("{rulename}");
	$new_rule=$tpl->javascript_parse_text("{new_rule}");
	$lang=$tpl->javascript_parse_text("{language}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$title=$tpl->javascript_parse_text("{subject}");
	$new_template=$tpl->javascript_parse_text("{new_template}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$online_help=$tpl->javascript_parse_text("{online_help}");
	$date=$tpl->javascript_parse_text("{zDate}");
	$replace=$tpl->_ENGINE_parse_body("{replace}");
	$enforce_rules=$tpl->javascript_parse_text("{enforce_rules}");
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$size=$tpl->javascript_parse_text("{size}");
	$sitename_explain=$tpl->javascript_parse_text("{artica_cache_rule_explain_sitename}");
	$t=time();
	$backToDefault=$tpl->javascript_parse_text("{backToDefault}");
	$ERROR_SQUID_REBUILD_TPLS=$tpl->javascript_parse_text("{ERROR_SQUID_REBUILD_TPLS}");
	$q=new mysql_squid_builder();
	$default_rules=$tpl->javascript_parse_text("{example_rules}");
	$hyper_cache_default_rules_ask=$tpl->javascript_parse_text("{hyper_cache_default_rules_ask}");
	
	//if(!$users->CORP_LICENSE){
		//$error="<p class=text-error>".$tpl->_ENGINE_parse_body("{MOD_TEMPLATE_ERROR_LICENSE}")."</p>";
	//}
	
	$buttons="
	buttons : [
	{name: '$new_rule', bclass: 'add', onpress : NewRule$t},
	{name: '$default_rules', bclass: 'add', onpress : Defaults$t},
	{name: '$apply', bclass: 'Reconf', onpress : Apply$t},
	
	],";	
	
$html="
	$error
	<table class='squid_enforce_rules_table' style='display: none' id='squid_enforce_rules_table' style='width:99%'></table>
	
<script>
var mem$t='';
$(document).ready(function(){
	$('#squid_enforce_rules_table').flexigrid({
	url: '$page?liste-rules=yes',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'enabled', width :60, sortable : true, align: 'center'},
		{display: '$rulename', name : 'rulename', width :255, sortable : true, align: 'left'},
		{display: '$sitename', name : 'sitename', width : 583, sortable : false, align: 'left'},
		{display: '$size', name : 'foldersize', width : 110, sortable : true, align: 'right'},
		{display: '&nbsp;', name : 'delete', width : 45, sortable : false, align: 'center'},
	
	],
	$buttons
	searchitems : [
		{display: '$rulename', name : 'rulename'},
		{display: '$sitename', name : 'sitename'},
	],
	sortname: 'rulename',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$enforce_rules</span>',
	useRp: true,
	rp: 250,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true
	
	});
});	
	


var xNewRule$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	$('#squid_enforce_rules_table').flexReload();
}

function NewRule$t(){
	Loadjs('squid.artica-rules.wizard.php');
		
}

function Apply$t(){
	Loadjs('squid.artica-rules.progress.php');
}

var xDefaults$t=function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#squid_enforce_rules_table').flexReload();
	Loadjs('squid.artica-rules.progress.php');
}
	
function Defaults$t(){
	if(!confirm('$hyper_cache_default_rules_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('default-rules','yes');
	XHR.sendAndLoad('$page', 'POST',xDefaults$t);						
}

</script>
";

echo $html;
}

function create_table(){
	
	
	$sql="CREATE TABLE IF NOT EXISTS `artica_caches` (
		`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		`foldersize` BIGINT UNSIGNED,
		`filesnumber` BIGINT UNSIGNED,
		`MaxSizeBytes` BIGINT UNSIGNED NOT NULL DEFAULT '3145728000',
		`FileTypes` TEXT,
		`OtherDomains` TEXT,
		`storagemin` SMALLINT UNSIGNED,
		`rulename` VARCHAR( 128 ) NOT NULL,
		`sitename` VARCHAR( 128 ) NOT NULL,
		`enabled` smallint(1) NOT NULL DEFAULT 1,
		 UNIQUE KEY `sitename` (`sitename`),
		 KEY `rulename` (`rulename`),
		 KEY `enabled` (`enabled`)
		)  ENGINE = MYISAM;
			";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	
	$sql="CREATE TABLE IF NOT EXISTS `artica_caches_sizes` (
		`ruleid` BIGINT UNSIGNED,
		`sizebytes` BIGINT UNSIGNED,
		`sitename` VARCHAR( 128 ) NOT NULL,
		 KEY `ruleid` (`ruleid`),
		 KEY `sitename` (`sitename`)
		)  ENGINE = MYISAM;
			";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);	
	
}

function build_defaults(){
	$q=new mysql_squid_builder();
	

	if(!$q->FIELD_EXISTS("artica_caches","OtherDomains","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `OtherDomains` TEXT";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	if(!$q->FIELD_EXISTS("artica_caches","MaxSizeBytes","artica_backup")){
		$sql="ALTER TABLE `artica_caches` ADD `MaxSizeBytes` BIGINT UNSIGNED NOT NULL DEFAULT '3145728000'";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	

	
	
	

	

	
}

function create_default_rules(){
	
	$q=new mysql_squid_builder();
	
	

	$FileTypes["application/octet-stream"]=1;
	$FileTypes["application/x-forcedownload"]=1;
	$FileTypes["application/x-msdos-program"]=1;
	$FileTypes["application/x-msi"]=1;
	$FileTypes["application/microsoftpatch"]=1;
	$FileTypes_enc=mysql_escape_string2(serialize($FileTypes));
	
	$q->QUERY_SQL("INSERT IGNORE INTO artica_caches (foldersize,filesnumber,MaxSizeBytes,FileTypes,storagemin,rulename,sitename,enabled,OtherDomains) VALUES
			(0,0,3145728000,'$FileTypes_enc',0,'Microsoft updates',
			'regex:(download|update)\\\\.(microsoft|windowsupdate)\\\\.com',1,'')");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
	$FileTypes=array();
	$FileTypes["text/css"]=1;
	$FileTypes["image/jpeg"]=1;
	$FileTypes["image/gif"]=1;
	$FileTypes["image/x-icon"]=1;
	$FileTypes["image/pjpeg"]=1;
	$FileTypes["image/bmp"]=1;
	$FileTypes["image/png"]=1;
	$FileTypes["image/x-jps"]=1;
	$FileTypes["image/vnd.fpx"]=1;
	$FileTypes["image/vnd.net-fpx"]=1;
	$FileTypes["image/florian"]=1;
	$FileTypes["image/fif"]=1;
	$FileTypes["image/vnd.dwg"]=1;
	
	$FileTypes["application/x-forcedownload"]=1;
	$FileTypes["application/x-msdos-program"]=1;
	$FileTypes["application/x-msi"]=1;
	$FileTypes["application/microsoftpatch"]=1;

	
	
	
	
	$FileTypes_enc=mysql_escape_string2(serialize($FileTypes));
	
	$q->QUERY_SQL("INSERT IGNORE INTO artica_caches (foldersize,filesnumber,MaxSizeBytes,FileTypes,storagemin,rulename,sitename,enabled,OtherDomains) VALUES
			(0,0,3145728000,'$FileTypes_enc',0,'All images and css,js files',
			'regex:\\\\.(png|gif|jpeg|css|js)$',1,'')");
			if(!$q->ok){echo $q->mysql_error;return;}
	
	
	
	$FileTypes["application/octet-stream"]=1;
	$FileTypes["image/jpeg"]=1;
	$FileTypes["image/gif"]=1;
	$FileTypes["image/x-icon"]=1;
	$FileTypes["text/html"]=1;
	$FileTypes["text/javascript"]=1;
	$FileTypes["text/css"]=1;
	$FileTypes["application/x-javascript"]=1;
	$FileTypes["application/javascript"]=1;
	$FileTypes["application/x-shockwave-flash"]=1;
	$FileTypes["font/woff"]=1;
	$FileTypes["font/woff2"]=1;
	$FileTypes["application/font-woff"]=1;
	$FileTypes["application/x-woff"]=1;
	$FileTypes["font/x-woff"]=1;
	$FileTypes["application/x-font-ttf"]=1;
	$FileTypes_enc=mysql_escape_string2(serialize($FileTypes));
	
	$OtherDomains["s-msn.com"]=true;
	$OtherDomains_enc=mysql_escape_string2(serialize($OtherDomains));
	
	$q->QUERY_SQL("INSERT IGNORE INTO artica_caches (foldersize,filesnumber,MaxSizeBytes,FileTypes,storagemin,rulename,sitename,enabled,OtherDomains)
			VALUES (0,0,3145728000,'$FileTypes_enc',0,'Microsoft MSN web site',
			'msn.com',1,'$OtherDomains_enc')");
	
	$OtherDomains=array();
	$OtherDomains["cloudflare.com"]=true;
	$OtherDomains["amazonaws.com"]=true;
	$OtherDomains["fonts.googleapis.com"]=true;
	$OtherDomains["img.youtube.com"]=true;
	$OtherDomains_enc=mysql_escape_string2(serialize($OtherDomains));
	
	$q->QUERY_SQL("INSERT IGNORE INTO artica_caches (foldersize,filesnumber,MaxSizeBytes,FileTypes,storagemin,rulename,sitename,enabled,OtherDomains)
			VALUES (0,0,3145728000,'$FileTypes_enc',0,'CDN(s) and most used',
			'cloudfront.net',1,'$OtherDomains_enc')");	
	

	
	
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
}


function new_rule(){
	
	$rulename=mysql_escape_string2(url_decode_special_tool($_POST["new-rule"]));
	$sitename=mysql_escape_string2(url_decode_special_tool($_POST["sitename"]));
	$sql="INSERT IGNORE INTO `artica_caches` (`sitename`,`rulename`,`enabled`) VALUES ('$sitename','$rulename','1')";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}


function list_rules(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$search='%';
	$table="artica_caches";
	$q=new mysql_squid_builder();
	$page=1;
	$_POST["query"]=trim($_POST["query"]);
	
	if(!$q->TABLE_EXISTS("artica_caches")){create_table();}
	if($q->COUNT_ROWS($table)==0){build_defaults();}

	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("no data"); return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show($q->mysql_error."<hr>$sql");}
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show($q->mysql_error."<hr>$sql");}
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";


	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error."<hr>$sql");}



	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(mysql_num_rows($results)==0){json_error_show("no data");}
	$wait_deletion=$tpl->javascript_parse_text("{wait_for_rule_deletion}");

	while ($ligne = mysql_fetch_assoc($results)) {
		$jsfiche=null;
		$herf=null;
		$ID=$ligne["ID"];
		$img="ok32.png";
		$text_mime=null;
		$foldersize="--";
		$wait_deletion_text=null;
		$MarkToDelete=$ligne["MarkToDelete"];
		
		$Modify="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?rule-js=yes&ID=$ID');\"
		style='font-size:16px;text-decoration:underline;font-weight:bold'>";

		
		if($ligne["enabled"]==0){$img="ok32-grey.png";}
		
		$OtherDomains=unserialize($ligne["OtherDomains"]);
		if(count($OtherDomains)>0){
			$TTF=array();
			while (list ($mimeF2, $mime) = each ($OtherDomains) ){
				if($mimeF2==null){continue;}
				$TTF[]=$mimeF2;
			}
			if(count($TTF)>0){
				$text_mime=$text_mime."<br><span style='font-size:12px'>".@implode(", ", $TTF)."</span>";
			}
		}
		
		$FileTypes=unserialize($ligne["FileTypes"]);
		if(count($FileTypes)==0){
			if($ligne["enabled"]==1){
				$img="warning32.png";
			}
		}else{
			$TTF=array();
			while (list ($mimeF2, $mime) = each ($FileTypes) ){
				$TTF[]=$mimeF2;
			}
			$text_mime=$text_mime."<br><span style='font-size:12px'>".@implode(", ", $TTF)."</span>";
		}
		
		if($ligne["foldersize"]>4096){
			$foldersize=FormatBytes($ligne["foldersize"]/1024);
			$ModifyFolder="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('squid.artica-rules.foldersize.php?ID=$ID');\"
			style='font-size:16px;text-decoration:underline;font-weight:bold'>";
		}
		
		$delete=imgtootltip("delete-32.png",null,"Loadjs('$MyPage?rule-delete-js=yes&ID=$ID');");
		if($MarkToDelete==1){$delete=null;$Modify=null;$ModifyFolder=null;$img="32-red.png";$wait_deletion_text="<br><i style='font-weight:bold;font-size:12px;color:#D32D2D'>$wait_deletion</i>";}
		
		
		
		$data['rows'][] = array(
				'id' => $ID,
				'cell' => array(
						"<img src='img/$img'>",
						"<span style='font-size:16px'>$Modify{$ligne["rulename"]}</a>$wait_deletion_text</span>"
						,"<span style='font-size:16px'>$Modify{$ligne["sitename"]}</a>$text_mime</span>",
						"<span style='font-size:16px'>$ModifyFolder{$foldersize}</a></span>",
						"$delete",
				)
		);
	}

if(count($data['rows'])==0){json_error_show("no data");}
echo json_encode($data);

}

function MimeArray(){
	$f["x-world/x-3dmf"]=true;
	$f["x-world/x-3dmf"]=true;
	$f["application/octet-stream"]=true;
	$f["application/msword"]=true;
	
	$f["application/x-compress"]=true;
	$f["application/x-compressed"]=true;
	
	$f["application/x-zip-compressed"]=true;
	$f["application/zip"]=true;
	$f["multipart/x-zip"]=true;
	$f["application/x-bzip2"]=true;
	$f["application/x-bzip"]=true;
	$f["application/x-bsh"]=true;
	$f["application/x-gsp"]=true;
	$f["application/x-gss"]=true;
	$f["application/x-gtar"]=true;
	$f["application/x-compressed"]=true;
	$f["application/x-gzip"]=true;
	$f["multipart/x-gzip"]=true;
	$f["application/book"]=true;
	
	$f["application/x-authorware-bin"]=true;
	$f["application/x-authorware-map"]=true;
	$f["application/x-authorware-seg"]=true;
	$f["text/vnd.abc"]=true;
	$f["text/html"]=true;
	$f["video/animaflex"]=true;
	$f["video/x-ms-wmv"]=true;
	$f["application/postscript"]=true;

	$f["application/x-aim"]=true;
	$f["text/x-audiosoft-intra"]=true;
	$f["application/x-navi-animation"]=true;
	$f["application/x-nokia-9000-communicator-add-on-software"]=true;
	$f["application/mime"]=true;
	$f["application/octet-stream"]=true;
	
	$f["application/x-chrome-extension"]=true;
	$f["application/x-forcedownload"]=true;
	$f["application/x-msdos-program"]=true;
	$f["application/x-msi"]=true;
	$f["application/microsoftpatch"]=true;
	$f["application/x-shockwave-flash"]=true;
	$f["font/woff"]=true;
	$f["font/woff2"]=true;
	$f["application/font-woff"]=true;
	$f["application/x-woff"]=true;
	$f["font/x-woff"]=true;
	$f["application/x-font-ttf"]=true;
	
	
	$f["application/x-mplayer2"]=true;
	$f["application/x-troff-msvideo"]=true;
	$f["application/x-dvi"]=true;
	
	
	$f["video/fli"]=true;
	$f["video/x-fli"]=true;
	$f["video/x-atomic3d-feature"]=true;
	$f["video/x-ms-asf"]=true;
	$f["video/x-ms-asf-plugin"]=true;
	$f["video/avi"]=true;
	$f["video/msvideo"]=true;
	$f["video/x-msvideo"]=true;
	$f["video/avs-video"]=true;
	$f["video/dl"]=true;
	$f["video/x-dl"]=true;
	$f["video/x-dv"]=true;
	$f["video/gl"]=true;
	$f["video/x-gl"]=true;
	$f["video/x-isvideo"]=true;
	$f["video/mpeg"]=true;
	$f["video/x-dv"]=true;
	$f["video/quicktime"]=true;
	$f["video/x-sgi-movie"]=true;
	$f["video/mpeg"]=true;
	$f["video/x-mpeg"]=true;
	$f["video/x-mpeq2a"]=true;
	$f["video/mp4"]=true;
	$f["video/f4f"]=true;
	$f["video/x-motion-jpeg"]=true;
	$f["video/x-sgi-movie"]=true;

	
	$f["audio/x-gsm"]=true;
	$f["audio/x-vnd.audioexplosion.mjuicemediafile"]=true;
	$f["audio/it"]=true;
	$f["audio/x-jam"]=true;	
	$f["audio/basic"]=true;
	$f["audio/x-au"]=true;
	$f["audio/aiff"]=true;
	$f["audio/x-aiff"]=true;
	$f["audio/make"]=true;
	$f["audio/mpeg"]=true;
	$f["audio/x-mpeg"]=true;
	$f["audio/mpeg3"]=true;
	$f["audio/x-mpeg-3"]=true;	
	$f["audio/nspaudio"]=true;
	$f["audio/x-nspaudio"]=true;
	$f["audio/x-liveaudio"]=true;	
	$f["audio/midi"]=true;
	$f["audio/nspaudio"]=true;
	$f["audio/x-nspaudio"]=true;
	$f["music/x-karaoke"]=true;
	$f["audio/mod"]=true;
	$f["audio/x-mod"]=true;
	$f["application/x-midi"]=true;
	$f["audio/midi"]=true;
	$f["audio/x-mid"]=true;
	$f["audio/x-midi"]=true;
	$f["audio/make"]=true;
	$f["application/x-vnd.audioexplosion.mzz"]=true;
	
	$f["music/crescendo"]=true;
	$f["x-music/x-midi"]=true;
	$f["music/crescendo"]=true;
	
	
	$f["application/java"]=true;
	$f["application/java-byte-code"]=true;
	$f["application/x-java-class"]=true;
	$f["text/x-asm"]=true;
	$f["text/asp"]=true;
	$f["text/plain"]=true;
	$f["text/css"]=true;
	$f["text/x-c"]=true;
	$f["text/html"]=true;
	$f["text/x-fortran"]=true;
	$f["text/x-java-source"]=true;
	$f["application/x-java-commerce"]=true;
	$f["application/x-javascript"]=true;
	$f["application/javascript"]=true;
	$f["application/ecmascript"]=true;
	$f["text/javascript"]=true;
	$f["text/ecmascript"]=true;
	
	

	
	$f["application/x-bcpio"]=true;
	$f["application/mac-binary"]=true;
	$f["application/macbinary"]=true;
	$f["application/octet-stream"]=true;
	$f["application/x-binary"]=true;
	$f["application/x-macbinary"]=true;
	$f["application/vnd.ms-pki.seccat"]=true;
	$f["text/x-c"]=true;
	$f["application/clariscad"]=true;
	$f["application/x-cocoa"]=true;
	$f["application/cdf"]=true;
	$f["application/x-cdf"]=true;
	$f["application/x-netcdf"]=true;
	$f["application/pkix-cert"]=true;
	$f["application/x-x509-ca-cert"]=true;
	$f["application/x-chat"]=true;
	$f["application/x-cpio"]=true;
	$f["application/mac-compactpro"]=true;
	$f["application/x-compactpro"]=true;
	$f["application/x-cpt"]=true;
	$f["application/pkcs-crl"]=true;
	$f["application/pkix-crl"]=true;
	$f["application/pkix-cert"]=true;
	$f["application/x-x509-ca-cert"]=true;
	$f["application/x-x509-user-cert"]=true;
	$f["application/x-csh"]=true;
	$f["text/x-script.csh"]=true;
	$f["application/x-pointplus"]=true;
	$f["application/x-director"]=true;
	$f["application/x-deepv"]=true;
	$f["application/x-x509-ca-cert"]=true;
	
	$f["application/x-director"]=true;

	
	
	$f["application/commonground"]=true;
	$f["application/drafting"]=true;
	$f["drawing/x-dwf (old)"]=true;
	$f["model/vnd.dwf"]=true;
	$f["application/acad"]=true;

	$f["application/dxf"]=true;

	$f["application/x-director"]=true;
	$f["text/x-script.elisp"]=true;
	$f["application/x-bytecode.elisp (compiled elisp)"]=true;
	$f["application/x-elc"]=true;
	$f["application/x-envoy"]=true;
	$f["application/postscript"]=true;
	$f["application/x-esrehber"]=true;
	$f["text/x-setext"]=true;
	$f["application/envoy"]=true;
	$f["application/x-envoy"]=true;
	
	
	$f["application/vnd.fdf"]=true;
	$f["application/fractals"]=true;
	$f["text/vnd.fmi.flexstor"]=true;
	$f["application/freeloader"]=true;

	$f["text/x-h"]=true;
	$f["application/x-hdf"]=true;
	$f["application/x-helpfile"]=true;
	$f["application/vnd.hp-hpgl"]=true;
	$f["text/x-script"]=true;
	$f["application/hlp"]=true;
	$f["application/x-helpfile"]=true;
	$f["application/x-winhelp"]=true;
	$f["application/vnd.hp-hpgl"]=true;
	$f["application/vnd.hp-hpgl"]=true;
	$f["application/binhex"]=true;
	$f["application/binhex4"]=true;
	$f["application/mac-binhex"]=true;
	$f["application/mac-binhex40"]=true;
	$f["application/x-binhex40"]=true;
	$f["application/x-mac-binhex40"]=true;
	$f["application/hta"]=true;
	$f["text/x-component"]=true;

	$f["text/webviewhtml"]=true;
	$f["x-conference/x-cooltalk"]=true;

	
	$f["application/iges"]=true;
	$f["model/iges"]=true;
	$f["application/iges"]=true;
	$f["model/iges"]=true;
	$f["application/x-ima"]=true;
	$f["application/x-httpd-imap"]=true;
	$f["application/inf"]=true;
	$f["application/x-internett-signup"]=true;
	$f["application/x-ip2"]=true;

	
	$f["application/x-inventor"]=true;
	$f["i-world/i-vrml"]=true;
	$f["application/x-livescreen"]=true;
	


	$f["application/x-ksh"]=true;
	$f["text/x-script.ksh"]=true;

	$f["application/x-latex"]=true;
	$f["application/lha"]=true;
	$f["application/x-lha"]=true;
	$f["application/x-lisp"]=true;
	$f["text/x-script.lisp"]=true;
	$f["text/x-la-asf"]=true;
	$f["application/x-latex"]=true;
	$f["application/x-lzh"]=true;
	$f["application/lzx"]=true;
	$f["application/x-lzx"]=true;
	
	$f["text/x-m"]=true;
	
	

	$f["audio/x-mpequrl"]=true;
	$f["application/x-troff-man"]=true;
	$f["application/x-navimap"]=true;
	
	$f["application/mbedlet"]=true;
	$f["application/x-magic-cap-package-1.0"]=true;
	$f["application/mcad"]=true;
	$f["application/x-mathcad"]=true;
	

	$f["text/mcf"]=true;
	$f["application/netmc"]=true;
	$f["application/x-troff-me"]=true;
	$f["message/rfc822"]=true;
	

	$f["application/x-frame"]=true;
	$f["application/x-mif"]=true;
	$f["message/rfc822"]=true;
	$f["www/mime"]=true;

	
	$f["application/x-meme"]=true;
	$f["application/base64"]=true;
	$f["application/x-project"]=true;
	$f["application/vnd.ms-project"]=true;
	$f["application/x-project"]=true;
	$f["application/marc"]=true;
	$f["application/x-troff-ms"]=true;
	
	
	$f["image/jpeg"]=true;
	$f["image/pjpeg"]=true;
	$f["image/x-jps"]=true;
	$f["image/vnd.fpx"]=true;
	$f["image/vnd.net-fpx"]=true;
	$f["image/florian"]=true;
	$f["image/fif"]=true;
	$f["image/vnd.dwg"]=true;
	$f["image/bmp"]=true;
	$f["image/x-windows-bmp"]=true;
	$f["image/x-dwg"]=true;
	$f["image/vnd.dwg"]=true;
	$f["image/x-dwg"]=true;
	$f["image/g3fax"]=true;
	$f["image/gif"]=true;
	$f["image/x-icon"]=true;
	$f["image/ief"]=true;
	$f["image/jutvision"]=true;
	$f["image/vasa"]=true;
	$f["image/naplps"]=true;
	
	$f["application/x-netcdf"]=true;
	$f["application/vnd.nokia.configuration-message"]=true;
	
	$f["application/x-mix-transfer"]=true;
	$f["application/x-conference"]=true;
	$f["application/x-navidoc"]=true;
	$f["application/octet-stream"]=true;
	$f["application/oda"]=true;
	$f["application/x-omc"]=true;
	$f["application/x-omcdatamaker"]=true;
	$f["application/x-omcregerator"]=true;
	$f["text/x-pascal"]=true;
	$f["application/pkcs10"]=true;
	$f["application/x-pkcs10"]=true;
	$f["application/pkcs-12"]=true;
	$f["application/x-pkcs12"]=true;
	$f["application/x-pkcs7-signature"]=true;
	$f["application/pkcs7-mime"]=true;
	$f["application/x-pkcs7-mime"]=true;
	$f["application/pkcs7-mime"]=true;
	$f["application/x-pkcs7-mime"]=true;
	$f["application/x-pkcs7-certreqresp"]=true;
	$f["application/pkcs7-signature"]=true;
	$f["application/pro_eng"]=true;
	$f["text/pascal"]=true;
	$f["image/x-portable-bitmap"]=true;
	$f["application/vnd.hp-pcl"]=true;
	$f["application/x-pcl"]=true;
	$f["image/x-pict"]=true;
	$f["image/x-pcx"]=true;
	$f["chemical/x-pdb"]=true;
	$f["application/pdf"]=true;
	$f["audio/make"]=true;
	$f["audio/make.my.funk"]=true;

	
	
	
	$f["application/x-newton-compatible-pkg"]=true;
	$f["application/vnd.ms-pki.pko"]=true;
	$f["text/plain"]=true;
	$f["text/x-script.perl"]=true;
	$f["application/x-pixclscript"]=true;
	
	$f["image/x-xpixmap"]=true;
	$f["image/png"]=true;
	$f["image/pict"]=true;
	$f["image/x-portable-greymap"]=true;
	$f["image/x-portable-anymap"]=true;
	$f["image/x-portable-graymap"]=true;
	$f["image/x-portable-pixmap"]=true;
	$f["image/x-quicktime"]=true;
	$f["image/x-tiff"]=true;
	$f["image/x-niff"]=true;
	$f["image/tiff"]=true;
	
	$f["image/cmu-raster"]=true;
	$f["image/x-cmu-raster"]=true;
	$f["image/vnd.dwg"]=true;
	$f["image/x-dwg"]=true;
	$f["image/vnd.xiff"]=true;
	$f["image/x-xbitmap"]=true;
	$f["image/x-xbm"]=true;
	$f["image/xbm"]=true;

	
	$f["text/x-script.perl-module"]=true;
	$f["application/x-pagemaker"]=true;
	$f["application/x-pagemaker"]=true;
	
	$f["application/x-portable-anymap"]=true;
	$f["application/x-cmu-raster"]=true;
	$f["application/mspowerpoint"]=true;
	$f["application/vnd.ms-powerpoint"]=true;
	$f["application/powerpoint"]=true;
	$f["model/x-pov"]=true;
	
	
	$f["application/vnd.ms-powerpoint"]=true;
	$f["application/x-mspowerpoint"]=true;
	$f["application/mspowerpoint"]=true;
	$f["application/x-freelance"]=true;
	$f["application/pro_eng"]=true;
	$f["application/postscript"]=true;
	$f["application/octet-stream"]=true;
	$f["paleovu/x-pv"]=true;
	$f["application/vnd.ms-powerpoint"]=true;
	$f["text/x-script.phyton"]=true;
	$f["application/x-bytecode.python"]=true;
	$f["audio/vnd.qcelp"]=true;
	$f["x-world/x-3dmf"]=true;
	$f["x-world/x-3dmf"]=true;
	
	$f["video/quicktime"]=true;
	$f["video/x-qtc"]=true;
	$f["audio/x-pn-realaudio"]=true;
	$f["audio/x-pn-realaudio-plugin"]=true;
	$f["audio/x-realaudio"]=true;
	$f["audio/x-pn-realaudio"]=true;
	

	
	$f["text/x-script.rexx"]=true;
	$f["image/vnd.rn-realflash"]=true;
	$f["image/x-rgb"]=true;
	$f["application/vnd.rn-realmedia"]=true;
	$f["audio/x-pn-realaudio"]=true;
	$f["audio/mid"]=true;
	$f["audio/x-pn-realaudio"]=true;
	$f["audio/x-pn-realaudio"]=true;
	$f["audio/x-pn-realaudio-plugin"]=true;
	$f["application/ringing-tones"]=true;
	$f["application/vnd.nokia.ringing-tone"]=true;
	$f["application/vnd.rn-realplayer"]=true;
	$f["application/x-troff"]=true;
	$f["image/vnd.rn-realpix"]=true;
	$f["audio/x-pn-realaudio-plugin"]=true;
	$f["text/richtext"]=true;
	$f["text/vnd.rn-realtext"]=true;
	$f["application/rtf"]=true;
	$f["application/x-rtf"]=true;
	$f["text/richtext"]=true;
	$f["application/rtf"]=true;
	$f["text/richtext"]=true;
	$f["video/vnd.rn-realvideo"]=true;
	$f["text/x-asm"]=true;
	$f["audio/s3m"]=true;
	$f["application/octet-stream"]=true;
	$f["application/x-tbook"]=true;
	$f["application/x-lotusscreencam"]=true;
	$f["text/x-script.guile"]=true;
	$f["text/x-script.scheme"]=true;
	$f["video/x-scm"]=true;
	$f["text/plain"]=true;
	$f["application/sdp"]=true;
	$f["application/x-sdp"]=true;
	$f["application/sounder"]=true;
	$f["application/sea"]=true;
	$f["application/x-sea"]=true;
	$f["application/set"]=true;
	$f["text/sgml"]=true;
	$f["text/x-sgml"]=true;
	$f["text/sgml"]=true;
	$f["text/x-sgml"]=true;
	$f["application/x-bsh"]=true;
	$f["application/x-sh"]=true;
	$f["application/x-shar"]=true;
	$f["text/x-script.sh"]=true;
	$f["application/x-bsh"]=true;
	$f["application/x-shar"]=true;
	$f["text/html"]=true;
	$f["text/x-server-parsed-html"]=true;
	$f["audio/x-psid"]=true;
	$f["application/x-sit"]=true;
	$f["application/x-stuffit"]=true;
	$f["application/x-koan"]=true;
	$f["application/x-seelogo"]=true;
	$f["application/smil"]=true;
	$f["application/smil"]=true;
	$f["audio/basic"]=true;
	$f["audio/x-adpcm"]=true;
	$f["application/solids"]=true;
	$f["application/x-pkcs7-certificates"]=true;
	$f["text/x-speech"]=true;
	$f["application/futuresplash"]=true;
	$f["application/x-sprite"]=true;
	$f["application/x-sprite"]=true;
	$f["application/x-wais-source"]=true;
	$f["text/x-server-parsed-html"]=true;
	$f["application/streamingmedia"]=true;
	$f["application/vnd.ms-pki.certstore"]=true;
	$f["application/step"]=true;
	$f["application/sla"]=true;
	$f["application/vnd.ms-pki.stl"]=true;
	$f["application/x-navistyle"]=true;
	$f["application/step"]=true;
	$f["application/x-sv4cpio"]=true;
	$f["application/x-sv4crc"]=true;
	$f["application/x-world"]=true;
	$f["x-world/x-svr"]=true;
	$f["application/x-shockwave-flash"]=true;
	$f["application/x-troff"]=true;
	$f["text/x-speech"]=true;
	$f["application/x-tar"]=true;
	$f["application/toolbook"]=true;
	$f["application/x-tbook"]=true;
	$f["application/x-tcl"]=true;
	$f["text/x-script.tcl"]=true;
	$f["text/x-script.tcsh"]=true;
	$f["application/x-tex"]=true;
	$f["application/x-texinfo"]=true;
	$f["application/x-texinfo"]=true;
	$f["application/plain"]=true;
	$f["text/plain"]=true;
	$f["application/gnutar"]=true;
	$f["application/x-compressed"]=true;
	$f["application/x-troff"]=true;
	$f["audio/tsp-audio"]=true;
	$f["application/dsptype"]=true;
	$f["audio/tsplayer"]=true;
	$f["text/tab-separated-values"]=true;
	$f["image/florian"]=true;
	$f["text/plain"]=true;
	$f["text/x-uil"]=true;
	$f["text/uri-list"]=true;
	$f["text/uri-list"]=true;
	$f["application/i-deas"]=true;
	$f["text/uri-list"]=true;
	$f["text/uri-list"]=true;
	$f["application/x-ustar"]=true;
	$f["multipart/x-ustar"]=true;
	$f["application/octet-stream"]=true;
	$f["text/x-uuencode"]=true;
	$f["text/x-uuencode"]=true;
	$f["application/x-cdlink"]=true;
	$f["text/x-vcalendar"]=true;
	$f["application/vda"]=true;
	$f["video/vdo"]=true;
	$f["application/groupwise"]=true;
	$f["video/vivo"]=true;
	$f["video/vnd.vivo"]=true;
	$f["video/vivo"]=true;
	$f["video/vnd.vivo"]=true;
	$f["application/vocaltec-media-desc"]=true;
	$f["application/vocaltec-media-file"]=true;
	$f["audio/voc"]=true;
	$f["audio/x-voc"]=true;
	$f["video/vosaic"]=true;
	$f["audio/voxware"]=true;
	$f["audio/x-twinvq-plugin"]=true;
	$f["audio/x-twinvq"]=true;
	$f["audio/x-twinvq-plugin"]=true;
	$f["application/x-vrml"]=true;
	$f["model/vrml"]=true;
	$f["x-world/x-vrml"]=true;
	$f["x-world/x-vrt"]=true;
	$f["application/x-visio"]=true;
	$f["application/x-visio"]=true;
	$f["application/x-visio"]=true;
	$f["application/wordperfect6.0"]=true;
	$f["application/wordperfect6.1"]=true;
	$f["application/msword"]=true;
	$f["audio/wav"]=true;
	$f["audio/x-wav"]=true;
	$f["application/x-qpro"]=true;
	$f["image/vnd.wap.wbmp"]=true;
	$f["application/vnd.xara"]=true;
	$f["application/msword"]=true;
	$f["application/x-123"]=true;
	$f["windows/metafile"]=true;
	$f["text/vnd.wap.wml"]=true;
	$f["application/vnd.wap.wmlc"]=true;
	$f["text/vnd.wap.wmlscript"]=true;
	$f["application/vnd.wap.wmlscriptc"]=true;
	$f["application/msword"]=true;
	$f["application/wordperfect"]=true;
	$f["application/wordperfect"]=true;
	$f["application/wordperfect6.0"]=true;
	$f["application/wordperfect"]=true;
	$f["application/wordperfect"]=true;
	$f["application/x-wpwin"]=true;
	$f["application/x-lotus"]=true;
	$f["application/mswrite"]=true;
	$f["application/x-wri"]=true;
	$f["application/x-world"]=true;
	$f["model/vrml"]=true;
	$f["x-world/x-vrml"]=true;
	$f["model/vrml"]=true;
	$f["x-world/x-vrml"]=true;
	$f["text/scriplet"]=true;
	$f["application/x-wais-source"]=true;
	$f["application/x-wintalk"]=true;

	$f["video/x-amt-demorun"]=true;
	$f["xgl/drawing"]=true;
	
	$f["application/excel"]=true;
	$f["application/vnd.ms-excel"]=true;
	$f["application/x-excel"]=true;
	$f["application/x-msexcel"]=true;
	
	$f["audio/xm"]=true;
	$f["application/xml"]=true;
	$f["text/xml"]=true;
	$f["xgl/movie"]=true;
	$f["application/x-vnd.ls-xpix"]=true;
	$f["image/x-xpixmap"]=true;
	$f["image/xpm"]=true;
	$f["image/png"]=true;
	$f["image/x-xwd"]=true;
	$f["image/x-xwindowdump"]=true;
	$f["application/vnd.ms-fontobject"]=true;
	$f["application/font-sfnt"]=true;
	$f["application/font-woff"]=true;
	$f["video/x-amt-showrun"]=true;
	$f["application/x-mpegURL"]=true;
	$f["chemical/x-pdb"]=true;
	$f["audio/mpegURL"]=true;
	$f["text/x-script.zsh"]=true;
	$f["video/MP2T"]=true;
	ksort($f);
	return $f;
}