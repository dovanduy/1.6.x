<?php

	if(isset($_GET["verbose"])){
		$GLOBALS["VERBOSE"]=true;
		$GLOBALS["DEBUG_MEM"]=true;
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
		ini_set('error_prepend_string',null);
		ini_set('error_append_string',null);
	}

	if($GLOBALS["VERBOSE"]){echo "<H1>DEBUG</H1>";}

    include_once(dirname(__FILE__).'/ressources/class.templates.inc');
	include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
	include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__).'/ressources/class.artica.inc');
	include_once(dirname(__FILE__).'/ressources/class.rtmm.tools.inc');
	include_once(dirname(__FILE__).'/ressources/class.squid.inc');
	include_once(dirname(__FILE__).'/ressources/class.dansguardian.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	//header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["search"])){search();exit;}
	
js();
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	header("content-type: application/x-javascript");
	$categories=$tpl->javascript_parse_text("{categories}");
	echo "YahooWin5('700','$page?popup=yes','$categories')";
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$description=$tpl->_ENGINE_parse_body("{description}");
	$select=$tpl->javascript_parse_text("{select}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$empty=$tpl->javascript_parse_text("{empty}");
	$t=time();
	$tablesize=629;
	$descriptionsize=465;
	$bts=array();

	
	$CATZ_ARRAY=unserialize(@file_get_contents("/home/artica/categories_databases/CATZ_ARRAY"));
	unset($CATZ_ARRAY["TIME"]);
	$c=0;
	while (list ($tablename, $items) = each ($CATZ_ARRAY) ){
		$items=intval($items);
		$c=$c+$items;
	}
	$c=FormatNumber($c);
	
	$title=$tpl->_ENGINE_parse_body("{categories}");
	$description=$tpl->javascript_parse_text("{items}");

	$html="
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
	<script>
	$(document).ready(function(){
	$('#$t').flexigrid({
	url: '$page?search=yes',
	dataType: 'json',
	colModel : [
	{display: '$title', name : 'zDate', width : 414, sortable : false, align: 'left'},
	{display: '$description', name : 'description', width : 198, sortable : false, align: 'right'},
	],
	searchitems : [
	{display: '$title', name : 'description'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$title - $c $description',
	useRp: true,
	rp: 200,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true

});
});
</script>

";

echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql();
	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexregex();
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$CATZ_ARRAY=unserialize(@file_get_contents("/home/artica/categories_databases/CATZ_ARRAY"));
	unset($CATZ_ARRAY["TIME"]);
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($CATZ_ARRAY);
	$data['rows'] = array();
	
	$catz=new mysql_catz();
	$TransArray=$catz->TransArray();
	$c=0;
	while (list ($tablename, $items) = each ($CATZ_ARRAY) ){
		
		if(isset($TransArray[$tablename])){$tablename=$TransArray[$tablename];}
		
		if($searchstring<>null){
			if(!preg_match("#$searchstring#", $tablename)){
				continue;
			}
		}
		$c++;
		$items=FormatNumber($items);
			$data['rows'][] = array(
				'id' => md5($tablename),
				'cell' => array(
						"<strong style='font-size:18px;color:$color'>{$tablename}</strong>",
						"<div style='font-size:18px;font-weight:normal;color:$color'>$items</div>",
				)
		);
	}
	
	$data['total'] = $c;
	echo json_encode($data);
	
	}
		
	function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
		$tmp1 = round((float) $number, $decimals);
		while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
			$tmp1 = $tmp2;
		return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
	}