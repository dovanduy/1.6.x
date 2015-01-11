<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.spamassassin.inc');
include_once('ressources/class.amavis.inc');

session_start();
$ldap=new clladp();
if(isset($_GET["loadhelp"])){loadhelp();exit;}

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

if(isset($_GET["whitelist"])){SaveWhiteList();exit;}
if(isset($_GET["del_whitelist"])){del_whitelist();exit;}
if(isset($_GET["whitelist-form-add"])){form_whitelist();exit;}
if(isset($_GET["blacklist-form-add"])){form_blacklist();exit;}
if(isset($_GET["MembersSearch"])){MembersSearch();exit;}


if(isset($_GET["js"])){js_popup();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["SelectedDomain"])){popup_switch();exit;}
if(isset($_GET["wblopt"])){wblopt_js();exit;}
if(isset($_GET["wblopt-popup"])){wblopt_popup();exit;}
if(isset($_GET["WBLReplicEnable"])){wblopt_save();exit;}
if(isset($_GET["WBLReplicNow"])){wblopt_replic();exit;}
if(isset($_GET["EnableWhiteListAndBlackListPostfix"])){ArticaRobotsSave();exit;}
if(isset($_GET["popup-domain-white"])){popup_domains();exit;}
if(isset($_GET["popup-domain-black"])){popup_domains();exit;}
if(isset($_GET["popup-hosts"])){popup_hosts();exit;}
if(isset($_GET["white-hosts"])){hosts_WhiteList();exit;}
if(isset($_GET["white-hosts-find"])){hosts_WhiteList_list();exit;}
if(isset($_GET["white-list-host"])){hosts_WhiteList_add();exit;}
if(isset($_GET["white-list-host-del"])){hosts_WhiteList_del();exit;}




if(isset($_GET["popup-global-black"])){blacklist_global_popup();exit;}
if(isset($_GET["popup-global-black-add"])){blacklist_global_add();exit;}
if(isset($_POST["popup-global-black-save"])){blacklist_global_save();exit;}
if(isset($_GET["popup-global-black-list"])){blacklist_global_list();exit;}
if(isset($_GET["GlobalBlackDelete"])){blacklist_global_delete();exit;}
if(isset($_GET["GlobalBlackDisable"])){blacklist_global_disable();exit;}

if(isset($_GET["popup-global-white"])){whitelist_global_popup();exit;}
if(isset($_GET["popup-global-white-add"])){whitelist_global_add();exit;}
if(isset($_POST["popup-global-white-save"])){whitelist_global_save();exit;}
if(isset($_GET["popup-global-white-list"])){whitelist_global_list();exit;}
if(isset($_GET["GlobalWhiteDisable"])){whitelist_global_disable();exit;}
if(isset($_GET["GlobalWhiteDelete"])){whitelist_global_delete();exit;}
if(isset($_GET["GlobalWhiteScore"])){whitelist_global_score();exit;}

if(isset($_GET["WhiteListResolvMX"])){WhiteListResolvMXSave();exit;}


function js_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{global_whitelist}');
	$data=file_get_contents('js/wlbl.js');
	$start="YahooWinS(700,'$page?popup=yes','$title');";
	if(isset($_GET["font-size"])){$fontsize="&font-size={$_GET["font-size"]}";}	
	if(isset($_GET["js-in-line"])){
		$start="document.getElementById('BodyContent').innerHTML='<center><img src=img/wait_verybig.gif></center>';\n$('#BodyContent').load('$page?popup=yes$fontsize');";
	}
	
	$html="
	$data
	
	function StartIndex(){
		$start
	}
	function EnableWhiteListAndBlackListPostfixEdit(){
		var EnableWhiteListAndBlackListPostfix=document.getElementById('EnableWhiteListAndBlackListPostfix').value;
		LoadAjax('EnableWhiteListAndBlackListPostfixDiv','$page?EnableWhiteListAndBlackListPostfix='+EnableWhiteListAndBlackListPostfix);
	
	}
	
	StartIndex();
	";
	echo $html;
	}
	
function wblopt_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{options}');
	$html="	
	function StartIndex2(){
		YahooWin(600,'$page?wblopt-popup=yes','$title');
	}
	

	
var x_WBLReplicNow= function (obj) {
	var results=obj.responseText;
	alert(results);
	StartIndex2();
	}
	
	
	function WBLReplicNow(){
		var XHR = new XHRConnection();
		XHR.appendData('WBLReplicNow','yes');
		document.getElementById('wbldiv').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
		XHR.sendAndLoad('$page', 'GET',x_WBLReplicNow);
	
	}		
	
	StartIndex2();
	";
	
	echo $html;
}

function ArticaRobotsSave(){
	$sock=new sockets();
	$sock->SET_INFO("EnableWhiteListAndBlackListPostfix",$_GET["EnableWhiteListAndBlackListPostfix"]);
	echo ArticaRobots();
	
}

function ArticaRobots(){
	
	$sock=new sockets();
	$EnableWhiteListAndBlackListPostfix=$sock->GET_INFO('EnableWhiteListAndBlackListPostfix');
	$p=Paragraphe_switch_img('{enable_artica_wbl_robots}','{enable_artica_wbl_robots_text}',
	"EnableWhiteListAndBlackListPostfix",$EnableWhiteListAndBlackListPostfix,'{enable_disbable}',300);
	$html="
	<table style='width:99%' class=form>
	<tr>
	<td>
	<div style='padding:3px;'>$p
	<div style='width:101%;text-align:right'>
		<input type='button' value='{apply}&nbsp;&nbsp;&raquo;&raquo;' OnClick=\"javascript:EnableWhiteListAndBlackListPostfixEdit();\">
	</div>
	</div>
	</td>
	</tr>
	</table>
	";
	
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body($html);
	
	
	
}


function wblopt_replic(){
	$sock=new sockets();
	$sock->getfile("WBLReplicNow");
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body('{success}');	
	
}

