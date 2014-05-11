<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	if(isset($_POST["EnableSargGenerator"])){EnableSargGenerator_unique_save();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["sarg-freeweb"])){freeweb();exit;}
	if(isset($_GET["params"])){parameters();exit;}
	if(isset($_GET["topsites_num"])){parameters_save();exit;}
	
	if(isset($_GET["sarg-report-type"])){parameters_reports();exit;}
	
	
	
	if(isset($_GET["sarg-reports"])){sarg_reports();exit;}
	if(isset($_GET["report_type"])){sarg_reports_add();exit;}
	if(isset($_GET["delete_report_type"])){sarg_reports_del();exit;}
	
	
	
	if(isset($_GET["members"])){members();exit;}
	
	if(isset($_GET["remote-users"])){remote_users();exit;}
	if(isset($_GET["local-users"])){local_users();exit;}
	if(isset($_GET["member-add"])){members_add();exit;}
	if(isset($_GET["member-delete"])){members_delete();exit;}		
	
	if(isset($_GET["tools"])){tools();exit;}
	if(isset($_GET["run-compile"])){task_run_sarg();exit;}
	
	if(isset($_GET["events"])){events();exit;}
	if(isset($_GET["inline"])){popup();exit;}
	if(isset($_GET["weekly-run-js"])){weekly_run_js();exit;}
	if(isset($_GET["monthly-run-js"])){monthly_run_js();exit;}
	if(isset($_GET["index-run-js"])){index_run_js();exit;}
	
js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_SARG}");
	$html="YahooWin4('930','$page?popup=yes','$title');";	
	echo $html;
	}
	
function EnableSargGenerator_unique_save(){
	
	$sock=new sockets();
	$sock->SET_INFO("EnableSargGenerator", $_POST["EnableSargGenerator"]);
	
}
function weekly_run_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$sock->getFrameWork("squid.php?sarg-weekly=yes");
	$title=$tpl->javascript_parse_text("{weekly_reports} {succes}");
	echo "alert('$title');";
	
}

function index_run_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$sock->getFrameWork("squid.php?sarg-index=yes");
	$title=$tpl->javascript_parse_text("{build_index_page_sarg} {succes}");
	echo "alert('$title');";	
}

function monthly_run_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$sock->getFrameWork("squid.php?sarg-monthly=yes");
	$title=$tpl->javascript_parse_text("{monthly_reports} {succes}");
	echo "alert('$title');";

}


function popup(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$array["status"]="{status}";
	$array["params"]="{parameters}";
	$array["sarg-reports"]="{sarg_reports}";
	$array["sarg-freeweb"]="{websites}";
	//$array["members"]="{members}";
	//$array["tools"]="{tools}";
	$array["events"]="{events}";
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="events"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"sarg.events.php?popup=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
		
	}
	
	$id=time();
	
	echo build_artica_tabs($html, "sarg_tabs")."<script>LeftDesign('statistics-white-256-opac20.png');</script>";
		
	
	
}

