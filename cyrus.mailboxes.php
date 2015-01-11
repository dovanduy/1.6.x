<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	include_once('ressources/class.system.network.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	$users=new usersMenus();
	if(!$users->AsMailBoxAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	if(isset($_GET["list-domains"])){list_domains();exit;}
	if(isset($_GET["items-list"])){items();exit;}
	if(isset($_POST["DeleteRealMailBox"])){DeleteRealMailBox();exit;}
	
page();


function page(){
	$tpl=new templates();
	$sock=new sockets();
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=400;
	$TB_WIDTH=874;
	$path=base64_decode($_GET["path"]);
	$md5path=md5($path);
	$mailboxes=$tpl->_ENGINE_parse_body("{mailboxes}");
	$domains=$tpl->javascript_parse_text("{domains}");
	$t=time();
	$new_entry=$tpl->_ENGINE_parse_body("{new_rule}");
	$files=$tpl->_ENGINE_parse_body("{files}");
	$deletemailbox_infos=$tpl->javascript_parse_text("{deletemailbox_infos}");
	$help=$tpl->_ENGINE_parse_body("{online_help}");
	$EnableVirtualDomainsInMailBoxes=$sock->GET_INFO("EnableVirtualDomainsInMailBoxes");
	if(!is_numeric($EnableVirtualDomainsInMailBoxes)){$EnableVirtualDomainsInMailBoxes=0;}
	if($EnableVirtualDomainsInMailBoxes==1){
		$swicthdomains="{name: '$domains', bclass: 'Search', onpress : domains$t},";
	}
	$title=$tpl->javascript_parse_text("{mailboxes}");
	$buttons="
	buttons : [
	{name: '$help', bclass: 'Help', onpress : ItemHelp$t},$swicthdomains
	],	";
	
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items-list=yes&t=$t&domain=',
	dataType: 'json',
	colModel : [	
		{display: '&nbsp;', name : 'action', width :31, sortable : false, align: 'center'},
		{display: '$mailboxes', name : 'mailbox', width :757, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'action', width :31, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$mailboxes', name : 'files'},
	],
	sortname: 'files',
	sortorder: 'asc',
	usepager: true,
	title: '<span id=title-$t style=font-size:22px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function ItemHelp$t(){
	s_PopUpFull('http://www.mail-appliance.org/index.php?cID=330','1024','900');
}

var x_DeleteRealMailBox$t= function (obj) {
	$('#flexRT$t').flexReload();	
}

function domains$t(){
	YahooWin2('550','$page?list-domains=yes&t=$t','$domains');

}

function DeleteRealMailBox$t(mbx,id){
	if(confirm('$deletemailbox_infos: '+mbx)){
		var XHR = new XHRConnection();
		XHR.appendData('DeleteRealMailBox',mbx);
		mem$t=id;
		XHR.sendAndLoad('$page', 'POST',x_DeleteRealMailBox$t);			
	}
	
}


</script>
";
	
	echo $html;
	
}

function items(){
	
	 $cyrus=new cyrus();
 	 $array=$cyrus->ListUsersBoxes($_POST["query"],$_GET["domain"]);
	 if(!is_array($array)){json_error_show("No mailbox");}
	 if(count($array)==0){json_error_show("No mailbox");}
	 $t=$_GET["t"];
	 $c=0;
	 
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($array);
	$data['rows'] = array();	 
	
	
	
	$search=null;
	if($_POST["query"]<>null){
		$search=string_to_regex($_POST["query"]);
	}
	 
	while (list ($mailbox_name, $ligne) = each ($array) ){
		$mailbox_name=trim($mailbox_name);
		if($mailbox_name==null){continue;}
		
	
		if($search<>null){if(!preg_match("#$search#", $ligne)){continue;}}
		if($_GET["domain"]<>null){
				$mailbox_name="$mailbox_name@{$_GET["domain"]}";
			}
				
				
		$delete=imgsimple("delete-24.png","","DeleteRealMailBox$t('$mailbox_name','".md5($ligne)."');");
		
				$c++;
				$data['rows'][] = array(
					'id' => md5($ligne),
					'cell' => array(
						"<span style='font-size:16px;color:$color'><img src='img/32-mailbox.png'></span>",
						"<span style='font-size:16px;color:$color'>$mailbox_name</a></span>",
						"<span style='font-size:16px;color:$color'>$delete</a></span>",
						)
					);				

			}
			 
	if($c==0){json_error_show("No mailbox");}
	$data['total'] = $c;
	echo json_encode($data);
}

function DeleteRealMailBox(){
	$mbx=$_POST["DeleteRealMailBox"];
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?DelMbx=$mbx");	
	
}

function list_domains(){
	$tpl=new templates();
	$page=CurrentPageName();
	$ldap=new clladp();
	$hash=$ldap->hash_get_local_domains();
	$t=$_GET["t"];
	$hash[null]="{select}";		
	$domainsf=Field_array_Hash($hash,"mailbox_domain_query$t",$mailbox_domain_query,"ChangeDomain$t('$page?MailBoxesDomainList=yes');",null,0,"padding:5px;font-size:18px");

	$form="
		<table style='width:99%' class=form>
		<tr>
			<td class=legend style='font-size:18px'>{domain}:</td>
			<td>$domainsf</td>
		</tr>
		</table>
		
		
		<script>
			function ChangeDomain$t(){
				var dom=document.getElementById('mailbox_domain_query$t').value;
				document.getElementById('title-$t').innerHTML=dom;
				$('#flexRT$t').flexOptions({url: '$page?items-list=yes&t=$t&domain='+dom}).flexReload(); 
			
			}
		
		</script>
		";
		echo $tpl->_ENGINE_parse_body($form);
	
}


