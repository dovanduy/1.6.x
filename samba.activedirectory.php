<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.kav4samba.inc');
	if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);$GLOBALS["VERBOSE"]=true;}
	if(isset($_GET["debug-page"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);$GLOBALS["VERBOSE"]=true;}

	
	if(!CheckSambaRights()){
		$tpl=new templates();
		$ERROR_NO_PRIVS=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "<H1>$ERROR_NO_PRIVS</H1>";die();
	}
	
	if(isset($_GET["domains-show"])){domains_show();exit;}
	if(isset($_GET["domains-show-list"])){domains_show_list();exit;}
	if(isset($_GET["domain-info"])){domain_info();exit;}
	if(isset($_GET["groups"])){groups();exit;}
	if(isset($_GET["groups-show-list"])){groups_list();exit;}
	if(isset($_GET["members-js"])){members_js();exit;}
	if(isset($_GET["members-table"])){members_table();exit;}
	if(isset($_GET["members-show-list"])){members_list();exit;}
	
tabs();


function members_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{members}::{$_GET["gpid"]}");
	echo "YahooWin3('650','$page?members-table=yes&gpid={$_GET["gpid"]}','$title');";
	
	
}


function NOT_AD(){
	
	$sock=new sockets();
	$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
	if($EnableSambaActiveDirectory==0){
	$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("
		<center style='margin:50px'>
		<table style='width:100%'>
			<tr>
				<td width=1%><img src='img/error-128.png'></td>
				<td valign='top' style='font-size:18px'>{SAMBA_NOT_CONNECTED_AD_ERROR}</td>
			</tr>
		</table>
		</center>
		
		");
		
		return true;
	}
	
	return false;
}

function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$array["parameters"]='{ad_samba_member}';
	$array["domains-show"]='{current_domains}';
	$array["groups"]='{groups}';
	
	
	
	
	$tpl=new templates();
	

	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="parameters"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"ad.connect.php?popup=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_samba_active_directory style='width:100%;height:710px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_samba_active_directory').tabs();
			});
		</script>";		
		
	
	
}

function domain_info(){
	if(NOT_AD()){return;}
	$sock=new sockets();
	$DC_INFO=base64_decode($sock->getFrameWork("samba.php?dcinfo=".base64_encode($_GET["domain-info"])));
	
	echo "<div style='font-size:18px'>{$_GET["domain-info"]} - $DC_INFO</div>";
	
	echo "<table style='width:99%' class=form>";
	
	$array=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfomoinsd=".base64_encode($_GET["domain-info"]))));
	while (list ($num, $line) = each ($array) ){
		if(preg_match("#(.*?):(.*)#", $line,$re)){
			
			echo "
			<tr>
				<td class=legend style='font-size:14px'>".trim($re[1])."<td>
				<td style='font-size:14px;font-weight:bold'>".trim($re[2])."<td>
			</tr>
			";
		}
	}
	
	echo "</table>";
	
	
	
	
	
	$array=unserialize(base64_decode($sock->getFrameWork("samba.php?dsgetdcname=".base64_encode($_GET["domain-info"]))));
	
	
	
echo "<div style='width:99%' class=form>";
	
	
	while (list ($num, $hostname) = each ($array) ){
		$hostname=trim($hostname);
		if($hostname==null){continue;}
		if(is_numeric($hostname)){continue;}
		if(isset($all[$hostname])){continue;}
		$all[$hostname]=true;
		echo "<div style='font-size:14px'>DC: {$_GET["domain-info"]} &laquo;<strong>$hostname</strong>&raquo;</div>";
		
		
	}
	echo "</div>";
}

function domains_show(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$domains=$tpl->_ENGINE_parse_body("{domain_name}");
	$dns_domaine=$tpl->_ENGINE_parse_body("{dns_domain}");
	$add_a_shared_folder=$tpl->_ENGINE_parse_body("{add_a_shared_folder}");
	$default_settings=$tpl->_ENGINE_parse_body("{default_settings}");
	$folder=$tpl->_ENGINE_parse_body("{folders}");
	$trash=$tpl->_ENGINE_parse_body("{trash}");
	$Transitive=$tpl->_ENGINE_parse_body("{transitive}");
	$trust=$tpl->_ENGINE_parse_body("{trust_type}");

	$TABLE_WIDTH=873;
	$pATH_WITH=415;
	if(isset($_GET["bypopup"])){
		$TABLE_WIDTH=736;
		$pATH_WITH=278;
	}
	
	
	
$buttons="
		buttons : [
		{name: '$add_a_shared_folder', bclass: 'add', onpress : AddShared$t},
		{name: '$add_usb', bclass: 'Usb', onpress : AddUsb$t},
		{name: '$default_settings', bclass: 'Reconf', onpress : Defsets$t},
		
		],";	

$buttons=null;




	
$html="
<input type='hidden' id='del_folder_name' value='{del_folder_name}'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
var IDTMP=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?domains-show-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$domains', name : 'icon1', width :256, sortable : false, align: 'left'},
		{display: '$dns_domaine', name : 'flags', width :219, sortable : true, align: 'left'},
		{display: '$trust', name : 'trust', width : 128, sortable : false, align: 'center'},
		{display: 'online', name : 'online', width : 31, sortable : false, align: 'center'},
		{display: 'In', name : 'In', width : 31, sortable : false, align: 'center'},
		{display: 'Out', name : 'Out', width : 31, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$domains', name : '$domains'},
		],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 790,
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function DomainINFO$t(domain){
	YahooWin2('550','$page?domain-info='+domain,'$domains::'+domain);

}

</script>


";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);		
	
}

