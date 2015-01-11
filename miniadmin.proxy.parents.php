<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

if(isset($_POST["AddSquidParentOptionOrginal"])){construct_options();exit;}
if(isset($_POST["DeleteSquidOption"])){delete_options();exit;}
if(isset($_POST["import-perform"])){import_perform();exit;}
if(isset($_POST["EnableParentProxy"])){parameters_save();exit;}
if(isset($_GET["parameters"])){parameters();exit;}

if(isset($_GET["section-parents"])){section_parents();exit;}
if(isset($_GET["search-parents"])){parents_search();exit;}
if(isset($_GET["parent-js"])){parent_js();exit;}
if(isset($_GET["pattern-id"])){rule_id();exit;}
if(isset($_POST["parent-id"])){parent_save();exit;}


if(isset($_GET["search-caches"])){caches_search();exit;}
if(isset($_POST["parent-delete"])){parent_delete();exit;}
if(isset($_GET["parent-js"])){parent_js();exit;}
if(isset($_GET["parent-id"])){parent_tab();exit;}
if(isset($_GET["parent-params"])){parent_params();exit;}
if(isset($_GET["parent-options"])){parent_options();exit;}
if(isset($_GET["parent-options-js"])){parent_options_js();exit;}
if(isset($_GET["parent-options-id"])){parent_options_id();exit;}
if(isset($_GET["search-options"])){parent_options_search();exit;}
if(isset($_GET["import-js"])){parent_import_js();exit;}
if(isset($_GET["parent-import-popup"])){parent_import_popup();exit;}
if(isset($_GET["edit-proxy-parent-options-explain"])){parent_options_explain();exit;}
if(isset($_GET["dump-js"])){dump_js();exit;}
if(isset($_GET["dump-popup"])){dump_popup();exit;}
tabs();


function tabs(){
	$page=CurrentPageName();
	$mini=new boostrap_form();
	$tpl=new templates();
	$array["{parameters}"]="$page?parameters=yes";
	$array["{squid_parents_proxy}"]="$page?section-parents=yes";
	$buuton1=$tpl->_ENGINE_parse_body(button("{apply_parameters}", "Loadjs('squid.compile.php')"));
	$buuton2=$tpl->_ENGINE_parse_body(button("{status}", "Loadjs('$page?dump-js=yes')"));
	echo "<div style='text-align:right;margin:5px'>$buuton1&nbsp;$buuton2</div>".$mini->build_tab($array);
}

function dump_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["parent-js"];
	$title="{status}";
	$title=$tpl->_ENGINE_parse_body($title);
	echo "YahooWin4('800','$page?dump-popup=yes','$title')";	
	
}

function dump_popup(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?dump-peers=yes")));
	
	
	echo "<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:95%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:12px !important'
	id='textarea2$t'>".@implode("\n", $datas);"</textarea>";
}


function parent_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["parent-js"];
	if($ID==0){$title="{add_a_parent_proxy}";}else{$title="{edit_squid_parent_parameters}:: $ID";}
	$title=$tpl->_ENGINE_parse_body($title);
	echo "YahooWin4('700','$page?parent-id=$ID','$title')";
}
function parent_options_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["parent-options-js"];
	$title=$tpl->_ENGINE_parse_body("{add}");
	echo "YahooWin5('650','$page?parent-options-id=$ID','$title')";
}

function parent_import_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	
	$title=$tpl->_ENGINE_parse_body("{import}");
	echo "YahooWin5('650','$page?parent-import-popup=yes','$title')";	
	
}


