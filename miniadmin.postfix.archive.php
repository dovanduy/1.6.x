<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.archive.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");


$users=new usersMenus();
if(!$users->AsPostfixAdministrator){die();}

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["rules"])){rules();exit;}
if(isset($_GET["search-events"])){events_table();exit;}
if(isset($_POST["MailArchiverEnabled"])){MailArchiverEnabled();exit;}
if(isset($_GET["database"])){database_section();exit;}
if(isset($_GET["search-indextables"])){database_search();exit;}
main_page();
//archiverlogs

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}


function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{archive_module}</H1>
		<p>{mymessaging_archive_text}</p>
	</div>	
	<div id='messaging-left'></div>
	
	<script>
		LoadAjax('messaging-left','$page?popup=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}



function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();	
	$boot=new boostrap_form();
	
	
	$array["{settings}"]="$page?settings=yes";
	$array["{database}"]="$page?database=yes";
	$array["{rules}"]="$page?rules=yes";
	$array["{events}"]="$page?events=yes";
	
	if(isset($_GET["title"])){
		
		echo $tpl->_ENGINE_parse_body("<H3>{archive_module}</H3>
		<p>{mymessaging_archive_text}</p>");
	}
	
	echo $boot->build_tab($array);
}

function rules(){
	$t=time();
	$html="<div id='$t'></div>
	<script>
		LoadAjax('$t','miniadmin.messaging.archive.php?popup=yes');
	</script>
	";
	echo $html;
	
	
}


function settings(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	
	$MailArchiverEnabled=$sock->GET_INFO("MailArchiverEnabled");
	$MailArchiverToMySQL=$sock->GET_INFO("MailArchiverToMySQL");
	$MailArchiverToMailBox=$sock->GET_INFO("MailArchiverToMailBox");
	$MailArchiverMailBox=$sock->GET_INFO("MailArchiverMailBox");
	$MailArchiverUsePerl=$sock->GET_INFO("MailArchiverUsePerl");
	$MailArchiverToSMTP=$sock->GET_INFO("MailArchiverToSMTP");
	$MailArchiverSMTP=$sock->GET_INFO("MailArchiverSMTP");
	$MailArchiverSMTPINcoming=$sock->GET_INFO("MailArchiverSMTPINcoming");
	
	
	$MailArchiverToMySQLMaxDays=$sock->GET_INFO("MailArchiverToMySQLMaxDays");
	$MailArchiverToMySQLBackupPath=$sock->GET_INFO("MailArchiverToMySQLBackupPath");
	if(!is_numeric($MailArchiverToMySQLMaxDays)){$MailArchiverToMySQLMaxDays=60;}
	if($MailArchiverToMySQLBackupPath==null){$MailArchiverToMySQLBackupPath="/home/artica/backup/mailsarchives";}
	
	if(!is_numeric($MailArchiverEnabled)){$MailArchiverEnabled=0;}
	if(!is_numeric($MailArchiverToMySQL)){$MailArchiverToMySQL=1;}
	if(!is_numeric($MailArchiverUsePerl)){$MailArchiverUsePerl=0;}
	if(!is_numeric($MailArchiverToSMTP)){$MailArchiverToSMTP=0;}
	if(!is_numeric($MailArchiverSMTPINcoming)){$MailArchiverSMTPINcoming=1;}	
	
	$boot=new boostrap_form();
	$boot->set_checkbox("MailArchiverEnabled", "{enable_APP_MAILARCHIVER}", $MailArchiverEnabled,array(
			"ONDISABLE"=>"{enable_APP_MAILARCHIVER_disable_text}"
			
			));
	$boot->set_checkbox("MailArchiverUsePerl", "{us_v2}", $MailArchiverUsePerl,array(
			"ONDISABLE"=>"{MailArchiverUsePerl_disable_text}"
			
			));
	$boot->set_checkbox("MailArchiverToMySQL", "{save_to_mysqldb}", $MailArchiverToMySQL);
	$boot->set_checkbox("MailArchiverToMailBox", "{send_to_mailbox}", $MailArchiverToMailBox,array(
			"LINK"=>"MailArchiverMailBox"
			));
	$boot->set_field("MailArchiverMailBox", "{mailbox}", $MailArchiverMailBox);
	
	
	$boot->set_checkbox("MailArchiverToSMTP", "{send_to_smtp_server}", $MailArchiverToSMTP,array(
			"LINK"=>"MailArchiverSMTP"
			));
	$boot->set_field("MailArchiverSMTP", "{smtp_server}", $MailArchiverSMTP);
	
	$boot->set_spacertitle("{retention_time}");
	$boot->set_field("MailArchiverToMySQLMaxDays", "{max_days}", $MailArchiverToMySQLMaxDays);
	$boot->set_field("MailArchiverToMySQLBackupPath", "{backup_directory}", $MailArchiverToMySQLBackupPath,array("BROWSE"=>true));
	
	
	
	echo $boot->Compile();	
}
function events(){
	$boot=new boostrap_form();
echo $boot->SearchFormGen(null,"search-events");
	
}

function events_table(){
	$tpl=new templates();
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("system.php?archiverlogs=yes&search=".base64_encode($_GET["search-events"]))));
	
	krsort($datas);
	while (list ($key, $line) = each ($datas) ){
		
		if(preg_match("#^(.+?)\s+(.+?)\s+(.+?)\[([0-9]+)\]:(.+)#", $line,$re)){
			$date=$re[1]." ".$re[2];
			$process=$re[3];
			$pid=$re[4];
			$line=$re[5];
		}
		
		if(preg_match("#^\[(.*?)\]\s+(.*)#",$line,$re)){
			$line=$re[2];
			$function=$re[1];
		}
		
		if(preg_match("#(.*?)in line:(.*)#", $line,$re)){
			$line=$re[1];
			$LinNumber=$re[2];
		}
		
		$class=null;
		if(preg_match("#error#i", $line)){$class="error";}
		if(preg_match("#failed#i", $line)){$class="error";}
		if(preg_match("#FATAL#i", $line)){$class="warning";}
		if(preg_match("#abnormally#i", $line)){$class="error";}
		if(preg_match("#could not#i", $line)){$class="error";}
		if(preg_match("#Reconfiguring#i", $line)){$class="info";}
		if(preg_match("#Accepting HTTP#i", $line)){$class="info";}
		if(preg_match("#Ready to serve requests#i", $line)){$class="info";}

		$addon="<div style='font-size:11px'>&laquo;$process&raquo; function: &laquo;$function&raquo;, line $LinNumber</div>";
		if(strpos($line, "}")>0){$line=$tpl->_ENGINE_parse_body($line);}
		$line=htmlentities($line);
		$tr[]="
		<tr class='$class'>
		<td nowrap>$date</td>
		<td>$pid</td>
		<td>$line$addon</td>
		</tr>
		";
	}
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered'>
		
			<thead>
				<tr>
					<th width=1%>{date}</th>
					<th width=1%>pid</th>			
					<th>{event}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
			
			</table>";	
	
}

