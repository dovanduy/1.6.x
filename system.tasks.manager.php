<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.os.system.tools.inc');

	$usersmenus=new usersMenus();
	if($usersmenus->AsArticaAdministrator==true){}else{header('location:users.index.php');exit;}	

if(isset($_GET["PID"])){PIDInfos();exit;}
if(isset($_GET["reload"])){echo page_proc();exit;}
if(isset($_GET["KillProcessByPid"])){KillProcessByPid();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["getmem"])){getmem();exit;}
if(isset($_GET["getcpu"])){getCpu();exit;}
if(isset($_GET["taskslist"])){processes();exit;}
if(isset($_GET["home"])){home();exit;}
if(isset($_GET["home-b"])){home_b();exit;}
if(isset($_GET["home-c"])){home_c();exit;}
if(isset($_GET["task-m"])){tasks_start();exit;}
if(isset($_GET["clean-mem-js"])){clean_mem_js();exit;}
if(isset($_POST["clean-mem-perform"])){clean_mem_perform();exit;}
if(isset($_GET["tasks-list"])){task_list();exit;}
if(isset($_POST["kill9"])){kill9();exit;}
js();

function js(){
$page=CurrentPageName();
$prefix=str_replace(".","_",$page);	
$tpl=new templates();
$title=$tpl->_ENGINE_parse_body('{task_manager}');
$html="RTMMail(1000,'$page?popup=yes','$title');";
echo $html;

}

function clean_mem_js(){
$page=CurrentPageName();
$tpl=new templates();
$clean_memory_ask=$tpl->javascript_parse_text("{clean_memory_ask}");
$html="

	
	var x_CleanCacheMem= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		RefreshTab('main_taskmanager_tabs');
	}

	function CleanCacheMem(){
		if(confirm('$clean_memory_ask')){
			var XHR = new XHRConnection();
			XHR.appendData('clean-mem-perform','yes');
			XHR.sendAndLoad('$page', 'POST',x_CleanCacheMem);			
		
		}
	
	}
CleanCacheMem();
";
echo $html;
	
}


function clean_mem_perform(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?CleanCacheMem=yes");
	
}

function kill9(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?kill-pid-single={$_POST["kill9"]}");
	
}


function popup(){
	
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$fontsize=16;
	
	$array["home"]="{home}";
	$array["task-m"]="{task_manager}";
	$array["lighttpd"]="{web_interface_service}";
	
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		if($num=="lighttpd"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"lighttpd.php?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo build_artica_tabs($html, "main_taskmanager_tabs");
	
}


function home(){
	$t=time();
	$page=CurrentPageName();
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top' width=50%><div id='$t-b'></div>
		<td valign='top' width=50%><div id='$t-c'></div>
	</tr>
	</table>
	<script>
		LoadAjax('$t-b','$page?home-b=yes&t=$t');
		LoadAjax('$t-c','$page?home-c=yes&t=$t');
	</script>
	";
	echo $html;
}

function home_b(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$tr[]=icon_memory();
	
	$tr[]=Paragraphe("ChemicalsMoveDown-64.png","{clean_memory}","{clean_memory_text}","javascript:Loadjs('$page?clean-mem-js=yes&t=$t');");
	
	$table=CompileTr2($tr,"form");
	echo $tpl->_ENGINE_parse_body($table);
	
}

function icon_memory(){
	$GLOBALS["ICON_FAMILY"]="SYSTEM";
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();$users=$GLOBALS["CLASS_USERS"];}else{$users=$GLOBALS["CLASS_USERS"];}
	if(!$users->AsAnAdministratorGeneric){return null;}
	$js="GotoSystemMemory()";
	$img="bg_memory-64.png";
	return Paragraphe($img,"{system_memory}","{system_memory_text}","javascript:$js");
}