function parent_tab(){
	$ID=$_GET["parent-id"];
	$page=CurrentPageName();
	$mini=new boostrap_form();
	$array["{parameters}"]="$page?parent-params=$ID";
	if($ID>0){
		$array["{squid_parent_options}"]="$page?parent-options=$ID";
	}
	echo $mini->build_tab($array);
	
}
function delete_options(){
	$q=new mysql();
	$sql="SELECT options FROM squid_parents WHERE ID={$_POST["ID"]}";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$array=unserialize(base64_decode($ligne["options"]));
	$key=$_POST["DeleteSquidOption"];

	writelogs("DELETING $key FOR {$_POST["ID"]}",__FUNCTION__,__FILE__,__LINE__);

	if(!is_array($array)){
		writelogs("Not an array...",__FUNCTION__,__FILE__,__LINE__);
		echo "unable to unserialize $array\n";
		$array=array();
		return;
	}
	unset($array[$key]);
	$newarray=base64_encode(serialize($array));
	$sql="UPDATE squid_parents SET options='$newarray' WHERE ID='{$_POST["ID"]}'";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();

}

function parent_import_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$boot=new boostrap_form();
	
	$array_type["parent"]="parent";
	$array_type["sibling"]="sibling";
	$array_type["multicast"]="multicast";
	
	$btname="{import}";
	
	$boot->set_formtitle("{import}");
	$boot->set_formdescription("{parents_squid_import_explain}");
	$boot->set_hidden("import-perform","yes");
	$boot->set_textarea("import-text", "{parents}",null);
	$boot->set_list("server_type","{server_type}",$array_type,"parent",array("TOOLTIP"=>"{squid_parent_sibling_how_to}"));
	$boot->set_field("options", "{options}", "proxy-only,no-query,round-robin,connect-timeout=7,connect-fail-limit=3,weight=%i");
	$boot->set_checkbox("delete_all", "{delete_all}", 0);
	$boot->set_button($btname);
	$boot->set_RefreshSearchs();
	$boot->set_CloseYahoo("YahooWin5");
	echo $boot->Compile();	
	
}