function wblopt_popup(){
	$page=CurrentPageName();
	
	for($i=1;$i<30;$i++){
		$va=$i*10;
		$array[$va]=$va;
	}
	
	
	$auto=new autolearning_spam();
	
$days="<table style='width:99%' class=form>
<tr><td valign='top'><H3>{schedule}</h3>
<p class=caption>{run_every}...</p>
</td></tr>";	

for($i=0;$i<60;$i++){
	if($i<10){$mins[$i]="0$i";}else{$mins[$i]=$i;}
	}
for($i=0;$i<24;$i++){
	if($i<10){$hours[$i]="0$i";}else{$hours[$i]=$i;}
	}	
	
preg_match('#(.+?):(.+)#',$auto->WBLReplicSchedule["CRON"]["time"],$re);
$minutes=Field_array_Hash($mins,'msched',$re[2]);
$hour=Field_array_Hash($hours,'hsched',$re[1]);



while (list ($num, $line) = each ($auto->array_days)){
	$day=$line;
	$enabled=$auto->WBLReplicSchedule["DAYS"][$day];
	$days=$days."
	<tr>
		<td class=legend>{$day}</td>
		<td>".Field_checkbox($day,1,$enabled)."</td>
	</tr>";
	
}	

$days=$days."
<tr>
			<td class=legend>{time}</td>
			<td>$hour&nbsp;:&nbsp;$minutes</td>
		</tr>
</table>";

	
	$ArticaRobots=ArticaRobots();
	$WBLReplicEachMin=$auto->WBLReplicEachMin;
	
	if($WBLReplicEachMin==null){$WBLReplicEachMin=60;}
	if(preg_match('#([0-9]+)h#',$WBLReplicEachMin,$re)){
		$WBLReplicEachMin=$re[1]*60;
	}
	
	$form1="<table style='width:99%' class=form>
					<tr>
						<td class=legend>{enable_learning_spam_mailbox}:</td>
						<td>" . Field_checkbox('WBLReplicEnable',1,$auto->WBLReplicEnable) ."</td>
					</tr>
					<tr>
						<td class=legend>{enable_learning_ham_mailbox}:</td>
						<td>" . Field_checkbox('WBLReplicaHamEnable',1,$auto->WBLReplicaHamEnable) ."</td>
					</tr>
					<tr>
					<tr><td colspan=2><hr></td></tr>
						<td class=legend colspan=2>
							<input type='button' OnClick=\"javascript:WBLReplicNow()\" value='{replicate_now}&nbsp;&raquo;'>
						</td>
					</tr>	
				</table>";
	
	
	
	$html="<H1>{autolearning}</H1>
	<p class=caption>{autolearning_text}</p>
	<div id='wbldiv'>
	<form name='ffm1rep'>
	<table style='width:99%' class=form>
	<tr>
		<td valign='top'>
		  $form1
		  <div id='EnableWhiteListAndBlackListPostfixDiv'>
		  $ArticaRobots
		  </div>
		</td>
		<td valign='top'>
			
			$days
		</td>
	</tr>
<tr>
		<td colspan=2 align='right'>
			<hr>
		</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'>
			<input type='button' OnClick=\"javascript:ParseForm('ffm1rep','$page',true);\" value='{apply}&nbsp;&raquo;'>
		</td>
	</tr>	
	</table>
	</form>
	</div>
		";
	
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
	
}

function wblopt_save(){
	$sock=new sockets();
	$WBLReplicEachMin=$_GET["WBLReplicEachMin"];
		if($WBLReplicEachMin>60){
		$WBLReplicEachMin=round($WBLReplicEachMin/60).'h';
	}
	
	$auto=new autolearning_spam();
	$auto->WBLReplicEachMin=$WBLReplicEachMin;
	$auto->WBLReplicaHamEnable=$_GET["WBLReplicaHamEnable"];
	$auto->WBLReplicEnable=$_GET["WBLReplicEnable"];
	
	$time="{$_GET["hsched"]}:{$_GET["msched"]}";
	
	$auto->WBLReplicSchedule["CRON"]["time"]=$time;
	$auto->WBLReplicSchedule["TIME"]["time"]=$time;
	
	while (list ($num, $line) = each ($_GET)){
		$auto->WBLReplicSchedule["DAYS"][$num]=$line;
	}
	$auto->Save();
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body('{success}');
	
	}
	
function form_blacklist(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$tt=time();
	$html="
	
	<div id='wblarea_B_$t'></div>
<div style='width:98%' class=form>
	<table style='width:99%'>
			<tr>
			<td class=legend style='font-size:18px'>{from}:</td>
			<td>" . Field_text("wlfrom-$tt",$_GET["whitelist"],'width:220px;font-size:18px;padding:3px',null,null,null,false,"AddblwformCheck$tt(0,event)") ."</td>
			</tr>
			<tr>
			<td class=legend><strong style='font-size:18px'>{recipient}:</td>
			<td>" . Field_text("wlto-$tt",$_GET["recipient"],'width:220px;font-size:18px;padding:3px',null,null,null,false,"AddblwformCheck$tt(0,event)") ."</td>
			</tr>
			<tr>
			<td colspan=2 align='right'>
				". button("{add}","Addblwform_black$tt(0)",22)."</td>
			</tr>
		</table>
</div>
	<script>
		
		
	var x_Addwl$tt=function(obj){
    	document.getElementById('wblarea_B_$t').innerHTML='';
		var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);return;}
		$('#table-$t').flexReload();
		document.getElementById('wlfrom-$tt').value='';
		document.getElementById('wlto-$tt').value='';
      }

      
    function AddblwformCheck$tt(tt,e){
    	if(checkEnter(e)){Addblwform_black$tt(0);}
    }
    
function Addblwform_black$tt(){
      var XHR = new XHRConnection();
      XHR.appendData('RcptDomain','{$_GET["domain"]}');
      XHR.appendData('whitelist',document.getElementById('wlfrom-$tt').value);
      XHR.appendData('recipient',document.getElementById('wlto-$tt').value);
      XHR.appendData('wbl',1);
      AnimateDiv('wblarea_B_$t');
      XHR.sendAndLoad('$page', 'GET',x_Addwl$tt);
      }	     
	
	</script>	
		
		";	
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}
	
