<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.rtmm.tools.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["list-table"])){list_table();exit;}
	
	if(isset($_GET["zoom-js"])){zoom_js();exit;}
	if(isset($_GET["zoom-tab"])){zoom_tab();exit;}
	if(isset($_GET["zoom-status"])){zoom_status();exit;}
	if(isset($_GET["zoom-transactions"])){zoom_transaction();exit;}
	if(isset($_GET["transactions-history"])){zoom_transaction_check();exit;}
	if(isset($_GET["transactions-table"])){zoom_transaction_table();exit;}
	if(isset($_GET["transaction-events"])){zoom_transaction_events();exit;}
	if(isset($_POST["rescan-id"])){zoom_transaction_kill();exit;}
	
	
page();
function page(){
	
	$hostname=$_GET["hostname"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$tt=$_GET["t"];
	$t=time();
	$q=new mysql();
	$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$items=$tpl->_ENGINE_parse_body("{items}");

	$build_parameters=$tpl->_ENGINE_parse_body("{build_parameters}");
	$new_item=$tpl->_ENGINE_parse_body("{new_item}");
	$import=$tpl->_ENGINE_parse_body("{import}");
	$title=$tpl->_ENGINE_parse_body("{last_transaction_messages} {total}:&nbsp;").  $q->COUNT_ROWS("smtp_logs", "artica_events");
	$country=$tpl->_ENGINE_parse_body("{country}");
	$sender=$tpl->_ENGINE_parse_body("{sender}");
	$recipient=$tpl->_ENGINE_parse_body("{recipient}");
	
	$buttons="
	buttons : [
	{name: '$new_item', bclass: 'add', onpress : NewDiffListItem$t},
	{name: '$import', bclass: 'Reconf', onpress :NewDiffListItemImport$t},
	],";		

	$buttons=null;
	
$html="


<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%;'></table>
	
<script>
var memid$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?list-table=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$country', name : 'Country', width : 31, sortable : true, align: 'center'},
		{display: '$date', name : 'time_connect', width : 60, sortable : true, align: 'left'},
		{display: '$sender', name : 'sender_user', width :147, sortable : true, align: 'left'},
		{display: '$recipient', name : 'delivery_user', width : 147, sortable : false, align: 'left'},
		{display: '$status', name : 'bounce_error', width : 230, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$sender', name : 'sender_user'},
		{display: '$recipient', name : 'delivery_user'},
		{display: '$status', name : 'bounce_error'},
		],
	sortname: 'time_connect',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 694,
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

</script>";	
	echo $html;
}


function list_table(){
	
	$MyPage=CurrentPageName();
	$page=1;
	$tpl=new templates();	
	
	$q=new mysql();
	if(!$q->TABLE_EXISTS("smtp_logs", "artica_events")){$q->BuildTables();}
	$table="smtp_logs";
	$t=$_GET["t"];
	$database="artica_events";
	$FORCE_FILTER=1;
	
	
	if(!$q->TABLE_EXISTS("smtp_logs", "artica_events")){json_error_show("!Error: artica_events No such table");}
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No item");}

	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		if($total==0){json_error_show("No rows for $search");}
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	
	$sql="SELECT *  FROM $table WHERE $FORCE_FILTER $searchstring $ORDER $limitSql";	
	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error<hr>$sql<hr>");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No data...",1);}
	$today=date('Y-m-d');
	$style="font-size:14px;";
	$ini=GetColor();
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	while ($line = mysql_fetch_assoc($results)) {
				
				$Country=$line["Country"];
				$country_img=GetFlags($Country);
				$Region=$line["Region"];
				$smtp_sender=$line["smtp_sender"];
				
				$SPAM=$line["SPAM"];
				$rcpt=$line["delivery_user"];
				$mailfrom=$line["sender_user"];
				$bounce_error=trim($line["bounce_error"]);
				$time=$line["time_connect"];
				$time=str_replace($today, "", $time);
				$spammy=$line["spammy"];
				$bg_color=null;
				if($spammy==1){$bounce_error="SPAMMY";}
				if($SPAM==1){$bounce_error="SPAM";}		
				$bounce_error_key=str_replace(" ","_",$bounce_error);	
				if(isset($ini[$bounce_error_key])){	
					$bg_color="background-color:{$ini[$bounce_error_key]["row_color"]};color:{$ini[$bounce_error_key]["text_color"]};margin:-5px;padding:5px;font-weight:bold";
				}
			
			if($mailfrom==null){$mailfrom="$unknown";}
			if($rcpt==null){$rcpt="$unknown";}
			$js="Loadjs('$MyPage?zoom-js=yes&id={$line["id"]}&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}')";
			$md=md5(serialize($ligne));
			$cells=array();
			$cells[]="<img src='img/$country_img'>";
			$cells[]="<span style='font-size:11px;'>$time</span>";
			$cells[]="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:11px;text-decoration:underline'>$mailfrom</a>";
			$cells[]="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:11px;text-decoration:underline'>$rcpt</a>";
			$cells[]="<span style='font-size:11px;'><div style='$bg_color'>$bounce_error</div></span>";
			
			
			
			$data['rows'][] = array(
				'id' =>$line["id"],
				'cell' => $cells
				);		
		

		}

	echo json_encode($data);		
}