function import_perform(){
	$OPTIONS=array();
	$delete_all=false;
	
	if(intval($_POST["delete_all"])==1){
		$delete_all=true;
	}
	
	if($_POST["options"]<>null){
		if(strpos($_POST["options"], ",")>0){
			$tb=explode(",",$_POST["options"]);
			while (list($num,$val)=each($tb)){
				$key=trim($val);
				$value=null;
				if(preg_match("#(.+?)=(.+)#", $val,$re)){
					$key=trim($re[1]);
					$value=trim($re[2]);
				}
				$OPTIONS[$key]=$value;
			}
		}else{
			$key=trim($_POST["options"]);
			$value=null;
			if(preg_match("#(.+?)=(.+)#", $_POST["options"],$re)){
				$key=trim($re[1]);
				$value=trim($re[2]);
			}
			$OPTIONS[$key]=$value;
		}
		
	}
	
	
	$prefix="INSERT INTO squid_parents (servername,server_port,server_type,icp_port,options,weight,enabled) VALUES ";
	$tr=explode("\n", $_POST["import-text"]);
	$i=count($tr);
	while (list($num,$val)=each($tr)){
		$val=trim($val);
		
		if($val==null){continue;}
		$NEWOPTION=array();
		reset($OPTIONS);
		while (list($a,$b)=each($OPTIONS)){
			$NEWOPTION[$a]=str_replace("%i", $i, $b);
		}

		$OPTIONSEC=mysql_escape_string2(base64_encode(serialize($NEWOPTION)));
		
		if(!preg_match("#(.+?):([0-9]+)#", $val,$re)){continue;}
		$t[]="('{$re[1]}','{$re[2]}','{$_POST["server_type"]}','0','$OPTIONSEC',$i,1)";
		$i=$i-1;
		
	}
	
	
	$q=new mysql();
	
	if(count($t)>0){
		if($delete_all){
			
			$q->QUERY_SQL("TRUNCATE TABLE `squid_parents`","artica_backup");
			if(!$q->ok){echo $q->mysql_error;}
		}
		
		$q->QUERY_SQL($prefix.@implode(",", $t),"artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
	}
	
}

function parent_params(){
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["parent-params"];
	$array_type["parent"]="parent";
	$array_type["sibling"]="sibling";
	$array_type["multicast"]="multicast";
	$btname="{add}";
	$q=new mysql();
	if($ID>0){
		$sql="SELECT * FROM squid_parents WHERE ID=$ID";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$btname="{apply}";
	}
	
	$addoptions=$tpl->_ENGINE_parse_body("{squid_parent_options}");
	$add=$tpl->_ENGINE_parse_body("{add}");
	$t=time();
	
	if(strlen(trim($ligne["icp_port"]))==0){$ligne["icp_port"]=0;}
	$options=$tpl->_ENGINE_parse_body("{options}");	
	$q=new mysql();
	$boot=new boostrap_form();
	
	
	
	
	if($ID==0){
		$title="{add_a_parent_proxy}";
		$boot->set_CloseYahoo("YahooWin4");
		
	
	}
	if($ID>0){
		$button="{apply}";
		$title=$ligne["servername"];
	}
	
if(!is_numeric($ligne["weight"])){$ligne["weight"]=1;}
if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	
	$boot->set_formtitle($title);
	$boot->set_hidden("parent-id",$ID);
	$boot->set_field("servername", "{hostname}", $ligne["servername"],array("MANDATORY"=>true));
	$boot->set_checkbox("enabled", "{enabled}", $ligne["enabled"]);
	$boot->set_field("weight", "{order}", $ligne["weight"],array("MANDATORY"=>true));
	$boot->set_field("server_port", "{listen_port}", $ligne["server_port"]);
	$boot->set_field("icp_port", "{icp_port}", $ligne["icp_port"],array("TOOLTIP"=>"{icp_port_explain}"));
	$boot->set_list("server_type","{server_type}",$array_type,$ligne["server_type"],array("TOOLTIP"=>"{squid_parent_sibling_how_to}"));
	$boot->set_button($btname);
	$boot->set_RefreshSearchs();
	echo $boot->Compile();	
	
	
}



function parent_save(){
	$q=new mysql();
	$users=new usersMenus();
	$ID=$_POST["parent-id"];
	
	if(preg_match("#^(.+?):([0-9]+)#", $_POST["servername"],$re)){
		$_POST["servername"]=$re[1];
		$_POST["server_port"]=$re[2];
	}
	if(!is_numeric($_POST["weight"])){$_POST["weight"]=1;}
	if($_POST["weight"]==0){$_POST["weight"]=1;}
	if(strlen(trim($_POST["icp_port"]))==null){$_POST["icp_port"]=0;}
	$sql_add="INSERT INTO squid_parents (servername,server_port,server_type,icp_port,weight,enabled)
	VALUES('{$_POST["servername"]}','{$_POST["server_port"]}','{$_POST["server_type"]}','{$_POST["icp_port"]}','{$_POST["weight"]}','{$_POST["enabled"]}')";
	
	$sql_edit="UPDATE squid_parents SET 
		servername='{$_POST["servername"]}',
		server_port='{$_POST["server_port"]}',
		server_type='{$_POST["server_type"]}',
		enabled='{$_POST["enabled"]}',
		icp_port='{$_POST["icp_port"]}',
		weight='{$_POST["weight"]}'
		WHERE ID=$ID";
	
	
	$q=new mysql();
	$sql=$sql_add;
	if($ID>0){$sql=$sql_edit;}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error."\n$sql";
		return;
	}
	
	
	
}