function form_whitelist(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$tt=time();
	$html="
	
	<div id='wblarea_A_$t'></div>
	<div style='width:98%' class=form>
	<table style='width:99%'>
			<tr>
			<td class=legend style='font-size:14px'>{from}:</td>
			<td>" . Field_text("wlfrom-$tt",$_GET["whitelist"],'width:220px;font-size:18px;padding:3px',null,null,null,false,"AddblwformCheck$tt(0,event)") ."</td>
			</tr>
			<tr>
			<td class=legend><strong style='font-size:14px'>{recipient}:</td>
			<td>" . Field_text("wlto-$tt",$_GET["recipient"],'width:220px;font-size:18px;padding:3px',null,null,null,false,"AddblwformCheck$tt(0,event)") ."</td>
			</tr>
			<tr>
			<td colspan=2 align='right'>
				". button("{add}","Addblwform$tt(0)",22)."</td>
			</tr>
		</table>
	</div>
	<script>
		
		
	var x_Addwl$tt=function(obj){
    	document.getElementById('wblarea_A_$t').innerHTML='';
		var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);return;}
		$('#table-$t').flexReload();
		document.getElementById('wlfrom-$tt').value='';
		document.getElementById('wlto-$tt').value='';
      }

      
    function AddblwformCheck$tt(tt,e){
    	if(checkEnter(e)){
    		Addblwform$tt(0);
    	}
    
    }
		
	function Addblwform$tt(){
	      var XHR = new XHRConnection();
	      XHR.appendData('RcptDomain','{$_GET["domain"]}');
	      XHR.appendData('whitelist',document.getElementById('wlfrom-$tt').value);
	      XHR.appendData('recipient',document.getElementById('wlto-$tt').value);
	      XHR.appendData('wbl',0);
	      AnimateDiv('wblarea_A_$t');
	      XHR.sendAndLoad('$page', 'GET',x_Addwl$tt);
      }			
	</script>	
		
		";	
	
	echo $tpl->_ENGINE_parse_body($html);
}

function popup_switch(){
	$domain=$_GET["SelectedDomain"];
	$type=$_GET["type"];
	switch ($type) {
		case "white":whitelistdom($domain);exit;break;
		case "black":blacklistdom($domain);exit;break;
		case null:whitelistdom($domain);exit;break;	
		}	
	
}

function popup_hosts(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$t=time();
	
	
	echo "<div id='$t'></div>
	<script>
	LoadAjax('$t','fw.whitehosts.php');
	</script>
	
	";
	return;	
}

function hosts_WhiteList_add(){
	if($_GET["white-list-host"]==null){echo "NULL VALUE";return null;}
	
	$users=new usersMenus();
	$tpl=new templates();
	if(!$users->AsPostfixAdministrator){
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "$error";
		die();
	}	
	
	if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#",$_GET["white-list-host"])){
		$ipaddr=gethostbyname($_GET["white-list-host"]);
		$hostname=$_GET["white-list-host"];
	}else{
		$ipaddr=$_GET["white-list-host"];
		$hostname=gethostbyaddr($_GET["white-list-host"]);
	}
	
	$sql="INSERT IGNORE INTO postfix_whitelist_con (ipaddr,hostname) VALUES('$ipaddr','$hostname')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?smtp-whitelist=yes");	
}

function hosts_WhiteList_del(){
	$users=new usersMenus();
	$tpl=new templates();
	if(!$users->AsPostfixAdministrator){
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "$error";
		die();
	}	
		
	$found=false;
	$server=$_GET["white-list-host-del"];
	$sql="DELETE FROM postfix_whitelist_con WHERE ipaddr='$server'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	$sql="DELETE FROM postfix_whitelist_con WHERE hostname='$server'";
	$q->QUERY_SQL($sql,"artica_backup");
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?smtp-whitelist=yes");
	
}


function hosts_WhiteList(){

	
	
	$page=CurrentPageName();
	$tpl=new templates();


	$html="
	<center>
	<table style='width:70%' class=form>
	<tr>
		<td class=legend>{host}</td>
		<td>". Field_text("PostfixAutoBlockWhiteList-search",null,"font-size:14px;padding:3px;width:220px",null,null,null,false,"PostfixAutoBlockWhiteListSearchCheck(event)")."</td>
		<td width=1%>". button("{search}","PostfixAutoBlockWhiteListSearch()")."</td>
	</tr>
	</table>
	<div id='PostfixAutoBlockWhiteList-list' style='width:100%;height:298px;overflow:auto'></div>
	</center>
	<script>
		function PostfixAutoBlockWhiteListSearchCheck(e){
			if(checkEnter(e)){PostfixAutoBlockWhiteListSearch();}
		}
		
		function PostfixAutoBlockWhiteListSearch(){
			var se=escape(document.getElementById('PostfixAutoBlockWhiteList-search').value);
			LoadAjax('PostfixAutoBlockWhiteList-list','$page?white-hosts-find='+se);
		
		}
	PostfixAutoBlockWhiteListSearch();
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function hosts_WhiteList_list(){
	
	$search=$_GET["white-hosts-find"];
	$search="*".$search."*";
	$search=str_replace("*","%",$search);
	$search=str_replace("%%","%",$search);
	
	$q=new mysql();
	$sql="SELECT * FROM postfix_whitelist_con WHERE (ipaddr LIKE '$search') OR (hostname LIKE '$search') LIMIT 0,100";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
	

	$html="
	<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:80%'>
	<thead class='thead'>
		<tr>
			<th>".imgtootltip("plus-24.png","{add}","AddHostWhite()")."</th>
			<th colspan=2></th>
		</tr>
	</thead>
	<tbody class='tbody'>";	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["ipaddr"]==null){continue;}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		if($ligne["hostname"]==null){$ligne["hostname"]=gethostbyname($ligne["ipaddr"]);}
		
		$html=$html . "<tr class=$classtr>
		<td><strong style='font-size:13px'><code>{$ligne["ipaddr"]} ({$ligne["hostname"]})</code></td>
		<td width=1%>" . imgtootltip("delete-32.png","{delete}","DelHostWhite('{$ligne["ipaddr"]}')")."</td>
	</tr>";
		
		
	}
	$html=$html."</tbody></table>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}


function popup(){
	if(isset($_GET["font-size"])){$fontsize="font-size:{$_GET["font-size"]}px;";$height="100%";}
	$array["automation"]="Automation";
	$array["popup-domain-white"]="{members}";
	$array["popup-global-white"]="{white list}:{global}";
	$array["popup-hosts"]="{hosts}:{white list}";
	$array["popup-domain-black"]="{domains}:{black list}";
	$array["popup-global-black"]="{black list}:{global}";
	$tpl=new templates();
	$page=CurrentPageName();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="automation"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"postfix.automation.php?tab=yes\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:18px'><span>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "main_config_wbladmin",1150);
		
	
}

function popup_domains(){
	$ldap=new clladp();
	$page=CurrentPageName();
	$tpl=new templates();
	$domain=$ldap->hash_get_all_domains();
	$domain[null]='{all}';
	$t=time();
	$new_item=$tpl->javascript_parse_text("{new_item}");
	$from=$tpl->javascript_parse_text("{sender}");
	$blacklist=$tpl->javascript_parse_text("{blacklist}");
	$whitelist=$tpl->javascript_parse_text("{whitelist}");
	$members=$tpl->javascript_parse_text("{members}");
	$array["white"]='{white list}';
	$array["black"]='{black list}';	
	$array[null]='{all}';
	$field=Field_array_Hash($domain,'selected_domain',null,"SelectDomain()",null,0,"font-size:13px;padding:3px");
	$tpl=new templates();
	
	
	$selected_type="black";
	$buttonClk="BlackListForm";	
	$about2=$tpl->javascript_parse_text("{about2}");
	
	$about_text=$tpl->javascript_parse_text("{blacklist_explain}");
	
	if(isset($_GET["popup-domain-white"])){
			$selected_type="white";
			$buttonClk="WhiteListForm";
			$about_text=$tpl->javascript_parse_text("{whitelist_explain}");
	}
			
$title=$tpl->_ENGINE_parse_body($array[$selected_type]);	


$html="
<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var mem_$t='';
var selected_id=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?MembersSearch=yes',
	dataType: 'json',
	colModel : [
		{display: '$members', name : 'type', width : 718, sortable : false, align: 'left'},
		{display: '$blacklist', name : 'from', width : 132, sortable : true, align: 'center'},
		{display: '$whitelist', recipients : 'category2', width : 132, sortable : false, align: 'center'},
		
		
	],
buttons : [
	{name: '$new_item', bclass: 'add', onpress : $buttonClk},
	{name: '$about2', bclass: 'help', onpress : About2$t},
		],	
	searchitems : [
		{display: '$members', name : 'from'},
		],
	sortname: 'category',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: false,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true
	
	});   
});


      
	function WhiteListForm(){
		YahooWin3(650,'$page?whitelist-form-add=yes&t=$t&domain=$domainSE','$new_item::$domainSE::$title');
	
	}
	function BlackListForm(){
		YahooWin3(650,'$page?blacklist-form-add=yes&t=$t&domain=$domainSE','$new_item::$domainSE::$title');
	
	}	

	var x_AddwlCallback$t=function(obj){
		var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);return;}
		$('#row'+mem_$t).remove();
      } 