function GetColor(){
	
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$ini->loadString($sock->GET_INFO("RTMMailConfig"));
	
	
	if($ini->_params["Discard"]["row_color"]==null){$ini->_params["Discard"]["row_color"]="#D00000";}
	if($ini->_params["Discard"]["text_color"]==null){$ini->_params["Discard"]["text_color"]="#FFFFFF";}
	
	if($ini->_params["Greylisting"]["row_color"]==null){$ini->_params["Greylisting"]["row_color"]="#949494";}
	if($ini->_params["Greylisting"]["text_color"]==null){$ini->_params["Greylisting"]["text_color"]="#FFFFFF";}
	
	if($ini->_params["Relay_access_denied"]["row_color"]==null){$ini->_params["Relay_access_denied"]["row_color"]="#D00000";}
	if($ini->_params["Relay_access_denied"]["text_color"]==null){$ini->_params["Relay_access_denied"]["text_color"]="#FFFFFF";}	
	
	if($ini->_params["User_unknown_in_relay_recipient_table"]["row_color"]==null){$ini->_params["User_unknown_in_relay_recipient_table"]["row_color"]="#D00000";}
	if($ini->_params["User_unknown_in_relay_recipient_table"]["text_color"]==null){$ini->_params["User_unknown_in_relay_recipient_table"]["text_color"]="#FFFFFF";}	
	
	if($ini->_params["RBL"]["row_color"]==null){$ini->_params["RBL"]["row_color"]="#949494";}
	if($ini->_params["RBL"]["text_color"]==null){$ini->_params["RBL"]["text_color"]="#FFFFFF";}	
	
	if($ini->_params["hostname_not_found"]["row_color"]==null){$ini->_params["hostname_not_found"]["row_color"]="#FFECEC";}
	if($ini->_params["hostname_not_found"]["text_color"]==null){$ini->_params["hostname_not_found"]["text_color"]="#000000";}
	
	if($ini->_params["malformed_address"]["row_color"]==null){$ini->_params["malformed_address"]["row_color"]="#FFECEC";}
	if($ini->_params["malformed_address"]["text_color"]==null){$ini->_params["malformed_address"]["text_color"]="#000000";}
	

	if($ini->_params["User_unknown"]["row_color"]==null){$ini->_params["User_unknown"]["row_color"]="#FFECEC";}
	if($ini->_params["User_unknown"]["text_color"]==null){$ini->_params["User_unknown"]["text_color"]="#000000";}	

	if($ini->_params["non-delivery"]["row_color"]==null){$ini->_params["non-delivery"]["row_color"]="#FFECEC";}
	if($ini->_params["non-delivery"]["text_color"]==null){$ini->_params["non-delivery"]["text_color"]="#000000";}		

	if($ini->_params["No_mailbox"]["row_color"]==null){$ini->_params["No_mailbox"]["row_color"]="#FFECEC";}
	if($ini->_params["No_mailbox"]["text_color"]==null){$ini->_params["No_mailbox"]["text_color"]="#000000";}	
	
		
	if($ini->_params["Domain_not_found"]["row_color"]==null){$ini->_params["Domain_not_found"]["row_color"]="#FFECEC";}
	if($ini->_params["Domain_not_found"]["text_color"]==null){$ini->_params["Domain_not_found"]["text_color"]="#000000";}

	if($ini->_params["DNS_Error"]["row_color"]==null){$ini->_params["DNS_Error"]["row_color"]="#D00000";}
	if($ini->_params["DNS_Error"]["text_color"]==null){$ini->_params["DNS_Error"]["text_color"]="#FFFFFF";}		
	
	if($ini->_params["SPAM"]["row_color"]==null){$ini->_params["SPAM"]["row_color"]="#F36C15";}
	if($ini->_params["SPAM"]["text_color"]==null){$ini->_params["SPAM"]["text_color"]="#FFFFFF";}

	if($ini->_params["SPAMMY"]["row_color"]==null){$ini->_params["SPAMMY"]["row_color"]="#FFC59E";}
	if($ini->_params["SPAMMY"]["text_color"]==null){$ini->_params["SPAMMY"]["text_color"]="#000000";}	

	if($ini->_params["Command died"]["text_color"]=$ini->_params["DNS_Error"]["text_color"]);
	if($ini->_params["Command died"]["row_color"]=$ini->_params["DNS_Error"]["row_color"]);
	
	
	$ini->_params["PostScreen"]["row_color"]=$ini->_params["Discard"]["row_color"];
	$ini->_params["PostScreen"]["text_color"]=$ini->_params["Discard"]["text_color"];
	
	$ini->_params["PostScreen_RBL"]["row_color"]=$ini->_params["RBL"]["row_color"];
	$ini->_params["PostScreen_RBL"]["text_color"]=$ini->_params["RBL"]["text_color"];	
	
	$ini->_params["timed_out"]["row_color"]=$ini->_params["DNS_Error"]["row_color"];
	$ini->_params["timed_out"]["text_color"]=$ini->_params["DNS_Error"]["text_color"];		
	
	$ini->_params["blacklisted"]["row_color"]=$ini->_params["Relay_access_denied"]["row_color"];
	$ini->_params["blacklisted"]["text_color"]=$ini->_params["Relay_access_denied"]["text_color"];
	
	return $ini->_params;	

}
function zoom_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$id=$_GET["id"];
	$sql="SELECT delivery_id_text FROM smtp_logs WHERE id=$id";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$title=$tpl->_ENGINE_parse_body("{transaction}:$id::{$ligne["delivery_id_text"]}");
	$html="RTMMail('890','$page?zoom-tab=yes&id=$id&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$title')";
	echo $html;
	
}

