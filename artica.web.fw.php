<?php
	$GLOBALS["ICON_FAMILY"]="PARAMETERS";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.httpd.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.samba.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	$usersmenus=new usersMenus();
	
	if($usersmenus->AsArticaAdministrator==false){header('location:users.index.php');exit;}
	
	if(isset($_GET["rules"])){rules();exit;}
	if(isset($_GET["add-js"])){add_js();exit;}
	if(isset($_GET["add-popup"])){add_popup();exit;}
	if(isset($_POST["ipaddr"])){Save();exit;}
	if(isset($_POST["delete"])){Delete();exit;}
	iptables_table();	
	
function add_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$title=$tpl->javascript_parse_text("{new_item}");
	echo "YahooWin('450','$page?add-popup=yes&t=$t','$title')";	
	
}

function add_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$html="	
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend nowrap style='font-size:18px'>{tcp_address}:</td>
			<td >" . field_ipv4("ipaddr-$t",null,"font-size:18px",false,"SaveCK$t(event)")."</td>
		</tr>
		<tr>
			<td class=legend nowrap style='font-size:18px'>{networks}:</td>
			<td>" . field_ipv4("cdir-$t",null,"font-size:18px",false,"SaveCK$t(event)")."</td>
		</tr>
			<td colspan=2 align='right'><hr>". button("{add}","Save$t()",22)."</td>
		</tr>
	</table>
	</form>
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
	ExecuteByClassName('SearchFunction');
	YahooWinHide();
}
function SaveCK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('cdir',document.getElementById('cdir-$t').value);
	XHR.appendData('ipaddr',document.getElementById('ipaddr-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}				
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Delete(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM iptables_webint WHERE ID='{$_POST["delete"]}'",'artica_backup');
	if(!$q->ok){echo "$q->mysql_error";}
}

function Save(){
	$q=new mysql();
	if(!$q->TABLE_EXISTS("iptables_webint", "artica_backup")){
	$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`iptables_webint` (
				`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				`pattern` VARCHAR(128) NOT NULL,
				 UNIQUE KEY `pattern` (`pattern`)
				) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){echo "$q->mysql_error";}
	}
	
	
	if($_POST["ipaddr"]<>null){
		$q->QUERY_SQL("INSERT IGNORE INTO `iptables_webint` (`pattern`) VALUES('{$_POST["ipaddr"]}')",'artica_backup');
		if(!$q->ok){echo "$q->mysql_error";}
	}
	if($_POST["cdir"]<>null){
		$q->QUERY_SQL("INSERT IGNORE INTO `iptables_webint` (`pattern`) VALUES('{$_POST["cdir"]}')",'artica_backup');
		if(!$q->ok){echo "$q->mysql_error";}
	}	
}
	
function iptables_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{security_access}");
	$new=$tpl->javascript_parse_text("{new_item}");
	$item=$tpl->javascript_parse_text("{item}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$LIGHTTPD_IP_ACCESS_TEXT=$tpl->_ENGINE_parse_body("{LIGHTTPD_IP_ACCESS_TEXT}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$apply=$tpl->javascript_parse_text("{apply}");
	
$t=time();
$html="
<div class=text-info style='font-size:16px'>$LIGHTTPD_IP_ACCESS_TEXT</div>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
	function LoadTable$t(){
		$('#flexRT$t').flexigrid({
		url: '$page?rules=yes&t=$t',
		dataType: 'json',
		colModel : [
		{display: '&nbsp;', name : 'ID', width :70, sortable : true, align: 'center'},
		{display: '$item', name : 'pattern', width : 389, sortable : true, align: 'left'},
		{display: '$delete', name : 'del', width : 70, sortable : false, align: 'center'},
	
		],
		buttons : [
		{name: '$new', bclass: 'add', onpress : NewRule$t},
		{name: '$apply', bclass: 'Apply', onpress : Apply$t},
	
		],
		searchitems : [
		{display: '$item', name : 'pattern'},
		],
		sortname: 'pattern',
		sortorder: 'asc',
		usepager: true,
		title: '<div style=\"font-size:16px\">$title</div>',
		useRp: true,
		rp: 15,
		showTableToggleBtn: false,
		width: '99%',
		height: 550,
		singleSelect: true
	
	});
}
var xRuleGroupUpDown$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	ExecuteByClassName('SearchFunction');
}
	

	
function Delete$t(ID){
	if(!confirm('$delete $item:'+ID+' ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete', ID);
	XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}
	
function Apply$t(){
	Loadjs('firewall.restart.php');
}

function NewRule$t() {
	Loadjs('$page?add-js=yes&t=$t',true);
}
LoadTable$t();
</script>
";
echo $html;
}	
function rules(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$FORCE_FILTER=null;
	$search='%';
	$table="iptables_webint";
	$page=1;
	$color="black";
	if($q->COUNT_ROWS("iptables_webint","artica_backup")==0){json_error_show("No datas - COUNT_ROWS",1);}
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}

	if (isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error."\n$sql",1);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(mysql_num_rows($results)==0){json_error_show($q->mysql_error,1);}
	


	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$delete=imgsimple("delete-48.png",null,"Delete{$_GET["t"]}({$ligne["ID"]})");
		$data['rows'][] = array(
				'id' => "{$ligne["ID"]}",
				'cell' => array(
						"<div style='font-size:22px;font-weight:bold;color:$color;margin-top:10px'>{$ligne["ID"]}</span>",
						"<div style='font-size:22px;font-weight:bold;color:$color;margin-top:10px'>{$ligne["pattern"]}</a>",
						"<div>$delete</div>")
		);
	}

	echo json_encode($data);

}