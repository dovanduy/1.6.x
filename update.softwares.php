<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');

//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

if(isset($_GET["items"])){items();exit;}
if(isset($_GET["ShowID"])){ShowID();exit;}
if(isset($_GET["ShowID-js"])){ShowID_js();exit;}
if(isset($_POST["empty-table"])){empty_table();exit;}
popup();


function ShowID_js(){
	
	$id=$_GET["ShowID-js"];
	if(!is_numeric($id)){
		
		return;
	
	}$tpl=new templates();
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$sql="SELECT subject FROM artica_update_task WHERE ID=$id";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	
	$subject=$tpl->javascript_parse_text($ligne["subject"]);
	echo "YahooWin3('550','$page?ShowID=$id','$subject')";
	
}



function popup(){

	
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	
	
	$manual_update=$tpl->javascript_parse_text("{manual_update}");
	$ARRAY=unserialize(base64_decode($sock->getFrameWork("system.php?mylinux=yes")));
	
	$ARRAY["LINUX_CODE_NAME"]=trim($ARRAY["LINUX_CODE_NAME"]);
	$ARRAY["LINUX_DISTRIBUTION"]=trim($ARRAY["LINUX_DISTRIBUTION"]);
	$ARRAY["LINUX_VERS"][0]=trim($ARRAY["LINUX_VERS"][0]);
	$ARRAY["LINUX_ARCHITECTURE"][0]=trim($ARRAY["LINUX_ARCHITECTURE"][0]);
	
	$title="{$ARRAY["LINUX_CODE_NAME"]}: {$ARRAY["LINUX_DISTRIBUTION"]} v.{$ARRAY["LINUX_VERS"][0]} {$ARRAY["LINUX_ARCHITECTURE"]}Bits";
	
	
	$html="<div style='font-size:48px;margin-bottom:50px'>$title</div>
	<center style='margin:30px'>". button("$manual_update","Loadjs('update.upload.php')",36)."</center>
	</div>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	return;
	
	
	
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$os=$tpl->_ENGINE_parse_body("{os}");
	$architecture=$tpl->_ENGINE_parse_body("{architecture}");
	$version=$tpl->_ENGINE_parse_body("{version}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$daemon=$tpl->_ENGINE_parse_body("{daemon}");
	$software=$tpl->javascript_parse_text("{softwares}");
	$refresh_index=$tpl->javascript_parse_text("{refresh_index}");
	$TB_HEIGHT=450;
	$TB_WIDTH=927;
	$TB2_WIDTH=551;
	$all=$tpl->_ENGINE_parse_body("{all}");
	$t=time();
	$sock=new sockets();
	$manual_update=$tpl->javascript_parse_text("{manual_update}");
	$ARRAY=unserialize(base64_decode($sock->getFrameWork("system.php?mylinux=yes")));
	
	$ARRAY["LINUX_CODE_NAME"]=trim($ARRAY["LINUX_CODE_NAME"]);
	$ARRAY["LINUX_DISTRIBUTION"]=trim($ARRAY["LINUX_DISTRIBUTION"]);
	$ARRAY["LINUX_VERS"][0]=trim($ARRAY["LINUX_VERS"][0]);
	$ARRAY["LINUX_ARCHITECTURE"][0]=trim($ARRAY["LINUX_ARCHITECTURE"][0]);
	
	$title="{$ARRAY["LINUX_CODE_NAME"]}: {$ARRAY["LINUX_DISTRIBUTION"]} v.{$ARRAY["LINUX_VERS"][0]} {$ARRAY["LINUX_ARCHITECTURE"]}Bits";
	

	$buttons="
	buttons : [
	{name: '$refresh_index', bclass: 'Reload', onpress : RefreshIndex$t},
	{name: '$manual_update', bclass: 'import', onpress : ManuUpdate$t},
	
	],	";
	$html="
<input type=hidden id='main_tab_logiciels' value='flexRT$t'>
<table class='flexRT' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
function BuildTable$t(){
	$('#flexRT$t').flexigrid({
		url: '$page?items=yes',
		dataType: 'json',
		colModel : [
		{display: '$software', name : 'software', width :421, sortable : true, align: 'left'},
		{display: '$date', name : 'zDate', width :245, sortable : true, align: 'left'},
		{display: '$version', name : 'version', width : 235, sortable : false, align: 'left'},
		{display: '$architecture', name : 'Arch', width :90, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'install', width :50, sortable : true, align: 'center'},
		],
		$buttons
	
		searchitems : [
		{display: '$software', name : 'subject'},
		],
		sortname: 'zDate',
		sortorder: 'desc',
		usepager: true,
		title: '$title',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 450,
		singleSelect: true,
		rpOptions: [500]

	});
}

function articaShowEvent(ID){
	YahooWin6('750','$page?ShowID='+ID,'$title::'+ID);
}

function ManuUpdate$t(){
	Loadjs('update.upload.php');
}


function RefreshIndex$t(){
	Loadjs('update.refresh.index.php');
}

var x_EmptyEvents= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#events-table-$t').flexReload();
	//$('#grid_list').flexOptions({url: 'newurl/'}).flexReload();
	// $('#fgAllPatients').flexOptions({ query: 'blah=qweqweqwe' }).flexReload();

}

function Warn$t(){
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes&critical=1'}).flexReload(); 
}
function info$t(){
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes&critical=2'}).flexReload(); 
}
function Err$t(){
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes&critical=0'}).flexReload(); 
}
function All$t(){
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes'}).flexReload(); 
}
function EmptyEvents(){
	if(!confirm('$empty_events_text_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('empty-table','yes');
	XHR.sendAndLoad('$page', 'POST',x_EmptyEvents);
}
setTimeout(\" BuildTable$t()\",800);
</script>";

echo $html;

}

function items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$tarballs_file="/usr/share/artica-postfix/ressources/logs/web/tarballs.cache";
	$FORCE=1;
	$search='%';
	$table="updsofts";
	$page=1;
	$sock=new sockets();
	if(!is_file($tarballs_file)){json_error_show("Index file missing",1);}
	
	$Content=@file_get_contents($tarballs_file);
	$strlen=strlen($Content);
	if(preg_match("#<PACKAGES>(.*?)</PACKAGES>#", $Content,$re)){
		$MAIN=unserialize(base64_decode($re[1]));
	}
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	if(!is_array($MAIN)){json_error_show("Index missing not an array - $strlen Bytes -",0);}
	if(count($MAIN)==0){json_error_show("Index missing",1);}
	$CurrentPage=CurrentPageName();

	//$array[$distri][$Arch][$package][$version]=array("VERSION"=>$version,"SIZE"=>$size,"DATE"=>$date);
	
	$PACKAGES["squid32"]="APP_SQUID";
	$PACKAGES["sambac"]="APP_SAMBA";
	$PACKAGES["ntopng"]="APP_NTOPNG";
	$PACKAGES["pythondev"]="APP_PYTHON_DEV";
	$PACKAGES["vsftpd"]="APP_VSFTPD";
	$PACKAGES["rdproxy"]="APP_RDPPROXY";
	$PACKAGES["ufdbcat"]="APP_UFDBCAT";
	$PACKAGES["syncthing"]="APP_SYNCTHING";

	$ARRAY=unserialize(base64_decode($sock->getFrameWork("system.php?mylinux=yes")));
	$LINUX_CODE_NAME=$ARRAY["LINUX_CODE_NAME"];
	$LINUX_DISTRIBUTION=$ARRAY["LINUX_DISTRIBUTION"];
	$DebianVer="debian{$ARRAY["LINUX_VERS"][0]}";
	$Architecture=$ARRAY["LINUX_ARCHITECTURE"];
	
	$search=string_to_flexregex();
	
	$c=0;
	while (list ($filename, $ligne) = each ($MAIN) ){
		$xlogs=null;
		$comp=true;
		$pic="32-install-soft.png";
		$filenameenc=urlencode($filename);
		
		$ARCH=$ligne["ARCH"];
		if(preg_match("#([0-9]+)#", $ARCH,$re)){
			$ARCHBIN=$re[1];
		}
		$SIZE=FormatBytes($ligne["SIZE"]/1024);
		$VERSION=$ligne["VERSION"];
		$distri=$ligne["distri"];
		$log[]=array();
		if($Architecture<>$ARCHBIN){$comp=false;$log[]="$Architecture !== $ARCHBIN";}
		if($LINUX_CODE_NAME<>"DEBIAN"){$log[]="$LINUX_CODE_NAME !== DEBIAN";$comp=false;}
		if($DebianVer<>$distri){$comp=false;$log[]="$DebianVer !== $distri";$comp=false;}
		$jsInstall="Loadjs('update.software.install.php?filename=$filenameenc');";
		if(!$comp){
			$pic="32-install-soft-grey.png";
			$jsInstall=null;
		}
		
		$img=imgsimple($pic,null,$jsInstall);
		$package=$tpl->_ENGINE_parse_body("{{$PACKAGES[$ligne["package"]]}}");
		
		$DATE=$tpl->_ENGINE_parse_body(date("Y {F} {l} d H:i ",$ligne["DATE"]));
		if($tpl->language=="fr"){
			$DATE=$tpl->_ENGINE_parse_body(date("{l} d {F} Y H:i ",$ligne["DATE"]));
		}
		
		if($search<>null){
			if(!preg_match("#$search#i", $filename)){
				if(!preg_match("#$search#i", $package)){
					if(!preg_match("#$search#i", $VERSION)){
						continue;
					}
				}
			}
		}
		
		
		$span="<span style='font-size:18px;'>";
		$spanf="</span>";
		$c++;
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"{$span}$package{$spanf}<br><i style='font-size:14px'>$filename ($SIZE)</i>$xlogs",
						"{$span}$DATE{$spanf}",
						"{$span}$VERSION{$spanf}",
						"{$span}$ARCH{$spanf}",$img
						)
		);
	}
	
	$data['page'] = $page;
	$data['total'] = $c;
	

	if($c==0){json_error_show("Index missing",1);}
	echo json_encode($data);

}