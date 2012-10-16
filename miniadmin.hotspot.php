<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");die();}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.archive.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

$users=new usersMenus();
if(!$users->AsHotSpotManager){header("location:miniadm.index.php");die();}


if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["sessions"])){sessions();exit;}
if(isset($_GET["sessions-items"])){sessions_items();exit;}
if(isset($_POST["DeleteSession"])){sessions_remove();exit;}

if(isset($_GET["members"])){members();exit;}
if(isset($_GET["members-items"])){members_items();exit;}
if(isset($_POST["EnableMember"])){members_enable();exit;}
if(isset($_GET["uid"])){member_popup();exit;}
if(isset($_POST["uid"])){members_save();exit;}
if(isset($_GET["ttl"])){members_ttl();exit;}
if(isset($_POST["uid-ttl"])){members_ttl_save();exit;}

main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}



function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{hostpot_members}</H1>
		<p>{hostpot_members_text}</p>
	</div>	
	<div id='backuped-tabs-$t'></div>
	
	<script>
		LoadAjax('backuped-tabs-$t','$page?tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["sessions"]='{sessions}';
	$array["members"]='{members}';
	while (list ($num, $ligne) = each ($array) ){
			
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
		}
	
	
	

	$html="
		<div id='main_hostspot' style='background-color:white;margin-top:10px'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_hostspot').tabs();
			

			});
		</script>
	
	";	
	
	echo $tpl->_ENGINE_parse_body($html);			
}

