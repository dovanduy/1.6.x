<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	
	if(isset($_GET["zarafa-orhpans-list"])){slist();exit;}
	if(isset($_POST["ZarafaStoreDelete"])){ZarafaStoreDelete();exit;}
	if(isset($_POST["ZarafaStoreLink"])){ZarafaStoreLink();exit;}
	if(isset($_POST["ZarafaStoreScan"])){ZarafaStoreScan();exit;}
	
	if(isset($_GET["ZarafaCopyToPublic-js"])){ZarafaCopyToPublic_js();exit;}
	if(isset($_GET["ZarafaCopyToPublic-popup"])){ZarafaCopyToPublic_popup();exit;}
	if(isset($_POST["ZarafaCopyToPublicPerform"])){ZarafaCopyToPublic_perform();exit;}
	if(isset($_GET["js"])){js();exit;}
popup();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$title=$tpl->javascript_parse_text("{orphans}");
	$html="YahooWin5('650','$page','$title');";
	echo $html;
}


function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$member=$tpl->_ENGINE_parse_body("{member}");
	$email=$tpl->_ENGINE_parse_body("{mail}");
	$store=$tpl->_ENGINE_parse_body("{store}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$member=$tpl->_ENGINE_parse_body("{member}/{store}");
	$link=$tpl->_ENGINE_parse_body("{link}");
	$mailbox_size=$tpl->_ENGINE_parse_body("{mailbox_size}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$delete_rule=$tpl->javascript_parse_text("{delete_rule}");
	$refresh=$tpl->_ENGINE_parse_body("{refresh}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$apply=$tpl->_ENGINE_parse_body("{apply_parameters}");
	$deleteTXT=$tpl->javascript_parse_text("{delete}");
	$ZarafaCopyToPublic=$tpl->javascript_parse_text("{ZarafaCopyToPublic}");
	$t=time();
	
	$q=new mysql();
	$sql="SELECT COUNT(zmd5) as tcount FROM zarafauserss WHERE license=1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$Licensed=$ligne["tcount"];
	
	$sql="SELECT COUNT(zmd5) as tcount FROM zarafauserss WHERE license=0";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$NoTLicensed=$ligne["tcount"];
	
	$sum=$Licensed+$NoTLicensed;
	
	$title=$tpl->_ENGINE_parse_body("$sum {members} $Licensed {licensed_mailboxes}");
	
	$import=$tpl->_ENGINE_parse_body("{import}");
	

	$buttons="
	buttons : [
	{name: '$refresh', bclass: 'Reload', onpress :  OrphanReScan$t},
	],	";
	
	
	$html="
	<center id='anim-$t' style='margin-bottom:10px'></center>
	<div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>
	
<script>
	var fetchid=0;
	$(document).ready(function(){
		$('#flexRT$t').flexigrid({
		url: '$page?zarafa-orhpans-list=yes&t=$t',
		dataType: 'json',
		colModel : [
		
			{display: '$date', name : 'zDate', width :139, sortable : true, align: 'left'},
			{display: '$member', name : 'uid', width :399, sortable : true, align: 'left'},
			{display: '$size', name : 'size', width :99, sortable : true, align: 'left'},
			{display: '$link', name : 'NONACTIVETYPE', width : 34, sortable : false, align: 'center'},
			{display: '$ZarafaCopyToPublic', name : 'NONACTIVETYPE1', width : 34, sortable : false, align: 'left'},
			{display: '&nbsp;', name : 'NONACTIVETYPE', width : 34, sortable : false, align: 'left'},
	],$buttons
	
	searchitems : [
		{display: '$member', name : 'uid'},
		{display: '$store', name : 'storeid'},
	],
	sortname: 'uid',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,300,500]
	
});
});
	
	
	
var x_Reload$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#flexRT$t').flexReload();
	document.getElementById('anim-$t').innerHTML='';
}
	
	
	
function Reload$t(){
	var XHR = new XHRConnection();
	XHR.appendData('mailboxes-reload','yes');
	document.getElementById('anim-$t').innerHTML='<img src=\"img/loader-big.gif\">';
	XHR.sendAndLoad('$page', 'POST',x_Reload$t);
}

var x_ZarafaStoreDelete= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	$('#flexRT$t').flexReload();
}	

function ZarafaStoreDelete(gpname){
	if(confirm('$deleteTXT '+gpname+' ?')){
		var XHR = new XHRConnection();
		XHR.appendData('ZarafaStoreDelete',gpname);
		XHR.sendAndLoad('$page', 'POST',x_ZarafaStoreDelete);
	}
}
		
function ZarafaStoreLink(storeid,uid){
	if(confirm(storeid+' --> '+uid+' ?')){
		var XHR = new XHRConnection();
		XHR.appendData('ZarafaStoreLink',storeid);
		XHR.appendData('uid',uid);
		XHR.sendAndLoad('$page', 'POST',x_ZarafaStoreDelete);
	}
}