function status(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$q=new mysql();
	$APP_SARG=$tpl->_ENGINE_parse_body("{APP_SARG}");
	$EnableSargGenerator=$sock->GET_INFO("EnableSargGenerator");
	if(!is_numeric($EnableSargGenerator)){$EnableSargGenerator=0;}
	
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?sarg-ini-status=yes')));
	$tr[]=DAEMON_STATUS_ROUND("APP_SARG",$ini,null,1);
	
	$disabled=$tpl->_ENGINE_parse_body("{disabled}");
	if($EnableSargGenerator==0){
		$version=Paragraphe32("noacco:$APP_SARG", "$disabled", "blur()", "warning-panneau-32.png");
		
	}else{
		$version=$sock->getFrameWork("sarg.php?version=yes");
		$status=unserialize($sock->GET_INFO("SargDirStatus"));
		$ff[]="version: $version";
		$ff[]="{size}: ".FormatBytes($status["SIZE"]);
		$ff[]="{files}: ".FormatNumber($status["FILES"])." {free}: ".FormatNumber($array["F_FREE"]);
		$ff[]="{free}: {$status["FREE"]}M";
		
		
		$version=Paragraphe32("noacco:$APP_SARG", "noacco:<div style='font-size:12px'>".$tpl->_ENGINE_parse_body(@implode("<br>", $ff))."</div>", "blur()", "ok32.png");
	}
	
	$sql="SELECT COUNT(*) as tcount FROM freeweb WHERE `groupware`='SARG'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){echo $q->mysql_error_html();}
	$count=$ligne["tcount"];
	if($count==0){
		$tr[]=Paragraphe32("no_website", "no_freeweb_service_explain", "Loadjs('freeweb.edit.php?hostname=&force-groupware=SARG');", "warning-panneau-32.png");
		
	}
	
	$tr[]=Paragraphe32("build_index_page", "build_index_page_sarg",
			"Loadjs('$page?index-run-js=yes');", "48-run.png");
	
	$tr[]=Paragraphe32("weekly_reports", "weekly_reports_execute", 
			"Loadjs('$page?weekly-run-js=yes');", "48-run.png");
	
	$tr[]=Paragraphe32("monthly_reports", "monthly_reports_execute",
			"Loadjs('$page?monthly-run-js=yes');", "48-run.png");	
	
	
	$tr[]=$version;
	
	
	$tableau=CompileTr3($tr,true);
	
	$html="<div class=explain style='font-size:14px'>{APP_SARG_TXT}</div>$tableau";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function freeweb(){
	$t=time();
	$page=CurrentPageName();
	$html="<div id='$t'></div>
	<script>LoadAjax('$t','freeweb.servers.php?force-groupware=SARG',true);</script>";
	echo $html;
	
	
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}

function local_users(){
	$stringtofind=$_GET["local-users"];
	$ldap=new clladp();
	$page=CurrentPageName();
	$tpl=new templates();		
	//if($stringtofind==null){$stringtofind="*";}
	$hash=$ldap->UserSearch(null,$stringtofind);
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("SargAccess")));	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th colspan=4>{members}</th>
	</tr>
</thead>
<tbody class='tbody'>";		
	
	for($i=0;$i<$hash[0]["count"];$i++){
		$ligne=$hash[0][$i];
		$uid=$ligne["uid"][0];
		if($uid==null){continue;}
		if($uid=="squidinternalauth"){continue;}
		if($array[$uid]<>null){continue;}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
			$ct=new user($uid);
			$js=MEMBER_JS($uid,1,1);
			$img=imgtootltip("contact-48.png","{view}",$js);
			$add=imgtootltip("plus-24.png","{add}","SargAddMember('$uid')");
			$html=$html."
			<tr class=$classtr>
			<td width=1%>$img</td>
			<td><strong style='font-size:14px'>$ct->DisplayName</td>
			<td width=1%>$add</td>
			</tr>
			";		
		
	}
	$html=$html."</tbody></table>
	<script>
	
	var x_SargAddMember= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			refresh_remote_users();
	}		
		function SargAddMember(uid){
			var XHR = new XHRConnection();
			XHR.appendData('member-add',uid);	
			document.getElementById('remote-users').innerHTML='<center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center>';
			XHR.sendAndLoad('$page', 'GET',x_SargAddMember);	
		}
		
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function members_add(){
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("SargMembers")));
	$array[$_GET["member-add"]]=$_GET["member-add"];
	$sock->SaveConfigFile(base64_encode(serialize($array)),"SargMembers");
	$sock->getFrameWork("cmd.php?sarg-config=yes");	
	
}

function members_delete(){
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("SargMembers")));
	unset($array[$_GET["member-delete"]]);
	$sock->SaveConfigFile(base64_encode(serialize($array)),"SargMembers");
	$sock->getFrameWork("cmd.php?sarg-config=yes");		
}