function MailArchiverEnabled(){
	$MailArchiverEnabled=$_POST["MailArchiverEnabled"];
	writelogs("MailArchiverEnabled=$MailArchiverEnabled",__FUNCTION__,__FILE__);
	$sock=new sockets();
	$sock->SET_INFO('MailArchiverEnabled',$MailArchiverEnabled);
	$sock->SET_INFO('MailArchiverMailBox',$_POST["MailArchiverMailBox"]);
	$sock->SET_INFO('MailArchiverToMailBox',$_POST["MailArchiverToMailBox"]);
	$sock->SET_INFO('MailArchiverToMySQL',$_POST["MailArchiverToMySQL"]);
	$sock->SET_INFO('MailArchiverUsePerl',$_POST["MailArchiverUsePerl"]);

	$sock->SET_INFO('MailArchiverToSMTP',$_POST["MailArchiverToSMTP"]);
	$sock->SET_INFO('MailArchiverSMTP',$_POST["MailArchiverSMTP"]);
	$sock->SET_INFO('MailArchiverSMTPINcoming',$_POST["MailArchiverSMTPINcoming"]);
	
	$sock->SET_INFO('MailArchiverToMySQLMaxDays',$_POST["MailArchiverToMySQLMaxDays"]);
	$sock->SET_INFO('MailArchiverToMySQLBackupPath',$_POST["MailArchiverToMySQLBackupPath"]);
	
	$sock->getFrameWork("postfix.php?milters=yes");
	$sock->getFrameWork("postfix.php?restart-mailarchiver=yes");
}

function database_section(){
	$tpl=new templates();
	$q=new mysql_mailarchive_builder();
	$TablesNum=$q->COUNT_TABLES("mailarchive");
	$Size=$q->DATABASE_SIZE_BYTES("mailarchive");
	$Size=FormatBytes($Size/1024);

	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$html="<H1>{backup_database_status}</H1>
	<H3>$TablesNum table(s)&nbsp|&nbsp;	$Size</H3>	
			
	".$boot->SearchFormGen("tablename,xday","search-indextables");
	$boot=new boostrap_form();

	echo $tpl->_ENGINE_parse_body($html);
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}

function database_search(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_mailarchive_builder();
	
	$search=string_to_flexquery("search-indextables");
	$sql="SELECT * FROM indextables WHERE 1 $search ORDER BY xday DESC";
	$results=$q->QUERY_SQL($sql,"mailarchive");
	if(!$q->ok){senderror($q->mysql_error);}
	if(mysql_num_rows($results)==0){senderrors("{this_request_contains_no_data}");}
	
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$id=md5(serialize($ligne));
		$ligne["rowsnum"]=FormatNumber($ligne["rowsnum"]);
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$tr[]="
		<tr id='$id'>
		<td width=1% nowrap><img src='img/table-show-48.png'></td>
		<td width=90% nowrap><div style='font-size:18px'>{$ligne["xday"]}</div></td>
		<td width=90% nowrap><div style='font-size:18px'><div style='font-size:18px'>{$ligne["rowsnum"]}</div></td>
		<td width=90% nowrap><div style='font-size:18px'><div style='font-size:18px'>{$ligne["size"]}</div></td>
		</tr>";
	}
	
	echo $tpl->_ENGINE_parse_body("
	
			<table class='table table-bordered'>
	
			<thead>
				<tr>
					<th colspan=2>{day}</th>
					<th >{rows}</th>
					<th >{size}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";
	
}
