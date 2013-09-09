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
if(isset($_POST["ipaddr"])){bridge_save();exit;}
if(isset($_POST["bridge-delete"])){bridge_delete();exit;}
tabs();


function tabs(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{firewall_bridges}"]="miniadm.network.bridges.iptables.php";
	$array["{interfaces_bridges}"]="$page?bridge-section=yes";
	echo $boot->build_tab($array);
}




function bridges_section(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$OPTIONS["BUTTONS"][]=button("{new_net_bridge}","Loadjs('$page?bridge-js=0')",16);
	$tpl=new templates();
	$expl="<div class=explain>{interface_bridges_explain}</div>";
	echo $expl.$boot->SearchFormGen("ipaddr,name","search-bridge",null,$OPTIONS);


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
	if($_GET["ID"]>0){
		$sql="SELECT * FROM nics_bridge WHERE ID='$ID'";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$title_button="{apply}";
	}

	$nics_array[null]="{select}";
	$ous[null]="{select}";

	$boot->set_field("name", "{name}", $ligne["name"]);
	$boot->set_field("ipaddr","{tcp_address}",$ligne["ipaddr"],array("IPV4"=>true));
	$boot->set_field("netmask","{netmask}",$ligne["netmask"],array("IPV4"=>true));
	$boot->set_field("cdir","CDIR",$ligne["cdir"],array("CDIR"=>"ipaddr,netmask"));
	$boot->set_field("broadcast","{broadcast}",$ligne["broadcast"],array("IPV4"=>true));
	$boot->set_field("gateway","{gateway}",$ligne["gateway"],array("IPV4"=>true));
	$boot->set_hidden("ID", $_GET["ID"]);
	if(!$users->AsSystemAdministrator){$boot->set_form_locked();}
	
	if($ID==0){$boot->set_CloseYahoo("YahooWin2");}
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
}


function bridge_save(){
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	
	
	if($_POST["netmask"]=='___.___.___.___'){$_POST["netmask"]="0.0.0.0";}
	if($_POST["gateway"]=='___.___.___.___'){$_POST["gateway"]="0.0.0.0";}
	if($_POST["ipaddr"]=='___.___.___.___'){$_POST["ipaddr"]="0.0.0.0";}
	
	
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";
	
	}
	
	if($ID>0){
		$sql="UPDATE nics_bridge SET ".@implode(",", $edit)." WHERE ID=$ID";
	}else{
		$sql="INSERT IGNORE INTO nics_bridge (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("system.php?bridge-build=yes");
	
}
function bridge_delete(){
	$sock=new sockets();
	$sock->getFrameWork("system.php?bridge-delete={$_POST["bridge-delete"]}");	
}

function bridge_search(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql();
	$table="nics_bridge";
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
		$cdir=$ligne["cdir"];
		$eth="br{$ligne["ID"]}";
		$eth_text="br{$ligne["ID"]}";
		
		if($ligne["cdir"]==null){
			$ligne["cdir"]=$net->array_TCP[$ligne["nic"]];
			$eth=$ligne["nic"];
		}
		
		if($ligne["cdir"]==null){
			$ligne["cdir"]=$net->array_TCP[$ligne["nic"]];
			$eth=$ligne["nic"];
		}
		$img="folder-network-48.png";
		
		$edit=$boot->trswitch("Loadjs('$page?bridge-js={$ligne["ID"]}')");
		$delete=imgsimple("delete-48.png","{delete}","Delete$t('{$ligne["ID"]}','$md')");
		
		$a=$ip->parseCIDR($cdir);
		if($a[0]==0){
			$img="warning-panneau-24.png";
			$cdir="<span style='color:red'>$cdir</span>";
		}
		
		$tr[]="
		<tr id='$md'>
			<td style='font-size:18px' width=1% nowrap $edit><img src='img/$img'></td>
			<td style='font-size:18px' width=1% nowrap $edit>$eth_text</td>
			<td style='font-size:18px' width=10% nowrap $edit>{$ligne["name"]}</td>
			<td style='font-size:18px' width=5% nowrap $edit>{$ligne["ipaddr"]}</td>
			<td style='font-size:18px' width=5% nowrap $edit>{$ligne["netmask"]}</td>
			<td style='font-size:18px' width=1% nowrap>$delete</td>
		</tr>
		";
				

	}
	$delete_text=$tpl->javascript_parse_text("{delete_nic_bridge}");
	echo $boot->TableCompile(array("ID"=>"ID:colspan=2",
			"org"=>" {name}",
			"ipaddr"=>"{ipaddr}",
			"netmask"=>"{netmask}",
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