function members_table(){
if(NOT_AD()){return;}
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$groups=$tpl->_ENGINE_parse_body("{groups}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$add_a_shared_folder=$tpl->_ENGINE_parse_body("{add_a_shared_folder}");
	$default_settings=$tpl->_ENGINE_parse_body("{default_settings}");
	$folder=$tpl->_ENGINE_parse_body("{folders}");
	$trash=$tpl->_ENGINE_parse_body("{trash}");
	$Transitive=$tpl->_ENGINE_parse_body("{transitive}");
	$trust=$tpl->_ENGINE_parse_body("{trust_type}");

	$TABLE_WIDTH=873;
	$pATH_WITH=415;
	if(isset($_GET["bypopup"])){
		$TABLE_WIDTH=736;
		$pATH_WITH=278;
	}
	
	
	
$buttons="
		buttons : [
		{name: '$add_a_shared_folder', bclass: 'add', onpress : AddShared$t},
		{name: '$add_usb', bclass: 'Usb', onpress : AddUsb$t},
		{name: '$default_settings', bclass: 'Reconf', onpress : Defsets$t},
		
		],";	

$buttons=null;




	
$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
var IDTMP$t=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?members-show-list=yes&t=$t&gpid={$_GET["gpid"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width :31, sortable : false, align: 'center'},
		{display: '$members', name : 'uid', width :554, sortable : true, align: 'left'},
		
		],
	$buttons
	searchitems : [
		{display: '$members', name : 'uid'},
		],
	sortname: 'uid',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 630,
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});
</script>


";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);		
		
	
	
	
}

function groups(){
	if(NOT_AD()){return;}
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$groups=$tpl->_ENGINE_parse_body("{groups}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$add_a_shared_folder=$tpl->_ENGINE_parse_body("{add_a_shared_folder}");
	$default_settings=$tpl->_ENGINE_parse_body("{default_settings}");
	$folder=$tpl->_ENGINE_parse_body("{folders}");
	$trash=$tpl->_ENGINE_parse_body("{trash}");
	$Transitive=$tpl->_ENGINE_parse_body("{transitive}");
	$trust=$tpl->_ENGINE_parse_body("{trust_type}");

	$TABLE_WIDTH=873;
	$pATH_WITH=415;
	if(isset($_GET["bypopup"])){
		$TABLE_WIDTH=736;
		$pATH_WITH=278;
	}
	
	
	
$buttons="
		buttons : [
		{name: '$add_a_shared_folder', bclass: 'add', onpress : AddShared$t},
		{name: '$add_usb', bclass: 'Usb', onpress : AddUsb$t},
		{name: '$default_settings', bclass: 'Reconf', onpress : Defsets$t},
		
		],";	

$buttons=null;




	
$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
var IDTMP$t=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?groups-show-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		
		{display: '&nbsp;', name : 'icon', width :31, sortable : true, align: 'center'},
		{display: '$groups', name : 'group', width :615, sortable : true, align: 'left'},
		{display: '$members', name : 'members', width :85, sortable : true, align: 'right'},
		
		],
	$buttons
	searchitems : [
		{display: '$groups', name : 'group'},
		],
	sortname: 'group',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 790,
	height: 539,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function DomainINFO$t(domain){
	YahooWin2('550','$page?domain-info='+domain,'$domains::'+domain);

}

</script>


";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);		
	
}

function members_list(){
	$tpl=new templates();
	$q=new mysql();	
	$MyPage=CurrentPageName();
	$table="activedirectoryusers";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="AND gpid={$_GET["gpid"]}";
	
	if($q->COUNT_ROWS($table,$database)==0){json_error_js($tpl->_ENGINE_parse_body("TABLE:$table<br>{error_no_datas}"));}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page =$_POST['page'];}
	
	
	if($_POST["query"]<>null){
		$_POST["query"]="*{$_POST["query"]}*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		if(strpos(" $search", "%")>0){
			$FILTER="AND (`{$_POST["qtype"]}` LIKE '$search') $FORCE_FILTER";
		}		
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FILTER$FORCE_FILTER";
		
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		if($FORCE_FILTER<>null){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			$total = $ligne["TCOUNT"];	
			
		}else{
			$total = $q->COUNT_ROWS($table,$database);
		}
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if($FILTER==null){$FILTER=$FORCE_FILTER;}

	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE 1 $FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$ligne["group"]=utf8_decode($ligne["group"]);
		$data['rows'][] = array(
		'id' => $ligne["uid"],
		'cell' => array(
		"<img src='img/user7-32.png'>",
		"<span style='font-size:16px;font-weight:bold'>{$ligne["uid"]}</span>",
		
		)
		);
	}