function parameters(){
	$users=new usersMenus();
	$squid=new squidbee();
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$sock=new sockets();
	$DisableDeadParents=$sock->GET_INFO("DisableDeadParents");
	if(!is_numeric($DisableDeadParents)){$DisableDeadParents=0;}
	$DisableDeadParentsSQL=$sock->GET_INFO("DisableDeadParentsSQL");
	if(!is_numeric($DisableDeadParentsSQL)){$DisableDeadParentsSQL=0;}
	
	
	
	$boot->set_checkbox("EnableParentProxy", "{enable_squid_parent}", $squid->EnableParentProxy,array("DISABLEALL"=>true,"TOOLTIP"=>"{EnableParentProxy_explain}"));
	$boot->set_checkbox("prefer_direct", "{prefer_direct}", $squid->prefer_direct,array("TOOLTIP"=>"{squid_prefer_direct}"));
	$boot->set_checkbox("nonhierarchical_direct", "{nonhierarchical_direct}", $squid->nonhierarchical_direct,array("TOOLTIP"=>"{squid_nonhierarchical_direct}"));
	$boot->set_checkbox("DisableDeadParents", "{DisableDeadParents}", $DisableDeadParents,array("TOOLTIP"=>"{squid_DisableDeadParents}"));
	$boot->set_checkbox("DisableDeadParentsSQL", "{DisableDeadParentsSQL}", $DisableDeadParentsSQL,array("TOOLTIP"=>"{squid_DisableDeadSqlParents}"));
	
	
	$boot->set_button("{apply}");
	$form=$boot->Compile();
	echo $form;
	
}

function parameters_save(){
	
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
	$ini->loadString($ArticaSquidParameters);
	$ini->_params["NETWORK"]["EnableParentProxy"]=$_POST["EnableParentProxy"];
	$ini->_params["NETWORK"]["prefer_direct"]=$_POST["prefer_direct"];
	$ini->_params["NETWORK"]["nonhierarchical_direct"]=$_POST["nonhierarchical_direct"];
	$sock->SaveConfigFile($ini->toString(), "ArticaSquidParameters");
	$sock->SET_INFO("DisableDeadParents", $_POST["DisableDeadParents"]);
	$sock->SET_INFO("DisableDeadParentsSQL", $_POST["DisableDeadParentsSQL"]);
	
	
}

function section_parents(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$sock=new sockets();
	$t=time();
	
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{add_a_parent_proxy}", "Loadjs('$page?parent-js=0')"));
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{import}", "Loadjs('$page?import-js=yes')"));
	echo $boot->SearchFormGen("servername","search-parents",null,$EXPLAIN);
}



function parent_delete(){
	
	$users=new usersMenus();
	$sql="DELETE FROM squid_parents WHERE ID={$_POST["parent-delete"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
}


function parent_options(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$sock=new sockets();
	$t=time();
	$ID=$_GET["parent-options"];
	
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{add}", "Loadjs('$page?parent-options-js=$ID')"));
	echo $boot->SearchFormGen("pattern","search-options","&parent-options-search=$ID",$EXPLAIN);	
	
	
}

function parent_options_search(){
	$ID=$_GET["parent-options-search"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sql="SELECT options FROM squid_parents WHERE ID=$ID";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$t=time();
	$array=unserialize(base64_decode($ligne["options"]));
	
	
	$search=string_to_flexregex("search-options");
	if(!is_array($array)){$array=array();}
	while (list($num,$val)=each($array)){
		$c++;
		if($search<>null){if(!preg_match("#$search#", $num)){continue;}}
		
		$md5=md5("PPROXY-OPTION-$ID-$num");
		$delete=imgsimple("delete-48.png","{delete}","DeleteSquidOption('$num','$md5')");
		$tr[]="
		<tr id='serv-$md5'>
		<td style='font-size:18px;'>$num</code></td>
		<td width=1% nowrap>$delete</td>
		</tr>
		
		";		

	}
	
	echo $tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
			<thead>
			<tr>
			<th >{options}</th>
			<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>").@implode("", $tr)."</tbody></table>
					
					
<script>
var rowmem$t='';
		var x_AddSquidOption$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			$('#serv-'+rowmem$t).remove();
			ExecuteByClassName('SearchFunction');
		}		

		function DeleteSquidOption(key,ID){
			var rowmem$t=ID;
			var XHR = new XHRConnection();
			XHR.appendData('DeleteSquidOption',key);
			XHR.appendData('ID',$ID);
			XHR.sendAndLoad('$page', 'POST',x_AddSquidOption$t);
		}
</script>										
";	
	
}



