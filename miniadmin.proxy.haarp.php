<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");


if(isset($_GET["tab-rules"])){tabs_rules();exit;}


if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_POST["EnableHaarp"])){parameters_save();exit;}
if(isset($_GET["delete_all_js"])){delete_all_js();exit;}


if(isset($_GET["rules"])){section_rules();exit;}
if(isset($_GET["search-rules"])){rules_search();exit;}
if(isset($_POST["rule-delete"])){rules_delete();exit;}
if(isset($_GET["search-webrules"])){section_webrules_search();exit;}
if(isset($_GET["section_webrules_add_js"])){section_webrules_add_js();exit;}
if(isset($_GET["pattern-js"])){rule_js();exit;}
if(isset($_GET["pattern-id"])){rule_id();exit;}
if(isset($_POST["pattern-id"])){rule_save();exit;}


if(isset($_GET["caches"])){caches_section();exit;}
if(isset($_GET["search-caches"])){caches_search();exit;}
if(isset($_POST["caches-delete"])){caches_delete();exit;}
if(isset($_GET["cache-js"])){caches_js();exit;}
if(isset($_GET["cache-id"])){caches_popup();exit;}
if(isset($_POST["cache-id"])){caches_save();exit;}

if(isset($_GET["events"])){events_section();exit;}
if(isset($_GET["events-search"])){events_search();exit;}

if(isset($_GET["srv-status"])){service_status();exit;}
if(isset($_GET["performances"])){performances();exit;}
tabs();


function tabs(){
	$page=CurrentPageName();
	$mini=new boostrap_form();
	$array["{parameters}"]="$page?parameters=yes";
	$array["{caches_rules}"]="$page?rules=yes";
	$array["{storage}"]="$page?caches=yes";
	$array["{performances}"]="$page?performances=yes";
	$array["{events}"]="$page?events=yes";
	echo $mini->build_tab($array);
}
function rule_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["pattern-js"];
	if($ID==0){$title="{new_rule}";}else{$title="{rule}:: $ID";}
	$title=$tpl->_ENGINE_parse_body($title);
	echo "YahooWin('700','$page?pattern-id=$ID','$title')";
}

function caches_js(){
	header("content-type: application/x-javascript");
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	$title="{new_cache}";
	$title=$tpl->_ENGINE_parse_body($title);
	echo "YahooWin('700','$page?cache-id=yes','$title')";	
	
}

function caches_popup(){
	
	$q=new mysql_squid_builder();
	$boot=new boostrap_form();
	
	
	$title="{new_cache}";
	$btname="{add}";
	$boot->set_CloseYahoo("YahooWin");
	
	
	$boot->set_formtitle($title);
	$boot->set_hidden("cache-id", "yes");
	$boot->set_field("directory", "{directory}", "/home/intel-cache-".time(),array("BROWSE"=>true));
	$boot->set_button($btname);
	$boot->set_RefreshSearchs();
	echo $boot->Compile();	
	
	
}

function rule_id(){
	$ID=$_GET["pattern-id"];
	$q=new mysql_squid_builder();
	$boot=new boostrap_form();
	
	if($ID==0){
		$title="{new_rule}";
		$btname="{add}";
		$boot->set_CloseYahoo("YahooWin");
		
	}else{
		$sql="SELECT * FROM haarp_redirpats WHERE ID='$ID'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){
			$error="<p class='text-error'>$q->mysql_error.</p>";
		}
		$title="{rule}::$ID";
		$btname="{apply}";
	}
	
	
	
	
	$boot->set_formtitle($title);
	$boot->set_formdescription("{haarp_rule_explain}");
	$boot->set_hidden("pattern-id", $ID);
	$boot->set_textarea("pattern", "{pattern}", $ligne["pattern"]);
	$boot->set_button($btname);
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
	
	
}

function caches_save(){
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("{license_error}");
		return;
	}

	$sql="INSERT IGNORE INTO haarp_caches (`directory`) VALUES ('{$_POST["directory"]}')";
	$q->QUERY_SQL($sql);	
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("haarp.php?restart=yes");
}

