<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.dansguardian.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsDansGuardianAdministrator){
		$tpl=new templates();
		FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		exit;
		
	}
	if(isset($_GET["rule-js"])){rule_js();exit;}
	if(isset($_GET["delete-js"])){rule_delete_js();exit;}
	if(isset($_GET["list"])){rules_list();exit;}
	if(isset($_GET["rule-popup"])){rule_popup();exit;}
	if(isset($_POST["rule"])){rule_save();exit;}
	if(isset($_POST["delete"])){rule_delete();exit;}
	
table();


function rule_delete_js(){
	header("content-type: application/x-javascript");
	$md5=$_GET["delete-js"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$txt=$tpl->javascript_parse_text("{delete}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$t=time();
	
	
	$html="
var xFunction$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#UFDB_PAGE_RULE').flexReload();
}
	
function Function$t(){
	if(!confirm('$txt $rule ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$md5');
	XHR.sendAndLoad('$page', 'POST',xFunction$t);
}
	
Function$t();
";
	echo $html;
}

function rule_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM ufdb_page_rules WHERE `zmd5`='{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function rule_js(){
	header("content-type: application/x-javascript");
	$md5=$_GET["rule-js"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$txt=$tpl->javascript_parse_text("{new_rule}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$t=time();
	if($md5<>null){$txt=null;}

$html="




var xFunction$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#UFDB_PAGE_RULE').flexReload();
	

}

function Function$t(){
var alias=prompt('$txt');
if(alias){
var XHR = new XHRConnection();
XHR.appendData('AddAlias',alias);
XHR.appendData('servername','{$_GET["servername"]}');
		XHR.sendAndLoad('$page', 'POST',xFunction$t);
}
}

//Function$t();
YahooWin2(990,'$page?rule-popup=$md5','$rule >> $txt');
";
		echo $html;

}




function rule_popup(){
	$dans=new dansguardian_rules();
	$md5=$_GET["rule-popup"];
	$tpl=new templates();
	$page=CurrentPageName();
	$fields_size=22;
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$t=time();
	$bt="{add}";
	if($md5<>null){$bt="{apply}";}
	
	$Timez[0]="{default}";
	$Timez[5]="5 {minutes}";
	$Timez[10]="10 {minutes}";
	$Timez[15]="15 {minutes}";
	$Timez[30]="30 {minutes}";
	$Timez[60]="1 {hour}";
	$Timez[120]="2 {hours}";
	$Timez[240]="4 {hours}";
	$Timez[720]="12 {hours}";
	$Timez[2880]="2 {days}";
	
	$cats=$dans->LoadBlackListes();
	while (list ($num, $ligne) = each ($cats) ){$newcat[$num]=$num;}
	$newcat[null]="{none}";
	$newcat["safebrowsing"]="Google Safe Browsing";
	$newcat["blacklist"]="{blacklist}";
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM ufdb_page_rules WHERE zmd5='$md5'"));
	if(!$q->ok){echo FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	
	$html[]="<div style='width:98%' class=form>";
	$html[]="<table style='width:100%'>";
	$html[]=Field_list_table("category-$t","{category}",$ligne["category"],$fields_size,$newcat,null,450);
	$html[]=Field_list_table("maxtime-$t","{unlock_during}",$ligne["maxtime"],$fields_size,$Timez,null,450);
	$html[]=Field_text_table("adgroup-$t","{active_directory_group}",$ligne["adgroup"],$fields_size,null,450);
	$html[]=Field_text_table("username-$t","{username}",$ligne["username"],$fields_size,null,450);
	$html[]=Field_checkbox_table("deny-$t", "{deny_unlock}",$ligne["deny"],$fields_size,null,"UnCheckAllow$t()");
	$html[]=Field_checkbox_table("allow-$t", "{allow_unlock}",$ligne["allow"],$fields_size,null,"UnCheckDeny$t()");
	$html[]=Field_checkbox_table("noauth-$t", "{not_authenticate}",$ligne["noauth"],$fields_size);
	$html[]=Field_list_table("addTocat-$t","{automatically_add_to}",$ligne["addTocat"],$fields_size,$newcat,null,450);
	
	
	$html[]=Field_button_table_autonome($bt,"Submit$t",30);
	$html[]="</table>";
	$html[]="</div>
	<script>
	var xSubmit$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		$('#UFDB_PAGE_RULE').flexReload();
	
	}
	
	function UnCheckAllow$t(){
		if(document.getElementById('deny-$t').checked){
			document.getElementById('allow-$t').checked=false;
		}else{
			document.getElementById('allow-$t').checked=true;
		
		}
	
	}
	
	function UnCheckDeny$t(){
		if(document.getElementById('allow-$t').checked){
			document.getElementById('deny-$t').checked=false;
		}else{
			document.getElementById('deny-$t').checked=true;
		}
	}
	
	
	function Submit$t(){
		var XHR = new XHRConnection();
		XHR.appendData('rule','$md5');
		XHR.appendData('category',document.getElementById('category-$t').value);
		XHR.appendData('adgroup',document.getElementById('adgroup-$t').value);
		XHR.appendData('username',document.getElementById('username-$t').value);
		XHR.appendData('addTocat',document.getElementById('addTocat-$t').value);
		XHR.appendData('maxtime',document.getElementById('maxtime-$t').value);
		
		
		
		
		if(document.getElementById('deny-$t').checked){
			XHR.appendData('deny','1');	
		}else{
			XHR.appendData('deny','0');	
		
		}
		if(document.getElementById('allow-$t').checked){
			XHR.appendData('allow','1');	
		}else{
			XHR.appendData('allow','0');	
		
		}	

		if(document.getElementById('noauth-$t').checked){
			XHR.appendData('noauth','1');	
		}else{
			XHR.appendData('noauth','0');	
		
		}		

		XHR.sendAndLoad('$page', 'POST',xSubmit$t);
	}
	
	UnCheckAllow$t();
	</script>
		
	";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function rule_save(){
	$md5=$_POST["rule"];
	$category=$_POST["category"];
	$adgroup=$_POST["adgroup"];
	$username=$_POST["username"];
	$deny=$_POST["deny"];
	$noauth=$_POST["noauth"];
	$allow=$_POST["allow"];
	$addTocat=$_POST["addTocat"];
	$maxtime=$_POST["maxtime"];
	$q=new mysql_squid_builder();
	if(trim($adgroup)==null){$adgroup="*";}
	
	if(!$q->FIELD_EXISTS("ufdb_page_rules", "maxtime")){
		$q->QUERY_SQL("ALTER TABLE `ufdb_page_rules` ADD `maxtime` smallint(3) NOT NULL DEFAULT 0,
				ADD INDEX ( `maxtime` )");
	}
	
	if(!$q->FIELD_EXISTS("ufdb_page_rules", "allow")){
		$q->QUERY_SQL("ALTER TABLE `ufdb_page_rules` ADD `allow` smallint(1) NOT NULL DEFAULT 0,
				ADD INDEX ( `allow` )");
	}	
	
	
	
	if($md5==null){
			$md5=md5(serialize($_POST));
		$q->QUERY_SQL("INSERT IGNORE INTO ufdb_page_rules 
			(`zmd5`,`category`,`adgroup`,`username`,`deny`,`noauth`,`allow`,`addTocat`,`maxtime`) VALUES
			('$md5','$category','$adgroup','$username','$deny','$noauth','$allow','$addTocat','$maxtime')
			");
		
	
	
	}else{
		$q->QUERY_SQL("UPDATE ufdb_page_rules SET 
			`category`='$category',
			`adgroup`='$adgroup',
			`username`='$username',
			`deny`='$deny',
			`noauth`='$noauth',
			`allow`='$allow',
			`addTocat`='$addTocat',
			`maxtime`='$maxtime'
			WHERE `zmd5`='$md5'");
		
		
	}
	
	if(!$q->ok){echo $q->mysql_error;}

}


function table(){


	$sock=new sockets();
	
	$EnableSquidGuardHTTPService=$sock->GET_INFO("EnableSquidGuardHTTPService");
	if(!is_numeric($EnableSquidGuardHTTPService)){$EnableSquidGuardHTTPService=1;}
	if($EnableSquidGuardHTTPService==0){
		echo FATAL_ERROR_SHOW_128("{web_page_service_is_disabled}");
		die();
	
	}
	
	$q=new mysql_squid_builder();

	if(!$q->TABLE_EXISTS("ufdb_page_rules")){
		$sql="CREATE TABLE IF NOT EXISTS `ufdb_page_rules` (
			`zmd5` varchar(90) NOT NULL,
			`category` varchar(90) NOT NULL,
			`deny` smallint(1) NOT NULL,
			`allow` smallint(1) NOT NULL,
			`adgroup` varchar(255) NOT NULL,
			`noauth` smallint(1) NOT NULL,
			`maxtime` smallint(3) NOT NULL,
			`infinite` smallint(1) NOT NULL,
			`addTocat` varchar(255) NOT NULL,
			`username` varchar(255) NOT NULL,
			PRIMARY KEY (`zmd5`),
			KEY `category` (`category`),
			KEY `deny` (`deny`),
			KEY `allow` (`allow`),
			KEY `infinite` (`infinite`)
			) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql);
		if(!$q->ok){FATAL_ERROR_SHOW_128($q->mysql_error_html());}
		return;
	}

	$page=CurrentPageName();
	$tpl=new templates();
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$context=$tpl->_ENGINE_parse_body("{context}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$destination=$tpl->_ENGINE_parse_body("{destination}");
	$website=$tpl->_ENGINE_parse_body("{website}");
	$settings=$tpl->javascript_parse_text("{watchdog_squid_settings}");
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");
	$members=$tpl->javascript_parse_text("{members}");
	$allow=$tpl->javascript_parse_text("{allow}");
	$category=$tpl->javascript_parse_text("{category}");
	$deny=$tpl->javascript_parse_text("{deny}");
	$banned_page_webservice=$tpl->javascript_parse_text("{banned_page_webservice}");
	$rules=$tpl->javascript_parse_text("{rules}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$t=time();

	$buttons="
	buttons : [
		{name: '$new_rule', bclass: 'add', onpress :  newrule$t},
		{name: '$apply', bclass: 'apply', onpress :  apply$t},
	],";


	$html="
	<table class='UFDB_PAGE_RULE' style='display: none' id='UFDB_PAGE_RULE' style='width:99%'></table>
	<script>
	function BuildTable$t(){
	$('#UFDB_PAGE_RULE').flexigrid({
	url: '$page?list=yes',
	dataType: 'json',
	colModel : [
	{display: '$category', name : 'category', width :323, sortable : false, align: 'left'},
	{display: '$deny', name : 'deny', width :70, sortable : true, align: 'center'},
	{display: '$allow', name : 'allow', width :70, sortable : true, align: 'center'},
	{display: '$members', name : 'members', width :348, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'delete', width :70, sortable : false, align: 'center'},
	],
	$buttons

	searchitems : [
		{display: '$category', name : 'category'},
	],
	sortname: 'category',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:18px>$banned_page_webservice >> $rules</strong>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]

});
}

function apply$t(){
	Loadjs('squid.compile.progress.php');
}

function newrule$t(){
	Loadjs('$page?rule-js=')
}
function purge_caches$t(){
Loadjs('system.services.cmd.php?APPNAME=APP_NGINX&action=purge&cmd=%2Fetc%2Finit.d%2Fnginx&appcode=APP_NGINX');
}
function import_export$t(){
Loadjs('miniadmin.proxy.reverse.import.php');
}

function New$t(){
Loadjs('nginx.new.php?peer-id={$_GET["ID"]}');
}
	BuildTable$t();
	</script>";
	echo $html;
}

function rules_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$table="ufdb_page_rules";
	$q=new mysql_squid_builder();
	
	$FORCE=1;
	$t=$_GET["t"];
	if($_POST["query"]<>null){$search=str_replace("*", ".*?", $_POST["query"]);}

	$total=0;
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("no data [".__LINE__."]",0);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];

	}else{
		
			$total = $q->COUNT_ROWS($table, "artica_events");
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE $FORCE $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",0);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$CurrentPage=CurrentPageName();

	if(mysql_num_rows($results)==0){json_error_show("no data");}
	$searchstring=string_to_flexquery();
$automatically_add_to=$tpl->javascript_parse_text("{automatically_add_to}");
$unlock_during=$tpl->javascript_parse_text("{unlock_during}");

	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	$q1=new mysql();
	$t=time();

	$fontsize=22;
	$span="<span style='font-size:{$fontsize}px'>";
	$everyone=$tpl->javascript_parse_text("{everyone}");

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$red="32-red.png";
		$ok="ok-32.png";
		$warn="warning32.png";
		$zmd5=$ligne["zmd5"];
		$category=$ligne["category"];
		$deny=$ligne["deny"];
		$adgroup=$ligne["adgroup"];
		$noauth=$ligne["noauth"];
		$infinite=$ligne["infinite"];
		$addTocat=$ligne["addTocat"];
		$username=$ligne["username"];
		$maxtime=$ligne["maxtime"];
		$allow=$ligne["allow"];
		$automatically_add_to_text=null;
		$unlock_during=null;
		$icon="ok32-grey.png";
		$icon_allow="ok32-grey.png";
		if($allow==1){
			$icon_allow=$ok;
			$icon="ok32-grey.png";
		}
		
		if($deny==1){
			$icon=$red;
			$icon_allow="ok32-grey.png";
		}
		if($username<>null){
			$adgroup=$username;
		}
		if($addTocat<>null){
			$automatically_add_to_text="<br><i>$automatically_add_to $addTocat</i>";
		}
		if($noauth==1){
			$icon_allow=$warn;
		}
		
		if($adgroup=="*"){$adgroup="$everyone";}
		
		
		$delete=imgsimple("delete-42.png",null,"Loadjs('$MyPage?delete-js=$zmd5')");
		$link="Loadjs('$MyPage?rule-js=$zmd5')";
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$link\"
		style='text-decoration:underline;font-size:{$fontsize}px'>";
		
		if($maxtime>0){
			$automatically_add_to_text=$automatically_add_to_text."<br><i>$unlock_during {$maxtime} minutes</i>";
		}

		$data['rows'][] = array(
				'id' => $zmd5,
				'cell' => array(
						"$href$category</a>$automatically_add_to_text",
						"<img src='img/$icon'>",
						"<img src='img/$icon_allow'>",
						"$span$adgroup</span>",
						$delete
				)
		);

	}
	echo json_encode($data);

}