function home_c(){
	$t=$_GET["t"];
	$os=new os_system();
	$mem=$os->html_Memory_usage();
	$page=CurrentPageName();
	$tpl=new templates();	
	$html="
	<table style='width:98%' class=form id='system-task-manager'>
	<tr>
		<td>$mem</td>
	</tr>
	</table>
	<div style='width:100%;text-align:right'>". imgtootltip("refresh-32.png","{refresh}","RefreshTab('main_taskmanager_tabs')")."</div>
	<script>
	
		function Startc$t(){
			setTimeout('xStart$t()',11000);
		}
		
		function xStartc$t(){
			LoadAjax('$t-c','$page?home-c=yes&t=$t');
		
		}
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function tasks_start(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$time=$tpl->_ENGINE_parse_body("{time}");
	$task=$tpl->_ENGINE_parse_body("{task}");

	$buttons="
	buttons : [
	{name: '<b>$new_member</b>', bclass: 'Add', onpress : NewMemberOU},
	{name: '<b>$manage_groups</b>', bclass: 'Groups', onpress : ManageGroupsOU},$bt_enable
	],";	
	$buttons=null;
$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
row_id='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?tasks-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: 'PID', name : 'ffff', width : 49, sortable : false, align: 'left'},
		{display: '%CPU', name : 'aaa', width : 43, sortable : false, align: 'left'},	
		{display: '%MEM', name : 'bbb', width : 45, sortable : true, align: 'left'},
		{display: '$time', name : 'ccc', width : 56, sortable : false, align: 'left'},
		{display: '$task', name : 'ddd', width : 636, sortable : false, align: 'left'},
		{display: 'kill', name : 'eee', width : 31, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$task', name : 'search'},
		],
	sortname: 'uid',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 958,
	height: 480,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	var x_sslBumbAddwl=function(obj){
     	var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);}
     	//$('#flexRT$t').flexReload();
     	}	
      
     function NewMemberOU(){
    		Loadjs('domains.add.user.php?ou=$ou&flexRT=$t')
		}

	function ManageGroupsOU(){
		Loadjs('domains.edit.group.php?ou=$ou_encoded&js=yes')
	}
	function xStart1$t(){
		if(!RTMMailOpen()){return;}
		if(!document.getElementById('flexRT$t')){return;}
		$('#flexRT$t').flexReload();
		setTimeout('xStart1$t()',5000);
	}

	function Kill9(pid){
		if(confirm('Kill PID:'+pid+' ?')){
		var XHR = new XHRConnection();
		XHR.appendData('kill9',pid);
		XHR.sendAndLoad('$page', 'POST',x_sslBumbAddwl);				
		
		}
	}	
	
	
	setTimeout('xStart1$t()',5000);
	
</script>

";
	
	echo $html;
	
}





function task_list(){
$tpl=new templates();	
$sock=new sockets();
$datas=$sock->getFrameWork("cmd.php?TaskLastManager=yes");

	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();
if($_POST["query"]<>null){
		$tofind=$_POST["query"];
		$tofind=str_replace(".", "\.", $tofind);
		$tofind=str_replace("[", "\[", $tofind);
		$tofind=str_replace("]", "\]", $tofind);
		$tofind=str_replace("*", ".*?", $tofind);
}
		
	

if(preg_match_all("#([0-9]+)\s+([0-9\.]+)\s+([0-9\.]+)\s+([0-9\:]+)\S+(.+)#",$datas,$re)){
	$c=0;
	
	while (list ($num, $ligne) = each ($re[1]) ){
		
		$color="black";
		$cmd=$re[5][$num];
		if($tofind<>null){if(!preg_match("#$tofind#", $cmd)){continue;}}
		$c++;
		$ttl=$sock->getFrameWork("cmd.php?TaskLastManagerTime=$ligne");
		
		$cpu=intval($re[2][$num]);
		if($cpu>70){$color="#BA0000";}
		
		$kill=imgsimple("delete-24.png",null,"Kill9('$ligne')");
		
		$data['rows'][] = array(
		'id' => md5(serialize($re)),
		'cell' => array(
		"<span style='font-size:14px;color:$color'>$ligne</span>",
		"<span style='font-size:14px;color:$color'>{$re[2][$num]}%</span>",
		"<span style='font-size:14px;color:$color'>{$re[3][$num]}%</a></span>",
		"<span style='font-size:14px;color:$color'>{$re[4][$num]}</a></span>",
		"<span style='font-size:14px;color:$color'>$cmd</a></span><div style='font-size:11px;font-weight:bold'><i>TTL:&nbsp;$ttl</i></div>",
		"<span style='font-size:14px;color:$color'>$kill</a></span>",
		
		)
		);		

		
	}

	
}
$data['total'] = $c;
echo json_encode($data);		
}

function getLoad(){
	$users=new usersMenus();
	$sock=new sockets();
	$array_load=sys_getloadavg();
	$org_load=$array_load[0];
	$cpunum=intval($users->CPU_NUMBER);
	
	$load=intval($org_load);
	//middle =$cpunum on va dire que 100% ($cpunum*2) + orange =0,75*$cpunum
	$max_vert_fonce=$cpunum;
	$max_vert_tfonce=$cpunum+1;
	$max_orange=$cpunum*0.75;
	$max_over=$cpunum*2;
	$purc1=$load/$cpunum;
	$pourc=round($purc1*100,2);
	$color="#5DD13D";
	if($load>=$max_orange){
		$color="#F59C44";
	}
	
	if($load>$max_vert_fonce){
		$color="#C5792D";
	}

	if($load>$max_vert_tfonce){
		$color="#83501F";
	}	
	

	
	if($load>=$max_over){
		$color="#640000";
		$text="<br>".texttooltip("{overloaded}","{overloaded}","Loadjs('overloaded.php')",null,0,"font-size:9px;font-weight:bold;color:#d32d2d");
	}	

	if($pourc>100){$pourc=100;}

return "
<tr>
	<td width=1% nowrap class=legend nowrap>{load_avg}:</strong></td>
	<td align='left'>
		<div style='width:100px;background-color:white;padding-left:0px;border:1px solid $color;margin-top:3px'>
			<div style='width:{$pourc}px;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:$color'>
				<span style='color:white;font-size:11px;font-weight:bold'>$pourc%</span>
			</div>
		</div>
	</td>
	<td width=1% nowrap><strong>{load}: $org_load&nbsp;[$cpunum cpu(s)]$text</strong></td>
</tr>";		
}

