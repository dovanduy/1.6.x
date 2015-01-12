<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.system.network.inc');
$GLOBALS["LOGFILE"]="/usr/share/artica-postfix/ressources/logs/web/exec.virtuals-ip.php.html";

$user=new usersMenus();
if($user->AsSquidAdministrator==false){
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();exit();
}

if(isset($_GET["logs"])){logs();exit;}
if(isset($_GET["zoom-js"])){zoom_js();exit;}
if(isset($_GET["zoom-popup"])){zoom_popup();exit;}
table_main();

function zoom_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$uri=urlencode($_GET["zoom-js"]);
	$MAIN=unserialize(base64_decode($_GET["zoom-js"]));
	$title="Zoom";
	if(is_numeric($MAIN["ID"])){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT eth,rulename FROM iptables_main WHERE ID='{$MAIN["ID"]}'","artica_backup"));
		$title="{$ligne["eth"]}::".$tpl->javascript_parse_text($ligne["rulename"]);
	}
	
	echo "YahooWin3('750','$page?zoom-popup=$uri','$title')";
	
}

function zoom_popup(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$MAIN=unserialize(base64_decode($_GET["zoom-popup"]));
	if(is_numeric($MAIN["ID"])){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT eth,rulename FROM iptables_main WHERE ID='{$MAIN["ID"]}'","artica_backup"));
		$title="{$ligne["eth"]}::".$tpl->javascript_parse_text($ligne["rulename"]);
	}
	
	$html="<div style='font-size:26px'>$title</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	";
	
	unset($MAIN['MAC']);
	unset($MAIN['ID']);
	
	while (list ($num, $line) = each ($MAIN)){
		$tr[]="<tr>
			<td class=legend style='font-size:18px;width=1%' nowrap>$num</td>
			<td style='font-size:18px'>$line</td>
			</tr>";
	}
	$html=$html.@implode("", $tr)."</table></div>";
	echo $tpl->_ENGINE_parse_body($html);
}


function table_main(){

	if($_GET["table"]=="STATUS"){iptables_status();exit;}
$button="	buttons : [
	{name: '$new', bclass: 'add', onpress : NewRule$t},
	{name: '$apply', bclass: 'Apply', onpress : Apply$t},

	],";
	$page=CurrentPageName();
	$tpl=new templates();
	$eth=$_GET["eth"];
	$ethC=new system_nic($eth);
	$iptable=$_GET["table"];
	$title=$tpl->javascript_parse_text("$eth &laquo;$ethC->NICNAME&raquo;");
	$new=$tpl->javascript_parse_text("{new_rule}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$type=$tpl->javascript_parse_text("{type}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$date=$tpl->javascript_parse_text("{zDate}");
	$from=$tpl->javascript_parse_text("{from}");
	$interface=$tpl->javascript_parse_text("{interface}");
	$to=$tpl->javascript_parse_text("{to}");
	$PROTO=$tpl->javascript_parse_text("{proto}");
	$action=$tpl->javascript_parse_text("{action}");
	$t=time();
	$button=null;
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	<script>

	function LoadTable$t(){
	$('#flexRT$t').flexigrid({
	url: '$page?logs=yes&eth=$eth',
	dataType: 'json',
	colModel : [
	{display: '$date', name : 'date', width :152, sortable : false, align: 'left'},
	{display: '$action', name : 'type1', width : 55, sortable : true, align: 'center'},
	{display: 'TABLE', name : 'TABLE', width : 55, sortable : true, align: 'center'},
	{display: '$rule', name : 'rule', width : 55, sortable : true, align: 'center'},
	{display: '$from $interface', name : 'IN', width : 58, sortable : true, align: 'left'},
	{display: '$from', name : 'MACIN', width : 190, sortable : true, align: 'left'},
	{display: '$to $interface', name : 'OUT', width : 58, sortable : true, align: 'left'},
	{display: '$to', name : 'OUTIP', width : 190, sortable : true, align: 'left'},
	{display: '$PROTO', name : 'proto', width : 70, sortable : true, align: 'center'},


	],

	searchitems : [
	{display: '$rulename', name : 'rulename'},
	],
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [30, 50,100,200,500]

});
}
var xRuleGroupUpDown$t= function (obj) {
var res=obj.responseText;
if(res.length>3){alert(res);return;}
$('#flexRT$t').flexReload();
ExecuteByClassName('SearchFunction');
}

function RuleGroupUpDown$t(ID,direction){
var XHR = new XHRConnection();
XHR.appendData('rule-order', ID);
XHR.appendData('direction', direction);
XHR.appendData('eth', '$eth');
XHR.appendData('table', '$iptable');
XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}

function DeleteRule$t(ID){
if(!confirm('$delete '+ID+' ?')){return;}
var XHR = new XHRConnection();
XHR.appendData('rule-delete', ID);
XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}

function Apply$t(){
Loadjs('firewall.restart.php');
}

function ChangEnabled$t(ID){
var XHR = new XHRConnection();
XHR.appendData('rule-enable', ID);
XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}

function NewRule$t() {
Loadjs('$page?ruleid=0&eth=$eth&t=$t&table=$iptable',true);
}
LoadTable$t();
</script>
";
	echo $html;

}

