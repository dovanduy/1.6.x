<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.ldap.inc');
	
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){echo "alert('no privileges');";die();}

	if(isset($_GET["disks-list"])){disks_list();exit;}
	if(isset($_GET["tools"])){tools();exit;}
	if(isset($_GET["form-edit"])){disk_form();exit;}
	if(isset($_POST["loop-dir"])){disk_form_save();exit;}
	if(isset($_POST["loop-del"])){disk_del();exit;}
	if(isset($_POST["loopcheck"])){disk_check();exit;}
	if(isset($_GET["js"])){js();exit;}
	start();
	
	
	function js(){
		header("content-type: application/x-javascript");
		$page=CurrentPageName();
		$tpl=new templates();
		$virtual_disks=$tpl->_ENGINE_parse_body("{virtual_disks}");
		$html="YahooWin3('895','$page','$virtual_disks');";
		echo $html;
	}	


function start(){
	$page=CurrentPageName();
	$tpl=new templates();
	$field=$_GET["field"];
	$value=$_GET["value"];

	

	$virtual_disks=$tpl->_ENGINE_parse_body("{virtual_disks}");
	$t=time();
	$disk=$tpl->_ENGINE_parse_body("{disk}");
	$name=$tpl->_ENGINE_parse_body("{name}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$delete_disk_confirm=$tpl->javascript_parse_text("{delete_disk_confirm}");
	$create_new_disk=$tpl->javascript_parse_text("{create_new_disk}");
	$verify_disks=$tpl->javascript_parse_text("{verify_disks}");

	$rebuild=Paragraphe("service-check-64.png","{verify_disks}","{verify_disks_text}","javascript:loopcheck()");
		
	
	$TB_WIDTH=550;
	$t=time();

	$buttons="
	buttons : [
	{name: '<b>$create_new_disk</b>', bclass: 'Add', onpress : LoopAddFormAdd},
	{name: '<b>$verify_disks</b>', bclass: 'Reconf', onpress : loopcheck},
	],";

	

	$html="
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>

	<script>
	var memmd5$t='';
	var memmd5img$t='';
	$(document).ready(function(){
		$('#$t').flexigrid({
			url: '$page?disks-list=yes&t=$t',
			dataType: 'json',
			colModel : [
			{display: '&nbsp;', name : 'hits', width : 32, sortable : false, align: 'center'},
			{display: '$disk', name : 'path', width : 381, sortable : true, align: 'left'},
			{display: '$name', name : 'disk_name', width : 127, sortable : true, align: 'left'},
			{display: '$size', name : 'size', width : 94, sortable : true, align: 'left'},
			{display: 'dev', name : 'hits', width : 94, sortable : true, align: 'left'},
			{display: '&nbsp;', name : 'hits', width : 32, sortable : false, align: 'center'},
		
		
		
			],$buttons
			searchitems : [
			{display: '$disk', name : 'path'},
			{display: '$name', name : 'disk_name'},
			],
			sortname: 'path',
			sortorder: 'desc',
			usepager: true,
			title: '<span id=\"title-img-$t\"></span>$virtual_disks',
			useRp: true,
			rp: 15,
			showTableToggleBtn: false,
			width: '99%',
			height: 450,
			singleSelect: true
		});
	});

	var x_LoopDel= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			if(document.getElementById('img-'+memmd5$t)){
				document.getElementById('img-'+memmd5$t).src='img/'+memmd5img$t;
			}
			$('#$t').flexReload();
		}	
					
	function LoopAddFormAdd(){
		LoopAddForm('');
	}
		
	function LoopAddForm(filename){
		YahooWin2(490,'$page?form-edit=yes&t=$t&filename='+escape(filename),'$virtual_disks');
	}		
	function LoopDel(path,zmd5,img){
		if(confirm('$delete_disk_confirm')){
			var XHR = new XHRConnection();
			memmd5img$t=img;
			memmd5$t=zmd5;
			document.getElementById('img-'+zmd5).src='img/ajax-loader.gif';
			XHR.appendData('loop-del',path);
			XHR.sendAndLoad('$page', 'POST',x_LoopDel);
		}
	}

	function loopcheck(){
		var XHR = new XHRConnection();
		XHR.appendData('loopcheck','yes');
		XHR.sendAndLoad('$page', 'POST',x_LoopDel);		
	}
</script>";
	echo $tpl->_ENGINE_parse_body($html);

}

