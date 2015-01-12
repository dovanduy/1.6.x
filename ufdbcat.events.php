<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["page"])){page();exit;}
if(isset($_GET["search"])){search();exit;}

page();







function page(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	
	
	$tpl=new templates();
	$t=time();
	$date=$tpl->_ENGINE_parse_body("{date}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$add=$tpl->_ENGINE_parse_body("{add}");
	$verify=$tpl->_ENGINE_parse_body("{analyze}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$squid_test_categories_explain=$tpl->_ENGINE_parse_body("{squid_test_categories_explain}");
	$title=$tpl->_ENGINE_parse_body("{APP_UFDBCAT} {events}");
	$import_catz_art_expl=$tpl->javascript_parse_text("{import_catz_art_expl}");
	$form=$tpl->_ENGINE_parse_body("
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{website}:</td>
		<td>". Field_text("WEBTESTS",null,"font-size:14px;padding:3px;border:2px solid #808080",
	null,null,null,false,"CheckSingleSite(event)")."</td>
	</tr>
	</table>
	");
	

	$buttons="
	buttons : [
	{name: '$category', bclass: 'Catz', onpress : ChangeCategory$t},
	
	],";

	$buttons=null;
	
	$html="
<table class='$t' style='display: none' id='$t' style='width:100%'></table>
<script>
var xsite='';
function flexigridStart$t(){
$('#$t').flexigrid({
	url: '$page?search=yes&category={$_GET["category"]}',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width : 142, sortable : false, align: 'left'},
		{display: 'PID', name : 'PID', width : 47, sortable : false, align: 'left'},		
		{display: '$events', name : 'description', width : 919, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$events', name : 'description'},
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 600,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500,1000]
	
	});   
}
setTimeout('flexigridStart$t()',800);
</script>";
echo $html;
	
}


function search(){
	
	$Mypage=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$rp=150;
	
	
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$search=string_to_flexregex();
	$sock->getFrameWork("squid.php?ufdbcat-logs=yes&search=".urlencode($search)."&rp=$rp");
	
	$array=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/ufdbcat.log"));
	$style="style='font-size:14px;'";
	
	
	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$data = array();
	$data['page'] = $page;
	
	$data['rows'] = array();
	
	
	krsort($array);
	$c=0;
	while (list ($num, $ligne) = each ($array) ){
		
  		if(!preg_match("#(.+?)\[([0-9]+)\]\s+(.*)#", $ligne,$re)){continue;}
  		$date=$re[1];
  		$pid=$re[2];
  		$event=$re[3];
  		
  	
  		$c++;
		$data['rows'][] = array(
			'id' => md5("{$ligne["zDate"]}{$ligne["description"]}"),
			'cell' => array(
				 "<span $style>$date</span>",
				"<span $style>$pid</span>",
					"<span $style>$event</span>",
					)
			);		
		
		
	}
	
	$data['total'] = $c;
echo json_encode($data);	

}