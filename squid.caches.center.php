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
		header("content-type: application/x-javascript");
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	
	if(isset($_GET["delete-empty-js"])){delete_empty_js();exit;}
	if(isset($_GET["items"])){items();exit;}
	if(isset($_GET["move-item-js"])){move_items_js();exit;}
	
	if(isset($_GET["item-js"])){items_js();exit;}
	if(isset($_GET["enable-js"])){enable_js();exit;}
	if(isset($_POST["enable-item"])){enable_item();exit;}
	
	
	if(isset($_GET["item-popup"])){items_popup();exit;}
	if(isset($_GET["CacheTypeExplain"])){CacheTypeExplain();exit;}
	if(isset($_POST["cache_directory"])){items_save();exit;}
	if(isset($_POST["delete-item"])){items_delete();exit;}
	if(isset($_GET["delete-item-js"])){items_js_delete();exit;}
	if(isset($_POST["move-item"])){move_items();exit;}
	if(isset($_POST["empty-item"])){empty_item();exit;}
	table();
	
function delete_empty_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$users=new usersMenus();
	$ID=$_GET["ID"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT cachename FROM squid_caches_center WHERE ID='$ID'","artica_backup"));
	$title=$ligne["cachename"];
	$action_empty_cache_ask=$tpl->javascript_parse_text("{action_empty_cache_ask}");	
	$action_empty_cache_ask=str_replace("%s", $title, $action_empty_cache_ask);
	
	$t=time();
	$html="
	
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){ alert(results); return; }
		$('#flexRT{$_GET["t"]}').flexReload();
		Loadjs('squid.caches.center.empty.progress.php');
	}
	function Save$t(){
		if(!confirm('$action_empty_cache_ask')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('empty-item','$ID');
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
		
	Save$t();
		
	";
	
	echo $html;	
}

function empty_item(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?cache-center-empty={$_POST["empty-item"]}");
	
}

function items_js_delete(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		echo "alert('".$tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}")."');";
		die();
	}
	$ID=$_GET["ID"];	
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT cachename FROM squid_caches_center WHERE ID='$ID'","artica_backup"));
	$title=$ligne["cachename"];
	$action_remove_cache_ask=$tpl->javascript_parse_text("{action_remove_cache_ask}");
	$t=time();
$html="

var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	$('#flexRT{$_GET["t"]}').flexReload();
	
}
function Save$t(){
	if(!confirm('$title: $action_remove_cache_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-item','$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
			
Save$t();			
			
";

echo $html;
	
}

function enable_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	

	
	$t=time();
	header("content-type: application/x-javascript");
	$html="
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	$('#flexRT{$_GET["t"]}').flexReload();
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('enable-item','{$_GET["ID"]}');
	XHR.appendData('t','{$_GET["t"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
Save$t();
	
			";
	
			echo $html;	
	
}

function move_items_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		echo "alert('".$tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}")."');";
		die();
	}
	
	$t=time();
	header("content-type: application/x-javascript");
	$html="
	
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	$('#flexRT{$_GET["t"]}').flexReload();
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('move-item','{$_GET["ID"]}');
	XHR.appendData('t','{$_GET["t"]}');
	XHR.appendData('dir','{$_GET["dir"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
		
Save$t();
		
	";
	
	echo $html;
	
}