function rule_save(){
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("{license_error}");
		return;
	}
	
	$_POST["pattern"]=str_replace("'", "", $_POST["pattern"]);
	
	$ID=$_POST["pattern-id"];
	if($ID==0){
		$sql="INSERT IGNORE INTO haarp_redirpats (pattern) VALUES ('{$_POST["pattern"]}')";
	}else{
		$sql="UPDATE haarp_redirpats SET `pattern='{$_POST["pattern"]}' WHERE ID='$ID'";
	}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	echo $sql;
	$sock=new sockets();
	$sock->getFrameWork("haarp.php?pattern=yes");
	
	
	
}

function parameters(){
	$users=new usersMenus();
	$sock=new sockets();
	if(!$users->HAARP_INSTALLED){senderror("{not_installed}");}
	$boot=new boostrap_form();
	$EnableHaarp=$sock->GET_INFO("EnableHaarp");
	if(!is_numeric($EnableHaarp)){$EnableHaarp=0;}
	$page=CurrentPageName();
	$HaarpDebug=$sock->GET_INFO("HaarpDebug");
	$HaarpPort=$sock->GET_INFO("HaarpPort");
	if(!is_numeric($HaarpPort)){$HaarpPort=0;}
	if(!is_numeric($HaarpDebug)){$HaarpDebug=0;}
	if($HaarpPort==0){
		$HaarpPort=rand(35000, 64000);
		$sock->SET_INFO("HaarpPort", $HaarpPort);
	}	
	
	$HaarpConf=unserialize(base64_decode($sock->GET_INFO("HaarpConf")));
	$SERVERNUMBER=$HaarpConf["SERVERNUMBER"];
	$MAXSERVERS=$HaarpConf["MAXSERVERS"];
	if(!is_numeric($SERVERNUMBER)){$SERVERNUMBER="15";}
	if(!is_numeric($MAXSERVERS)){$MAXSERVERS="500";}
	
	$boot->set_checkbox("EnableHaarp", "{enable}", $EnableHaarp,array("DISABLEALL"=>true));
	$boot->set_checkbox("HaarpDebug", "{debug}", $HaarpDebug);
	$boot->set_field("HaarpPort", "{internal_port}", $HaarpPort);
	$boot->set_field("SERVERNUMBER", "{daemons_number}", $SERVERNUMBER);
	$boot->set_field("MAXSERVERS", "{max_daemons}", $MAXSERVERS);
	$boot->set_formdescription("{APP_HAARP_EXPLAIN}");
	
	
	$f[]="SERVERNUMBER 250";
	$f[]="MAXSERVERS 1000";	
	
	$boot->set_button("{apply}");
	$form=$boot->Compile();
	
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' style='vertical-align:top'><div id='haarp-status'></div>
		
		<div style='width:100%;text-align:right'>". imgtootltip("refresh-32.png",null,"LoadAjax('haarp-status','$page?srv-status=yes');")."</div>
		</td>
		<td valign='top' style='vertical-align:top;padding-left:15px'>$form</td>
	</tr>
	</table>
	<script>
		LoadAjax('haarp-status','$page?srv-status=yes');		
			
	</script>";
	echo $html;
	
}

function service_status(){
	$sock=new sockets();
	$status=base64_decode($sock->getFrameWork("haarp.php?status=yes"));
	$ini=new Bs_IniHandler();
	$ini->loadString($status);
	$APP_HAARP=DAEMON_STATUS_ROUND("APP_HAARP",$ini,null,1);
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($APP_HAARP);
	
}

function parameters_save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableHaarp", $_POST["EnableHaarp"]);
	$sock->SET_INFO("HaarpPort", $_POST["HaarpPort"]);
	$sock->SET_INFO("HaarpDebug", $_POST["HaarpDebug"]);
	$HaarpConf=unserialize(base64_decode($sock->GET_INFO("HaarpConf")));
	
	while (list ($index, $val) = each ($_POST) ){
		$HaarpConf[$index]=$val;
		
	}
	
	$sock->SaveConfigFile(base64_encode(serialize($HaarpConf)), "HaarpConf");
	$sock->getFrameWork("haarp.php?restart=yes");
	$sock->getFrameWork("cmd.php?squid-rebuild=yes");
	
}

