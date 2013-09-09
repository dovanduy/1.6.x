<?php
session_start();
$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.ufdbguard-tools.inc");

if(isset($_GET["verbose"])){$GLOBALS["DEBUG_PRIVS"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);header("location:miniadm.logon.php");}
BuildSessionAuth();
if($_SESSION["uid"]=="-100"){writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);header("location:admin.index.php");die();}
$users=new usersMenus();
if($GLOBALS["VERBOSE"]){
	if(!$users->AsProxyMonitor){
		echo "<H1>AsProxyMonitor = FALSE</H1>";
		return;
	
	}else{
		echo "<H1>AsProxyMonitor = TRUE</H1>";
	}
}
if(!$users->AsProxyMonitor){header("location:miniadm.logon.php");}


if(isset($_GET["content"])){content();exit;}
if(isset($_GET["query-logs"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["section-realtime"])){section_realtime();exit;}

if(isset($_GET["section-ufdb"])){section_ufdb();exit;}
if(isset($_GET["ufdb-logs"])){table_ufdb();exit;}


if(isset($_GET["section-blocked-tabs"])){section_blocked_tabs();exit;}
if(isset($_GET["section-blocked-realtime"])){section_blocked_realtime();exit;}
if(isset($_GET["section-blocked-realtime-search"])){section_blocked_realtime_search();exit;}



if(isset($_GET["section-blocked"])){section_blocked();exit;}
if(isset($_GET["query-blocked"])){table_blocked();exit;}

if(isset($_GET["section-blocked-js"])){section_blocked_js();exit;}
if(isset($_GET["section-blocked-popup"])){section_blocked_form();exit;}
if(isset($_POST["QUERY_BLOCKED_DATE"])){section_blocked_save();exit;}

main_page();
exit;


if(isset($_GET["choose-language"])){choose_language();exit;}
if(isset($_POST["miniconfig-POST-lang"])){choose_language_save();exit();}


function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;
	
	
}

function tabs(){
	$boot=new boostrap_form();
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();	
	$array["{APP_SQUID}"]="$page?section-realtime=yes";
	$users=new usersMenus();
	
	
	
	if($users->APP_FTP_PROXY){
		$EnableFTPProxy=$sock->GET_INFO("EnableFTPProxy");
		if(!is_numeric($EnableFTPProxy)){$EnableFTPProxy=0;}	
		if($EnableFTPProxy==1){	
			$array["{APP_FTP_PROXY}"]="miniadm.system.syslog.php?prepend=ftp-proxy,ftp-child";
		}
	}
	
	if($users->APP_UFDBGUARD_INSTALLED){
		$EnableUfdbGuard=$sock->EnableUfdbGuard();
		if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
		if($EnableUfdbGuard==1){
			$array["{webfiltering}"]="$page?section-blocked-tabs=yes";
			
		}
	}
	echo $boot->build_tab($array);
	
}

function section_blocked_tabs(){
	$boot=new boostrap_form();
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$array["{blocked_requests}"]="$page?section-blocked-realtime=yes";
	$array["{service_events}"]="$page?section-ufdb=yes";
	$array["{history}"]="$page?section-blocked=yes";
	echo $boot->build_tab($array);
	
}

function section_realtime(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();	
	$SearchQuery=$boot->SearchFormGen(null,"query-logs");
	echo $SearchQuery;
}

function section_blocked_realtime(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$SearchQuery=$boot->SearchFormGen(null,"section-blocked-realtime-search");
	echo $SearchQuery;	
}

function section_ufdb(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$SearchQuery=$boot->SearchFormGen(null,"ufdb-logs");
	echo $SearchQuery;
}

function section_blocked(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$LINKS["LINKS"][]=array("LABEL"=>"{advanced_search}","JS"=>"Loadjs('$page?section-blocked-js=yes')");
	
	$SearchQuery=$boot->SearchFormGen(null,"query-blocked",null,$LINKS);
	echo $SearchQuery;
}

function section_blocked_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title="{advanced_search}";
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin2(600,'$page?section-blocked-popup=yes','$title')";	
	
}