function zoom_status(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$id=$_GET["id"];
	$sql="SELECT * FROM smtp_logs WHERE id=$id";
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	
	$html="<table style='width:99%' class=form>";
	
	while (list ($key, $value) = each ($ligne) ){
		if(is_numeric($key)){continue;}
		if($key=="transaction"){continue;}
		$html=$html."
		<tr>
			<td class=legend style='font-size:16px'>$key</td>
			<td style='font-size:14px;font-weight:bold'>$value</td>
		</tr>
		";
		
		
		
	}
		$html=$html."</table>";
		
		echo $tpl->_ENGINE_parse_body($html);
}

function zoom_tab(){
	$page=CurrentPageName();
	
	$array["zoom-status"]='{status}';
	$array["zoom-transactions"]="{transactions}";
	
	while (list ($num, $ligne) = each ($array) ){
		
		$tab[]="<li><a href=\"$page?$num=yes&id={$_GET["id"]}&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}\"><span style='font-size:16px'>$ligne</span></a></li>\n";
			
		}
	$tpl=new templates();
	
	$html="
		<div id='main_rtmm_transct' style='background-color:white'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_rtmm_transct').tabs({
				    load: function(event, ui) {
				        $('a', ui.panel).click(function() {
				            $(ui.panel).load(this.href);
				            return false;
				        });
				    }
				});
			

			});
		</script>
	
	";
		
	$tpl=new templates();
	$html=$tpl->_ENGINE_parse_body($html);
	
echo $html;	
	
}

function zoom_transaction(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();	
	$id=$_GET["id"];	
	$sql="SELECT delivery_id_text,LENGTH(`transaction`) as tlength FROM smtp_logs WHERE id=$id";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	
	
	if($ligne["tlength"]==0){
		zoom_transaction_order($ligne["delivery_id_text"],$id);return;
	}
	$t=time();
	$html="<div id='$t' style='margin:-10px'></div>
		<script>
				LoadAjax('$t','$page?transactions-table=yes&id=$id&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}');
		</script>	
	
	";
	echo $html;
}