function About2$t(){
	alert('$about_text');
}
      
      
function DeleteWhiteList(to,from,md){
      var XHR = new XHRConnection();
      mem_$t=md;
      wbl=0;
      XHR.appendData('RcptDomain','$domainSE');
      XHR.appendData('del_whitelist',from);
      XHR.appendData('recipient',to);
      XHR.appendData('wbl','0');
      XHR.sendAndLoad('$page', 'GET',x_AddwlCallback$t);    
      }
	      
	function DeleteBlackList(to,from,md){
		mem_$t=md;      
		var XHR = new XHRConnection();
	    XHR.appendData('RcptDomain','$domainSE');
	    XHR.appendData('del_whitelist',from);
	    XHR.appendData('recipient',to);
	    XHR.appendData('wbl','1');
	    XHR.sendAndLoad('$page', 'GET',x_AddwlCallback$t);   
	}
</script>
";

echo $html;


}



function MembersSearch(){
	
	$ldap=new clladp();
	$tofind=$_POST["query"];
	if($tofind==null){$tofind='*';}else{$tofind="*$tofind*";}
	$filter="(&(objectClass=userAccount)(|(cn=$tofind)(mail=$tofind)(displayName=$tofind)(uid=$tofind) (givenname=$tofind)))";
	$attrs=array("displayName","uid","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber","sAMAccountName");
	$hash=$ldap->Ldap_search($ldap->suffix,$filter,$attrs,$_POST["rp"]);
	
	$users=new user();
	$number=$hash["count"];
	
	if($number==0){json_error_show("no member");}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $number;
	$data['rows'] = array();
	$color="black";
	for($i=0;$i<$number;$i++){
		$userARR=$hash[$i];
		$uid=$userARR["uid"][0];
		$uidenc=urlencode($uid);
		if($uid=="squidinternalauth"){continue;}
		$js=MEMBER_JS($uid,1,1);
		
		$jsbl="javascript:Loadjs('whitelists.members.php?uid=$uidenc');";
	
		if(($userARR["sn"][0]==null) && ($userARR["givenname"][0]==null)){$userARR["sn"][0]=$uid;}
		$sn=texttooltip($userARR["sn"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		$givenname=texttooltip($userARR["givenname"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		$title=texttooltip($userARR["title"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		$mail=texttooltip($userARR["mail"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		$telephonenumber=texttooltip($userARR["telephonenumber"][0],"{display}:$uid",$js,null,0,"font-size:13px");
		if($userARR["telephonenumber"][0]==null){$userARR["telephonenumber"][0]="&nbsp;";}
		if($userARR["mail"][0]==null){$userARR["mail"][0]="&nbsp;";}
	
	
		$ct=new user($uid);
		$CountDeBlack=count($ct->amavisBlacklistSender);
		$countDeWhite=count($ct->amavisWhitelistSender);
		$id=md5("$uid");
		
		$data['rows'][] = array(
				'id' => $id,
				'cell' => array(
						"<span style='font-size:22px;color:$color'><a href=\"javascript:blur();\" OnClick=\"$jsbl\" style='text-decoration:underline'>{$userARR["givenname"][0]} {$userARR["sn"][0]}</a>
						<br><i style='font-size:14px'><a href=\"javascript:blur();\" OnClick=\"$js\" style='text-decoration:underline'>$uid {$userARR["mail"][0]}</a></i></span>",
						"<span style='font-size:22px;color:$color'>$CountDeBlack</span>",
						"<span style='font-size:22px;color:$color'>$countDeWhite</span>",
						)
		);
		}
		

		
		echo json_encode($data);
	
}


	



function whitelistdom($domain=null){

	$ldap=new clladp();
	if($domain<>null){$domain="*";}
	$hash=$ldap->WhitelistsFromDomain($domain);
	if($GLOBALS["VERBOSE"]){echo "HASH -> " .count($hash)." Items\n<br>";
	print_r($hash);
	}
	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();	
	
	if($_POST["query"]<>null){
		$search=$_POST["query"];
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*?", $search);
	}
	

$c=0;
if(is_array($hash)){	
	while (list ($from, $line) = each ($hash)){
		
		$recipient_domain=$from;
		if(preg_match("#(.+?)@(.+)#",$recipient_domain,$re)){$recipient_domain=$re[2];}
		$ou=$ldap->ou_by_smtp_domain($recipient_domain);		
		while (list ($num, $wl) = each ($line)){
			if($search<>null){ if(!preg_match("#$search#", $wl)){continue;} }
			$c++;
			$id=md5("$from$wl");
			$delete=imgsimple("delete-32.png","{delete}","DeleteWhiteList('$from','$wl','$id');");
			$data['rows'][] = array(
				'id' => $id,
				'cell' => array(
				"<span style='font-size:14px;color:$color'>$ou</span>",
				"<span style='font-size:14px;color:$color'>$wl</span>",
				"<span style='font-size:14px;color:$color'>$from</span>",
				 $delete)
				);
			}
	}
}
	
	$data['total'] = $c;
	echo json_encode($data);
}



function SaveWhiteList(){
	$tpl=new templates();
	$to=$_GET["recipient"];
	$wbl=$_GET["wbl"];
	$RcptDomain=$_GET["RcptDomain"];
	
	$from=$_GET["whitelist"];
	if($to==null){
		$to="*@$RcptDomain";
	}
	
if($from==null){
		echo $tpl->_ENGINE_parse_body('{from}: {error_miss_datas}');return false;
	}	
	
	
	if(substr($to,0,1)=='@'){
		$domain=substr($to,1,strlen($to));
	}else{
		if(strpos($to,'@')>0){
			$tbl=explode('@',$to);
			$domain=$tbl[1];
		}else{
			$domain=$to;
			$to="@$to";
		}
	}
	
	$tbl[0]=str_replace("*","",$tbl[0]);
	$ldap=new clladp();
	$domains=$ldap->hash_get_all_domains();
	if($domains[$domain]==null){
		echo $tpl->javascript_parse_text('{recipient}: {error_unknown_domain} '.$domain);return false;
	}
	
	if($tbl[0]==null){
		$ldap->WhiteListsAddDomain($domain,$from,$wbl);
		return true;
	}else{
		$uid=$ldap->uid_from_email($to);
		if($uid==null){
			echo $tpl->javascript_parse_text('{recipient}: {error_no_user_exists} '.$to);return false;
		}
		$ldap->WhiteListsAddUser($uid,$from,$wbl);
	}
}


function del_whitelist(){
	$ldap=new clladp();
	$to=$_GET["recipient"];
	$from=$_GET["del_whitelist"];
	$ldap->WhiteListsDelete($to,$from,$_GET["wbl"]);
	}






function blacklistdom($domain=null){
	
	$ldap=new clladp();
	if($domain<>null){$domain="*";}
	$hash=$ldap->BlackListFromDomain($domain);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();	
	
	if($_POST["query"]<>null){
		$search=$_POST["query"];
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*?", $search);
	}
	

$c=0;
if(is_array($hash)){	
	while (list ($from, $line) = each ($hash)){
		
		$recipient_domain=$from;
		if(preg_match("#(.+?)@(.+)#",$recipient_domain,$re)){$recipient_domain=$re[2];}
		$ou=$ldap->ou_by_smtp_domain($recipient_domain);		
		while (list ($num, $wl) = each ($line)){
		if($search<>null){
			if(!preg_match("#$search#", $wl)){continue;}
		}
			$c++;
			$id=md5("$from$wl");
			$delete=imgsimple("delete-24.png","{delete}","DeleteBlackList('$from','$wl','$id');");
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
		"<span style='font-size:14px;color:$color'>$ou</span>",
		"<span style='font-size:14px;color:$color'>$wl</span>",
		"<span style='font-size:14px;color:$color'>$from</span>",
		 $delete)
		);
	
		
	}}
}
	
	$data['total'] = $c;
	echo json_encode($data);	


}



class autolearning_spam{
	var $WBLReplicEachMin="6h";
	var $WBLReplicEnable=0;
	var $WBLReplicaHamEnable=0;
	var $WBLReplicSchedule=array();
	var $array_days=array();
	
	
	
	function autolearning_spam(){
		$sock=new sockets();
		$ini=new Bs_IniHandler();
		$this->WBLReplicEachMin=$sock->GET_INFO('WBLReplicEachMin');
		$this->WBLReplicEnable=$sock->GET_INFO('WBLReplicEnable');
		$this->WBLReplicaHamEnable=$sock->GET_INFO('WBLReplicaHamEnable');
		$ini->loadString($sock->GET_INFO('WBLReplicSchedule'));
		$this->WBLReplicSchedule=$ini->_params;
		$this->array_days=array("sunday","monday","tuesday","wednesday","thursday","friday","saturday");
		$this->BuildDefault();
		
		
	}
	
	function BuildDefault(){
		if($this->WBLReplicEachMin==null){$this->WBLReplicEachMin="6h";}
		if($this->WBLReplicEnable==null){$this->WBLReplicEnable=0;}
		if($this->WBLReplicaHamEnable==null){$this->WBLReplicaHamEnable=0;}
		
		while (list ($num, $line) = each ($this->array_days)){
			if($this->WBLReplicSchedule["DAYS"][$line]==null){$this->WBLReplicSchedule["DAYS"][$line]=1;}
			}
		if($this->WBLReplicSchedule["TIME"]["time"]==null){
			$this->WBLReplicSchedule["CRON"]["time"]="3:0";
			$this->WBLReplicSchedule["TIME"]["time"]="3:0";
		}
		reset($this->array_days);
	}
	
	
	function Save(){
		$days=null;
		$sock=new sockets();
		$sock->SET_INFO('WBLReplicEachMin',$this->WBLReplicEachMin);
		$sock->SET_INFO('WBLReplicEnable',$this->WBLReplicEnable);
		$sock->SET_INFO('WBLReplicaHamEnable',$this->WBLReplicaHamEnable);
		
		while (list ($num, $line) = each ($this->array_days)){
			if($this->WBLReplicSchedule["DAYS"][$line]==1){$days[]=$num;}
		}
		if(is_array($days)){
			
			$this->WBLReplicSchedule["CRON"]["days"]=implode(',',$days);
		}else{
			$this->WBLReplicSchedule["CRON"]["days"]=null;
		}
		$this->WBLReplicSchedule["CRON"]["time"]=$this->WBLReplicSchedule["TIME"]["time"];
		
		$ini=new Bs_IniHandler();
		$ini->_params=$this->WBLReplicSchedule;
		$sock->SaveConfigFile($ini->toString(),'WBLReplicSchedule');
		$sock->getfile("delcron:artica-autolearn");
		if($this->WBLReplicSchedule["CRON"]["days"]<>null){
			if(preg_match('#(.+?):(.+)#',$this->WBLReplicSchedule["CRON"]["time"],$re)){
				$sock->getfile("addcron:{$re[2]} {$re[1]} * * {$this->WBLReplicSchedule["CRON"]["days"]} root /usr/share/artica-postfix/bin/artica-learn >/dev/null 2>&1;artica-autolearn");
			}
		}
		
	}
	
}


function whitelist_global_popup(){
	$tpl=new templates();
	$users=new usersMenus();
	$users->LoadModulesEnabled();
	$q=new mysql();
	$sock=new sockets();
	$page=CurrentPageName();
	$EnableAmavisDaemon=$users->EnableAmavisDaemon;
	if(!$users->AMAVIS_INSTALLED){$EnableAmavisDaemon=0;}
	if(!is_numeric($EnableAmavisDaemon)){$EnableAmavisDaemon=0;}	
	if($EnableAmavisDaemon==1){$amavis=new amavis();$max_score=$amavis->main_array["BEHAVIORS"]["sa_tag2_level_deflt"];}	
	$max_score_white_text=$tpl->javascript_parse_text("{max_score_white_text}\\n{score}:$max_score");
	$WhiteListResolvMX=$sock->GET_INFO("WhiteListResolvMX");
	if(!is_numeric($WhiteListResolvMX)){$WhiteListResolvMX=0;}
	$t=time();
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}
	if($_GET["ou"]==null){$_GET["ou"]="master";}	
	$tpl=new templates();
	$add=$tpl->_ENGINE_parse_body("{add}");
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}
	if($_GET["ou"]==null){$_GET["ou"]="master";}
	$popup_title=$tpl->_ENGINE_parse_body("{domains}:{white list}:{global}::{add}");
	$explain=$tpl->javascript_parse_text("{whitelist_global_explain}");
	$wbl_resolv_mx=$tpl->_ENGINE_parse_body("{wbl_resolv_mx}");
	$sender=$tpl->_ENGINE_parse_body("{sender}");
	$score=$tpl->_ENGINE_parse_body("{score}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$new_item=$tpl->_ENGINE_parse_body("{new_item}");
	$about2=$tpl->javascript_parse_text("{about2}");
	
	
	$html="
<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
	<table>
	<tr>
		<td colspan=2 align='right'>
		<table style='width:220px'>
			<tr>
				<td class=legend>$wbl_resolv_mx:</td>
				<td>". Field_checkbox("WhiteListResolvMX",1,$WhiteListResolvMX,"WhiteListResolvMXSave()")."</td>
				<td width=1%>". help_icon("{wbl_resolv_mx_explain}")."</td>
			</tr>
		</table>
	</table>
<script>
var mem_$t='';
var selected_id=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?popup-global-white-list=yes&t=$t&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none4', width : 39, sortable : false, align: 'left'},	
		{display: '$sender', name : 'type', width : 593, sortable : false, align: 'left'},
		{display: '$score', name : 'from', width : 109, sortable : true, align: 'left'},
		{display: '$enabled', recipients : 'category2', width : 72, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'none2', width : 42, sortable : false, align: 'center'},
		
	],
buttons : [
	{name: '$new_item', bclass: 'add', onpress : GlobalWhiteListAdd},
	{name: '$about2', bclass: 'help', onpress : About$t},
		],	
	searchitems : [
		{display: '$sender', name : 'sender'},
		],
	sortname: 'sender',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$popup_title</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true
	
	});   
});
	function GlobalWhiteListAdd(){
		YahooWin4('550','$page?popup-global-white-add=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}&t=$t','{$_GET["hostname"]}::$popup_title');
	}
	
function About$t(){
	alert('$explain');
}
		
	function WhiteListResolvMXSave(){
		var enabled=0;
		if(document.getElementById('WhiteListResolvMX').checked){enabled=1;}
		var XHR = new XHRConnection();
		XHR.appendData('WhiteListResolvMX',enabled);
		XHR.sendAndLoad('$page', 'GET');		
	}
	
	function GlobalWhiteRefresh(){
		$('#table-$t').flexReload();
	
	}
	
	
	
	var x_GlobalWhiteDelete= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}	
		$('#row'+mem_$t).remove();
	}	
	
	var x_GlobalWhiteDisable= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		
	}		
	
	function GlobalWhiteDelete(key){
		mem_$t=key;
		var XHR = new XHRConnection();
		XHR.appendData('GlobalWhiteDelete',key);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');		
		XHR.sendAndLoad('$page', 'GET',x_GlobalWhiteDelete);
		}	
		
	function GlobalWhiteDisable(ID){
		var XHR = new XHRConnection();
		XHR.appendData('ID',ID);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');		
		if(document.getElementById('enabled_'+ID).checked){XHR.appendData('GlobalWhiteDisable',1);}else{XHR.appendData('GlobalWhiteDisable',0);}
		XHR.sendAndLoad('$page', 'GET',x_GlobalWhiteDisable);
	}
	
	
	var x_GlobalScoreModify= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}	
		GlobalWhiteRefresh();
	}		
	
	function GlobalScoreModify(ID,score){
		var score=prompt('$max_score_white_text',score);
		if(score){
			var XHR = new XHRConnection();
			XHR.appendData('GlobalWhiteScore','yes');
			XHR.appendData('score',score);
			XHR.appendData('ID',ID);
			XHR.appendData('hostname','{$_GET["hostname"]}');
			XHR.appendData('ou','{$_GET["ou"]}');
			document.getElementById('score_'+ID).innerHTML=score;		
			XHR.sendAndLoad('$page', 'GET',x_GlobalScoreModify);		
		}
	}
