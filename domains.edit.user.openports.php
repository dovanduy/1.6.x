<?php
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
session_start ();
include_once ('ressources/class.templates.inc');
include_once ('ressources/class.ldap.inc');
include_once ('ressources/class.users.menus.inc');
include_once ('ressources/class.artica.inc');
include_once ('ressources/class.pure-ftpd.inc');
include_once ('ressources/class.user.inc');
include_once ('ressources/class.computers.inc');
include_once ('ressources/class.ini.inc');
include_once ('ressources/class.ocs.inc');
include_once (dirname ( __FILE__ ) . "/ressources/class.cyrus.inc");

if ((!isset ($_GET["uid"] )) && (isset($_POST["uid"]))){$_GET["uid"]=$_POST["uid"];}
if ((isset ($_GET["uid"] )) && (! isset ($_GET["userid"] ))) {$_GET["userid"] = $_GET["uid"];}

//permissions	
$usersprivs = new usersMenus ( );
$change_aliases = GetRights_aliases();

if(!$change_aliases){echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");}
if(isset($_GET["search"])){list_ports();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$ComputersAllowNmap=$sock->GET_INFO("ComputersAllowNmap");
	if($ComputersAllowNmap==null){$ComputersAllowNmap=1;}
	
	$cmp=new computers($_GET["userid"]);
	
	$t=time();
	$port=$tpl->_ENGINE_parse_body("{port}");
	$lastest_scan=$tpl->_ENGINE_parse_body("{latest_scan}");
	$service=$tpl->_ENGINE_parse_body("{service}");
	$directories=$tpl->_ENGINE_parse_body("{directories}");
	$depth=$tpl->_ENGINE_parse_body("depth");
	$execute=$tpl->_ENGINE_parse_body("{execute}");
	$all=$tpl->_ENGINE_parse_body("{all}");
	$scan_it=$tpl->javascript_parse_text("{scan_it}");
	$computers=$cmp->DisplayName;
	//$button = Paragraphe ( "64-samba-find.png", "$computer->DisplayName", "{scan_it}", 
			//"javascript:Loadjs('nmap.progress.php?MAC=$computer->ComputerMacAddress&ipaddr=$computer->ComputerIP')", "scan_your_network", 210 );
		
	if($ComputersAllowNmap==1){
		$buttons="
	buttons : [
	{name: '$scan_it', bclass: 'Add', onpress : Scanit$t},
	],	";
	}

	$uri="$page?search=yes&MAC={$cmp->ComputerMacAddress}";

	$html="
	<table class='FICHE_COMPUTER_OPEN_PORTS' style='display: none' id='FICHE_COMPUTER_OPEN_PORTS' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
	$('#FICHE_COMPUTER_OPEN_PORTS').flexigrid({
	url: '$uri',
	dataType: 'json',
	colModel : [
	{display: '$port', name : 'port', width :486, sortable : true, align: 'left'},
	{display: '$service', name : 'service', width :311, sortable : true, align: 'left'},
	],
	$buttons

	searchitems : [
	{display: '$port', name : 'port'},
	{display: '$service', name : 'service'},
	
	],
	sortname: 'port',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$computers</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	});
});

function Scanit$t(){
	Loadjs('nmap.progress.php?MAC=$cmp->ComputerMacAddress&ipaddr=$cmp->ComputerIP')
}

</script>
";

echo $html;

}

function list_ports(){


	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];

	$search='%';
	$table="open_ports";
	$database='ocsweb';
	$page=1;
	$FORCE_FILTER="";
	
	$table="(SELECT * FROM open_ports WHERE mac='{$_GET["MAC"]}') as t";

	if(!$q->TABLE_EXISTS("open_ports", $database)){json_error_show("open_ports, No such table...",0);}
	if($q->COUNT_ROWS("open_ports",'ocsweb')==0){json_error_show("No data...",0);}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();


	if($searchstring<>null){

		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"ocsweb"));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show($q->mysql_error,1);}

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,'ocsweb'));
		if(!$q->ok){json_error_show($q->mysql_error,1);}
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,'ocsweb');
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){
		json_error_show("no data<hr>$sql",0);
	}
	$sock=new sockets();
	$cmp=new computers();

	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$id=md5(serialize($ligne));
		$data['rows'][] = array(
				
				'id' => $id,
				'cell' => array(
						"<span style='font-size:16px;color:$color;'><strong>{$ligne["port"]}</strong>",
						"<span style='font-size:16px;color:$color;'>{$ligne["service"]}</a></span>",

							
				)
		);
	}


	echo json_encode($data);

}

