<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.httpd.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	
	
$usersmenus=new usersMenus();
if($usersmenus->AsArticaAdministrator==false){die();}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["tri"])){echo events_table();exit;}
if(isset($_GET["events-table"])){events_table();exit;}
if(isset($_GET["ShowID"])){ShowID();exit;}
if(isset($_GET["delete_all_items"])){delete_all_items();exit;}
if(isset($_POST["empty-table"])){delete_all_items();exit;}

js();	


		
function js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{artica_events}');
	$start="artica_events_start()";
	
	if(isset($_GET["filterby"])){
		$filterby="&tri=yes&LockBycontext={$_GET["filterby"]}";
	}
	
	if(isset($_GET["in-div"])){
		$start="LoadAjax('{$_GET["in-div"]}','$page?popup=yes$filterby')";
	}
	
	if(isset($_GET["in-front-ajax"])){
		$start="artica_events_start2()";
	}
	if(isset($_GET["external-events"])){
		$start="articaShowEvent({$_GET["external-events"]})";
		
	}
	
	
	$html="
	
	function artica_events_start(){
	 	YahooWin5('750','$page?popup=yes&without-tri={$_GET["without-tri"]}','$title');
	}
	
	function artica_events_start2(){
		$('#BodyContent').load('$page?popup=yes$filterby');
	}
	 
	 function tripar(){
	 	var context=document.getElementById('context').value;
	 	var process=document.getElementById('process').value;
	 	var se=document.getElementById('event-search').value;
	 	se=escape(se);
	 	LoadAjax('articaevents','$page?tri=yes&context='+context+'&process='+process+'&pattern='+se);
	 
	}
	
	function EventSearchCheck(e){
		if(checkEnter(e)){tripar();}
	}
	
	function articaShowEvent(ID){
		 YahooWin6('750','$page?ShowID='+ID,'$title::'+ID);
	}
	 
	
	
	$start;";
	
	echo $html;	
	
}

function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$context=$tpl->_ENGINE_parse_body("{context}");	
	$events=$tpl->_ENGINE_parse_body("{events}");	
	$empty=$tpl->_ENGINE_parse_body("{empty}");	
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");	
	$TB_HEIGHT=450;
	$TB_WIDTH=550;
	$TB2_WIDTH=400;
	if(isset($_GET["full-size"])){
		$TB_WIDTH=872;
		$TB2_WIDTH=610;
	}
	
	if(isset($_GET["full-medium"])){
		$TB_WIDTH=845;
		$TB_HEIGHT=400;
		$TB2_WIDTH=580;
		
	}
	
	$t=time();
	
	$buttons="
	buttons : [
	{name: '$empty', bclass: 'Delz', onpress : EmptyEvents},
	
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
		{display: '$context', name : 'context', width :80, sortable : true, align: 'center'},
		{display: '$events', name : 'events', width : $TB2_WIDTH, sortable : false, align: 'left'},
	],
	$buttons

	searchitems : [
		{display: '$events', name : 'text'},
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

	function articaShowEvent(ID){
		 YahooWin6('750','$page?ShowID='+ID,'$title::'+ID);
	}
	
	var x_EmptyEvents= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#events-table-$t').flexReload();
		//$('#grid_list').flexOptions({url: 'newurl/'}).flexReload(); 
		// $('#fgAllPatients').flexOptions({ query: 'blah=qweqweqwe' }).flexReload();
		
	}		
	
	function EmptyEvents(){
		if(confirm('$empty_events_text_ask')){
			var XHR = new XHRConnection();
			XHR.appendData('empty-table','yes');
			XHR.sendAndLoad('$page', 'POST',x_EmptyEvents);			
		}
	
	}
	
</script>";
	
	echo $html;	
	
}

function delete_all_items(){
	$sql="TRUNCATE TABLE events";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_events");	
	if(!$q->ok){echo $q->mysql_error;}
	
}