</script>
	";
echo $html;	
	
	
}

function whitelist_global_list(){
	$tpl=new templates();
	$users=new usersMenus();
	$users->LoadModulesEnabled();
	$q=new mysql();
	$EnableAmavisDaemon=$users->EnableAmavisDaemon;
	if(!$users->AMAVIS_INSTALLED){$EnableAmavisDaemon=0;}
	if(!is_numeric($EnableAmavisDaemon)){$EnableAmavisDaemon=0;}	
	if($EnableAmavisDaemon==1){
		$amavis=new amavis();
		$max_score=$amavis->main_array["BEHAVIORS"]["sa_tag2_level_deflt"];
	}	
	
	$max_score_white_text=$tpl->javascript_parse_text("{max_score_white_text}\\n{score}:$max_score");
	
	$search='%';
	$table="postfix_global_whitelist";
	$page=1;
	$FORCE_FILTER="AND `hostname`='{$_GET["hostname"]}' ";
	$total=0;
	$MyPage=CurrentPageName();
	if(!$q->TestingConnection()){json_error_show("Connection to MySQL server failed");}
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("$table empty");}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show($q->mysql_error." LINE:".__LINE__);}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show($q->mysql_error." LINE:".__LINE__);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();	
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error." LINE:".__LINE__);}
	if(mysql_num_rows($results)==0){json_error_show("no row");}
	$score=0;
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$allmxText=null;
		$disable=Field_checkbox("enabled_{$ligne["ID"]}",1,$ligne["enabled"],"GlobalWhiteDisable('{$ligne["ID"]}')");
		$delete=imgsimple("delete-32.png","{delete}","GlobalWhiteDelete('{$ligne["ID"]}')");
		$modifyScore="<a href=\"javascript:blur();\" OnClick=\"javascript:GlobalScoreModify('{$ligne["ID"]}','$score');\" style='text-decoration:underline;font-weight:bold'>";
		$allmx=unserialize($ligne["allmx"]);
		if($score==0){$score="{no}";}else{$score="-{$ligne["score"]}";}
		$icon="datasource-32.png";
		if($EnableAmavisDaemon==0){$score="{disabled}";}
		if(count($allmx)>0){
			$allmxText="<div style='font-size:11px'><i>".@implode(", ", $allmx)."</i></div>";
		}
		
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
			"<img src='img/$icon'>",
			"<span style='font-size:16px'><code>{$ligne["sender"]}</code></span>$allmxText",
			$tpl->_ENGINE_parse_body("<span style='font-size:14px'><strong style='font-size:14px' id='score_{$ligne["ID"]}'>$modifyScore$score</a></strong></span>"),
			"$disable",
			$delete )
		);		
	}
	
	
	echo json_encode($data);	
}