function move_items(){
	$q=new mysql();
	$ID=$_POST["move-item"];
	$t=$_POST["t"];
	$dir=$_POST["dir"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT zOrder,cpu FROM squid_caches_center WHERE ID='$ID'","artica_backup"));
	if(!$q->ok){echo $q->mysql_error;}
	
	$cpu=$ligne["cpu"];
	$CurrentOrder=$ligne["zOrder"];
	
	if($dir==0){
		$NextOrder=$CurrentOrder-1;
	}else{
		$NextOrder=$CurrentOrder+1;
	}
	
	$sql="UPDATE squid_caches_center SET zOrder=$CurrentOrder WHERE zOrder='$NextOrder' AND `cpu`='$cpu'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	
	$sql="UPDATE squid_caches_center SET zOrder=$NextOrder WHERE ID='$ID'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}

	$results=$q->QUERY_SQL("SELECT ID FROM squid_caches_center WHERE `cpu`='$cpu' ORDER by zOrder","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];

		$sql="UPDATE squid_caches_center SET zOrder=$c WHERE ID='$ID'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
		$c++;
	}
	
	
}

function enable_item(){
	$ID=$_POST["enable-item"];
	$q=new mysql();
	$enabled=1;
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM squid_caches_center WHERE ID='$ID'","artica_backup"));
	if($ligne["enabled"]==1){$enabled=0;}
	$q->QUERY_SQL("UPDATE squid_caches_center SET `enabled`='$enabled' WHERE ID='$ID'","artica_backup");
}


function items_delete(){
	$ID=$_POST["delete-item"];
	$q=new mysql();
	$sql="UPDATE squid_caches_center SET `remove`=1 WHERE ID=$ID";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql";return;}
}
	
function items_js(){
	header("content-type: application/x-javascript");
	
	
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		header("content-type: application/x-javascript");
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{onlycorpavailable}")."');";
		die();
	}
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	
	$ID=$_GET["ID"];
	
	$title=$tpl->_ENGINE_parse_body("{new_cache}");
	
	$q=new mysql();
	if($ID>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT cachename FROM squid_caches_center WHERE ID='$ID'","artica_backup"));
		$title=$ligne["cachename"];
	}
	
	echo "YahooWin2('940','$page?item-popup=yes&ID=$ID&t={$_GET["t"]}','$ID:$title',true)";
	
	
}

