<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.iptables-chains.inc');
include_once('ressources/class.resolv.conf.inc');

$users=new usersMenus();
if(!$users->AsPostfixAdministrator){die();}

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["popup-add-js"])){popup_js();exit;}
if(isset($_POST["iii_DNSBL"])){Save();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["remove"])){remove();exit;}
table();


function popup_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{new_server}");
	$html="YahooWin3(681,'$page?popup=yes&hostname={$_GET["hostname"]}','$title')";
	echo $html;
	
}


function popup(){
	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$data=file_get_contents("ressources/dnsrbl.db");
	$tr=explode("\n",$data);
	while (list ($num, $val) = each ($tr) ){
		if(preg_match("#RBL:(.+)#",$val,$re)){
			$RBL[strtolower(trim($re[1]))]=strtolower(trim($re[1]));
		}
		if(preg_match("#RHSBL:(.+)#",$val,$re)){
			$RHSBL[$re[1]]=$re[1];
		}
	}
	$RHSBL[null]="{select}";
	ksort($RHSBL);
	$list=Field_array_Hash($RHSBL,"iii_DNSBL",null,"style:font-size:22px");
	
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{RHSBL}</td>
		<td>$list</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{new_service}:</td>
		<td>". Field_text("yyy_DNSBL",null,"font-size:22px")."</td>
	</tr>		
	<tr>
		<td colspan=2 align='right'><hr>". button("{add}","Save$t()",30)."</td>
	</tr>
	</table>
	</div>
<script>
var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	YahooWin3Hide();
	$('#POSTFIX_RHSBL_TABLE').flexReload();
}
	
function Save$t(){
	var XHR = new XHRConnection();
	var ii=document.getElementById('yyy_DNSBL').value;
	XHR.appendData('hostname', '{$_GET["hostname"]}');
	if(ii.length>5){
		 XHR.appendData('iii_DNSBL', ii);
	}else{
   		XHR.appendData('iii_DNSBL', document.getElementById('iii_DNSBL').value);
   	}
		      
	XHR.sendAndLoad('$page', 'POST',xSave$t);  		
}
</script>						
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$q=new mysql();
	$q->QUERY_SQL("INSERT IGNORE INTO postfix_rhsbl (dnsbl,hostname) VALUE ('{$_POST["iii_DNSBL"]}','{$_POST["hostname"]}')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}


function remove(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM postfix_rhsbl WHERE ID='{$_POST["remove"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function table(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$compile=$tpl->_ENGINE_parse_body("{compile}");
	$port=25;
	$t=time();
	$date=$tpl->_ENGINE_parse_body("{date}");
	$server=$tpl->_ENGINE_parse_body("{RHSBL}");
	$add=$tpl->_ENGINE_parse_body("{new_server}");
	$add_websites=$tpl->_ENGINE_parse_body("{add}");
	$verify=$tpl->_ENGINE_parse_body("{analyze}");
	$log=$tpl->_ENGINE_parse_body("{log}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$import=$tpl->_ENGINE_parse_body("{import}");
	$delete_all=$tpl->_ENGINE_parse_body("{delete_all_items}");
	$import_catz_art_expl=$tpl->javascript_parse_text("{import_catz_art_expl}");
	$apply=$tpl->javascript_parse_text('{apply}');
	$title=$tpl->javascript_parse_text("{RHSBL}");
	if(!isset($_GET["hostname"])){$_GET["hostname"]="master";}
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}
	
	
	$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`postfix_rhsbl` (
		`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		`dnsbl` VARCHAR( 128 ) NOT NULL,
		`hostname` VARCHAR( 128 ) NOT NULL,
		 KEY `hostname`(`hostname`),
		 KEY `dnsbl`(`dnsbl`)) ENGINE=MYISAM;";
	
	$q->QUERY_SQL($sql,'artica_backup');
	
$buttons="
	buttons : [
		
		{name: '<strong style=font-size:18px>$add</strong>', bclass: 'Add', onpress : NewServer$t},
		{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'apply', onpress : Apply$t},
		
	
		],";
	
		$html="
		<table class='POSTFIX_RHSBL_TABLE' style='display: none' id='POSTFIX_RHSBL_TABLE' style='width:100%'></table>
		<script>
		var xsite='';
		$(document).ready(function(){
		$('#POSTFIX_RHSBL_TABLE').flexigrid({
		url: '$page?search=yes&hostname={$_GET["hostname"]}',
		dataType: 'json',
		colModel : [
		{display: '<span style=font-size:22px>$server</span>', name : 'dnsbl', width : 651, sortable : false, align: 'left'},
		{display: 'DEL', name : 'Del', width : 60, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$server', name : 'dnsbl'},
		],
		sortname: 'ID',
		sortorder: 'desc',
		usepager: true,
		title: '<strong style=font-size:30px>$title</strong>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 550,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
var xRemovePostfixRHSBL= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	$('#POSTFIX_RHSBL_TABLE').flexReload();
}

function NewServer$t(){
	Loadjs('$page?popup-add-js=yes&hostname={$_GET["hostname"]}');

}

function Apply$t(){
	Loadjs('postfix.clients_restrictions.progress.php');
}
	
	
function RemovePostfixRHSBL(ID){
	var XHR = new XHRConnection();
	XHR.appendData('remove',ID);
	XHR.sendAndLoad('$page', 'POST',xRemovePostfixRHSBL);
	}
	
</script>
	
	";
	echo $html;
}

function search(){
	$search='%';
	$page=1;
	$port=$_GET["port"];
	$q=new mysql();
	$tpl=new templates();
	
	
	$sql_search=string_to_flexquery();
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
		
	if($sql_search<>null){
	
	
		$sql="SELECT COUNT(*) AS TCOUNT FROM postfix_rhsbl WHERE hostname='{$_GET["hostname"]}' $sql_search";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) AS tcount FROM postfix_rhsbl WHERE hostname='{$_GET["hostname"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT * FROM postfix_rhsbl WHERE hostname='{$_GET["hostname"]}' $sql_search $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = mysql_num_rows($results);
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No data",1);}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$dnsbl=$ligne["dnsbl"];
		$delete=imgsimple("delete-42.png",null,"RemovePostfixRHSBL($ID)");
	
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<strong style='font-size:26px'>$dnsbl</strong>",
						"<center>$delete</center>")
		);
	
	
	}
	echo json_encode($data);
	
}


