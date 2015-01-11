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
include_once(dirname(__FILE__)."/ressources/class.user.inc");


$users=new usersMenus();
if(!$users->AsDnsAdministrator){die();}

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["search-events"])){events_table();exit;}


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
		<H1>{dns_service}</H1>
		<p>{pdns_explain}</p>
	</div>	
	<div id='messaging-left'></div>
	
	<script>
		LoadAjax('messaging-left','$page?popup=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function status(){
	$tpl=new templates();
	$sock=new sockets();
	
	$EnablePDNS=$sock->GET_INFO("EnablePDNS");
	if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
	

	if($EnablePDNS==0){
		echo $tpl->_ENGINE_parse_body("<p class=text-error>{error_powerdns_disabled}</p>");
		return;
		
	}
	
	$page=CurrentPageName();
	
	$datas=base64_decode($sock->getFrameWork("services.php?pdns-status=yes"));
	$ini=new Bs_IniHandler();
	$ini->loadString($datas);
	
	
	$tr[]=DAEMON_STATUS_ROUND("APP_PDNS",$ini,null);
	$tr[]=DAEMON_STATUS_ROUND("APP_PDNS_INSTANCE",$ini,null);
	$tr[]=DAEMON_STATUS_ROUND("PDNS_RECURSOR",$ini,null);	
	
	echo "<center><div style='width:850px'>".
	$tpl->_ENGINE_parse_body(CompileTr3($tr,true)).
	"</div></center>";
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();	
	$boot=new boostrap_form();
	
	if(isset($_GET["explain-title"])){
		$title=$tpl->_ENGINE_parse_body("		<H3>{dns_service}</H3>
		<p style='margin-bottom:10px'>{pdns_explain}</p>");
		
	}
	
	$array["{dns_entries}"]="miniadm.PowerDNS.entries.php";
	$array["{settings}"]="$page?settings=yes";
	$array["{status}"]="$page?status=yes";
	$array["{events}"]="$page?events=yes";
	echo $title.$boot->build_tab($array);
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
	
	$EnablePDNS=$sock->GET_INFO("EnablePDNS");
	$PDNSRestartIfUpToMB=$sock->GET_INFO("PDNSRestartIfUpToMB");
	$DisablePowerDnsManagement=$sock->GET_INFO("DisablePowerDnsManagement");
	$EnablePDNS=$sock->GET_INFO("EnablePDNS");
	
	$PowerUseGreenSQL=$sock->GET_INFO("PowerUseGreenSQL");
	$PowerDisableDisplayVersion=$sock->GET_INFO("PowerDisableDisplayVersion");
	$PowerActHasMaster=$sock->GET_INFO("PowerActHasMaster");
	$PowerDNSDNSSEC=$sock->GET_INFO("PowerDNSDNSSEC");
	$PowerDNSDisableLDAP=$sock->GET_INFO("PowerDNSDisableLDAP");
	$PowerChroot=$sock->GET_INFO("PowerChroot");
	$PowerActAsSlave=$sock->GET_INFO("PowerActAsSlave");
	$PowerDNSLogLevel=$sock->GET_INFO("PowerDNSLogLevel");
	$PowerSkipCname=$sock->GET_INFO("PowerSkipCname");
	
	if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
	$PowerDNSMySQLEngine=1;
	if(!is_numeric($PowerActHasMaster)){$PowerActHasMaster=0;}
	if(!is_numeric($PDNSRestartIfUpToMB)){$PDNSRestartIfUpToMB=700;}
	if(!is_numeric($DisablePowerDnsManagement)){$DisablePowerDnsManagement=0;}
	if(!is_numeric($PowerUseGreenSQL)){$PowerUseGreenSQL=0;}
	if(!is_numeric($PowerDisableDisplayVersion)){$PowerDisableDisplayVersion=0;}
	if(!is_numeric($PowerDNSDNSSEC)){$PowerDNSDNSSEC=0;}
	if(!is_numeric($PowerDNSDisableLDAP)){$PowerDNSDisableLDAP=1;}
	if(!is_numeric($PowerChroot)){$PowerChroot=0;}
	if(!is_numeric($PowerActAsSlave)){$PowerActAsSlave=0;}
	if(!is_numeric($PowerDNSLogLevel)){$PowerDNSLogLevel=1;}
	if(!is_numeric($PowerSkipCname)){$PowerSkipCname=0;}
	
	
	$PowerDNSMySQLType=$sock->GET_INFO("PowerDNSMySQLType");
	$PowerDNSMySQLRemoteServer=$sock->GET_INFO("PowerDNSMySQLRemoteServer");
	$PowerDNSMySQLRemotePort=$sock->GET_INFO("PowerDNSMySQLRemotePort");
	$PowerDNSMySQLRemoteAdmin=$sock->GET_INFO("PowerDNSMySQLRemoteAdmin");
	$PowerDNSMySQLRemotePassw=$sock->GET_INFO("PowerDNSMySQLRemotePassw");
	if(!is_numeric($PowerDNSMySQLType)){$PowerDNSMySQLType=1;}
	if(!is_numeric($PowerDNSMySQLRemotePort)){$PowerDNSMySQLRemotePort=3306;}
	
	$PowerDNSMySQLTypeA[1]="{main_mysql_server_2}";
	$PowerDNSMySQLTypeA[2]="{main_mysql_server_4}";
	$PowerDNSMySQLTypeA[3]="{main_mysql_server_5}";
	
	for($i=0;$i<10;$i++){
		$loglevels[$i]=$i;
	}
	
	

	$boot=new boostrap_form();
	$boot->set_checkbox("EnablePDNS", "{EnablePDNS}", $EnablePDNS,array(
			"ONDISABLE"=>"{EnablePDNS_disable_text}"
		
	));	
	$boot->set_checkbox("DisablePowerDnsManagement", "{DisablePowerDnsManagement}", $DisablePowerDnsManagement);
	$boot->set_checkbox("PowerActHasMaster", "{ActHasMaster}", $PowerActHasMaster);
	$boot->set_checkbox("PowerActAsSlave", "{ActHasSlave}", $PowerActAsSlave);
	$boot->set_checkbox("PowerDNSDNSSEC", "DNSSEC", $PowerDNSDNSSEC);
	$boot->set_checkbox("PowerUseGreenSQL", "{useGreenSQL}", $PowerUseGreenSQL);
	$boot->set_checkbox("PowerDisableDisplayVersion", "{DisableDisplayVersion}", $PowerDisableDisplayVersion);
	$boot->set_checkbox("PowerChroot", "{chroot}", $PowerChroot);
	$boot->set_list("PowerDNSLogLevel", "{log level}",$loglevels,$PowerDNSLogLevel);
	$boot->set_field("PDNSRestartIfUpToMB", "{RestartServiceifReachMb}", $PDNSRestartIfUpToMB);
	$boot->set_list("PowerDNSMySQLType", "{mysql_database}",$PowerDNSMySQLTypeA,$PowerDNSMySQLType);
	$boot->set_field("PowerDNSMySQLRemoteServer", "{remote_mysql_server}", $PowerDNSMySQLRemoteServer);
	$boot->set_field("PowerDNSMySQLRemotePort", "{mysql_server_port}", $PowerDNSMySQLRemotePort);
	$boot->set_field("PowerDNSMySQLRemoteAdmin", "{mysql_admin}", $PowerDNSMySQLRemoteAdmin);
	$boot->set_fieldpassword("PowerDNSMySQLRemotePassw", "{password}", $PowerDNSMySQLRemotePassw);
	$boot->set_button("{apply}");
	$boot->setAjaxPage("pdns.php");
	$boot->set_PROTO("GET");
	
	
	echo $tpl->_ENGINE_parse_body("<div class=text-info>{pdns_explain}</div>").$boot->Compile();	
}
function events(){
	$boot=new boostrap_form();
echo $boot->SearchFormGen(null,"search-events");
	
}

function events_table(){
	$tpl=new templates();
	$sock=new sockets();
	$pattern=base64_encode($_GET["search-events"]);	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?syslog-query=$pattern&prefix=pdns*");
	$datas=explode("\n", @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/syslog.query"));
	
	krsort($datas);
	while (list ($key, $line) = each ($datas) ){
		

		$line=htmlentities($line);
		$tr[]="
		<tr class='$class'>
		<td>$line</td>
		</tr>
		";
	}
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered'>
		
			<thead>
				<tr>
					<th width=1%>{events}</th>
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


	$sock->getFrameWork("postfix.php?milters=yes");
	$sock->getFrameWork("postfix.php?restart-mailarchiver=yes");
}