function members(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$users=new usersMenus();
	$sock=new sockets();
	if(!$users->ARTICA_META_ENABLED){	
		$createUser=imgtootltip("identity-add-48.png","{add user explain}","Loadjs('create-user.php');");
	}else{
		if($sock->GET_INFO("AllowArticaMetaAddUsers")==1){
			$createUser=imgtootltip("identity-add-48.png","{add user explain}","Loadjs('create-user.php');");
		}
	}
	$html="
	<div class=explain>{SARG_MEMBERS_EXPLAIN}</div>
	<table style='width:100%'>
	<tr>
	<td valign='top' width=50%>
		<center>". Field_text("local_user_search",null,"font-size:14px;padding:3px",null,null,null,false,"SearchLocalUserEnter(event)")."</center>
		<hr>
		<div style='width:100%;height:300px;overflow:auto' id='local-users'></div>
		<div style='text-align:right;width:100%;padding-top:5px;border-top:1px solid #CCCCCC'>$createUser</div>
	</td>
	<td valign='top'  width=50%>
		
		<div style='width:100%;height:300px;overflow:auto' id='remote-users'></div>
	</td>
	</tr>
	<script>
		function refresh_remote_users(){
			LoadAjax('remote-users','$page?remote-users=yes');
		}
		
		function RefreshLocalMember(){
			var search=escape(document.getElementById('local_user_search').value);
			LoadAjax('local-users','$page?local-users='+search);
		}
		
		function SearchLocalUserEnter(e){
			if(checkEnter(e)){RefreshLocalMember();}
		}
		
		refresh_remote_users();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function remote_users(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ip_address=$tpl->_ENGINE_parse_body("{ip_address}");		
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("SargMembers")));
	$ldap=new clladp();
	$array[$ldap->ldap_admin]=$ldap->ldap_admin;
	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th colspan=2>{sarg_access}</th>
	<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
	if(is_array($array)){
		while (list ($uid, $conf) = each ($array) ){
			if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
			$ct=new user($uid);
			$delete=imgtootltip("delete-32.png","{delete}","RemoteDelMember('$uid')");
			$js=MEMBER_JS($uid,1,1);
			if($uid==$ldap->ldap_admin){$delete="&nbsp;";$js=null;}
			$img=imgtootltip("contact-48.png","{view}",$js);

			$html=$html."
			<tr class=$classtr>
			<td width=1%>$img</td>
			<td><strong style='font-size:14px'>$ct->DisplayName</td>
			<td width=1%>$delete</td>
			</tr>
			";
			
		}
	}
	
	$html=$html."</tbody></table>
	<script>
		RefreshLocalMember();
		
	var x_RemoteDelMember= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			refresh_remote_users();
		}
				
		function RemoteDelMember(uid){
			var XHR = new XHRConnection();
			XHR.appendData('member-delete',uid);	
			document.getElementById('remote-users').innerHTML='<center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center>';
			XHR.sendAndLoad('$page', 'GET',x_RemoteDelMember);	
		}

		
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}


function tools(){
	$page=CurrentPageName();
	$tpl=new templates();
	$exec=Paragraphe("64-refresh.png","{RUN_COMPILATION}","{RUN_COMPILATION_SARG}","javascript:RunSarg();");
	
	$tr[]=$exec;
	$tr[]=$visible_hostname;
	$tr[]=$templates_error;
	$tr[]=$squid_advanced_parameters;
	$tr[]=$enable_squid_service;
	$tr[]=$sarg;	
	

	
$tables[]="<table style='width:100%'><tr>";
$t=0;
while (list ($key, $line) = each ($tr) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td valign='top'>$line</td>";
		if($t==3){$t=0;$tables[]="</tr><tr>";}
		
}
if($t<3){
	for($i=0;$i<=$t;$i++){
		$tables[]="<td valign='top'>&nbsp;</td>";				
	}
}
				
$tables[]="</table>";	
	
	$html=$html.implode("\n",$tables);	
	$html=$html."
	<script>
		var x_RunSarg= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			RefreshTab('sarg_tabs');
		}
				
		function RunSarg(){
			var XHR = new XHRConnection();
			XHR.appendData('run-compile','yes');	
			XHR.sendAndLoad('$page', 'GET',x_RunSarg);	
		}
	
	</script>
	";

	echo $tpl->_ENGINE_parse_body($html);
	
}
function task_run_sarg(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?sarg-run=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{apply_upgrade_help}");
	
}


function events(){
	
	$pointer="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";
$html="
			<table class=tableView style='width:95%'>
				<thead class=thead>
				<tr>
					<th width=1% nowrap colspan=2>{context}:</td>
					<th width:99%'></td>			
				</tr>
				</thead>
				";	
	$q=new mysql();
	$sql="UPDATE `events` SET context='proxy' where context='squid'";
	$q->QUERY_SQL($sql,"artica_events");
	$sql="SELECT * FROM events WHERE context='proxy' and text LIKE '%SARG%' ORDER BY ID DESC";
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo("<H3>$sql $q->mysql_error</H3>");}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($cl=="oddRow"){$cl=null;}else{$cl="oddRow";}
		$js="Loadjs('/artica.events.php?external-events={$ligne["ID"]}');";
		
		
		$html=$html . "<tr class=$cl>
		<td width=1%><img src='img/fw_bold.gif'></td>
		<td valign='middle' nowrap style='font-size:14px;' width=1% nowrap>{$ligne["zDate"]}</td>
		<td valign='top' width=99%><div style='font-size:14px;text-decoration:underline' width=1% nowrap $pointer OnClick=\"javascript:$js\">{$ligne["text"]}</div></td>
		</tR>
		
		";
		
	}
	$html=$html . "</tbody></table>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}