function section_blocked_form(){
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	$ARRAYTABLES=$q->LIST_TABLES_BLOCKED();
	while (list ($tablename, $none) = each ($ARRAYTABLES) ){
		$time=$q->TIME_FROM_DAY_TABLE($tablename);
		$days[$time]=time_to_date($time);
		
	}
	$BLOCKED_CATEGORY_LIMITS[50]=50;
	$BLOCKED_CATEGORY_LIMITS[250]=250;
	$BLOCKED_CATEGORY_LIMITS[500]=250;
	$BLOCKED_CATEGORY_LIMITS[1000]=1000;
	$BLOCKED_CATEGORY_LIMITS[2000]=2000;
	
	krsort($days);
	$boot->set_list("QUERY_BLOCKED_DATE", "{date}", $days,$_SESSION["QUERY_BLOCKED_DATE"]);
	$boot->set_field("QUERY_BLOCKED_UID", "{member}", $_SESSION["QUERY_BLOCKED_UID"]);
	$boot->set_field("QUERY_BLOCKED_CATEGORY", "{category}", $_SESSION["QUERY_BLOCKED_CATEGORY"]);
	$boot->set_list("BLOCKED_CATEGORY_LIMIT", "{rows}", $BLOCKED_CATEGORY_LIMITS,$_SESSION["BLOCKED_CATEGORY_LIMIT"]);
	$boot->set_button("{search}");
	$boot->set_CloseYahoo("YahooWin2");
	$boot->set_formdescription("{advanced_search_explain}");
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
}
function section_blocked_save(){
	while (list ($key, $value) = each ($_POST) ){
		$_SESSION[$key]=$value;
	}
}


function content(){
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();

	$html="<div class=BodyContent>
	<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>&nbsp;&raquo;&nbsp;
	</div>
	<H1>{realtime_requests}</H1>
	<p>{realtime_requests_text}</p>
	<div class=BodyContentWork id='$t-div'></div>


	<script>
		LoadAjax('$t-div','$page?tabs=yes');
	</script>

	";
	echo $tpl->_ENGINE_parse_body($html);


}
function table_ufdb(){
	
	if($_GET["ufdb-logs"]<>null){
		$search=base64_encode($_GET["ufdb-logs"]);
		$search="&search=$search";
	}
	$sock=new sockets();
	$tables=unserialize(base64_decode($sock->getFrameWork("squid.php?ufdbguard-events=yes&rp=500$search")));
	
	
	if(count($tables)==0){
		senderror("no data");
	}

	
	krsort($tables);
	while (list ($ID, $line) = each ($tables) ){
		if(!preg_match('#(.+?)\s+\[(.+?)\]\s+(.+)#', $line,$re)){continue;}
		$color="black";
		$date=$re[1];
		$pid=$re[2];
		$event=$re[3];
	
		
		$class=LineToClass($event);
		
		
	
		$tr[]=
		"<tr class=$class>
		<td nowrap style='font-size:12px'>$date</td>
		<td style='font-size:12px' width=1% nowrap>$pid</td>
		<td style='font-size:12px'>$event</td>
		</tr>
		";

	
	
	}
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered'>
				
			<thead>
			<tr>
			<th>{date}</th>
			<th>PID</th>
			<th>{events}</th>
			</tr>
			</thead>
			<tbody>
			").@implode("\n", $tr)." </tbody></table>";	
}

