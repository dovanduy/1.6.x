<?php


echo "Patching EXT4 filesystem...\n";
$f=explode("\n",@file_get_contents("/etc/fstab"));

$change=false;

while (list ($num, $val) = each ($f) ){
	
	if(preg_match("#(.+?)\s+(.+?)\s+ext4\s+(.+?)\s+([0-9]+)\s+([0-9]+)#", $val,$re)){
		$newoptions=PatchOptions($re[3]);
		echo "EXT4 {$re[1]} with options {$re[3]} changed to $newoptions\n";
		$f[$num]="{$re[1]}\t{$re[2]}\text4\t$newoptions\t{$re[4]}\t{$re[5]}";
		echo "EXT4 {$re[1]} change journal to journal_data_writeback\n";
		shell_exec("/sbin/tune2fs -o journal_data_writeback {$re[1]}");
		$change=true;
		continue;
		
	}
	if($change){
		@file_put_contents("/etc/fstab", @implode("\n", $f));
	}
	
}



function PatchOptions($options){
	$f=explode(",",$options);
	$OPTS["defaults"]=true;
	while (list ($num, $val) = each ($f) ){
		$val=trim($val);
		if(trim($val)==null){continue;}
		$OPTS[$val]=true;
	}
	
	unset($OPTS["defaults"]);
	
	$OPTS["rw"]=true;
	$OPTS["noatime"]=true;
	$OPTS["data=writeback"]=true;
	$OPTS["barrier=0"]=true;
	$OPTS["commit=100"]=true;
	$OPTS["nobh"]=true;
	$OPTS["user_xattr"]=true;
	$OPTS["acl"]=true;
	
	while (list ($opts2, $val) = each ($OPTS) ){
		$t[]=$opts2;
		
	}
	return @implode(",", $t);
	
	
}