function parent_options_id(){
	$ID=$_GET["parent-options-id"];
$tt=time();
	$t=$_GET["t"];
	$page=CurrentPageName();
	$q=new mysql();
	$sql="SELECT options FROM squid_parents WHERE ID=$ID";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	
	$array=unserialize(base64_decode($ligne["options"]));
	$options[null]="{select}";
	$options[base64_encode("proxy-only")]="proxy-only";
	$options[base64_encode("Weight=n")]="Weight=n";
	$options[base64_encode("ttl=n")]="ttl=n";
	$options[base64_encode("no-query")]="no-query";
	$options[base64_encode("default")]="default";
	$options[base64_encode("round-robin")]="round-robin";
	$options[base64_encode("multicast-responder")]="multicast-responder";
	$options[base64_encode("closest-only")]="closest-only";
	$options[base64_encode("no-digest")]="no-digest";
	$options[base64_encode("no-netdb-exchange")]="no-netdb-exchange";
	$options[base64_encode("no-delay")]="no-delay";
	$options[base64_encode("login=user:password")]="login=user:password";
	$options[base64_encode("connect-timeout=nn")]="connect-timeout=nn";
	$options[base64_encode("digest-url=url")]="digest-url=url";
	//$options[base64_encode("ssl")]="ssl";
	
	$html="
	<table style='width:100%'>
	<tr>	
		<td class=legend style='font-size:18px'>{squid_parent_options}:</td>
		<td>". Field_array_Hash($options,"squid_parent_options_f",base64_encode("proxy-only"),"FillSquidParentOptions$tt()",null,0,
		"font-size:16px;padding:5px")."</td>
	</tr>
	</table>
	<div id='squid_parent_options_filled'></div>
	<script>
	
	function FillSquidParentOptions$tt(){
			var selected=document.getElementById('squid_parent_options_f').value
			LoadAjax('squid_parent_options_filled','$page?edit-proxy-parent-options-explain='+selected+'&ID=$ID&tt=$tt');
		}
		
		var x_AddSquidOption$tt= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			YahooWin5Hide();
			ExecuteByClassName('SearchFunction');
			
		}		
	
	
		function AddSquidOption$tt(){
			var XHR = new XHRConnection();
			XHR.appendData('AddSquidParentOptionOrginal','{$ligne["options"]}');
			XHR.appendData('key',document.getElementById('squid_parent_options_f').value);
			XHR.appendData('ID',$ID);
			if(document.getElementById('parent_proxy_add_value')){
				XHR.appendData('value',document.getElementById('parent_proxy_add_value').value);
			}
			
			XHR.sendAndLoad('$page', 'POST',x_AddSquidOption$tt);
		}
	
	
		FillSquidParentOptions$tt();
	</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}