function section_blocked_realtime_search(){
	$_GET["section-blocked-realtime-search"]=url_decode_special_tool($_GET["section-blocked-realtime-search"]);
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	if(!isset($_GET["rp"])){$_GET["rp"]=350;}
	
	
	
	if($_GET["section-blocked-realtime-search"]<>null){
		$search=base64_encode($_GET["section-blocked-realtime-search"]);
		$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?ufdbguard-logs=$search&rp={$_GET["rp"]}")));
		if(count($datas)==0){senderror("no data");}
		$total=count($datas);
	
	}else{
		$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?ufdbguard-logs=&rp={$_GET["rp"]}")));
		if(count($datas)==0){senderror("no data");}
		$total=count($datas);
	}	
	
	
	
	
	$boot=new boostrap_form();
	$q2=new mysql();
	$t=time();
	
	
	while (list ($ID, $line) = each ($datas) ){
		
		
		if(!preg_match('#(.+?)\s+\[(.+?)\]\s+(.+)#', $line,$re)){continue;}
		$color="black";
		$date=$re[1];
		$pid=$re[2];
		$event=$re[3];
	

		if(!preg_match("#^BLOCK\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+[A-Z]+#", $event,$re)){continue;}
		
		
		$account=$re[1];
		$group=$re[2];
		$category=$re[4];
		$rule=$re[3];
		$uri=$re[5];
		$sitename=null;
		$js=null;
		$unblock=null;
		
		
		
		if(preg_match("#^art(.+)#", $category,$re)){
			$category=CategoryCodeToCatName($category);
			$CATEGORY_PLUS_TXT="Artica Database";
		}
		
		if(preg_match("#^tls(.+)#", $category,$re)){
			$category=CategoryCodeToCatName($category);
			$CATEGORY_PLUS_TXT="Toulouse University Database";
		}		
		$URLAR=parse_url($uri);
		if(isset($URLAR["host"])){$sitename=$URLAR["host"];}
		
		if(preg_match("#^(.*?):[0-9]+$#", $sitename,$re)){$sitename=$re[1];}
		if(preg_match("#^www\.(.*?)$#", $sitename,$re)){$sitename=$re[1];}
		
		if($sitename<>null){
			$js="Loadjs('squid.categories.php?category=$category&website=$sitename')";
			$link=$boot->trswitch($js);
			$unblock=imgsimple("whitelist-24.png",null,"UnBlockWebSite$t('$sitename')");
			$ligne3=mysql_fetch_array($q2->QUERY_SQL("SELECT items FROM urlrewriteaccessdeny WHERE items='$sitename'","artica_backup"));
		}
		
		if(!$q2->ok){
			$unblock="<img src='img/icon_err.gif'><br>$q2->mysql_error";
		}else{
			if($ligne3["items"]<>null){
				$unblock=imgsimple("20-check.png",null,null);
			}
		}
		
		$strlen=strlen($uri);
		$uriT = wordwrap($uri, 100, "\n", true);
		$uriT = htmlentities($uriT);
		$uriT = nl2br($uriT);
		$uriT=str_replace($sitename, "<a href=\"javascript:blur()\"
				OnClick=\"javascript:Loadjs('miniadm.webstats.familysite.all.php?familysite=$sitename');\"
				style='text-decoration:underline;color:$color'>$sitename</a>", $uriT);		
	
	
		$tr[]=
		"<tr>
		<td nowrap style='font-size:14px' width=1% nowrap>$date</td>
		<td style='font-size:14px' width=1% nowrap>$pid</td>
		<td style='font-size:14px' width=1% nowrap>$category<div style='font-size:11px'>$CATEGORY_PLUS_TXT</div></td>
		<td style='font-size:14px' width=1% nowrap>$account/$group</td>
		<td style='font-size:14px' width=1% nowrap>$rule</td>
		<td style='font-size:14px'>$uriT</td>
		<td style='font-size:14px' width=1% nowrap>$unblock</td>
		</tr>
		";
	
	
	
	}
	
	
	$tpl=new templates();
	$UnBlockWebSiteExplain=$tpl->javascript_parse_text("{UnBlockWebSiteExplain}");
	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered'>
	
			<thead>
			<tr>
			<th>{date}</th>
			<th>PID</th>
			<th>{category}</th>
			<th>{member}</th>
			<th nowrap>{rulename}</th>
			<th>{url}</th>
			<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			").@implode("\n", $tr)." </tbody></table>		<script>
	var x_UnBlockWebSite$t=function(obj){
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){alert(tempvalue);}
	      
	}	

function UnBlockWebSite$t(domain){
	if(confirm('$UnBlockWebSiteExplain:'+domain+' ?')){
		var XHR = new XHRConnection();
		XHR.appendData('unlock',domain);
		XHR.sendAndLoad('squid.blocked.events.php', 'POST',x_UnBlockWebSite$t);
	}

}
</script>";


	
}


