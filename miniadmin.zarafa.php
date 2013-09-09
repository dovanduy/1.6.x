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
if(!$users->AsMailBoxAdministrator){die();}

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["search-records"])){events_search();exit;}
if(isset($_GET["statistics"])){statistics();exit;}
if(isset($_GET["mailboxes"])){mailboxes();exit;}
if(isset($_GET["search-mailboxes"])){mailboxes_search();exit;}
if(isset($_GET["search-statistics"])){statistics_search();exit;}
//zarafa-stats-system
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
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork('cmd.php?zarafa-status=yes'));
	$ini->loadString($datas);
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{APP_ZARAFA} v{$ini->_params["APP_ZARAFA"]["master_version"]}</H1>
		<p>{APP_ZARAFA_TEXT}</p>
	</div>	
	<div id='messaging-left'></div>
	
	<script>
		LoadAjax('messaging-left','$page?tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	
	
	$array["{status}"]="$page?status=yes";
	$array["{statistics}"]="$page?statistics=yes";
	$array["{events}"]="$page?events=yes";
	$array["{mailboxes}"]="$page?mailboxes=yes";
	$array["{smartphones}"]="miniadmin.zpush.php";
	
	
	echo $boot->build_tab($array);	
	
	
}

function status(){
	
	$html="<div id='zarafa-services-status' style='width:100%;'></div>
	<script>
		LoadAjax('zarafa-services-status','zarafa.index.php?services-status=yes&miniadm=yes');
			
	</script>";
	
	echo $html;
	
}
function events(){
	$boot=new boostrap_form();
	$SearchQuery=$boot->SearchFormGen("events","search-records");	
	echo $SearchQuery;
}

function mailboxes(){
	$boot=new boostrap_form();
	$button=button("{new_member}","Loadjs('create-user.php?ByZarafa=yes');",16);
	$EXPLAIN["BUTTONS"][]=$button;
	$SearchQuery=$boot->SearchFormGen("uid,mail,ou,NONACTIVETYPE","search-mailboxes",null,$EXPLAIN);
	echo $SearchQuery;
}

function statistics(){
	$boot=new boostrap_form();
	$SearchQuery=$boot->SearchFormGen("statistics","search-statistics");
	echo $SearchQuery;
}



function mailboxes_search(){
	$tpl=new templates();
	$table="zarafauserss";
	$database="artica_events";
	$boot=new boostrap_form();
	$q=new mysql();
	$page=1;
	$ORDER="ORDER BY `uid`";
	$member=$tpl->_ENGINE_parse_body("{member}");
	$email=$tpl->_ENGINE_parse_body("{mail}");
	$ou=$tpl->_ENGINE_parse_body("{organization}");
	$license=$tpl->_ENGINE_parse_body("{license}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$user=$tpl->_ENGINE_parse_body("{user}");
	$mailbox_size=$tpl->_ENGINE_parse_body("{mailbox_size}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$delete_rule=$tpl->javascript_parse_text("{delete_rule}");
	$refresh=$tpl->_ENGINE_parse_body("{refresh}");
	$deleteAll=$tpl->_ENGINE_parse_body("{delete_all}");
	$apply=$tpl->_ENGINE_parse_body("{apply_parameters}");	
	
	
	
	$total=0;
	if($q->COUNT_ROWS($table,$database)==0){ throw new Exception("$table, No entry...",500);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}	
	
	$searchstring=string_to_flexquery("search-mailboxes");
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){ throw new Exception("$q->mysql_error");}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$uid=$ligne["uid"];
		if(strtolower($uid)=="no"){continue;}
		$md5=$ligne["zmd5"];
		$color="black";
		$imglicense="22-key.png";
		if($ligne["license"]==0){$imglicense="ed_delete_grey.gif";}
		$js=MEMBER_JS($uid,1,1);
		$license=imgsimple($imglicense,"{delete}");
		$ligne["storesize"]=FormatBytes($ligne["storesize"]/1024);
		$link=$boot->trswitch($js);
		$tr[]="
		<tr id='$id' style='font-size:16px'>
		<td $link nowrap><i class='icon-user'></i>&nbsp;$uid</a></td>
		<td $link nowrap><i class='icon-user'></i>&nbsp;{$ligne["mail"]}</td>
		<td $link nowrap><i class='icon-info-sign'></i>&nbsp;{$ligne["ou"]}</td>
		<td $link nowrap><i class='icon-info-sign'></i>&nbsp;$license</td>
		<td $link nowrap><i class='icon-info-sign'></i>&nbsp;{$ligne["NONACTIVETYPE"]}</td>
		<td $link nowrap><i class='icon-info-sign'></i>&nbsp;{$ligne["storesize"]}</td>
		</tr>";		
	}
		
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>$member</th>
					<th>$email</th>
					<th>$ou</th>
					<th>{license2}</th>
					<th>&nbsp;</th>
					<th>$mailbox_size</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
		</table>
<script>
	function ZoomEvents(content){
		RTMMail(650,'postfix.events.new.php?ZoomEvents='+content);
	}
</script>";	
	
}