function blacklist_global_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$add=$tpl->_ENGINE_parse_body("{add}");
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}
	if($_GET["ou"]==null){$_GET["ou"]="master";}
	$explain=$tpl->_ENGINE_parse_body("{blacklist_global_explain}");
	$sender=$tpl->_ENGINE_parse_body("{sender}");
	$score=$tpl->_ENGINE_parse_body("{score}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$new_item=$tpl->_ENGINE_parse_body("{new_item}");	
	$t=time();
	
	$popup_title=$tpl->_ENGINE_parse_body("{domains}:{black list}:{global}");
$html="<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
	<div class=text-info>$explain</div></td>
	
<script>
var mem_$t='';
var selected_id=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?popup-global-black-list=yes&t=$t&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none4', width : 39, sortable : false, align: 'left'},	
		{display: '$sender', name : 'type', width : 649, sortable : false, align: 'left'},
		{display: '$enabled', recipients : 'category2', width : 72, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'none2', width : 31, sortable : false, align: 'left'},
		
	],
buttons : [
	{name: '$new_item', bclass: 'add', onpress : GlobalBlackListAdd},
		],	
	searchitems : [
		{display: '$sender', name : 'sender'},
		],
	sortname: 'sender',
	sortorder: 'desc',
	usepager: true,
	title: '$popup_title',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 859,
	height: 350,
	singleSelect: true
	
	});   
});

	function GlobalBlackListAdd(){
		YahooWin4('550','$page?popup-global-black-add=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','{$_GET["hostname"]}::$popup_title');
	}
		
	function GlobalBlackRefresh(){
		$('#table-$t').flexReload();
	}
	
	var x_GlobalBlackDelete$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}	
		$('#row'+mem_$t).remove();
	}	
	
	var x_GlobalBlackDisable= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		
	}		
	
	function GlobalBlackDelete(key){
		var XHR = new XHRConnection();
		mem_$t=key;
		XHR.appendData('GlobalBlackDelete',key);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');		
		XHR.sendAndLoad('$page', 'GET',x_GlobalBlackDelete$t);
		}	
		
	function GlobalBlackDisable(ID){
		var XHR = new XHRConnection();
		XHR.appendData('ID',ID);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');		
		if(document.getElementById('enabled_'+ID).checked){XHR.appendData('GlobalBlackDisable',1);}else{XHR.appendData('GlobalBlackDisable',0);}
		XHR.sendAndLoad('$page', 'GET',x_GlobalBlackDisable);
	}	
	
	</script>
	";
	
	echo $html;
	
}

