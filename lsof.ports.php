<?php
session_start ();
include_once ('ressources/class.templates.inc');
include_once ('ressources/class.ldap.inc');
include_once ('ressources/class.users.menus.inc');


	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["search"])){search();exit;}
	if(isset($_POST["lsofkill"])){lsofkill();exit;}
js();


function js(){
	$page=CurrentPageName();
	$html="
	
	
function SyslogLoadpage(){
	$('#BodyContent').load('$page?popup=yes');
	}
	
	
	SyslogLoadpage();";
	
	echo $html;
	
	
}
//COMMAND    PID     USER   FD   TYPE   DEVICE SIZE/OFF NODE NAME
//nscd      2726        0   14u  IPv4 15201465      0t0  TCP 127.0.0.1:41797->127.0.0.1:389 (ESTABLISHED)

function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$service=$tpl->_ENGINE_parse_body("{servicew}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$context=$tpl->_ENGINE_parse_body("{context}");	
	$events=$tpl->_ENGINE_parse_body("{events}");	
	$search=$tpl->_ENGINE_parse_body("{search}");	
	$service=$tpl->_ENGINE_parse_body("{servicew}");
		$TB_WIDTH=872;
		$TB2_WIDTH=610;
	
	$t=time();
	
	$buttons="
	buttons : [
	{name: '$empty', bclass: 'Delz', onpress : EmptyEvents},
	
		],	";
	
	$buttons=null;
	
	$html="
	<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
<script>
lsofmem='';
$(document).ready(function(){
$('#events-table-$t').flexigrid({
	url: '$page?search=yes&prepend={$_GET["prepend"]}',
	dataType: 'json',
	colModel : [
		{display: '$service', name : 'service', width :93, sortable : true, align: 'left'},
		{display: 'PID', name : 'pid', width :40, sortable : false, align: 'left'},
		{display: 'PROTO', name : 'prot', width :40, sortable : false, align: 'left'},
		{display: 'ports', name : 'ports', width : 578, sortable : false, align: 'left'},
		{display: 'kill', name : 'kill', width :40, sortable : false, align: 'left'},
	],
	$buttons

	searchitems : [
		{display: '$search', name : 'search'},
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: 600,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});


		var X_LsofKill=function (obj) {
			var results=obj.responseText;
			if(results.length>5){alert(results);return;}	
			$('#row'+lsofmem).remove();
		}		

	function LsofKill(pid,service,md){
			if(confirm('Kill: '+service+'['+pid+'] ?')){
			lsofmem=md;
			var XHR = new XHRConnection();
			XHR.appendData('lsofkill',pid);
			XHR.sendAndLoad('$page', 'POST',X_LsofKill);
			}
		}	
</script>";
	
	echo $html;
	return ;

}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	
	
	
	
	$table="events";
	$page=1;
	$ORDER="ORDER BY zDate DESC";
	
	$total=0;
	
	
	$pattern=base64_encode($_GET["search"]);
	$sock=new sockets();
	
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	if($_POST["qtype"]=="service"){
		if($_POST["query"]<>null){
			$_GET["prefix"]=$_POST["query"];
			$_POST["query"]=null;
		}
	}
	
	if($_POST["qtype"]=="service"){
		if($_POST["query"]<>null){
			$_GET["prefix"]=$_POST["query"];
			$_POST["query"]=null;
		}
	}	
	


	if($_POST["query"]<>null){

		$search=base64_encode($_POST["query"]);
		$array=unserialize(base64_decode($sock->getFrameWork("services.php?port-list=$search&prepend={$_GET["prepend"]}&rp={$_POST["rp"]}&prefix={$_GET["prefix"]}")));	
		$total = count($array);
		
	}else{
		$array=unserialize(base64_decode($sock->getFrameWork("services.php?port-list=&prepend={$_GET["prepend"]}&rp={$_POST["rp"]}&prefix={$_GET["prefix"]}")));	
		$total = count($array);
	}
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if($_POST["sortname"]<>null){
		if($_POST["sortorder"]=="desc"){krsort($array);}else{ksort($array);}
	}
	
	$c=0;
	while (list ($key, $line) = each ($array) ){
		if(trim($line)==null){continue;}
			$date=null;
			$host=null;
			$service=null;
			$pid=null;
			$color="black";
			//if(preg_match("#(ERROR|WARN|FATAL|UNABLE)#i", $line)){$color="#D61010";}
				
			$style="<span style='color:$color;font-size:14px'>";
			$styleoff="</span>";
			
			if(preg_match("#^[A-Z]+\s+[A-Z]+\s+[A-Z]+\s+#",$line)){continue;}
			$md=md5($line);
			
		if(preg_match("#(.*?)\s+([0-9]+)\s+(.*?)\s+(.*?)\s+(.*?)\s+([0-9]+)\s+.*?\s+([A-Z]+)\s+(.*)#",$line,$re)){
			$service=$re[1];
			$pid=$re[2];
			$proto=$re[7];
			$line=$re[8];
			
			if(is_numeric($pid)){
				$delete="<a href=\"javascript:blur();\" OnClick=\"javascript:LsofKill('$pid','$service','$md')\"><img src='img/delete-24.png'></a>";
				
			}
			
			$c++;$data['rows'][] = array('id' => $md,'cell' => array("$style$service$styleoff","$style$pid$styleoff","$style$proto$styleoff","$style$line$styleoff",$delete ));
			continue;	
		}

		
		$c++;$data['rows'][] = array('id' => $md,'cell' => array("$style$date$styleoff","$style$service$styleoff","$style$pid$styleoff","$style$line$styleoff","&nbsp;" ));
		

	}
	$data['total'] = $c;
	
echo json_encode($data);		

}

function lsofkill(){
	$pid=$_POST["lsofkill"];
	if(!is_numeric($pid)){return;}
	if($pid<10){return;}
	
	$sock=new sockets();
	$sock->getFrameWork("services.php?kill-pid=$pid");
	
}

function search_old(){
	
	
	if(!is_array($array)){return null;}
	
	$html="<table class=TableView>";
	
	while (list ($key, $line) = each ($array) ){
		if($line==null){continue;}
		if($tr=="class=oddrow"){$tr=null;}else{$tr="class=oddrow";}
		
			$html=$html."
			<tr $tr>
			<td><code>$line</cod>
			</tr>
		
		";
		
	}
	
	
	$html=$html."</table>";

	echo $html;
}




?>