function CacheTypeExplain(){
	$t=$_GET["t"];
	$ID=intval($_GET["ID"]);
	$type=$_GET["CacheTypeExplain"];
	$EXPL["ufs"]="{cache_type_text}";
	$EXPL["aufs"]="{cache_type_text}";
	$EXPL["diskd"]="{cache_type_text}";
	$EXPL["rock"]="{SQUID_ROCK_STORE_EXPLAIN}";
	$EXPL["tmpfs"]="{SQUID_TMPFS_STORE_EXPLAIN}";
	$EXPL["Cachenull"]="{SQUID_NULL_STORE_EXPLAIN}";
	
	
	$explain=$EXPL[$type];
	$tpl=new templates();
	
	$js="
	document.getElementById('cache_dir_level2-$t').disabled=false;
	document.getElementById('cache_dir_level1-$t').disabled=false;
	document.getElementById('CPU-$t').disabled=false;
	document.getElementById('cache_directory-$t').disabled=false;
	document.getElementById('squid-cache-size-$t').disabled=false;
	";
	
	if($type=="rock"){
		$js="document.getElementById('cache_dir_level2-$t').disabled=true;
		document.getElementById('cache_dir_level1-$t').disabled=true;
		document.getElementById('CPU-$t').disabled=true;";
	}
	
	if($type=="tmpfs"){
		$js="
		document.getElementById('cache_dir_level2-$t').disabled=true;
		document.getElementById('cache_dir_level1-$t').disabled=true;
		document.getElementById('cache_directory-$t').disabled=true;
		
		";
	}
		
	if($type=='Cachenull'){
		$js="
		document.getElementById('cache_dir_level2-$t').disabled=true;
		document.getElementById('cache_dir_level1-$t').disabled=true;
		document.getElementById('cache_directory-$t').disabled=true;
		document.getElementById('squid-cache-size-$t').disabled=true;
		";		
	}
		

	if($ID>0){$js=null;}
	
	echo $tpl->_ENGINE_parse_body("
	<div class=explain style='font-size:16px'>
			<strong style='font-size:18px'>$type:</strong><hr>$explain</div>")."<script>$js</script>";
	
}
	
function items_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();

	$ID=intval($_GET["ID"]);
	
	
	$cpunumber=$users->CPU_NUMBER;
	
	for($i=1;$i<$cpunumber+1;$i++){
		$CPUZ[$i]="CPU $i";
	}
	
	$t=time();
	$bt="{add}";
	
	$cpu=1;
	$cachename=time();
	
	if($ID>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM squid_caches_center WHERE ID='$ID'","artica_backup"));
		if(!$q->ok){echo $q->mysql_error_html();}
		$cachename=$ligne["cachename"];
		$cache_directory=$ligne["cache_dir"];
		$cache_type=$ligne["cache_type"];
		$cache_size=$ligne["cache_size"];
		$cache_dir_level1=$ligne["cache_dir_level1"];
		$cache_dir_level2=$ligne["cache_dir_level2"];
		$cache_type=$ligne["cache_type"];
		$enabled=$ligne["enabled"];
		$cachename=$ligne["cachename"];
		$cpu=$ligne["cpu"];
		$bt="{apply}";
	}
	
	//default
	if($cache_directory==null){$cache_directory="/home/squid/caches/cache-".time();}
	if(!is_numeric($cache_size)){$cache_size=5000;}
	if(!is_numeric($cache_dir_level1)){$cache_dir_level1=16;}
	if(!is_numeric($cache_dir_level2)){$cache_dir_level2=256;}
	if(!is_numeric($enabled)){$enabled=1;}
	
	if($cache_size<1){$cache_size=5000;}
	if($cache_dir_level1<16){$cache_dir_level1=16;}
	if($cache_dir_level2<64){$cache_dir_level2=64;}
	if($cache_type==null){$cache_type="aufs";}
	
	$caches_types=unserialize(base64_decode($sock->getFrameWork("squid.php?caches-types=yes")));
	$caches_types[null]='{select}';
	$caches_types["rock"]="{squid_rock}";
	$caches_types["tmpfs"]="{squid_cache_memory}";
	$caches_types["Cachenull"]="{without_cache}";
	
	
	
	
	$type=$tpl->_ENGINE_parse_body(Field_array_Hash($caches_types,"cache_type-$t",$cache_type,"CheckCachesTypes$t()",null,0,"font-size:16px;padding:3px"));
	$cpus=$tpl->_ENGINE_parse_body(Field_array_Hash($CPUZ,"CPU-$t",$cpu,"blur()",null,0,"font-size:16px;padding:3px"));
	
	$browse=button("{browse}...", "Loadjs('SambaBrowse.php?no-shares=yes&field=cache_directory-$t')",16);
	if($ID>0){$browse=null;
	$perr="<p class=text-error>{cannot_modify_a_created_cache}</p>";
	}
	
	$html="
	<div id='waitcache-$t'></div>
	<div style='width:98%' class=form>
	$perr
	<table style='width:99%'>
	<tr>
		<td class=legend style='font-size:16px' nowrap>{enabled}:</td>
		<td style='font-size:16px'>" . Field_checkbox("enabled-$t",1,$enabled,"EnableCheck$t()")."</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px' nowrap>{name}:</td>
		<td>" . Field_text("cachename-$t",$cachename,"width:350px;font-size:16px;padding:3px")."</td>
		<td></td>
		<td>&nbsp;</td>
	</tr>	
				
	<tr>
		<td class=legend style='font-size:16px' nowrap>{cpu}:</td>
		<td>$cpus</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>				
	
	<tr>
		<td class=legend style='font-size:16px' nowrap>{directory}:</td>
		<td>" . Field_text("cache_directory-$t",$cache_directory,"width:350px;font-size:16px;padding:3px")."</td>
		<td>$browse</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px' nowrap>{type}:</td>
		<td>$type</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px' nowrap>{cache_size}:</td>
		<td style='font-size:16px'>" . Field_text("squid-cache-size-$t",$cache_size,"width:60px;font-size:16px;padding:3px")."&nbsp;MB</td>
		<td>&nbsp;</td>
		<td>" . help_icon('{cache_size_text}',false,'squid.index.php')."</td>
	</tr>
	<tr>
		<td class=legend nowrap style='font-size:16px'>{cache_dir_level1}:</td>
		<td>" . Field_text("cache_dir_level1-$t",$cache_dir_level1,'width:50px;font-size:16px;padding:3px')."</td>
		<td>&nbsp;</td>
		<td>" . help_icon('{cache_dir_level1_text}',false,'squid.index.php')."</td>
	</tr>
	<tr>
		<td class=legend nowrap style='font-size:16px'>{cache_dir_level2}:</td>
		<td>" . Field_text("cache_dir_level2-$t",$cache_dir_level2,'width:50px;font-size:16px;padding:3px')."</td>
		<td>&nbsp;</td>
		<td>" . help_icon('{cache_dir_level2_text}',false,'squid.index.php')."</td>
	</tr>
	<tr>
		<td align='right' colspan=4><hr>". button($bt,"AddNewCacheSave$t()",22)."</td>
	</tr>
	</table>
	<p>&nbsp;</p>
	<div id='CacheTypeExplain-$t'></div>
	<div style='font-size:12px'><i>{warn_calculate_nothdsize}</i></div>
	<script>
function CheckCachesTypes$t(){
	cachetypes=document.getElementById('cache_type-$t').value;
	CacheTypeExplain$t();
}

function CacheTypeExplain$t(){
	cachetype=document.getElementById('cache_type-$t').value;
	LoadAjaxTiny('CacheTypeExplain-$t','$page?t=$t&ID=$ID&CacheTypeExplain='+cachetype);
}
	
var x_AddNewCacheSave$t= function (obj) {
	var cacheid=$ID;
	var results=obj.responseText;
	document.getElementById('waitcache-$t').innerHTML='';
	if(results.length>3){ alert(results); return; }
	if(cacheid==0){YahooWin2Hide();}
	$('#flexRT{$_GET["t"]}').flexReload();
}
	
function AddNewCacheSave$t(){
	var enabled=1;
	var XHR = new XHRConnection();
	if(!document.getElementById('enabled-$t').checked){enabled=0;}
	XHR.appendData('cache_directory',document.getElementById('cache_directory-$t').value);
	XHR.appendData('cache_type',document.getElementById('cache_type-$t').value);
	XHR.appendData('size',document.getElementById('squid-cache-size-$t').value);
	XHR.appendData('cache_dir_level1',document.getElementById('cache_dir_level1-$t').value);
	XHR.appendData('cache_dir_level2',document.getElementById('cache_dir_level2-$t').value);
	XHR.appendData('CPU',document.getElementById('CPU-$t').value);
	XHR.appendData('cachename',document.getElementById('cachename-$t').value);
	XHR.appendData('ID','$ID');
	XHR.appendData('enabled',enabled);
	AnimateDiv('waitcache-$t');
	XHR.sendAndLoad('$page', 'POST',x_AddNewCacheSave$t);
}
	
function CheckCacheid(){
	var cacheid=$ID;
	if(cacheid>0){
		document.getElementById('cache_type-$t').disabled=true;
		document.getElementById('cache_directory-$t').disabled=true;
		document.getElementById('squid-cache-size-$t').disabled=true;
		document.getElementById('cache_type-$t').disabled=true;
		document.getElementById('cache_dir_level1-$t').disabled=true;
		document.getElementById('cache_dir_level2-$t').disabled=true;
	}
}
	
function EnableCheck$t(){
	var enabled=1;
	var cacheid=$ID;
	if(!document.getElementById('enabled-$t').checked){enabled=0;}
	
	document.getElementById('cache_directory-$t').disabled=true;
	document.getElementById('squid-cache-size-$t').disabled=true;
	document.getElementById('cache_type-$t').disabled=true;
	document.getElementById('cache_dir_level1-$t').disabled=true;
	document.getElementById('cache_dir_level2-$t').disabled=true;
	
	if(enabled==1){
		if(cacheid==0){
			document.getElementById('cache_directory-$t').disabled=false;
			document.getElementById('squid-cache-size-$t').disabled=false;
			document.getElementById('cache_type-$t').disabled=false;
			document.getElementById('cache_dir_level1-$t').disabled=false;
			document.getElementById('cache_dir_level2-$t').disabled=false;
		}
	}
	
}


CacheTypeExplain$t();
EnableCheck$t();
CheckCacheid();
</script>
";
	
echo $tpl->_ENGINE_parse_body($html);
}

