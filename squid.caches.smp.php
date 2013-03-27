<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.squid.inc');

$users=new usersMenus();
if(!$users->AsSquidAdministrator){die("NO PRIVS");}
if(!$users->CORP_LICENSE){die("NO PRIVS");}
if(isset($_GET["section-smp"])){section_smp();exit;}
if(isset($_POST["cache_directory"])){addcache_save();exit;}

if(isset($_GET["addcache-js"])){addcache_js();exit;}
if(isset($_GET["addcache-popup"])){addcache_popup();exit;}

if(isset($_GET["delete-cache-js"])){delete_cache_js();exit;}
if(isset($_POST["delete-cache"])){delete_cache();exit;}

if(isset($_GET["abort-delete-cache-js"])){delete_cache_abort_js();exit;}
if(isset($_POST["abort-delete-cache"])){delete_cache_abort();exit;}



if(isset($_GET["events-js"])){events_js();exit;}
if(isset($_GET["events-table"])){events_table();exit;}
if(isset($_GET["cachelogs-events-list"])){events_search();exit;}

if(isset($_GET["back-js"])){back_js();exit;}
if(isset($_POST["back"])){back();exit;}

if(isset($_GET["apply-js"])){apply_js();exit;}
if(isset($_POST["apply-cache"])){apply_perform();exit;}
if(isset($_GET["prc"])){prc();exit;}
page();


function events_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$uuid=$_GET["uuid"];
	$title=$tpl->javascript_parse_text("{events}");
	echo "YahooWin5('770','$page?events-table=yes','$title')";
	
}

	
function back_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$cpunum=$_GET["cpunum"];
	$uuid=$_GET["uuid"];
	$t=time();
	$apply_confirm_text=$tpl->javascript_parse_text("{back_to_single_proc_explain}");
	$html="
	var x_Perform$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){
		alert(results);
	}
	
	RefreshTab('squid_main_caches_new');
	}
	
	
	function Perform$t(){
	if(!confirm('$apply_confirm_text')){return;}
	var XHR = new XHRConnection();
	AnimateDiv('main-smp-toolbar');
	XHR.appendData('back','yes');
	XHR.sendAndLoad('$page', 'POST',x_Perform$t);
	}
	
	Perform$t();
	";
	
	echo $html;		
	
}	
	

function addcache_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();	
	$cpunum=$_GET["cpunum"];
	$uuid=$_GET["uuid"];
	$cacheid=$_GET["cacheid"];
	
	
	if($cacheid==null){$title=$tpl->_ENGINE_parse_body("{cpu} $cpunum {create_a_new_cache}");}else{
		$q=new mysql();
		$sql="SELECT * FROM squid_caches32 WHERE `cacheid`='$cacheid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if($q->ok){
			$base=basename($ligne["cache_directory"]);
			$size=$ligne["size"];
			$title=$tpl->_ENGINE_parse_body("$base::{$size}G::{cpu}:$cpunum");
		}else{
			$title=$q->mysql_error;
		}
	}
	
	
	$html="YahooWin2('889','$page?addcache-popup=yes&cpunum=$cpunum&uuid={$_GET["uuid"]}&cacheid=$cacheid','$title')";
	echo $html;
}

function apply_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$apply_confirm_text=$tpl->javascript_parse_text("{squidcach32_applyexplain}");
	$html="
	var x_Perform$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){
			alert(results);
		}
		
		RefreshTab('squid_main_caches_new');
	}
	
	
	function Perform$t(){
		if(!confirm('$apply_confirm_text')){return;}
		var XHR = new XHRConnection();
		AnimateDiv('main-smp-toolbar');
		XHR.appendData('apply-cache','yes');
		XHR.sendAndLoad('$page', 'POST',x_Perform$t);
	}
	
	Perform$t();
	";
	
	echo $html;	
	
}

function apply_perform(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?caches-smp-create=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{success}");
}

function delete_cache_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$cacheid=$_GET["delete-cache-js"];	
	$q=new mysql();
	$sql="SELECT * FROM squid_caches32 WHERE `cacheid`='$cacheid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$delete_text=$tpl->javascript_parse_text("{delete_cache32_ask}");	
	$base=basename($ligne["cache_directory"]);
	$size=$ligne["size"];	
	$delete_text=str_replace("%s", "$base ({$size}G)", $delete_text);
	$t=time();
	
	$html="
		var x_Delete$t= function (obj) {
			var cacheid='$cacheid';
			var results=obj.responseText;
			
			if(results.length>3){
				alert(results);
				}
			
			RefreshTab('squid_main_caches_new');
			
		}			
	
	
		function Delete$t(){
			if(!confirm('$delete_text')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('delete-cache','$cacheid');
			XHR.sendAndLoad('$page', 'POST',x_Delete$t);
		}
		
		 Delete$t();	
	";
	
	echo $html;
	
}