function construct_options(){
	$ID=$_POST["ID"];
	$q=new mysql();
	$sql="SELECT options FROM squid_parents WHERE ID={$_POST["ID"]}";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$based=unserialize(base64_decode($ligne["options"]));
	$key=base64_decode($_POST["key"]);
	
	writelogs("$ID]decoded key:\"$key\"",__FUNCTION__,__FILE__,__LINE__);
	if(preg_match("#(.+?)=#",$key,$re)){
		$key=$re[1];
	}


	if(!is_array($based)){
		$based[$key]=$_POST["value"];
		writelogs("$ID]send ". serialize($based),__FUNCTION__,__FILE__,__LINE__);
		$NewOptions=base64_encode(serialize($based));
		$q->QUERY_SQL("UPDATE squid_parents SET options='$NewOptions' WHERE ID='$ID'","artica_backup");
		return;
	}

	$based[$key]=$_POST["value"];

	while (list($num,$val)=each($based)){
		if(trim($num)==null){continue;}
		$f[$num]=$val;
	}


	$NewOptions=base64_encode(serialize($f));
	$q->QUERY_SQL("UPDATE squid_parents SET options='$NewOptions' WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}


}

function parents_search(){
	$sock=new sockets();
	$page=CurrentPageName();
	$haarp=new haarp();
	$tpl=new templates();
	$t=time();
	$q=new mysql();
	$cacheFile="/usr/share/artica-postfix/ressources/logs/web/squid.peers.db";
	$color="black";
	$timefile=filemtime($cacheFile);
	$lastdate=date("Y-m-d H:i:s",$timefile);
	
	
	$STATUS=unserialize(@file_get_contents($cacheFile));
	
	$searchstring=string_to_flexquery("search-parents");
	$users=new usersMenus();
	$LIC=0;if($users->CORP_LICENSE){$LIC=1;}
	$delete_rule=$tpl->javascript_parse_text("{delete_cache}");
	$license_error=$tpl->javascript_parse_text("{license_error}");
	$sql="SELECT * FROM squid_parents WHERE 1 $searchstring ORDER BY weight DESC";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){senderrors($q->mysql_error);}
	
	$boot=new boostrap_form();
	$t=time();
	$domains=$tpl->_ENGINE_parse_body("{domains}");
	$all=$tpl->_ENGINE_parse_body("{all}");
	$fetchesWord=$tpl->_ENGINE_parse_body("{fetches}");
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$status_icon="42-green.png";
		$fetches=null;
		$ID=$ligne["ID"];
		$color="black";
		$delete=imgtootltip("delete-24.png",null,"Delete$t('{$ligne["ID"]}')");
		$STATUS_KEY=$ligne["servername"];
		if(!isset($STATUS[$STATUS_KEY]["STATUS"])){
			if(isset($STATUS["Peer{$ligne["ID"]}"])){
				$STATUS_KEY="Peer{$ligne["ID"]}";
			}
		}
		
		if($ligne["icp_port"]>0){$ligne["server_port"]=$ligne["server_port"]."/".$ligne["icp_port"];}
		
		$ligne3=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(md5) as tcount FROM cache_peer_domain WHERE `servername`='{$ligne["servername"]}'","artica_backup"));
		$countDeDomains=$ligne3["tcount"];
		if($countDeDomains==0){$countDeDomains=$all;}
	
		$array=unserialize(base64_decode($ligne["options"]));
		
		if(!isset($STATUS[$STATUS_KEY]["STATUS"])){
			$status_icon="42-green-grey.png";
		}else{
			if($STATUS[$STATUS_KEY]["STATUS"]=="Down"){$status_icon="42-red.png";}
		}
		
		$search=string_to_flexregex("search-options");
		if(!is_array($array)){$array=array();}
		$tty=array();
		while (list($num,$val)=each($array)){
			if($val<>null){
				$val="=$val";
			}
			$tty[]="$num$val";
		}
		$options=@implode(", ", $tty);
		$js=$boot->trswitch("Loadjs('$page?parent-js={$ligne["ID"]}')");
		$jsdomains=$boot->trswitch("Loadjs('squid.cache_peer_domain.php?servername={$ligne["servername"]}&t=$t')");
		
		if(is_numeric($STATUS[$STATUS_KEY]["FETCHES"])){
			$fetches="<span style='font-size:12px'>($fetchesWord: ". FormatNumber($STATUS[$STATUS_KEY]["FETCHES"]).")</span>";
		}
		
		if($ligne["enabled"]==0){
			$status_icon="42-green-grey.png";
			$fetches="<span style='font-size:12px'>($fetchesWord: -)</span>";
			$color="#A7A7A7";
		}
		
		$tr[]="
		<tr id='serv-$ID'>
		<td style='font-size:18px;color:$color' $js width=1% nowrap><img src='img/$status_icon'></td>
		<td style='font-size:18px;color:$color' $js>{$ligne["servername"]} $fetches<div style='font-size:12px'>$options</div></td>
		<td style='font-size:18px;color:$color' $js>{$ligne["server_port"]}</code></td>
		<td style='font-size:18px;color:$color' $js>{$ligne["server_type"]}</code></td>
		<td style='font-size:18px;color:$color' $jsdomains>$countDeDomains $domains</code></td>
		<td width=1% nowrap>$delete</td>
		</tr>
	
		";
	
	}
	$servername=$tpl->_ENGINE_parse_body("{hostname}");
	$listen_port=$tpl->_ENGINE_parse_body("{listen_port}");
	$server_type=$tpl->_ENGINE_parse_body("{server_type}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	
	
	echo $tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
			<thead>
			<tr>
			<th >{status}</th>
			<th >$servername  ($lastdate)</th>
			<th >$listen_port</th>
			<th>$server_type</th>
			<th>$domains</th>
			<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>").@implode("", $tr)."</tbody></table>
<script>
var mem$t='';
	var x_Delete$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	$('#serv-'+mem$t).remove();
}
function Delete$t(ID){
	var LIC=$LIC;
	
	if(confirm('$delete '+ID+'?')){
		mem$t=ID;
		var XHR = new XHRConnection();
		XHR.appendData('parent-delete',ID);
		XHR.sendAndLoad('$page', 'POST',x_Delete$t);
		}
	}
	</script>";
	
	}