function table(){
	include_once(dirname(__FILE__)."/ressources/class.squid.accesslogs.inc");
	$_GET["query"]=url_decode_special_tool($_GET["query"]);
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();	
	if(!isset($_GET["rp"])){$_GET["rp"]=150;}
	if($_GET["query-logs"]<>null){
		$search=base64_encode($_GET["query-logs"]);
		$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?accesslogs=$search&rp={$_GET["rp"]}")));
		$total=count($datas);
		
	}else{
		$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?accesslogs=&rp={$_GET["rp"]}")));
		$total=count($datas);
	}
	
	$squidacc=new accesslogs();
	$c=0;
	while (list ($key, $line) = each ($datas) ){
		$line=trim($line);
		$lineS=$line;
		$color="black";
		$return_code_text=null;
		$ff=array();
		$color="black";
		if(preg_match('#(.+?)\s+(.+?)\s+squid\[.+?:\s+MAC:(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+\[(.+?)\]\s+\"([A-Z]+)\s+(.+?)\s+.*?"\s+([0-9]+)\s+([0-9]+)#i',$line,$re)){
			$re[6]=trim($re[6]);
			if($re[5]=="-"){
				if( ($re[6]<>"-") && !is_null($re[6])){
					$re[5]=$re[6];
					$re[6]="-";
				}
			}
		
				
			//$ff[]="F=1";
			//while (list ($a, $b) = each ($re) ){$ff[]="$a=$b";}
			//$array["RE"]=@implode("<br>", $ff);
			$uri=$re[9];
		
			$date=date("Y-m-d H:i:s",strtotime($re[7]));
			$mac=$re[3];
			$ip=$re[4];
			$user=$re[5];
			$dom=$re[6];
			$proto=$re[8];
			$return_code=$re[10];
			$size=$re[11];
		
			$array["IP"]=$ip;
			$array["URI"]=$uri;
			$array["DATE"]=$date;
			$array["MAC"]=$mac;
			$array["USER"]=$user;
			$array["USER"]=$user;
			$array["PROTO"]=$proto;
			$array["CODE"]=$return_code;
			$array["SIZE"]=$size;
			$array["LINE"]=$line;
			$data['rows'][]=$squidacc->Buildline($array,true);
			continue;
		
		}
			
			
		if(preg_match('#(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+.*?\s+MAC:(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+\[(.+?)\]\s+\"([A-Z]+)\s+(.+?)\s+.*?"\s+([0-9]+)\s+([0-9]+)#',$line,$re)){
		$time=$re[3];
		$prox=$re[4];
		$mac=$re[5];
		$date=date("Y-m-d H:i:s",strtotime($re[9]));
		$ip=$re[6];
		$user=$re[8];
		
		$proto=$re[10];
		$return_code=$re[12];
		$size=$re[13];
		$uri=$re[11];
		//$ff[]="F=2";
		//while (list ($a, $b) = each ($re) ){$ff[]="$a=$b";}
		//$array["RE"]=@implode("<br>", $ff);
		$array["PROXY"]=$prox;
		$array["IP"]=$ip;
		$array["URI"]=$uri;
		$array["DATE"]=$date;
		$array["MAC"]=$mac;
		$array["USER"]=$user;
		$array["USER"]=$user;
		$array["PROTO"]=$proto;
		$array["CODE"]=$return_code;
		$array["SIZE"]=$size;
		$array["LINE"]=$line;
		
			$data['rows'][]=$squidacc->Buildline($array,true);
			continue;
		
		}
			
		
			
		if(preg_match('#(.*?)\s+([0-9]+)\s+([0-9:]+).*?\]:\s+(.*?)\s+(.+)\s+(.+)\s+.+?"([A-Z]+)\s+(.+?)\s+.*?"\s+([0-9]+)\s+([0-9]+)#',$line,$re)){
		
		if(preg_match("#(TCP_DENIED|ERR_CONNECT_FAIL)#", $line)){
					$color="#BA0000";
				}
				    $dates="{$re[1]} {$re[2]} ".date('Y'). " {$re[3]}";
					$ip=$re[4];
			$user=$re[5];
					
				$re[6]=trim($re[6]);
					if($re[5]=="-"){
						if( ($re[6]<>"-") && !is_null($re[6])){
				$re[5]=$re[6];
				$re[6]="-";
				}
			}
		
			//$ff[]="F=3";
			//while (list ($a, $b) = each ($re) ){$ff[]="$a=$b";}
			//$array["RE"]=@implode("<br>", $ff);
				
			$date=date("Y-m-d H:i:s",strtotime($dates));
				$uri=$re[8];
				$proto=$re[7];
				$return_code=$re[8];
				$size=$re[9];
					
				$array["IP"]=$ip;
				$array["URI"]=$uri;
				$array["DATE"]=$date;
				$array["MAC"]=$mac;
				$array["USER"]=$user;
				$array["USER"]=$user;
				$array["PROTO"]=$proto;
				$array["CODE"]=$return_code;
				$array["SIZE"]=$size;
				$array["LINE"]=$line;
				$data['rows'][]=$squidacc->Buildline($array,true);
				
				continue;
		
			}
			
			
			
	}
		
				
		
		$tr[]=@implode($data['rows'], "\n");
		
		

	
	
	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered'>
			
			<thead>
				<tr>
					<th>{date}</th>
					<th>{proto}</th>
					<th>{urls} ($total)</th>
					<th>{member}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody></table>";
	
}

