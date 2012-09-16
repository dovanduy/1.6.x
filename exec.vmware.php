<?php
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");


$unix=new unix();

$dmidecode=$unix->find_program("dmidecode");
if(!is_file($dmidecode)){return;}


exec("$dmidecode 2>&1",$results);
$vmware=false;
while (list ($num, $ligne) = each ($results) ){
	
	if(preg_match("#Manufacturer.+?VMware#i", $ligne)){$vmware=true;break;}
}

if(!$vmware){
	echo "Starting......: Not a VMware machine...\n";
	die();
}
$echo=$unix->find_program("echo");
$array=$unix->dirdir("/sys/block");

while (list ($num, $directory) = each ($array) ){
	if(is_file("$directory/queue/scheduler")){
		echo "Starting......: VMware, turn scheduler to noop on ". basename($directory)."\n";
		shell_exec("$echo noop >$directory/queue/scheduler");
	}
	
}
?>
