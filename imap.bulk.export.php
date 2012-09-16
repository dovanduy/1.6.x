<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsMailBoxAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["events"])){events();exit;}
	if(isset($_POST["BULK_IMAP_SERVER"])){SAVE();exit;}
	if(isset($_GET["events-table"])){events_table();exit;}
	if(isset($_GET["ShowID"])){ShowID();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{bulk_imap_export}");
	$html="YahooWin3('650','$page?tabs=yes','$title');";
	echo $html;
	
}

function events(){
	$page=CurrentPageName();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$subject=$tpl->_ENGINE_parse_body("{subject}");	
	$events=$tpl->_ENGINE_parse_body("{events}");	

	$TB_WIDTH=550;
	$TB2_WIDTH=400;
	if(isset($_GET["full-size"])){
		$TB_WIDTH=872;
		$TB2_WIDTH=610;
	}
	$t=time();
	
	$buttons="
	buttons : [
	{name: '$new_category', bclass: 'Catz', onpress : AddCatz},
	
		],	";
	$html="
	<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#events-table-$t').flexigrid({
	url: '$page?events-table=yes',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width :127, sortable : true, align: 'center'},
		{display: '$subject', name : 'subject', width :429, sortable : true, align: 'left'},
		
	],

	searchitems : [
		{display: '$subject', name : 'subject'},
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 600,
	height: 390,
	singleSelect: true
	
	});   
});

	function ZoomExport(ID){
		 YahooWin6('750','$page?ShowID='+ID,'Zoom::'+ID);
	}
</script>";
	
	echo $html;		
	
	
}

function tabs(){
	
	$page=CurrentPageName();
	$array["popup"]="{parameters}";
	$array["events"]="{events}";
	$height="550px";	
	$style="style='font-size:14px'";

	while (list ($num, $ligne) = each ($array) ){
		$html[]="<li><a href=\"$page?$num=yes&hostname=$hostname\"><span $style>$ligne</span></a></li>\n";
	}	
	
	$tab="<div id=main_bulkexport style='width:100%;height:$height;overflow:auto;$styleG'><ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_bulkexport').tabs();
			});
		</script>";		
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($tab);
	
}