function section_rules(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$sock=new sockets();
	$t=time();
	
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_rule}", "Loadjs('$page?pattern-js=0')"));
	echo $boot->SearchFormGen("pattern","search-rules",null,$EXPLAIN);
}

function caches_section(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$sock=new sockets();
	$t=time();
	
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_cache}", "Loadjs('$page?cache-js=0')"));
	echo $boot->SearchFormGen("directory","search-caches",null,$EXPLAIN);	
}

function rules_delete(){
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("{license_error}");
		return;
	}
	
	
	$q->QUERY_SQL("DELETE FROM haarp_redirpats WHERE ID={$_POST["rule-delete"]}");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("haarp.php?pattern=yes");
}

function caches_delete(){
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("{license_error}");
		return;
	}
	
	
	$q->QUERY_SQL("DELETE FROM haarp_caches WHERE ID={$_POST["cache-delete"]}");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("haarp.php?restart=yes");	
	
}

function caches_search(){
	$sock=new sockets();
	$page=CurrentPageName();
	$haarp=new haarp();
	$tpl=new templates();
	$t=time();
	$q=new mysql_squid_builder();
	
	$searchstring=string_to_flexquery("search-rules");
	$users=new usersMenus();
	$LIC=0;if($users->CORP_LICENSE){$LIC=1;}
	$delete_rule=$tpl->javascript_parse_text("{delete_cache}");
	$license_error=$tpl->javascript_parse_text("{license_error}");
	$sql="SELECT * FROM haarp_caches WHERE 1 $searchstring";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderrors($q->mysql_error);}
	
	$boot=new boostrap_form();
	
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	
		$ID=$ligne["ID"];
		$delete=imgtootltip("delete-24.png",null,"CacheDelete('{$ligne["ID"]}')");
		if($ligne["size"]>0){
			$ligne["size"]=FormatBytes($ligne["size"]/1024);
		}
	
		$tr[]="
		<tr id='cache-$ID'>
		<td style='font-size:18px;'>{$ligne["directory"]}</code></td>
		<td style='font-size:18px;'>{$ligne["size"]}</code></td>
		<td width=1% nowrap>$delete</td>
		</tr>
	
		";
	
	}
	
	echo $tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
			<thead>
			<tr>
			<th >{caches}</th>
			<th >{size}</th>
			<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>").@implode("", $tr)."</tbody></table>
<script>
var mem$t='';
	var x_Delete$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#cache-'+mem$t).remove();
}
function CacheDelete(ID){
	var LIC=$LIC;
	if(LIC==0){ alert('$license_error');return;}
	if(confirm('$delete_rule '+ID+'?')){
		mem$t=ID;
		var XHR = new XHRConnection();
		XHR.appendData('rule-delete',ID);
		XHR.sendAndLoad('$page', 'POST',x_Delete$t);
		}
	}
	</script>";
	
	}
function rules_search(){
	$sock=new sockets();
	$page=CurrentPageName();
	$haarp=new haarp();
	$tpl=new templates();
	$t=time();
	$q=new mysql_squid_builder();
	
	$searchstring=string_to_flexquery("search-rules");
	$users=new usersMenus();
	$LIC=0;if($users->CORP_LICENSE){$LIC=1;}
	$delete_rule=$tpl->javascript_parse_text("{delete_rule}");
	$license_error=$tpl->javascript_parse_text("{license_error}");
	$sql="SELECT * FROM haarp_redirpats WHERE 1 $searchstring";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderrors($q->mysql_error);}
	
	$boot=new boostrap_form();
	
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$ID=$ligne["ID"];
		$delete=imgtootltip("delete-24.png",null,"PatternDelete('{$ligne["ID"]}')");
		$Edit=$boot->trswitch("Loadjs('$page?pattern-js={$ligne["ID"]}')");
		
		$tr[]="
			<tr id='$ID'>
				<td style='font-size:16px;' $Edit><code style='font-size:16px'>{$ligne["pattern"]}</code></td>
				<td width=1% nowrap>$delete</td>
			</tr>
				
			";
		
	}
	
	echo $tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
			<thead>
			<tr>
			<th >{rules}</th>
			<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>").@implode("", $tr)."</tbody></table>