function logs(){
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$logfile="/usr/share/artica-postfix/ressources/logs/web/iptables.log";
	$searchstring=urlencode(string_to_flexregex());
	if(!isset($_POST["rp"])){$_POST["rp"]=100;}
	$sock->getFrameWork("network.php?iptables-events=yes&eth={$_GET["eth"]}&rp={$_POST["rp"]}&search=$searchstring");
	$tpl=new templates();
	$results=explode("\n",@file_get_contents($logfile));
	@unlink($logfile);
	
	if(count($results)==0){json_error_show("no data");}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($results);
	$data['rows'] = array();
	$q=new mysql_squid_builder();
	krsort($results);
	$c=0;
	while (list ($num, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		$MACIN="-";
		$MACOUT="-";
		$SRC="-";
		$color="black";
		if($GLOBALS["VERBOSE"]){echo "$line<hr>";}
		
		if(preg_match("#(.+?)\s+([0-9]+)\s+([0-9\:]+)\s+.*?\](.*)#",$line,$re)){
			$date=strtotime("{$re[1]} {$re[2]} {$re[3]}");
			$datetext=time_to_date($date,true);
			if($GLOBALS["VERBOSE"]){print_r($re);}
			$line=trim($re[4]);
		}
		if($GLOBALS["VERBOSE"]){echo "$line<hr>";}
		//  ARTICA-FW-ID-8:IN= OUT=br1 SRC=192.168.1.1 DST=192.168.1.135 
		//LEN=52 TOS=0x00 PREC=0x00 TTL=64 ID=0 DF PROTO=TCP SPT=8080 DPT=51484 WINDOW=14600 
		//RES=0x00 ACK SYN URGP=0", $subject))
		
		$lineZ=explode(" ",$line);
		
		while (list ($a, $b) = each ($lineZ)){
			if(!preg_match("#(.+)=(.*)#", $b,$re)){continue;}
			$MAIN[$re[1]]=$re[2];
			
		}
		

		$IN=$MAIN["IN"];
		$OUT=$MAIN["OUT"];
		$PHYSIN=$MAIN["PHYSIN"];
		$PHYSOUT=$MAIN["PHYSOUT"];
		$MAC=$MAIN["MAC"];
		$SRC=$MAIN["SRC"];
		$DST=$MAIN["DST"];
		$SPT=$MAIN["SPT"];
		$DPT=$MAIN["DPT"];
		$PROTO=$MAIN["PROTO"];
		$TABLE=$MAIN["TABLE"];
		$ACTION=$MAIN["ACTION"];
		$AID=$MAIN["AID"];
		if($AID<>null){
			$AID_TABLE=explode("/",$AID);
			if($GLOBALS["VERBOSE"]){echo "<hr>".print_r($AID_TABLE)."<hr>";}
			$RULE_ID=$AID_TABLE[0];
			if(preg_match("#([0-9]+)#", $RULE_ID,$rz)){$RULE_ID=$rz[1];}
			$ACTION=$AID_TABLE[2];
			$ACTION_SOURCE=$ACTION;
			$TABLE=$AID_TABLE[1];
			$MAIN["ID"]=$RULE_ID;
			$MAIN["ACTION"]=$ACTION_SOURCE;
			$MAIN["TABLE"]=$TABLE;
			
		}
		
		if($TABLE==null){$TABLE="none";}
		if($ACTION==null){$ACTION="none";}
		$TABLE=$tpl->javascript_parse_text("{{$TABLE}}");
		$ACTION=$tpl->javascript_parse_text("{{$ACTION}}");
		
		//Aug 13 15:30:49 router kernel: [1918085.984702] ARTICA-FW-ID-1:IN=br1 OUT= PHYSIN=eth0 MAC=01:00:5e:00:00:01:00:17:33:f6:95:f4:08:00 SRC=172.16.255.254 DST=224.0.0.1 LEN=28 TOS=0x10 PREC=0x80 TTL=1 ID=37754 PROTO=2
		if($IN==null){$IN="-";}
		if($OUT==null){$OUT="-";}
		if($PHYSIN==null){$PHYSIN="-";}
		if($MACIN==null){$MACIN="-";}
		if($MACOUT==null){$MACOUT="-";}
		if($SRC==null){$SRC="-";}
		if($PHYSOUT==null){$PHYSOUT="-";}
		if($SPT==null){$SPT="-";}
		if($DPT==null){$DPT="-";}
		
		if(preg_match("#(.*?):(.*?):(.*?):(.*?):(.*?):(.*?):(.*?):(.*?):(.*?):(.*?):(.*?):(.*?):(.*?):(.*?)#", $MAC,$ri)){
			$MACIN="{$ri[1]}:{$ri[2]}:{$ri[3]}:{$ri[4]}:{$ri[5]}:{$ri[6]}";
		   $MACOUT="{$ri[7]}:{$ri[8]}:{$ri[9]}:{$ri[10]}:{$ri[11]}:{$ri[12]}";
			
		}
		$MAIN["MAC - IN"]=$MACIN;
		$MAIN["MAC - OUT"]=$MACOUT;
		
		
		$ICONS["LOG"]="22-logs.png";
		$ICONS["REJECT"]="22-red.png";
		$ICONS["DROP"]="22-red.png";
		$ICONS["RETURN"]="ok22.png";
		$ICONS["ACCEPT"]="ok22.png";
		$ICONS["DROP"]="22-red.png";
		$ICONS["MARK"]="22-red.png";
		$ICONS["FORWARD"]="forwd_22.png";
		
		$image=$ICONS[$ACTION_SOURCE];
		if(is_numeric($PROTO)){$color="#D11C2F";$image="22-warn.png";}
		
		
		$uri=urlencode(base64_encode(serialize($MAIN)));
		$javascript="href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?zoom-js=$uri');\" style='font-size:12px;font-weight:normal;color:$color;text-decoration:underline'";
		
			
		$mkey=md5($line);
		$c++;
		$data['rows'][] = array(
				'id' => "$mkey",
				'cell' => array(
						"<span style='font-size:12px;font-weight:normal;color:$color'>$datetext</span>",
						"<center style='font-size:12px;font-weight:normal;color:$color'><img src=img/$image></center>",
						"<a $javascript>$TABLE</a>",
						"<span style='font-size:12px;font-weight:normal;color:$color'>$RULE_ID</span>",
						"<a $javascript>$IN/$PHYSIN</a>",
						"<span style='font-size:12px;font-weight:normal;color:$color'>$SRC:$SPT/$MACIN</span>",
						"<span style='font-size:12px;font-weight:normal;color:$color'>$OUT/$PHYSOUT</span>",
						"<a $javascript>$DST:$DPT/$MACOUT</span>",
						"<span style='font-size:12px;font-weight:normal;color:$color'>$PROTO</span>",
					)
		);
		
	}
	if(count($c)==0){json_error_show("no data");}
	echo json_encode($data);
	
}
function time_to_date($xtime,$time=false){
	if(!class_exists("templates")){return;}
	$tpl=new templates();
	$dateT=date("{l} {F} d",$xtime);
	if($time){$dateT=date("{l} {F} d H:i:s",$xtime);}
	if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$xtime);if($time){$dateT=date("{l} d {F} H:i:s",$xtime);}}
	return $tpl->_ENGINE_parse_body($dateT);

}