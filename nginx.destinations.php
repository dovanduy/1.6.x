<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.nginx-sources.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<p class=text-error>". $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}")."</p>";
		die();exit();
	}
	
	if(isset($_GET["list"])){list_items();exit;}
	if(isset($_GET["delete-destination-js"])){delete_destination_js();exit;}
	if(isset($_POST["delete-destination"])){delete_destination();exit;}

table();


function table(){
	$page=CurrentPageName();
	
	
	$t=time();
	$array["TOKEN"]="list";
	$array["TIME"]=$t;
	$array["TITLE"]="{destination_servers_list}";
	$array["currentpage"]=CurrentPageName();
	$array["sortname"]="servername";
	$array["cols"][]=array("source_name:servername",550);
	$array["cols"][]=array("destination_servers_list:ipaddr",550);
	$array["cols"][]=array("compile2",90);
	$array["cols"][]=array("delete",90);
	$array["buttons"][]=array("{new_server}","New$t","add");
	$array["func"]["New$t"]="Loadjs('nginx.peer.php?js=yes&ID=0&t=$t')";
	
	echo TABLE_FLEXIGRID($array);
	
}

function TABLE_FLEXIGRID($array){
	$t=$array["TIME"];
	$buttons=null;
	$tpl=new templates();
	$title=null;
	if(!isset($array["TITLE"])){$array["TITLE"]=null;}
	if($array["TITLE"]<>null){
		$title="<span style=font-size:30px>".$tpl->javascript_parse_text($array["TITLE"])."</span>";
	}
	
$f[]="
<table class='NGINX_DESTINATION_MAIN_TABLE' style='display: none' id='NGINX_DESTINATION_MAIN_TABLE' style='width:99%'></table>
<script>		
function BuildTable$t(){
		$('#NGINX_DESTINATION_MAIN_TABLE').flexigrid({
			url: '{$array["currentpage"]}?{$array["TOKEN"]}=yes&t=$t',
			dataType: 'json',
			colModel : [";
while (list ($num, $subarray) = each ($array["cols"]) ){
	$caption=null;
		if(strpos($subarray[0], ":")>0){
			$FI=explode(":",$subarray[0]);
			$caption=$tpl->javascript_parse_text("{".$FI[0]."}");
			$subarray[0]=$FI[1];
		}
		if($caption==null){
			$caption=$tpl->javascript_parse_text("{".$subarray[0]."}");
		}
		$align="left";
		if($subarray[0]=="delete"){$align="center";}
		$f[]="{display: '<span style=font-size:20px>$caption</span>', name : '{$subarray[0]}', width :{$subarray[1]}, sortable : false, align: '$align'},";
}

$f[]="],";

if(count($array["buttons"]>0)){
	$f[]="buttons : [";
	while (list ($num, $subarray) = each ($array["buttons"]) ){
		$subarray[0]=$tpl->javascript_parse_text($subarray[0]);
		$f[]="{name: '<strong style=font-size:22px>{$subarray[0]}</strong>', bclass: '{$subarray[2]}', onpress : {$subarray[1]}},";
	}
		
	$f[]="],	";	
	
}
$f[]="";	
$f[]="searchitems : [";

reset($array["cols"]);
while (list ($num, $subarray) = each ($array["cols"]) ){
	
	$caption=null;
	if(strpos($subarray[0], ":")>0){
		$FI=explode(":",$subarray[0]);
		$caption=$tpl->javascript_parse_text("{".$FI[0]."}");
		$subarray[0]=$FI[1];
	}
	if($caption==null){
		$caption=$tpl->javascript_parse_text("{".$subarray[0]."}");
	}
	
	
	$align="left";
	if($subarray[0]=="delete"){$align="center";}
	$f[]="{display: '<span style=font-size:20px>$caption</span>', name : '{.$subarray[0]}'},";
}

			
	$f[]="],
			sortname: '{$array["sortname"]}',
			sortorder: 'asc',
			usepager: true,
			title: '<span style=font-size:30px>$title</strong>',
			useRp: true,
			rp: 50,
			showTableToggleBtn: false,
			width: '99%',
			height: 550,
			singleSelect: true,
			rpOptions: [10, 20, 30, 50,100,200,500]
	
		});
	}
";
	
	while (list ($functioname, $content) = each ($array["func"]) ){
		$f[]="function $functioname(){ $content; }";
	}

$f[]="BuildTable$t();
</script>";
	
return @implode("\n", $f);	
}


function delete_destination_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$source_id=$_GET["ID"];
	if(!is_numeric($source_id)){$source_id=0;}
	if($source_id==0){return;}
	
	if($source_id>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM reverse_sources WHERE ID='$source_id'"));
		$title=$ligne["servername"];
	}
	
	$title=$tpl->javascript_parse_text("{delete} $title ?");
	
	echo "
var xDelete$t = function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return}
	$('#flexRT{$_GET["t"]}').flexReload();
}			
function Delete$t(){
	if(!confirm('$title')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-destination','$source_id');
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
}

 Delete$t();";
	
	
}