function members(){
	$today=date("Y-m-d");
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=710;
	$members=$tpl->_ENGINE_parse_body("{members}");
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");
	$ttl=$tpl->_ENGINE_parse_body("{ttl}");
	$finaltime=$tpl->_ENGINE_parse_body("{duration}");
	$endtime=$tpl->_ENGINE_parse_body("{endtime}");
	$title=$tpl->_ENGINE_parse_body("{sessions} ".date("{l} d {F}"));
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`=''");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$new_account=$tpl->_ENGINE_parse_body("{new_account}");
	$buttons="
	buttons : [
	{name: '$new_account', bclass: 'Add', onpress : NewAccount$t},
	],";	
	
	$html="
	<div id='query-explain'></div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?members-items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$members', name : 'uid', width :500, sortable : true, align: 'left'},	
		{display: '$ttl', name : 'ttl', width :152, sortable : true, align: 'left'},
		{display: '$finaltime', name : 'sessiontime', width :136, sortable : true, align: 'left'},
		{display: '$enabled', name : 'enabled', width :31, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'none', width :21, sortable : true, align: 'center'},
	],
	$buttons

	searchitems : [
		{display: '$members', name : 'uid'},
		{display: '$hostname', name : 'hostname'},
	
	],
	sortname: 'uid',
	sortorder: 'asc',
	usepager: true,
	title: '<span id=\"title-$t\">$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 940,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});
	var x_DeleteSession$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#row'+mem$t).remove();
		
	}

function DeleteSession$t(md){
	mem$t=md;
	var XHR = new XHRConnection();
	XHR.appendData('DeleteSession',md);	
	XHR.sendAndLoad('$page', 'POST',x_DeleteSession$t);	

}

function NewAccount$t(){
	YahooWin4('600','$page?uid=&t=$t','$new_account');
}

	var x_MemberEnable$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#flexRT$t').flexReload();
		
	}

function MemberEnable$t(uid){
	var enabled=0;
	if(document.getElementById('enable_'+uid).checked){enabled=1;}
	var XHR = new XHRConnection();
	XHR.appendData('EnableMember',uid);
	XHR.appendData('value',enabled);		
	XHR.sendAndLoad('$page', 'POST',x_MemberEnable$t);	
	
}

</script>";
	
	echo $html;	
}

function members_ttl(){
	$tt=$_GET["t"];
	$uid=$_GET["ttl"];
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$t=time();
	
	$Timez[0]="{unlimited}";
	$Timez[30]="30 {minutes}";
	$Timez[60]="1 {hour}";
	$Timez[120]="2 {hours}";
	$Timez[180]="3 {hours}";
	$Timez[360]="6 {hours}";
	$Timez[720]="12 {hours}";
	$Timez[1440]="1 {day}";
	$Timez[2880]="2 {days}";
	$Timez[10080]="1 {week}";
	$Timez[20160]="2 {weeks}";
	$Timez[40320]="1 {month}";		
	
	
	$bttext="{apply}";
	
	
	$html="
	<div id='$t-animate'></div>
	<table style='width:99%' class=form>
		<tr>
		<td class=legend style='font-size:16px'>{ttl}:</td>
		<td style='font-size:16px'>". Field_array_Hash($Timez,"ttl-$t",$ligne["ttl"],null,null,0,"font-size:14px")."</td>	</tr>			
	</tr>
	
	<tr>
		<td colspan=2 align='right'><hr>". button("$bttext","SaveTTLHotspot()","16px")."</td>
	</tr>
	</table>
	<script>
	var x_SaveTTLHotspot$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('$t-animate').innerHTML='';
		if(results.length>3){alert(results);return;}
		$('#flexRT$tt').flexReload();
		YahooWin4Hide();
	}


function SaveTTLHotspot(){
		var XHR = new XHRConnection();
		if(document.getElementById('ttl-$t')){XHR.appendData('ttl',document.getElementById('ttl-$t').value);}
		XHR.appendData('uid-ttl','$uid');			
		AnimateDiv('$t-animate');
		XHR.sendAndLoad('$page', 'POST',x_SaveTTLHotspot$t);
	}
	
	
	</script>";
	
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function member_popup(){
	$tt=$_GET["t"];
	$uid=$_GET["uid"];
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$t=time();
	$bttext="{add}";
	$close=0;
	if($uid<>null){
		$sql="SELECT uid,ttl,enabled FROM hotspot_members WHERE uid='$uid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$bttext="{apply}";
		$close=1;
		
	}
	$error_passwords_mismatch=$tpl->javascript_parse_text("{error_passwords_mismatch}");
	$Timez[0]="{unlimited}";
	$Timez[30]="30 {minutes}";
	$Timez[60]="1 {hour}";
	$Timez[120]="2 {hours}";
	$Timez[180]="3 {hours}";
	$Timez[360]="6 {hours}";
	$Timez[720]="12 {hours}";
	$Timez[1440]="1 {day}";
	$Timez[2880]="2 {days}";
	$Timez[10080]="1 {week}";
	$Timez[20160]="2 {weeks}";
	$Timez[40320]="1 {month}";		
	$sock=new sockets();
	$HotSpotConfig=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));
	if(!is_numeric($HotSpotConfig["USELDAP"])){$HotSpotConfig["USELDAP"]=1;}
	if(!is_numeric($HotSpotConfig["USEMYSQL"])){$HotSpotConfig["USEMYSQL"]=1;}
	if(!is_numeric($HotSpotConfig["CACHE_AUTH"])){$HotSpotConfig["CACHE_AUTH"]=60;}
	if(!is_numeric($HotSpotConfig["CACHE_TIME"])){$HotSpotConfig["CACHE_TIME"]=120;}
	if(!is_numeric($HotSpotConfig["USETERMS"])){$HotSpotConfig["USETERMS"]=1;}
	
	$ttl="	<tr>
		<td class=legend style='font-size:16px'>{ttl}:</td>
		<td style='font-size:16px'>". Field_array_Hash($Timez,"ttl-$t",$ligne["ttl"],null,null,0,"font-size:14px")."</td>	</tr>			
	</tr>";
	if($close==0){$ttl=null;}
	
	if(!is_numeric($ligne["sessiontime"])){$ligne["sessiontime"]=$HotSpotConfig["CACHE_AUTH"];}
	
	$html="
	<div id='$t-animate'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{account}</td>
		<td>". Field_text("uid-$t",$uid,"font-size:14px;width:300px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{password}</td>
		<td>". Field_password("nolock:password-$t",null,"font-size:14px;width:300px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{password} ({confirm})</td>
		<td>". Field_password("nolock:password1-$t",null,"font-size:14px;width:300px")."</td>
	</tr>	

	<tr>
		<td class=legend style='font-size:16px'>{verif_auth_each}:</td>
		<td style='font-size:16px'>". Field_text("maxtime-$t",$ligne["sessiontime"],"font-size:16px;width:90px")."&nbsp;{minutes}</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("$bttext","SaveAccountHotspot()","16px")."</td>
	</tr>
	</table>
	<script>
	var x_SaveAccountHotspot$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('$t-animate').innerHTML='';
		if(results.length>3){alert(results);return;}
		$('#flexRT$tt').flexReload();
		var close=$close;
		if(close==1){YahooWin4Hide();}
	}


function SaveAccountHotspot(){
		
		var XHR = new XHRConnection();
		var password=document.getElementById('password-$t').value;
		if(password.length>0){
			var password1=document.getElementById('password1-$t').value;
			if(password1!=password){alert('$error_passwords_mismatch');return;}
			password=encodeURIComponent(password);
			XHR.appendData('password',password);
		}
		XHR.appendData('uid',document.getElementById('uid-$t').value);
		if(document.getElementById('ttl-$t')){XHR.appendData('ttl',document.getElementById('ttl-$t').value);}
		XHR.appendData('maxtime',document.getElementById('maxtime-$t').value);			
		AnimateDiv('$t-animate');
		XHR.sendAndLoad('$page', 'POST',x_SaveAccountHotspot$t);
	}
	
	function Check$t(){
		var close=$close;
		if(close==1){return;}
		document.getElementById('uid-$t').disabled=true;
	
	}
	Check$t();
	</script>";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}
function members_save(){
	$uid=$_POST["uid"];
	$q=new mysql_squid_builder();
	$sql="SELECT uid,ttl,enabled FROM hotspot_members WHERE uid='$uid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["uid"]==null){
		$_POST["password"]=url_decode_special_tool($_POST["password"]);
		if($_POST["ttl"]>0){
			$_POST["ttl"] = strtotime("+{$_POST["ttl"]} minutes", time());
		}
		$_POST["password"]=md5($_POST["password"]);
		$sql="INSERT IGNORE INTO hotspot_members (uid,ttl,sessiontime,password,enabled) VALUES
		('$uid','{$_POST["ttl"]}','{$_POST["sessiontime"]}','{$_POST["password"]}',1)";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
		return;
	}
	
	if($_POST["password"]<>null){
		$_POST["password"]=url_decode_special_tool($_POST["password"]);
		$password=",password=MD5('{$_POST["password"]}')";
	}
	$sql="UPDATE hotspot_members SET uid='$uid',sessiontime={$_POST["maxtime"]}$password WHERE uid='$uid'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}	
}

function members_ttl_save(){
	$_POST["ttl"] = strtotime("+{$_POST["ttl"]} minutes", time());
	$uid=$_POST["uid-ttl"];
	$q=new mysql_squid_builder();
	$sql="UPDATE hotspot_members SET uid='$uid',ttl={$_POST["ttl"]} WHERE uid='$uid'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}		
}

function members_enable(){
	
	$sql="UPDATE hotspot_members SET enabled={$_POST["value"]} WHERE uid='{$_POST["EnableMember"]}'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}

function sessions(){
	$today=date("Y-m-d");
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=710;
	$members=$tpl->_ENGINE_parse_body("{members}");
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");
	$logintime=$tpl->_ENGINE_parse_body("{logintime}");
	$finaltime=$tpl->_ENGINE_parse_body("{duration}");
	$endtime=$tpl->_ENGINE_parse_body("{endtime}");
	$title=$tpl->_ENGINE_parse_body("{sessions} ".date("{l} d {F}"));
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE `md5`=''");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$html="
	<div id='query-explain'></div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?sessions-items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$members', name : 'uid', width :190, sortable : true, align: 'left'},	
		{display: '$MAC', name : 'MAC', width :125, sortable : true, align: 'left'},
		{display: '$logintime', name : 'logintime', width :152, sortable : true, align: 'left'},
		{display: '$finaltime', name : 'finaltime', width :136, sortable : true, align: 'left'},
		{display: '$endtime', name : 'endtime', width :152, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'none', width :21, sortable : true, align: 'center'},
	],
	$buttons

	searchitems : [
		{display: '$members', name : 'uid'},
		{display: '$hostname', name : 'hostname'},
	
	],
	sortname: 'logintime',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=\"title-$t\">$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 940,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});
	var x_DeleteSession$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#row'+mem$t).remove();
		
	}

function DeleteSession$t(md){
	mem$t=md;
	var XHR = new XHRConnection();
	XHR.appendData('DeleteSession',md);	
	XHR.sendAndLoad('$page', 'POST',x_DeleteSession$t);	

}

function Chooseday$t(){
	YahooWin2('260','$page?choose-day=yes&t=$t','$choose_day');

}

</script>";
	
	echo $html;	
}
function sessions_remove(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM hotspot_sessions WHERE md5='{$_POST["DeleteSession"]}'");
	if(!$q->ok){echo $q->mysql_error;}
}


function sessions_items(){

	
	$myPage=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$table="hotspot_sessions";
	$uid=$_SESSION["uid"];
	$t=$_GET["t"];
	$tm=array();
	$search='%';
	$page=1;
	$FORCE_FILTER="";
	
	if(strpos($table, ",")==0){
		if(!$q->TABLE_EXISTS($table)){
			json_error_show("$table: No such table",0,true);
		}
	}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}	
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}	
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	
		$md5=$ligne["md5"];
		
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	
	$minutes=$tpl->_ENGINE_parse_body("{minutes}");
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$urljs="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('$myPage?MessageID-js=$MessageID&table=$table_query')\"
		style='font-size:11px;text-decoration:underline'>";
		$resend=imgsimple("arrow-blue-left-24.png",null,"javascript:Loadjs('$myPage?MessageID-resend-js=$MessageID&table=$table_query')");
		
		$AddMinutes=intval($ligne["maxtime"]);
		$logintime=intval($ligne["logintime"]);
		$Start=$tpl->_ENGINE_parse_body(date("{l} d H:i",$logintime));
		$delete=imgsimple("delete-24.png",null,"DeleteSession$t('{$ligne["md5"]}')");
		$End=$tpl->_ENGINE_parse_body(date("{l} d H:i",$ligne["finaltime"]));
		$hostname=$ligne["hostname"];
		
		
		//$subject=mime_decode($subject);
		$data['rows'][] = array(
				'id' => $ligne["md5"],
				'cell' => array(
					"<span style='font-size:16px;color:$color'>{$ligne["uid"]}<br><i style='font-size:12px'>$hostname</i></a></span>",
					"<span style='font-size:14px;color:$color'>{$ligne["MAC"]}</a></span>",
					"<span style='font-size:14px;color:$color'>$Start</a></span>",
					"<span style='font-size:14px;color:$color'>{$ligne["maxtime"]} $minutes</a></span>",
					"<span style='font-size:14px;color:$color'>$End</a></span>",
					"<span style='font-size:14px;color:$color'>$delete</a></span>",
					
					)
				);
			}
	
	
echo json_encode($data);	
}

function members_items(){
	$myPage=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$table="hotspot_members";
	$uid=$_SESSION["uid"];
	$t=$_GET["t"];
	$tm=array();
	$search='%';
	$page=1;
	$FORCE_FILTER="";
	
	$sock=new sockets();
	
	$HotSpotConfig=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));
	if(!is_numeric($HotSpotConfig["USELDAP"])){$HotSpotConfig["USELDAP"]=1;}
	if(!is_numeric($HotSpotConfig["USEMYSQL"])){$HotSpotConfig["USEMYSQL"]=1;}
	if(!is_numeric($HotSpotConfig["CACHE_AUTH"])){$HotSpotConfig["CACHE_AUTH"]=60;}
	if(!is_numeric($HotSpotConfig["CACHE_TIME"])){$HotSpotConfig["CACHE_TIME"]=120;}
	if(!is_numeric($HotSpotConfig["USETERMS"])){$HotSpotConfig["USETERMS"]=1;}		
	
	if(strpos($table, ",")==0){
		if(!$q->TABLE_EXISTS($table)){
			json_error_show("$table: No such table",0,true);
		}
	}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}	
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error);}	
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

		
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	
	$minutes=$tpl->_ENGINE_parse_body("{minutes}");
	$unlimited=$tpl->_ENGINE_parse_body("{unlimited}");
	$ttl=$tpl->_ENGINE_parse_body("{ttl}");
	while ($ligne = mysql_fetch_assoc($results)) {

		
		$uid= $ligne["uid"];
		$ttl=intval($ligne["ttl"]);
		$logintime=intval($ligne["logintime"]);
		$Start=$tpl->_ENGINE_parse_body(date("{l} d H:i",$logintime));
		$delete=imgsimple("delete-24.png",null,"DeleteMember$t('{$ligne["uid"]}')");
		$End=$tpl->_ENGINE_parse_body(date("{l} d H:i",$ligne["finaltime"]));
		$hostname=$ligne["hostname"];
		$MAC=$ligne["MAC"];
		$ipaddr=$ligne["ipaddr"];
		$ttl=$ligne["ttl"];
		
		if($ttl==0){$ttl=$unlimited;}else{$ttl=$tpl->_ENGINE_parse_body(date("{l} d H:i",$ttl));}
		if($ligne["sessiontime"]==0){$ligne["sessiontime"]=$HotSpotConfig["CACHE_TIME"];}
		
		$enabled=Field_checkbox("enable_$uid", 1,$ligne["enabled"],"MemberEnable$t('$uid')");
		$color="black";
		if($ligne["enabled"]==0){$color="#A4A1A1";}
		
		$urljs="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:YahooWin4('600','$myPage?uid={$ligne["uid"]}&t=$t','{$ligne["uid"]}');\"
		style='font-size:14px;text-decoration:underline;color:$color'>";	

		$urlttl="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:YahooWin4('500','$myPage?ttl={$ligne["uid"]}&t=$t','$ttl:{$ligne["uid"]}');\"
		style='font-size:14px;text-decoration:underline;color:$color'>";		
		
		//$subject=mime_decode($subject);
		$data['rows'][] = array(
				'id' => $ligne["uid"],
				'cell' => array(
					"<span style='font-size:14px;color:$color'>$urljs{$ligne["uid"]}</a><br><i style='font-size:12px'>$hostname&nbsp;|&nbsp;$MAC&nbsp;|&nbsp;$ipaddr</i></a></span>",
					"<span style='font-size:14px;color:$color'>$urlttl$ttl</a></span>",
					"<span style='font-size:14px;color:$color'>{$ligne["sessiontime"]} $minutes</a></span>",
					"<span style='font-size:14px;color:$color'>$enabled</a></span>",
					"<span style='font-size:14px;color:$color'>$delete</a></span>",
					
					)
				);
			}
	
	
echo json_encode($data);	
}