function items_save(){
	
	
	$_POST=mysql_escape_line_query($_POST);
	$cache_directory=$_POST["cache_directory"];
	$cache_type=$_POST["cache_type"];
	$size=$_POST["size"];
	$cache_dir_level1=$_POST["cache_dir_level1"];
	$cache_dir_level2=$_POST["cache_dir_level2"];
	$CPU=$_POST["CPU"];
	$cachename=$_POST["cachename"];
	$enabled=$_POST["enabled"];
	$ID=$_POST["ID"];
	if($cache_type=="rock"){$CPU=0;}
	$q=new mysql();
	
	if($cache_type=="tmpfs"){
		$users=new usersMenus();
		$memMB=$users->MEM_TOTAL_INSTALLEE/1024;
		$memMB=$memMB-1500;
		if($size>$memMB){
			$size=$memMB-100;
		}
	}
	
	
	if($ID==0){
		$q->QUERY_SQL("INSERT IGNORE INTO squid_caches_center 
		(cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,zOrder)
		VALUES('$cachename',$CPU,'$cache_directory','$cache_type','$size','$cache_dir_level1','$cache_dir_level2',$enabled,0,0,1)","artica_backup");
	}else{
		$q->QUERY_SQL("UPDATE squid_caches_center SET 
			cachename='$cachename',
			cpu=$CPU,
			cache_size='$size',
			enabled=$enabled
			WHERE ID=$ID","artica_backup");
		
	}
	
if(!$q->ok){echo $q->mysql_error;}
	
	
}



function table(){
$page=CurrentPageName();
$tpl=new templates();
$users=new usersMenus();
$tt=time();
$t=$_GET["t"];
$_GET["ruleid"]=$_GET["ID"];
$cache=$tpl->javascript_parse_text("{cache}");
$directory=$tpl->_ENGINE_parse_body("{directory}");
$type=$tpl->javascript_parse_text("{type}");
$rule=$tpl->javascript_parse_text("{rule}");
$delete=$tpl->javascript_parse_text("{delete} {zone} ?");
$rewrite_rules_fdb_explain=$tpl->javascript_parse_text("{rewrite_rules_fdb_explain}");
$new_cache=$tpl->javascript_parse_text("{new_cache}");
$license=$tpl->javascript_parse_text("{artica_license}");
$rules=$tpl->javascript_parse_text("{rules}");
$cpu=$tpl->javascript_parse_text("{cpu}");
$apply=$tpl->javascript_parse_text("{apply}");
$action=$tpl->javascript_parse_text("{action}");
$restricted_ports=$tpl->javascript_parse_text("{restricted_ports}");
$title=$tpl->javascript_parse_text("{caches}");
$size=$tpl->javascript_parse_text("{size}");
$order=$tpl->javascript_parse_text("{order}");
$all=$tpl->javascript_parse_text("{all}");
$reconstruct_caches=$tpl->javascript_parse_text("{reconstruct_caches}");
$enable=$tpl->_ENGINE_parse_body("{enable}");
$refresh=$tpl->javascript_parse_text("{refresh}");
$tt=time();
$sock=new sockets();

$smp_mode_is_disabled=null;

$cpunumber=$users->CPU_NUMBER;

$q=new mysql();
$q->CheckTablesSquid();




$bts[]="{name: '$new_cache', bclass: 'add', onpress : NewRule$tt}";
$bts[]="{name: '$all', bclass: 'cpu', onpress : Bycpu0}";

$CPUZ[]="function Bycpu0(){
$('#flexRT$tt').flexOptions({url: '$page?items=yes&t=$tt&tt=$tt&cpu=0'}).flexReload();
}";

for($i=1;$i<$cpunumber+1;$i++){
	$bts[]="{name: 'CPU $i', bclass: 'cpu', onpress : Bycpu$i}";
	$CPUZ[]="function Bycpu$i(){
		$('#flexRT$tt').flexOptions({url: '$page?items=yes&t=$tt&tt=$tt&cpu=$i'}).flexReload(); 
	}";
}


$bts[]="{name: '$apply', bclass: 'Reconf', onpress : Apply$tt}";
$bts[]="{name: '$reconstruct_caches', bclass: 'recycle', onpress : ReconstructCaches$tt}";
$bts[]="{name: '$refresh', bclass: 'Reload', onpress : Refresh$tt}";






if(!$users->CORP_LICENSE){
	$bts[]="{name: '$license', bclass: 'Warn', onpress : License$tt}";
}
	
$buttons="buttons : [ ".@implode(",", $bts)." ],";
	
$html="$smp_mode_is_disabled
<input type='hidden' id='CACHE_CENTER_TABLEAU' value='flexRT$tt'>
<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
<script>
	function Start$tt(){
		$('#flexRT$tt').flexigrid({
		url: '$page?items=yes&t=$tt&tt=$tt',
		dataType: 'json',
		colModel : [
		{display: '$order', name : 'cpu', width :32, sortable : true, align: 'center'},
		{display: '$cpu', name : 'cpu', width :32, sortable : true, align: 'center'},
		{display: '$cache', name : 'cachename', width :122, sortable : true, align: 'left'},
		{display: '$directory', name : 'cache_dir', width :162, sortable : true, align: 'left'},
		{display: '$type', name : 'cache_type', width :55, sortable : true, align: 'left'},
		{display: '$size', name : 'cache_size', width :160, sortable : true, align: 'left'},
		{display: '%', name : 'percentcache', width :55, sortable : true, align: 'center'},
		{display: '$enable', name : 'enabled', width :55, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'up', width :55, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'down', width :55, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 70, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'rebuild', width : 70, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$cache', name : 'cachename'},
		{display: '$cpu', name : 'cpu'},
		{display: '$type', name : 'cache_type'},
		{display: '$directory', name : 'cache_dir'},
		],
		sortname: 'zOrder',
		sortorder: 'asc',
		usepager: true,
		title: '$title',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 450,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	
	});
	}
	
var xNewRule$tt= function (obj) {
var res=obj.responseText;
if (res.length>3){alert(res);return;}
$('#flexRT$t').flexReload();
$('#flexRT$tt').flexReload();
}
	
function Apply$tt(){
	Loadjs('squid.restart.php?ApplyConfToo=yes&ask=yes');
	
}
function Refresh$tt(){
	Loadjs('squid.refresh-status.php');
}

	
function  License$tt(){
	Loadjs('artica.license.php');
}
". @implode("\n", $CPUZ)."
	
function NewRule$tt(){
	Loadjs('$page?item-js=yes&ID=0&t=$tt',true);
}
function ReconstructCaches$tt(){
	Loadjs('squid.rebuildcahes.php');
}
	
function INOUT$tt(ID){
var XHR = new XHRConnection();
XHR.appendData('INOUT', ID);
XHR.sendAndLoad('$page', 'POST',xINOUT$tt);
}
	
function rports(){
Loadjs('squid.webauth.hotspots.restricted.ports.php',true);
}
	
function reverse$tt(ID){
	var XHR = new XHRConnection();
	XHR.appendData('reverse', ID);
	XHR.sendAndLoad('$page', 'POST',xINOUT$tt);
}
	
var x_LinkAclRuleGpid$tt= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#table-$t').flexReload();
	$('#flexRT$tt').flexReload();
	ExecuteByClassName('SearchFunction');
	}
	function FlexReloadRulesRewrite(){
	$('#flexRT$t').flexReload();
	}
	
	function MoveRuleDestination$tt(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('rules-destination-move', mkey);
	XHR.appendData('direction', direction);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
	}
	
	function MoveRuleDestinationAsk$tt(mkey,def){
	var zorder=prompt('Order',def);
	if(!zorder){return;}
	var XHR = new XHRConnection();
	XHR.appendData('rules-destination-move', mkey);
	XHR.appendData('rules-destination-zorder', zorder);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
	}
	Start$tt();
	
	</script>
	";
		echo $html;
}