function popup(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$array=unserialize(base64_decode($sock->GET_INFO("ImapBulkImapExport")));
	$ImapBulkImapExportEnable=$sock->GET_INFO("ImapBulkImapExportEnable");
	if(!is_numeric($ImapBulkImapExportEnable)){$ImapBulkImapExportEnable=0;}
	if(!is_numeric($array["BULK_IMAP_ARTICA_PORT"])){$array["BULK_IMAP_ARTICA_PORT"]=9000;}
	if(!is_numeric($array["BULK_IMAP_PORT"])){$array["BULK_IMAP_PORT"]=143;}
	if($array["BULK_IMAP_ARTICA_ADMIN"]==null){$array["BULK_IMAP_ARTICA_ADMIN"]="Manager";}
	
	$html="
	<table style='width:100%'>
	<tbody>
	<tr>
	<td valign='top'><a href='http://www.artica.fr/download/artica-bulk-imap-export.pdf'><img src='img/pdf-64.png'><center>{help}</center></a>
	<td valign='top'><div class=explain>{bulk_imap_export_explain}</div></td>
	</tr>
	</tbody>
	</table>
		<div id='BULK_IMAP_DIV'>
		<input type='hidden' id='BULK_IMAP_SCHEDULE' name='BULK_IMAP_SCHEDULE' value='{$array["BULK_IMAP_SCHEDULE"]}'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend nowrap>{enable_bulk_imap_task}:</td>
		<td>". Field_checkbox("ImapBulkImapExportEnable",1,$ImapBulkImapExportEnable,"ImapBulkImapExportEnableCheck()")."</td>
	</tr>	
	<tr>
		<td class=legend nowrap>{imap_server}:</td>
		<td>". Field_text("BULK_IMAP_SERVER",$array["BULK_IMAP_SERVER"],"font-size:14px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend nowrap>{imap_server_port}:</td>
		<td>". Field_text("BULK_IMAP_PORT",$array["BULK_IMAP_PORT"],"font-size:14px;width:60px")."</td>
	</tr>	
	<tr>
		<td class=legend>{zarafa_server}:</td>
		<td>". Field_checkbox("BULK_IMAP_ZARAFA",1,$array["BULK_IMAP_ZARAFA"])."</td>
	</tr>
	<tr>
		<td nowrap width=1% align='right' class=legend>
			<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('cron.php?field=BULK_IMAP_SCHEDULE')\" 
			style='font-size:13px;text-decoration:underline;color:black' id='scheduleAID2'>{schedule}:</a></td>
		<td>". Field_text("BULK_IMAP_SCHEDULE",$array["BULK_IMAP_SCHEDULE"],"font-size:14px;width:90px")."</td>
		
	</tr>			
	<tr>
		<td class=legend nowrap>{Remote_server_is_an_Artica_server}:</td>
		<td>". Field_checkbox("BULK_IMAP_ARTICA",1,$array["BULK_IMAP_ARTICA"],"BULK_IMAP_ARTICA_CHECK()")."</td>
	</tr>		
	<tr>
		<td class=legend>{web_ssl_console_port}:</td>
		<td>". Field_text("BULK_IMAP_ARTICA_PORT",$array["BULK_IMAP_ARTICA_PORT"],"font-size:14px;width:60px")."</td>
	</tr>
	<tr>
		<td class=legend>{username}:</td>
		<td>". Field_text("BULK_IMAP_ARTICA_ADMIN",$array["BULK_IMAP_ARTICA_ADMIN"],"font-size:14px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend>{password}:</td>
		<td>". Field_password("BULK_IMAP_ARTICA_PASS",$array["BULK_IMAP_ARTICA_PASS"],"font-size:14px;width:90px")."</td>
	</tr>
		<tr><td colspan=2 align='right'><hr>". button("{apply}", "BULK_IMAP_SAVE()")."</td></tr>
	</tbody>
	</table>	
	</div>
	<script>
	
		var x_BULK_IMAP_SAVE= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);}
			RefreshTab('main_bulkexport');
		}
	
		function BULK_IMAP_SAVE(){
			var XHR=XHRParseElements('BULK_IMAP_DIV');
			AnimateDiv('BULK_IMAP_DIV');
			XHR.sendAndLoad('$page', 'POST',x_BULK_IMAP_SAVE);
			}
			
			
	function ImapBulkImapExportEnableCheck(){
			document.getElementById('BULK_IMAP_SERVER').disabled=true;
			document.getElementById('BULK_IMAP_PORT').disabled=true;
			document.getElementById('BULK_IMAP_ARTICA').disabled=true;
			document.getElementById('BULK_IMAP_ZARAFA').disabled=true;
			
			
			
			if(document.getElementById('ImapBulkImapExportEnable').checked){
				document.getElementById('BULK_IMAP_SERVER').disabled=false;
				document.getElementById('BULK_IMAP_PORT').disabled=false;	
				document.getElementById('BULK_IMAP_ARTICA').disabled=false;
				document.getElementById('BULK_IMAP_ZARAFA').disabled=false;		
			}
		BULK_IMAP_ARTICA_CHECK();
			
	
	}
			
	function BULK_IMAP_ARTICA_CHECK(){
			document.getElementById('BULK_IMAP_ARTICA_PORT').disabled=true;
			document.getElementById('BULK_IMAP_ARTICA_ADMIN').disabled=true;
			document.getElementById('BULK_IMAP_ARTICA_PASS').disabled=true;
	
	
		if(document.getElementById('BULK_IMAP_ARTICA').checked){
			if(document.getElementById('ImapBulkImapExportEnable').checked){
				document.getElementById('BULK_IMAP_ARTICA_PORT').disabled=false;
				document.getElementById('BULK_IMAP_ARTICA_ADMIN').disabled=false;
				document.getElementById('BULK_IMAP_ARTICA_PASS').disabled=false;
				}
			}
		
		}
	
	ImapBulkImapExportEnableCheck();
	
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function SAVE(){
	$sock=new sockets();
	$sock->SET_INFO("ImapBulkImapExportEnable", $_POST["ImapBulkImapExportEnable"]);
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "ImapBulkImapExport");
	$sock->getFrameWork("services.php?BULK_IMAP_SCHEDULE=yes");
}
function events_table(){
	
	
//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	
	$search='%';
	$table="exports";
	$page=1;
	$ORDER="ORDER BY zDate DESC";
	
	
	if($q->COUNT_ROWS($table,"artica_events")==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
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
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){
		
	}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$original_date=$ligne["zDate"];
		$ligne["zDate"]=str_replace($tt,'{today}',$ligne["zDate"]);	
		$original_date=$ligne["zDate"];
		$ligne["zDate"]=str_replace($tt,'{today}',$ligne["zDate"]);
		$time=strtotime($original_date);
		$distanceOfTimeInWords=distanceOfTimeInWords($time,time());
		
		
		
			
	$link="<a href=\"javascript:blur();\" OnClick=\"javascript:ZoomExport({$ligne["ID"]})\"
		style='font-size:13px;text-decoration:underline'>";
	if(trim($ligne["description"])==null){$link=null;}
	
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array($ligne["zDate"],"$link{$ligne["subject"]}</a><div style='font-size:11px'>$distanceOfTimeInWords</div>" )
		);
	}
	
	
echo json_encode($data);		
	

	
}

function ShowID(){
	$id=$_GET["ShowID"];
	if(!is_numeric($id)){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("<H2>{error}</H2>");
		return;
		
	}
	$sql="SELECT * FROM exports WHERE ID=$id";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	
	$subject=$ligne["subject"];
	
	
	if(preg_match("#<body>(.+?)</body>#is",$ligne["description"],$re)){
		$content=$re[1];
	}
	
	;
	if($content==null){
		
		if(strpos($ligne["description"],"<td")>0){$html=true;}
		$tbl=explode("\n",$ligne["description"]);
			if(is_array($tbl)){
				while (list ($index, $line) = each ($tbl) ){
				if($html){
					$content=$content .$line;
				}else{
					$content=$content."<div><code>". htmlentities(stripslashes($line))."</code></div>";
				}
			
				}
			}
		}
	
	$html="<H3>$subject</H3>
	<hr>
	<div style='width:92%;height:450px;overflow:auto;margin:5px;padding:5px'>
	$content
	</div>
	
	
	";
	
	echo $html;
	
	
}