function parameters(){
	$sock=new sockets();
	$tpl=new templates();
	$EnableSargGenerator=$sock->GET_INFO("EnableSargGenerator");
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");
	if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	$SargConfig=unserialize(base64_decode($sock->GET_INFO("SargConfig")));
	$SargConfig=SargDefault($SargConfig);
	if(!is_numeric($EnableSargGenerator)){$EnableSargGenerator=0;}
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

//topusers topsites sites_users users_sites date_time denied auth_failures site_user_time_date downloads

$LASTLOGS[30]="1 {month}";
$LASTLOGS[60]="2 {months}";
$LASTLOGS[90]="3 {months}";
$LASTLOGS[120]="4 {months}";
$LASTLOGS[150]="5 {months}";
$LASTLOGS[360]="1 {year}";

if(!is_numeric($SargConfig["lastlog"])){$SargConfig["lastlog"]=90;}
if($SargConfig["lastlog"]<1){$SargConfig["lastlog"]=90;}

$html="
<div id='sarg-config-form'>
<table style='width:99%' class=form>
<tr>
	<td class=legend style='font-size:14px'>{enable}:</td>
	<td>". Field_checkbox("EnableSargGenerator",1,$EnableSargGenerator,"CheckSargFormSave()")."</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px'>{DisableArticaProxyStatistics}:</td>
	<td>". Field_checkbox("DisableArticaProxyStatistics",1,$DisableArticaProxyStatistics)."</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px'>{directory}:</td>
	<td>". Field_text("SargOutputDir",$SargOutputDir,"font-size:14px;padding:3px;width:320px")."</td>
	<td>". button_browse("SargOutputDir")."</td>
</tr>			
<tr>
	<td class=legend style='font-size:14px'>{language}:</td>
	<td>". Field_array_Hash($langs,"language",$SargConfig["language"],"style:font-size:14px;padding;3px")."</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px'>{sarg_title}:</td>
	<td>". Field_text("title",$SargConfig["title"],"font-size:14px;padding:3px;width:220px")."</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px'>{enable_graphs}:</td>
	<td>". Field_checkbox("graphs",1,$SargConfig["graphs"])."</td>
	<td>&nbsp;</td>
</tr>

<tr>
	<td class=legend style='font-size:14px'>{sarg_user_ip}:</td>
	<td>". Field_checkbox("user_ip",1,$SargConfig["user_ip"])."</td>
	<td>&nbsp;</td>
</tr>

<tr>
	<td class=legend style='font-size:14px'>{sarg_resolve_ip}:</td>
	<td>". Field_checkbox("resolve_ip",1,$SargConfig["resolve_ip"])."</td>
	<td>&nbsp;</td>
</tr>

<tr>
	<td class=legend style='font-size:14px'>{sarg_records_without_userid}:</td>
	<td>". Field_array_Hash($overwrite_report,"records_without_userid",$SargConfig["records_without_userid"],"style:font-size:14px;padding;3px")."</td>
	<td>&nbsp;</td>
</tr>

<tr>
	<td class=legend style='font-size:14px'>{sarg_long_url}:</td>
	<td>". Field_checkbox("long_url",1,$SargConfig["long_url"])."</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px'>{sarg_topsites_num}:</td>
	<td>". Field_text("topsites_num",$SargConfig["topsites_num"],"font-size:14px;padding:3px;width:90px")."</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px'>{sarg_topuser_num}:</td>
	<td>". Field_text("topuser_num",$SargConfig["topuser_num"],"font-size:14px;padding:3px;width:90px")."</td>
	<td>". help_icon("{sarg_topuser_exp}")."</td>
</tr>


<tr>
	<td class=legend style='font-size:14px'>{topsites_sort_order}:</td>
	<td>". Field_array_Hash($sort_order,"topsites_sort_order",$SargConfig["topsites_sort_order"],"style:font-size:14px;padding;3px")."</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px'>{index_sort_order}:</td>
	<td>". Field_array_Hash($sort_order,"index_sort_order",$SargConfig["index_sort_order"],"style:font-size:14px;padding;3px")."</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px'>{sarg_date_format}:</td>
	<td>". Field_array_Hash($sarg_date_format,"date_format",$SargConfig["date_format"],"style:font-size:14px;padding;3px")."</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class=legend style='font-size:14px'>{sarg_lastlog}:</td>
	<td>". Field_array_Hash($LASTLOGS,"lastlog",$SargConfig["lastlog"],"style:font-size:14px;padding;3px")."</td>
	<td>&nbsp;</td>
</tr>

<tr>
	<td colspan=3 align='right'>". button("{apply}","SargSaveConf()",16)."</td>
</tr>
</table>
</div>
<script>

		var x_SargSaveConf= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			RefreshTab('sarg_tabs');
		}
		
	function CheckSargForm(){
		
		document.getElementById('language').disabled=true;
		document.getElementById('title').disabled=true;
		document.getElementById('topsites_num').disabled=true;
		document.getElementById('topuser_num').disabled=true;
		document.getElementById('topsites_sort_order').disabled=true;
		document.getElementById('index_sort_order').disabled=true;
		document.getElementById('date_format').disabled=true;
		document.getElementById('lastlog').disabled=true;
		document.getElementById('graphs').disabled=true;
		document.getElementById('user_ip').disabled=true;
		document.getElementById('long_url').disabled=true;
		document.getElementById('resolve_ip').disabled=true;
		document.getElementById('records_without_userid').disabled=true;
		document.getElementById('DisableArticaProxyStatistics').disabled=true;
		document.getElementById('SargOutputDir').disabled=true;
		
		
		
		if(!document.getElementById('EnableSargGenerator').checked){return;}
		document.getElementById('language').disabled=false;
		document.getElementById('title').disabled=false;
		document.getElementById('topsites_num').disabled=false;
		document.getElementById('topuser_num').disabled=false;
		document.getElementById('topsites_sort_order').disabled=false;
		document.getElementById('index_sort_order').disabled=false;
		document.getElementById('date_format').disabled=false;
		document.getElementById('lastlog').disabled=false;
		document.getElementById('graphs').disabled=false;
		document.getElementById('user_ip').disabled=false;
		document.getElementById('long_url').disabled=false;
		document.getElementById('resolve_ip').disabled=false;	
		document.getElementById('records_without_userid').disabled=false;
		document.getElementById('DisableArticaProxyStatistics').disabled=false;	
		document.getElementById('SargOutputDir').disabled=false;
	
	}
	
	
	var x_none= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		CheckSargForm();
		}	
	
	function CheckSargFormSave(){
		var XHR = new XHRConnection();
		var EnableSargGenerator=0;
		if(document.getElementById('EnableSargGenerator').checked){EnableSargGenerator=1;}
		XHR.appendData('EnableSargGenerator',EnableSargGenerator);	
		XHR.sendAndLoad('$page', 'POST',x_none);
	}
		
		
	function SargSaveConf(){
			var EnableSargGenerator=$EnableSargGenerator; 
			var EnableSargGeneratorCK=0;
			if(document.getElementById('EnableSargGenerator').checked){EnableSargGeneratorCK=1;}
			var XHR = new XHRConnection();
			XHR.appendData('uuid','{$_REQUEST["uuid"]}');
			XHR.appendData('group_id','{$_REQUEST["group_id"]}');		
			XHR.appendData('language',document.getElementById('language').value);
			XHR.appendData('title',document.getElementById('title').value);
			XHR.appendData('topsites_num',document.getElementById('topsites_num').value);
			XHR.appendData('topuser_num',document.getElementById('topuser_num').value);
			XHR.appendData('topsites_sort_order',document.getElementById('topsites_sort_order').value);
			XHR.appendData('index_sort_order',document.getElementById('index_sort_order').value);
			XHR.appendData('date_format',document.getElementById('date_format').value);
			XHR.appendData('lastlog',document.getElementById('lastlog').value);
			XHR.appendData('records_without_userid',document.getElementById('records_without_userid').value);
			XHR.appendData('SargOutputDir',document.getElementById('SargOutputDir').value);
			
			if(EnableSargGeneratorCK!=EnableSargGenerator){
				if(confirm('$warn_squid_restart')){
					XHR.appendData('RESTART_SQUID','yes');
				}
			}
			
			
			if(document.getElementById('graphs').checked){XHR.appendData('graphs',1);}else{XHR.appendData('graphs',0);}
			if(document.getElementById('user_ip').checked){XHR.appendData('user_ip',1);}else{XHR.appendData('user_ip',0);}
			if(document.getElementById('long_url').checked){XHR.appendData('long_url',1);}else{XHR.appendData('long_url',0);}
			if(document.getElementById('resolve_ip').checked){XHR.appendData('resolve_ip',1);}else{XHR.appendData('resolve_ip',0);}
			if(document.getElementById('EnableSargGenerator').checked){XHR.appendData('EnableSargGenerator',1);}else{XHR.appendData('EnableSargGenerator',0);}
			if(document.getElementById('DisableArticaProxyStatistics').checked){XHR.appendData('DisableArticaProxyStatistics',1);}else{XHR.appendData('DisableArticaProxyStatistics',0);}
			AnimateDiv('sarg-config-form');
			XHR.sendAndLoad('$page', 'GET',x_SargSaveConf);
			}
			
	CheckSargForm();
