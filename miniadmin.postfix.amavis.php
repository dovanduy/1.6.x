<?php
session_start();
//$GLOBALS["VERBOSE"]=true;
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}

include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__).'/ressources/class.amavis.inc');
include_once(dirname(__FILE__)."/ressources/class.user.inc");

$users=new usersMenus();
if(!$users->AsPostfixAdministrator ){die();}

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["SearchQuery"])){policies_search();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_POST["INI_SAVE"])){parameters_save();exit;}
if(isset($_GET["search-events"])){events_table();exit;}

if($GLOBALS["VERBOSE"]){echo "Main page<br>\n";}
main_page();



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
		<H1>{APP_AMAVISD_NEW}</H1>
		<p>{AMAVIS_DEF}</p>
	</div>
	<div id='center'></div>

	<script>
	LoadAjax('center','$page?tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{parameters}"]="$page?parameters=yes";
	$array["{events}"]="$page?events=yes";
	echo $boot->build_tab($array);
}
function events(){
	$boot=new boostrap_form();
	echo $boot->SearchFormGen(null,"search-events");

}

function parameters(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$boot=new boostrap_form();
	$amavis=new amavis();
	$AmavisMemoryInRAM=$sock->GET_INFO("AmavisMemoryInRAM");
	if(!is_numeric($AmavisMemoryInRAM)){$AmavisMemoryInRAM=0;}
	$AmavisDebugSpamassassin=$sock->GET_INFO("AmavisDebugSpamassassin");
	if(!is_numeric($AmavisDebugSpamassassin)){$AmavisDebugSpamassassin=0;}
	$BuildNetworks=$amavis->BuildNetworks();
	$BuildNetworks=str_replace(" ", "\\n", $BuildNetworks);
	$trust_my_net=$tpl->javascript_parse_text("{trust_my_net}");	
	$bt="{apply}";
	for($i=0;$i<6;$i++){
		$hash[$i]="{log_level} 0$i";
	
	}	
	$array=array(
			null=>"{select}",
			"D_PASS"=>"{D_PASS}",
			"D_DISCARD"=>'{D_DISCARD}',
			"D_BOUNCE"=>'{D_BOUNCE}',
			"D_REJECT"=>'{D_REJECT}');

	$boot->set_hidden("INI_SAVE", "BEHAVIORS");
	$boot->set_checkbox("AmavisDebugSpamassassin", "{sa_debug}", $AmavisDebugSpamassassin);
	$boot->set_list("log_level", "{log_level}", $hash,$amavis->main_array["BEHAVIORS"]["log_level"]);
	$boot->set_list("final_virus_destiny", "{final_virus_destiny}", $array,$amavis->main_array["BEHAVIORS"]["final_virus_destiny"]);
	$boot->set_list("final_banned_destiny", "{final_virus_destiny}", $array,$amavis->main_array["BEHAVIORS"]["final_banned_destiny"]);
	$boot->set_list("final_spam_destiny", "{final_spam_destiny}", $array,$amavis->main_array["BEHAVIORS"]["final_spam_destiny"]);
	$boot->set_list("final_bad_header_destiny", "{final_bad_header_destiny}", $array,$amavis->main_array["BEHAVIORS"]["final_bad_header_destiny"]);
	$boot->set_checkbox("always_clean", "{transfert_messages_if_av_failed}", $amavis->main_array["BEHAVIORS"]["always_clean"]);
	$boot->set_checkbox("trust_my_net", "{trust_my_net}", $amavis->main_array["BEHAVIORS"]["trust_my_net"]);
	$boot->set_checkbox("enable_db", "{amavis_enable_db}", $amavis->main_array["BEHAVIORS"]["enable_db"]);
	$boot->set_checkbox("enable_global_cache", "{amavis_enable_global_cache}", $amavis->main_array["BEHAVIORS"]["enable_global_cache"]);
	$boot->set_spacertitle("{performances}");
	$boot->set_field("AmavisMemoryInRAM", "{AmavisMemoryInRAM} (MB)", $AmavisMemoryInRAM);
	$boot->set_field("max_servers", "{max_servers}", $amavis->main_array["BEHAVIORS"]["max_servers"]);
	$boot->set_field("max_requests", "{max_requests}", $amavis->main_array["BEHAVIORS"]["max_requests"]);
	$boot->set_field("child_timeout", "{child_timeout}", $amavis->main_array["BEHAVIORS"]["child_timeout"]);
	echo $boot->Compile();

	
}

function parameters_save(){
	$AmavisMemoryInRAM=$_POST["AmavisMemoryInRAM"];
	if($AmavisMemoryInRAM>0){
		if($AmavisMemoryInRAM<128){$AmavisMemoryInRAM=128;}
	}
	
	$sock=new sockets();
	$sock->SET_INFO('AmavisMemoryInRAM',$AmavisMemoryInRAM);
	$sock->SET_INFO('AmavisDebugSpamassassin',$AmavisDebugSpamassassin);
	
	
	$amavis=new amavis();
	$amavis->main_array["BEHAVIORS"]["max_servers"]=$_POST["max_servers"];
	$amavis->main_array["BEHAVIORS"]["max_requests"]=$_POST["max_requests"];
	$amavis->main_array["BEHAVIORS"]["child_timeout"]=$_POST["child_timeout"];
	
	$amavis->main_array["BEHAVIORS"]["final_virus_destiny"]=$_POST["final_virus_destiny"];
	$amavis->main_array["BEHAVIORS"]["final_banned_destiny"]=$_POST["final_banned_destiny"];
	$amavis->main_array["BEHAVIORS"]["final_spam_destiny"]=$_POST["final_spam_destiny"];
	$amavis->main_array["BEHAVIORS"]["always_clean"]=$_POST["always_clean"];
	$amavis->main_array["BEHAVIORS"]["trust_my_net"]=$_POST["trust_my_net"];
	$amavis->main_array["BEHAVIORS"]["enable_db"]=$_POST["enable_db"];
	$amavis->main_array["BEHAVIORS"]["enable_global_cache"]=$_POST["enable_global_cache"];
	$amavis->main_array["BEHAVIORS"]["log_level"]=$_POST["log_level"];
	
	
	
	
	$amavis->Save();
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{ERROR_NEED_TO_SAVEAPPLY}");
}
function events_table(){
$sock=new sockets();
	$users=new usersMenus();
	$maillog_path=$users->maillog_path;
	$query=base64_encode($_GET["search-events"]);
	$datas=unserialize(base64_decode($sock->getFrameWork("postfix.php?query-maillog=yes&filter=$query&maillog=$maillog_path&rp=700&prefix=amavis")));
	$datas=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/query.mail.log"));
	krsort($array);
	$tpl=new templates();
	while (list ($key, $line) = each ($datas) ){
		$lineOrg=$line;
		if(preg_match("#^[a-zA-Z]+\s+[0-9]+\s+([0-9\:]+)\s+(.+?)\s+(.+?)\[([0-9]+)\]:(.+)#", $line,$re)){
			$date="{$re[1]}";
			$host=$re[2];
			$service=$re[3];
			$pid=$re[4];
			$line=$re[5];
	
	
		}
	
		$class=LineToClass($line);
	
	
		$line=htmlentities($line);
		$tr[]="
		<tr class='$class'>
		<td nowrap>$date</td>
		<td>$pid</td>
		<td>$line</td>
		</tr>
		";
	}
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered'>
	
			<thead>
				<tr>
					<th width=1%>{date}</th>
					<th>PID</th>
					<th>{event}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
		
			</table>";
	

}
?>