function disks_list(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$database="artica_backup";
	$FORCE_FILTER=null;
	$table="loop_disks";
	$sock=new sockets();
	
	if(!$q->TABLE_EXISTS($table,$database)){json_error_show("$table, no such table",1);}

	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error",1);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$sizeT=$tpl->_ENGINE_parse_body("{disksize}");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$md5=md5(serialize($ligne));
		$disk=basename($ligne["path"]);
		$pathesc=urlencode($ligne["path"]);
		$size=FormatBytes($ligne["size"]*1024);
		$img="Database32.png";
		if($ligne["loop_dev"]==null){$img="Database32-red.png";$ligne["loop_dev"]="&nbsp;";}
		
		$inodes=null;
		$ssize=null;
		$href="<a href=\"javascript:blur()\" OnClick=\"javascript:LoopAddForm('{$ligne["path"]}')\"
		style='font-size:14px;font-weight:bold;text-decoration:underline'>";
		$href=null;
		
		if($ligne["loop_dev"]<>null){
			$array=unserialize(base64_decode($sock->getFrameWork("system.php?tune2fs-values=".base64_encode($ligne["loop_dev"])."&dirscan=".base64_encode("/automounts/{$ligne["disk_name"]}"))));
			if(is_array($array)){
				
				if(!isset($array["ERROR"])){
					$inodes="<i style='font-size:11px;font-weight:bold'>Inodes:{$array["INODES_USED"]}/{$array["INODES_MAX"]} ({$array["INODES_POURC"]}%)</i>";
			
					if(isset($array["SIZE"])){
						$inodes="$inodes<i style='font-size:11px;font-weight:bold'>&nbsp;|&nbsp;$sizeT:{$array["USED"]}/{$array["SIZE"]} ({$array["POURC"]}%)</i>";
					}
				
					$browsejs="Loadjs('tree.php?mount-point=".urlencode("/automounts/{$ligne["disk_name"]}")."')";
					$imgbrowss="<a href=\"javascript:blur();\" OnClick=\"javascript:$browsejs\"><img src='img/icon_mailfolder.gif' align='left' style='margin:5px'></a>";
				}else{
					$inodes="<img src='img/warning-panneau-24.png' style='margin-right:5px;margin-left:-10px;margin-top:-5px' align=left><span style='color:red'>{$array["ERROR_TEXT"]}</span>";
				}
			}

			
			
		}
		
		$delete=imgsimple("delete-32.png","{delete}","LoopDel('{$ligne["path"]}','$md5','$img')");
		
		
		
		$data['rows'][] = array(
				'id' => $md5,
				'cell' => array(
						"<img src='img/$img' id='img-$md5'>",
						"<span style='font-size:16px'>$imgbrowss$href$disk</a></span><div><i style='font-size:11px'>{$ligne["path"]}&nbsp;&raquo;&raquo;&nbsp;/automounts/{$ligne["disk_name"]}</i></div><div>$inodes$ssize</div>",
						"<span style='font-size:16px'>$href{$ligne["disk_name"]}</a></span>",
						"<span style='font-size:16px'>$href$size</a></span>",
						"<span style='font-size:16px'>$href{$ligne["loop_dev"]}</a></span>",
						"<span style='font-size:16px'>$delete</a></span>",
							
				)
		);		
		
	}
	
	echo json_encode($data);
}