</script>


";

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
	
	
}

function SargDefault($SargConfig){
	if($SargConfig["report_type"]==null){$SargConfig["report_type"]="topusers topsites sites_users users_sites date_time denied auth_failures site_user_time_date downloads";}
	if(!is_numeric($SargConfig["topuser_num"])){$SargConfig["topuser_num"]=0;}
	if(!is_numeric($SargConfig["long_url"])){$SargConfig["long_url"]=0;}
	if(!is_numeric($SargConfig["graphs"])){$SargConfig["graphs"]=1;}
	if(!is_numeric($SargConfig["user_ip"])){$SargConfig["user_ip"]=1;}
	if(!is_numeric($SargConfig["topsites_num"])){$SargConfig["topsites_num"]=100;}
	if(!is_numeric($SargConfig["topuser_num"])){$SargConfig["topuser_num"]=0;}
	if(!is_numeric($SargConfig["lastlog"])){$SargConfig["lastlog"]=0;}
	if($SargConfig["topsites_sort_order"]==null){$SargConfig["topsites_sort_order"]="D";}
	if($SargConfig["index_sort_order"]==null){$SargConfig["index_sort_order"]="D";}
	if($SargConfig["topsites_num"]<2){$SargConfig["topsites_num"]=100;}
	
	if($SargConfig["date_format"]==null){$SargConfig["date_format"]="e";}
	if($SargConfig["language"]==null){$SargConfig["language"]="English";}
	if($SargConfig["title"]==null){$SargConfig["title"]="Squid User Access Reports";}
	if($SargConfig["records_without_userid"]==null){$SargConfig["records_without_userid"]="ip";}
	
	
	
	
	return $SargConfig;
}