function events_table(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	
	$search='%';
	$table="events";
	$page=1;
	$ORDER="ORDER BY zDate DESC";
	
	$total=0;
	if($q->COUNT_ROWS($table,"artica_events")==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND ((`{$_POST["qtype"]}` LIKE '$search') OR (`content` LIKE '$search'))";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
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
		if($ligne["process"]==null){$ligne["process"]="{unknown}";}
		$original_date=$ligne["zDate"];
		$ligne["zDate"]=str_replace($tt,'{today}',$ligne["zDate"]);	
		if($ligne["process"]==null){$ligne["process"]="{unknown}";}
		$original_date=$ligne["zDate"];
		$ligne["zDate"]=str_replace($tt,'{today}',$ligne["zDate"]);
		
		if(preg_match("#\[.+?\]:\s+\[.+?\]:\s+\((.+?)\)\s+:(.+)#",$ligne["text"],$re)){$ligne["text"]=$re[2];$computer=$re[1];}
		if(preg_match("#\[.+?\]:\s+\[.+?\]:\s+\((.+?)\)\:\s+(.+)#",$ligne["text"],$re)){$ligne["text"]=$re[2];$computer=$re[1];}
		if(preg_match("#\[.+?\]:\s+\[.+?\]:\s+\((.+?)\)\s+(.+)#",$ligne["text"],$re)){$ligne["text"]=$re[2];$computer=$re[1];}
		if(preg_match("#\[.+?\]:\s+\[.+?\]:\((.+?)\)\s+(.+)#",$ligne["text"],$re)){$ligne["text"]=$re[2];$computer=$re[1];}
		if(preg_match("#\[.+?\]:\s+\[.+?\]:\s+(.+)#",$ligne["text"],$re)){$ligne["text"]=$re[1];}
		
		$affiche_text=$ligne["text"];
		if(strlen($affiche_text)>90){$affiche_text=substr($affiche_text,0,85).'...';}
		
		$tooltip="<li><strong>{date}:&nbsp;$original_date</li><li><strong>{computer}:&nbsp;$computer</strong></li><li><strong>{process}:&nbsp;{$ligne["process"]}</li>";
		$tooltip=$tooltip."<li><strong>{context}:&nbsp;{$ligne["context"]}</strong></li><hr>{click_to_display}<hr>";
		$tooltip=$tooltip."<div style=font-size:9px;padding:3px>{$ligne["text"]}</div>";
		
		if(preg_match("#<body>(.+?)</body>#is",$ligne["content"],$re)){$content=strip_tags($re[1]);}else{$content=strip_tags($ligne["content"]);}
		if(strlen($content)>300){$content=substr($content,0,290)."...";}
	
		$ID=$ligne["ID"];
		$js="articaShowEvent($ID);";
		
		$color="5C81A7";
		if(preg_match("#(error|fatal|unable)#i",$affiche_text)){$color="B50113";}
		
		$affiche_text=texttooltip($affiche_text,$tooltip,$js,null,0,"font-size:13px;font-weight:bolder;color:#$color");
		if($cl=="oddRow"){$cl=null;}else{$cl="oddRow";}
		$time=strtotime($original_date);
		$distanceOfTimeInWords=distanceOfTimeInWords($time,time());
		
		
		
		$OBS="<div style='font-size:13px;margin:0px;padding:0px'>$affiche_text</div><div style='font-size:11px;margin:0px;padding:0px'><i>$distanceOfTimeInWords</i><br><i>$content</i></div>";	
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array($ligne["zDate"],$ligne["context"],$OBS )
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
	$sql="SELECT * FROM events WHERE ID=$id";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	
	$subject=$ligne["text"];
	
	
	if(preg_match("#<body>(.+?)</body>#is",$ligne["content"],$re)){
		$content=$re[1];
	}
	
	;
	if($content==null){
		
		if(strpos($ligne["content"],"<td")>0){$html=true;}
		$tbl=explode("\n",$ligne["content"]);
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


//ChangeSuperSuser	
	
?>	