function getCpu(){
	
	$sock=new sockets();
	$cpu_purc=$sock->getFrameWork("cmd.php?cpualarm=yes");
	$cpu_purc_text=$cpu_purc."%";
	$cpu_color="#5DD13D";
	if($cpu_purc>70){$cpu_color="#F59C44";}
	if($cpu_purc>90){$cpu_color="#D32D2D";}
	$pouc_disk_io_text="<br><span style='font-size:9px'>% CPU:$pouc_disk_io%</span>";
	$cpu="
	<div style='width:100px;background-color:white;padding-left:0px;border:1px solid $color;margin-top:3px'>
		<div style='width:{$pouc_disk_io}px;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:$color'></div>
	</div>
	
	
	";
	
	
$cpu="<tr>
				<td width=1% nowrap class=legend nowrap>{cpu_usage}:</strong></td>
				<td align='left'>
					<div style='width:100px;background-color:white;padding-left:0px;border:1px solid $cpu_color'>
						<div style='width:{$cpu_purc}px;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:$cpu_color'>
							<strong>{$cpu_purc}%</strong></div>
					</div>
				</td>
				<td width=1% nowrap><strong style='color:$cpu_color'>{$cpu_purc}%</strong></td>
				</tr>";	
$load=getLoad();
	$html="<table>$cpu$load
		</table>";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}



function Page(){
$html="<div style='padding:20px;height:350px;overflow:auto' id='page_taskM'>

" . page_proc() . "</div>";
echo $html;
	
}

function page_proc(){
	$sock=new sockets();
	$tpl=new templates();
	
	$sock->getfile('TaskManager');
	include_once("ressources/psps.inc");
	
	
	
	
	$html="
	<center><input type='button' OnClick=\"javascript:ReloadTaskManager();\" value='&laquo;&nbsp;{reload}&nbsp;&raquo;'></center>
	<table style='width:100%;border:1px solid #CCCCCC;padding:3px'>
	 	<tr style='background-color:#CCCCCC'>
	 	<td valign='middle' class='bottom' style='font-size:10px;font-weight:bold'>&nbsp;</td>
	 	<td valign='top' class='bottom' style='font-size:10px;font-weight:bold'>&nbsp;{process_name}</td>
	 	<td valign='top' class='bottom' style='font-size:10px;font-weight:bold'>&nbsp;PID</td>
	 	<td valign='top' class='bottom'  style='font-size:10px;font-weight:bold'>&nbsp;{memory}</td>
	 	</tr>
	 	";
	
	 while (list ($num, $ds) = each ($processes) ){
	 	
	 	$tools=ParseArray($ds['status']);
	 	$tooltip=CellRollOver("ProcessTaskEdit('{$num}')",$tools);
	 	$html=$html . "
	 	<tr $tooltip>
	 	<td valign='middle' class='bottom' style='font-size:10px'><img src='img/fw-vert-s.gif'></td>
	 	<td valign='top' class='bottom' style='font-size:10px'>{$ds['status']["name"]}</td>
	 	<td valign='top' class='bottom' style='font-size:10px'>{$num}</td>
	    <td valign='top' class='bottom'  style='font-size:10px'>". FormatBytes($ds['memory'])."</td>
	 	</tr>
	 	";}
	 	
	 
	
	$html=$html . "
	</table>
	";
	return  $tpl->_ENGINE_parse_body($html);
	
}


function ParseArray($LINE){
	
	while (list ($num, $ligne) = each ($LINE) ){
		
		
		$html=$html."<tr><td width=1% nowrap><strong>$num</strong></td><td width=1% nowrap><strong>$ligne</strong></td></tr>";
		
		
		
	}
	
	return "<table style=width:250px>$html</table>";
	
	
}

function PIDInfos(){
	$PID=$_GET["PID"];
	$sock=new sockets();
	$sock->getfile('TaskManager');
	include_once("ressources/psps.inc");
	$ARRAY=$processes[$PID];
	
	while (list ($num, $ligne) = each ($ARRAY["status"]) ){
		$html=$html .
		"
		<tr>
		<td align='right' valign='top'><strong style='font-size:11px'>$num:</td>
		<td align='left' valign='top'><strong style='font-size:11px'>$ligne</td>
		</tR>";
		
	}
	
	
	$html="
	<H4>{$ARRAY["status"]["processname"]} (pid $PID <code>{$ARRAY["process_path"]}</code>)</H4>
	
		<center><input type='button' value='{kill_process}&nbsp;&raquo;' OnClick=\"javascript:KillProcessByPid('$PID');\"></center>
	<div style='padding:20px;height:320px;overflow:auto'>
		<table>
			$html
		</table>
	</div>
	
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function KillProcessByPid(){
	$pid=$_GET["KillProcessByPid"];
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?kill-pid-number={$_GET["KillProcessByPid"]}");
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("{success}\n$datas");
	
	
}



?>