<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",@implode(" ", $argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.milter.greylist.inc');


include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/ressources/class.fetchmail.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");


if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--mysql-defaults"){mysql_defaults();build();exit;}


function build(){
	$q=new mysql();
	$f[]="# /etc/milter-regex.conf";
	$f[]="";
	$f[]="accept";
	$f[]="connect // /127.0.0.1/";
	$f[]="";
	$f[]="# whitelist some criteria first";
	$f[]="accept";
	$f[]="helo /whitelist/";
	$f[]="helo /WORLD/";
	
	if($q->COUNT_ROWS("milterregex_acls", "artica_backup")==0){mysql_defaults();}
	
	$sql="SELECT * FROM milterregex_acls WHERE (`instance` = 'master') AND enabled=1 AND type='accept'";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){return;}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$f[]=buildline($ligne);
	}
	
	$f[]="";
	$f[]="# ####################### END WHITELIST ########################";
	
	$sql="SELECT * FROM milterregex_acls WHERE (`instance` = 'master') AND enabled=1 AND type='discard'";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){return;}
	if(mysql_num_rows($results)>0){
		$f[]="discard";
		while ($ligne = mysql_fetch_assoc($results)) {
			$f[]=buildline($ligne);
		}
		
	}
	
	
	
	$sql="SELECT * FROM milterregex_acls WHERE (`instance` = 'master') AND enabled=1 AND type='reject'";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){return;}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$description=$ligne["description"];
		$pattern=$ligne["pattern"];
		if(trim($pattern)==null){continue;}
		if($description==null){$description="Go away!";}
		$f[]="reject \"$description\"";
		$f[]=buildline($ligne);
	}

	
	$f[]="";
	$f[]="#tempfail \"Sender IP address not resolving\"";
	$f[]="#connect /\[.*\..*\]/ //";
	$f[]="";


	
	
	
	
	//$f[]="# reject things that look like they might come from a dynamic address";
	//$f[]="reject \"Dynamic Network ID1\"";
	//$f[]="connect /[0-9][0-9]*\-[0-9][0-9]*\-[0-9][0-9]*/ //";
	//$f[]="reject \"Dynamic Network ID2\"";
	//$f[]="connect /[0-9][0-9]*\.[0-9][0-9]*\.[0-9][0-9]*/ //";
	//$f[]="reject \"Dynamic Network ID3\"";
	//$f[]="connect /[0-9]{12}/e //";
	
	$f[]="";
	$f[]="# This is rather pointless, some receivers do callback checks using <>";
	$f[]="# and refuse service if you're not accepting <> (which is RFC compliant";
	$f[]="# for bounces). And sendmail itself will enforce legitimate format for";
	$f[]="# non-empty forms (enforcing a @, checking the domain, etc.).";
	$f[]="#reject \"Malformed MAIL FROM (not an email address or <>)\"";
	$f[]="#envfrom /(<>|<.*@.*>)/en";
	$f[]="";
	$f[]="reject \"Malformed RCPT TO (not an email address, not <.*@.*>)\"";
	$f[]="envrcpt /<(.*@.*|Postmaster)>/ein";
	$f[]="";
	//$f[]="reject \"HTML mail not accepted\"";
	//$f[]="( header ,^Content-Type\$,i ,^text/html,i or body ,^Content-Type: text/html,i ) and not header ,^From\$, ,deraadt,";
	$f[]="";
	$f[]="reject \"Swen worm (caps)\"";
	$f[]="header /^(TO|FROM|SUBJECT)\$/e // and not header /^From\$/i /telus.blackberry.net/";
	$f[]="";
	$f[]="#reject \"Swen worm (boundary)\"";
	$f[]="#header /^Content-Type\$/i /boundary=\"Boundary_(ID_/i";
	$f[]="#header /^Content-Type\$/i /boundary=\"[a-z]*\"/";
	$f[]="";
	$f[]="reject \"Swen worm (body)\"";
	$f[]="body ,^Content-Type: audio/x-wav; name=\"[a-z]*\.[a-z]*\",i";
	$f[]="body ,^Content-Type: application/x-msdownload; name=\"[a-z]*\.[a-z]*\",i";
	$f[]="";
	//$f[]="reject \"Unwanted (executable) attachment type\"";
	//$f[]="header ,^Content-Type\$, ,multipart/mixed, and body ,^Content-Type: application/, and body ,name=\".*\.(pif|exe|scr|com|bat|rar)\"\$,e";
	$f[]="";
	$f[]="reject \"Opt-out 'mailing list', spam, get lost (otcjournal)\"";
	$f[]="header /^X-List-Host\$/ /otcjournal/i";
	$f[]="header /^List-Owner\$/ /smallcapnetwork/i";
	$f[]="";
	$f[]="reject \"sonicsurf.ch spam, get lost\"";
	$f[]="header /^Received\$/ /\[195\.129\.5[89]\..*\]/";
	$f[]="";
	$f[]="reject \"Eat your socks, you fscking spammer.\"";
	$f[]="body /^The New Media Publishers AG/i";
	$f[]="body /^New.*Media.*Publisher/i";
	$f[]="body /^Socks and more AG/i";
	$f[]="body /^Business Corp\. for W\.& L\. AG/i";
	$f[]="body /Horizon *Business *Corp/";
	$f[]="body /Postfach, 6062 Wilen/i";
	$f[]="body /041.*661.*17.*(18|19|20)/e";
	$f[]="body /043.*317.*02.*8[0-9]/";
	$f[]="body /0_4_1_/";
	$f[]="body /W_i_l_e_n/i";
	$f[]="body ,^Ort/Datum:.*____,";
	$f[]="";
	
	@file_put_contents("/etc/milter-regex.conf", @implode("\n", $f));
	@chown("/etc/milter-regex.conf","postfix");
	
}

