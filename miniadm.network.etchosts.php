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


if(isset($_GET["search-etchosts"])){etchosts_search();exit;}
if(isset($_GET["etchosts-js"])){etchosts_js();exit;}
if(isset($_GET["etchosts-popup"])){etchosts_popup();exit;}
if(isset($_POST["ipaddr"])){etchosts_save();exit;}
if(isset($_POST["etchosts-delete"])){etchosts_delete();exit;}
etchostss_section();

function etchostss_section(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$tpl=new templates();
	$OPTIONS["BUTTONS"][]=button("{add_new_entry}","Loadjs('$page?etchosts-js=yes')",16);
	echo 
	$tpl->_ENGINE_parse_body("<div class=explain>{etc_hosts_explain}</div>").	$boot->SearchFormGen("ipaddr,hostname","search-etchosts",null,$OPTIONS);


}
function etchosts_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	header("content-type: application/x-javascript");
	$ID=$_GET["etchosts-js"];
	$title="{add_new_entry}";
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin2('700','$page?etchosts-popup=yes&ID=$ID','$title')";
	
}
function etchosts_popup(){
	$boot=new boostrap_form();
	$sock=new sockets();
	$users=new usersMenus();
	$ldap=new clladp();
	$ID=$_GET["ID"];
	$title_button="{add_new_entry}";
	$boot->set_field("hostname", "{hostname}", $ligne["name"]);
	$boot->set_field("ipaddr","{tcp_address}",$ligne["ipaddr"],array("IPV4"=>true));
	
	$boot->set_field("alias", "{alias}", null);
	if(!$users->AsSystemAdministrator){$boot->set_form_locked();}
	
	if($ID==0){$boot->set_CloseYahoo("YahooWin2");}
	$boot->set_button("{add}");
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
}


function etchosts_save(){
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	
	
	
	if($_POST["ipaddr"]=='___.___.___.___'){$_POST["ipaddr"]=null;}
	if($_POST["ipaddr"]==null){alert("NO IP ADDR");return ;}
	
	
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";
	
	}
	
	if($ID>0){
		$sql="UPDATE net_hosts SET ".@implode(",", $edit)." WHERE ID=$ID";
	}else{
		$sql="INSERT IGNORE INTO net_hosts (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("system.php?etchosts-build=yes");
	
}
function etchosts_delete(){
	$sock=new sockets();
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM net_hosts WHERE zmd5='{$_POST["etchosts-delete"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock->getFrameWork("system.php?etchosts-build=yes");
}

function etchosts_search(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql();
	$table="net_hosts";
	$database="artica_backup";
	$t=time();
	
	if($q->COUNT_ROWS($table, $database)==0){
		$sock->getFrameWork("system.php?etchosts-default=yes");
	}
	
	$ORDER=$boot->TableOrder(array("hostname"=>"ASC"));
	
	$sock=new sockets();
	$net=new networking();
	$ip=new IP();
	$interfaces=unserialize(base64_decode($sock->getFrameWork("cmd.php?ifconfig-interfaces=yes")));
	
	$searchstring=string_to_flexquery("search-etchosts");
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	$net=new networking();
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md=md5(serialize($ligne));
		
		
		$delete=imgsimple("delete-48.png","{delete}","Delete$t('{$ligne["zmd5"]}','$md','{$ligne["hostname"]}@{$ligne["ipaddr"]}')");
		
				
		$tr[]="
		<tr id='$md'>
			<td style='font-size:20px' width=90% nowrap >{$ligne["hostname"]}</td>
			<td style='font-size:20px' width=5% nowrap >{$ligne["ipaddr"]}</td>
			<td style='font-size:20px' width=5% nowrap >{$ligne["alias"]}</td>
			<td style='font-size:20px' width=1% nowrap>$delete</td>
		</tr>
		";
				

	}
	$delete_text=$tpl->javascript_parse_text("{delete_nic_etchosts}");
	echo $boot->TableCompile(array(
			"hostname"=>"{hostname}",
			"ipaddr"=>"{ipaddr}",
			"alias"=>"{alias}",
			"delete"=>null,
		),$tr)."
					
<script>
var mem$t='';
var xDelete$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#'+mem$t).remove();
}
function Delete$t(md5,mem,ipaddr){
	mem$t=mem;
	if(confirm('$delete_text: '+ipaddr+'?')){
		mem$t=mem;
		var XHR = new XHRConnection();
		XHR.appendData('etchosts-delete',md5);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
		}
	}
</script>					
";
	
	
}