<script>
var mem$t='';
var x_Delete$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#'+mem$t).remove();
}
function PatternDelete(ID){
	var LIC=$LIC;
	if(LIC==0){ alert('$license_error');return;}
	if(confirm('$delete_rule '+ID+'?')){
		mem$t=ID;
		var XHR = new XHRConnection();
		XHR.appendData('rule-delete',ID);
		XHR.sendAndLoad('$page', 'POST',x_Delete$t);
		}
	}
	</script>";	
	
}
function events_section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	echo $boot->SearchFormGen(null,"events-search");
}


function events_search(){
	$tpl=new templates();
	$sock=new sockets();
	$search=urlencode($_GET["events-search"]);
	$datas=unserialize(base64_decode($sock->getFrameWork("haarp.php?access-events=$search")));
	krsort($datas);
	$boot=new boostrap_form();
	while (list ($key, $value) = each ($datas) ){
		$class=LineToClass($value);
		$tr[]="
		<tr class=$class>
		<td style='vertical-align:middle'>$value</td>
		</tr>
		";
	}
	echo $tpl->_ENGINE_parse_body("

			<table class='table table-bordered table-hover'>

			<thead>
			<tr>
			<th>{events}</th>
			</tr>
			</thead>". @implode("", $tr)."</table>");
}


function performances(){
	
	$q=new mysql();
	
	$sql="select domain,COUNT(*) as files,sum(filesize) as size,sum(filesize*requested) as eco, sum(requested) as hits from haarp where deleted=0 and static=0 group by domain order by 1 DESC";
	
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){senderrors($q->mysql_error);}
	
	$boot=new boostrap_form();

	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		
		$domain=$ligne["domain"]; //0
		$files=$ligne["files"]; //1
		$size=$ligne["size"]; // 2
		$eco=$ligne["eco"]; //3 
		$hits=$ligne["hits"];  //4
		if($size>0){
			$percent=($eco*100)/$size;
		}
		$percent=round($percent,2);
		$totaleconomy=$totaleconomy+ $eco;
		$totalhits =$totalhits+$hits;
		$totalcount =$totalcount+$files;
		$totalsize = $totalsize+$size;
		
		$files=FormatNumber($files);
		$hits=FormatNumber($hits);
		$eco=FormatBytes($eco/1024);
		$size=FormatBytes($size/1024);
		
		$tr[]="
		<tr>
			<td style='font-size:18px'>$domain ($percent%)</td>
			<td style='font-size:18px' width=1% nowrap>$files</td>	
			<td style='font-size:18px' width=1% nowrap>$size</td>
			<td style='font-size:18px' width=1% nowrap>$eco</td>
			<td style='font-size:18px' width=1% nowrap>$hits</td>
		</tr>
				
		";
		
	}
	
	$totalcount=FormatNumber($totalcount);
		$totalhits=FormatNumber($totalhits);
		$totaleconomy=FormatBytes($totaleconomy/1024);
		$totalsize=FormatBytes($totalsize/1024);
	$tr[]="
	<tr style='background-color:#A5A5A5'>
	<td style='font-size:22px;font-weight:bold'>&nbsp;</td>
	<td style='font-size:22px;font-weight:bold'>$totalcount</td>
	<td style='font-size:22px;font-weight:bold'>$totalsize</td>
	<td style='font-size:22px;font-weight:bold'>$totaleconomy</td>
	<td style='font-size:22px;font-weight:bold'>$totalhits</td>
	</tr>
	
	";	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("
	
			<table class='table table-bordered'>
	
			<thead>
			<tr>
			<th>{domains}</th>
			<th>{files}</th>
			<th>{size}</th>
			<th>{economy}</th>
			<th>{hits}</th>
			</tr>
			</thead>". @implode("", $tr)."</table>");	
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}