function delete_cache_abort_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$cacheid=$_GET["abort-delete-cache-js"];
	$q=new mysql();
	$sql="SELECT * FROM squid_caches32 WHERE `cacheid`='$cacheid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$delete_text=$tpl->javascript_parse_text("{abort_remove_task}");
	$t=time();
	
	$html="
	var x_Delete$t= function (obj) {
	var cacheid='$cacheid';
	var results=obj.responseText;
		
	if(results.length>3){
	alert(results);
	}
		
	RefreshTab('squid_main_caches_new');
		
	}
	
	
	function Delete$t(){
	if(!confirm('$delete_text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('abort-delete-cache','$cacheid');
	XHR.sendAndLoad('$page', 'POST',x_Delete$t);
	}
	
	Delete$t();
	";
	
	echo $html;	
	
}

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	if(!isset($_GET["uuid"])){$_GET["uuid"]=$sock->getframework("cmd.php?system-unique-id=yes");}	
	$CPU_NUMBER=$sock->getFrameWork("services.php?CPU-NUMBER=yes");
	
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	
	if($DisableAnyCache==1){
		$html=FATAL_ERROR_SHOW_128("{all_cache_method_are_globally_disabled}");
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	
	$maintoolbar="
	<center id='main-smp-toolbar'>
	<table style='width:85%' class=form>
		<tr>
			<td align='center'>".imgtootltip("apply-config-44.gif","{apply}","Loadjs('$page?apply-js=yes')",null,time())."</td>
			<td align='center'>".imgtootltip("events-64.png","{events}","Loadjs('$page?events-js=yes&uuid={$_GET["uuid"]}')",null,time()+3)."</td>							
			<td align='center'>".imgtootltip("48-refresh.png","{refresh}","RefreshTab('squid_main_caches_new')",null,time()+2)."</td>
			<td align='center'>".imgtootltip("48-cancel.png","{back_to_single_proc}","Loadjs('$page?back-js=yes&uuid={$_GET["uuid"]}')",null,time()+2)."</td>
		</tr>
	</table>
	</center>";		

	

	$t=time();
	
	$tpl=new templates();
	$sql="SELECT * FROM cachestatus WHERE uuid='{$_GET["uuid"]}'";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$array[trim($ligne["cachedir"])]["CURSIZE"]=FormatBytes($ligne["currentsize"]);
		$array[trim($ligne["cachedir"])]["MAXSIZE"]=trim($ligne["maxsize"]);
		$array[trim($ligne["cachedir"])]["POURC"]=trim($ligne["pourc"]);
	
	}
	
	$arraySerialize=urlencode(base64_decode(serialize($array)));
	for($i=1;$i<$CPU_NUMBER+1;$i++){
		
		$tr[]="<div id='section-smp-$i'></div>";
		$js[]="
			function SectionSMP$t$i(){	
				LoadAjax('section-smp-$i','$page?section-smp&procnum=$i&uuid={$_GET["uuid"]}&arraySerialize=$arraySerialize');
		
		}";
		
		$jswait[]="setTimeout(\"SectionSMP$t$i()\",1{$i}00);";
		
		
		
		
	}
	
	$html="<center>$maintoolbar<div style='width:700px'>".CompileTr2($tr)."</div></center>
	<script>\n".@implode("\n", $js)."\n".@implode("\n", $jswait)."</script>
			
	";
	echo $tpl->_ENGINE_parse_body($html);
	$sock=new sockets();
	$sock->getFrameWork("squid.php?refresh-caches-infos=yes");	
	
}

function section_smp(){
	$tpl=new templates();
	
	
	if(isset($_GET["arraySerialize"])){$array=unserialize(base64_decode($_GET["arraySerialize"]));}

	
		
	echo $tpl->_ENGINE_parse_body(ParagrapheCPU($_GET["procnum"],$_GET["uuid"],$array));
	
}


