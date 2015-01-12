<?php
$array=explode("\n",@file_get_contents("/home/dtouzeau/Bureau/laye7.txt"));

while (list ($index, $ligne) = each ($array) ){
	$ligne=trim($ligne);
	if($ligne==null){continue;}
	if(preg_match("#<a href=\"layer7-protocols\/protocols\/(.+?).pat\">(.+?)<\/a>#", $ligne,$re)){
		
		$uri="http://l7-filter.sourceforge.net/layer7-protocols/protocols/{$re[1]}.pat";
		$key=trim($re[2]);
		$ARRAY[$key]["URL"]=$uri;
		continue;
	}
	
	if(preg_match("#LEVEL=([0-9]+)#", $ligne,$re)){
		$ARRAY[$key]["LEVEL"]=$re[1];
		continue;
		
	}
	
	if($ligne<>null){
		if(!preg_match("#<a#", $ligne)){
			$ARRAY[$key]["EXPLAIN"]=$ligne;
		}
	}
	
}

@file_put_contents("/usr/share/artica-postfix/ressources/databases/layer7.db", serialize($ARRAY));