function blacklist_global_list(){
	
	$q=new mysql();
	$search='%';
	$table="postfix_global_blacklist";
	$page=1;
	$FORCE_FILTER="AND `hostname`='{$_GET["hostname"]}' ";
	$total=0;
	$MyPage=CurrentPageName();
	$database="artica_backup";
	if(!$q->TestingConnection()){json_error_show("Connection to mysql failed with TestingConnection()",1);}
	if(!$q->TABLE_EXISTS($table, $database)){json_error_show("postfix_global_blacklist No such table...",1);}
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("postfix_global_blacklist is empty...",1);}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show($q->mysql_error." LINE:".__LINE__);}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show($q->mysql_error." LINE:".__LINE__);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();	
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error." LINE:".__LINE__);}
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$disable=Field_checkbox("enabled_{$ligne["ID"]}",1,$ligne["enabled"],"GlobalBlackDisable('{$ligne["ID"]}')");
		$delete=imgtootltip("delete-24.png","{delete}","GlobalBlackDelete('{$ligne["ID"]}')");
		$icon="datasource-32.png";
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
			"<img src='img/$icon'>",
			"<span style='font-size:14px'><code>{$ligne["sender"]}</code></span>",
			"$disable",
			$delete )
		);		
	}	

echo json_encode($data);	
	
}