function zoom_transaction_order($msgid,$id){
	$tpl=new templates();
	$page=CurrentPageName();	
	$sock=new sockets();
	$sock->getFrameWork("postfix.php?transactions-order=$msgid&id=$id");
	$t=time();
	$html="<div id='$t'>
		<center style='font-size:18px;padding:50px'>{please_wait_search_transaction_history}</center>
		</div>
		
		<script>
			function TransactionZoomSearch(){
				if(!RTMMailOpen()){return;}
				LoadAjax('$t','$page?transactions-history=yes&msgid=$msgid&id=$id&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}');
			}
			
			setTimeout('TransactionZoomSearch()',3000);
		</script>	
		";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function zoom_transaction_check(){
	$tpl=new templates();
	$page=CurrentPageName();		
	$id=$_GET["id"];	
	$q=new mysql();	
	$sql="SELECT delivery_id_text,LENGTH(`transaction`) as tlength FROM smtp_logs WHERE id=$id";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	if($ligne["tlength"]==0){
		echo $tpl->_ENGINE_parse_body("<center style='font-size:18px;padding:50px'>{please_wait_search_transaction_history}</center>
		<script>setTimeout('TransactionZoomSearch()',3000);</script>
		");
		return;
	}	
	
	$t=time();
	$html="<div id='$t' style='margin:-10px'></div>
		<script>
				LoadAjax('$t','$page?transactions-table=yes&id=$id&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}');
		</script>	
	
	";
	echo $html;	
	
}

function zoom_transaction_table(){
	$hostname=$_GET["hostname"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$tt=$_GET["t"];
	$t=time();
	$id=$_GET["id"];
	$events=$tpl->_ENGINE_parse_body("{events}");
	$title=$tpl->_ENGINE_parse_body("{last_transaction_messages} ID:$id");
	$rescan=$tpl->_ENGINE_parse_body("{rescan}");
	$q=new mysql();
		$sql="SELECT time_stamp	FROM smtp_logs WHERE id=$id";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$time=strtotime($ligne["time_stamp"]);
		$pp=date("l d F H:i:s",$time);	
	
	$buttons="
	buttons : [
	{name: '$rescan', bclass: 'Reconf', onpress : Rescan$t},
	
	],";		

	
	
$html="


<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%;'></table>
	
<script>
var memid$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?transaction-events=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t&id=$id',
	dataType: 'json',
	colModel : [
		{display: '$events', name : 'events', width : 824, sortable : true, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$events', name : 'events'},
		],
	sortname: 'time_connect',
	sortorder: 'desc',
	usepager: true,
	title: '$title - $pp',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 857,
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

		var X_Rescan$t= function (obj) {
			var results=obj.responseText;
			if (results.length>0){alert(results);}
			RefreshTab('main_rtmm_transct');
				
			}		
		function Rescan$t(){
				var XHR = new XHRConnection();
				XHR.appendData('hostname','{$_GET["hostname"]}');
				XHR.appendData('ou','{$_GET["ou"]}');
				XHR.appendData('rescan-id',$id);
				XHR.sendAndLoad('$page', 'POST',X_Rescan$t);
				
			}	

</script>";	
	echo $html;	
	
}
function zoom_transaction_kill(){
	$tpl=new templates();
	$page=CurrentPageName();		
	$id=$_POST["rescan-id"];	
	$q=new mysql();		
	$sql="UPDATE smtp_logs SET `transaction`='' WHERE id=$id";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error;}
}

function zoom_transaction_events(){
	$tpl=new templates();
	$page=CurrentPageName();		
	$id=$_GET["id"];	
	$q=new mysql();	
	$sql="SELECT transaction FROM smtp_logs WHERE id=$id";	
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$array=explode("\n", base64_decode($ligne["transaction"]));
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($array);
	$data['rows'] = array();	
	if($_POST["query"]<>null){$search=string_to_regex($_POST["query"]);}
	while (list ($num, $ligne) = each ($array) ){
		if($ligne==null){continue;}
		if($search<>null){if(!preg_match("#$search#", $ligne)){continue;}}
		
		$cells=array();
		
		
			$cells[]="$ligne";
			
			
			
			$data['rows'][] = array(
				'id' =>$line["id"],
				'cell' => $cells
				);		
		

		}

	echo json_encode($data);		
	
}