function events_search(){
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$maillog_path=$users->maillog_path;
	$query=base64_encode($_GET["search-records"]);
	if(!isset($_GET["rp"])){$_GET["rp"]=500;}
	$array=unserialize(base64_decode($sock->getFrameWork("postfix.php?query-maillog=yes&filter=$query&maillog=$maillog_path&rp={$_GET["rp"]}&zarafa-filter=yes&mimedefang-filter={$_GET["mimedefang-filter"]}")));
	if($_POST["sortorder"]=="desc"){krsort($array);}else{ksort($array);}
	$boot=new boostrap_form();
	while (list ($index, $line) = each ($array) ){
		$lineenc=base64_encode($line);
		if(preg_match("#^[a-zA-Z]+\s+[0-9]+\s+([0-9\:]+)\s+(.+?)\s+(.+?)\[([0-9]+)\]:(.+)#", $line,$re)){
			$date="{$re[1]}";
			$host=$re[2];
			$service=$re[3];
			$pid=$re[4];
			$line=$re[5];
				
				
		}
		$loupejs="ZoomEvents('$lineenc')";
		$img=statusLogs($line);
		
		$link=$boot->trswitch($loupejs);
		$tr[]="
		<tr id='$id' style='font-size:12px'>
		<td $link nowrap><i class='icon-time'></i>&nbsp;$date</a></td>
		<td $link nowrap><i class='icon-info-sign'></i>&nbsp;$host</td>
		<td $link nowrap>$service</td>
		<td $link nowrap>$pid</td>
		<td style='text-align:center'><img src='$img'></td>
		<td $link'>$line</td>
		</tr>";		

	}
echo $tpl->_ENGINE_parse_body("
		
		<table class='table table-bordered table-hover'>
		
			<thead>
				<tr>
					<th>{date}</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<th>{events}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
		</table>
<script>
	function ZoomEvents(content){
		RTMMail(650,'postfix.events.new.php?ZoomEvents='+content);
	}
</script>";
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}
function statistics_search(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("zarafa.php?zarafa-stats-system=yes")));
	$tpl=new templates();
	$search=string_to_flexregex("search-statistics");
	
	while (list ($index, $ligne) = each ($datas) ){
		if(strpos($ligne, "-----------------------")>0){continue;}
		if(strpos($ligne, "0x6740001E")>0){continue;}
		if(strpos($ligne, "Time")>0){continue;}
		
		if($search<>null){if(!preg_match("#$search#", $ligne)){continue;}}
		$ligne=trim($ligne);
		$ligneR=explode("\t", $ligne);
		$T=array();
		while (list ($a, $b) = each ($ligneR) ){
			if($b==null){continue;}
			$T[]=$b;
		}
		$explain=$T[1];
		$value=$T[2];
		if(preg_match("#(size|memory)#i", $explain)){
			$value=FormatBytes($value/1024);
		}else{
			if(is_numeric($value)){
				$value=FormatNumber($value);
			}
		}
		
		$tr[]="
		<tr id='$id' style='font-size:16px'>
		<td $link nowrap><i class='icon-info-sign'></i>&nbsp;$explain</a></td>
		<td $link nowrap><i class='icon-info-sign'></i>&nbsp;$value</td>
		</tr>";
		
	}
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{item}</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
				</table>";	
	
}