function sarg_reports(){
	$page=CurrentPageName();
	$tpl=new templates();
$reports=array(
	null=>"{select}",
	"topusers"=>"{sarg_topusers}",
	"topsites"=>"{sarg_topsites}",
	"sites_users"=>"{sarg_sites_users}",
	"date_time"=>"{sarg_date_time}",
	"denied"=>"{sarg_denied}",
	"auth_failures"=>"{sarg_auth_failures}",
	"site_user_time_date"=>"{sarg_site_user_time_date}",
	"downloads"=>"{sarg_downloads}"
);	

$html="
<table>
<tr>
	<td class=legend nowrap>{report_type}:</td>
	<td width=1%>". Field_array_Hash($reports,"sarg_report_type",null,"style:font-size:14px;padding;3px")."</td>
	<td width=99%>". imgtootltip("plus-24.png","{add}","SargAddReport()")."</td>
</tr>
</table>

<div id='sarg-report-type' style='width:100%;height:450px;overflow:auto'></div>

<script>
		var x_SargAddReport= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			SargReportList();
		}
				
		function SargAddReport(){
			var XHR = new XHRConnection();
			XHR.appendData('uuid','{$_REQUEST["uuid"]}');
			XHR.appendData('group_id','{$_REQUEST["group_id"]}');		
			XHR.appendData('report_type',document.getElementById('sarg_report_type').value);	
			document.getElementById('sarg-report-type').innerHTML='<center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center>';
			XHR.sendAndLoad('$page', 'GET',x_SargAddReport);	
		}
		
		function SargReportList(){
			LoadAjax('sarg-report-type','$page?uuid={$_REQUEST["uuid"]}&group_id={$_REQUEST["group_id"]}&sarg-report-type=yes');
		
		}
SargReportList();

