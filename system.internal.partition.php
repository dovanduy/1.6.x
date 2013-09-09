<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once("ressources/class.os.system.inc");
	include_once("ressources/class.lvm.org.inc");
	include_once("ressources/class.autofs.inc");
	
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){echo "alert('no privileges');";die();}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["infos"])){infos();exit;}
	if(isset($_GET["tasks"])){tasks();exit;}
	if(isset($_GET["lvm"])){lvm();exit;}
	if(isset($_GET["lvm_status"])){lvm_status();exit;}
	if(isset($_GET["autofs-connect"])){autofs_connect();exit;}
	if(isset($_POST["btrfs-convert"])){btrfs_convert();exit;}
	js();
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{partition}:{$_GET["dev"]}");
	$html="YahooWin5('950','$page?tabs=yes&dev={$_GET["dev"]}','$title');";
	echo $html;
	
}

function tabs(){
	
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();

	$arrayDev=unserialize(base64_decode($sock->getFrameWork("cmd.php?udevinfos=yes&dev={$_GET["dev"]}")));
	$arrayField=base64_encode(serialize($arrayDev));
	if(!is_file('ressources/usb.scan.inc')){$sock->getFrameWork("cmd.php?usb-scan-write=yes");}	
	include_once 'ressources/usb.scan.inc';	
	
	$array["infos"]='{info}';
	if(isset($lvm_dev[$dev])){$array["lvm"]='{virtual_disks}';}
	$array["tasks"]='{tasks}';	
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&dev={$_GET["dev"]}&dev-infos=$arrayField\"><span style='font-size:16px'>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_partition");
	
}