function delete_cache(){
	$cacheid=$_POST["delete-cache"];
	$sql="UPDATE squid_caches32 SET ToDelete=1, enabled=0 WHERE cacheid='$cacheid'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	
}
function delete_cache_abort(){
	$cacheid=$_POST["abort-delete-cache"];
	$sql="UPDATE squid_caches32 SET ToDelete=0, enabled=1 WHERE cacheid='$cacheid'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");	
}

function prc(){
	
	
	if(!isset($_GET["arraySerialize"])){
		$sql="SELECT * FROM cachestatus WHERE uuid='{$_GET["uuid"]}'";
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$arrayCaches[trim($ligne["cachedir"])]["CURSIZE"]=FormatBytes($ligne["currentsize"]);
			$arrayCaches[trim($ligne["cachedir"])]["MAXSIZE"]=trim($ligne["maxsize"]);
			$arrayCaches[trim($ligne["cachedir"])]["POURC"]=trim($ligne["pourc"]);
	
		}
	}else{
		$arrayCaches=unserialize(base64_decode($_GET["arraySerialize"]));
	}
	
	$q=new mysql();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT cache_directory FROM squid_caches32 WHERE cacheid='{$_GET["prc"]}'","artica_backup"));
	if($GLOBALS["VERBOSE"]){echo "{$_GET["prc"]} -> {$ligne["cache_directory"]}<br>\n";}
	if(!isset($arrayCaches[$ligne["cache_directory"]])){return;}
	echo"<strong style='color:#BD0000'>{$arrayCaches[$ligne["cache_directory"]]["POURC"]}%  - {$arrayCaches[$ligne["cache_directory"]]["CURSIZE"]}</strong>";
}

function ParagrapheCPU($cpunum,$uuid,$arrayCaches){
	$page=CurrentPageName();
	
	$hds[]="
	<table style='width:100%'>
	<tr>
		<td width=1%><img src='img/hard-drive-add-32.png'></td>
		<td><a href='javascript:blur();' OnClick=\"javascript:Loadjs('$page?addcache-js=yes&cpunum=$cpunum&uuid={$_GET["uuid"]}');\"
		style='font-size:12px;text-decoration:underline'>{create_a_new_cache}</a></td>
		<td width=1%>&nbsp;</td>
	</tr>
	</table>
	";
	
	$q=new mysql();
	$t=time();
	$results=$q->QUERY_SQL("SELECT * FROM squid_caches32 WHERE uuid='$uuid' AND cpunum='$cpunum'","artica_backup");
	if(!$q->ok){$hds[]="<strong>$q->mysql_error</strong>";}
	$timeout=0;
	
	if(is_array($arrayCaches)){
		$arrayCachesQ="&arraySerialize=".urlencode(base64_encode(serialize($arrayCaches)));
	}	
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$color="black";
		$icon="usb-disk-32.png";
		$hdname=basename($ligne["cache_directory"]);
		$size=$ligne["size"];
		$type=$ligne["cache_type"];
		$delete=imgtootltip("ed_delete.gif","{delete}","Loadjs('$page?delete-cache-js={$ligne["cacheid"]}')");
		$timeout++;


		$prc="<span id='prc-{$ligne["cacheid"]}'></span>";
		$jsprc="function prc$t(){
		LoadAjaxTiny('prc-{$ligne["cacheid"]}','$page?prc={$ligne["cacheid"]}&uuid={$_GET["uuid"]}$arrayCachesQ');
		}
		setTimeout(\"prc$t()\",1{$timeout}00);";	

		
		if($ligne["enabled"]==0){
			$color="#B3B3B3";
			$prc=null;
			$jsprc=null;
		}else{
			$GSize=$GSize+$size;
		}
		
		if($ligne["Building"]==1){$icon="preloader.gif";$delete=null;$jsprc=null;$prc=null;}
		if($ligne["Building"]==2){
			if($ligne["enabled"]==1){
				$icon="disk_share_enable-32.png";
			}
		}
		
		$js="<a href='javascript:blur();' OnClick=\"javascript:Loadjs('$page?addcache-js=yes&cacheid={$ligne["cacheid"]}&cpunum=$cpunum&uuid={$_GET["uuid"]}');\"
		style='font-size:12px;text-decoration:underline;color:$color'>";
		
		if($ligne["ToDelete"]==1){
			$icon="usb-disk-32-2-del.png";
			$delete=imgtootltip("ed_delete_grey.gif","{delete}","Loadjs('$page?abort-delete-cache-js={$ligne["cacheid"]}')");
			$js="<span style='font-size:12px;color:$color'>";
			$prc=null;
			$jsprc=null;
		}
		

		$jsEnd[]=$jsprc;	
				
		
		
		$hds[]="
			
			<table style='width:100%;border: 1px solid #DDDDDD;border-radius: 5px 5px 5px 5px;margin-top: 5px;'>	
			<tr>
				<td width=1%><img src='img/$icon'></td>
				<td>$js$hdname</a><div style='color:$color;font-weight:bold;text-align:right'>$type - {$size}G</div>
				<div style=';text-align:right'>$prc</div></span></td>
				<td width=1%>$delete</td>
			</tr>
			</table>
			
			";
		
		$t=$t+1;
		

		
	}
	
	$refresh="<tr><td colspan=3 align='right'>". imgtootltip("16-refresh.png","{refresh}","LoadAjax('section-smp-$cpunum','$page?section-smp&procnum=$cpunum&uuid={$_GET["uuid"]}')")."</td>";
	
	if($GSize>0){
		$refresh=null;
		$GlobalSize="
		<table style='width:100%;margin-top:5px;'>		
		<tr>
		
		<td width=99% align='right'><strong style='font-size:16px;'>{total}&nbsp;{$GSize}G</td>
		<td width=99% align='right'>".imgtootltip("16-refresh.png","{refresh}","LoadAjax('section-smp-$cpunum','$page?section-smp&procnum=$cpunum&uuid={$_GET["uuid"]}')")."</td>
		</tr>
		</table>";
	}
	
	$t=time();
	$toolbox=@implode("\n", $hds);
	$t=time();
	$html="
	<table style='width:99%' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/system-64.org.png'></td>
			<td valign='top' width=99%>
				<div style='font-size:16px;font-weight:bold;margin-bottom:15px'>{cpu} $cpunum</div>
					$toolbox	
					$GlobalSize
			</td>
		</tr>
		$refresh
	</table> 
				
	<script>
	".@implode("\n", $jsEnd)."</script>
		
				
	";

	return $html;
	
}