function items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$users=new usersMenus();
	$q=new mysql();
	$sock=new sockets();


	$t=$_GET["t"];
	$search='%';
	$table="squid_caches_center";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	if(isset($_GET["cpu"])){
		if($_GET["cpu"]>0){
			$FORCE_FILTER="AND `cpu`={$_GET["cpu"]}";
		}
	}
	
	
	if($q->COUNT_ROWS($table, "artica_backup")==0){
		$squid=new squidbee();
		$cachename=basename($squid->CACHE_PATH);
		$q->QUERY_SQL("INSERT IGNORE INTO $table (cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache)
		VALUES('$cachename',1,'$squid->CACHE_PATH','$squid->CACHE_TYPE','2000','128','256',1,0,0)","artica_backup");
		if(!$q->ok){json_error_show($q->mysql_error."<br>",1);}
	}


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];

	}else{
		$total = $q->COUNT_ROWS($table,"artica_backup");
		
	}
	


	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,"artica_backup");

	$no_rule=$tpl->_ENGINE_parse_body("{no_rule}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",1);}
	if(mysql_num_rows($results)==0){json_error_show("no data $sql",1);}

	$fontsize="16";

	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$options_text=null;
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-item-js=yes&ID={$ligne["ID"]}&t={$_GET["t"]}')");
		$reconstruct=imgsimple("dustbin-32.png",null,"Loadjs('$MyPage?delete-empty-js=yes&ID={$ligne["ID"]}&t={$_GET["t"]}')");
		$ID=$ligne["ID"];
		$cachename=$ligne["cachename"];
		$cache_dir=$ligne["cache_dir"];
		$cache_type=$ligne["cache_type"];
		$cache_size=$ligne["cache_size"];
		$percentcache=$ligne["percenttext"];
		$cpu=$ligne["cpu"];
		$explainSMP=null;
		
		if(!$users->CORP_LICENSE){$link=null;$delete=null;}
		if($cache_type=="tmpfs"){$cache_dir="-";}
		$cache_size=FormatBytes($cache_size*1024);
		
		if($ligne["enabled"]==0){$color="#9A9A9A";}
		if($ligne["remove"]==1){$color="#C7C7C7";$delete="&nbsp;";}
		$usedcache=FormatBytes($ligne["usedcache"]/1024);
	
		
		$link="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?item-js=yes&ID=$ID&t={$_GET["t"]}',true);\"
		style='font-size:{$fontsize}px;font-weight:normal;color:$color;text-decoration:underline'
		>";
		
		
		

		
		$up=imgsimple("arrow-up-32.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=0&t={$_GET["t"]}')");
		$down=imgsimple("arrow-down-32.png",null,"Loadjs('$MyPage?move-item-js=yes&ID={$ligne["ID"]}&dir=1&t={$_GET["t"]}')");
		$enable=Field_checkbox("enable-{$ligne['ID']}", 1,$ligne["enabled"],"Loadjs('$MyPage?enable-js=yes&ID={$ligne["ID"]}&t={$_GET["t"]}')");
		
		
		if($cache_type=="Cachenull"){
			$usedcache="-";
			$cache_size="-";
			$percentcache="0";
			$cache_type="-";
			$cache_dir="-";
			$reconstruct="&nbsp;";
		}
		
		
		if($ligne["remove"]==1){$link=null;}
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>{$ligne["zOrder"]}</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link$cpu</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link$cachename</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link$cache_dir</a></span><i><strong style='color:$color'>$explainSMP</i></strong>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link$cache_type</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link$usedcache/$cache_size</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$link{$percentcache}%</a></span>",$enable,
						$up,$down,
						
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",$reconstruct)
		);
	}


	echo json_encode($data);

}
