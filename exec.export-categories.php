<?php 
set_time_limit(0);
$GLOBALS["CATS"][]="agressive";
$GLOBALS["CATS"][]="astrology";
$GLOBALS["CATS"][]="audio-video";
$GLOBALS["CATS"][]="automobile/bikes";
$GLOBALS["CATS"][]="automobile/boats";
$GLOBALS["CATS"][]="automobile/cars";
$GLOBALS["CATS"][]="automobile/planes";
$GLOBALS["CATS"][]="blog";
$GLOBALS["CATS"][]="chat";
$GLOBALS["CATS"][]="cleaning";
$GLOBALS["CATS"][]="clothing";
$GLOBALS["CATS"][]="dangerous_material";
$GLOBALS["CATS"][]="dating";
$GLOBALS["CATS"][]="downloads";
$GLOBALS["CATS"][]="drugs";
$GLOBALS["CATS"][]="filehosting";
$GLOBALS["CATS"][]="finance/banking";
$GLOBALS["CATS"][]="finance/insurance";
$GLOBALS["CATS"][]="finance/moneylending";
$GLOBALS["CATS"][]="finance/realestate";
$GLOBALS["CATS"][]="forums";
$GLOBALS["CATS"][]="gamble";
$GLOBALS["CATS"][]="games";
$GLOBALS["CATS"][]="governments";
$GLOBALS["CATS"][]="hacking";
$GLOBALS["CATS"][]="hobby/arts";
$GLOBALS["CATS"][]="hobby/cooking";
$GLOBALS["CATS"][]="hobby/games";
$GLOBALS["CATS"][]="hobby/pets";
$GLOBALS["CATS"][]="hospitals";
$GLOBALS["CATS"][]="housing/reale_state_";
$GLOBALS["CATS"][]="humanitarian";
$GLOBALS["CATS"][]="imagehosting";
$GLOBALS["CATS"][]="isp";
$GLOBALS["CATS"][]="jobsearch";
$GLOBALS["CATS"][]="malware";
$GLOBALS["CATS"][]="marketingware";
$GLOBALS["CATS"][]="medical";
$GLOBALS["CATS"][]="mixed_adult";
$GLOBALS["CATS"][]="mobile-phone";
$GLOBALS["CATS"][]="models";
$GLOBALS["CATS"][]="movies";
$GLOBALS["CATS"][]="music";
$GLOBALS["CATS"][]="news";
$GLOBALS["CATS"][]="passwords";
$GLOBALS["CATS"][]="phishing";
$GLOBALS["CATS"][]="porn";

$GLOBALS["CATS"][]="publicite";
$GLOBALS["CATS"][]="radiotv";
$GLOBALS["CATS"][]="reaffected";
$GLOBALS["CATS"][]="recreation/humor";
$GLOBALS["CATS"][]="recreation/nightout";
$GLOBALS["CATS"][]="recreation/schools";
$GLOBALS["CATS"][]="recreation/sports";
$GLOBALS["CATS"][]="getmarried";
$GLOBALS["CATS"][]="police";
$GLOBALS["CATS"][]="recreation/travel";
$GLOBALS["CATS"][]="recreation/wellness";
$GLOBALS["CATS"][]="redirector";
$GLOBALS["CATS"][]="religion";
$GLOBALS["CATS"][]="remote-control";
$GLOBALS["CATS"][]="science/computing";
$GLOBALS["CATS"][]="science/weather";
$GLOBALS["CATS"][]="searchengines";
$GLOBALS["CATS"][]="sect";
$GLOBALS["CATS"][]="sex/lingerie";
$GLOBALS["CATS"][]="sexual_education";
$GLOBALS["CATS"][]="shopping";
$GLOBALS["CATS"][]="socialnet";
$GLOBALS["CATS"][]="spyware";
$GLOBALS["CATS"][]="sslsites";
$GLOBALS["CATS"][]="strong_redirector";
$GLOBALS["CATS"][]="tracker";
$GLOBALS["CATS"][]="translators";
$GLOBALS["CATS"][]="updatesites";
$GLOBALS["CATS"][]="violence";
$GLOBALS["CATS"][]="warez";
$GLOBALS["CATS"][]="weapons";
$GLOBALS["CATS"][]="webmail";
$GLOBALS["CATS"][]="webphone";
$GLOBALS["CATS"][]="webplugins";
$GLOBALS["CATS"][]="webradio";
$GLOBALS["CATS"][]="webtv";
$GLOBALS["CATS"][]="celebrity";
$GLOBALS["CATS"][]="books";
$GLOBALS["CATS"][]="maps";

$unix=new unix();
$URIBASE=$unix->MAIN_URI();

	while (list ($index, $category) = each ($GLOBALS["CATS"]) ){
		echo "echo sending Extracting $category\n";
		shell_exec("/usr/bin/wget $URIBASE/compile-www.php?ExportCategory=$index -O /tmp/tmp.txt");
		echo "echo sending Extracting $category done\n";
	}
shell_exec("/usr/bin/wget $URIBASE/compile-www.php?compress-categories=yes -O /tmp/tmp.txt");	


