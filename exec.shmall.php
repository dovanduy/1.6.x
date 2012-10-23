<?php
$TotalKbytes=0;
$f=file("/proc/meminfo");

while (list ($num, $ligne) = each ($f) ){
	
	if(preg_match("#MemTotal:\s+([0-9]+)\s+#", $ligne,$re)){
		
		$TotalKbytes=$re[1];
		$TotalBytes=$TotalKbytes*1024;
		echo "shmall Max memory: {$TotalKbytes}kb ($TotalBytes bytes)\n";
	}
}
if($TotalKbytes==0){die("Fatal error ".basename(__FILE__));}
$PageSize=exec("/usr/bin/getconf PAGE_SIZE");
echo "shmall: PAGE_SIZE =  $PageSize\n";
$ShmallValue=$TotalBytes/$PageSize;
$ShmallMaxValue=$ShmallValue*$PageSize;
$ShmallMaxValueKB=round($ShmallMaxValue/1024,2);
$ShmallValueKB=round($ShmallValue/1024,2);
echo "shmall Cleaning /etc/sysctl.conf\n";
@unlink("/etc/sysctl.conf");
@file_put_contents("/etc/sysctl.conf", "");
echo "shmall: kernel.shmall  =  $ShmallValue bytes ($ShmallValueKB KB)\n";
echo "shmall: kernel.shmmax  =  $ShmallMaxValue bytes ($ShmallMaxValueKB KB)\n";
shell_exec("/bin/echo $ShmallMaxValue >/proc/sys/kernel/shmmax");
shell_exec("/bin/echo $ShmallValue >/proc/sys/kernel/shmall");
shell_exec("/bin/echo 4096 >/proc/sys/kernel/shmmni");
shell_exec("/sbin/sysctl -w vm.overcommit_memory=1");
shell_exec("/sbin/sysctl vm.vfs_cache_pressure=100");
shell_exec("/sbin/sysctl -p");
