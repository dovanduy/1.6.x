<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.dansguardian.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsSquidAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}").");";
		exit;
		
	}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["tracker"])){tracker();exit;}
	if(isset($_GET["tracker-list"])){tracker_list();exit;}
	
	if(isset($_POST["www"])){save();exit;}
	js();
	
	function js(){
		header("content-type: application/x-javascript");
		$page=CurrentPageName();
		$tpl=new templates();
		$width=995;
		$statusfirst=null;
		$title=$tpl->_ENGINE_parse_body("{official_categories_support}");
		$start="YahooWinBrowse('700','$page?tabs=yes','$title');";
		$html="$start";
		echo $html;
	
	}	
	
function tabs(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$q=new mysql();
	$array["popup"]='{submit}';
	$array["tracker"]='{tracker}';
	$fontsize=16;
	
	
	while (list ($num, $ligne) = each ($array) ){
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
	}
	
	echo build_artica_tabs($tab, "categories-ticket")."";
	
	
	
	}	
	
	
function popup() {
	$page=CurrentPageName();
	$tpl=new templates();
	$dans=new dansguardian_rules();
	$cats=$dans->LoadBlackListes();
	$t=time();
	while (list ($num, $ligne) = each ($cats) ){$newcat[$num]=$num;}
	$newcat[null]="{select}";
	$field_category=Field_array_Hash($newcat,"category-add$t",null,null,"style:font-size:22px")."</span>";
	
	
	$html="<div style='font-size:16px' class=text-info>{bad_category_explain}</div>
	<div style='width:95%;padding:15px' class=form>
	<center>
		<table style='width:100%'>
		<tr>
			<td style='font-size:22px'>{websites}:</td>
			<td>
				<textarea style='margin-top:5px;font-family:Courier New;font-weight:bold;width:99%;height:150px;border:5px solid #8E8E8E;overflow:auto;font-size:22px !important' id='www-$t'></textarea>
			</td>
		</tr>
		<tr>
			<td style='font-size:22px' nowrap>{should_categorized_to}:</td>
			<td>$field_category</td>
		</tr>
		<tr>
			<td colspan=2 align='right'><hr>". button("{submit}","Save$t();",30)."</td>
		</tr>
	</table>
	</center>
	</div>		
	<div id='results-$t'></div>		
	<script>

	var xSave$t= function (obj) {
		var res=obj.responseText;
		if (res.length>0){ alert(res); }
		ExecuteByClassName('SearchFunction');
	}	
	
	function Save$t(){
		var XHR = new XHRConnection();
		var cat=document.getElementById('category-add$t').value;
		if(cat.length==0){return;}
		XHR.appendData('category',cat);
		XHR.appendData('www',document.getElementById('www-$t').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	}	
	
	
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}
function save(){
	$tpl=new templates();
	$www=$_POST["www"];
	$category=$_POST["category"];
	$q=new mysql_squid_builder();
	
	
	
	$sql="CREATE TABLE IF NOT EXISTS `catztickets` (
	`sitename` varchar(128) NOT NULL,
	`category` varchar(90) NOT NULL,
	`zDate` datetime NOT NULL,
	`zDate2` datetime NOT NULL,
	`status` smallint(1) NOT NULL,
	KEY `sitename` (`sitename`),
	KEY `category` (`category`),
	KEY `status` (`status`),
	KEY `zDate` (`zDate`),
	KEY `zDate2` (`zDate2`)
	) ENGINE=MYISAM;";	
	
	$q->QUERY_SQL($sql);
	
	$TR=explode("\n",$www);
	while (list ($none, $ww) = each ($TR) ){
		$www=$q->WebsiteStrip($ww);
		if($www==null){continue;}
		$date=date("Y-m-d H:i:s");
		$q->QUERY_SQL("INSERT INTO catztickets (sitename,zDate,status,category,zDate2) VALUES ('$www','$date','0','$category','$date')");
		$z[]=$www;
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	if(count($z)>0){
		echo $tpl->javascript_parse_text("{succes}")."\n".@implode("\n", $z);
		$sock=new sockets();
		$sock->getFrameWork("squid.php?export-category-tickets=yes");
	}
}


function tracker(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$websites=$tpl->_ENGINE_parse_body("{websites}");
	$status=$tpl->_ENGINE_parse_body("{status}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	$tracker=$tpl->_ENGINE_parse_body("{tracker}");
	$t=time();
	
	$buttons="	buttons : [
	{name: '$new_port', bclass: 'add', onpress : HTTPSafePortSSLAdd},

	],";
	$buttons=null;
	$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>

function Start$t(){
	$(document).ready(function(){
		$('#flexRT$t').flexigrid({
			url: '$page?tracker-list=yes',
			dataType: 'json',
			colModel : [
				{display: '$zdate', name : 'zDate', width : 129, sortable : true, align: 'left'},
				{display: '$websites', name : 'sitename', width : 201, sortable : true, align: 'left'},
				{display: '$category', name : 'category', width : 201, sortable : true, align: 'left'},
				{display: '$status', name : 'status', width : 40, sortable : true, align: 'center'},
				],$buttons
	
				searchitems : [
					{display: '$websites', name : 'sitename'},
					{display: '$category', name : 'category'},
				],	
	
			sortname: 'zDate',
			sortorder: 'desc',
			usepager: true,
			title: '$tracker',
			useRp: false,
			rp: 15,
			showTableToggleBtn: false,
			width: '95%',
			height: 450,
			singleSelect: true
				});
	});
}


function REFRESH_HTTP_SAFE_PORTS_SSL_LIST(){
$('#$t').flexReload();
}

var x_HTTPSafePortSSLAdd=function (obj) {
var results=obj.responseText;
if (results.length>0){alert(results);}
REFRESH_HTTP_SAFE_PORTS_SSL_LIST();
}

function HTTPSafePortSSLAdd(){
var XHR = new XHRConnection();
var explain='';
var value=prompt('$HTTP_ADD_SAFE_PORTS_EXPLAIN');
if(!value){return;}
explain=prompt('$GIVE_A_NOTE','my specific web port...');
if(value){
XHR.appendData('http-safe-ports-ssl-add',value);
XHR.appendData('http-safe-ports-ssl-explain',explain);
XHR.sendAndLoad('$page', 'GET',x_HTTPSafePortSSLAdd);
}
}


var x_HttpSafePortSSLDelete= function (obj) {
var tempvalue=obj.responseText;
if(tempvalue.length>3){alert(tempvalue);return;}
$('#row'+rowSquidPosrt).remove();
}

function HttpSafePortSSLDelete(enc,id){
rowSquidPosrt=id;
var XHR = new XHRConnection();
XHR.appendData('http-safe-ports-ssl-del',enc);
XHR.sendAndLoad('$page', 'GET',x_HttpSafePortSSLDelete);
}
	

Start$t();
</script>";

echo $tpl->_ENGINE_parse_body($html);


}


function tracker_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	$q=new mysql_squid_builder();
	$search='%';
	$table="catztickets";


	$page=1;
	$FORCE_FILTER="";
	if(!$q->TABLE_EXISTS("$table")){json_error_show("$table No such table");}
	if($q->COUNT_ROWS("$table",'artica_events')==0){json_error_show("$table No data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$q2=new mysql();


	$searchstring=string_to_flexquery();

	if($searchstring<>null){

		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,'artica_events'));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,'artica_events');
	if(!$q->ok){json_error_show("$table: $q->mysql_error",2);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$today=date('Y-m-d');
	if(!$q->ok){json_error_show($q->mysql_error,2);}

	if(mysql_num_rows($results)==0){
		json_error_show($sql,2);}


		while ($ligne = mysql_fetch_assoc($results)) {
			$status="time-32.png";
			$ligne["zDate"]=str_replace($today,"{today}",$ligne["zDate"]);
			$ligne["zDate"]=$tpl->_ENGINE_parse_body("{$ligne["zDate"]}");
			$id=md5(serialize($ligne));
			
			if($ligne["status"]==0){$status="time-32.png";}
			if($ligne["status"]==1){$status="check-32.png";}
			
			
			
			$data['rows'][] = array(
					'id' => $id,
					'cell' => array(
							"<span style='font-size:14px;'>{$ligne["zDate"]}</span>",
							"<span style='font-size:14px;'>{$ligne["sitename"]}</a></span>",
							"<span style='font-size:14px;'>{$ligne["category"]}</a></span>",
							"<span style='font-size:14px;'><img src='img/$status'></a></span>",
							
					)
		);
		}
echo json_encode($data);
}