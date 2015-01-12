<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsWebStatisticsAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["list"])){list_items();exit;}
	if(isset($_GET["alias-js"])){alias_js();exit;}
	if(isset($_GET["alias-popup"])){alias_popup();exit;}
	if(isset($_POST["ID"])){save();exit;}
	if(isset($_GET["delete-alias-js"])){delete_js();exit;}
	if(isset($_POST["delete-alias-id"])){delete_perform();exit;}
	if(isset($_POST["defaults"])){add_defaults();exit;}
table();


function Save(){
	$tpl=new templates();
	
	$sql=FORM_CONSTRUCT_SQL_FROM_POST("sarg_aliases","ID");
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}

function delete_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$delete=$tpl->javascript_parse_text("{delete} {item}");
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `pattern` FROM sarg_aliases WHERE ID='$ID'"));
	$html="
var xDelete{$t}{$ID} = function (obj) {
	var ID=$ID;
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#flexRT$t').flexReload();
}

function Delete{$t}{$ID}(){
	if( !confirm('$delete {$ligne["pattern"]} ?') ){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-alias-id','$ID');
	XHR.sendAndLoad('$page', 'POST',xDelete{$t}{$ID},true);
}
Delete{$t}{$ID}();";
echo $html;
}
function delete_perform(){
	$ID=$_POST["delete-alias-id"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM sarg_aliases WHERE ID='$ID'");
}

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$title=$tpl->_ENGINE_parse_body("{sarg_aliases}");
	$group=$tpl->_ENGINE_parse_body("{group2}");
	$addr=$tpl->_ENGINE_parse_body("{addr}");
	$new_alias=$tpl->_ENGINE_parse_body("{new_alias}");
	$pattern=$tpl->_ENGINE_parse_body("{pattern}");
	$replace=$tpl->_ENGINE_parse_body("{replace}");
	$appy=$tpl->_ENGINE_parse_body("{apply}");
	$defaults=$tpl->javascript_parse_text("{defaults}");
	$q=new mysql_squid_builder();
	$sql="CREATE TABLE IF NOT EXISTS `sarg_aliases` ( `ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `pattern` VARCHAR( 128 ) NOT NULL, `group` VARCHAR( 90 ) NOT NULL, `replace` VARCHAR(90), UNIQUE KEY `pattern` (`pattern`), KEY `group` (`group`), KEY `replace` (`replace`) )  ENGINE = MYISAM; ";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();}
	
	$buttons="
	buttons : [
	{name: '$new_alias', bclass: 'add', onpress : Add$t},
	{name: '$defaults', bclass: 'add', onpress : defaults$t},
	],";

	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	<script>
	$(document).ready(function(){
	var md5H='';
	$('#flexRT$t').flexigrid({
	url: '$page?list=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$pattern', name : 'pattern', width : 280, sortable : false, align: 'left'},
	{display: '$group', name : 'group', width :156, sortable : true, align: 'left'},
	{display: '$replace', name : 'replace', width :94, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'delete', width : 52, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$pattern', name : 'pattern'},
	{display: '$group', name : 'group'},
	{display: '$replace', name : 'replace'},
	],
	sortname: 'pattern',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});
function FlexReloadDNSMASQHOSTS(){
$('#flexRT$t').flexReload();
}

function BlackList$t(){
Loadjs('squid.dns.items.black.php');
}
var xdefaults$t = function (obj) {
	
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#flexRT$t').flexReload();
}
function defaults$t(){
	var XHR = new XHRConnection();
	XHR.appendData('defaults','yes');
	XHR.sendAndLoad('$page', 'POST',xdefaults$t,true);
}

function DnsmasqDeleteAddress(md5,num){
md5H=md5;
var XHR = new XHRConnection();
XHR.appendData('DnsmasqDeleteAddress',num);
XHR.sendAndLoad('$page', 'GET',x_AddDnsMasqHostT);
}

function Add$t(){
Loadjs('$page?alias-js=yes&ID=0&t=$t',true);

}

function Apply$t(){
Loadjs('system.services.cmd.php?APPNAME=APP_DNSMASQ&action=restart&cmd=%2Fetc%2Finit.d%2Fdnsmasq&appcode=DNSMASQ');
}

var x_AddDnsMasqHostT= function (obj) {
var results=obj.responseText;
if(results.length>0){alert(results);return;}
$('#row'+md5H).remove();
}

</script>
";

echo $tpl->_ENGINE_parse_body($html);
}