function blacklist_global_add(){
	$tpl=new templates();
	$page=CurrentPageName();

	$html="
	<div id='globalblack-smtp-div'></div>
	<div class=text-info style='font-size:16px'>{blacklist_global_add_explain}</div>
	<div style='width:98%' class=form>
		<textarea id='globalblack-servers-container' 
			style='width:100%;border:0px;height:350px;overflow:auto;font-size:16px !important'></textarea>
		<div style='text-align:right'>". button("{add}","GlobalBlackSave()",22)."</div>
	</div>
	<script>
	
	var x_GlobalBlackSave= function (obj) {
		document.getElementById('globalblack-smtp-div').innerHTML='';
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		YahooWin4Hide();
		GlobalBlackRefresh();
	}			
		
	function GlobalBlackSave(){
		var XHR = new XHRConnection();
		XHR.appendData('popup-global-black-save',document.getElementById('globalblack-servers-container').value);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');
		AnimateDiv('globalblack-smtp-div');
		XHR.sendAndLoad('$page', 'POST',x_GlobalBlackSave);		
		}
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function whitelist_global_add(){
	$tpl=new templates();
	$page=CurrentPageName();

	$html="
	<div id='globalwhite-smtp-div'></div>
	<div class=text-info style='font-size:16px'>{whitelist_global_add_explain}</div>
	<div style='width:98%' class=form>
		<textarea id='globalwhite-servers-container' style='width:100%;height:350px;border:0px;overflow:auto;font-size:16px !important'></textarea>
		<div style='text-align:right'>". button("{add}","GlobalWhiteSave()",22)."</div>
	</div>
	
	<script>
	
	var x_GlobalWhiteSave= function (obj) {
		document.getElementById('globalwhite-smtp-div').innerHTML='';
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		YahooWin4Hide();
		GlobalWhiteRefresh();
	}			
		
	function GlobalWhiteSave(){
		var XHR = new XHRConnection();
		XHR.appendData('popup-global-white-save',document.getElementById('globalwhite-servers-container').value);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');
		AnimateDiv('globalwhite-smtp-div');
		XHR.sendAndLoad('$page', 'POST',x_GlobalWhiteSave);		
		}
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);	
}



function blacklist_global_save(){
	
	$hostname=$_POST["hostname"];
	$datas=explode("\n",$_POST["popup-global-black-save"]);
	$prefix="INSERT INTO postfix_global_blacklist (sender,hostname) VALUES ";
	
	if(!is_array($datas)){echo "No data";return;}
	while (list ($num, $words) = each ($datas) ){	
		if(trim($words)==null){continue;}
		$words=addslashes($words);
		$ws[]="('$words','$hostname')";
	}
	
	$q=new mysql();
	
	if(!$q->TestingConnection()){
		echo "Testing connection to MySQL Server failed with error:\n".$q->mysql_error."\n";
		writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	if(!$q->TABLE_EXISTS("postfix_global_blacklist", "artica_backup")){$q->BuildTables();}
	$sql=$prefix.@implode(",",$ws);
	$q->QUERY_SQL($sql,"artica_backup");
	
	
	
	writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
	if(!$q->ok){
		echo $q->mysql_error."\n".$sql."\n";
		writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-smtp-sender-restrictions={$_GET["hostname"]}");	
	
}

function whitelist_global_save(){
	$hostname=$_POST["hostname"];
	$datas=explode("\n",$_POST["popup-global-white-save"]);
	$prefix="INSERT INTO postfix_global_whitelist (sender,hostname) VALUES ";
	
	if(!is_array($datas)){echo "No data";return;}
	while (list ($num, $words) = each ($datas) ){	
		if(trim($words)==null){continue;}
		$words=addslashes($words);
		$ws[]="('$words','$hostname')";
	}
	
	$q=new mysql();
	if(!$q->TestingConnection()){
		echo "Connection to MySQL server failed\n";
		return;
	}
	
	if(!$q->TABLE_EXISTS("postfix_global_whitelist", "artica_backup")){$q->BuildTables();}
	$sql=$prefix.@implode(",",$ws);
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n".$sql."\n";return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-whitelisted-global=yes");	
	
}

function blacklist_global_delete(){
	if(!is_numeric($_GET["GlobalBlackDelete"])){return null;}
	$sql="DELETE FROM postfix_global_blacklist WHERE ID='{$_GET["GlobalBlackDelete"]}'";
	$q=new mysql();
	
	if(!$q->TestingConnection()){
		echo "Testing connection to MySQL Server failed with error:\n".$q->mysql_error."\n";
		writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		return;
	}	
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n".$sql."\n";return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-smtp-sender-restrictions={$_GET["hostname"]}");	
}
function whitelist_global_delete(){
	if(!is_numeric($_GET["GlobalWhiteDelete"])){return null;}
	$sql="DELETE FROM postfix_global_whitelist WHERE ID='{$_GET["GlobalWhiteDelete"]}'";
	$q=new mysql();
	
	if(!$q->TestingConnection()){
		echo "Connection to MySQL server failed\n";
		return;
	}	
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n".$sql."\n";return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-whitelisted-global=yes");	
}

function whitelist_global_score(){
	if(!is_numeric($_GET["ID"])){return null;}
	$sql="UPDATE postfix_global_whitelist SET score='{$_GET["score"]}' WHERE ID='{$_GET["ID"]}'";
	$q=new mysql();
	if(!$q->TestingConnection()){
		echo "Connection to MySQL server failed\n";
		return;
	}	
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n".$sql."\n";return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-whitelisted-global=yes");	
}


function whitelist_global_disable(){
	if(!is_numeric($_GET["ID"])){return null;}
	$sql="UPDATE postfix_global_whitelist SET enabled='{$_GET["GlobalWhiteDisable"]}' WHERE ID='{$_GET["ID"]}'";
	$q=new mysql();
	if(!$q->TestingConnection()){
		echo "Connection to MySQL server failed\n";
		return;
	}	
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n".$sql."\n";return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-whitelisted-global=yes");
}

function blacklist_global_disable(){
	if(!is_numeric($_GET["ID"])){return null;}
	$sql="UPDATE postfix_global_blacklist SET enabled='{$_GET["GlobalBlackDisable"]}' WHERE ID='{$_GET["ID"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n".$sql."\n";return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-smtp-sender-restrictions={$_GET["hostname"]}");
}

function WhiteListResolvMXSave(){
	$sock=new sockets();
	$sock->SET_INFO("WhiteListResolvMX",$_GET["WhiteListResolvMX"]);
	$sock->getFrameWork("cmd.php?WhiteListResolvMX=yes");
}


?>