function buildline($ligne){
	$pattern=$ligne["pattern"];
	if(trim($pattern)==null){
		echo "Pattern is null rule {$ligne["zmd5"]}\n";
	}
	$description=$ligne["description"];
	
	$method=$ligne["method"];
	$token[]="i";
	if($ligne["reverse"]==1){$token[]="n";}
	if($ligne["extended"]==1){$token[]="e";}
	$tokens=@implode("", $token);
	if($method=="envfrom"){return "envfrom /$pattern/$tokens";}
	if($method=="envrcpt"){return "envrcpt /$pattern/$tokens";}	
	if($method=="helo"){return "helo /$pattern/$tokens";}
	if($method=="body"){return "body /$pattern/$tokens";}
	if($method=="header"){return "header $pattern$tokens";}	
	if($method=="connect"){return "connect /$pattern/$tokens //";}
	if($method=="subject"){return "header /^Subject$/ /$pattern/$tokens";}
}


function mysql_defaults(){
	$q=new mysql();
	$defaults='a:15:{i:0;a:10:{s:4:"zmd5";s:32:"255751b8841af40879ed34a929912800";s:8:"instance";s:6:"master";s:6:"method";s:7:"envfrom";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:29:"austinbrooks[0-9]*@gmail\.com";s:11:"description";s:27:"You are a spamer from gMail";s:5:"zDate";s:19:"2015-08-23 21:48:42";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:1;a:10:{s:4:"zmd5";s:32:"17d14c5d74531d6c3f6cdb3882a638a4";s:8:"instance";s:6:"master";s:6:"method";s:7:"envfrom";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:31:"jeff.wooderson[0-9]*@gmail\.com";s:11:"description";s:27:"You are a spamer from gMail";s:5:"zDate";s:19:"2015-08-23 21:49:07";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:2;a:10:{s:4:"zmd5";s:32:"dbcd58ec5fbe924ba252bd080808ece8";s:8:"instance";s:6:"master";s:6:"method";s:7:"envfrom";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:16:"@grupoziike\.com";s:11:"description";s:29:"You are a spamer from godaddy";s:5:"zDate";s:19:"2015-08-23 21:49:48";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:3;a:10:{s:4:"zmd5";s:32:"9ac5a778b805ee40273384b88ad7dfb2";s:8:"instance";s:6:"master";s:6:"method";s:4:"helo";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:12:"127\.0\.0\.1";s:11:"description";s:42:"Spoofed HELO (my own IP address, nice try)";s:5:"zDate";s:19:"2015-08-23 21:50:43";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:4;a:10:{s:4:"zmd5";s:32:"3600983c0dec8252b64bb3bab8799cda";s:8:"instance";s:6:"master";s:6:"method";s:4:"helo";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:2:"\.";s:11:"description";s:37:"Malformed HELO (not a domain, no dot)";s:5:"zDate";s:19:"2015-08-23 22:00:59";s:7:"reverse";s:1:"1";s:8:"extended";s:1:"0";}i:5;a:10:{s:4:"zmd5";s:32:"d5cd851c1395801890940b3f87fe39d9";s:8:"instance";s:6:"master";s:6:"method";s:7:"envrcpt";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:20:"<(.*@.*|Postmaster)>";s:11:"description";s:53:"Malformed RCPT TO (not an email address, not <.*@.*>)";s:5:"zDate";s:19:"2015-08-23 22:04:40";s:7:"reverse";s:1:"1";s:8:"extended";s:1:"1";}i:6;a:10:{s:4:"zmd5";s:32:"dc982138e45ec1ef483ed667dc79f791";s:8:"instance";s:6:"master";s:6:"method";s:6:"header";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:30:"/^From\$/i /link-builder\.com/";s:11:"description";s:7:"Spammer";s:5:"zDate";s:19:"2015-08-23 22:08:07";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:7;a:10:{s:4:"zmd5";s:32:"f0759c221e95412988e06fe174c19105";s:8:"instance";s:6:"master";s:6:"method";s:7:"subject";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:19:"Expecting your mail";s:11:"description";s:8:"Go away!";s:5:"zDate";s:19:"2015-08-23 22:12:52";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:8;a:10:{s:4:"zmd5";s:32:"e7ec449e6c80e8388aa8b97c0139cc72";s:8:"instance";s:6:"master";s:6:"method";s:7:"subject";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:25:"Notice to appear in Court";s:11:"description";s:8:"Go away!";s:5:"zDate";s:19:"2015-08-23 22:13:20";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:9;a:10:{s:4:"zmd5";s:32:"e98626e0a6af45bb7640f6cd3fdd17d9";s:8:"instance";s:6:"master";s:6:"method";s:7:"subject";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:16:"You miss this \?";s:11:"description";s:8:"Go away!";s:5:"zDate";s:19:"2015-08-23 22:13:54";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:10;a:10:{s:4:"zmd5";s:32:"a7ea66a132d85b3bc150e471579b282f";s:8:"instance";s:6:"master";s:6:"method";s:7:"subject";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:31:"Say "Bye-Bye" to Adwords Budget";s:11:"description";s:8:"Go away!";s:5:"zDate";s:19:"2015-08-23 22:14:26";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:11;a:10:{s:4:"zmd5";s:32:"5ac8f1747798fa7ffbc74d0ebae0b524";s:8:"instance";s:6:"master";s:6:"method";s:7:"subject";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:32:"Website review and analysis for:";s:11:"description";s:8:"Go away!";s:5:"zDate";s:19:"2015-08-23 22:14:58";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:12;a:10:{s:4:"zmd5";s:32:"29ba904af3da72c4e76ebeb761fb136c";s:8:"instance";s:6:"master";s:6:"method";s:7:"subject";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:33:"^READ THIS IMPORTANT INFORMATION$";s:11:"description";s:36:"Please Get more info in your subject";s:5:"zDate";s:19:"2015-08-23 22:47:48";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:13;a:10:{s:4:"zmd5";s:32:"4877944166423fb711c1a6aa162b4414";s:8:"instance";s:6:"master";s:6:"method";s:4:"body";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:21:"emailtrack[0-9]*\.com";s:11:"description";s:20:"emailtrack is banned";s:5:"zDate";s:19:"2015-08-23 23:00:44";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}i:14;a:10:{s:4:"zmd5";s:32:"bc54c17e223173894a5bc4d54a569e25";s:8:"instance";s:6:"master";s:6:"method";s:4:"body";s:4:"type";s:6:"reject";s:7:"enabled";s:1:"1";s:7:"pattern";s:21:":\/\/go\.madmimi\.com";s:11:"description";s:24:"No spam from madmimi.com";s:5:"zDate";s:19:"2015-08-23 23:09:42";s:7:"reverse";s:1:"0";s:8:"extended";s:1:"0";}}';
	$MAIN=unserialize($defaults);
	while (list ($num, $ligne) = each ($MAIN) ){
		
		while (list ($a, $b) = each ($ligne) ){
			$ligne[$a]=mysql_escape_string2($b);
		}
		
		$description=$ligne["description"];
		$pattern=$ligne["pattern"];
		$method=$ligne["method"];
		$zmd5=$ligne["zmd5"];
		$instance=$ligne["instance"];
		$method=$ligne["method"];
		$type=$ligne["type"];
		$enabled=$ligne["enabled"];
		$reverse=$ligne["reverse"];
		$extended=$ligne["extended"];
		$zDate=$ligne["zDate"];
		
		$sql="INSERT INTO `milterregex_acls`
		(`zmd5`,`zDate`,`instance`,`method`,`type`,`pattern`,`description`,`enabled`,`reverse`,`extended`) VALUES
		('$zmd5','$zDate','$instance','$method','$type','$pattern','$description',$enabled,$reverse,$extended);";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return;}
	}
	
	
}