</script>

";

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
}


function parameters_reports(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$SargConfig=unserialize(base64_decode($sock->GET_INFO("SargConfig")));
	$SargConfig=SargDefault($SargConfig);
	$reports=explode(" ",$SargConfig["report_type"]);
	$html="
			<table class=tableView style='width:95%'>
				<thead class=thead>
				<tr>
					<th width=99% nowrap colspan=2>{reports}:</td>
					<th width:1%'></td>			
				</tr>
				</thead>
				";		
	
	
	while (list ($key, $line) = each ($reports) ){
		if($line==null){continue;}
		if($cl=="oddRow"){$cl=null;}else{$cl="oddRow";}
		$delete="SargDeleteReport('$line');";
		
		
		$html=$html . "
		<tr class=$cl>
			<td width=1%><img src='img/chart-grant-22.png'></td>
			<td valign='middle' nowrap style='font-size:14px;' width=99% nowrap>{sarg_{$line}}</td>
			<td valign='top' width=1%>". imgtootltip("delete-32.png","{delete}",$delete)."</td>
		</tr>
		";
		}
		
		
		
		$html=$html . "</table>
		
		<script>
		
		function SargDeleteReport(report){
			var XHR = new XHRConnection();
			XHR.appendData('uuid','{$_REQUEST["uuid"]}');
			XHR.appendData('group_id','{$_REQUEST["group_id"]}');		
			XHR.appendData('delete_report_type',report);	
			AnimateDiv('sarg-report-type');
			XHR.sendAndLoad('$page', 'GET',x_SargAddReport);	
		}			
	</script>";		
		
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	}
	
function sarg_reports_add(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$SargConfig=unserialize(base64_decode($sock->GET_INFO("SargConfig")));	
	$SargConfig=SargDefault($SargConfig);
	$reports=explode(" ",$SargConfig["report_type"]);
	while (list ($key, $line) = each ($reports) ){
		if($line==null){continue;}
		$p[$line]=$line;
	}
	
	$p[$_GET["report_type"]]=$_GET["report_type"];
	while (list ($key, $line) = each ($p) ){
		$a[]=$line;
	}	
	$SargConfig["report_type"]=implode(" ",$a);
	$sock->SaveConfigFile(base64_encode(serialize($SargConfig)),"SargConfig");	
	
}

function sarg_reports_del(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$SargConfig=unserialize(base64_decode($sock->GET_INFO("SargConfig")));	
	$SargConfig=SargDefault($SargConfig);
	$reports=explode(" ",$SargConfig["report_type"]);
	while (list ($key, $line) = each ($reports) ){
		if($line==null){continue;}
		$p[$line]=$line;
	}
	
	unset($p[$_GET["delete_report_type"]]);
	while (list ($key, $line) = each ($p) ){
		$a[]=$line;
	}	
	$SargConfig["report_type"]=implode(" ",$a);
	$SargConfig=SargDefault($SargConfig);
	$sock->SaveConfigFile(base64_encode(serialize($SargConfig)),"SargConfig");	
	
}

function parameters_save(){
	$sock=new sockets();
	if(isset($_GET["RESTART_SQUID"])){
		$sock->getFrameWork("cmd.php?squid-restart=yes");
	}
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	$sock->SET_INFO("EnableSargGenerator",$_GET["EnableSargGenerator"]);
	$sock->SET_INFO("DisableArticaProxyStatistics",$_GET["DisableArticaProxyStatistics"]);
	$sock->SET_INFO("SargOutputDir",$_GET["SargOutputDir"]);
	unset($_GET["SargOutputDir"]);
	
	
	if($_GET["DisableArticaProxyStatistics"]<>$DisableArticaProxyStatistics){$sock->getFrameWork("cmd.php?restart-artica-maillog=yes");}
	$tpl=new templates();
	$page=CurrentPageName();
	$SargConfig=unserialize(base64_decode($sock->GET_INFO("SargConfig")));	
	$SargConfig=SargDefault($SargConfig);	
	while (list ($key, $line) = each ($_GET) ){
		$SargConfig[$key]=$line;
		
	}
	$SargConfig=SargDefault($SargConfig);	
	$sock->SaveConfigFile(base64_encode(serialize($SargConfig)),"SargConfig");	
	$sock->getFrameWork("squid.php?test-sarg=yes");
	$sock->getFrameWork("squid.php?sarg-conf=yes");
}
	
?>