function table_blocked(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$dt=strtotime("{$_GET["zday"]} 00:00:00");

	if(isset($_SESSION["QUERY_BLOCKED_DATE"])){
		$dt=$_SESSION["QUERY_BLOCKED_DATE"];
	}
	$zday=date('Ymd',$dt);
	$title_date=time_to_date($dt);
	$MAIN_QUERY=$_GET["query-blocked"];
	
	$search='%';
	$table=$zday."_blocked";
	if(!$q->TABLE_EXISTS($table)){
		senderrors("{no_such_table}:$table");
	}
	$main_query=$_GET["query-blocked"];
	$QUERY_BLOCKED_UID=$_SESSION["QUERY_BLOCKED_UID"];
	$QUERY_BLOCKED_CATEGORY=$_SESSION["QUERY_BLOCKED_CATEGORY"];
	$BLOCKED_CATEGORY_LIMIT=$_SESSION["BLOCKED_CATEGORY_LIMIT"];
	
	if(!is_numeric($BLOCKED_CATEGORY_LIMIT)){$BLOCKED_CATEGORY_LIMIT=250;}
	
	if($QUERY_BLOCKED_UID<>null){
		$title_members=" {and} {member}:$QUERY_BLOCKED_UID";
		$QUERY_BLOCKED_UID_OP=" = ";
		if(preg_match("#not.*?:(.+)#i", $QUERY_BLOCKED_UID,$re)){$neg=true;$QUERY_BLOCKED_UID=trim($re[1]);}
		if($neg){$QUERY_BLOCKED_UID_OP=" != ";}
		$QUERY_BLOCKED_UID=str_replace("*", "%", $QUERY_BLOCKED_UID);
		if(strpos(" $QUERY_BLOCKED_UID", "%")>0){
				$QUERY_BLOCKED_UID_OP=" LIKE ";
				if($neg){$QUERY_BLOCKED_UID_OP=" NOT LIKE ";}
		}
		$QUERY_BLOCKED_UID_SQL=" AND (
			(`client` $QUERY_BLOCKED_UID_OP'$QUERY_BLOCKED_UID') 
			OR (`hostname` $QUERY_BLOCKED_UID_OP'$QUERY_BLOCKED_UID')
			OR (`uid` $QUERY_BLOCKED_UID_OP'$QUERY_BLOCKED_UID')
			)
		";
	}
	
	$neg=false;
	if($QUERY_BLOCKED_CATEGORY<>null){
		$title_cat=" {and} {category}:$QUERY_BLOCKED_CATEGORY";
		if(preg_match("#not.*?:(.+)#i", $QUERY_BLOCKED_CATEGORY,$re)){$neg=true;$QUERY_BLOCKED_CATEGORY=trim($re[1]);}
		$QUERY_BLOCKED_CATEGORY=str_replace("*", "%", $QUERY_BLOCKED_CATEGORY);
		$QUERY_BLOCKED_CATEGORY_OP=" = ";
		if($neg){$QUERY_BLOCKED_CATEGORY_OP=" != ";}
		if(strpos(" $QUERY_BLOCKED_CATEGORY", "%")>0){
			$QUERY_BLOCKED_CATEGORY_OP=" LIKE ";
			if($neg){$QUERY_BLOCKED_CATEGORY_OP=" NOT LIKE ";}
		}
		$QUERY_BLOCKED_CATEGORY_SQL=" AND `category`$QUERY_BLOCKED_CATEGORY_OP'$QUERY_BLOCKED_CATEGORY'";
	}
	
	$neg=false;
	if($MAIN_QUERY<>null){
		$title_cat=" {and} {website}:$MAIN_QUERY";
		if(preg_match("#not.*?:(.+)#i", $MAIN_QUERY,$re)){$neg=true;$MAIN_QUERY=trim($re[1]);}
		$MAIN_QUERY=str_replace("*", "%", $MAIN_QUERY);
		$MAIN_QUERY_OP=" = ";
		if($neg){$MAIN_QUERY_OP=" != ";}
		if(strpos(" $MAIN_QUERY", "%")>0){
			$MAIN_QUERY_OP=" LIKE ";
			if($neg){$MAIN_QUERY_OP=" NOT LIKE ";}
		}
		$MAIN_QUERY_SQL=" AND `website`$MAIN_QUERY_OP'$MAIN_QUERY'";
	}
	
	$sql="SELECT * FROM `$table` WHERE 1 $MAIN_QUERY_SQL$QUERY_BLOCKED_UID_SQL$QUERY_BLOCKED_CATEGORY_SQL ORDER BY zDate LIMIT 0,$BLOCKED_CATEGORY_LIMIT";
	

	
	$results = $q->QUERY_SQL($sql,'artica_events');
	$boot=new boostrap_form();	
	$UnBlockWebSiteExplain=$tpl->javascript_parse_text("{UnBlockWebSiteExplain}");
	$today=date('Y-m-d');
	if(!$q->ok){
		sendserrors($q->mysql_error."<br>$sql");
	}
	$t=time();
	$q2=new mysql();
	while ($ligne = mysql_fetch_assoc($results)) {	
		
		$member=$ligne["client"];
		if($ligne["hostname"]<>null){$member=$ligne["hostname"];}
		if($ligne["uid"]<>null){$member=$ligne["uid"];}
		$js="Loadjs('squid.categories.php?category={$ligne["category"]}&website={$ligne["website"]}')";
		$link=$boot->trswitch($js);
		
		$unblock=imgsimple("whitelist-24.png",null,"UnBlockWebSite$t('{$ligne["website"]}')");
		
		$ligne3=mysql_fetch_array($q2->QUERY_SQL("SELECT items FROM urlrewriteaccessdeny WHERE items='{$ligne["website"]}'","artica_backup"));
		if(!$q->ok){
			$unblock="<img src='img/icon_err.gif'><br>$q->mysql_error";
		}else{
			if($ligne3["items"]<>null){
				$unblock=imgsimple("20-check.png",null,null);
			}
		}		
		
		if($ligne["blocktype"]<>null){
			$blocktype="<div><i style='font-size:10px'>{$ligne["blocktype"]}</i></div>";
		}
		$tr[]="
		<tr>
		<td nowrap><i class='icon-time'></i>&nbsp;{$ligne["zDate"]}</td>
		<td $link><i class='icon-user'></i>&nbsp;$member</td>
		<td $link><i class='icon-globe'></i>&nbsp;{$ligne["website"]}$blocktype</td>
		<td $link><i class='icon-info'></i>&nbsp;{$ligne["category"]}</td>
		<td $link><i class='icon-info'></i>&nbsp;{$ligne["rulename"]}</td>
		<td>$unblock</td>
		</tr>";
	
	
	}
	
	echo $tpl->_ENGINE_parse_body("
			
		<p>{search}:$title_date$title_members$title_cat</p>
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{zDate}&nbsp;</th>
					<th>{member}</th>
					<th>{website}</th>
					<th>{category}</th>
					<th>{rule}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
		</table>
		<script>
	var x_UnBlockWebSite$t=function(obj){
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){alert(tempvalue);}
	      
	}	

function UnBlockWebSite$t(domain){
	if(confirm('$UnBlockWebSiteExplain:'+domain+' ?')){
		var XHR = new XHRConnection();
		XHR.appendData('unlock',domain);
		XHR.sendAndLoad('squid.blocked.events.php', 'POST',x_UnBlockWebSite$t);
	}

}
</script>					
";	
}