function infos(){
	$dev=$_GET["dev"];
	$master_dev=substr($dev,0,strlen($dev)-1);
	$users=new usersMenus();
	$tpl=new templates();
	$sock=new sockets();
	if(!is_file('ressources/usb.scan.inc')){$sock->getFrameWork("cmd.php?usb-scan-write=yes");}	
	include_once 'ressources/usb.scan.inc';
	if(!isset($_GLOBAL["disks_list"][$master_dev])){echo $tpl->_ENGINE_parse_body("<center><H2>{UNABLE_TO_OBTAIN_INFORMATIONS_FROM}:$dev</H2></center>");}
	$partArray=$_GLOBAL["disks_list"][$master_dev]["PARTITIONS"][$dev];
	$usb=new usb();
	$devser=urlencode($dev);
	$array=unserialize(base64_decode($_GET["dev-infos"]));
	
	while (list ($num, $ligne) = each ($array) ){
		
		$length=strlen($ligne);
		if($length>36){$ligne=substr($ligne,0,33)."...";}
		$tr=$tr."
		<tr>
			<td class=legend>{{$num}}:</td>
			<td style='font-size:12px;font-weight:bold'>$ligne</td>
		</tr>	
		";
		
	}
	
	
	$html="
	<div style='font-size:16px;font-weight:bold;'>$dev ({$partArray["ID_FS_LABEL"]})</div>
	<div><i style='font-size:12px;font-weight:bold;margin-bottom:10px'>{mounted}:&nbsp;{$partArray["MOUNTED"]} {$partArray["ID_FS_TYPE"]}</i></div>
	
	<table style='width:100%'>
	<tr>
		<td valign='top' width=1%><img src='img/partition-infos-128.png'></td>
		<td valign='top' width=99%>
			<table style='width:99%' class=form>
			<tr>
				<td class=legend>{type}:</td>
				<td style='font-size:12px;font-weight:bold'>{$usb->getPartypename($partArray["TYPE"])} ({$partArray["TYPE"]})</td>
			</tr>
			<tr>
				<td class=legend>{size}:</td>
				<td style='font-size:12px;font-weight:bold'>{$partArray["SIZE"]}</td>
			</tr>			
			$tr
	</table>
	</td>
	</tr>
	</table>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function tasks(){
	$users=new usersMenus();	
	$sock=new sockets();
	$dev=$_GET["dev"];
	$master_dev=substr($dev,0,strlen($dev)-1);
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	if(!is_file('ressources/usb.scan.inc')){$sock->getFrameWork("cmd.php?usb-scan-write=yes");}	
	include_once 'ressources/usb.scan.inc';
	if(!isset($_GLOBAL["disks_list"][$master_dev])){echo $tpl->_ENGINE_parse_body("<center><H2>{UNABLE_TO_OBTAIN_INFORMATIONS_FROM}:$dev</H2></center>");}
	$partArray=$_GLOBAL["disks_list"][$master_dev]["PARTITIONS"][$dev];	
	$autofs_ask_dir=$tpl->javascript_parse_text("{autofs_ask_dir}");
	$autofs_ask_dir_def=basename($dev);
	$blkidArray=unserialize(base64_decode($sock->getFrameWork("services.php?blkid=$dev")));
	$array=unserialize(base64_decode($_GET["dev-infos"]));
	$connect_partitions=Paragraphe("partitions-settings-64.png","{CONNECT_HD}","{CONNECT_HD_TEXT}","javascript:Loadjs('fstab.php?dev=$dev');");
	$connect_partitions_disabled=Paragraphe("partitions-settings-64-grey.png","{CONNECT_HD}","{CONNECT_HD_TEXT}");
	$ask_convert_item=$tpl->javascript_parse_text("{ask_convert_item}");
	$btrfs_convert=Paragraphe("disk-64.png","{BTRFS_CONVERT}","{APP_BTRFS_CONVERT_EXPLAIN}","javascript:BTRFS_CONVERT()");
	$btrfs_convert_disabled=Paragraphe("disk-64-grey.png","{BTRFS_CONVERT}","{APP_BTRFS_CONVERT_EXPLAIN}");
	
     $inodes=Paragraphe("64-filetype.png", "Inodes", "{Inodes_change_explain}",
    "javascript:Loadjs('system.internal.partition.inodes.php?dev=$dev')");
	
	
	if(!isset($array["ID_FS_TYPE"])){if($blkidArray["TYPE"]<>null){$array["ID_FS_TYPE"]=$blkidArray["TYPE"];}}	
	
	$automount=Paragraphe("magneto-64.png","{automount_center}","{partition_automount_center_text}",
	"javascript:PartitionAutofsConnect();");
	

	
	if(!$users->BTRFS_INSTALLED){$btrfs_convert=$btrfs_convert_disabled;}
	if(trim($partArray["MOUNTED"]=="/")){$btrfs_convert=$btrfs_convert_disabled;}

	$automount_disabled=Paragraphe("magneto-64-grey.png","{automount_center}","{partition_automount_center_text}");
	
	if(trim($partArray["MOUNTED"])<>null){
		$automount=$automount_disabled;
		$connect_partitions=$connect_partitions_disabled;
	}
	
	if(isset($lvm_dev[$dev])){
		$automount=$automount_disabled;
		$connect_partitions=$connect_partitions_disabled;		
	}
	
	if(!isset($array["ID_FS_TYPE"])){
		$automount=$automount_disabled;
		$connect_partitions=$connect_partitions_disabled;			
	}
	if(!$users->autofs_installed){
		$automount=$automount_disabled;	
	}
	
	$tr[]=$inodes;
	$tr[]="$btrfs_convert";
	$tr[]="$automount";
	$tr[]=$connect_partitions;
	
	
	
	$intro[]="<table style='width:99%' class=form>";
		
		$icon=imgtootltip("mailbox_hd.gif","$xdevPart","$xdevPart");
		$intro[]="<tr>
		<td style='font-size:13px;font-weight:bold'>$icon</td>
		<td class=legend style='font-size:13px'>{xtype}:</td>
		<td style='font-size:13px;font-weight:bold'>$href{$blkidArray["TYPE"]}</a></td>
		<td class=legend style='font-size:13px' width=1%>&nbsp;|&nbsp;</td>
		<td class=legend style='font-size:13px'>{label}:</td>
		<td style='font-size:13px;font-weight:bold'>$href{$blkidArray["LABEL"]}</a></td>
	</tr>";
		
	
	$intro[]="</table>";	
	
	
	
	
$tables[]="<table style='width:99%;margin-top:15px' class=form><tr>";
$t=0;
while (list ($key, $line) = each ($tr) ){
		$line=trim($line);
		if($line==null){continue;}
		$t=$t+1;
		$tables[]="<td valign='top'>$line</td>";
		if($t==2){$t=0;$tables[]="</tr><tr>";}
		}

if($t<2){
	for($i=0;$i<=$t;$i++){
		$tables[]="<td valign='top'>&nbsp;</td>";				
	}
}	
	
	
$html=@implode("", $intro)."<center><div style='width:470px'>". implode("\n",$tables)."</div></center>

<script>

var x_PartitionAutofsConnect= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	RefreshTab('main_config_partition');
	RefreshTab('partinfosdiv');
	}	

	function PartitionAutofsConnect(){
		var fs='{$array["ID_FS_TYPE"]}';
		var dir=prompt('$autofs_ask_dir','$autofs_ask_dir_def');
		if(dir){
			var XHR = new XHRConnection();
			XHR.appendData('autofs-connect','$dev');
			XHR.appendData('dev','$dev');
			XHR.appendData('fs',fs);
			XHR.appendData('LOCAL_DIR',dir);
			XHR.sendAndLoad('$page', 'GET',x_PartitionAutofsConnect);			
		}
	
	}
	
	function BTRFS_CONVERT(){
		if(confirm('$ask_convert_item ?')){
			var XHR = new XHRConnection();
			XHR.appendData('btrfs-convert','$dev');
			XHR.sendAndLoad('$page', 'POST',x_PartitionAutofsConnect);	
		}
	
	}
	
</script>

";

	
	$datas=$tpl->_ENGINE_parse_body($html);		
	
	
	echo $datas;	
	
}