function addcache_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$cpunum=$_GET["cpunum"];
	$uuid=$_GET["uuid"];
	$cacheid=$_GET["cacheid"];
	
	$t=time();
	$bt="{add}";
	if(strlen($cacheid)>5){
		$q=new mysql();
		$sql="SELECT * FROM squid_caches32 WHERE `cacheid`='$cacheid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$cache_directory=$ligne["cache_directory"];
		$cache_size=$ligne["size"];
		$cache_dir_level1=$ligne["cache_dir_level1"];
		$cache_dir_level2=$ligne["cache_dir_level2"];
		$cache_maxsize=$ligne["cache_maxsize"];
		$cache_type=$ligne["cache_type"];
		$enabled=$ligne["enabled"];
		$bt="{apply}";
	}
	
	//default
	if($cache_directory==null){$cache_directory="/home/squid/caches/cache-$cpunum-".time();}
	if(!is_numeric($cache_size)){$cache_size=5;}
	if(!is_numeric($cache_dir_level1)){$cache_dir_level1=16;}
	if(!is_numeric($cache_dir_level2)){$cache_dir_level2=256;}
	if(!is_numeric($cache_maxsize)){$cache_maxsize=0;}
	if(!is_numeric($enabled)){$enabled=1;}
	
	if($cache_size<1){$cache_size=5;}
	if($cache_dir_level1<16){$cache_dir_level1=16;}
	if($cache_dir_level2<64){$cache_dir_level2=64;}
	if($cache_type==null){$cache_type="diskd";}
	
	$caches_types=unserialize(base64_decode($sock->getFrameWork("squid.php?caches-types=yes")));
	$caches_types[null]='{select}';
	unset($caches_types["rock"]);
	$type=$tpl->_ENGINE_parse_body(Field_array_Hash($caches_types,"cache_type-$t",$cache_type,"CheckCachesTypes$t()",null,0,"font-size:16px;padding:3px"));	
	
	
	$html="
	<div id='waitcache-$t'></div>
	<table style='width:99%' class=form>
		<tr>
			<td class=legend style='font-size:16px' nowrap>{enabled}:</td>
			<td style='font-size:16px'>" . Field_checkbox("enabled-$t",1,$enabled,"EnableCheck$t()")."</td>
			<td>&nbsp;</td>
			<td>" . help_icon('{cache_size_text}',false,'squid.index.php')."</td>
		</tr>	
	<tr>
		<td class=legend style='font-size:16px' nowrap>{directory}:</td>
		<td>" . Field_text("cache_directory-$t",$cache_directory,"width:350px;font-size:16px;padding:3px")."</td>
		<td>". button("{browse}...", "Loadjs('SambaBrowse.php?no-shares=yes&field=cache_directory-$t')",12)."</td>
	</tr>
		<tr>
			<td class=legend style='font-size:16px' nowrap>{type}:</td>
			<td>$type</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>			
		<tr>
			<td class=legend style='font-size:16px' nowrap>{cache_size}:</td>
			<td style='font-size:16px'>" . Field_text("squid-cache-size-$t",$cache_size,"width:60px;font-size:16px;padding:3px")."&nbsp;G</td>
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
			<td class=legend nowrap style='font-size:16px'>{max_objects_size}:</td>
			<td  style='font-size:16px'>" . Field_text("cache_maxsize-$t",$cache_maxsize,'width:90px;font-size:16px;padding:3px',null,"calculateSize()",null,false,null)."&nbsp;Mbytes&nbsp;<span id='squid-maxsize-vals'></span></td>
			<td>&nbsp;</td>
			<td>" . help_icon('{squid_rock_maxsize}',false,'squid.index.php')."</td>
		</tr>					
		<tr>
		<td align='right' colspan=4><hr>". button($bt,"AddNewCacheSave$t()",14)."</td>
		</tr>							
	</table>
	<p>&nbsp;</p>
	<div style='font-size:12px'><i>{warn_calculate_nothdsize}</i></div>		
	<script>
		function CheckCachesTypes$t(){
			cachetypes=document.getElementById('cache_type-$t').value;
			
		}
		
	var x_AddNewCacheSave$t= function (obj) {
			var cacheid='$cacheid';
			var results=obj.responseText;
			document.getElementById('waitcache-$t').innerHTML='';
			if(results.length>3){
				alert(results);
				return;
				}
			if(cacheid.length==0){YahooWin2Hide();}
			RefreshTab('squid_main_caches_new');
			
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
			XHR.appendData('cache_maxsize',document.getElementById('cache_maxsize-$t').value);
			XHR.appendData('cpunum','$cpunum');
			XHR.appendData('uuid','$uuid');
			XHR.appendData('cacheid','$cacheid');
			XHR.appendData('enabled',enabled);
			AnimateDiv('waitcache-$t');
			XHR.sendAndLoad('$page', 'POST',x_AddNewCacheSave$t);
		}

	function CheckCacheid(){
		var cacheid='$cacheid';
		if(cacheid.length>2){
			document.getElementById('cache_directory-$t').disabled=true;
			document.getElementById('squid-cache-size-$t').disabled=true;
			document.getElementById('cache_type-$t').disabled=true;
			document.getElementById('cache_dir_level1-$t').disabled=true;
			document.getElementById('cache_dir_level2-$t').disabled=true;
			
		
		}
	}
	
	function EnableCheck$t(){
			var enabled=1;
			var cacheid='$cacheid';
			if(!document.getElementById('enabled-$t').checked){enabled=0;}
			if(cacheid.length==0){return;}
			
			
			document.getElementById('cache_directory-$t').disabled=true;
			document.getElementById('squid-cache-size-$t').disabled=true;
			document.getElementById('cache_type-$t').disabled=true;
			document.getElementById('cache_dir_level1-$t').disabled=true;
			document.getElementById('cache_dir_level2-$t').disabled=true;
			document.getElementById('cache_maxsize-$t').disabled=true;		
			var cacheid='$cacheid';
			if(cacheid.length>2){
				if(enabled==1){
					document.getElementById('cache_maxsize-$t').disabled=false;	
				}
			}else{
				if(enabled==1){
					document.getElementById('cache_directory-$t').disabled=false;
					document.getElementById('squid-cache-size-$t').disabled=false;
					document.getElementById('cache_type-$t').disabled=false;
					document.getElementById('cache_dir_level1-$t').disabled=false;
					document.getElementById('cache_dir_level2-$t').disabled=false;
					document.getElementById('cache_maxsize-$t').disabled=false;					
				
				}
			
			}
	}

	 CheckCacheid();		
	EnableCheck$t();
	</script>							
				
	";
	
	echo $tpl->_ENGINE_parse_body($html);

}

