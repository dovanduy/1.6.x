<?php

include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.openssh.inc');
include_once('ressources/class.user.inc');

$user=new usersMenus();
if($user->AsSystemAdministrator==false){
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();exit();
}

if(isset($_GET["list"])){list_items();exit;}
if(isset($_GET["new-js"])){new_js();exit;}
if(isset($_GET["new-member"])){new_member();exit;}
if(isset($_POST["username"])){new_members_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete_member();exit;}

table();


function new_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{new_member}");
	echo "YahooWin(800,'$page?new-member=yes','$title')";
	
}

function delete_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$servername=$_GET["servername"];
	header("content-type: application/x-javascript");
	$explain=$tpl->javascript_parse_text("{delete} {$_GET["delete-js"]}");
	
	$t=time();
	$html="
var xDeleteFreeWeb$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}
	$('#SSHD_ALLOW_TABLE').flexReload();
	
}

function DeleteFreeWeb$t(){
	if(!confirm('$explain')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','{$_GET["delete-js"]}');
	XHR.sendAndLoad('$page', 'POST',xDeleteFreeWeb$t);
}
DeleteFreeWeb$t();
";
	echo $html;

}

function new_member(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$html="<div style='width:98%' class=form>
	<div class=text-info style='font-size:16px'>{sshd_AllowUsers_explain}</div>
	<table style='width:100%'>
			
	".Field_text_table("username-$t", "{user2}",null,22,null,450).
	Field_text_table("domain-$t", "{domain}",null,22,null,450).
	Field_button_table_autonome("{add}", "Save$t",26).
	"</table>
	<script>
	var xSave$t= function (obj) {
		var res=obj.responseText;
		
		if (res.length>3){
			alert(res);
			return;
		}
		$('#SSHD_ALLOW_TABLE').flexReload();
		YahooWinHide();
		
	}	

	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('username',encodeURIComponent(document.getElementById('username-$t').value));
		XHR.appendData('domain',encodeURIComponent(document.getElementById('domain-$t').value));
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	}
</script>	
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function new_members_save(){
	
	
	$username=url_decode_special_tool($_POST["username"]);
	if($username==null){$username="*";}
	$domain=url_decode_special_tool($_POST["domain"]);
	if($domain==null){$domain="*";}
	
	$pattern=mysql_escape_string2("$username@$domain");
	
	
	$q=new mysql();
	$q->QUERY_SQL("INSERT IGNORE INTO sshd_allowusers (pattern) VALUES ('$pattern')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}

function delete_member(){
	$pattern=mysql_escape_string2($_POST["delete"]);
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM sshd_allowusers WHERE `pattern`='$pattern'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}


function table(){

	//sshd_allowusers
	$sock=new sockets();
	$q=new mysql();
	$q->BuildTables();
	$page=CurrentPageName();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$new_member=$tpl->_ENGINE_parse_body("{new_member}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$destination=$tpl->_ENGINE_parse_body("{destination}");
	$website=$tpl->_ENGINE_parse_body("{website}");
	$settings=$tpl->javascript_parse_text("{watchdog_squid_settings}");
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");
	$apply_parameters=$tpl->javascript_parse_text("{apply_parameters}");
	$purge_caches=$tpl->javascript_parse_text("{purge_caches}");
	$members=$tpl->javascript_parse_text("{members}");
	$new_server=$tpl->javascript_parse_text("{new_server}");
	$TB_HEIGHT=450;
	$TB_WIDTH=927;
	$TB2_WIDTH=551;
	$all=$tpl->_ENGINE_parse_body("{all}");
	$t=time();

	$buttons="
	buttons : [

	{name: '$apply_parameters', bclass: 'apply', onpress :  apply_parameters$t},
	],	";

	$buttons="
	buttons : [
	{name: '$new_member', bclass: 'add', onpress : New$t},
	{name: '$apply_parameters', bclass: 'apply', onpress :  apply_parameters$t},
	],	";

	$html="
	<table class='SSHD_ALLOW_TABLE' style='display: none' id='SSHD_ALLOW_TABLE' style='width:99%'></table>
	<script>
	function BuildTable$t(){
	$('#SSHD_ALLOW_TABLE').flexigrid({
	url: '$page?list=yes&t=$t&ID={$_GET["ID"]}',
	dataType: 'json',
	colModel : [
	{display: '$members', name : 'pattern', width :560, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'delete', width :60, sortable : false, align: 'center'},
	],
	$buttons

	searchitems : [
	{display: '$members', name : 'pattern'},
	],
	sortname: 'pattern',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]

});
}

function apply_parameters$t(){
Loadjs('sshd.apply.progress.php')
}

function New$t(){
	Loadjs('$page?new-js=yes');
}
BuildTable$t();
</script>";
	echo $html;
}

function list_items(){
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$all_text=$tpl->_ENGINE_parse_body("{all}");
	$GLOBALS["CLASS_TPL"]=$tpl;
	$q=new mysql();
	$FORCE=1;
	$sock=new sockets();
	$search='%';
	$table="sshd_allowusers";
	$page=1;
	$freeweb_compile_background=$tpl->javascript_parse_text("{freeweb_compile_background}");
	$reset_admin_password=$tpl->javascript_parse_text("{reset_admin_password}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_nginx_text=$tpl->javascript_parse_text("{delete_freeweb_nginx_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");

	$total=0;
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("no data",0);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];

	}else{
		if(strlen($FORCE)>2){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			$total = $ligne["TCOUNT"];
		}else{
			$total = $q->COUNT_ROWS($table, "artica_backup");
		}
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE $FORCE $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$CurrentPage=CurrentPageName();

	if(mysql_num_rows($results)==0){json_error_show("no data");}
	$searchstring=string_to_flexquery();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	$q1=new mysql();
	$t=time();
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$pattern=$ligne["pattern"];
		$patternenc=urlencode($pattern);
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-js=$patternenc')");

		$data['rows'][] = array(
				'id' => md5($pattern),
				'cell' => array(
						"<span style='font-size:24px;font-weight:bold;'>$pattern</span>",
						$delete
				)
		);
	}

		echo json_encode($data);
}