function alias_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	if($ID==0){
		$title=$tpl->javascript_parse_text("{new_host}");
		echo "YahooWin3('550','$page?alias-popup=yes&ID=$ID&t=$t','$title');";
	}else{
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `pattern` FROM sarg_aliases WHERE ID='$ID'"));
		$title=$tpl->javascript_parse_text("{website}:{$ligne["pattern"]}");
		echo "YahooWin3('550','$page?alias-popup=yes&ID=$ID&t=$t','$title');";
	}
}
function alias_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$time=time();
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$btname="{add}";
	if($ID>0){
		$btname="{apply}";
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM sarg_aliases WHERE ID='$ID'"));
	}


	$html="
	<center id='id-$time' class=form style='width:95%'>
	<table style='width:99%' >
	<tbody>
	<tr>
	<td class=legend style='font-size:18px'>{pattern}:</td>
	<td>" . Field_text("pattern-$time",$ligne["pattern"],"font-size:18px;padding:13px;x") . "</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{group2}:</td>
		<td>" . Field_text("group-$time",$ligne["group"],"font-size:18px",false,"SaveCK$time(event)") . "</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{string}:</td>
		<td>" . Field_text("replace-$time",$ligne["replace"],"font-size:18px",false,"SaveCK$time(event)") . "</td>
	</tr>				
	<tr>
	<td colspan=2 align='right'><hr>". button($btname,"Save$time()",22)."</td>
</tr>
</tbody>
</table>
</center>
<script>
	var xSave$time= function (obj) {
		var ID=$ID;
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		$('#flexRT{$_GET["t"]}').flexReload();
		if(ID==0){YahooWin3Hide();}
}
function Save$time(){
	var XHR = new XHRConnection();
	XHR.appendData('ID','$ID');
	XHR.appendData('pattern',document.getElementById('pattern-$time').value);
	XHR.appendData('group',document.getElementById('group-$time').value);
	XHR.sendAndLoad('$page', 'POST',xSave$time,true);
}
function SaveCK$time(e){
	if(checkEnter(e)){ Save$time(); }
}

</script>
";
echo $tpl->_ENGINE_parse_body($html);
}

function list_items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	

	$t=$_GET["t"];
	$search='%';
	$table="sarg_aliases";
	if($q->COUNT_ROWS("sarg_aliases")==0){add_defaults();}

	$page=1;
	$FORCE_FILTER=null;

	$total=0;


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$total = $q->COUNT_ROWS("sarg_aliases");
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}


	if(!is_numeric($rp)){$rp=50;}
	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);

	$no_rule=$tpl->_ENGINE_parse_body("{no data}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){
		if(strpos($q->mysql_error, "doesn't exist")>0){$q->CheckTables();$results = $q->QUERY_SQL($sql);}
	}

	if(!$q->ok){	json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("no data");}

	$fontsize="16";

	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-alias-js=yes&ID={$ligne["ID"]}&t=$t&tt={$_GET["tt"]}')");

		$editjs="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?alias-js=yes&ID={$ligne["ID"]}&t={$_GET["t"]}',true);\"
		style='font-size:{$fontsize}px;font-weight:bold;color:$color;text-decoration:underline'>";

		
		$pattern=$ligne["pattern"];
		$group=$ligne["group"];
		$replace=$ligne["replace"];

		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$pattern</a><br><i style='font-size:12px'>&nbsp;$grouptype</i></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$group</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$replace</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
		);
	}


	echo json_encode($data);

}

