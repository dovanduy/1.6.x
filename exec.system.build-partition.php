<?php
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.auth.tail.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.tail.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}


if($argv[1]=="--full"){disk_build_unique_partition($argv[2],$argv[3]);die();}


function disk_build_unique_partition($dev,$label){
	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".md5($dev.$label);
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		events("Already PID $oldpid exists, aborting...");
		return;
	}
	
	
	$mount=$unix->find_program("mount");
	$filelogs="/usr/share/artica-postfix/ressources/logs/web/".md5($dev);
	$GLOBALS["FILELOG"]=$filelogs;
	$disk_label=str_replace(" ", "_", $label);
	$targetMountPoint=$unix->isDirInFsTab("/media/$disk_label");
	if($targetMountPoint<>null){
		events("/media/$disk_label already set in fstab!! remove entry in fstab first...");
		events("Mounting the new media");
		$cmd="$mount /media/$disk_label 2>&1";	
		$results=array();
		exec($cmd,$results);
		while (list ($num, $val) = each ($results) ){events($val);}			
		return;
	}
	$tmpfile=$unix->FILE_TEMP();
	@file_put_contents($tmpfile, ",,L\n");
	
	events("Cleaning $dev..., please wait...");
	$dd=$unix->find_program("dd");
	$sfdisk=$unix->find_program("sfdisk");
	$mkfs=$unix->find_program("mkfs.ext4");
	$e2label=$unix->find_program("e2label");
	$mount=$unix->find_program("mount");
	if(!is_file($mkfs)){
		$mkfs=$unix-find_program("mkfs.ext3");
		$mkfs="$mkfs -b 4096 ";
		$extV="ext3";
	}else{
		$mkfs="$mkfs -Tlargefile4 ";
		$extV="ext4";
	}
	$cmd="$dd if=/dev/zero of=$dev bs=512 count=1 2>&1";
	events($cmd);
	$results=array();
	exec($cmd,$results);
	while (list ($num, $val) = each ($results) ){events($val);}
	
	events("Cleaning $dev..., please wait...");
	$cmd="$sfdisk -f $dev <$tmpfile 2>&1";
	
	events($cmd);
	$results=array();
	exec($cmd,$results);
	while (list ($num, $val) = each ($results) ){events($val);}	

	$FindFirstPartition=FindFirstPartition($dev);
	events("First partition = `$FindFirstPartition`");
	if($FindFirstPartition==null){
		events("First partition = FAILED");
		return;
	}
	
	$cmd="$mkfs $FindFirstPartition 2>&1";
	events("Formatting  $FindFirstPartition, please wait....");
	events($cmd);
	$results=array();
	exec($cmd,$results);
	while (list ($num, $val) = each ($results) ){events($val);}

	
	events("Set label to $disk_label");
	$cmd="$e2label $FindFirstPartition $disk_label 2>&1";
	events($cmd);
	$results=array();
	exec($cmd,$results);
	while (list ($num, $val) = each ($results) ){events($val);}

	events("Change fstab to include new media $FindFirstPartition to /media/$disk_label");
	disk_change_fstab($FindFirstPartition,$extV,"/media/$disk_label");
	events("Mounting the new media");
	$cmd="$mount $FindFirstPartition 2>&1";
	events($cmd);
	$results=array();
	exec($cmd,$results);
	while (list ($num, $val) = each ($results) ){events($val);}
	events("done...");	
	
}

function FindFirstPartition($dev){
	$unix=new unix();
	$fdisk=$unix->find_program("fdisk");
	exec("$fdisk -l $dev 2>&1",$results);
	while (list ($num, $val) = each ($results) ){
		if(preg_match("#^(.+?)\s+1.*?Linux#", $val,$re)){
			return $re[1];
		}
		
	}
	
}

function disk_change_fstab($dev,$ext,$target){
	if($target==null){
		events("disk_change_fstab():: No target specified...");
		return;
	}
	$line="$dev\t$target\t$ext\trw,relatime,errors=remount-ro,user_xattr,acl  0    1";
	$f=explode("\n",@file_get_contents("/etc/fstab"));
	
	$devRegex=str_replace("/", "\/", $dev);
	$devRegex=str_replace(".", "\.", $dev);
	
	
	@mkdir($target,0755,true);
	$found=false;
	while (list ($num, $val) = each ($f) ){
		if(preg_match("#^$devRegex\s+#", $val)){
			$f[$num]=$line;
			$found=true;
		}
	}
	if(!$found){$f[]=$line."\n";}
	@file_put_contents("/etc/fstab", @implode("\n", $f));
}
//##############################################################################
function events($text){
	$pid=@getmypid();
	$date=@date("h:i:s");
	$logFile=$GLOBALS["FILELOG"];

	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$date [$pid]: $text\n";}
	@fwrite($f, "$date [$pid]: $text\n");
	@fclose($f);
	@chmod($logFile, 0777);
}