function lvm(){
	$page=CurrentPageName();
	$dev=$_GET["dev"];
	$html="<div id='lvm_status' style='width:100%'></div>
	
	<script>
		function RefreshLVMDisk(dev){
			LoadAjax('lvm_status','$page?lvm_status=yes&dev='+dev+'&dev-infos={$_GET["dev-infos"]}');
		}
		
		RefreshLVMDisk('{$_GET["dev"]}');
	</script>
	
	";
	
	echo $html;
	
}

function lvm_status(){
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->getFrameWork("cmd.php?pvscan=yes")));
	
	$LVM_AR=$array[$_GET["dev"]];
	$group_name=$LVM_AR["VG"];
	if($group_name<>null){
		$VG_INFO=unserialize(base64_decode($sock->getFrameWork("cmd.php?vgs-info=$group_name")));
		$disks=vg_disks($group_name);
		}else{
			$disks="
			<div id='lvcreategroupid'>
			
			<center>
			<hr>
				<div style='font-size:16px'>{vgcreate_dev}</div>
			".imgtootltip("rename-disk-64.png","{vgcreate_dev_explain}","LVMVolumeGroupCreate('{$_GET["dev"]}')")."
			</center>
			<hr>
			</div>";
		}

	$add="
	<tr>
		<td width=1%><img src='img/add-database-32.png'></td>
		<td ". CellRollOver("lvcreate('$group_name')","{ADD_VG_TEXT}").">{ADD_VG}</td>
	</tr>
	
	";
	
	$html="
	<table style='width:100%'>
		<td width=99%><div style='font-size:16px'>$group_name&nbsp;&raquo;&raquo;{APP_LVMS} {size}:&nbsp;{$VG_INFO["$group_name"]["SIZE"]}</div></td>
		<td width=1%>". imgtootltip("32-usb-refresh.png","{refresh}","RefreshLVMDisk('{$_GET["dev"]}')")."</td>
	</tr>
	</table>
	$disks
	<table style='width:100%'>
		<tr>
			<td valign='top' width=99%>
				<div id='lvg' style='width:100%;height:450px;overflow:auto'>
					". lv_groups_list($group_name)."
				</div>
			</td>
			
		</tr>
	</table>
	
	";
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}
function vg_disks($groupname){
$sock=new sockets();	
$ar=unserialize(base64_decode($sock->getFrameWork("cmd.php?vg-disks=yes")));	
$disks=$ar[$groupname];

while (list ($index, $array) = each ($disks)){
 $array[0]=trim($array[0]);
 $table1=$table1."<td align='center'>". imgtootltip('usb-disk-64-2-del.png',"{unlink}","VGUnlinkDisk('$groupname','{$array[0]}')")."</td>";
 $table2=$table2."<TD nowrap><strong>".basename($array[0]). "&nbsp;({$array[1]}G)</strong></td>";
 
}
 $table1=$table1."<td align='center'>". imgtootltip('usb-disk-64-2-add.png',"{link_hard_drive}","VGlinkDisk('$groupname')")."</td>";
 $table2=$table2."<TD nowrap><strong>{link_hard_drive}</strong></td>";
 $table1=$table1."<td align='center'>". imgtootltip('database-48-add.png',"{ADD_VG_TEXT}","lvcreate('$groupname')")."</td>";
 $table2=$table2."<TD nowrap><strong>{ADD_VG}</strong></td>";
 

$html="<table><tr>$table1</tr><tr>$table2</tr></table>";
return $html;
	
}
function lv_groups_list($group){
		if($group==null){return null;}
		$sock=new sockets();
		$datas=$sock->getFrameWork("cmd.php?lvm-lvs=$group");
		$array=unserialize(base64_decode($datas));
		$all_lvs=unserialize(base64_decode($sock->getFrameWork("cmd.php?lvs-all=yes")));
		
		print_r($all_vgs);
	include('ressources/usb.scan.inc');	
	$html="
	<br>
	<div style='width:100%;height:250px;overflow:auto'>
	<table style='width:100%'>";
	
	
if(is_array($array)){
		$partitions="<table style='width:100%'>
		<tr>
		<th width=1%>&nbsp;</th>
		<th><strong style='font-size:12px'>{disk}</th>
		<th><strong style='font-size:12px' valign='middle'>&nbsp;</th>
		<th><strong style='font-size:12px' width=1% nowrap>{used}</th>
		<th><strong style='font-size:12px' width=1% nowrap>{size}</th>
		<th width=1% nowrap><strong style='font-size:12px' >{mounted}</th>
		<th>&nbsp;</th>
		
		</tr>";
	
	while (list ($name, $size) = each ($array)){
		if($name==null){continue;}
		
			$count=$count+1;
			$size=str_replace('.00','',$size);
			$dev="/dev/$group/$name";
			$name_mapper=str_replace("-","--",$name);
			$mapper="/dev/mapper/$group-$name_mapper";
			$diskInfos=unserialize(base64_decode($sock->getFrameWork("cmd.php?DiskInfos=$mapper")));
			
			$js="vgmanage('$dev')";		
		
		$perc=pourcentage($diskInfos["POURC"]);
		
		$MOUNTED=$diskInfos["MOUNTED"];
		if($diskInfos["USED"]==null){$diskInfos["USED"]=0;}
		$SIZE=$all_lvs[$name]["SIZE"];
		$delete=imgtootltip("database-48-delete.png","{delete}","Loadjs('lvm.vg.php?lvremove-js=$mapper&group_name=$group')");
		$show=imgtootltip("database-48.png","{view}",$js);
		
		
		
		//lvremove('/dev/mapper/internet_backup-toto');
		
		$partitions=$partitions . "
		
		<tr>
		<td width=1%>$show</td>
		<td><strong style='font-size:12px'>$name</td>
		<td valign='middle'><strong style='font-size:12px' >$perc</td>
		<td width=1%  nowrap align='right'><strong style='font-size:12px' >{$diskInfos["USED"]}</td>
		<td width=1% nowrap align='right'><strong style='font-size:12px' >$SIZE</td>
		<td width=1% nowrap><strong style='font-size:12px'>$MOUNTED</td>
		<td width=1% nowrap><strong style='font-size:12px'>$delete</td>
		
		</tr>
		<tr>
			<td colspan=7><hr></td>
		</tr>
		
		";
		
	}
	$partitions=$partitions."</table>";
	
}
	

	
	$tpl=new templates();
	$html=$partitions;
	return $tpl->_ENGINE_parse_body($html);
	
}