function OrphanReScan$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ZarafaStoreScan','yes');
	XHR.sendAndLoad('$page', 'POST',x_ZarafaStoreDelete);		
}
		
function ZarafaCopyToPublic(storeid){
	Loadjs('$page?ZarafaCopyToPublic-js=yes&storeid='+storeid);
}
</script>";
			echo $html;	
	
}

function slist(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();

	$t=$_GET["t"];
	$search='%';
	$table="zarafa_orphaned";
	$database="artica_backup";
	$page=1;
	$ORDER="";

	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("$table, no such table");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error");}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$THIS_USER_DOES_NOT_EXISTS_TEXT=$tpl->_ENGINE_parse_body("{THIS_USER_DOES_NOT_EXISTS}");

	while ($ligne = mysql_fetch_assoc($results)) {
		$href=null;
		$md5=md5(serialize($ligne));
		$ligne["uid"]=trim($ligne["uid"]);
		$uid=$ligne["uid"];
		$color="black";
		$THIS_USER_DOES_NOT_EXISTS=null;
		$delete=imgsimple("delete-32.png","{delete}:{$ligne["storeid"]}","ZarafaStoreDelete('{$ligne["storeid"]}')");
		$CopyToPublic=imgsimple("rebuild-mailboxes-32.png",null,"ZarafaCopyToPublic('{$ligne["storeid"]}')");
		
		$date=strtotime($ligne["zDate"]);
		$distanceOfTimeInWords=$tpl->_ENGINE_parse_body(distanceOfTimeInWords($date,time()));
		$users=new user($ligne["uid"]);
		$relink=imgsimple("32-backup.png","{link}","ZarafaStoreLink('{$ligne["storeid"]}','{$ligne["uid"]}')");
		if($users->mail==null){
			$THIS_USER_DOES_NOT_EXISTS="<div style='color:#891C1C;font-size:11px;font-weight:bold'><i>$THIS_USER_DOES_NOT_EXISTS_TEXT</i></div>";
			$color="#8a8a8a";
			$relink="&nbsp;";
		}
		
		
		
		$span="<span style='font-size:14px'>";
		$ligne["size"]=FormatBytes($ligne["size"]/1024);

		
		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
						$span.$href.$ligne["zDate"]."</a></span>",
						$span.$href."<strong>{$ligne["uid"]}</strong></a>$THIS_USER_DOES_NOT_EXISTS<div style='font-size:11px'><i>{$ligne["storeid"]}<br>$distanceOfTimeInWords</i>",
						$span.$href.$ligne["size"]."</a></span>",
						$span.$relink."</span>",
						$span.$href.$CopyToPublic."</a></span>",
						$span.$delete."</a></span>",
				)
		);		
		

	}


	echo json_encode($data);

}

