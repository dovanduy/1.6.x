<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	
	
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["dmesg-list"])){dmesg_search();exit;}

	if(isset($_GET["parameters"])){parameters();exit;}
	if(isset($_POST["kernel.panic_on_oops"])){save();exit;}
	if(isset($_POST["kernel_panic_on_oops"])){save();exit;}
js();

function tabs(){
	

	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$array["popup"]='{kernel_infos}';
	$array["parameters"]='{parameters}';

	
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:16px'>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_dmesg style='width:100%;height:700px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_dmesg').tabs();
			
			
			});
	</script>";		
	
}


function js(){
		$t=time();
		$jsstart="syslogConfigLoad$t()";
		if(isset($_GET["windows"])){$jsstart="syslogConfigLoadPopup$t()";}
		$page=CurrentPageName();
		$tpl=new templates();
		$title=$tpl->_ENGINE_parse_body("{kernel_infos}");
		$html="
		
		
		function syslogConfigLoad$t(){
			$('#BodyContent').load('$page?popup=yes');
			}
			
		function syslogConfigLoadPopup$t(){
			YahooWin4(700,'$page?tabs=yes','$title');
			}			
			
		$jsstart;
		";
		echo $html;
		
	
}
function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tpl=new templates();
	$t=time();
	
	$rows=$tpl->_ENGINE_parse_body("{rows}");

	
	
			

	$buttons="
	buttons : [
		{name: '<b>$new_database</b>', bclass: 'add', onpress : AddDatabase$t },
		
	
		],";
	$buttons=null;
	$html="
	<div style='margin-left:-10px'>
	<table class='DMESG_TABLE' style='display: none' id='DMESG_TABLE' style='width:100%;margin:-10px'></table>
	</div>
<script>
memedb$t='';
$(document).ready(function(){
$('#DMESG_TABLE').flexigrid({
	url: '$page?dmesg-list=yes&t=$t&hostname=$hostname&ou={$_GET["ou"]}',
	dataType: 'json',
	colModel : [
		{display: '$rows', name : 'dbname', width : 639, sortable : true, align: 'left'},
		
	],
	
	$buttons

	searchitems : [
		{display: '$database', name : 'dbname'},
		
		],
	sortname: 'dbname',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 672,
	height: 522,
	singleSelect: true
	
	});   
});

</script>";
	echo $html;	
	

}

function dmesg_search(){
	
	
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->getFrameWork("services.php?dmesg=yes")));
	if(!is_array($array)){json_error_show("Fatal, no such data");}
	$search=string_to_regex($_POST["query"]);

	
	if(isset($_POST['page'])) {$page = $_POST['page'];}	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	$pageEnd=$pageStart+$rp;
	if($pageEnd<=0){$pageEnd=$rp;}
//json_error_show("Start:$pageStart, end:$pageEnd");

	if($_POST["sortorder"]=="desc"){krsort($array);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = count($array);
	$data['rows'] = array();
$c=0;
	
	while (list ($key, $line) = each ($array) ){
		if($line==null){continue;}
		if($search<>null){if(!preg_match("#$search#i", $line)){continue;}}
		$c++;
		if($c<=$pageStart){continue;}
		if($c>$pageEnd){break;}
		
		
		
	$data['rows'][] = array(
		'id' => md5($line),
		'cell' => array(
	
			"$spanOn$line</span>" )
		);		

		
	}
	
	
	echo json_encode($data);	

	
}

