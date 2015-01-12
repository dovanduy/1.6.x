<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
if(!isset($_GET["t"])){$_GET["t"]=time();}
if(!is_numeric($_GET["t"])){$_GET["t"]=time();}

$user=new usersMenus();
if(($user->AsSystemAdministrator==false) OR ($user->AsSambaAdministrator==false)) {
	$tpl=new templates();
	$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	$text=replace_accents(html_entity_decode($text));
	echo "alert('$text');";
	exit;
}

if(isset($_GET["items"])){items();exit;}


table();

function table(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	$dansguardian2_members_groups_explain=$tpl->_ENGINE_parse_body("{dansguardian2_members_groups_explain}");
	$t=time();
	$certificates=$tpl->_ENGINE_parse_body("{certificates}");
	$Organization=$tpl->_ENGINE_parse_body("{organizationName}");
	$organizationalUnitName=$tpl->_ENGINE_parse_body("{organizationalUnitName}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$type=$tpl->javascript_parse_text("{type}");
	$new_certificate=$tpl->javascript_parse_text("{new_certificate}");
	$title=$tpl->_ENGINE_parse_body("{certificates_center}:{$_GET["CommonName"]}");
	$delete_certificate_ask=$tpl->javascript_parse_text("{delete_certificate_ask}");
	$buttons="
	buttons : [
	{name: '$new_certificate', bclass: 'Add', onpress : new_certificate$t},
	],";
	
	$html="
	<div style='margin-left:0px'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>
	<script>
	var rowid$t='';
	function LoadTable$t(){
	$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t&CommonName={$_GET["CommonName"]}',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'del', width :60, sortable : false, align: 'center'},
	{display: '$certificates', name : 'certificates', width : 543, sortable : false, align: 'left'},
	{display: '$type', name : 'certificates', width : 266, sortable : false, align: 'left'},
	
	],
	
	sortname: 'CommonName',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 500,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
}
LoadTable$t();
</script>
";

echo $html;
	
	
}

function items(){
	//1.4.010916
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$CommonName=$_GET["CommonName"];
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();

	$title=$tpl->javascript_parse_text("{privkey}");
	$jsEdit="Loadjs('certificates.center.srca.php?CommonName=$CommonName&js=yes');";
	$urljs="<a href=\"javascript:blur();\" OnClick=\"$jsEdit\" style='font-size:22px;text-decoration:underline'>";

	$data['rows'][] = array(
		'id' => "srca",
		'cell' => array(
		"<img src='img/certificate-32.png'>",
		"<span style='font-size:22px;'>$urljs{$title}</a></span>",
		"<span style='font-size:22px;'>PRIVATE KEY</a></span>"
		)
		);
	
	$title=$tpl->javascript_parse_text("{RSA_PRIVATE_KEY}");
	$jsEdit="Loadjs('certificates.center.privkey.php?CommonName=$CommonName&js=yes');";
	$urljs="<a href=\"javascript:blur();\" OnClick=\"$jsEdit\" style='font-size:22px;text-decoration:underline'>";
	
	$data['rows'][] = array(
			'id' => "privkey",
			'cell' => array(
					"<img src='img/certificate-32.png'>",
					"<span style='font-size:22px;'>$urljs{$title}</a></span>",
					"<span style='font-size:22px;'>RSA PRIVATE KEY</a></span>"
			)
	);	
	
	
	$title=$tpl->javascript_parse_text("{certificate}");
	$jsEdit="Loadjs('certificates.center.crt.php?CommonName=$CommonName&js=yes');";
	$urljs="<a href=\"javascript:blur();\" OnClick=\"$jsEdit\" style='font-size:22px;text-decoration:underline'>";
	
	$data['rows'][] = array(
			'id' => "certificate",
			'cell' => array(
					"<img src='img/certificate-32.png'>",
					"<span style='font-size:22px;'>$urljs{$title}</a></span>",
					"<span style='font-size:22px;'>CERTIFICATE</a></span>"
			)
	);
	
	
	$title=$tpl->javascript_parse_text("{CSR}");
	$jsEdit="Loadjs('certificates.center.csr.php?CommonName=$CommonName&js=yes');";
	$urljs="<a href=\"javascript:blur();\" OnClick=\"$jsEdit\" style='font-size:22px;text-decoration:underline'>";
	
	$data['rows'][] = array(
			'id' => "CSR",
			'cell' => array(
					"<img src='img/certificate-32.png'>",
					"<span style='font-size:22px;'>$urljs{$title}</a></span>",
					"<span style='font-size:22px;'>CERTIFICATE REQUEST</a></span>"
							
			)
	);

	

	


	
	
	$data['total']=count($data['rows']);
		
	echo json_encode($data);

}