function parent_options_explain(){
	$tt=$_GET["tt"];
	if($_GET["edit-proxy-parent-options-explain"]==null){return null;}
	$page=CurrentPageName();
	$options[base64_encode("proxy-only")]="{parent_options_proxy_only}";
	$options[base64_encode("Weight=n")]="{parent_options_proxy_weight}";
	$options[base64_encode("ttl=n")]="{parent_options_proxy_ttl}";
	$options[base64_encode("no-query")]="{parent_options_proxy_no_query}";
	$options[base64_encode("default")]="{parent_options_proxy_default}";
	$options[base64_encode("round-robin")]="{parent_options_proxy_round_robin}";
	$options[base64_encode("multicast-responder")]="{parent_options_proxy_multicast_responder}";
	$options[base64_encode("closest-only")]="{parent_options_proxy_closest_only}";
	$options[base64_encode("no-digest")]="{parent_options_proxy_no_digest}";
	$options[base64_encode("no-netdb-exchange")]="{parent_options_proxy_no_netdb_exchange}";
	$options[base64_encode("no-delay")]="{parent_options_proxy_no_delay}";
	$options[base64_encode("login=user:password")]="{parent_options_proxy_login}";
	$options[base64_encode("connect-timeout=nn")]="{parent_options_proxy_connect_timeout}";
	$options[base64_encode("digest-url=url")]="{parent_options_proxy_digest_url}";
	$options[base64_encode("connect-fail-limit=n")]="{parent_options_proxy_connect_fail_limit}";
	
	
	
	$options_forms[base64_encode("digest-url=url")]=true;
	$options_forms[base64_encode("connect-timeout=nn")]=true;
	$options_forms[base64_encode("ttl=n")]=true;
	$options_forms[base64_encode("Weight=n")]=true;
	$options_forms[base64_encode("login=user:password")]=true;
	$options_forms[base64_encode("connect-fail-limit=n")]=true;
	
	if($options_forms[$_GET["edit-proxy-parent-options-explain"]]){
		$form="
		<div style='width:98%' class=form>
		<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:16px'>". base64_decode($_GET["edit-proxy-parent-options-explain"]).":</td>
			<td>". Field_text("parent_proxy_add_value",null,"font-size:16px !important;padding:3px")."</td>
		</tr>
		</table>";
	
	}
	
	$html="<div class=text-info style='font-size:14px'>{$options[$_GET["edit-proxy-parent-options-explain"]]}</div>
	$form
	<div style='text-align:right'><hr>
	". button("{add} ".base64_decode($_GET["edit-proxy-parent-options-explain"]),"AddSquidOption$tt()",16)."</div>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}