function parameters(){
	
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$need_to_reboot_ask=$tpl->javascript_parse_text("{need_to_reboot_ask}");
	$users=new usersMenus();
	$MEM=$users->MEM_TOTAL_INSTALLEE*1024;
	$shmmax=round($MEM*0.9);
	$shmall=268435456;
	
	if($users->ArchStruct==32){
		$shmall=268435456;
	}
	if($users->ArchStruct==64){
		if($users->MEM_HIGER_1G){
			$shmall=1073741824;
		}
	}
	
	$kernelConfigArray=unserialize(base64_decode($sock->GET_INFO("KernelTuning")));
	
		$config["vm.min_free_kbytes"]["PROP"]=3789;
		//$config["vm.nr_hugepages"]["PROP"]=512;
		$config["vm.vfs_cache_pressure"]["PROP"]=50;
		$config["vm.overcommit_ratio"]["PROP"]="0";
		$config["vm.overcommit_memory"]["PROP"]=1;
		$config["vm.swappiness"]["PROP"]=5;
		$config["vm.lower_zone_protection"]["PROP"]=250;
		$config["kernel.panic_on_oops"]["PROP"]=0;
		//$config["kernel.sem"]["PROP"] = "250 32000 100 128";
		$config["kernel.shmall"]["PROP"] = $shmall;
		$config["kernel.shmmax"]["PROP"] = $shmmax;
		$config["kernel.shmmni"]["PROP"] = 4096;
			$t=time();
	$writeDefault=false;
	$KernelDefaultsValues=unserialize(base64_decode($sock->GET_INFO("KernelDefaultsValues")));
	if(!is_array($KernelDefaultsValues)){
		$writeDefault=true;
	}
	while (list ($key, $line) = each ($config) ){
			$config[$key]["VALUE"]=$sock->getFrameWork("cmd.php?sysctl-value=yes&key=".base64_encode($key));
		
			if($writeDefault){
				$KernelDefaultsValues[$key]=$config[$key]["VALUE"];
			}		
		
		$config[$key]["SET"]=$kernelConfigArray[$key];
		
	}
	
	if($writeDefault){
		$sock->SaveConfigFile(base64_encode(serialize($KernelDefaultsValues)), "KernelDefaultsValues");
	}
	
	reset($config);
	
	if(is_array($KernelDefaultsValues)){
		$defaults="<table style='width:99%' class=form>
		<tr>
			<td colspan=2 style='font-size:16px'>{orginal_values}:</td>
		</tr>
		";
		while (list ($key, $conf) = each ($KernelDefaultsValues) ){
			$defaults=$defaults.
			"<tr>
				<td class=legend style='font-size:12px'>$key:</td>
				<td style='font-size:12px'>$conf</td>
			</tr>";
			
			
		}
		
		$defaults=$defaults."</table>";
		
	}
	
	
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>";
	while (list ($key, $conf) = each ($config) ){
		if(trim($conf["VALUE"])==null){continue;}
		$proposal=null;
		$conf["VALUE"]=trim($conf["VALUE"]);
		if($conf["PROP"]<>null){
			if($conf["PROP"]<>$conf["VALUE"]){
					$proposal="<div style='font-size:13px;color:#C20808'>{proposal}:<i><strong>&laquo;{$conf["PROP"]}&raquo;&nbsp;{against}:&laquo;{$conf["VALUE"]}&raquo;</strong></i></div>";
				}
			}
	
		if($conf["SET"]==null){
			if($conf["PROP"]<>null){$value=$conf["PROP"];}else{$value=$conf["VALUE"];}
		}else{
			$value=$conf["SET"];
		}
		$size=null;
		if($key=="kernel.sem"){$proposal=null;}
		if($key=="kernel.shmmax"){$size=$conf["VALUE"]/1024;$size=round($size/1024,2)."Mo";}
		if($key=="kernel.shmall"){$size=$conf["VALUE"]/1024;$size=round($size/1024,2)."Mo";}
	
		
		
		$html=$html."
		<tr>
			<td class=legend style='font-size:16px' nowrap>$key$proposal</td>
			<td style='font-size:16px'>". Field_text($key,$value,"font-size:16px;width:70%;font-weight:bolder")."&nbsp;$size</td>
			<td width=1%>". help_icon("{{$key}}")."</td>
		</tr>
		
		";
		
		
		$js[]="\tif(document.getElementById('$key')){\t\tXHR.appendData('$key',document.getElementById('$key').value);\n\t}\n";
		
	}
	$html=$html."
	<tr>
		<td colspan=3 align='right'>". button("{apply}","SaveKernelSettings$t()",16)."</td>
	</tr>
	</table>
	$defaults
	<script>
var xSaveKernelSettings$t= function (obj) {
		var results=trim(obj.responseText);
		document.getElementById('$t').innerHTML='';
		if(results.length>0){alert(results);return;}
		RefreshTab('main_dmesg');
	
	}		
	function SaveKernelSettings$t(){
		var XHR = new XHRConnection();
	
		if(confirm('$need_to_reboot_ask')){XHR.appendData('OSREBBOOT','yes');}

		
		".@implode("\n", $js)."
		AnimateDiv('$t');   
		XHR.sendAndLoad('$page', 'POST',xSaveKernelSettings$t);
		
	}
		</script>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function save(){
	$sock=new sockets();
	$reboot=null;
	if(isset($_POST["OSREBBOOT"])){$reboot="&reboot=yes";unset($_POST["OSREBBOOT"]);}
	
	while (list ($key, $val) = each ($_POST) ){
		$val=trim($val);
		if($val==null){continue;}
		if(preg_match("#^(.+?)_#", $key,$re)){
			$key=str_replace("{$re[1]}_", "{$re[1]}.", $key);
			$AA[$key]=$val;
		}else{
			$AA[$key]=$val;
		}
		
	}
	
	
	$sock->SaveConfigFile(base64_encode(serialize($AA)), "KernelTuning");
	$sock->getFrameWork("services.php?KernelTuning=yes$reboot");
	if($reboot<>null){echo "rebooting Now !!!";}
	
	
}