echo json_encode($data);	
		
	
}

function groups_list(){
	$tpl=new templates();
	$q=new mysql();	
	$MyPage=CurrentPageName();
	$table="activedirectorygroups";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="";
	
	if($q->COUNT_ROWS($table,$database)==0){json_error_js($tpl->_ENGINE_parse_body("TABLE:$table<br>{error_no_datas}"));}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page =$_POST['page'];}
	
	
	if($_POST["query"]<>null){
		$_POST["query"]="*{$_POST["query"]}*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		if(strpos(" $search", "%")>0){
			$FILTER="AND (`{$_POST["qtype"]}` LIKE '$search') $FORCE_FILTER";
		}		
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FILTER$FORCE_FILTER";
		
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error\n$sql");}
		$total = $ligne["TCOUNT"];
		
	}else{
		if($FORCE_FILTER<>null){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
			if(!$q->ok){json_error_show("$q->mysql_error\n$sql");}
			$total = $ligne["TCOUNT"];	
			
		}else{
			$total = $q->COUNT_ROWS($table,$database);
		}
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if($FILTER==null){$FILTER=$FORCE_FILTER;}

	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE 1 $FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$ligne["group"]=utf8_decode($ligne["group"]);
		$data['rows'][] = array(
		'id' => $ligne["gpid"],
		'cell' => array(
		"<img src='img/win7groups-32.png'>",
		"<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?members-js=yes&gpid={$ligne["gpid"]}');\" style='font-size:16px;text-decoration:underline'>{$ligne["group"]}</span>",
		"<span style='font-size:18px'>{$ligne["UsersNum"]}</span>"
		)
		);
	}
echo json_encode($data);	
	
}	
	
function domains_show_list(){
	$sock=new sockets();
	$t=$_GET["t"];
	//wbinfoalldom
	
	$array=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfo-m-verb=yes")));
	
	
	while (list ($num, $ligne) = each ($array) ){
		if($GLOBALS["VERBOSE"]){echo "<div><code style='font-size:14px'>$ligne</code></div>";}
		
		if(preg_match("#^([A-Z]+)\s+(.*?)\s+([A-Za-z]+)\s+(Yes|No)\s+(Yes|No)\s+(Yes|No)#", $ligne,$re)){
			$DOMAINS[trim($re[1])]=array("DNS"=>trim($re[2]),"TRUST"=>$re[3],"TRANS"=>$re[4],"IN"=>$re[5],"OUT"=>$re[6]);
		}
	}
	$array=unserialize(base64_decode($sock->getFrameWork("samba.php?wbinfoalldom=yes")));
	while (list ($num, $ligne) = each ($array) ){
		if(preg_match("#^(.*?):(.*)#", $ligne,$re)){
			$DOMAINS[trim($re[1])]["ONLINE"]=trim($re[2]);
		}
	}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();	
	
	if(function_exists("string_to_regex")){
		if($_POST["query"]<>null){$search=string_to_regex($_POST["query"]);}
	}
	
	$c=0;
	while (list ($WORKGROUP, $array) = each ($DOMAINS) ){	
		if($search<>null){if(!preg_match("#$search#", $WORKGROUP)){continue;}}
		
		$transitive="ok24-grey.png";
		$online="ok24-grey.png";
		$in="ok24-grey.png";
		$out="ok24-grey.png";
		
		
		if(trim($array["TRANS"])=="Yes"){$transitive="ok24.png";}
		if(trim($array["ONLINE"])=="online"){$online="ok24.png";}
		if(trim($array["IN"])=="Yes"){$in="ok24.png";}
		if(trim($array["OUT"])=="Yes"){$out="ok24.png";}
		$c++;
		
		$data['rows'][] = array(
		'id' => $md,
		'cell' => array(
		 
		 "<a href=\"javascript:blur();\" OnClick=\"javascript:DomainINFO$t('$WORKGROUP');\" style='font-size:16px;text-decoration:underline'>$WORKGROUP</span>",
		"<span style='font-size:16px'>{$array["DNS"]}</span>",
		"<span style='font-size:16px'>{$array["TRUST"]}</span>",
		"<img src='img/$transitive'>",
		"<img src='img/$online'>",
		"<img src='img/$in'>",
		"<img src='img/$out'>",
		
		)
		);	
		
	}
	
	$data['total'] = $c;
	
	
echo json_encode($data);
	
	
}
