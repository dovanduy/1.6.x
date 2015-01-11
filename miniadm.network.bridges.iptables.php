<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");


if(isset($_GET["search-bridge"])){bridge_search();exit;}
if(isset($_GET["bridge-js"])){bridge_js();exit;}
if(isset($_GET["bridge-popup"])){bridge_popup();exit;}
if(isset($_GET["bridge-section"])){bridges_section();exit;}
if(isset($_POST["nic_inbound"])){bridge_save();exit;}
if(isset($_POST["bridge-delete"])){bridge_delete();exit;}
bridges_section();







function bridges_section(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$OPTIONS["BUTTONS"][]=button("{new_net_bridge}","Loadjs('$page?bridge-js=0')",16);
	$expl="<div class=text-info>{firewall_bridges_explain}</div>";
	echo $expl.$boot->SearchFormGen("nic_inbound,nic_linked","search-bridge",null,$OPTIONS);


}
function bridge_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	header("content-type: application/x-javascript");
	$ID=$_GET["bridge-js"];
	$title="{new_net_bridge}";
	if($ID>0){
		$title="{network_bridge}::$ID";
	}
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin2('700','$page?bridge-popup=yes&ID=$ID','$title')";
	
}
function bridge_popup(){
	$boot=new boostrap_form();
	$sock=new sockets();
	$users=new usersMenus();
	$ldap=new clladp();
	$ID=$_GET["ID"];
	$title_button="{add}";
	$nics=unserialize(base64_decode($sock->getFrameWork("cmd.php?list-nics=yes")));
	$IP=new networking();
	
	while (list ($key, $value) = each ($nics) ){
		
		$array=$IP->GetNicInfos($value);

		$NICZ[$value]=$value. " [{$array["IPADDR"]}]";
		
	}
	
	$boot->set_list("nic_inbound", "{from}", $NICZ,null);
	$boot->set_list("nic_linked", "{to}", $NICZ,null);
	$boot->set_hidden("ID", $_GET["ID"]);
	if(!$users->AsSystemAdministrator){$boot->set_form_locked();}
	
	if($ID==0){$boot->set_CloseYahoo("YahooWin2");}
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
}


function bridge_save(){
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	$q=new mysql();
	if(!$q->FIELD_EXISTS("iptables_bridge","nic_inbound","artica_backup")){
		$sql="ALTER TABLE `iptables_bridge` ADD `nic_inbound` varchar(40) , ADD INDEX ( `nic_inbound` ) ";
		$q->QUERY_SQL($sql,'artica_backup');
		
	}
	
	
	$_POST["zmd5"]=md5($_POST["nic_inbound"].$_POST["nic_linked"]);
	
		
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";
	
	}
	
	if($ID>0){
		$sql="UPDATE iptables_bridge SET ".@implode(",", $edit)." WHERE ID=$ID";
	}else{
		$sql="INSERT IGNORE INTO iptables_bridge (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?virtuals-ip-reconfigure=yes");
	
}
function bridge_delete(){
	$sock=new sockets();
	$ID=$_POST["bridge-delete"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM iptables_bridge WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$sock->getFrameWork("cmd.php?virtuals-ip-reconfigure=yes");
}

function bridge_search(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql();
	$table="iptables_bridge";
	$database="artica_backup";
	$t=time();
	
	$ORDER=$boot->TableOrder(array("ID"=>"DESC"));
	
	$sock=new sockets();
	$net=new networking();
	$ip=new IP();
	$interfaces=unserialize(base64_decode($sock->getFrameWork("cmd.php?ifconfig-interfaces=yes")));
	
	$searchstring=string_to_flexquery("search-bridge");
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	$net=new networking();
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md=md5(serialize($ligne));
		$ip=new IP();
		$img="folder-network-48.png";
		$cdir=$ligne["cdir"];
		$eth="br{$ligne["ID"]}";
		$eth_text="br{$ligne["ID"]}";
		
		
		
		
		
			$array=$net->GetNicInfos($ligne["nic_inbound"]);
			$nic_inbound_ip=$array["IPADDR"];
			$array=$net->GetNicInfos($ligne["nic_linked"]);
			$nic_linked_ip=$array["IPADDR"];
			$delete=imgsimple("delete-48.png","{delete}","Delete$t('{$ligne["ID"]}','$md')");
		
		$tr[]="
		<tr id='$md'>
			<td style='font-size:18px' width=1% nowrap><img src='img/$img'></td>
			<td style='font-size:18px' width=1% nowrap>{$ligne["nic_inbound"]} - $nic_inbound_ip</td>
			<td style='font-size:18px' width=10% nowrap>{$ligne["nic_linked"]} - $nic_linked_ip</td>
			<td style='font-size:18px' width=1% nowrap>$delete</td>
		</tr>
		";
				

	}
	$delete_text=$tpl->javascript_parse_text("{delete_nic_bridge}");
	echo $boot->TableCompile(array("ID"=>"ID",
			"nic_inbound"=>"{from}",
			"nic_linked"=>"{to}",
			"delete"=>null,
		),$tr)."
					
<script>
var mem$t='';
var xDelete$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#'+mem$t).remove();
}
function Delete$t(ID,mem){
	mem$t=mem;
	if(confirm('$delete_text ID: '+ID+'?')){
		mem$t=mem;
		var XHR = new XHRConnection();
		XHR.appendData('bridge-delete',ID);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
		}
	}
</script>					
";
	
	
}