function addcache_save(){
	
	while (list ($num, $pid) = each ($_POST)){
		$_POST[$num]=trim($pid);
	}
	
	$md5=md5($_POST["cache_directory"].$_POST["uuid"].$_POST["cpunum"]);
	$sql="INSERT INTO squid_caches32 (cacheid,cache_directory,cache_type,cache_maxsize,size,cache_dir_level1,
		cache_dir_level2,uuid,cpunum,enabled)
		VALUES ('$md5','{$_POST["cache_directory"]}','{$_POST["cache_type"]}','{$_POST["cache_maxsize"]}','{$_POST["size"]}','{$_POST["cache_dir_level1"]}',
		'{$_POST["cache_dir_level2"]}','{$_POST["uuid"]}','{$_POST["cpunum"]}',{$_POST["enabled"]})";
	
	if(strlen($_POST["cacheid"]>2)){
		$sql="UPDATE squid_caches32 SET 
			cache_maxsize='{$_POST["cache_maxsize"]}',
			enabled='{$_POST["enabled"]}'
			WHERE cacheid='{$_POST["cacheid"]}'
			";
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}


function events_table(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	
	$title=$tpl->_ENGINE_parse_body("{today}: {artica_events} ".date("H")."h");
	
	$t=time();
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	
	<script>
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?cachelogs-events-list=yes',
	dataType: 'json',
	colModel : [
	{display: '$zdate', name : 'zDate', width :120, sortable : true, align: 'left'},
	{display: 'PID', name : 'pid', width :32, sortable : false, align: 'left'},
	{display: '$events', name : 'events', width : 530, sortable : false, align: 'left'},
	],
	
	searchitems : [
	{display: '$events', name : 'events'}
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 754,
	height: 420,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
	
	function SelectGrid2(com, grid) {
	var items = $('.trSelected',grid);
	var id=items[0].id;
	id = id.substring(id.lastIndexOf('row')+3);
	if (com == 'Select') {
	LoadAjax('table-1-selected','$page?familysite-show='+id);
	}
	}
	
	$('table-1-selected').remove();
	$('flex1').remove();
	
	</script>
	
	
	";
	
	echo $html;
	
	}	
	function events_search(){
		$page=CurrentPageName();
		$tpl=new templates();
		$sock=new sockets();
	
	
	
		if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
		if(isset($_POST['page'])) {$page = $_POST['page'];}
		if(isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
		if($_POST["query"]<>null){
			$search=base64_encode($_POST["query"]);
			$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?cache-smtp-logs=$search&rp={$_POST["rp"]}")));
			$total=count($datas);
	
		}else{
			$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?cache-smtp-logs=&rp={$_POST["rp"]}")));
			$total=count($datas);
		}
	
	
		$pageStart = ($page-1)*$rp;
	
		if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){
			if($_POST["sortname"]=="zDate"){
				if($_POST["sortorder"]=="desc"){
					krsort($datas);
				}
			}
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	
			$data = array();
			$data['page'] = $page;
			$data['total'] = $total;
			$data['rows'] = array();
			while (list ($key, $line) = each ($datas) ){
	
				if(!preg_match("#^(.+?)\s+\[([0-9]+)\]\s+(.+?)::([0-9]+)\s+(.+)#", $line,$re)){continue;}
				

				$date=$re[1];
				$pid=$re[2];
				$function=$re[3];
				$linenum=$re[4];
				$line=$re[5];
				$line=str_replace("Starting......: [SMP]","",$line);
				$line=str_replace("Starting......: [SMP] Starting......: [SMP]","",$line);	
				$line=str_replace("Starting......:","",$line);	
				$line=str_replace("[SYS]:","",$line);	
				$line=str_replace("[WATCH]:","",$line);	
				$line=str_replace("[VER]:","",$line);	
				$line=$tpl->_ENGINE_parse_body($line);
				$line=trim($line);
				$data['rows'][] = array(
						'id' => md5($line),
						'cell' => array($date,$pid,"<div>$line</div><div><i>function:$function line $linenum</div>" )
				);
			}
			echo json_encode($data);
	}
	
function back(){
	$sock=new sockets();
	$sock->SET_INFO("DisableSquidSNMPMode", 1);
	$sock->getFrameWork("squid.php?rebuild-caches=yes");
}
