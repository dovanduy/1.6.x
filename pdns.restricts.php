<?php
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");


if(posix_getuid()<>0){
	$user=new usersMenus();
	if(!GetRights()){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die();exit();
	}
}

if(isset($_GET["items"])){items();exit;}
if(isset($_GET["item-config"])){item_config();exit;}
if(isset($_GET["item-id"])){item_popup();exit;}
if(isset($_GET["item-id-js"])){item_js();exit;}
if(isset($_POST["addr"])){item_add();exit;}
if(isset($_POST["delete-item"])){item_delete();exit;}
if(isset($_POST["RepairPDNSTables"])){RepairPDNSTables();exit;}
if(isset($_GET["restrictions"])){restrictions();exit;}
if(isset($_POST["EnablePDNSRecurseRestrict"])){EnablePDNSRecurseRestrict();exit;}
table();

function GetRights(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->AsDnsAdministrator){return true;}
	if($users->ASDCHPAdmin){return true;}
}

function table(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$q=new mysql();
	$TB_HEIGHT=500;
	$TB_WIDTH=880;
	$domains=$tpl->_ENGINE_parse_body("{domains}");
	$new_domain_controller=$tpl->_ENGINE_parse_body("{new_domain_controller}");
	$table="records";
	$database='powerdns';
	$t=time();
	
	
	if(!$q->TABLE_EXISTS("records", "powerdns")){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{error_missing_tables_click_to_repair}")."
		<hr>
		<center id='$t'>". button("{repair}", "RepairPDNSTables()","22px")."</center>
		<script>
			var x_RepairPDNSTables=function (obj) {
					var results=obj.responseText;
					if(results.length>0){alert(results);}	
			
					RefreshTab('main_config_pdns');
				}
			function RepairPDNSTables(){
				var XHR = new XHRConnection();
				XHR.appendData('RepairPDNSTables','yes');
				AnimateDiv('$t');
			    XHR.sendAndLoad('$page', 'POST',x_RepairPDNSTables);	
			}
			</script>		
		
		");
		return;
		
	}

	$new_entry=$tpl->_ENGINE_parse_body("{new_allowed_address}");
	$t=time();
	$address=$tpl->_ENGINE_parse_body("{address}");
	$restriction=$tpl->javascript_parse_text("{parameters}");
	$addText=$tpl->javascript_parse_text("{pdns_restrict_explain}");
	
	
	
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewPDNSEntry2$t},
	{name: '$restriction', bclass: 'Settings', onpress : Restrictions$t},
	
	],	";
			//$('#flexRT$t').flexReload();
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$address', name : 'address', width :765, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width :31, sortable : false, align: 'center'},
		
		 	

	],
	$buttons

	searchitems : [
		{display: '$address', name : 'address'},
	],
	sortname: 'address',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

	var x_PdnsZoneDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}	
		$('#row'+mem$t).remove();
	}

function PdnsAddressDelete$t(addr,id){
	mem$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('delete-item',addr);
    XHR.sendAndLoad('$page', 'POST',x_PdnsZoneDelete$t);	
	}
	
function Restrictions$t(){
	YahooWin2('600','$page?restrictions=yes&t=$t','$restriction');
}
	
	var x_SaveDNSEntry$t=function (obj) {
		var results=obj.responseText;
		if (results.length>0){alert(results);return;}
		$('#flexRT$t').flexReload();
	}		

	function NewPDNSEntry2$t(id){
		var addr=prompt('$addText');
		if(addr){
			var XHR = new XHRConnection();
			XHR.appendData('addr',addr);
			XHR.sendAndLoad('$page', 'POST',x_SaveDNSEntry$t);			
		
		}
	}


	
</script>";
	
	echo $html;		
}	


function items(){
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	$users=new usersMenus();

	$sock=new sockets();
	$search='%';
	$table="pdns_restricts";
	$database='artica_backup';
	$page=1;

	if(!$q->TABLE_EXISTS($table, $database)){$q->BuildTables();}
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table, No such table...",0);}
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No data...",0);}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	//id 	domain_id 	name 	type 	content 	ttl 	prio 	change_date 	ordername 	auth
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show($q->mysql_error);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){
		json_error_show("No item");
	}
	$tpl=new templates();
	$EnablePDNSRecurseRestrict=$sock->GET_INFO("EnablePDNSRecurseRestrict");
	if(!is_numeric($EnablePDNSRecurseRestrict)){$EnablePDNSRecurseRestrict=0;}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5($ligne["address"]);
		$color="black";
		if($EnablePDNSRecurseRestrict==0){$color="#8a8a8a";$colored=$color;}
		$delete=imgsimple("delete-24.png",null,"PdnsAddressDelete$t('{$ligne["address"]}','$id')");
		$text_recursive=null;
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
		"<span style='font-size:16px;color:$color'>{$ligne["address"]}</span>",
		$delete )
		);
	}
	
	
echo json_encode($data);		
	
}

function RepairPDNSTables(){
	$sock=new sockets();
	echo @implode("\n",unserialize(base64_decode($sock->getFrameWork("pdns.php?repair-tables=yes"))));
}



function item_add(){
	$addr=$_POST["addr"];
	
	$ip=new IP();
	
	if(!$ip->isIPAddressOrRange($addr)){
		echo "Wrong Network Address or range \"$addr\"";
		return;
		
	}
	
	
	$q=new mysql();
	$sql="INSERT IGNORE INTO pdns_restricts (`address`) VALUES('$addr')";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("pdns.php?reconfigure=yes");	
	
}
function item_delete(){
	$id=$_POST["delete-item"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM pdns_restricts WHERE `address`='$id'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("pdns.php?reconfigure=yes");	
}

function restrictions(){
	$t=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnablePDNSRecurseRestrict=$sock->GET_INFO("EnablePDNSRecurseRestrict");
	if(!is_numeric($EnablePDNSRecurseRestrict)){$EnablePDNSRecurseRestrict=0;}
	$p=Paragraphe_switch_img("{enable_restriction}", "{enable_restriction_pdns_explain}","EnablePDNSRecurseRestrict",$EnablePDNSRecurseRestrict,null,550);
	
	$html="<table style='width:98%' class=form>
	<tr>
		<td>$p</td>
	</tr>
	<tr>	
		<td align='right'><hr>". button("{apply}","Save$t()","18px")."</td>
	</tr>
	</table>

	<script>
	var xSave$t= function (obj) {
		var results=obj.responseText;
		YahooWin2Hide();
		if(results.length>2){
			alert(results);
			
			return;

			}
			
			$('#flexRT$t').flexReload();
				 
		}		

function Save$t(){
			var XHR = new XHRConnection();
			XHR.appendData('EnablePDNSRecurseRestrict',document.getElementById('EnablePDNSRecurseRestrict').value);
			XHR.sendAndLoad('$page', 'POST',xSave$t);			
		
		}			
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function EnablePDNSRecurseRestrict(){
	$sock=new sockets();
	$sock->SET_INFO("EnablePDNSRecurseRestrict", $_POST["EnablePDNSRecurseRestrict"]);
	$sock->getFrameWork("cmd.php?pdns-restart=yes");
	
}