function btrfs_convert(){
	$dev=$_POST["btrfs-convert"];
	$sock=new sockets();
	$sock->getFrameWork("btrfs.php?btrfs-convert=$dev");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{ask_convert_item_background}",1);
	
}


function autofs_connect(){
	
	$auto=new autofs();
	$ldap=new clladp();
	$sock=new sockets();
	$_GET["LOCAL_DIR"]=strtolower($ldap->StripSpecialsChars($_GET["LOCAL_DIR"]));
	$upd=array();
	$dn="cn={$_GET["LOCAL_DIR"]},ou=auto.automounts,ou=mounts,$ldap->suffix";
	
	if($_GET["fs"]==null){
		$prefix_pattern="-fstype=auto,check=none,noatime";
	}
	
	if($_GET["fs"]=="ext2"){
		$prefix_pattern="-fstype=ext2,check=none,noatime";
	}	
	
	if($_GET["fs"]=="ext3"){
		$prefix_pattern="-fstype=ext3,check=none,noatime,nodiratime,data=journal,user";
	}
	

	if($_GET["fs"]=="ext4"){
		$prefix_pattern="-fstype=ext4,check=none,noatime,nodiratime,data=journal,user";
	}		
	if($_GET["fs"]=="vfat"){
		$prefix_pattern="-fstype=vfat,uid=1000,gid=1010";
	}
	
	if($_GET["fs"]=="ntfs"){
		$prefix_pattern="-fstype=ntfs-3g,uid=1000,gid=1010";
	}
	
	if($prefix_pattern==null){
		$prefix_pattern="-fstype=auto,check=none,noatime";
	}
	
	$pattern="$prefix_pattern :{$_GET["dev"]}";
	

if(!$ldap->ExistsDN($dn)){
	$upd["ObjectClass"][]='top';
	$upd["ObjectClass"][]='automount';
	$upd["cn"][]="{$_GET["LOCAL_DIR"]}";
	$upd["automountInformation"][]=$pattern;
	if(!$ldap->ldap_add($dn,$upd)){echo "function: ".__FUNCTION__."\n"."file: ".__FILE__."\nline: ".__LINE__."\n" .$ldap->ldap_last_error;return;}
	$sock->getFrameWork("cmd.php?autofs-reload=yes");
	return;
	}
	
	
	$upd["automountInformation"][]=$pattern;
	if(!$ldap->Ldap_modify($dn,$upd)){
		echo "function: ".__FUNCTION__."\n"."file: ".__FILE__."\nline: ".__LINE__."\n" .$ldap->ldap_last_error;
		return false;
	}	
	
	$sock->getFrameWork("cmd.php?autofs-reload=yes");	
	
}