function slist2(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql();
	$deleteTXT=$tpl->javascript_parse_text("{delete}");
	$sql="SELECT * FROM zarafa_orphaned ORDER BY size DESC LIMIT 0,100";
	
	if($_GET["search"]<>null){
		$_GET["search"]=str_replace("*", "%", $_GET["search"]);
		$sql="SELECT * FROM zarafa_orphaned WHERE uid LIKE '{$_GET["search"]}' OR storeid LIKE '{$_GET["search"]}' ORDER BY size DESC LIMIT 0,100";
	}
	
$results=$q->QUERY_SQL($sql,"artica_backup");	
		if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
		
	$html="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1% align='center'>". imgtootltip("32-redo.png","{analyze}","OrphanReScan()")."</th>
		<th width=1%>{date}</th>
		<th width=99%>{member}/{store}</th>
		<th>{link}</th>
		<th>{size}</th>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";		

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$ligne["uid"]=trim($ligne["uid"]);
		$color="black";
		$THIS_USER_DOES_NOT_EXISTS=null;
		$delete=imgtootltip("delete-32.png","{delete}:{$ligne["storeid"]}","ZarafaStoreDelete('{$ligne["storeid"]}')");
		$CopyToPublic=imgtootltip("rebuild-mailboxes-32.png","{$ligne["storeid"]}<hr>{ZarafaCopyToPublic}","ZarafaCopyToPublic('{$ligne["storeid"]}')");
		
		$date=strtotime($ligne["zDate"]);
		$distanceOfTimeInWords=distanceOfTimeInWords($date,time());	
		$users=new user($ligne["uid"]);
		$relink=imgtootltip("32-backup.png","{link}","ZarafaStoreLink('{$ligne["storeid"]}','{$ligne["uid"]}')");
		if($users->mail==null){
			$THIS_USER_DOES_NOT_EXISTS="<div style='color:#891C1C;font-size:11px;font-weight:bold'><i>{THIS_USER_DOES_NOT_EXISTS}</i></div>";
			$color="#8a8a8a";
			$relink="&nbsp;";
		}
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$html=$html."
		<tr class=$classtr>
		<td style='font-size:14px;color:$color' nowrap colspan=2>{$ligne["zDate"]}</a></td>
		<td style='font-size:14px;color:$color'><strong>{$ligne["uid"]}</strong></a>$THIS_USER_DOES_NOT_EXISTS<div style='font-size:11px'><i>{$ligne["storeid"]}<br>$distanceOfTimeInWords</i></td>
		<td style='font-size:14px;color:$color'>$relink</td>
		<td style='font-size:14px;color:$color'>{$ligne["size"]}</a></td>
		<td width=1%>$CopyToPublic</td>
		<td width=1%>$delete</td>
		</tr>
		
		";
		
		
	}

	$html=$html."</tbody></table>
		<script>
	var x_ZarafaStoreDelete= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
	    	ZarafaOrphansShow();
		}	

		function ZarafaStoreDelete(gpname){
			if(confirm('$deleteTXT '+gpname+' ?')){
			var XHR = new XHRConnection();
			XHR.appendData('ZarafaStoreDelete',gpname);
			AnimateDiv('zarafa-orhpans-list');
			XHR.sendAndLoad('$page', 'POST',x_ZarafaStoreDelete);
			}
		}
		
		function ZarafaStoreLink(storeid,uid){
			if(confirm(storeid+' --> '+uid+' ?')){
			var XHR = new XHRConnection();
			XHR.appendData('ZarafaStoreLink',storeid);
			XHR.appendData('uid',uid);
			AnimateDiv('zarafa-orhpans-list');
			XHR.sendAndLoad('$page', 'POST',x_ZarafaStoreDelete);
			}
		}

		function OrphanReScan(){
			var XHR = new XHRConnection();
			XHR.appendData('ZarafaStoreScan','yes');
			AnimateDiv('zarafa-orhpans-list');
			XHR.sendAndLoad('$page', 'POST',x_ZarafaStoreDelete);		
		}
		
		function ZarafaCopyToPublic(storeid){
			Loadjs('$page?ZarafaCopyToPublic-js=yes&storeid='+storeid);
		
		}
		
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function ZarafaStoreDelete(){
	$sql="DELETE FROM zarafa_orphaned WHERE storeid='{$_POST["ZarafaStoreDelete"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("zarafa.php?zarafa-orphan-kill={$_POST["ZarafaStoreDelete"]}");
	
	
}

function ZarafaStoreLink(){
	$sql="DELETE FROM zarafa_orphaned WHERE storeid='{$_POST["ZarafaStoreLink"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("zarafa.php?zarafa-orphan-link={$_POST["ZarafaStoreLink"]}&uid={$_POST["uid"]}");	
}

function ZarafaStoreScan(){
	$sock=new sockets();
	$sock->getFrameWork("zarafa.php?zarafa-orphan-scan=yes");		
}

function ZarafaCopyToPublic_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{ZFCopyToPublic}:{$_GET["storeid"]}");
	$html="YahooWin5(450,'$page?ZarafaCopyToPublic-popup=yes&store={$_GET["storeid"]}','$title')";
	echo $html;
	
}

function ZarafaCopyToPublic_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$html="
	<div id='$t'>
	<div class=explain>{ZarafaCopyToPublic}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{member}:</td>
		<td>". Field_text("HookRecipt",null,"font-size:16px;width:220px")."</td>
		<td width=1%>". button("{browse}","Loadjs('MembersBrowse.php?OnlyUsers=1&NOComputers=0&Zarafa=1&callback=ZarafaCopyToPublicCallBack')")."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{move}","ZarafaCopyToPublicPerform()",16)."</td>
	</tr>
	</table>
	</div>
	<script>
	
	function ZarafaCopyToPublicCallBack(uid,prepend,gid){
		document.getElementById('HookRecipt').value=uid;
		WinORGHide();
	
	}
	
	var x_ZarafaCopyToPublicPerform= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
	    	YahooWin5Hide();
	    	if(document.getElementById('zarafa-orhpans-list')){ZarafaOrphansShow();}
		}	

		function ZarafaCopyToPublicPerform(){
			var uid=document.getElementById('HookRecipt').value;
			if(uid.length==0){return;}
			var XHR = new XHRConnection();
			XHR.appendData('ZarafaCopyToPublicPerform','yes');
			XHR.appendData('storeid','{$_GET["store"]}');
			XHR.appendData('uid',document.getElementById('HookRecipt').value);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_ZarafaCopyToPublicPerform);
		}
	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function ZarafaCopyToPublic_perform(){
	$sock=new sockets();
	$CopyToPublic=base64_encode(serialize(array(
		"storeid"=>$_POST["storeid"],
		"uid"=>$_POST["uid"]
	)));
	
	$data=$sock->getFrameWork("zarafa.php?CopyToPublic=$CopyToPublic");
	$sock->getFrameWork("zarafa.php?zarafa-orphan-scan=yes");		
	echo base64_decode($data);
	
	
}