function disk_form(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=$_GET["t"];
	
	$please_wait_building_the_disk=$tpl->javascript_parse_text("{please_wait_building_the_disk}...");
	
	$html="<div class=explain style='font-size:14px'>{disk_loop_explain}</div>
			<center id='anim2-$t' style='font-size:22px'></center>
			<div id='anim-$t' style='margin-top:10px'></div>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend>{name}:</td>
		<td>". Field_text("loop-name",null,"font-size:14px;width:120px")."</td>
		<td></td>
	</tr>	
	<tr>
		<td class=legend>{directory}:</td>
		<td>". Field_text("loop-dir",null,"font-size:14px;width:220px")."</td>
		<td>".button("{browse}...","Loadjs('SambaBrowse.php?no-shares=yes&field=loop-dir')",12)."</td>
	</tr>
	<tr>
		<td class=legend>{size}:</td>
		<td style='font-size:14px'>". Field_text("loop-size",1000,"font-size:14px;width:90px")."&nbsp;MB</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend>{maxfds}:</td>
		<td style='font-size:14px'>". Field_text("maxfds",8642,"font-size:14px;width:90px")."&nbsp;{files}</td>
		<td>&nbsp;</td>
	</tr>				
	<tr>
		<td colspan=3 align='right'><hr>". button("{add}","SaveLoopINMysql()",16)."</td>
	</tr>
	</table>

	<script>
	var x_SaveLoopINMysql= function (obj) {
				var results=obj.responseText;
				if(results.length>3){alert(results);}
				$('#$t').flexReload();
				YahooWin2Hide();
				if(document.getElementById('title-img-$t')){document.getElementById('title-img-$t').innerHTML='';}		
				if(document.getElementById('anim2-$t')){document.getElementById('anim2-$t').innerHTML='';}		
			}	
			
		function SaveLoopINMysql(lvs){
				var XHR = new XHRConnection();
				XHR.appendData('loop-name',document.getElementById('loop-name').value);
				XHR.appendData('loop-dir',document.getElementById('loop-dir').value);
				XHR.appendData('loop-size',document.getElementById('loop-size').value);
				XHR.appendData('maxfds',document.getElementById('maxfds').value);
				if(document.getElementById('anim2-$t')){
					document.getElementById('anim2-$t').innerHTML='$please_wait_building_the_disk';
				}
				
				AnimateDiv('anim-$t');
				if(document.getElementById('title-img-$t')){
					document.getElementById('title-img-$t').innerHTML='<img src=\"img/preloader.gif\">';
				}
				XHR.sendAndLoad('$page', 'POST',x_SaveLoopINMysql);
				
			}	
	</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function disk_del(){
	$sql="SELECT disk_name FROM loop_disks WHERE `path`='{$_POST["loop-del"]}'";
	$q=new mysql();
	$sock=new sockets();
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["disk_name"]=="UpdateUtility"){
		$sock->SET_INFO("UpdateUtilityUseLoop", 0);
	}
	$path=urlencode($_POST["loop-del"]);
	
	
	
	echo $sock->getFrameWork("lvm.php?loop-del=$path");
	
	
	
	
	
}

function disk_form_save(){
	if($_POST["loop-dir"]==null){$_POST["loop-dir"]="/home/virtuals-disks";}
	if($_POST["loop-name"]==null){$_POST["loop-name"]=time();}
	$path=$_POST["loop-dir"]."/".time().".disk";
	$size=$_POST["loop-size"];
	$t=new htmltools_inc();
	$sock=new sockets();
	$_POST["loop-name"]=$t->StripSpecialsChars($_POST["loop-name"]);	
	if(!is_numeric($size)){$size=10000;}
	$_POST["loop-name"]=addslashes($_POST["loop-name"]);
	$dir=$_POST["loop-dir"];
	
	$HardDriveSizeMB=unserialize(base64_decode($sock->getFrameWork("system.php?HardDriveDiskSizeMB=".base64_encode($dir))));
	if(!is_array($HardDriveSizeMB)){echo "Fatal Error Cannot retreive information for `$dir`";return;}
	$AVAILABLEMB=$HardDriveSizeMB["AVAILABLE"];if($AVAILABLEMB<$size){$T=$size-$AVAILABLEMB;echo "Fatal Error : Available: {$AVAILABLEMB}MB, need at least {$T}MB";return;}	
	
	
	$sql="INSERT INTO loop_disks (`path`,`size`,`disk_name`,`maxfds`) VALUES ('$path','$size','{$_POST["loop-name"]}','{$_POST["maxfds"]}')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	
	if($size<110000){
		echo base64_decode($sock->getFrameWork("lvm.php?loopcheck=yes&output=yes"));
	}else{
		$sock->getFrameWork("lvm.php?loopcheck=yes");
	}
}

function disk_check(){
	$sock=new sockets();
	$sock->getFrameWork("lvm.php?loopcheck=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{install_app}");	
	
}

?>
