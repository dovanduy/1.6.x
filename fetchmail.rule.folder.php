<?php

include_once('ressources/class.templates.inc');
include_once('ressources/class.fetchmail.inc');	
include_once('ressources/class.imap-read.inc');
$user=new usersMenus();
$tpl=new templates();


if(!GetRights_aliases()){
	if($_REQUEST["uid"]<>$_SESSION["uid"]){die("No privileges");}
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["browse-imap"])){browse_imap();exit;}
if(isset($_POST["folderenc"])){savefolder();exit;}
js();


function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$fetch=new Fetchmail_settings();
	$rule=$fetch->LoadRule($_REQUEST["ruldeid"]);
	if($rule["proto"]<>"imap"){
		echo "alert('".$tpl->javascript_parse_text("{error_only_imap_supported}")."')";
		return;
		
	}
	$html="YahooWinBrowse('650','$page?popup=yes&ruldeid={$_GET["ruldeid"]}&uid={$_GET["uid"]}','{$rule["poll"]}')";
	echo $html;
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$folders=$tpl->_ENGINE_parse_body("{folders}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$uri=$tpl->_ENGINE_parse_body("{url}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$user=$tpl->_ENGINE_parse_body("{user}");
	$title=$tpl->_ENGINE_parse_body("{today}: {realtime_requests} ".date("H")."h");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$delete_rule=$tpl->javascript_parse_text("{delete_rule}");
	$refresh=$tpl->_ENGINE_parse_body("{refresh}");
	$t=time();	

	

	$fetch=new Fetchmail_settings();
	$rule=$fetch->LoadRule($_GET["ruldeid"]);	
	$port=143;
	$tls="notls";
	if($rule["ssl"]==1){$port=993;$tls="ssl/novalidate-cert";}

	$title="{$rule["poll"]}:$port/$tls";
	

	
	
	$import=$tpl->_ENGINE_parse_body("{import}");
	
	
	$buttons="
	buttons : [
	{name: '$new_rule', bclass: 'Add', onpress : add_fetchmail_rules$t},
	{name: '$import', bclass: 'Copy', onpress : ImportBulk$t},
	{name: '$refresh', bclass: 'Reload', onpress : Reload$t},
		],	";		
	$buttons=null;
	
	$html="
	<div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>
	
<script>
var fetchid=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?browse-imap=yes&ruldeid={$_GET["ruldeid"]}&uid={$_GET["uid"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$folders', name : 'uid', width :542, sortable : false, align: 'left'},
		{display: '$enabled', name : 'enabled', width :31, sortable : false, align: 'left'},
		],$buttons
	
	searchitems : [
		{display: '$folders', name : 'folders'},
		],
	sortname: 'uid',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 620,
	height: 408,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

   

  
  function Reload$t(){
  	$('#flexRT$t').flexReload();
  }
  
	var x_EnableImapf= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
	}   
  
  function EnableImapf(md,folderbased){
  	 var XHR = new XHRConnection();
  	 if(document.getElementById('ck_'+md).checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
  	 XHR.appendData('ruldeid','{$_GET["ruldeid"]}');
  	 XHR.appendData('uid','{$_GET["uid"]}');
  	 XHR.appendData('folderenc',folderbased);
  	 XHR.appendData('md5',md);
  	 XHR.setLockOff();
  	 XHR.sendAndLoad('$page', 'POST',x_EnableImapf);  
  }

</script>";
	
	echo $html;
	return;
}	
	

function browse_imap(){
	$fetch=new Fetchmail_settings();
	$rule=$fetch->LoadRule($_GET["ruldeid"]);	
	$port=143;
	$tls="notls";
	if($rule["ssl"]==1){$port=993;$tls="ssl/novalidate-cert";}
	if($rule["poll"]==null){
		json_error_show("Fatal, No IMAP server defined for rule id:{$_GET["ruldeid"]}");
		return;		
	}
	//$host,$username,$password,$folder='INBOX',$port=143,$tls='notls
	$imap = new ImapRead($rule["poll"],$rule["user"],$rule["pass"],"INBOX",$port,$tls);
	if($imap->is_connected==0){
		json_error_show("could not connect to {$rule["poll"]}:$port<div>$imap->imap_error</div>");
		return;
	}
	
	
	
	$array=$imap->returnMailboxListArr();
	
	$sql="SELECT folders FROM fetchmail_rules WHERE ID='{$_GET["ruldeid"]}'";
	$q=new mysql();
	$arrayF=array();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	$arrayF=unserialize(base64_decode($ligne["folders"]));	
	
	$page=1;
	$data = array();
	$data['page'] = $page;
	$data['total'] = count($array);
	$data['rows'] = array();	
	$search=null;
	if($_POST["query"]<>null){
		$search=string_to_regex($_POST["query"]);
	}
	
	while (list ($num, $ligne) = each ($array) ){
	
		if(preg_match("#^\{.*?\}(.+)#", $ligne,$re)){$folder=$re[1];}
		if(preg_match("#^.*?INBOX\/(.+)#", $ligne,$re)){$folder=$re[1];}
		if($folder=="INBOX"){continue;}
		if($search<>null){if(!preg_match("#$search#i", $folder)){continue;}}
		$c++;
		$md=md5($folder);
		$base=base64_encode($folder);
		$enabledv=0;
		if(isset($arrayF[$md])){$enabledv=1;}
		$enable=Field_checkbox("ck_$md", 1,$enabledv,"EnableImapf('$md','$base')");
		$span="<span style='font-size:16px;font-weight:bolder'>";
		
		$data['rows'][] = array(
		'id' => $md,
		'cell' => array(
			$span.$href.$folder."</a></span>",
			$enable
			)
		);
		
	}
	$data['total'] = $c;
	echo json_encode($data);	
	
}
function savefolder(){
		$sql="SELECT folders FROM fetchmail_rules WHERE ID='{$_POST["ruldeid"]}'";
		$q=new mysql();
		$array=array();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
		$array=unserialize(base64_decode($ligne["folders"]));
		
		if($_POST["enabled"]==0){
			unset($array[$_POST["md5"]]);
		}else{
			$array[$_POST["md5"]]=$_POST["folderenc"];
		}
		$newarray=base64_encode(serialize($array));
		$sql="UPDATE fetchmail_rules SET folders='$newarray' WHERE ID='{$_POST["ruldeid"]}'";
		$q->QUERY_SQL($sql,"artica_backup");
		
		if(!$q->ok){
			if(preg_match("#Unknown column#", $q->mysql_error)){
				$q->BuildTables();
				$q->QUERY_SQL($sql,"artica_backup");
			}
		}
		
		if(!$q->ok){echo $q->mysql_error;return;}
		$sock=new sockets();
		$sock->getFrameWork("fetchmail.php?reload-fetchmail=yes");
}