function delete_destination(){
	$q=new mysql_squid_builder();
	$reverse=new nginx_sources();
	$reverse->DeleteSource($_POST["delete-destination"]);


}


function list_items(){
	$STATUS=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/nginx.status.acl"));
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$all_text=$tpl->_ENGINE_parse_body("{all}");
	$GLOBALS["CLASS_TPL"]=$tpl;
	$q=new mysql_squid_builder();
	$OrgPage="miniadmin.proxy.reverse.php";
	$CurrentPage=CurrentPageName();
	$sock=new sockets();
	$GLOBALS["CLASS_TPL"]=$tpl;
	$EnableFreeWeb=intval($sock->GET_INFO("EnableFreeWeb"));
	$t=$_GET["t"];
	$FORCE=1;
	$search='%';
	$table="reverse_sources";
	$page=1;
	$freeweb_compile_background=$tpl->javascript_parse_text("{freeweb_compile_background}");
	$reset_admin_password=$tpl->javascript_parse_text("{reset_admin_password}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_nginx_text=$tpl->javascript_parse_text("{delete_freeweb_nginx_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");

	$total=0;
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("no data",1);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];

	}else{
		if(strlen($FORCE)>2){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
			$total = $ligne["TCOUNT"];
		}else{
			$total = $q->COUNT_ROWS($table, "artica_events");
		}
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM `$table` WHERE $FORCE $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	if(mysql_num_rows($results)==0){json_error_show("no data");}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
	
		$icon="64-idisk-server.png";
		$icon_failed="64-idisk-server-grey.png";
		$icon2="folder-network-64.png";
		$icon2_failed="folder-network-64-grey.png";
		$isSuccessIcon="none-20.png";
		$isSuccessIcon_failed="check-32-grey.png";
		$isSuccessIcon_success="check-32.png";
		$isSuccessLink=null;
		$cacheid=$ligne["cacheid"];
		$CacheText=null;
	
		if($ligne["OnlyTCP"]==1){
			$icon="folder-network-64.png";
			$icon_failed="folder-network-64-grey.png";
		}
	
		$color="black";
		$md=md5(serialize($ligne));
		if($ligne["enabled"]==0){
			$icon=$icon_failed;
			$icon2=$icon2_failed;
			$color="#8a8a8a";
		}
	
		$servername=$ligne["servername"];
		
		$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?delete-destination-js=yes&ID={$ligne["ID"]}&t=$t')");
	
		
		if($ligne["ipaddr"]=="127.0.0.1"){$delete="&nbsp;";}
	
		$isSuccess=$ligne["isSuccess"];
	
		
	
		$isSuccesstxt=unserialize(base64_decode($ligne["isSuccesstxt"]));
		if(count($isSuccesstxt)>1){
			//$isSuccessLink=$boot->trswitch("Loadjs('$page?js-source-tests={$ligne["ID"]}')");
			$isSuccessIcon=$isSuccessIcon_success;
			if($isSuccess==0){
				$isSuccessIcon=$isSuccessIcon_failed;
				$color="#C40000";
			}
				
			$jsedit=imgsimple($isSuccessIcon,null,"Loadjs('$page?js-source=yes&source-id={$ligne["ID"]}')");
			
		}
		
		if($cacheid>0){
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT keys_zone FROM nginx_caches WHERE ID='$cacheid'"));
			$jsedit="Loadjs('nginx.caches.php?js-cache=yes&ID={$cacheid}')";
			$CacheText="<br><i style='font-size:18px;font-weight:bold;color:#46a346'>Cache:&nbsp;<a href=\"javascript:blur();\" style='text-decoration:underline;color:#46a346' OnClick=\"javascript:$jsedit\">".utf8_encode($ligne2["keys_zone"])."</a></i>";
			$jsedit=null;
		}
		
		$jsedit="<a href=\"javascript:blur();\" 
							OnClick=\"javascript:Loadjs('nginx.peer.php?js=yes&ID={$ligne["ID"]}');\"
							style='font-size:24px;text-decoration:underline;font-weight:bold'>";
		
		$compile=imgsimple("apply-48.png",null,"Loadjs('nginx.destination.progress.php?cacheid={$ligne["ID"]}')");
		
		
		$data['rows'][] = array(
				'id' => $md,
				'cell' => array(
						
						"$jsedit$servername</a>",
						"$jsedit{$ligne["ipaddr"]}:{$ligne["port"]}</a>$CacheText","<center>$compile</center>",
						"<center>$delete</center>",
						
				)
		);		

	
	
	}
	echo json_encode($data);
}
	
	
	