function add_defaults(){
	
	
	$array["image-hosting"]["FILE"]="class.squid.category.array.imagehosting.inc";
	$array["image-hosting"]["CLASS"]="array_category_imagehosting";
	
	$array["Arts"]["CLASS"]="array_category_arts";
	$array["Arts"]["FILE"]="class.squid.category.array.arts.inc";
	
	$array["Tracker"]["CLASS"]="array_category_tracker";
	$array["Tracker"]["FILE"]="class.squid.category.array.tracker.inc";
	
	$array["Porn"]["CLASS"]="array_category_porn";
	$array["Porn"]["FILE"]="class.squid.category.array.porn.inc";
	
	$array["smallads"]["CLASS"]="array_category_smallads";
	$array["smallads"]["FILE"]="class.squid.category.array.smallads.inc";
	
	$array["smallads"]["CLASS"]="array_category_travel";
	$array["smallads"]["FILE"]="class.squid.category.array.travel.inc";		
	
	$array["industry"]["CLASS"]="array_category_industry";
	$array["industry"]["FILE"]="class.squid.category.array.industry.inc";
	
	$array["isp"]["CLASS"]="array_category_isp";
	$array["isp"]["FILE"]="class.squid.category.array.isp.inc";
	
	$array["malware"]["CLASS"]="array_category_malware";
	$array["malware"]["FILE"]="class.squid.category.array.malware.inc";

	$array["movies"]["CLASS"]="array_category_movies";
	$array["movies"]["FILE"]="class.squid.category.array.movies.inc";	
	
	$array["music"]["CLASS"]="array_category_music";
	$array["music"]["FILE"]="class.squid.category.array.music.inc";
	
	$array["advertisement"]["CLASS"]="array_category_publicite";
	$array["advertisement"]["FILE"]="class.squid.category.array.publicite.inc";
	
	$array["remote-control"]["CLASS"]="array_category_remotecontrol";
	$array["remote-control"]["FILE"]="class.squid.category.array.remotecontrol.inc";
	
	$array["school"]["CLASS"]="array_category_school";
	$array["school"]["FILE"]="class.squid.category.array.school.inc";
	
	$array["computing"]["CLASS"]="array_category_sciencecomputing";
	$array["computing"]["FILE"]="class.squid.category.array.sciencecomputing.inc";
	
	$array["shopping"]["CLASS"]="array_category_shopping";
	$array["shopping"]["FILE"]="class.squid.category.array.shopping.inc";
	
	$array["sports"]["CLASS"]="array_category_sport";
	$array["sports"]["FILE"]="class.squid.category.array.sport.inc";	
	
	$array["spyware"]["CLASS"]="array_category_spyware";
	$array["spyware"]["FILE"]="class.squid.category.array.spyware.inc";
	
	$array["travel"]["CLASS"]="array_category_travel";
	$array["travel"]["FILE"]="class.squid.category.array.travel.inc";
	
	$array["updates"]["CLASS"]="array_category_updatesites";
	$array["updates"]["FILE"]="class.squid.category.array.updatesites.inc";
	
	$array["web-plugins"]["CLASS"]="array_category_webplugins";
	$array["web-plugins"]["FILE"]="class.squid.category.array.webplugins.inc";

	$array["web-radio"]["CLASS"]="array_category_webradio";
	$array["web-radio"]["FILE"]="class.squid.category.array.webradio.inc";
	
	$array["health"]["CLASS"]="array_category_health";
	$array["health"]["FILE"]="class.squid.category.array.health.inc";	
	
	$array["file-hosting"]["CLASS"]="array_category_filehosting";
	$array["file-hosting"]["FILE"]="class.squid.category.array.filehosting.inc";	

	$array["dynamic"]["CLASS"]="array_category_dynamic";
	$array["dynamic"]["FILE"]="class.squid.category.array.dynamic.inc";	
	
	$array["downloads"]["CLASS"]="array_category_downloads";
	$array["downloads"]["FILE"]="class.squid.category.array.downloads.inc";
	
	
	$array["chat"]["CLASS"]="array_category_chat";
	$array["chat"]["FILE"]="class.squid.category.array.chat.inc";	
	
	$array["blogs"]["CLASS"]="array_category_blog";
	$array["blogs"]["FILE"]="class.squid.category.array.blog.inc";

	$array["audio-video"]["CLASS"]="array_category_audiovideo";
	$array["audio-video"]["FILE"]="class.squid.category.array.audiovideo.inc";	
	
	$array["socialnet"]["CLASS"]="array_category_socialnet";
	$array["socialnet"]["FILE"]="class.squid.category.array.socialnet.inc";	
	
	$array["web-apps"]["CLASS"]="array_category_webapps";
	$array["web-apps"]["FILE"]="class.squid.category.array.webapps.inc";
	
	$array["translators"]["CLASS"]="array_category_translator";
	$array["translators"]["FILE"]="class.squid.category.array.translator.inc";
	
	$array["remote-control"]["CLASS"]="array_category_remotecontrol";
	$array["remote-control"]["FILE"]="class.squid.category.array.remotecontrol.inc";
	
	$array["ssl-sites"]["CLASS"]="array_category_sslsites";
	$array["ssl-sites"]["FILE"]="class.squid.category.array.sslsites.inc";
	
	$array["pictures"]["CLASS"]="array_category_pictureslib";
	$array["pictures"]["FILE"]="class.squid.category.array.pictureslib.inc";	
	
	$array["books"]["CLASS"]="array_category_books";
	$array["books"]["FILE"]="class.squid.category.array.books.inc";	
	
	$array["dictionaries"]["CLASS"]="array_category_dictionaries";
	$array["dictionaries"]["FILE"]="class.squid.category.array.dictionaries.inc";
	
	$array["womanbrand"]["CLASS"]="array_category_womanbrand";
	$array["womanbrand"]["FILE"]="class.squid.category.array.womanbrand.inc";
	
	$array["webtv"]["CLASS"]="array_category_webtv";
	$array["webtv"]["FILE"]="class.squid.category.array.webtv.inc";	
	
	$array["search"]["CLASS"]="array_category_searchengines";
	$array["search"]["FILE"]="class.squid.category.array.searchengines.inc";
	
	
	
	while (list ($cat, $main) = each ($array) ){
		
		include_once(dirname(__FILE__)."/ressources/{$main["FILE"]}");
		$method = $main["CLASS"];
		$class = new $method(); 
		$subarray=$class->return_array(true);
		
		
		while (list ($www, $none) = each ($subarray) ){
			$www=trim($www);
			if(substr($www, 0,1)=="."){$www=substr($www, 1,strlen($www));}
			$l[]="('*.$www','$cat','')";
		}
		
		
		
		
		
		
	}
	
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("INSERT IGNORE INTO `sarg_aliases` (`pattern`,`group`,`replace`) VALUES ".@implode($l, ","));
	
}
