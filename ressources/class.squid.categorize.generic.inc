<?php
class generic_categorize{
	//http://adblockrules.org/download.php?type=all
	// http://winhelp2002.mvps.org/hosts.txt
	//http://www.oxymium.net/policyrss.php?type=sender-domain
	
	private function Blog($www){
		
		
		$array=array("jimdo","canalblog","blogia","freeblogit.com","skynetblogs.be","wordpress","freewebspace","hubpages","wordpress.com",
		"e-monsite","yuku","tumblr","over-blog","ucoz","punt","grygorczuk","skyblog","blogspirit",
		"freedomblogging","bloguez","pagesperso-orange","areavoices","blogspot","typepad","xanga.com",
		"monsite-orange","blogspirit","blogspace","livejournal","blog.mongenie","diaryclub","blogcu.com",
		"blogs-r","at.webry.info","hautetfort.com","dreamwidth.org","bbs.mythem.es","diary","free","cafe24.com",
		"freeblog","myblog","egloos","coueb.fr","viabloga","blog.sfr.fr",
		);
		
		if($this->ParseArray($array, $www)){return "blog";}
		
		while (list ($none, $pattern) = each ($array) ){
			if(preg_match("#\.$pattern\.[a-z\.]+$#", $www)){return "blog";}
			if(preg_match("#^$pattern\.[a-z\.]+$#", $www)){return "blog";}
		}
		
		if(preg_match("#^blogsearch.*#", $www)){return "blog";}
		if(preg_match("#^blog\..+?#", $www)){return "blog";}
		if(preg_match("#blog\.[a-z]+$#", $www)){return "blog";}
		if(preg_match("#^blog[a-z]+#", $www)){return "blog";}
		if(preg_match("#blog[0-9]+\.fc2\.com$#", $www)){return "blog";}
		if(preg_match("#s[0-9]+\.xrea\.com$#", $www)){return "blog";}

		if(preg_match("#\.blogs\.liberation\.fr$#", $www)){return "blog";}
		if(preg_match("#\.blogs\.nouvelobs\.com$#", $www)){return "blog";}
		if(preg_match("#\.100webspace\.net$#", $www)){return "blog";}
		if(preg_match("#\.blogspot\.[\.a-z]+$#", $www)){return "blog";}
		if(preg_match("#\.blog\.lemonde\.[a-z]+$#", $www)){return "blog";}
		if(preg_match("#leblogde#", $www)){return "blog";}
		if(preg_match("#\.blog.club-corsica\.com$#", $www)){return "blog";}
		if(preg_match("#\.blogs\.sudouest\.fr$#", $www)){return "blog";}
		if(preg_match("#\.blogspot\.com\.ar$#", $www)){return "blog";}
		if(preg_match("#\.blog2b\.net$#", $www)){return "blog";}
		if(preg_match("#\.unblog\.fr$#", $www)){return "blog";}
		if(preg_match("#\.skyrock\.com$#", $www)){return "blog";}
		if(preg_match("#\.over-blog\.fr$#", $www)){return "blog";}
		if(preg_match("#\.perso\.sfr\.fr$#", $www)){return "blog";}
		if(preg_match("#\.centerblog\.net$#", $www)){return "blog";}
		if(preg_match("#\.blogfa\.com$#", $www)){return "blog";}
		if(preg_match("#\.ifunnyblog\.com$#", $www)){return "blog";}
		if(preg_match("#\.narod\.ru$#", $www)){return "blog";}
		if(preg_match("#\.weebly\.com$#", $www)){return "blog";}
		if(preg_match("#\.posterous\.com$#", $www)){return "blog";}
		if(preg_match("#\.mygirlyspace\.[a-z]+$#", $www)){return "blog";}
		if(preg_match("#.*?-blog\.[a-z]+$#", $www)){return "blog";}
		if(preg_match("#\.wifeo\.com$#", $www)){return "blog";}
		if(preg_match("#\.skynetblogs\.[a-z]+$#", $www)){return "blog";}
		if(preg_match("#\.theseblogs\.com$#", $www)){return "blog";}
		if(preg_match("#\.arabblogs\.com$#", $www)){return "blog";}
		if(preg_match("#\.blogger\.com\.br$#", $www)){return "blog";}
		if(preg_match("#\.blog\.#", $www)){return "blog";}
		if(preg_match("#\.blogs\.#", $www)){return "blog";}
		if(preg_match("#\.chez-alice\.fr#", $www)){return "blog";}
		if(preg_match("#\.blogs\..*?\.[a-z]+$#", $www)){return "blog";}
		if(preg_match("#\.blox\.pl$#", $www)){return "blog";}
		if(preg_match("#\.dy\.fi$#", $www)){return "blog";}
		if(preg_match("#\.fotopages\.com$#", $www)){return "blog";}
		if(preg_match("#\.arabb\.net$#", $www)){return "blog";}
		if(preg_match("#\.persiangig\.com$#", $www)){return "blog";}
		if(preg_match("#\.googlepages\.com$#", $www)){return "blog";}
		if(preg_match("#\.web44\.net$#", $www)){return "blog";}
		if(preg_match("#\.blogeasy\.com$#", $www)){return "blog";}
		if(preg_match("#\.blogosfera\.uol\.com\.br$#", $www)){return "blog";}
		if(preg_match("#\.host\.adobe\.com$#", $www)){return "blog";}
		if(preg_match("#\.sm4\.biz$#", $www)){return "blog";}
		if(preg_match("#\.mex\.tl$#", $www)){return "blog";}
		if(preg_match("#\.globat\.com$#", $www)){return "blog";}
		if(preg_match("#\.wix\.com$#", $www)){return "blog";}
		if(preg_match("#\.altervista\.org$#", $www)){return "blog";}
		if(preg_match("#\.girlshopes\.com$#", $www)){return "blog";}
		if(preg_match("#\.blogy\.[a-z]+$#", $www)){return "blog";}
		if(preg_match("#\.monsite\.orange\.[a-z]+$#", $www)){return "blog";}
		if(preg_match("#blogers\.#", $www)){return "blog";}
	}
	
	
	private function transports($www){
		$array=array("ups.com","vnf.fr","ptrss.com","teleroute","fedex");
		if($this->ParseArray($array, $www)){return "transport";}
		
		if(preg_match("#^transport-.*?\.[a-z]+#", $www)){return "transport";}
		if(preg_match("#(chronopost|carriers|autocars|taxiauto|\-transport\-)#", $www)){return "transport";}
		if(preg_match("#^transports-#", $www)){return "transport";}
		if(preg_match("#transports\.[a-z]+#", $www)){return "transport";}
		if(preg_match("#\.dhl-([a-z]+)\.com$#", $www)){return "transport";}		
		if(preg_match("#-transporter\.[a-z\.]+$#", $www)){return "transport";}
		if(preg_match("#transport\.[a-z\.]+$#", $www)){return "transport";}
		
	}
	
	private function Tracker($www){
		if(preg_match("#[0-9\-]+\.fbx\.proxad\.net$#", $www)){return "dynamic";}
		
		
		$array=array("cnzz","xiti","resultspage","hstrck.com","memecounter","meetrics","effectivemeasure","irotracker","rtrk","omtrdc.net",
				"ezakus","statistik","odiso","online-metrix","cleervoyance","pandastats","estadisticasgratis",
				"lognormal","zeroredirect","quantcount","raster-xml","revolvermaps","optimizely","hcatracker","adlooxtracking.com",
				"redhelper","ceipmsn","userdail","go2cloud.org","adlooxtracking","mobileapptracking","sindelclick.com","hitslink","rtrk.nl","shinystat.com","cmos.greencompute.org","ninjastat.com","oadz.com",
		);
		
		if(preg_match("#[a-z][0-9]+tracking[0-9]+\.[a-z]+$#", $www)){return "tracker";}
		
		if($this->ParseArray($array, $www)){return "tracker";}
		
		include_once(dirname(__FILE__)."/class.squid.categorize.tracker.php");
		$track=new categorize_tracker();
		$val=$track->Tracker($www);
		if($val<>null){return $val;}
		if(preg_match("#^metrics\.#", $www)){return "tracker";}
		if(preg_match("#\.trw[0-9]+\.com$#", $www)){return "tracker";}
		if(preg_match("#\.2o7\.net$#", $www)){return "tracker";}
		if(preg_match("#^webanalytics\.#", $www)){return "tracker";}
		if(preg_match("#\.traficstat\.[a-z]+$#",$www)){return "tracker";}
		if(preg_match("#\.popstats\.com\.br$#",$www)){return "tracker";}
		if(preg_match("#\.trck\.me$#",$www)){return "tracker";}
		if(preg_match("#^nalog[0-9]+\.kaluga\.net$#",$www)){return "tracker";}
		if(preg_match("#\.web-force\.eu$#",$www)){return "tracker";}
		if(preg_match("#\.trafficg\.com$#",$www)){return "tracker";}
		if(preg_match("#cptrack[0-9]+\.[a-z]+$#",$www)){return "tracker";}
		if(preg_match("#\.online-metrix\.net$#",$www)){return "tracker";}
		if(preg_match("#\.track\.clicksure\.com$#",$www)){return "tracker";}
		if(preg_match("#\.track[0-9]+\.com$#",$www)){return "tracker";}
		
		if(preg_match("#^cedexis-#",$www)){return "tracker";}
		if(preg_match("#^statistik\.#",$www)){return "tracker";}
		if(preg_match("#\.tracker\.prq\.to$#",$www)){return "tracker";}
		
		
		if(preg_match("#\.histats\.su$#",$www)){return "tracker";}
		if(preg_match("#surveytracker\.com$#",$www)){return "tracker";}
		if(preg_match("#^piwik\..*#",$www)){return "tracker";}
		
		if(preg_match("#\.ehistats\.[a-z]+$#",$www)){return "tracker";}
		if(preg_match("#\.iestats\.[a-z]+$#",$www)){return "tracker";}
		if(preg_match("#^logc.*?\.at\.pagesjaunes\.fr$#",$www)){return "tracker";}
		if(preg_match("#log\.optimizely\.com$#",$www)){return "tracker";}
		
	}
	
	private function society($www){
		
		$array=array("virgilio.it","lifehack.org","ezinemark","pixnet.net","oeeee","beon.ru","jysq.net","funmehr.com","cuantarazon.com");
		if($this->ParseArray($array, $www)){return "society";}
		if(preg_match("#\.thinkpage\.cn$#", $www)){return "society";}
		if(preg_match("#\.biobiochile\.cl$#", $www)){return "society";}
		if(preg_match("#\.xiangrikui\.com$#", $www)){return "society";}
		if(preg_match("#\.interia\.pl$#", $www)){return "society";}
		if(preg_match("#^img.*?\.mynet\.com$#", $www)){return "society";}
		if(preg_match("#\.afisha\.(ru|net)$#", $www)){return "society";}
		if(preg_match("#\.allabout\.co\.jp$#", $www)){return "society";}
		if(preg_match("#\.tistory\.com$#", $www)){return "society";}
		if(preg_match("#\.sympatico\.ca$#", $www)){return "society";}
		if(preg_match("#\.meltybuzz\.[a-z]+$#", $www)){return "society";}
		if(preg_match("#\.jampo\.com\.ua$#", $www)){return "society";}
		if(preg_match("#\.onet\.pl$#", $www)){return "society";}
		if(preg_match("#\.globo\.com$#", $www)){return "society";}
		if(preg_match("#\.alfa\.lt$#", $www)){return "society";}
		if(preg_match("#\.qip\.ru$#", $www)){return "society";}
		if(preg_match("#\.alnaddy\.com$#", $www)){return "society";}
		if(preg_match("#\.alnaddycdn\.com$#", $www)){return "society";}
		if(preg_match("#\.anazahra\.com$#", $www)){return "society";}
		if(preg_match("#\.cuantarazon\.com$#", $www)){return "society";}
		
		
		
	}
	
	private function remote_control($www){
		
		$array=array("teamviewer","webex","islonline");
		if($this->ParseArray($array, $www)){return "remote-control";}
		
	}
	
	
	private function Filehosting($www){
			
			

		
		$array=array("filewatcher","cloudfront","edgesuite","amazonaws","smcloud","mediafire","dnsmex",
				"akamai","dropbox","dropboxusercontent","yousendit","scene7","vo\.msecnd","depositfiles",
				"zingload","pinterest","e-loader","wuala","wetransfer"
		);
		
		if($this->ParseArray($array, $www)){return "filehosting";}
		
		
		
		
		
		if(preg_match("#^[0-9]+.*?\.bb\.sky\.com$#", $www)){return "filehosting";}
		
		if(preg_match("#mobstor\..*?\.yahoo\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#4shared\.[a-z]+$#",$www)){return "filehosting";}
		
		if(preg_match("#\.docs\.google\.[a-z]+$#", $www)){return "filehosting";}
		if(preg_match("#\.drive\.google\.[a-z]+$#", $www)){return "filehosting";}		
		if(preg_match("#\.ftpperso\.free\.fr$#",$www)){return "filehosting";}
		
		if(preg_match("#\.scene7\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.cloudfiles\.rackspacecloud\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.static69\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.cachefly\.net$#",$www)){return "filehosting";}
		if(preg_match("#^files\.static[0-9]+\.com$#",$www)){return "filehosting";}
		if(preg_match("#noop-q\.cddb2\.cddbp\.net$#",$www)){return "filehosting";}
		if(preg_match("#\.media\.tumblr\.com$#",$www)){return "filehosting";}
		
		if(preg_match("#\.uploaded\.to$#",$www)){return "filehosting";}
		if(preg_match("#\.freakshare\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.e-loader\.net$#",$www)){return "filehosting";}
		if(preg_match("#\.safesync\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.livefilestore\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.scribdassets\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.theuploadhelp\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.storage\.msn\.com$#",$www)){return "filehosting";}
		if(preg_match("#cdn[0-9]+\..*?\.yumping\.info$#",$www)){return "filehosting";}
		if(preg_match("#\.theuploadhelp\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.theuploadhelp\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.file-static\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.filestatic\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.openfile\.ru$#",$www)){return "filehosting";}
		if(preg_match("#\.1fichier\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#\.uploadhero\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#\.livefile\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#\.filedefend\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#\.users\.storage\.live\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#\.zippyshare\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#\.gigapeta\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#\.storage\.live\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#\.uploaded\.net$#",$www)){return "filehosting";}
		if(preg_match("#\.turbobit\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#\.uploadhero\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.hotfile\.com$#",$www)){return "filehosting";}
		if(preg_match("#^fs.*?\.www\.ex\.ua$#",$www)){return "filehosting";}
		if(preg_match("#\.dpfile\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.uptobox\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.minus\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.letitbit\.net$#",$www)){return "filehosting";}
		if(preg_match("#\.pat00\.de$#",$www)){return "filehosting";}
		if(preg_match("#\.sockshare\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.multiupload\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#static-megahamster\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#\.directupload\.net$#",$www)){return "filehosting";}
		if(preg_match("#\.fileswap\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.hubic\.me$#",$www)){return "filehosting";}
		if(preg_match("#\.yourupload\.com$#",$www)){return "filehosting";}
		if(preg_match("#^pastebin\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#\.pastebin\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#\.wetransferbeta\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#\.wetransfer\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#\.archive\.org$#",$www)){return "filehosting";}
		if(preg_match("#\.ryushare\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.hugefiles\.net$#",$www)){return "filehosting";}
		if(preg_match("#\.dvetech\.fr$#",$www)){return "filehosting";}
		if(preg_match("#\.directupload\.net$#",$www)){return "filehosting";}
	}
	
	
	private function imagehosting($www){
		
		$array=array("bababian","photobucket","servimg","edgefcs","imagevenue","mzsimg.com","web-album.org","iofferphoto","rackcdn","t26","tinypic","imageshack","stylesfire","fotolog.com");
		if($this->ParseArray($array, $www)){return "imagehosting";}

		if(strpos($www, ".yimg.com")>0){return "imagehosting";}
		if(strpos($www, ".imageshack.")>0){return "imagehosting";}
		if(strpos($www, ".turboimagehost.com")>0){return "imagehosting";}
		if(strpos($www, ".imagevenue.com")>0){return "imagehosting";}
		if(preg_match("#fsimg\.ru$#",$www)){return "imagehosting";}
		if(preg_match("#xooimage.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.ytimg.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.chomikuj.pl$#",$www)){return "imagehosting";}
		if(preg_match("#\.ggpht.com$#",$www)){return "imagehosting";}
		if(preg_match("#(photofile|instagram)#",$www)){return "imagehosting";}
		
		if(preg_match("#\.upload69\.net$#",$www)){return "imagehosting";}
		if(preg_match("#\.image.*?\.edgesuite\.net$#",$www)){return "imagehosting";}
		if(preg_match("#\.slide\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.edgecastcdn.net$#",$www)){return "imagehosting";}
		if(preg_match("#\.pic\.centerblog\.net$#",$www)){return "imagehosting";}
		
		if(preg_match("#\.smugmug\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.cbsistatic\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.kn3\.net$#",$www)){return "imagehosting";}
		if(preg_match("#\.mzstatic\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.pbase\.com$#",$www)){return "imagehosting";}
		if(preg_match("#^distilleryimage[0-9]+\.instagram.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.0zz0\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.comic\.cdvcdn\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.ch-img\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.photo\.store\.qq\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.cdn101\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.adoosimg\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.caidimg\.com$#",$www)){return "imagehosting";}
		if(preg_match("#img[0-9]+\.flixcart\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.twitgoo\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.webstore-images-eu\.[a-z]+$#",$www)){return "imagehosting";}
		if(preg_match("#\.dmcdn\.net$#",$www)){return "imagehosting";}
		if(preg_match("#\.up1up2\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.imagebam\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.postimage\.org$#",$www)){return "imagehosting";}
		if(preg_match("#\.myimg\.[a-z]+$#",$www)){return "imagehosting";}
		if(preg_match("#\.postimage\.org+$#",$www)){return "imagehosting";}
		if(preg_match("#\.ph\.126\.net$#",$www)){return "imagehosting";}
		if(preg_match("#\.okr\.ro$#",$www)){return "imagehosting";}
		if(preg_match("#\.imgchili\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.upanh\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.exs\.cx$#",$www)){return "imagehosting";}
		if(preg_match("#\.hiboox\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.imagestime\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.imageporter\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.pixi\.ru$#",$www)){return "imagehosting";}
		if(preg_match("#\.imagerix\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.radikal\.ru$#",$www)){return "imagehosting";}
		if(preg_match("#^s[0-9]+\.ucoz\.(net|com)$#",$www)){return "imagehosting";}
		if(preg_match("#\.ipicture\.ru$#",$www)){return "imagehosting";}
		if(preg_match("#\.fastpic\.ru$#",$www)){return "imagehosting";}
		if(preg_match("#\.casimages\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.imgr\.it$#",$www)){return "imagehosting";}
		if(preg_match("#\.thebrief\.ru$#",$www)){return "imagehosting";}
		if(preg_match("#\.kitik\.at$#",$www)){return "imagehosting";}
		if(preg_match("#\.imgiz.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.imagenar\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.vector\.us$#",$www)){return "imagehosting";}
		if(preg_match("#\.imagevenue\.com$#",$www)){return "imagehosting";}
	}
	
	private function audiovideo($www){
		
		$array=array("googlevideo",
				"youtube","piximedia","vuclip.com",
				"atmcdn",
				"gamaniak",
				"listentoyoutube",
				"video.msn.com","shockya","brightcove","videostep.com",
				"castup","cdnvideo","ustream.tv","movideo.com",
				"rocktube","vimeocdn","freemake","windowsmedia","tare","livestream","nowvideo","vimeo","real",
				"dropshots","allmyvideos","pcvideo","loadup","videobam","popscreencdn","ultimedia",
				"firststreaming","videoegg","lkimg","flvstorage","topclip","dailymotion","trilulilu","videoplaza","video-loader");
		
		if($this->ParseArray($array,$www)){return "audio-video";}

		if(preg_match("#video.*?\.msn\.[a-z]+$#", $www)){return "audio-video";}
		if(preg_match("#(videomore|moviclip)\.[a-z]+$#",$www)>0){return "audio-video";}
		if(preg_match("#^video\..+?#",$www)>0){return "audio-video";}
		if(preg_match("#\.video.[a-z]-msn\.com$#",$www)>0){return "audio-video";}
		if(preg_match("#^video[0-9]+\.#",$www)>0){return "audio-video";}
		if(preg_match("#^video[0-9\-]+\.rutube\.ru$#", $www)){return "audio-video";}
		if(preg_match("#\.pop-music-dvds\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.media.*?.cddb[0-9]+\.cddbp.[a-z]+$#", $www)){return "audio-video";}
		if(preg_match("#\.video\.ckcdn\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.video\.yahoo\.[a-z]+$#", $www)){return "audio-video";}
		if(preg_match("#\.video\.mynet\.com$#", $www)){return "audio-video";}
		if(preg_match("#^video.+?\.pagesjaunes\.[a-z]$#", $www)){return "audio-video";}
		if(preg_match("#^video-.*?\.pagesjaunes\.[a-z]$#", $www)){return "audio-video";}
		if(preg_match("#^video-[a-z][0-9]+\.pagesjaunes\.fr$#", $www)){return "audio-video";}
		if(preg_match("#video.*?\.akamaihd\.net$#", $www)){return "audio-video";}
		if(preg_match("#\.videohub\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.5mn\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.vidyotv\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.popscreen\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.novamov\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.akamaistream\.net$#", $www)){return "audio-video";}
		if(preg_match("#\.videoweed\.es$#", $www)){return "audio-video";}
		if(preg_match("#\.videomark\.[a-z]+$#", $www)){return "audio-video";}
		if(preg_match("#^videomark\.[a-z]+$#", $www)){return "audio-video";}
		if(preg_match("#\.wrzuta\.pl$#", $www)){return "audio-video";}
		if(preg_match("#\.ipercast\.net$#", $www)){return "audio-video";}
		if(preg_match("#\.longtailvideo\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.playstream\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.brainsonic\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.uloz\.to$#", $www)){return "audio-video";}
		if(preg_match("#\.nflxvideo\.net$#", $www)){return "audio-video";}
		if(preg_match("#\.ivi\.ru$#", $www)){return "audio-video";}
		if(preg_match("#(media-player|mediaplayer|plusbellelavie)#", $www)){return "audio-video";}
		
		
	}
	
	
	
	
	private function Books($www){	
		
		
		$array=array("elsevier","booksite",'vonibo','eyrolles',"cyberpresse","berghahnbooks","yoolib",'wikibooks',
		"bnf","electre",'tradepub','docstoc-store','oreilly');
		if($this->ParseArray($array, $www)){return "books";}
		
		if(strpos($www, "books.google.")>0){return "books";}
		if(preg_match("#^books\.#", $www)){return "books";}
		if(preg_match("#^librairie\.#", $www)){return "books";}
		if(preg_match("#(bouquins|salondulivre|bibliotheek)#", $www)){return "books";}

	}
	
	private function Children($www){
		if(preg_match("#(maternelle|mercerie)#",$www)){return "children";}
		if(preg_match("#\.treehousetv\.com$#",$www)){return "children";}
		if(preg_match("#\.clubpenguin\.com$#",$www)){return "children";}
		if(preg_match("#\.gulli\.fr$#",$www)){return "children";}
		if(preg_match("#\.pour-enfants\.fr$#",$www)){return "children";}
		if(preg_match("#\.pppst\.com$#",$www)){return "children";}
		
	}
	
	private function MobilePhone($www){
		$array=array("tele2","globile","love2recycle.fr","nrjmobile","phoneshop","virginmobile");
		if($this->ParseArray($array, $www)){return "mobile-phone";}
		if(preg_match("#\.globile\.co$#",$www)){return "mobile-phone";}
		if(preg_match("#\.mts\.ru$#",$www)){return "mobile-phone";}
		if(preg_match("#optusnet\.com\.au$#",$www)){return "mobile-phone";}
		if(preg_match("#\.itunes\.apple\.com$#",$www)){return "mobile-phone";}
		if(preg_match("#\.pubnub\.com$#",$www)){return "mobile-phone";} 
		if(preg_match("#\.magikmobile\.com$#",$www)){return "mobile-phone";} 
		if(preg_match("#\.wapad\.fr$#",$www)){return "mobile-phone";} 
		if(preg_match("#^mobile-phones\.#",$www)){return "mobile-phone";} 
		if(preg_match("#\.areamobile\.de$#",$www)){return "mobile-phone";} 
		if(preg_match("#^android\..+?\.[a-z]+$#",$www)){return "mobile-phone";}
		if(preg_match("#\.indri\.fr$#",$www)){return "mobile-phone";} 
		if(preg_match("#\.canaltp\.fr$#",$www)){return "mobile-phone";} 
		if(preg_match("#\.samsungapps\.com$#",$www)){return "mobile-phone";} 
		if(preg_match("#\.ssuggest\.com$#",$www)){return "mobile-phone";} 
		if(preg_match("#(\-|\.])nokia\.[a-z]+$#",$www)){return "mobile-phone";} 
		if(preg_match("#(mobilephone|blackberry|yourphone|smartphone|galaxynote|nokia|android|windowsphon|bestphone|mobileapps)#",$www)){return "mobile-phone";} 
		if(preg_match("#\.blackberry\.[a-z]+$#",$www)){return "mobile-phone";}
		if(preg_match("#\.android\.[a-z]+$#",$www)){return "mobile-phone";} 
		if(preg_match("#^gsm[0-9]+\.[a-z]+$#",$www)){return "mobile-phone";}
		if(preg_match("#\.sonymobile\.[a-z]+$#",$www)){return "mobile-phone";}
		if(preg_match("#\.t-mobile\.com$#",$www)){return "mobile-phone";}
		if(preg_match("#\.nokia\.[a-z]+$#",$www)){return "mobile-phone";}
		if(preg_match("#\.dolphin-browser\.com$#",$www)){return "mobile-phone";}
		if(preg_match("#\.aptoide\.com$#",$www)){return "mobile-phone";}
		if(preg_match("#\.bazaarandroid\.com$#",$www)){return "mobile-phone";}
		if(preg_match("#\.mobifiesta\.com$#",$www)){return "mobile-phone";}
		if(preg_match("#\.love2recycle\.[a-z]+$#",$www)){return "mobile-phone";}
		if(preg_match("#\.stickrboo\.com$#",$www)){return "mobile-phone";}
		if(preg_match("#\.10010\.com$#",$www)){return "mobile-phone";}
		if(preg_match("#\.ikomobi\.fr$#",$www)){return "mobile-phone";}
		if(preg_match("#\.droidslice\.[a-z\.]+$#",$www)){return "mobile-phone";}
		if(preg_match("#\.telusmobility\.[a-z\.]+$#",$www)){return "mobile-phone";}
		if(preg_match("#iphone[0-9]+#",$www)){return "mobile-phone";}
		if(preg_match("#-mobile\.#",$www)){return "mobile-phone";}
		if(preg_match("#sonneri.*?\.orange\.[a-z\.]+$#",$www)){return "mobile-phone";}
		
	}
	
	Private function humanitarian($www){
		if(strpos($www, ".croix-rouge.fr")>0){return "humanitarian";}
		if(preg_match("#\.donnerenligne\.fr$#", $www)){return "humanitarian";}
		if(preg_match("#\.oxfam\.[a-z\.]+$#", $www)){return "humanitarian";}
	}
	
	private function schools($www){
		include_once(dirname(__FILE__)."/class.squid.catz.schools.inc");
		$pub=new squid_catz_schools();$cat=$pub->parse($www);if($cat<>null){return "recreation/schools";}
		$array=array("universidad-finis-terrae","finisterrae.cl","tu-harburg","mathletics","grenet","powerschool",
		"schoology","edublogs","sumdog","tableau-noir","hbxxedu.com","uam.es","edu.au","u-cergy");
		
		if($this->ParseArray($array, $www)){return "recreation/schools";}
		
		if(preg_match("#\.state\.oh\.us$#", $www)){return "recreation/schools";}
		if(preg_match("#\.sch\.uk$#", $www)){return "recreation/schools";}
		if(preg_match("#\.raisoni\.net$#", $www)){return "recreation/schools";}
		if(preg_match("#\.insa-.*?\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.edu\.[a-z]+$#", $www)){return "recreation/schools";}
		
		if(preg_match("#\.ac.th$#", $www)){return "recreation/schools";}
		if(preg_match("#\.ac.ir$#", $www)){return "recreation/schools";}
		if(preg_match("#\.sjks\.be$#", $www)){return "recreation/schools";}
		if(preg_match("#\.sch\.uk$#", $www)){return "recreation/schools";}
		
		if(preg_match("#\.pleiad\.net$#", $www)){return "recreation/schools";}
		if(preg_match("#\.k12\.ia\.us$#", $www)){return "recreation/schools";}
		if(preg_match("#\.ac\.[a-z]+$#", $www)){return "recreation/schools";}
		if(preg_match("#\.crdp-.*?\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#(collegesaint|student)#", $www)){return "recreation/schools";}
		if(preg_match("#(college|lycee|ecole).*?etudiant$#", $www)){return "recreation/schools";}
		if(preg_match("#resultats-bac#", $www)){return "recreation/schools";}
		if(preg_match("#(college|lycee|ecole).*?\.free\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.univ-[a-z]+\.(fr|org)$#", $www)){return "recreation/schools";}
		if(preg_match("#^univ-[a-z]+\.(fr|org)$#", $www)){return "recreation/schools";}
		if(preg_match("#^usd[0-9]+\.com$#", $www)){return "recreation/schools";}
		if(preg_match("#\.usd[0-9]+\.com$#", $www)){return "recreation/schools";}
	}
	
	private function sports($www){
		
		$array=array("sofoot");
		
		$sports="foot|tennis|judo|jujitsu|rugby|football|championat|fifa";
		if($this->ParseArray($array, $www)){return "recreation/sports";}
		if(preg_match("#volleyball#", $www)){return "recreation/sports";}
		if(preg_match("#nbcsportsmedia.*?\.msnbc\.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.oxylane\.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.superesportes\.com\.br$#", $www)){return "recreation/sports";}
		if(preg_match("#\.fff\.fr$#", $www)){return "recreation/sports";}
		if(preg_match("#football.*?\.msn\.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.fifa\.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.mikatiming\.de$#", $www)){return "recreation/sports";}
		if(preg_match("#\.eurosport\.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.sport\.[a-z]+$#", $www)){return "recreation/sports";}
		if(preg_match("#^football\..+$#", $www)){return "recreation/sports";}
		if(preg_match("#\.sport[0-9]+\.[a-z]+$#", $www)){return "recreation/sports";}
		if(preg_match("#^sport[0-9]+\.[a-z]+$#", $www)){return "recreation/sports";}
		if(preg_match("#^sport\.#", $www)){return "recreation/sports";}
		if(preg_match("#^sports\.#", $www)){return "recreation/sports";}
		if(preg_match("#^foot\.#", $www)){return "recreation/sports";}
		if(preg_match("#\.nfl\.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.sportowefakty\.pl$#", $www)){return "recreation/sports";}
		if(preg_match("#\.footeo\.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.golfsmith\.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.mlb.com$#", $www)){return "recreation/sports";}
		if(preg_match("#sport\.yahoo\.[a-z]+$#", $www)){return "recreation/sports";}
		if(preg_match("#(football|intersport|jujitsu|judoclub|paintball|-rugby-|judo-club|self-defense|aerobic|sportetstyle|footeo|judoclub|petanque|culturisme|rolandgarros)#", $www)){return "recreation/sports";}
		if(preg_match("#\.clubeo.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.static-clubeo.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.ffboxe\.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.datasport\.[a-z]+$#", $www)){return "recreation/sports";}		
		if(preg_match("#triathlon#", $www)){return "recreation/sports";}	
		if(preg_match("#\.spox\.com$#", $www)){return "recreation/sports";}	
		if(preg_match("#\.sports\..*?\.[a-z]+$#", $www)){return "recreation/sports";}
		if(preg_match("#\.sport\..*?\.[a-z]+$#", $www)){return "recreation/sports";}
		if(preg_match("#\.mundodeportivo\.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.elmundodeportivo\.es$#", $www)){return "recreation/sports";}
		if(preg_match("#\.goal\.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.nba\.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.communautefooteo\.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.sporttvapp\.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.championat\.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.athle\.com$#", $www)){return "recreation/sports";}
		if(preg_match("#\.i-run\.[a-z]+$#", $www)){return "recreation/sports";}
		if(preg_match("#-sports\.[a-z\.]+$#", $www)){return "recreation/sports";}
		if(preg_match("#in(foot|tennis)\.#", $www)){return "recreation/sports";}
		if(preg_match("#-($sports)\.[a-z]+$#", $www)){return "recreation/sports";}
		if(preg_match("#^($sports)-#", $www)){return "recreation/sports";}
		
	}
	
	private function shopping($www){
		
		$array=array("1jour-1bonplan","wholesalepowertools","cdiscount","blizko","focalprice","cherchons","diplotop",
				"360buy","mbstatic","groupon","zura","amazon","htfacile","leguide","crystalcommerce","babidou","jd.com","coovee.net",
				"couponcodes","alloy.ru","franceprix","consopremium","topachat","bonsplansdemarc","teamfanshop.com","hmmm.cz"
				
		);
		
		if($this->ParseArray($array, $www)){return "shopping";}

		
		include_once(dirname(__FILE__)."/class.squid.catz.shopping.inc");
		$pub=new squid_catz_shopping();$cat=$pub->parse($www);if($cat<>null){return "shopping";}
		if(preg_match("#.*?-amazon\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#achetez#",$www)){return "shopping";}

		if(preg_match("#\.digitalriver\.com$#",$www)){return "shopping";}
		if(preg_match("#\.m6boutique\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#\.cataloguepromo\.fr$#",$www)){return "shopping";}
		if(preg_match("#\.carrefour\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#\.ftuan\.com$#",$www)){return "shopping";}
		if(preg_match("#\.rdc\.fr$#",$www)){return "shopping";}
		if(preg_match("#\.shoptondeal\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#ventes-privees#",$www)){return "shopping";}
		if(preg_match("#(beauteprivee|bonplan|fleuriste|achats|bestpromo|nicerdicer|pascher|epicerie|couponcode|centrecommercial|achetez|shoocare|laredoute|3suisses|priceminister|boutique|casashops|bigcommerce|reductionweb|shopping)#",$www)){return "shopping";}
		if(preg_match("#alibaba\.com#",$www)){return "shopping";}
		if(preg_match("#\.dx\.com#",$www)){return "shopping";}
		if(preg_match("#\.ilamparas\.com#",$www)){return "shopping";}
		if(preg_match("#\.auctions\.yahoo\.#",$www)){return "shopping";}
		if(preg_match("#\.bonial\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#\.iai-shop\.com$#",$www)){return "shopping";}
		if(preg_match("#\.sluzby\.cz$#",$www)){return "shopping";}
		if(preg_match("#\.made-in-china\.com$#",$www)){return "shopping";}
		if(preg_match("#^buy\..*?$#",$www)){return "shopping";}
		if(preg_match("#^price-#",$www)){return "shopping";}
		if(preg_match("#-coupons\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#pas-cher\.#",$www)){return "shopping";}
		if(preg_match("#^magasin-#",$www)){return "shopping";}
		
		
	}
	private function pictureslib($www){
		$array=array("mycanvas.com","photoshelter","fliphotos");
		if($this->ParseArray($array, $www)){return "pictureslib";}
		if(strpos($www, "asiaphotopro.com")>0){return "pictureslib";}
		if(strpos($www, ".photozou.jp")>0){return "pictureslib";}
		if(strpos($www, "photodeck.")>0){return "pictureslib";}
		if(strpos($www, "fotosearch.com")>0){return "pictureslib";}
		if(preg_match("#\.deviantart\.com$#",$www)){return "pictureslib";}
		if(preg_match("#\.deviantart\.net$#",$www)){return "pictureslib";}
		if(preg_match("#\.istockphoto\.com$#",$www)){return "pictureslib";}
		if(preg_match("#\.ypcdn\.com$#",$www)){return "pictureslib";}
		if(preg_match("#\.hizliresim\.com$#",$www)){return "pictureslib";}
		if(preg_match("#\.canstockphoto\.com$#",$www)){return "pictureslib";}
		if(preg_match("#\.corbisimages\.com$#",$www)){return "pictureslib";}
		if(preg_match("#\.bestgraph\.[a-z]+$#",$www)){return "pictureslib";}
		if(preg_match("#\.keepeek\.[a-z]+$#",$www)){return "pictureslib";}
		if(preg_match("#\.kalooga\.com$#",$www)){return "pictureslib";}
		if(preg_match("#\.kanimg\.com$#",$www)){return "pictureslib";}
		if(preg_match("#\.2photo\.ru$#",$www)){return "pictureslib";}
		if(preg_match("#\.scribol\.com$#",$www)){return "pictureslib";}
		if(preg_match("#\.caption\.it$#",$www)){return "pictureslib";}
		if(preg_match("#\.shutterstock\.com$#",$www)){return "pictureslib";}
		if(preg_match("#\.depositphotos\.com$#",$www)){return "pictureslib";}
		if(preg_match("#fotogalerie#",$www)){return "pictureslib";}
		if(preg_match("#\.freepik\.[a-z]+$#",$www)){return "pictureslib";}

	}

	
	
	private function governments($www){
		
		$array=array("st-christo");
		if($this->ParseArray($array, $www)){return "governments";}
		
		if(preg_match("#\.justice\.gouv\.fr$#", $www)){return "justice";}
		if(preg_match("#\.tribunal-administratif\.[a-z]+$#", $www)){return "justice";}
		if(preg_match("#\.gouv\.fr$#", $www)){return "governments";}
		if(preg_match("#\.gov$#", $www)){return "governments";}
		if(preg_match("#\.gov\.cn$#", $www)){return "governments";}
		if(preg_match("#gouv\.qc\.ca$#", $www)){return "governments";}	
		if(preg_match("#\.gov\.[a-z]+$#", $www)){return "governments";}
		if(preg_match("#\.govt\.[a-z]+$#", $www)){return "governments";}
		if(preg_match("#\.gouv\.[a-z]+$#", $www)){return "governments";}
		if(preg_match("#\.gob\.mx$#", $www)){return "governments";}
		if(preg_match("#^ville-.*?\.fr$#", $www)){return "governments";}	
		if(preg_match("#mairie\-.+?\.(fr|org)#", $www)){return "governments";}	
		if(preg_match("#public\.lu$#", $www)){return "governments";}
		if(preg_match("#\.state\.fl\.us$#", $www)){return "governments";}	
		if(preg_match("#\.usps\.com$#", $www)){return "governments";}	
		if(strpos($www, "urssaf.fr")>0){return "governments";}
		if(preg_match("#\.caf\.fr$#", $www)){return "governments";}
		if(preg_match("#^comune\..*?\.it$#", $www)){return "governments";}	
		if(preg_match("#\.cantal\.fr$#", $www)){return "governments";}	
		if(preg_match("#\.cnfpt\.fr$#", $www)){return "governments";}	
		if(preg_match("#\.service-public\.fr$#", $www)){return "governments";}	
		if(preg_match("#\.reseaudescommunes\.fr$#", $www)){return "governments";}	
		if(preg_match("#^cg[0-9]+\.fr$#", $www)){return "governments";}	
		if(preg_match("#^sdis[0-9]+\.fr$#", $www)){return "governments";}
		if(preg_match("#\.sdis[0-9]+\.fr$#", $www)){return "governments";}
		if(preg_match("#^sde[0-9]+\.fr$#", $www)){return "governments";}
		if(preg_match("#^cnam-.*?\.(net|fr)$#", $www)){return "governments";}
		if(preg_match("#\.nrw\.de$#", $www)){return "governments";}
		if(preg_match("#\.ville-(.*?)\.fr$#", $www)){return "governments";}
		if(preg_match("#^ville\..*?\.ca$#", $www)){return "governments";}
		if(preg_match("#\.teleguichets\.com$#", $www)){return "governments";}
		if(preg_match("#\.cg[0-9]+\.fr$#", $www)){return "governments";}
		if(preg_match("#\.cci\.fr$#", $www)){return "governments";}
		if(preg_match("#\.mos\.ru$#", $www)){return "governments";}
		if(preg_match("#\.hamburg\.de$#", $www)){return "governments";}
		if(preg_match("#\.cci-.*?\.fr$#", $www)){return "governments";}
		if(preg_match("#\.mairie-.*?\.fr$#", $www)){return "governments";}
		if(preg_match("#^mairie.*?\.org$#", $www)){return "governments";}
		if(preg_match("#\.agglo-(.+?)\.fr$#", $www)){return "governments";}
		if(preg_match("#^cesr-.*?\.fr$#", $www)){return "governments";}
		if(preg_match("#\.ville\.orange\.fr$#", $www)){return "governments";}
		if(preg_match("#consulatde#", $www)){return "governments";}
		if(preg_match("#^consulat-#", $www)){return "governments";}
		if(preg_match("#comune\..*?\.it$#", $www)){return "governments";}

		
	}
	
	private function searchengines($www){
		
		$array=array("vulka.es","atver","yandex","webmarchand","dastelefonbuch.de","repertoire-web","towar.ru");
		if($this->ParseArray($array, $www)){return "searchengines";}
		
		if(strpos($www, ".l.google.com")>0){return "searchengines";}
		if(strpos($www, "ipv6.yahoo.com")>0){return "searchengines";}
		if(preg_match("#\.atver\.lv$#",$www)){return "searchengines";}
		if(preg_match("#amarillas#", $www)){return "searchengines";}
		if(strpos($www, ".b.yahoo.com")>0){return "searchengines";}	
		if(preg_match("#\.googleusercontent\.com$#",$www)){return "searchengine";}
		if(preg_match("#^client([0-9]+)\.google\.([a-z\.]+)$#", $www)){return "searchengines";}	
		if(preg_match("#^google\.([a-z\.]+)$#", $www)){return "searchengines";}	
		if(preg_match("#^clients[0-9]+\.google\.([a-z\.]+)$#", $www)){return "searchengines";}

		if(preg_match("#\.9msn\.com\.au$#", $www)){return "searchengines";}	
		if(preg_match("#\.web\.de$#", $www)){return "searchengines";}	
		
		if(preg_match("#^[0-9]+\.[a-z]\.msn\.com$#", $www)){return "searchengines";}
		if(preg_match("#\.stb\.[a-z]-msn\.com$#", $www)){return "searchengines";}	
		if(preg_match("#msnbcm[a-z]+\.msn\.com$#", $www)){return "searchengines";}
		if(preg_match("#^pn[0-9]+\.[a-z]+\.yahoo\.[a-z]+$#", $www)){return "searchengines";}	
		if(preg_match("#^v[0-9]test[0-9]\.yahoo\.[a-z]+$#", $www)){return "searchengines";}	
		if(preg_match("#^a[0-9]+\.bing\.com$#", $www)){return "searchengines";}	
		if(preg_match("#^msnbcmedia[0-9]+\.msn\.com$#", $www)){return "searchengines";}	
		if(preg_match("#\.googleapis\.com$#", $www)){return "searchengines";}	
		if(preg_match("#^[a-z][0-9]\.gstatic\.com$#", $www)){return "searchengines";}
		if(preg_match("#[a-z]+\.gmodules\.com$#", $www)){return "searchengines";}
		if(preg_match("#\.56abc\.cn$#", $www)){return "searchengines";}	
		if(preg_match("#^cm\.ac[0-9]+\.msn\.com$#", $www)){return "searchengines";}
		if(preg_match("#^dstest[0-9]+\.yahoo\.com$#", $www)){return "searchengines";}
		if(preg_match("#^v[0-9]+test\.yahoo\.[a-z]+$#", $www)){return "searchengines";}
		if(preg_match("#\.s-msn\.com$#", $www)){return "searchengines";}
		if(preg_match("#\.danslemonde\.net$#", $www)){return "searchengines";}
		if(preg_match("#\.118000\.fr$#", $www)){return "searchengines";}
		if(preg_match("#\.blidoo\.pt$#", $www)){return "searchengines";}
		if(preg_match("#^annuaire[\-\.].+?\.[a-z]+$#", $www)){return "searchengines";}
		if(preg_match("#\.android\.clients\.google\.com$#", $www)){return "searchengines";}
		if(preg_match("#\.gougou\.com$#", $www)){return "searchengines";}
		if(preg_match("#\.metric\.gstatic\.com$#", $www)){return "searchengines";}
		if(preg_match("#\.toolbarqueries\.google\.[a-z]+$#", $www)){return "searchengines";}
		if(preg_match("#\.lememo\.com$#", $www)){return "searchengines";}
		if(preg_match("#\.pagesjaunes\.fr$#", $www)){return "searchengines";}
		if(preg_match("#\.search\.yahoo\.[a-z]+$#", $www)){return "searchengines";}
		if(preg_match("#\.top\.list\.ru$#", $www)){return "searchengines";}
		if(preg_match("#\.paginegialle\.it$#", $www)){return "searchengines";}
		if(preg_match("#\.famouswhy\.com$#", $www)){return "searchengines";}
		if(preg_match("#\.kimatel\.fr$#", $www)){return "searchengines";}
		if(preg_match("#\.msedge.net$#", $www)){return "searchengines";}
		if(preg_match("#\.nonsolocap\.it$#", $www)){return "searchengines";}
		if(preg_match("#\.paginebianche\.it$#", $www)){return "searchengines";}
		if(preg_match("#\.yellowpages\.co\.id$#", $www)){return "searchengines";}
		if(preg_match("#\.yellow\.com\.au$#", $www)){return "searchengines";}
		if(preg_match("#\.yottos\.com$#", $www)){return "searchengines";}
		if(preg_match("#\.query\.yahoo\.[a-z]+$#", $www)){return "searchengines";}
		if(preg_match("#\.searchbyphone\.com$#", $www)){return "searchengines";}
		if(preg_match("#\.google.com\.[a-z]+$#", $www)){return "searchengines";}
		if(preg_match("#\.krak\.dk+$#", $www)){return "searchengines";}
		if(preg_match("#\.paginasamarillas\.com$#", $www)){return "searchengines";}
		if(preg_match("#\.les-pages\.com$#", $www)){return "searchengines";}
		if(preg_match("#\.gelbeseiten\.de$#", $www)){return "searchengines";}
		if(preg_match("#\.anygator\.com$#", $www)){return "searchengines";}
		if(preg_match("#yellowpage#", $www)){return "searchengines";}
		if(preg_match("#\.yellow\.[a-z\.]+$#", $www)){return "searchengines";}
		if(preg_match("#\.gstatic\.com$#", $www)){return "searchengines";}
		if(preg_match("#\.telephone-adresse\.com$#", $www)){return "searchengines";}
		if(preg_match("#\.nomao\.com$#", $www)){return "searchengines";}
		if(preg_match("#\.szukacz\.pl$#", $www)){return "searchengines";}
		if(preg_match("#\.yalwa\.co\.za$#", $www)){return "searchengines";}
		if(preg_match("#\.pai.pt$#",$www)){return "searchengines";}
		if(preg_match("#\.msn\.com$#",$www)){return "searchengines";}
		if(preg_match("#\.paginasamarillas\.[a-z\.]+$#", $www)){return "searchengines";}
		if(preg_match("#\.xo\.gr$#", $www)){return "searchengines";}
		if(preg_match("#\.cherchtoo\.fr$#", $www)){return "searchengines";}
		
		if(preg_match("#^annuaire.*?\.[a-z\.]+$#", $www)){return "searchengines";}
		if(preg_match("#annuaire\.[a-z\.]+$#", $www)){return "searchengines";}
		
	}
	
	private function dictionaries($www){
		
		$array=array("thefreedictionary","yourdictionary","oxfordjournals","about","abes.fr","theses.fr","wikipedia","wikia","docstoc","wikidot","wikispaces","wikimedia","wikiquote","wikisource");
		if($this->ParseArray($array, $www)){return "dictionaries";}
		
		

		if(preg_match("#\.toolserver\.org$#",$www)){return "dictionaries";}
		if(preg_match("#\.freeservicemanuals\.info$#",$www)){return "dictionaries";}
		if(preg_match("#\.reference\.com$#",$www)){return "dictionaries";}
		if(preg_match("#\.dictionary\.com$#",$www)){return "dictionaries";}
		if(preg_match("#\.imonografias\.com$#",$www)){return "dictionaries";}
		if(preg_match("#\.academic.ru$#",$www)){return "dictionaries";}
		if(preg_match("#\.fracademic\.com$#",$www)){return "dictionaries";}
		if(preg_match("#\.diplodocs\.com$#",$www)){return "dictionaries";}
		if(preg_match("#\.answers\.com$#",$www)){return "dictionaries";}
		if(preg_match("#\.custhelp\.com$#",$www)){return "dictionaries";}
		if(preg_match("#\.encarta\.msn\.[a-z\.]+$#", $www)){return "dictionaries";}
		if(preg_match("#\.tpub\.com$#",$www)){return "dictionaries";}
		if(preg_match("#^wikipedia\.[a-z\.]+$#", $www)){return "dictionaries";}
		if(preg_match("#wisegeek\.#",$www)){return "dictionaries";}
	}
	
	private function webtv($www){
		
		$array=array("moiseriali","blip","ustream","zap2it","tvrage","arte","canalplus","mtv","diziizle","m6web",
				"bfmtv","france2","france3","24tv.ua","luxnet.ua","francetelevisions","mediasetpremium.it","tvnet","jcntv.jp","webtv-corp.ru","wmaker.tv","leclubcanal","premiumtv","partner-tvmag.net");
		
		if($this->ParseArray($array, $www)){return "webtv";}
		
		if(preg_match("#\.tv\.yahoo\.[a-z]+$#",$www)){return "webtv";}
		if(preg_match("#\.tv\.search\.[a-z]+$#",$www)){return "webtv";}
		if(preg_match("#\.rtl\.lu$#",$www)){return "webtv";}
		if(preg_match("#^tv.+?\.orange\.[a-z]+$#",$www)){return "webtv";}
		
		if(preg_match("#\.tv2\.dk$#",$www)){return "webtv";}
		if(preg_match("#\.wat\.[a-z]+$#",$www)){return "webtv";}
		

		if(preg_match("#\.20min-tv\.ch$#", $www)){return "webtv";}
		if(preg_match("#\.crystal-tv\.[a-z]+$#", $www)){return "webtv";}
		if(preg_match("#\.programme-tv\.net#",$www)){return "webtv";}
		if(preg_match("#\.france3\.fr$#", $www)){return "webtv";}
		if(preg_match("#\.natgeotv\.com$#", $www)){return "webtv";}
		if(preg_match("#(freetv|onlinetv)#", $www)){return "webtv";}	
		if(preg_match("#\.extratv\.com$#", $www)){return "webtv";}	
		if(preg_match("#\.justin\.tv$#", $www)){return "webtv";}	
		if(preg_match("#\.arcoiris\.tv$#", $www)){return "webtv";}
		if(preg_match("#\.cc\.com$#", $www)){return "webtv";}
		if(preg_match("#\.tvb.com$#", $www)){return "webtv";}
		if(preg_match("#\.flumotion\.[a-z]+$#", $www)){return "webtv";}
		if(preg_match("#\.letv\.com$#", $www)){return "webtv";}
		if(preg_match("#\.letvimg\.com$#", $www)){return "webtv";}
		if(preg_match("#\.canalsat\.[a-z]+$#", $www)){return "webtv";}
		if(preg_match("#\.canal13\.artear\.com\.ar$#", $www)){return "webtv";}
		if(preg_match("#\.m6web\.fr$#", $www)){return "webtv";}
		if(preg_match("#(offrescanal|televize)#", $www)){return "webtv";}
		if(preg_match("#\.reise-tv\.com$#", $www)){return "webtv";}
		if(preg_match("#-tv\.[a-z]+$#", $www)){return "webtv";}
		if(preg_match("#\.inttv\.ru$#", $www)){return "webtv";}
		if(preg_match("#^tv\.$#", $www)){return "webtv";}
		if(preg_match("#\.canal-plus\.[a-z]+$#", $www)){return "webtv";}
		if(preg_match("#\.boomtv.[a-z]+$#", $www)){return "webtv";}
		if(preg_match("#\.worldnow\.com$#", $www)){return "webtv";}
		if(preg_match("#\.snacktv\.de$#", $www)){return "webtv";}
		if(preg_match("#\.tv8montblanc\.com$#", $www)){return "webtv";}
		if(preg_match("#\.canal13\.cl$#", $www)){return "webtv";}
		if(preg_match("#\.volia\.[a-z]+$#", $www)){return "webtv";}
		if(preg_match("#\.cgates\.lt$#", $www)){return "webtv";}
		if(preg_match("#^tvsl[0-9]+\.orange\.[a-z]+$#", $www)){return "webtv";}
		if(preg_match("#program.*?-tv#", $www)){return "webtv";}
		if(preg_match("#^webtv#", $www)){return "webtv";}
		
		
		
	}
	
private function webplugins($www){
	
	$array=array("onicon","jquerytools","readspeaker","talkinglayers","xmlrequest","googleapis","linkwithin");
	
	if($this->ParseArray($array, $www)){return "webplugins";}
	
	if(preg_match("#\.onicon\.ru$#",$www)){return "webplugins";}
	if(preg_match("#tinymce#",$www)){return "webplugins";}
		
}
	
private function socialnet($www){
	
		$array=array('tortugasocial',"fbcdn","mylife","badoocdn","gfranq","gigya","badoo","twimg","pinterest","p4fb","thestudentroom","facebook","smeet","disqus","hostmonarchy","pipl","myspacecdn","myspace");
		if($this->ParseArray($array, $www)){return "socialnet";}
	
				
		
		if(strpos($www, ".zankyou.com")>0){return "socialnet";}
		if(preg_match("#^[a-z0-9]+tagstat\.com$#", $www)){return "socialnet";}
		
		
		if(preg_match("#\.cmcdn\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.social\.[a-z]-msn\.com$#", $www)){return "socialnet";}
		
		
		if(preg_match("#plusone\.google\.[a-z\.]+$#", $www)){return "socialnet";}
		if(preg_match("#\.licdn\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.poco\.cn$#", $www)){return "socialnet";}
		if(preg_match("#\.perfspot\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.pixnet\.net$#", $www)){return "socialnet";}
		if(preg_match("#\.tagstat\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.social\.msn\.[a-z]+$#", $www)){return "socialnet";}
		if(preg_match("#\.vk\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.vk\.me$#", $www)){return "socialnet";}
		if(preg_match("#\.social\.microsoft\.[a-z]+$#", $www)){return "socialnet";}
		if(preg_match("#\.odnoklassniki\.ru$#", $www)){return "socialnet";}
		if(preg_match("#\.yammer\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.socialtwist\.com$#", $www)){return "socialnet";}
		if(preg_match("#^fbcdn.*?.akamai[a-z]+\.[a-z]+$#", $www)){return "socialnet";}
		if(preg_match("#^fbexternal.*?.akamai[a-z]+\.[a-z]+$#", $www)){return "socialnet";}
		if(preg_match("#\.pinme\.ru$#", $www)){return "socialnet";}
		if(preg_match("#\.vk\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.myspace\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.kiweo\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.socialappspot\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.fbops\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.esj-lille\.net$#", $www)){return "socialnet";}
		if(preg_match("#\.necat\.ru$#", $www)){return "socialnet";}
		if(preg_match("#\.moikrug\.ru$#", $www)){return "socialnet";}
		if(preg_match("#^fbcdn_sphotos_.*?-.*?\.akamaihd\.net$#", $www)){return "socialnet";}
		if(preg_match("#^fbcdn-photos-.*?\.akamaihd\.net$#", $www)){return "socialnet";}
		if(preg_match("#\.orkut\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.dajie\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.dajieimg\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.livefyre\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.twitter\.[a-z]+$#", $www)){return "socialnet";}
		if(preg_match("#\.viadeo-static\.com$#", $www)){return "socialnet";}
		if(preg_match("#^fbcdn-.*?thumb.*?\.akamaihd\.[a-z]+$#", $www)){return "socialnet";}
		if(preg_match("#^fbcdn-.*?photos.*?\.akamaihd\.[a-z]+$#", $www)){return "socialnet";}
		if(preg_match("#\.wykop.pl$#", $www)){return "socialnet";}
		if(preg_match("#\.phncdn\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.imgwykop.pl$#", $www)){return "socialnet";}
		if(preg_match("#\.weibo\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.ninggadgets\.[a-z]+$#", $www)){return "socialnet";}
		if(preg_match("#\.copainsdavant\.[a-z]+$#", $www)){return "socialnet";}
		if(preg_match("#\.mamba\.ru$#", $www)){return "socialnet";}
		if(preg_match("#\.offertabs\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.addthis\.com$#", $www)){return "socialnet";}
		if(preg_match("#[a-z]+-facebook\.[a-z]+$#", $www)){return "socialnet";}
		if(preg_match("#\.finmetrics\.ru$#",$www)){return "socialnet";}
		if(preg_match("#^fbstatic-.*?\.akamaihd\.net$#", $www)){return "socialnet";}
		if(preg_match("#forbuhgalteria\.ru$#", $www)){return "socialnet";}
		if(preg_match("#\.jeeran\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.socialwin\.ru$#", $www)){return "socialnet";}
		if(preg_match("#\.forfolks\.ru$#", $www)){return "socialnet";}
		if(preg_match("#\.ext\.vkontashe\.ru$#", $www)){return "socialnet";}
		if(preg_match("#\.erenta\.ru$#", $www)){return "socialnet";}
		if(preg_match("#\.socialwin\.ru$#", $www)){return "socialnet";}
		if(preg_match("#\.tenoclock\.ru$#", $www)){return "socialnet";}
		if(preg_match("#\.gravatar\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.userapi\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.livefyre\.com$#", $www)){return "socialnet";}
		
		if(preg_match("#\.cyworld\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.(meetme|kanobu)\.[a-z\.]+$#", $www)){return "socialnet";}
		if(preg_match("#\.spaces\.ru$#", $www)){return "socialnet";}
		
	}	
	
	private function publicite($www){
		
		
		
		$array=array("open-system","adsmogo","ic-live","gestionpub","bidvertiser","banners-service","agency-siam.ru","servedbyopenx.com",
				"easypush","ovoadv","clickbank" ,"adhood","betrad","deskrgas.net","smaato","cibleclick","madvertise","fmpub","plugrush","madnet.ru","client-privilege.com",
				"jumpsavehuge.com","syn-api.com","luxup.ru","promo-onweb.com","network-media","network-medianet","mediagra.com",'ivwbox.de',"pubmatic.com","movad.de",
		);
		
		if($this->ParseArray($array, $www)){return "publicite";}
		
		
		include_once(dirname(__FILE__)."/class.squid.catz.publicite.inc");
		$pub=new squid_catz_publicite();$cat=$pub->parse($www);if($cat<>null){return "publicite";}
		if(preg_match("#\.adserver[0-9]+\.mediacpm\.com$#", $www)){return "publicite";}
		if(preg_match("#\.wt-rotator[0-9]+\.ru$#", $www)){return "publicite";}
		if(preg_match("#\.madvertise\.[a-z]+$#", $www)){return "publicite";}
		if(preg_match("#\.meetrics\.[a-z]+$#", $www)){return "publicite";}
		if(preg_match("#\.directdisplayad\.com$#", $www)){return "publicite";}
		if(preg_match("#\.pubmatic\.[a-z]+$#", $www)){return "publicite";}
		if(preg_match("#\.gestionpub\.com$#", $www)){return "publicite";}
		if(preg_match("#\.a2dfp\.net$#", $www)){return "publicite";}
		if(preg_match("#\.smaatolabs\.net$#", $www)){return "publicite";}
		if(preg_match("#\.pocketads\.pl$#", $www)){return "publicite";}
		if(preg_match("#^publicidad\.$#", $www)){return "publicite";}
		if(preg_match("#\.advertmediahost\.com$#", $www)){return "publicite";}
		if(preg_match("#\.picadmedia\.com$#", $www)){return "publicite";}
		if(preg_match("#\.bannerspace\.net$#", $www)){return "publicite";}
		if(preg_match("#\.netonly\.net$#", $www)){return "publicite";}
		if(preg_match("#advertise#", $www)){return "publicite";}
		if(preg_match("#\.netavenir\.com$#", $www)){return "publicite";}
		if(preg_match("#\.smartadserver\.com$#", $www)){return "publicite";}
		if(preg_match("#\.doubleclick\.net$#", $www)){return "publicite";}
		if(preg_match("#\.genhit\.com$#", $www)){return "publicite";}
		if(preg_match("#\.advidi\.com$#", $www)){return "publicite";}
		if(preg_match("#\.adxpose\.com$#", $www)){return "publicite";}
		if(preg_match("#\.adorika\.[a-z]+$#", $www)){return "publicite";}
		if(preg_match("#\.adap\.tv$#", $www)){return "publicite";}
		if(preg_match("#\.adriver\.ru$#", $www)){return "publicite";}
		if(preg_match("#\.webleads-tracker\.fr$#", $www)){return "publicite";}
		if(preg_match("#\.performads\.fr$#", $www)){return "publicite";}
		if(preg_match("#\.dynad\.net$#", $www)){return "publicite";}
		if(preg_match("#\.sdv\.fr$#", $www)){return "publicite";}
		if(preg_match("#\.like-interactive\.com$#", $www)){return "publicite";}
		if(preg_match("#\.adskeeper\.[a-z\.]+$#", $www)){return "publicite";}
		if(preg_match("#\.apsalar\.com$#", $www)){return "publicite";}
		if(preg_match("#\.fastclick\.com$#", $www)){return "publicite";}
		if(preg_match("#\.fastclick\.net$#", $www)){return "publicite";}
		if(preg_match("#\.buysellads\.com$#", $www)){return "publicite";}
		if(preg_match("#\.viglink\.com$#", $www)){return "publicite";}
		if(preg_match("#\.invitemedia\.com$#", $www)){return "publicite";}
		if(preg_match("#\.exelator\.com$#", $www)){return "publicite";}
		if(preg_match("#\.demdex\.com$#", $www)){return "publicite";}
		if(preg_match("#\.demdex\.net$#", $www)){return "publicite";}	
		if(preg_match("#\.adresult\.ch$#", $www)){return "publicite";}	
		if(preg_match("#\.clickintext\.net$#", $www)){return "publicite";}
		if(preg_match("#\.marsads\.[a-z]+$#", $www)){return "publicite";}	
		if(preg_match("#\.ad6\.fr$#", $www)){return "publicite";}	
		if(preg_match("#\.ladmedia\.fr$#", $www)){return "publicite";}
		if(preg_match("#\.adv-server\.[a-z]+$#", $www)){return "publicite";}	
		if(preg_match("#\.applovin\.com$#", $www)){return "publicite";}	
		if(preg_match("#\.adnxs\.com$#", $www)){return "publicite";}	
		if(preg_match("#\.netadsrv\.[a-z]+$#", $www)){return "publicite";}
		if(preg_match("#^publicidad\.#", $www)){return "publicite";}
		if(preg_match("#\.cabestan\.com$#", $www)){return "publicite";}
		if(preg_match("#\.adwolf\.ru$#", $www)){return "publicite";}
		if(preg_match("#\.pub\.247-inc\.net$#", $www)){return "publicite";}
		if(preg_match("#\.(securesites|adscale|scubl)\.[a-z]+$#", $www)){return "publicite";}
		if(preg_match("#\.justclick\.ru$#", $www)){return "publicite";}
		if(preg_match("#\.noname\.fr$#", $www)){return "publicite";}
		if(preg_match("#-affiliation\.[a-z]+$#", $www)){return "publicite";}	
		if(preg_match("#\.adrime\.[a-z]+$#", $www)){return "publicite";}
		if(preg_match("#^adrime\.[a-z]+$#", $www)){return "publicite";}	
		if(preg_match("#advert\.[a-z\.]+$#", $www)){return "publicite";}	
		
	}
	
	private function dating($www){
		$array=array("edenflirt","badoo","datinggold","xflirt","wekiss","amoureux","illimeet","monamour",
				"rencx","123-cougars","easy-rencontres","lovezx","lovein","polishhearts","51img1","camsympa",
				"rdvmobile","beboo","becoquins","sexydating","be2.fr","datingforlife",
				
				);
				
			if($this->ParseArray($array, $www)){return "dating";}		
			if(preg_match("#(-rencontre|datelove|speeddating)#", $www)){return "dating";}
			if(preg_match("#^dating#", $www)){return "dating";}
			if(preg_match("#dating(\-|\.)#", $www)){return "dating";}
			if(preg_match("#-datings#", $www)){return "dating";}
		
		
	}
	
	private function mixed_adult($www){
		
		
		$array=array("passion","doctissimo","thechive","plaisircoquin","be-coquin","coquins","coquin",
				"coquineavenue","coquine-avenue","avenue-coquine","avenue-privee","avenuesexy","ruedesplaisirs","entrecoquins","plaisirs-des-reves"
				);
		
		if($this->ParseArray($array, $www)){return "mixed_adult";}
		if(preg_match("#(doctissimo|zoophilie|transsexu|plancul)#", $www)){return "mixed_adult";}
		
	}
	
	private function clothing($www){
		$array=array("glam","c-mod","etnies","morel-france","bon-clic-bon-genre","placedestendances",
				"spartoo","spreadshirt","etam","zalando","ztat","shopstyle","forzieri","howards",
				"orchestra","scarosso","misscoquines","costumemagic","marksandspencer"
		);
		
		if(preg_match("#(chaussure|shirtinator|timberland|clothes|clothing|marquepaschere|hugoboss|vetement|burberry|basketnike|hollister|abercrombie|marroquineri|costume)#", $www)){return "clothing";}
		
		if(preg_match("#^maillots-#", $www)){return "clothing";}
		if(preg_match("#^polo.*?pascher#", $www)){return "clothing";}
		if($this->ParseArray($array, $www)){return "clothing";}
		
	}
	
	private function proxy($www){
		
		$array=array("hidemyass","cameleo","anonymsp","ega3","arendadorogo","aradero",
				"serqus","forfolks","amaleto","gredor","parsekus","raufen","vkontakte","vkadre",
				"vkomsakte","fkontakle","vkvkvkvkvk","worka","vkokoko","serqus","generalxy4","vkokoko",
				"blokunet","cmle","cmla","bremdy","daydostup","oknovpope.ru","vkokoko.ru",
		);
		
		if($this->ParseArray($array, $www)){return "proxy";}
		

		if(preg_match("#\.vpn\.mgn\.ru$#",$www)){return "proxy";}
		
	}
	
	
	private function industry($www){
		$array=array("jlg","mindvalley","bunchball","edf.com","chat2deal","nachtmann","whopper","burgerking","burgerkingfrance","pagespro-orange",
		"schneider-electric.com","sony-europe.com","afacerist","hubb","telenav","choosit","lyonnaise-des-eaux","adp","cat",
		"danfoss","batiactu","schunk","advertory","samsonite","personal-wissen.net","etiuslugi.ru","schneider","198ic.com","gesep.com","samsung","datazug","surinternet","bankfirstcorp","sandhills",
		);

		if($this->ParseArray($array, $www)){return "industry";}
		if(preg_match("#(telemarketing|patisserie|artisans|-expertise\.|electric|radyator|industrial|charcuterie|architekten|assainissement|electricite|europages|manutention|industrie)#", $www)){return "industry";}
		if(preg_match("#(beton|mortier|fournituresbtp|materiaux|entreprendre|agricole|ingenierie|hydraulic|traiteur|metaux|depannage|artisan|soudeuse|plombier|chauffagiste|soudeur|hydraulique|electro|elektro|schneider|metaal|electrique|materiel-de|imprimerie|bobinage|agroalimentaire|prefabricados|agroplastic|agroequipement|xtrmexport|expert-comptable|boulangerie|boucherie|cartonnerie|ferronnerie)#",$www)){return "industry";}
		if(preg_match("#\.pro$#", $www)){return "industry";}
		if(preg_match("#^(plastik|grossiste)#", $www)){return "industry";}
		if(preg_match("#plastik\.[a-z\.]+#", $www)){return "industry";}
		if(preg_match("#axima.*?seitha#", $www)){return "industry";}
		if(preg_match("#-associes\.[a-z\.]+#", $www)){return "industry";}
		if(preg_match("#-(consulting|creation|entreprises)\.[a-z\.]+#", $www)){return "industry";}
		

		
	}
	
	private function wellness($www){
		if(preg_match("#(fitness|magnetiseur|vitalite|dieteticien|-beaute-|aloevera|bienetre|wellness|manucure|coiffure)#",$www)){return "recreation/wellness";}
		if(preg_match("#massage[\.|\-]#",$www)){return "recreation/wellness";}
		if(preg_match("#psychologies\.com$#",$www)){return "recreation/wellness";}
		if(preg_match("#\.becomegorgeous\.com$#",$www)){return "recreation/wellness";}
	}
	
	private function tobacco($www){
		$array=array("vapotille","enviedevape","e-clopmania","ciga.fr","forum-ecigarette");
		
		if(preg_match("#(cigarette|tobacco|tabac)#", $www)){return "tobacco";}
		
		if($this->ParseArray($array, $www)){return "tobacco";}
		
	}
	
	private function womanbrand($www){
		
		$array=array("mywoman",
				"yxlady",
				"aufeminin",
				"prime-beaute",
				"mmasculin",
				"vogue",
				"cosmopolitan",
				"grazia",
				"gzmama","meltyfashion","lockerz","onlylady","costume.dk",
				);
		
		if($this->ParseArray($array, $www)){return "womanbrand";}
		if(preg_match("#(fashion|beaute|mode|glamour|modeparis)#", $www)){return "womanbrand";}
		if(preg_match("#feminin\.[a-z]+$#", $www)){return "womanbrand";}
		
	}
	
	private function ParseArray($array,$www){
		while (list ($none, $pattern) = each ($array) ){
			$pattern=str_replace("*", ".*?", $pattern);
			
			if(strpos($pattern, ".")>0){
				$pattern=str_replace(".", "\.", $pattern);
				if(preg_match("#\.$pattern#",$www)){return true;}
				if(preg_match("#^$pattern#",$www)){return true;}
				if(preg_match("#$pattern#",$www)){return true;}
				continue;
				}
			
				if(preg_match("#\.$pattern\.[a-z\.]+$#", $www)){return true;}
				if(preg_match("#^$pattern\.[a-z\.]+$#", $www)){return true;}
			}
			
			return false;
		
	}
	
	
	private function ScienceComputing($www){
		if(preg_match("#\.[a-z]+\.avast\.com$#",$www)){return "updatesites";}
		if(preg_match("#\.www3\.hp\.com$#",$www)){return "updatesites";}
		
		
		$array=array("canonical","n1adsl",".internetarchaeology",
		"googlecode",
		"sourceforge",
		"opendns",
		"symantec",
		"nabble","coremetrics.com",
		"htp","atos.net","atosorigin","atosworldline","ipv6test.net",
		"citrix","dell",
		"hyves","spiceworks","dslreports","itpub.net","redsoft.at",
		"sanasecurity","github",
		"canonical.com","dell","codes-sources","ibm","parallels","unveiltech","jquery","opera","windows\.microsoft","canon-europe","canon",
		"ubuntu","eye.fi","messagelabs.com","omniroot");
		
		if($this->ParseArray($array,$www)){return "science/computing";}
		
		
		
		if(preg_match("#([\.|-])photoshop([-|\.])#", $www)){return "science/computing";}
		if(preg_match("#^(clavier|keyboard)-#", $www)){return "science/computing";}
		if(preg_match("#(informatique|informatic|imprimante|computing|komputer|computer|kaspersky|pcastuces|squid-cache|ordinateur|reseaunance|logiciel|software|sourcecode|freebox)#", $www)){return "science/computing";}
		if(preg_match("#zoneadsl\.#", $www)){return "science/computing";}
		
		if(preg_match("#-geek\.#", $www)){return "science/computing";}
		if(preg_match("#\.wdc\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.clubic\.com$#", $www)){return "science/computing";}
		
		if(preg_match("#\.us\.archive\.org$#", $www)){return "science/computing";}
		if(preg_match("#\.help\.outlook\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.jouve\.fr$#", $www)){return "science/computing";} 
		if(preg_match("#\.ldlc\.com$#", $www)){return "science/computing";} 
		if(preg_match("#\.nas-central\.org$#", $www)){return "science/computing";} 
		if(preg_match("#\.pc120\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.digitalpoint\.com$#", $www)){return "science/computing";}  
		if(preg_match("#\.blob\.core\.windows\.net$#", $www)){return "science/computing";}   
		if(preg_match("#\.parallels\.com$#", $www)){return "science/computing";}  
		if(preg_match("#[a-z]+\.sourceforge\.[a-z]+$#", $www)){return "science/computing";} 
		 
		if(preg_match("#\.as41690\.net$#", $www)){return "science/computing";} 
		if(preg_match("#\.bigair\.net\.au$#", $www)){return "science/computing";}
		if(preg_match("#\.acapela-group\.com$#", $www)){return "science/computing";} 
		if(preg_match("#\.ie\.microsoft\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.technet\.microsoft\.[a-z]+#", $www)){return "science/computing";} 
		if(preg_match("#\.answers\.microsoft\.[a-z]+#", $www)){return "science/computing";}
		if(preg_match("#\.github\.com$#", $www)){return "science/computing";} 
		if(preg_match("#\.fullproduct\.download\.microsoft\.[a-z]+#", $www)){return "science/computing";} 
		if(preg_match("#\.ebp\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.hp\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.intel\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.verytech\.it$#", $www)){return "science/computing";}
		if(preg_match("#\.isleofwightspeedtes\.[a-z]+$#", $www)){return "science/computing";}
		if(preg_match("#\.phplivesource\.[a-z]+$#", $www)){return "science/computing";}
		if(preg_match("#\.asus\.[a-z]+$#", $www)){return "science/computing";}
		if(preg_match("#\.driverboost\.[a-z]+$#", $www)){return "science/computing";}
		if(preg_match("#\.checkpoint\.[a-z]+$#", $www)){return "science/computing";}
		if(preg_match("#\.webspeed\.[a-z]+$#", $www)){return "science/computing";}
		if(preg_match("#\.fortinet\.[a-z]+$#", $www)){return "science/computing";}
		if(preg_match("#\.developpez\.com+$#", $www)){return "science/computing";}
		if(preg_match("#\.oracle\.com+$#", $www)){return "science/computing";}
		if(preg_match("#\.pcmag\.[a-z]+$#", $www)){return "science/computing";}
		if(preg_match("#\.ornis\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.linksys\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.ipv6test\.semnicneposilejte\.cz$#", $www)){return "science/computing";}
		if(preg_match("#\.freshdesignweb.com$#", $www)){return "science/computing";}
		if(preg_match("#\.smile\.fr$#", $www)){return "science/computing";}
		if(preg_match("#\.objectis\.net$#", $www)){return "science/computing";}
		if(preg_match("#\.pny\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.providesupport\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.sage\.[a-z]+$#", $www)){return "science/computing";}
		if(preg_match("#\.mcafee\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.codeplex\.com$#", $www)){return "science/computing";}
		if(preg_match("#-soft\.[a-z]+$#", $www)){return "science/computing";}
		if(preg_match("#\.juniper\.[a-z]+$#", $www)){return "science/computing";}
		if(preg_match("#\.intel\.[a-z]+$#", $www)){return "science/computing";}
		if(preg_match("#\.macromedia\.[a-z]+$#", $www)){return "science/computing";}
		if(preg_match("#^macromedia\.[a-z]+$#", $www)){return "science/computing";}
		if(preg_match("#\.(abeltronica|beyondtrust|sourcearchive)\.[a-z]+$#", $www)){return "science/computing";}
		if(preg_match("#-adsl\.#", $www)){return "science/computing";}

	}
	
	private function weather($www){
		
		$array=array("meteonews","gismeteo","extremeweathercenters","weather","weatherbug","buienradar","accuweather","lachainemeteo","weatherzone","accu-weather");
		
		if($this->ParseArray($array, $www)){return "science/weather";}
		
		
		if(preg_match("#-meteo\.[a-z]+$#", $www)){return "science/weather";}
		if(preg_match("#^meteo\..+?#", $www)){return "science/weather";}
		if(preg_match("#^meteo-.*?\.[a-z]+#", $www)){return "science/weather";}
		if(preg_match("#\.meteofrance\.com$#", $www)){return "science/weather";}
		if(preg_match("#\.meteosun\.com$#", $www)){return "science/weather";}
		if(preg_match("#\.wetter\.com$#", $www)){return "science/weather";}
		if(preg_match("#\.meteo-.*?\.[a-z]+$#", $www)){return "science/weather";}
		if(preg_match("#\.wetter\.#", $www)){return "science/weather";}
		if(preg_match("#^weather\.#", $www)){return "science/weather";}
		if(preg_match("#(\.|\-)(meteo|weather|meteonova|wetter)(\.|\-)#", $www)){return "science/weather";}
		if(preg_match("#meteoservice#", $www)){return "science/weather";}
		
	}
	

	private function music($www){
		
		$array=array("easyzic.com","musicblog","sheetmusicengine","pitchfork","kazaa","universalmusic","sonymusic","hulkshare","projectplaylist");
		if($this->ParseArray($array, $www)){return "music";}
	
		if(preg_match("#-music\.[a-z]+$#", $www)){return "music";}
		if(preg_match("#\.maases.com$#", $www)){return "music";}
		if(preg_match("#\.musique\.fnac\.com$#", $www)){return "music";}
		if(preg_match("#\.deezer.com$#", $www)){return "music";}
		if(preg_match("#\.beezik.com$#", $www)){return "music";}
		if(preg_match("#\.napster\.com$#", $www)){return "music";}
		if(preg_match("#\.alloclips.com$#", $www)){return "music";}
		if(preg_match("#\.universalmusic.[a-z]+$#", $www)){return "music";}
		if(preg_match("#\.soundcloud\.com$#", $www)){return "music";}
		if(preg_match("#\.hypeeater\.com$#", $www)){return "music";}
		if(preg_match("#\.music4live\.[a-z]+$#", $www)){return "music";}
		if(preg_match("#^music\..+?\.[a-z]+$#", $www)){return "music";}
		if(preg_match("#\.6rb\.com$#", $www)){return "music";}
		if(preg_match("#\.music\.metaservices\.microsoft.[a-z]+$#", $www)){return "music";}
		if(preg_match("#\.qobuz\.com$#", $www)){return "music";}
		if(preg_match("#\.spotilocal\.com$#", $www)){return "music";}
		if(preg_match("#\.guitartricks\.com$#", $www)){return "music";}
		if(preg_match("#\.scorser\.com$#", $www)){return "music";}
		if(preg_match("#\.midomi\.com$#", $www)){return "music";}
		if(preg_match("#\.noomiz\.com$#", $www)){return "music";}
		if(preg_match("#\.zimbalam\.com$#", $www)){return "music";}
		if(preg_match("#\.believe\.fr$#", $www)){return "music";}
		if(preg_match("#(musique|musician|harmonica|musik|musical|guitar|jazz)#", $www)){return "music";}
		if(preg_match("#promusic[0-9]+\.[a-z]+$#", $www)){return "music";}
		if(preg_match("#\.zzounds\.com$#", $www)){return "music";}
		if(preg_match("#\.worldclips\.ru$#", $www)){return "music";}
		if(preg_match("#\.soundclick\.com$#", $www)){return "music";}
		if(preg_match("#\.grosse-caisse\.fr$#", $www)){return "music";}
		if(preg_match("#1ting\.com$#", $www)){return "music";}
		if(preg_match("#\.lyrics\.com$#", $www)){return "music";}
		if(preg_match("#\.playgroundmag\.net$#", $www)){return "music";}
		if(preg_match("#\.stream.*?\.grooveshark\.com$#", $www)){return "music";}
		if(preg_match("#\.grooveshark\.com$#", $www)){return "music";}
		if(preg_match("#\.qianqian\.com$#", $www)){return "music";}
		if(preg_match("#\.muzofon\.com$#", $www)){return "music";}
		
	}
	
	private function culture($www){
		$array=array("evene.fr","monuments-nationaux.fr");
		
		if($this->ParseArray($array, $www)){return "culture";}
		
		if(preg_match("#\.monuments-nationaux\.fr+$#", $www)){return "culture";}
		if(preg_match("#^musee(\-|\.]).*?\.fr$#", $www)){return "culture";}
		if(preg_match("#^exposition-.*?\.[a-z]+$#", $www)){return "culture";}
		if(preg_match("#^culture-.*?\.[a-z]+$#", $www)){return "culture";}
		if(preg_match("#^opera-.+?\.[a-z]+$#", $www)){return "culture";}
		if(preg_match("#\.structurae\.[a-z]+$#", $www)){return "culture";}
		if(preg_match("#\.antoloji\.com$#", $www)){return "culture";}
		if(preg_match("#\.beniculturali\.it$#", $www)){return "culture";}
		if(preg_match("#^operade#", $www)){return "culture";}
		if(preg_match("#^mediatheques\.#", $www)){return "culture";}
		if(preg_match("#(theatre|culturali|culturel|museum|musee|muzeum|conservatoire)#", $www)){return "culture";}
		
		
		
	}
	
	private function maps($www){
		
		$array=array("tomtom","usnaviguide","garmin","virtualearth","city-nav","viamichelin",
				"viva-images","streetdirectory","urbandive","viewsurf","1bis",
				"mapswithme","mapquest","openstreetmap","mapit","lewatmana","wikimapia",
				"opencyclemap","onyourmap","intermaps","mapbar","sytadin","maps","mapy",
				"mapcity","go2map","city-map","3dxz.cn"
		);
		
		if($this->ParseArray($array, $www)){return "maps";}
		
		

		if(preg_match("#maps\.yahoo\.com$#", $www)){return "maps";}
		if(preg_match("#\.map.ymap.[a-z\.]+$#", $www)){return "maps";}
		if(preg_match("#\.map[0-9]+\.com$#", $www)){return "maps";}
		if(preg_match("#\.map\.baidu\.com$#", $www)){return "maps";}
		if(preg_match("#\.maptiles-maptp[0-9]+\.[a-z]+$#",$www)){return "maps";}
		if(preg_match("#inforoute#",$www)){return "maps";}

	}
	
	private function webmail($www){
		if(preg_match("#webmail#", $www)){return "webmail";}
		if(strpos($www, "mail.yahoo.")>0){return "webmail";}
		if(preg_match("#mail\.live\.com$#", $www)){return "webmail";}
		if(preg_match("#mailplus\.nl$#", $www)){return "webmail";}
		if(preg_match("#login\.+?\.yahoo\.#", $www)){return "webmail";}
		if(preg_match("#login\.+?\.yahoo\.#", $www)){return "webmail";}	
		if(preg_match("#^webmail\.#", $www)){return "webmail";}	
		if(preg_match("#^servmail\..+?#", $www)){return "webmail";}	
		if(preg_match("#\.mail\.ru$#", $www)){return "webmail";}
		if(preg_match("#\.gmx\.net$#", $www)){return "webmail";}
		if(preg_match("#^messagerie\..+?\.[a-z]+$#", $www)){return "webmail";}
		if(preg_match("#\.mail\.[a-z]+$#", $www)){return "webmail";}
		if(preg_match("#\.hostedexchange\.fr$#", $www)){return "webmail";}
		if(preg_match("#^zimbra\..+?$#", $www)){return "webmail";}
		if(preg_match("#^mail\..+?$#", $www)){return "webmail";}
		if(preg_match("#^zarafa\..+?$#", $www)){return "webmail";}
		if(preg_match("#\.member\..+?\.yahoo\.[a-z]+$#",$www)){return "webmail";}
		if(preg_match("#\.login.vip\..+?\.yahoo\.[a-z]+$#",$www)){return "webmail";}
		if(preg_match("#\.mail\.ru$#",$www)){return "webmail";}
		if(preg_match("#\.my\.msn\.com$#",$www)){return "webmail";}
		if(preg_match("#^webmail[a-z0-9]\.#",$www)){return "webmail";}
		if(preg_match("#webmail[0-9]+m\.orange\.[a-z]+$#",$www)){return "webmail";}
		if(preg_match("#\.mail\.vip\..*?\.yahoo\.[a-z\.]+$#",$www)){return "webmail";}
		if(preg_match("#\.cradleside\.com$#",$www)){return "webmail";}
		if(preg_match("#^gfx[0-9]+\.hotmail\.[a-z\.]+$#",$www)){return "webmail";}
		if(preg_match("#^messagerie-.*?\.sfr\.[a-z\.]+$#",$www)){return "webmail";}
		if(preg_match("#relay\.mail\.#",$www)){return "webmail";}
	}
	
	private function chat($www){
		
		$array=array("iadvize","livechatinc","envolve","chatango","freeshoutbox","chatroll","vichatter");
		
		while (list ($none, $pattern) = each ($array) ){
			if(preg_match("#\.$pattern\.[a-z\.]+$#", $www)){return "chat";}
			if(preg_match("#^$pattern\.[a-z\.]+$#", $www)){return "chat";}
		}
		
		
		

		if(preg_match("#\.messenger\.live\.[a-z]+$#",$www)){return "chat";}
		
		if(preg_match("#\.activengage\.com$#",$www)){return "chat";}
		if(preg_match("#\.msg\.yahoo\.com$#",$www)){return "chat";}
		if(preg_match("#\.liveperson\.net$#",$www)){return "chat";}
		if(preg_match("#async\.olark\.com$#",$www)){return "chat";}
		if(preg_match("#\.bowobcloud[0-9]+\.com$#",$www)){return "chat";}
		if(preg_match("#^chatserver\.#",$www)){return "chat";}
		if(preg_match("#\.chat[0-9]+\.ningim\.com$#",$www)){return "chat";}
		if(preg_match("#\.way2sms\.com$#",$www)){return "chat";}
		if(preg_match("#\.ltalk\.ru$#",$www)){return "chat";}
		if(preg_match("#\.chat\.yahoo\.[a-z]+$#",$www)){return "chat";}
		if(preg_match("#\.coco\.fr$#",$www)){return "chat";}
		if(preg_match("#\.kakao\.co\.kr$#",$www)){return "chat";}
		if(preg_match("#^chat\..*\.[a-z]+$#",$www)){return "chat";}
		if(preg_match("#\.livetex\.ru$#",$www)){return "chat";}
		if(preg_match("#\.chat\.professionali\.ru$#",$www)){return "chat";}
		if(preg_match("#\.msg\.ac[0-9]+\.yahoo.[a-z\.]+$#",$www)){return "chat";}
		if(preg_match("#\.olark\.com$#",$www)){return "chat";}
		if(preg_match("#\.pidip\.com$#",$www)){return "chat";}
		if(preg_match("#\.icq\.[a-z]+$#",$www)){return "chat";}
		if(preg_match("#\.talkgadget\.google\.[a-z]+$#",$www)){return "chat";}

	}	
	
	private function porn($www){
		
		$array=array('ruerotic',"xxx4u","sopital","publicsexchat","red8porn","fux.com","hidrvids","freepornvs","adultgamecity","youjizz","livesexbook","sex-zone","adnlt.com");
		if($this->ParseArray($array, $www)){return "porn";}
		
		
		if(preg_match("#(video|film|movie).*?sexo#", $www)>0){return "porn";}
		
		include_once(dirname(__FILE__)."/class.squid.catz.porn.inc");
		$pub=new squid_catz_porn();$cat=$pub->parse($www);if($cat<>null){return "porn";}	
		if(preg_match("#\.newsfilter\.org$#", $www)>0){return "porn";}
		if(preg_match("#\.utrehter\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.smutxx\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.nastymovs\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.hardsextube\.com$#", $www)>0){return "porn";}
		if(preg_match("#^sexyblog\.*$#", $www)>0){return "porn";}
		if(preg_match("#\.doublepimp\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.mesvip\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.celebsempire\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.smut\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.bfdare\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.brutaltops\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.ventedvdx\.net$#", $www)>0){return "porn";}
		if(preg_match("#\.orgasmatrix\.com$#", $www)>0){return "porn";}
		if(preg_match("#(blonde|teen).*?(porn)#", $www)>0){return "porn";}
		if(preg_match("#(porn).*?(stars)#", $www)>0){return "porn";}
		if(preg_match("#(fucking|youporn|ruporn|sexshop|pornhub|pornsex|nudegirl|livesex|internetporn|freeporn|prostoporno|internetsex|transexual|fetichiste|contactsexe|girldatesforfree)#", $www)){return "porn";}
		if(preg_match("#^gay-#", $www)){return "porn";}
		if(preg_match("#mobileporn\.#", $www)>0){return "porn";}
		if(preg_match("#\.fetishok\.com$#", $www)>0){return "porn";}
		if(preg_match("#sexserver[0-9]+\.[a-z]+$#", $www)){return "porn";}
		if(preg_match("#\.adult-empire\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.teensaloon\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.redtubefiles\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.hazecash\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.freepornvs\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.xxx\.[a-z]+$#", $www)){return "porn";}
		if(preg_match("#\.pornhubpremium\.[a-z]+$#", $www)){return "porn";}
		if(preg_match("#\.mypornmotion\.[a-z]+$#", $www)){return "porn";}
		if(preg_match("#\.pornpoly.com$#", $www)){return "porn";}
		if(preg_match("#\.4Tube\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.AlphaPorno\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.Beeg\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.BravoTeens\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.BravoTube\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.H2Porn\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.HardSexTube\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.HellPorno\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.PornerBros\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.PornSharia\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.PornTube\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.PornTubePro\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.RedTube\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.TubeWolf\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.YourLust\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.(adbucks|suero|hardpics|xxxtop|stoporn|sexfree|mega-porno|webfreesex|hotclip|hayday|pornstarspy|prostoporno)\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.jasminfetishstatic\.com#i",$www)){return "porn";}
		if(preg_match("#\.lj\.com#i",$www)){return "porn";}
		if(preg_match("#\.ljstatic\.com#i",$www)){return "porn";}
		if(preg_match("#\.perfectgirls\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.mydirtyhobby\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.amateurcommunity\.com$#i",$www)){return "porn";}
		if(preg_match("#\.xxxallaccesspass\.com$#i",$www)){return "porn";}
		if(preg_match("#\.culiarmedia\.[a-z]+$#i",$www)){return "porn";}
		if(preg_match("#\.xx[0-9]+t\.com$#i",$www)){return "porn";}
		if(preg_match("#\.[0-9]tub\.com$#i",$www)){return "porn";}
		if(preg_match("#\.erog\.fr$#i",$www)){return "porn";}
		if(preg_match("#\.(gaycities|hostedtube)\.[a-z]+$#i",$www)){return "porn";}
		if(preg_match("#\.awempire\.com$#i",$www)){return "porn";}
		if(preg_match("#^(nudist|teenporn)#i",$www)){return "porn";}
		if(preg_match("#\.flamehub\.[a-z]+$#i",$www)){return "porn";}
		if(preg_match("#\.extremetube\.[a-z]+$#i",$www)){return "porn";}
		if(preg_match("#\.freepornfast\.[a-z]+$#i",$www)){return "porn";}
		if(preg_match("#\.phonpix\.[a-z]+$#i",$www)){return "porn";}
		if(preg_match("#redtube[0-9]+\.[a-z]+$#i",$www)){return "porn";}
		if(preg_match("#\.sexibl\.[a-z]+$#i",$www)){return "porn";}
		if(preg_match("#topsex[0-9]+\.[a-z]+$#i",$www)){return "porn";}
		if(preg_match("#\.metartnetwork\.[a-z\.]+$#", $www)>0){return "porn";}
		if(preg_match("#guests\.met-art\.[a-z\.]+$#", $www)>0){return "porn";}	
		if(preg_match("#\.sexart\.[a-z\.]+$#", $www)>0){return "porn";}
		if(preg_match("#\.thelifeerotic\.[a-z\.]+$#", $www)>0){return "porn";}
		if(preg_match("#\.eroticbeauty\.[a-z\.]+$#", $www)>0){return "porn";}
		if(preg_match("#\.errotica-archives\.[a-z\.]+$#", $www)>0){return "porn";}
		if(preg_match("#\.michaelninn\.[a-z\.]+$#", $www)>0){return "porn";}
		if(preg_match("#\.rylskyart\.[a-z\.]+$#", $www)>0){return "porn";}
		if(preg_match("#\.alsscan\.[a-z\.]+$#", $www)>0){return "porn";}
		if(preg_match("#\.vivthomas\.[a-z\.]+$#", $www)>0){return "porn";}
		if(preg_match("#\.eternaldesire\.[a-z\.]+$#", $www)>0){return "porn";}
		if(preg_match("#\.hollyrandall\.[a-z\.]+$#", $www)>0){return "porn";}
		if(preg_match("#\.metartnetwork\.[a-z\.]+$#", $www)>0){return "porn";}		
		
	}
	
	private function movies($www){
		
		$array=array("cine-news-mail");
		if($this->ParseArray($array, $www)){return "movies";}
		
		if(strpos($www, ".cellcastmedia.com")>0){return "movies";}
		if(strpos($www, ".streamtheworld.com")>0){return "movies";}
		if(preg_match("#\.megavod\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.studiocanal\.com$#", $www)){return "movies";}
		if(preg_match("#\.tvsubtitles\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.openfilm\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.supergoodmovies\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.movies\.yahoo\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.kinepolis\.com$#", $www)){return "movies";}	
		if(preg_match("#(movie|film|cinema|streaming|filmandvideolighting).*?\.(blogspot|altervista).*?\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#^.+?-lefilm\.com$#", $www)){return "movies";}
		if(preg_match("#\.netflix\.com$#", $www)){return "movies";}
		if(preg_match("#\.*?starmovies\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.streaming\.claranet\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.moviesmobile\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.[0-9]+starmovies\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.cotecine\.fr$#", $www)){return "movies";}
		if(preg_match("#\.ariamovie[0-9]+\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#(bollywood|maxfilm|freefilm|film.*?online|film.*?gratis|filmpreview|themovie)#", $www)){return "movies";}
		if(preg_match("#^cinema\.#", $www)){return "movies";}
		if(preg_match("#(\-|\.|[0-9]+)(movie|film|cinema|streaming)(\-|[0-9]+|[s])\.#", $www)){return "movies";}
		if(preg_match("#\.subs4movies\.com$#", $www)){return "movies";}
		if(preg_match("#\.mymovies\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.video\.mystreamservice.com$#", $www)){return "movies";}
		if(preg_match("#\.vsetu\.eu$#", $www)){return "movies";}
		if(preg_match("#\.videobam\.com$#", $www)){return "movies";}
		if(preg_match("#\.megavod\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.cinewatcher\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.click-network\.com$#", $www)){return "movies";}
		if(preg_match("#\.warnerbros\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.cineday\.orange\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.ivi\.ru$#", $www)){return "movies";}
		if(preg_match("#^film-#", $www)){return "movies";}
		if(preg_match("#-film-#", $www)){return "movies";}
		if(preg_match("#-films-#", $www)){return "movies";}
		if(preg_match("#^films-#", $www)){return "movies";}
		if(preg_match("#movies\.[a-z\.]+$#", $www)){return "movies";}
		if(preg_match("#moviefa[0-9]+\.#", $www)){return "movies";}
		if(preg_match("#-the-movie(\.|-)#", $www)){return "movies";}
		
		
	}
	
	private function celebrity($www){
		$array=array("justjared","bunte","promiflash","fanpop","zimbio","dailysingle");
		if($this->ParseArray($array, $www)){return "celebrity";}
		
	}
	
	
	private function games($www){	
		include_once(dirname(__FILE__)."/class.squid.catz.games.inc");
		
		$array=array("jeux-ios","majorleaguegaming","gaijinent","101xp","gamer-info","gamefaqs","king.com",
				"gamingfacts","midasplayer","king","happyoz","boardgamearena","didi*games","y8freegames","kaisergames",
				"playdidi","fupa.com","la.net.ua","battle.net","microjogos","gamedonga.co.kr","wow-europe.com","blizzard.com");
		if($this->ParseArray($array, $www)){return "games";}
		
		$pub=new squid_catz_games();
		$cat=$pub->parse($www);
		if($cat<>null){return "games";}	
		
		
		if(preg_match("#\.playme[0-9]+\.[a-z]+$#",$www)){return "games";}
		if(preg_match("#\.roblox\.com$#",$www)){return "games";}
		if(preg_match("#\.torn\.com$#",$www)){return "games";}
		if(preg_match("#(playstation|nintendo|blizzard\.com|jeuxvideo|pokemon|worldofwarcraft|minecraft|xboxlive|multigaming|battlefield|skyrim|gameportal|juegos|xbox360|top-jeux)#",$www)){return "games";}
		
		if(preg_match("#\.torn\.com$#",$www)){return "games";}
		if(preg_match("#\.spele\.nl$#",$www)){return "games";}
		if(preg_match("#^game[0-9]+\.$#",$www)){return "games";}
		if(preg_match("#\.app111\.com$#",$www)){return "games";}
		if(preg_match("#\.game[0-9]+\.[a-z]+$#",$www)){return "games";}
		if(preg_match("#\.gameone\.[a-z]+$#",$www)){return "games";}
		if(preg_match("#\.battleknight\.[a-z]+$#",$www)){return "games";}
		if(preg_match("#\.unityplay\.com$#",$www)){return "games";}
		if(preg_match("#\.steampowered\.[a-z]+$#",$www)){return "games";}
		if(preg_match("#\.forgeofempires\.[a-z]+$#",$www)){return "games";}
		if(preg_match("#^forgeofempires\.[a-z]+$#",$www)){return "games";}
		if(preg_match("#\.gameanyone\.[a-z]+$#",$www)){return "games";}
		if(preg_match("#^vlan-.*?-game-.*?\.comnet\.bg$#",$www)){return "games";}
		if(preg_match("#(jeux|game).*?\.free\.fr$#", $www)){return "games";}
		if(preg_match("#\.gameloft\.com$#", $www)){return "games";}
		if(preg_match("#\.gao7\.com$#", $www)){return "games";}
		if(preg_match("#\.mobappbox\.com$#", $www)){return "games";}
		if(preg_match("#mobivillage\.[a-z]+$#",$www)){return "games";}
		if(preg_match("#facepunchstudios#",$www)){return "games";}
		if(preg_match("#\.creative-mobile\.com$#",$www)){return "games";}
		if(preg_match("#\.americasarmy\.com$#",$www)){return "games";}
		if(preg_match("#\.secondlife\.com$#",$www)){return "games";}
		if(preg_match("#\.gamershell\.com$#",$www)){return "games";}
		if(preg_match("#\.socialquantum\.com$#",$www)){return "games";}
		if(preg_match("#\.scoreloop\.com$#", $www)){return "games";}
		if(preg_match("#^juegosde#", $www)){return "games";}
		if(preg_match("#(jeu|jeux)\.orange\.[a-z]+$#",$www)){return "games";}
	}
	
	private function automobile($www){
		
		
		
		$array=array("rdw","otomoto","automotive","ebizautos","stemco","texasdirectauto","ironhorse","opelreseau",
				"mercedes-benz","webmotors","renault","renault-egypt","carrental","myrenault","hertz","audi","avis","europcar","auto","autotrader","car.gr","carcdn.gr",
		);
		
		if($this->ParseArray($array, $www)){return "automobile/cars";}
		
		$prefixrenault="(dacia|zoe|trafic|clio|sport|car|cancun|campaigns)";
		
		
		if(preg_match("#(automotos|-auto-|autoservice|carburant|automotiva|peugeot|auto-ecole|hyundai|autobilan|volkswagen|camions|autodrive|autocarros|motorsports|otomobil|motorcar|citroen|automobile|voiture|rentalcars|toyota|europcar)#",$www)){return "automobile/cars";}
		if(preg_match("#auto\.sanook\.com$#",$www)){return "automobile/cars";}
		
		if(preg_match("#$prefixrenault.*?renault#",$www)){return "automobile/cars";}
		if(preg_match("#renault.*?$prefixrenault#",$www)){return "automobile/cars";}
		if(preg_match("#renault-#",$www)){return "automobile/cars";}
		if(preg_match("#autov\.[a-z\.]+$#", $www)){return "automobile/cars";}
		if(preg_match("#\.securitest\.fr$#", $www)){return "automobile/cars";}
		if(preg_match("#\.autosecurite\.com$#", $www)){return "automobile/cars";}
		if(preg_match("#\.bitauto\.com$#", $www)){return "automobile/cars";}
		if(preg_match("#^location.*?(voiture|vehicule)#", $www)){return "automobile/cars";}
		if(preg_match("#\.actionmotorsports\.com$#", $www)){return "automobile/cars";}
		if(preg_match("#\.auto-selection\.com$#", $www)){return "automobile/cars";}
		if(preg_match("#\.vrum\.com\.br$#", $www)){return "automobile/cars";}
		if(preg_match("#auto-moto\.[a-z]+$#", $www)){return "automobile/cars";}
		if(preg_match("#\.[0-9]+auto\.com$#", $www)){return "automobile/cars";}
		if(preg_match("#\.autoscout[0-9]+\.net$#", $www)){return "automobile/cars";}
		if(preg_match("#\.wdlarson\.com$#", $www)){return "automobile/cars";}
		if(preg_match("#\.auto\.ifeng\.com$#", $www)){return "automobile/cars";}
		if(preg_match("#\.carpartswholesale\.com$#", $www)){return "automobile/cars";}
		if(preg_match("#\.seat\.fr$#", $www)){return "automobile/cars";}
		if(preg_match("#yokohama#", $www)){return "automobile/cars";}
		if(preg_match("#\.hyundai\.[a-z]+$#", $www)){return "automobile/cars";}
		if(preg_match("#\.kia\.[a-z]+$#", $www)){return "automobile/cars";}
		if(preg_match("#permis.*?points#", $www)){return "automobile/cars";}
		if(preg_match("#(vehicule|volkswagen|elephantbleu|automovil|euromaster|pointspermis|carrosserie|automotive|chevrolet|cabriolet|voiture|eurotyre|automobile|gonka|mazda|automotivo|pneumatic)#", $www)){return "automobile/cars";}
		if(preg_match("#\.chevroletconnect\.[a-z]+$#", $www)){return "automobile/cars";}
		if(preg_match("#\.img.*?-otomoto\.*$#", $www)){return "automobile/cars";}
		if(preg_match("#\.auto-noproblem\.com$#", $www)){return "automobile/cars";}
		if(preg_match("#\.rc-car-online\.[a-z]+$#", $www)){return "automobile/cars";}
		if(preg_match("#-pneus\.[a-z\.]+$#", $www)){return "automobile/cars";}
		if(preg_match("#repar.*?-auto-#", $www)){return "automobile/cars";}		
		if(preg_match("#-auto\.[a-z\.]+$#",$www)){return "automobile/cars";}
		if(preg_match("#^(auto|bmw|occasions)-#",$www)){return "automobile/cars";}
		
		
	}
	
	private function mailing($www){
		$array=array('maileva','selfmail','activemailer',"webcontrolcenter.com","spamtrap");
		
		if($this->ParseArray($array, $www)){return "mailing";}
		
		if(preg_match("#^mail[0-9]+\.#",$www)){return "mailing";}
		if(preg_match("#^mail\.#",$www)){return "mailing";}
		if(preg_match("#^click\.email\.*#",$www)){return "mailing";}
		if(preg_match("#^click[0-9]+\.mail\..*#",$www)){return "mailing";}
		if(preg_match("#^email\..+?#",$www)){return "mailing";}
		if(preg_match("#^emailimages\..+?#",$www)){return "mailing";}
		if(preg_match("#^emailing\..+?#",$www)){return "mailing";}
		if(preg_match("#^emailings\..+?#",$www)){return "mailing";}
		if(preg_match("#^emailnotifier\..+?#",$www)){return "mailing";}
		if(preg_match("#^emailpromo.?#",$www)){return "mailing";}
		if(preg_match("#^emails\..+?#",$www)){return "mailing";}
		if(preg_match("#^mailing..+?#",$www)){return "mailing";}
		if(preg_match("#^email\.#",$www)){return "mailing";}
		if(preg_match("#^campaign\..*?\.flyfarsite\.net$#",$www)){return "mailing";}	
		if(preg_match("#\.newsletter\..+?#",$www)){return "mailing";}
		if(preg_match("#(.+?)-mailing\.#", $www)){return "mailing";}
		if(preg_match("#\.list-manage\.com$#", $www)){return "mailing";}
		if(preg_match("#\.rs6\.net$#", $www)){return "mailing";}
		if(preg_match("#\.mailer\.#", $www)){return "mailing";}
		if(preg_match("#\.textcashnetwork\.com$#", $www)){return "mailing";}
		if(preg_match("#\.newsletters\..+?#", $www)){return "mailing";}
		if(preg_match("#\.mailplus\.nl$#", $www)){return "mailing";}
		if(preg_match("#\.newsletter([\-\.s]+)#", $www)){return "mailing";}
		if(preg_match("#\.esmailer.+?$#", $www)){return "mailing";}
		if(preg_match("#\.mailmarket.+?$#", $www)){return "mailing";}
		if(preg_match("#\.canal-marketing\.com$#", $www)){return "mailing";}
		if(preg_match("#\.email-brokers\.com$#", $www)){return "mailing";}
		if(preg_match("#\.email-reference\.com$#", $www)){return "mailing";}
		if(preg_match("#\.message-business\.com$#", $www)){return "mailing";}
		if(preg_match("#\.mcsv[0-9]+\.net$#", $www)){return "mailing";}
		if(preg_match("#\.lettre-information\.com$#", $www)){return "mailing";}
		if(preg_match("#\.cccampaigns\.com$#", $www)){return "mailing";}
		if(preg_match("#\.ccemails\.net$#", $www)){return "mailing";}
		if(preg_match("#^emailstrategie-#", $www)){return "mailing";}
		if(preg_match("#^newsletters\.#", $www)){return "mailing";}
		if(preg_match("#[\.\-]emailing\.[a-z\.]+$#", $www)){return "mailing";}
		if(preg_match("#\.attachmail\.ru$#", $www)){return "mailing";}
		if(preg_match("#\.super-affaire.com$#", $www)){return "mailing";}
		if(preg_match("#\.sertmail\.com$#", $www)){return "mailing";}
		if(preg_match("#\.en25\.com$#", $www)){return "mailing";}
		if(preg_match("#\.abcyoyo\.com$#", $www)){return "mailing";}
		if(preg_match("#\.promo-vip\.com$#", $www)){return "mailing";}
		if(preg_match("#\.email-attitude\.com$#", $www)){return "mailing";}
		if(preg_match("#\.dadk26ma\.com$#", $www)){return "mailing";}
		if(preg_match("#\.ymlp332\.net$#", $www)){return "mailing";}
		if(preg_match("#\.email-match\.fr$#", $www)){return "mailing";}
		if(preg_match("#\.epticahosting\.com$#", $www)){return "mailing";}
		if(preg_match("#\.goldmail\.com$#", $www)){return "mailing";}
		if(preg_match("#\.bp-email\.com$#", $www)){return "mailing";}
		if(preg_match("#^mailling\..+?\.[a-z]+$#", $www)){return "mailing";}
		if(preg_match("#^mailsever[0-9]+\..+?\.[a-z]+$#", $www)){return "mailing";}
		if(preg_match("#\.solomailings\.com$#", $www)){return "mailing";}
		if(preg_match("#\.emails\.fnac\.com$#", $www)){return "mailing";}
		if(preg_match("#\.emv2\.com$#", $www)){return "mailing";}
		if(preg_match("#\.mailingwork\.de$#", $www)){return "mailing";}		
		if(preg_match("#\.addemar\.com$#", $www)){return "mailing";}	
		if(preg_match("#\.createsend[0-9]+\.com$#", $www)){return "mailing";}
		if(preg_match("#\.cmail[0-9]+\.com$#", $www)){return "mailing";}
		if(preg_match("#\.list-manage[0-9]+\.[a-z]+$#", $www)){return "mailing";}
		if(preg_match("#\.niouzelettre\.com$#", $www)){return "mailing";}
		if(preg_match("#\.batimail\.fr$#", $www)){return "mailing";}
		if(preg_match("#\.easymailing\..*#", $www)){return "mailing";}
		if(preg_match("#\.rabnews\.de$#", $www)){return "mailing";}
		if(preg_match("#\.netbooster-studio\.com$#", $www)){return "mailing";}
		if(preg_match("#\.emailmsg\.net$#", $www)){return "mailing";}
		if(preg_match("#.*?mailsv\.softbank\.jp$#", $www)){return "mailing";}
		if(preg_match("#\.createsend\.com$#", $www)){return "mailing";}
		if(preg_match("#\.mailjet\.com$#", $www)){return "mailing";}
		if(preg_match("#\.itimesoffers\.com$#", $www)){return "mailing";}
		if(preg_match("#msx-smtp[0-9\-]+\.hinet\.net$#", $www)){return "mailing";}
		if(preg_match("#^ms.*?\.hinet\.net$#", $www)){return "mailing";}
		if(preg_match("#(newsletter|mailsender|clicksmail|emailing|email-service)#", $www)){return "mailing";}
		if(preg_match("#\.mailingreport\.com$#", $www)){return "mailing";}
		if(preg_match("#\.magical-ears\.com$#", $www)){return "mailing";}
		if(preg_match("#\.adsender\.us$#", $www)){return "mailing";}
		if(preg_match("#\.mkt[0-9]+\.com$#", $www)){return "mailing";}
		if(preg_match("#^mkt[0-9]+\.com$#", $www)){return "mailing";}
		if(preg_match("#^mx14\..*\.[a-z]+$#", $www)){return "mailing";}
		if(preg_match("#\.imgsmail\.ru$#", $www)){return "mailing";}
		if(preg_match("#\.hdemail\.jp$#", $www)){return "mailing";}
		if(preg_match("#\.mailing.*?\.com$#", $www)){return "mailing";}
		if(preg_match("#\.resellermail\.[a-z]+$#", $www)){return "mailing";}
		if(preg_match("#^mailin.*$#", $www)){return "mailing";}
		if(preg_match("#^mailback[0-9]+\.#", $www)){return "mailing";}
		if(preg_match("#-mailcenter\.[a-z]+$#", $www)){return "mailing";}
		if(preg_match("#\.mailgm\.[a-z]+$#", $www)){return "mailing";}
		if(preg_match("#\.maakave[0-9]+\.[a-z]+$#", $www)){return "mailing";}
		
	}
	
	private function sslsites($www){
		
		if(preg_match("#\.verisign\.com$#",$www)){return "sslsites";}
		if(preg_match("#\.geotrust\.com$#",$www)){return "sslsites";}
		if(preg_match("#\.digicert\.com$#", $www)){return "sslsites";}
		if(preg_match("#\.entrust\.net$#", $www)){return "sslsites";}
		if(preg_match("#\.startssl\.com$#", $www)){return "sslsites";}
	}
	
	private function gamble($www){
		$array=array("plein2kdo.com","108qambc.com","16xx.net","gambler");
		if($this->ParseArray($array, $www)){return "gamble";}
		if(preg_match("#\.check4d\.com$#",$www)){return "gamble";}
		if(preg_match("#\.jaro\.com$#",$www)){return "gamble";}
		if(preg_match("#\.(pmu|paristeam)\.(fr|com)$#",$www)){return "gamble";}
		if(preg_match("#[0-9]+poker\.[a-z]+$#",$www)){return "gamble";}
		if(preg_match("#(casino|bet(ting|s)|lott(ery|o)|gam(e[rs]|ing|bl(e|ing))|sweepstake|poker).*?\.(blogspot|altervista).*?\.[a-z]+$#", $www)>0){return "gamble";}
	}
	
	private function downloads_catz($www){
		
		$array=array("fileice","rapidshare","rapidgator","softonic","sftcdn","torrentino",
				"yourfiledownloader","rutracker","alphacoders","altools","utorrent","bittorrent","vip-file","softsalad"
				,"software\.informer","brothersoft","trulydownload.com","otherdownload","uptodown","malavida","soft112","winds10","telecharsoft"
				);
		
		if($this->ParseArray($array, $www)){return "downloads";}
		
		if(preg_match("#\.rargb\.me$#", $www)){return "downloads";}
		if(preg_match("#torrent\.#", $www)){return "downloads";}
		if(preg_match("#torrents\.#", $www)){return "downloads";}
		if(preg_match("#torrent-#", $www)){return "downloads";}
		if(preg_match("#torrents-#", $www)){return "downloads";}
		if(preg_match("#^torrent[0-9]+#", $www)){return "downloads";}
		if(preg_match("#-torrent(\-|\.)#", $www)){return "downloads";}
		if(preg_match("#(-utorrent|-torrent|-btorrent)#", $www)){return "downloads";}
		if(preg_match("#(divdrip|internetdownloadmanager|dvdrip)#", $www)){return "downloads";}
		if(preg_match("#\.[0-9]+-download[\.a-z]+$#", $www)){return "downloads";}
		if(preg_match("#-p2p\.[a-z]+$#", $www)){return "downloads";}
		if(preg_match("#-telecharger\.[\.a-z]+$#", $www)){return "downloads";}
		if(preg_match("#-ftp\.[\.a-z]+$#", $www)){return "downloads";}
		
	}
	
	private function dynamic($www){
		
		
		$array=array("telepac","doesntexist","dynamic.mts-nn.ru","kabel-badenwuerttemberg.de","prudok.ru","dynamic.pppoe.mgts.by","user.veloxzone.com.br","megared.net.mx","eastlink.ca","dsl.sura.ru","orangearmenia","moldtelecom","cable.virginmedia.com","charter.com","com2com.ru","icnhost.net","telnet.bg","kiber.net.ua","telnetcommunications","hawaiiantel","homedns","dyndns","at-band-camp.net",
		"barrel-of-knowledge.info","barrell-of-knowledge.info","better-than.tv","blogdns.com","izhnt.ru","qala.com.sg","online.dn.ua","infra.lync.com","sodetel.net.lb","internet-x.hu","keysurf.net","1scom.net","ondsl.gr","navoi.net.gs.uz","dsl*fidnet.com",
		"blogdns.net","blogdns.org","blogsite.org","boldlygoingnowhere.org","broke-it.net","optonline.net","qwerty.ru","nationalcablenetworks.ru","chitaonline.ru",
		"buyshouses.net","cechire.com","dnsalias.com","dnsalias.net","dnsalias.org","dnsdojo.com","kthopen.kth.se","mc.videotron.ca","flynet.by","unitymediagroup.de",
		"dnsdojo.net","dnsdojo.org","does-it.net","doesntexist.com","doesntexist.org","dontexist.com","ddfr.nl","retail.datagroup.ua",
		"dontexist.net","dontexist.org","doomdns.com","doomdns.org","dvrdns.org","dyn-o-saur.com","orlandonet.net","alicedsl.de",
		"dynalias.com","dynalias.net","dynalias.org","dynathome.net","dyndns-at-home.com","alfalinks.lv","is74.ru","optonline.net",
		"dyndns-at-work.com","dyndns-blog.com","dyndns-free.com","dyndns-home.com","dyndns-ip.com","pip.digsys.bg","rdsnet.ro","moldtelecom.md",
		"dyndns-mail.com","dyndns-office.com","dyndns-pics.com","dyndns-remote.com","dyndns-server.com","ip.primehome.com","ziggo.nl",
		"dyndns-web.com","dyndns-wiki.com","dyndns-work.com","dyndns.biz","dyndns.info","dyndns.org","standardbroadband.ca","soskol.ru","plus.kerch.net","14-tataidc.co.in","residential.rdsnet.ro","dynamic.ziggo.nl","stv.ru",
		"*cable.fidnet.com","host*pool*.leonet.it","host*.e4a.it","dsl*.greenmountainaccess.net","elektrons-k.lv","myown.be","host*.tygrys.net","istv.uz",
		"dyndns.tv","dyndns.ws","endofinternet.net","endofinternet.org","endoftheinternet.org","ripe.coltfrance.com","cmk.ru","abakusbp.net",
		"est-a-la-maison.com","est-a-la-masion.com","est-le-patron.com","est-mon-blogueur.com","catnet","nationalcablenetworks.ru","avangarddsl.ru","baltnet.ru","pool.mediaways.net","optusnet.com.au",
		"for-better.biz","for-more.biz","for-our.info","superkabel.de","glocalnet.net","for-some.biz","for-the.biz","forgot.her.name","forgot.his.name","from-ak.com","from-al.com","from-ar.com","from-az.net","from-ca.com","from-co.net","from-ct.com","from-dc.com","from-de.com","from-fl.com","from-ga.com","from-hi.com","from-ia.com","from-id.com","from-il.com","from-in.com","from-ks.com","from-ky.com","from-la.net","from-ma.com","from-md.com","from-me.org","from-mi.com","from-mn.com","from-mo.com","from-ms.com","from-mt.com","from-nc.com","from-nd.com","from-ne.com","from-nh.com","from-nj.com","from-nm.com","from-nv.com","from-ny.net","from-oh.com","from-ok.com","from-or.com","from-pa.com","from-pr.com","from-ri.com","from-sc.com","from-sd.com","from-tn.com","from-tx.com","from-ut.com","from-va.com","from-vt.com","from-wa.com","from-wi.com","from-wv.com","from-wy.com","ftpaccess.cc","fuettertdasnetz.de","game-host.org","game-server.cc","getmyip.com","gets-it.net","go.dyndns.org","gotdns.com","gotdns.org","groks-the.info","groks-this.info","ham-radio-op.net","here-for-more.info","hobby-site.com","hobby-site.org","home.dyndns.org","homedns.org","homeftp.net","homeftp.org","homeip.net","homelinux.com","homelinux.net","homelinux.org","homeunix.com","homeunix.net","homeunix.org","iamallama.com","in-the-band.net","is-a-anarchist.com","is-a-blogger.com","is-a-bookkeeper.com","is-a-bruinsfan.org","is-a-bulls-fan.com","is-a-candidate.org","is-a-caterer.com","is-a-celticsfan.org","is-a-chef.com","is-a-chef.net","is-a-chef.org","is-a-conservative.com","is-a-cpa.com","is-a-cubicle-slave.com","is-a-democrat.com","is-a-designer.com","is-a-doctor.com","is-a-financialadvisor.com","is-a-geek.com","is-a-geek.net","is-a-geek.org","is-a-green.com","is-a-guru.com","is-a-hard-worker.com","is-a-hunter.com","is-a-knight.org","is-a-landscaper.com","is-a-lawyer.com","is-a-liberal.com","is-a-libertarian.com","is-a-linux-user.org","is-a-llama.com","is-a-musician.com","is-a-nascarfan.com","is-a-nurse.com","is-a-painter.com","is-a-patsfan.org","is-a-personaltrainer.com","is-a-photographer.com","is-a-player.com","is-a-republican.com","is-a-rockstar.com","is-a-socialist.com","is-a-soxfan.org","is-a-student.com","is-a-teacher.com","is-a-techie.com","is-a-therapist.com","is-an-accountant.com","is-an-actor.com","is-an-actress.com","is-an-anarchist.com","is-an-artist.com","is-an-engineer.com","is-an-entertainer.com","is-by.us","is-certified.com","is-found.org","is-gone.com","is-into-anime.com","is-into-cars.com","is-into-cartoons.com","is-into-games.com","is-leet.com","is-lost.org","is-not-certified.com","is-saved.org","is-slick.com","is-uberleet.com","is-very-bad.org","is-very-evil.org","is-very-good.org","is-very-nice.org","is-very-sweet.org","is-with-theband.com","isa-geek.com","isa-geek.net","isa-geek.org","isa-hockeynut.com","issmarterthanyou.com","isteingeek.de","istmein.de","kicks-ass.net","kicks-ass.org","knowsitall.info","land-4-sale.us","lebtimnetz.de","leitungsen.de","likes-pie.com","likescandy.com","merseine.nu","mine.nu","misconfused.org","mypets.ws","myphotos.cc","neat-url.com","office-on-the.net","on-the-web.tv","podzone.net","podzone.org","readmyblog.org","saves-the-whales.com","scrapper-site.net","scrapping.cc","selfip.biz","selfip.com","selfip.info","selfip.net","selfip.org","sells-for-less.com","sells-for-u.com","sells-it.net","sellsyourhome.org","servebbs.com","servebbs.net","servebbs.org","serveftp.net","serveftp.org","servegame.org","shacknet.nu","simple-url.com","space-to-rent.com","stuff-4-sale.org","stuff-4-sale.us","teaches-yoga.com","thruhere.net","traeumtgerade.de","webhop.biz","webhop.info","webhop.net","webhop.org","worse-than.tv","writesthisblog.com",
		"megared.net.mx","dynamic.irkutsk.ertelecom.ru","static-adsl.isurgut.ru","home.otenet.gr","pool.ukrtel.net","rdsnet.ro","cox.net","dyn.optonline.net","superkabel.de","telered.com.ar","telecentro-reversos.com.ar","fibertel.com.ar","speedy.com.ar","adsl.alicedsl.de","netcabo.co.ao","cable.netissat.bg","team.ba","bgcell.net",
		"*dynamic*.connect.com.fj","static.nts-online.net","volia.com","cablenet.de",'metromax.ru',"cable.netins.net",'kapos-net.hu',"xdsl.ab.ru","podolsknet.ru","bredband.telia.com","dynamic.peoplenet.ua","cable.virginmedia.com",
		"cpe.distributel.net","adsl.highway.telekom.at","sssnet.com","cust.bredbandsbolaget.se","optisprint.bg","radiocom.net.ua","in-addr.arpa","kabel-badenwuerttemberg.de","skylink.ru","spb-teleport.ru",
		"steel.net.ar","yota.com.ni","tstt.net.tt","dhcp*wayport.net","maxcom-bg.com","suddenlink.net","arcor-ip.net","dynamic.apogeenet.net",
		"zajecar-web.net","klimovsk.net","alania.net","btc-net.bg","ricona.net.ua","pool.ukrtel.net","dyn.bashtel.ru","mts-nn.ru","retail.telecomitalia.it","net.upcbroadband.cz",
		"pz.ekk.bg","netbynet.ru","megaline.telecom.kz","pioneernet.net","telecom.sk","nt55.ru","dianet.info","petrotel.pl","bayoucable","echoes.net","samfundet.no","tvs12.jp","szarvasnet.hu",
		"mobileonline.telia.com","dialin.compot.ru","idnet.net","osnanet.de","cpe.powergate.ca","openband.net","cpenet.com.ar","mgtelecom.ru","satnet-bg.com","podolsknet.ru","nodosud.com.ar","p-t-k.net","netland.3s.pl","broadband.redcom.ru","cotelcam.net.ar","wtnet.de",
		"actcom.co.il","iprevolution.co.jp","connect.az","jcsfiberlink.net","isp.timbrasil.com.br","hcinet.net","cnt.nerim.net","anitex.by","knology.net","bootp.virginia.edu","tricom.net","cablemodem.amnethn.com","cotas.com.bo","retail.datagroup.ua",
		"blomand.net","cpe.townisp.com","dynamic.hinet.net","kyivstar.net","moldtelecom.md","kristel.ru","kateka.lt","surewest.net","airstreamcomm.net","airnet.ru","nationalcablenetworks.ru","nt.net","ppcom.ru","20la.com.cn","shoreham.net","uln.ru","gcn.ua","cablenet.net.ar","netplaza.fi","broadband.kyivstar.net","is.com.fj","static.3bb.co.th","astinet.telkom.net.id","orangearmenia.am","charter.com","comstar.net.ua","king-online.ru","cable.virginmedia.com","zolcomp.hu","bigsandytelco.com","kyivstar.net","ertelecom.ru","broadband44.net","mojohost.com","balchik.net","e-telecom.ru","icnet.ne.jp","homenet.orn.ru","sktv.ru","lynet.no","skybroadband.com","ptt.rs","broadband.tesco.net","juropnet.hu","pacenet-india.com","teleweb.at","astra2connect.com","wonenbreburgnet.nl","dynamic-dsl.frii.net","norwoodlight","highspeed-baumann.de","dyn.telefonica.de","lipetsk.ru","troycable.net","omkc.ru","dhcp.transact.bm","net.il","iconnectbg.net","dhcp.surewest.net","host-telecom.com","adsl.dynamic.seed.net.tw",
		"vinf.ru","kosnet.ru","seaside.ns.ca","onlink.net","adsl.kis.ru","hnt.ru","spectrum.com.au","ringnett.no","wi.net","zappmobile.ro","dyn.ctinets.com","kbcnet.rs","fament.com","hardynet.com","dynamic.ufanet.ru","dynamic-pool.mclaut.net","dhcp.mpi-stuttgart.mpg.de","gvtel.com",
		"priv.bahnhof.se","intelnet.net.gt","prod-infinitum.com.mx","ftth.internl.net","systech.hu","ark.cablelynx.com","avangarddsl.ru","dyn.bashtel.ru","iusacell.net","isurgut.ru","saturn.infonet.ee","dyn.com","cable.virginmedia.com","superkabel.de","bredbandsbolaget.se","ephone.se","onlinehome-server.info","dynamic.adsl.gvt.net.br","retail.telecomitalia.it","wlms-broadband.com","airtelbroadband.in","isp.belgacom.be","adsl.cyta.gr","dsl.irvnca.pacbell.net","webside.pt","connect.com.fj","midsouth.biz.rr.com","aircel.co.in"
		);
		
		if($this->ParseArray($array, $www)){return "dynamic";}
		
	
		
		include_once(dirname(__FILE__)."/class.squid.catz.dynamic.inc");
		$pub=new squid_catz_dynamic();$cat=$pub->parse($www);if($cat<>null){return "dynamic";}
		if(preg_match("#^box[0-9]+\.bluehost\.com$#", $www)){return "dynamic";}
		if(preg_match("#^modemcable[0-9]+#", $www)){return "dynamic";}
		if(preg_match("#^(dyn|host).*?\.edu#", $www)){return "dynamic";}
		if(preg_match("#^ppp.*?\.(access|tis-dialog|gorge|argontech|internode|htc|omkc)\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#\.highway\.[a-z][0-9]+\.net$#", $www)){return "dynamic";}
		if(preg_match("#[0-9\-\.]+\.telenet\.ru$#", $www)){return "dynamic";}
		if(preg_match("#[0-9\-\.]+\.interra\.ru$#", $www)){return "dynamic";}
		if(preg_match("#.bredband\.telia\.com$#", $www)){return "dynamic";}
		if(preg_match("#-pool.*?\.sccoast\.net$#", $www)){return "dynamic";}
		if(preg_match("#\.uio\.satnet\.net$#", $www)){return "dynamic";}
		if(preg_match("#\.dsl\.iowatelecom\.net$#", $www)){return "dynamic";}
		if(preg_match("#dsl\.telepac\.pt$#", $www)){return "dynamic";}
		if(preg_match("#\.adsl\.kirov\.ru$#", $www)){return "dynamic";}
		if(preg_match("#\.pool\.t-online\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#\.dsl-dynamic\.vsi\.ru$#", $www)){return "dynamic";}
		if(preg_match("#\.adsl\.datanet\.hu$#", $www)){return "dynamic";}
		if(preg_match("#\.dmisinetworks\.net$#", $www)){return "dynamic";}
		if(preg_match("#\.xnet\.hr$#", $www)){return "dynamic";}
		if(preg_match("#^[0-9]+.*?\.scnet\.cz$#", $www)){return "dynamic";}
		if(preg_match("#static\.quiettouch\.com$#", $www)){return "dynamic";}
		if(preg_match("#^access.*?\.neowave\.com\.br$#", $www)){return "dynamic";}
		if(preg_match("#\.usr\.predialnet\.com\.br$#", $www)){return "dynamic";}
		if(preg_match("#\.inet\.dsl\.telianet\.dk$#", $www)){return "dynamic";}
		if(preg_match("#^dyn.*?\.apogeenet\.net$#", $www)){return "dynamic";}
		if(preg_match("#^cpe.*?\.tvcom\.net\.ua$#", $www)){return "dynamic";}
		if(preg_match("#^res.*?\.mediana\.net\.ua$#", $www)){return "dynamic";}
		if(preg_match("#onetel.*?\.setka\.od\.ua$#", $www)){return "dynamic";}
		if(preg_match("#net\.sccoast\.net$#", $www)){return "dynamic";}
		if(preg_match("#v-.*?\.webazilla\.com$#", $www)){return "dynamic";}
		if(preg_match("#[0-9]+\.kovrovinter\.net$#", $www)){return "dynamic";}
		if(preg_match("#ip.*?\.direct-adsl\.nl$#", $www)){return "dynamic";}
		if(preg_match("#dsl-dhcp#", $www)){return "dynamic";}
		if(preg_match("#dhcp-ind\.#", $www)){return "dynamic";}
		if(preg_match("#\.dyndns-.*?\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#[0-9]+\.(serverel|kansai-bb)\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#\.dip\.t-dialin\.net$#", $www)){return "dynamic";}
		if(preg_match("#\.pool\.telekom\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#\.adsl\.tpnet\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#^static-dsl-#", $www)){return "dynamic";}
		if(preg_match("#^dsl-[0-9]+#", $www)){return "dynamic";}
		if(preg_match("#^adsl_dynamic-#", $www)){return "dynamic";}
		if(preg_match("#\.(dynamicip|adsl-dyn|dsl|dsl-dynamic|dhcp-dynamic|dsl-dhcp)\.#", $www)){return "dynamic";}
		if(preg_match("#\.ppp-bb\..*?\.ne\.jp$#", $www)){return "dynamic";}
		if(preg_match("#cpe\.cableonda\.net$#", $www)){return "dynamic";}
		if(preg_match("#cable-.*?\.kymp\.net$#", $www)){return "dynamic";}
		if(preg_match("#\.bb\.online.no$#", $www)){return "dynamic";}
		if(preg_match("#net-.*\.kaluga\.ru$#", $www)){return "dynamic";}
		if(preg_match("#pool\.nikopol\.net$#", $www)){return "dynamic";}
		if(preg_match("#[0-9]+\.onvol\.net$#", $www)){return "dynamic";}
		if(preg_match("#adsl-surfen\.hetnet\.nl$#", $www)){return "dynamic";}
		if(preg_match("#[0-9]+\.tkchopin\.pl$#", $www)){return "dynamic";}
		if(preg_match("#kiv\.fav\.zcu\.cz$#", $www)){return "dynamic";}
		if(preg_match("#^valve-.*?\.telia\.net$#", $www)){return "dynamic";}
		if(preg_match("#\.mobiel\.utwente\.nl$#", $www)){return "dynamic";}
		if(preg_match("#\.mee\.uni-bonn\.de$#", $www)){return "dynamic";}
		if(preg_match("#dhcp\..*\.unimi\.it$#", $www)){return "dynamic";}
		if(preg_match("#^wifi.*?\.u-.*?\.fr$#", $www)){return "dynamic";}
		if(preg_match("#\.static-ip\.oleane\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#[0-9\-]+\.catv\.esch\.lu$#", $www)){return "dynamic";}
		if(preg_match("#^i.*?\.versanet\.de$#", $www)){return "dynamic";}
		if(preg_match("#^fibhost.*?\.fibernet\.hu$#", $www)){return "dynamic";}
		if(preg_match("#^comp.*?\.2day\.kz$#", $www)){return "dynamic";}
		if(preg_match("#wlan\.uni-bremen\.de$#", $www)){return "dynamic";}
		if(preg_match("#^wlan-[0-9\-]+\..*$#", $www)){return "dynamic";}
		if(preg_match("#\.servebeer\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#^lan.*?\.elekta\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#^tdev.*?codetel\.net\.do$#", $www)){return "dynamic";}
		if(preg_match("#[0-9\-]+\.online\.com\.kh$#", $www)){return "dynamic";}
		if(preg_match("#^lan-.*?\.users\.mns\.ru$#", $www)){return "dynamic";}
		if(preg_match("#\.cable\.(graceba|tolna)\.net$#", $www)){return "dynamic";}
		if(preg_match("#[0-9\-]+\.compassnet\.co\.nz$#", $www)){return "dynamic";}
		if(preg_match("#\.cust\.t-mobile\.co\.uk$#", $www)){return "dynamic";}
		if(preg_match("#[0-9\-]+\.nextgentel\.com$#", $www)){return "dynamic";}
		if(preg_match("#ppp\.wakwak\.ne\.jp$#", $www)){return "dynamic";}
		if(preg_match("#[0-9\-]+\.netatonce\.net$#", $www)){return "dynamic";}
		if(preg_match("#net.*?\.t-com\.ne\.jp$#", $www)){return "dynamic";}
		if(preg_match("#dsl\.vsp\.fi$#", $www)){return "dynamic";}
		if(preg_match("#[0-9\-]+\.infomir\.com\.ua$#", $www)){return "dynamic";}
		if(preg_match("#\.hd\.free\.fr$#", $www)){return "dynamic";}
		if(preg_match("#\.synology\.me$#", $www)){return "dynamic";}
		if(preg_match("#[0-9\-]+\.ses-broadband\.net$#", $www)){return "dynamic";}
		if(preg_match("#[0-9\-]+\.cust\.bluewin\.ch$#", $www)){return "dynamic";}
		if(preg_match("#[0-9\-]+\.vgt\.ru$#", $www)){return "dynamic";}
		if(preg_match("#\.dynadsl\.kis\.ru$#", $www)){return "dynamic";}
		if(preg_match("#[0-9\.]+\.[a-z]+\.[0-9]+\.go\.com\.jo$#", $www)){return "dynamic";}
		if(preg_match("#ppp-dhcp\.logic\.bm$#", $www)){return "dynamic";}
		if(preg_match("#^ppp-dhcp.*#", $www)){return "dynamic";}
		if(preg_match("#\.customer\.vsm\.sh$#", $www)){return "dynamic";}
		if(preg_match("#^dyn-[0-9\-]+.*#", $www)){return "dynamic";}
		if(preg_match("#^adsl[0-9\-]+\.#", $www)){return "dynamic";}
		if(preg_match("#[0-9]+.*?\.in72\.ru$#", $www)){return "dynamic";}
		if(preg_match("#^dsl-.*?pool.*?[0-9\-\.]+\.#", $www)){return "dynamic";}
		if(preg_match("#\.dynamic\.webworksgy\.com$#", $www)){return "dynamic";}
		if(preg_match("#dhcp\.(indytel|mvtel|inet|logic|iowatelecom)\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#-dynamic\.kti\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#dialup\.saransk\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#dynamic\.pppoe\.mgts\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#cpe\.sourcecable\.net$#", $www)){return "dynamic";}
		if(preg_match("#\.adsl\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#adsl.*\.telefonica\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#\.oct-net\.ne\.jp$#", $www)){return "dynamic";}
		if(preg_match("#\.ucatv\.ne\.jp$#", $www)){return "dynamic";}
		if(preg_match("#\.cust\.ip\..*?\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#ppp.*?\.[a-z]+\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#ppp[0-9]+#", $www)){return "dynamic";}
		if(preg_match("#pppoe-.*?.bellaliant\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#[0-9]+.*?\.pppoe\.#", $www)){return "dynamic";}
		if(preg_match("#nwlink\.spb\.ru$#", $www)){return "dynamic";}
		if(preg_match("#ppp\.asahi-net\.or\.jp$#", $www)){return "dynamic";}
		if(preg_match("#upc-i\.chello\.nl$#", $www)){return "dynamic";}
		if(preg_match("#\.home\.otenet\.gr$#", $www)){return "dynamic";}
		if(preg_match("#[0-9]+\.ctinets\.com$#", $www)){return "dynamic";}
		if(preg_match("#[0-9]+\.static\.ctinets\.com$#", $www)){return "dynamic";}
		if(preg_match("#^static.*?\.rev\.dyxnet\.com$#", $www)){return "dynamic";}
		if(preg_match("#^so.*?\.asiawhere\.com$#", $www)){return "dynamic";}
		if(preg_match("#[0-9\-]+.*?\.(kbtelecom|icpnet|telrad)\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#^[0-9]+\..*?\.vectranet\.pl$#", $www)){return "dynamic";}
		if(preg_match("#^box[0-9]+\.bluehost\.com$#", $www)){return "dynamic";}
		if(preg_match("#\.dhcp-dynamic\.#", $www)){return "dynamic";}
		if(preg_match("#^ip.*?\.(banglalionwimax|interplanet)\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#\.pphosted\.com$#", $www)){return "dynamic";}
		if(preg_match("#\.bizhost\.ultina\.jp$#", $www)){return "dynamic";}
		if(preg_match("#^s[0-9]+\.(xrea|xserver|coreserver)\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#^[a-z]+[0-9].\coreserver\.jp$#", $www)){return "dynamic";}
		if(preg_match("#\.coowo\.com$#", $www)){return "dynamic";}
		if(preg_match("#pool.*?\.(interbusiness|infonet|vml-ext7|businesstel|ptcomm|seti|earthlink|brain|e-styleisp|is74|externet|telenor|tolna|interplanet|telusmobility|donpac|ippark|telnetcommunications|primcom|bellsolutions|magistral)\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#xdsl-cust\.uno\.it$#", $www)){return "dynamic";}
		if(preg_match("#\.flagman\.zp\.ua$#", $www)){return "dynamic";}
		if(preg_match("#\.staticip\.rima-tde\.net$#", $www)){return "dynamic";}
		if(preg_match("#\.unused-addr\.ncport\.ru$#", $www)){return "dynamic";}
		if(preg_match("#[0-9\-]+\.(vivanet|cn)\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#^tk[0-9]+\.teleweb\.at$#", $www)){return "dynamic";}
		if(preg_match("#^dyn.*?\.nbw\.tue\.nl$#", $www)){return "dynamic";}
		if(preg_match("#^chello[0-9]+\.chello\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#^ip-.*?[0-9]+.*?\.(kiev|proline|sunline)\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#\.bredband\.skanova\.com$#", $www)){return "dynamic";}
		if(preg_match("#^ip-.*?\.silale\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#^eth-.*?\.natm\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#^power.*?\.powerline.com\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#vpn\.uva\.nl$#", $www)){return "dynamic";}
		if(preg_match("#\.client\.tomica\.ru$#", $www)){return "dynamic";}
		if(preg_match("#\.in-addr\.arpa$#", $www)){return "dynamic";}
		if(preg_match("#\.sand\.cisbg\.com$#", $www)){return "dynamic";}
		if(preg_match("#\.upc-e\.chello\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#\.parabole\.lt$#", $www)){return "dynamic";}
		if(preg_match("#\.interpc\.pl$#", $www)){return "dynamic";}
		if(preg_match("#\.b2b2c\.ca$#", $www)){return "dynamic";}
		if(preg_match("#\.perm\.ertelecom\.ru$#", $www)){return "dynamic";}
		if(preg_match("#\.ntlworld\.ie$#", $www)){return "dynamic";}
		if(preg_match("#\.speedy\.telkom\.net\.id$#", $www)){return "dynamic";}
		if(preg_match("#\.skylink\.ru$#", $www)){return "dynamic";}
		if(preg_match("#^host.*?\.(parktele|infolink)\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#host[0-9]+-ktv\.furedkabel\.hu$#", $www)){return "dynamic";}
		if(preg_match("#^host[0-9\-]+\.(resnet|bs-telecom|cisbg|junet|hostmonster|alania)\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#^hst[0-9\-]+\.balticservers\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#^static[0-9\-]+\.junet\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#^vidin.*?\.vidaoptics\.com$#", $www)){return "dynamic";}
		if(preg_match("#\.pccwmobile.com$#", $www)){return "dynamic";}
		if(preg_match("#\.static\.rozabg\.com$#", $www)){return "dynamic";}
		if(preg_match("#^host.*?\.bs-telecom\.net$#", $www)){return "dynamic";}
		if(preg_match("#^client.*?\.green-net\.ro$#", $www)){return "dynamic";}
		if(preg_match("#\.net[0-9]+\.udm\.net$#", $www)){return "dynamic";}
		if(preg_match("#^pop-.*?\.escom\.bg$#", $www)){return "dynamic";}
		if(preg_match("#\.ip\.peterstar\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#\.talkadsl[0-9]+\.com$#", $www)){return "dynamic";}
		if(preg_match("#\.tel\.net\.ba$#", $www)){return "dynamic";}
		if(preg_match("#\.seed\.net\.tw$#", $www)){return "dynamic";}
		if(preg_match("#\.(skknet|polustrovo)\.net$#", $www)){return "dynamic";}
		if(preg_match("#\.comnet\.bg$#", $www)){return "dynamic";}
		if(preg_match("#\.homell\.natm\.ru$#", $www)){return "dynamic";}
		if(preg_match("#^client.*?\.(iberbanda|infolink|euskaltel|ultracom|speedy\-net)\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#^dialup.*?\.(overta|azerin|magistral|comch|pdi|inetia)\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#adsl.*?\.(ptt|online|internode|start|belgacom|green|newnet|espeed|altohiway|cybersmart|cistron|cyfra)\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#^customer.*?\.livas\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#\.tvhost\.ru$#", $www)){return "dynamic";}
		if(preg_match("#\.cust\.mdfnet\.se$#", $www)){return "dynamic";}
		if(preg_match("#^[0-9]+\.(solunet|izhnt|techcomnet)\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#dyn\.iinet\.net\.au$#", $www)){return "dynamic";}
		if(preg_match("#\.ftth\.rus-com\.net$#", $www)){return "dynamic";}
		if(preg_match("#^[0-9]+.*?\.thezone\.bg$#", $www)){return "dynamic";}
		if(preg_match("#^hst-.*?\.fibermax\.bg$#", $www)){return "dynamic";}
		if(preg_match("#\.server-home\.org$#", $www)){return "dynamic";}
		if(preg_match("#\.stat\.salzburg-online\.at$#", $www)){return "dynamic";}
		if(preg_match("#\.adsl\.internet\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#-adsl\.vntc\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#fixip\.t-online\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#^mobint-.*?\.crnagora\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#[0-9]+\.(conceptcable|netnet)\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#\.(crestviewcable|mncable)\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#\.dhcp\.inet\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#(dhcp-pool|dynamicip|dynamic-pool|dynamic-vpdn)#", $www)){return "dynamic";}
		if(preg_match("#^pptp-.*?\.(kamensktel|vnet\.dn)\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#^bb.*?.singnet\.com\.sg$#", $www)){return "dynamic";}
		if(preg_match("#^catv-.*?\.tbwil\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#pool.*?\.(elberton|iconz)\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#pc\.[a-z]+\.net\.ua$#", $www)){return "dynamic";}
		if(preg_match("#^wireless-.*?\.edu#", $www)){return "dynamic";}
		if(preg_match("#pool.*?\.(orange|bmts|t-umts)\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#dynamic.*?\.connect\.com\.fj$#", $www)){return "dynamic";}
		if(preg_match("#dhcp.*?(amaonline)\.[a-z\.]+$#", $www)){return "dynamic";}
		if(preg_match("#^atech-.*?\.atech\.lv$#", $www)){return "dynamic";}
		if(preg_match("#dynamic-client-[0-9]+#", $www)){return "dynamic";}
		if(preg_match("#^dhcp-[a-z][0-9]+\.#", $www)){return "dynamic";}
		
	}
	
	
	private function travel($www){
		
		$array=array("secure-reservation","expedia","chatel","trivago","isango","aeroport","hotels","ihg",
		"makemytrip","opodo","cityway","airfrance","gho","hoteles","ihacom","tourinsoft","geo-web-mail","geo"
		,"partirpascher","ruskompas","hostelsystem","resawin","hotels-de-charme","trivago","audierne.oscim.org","viesumajas.lv","zafirotours");
		if($this->ParseArray($array, $www)){return "recreation/travel";}
		
		if(strpos($www, "voyages")>0){return "recreation/travel";}
		
		if(preg_match("#^travel\..*?$#", $www)){return "recreation/travel";}
		if(preg_match("#^(turismo|travel|voyage)#", $www)){return "recreation/travel";}
		if(preg_match("#-travel\.#", $www)){return "recreation/travel";}
		if(preg_match("#travel-.*?\.#", $www)){return "recreation/travel";}
		if(preg_match("#\.voyage\.#", $www)){return "recreation/travel";}
		if(preg_match("#\.voyages\.#", $www)){return "recreation/travel";}
		if(preg_match("#^hotel-[a-z]+#", $www)){return "recreation/travel";}
		if(preg_match("#^hotels-[a-z\.]+#", $www)){return "recreation/travel";}
		if(preg_match("#^hotel[a-z]+\.[a-z]+$#", $www)){return "recreation/travel";}
		if(preg_match("#^voyage.+?#", $www)){return "recreation/travel";}
		if(preg_match("#^voyages.+?#", $www)){return "recreation/travel";}
		if(preg_match("#^vols\..+?#", $www)){return "recreation/travel";}
		if(preg_match("#^camping[-\.].+?\..+?$#", $www)){return "recreation/travel";}
		if(preg_match("#^hotel[a-z]+.+?\.[a-z]+$#", $www)){return "recreation/travel";}
		if(preg_match("#^kyriad-(.+?)\.fr$#", $www)){return "recreation/travel";}	
		if(preg_match("#(\.|\-)tourisme\.#", $www)){return "recreation/travel";}
		if(preg_match("#\.l-et-v\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#^gites(\-|\.)#", $www)){return "recreation/travel";}
		if(preg_match("#\.urbita\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#\.turistforeningen\.[a-z]+$#", $www)){return "recreation/travel";}
		if(preg_match("#\.lvpingphoto\.com$#",$www)){return "recreation/travel";}
		if(preg_match("#gite.*?-de-france#", $www)){return "recreation/travel";}
		if(preg_match("#\.emirates\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#\.travimp\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#\.airplus\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#\.disneylandparis\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#\.atraveo\.[a-z]+$#", $www)){return "recreation/travel";}
		if(preg_match("#\.travel$#", $www)){return "recreation/travel";}
		if(preg_match("#-camping\.[a-z]+$#", $www)){return "recreation/travel";}
		if(preg_match("#(holiday|fasthotel|campingdela|mercanotel|vacanze|transpole|vacances|airlines|tourisme|voyage|hoteliere|travel|fastbooking|touristik)#", $www)){return "recreation/travel";}
	}
	
	
	function news($www){
		
		$array=array("n24","news","3news","journaldunet","linternaute","feedsportal","townnews","cnn",
				"newser","wsj","newsinc","orbyt","itespresso","nytimes","dailymail","n24.de","ruvr.ru","leprogres","lesechos","bbcimg","bbc");
				
		if($this->ParseArray($array, $www)){return "news";}
		
		if(preg_match("#-news\.[a-z]+$#",$www)){return "news";}
		

		if(preg_match("#\.wn\.com$#",$www)){return "news";}
		if(preg_match("#\.wpdigital\.net$#",$www)){return "news";}
		if(preg_match("#\.govtech\.com$#",$www)){return "news";}
		if(preg_match("#\.elchron\.cz$#",$www)){return "news";}
		if(preg_match("#\.oneindia\.in$#",$www)){return "news";}
		if(preg_match("#\.postbulletin\.com$#",$www)){return "news";}
		if(preg_match("#\.rbc\.ru$#",$www)){return "news";}
		if(preg_match("#\.smh\.com\.au$#",$www)){return "news";}
		if(preg_match("#\.yourguide\.com\.au$#",$www)){return "news";}
		if(preg_match("#\.europapress\.net$#",$www)){return "news";}
		if(preg_match("#\.lesechos\.fr$#",$www)){return "news";}
		if(preg_match("#\.editionsdumoniteur\.com$#",$www)){return "news";}
		if(preg_match("#\.indiatimes\.com$#",$www)){return "news";}
		if(preg_match("#\.nouvelobs\.com$#",$www)){return "news";}
		if(preg_match("#\.news\.yahoo\.[a-z]+$#",$www)){return "news";}
		if(preg_match("#\.msnbc\.msn\.com$#",$www)){return "news";}
		if(preg_match("#\.corriere\.it$#",$www)){return "news";}
		if(preg_match("#\.rb\.ru$#",$www)){return "news";}
		if(preg_match("#\.ad\.nl$#", $www)){return "news";}
		if(preg_match("#^news\..+#", $www)){return "news";}
		if(preg_match("#\.foxnews\.com$#", $www)){return "news";}
		if(preg_match("#noticias24.com$#", $www)){return "news";}
		if(preg_match("#\.midilibre\.fr$#", $www)){return "news";}
		if(preg_match("#\.pressdisplay\.com$#", $www)){return "news";}
		if(preg_match("#\.washingtonpost\.com$#", $www)){return "news";}
		if(preg_match("#\.lemondeinformatique\.fr$#", $www)){return "news";}
		if(preg_match("#\.lemoniteur\.fr$#", $www)){return "news";}
		if(preg_match("#\.lefigaro\.fr$#", $www)){return "news";}	
		if(preg_match("#\.reuters\.com$#", $www)){return "news";}	
		if(preg_match("#\.news[0-9]+\.[a-z]+$#", $www)){return "news";}
		if(preg_match("#\.unita\.it$#", $www)){return "news";}
		if(preg_match("#\.publico\.pt$#", $www)){return "news";}
		if(preg_match("#\.repubblica\.it$#", $www)){return "news";}
		if(preg_match("#\.epochtimes\.com$#", $www)){return "news";}
		if(preg_match("#\.businessweek\.com$#", $www)){return "news";}
		if(preg_match("#\.abcimg\.es$#", $www)){return "news";}
		if(preg_match("#\.techradar\.futurecdn\.net$#", $www)){return "news";}
		if(preg_match("#\.techradar\.[a-z]+$#", $www)){return "news";}
		if(preg_match("#\.sfgate\.com$#", $www)){return "news";}
		if(preg_match("#\.gazeta\.[a-z]+$#", $www)){return "news";}
		if(preg_match("#\.terra\.com$#", $www)){return "news";}
		if(preg_match("#\.emol\.com$#", $www)){return "news";}
		if(preg_match("#\.diariocritico\.com$#", $www)){return "news";}
		if(preg_match("#\.h-avis\.no$#", $www)){return "news";}
		if(preg_match("#\.usatoday\.com$#", $www)){return "news";}
		if(preg_match("#^courrierdes#", $www)){return "news";}
		if(preg_match("#(watch|pap|cast|herald|sentinel|courier|gazet|tribune|chronicle|daily|news).*?\.(blogspot|altervista).*?\.[a-z]+$#", $www)>0){return "news";}
	}
	
	private function smallads($www){
		$array=array("craigslist","leboncoin","hyperannonces","vivastreet","toursannoncesn","avito","ruslist.ru","clasificadosgratis","videdressing","khojle","ebaystatic","ebay","ekomissionka","addyourads");
		if($this->ParseArray($array, $www)){return "smallads";}
		if(preg_match("#\.traderonline\.com$#", $www)){return "smallads";}
		
		if(preg_match("#\.clubannonces.com$#", $www)){return "smallads";}
		if(preg_match("#\.sulekha\.com$#", $www)){return "smallads";}
		if(preg_match("#\.tweedehands\.nl$#", $www)){return "smallads";}
		if(preg_match("#\.lespac\.com$#", $www)){return "smallads";}
		if(preg_match("#\.olx\.[a-z]+$#", $www)){return "smallads";}
		if(preg_match("#\.olx\.[a-z]+\.[a-z]+$#", $www)){return "smallads";}
		if(preg_match("#\.pap\.fr$#", $www)){return "smallads";}
		if(preg_match("#\.craigslist\.fr$#", $www)){return "smallads";}
		if(preg_match("#\.[a-z]+-annonces\.[a-z]+$#", $www)){return "smallads";}	
			
		if(preg_match("#\.slando\.ru$#", $www)){return "smallads";}
		if(preg_match("#\.gratka\.pl$#", $www)){return "smallads";}
		if(preg_match("#\.freepropertyads\.[a-z\.]+$#", $www)){return "smallads";}	
		if(preg_match("#\.alamaulastatic\.com$#", $www)){return "smallads";}
		if(preg_match("#\.olx-st\.com$#", $www)){return "smallads";}
		if(preg_match("#\.olx\.[a-z]+$#", $www)){return "smallads";}
		if(preg_match("#\.kijiji\.[a-z]+$#", $www)){return "smallads";}
		if(preg_match("#^kijiji\.[a-z]+$#", $www)){return "smallads";}
		if(preg_match("#\.tboks\.com$#", $www)){return "smallads";}
		if(preg_match("#\.mundoanuncio\.[a-z\.]+$#", $www)){return "smallads";}
		if(preg_match("#\.adicio\.com$#", $www)){return "smallads";}
		if(preg_match("#\.freecycle\.org$#", $www)){return "smallads";}
		if(preg_match("#\.slando\.[a-z\.]+$#", $www)){return "smallads";}
		if(preg_match("#^avito\.[a-z\.]+$#", $www)){return "smallads";}
		if(preg_match("#\.avito\.[a-z\.]+$#", $www)){return "smallads";}
		if(preg_match("#\.site-pap\.fr$#", $www)){return "smallads";}
		if(preg_match("#\.freelyads\.[a-z\.]+$#", $www)){return "smallads";}
		if(preg_match("#\.olxalgerie\.com$#", $www)){return "smallads";}
		if(preg_match("#\.auctions\.yahoo\.[a-z\.]+$#", $www)){return "smallads";}
		if(preg_match("#\.mes-occasions\.[a-z\.]+$#", $www)){return "smallads";}
		if(preg_match("#\.annoncesjaunes\.fr$#", $www)){return "smallads";}
		if(preg_match("#(clasificados|annonces)#", $www)){return "smallads";}
	}
	
	private function realestate($www){
		
		$array=array("nndv.ru","soufunimg","soufun","meilleursagents");
		if($this->ParseArray($array, $www)){return "finance/realestate";}
		
		
		$patternG="(maison|villa|pavillon|appartement|demeures|logement|habitat|immobilier)";
		if(preg_match("#\.sjaap\.nl$#", $www)){return "finance/realestate";}
		if(preg_match("#\.century21\.fr$#", $www)){return "finance/realestate";}
		if(preg_match("#\.poliris\.com$#", $www)){return "finance/realestate";}
		if(preg_match("#^sahlm[0-9]+\.fr$#", $www)){return "finance/realestate";}
		if(preg_match("#^laforet-immobilier-.*?\.[a-z]+#", $www)){return "finance/realestate";}
		if(preg_match("#\.kyero\.com$#", $www)){return "finance/realestate";}
		if(preg_match("#\.cybelagence\.net#", $www)){return "finance/realestate";}
		if(strpos($www, "realestate")){return "finance/realestate";}
		if(preg_match("#^realestate\.#", $www)){return "finance/realestate";}
		if(preg_match("#habitat[0-9]+\.fr$#", $www)){return "finance/realestate";}
		if(preg_match("#\.imonet\.[a-z]+#", $www)){return "finance/realestate";}
		if(preg_match("#^imonet\.[a-z]+#", $www)){return "finance/realestate";}
		if(preg_match("#^opmhlm-.*?\.fr$#", $www)){return "finance/realestate";}	
		if(preg_match("#\.img.*?-otodom\..*?$#", $www)){return "finance/realestate";}	
		if(preg_match("#\.seeff\.com$#", $www)){return "finance/realestate";}	
		
		if(preg_match("#^immo(ouest|sud|est|nord)#", $www)){return "finance/realestate";}
		if(preg_match("#^immo-#", $www)){return "finance/realestate";}
		if(preg_match("#-immo-#", $www)){return "finance/realestate";}
		if(preg_match("#immo\.[a-z]+$#", $www)){return "finance/realestate";}
		if(preg_match("#(immobilier|immobilie|logement|immobiliar|mobiliario|residence|pavillon|habitat|realestate|demeures|apartamente|mobiliaria|immobilien)#", $www)){return "finance/realestate";}	
		if(preg_match("#(trouver|chercher|location).*?(logement|maison|appart|syndic|habitat|pavillon).*?\.[a-z]+$#", $www)){return "finance/realestate";}
		if(preg_match("#^$patternG-.*?\.site-pap\.fr$#", $www)){return "finance/realestate";}
		if(preg_match("#$patternG.*?(vendre|vente|louer)#", $www)){return "finance/realestate";}
		if(preg_match("#^$patternG-#", $www)){return "finance/realestate";}
	}
	
	private function banking($www){
		
		$array=array("banamex","natixis","paypal","americanexpress","pncbank","citibank","bornpay",
				"banquepopulaire","societegenerale","labanquepostale","credit-agricole","deutsche-bank","caisse-epargne",
				"bnpparibas","mastercardconnect","cib.com.cn","bb.com.br",
				);
		
		if($this->ParseArray($array, $www)){return "finance/banking";}
		
		if(preg_match("#\.e-merchant\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.pepitastore\.com$#",$www)){return "finance/banking";}
		

		if(preg_match("#\.alipay\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.capitalone360\.com$#",$www)){return "finance/banking";}

		if(preg_match("#\.clickandbuy\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.postbank\.de$#",$www)){return "finance/banking";}
		if(preg_match("#\.isabel\.be$#",$www)){return "finance/banking";}
		
		if(preg_match("#\.cbc\.be$#",$www)){return "finance/banking";}	
		if(preg_match("#\.vend-o\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.bil\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.paypal\.[a-z]+$#",$www)){return "finance/banking";}
		if(preg_match("#\.paypal\.[a-z]+\.[a-z]+$#",$www)){return "finance/banking";}
		if(preg_match("#\.americanexpress\.[a-z\.]+$#",$www)){return "finance/banking";}
		if(preg_match("#\.ca-languedoc\.[a-z]+$#",$www)){return "finance/banking";}
		if(preg_match("#^paypal\.[a-z\.]+$#",$www)){return "finance/banking";}
		if(preg_match("#\.micropayment\.[a-z\.]+$#",$www)){return "finance/banking";}
		if(preg_match("#\.mastercardconnect\.com$#",$www)){return "finance/banking";}
		
		if(preg_match("#\.credit-agricole-.*?\.[a-z\.]+$#",$www)){return "finance/banking";}
		if(preg_match("#\.harrisbank\.[a-z\.]+$#",$www)){return "finance/banking";}
		if(preg_match("#\.abnamro\.[a-z\.]+$#",$www)){return "finance/banking";}
		
	}
	
	function GetCategories($www){
		$www=strtolower($www);
		if(trim($www)==null){
			if($GLOBALS["VERBOSE"]){echo "GetCategories() no such website\n";}
		}
		$cat=null;
		
		$cat=$this->clothing($www);if($cat<>null){return $cat;}
		$cat=$this->industry($www);if($cat<>null){return $cat;}
		$cat=$this->remote_control($www);if($cat<>null){return $cat;}
		$cat=$this->sslsites($www);if($cat<>null){return $cat;}
		
		$cat=$this->ScienceComputing($www);if($cat<>null){return $cat;}
		$cat=$this->dynamic($www);if($cat<>null){return $cat;}
		
		
		$cat=$this->weather($www);if($cat<>null){return $cat;}
		$cat=$this->smallads($www);if($cat<>null){return $cat;}
		$cat=$this->shopping($www);if($cat<>null){return $cat;}
		$cat=$this->womanbrand($www);if($cat<>null){return $cat;}
		
		
		$cat=$this->publicite($www);if($cat<>null){return $cat;}
		$cat=$this->proxy($www);if($cat<>null){return $cat;}
		
		$cat=$this->mixed_adult($www);if($cat<>null){return $cat;}
		$cat=$this->porn($www);if($cat<>null){return $cat;}
		$cat=$this->dating($www);if($cat<>null){return $cat;}
		$cat=$this->games($www);if($cat<>null){return $cat;}
		$cat=$this->culture($www);if($cat<>null){return $cat;}
		$cat=$this->society($www);if($cat<>null){return $cat;}
		$cat=$this->celebrity($www);if($cat<>null){return $cat;}
		
		$cat=$this->Tracker($www);if($cat<>null){return $cat;}
		$cat=$this->mailing($www);if($cat<>null){return $cat;}
		$cat=$this->chat($www);if($cat<>null){return $cat;}
		
		
		$cat=$this->gamble($www);if($cat<>null){return $cat;}
		$cat=$this->movies($www);if($cat<>null){return $cat;}
		
		// AUTOMOBILE
		$cat=$this->automobile($www);if($cat<>null){return $cat;}
		$cat=$this->transports($www);if($cat<>null){return $cat;}
		
		$cat=$this->webplugins($www);if($cat<>null){return $cat;}
		$cat=$this->tobacco($www);if($cat<>null){return $cat;}
		
		
		
		$cat=$this->wellness($www);if($cat<>null){return $cat;}
		$cat=$this->Blog($www);if($cat<>null){return $cat;}
		$cat=$this->news($www);if($cat<>null){return $cat;}
		$cat=$this->travel($www);if($cat<>null){return $cat;}
		
		//SPORTS
		$cat=$this->sports($www);if($cat<>null){return $cat;}
		$cat=$this->webmail($www);if($cat<>null){return $cat;}
		$cat=$this->maps($www);if($cat<>null){return $cat;}
		$cat=$this->webtv($www);if($cat<>null){return $cat;}
		$cat=$this->dictionaries($www);if($cat<>null){return $cat;}
		
		$cat=$this->music($www);if($cat<>null){return $cat;}
		
		
		$cat=$this->Filehosting($www);if($cat<>null){return $cat;}
		$cat=$this->imagehosting($www);if($cat<>null){return $cat;}
		
		$cat=$this->downloads_catz($www);if($cat<>null){return $cat;}
		$cat=$this->realestate($www);if($cat<>null){return $cat;}
		$cat=$this->banking($www);if($cat<>null){return $cat;}
		
		$cat=$this->Children($www);if($cat<>null){return $cat;}
		$cat=$this->MobilePhone($www);if($cat<>null){return $cat;}
		$cat=$this->schools($www);if($cat<>null){return $cat;}
		
		$cat=$this->governments($www);if($cat<>null){return $cat;}
		$cat=$this->Books($www);if($cat<>null){return $cat;}
		$cat=$this->humanitarian($www);if($cat<>null){return $cat;}
		$cat=$this->audiovideo($www);if($cat<>null){return $cat;}
		$cat=$this->searchengines($www);if($cat<>null){return $cat;}
		$cat=$this->pictureslib($www);if($cat<>null){return $cat;}
		$cat=$this->socialnet($www);if($cat<>null){return $cat;}
		
		
		
		if(preg_match("#\.[a-z]rsi\.symantec\.com$#", $www)){return "cleaning";}
		if(preg_match("#\.malwarehelp\.org$#", $www)){return "cleaning";}
		
		if(preg_match("#ancestry\.#", $www)){return "genealogy";}
		if(preg_match("#(genealogist|genealogy)#", $www)){return "genealogy";}

		if(preg_match("#\.pingan\.com$#",$www)){return "finance/insurance";}
		if(preg_match("#^pingan\.com$#",$www)){return "finance/insurance";}
		if(preg_match("#\.generali\.fr$#",$www)){return "finance/insurance";}
		if(preg_match("#\.ameli\.fr$#",$www)){return "finance/insurance";}
		if(preg_match("#.*?-assurances\.[a-z]+$#",$www)){return "finance/insurance";}
		if(preg_match("#.*?-assurance\.[a-z]+$#",$www)){return "finance/insurance";}
		if(preg_match("#(mutuelle|assurance|insurance|allianz|swisslife|seguranca|prevoyance|assuradeuren)#",$www)){return "finance/insurance";}
		if(preg_match("#\.darva\.com$#",$www)){return "finance/insurance";}		
		if(preg_match("#\.allianz\.[a-z]+$#",$www)){return "finance/insurance";}
		
		if(preg_match("#(creditcard|ses-credits)#",$www)){return "finance/moneylending";}
		if(preg_match("#-credit\.[a-z\.]+$#",$www)){return "finance/moneylending";}
		
		if(preg_match("#^avocat[\-\.]#", $www)){return "justice";}
		if(preg_match("#^avocats-.+?\..+?$#", $www)){return "justice";}
		if(preg_match("#-avocat\.[a-z]+$#", $www)){return "justice";}
		if(preg_match("#^huissier(\.|\-)#", $www)){return "justice";}
		if(preg_match("#\.notaires\.fr$#", $www)){return "justice";}
		if(preg_match("#\.jus\.br$#", $www)){return "justice";}
		if(preg_match("#\.roi\.ru$#", $www)){return "justice";}
		if(preg_match("#(avocat|notaire|judiciar|advokat|judiciaire|justica|huissier|harcelement|justice|tribunal|prudhommes)#", $www)){return "justice";}
		
		if(preg_match("#(geologie|sciences|archaeology)#", $www)){return "sciences";}
		if(preg_match("#\.researchgate\.net$#", $www)){return "sciences";}
		if(preg_match("#^cnrs-.*?\.fr$#", $www)){return "sciences";}
		if(preg_match("#\.cnrs\.fr$#", $www)){return "sciences";}
		if(preg_match("#\.cnrs-.*?\.fr$#", $www)){return "sciences";}
		
		if(preg_match("#\.asso\.fr$#", $www)){return "associations";}
		if(preg_match("#^asso.*\.free\.fr$#", $www)){return "associations";}
		if(preg_match("#^mfr[0-9]+\.org$#", $www)){return "associations";}
		if(preg_match("#^associations\..*?\.[a-z]+$#", $www)){return "associations";}
		if(preg_match("#^association-.*?\.[a-z]+$#", $www)){return "associations";}
		if(preg_match("#^association[0-9]+.*?\.[a-z]+$#", $www)){return "associations";}
		if(preg_match("#^association[a-z]+\.[a-z]+$#", $www)){return "associations";}
		if(preg_match("#^associations-.*?\.[a-z]+$#", $www)){return "associations";}
		if(preg_match("#\.asso-web\.com$#", $www)){return "associations";}
		if(preg_match("#^asso-.*?\.fr$#", $www)){return "associations";}
		if(preg_match("#-association\.[a-z]+$#", $www)){return "associations";}
		if(preg_match("#\..*?-telethon\.[a-z]+$#", $www)){return "associations";}
		if(preg_match("#\.asso-web\.[a-z]+$#", $www)){return "associations";}
		if(preg_match("#centresocial#", $www)){return "associations";}
		if(preg_match("#-asso\.fr$#", $www)){return "associations";}
		if(preg_match("#\.vgwort.de$#", $www)){return "associations";}
		if(preg_match("#^resistance-[0-9]+\.[a-z]+$#", $www)){return "associations";}
		

		
		if(preg_match("#\.swagbucks\.com$#", $www)){return "paytosurf";}
		if(preg_match("#\.mow\.fm$#", $www)){return "paytosurf";}
		if(preg_match("#\.bitterstrawberry\.com$#", $www)){return "paytosurf";}
		if(preg_match("#(howtomakemoneyonline|moneymaker|earnmoney|makingmoney|makedreamprofits)#", $www)){return "paytosurf";}
		
		if(preg_match("#\.mangareader\.net$#", $www)){return "manga";}
		if(preg_match("#-mangas\.[a-z\.]+$#", $www)){return "manga";}
		if(preg_match("#\.voyance\.avigora\.com$#", $www)){return "astrology";}
		if(preg_match("#\.tarot\.com$#", $www)){return "astrology";}
		if(preg_match("#(astrolog|voyance|horoskop|horoscope)#", $www)){return "astrology";}
		
		if(preg_match("#\.vodeclic\.[a-z]+$#", $www)){return "learning";}
		if(preg_match("#\.sprachcaffe\.com$#", $www)){return "learning";}
		if(preg_match("#jmguinand\.com$#", $www)){return "learning";}
		if(preg_match("#\.telelangue\.com$#", $www)){return "learning";}
		if(preg_match("#\.englishtown\.com$#", $www)){return "learning";}
		if(preg_match("#(formations|formazione|learning)#", $www)){return "learning";}
		if(preg_match("#\.afpa\.fr$#", $www)){return "learning";}
		if(preg_match("#fongecif-.*?\.fr$#", $www)){return "learning";}
		if(preg_match("#^([a-z]formation|cciformation)#", $www)){return "learning";}
		
		if(strpos($www, "phobos.apple.com")>0){return "updatesites";}
		if(preg_match("#\.iphmx\.com$#",$www)){return "updatesites";}
		if(preg_match("#\.windowsupdate.com$#",$www)){return "updatesites";}
		if(preg_match("#\.update\.microsoft\.com$#",$www)){return "updatesites";}
		if(preg_match("#\.cloud\.avg\.com$#",$www)){return "updatesites";}
		if(preg_match("#\.avira-update\.[a-z]+$#",$www)){return "updatesites";}
		if(preg_match("#\.bandwidthplace\.com$#",$www)){return "updatesites";}
		if(preg_match("#\.ipv6test\.com$#",$www)){return "updatesites";}
		if(preg_match("#speedtest#",$www)){return "updatesites";}
		if(preg_match("#[0-9]+.*?(mp|g)\.avast\.com$#",$www)){return "updatesites";}
		if(preg_match("#[a-z][0-9]+[a-z]+\.avast\.com$#",$www)){return "updatesites";}
		if(preg_match("#\.ctmail\.com$#",$www)){return "updatesites";}
		if(preg_match("#^upd.*?\.kaspersky.*\.#",$www)){return "updatesites";}
		if(preg_match("#^archive.(ubuntu|debian).*\.#",$www)){return "updatesites";}
		
		if(preg_match("#^speedtest\..*?$#", $www)){return "updatesites";}
		if(preg_match("#\.testdebit\.info$#", $www)){return "updatesites";}
		if(preg_match("#\.mbamupdates\.com$#",$www)){return "updatesites";}
		if(preg_match("#downloa.+?\.avast\.[a-z]+#", $www)){return "updatesites";}
		if(preg_match("#ff\.avast\.com#", $www)){return "updatesites";}
		if(preg_match("#ksn.+?\.kaspersky-labs\.com#", $www)){return "updatesites";}
		if(preg_match("#fpl[0-9]+\-af\.avg\.com#", $www)){return "updatesites";}
		if(preg_match("#\.geo\.kaspersky\.com#", $www)){return "updatesites";}
		if(preg_match("#\.pack\.google\.com$#",$www)){return "updatesites";}
		if(preg_match("#\.icloud\.com$#",$www)){return "updatesites";}
		if(preg_match("#sync[0-9]+\.services\.mozilla\.com$#",$www)){return "updatesites";}
		if(preg_match("#^titanium.+?\.trendmicro.com$#",$www)){return "updatesites";}
		if(preg_match("#url\.trendmicro\.com$#",$www)){return "updatesites";}
		if(preg_match("#^ct-cache(.*?)\.f-secure\.[a-z]+$#",$www)){return "updatesites";}
		if(preg_match("#\.orsp\.f-secure\.[a-z]+$#",$www)){return "updatesites";}
		if(preg_match("#\.www3\.hp\.com$#",$www)){return "updatesites";}
		


		if(preg_match("#(makeup|parfume|perfume|maquillage|sephora|marionnaud|parfum|perfume|cosmetique|danieljouvance|cosmetic|yves-rocher|shampoo|shampoing)#",$www)){return "cosmetics";}
		if(preg_match("#\.nivea\.fr$#",$www)){return "cosmetics";}
		if(preg_match("#coeur-de-bio\.com#",$www)){return "cosmetics";}
		if(preg_match("#\.skinstore\.[a-z]+$#",$www)){return "cosmetics";}
		if(preg_match("#\.mugler\.[a-z]+$#",$www)){return "cosmetics";}
		if(preg_match("#^mugler\.[a-z]+$#",$www)){return "cosmetics";}
		if(preg_match("#\.(lvmh|watchstraps|louisvuitton|juliendorcel|histoiredor\.)\.[a-z]+$#",$www)){return "luxury";}
		if(preg_match("#^bijoux#",$www)){return "luxury";}
		if(preg_match("#-bijou\.[a-z]+$#",$www)){return "luxury";}
		if(preg_match("#montres-#",$www)){return "luxury";}
		if(preg_match("#montres\.#",$www)){return "luxury";}
		
		if(preg_match("#\.swatchgroup\.[a-z]+$#",$www)){return "luxury";}
		if(preg_match("#\.worldtempus\.[a-z]+$#",$www)){return "luxury";}
		if(preg_match("#\.jewell\.[a-z]+$#",$www)){return "luxury";}
		if(preg_match("#(bijouterie|juliendorcel|julien-dorcel|lvsacs|chronollection|vuittonsac)#",$www)){return "luxury";}
		
		
		if(preg_match("#handicap.*?\.#",$www)){return "handicap";}
		if(preg_match("#adapei-[0-9]+\.fr$#",$www)){return "handicap";}

		if(preg_match("#\.flip-zone\.com$#",$www)){return "models";}
		
		if(preg_match("#\.tinyurl\.[a-z]+$#",$www)){return "redirector";}
		if(preg_match("#\.myrlk\.com$#",$www)){return "redirector";}
		if(preg_match("#\.serving-sys\.com$#", $www)){return "malware";}
		if(preg_match("#\.vstatsr\.ru$#", $www)){return "malware";}
		if(preg_match("#\.moycd\.ru$#", $www)){return "malware";}
		if(preg_match("#\.xxuz\.com$#", $www)){return "malware";}
		if(preg_match("#\.xxxl-cash\.net$#", $www)){return "malware";}
		if(preg_match("#\.torntv\.com$#", $www)){return "malware";}
		if(preg_match("#\.spamfighter\.com$#", $www)){return "malware";}
		if(preg_match("#\.ourtoolbar\.com$#", $www)){return "malware";}
		if(preg_match("#\.validclick\.net$#", $www)){return "malware";}
		if(preg_match("#\.conduit\.[a-z]+$#",$www)){return "malware";}
		if(preg_match("#\.rkconnect\.com$#",$www)){return "malware";}
		if(preg_match("#\.getwebcake.\com$#",$www)){return "malware";}
		if(preg_match("#\.complitly\.[a-z]+$#",$www)){return "malware";}
		if(preg_match("#\.complitly[a-z]+search\.[a-z]+$#",$www)){return "malware";}
		if(preg_match("#\.complitlyjs\.[a-z]+$#",$www)){return "malware";}
		
		if(preg_match("#orange-business.com$#", $www)){return "isp";}
		if(preg_match("#\.infomaniak\.ch$#", $www)){return "isp";}
		if(preg_match("#\.freehostyou\.com$#",$www)){return "isp";}
		if(preg_match("#\.freeola\.com$#", $www)){return "isp";}
		if(preg_match("#\.freeola\.co\.uk$#", $www)){return "isp";}
		if(preg_match("#\.vfimg\.it$#", $www)){return "isp";}
		if(preg_match("#\.movistar\.com\.pe$#", $www)){return "isp";}
		if(preg_match("#\.attccc\.com$#", $www)){return "isp";}
		if(preg_match("#\.189\.cn$#", $www)){return "isp";}
		if(preg_match("#\.bell\.ca$#", $www)){return "isp";}
		if(preg_match("#\.upmania\.eu$#", $www)){return "isp";}
		if(preg_match("#\.x10\.mx$#", $www)){return "isp";}
		if(preg_match("#\.assistance-numericable\.fr$#", $www)){return "isp";}
		if(preg_match("#\.numericable\.fr$#", $www)){return "isp";}
		if(preg_match("#\.serverloft\.com$#", $www)){return "isp";}
		if(preg_match("#\.1and1\.[a-z\.]+$#", $www)){return "isp";}
		if(preg_match("#^1and1\.[a-z\.]+$#", $www)){return "isp";}
		
		if(preg_match("#^extranet\..+?$#", $www)){return "webapps";}
		if(preg_match("#\.ingenie\.fr$#", $www)){return "webapps";}
		if(preg_match("#\.dmvalet\.com$#", $www)){return "webapps";}
		if(preg_match("#\.office\.microsoft\.[a-z]+$#", $www)){return "webapps";}
		if(preg_match("#\.officeapps\.#", $www)){return "webapps";}
		if(preg_match("#\.force\.com$#", $www)){return "webapps";}
		if(preg_match("#\.infusionsoft\.com$#", $www)){return "webapps";}
		if(preg_match("#\.sgizmo\.com$#", $www)){return "webapps";}
		if(preg_match("#\.appspot\.com$#", $www)){return "webapps";}
		if(preg_match("#(google-analytics|symbaloo)\.#", $www)){return "webapps";}
		if(preg_match("#\.sharethis\.com$#", $www)){return "webapps";}
		if(preg_match("#\.questionmarket\.com$#", $www)){return "webapps";}
		if(preg_match("#\.brighttalk\.[a-z]+$#", $www)){return "webapps";}
		if(preg_match("#\.salesforce\.[a-z]+$#", $www)){return "webapps";}
		if(preg_match("#\.ixbus\.net+$#", $www)){return "webapps";}
		if(preg_match("#\.join\.me$#", $www)){return "webapps";}
		if(preg_match("#\.phx\.gbl$#", $www)){return "webapps";}
		if(preg_match("#\.viewnetcam\.[a-z]+$#", $www)){return "webapps";}
		if(preg_match("#\.webasyst\.[a-z]+$#", $www)){return "webapps";}
		if(preg_match("#\.37signals\.[a-z]+$#", $www)){return "webapps";}
		if(preg_match("#\.microsoftemail\.com$#", $www)){return "phishing";}
		if(preg_match("#\.vkomdente\.ru$#", $www)){return "phishing";}
		if(preg_match("#\.clickbook\.net$#", $www)){return "webapps";}

		
		if(preg_match("#\.wall-art\.com$#", $www)){return "housing/accessories";}
		if(preg_match("#^meubles-.*#", $www)){return "housing/accessories";}
		if(preg_match("#(literie|meubles|mobilier|jardiland|climatisation|decoraction)(\-|\.)#", $www)){return "housing/accessories";}
		if(preg_match("#\.fly\.fr$#", $www)){return "housing/accessories";}
		if(preg_match("#(mobilier-et-decoration|-culligan|kitchen|grillage|cloture|bathroom|jardiland|decoration|mobilier|abatjour|muebles|luminaire)#", $www)){return "housing/accessories";}
		if(preg_match("#\.cuisines-schmidt\.com$#", $www)){return "housing/accessories";}
		if(preg_match("#\.ikea\.[a-z]+$#", $www)){return "housing/accessories";}
		if(preg_match("#\.longmoney\.ru$#", $www)){return "housing/accessories";}
		
		
		$pat="(cuisine|cheminees|volet|volets|bougies|toitures)";
		
		if(preg_match("#^$pat-.+?\.#", $www)){return "housing/accessories";}
		if(preg_match("#-$pat-#", $www)){return "housing/accessories";}
		if(preg_match("#$pat\..+?#", $www)){return "housing/accessories";}
		if(preg_match("#-$pat\.#", $www)){return "housing/accessories";}
		if(preg_match("#\.$pat\.#", $www)){return "housing/accessories";}
		if(preg_match("#(leroymerlin|ideesmaison)\.[a-z]+#", $www)){return "housing/accessories";}

		if(preg_match("#\.lpo\.fr$#", $www)){return "animals";}
		if(preg_match("#\.ref-union\.org$#", $www)){return "hobby/other";}
		if(preg_match("#^tango-.*?\.[a-z]+$#", $www)){return "hobby/other";}
		if(preg_match("#kalligraphie#", $www)){return "hobby/other";}
		if(preg_match("#(salledebain|piscina|cadransolaire|pour-le-jardin|couverture|voletroulant|spotled|voletsroulant|toitures|isolation|poeleabois|carrelages|piscine|chauffage|plomberie|droguerie|luminaire)#", $www)){return "housing/accessories";}
		if(preg_match("#-jardin\.[a-z]+$#", $www)){return "housing/accessories";}
		
		
		if(preg_match("#\.sliderocket.com$#", $www)){return "webapps";}
		if(preg_match("#\.quora\.com$#", $www)){return "webapps";}
		if(preg_match("#\.hostedexchange\.fr$#", $www)){return "webapps";}
		if(preg_match("#\.officelive\.com$#", $www)){return "webapps";}
		if(preg_match("#\.salesforce\.com$#", $www)){return "webapps";}
		if(preg_match("#\.aspect\.com$#", $www)){return "webapps";}
		if(preg_match("#\.mescontenus\.orange\.fr$#", $www)){return "webapps";}
		if(preg_match("#\.netviewer\.com$#", $www)){return "webapps";}
		
		if(preg_match("#\.connect\.astaro\.com$#", $www)){return "webapps";}
		if(preg_match("#\.inetpsa\.com$#", $www)){return "webapps";}
		if(preg_match("#\.cpn\.vwg$#", $www)){return "webapps";}
		
		if(preg_match("#(ecurie|sellerie|marechalerie|cheveaux|equitation|equestre|horses|ecuries|horseware)#", $www)){return "horses";}
		if(preg_match("#^ecurie-#", $www)){return "horses";}
		if(preg_match("#\.racingpost\.com$#", $www)){return "horses";}
		if(preg_match("#-cheval\.[a-z\.]+$#", $www)){return "horses";}
		
		
		if(preg_match("#(radio|webradio)#", $www)){return "webradio";}
		if(preg_match("#\.azur-fm\.[a-z]+$#", $www)){return "webradio";}
		if(strpos($www, ".skyrock.net")>0){return "webradio";}
		if(preg_match("#\.radiobeta\.info$#", $www)){return "webradio";}
		if(preg_match("#\.rtl2\.fr$#", $www)){return "webradio";}
		if(preg_match("#\.slacker\.com$#", $www)){return "webradio";}
		if(preg_match("#\.pandora\.com$#", $www)){return "webradio";}
		if(preg_match("#\.2dayfm\.com\.au$#", $www)){return "webradio";}
		if(preg_match("#\.antenne\.de$#", $www)){return "webradio";}
		if(preg_match("#\.radiosuisseclassique\.ch$#", $www)){return "webradio";}
		if(preg_match("#\.radiocdn\.com$#", $www)){return "webradio";}
		if(preg_match("#\.cienradios\.com$#", $www)){return "webradio";}
		if(preg_match("#\.g-media\.com$#", $www)){return "webradio";}
		if(preg_match("#\.mediaworks\.co\.nz$#", $www)){return "webradio";}
		if(strpos($www, ".nrj.fr")>0){return "webradio";}  
		if(preg_match("#\.fm$#", $www)){return "webradio";}
		if(preg_match("#\.cienradios\.com\.ar$#", $www)){return "webradio";}
		if(preg_match("#\.radiotuna\.com$#", $www)){return "webradio";}
		if(preg_match("#\.streammonster\.com$#", $www)){return "webradio";}
		
		if(preg_match("#^[0-9]+job\.[a-z]+$#", $www)){return "jobsearch";}
		if(preg_match("#\.linkedin\.[a-z]+$#", $www)){return "jobsearch";}
		if(preg_match("#\.dajie\.com$#", $www)){return "jobsearch";}
		if(preg_match("#\.dajieimg\.com$#", $www)){return "jobsearch";}		
		if(preg_match("#\.newjobsmalaysia\.com$#", $www)){return "jobsearch";}
		if(preg_match("#\.estjob\.com$#", $www)){return "jobsearch";}
		if(preg_match("#\.cadremploi\.fr$#", $www)){return "jobsearch";}
		if(preg_match("#^jobs\..+#", $www)){return "jobsearch";}
		if(preg_match("#^emploi\..+#", $www)){return "jobsearch";}
		if(preg_match("#\.apec\.fr$#", $www)){return "jobsearch";}
		if(preg_match("#^job\..+#", $www)){return "jobsearch";}
		if(preg_match("#(jobsearch|offre-emploi|recruit|newjob|chercheunemploi|recruitment|recruiting|recruit\.)#", $www)){return "jobsearch";}
		if(preg_match("#\.jobsonline\.net$#", $www)){return "jobsearch";}
		if(preg_match("#\.viadeo\.com$#", $www)){return "jobsearch";}
		if(preg_match("#\.naukri\.com$#", $www)){return "jobsearch";}
		if(preg_match("#\.praca\.onet\.pl$#", $www)){return "jobsearch";}
		if(preg_match("#\.trabajando\.com$#", $www)){return "jobsearch";}
		if(preg_match("#\.monster\.[a-z]+$#", $www)){return "jobsearch";}	
		if(preg_match("#\.newjobs\.[a-z]+$#", $www)){return "jobsearch";}
		if(preg_match("#\.zonajobs\.[a-z\.]+$#", $www)){return "jobsearch";}
		if(preg_match("#\.profilsearch\.com$#", $www)){return "jobsearch";}
		if(preg_match("#recrute\.[a-z]+$#", $www)){return "jobsearch";}
		if(preg_match("#-interim\.#", $www)){return "jobsearch";}
		if(preg_match("#\.ilconte\.ru$#", $www)){return "jobsearch";}
		if(preg_match("#engineeer\.com$#", $www)){return "jobsearch";}
		
		if(preg_match("#-dessous\.[a-z]+$#", $www)){return "sex/lingerie";}
		if(preg_match("#-dessous-#", $www)){return "sex/lingerie";}
		if(preg_match("#^[0-9]+dessous#", $www)){return "sex/lingerie";}
		if(preg_match("#^(lesdessous|dessousde)#", $www)){return "sex/lingerie";}
		if(preg_match("#(charme|gay).*?(lingerie|dessous)#", $www)){return "sex/lingerie";}
		
		if(strpos($www, "collegehumor.com")>0){return "recreation/humor";}
		if(preg_match("#\.bu2z\.com$#",$www)){return "recreation/humor";}
		if(preg_match("#-funny\.$#",$www)){return "recreation/humor";}
		
		if(preg_match("#\.cariboom\.com$#",$www)){return "weapons";}
		if(preg_match("#pistoliers#",$www)){return "weapons";}
		
		if(preg_match("#\.punchbowl\.com$#",$www)){return "gifts";}
		if(preg_match("#\.wonderbox\.com$#",$www)){return "gifts";}
		if(preg_match("#\.americangreetings\.com$#",$www)){return "gifts";}
		if(preg_match("#\.cybercartes\.com$#",$www)){return "gifts";}
		if(preg_match("#\.aquarelle\.com$#",$www)){return "gifts";}
		if(preg_match("#\.aquarelle\.eu$#",$www)){return "gifts";}
		if(preg_match("#(surprises|-fleurs-)#",$www)){return "gifts";}
		if(preg_match("#amour.*?fleurs#",$www)){return "gifts";}

		if(preg_match("#\.bankrate\.com$#",$www)){return "finance/moneylending";}
		if(preg_match("#\.cofinoga\.[a-z]+$#",$www)){return "finance/moneylending";}
		if(preg_match("#\.cetelem\.[a-z]+$#",$www)){return "finance/moneylending";}
		if(preg_match("#\.rong360\.com+$#",$www)){return "finance/moneylending";}
		

		
		if(preg_match("#\.petitionsduweb\.fr$#",$www)){return "politic";}
		if(preg_match("#\.cgt\.fr$#",$www)){return "politic";}
		if(preg_match("#(socialist|referendum|petition|communist|democrats)#",$www)){return "politic";}
		if(preg_match("#(communisme|marxisme|ecologisme|feminisme|democratique|republicanisme|citoyen|syndicale|anticapitalisme|socialisme|enternationalisme|antilibéralisme|altermondialisme|republicanisme|leninisme|revolutionnaire|collectivisme|ecosocialisme|autogestion|progressisme|laicisme|humanisme)#",$www)){return "politic";}
		if(preg_match("#.*?-depute\.fr$#",$www)){return "politic";}
		if(preg_match("#^depute-.*?\.fr$#",$www)){return "politic";}
		
			
		if(preg_match("#(hack|crack|serialz).*?\.(blogspot|altervista).*?\.[a-z]+$#", $www)>0){return "hacking";}
		if(preg_match("#(soft|software).*?\.(blogspot|altervista).*?\.[a-z]+$#", $www)>0){return "hacking";}
		if(preg_match("#\.elhacker.net$#",$www)){return "hacking";}
		if(preg_match("#\.hackers\.[a-z\.]+$#",$www)){return "hacking";}
		if(preg_match("#^hack-.*?#",$www)){return "hacking";}
		if(preg_match("#(hacking|hacker|hackz|hackerz|crackz|serialz)#",$www)){return "hacking";}
		
		
		if(preg_match("#-financial\.#",$www)){return "financial";}
		if(preg_match("#(entrepreneurs|loi-duflot)#",$www)){return "financial";}
		if(preg_match("#\.ucm.be$#",$www)){return "financial";}
		if(preg_match("#\.forbes\.com$#",$www)){return "financial";}
		if(preg_match("#\.clickbank\.net$#",$www)){return "financial";}
		if(preg_match("#\.capitalone\.com$#",$www)){return "financial";}
		if(preg_match("#\.le-rsi\.fr$#",$www)){return "financial";}
		if(preg_match("#\.symexbelgium\.com$#",$www)){return "financial";}
		if(preg_match("#\.dailybusiness\.[a-z]+$#",$www)){return "financial";}
		if(preg_match("#\.finance\.yahoo.[a-z]+$#",$www)){return "financial";}
		if(preg_match("#\.bbvanet\.com\.mx+$#",$www)){return "financial";}
		if(preg_match("#\.hankyung\.com$#",$www)){return "financial";}
		if(preg_match("#\.hbr\.org$#",$www)){return "financial";}
		if(preg_match("#\.deloitte.[a-z\.]+$#",$www)){return "financial";}
		if(preg_match("#-fiscal\.#",$www)){return "financial";}
		
		
		if(preg_match("#\.ancestry\.com$#",$www)){return "genealogy";}
		if(preg_match("#\.geneawiki\.com$#",$www)){return "genealogy";}
		

				
		if(preg_match("#\.arkive\.org$#",$www)){return "nature";}
		if(preg_match("#\.wwf\.org\.uk$#",$www)){return "green";}
		if(preg_match("#\.durable\.com$#",$www)){return "green";}
		if(preg_match("#^crpf-(.*?)\.fr$#",$www)){return "green";}
		if(preg_match("#\.nationalgeographic\.[a-z]+$#",$www)){return "green";}
		if(preg_match("#\.cirad\.fr$#",$www)){return "green";}
		if(preg_match("#observatoire.*?eau#",$www)){return "green";}
		if(preg_match("#\.astee\.[a-z]+$#",$www)){return "green";}
		if(preg_match("#(environnement|ecologic|ecologie|ecology|espaces-verts)#",$www)){return "green";}
		
		
		if(preg_match("#^192\.168\.[0-9]+\.[0-9]+$#", $www)){return "internal";}
		if(preg_match("#^172\.[0-9]+\.[0-9]+\.[0-9]+$#", $www)){return "internal";}
		if(preg_match("#^172\.16\.[0-9]+\.[0-9]+$#", $www)){return "internal";}
		if(preg_match("#^127\.0\.[0-9]+\.[0-9]+$#", $www)){return "internal";}		
		if(preg_match("#^10\.[0-9]+\.[0-9]+\.[0-9]+$#", $www)){return "internal";}
		
		
		
		if(preg_match("#\.lan$#", $www)){return "internal";}
		if(preg_match("#\.local$#", $www)){return "internal";}
		if(preg_match("#\.intra$#", $www)){return "internal";}
		if(preg_match("#\.lab$#", $www)){return "internal";}
		if(preg_match("#\.(intranet|locale)$#", $www)){return "internal";}
		//if(preg_match("#^130\.117\.[0-9]+\.[0-9]+$#", $www)){return "internal";}
		

		if(preg_match("#\.justice\.fr$#", $www)){return "justice";}
		if(preg_match("#\.lemondedudroit\.fr$#", $www)){return "justice";}
		if(preg_match("#\.lgdj\.fr$#", $www)){return "justice";}
		if(preg_match("#^notaire-#", $www)){return "justice";}
		
		if(strpos($www, ".m6web.fr")>0){return "webtv";}
		if(preg_match("#\.plusbellelavie\.fr$#", $www)){return "webtv";}
		if(preg_match("#\.canal-plus\.com$#", $www)){return "webtv";}
		if(preg_match("#\.tf1\.fr$#", $www)){return "webtv";}
		if(preg_match("#\.chiens-de-france\.[a-z]+$#", $www)){return "hobby/pets";}
		if(preg_match("#(\.|\-)chiens(\.|\-)#", $www)){return "hobby/pets";}
		if(preg_match("#(labradors|toiletteur|malinois|animalerie|animali|yorkshire|royalcanin|shih-tzu|shihtzu|veterinaria|alynxstar|tiendanimal|monchien|heurtschia)#", $www)){return "hobby/pets";}
		if(preg_match("#pension.*?(anima|chien|chat)#", $www)){return "hobby/pets";}
		if(preg_match("#^pets-#", $www)){return "hobby/pets";}
		
		
		
		
		if(preg_match("#(tattooing|tatouage|tattoo|tatouer|piercing)#", $www)){return "tattooing";}
		if(preg_match("#\.bernardforever\.fr$#", $www)){return "tattooing";}

		if(preg_match("#ecoles48\.net$#", $www)){return "teens";}
		if(preg_match("#\.melty\.fr$#", $www)){return "teens";}
		if(preg_match("#\.mboa\.info$#", $www)){return "teens";}
		
		if(preg_match("#\.europeana\.eu$#", $www)){return "culture";}
		if(preg_match("#\.leschampslibres\.fr$#", $www)){return "culture";}
		if(preg_match("#\.chateauversailles\.fr$#", $www)){return "culture";}
		if(preg_match("#\.culture\.fr$#", $www)){return "culture";}
		if(preg_match("#^musees-(.*?)\.[a-z]+$#", $www)){return "culture";}
		if(preg_match("#museum#", $www)){return "culture";}
		
		
		if(preg_match("#\.healthcentral\.com$#", $www)){return "health";}
		if(preg_match("#\.lgds\.eu$#", $www)){return "health";}
		if(preg_match("#\.apigem\.org$#", $www)){return "health";}
		if(preg_match("#\.curie\.fr$#", $www)){return "health";}
		if(preg_match("#\.doctuo\.cl$#", $www)){return "health";}
		if(preg_match("#^optique-.*?\.(fr|com|lu)$#", $www)){return "health";}
		
		if(preg_match("#-soins-#", $www)){return "health";}
		if(preg_match("#\.katalog-stomatologu\.[a-z\.]+$#", $www)){return "health";}
		if(preg_match("#\.valtaf\.be$#", $www)){return "health";}
		if(preg_match("#(therapie|orthopedique|ophtalmo|orthopedie|-sante-|dermatologue|health|orthopaedie|varices|podolog|orthodontie|medecin|physiotherapie|psychiatrie|andrologue|arthrose|osteopath|neurologie|chirurgie|cardiologie|dermatology|opticien|dentiste|psychologue|antigymnastique|ophtalmologue)#", $www)){return "health";}
		//allemand
		if(preg_match("#(radiologische|psihoterapie|radioonkologie|medizinischen|hematologie|strahlentherapie)#", $www)){return "health";}
		
		if(preg_match("#-medical$#", $www)){return "medical";}
		if(preg_match("#medical-$#", $www)){return "medical";}
		if(preg_match("#\.ey99\.com$#", $www)){return "medical";}
		
		if(preg_match("#(farmacie|doktor|laboratoire-analyses|medicabiozone|pharmacie|ronflement|medical)#", $www)){return "medical";}
		if(preg_match("#ordinace.*?\.cz$#", $www)){return "medical";}
		
		if(preg_match("#\.inserm\.fr$#", $www)){return "sciences";}
		if(preg_match("#\.inria\.fr$#", $www)){return "sciences";}
		if(preg_match("#\.genethique\.[a-z]+$#", $www)){return "sciences";}
		if(preg_match("#\.ird\.fr$#", $www)){return "sciences";}
		if(preg_match("#\.cnrs\.fr$#", $www)){return "sciences";}
		if(preg_match("#\.inrialpes\.fr$#", $www)){return "sciences";}
		
		if(preg_match("#(wedding|mariage|marriage|getmarried|marions-les|married|noces|cremaillere|mariees|abcsalles)#", $www)){return "getmarried";}
		if(preg_match("#\.edressbridal\.com#", $www)){return "getmarried";}
		if(preg_match("#robe.*?mariee*#", $www)){return "getmarried";}
		
		
		if(preg_match("#(thermomix|vorwerk\.|robotkobold)#", $www)){return "electricalapps";}
		if(preg_match("#\.lg\.[a-z]+$#", $www)){return "electricalapps";}
		if(preg_match("#\.comhttp$#", $www)){return "reaffected";}
		if(preg_match("#\.cpom$#", $www)){return "reaffected";}
		
		
		if(preg_match("#\.forum\.#", $www)){return "forums";}
		if(strpos($www, "forum")>0){return "forums";}
		if(strpos($www, "sites.googlegroups.com")>0){return "forums";}
		if(preg_match("#^forum\.#", $www)){return "forums";}
		if(preg_match("#^forums\.#", $www)){return "forums";}
		if(preg_match("#answers\.yahoo\.com$#", $www)){return "forums";}
		if(preg_match("#\.kaskus\.us$#", $www)){return "forums";}
		if(preg_match("#\.ezboard\.com$#", $www)){return "forums";}
		if(preg_match("#\.yoo7\.com$#", $www)){return "forums";}
		
		if(preg_match("#revolvermaps\.com$#", $www)){return "webplugins";}
		if(preg_match("#\.googleapis\.com$#", $www)){return "webplugins";}
		if(preg_match("#\.geo[0-9]+\.com$#", $www)){return "webplugins";}
		if(preg_match("#\.wufoo\.com$#", $www)){return "webplugins";}
		if(preg_match("#\.psplugin\.com$#", $www)){return "webplugins";}
		
		
		if(preg_match("#\.centres-sociaux\.fr$#", $www)){return "humanitarian";}

		
		
		if(preg_match("#^wpad\.#", $www)){return "internal";}
		
		
		if(preg_match("#^newsletter\..+#", $www)){return "mailing";}
		if(preg_match("#purlsmail.com$#", $www)){return "mailing";}
		if(preg_match("#pompes.*funebres#", $www)){return "religion";}
		if(preg_match("#\.catholique\.fr$#", $www)){return "religion";}
		if(preg_match("#\.cef\.fr$#", $www)){return "religion";}
		if(preg_match("#funeral.*home#", $www)){return "religion";}
		if(preg_match("#\.netministry\.com$#", $www)){return "religion";}
		if(preg_match("#(obseque|pastorale|chretien|chretienne|musulman|coran|bible|catholic)#", $www)){return "religion";}
		if(preg_match("#\.mustafahosny\.com$#", $www)){return "religion";}
		if(preg_match("#church\.#", $www)){return "religion";}
		
		
		if(preg_match("#(allocine|filmtrailer)\.[a-z]+$#",$www)){return "movies";}
		if(preg_match("#\.logmein\.com$#",$www)){return "remote-control";}

		if(preg_match("#\.homestyler\.com$#",$www)){return "housing/doityourself";}
		if(preg_match("#(deco-travaux|bricolage|carrelage|-house-plan|ideesmaison|bricoleur)#",$www)){return "housing/doityourself";}
		
		
		if(preg_match("#(devis|deco).*?(travaux|interieur)#",$www)){return "housing/doityourself";}
		if(preg_match("#\.ath\.cx$#",$www)){return "dynamic";}
		if(preg_match("#\.synology\.me$#",$www)){return "dynamic";}
		if(preg_match("#\.dyndns-mail\.com$#",$www)){return "dynamic";}
		 if(preg_match("#\.dyndns\.org$#",$www)){return "dynamic";}


		
		if(preg_match("#\.electro-services[0-9]+\.[a-z]+$#", $www)){return "electricalapps";}

		if(preg_match("#\.vcst\.net$#", $www)){return "wine";}
		if(preg_match("#^wine-.*?\.[a-z\.]+$#", $www)){return "wine";}
		if(preg_match("#^vin-.*?\.[a-z\.]+$#", $www)){return "wine";}
		if(preg_match("#-wine-.*?\.[a-z\.]+$#", $www)){return "wine";}
		if(preg_match("#-vin-.*?\.[a-z\.]+$#", $www)){return "wine";}
		if(preg_match("#^(vinho|vignoble)#", $www)){return "wine";}
		if(preg_match("#vinhos\.#", $www)){return "wine";}
		
		if(preg_match("#\.merchantcircle\.com$#", $www)){return "financial";}
		if(preg_match("#\.moneycontrol\.+#", $www)){return "financial";}
		if(preg_match("#^finance\..+?$#", $www)){return "financial";}
		
		if(preg_match("#\.bharatmatrimony\.com$#", $www)){return "dating";}
		if(preg_match("#\.datingfactory\.com$#", $www)){return "dating";}
		if(strpos($www, ".babylon.com")>0){return "translators";}
		if(preg_match("#^translate.*\.#", $www)){return "translators";}
		if(preg_match("#\.babelfish\.yahoo.com$#", $www)){return "translators";}
		if(preg_match("#\.zaixian-fanyi\.[a-z\.]+$#", $www)){return "translators";}
		if(preg_match("#\.nciku\.com$#", $www)){return "translators";}
		if(preg_match("#\.woxikon\.de$#", $www)){return "translators";}
		if(preg_match("#\.proz\.com$#", $www)){return "translators";}
		
		if(preg_match("#^traduction-#", $www)){return "translators";}
		if(preg_match("#^translate\.google\.[a-z]+$#", $www)){return "translators";}
		if(preg_match("#\.scorecardresearch\.com$#", $www)){return "marketingware";}
		if(preg_match("#\.email-match\.com$#", $www)){return "marketingware";}
		if(preg_match("#\.lb-clients\.com$#", $www)){return "marketingware";}
		if(preg_match("#\.dolead\.com$#", $www)){return "marketingware";}
		if(preg_match("#\.hubspot\.com$#", $www)){return "marketingware";}
		


	

		if(preg_match("#\.w3snoop\.com$#", $www)){return "webplugins";}
		if(preg_match("#\.rssinclude\.com$#", $www)){return "webplugins";}
		if(preg_match("#\.plebius\.net$#", $www)){return "webplugins";}
		
		
		
		
		if(preg_match("#\.mobijoy\.com$#", $www)){return "ringtones";}
		if(preg_match("#\.lawyers\.com$#", $www)){return "justice";}
		if(preg_match("#ordre-avocats-.*?.com$#", $www)){return "justice";}
		

		
		if(preg_match("#\.svp\.com$#", $www)){return "dictionaries";}
		if(preg_match("#\.horoscope\.com$#", $www)){return "dictionaries";}
		if(preg_match("#\.comprendrechoisir\.com$#", $www)){return "dictionaries";}
		if(preg_match("#\.cityvoter\.com$#", $www)){return "recreation/nightout";}
		if(preg_match("#\.citysearch\.com$#", $www)){return "recreation/nightout";}
		if(preg_match("#\.onvasortir\.com$#", $www)){return "recreation/nightout";}
		if(preg_match("#\.today\.tod\.ua$#", $www)){return "recreation/nightout";}
		if(preg_match("#\.soonnight\.com$#", $www)){return "recreation/nightout";}
		if(preg_match("#\.tonight\.eu$#", $www)){return "recreation/nightout";}
		if(preg_match("#\.eventful\.com$#", $www)){return "recreation/nightout";}
		if(preg_match("#-restaurant\.[a-z\.]+$#", $www)){return "recreation/nightout";}
		if(preg_match("#\.sortir\.ville\.orange\.fr$#", $www)){return "recreation/nightout";}
		if(preg_match("#^(sortira|pizza)#", $www)){return "recreation/nightout";}
		if(preg_match("#(lecafe|ristorante|discotheque|billetterie)#", $www)){return "recreation/nightout";}
		if(preg_match("#sortir\.#", $www)){return "recreation/nightout";}
		if(preg_match("#^restaurant#", $www)){return "recreation/nightout";}
		if(preg_match("#-resto\.(fr|com|ch|be)$#", $www)){return "recreation/nightout";}
		if(preg_match("#^(resto|festivals|concerts|fnacspectacles)-#", $www)){return "recreation/nightout";}
		
		
		if(preg_match("#^impots-.*?#", $www)){return "finance/other";}
		if(preg_match("#^impot-.*?#", $www)){return "finance/other";}
		if(preg_match("#-impots\.[a-z]+#", $www)){return "finance/other";}
		if(preg_match("#-impot\.[a-z]+#", $www)){return "finance/other";}
		if(preg_match("#reduction.*?impot#", $www)){return "finance/other";}
		if(preg_match("#\.dispofi\.[a-z]+#", $www)){return "finance/other";}
		
		
		if(preg_match("#\.prositehosting\.co\.uk$#", $www)){return "isp";}
		if(preg_match("#\.bouyguestelecom\.fr$#", $www)){return "isp";}
		if(preg_match("#\.onlinehome-server\.info$#", $www)){return "isp";}
		if(preg_match("#\.cdnplanet\.com$#", $www)){return "isp";}
		if(preg_match("#\.cdn77\.net$#", $www)){return "isp";}
		
		if(preg_match("#(clinique|clinica|hospital|hopital|hopitaux|polyclinique|maisonderetraite|clinic)#", $www)){return "hospitals";}
		if(preg_match("#ap-hop-paris\.fr$#", $www)){return "hospitals";}
		if(preg_match("#^clinique([-\.]+).+?\.$#", $www)){return "hospitals";}
		if(preg_match("#^chu-.+?\.fr$#", $www)){return "hospitals";}
		if(preg_match("#^chi-.+?\.fr$#", $www)){return "hospitals";}
		if(preg_match("#^ch-.+?\.fr$#", $www)){return "hospitals";}
		if(preg_match("#^chi[0-9]+\.fr$#", $www)){return "hospitals";}
		if(preg_match("#\.cgos\.info$#", $www)){return "hospitals";}
		if(preg_match("#^dolcea-maison-#", $www)){return "hospitals";}
		

		
		if(preg_match("#(aviation|aeroclub|avionique|aeronaut|aircraft)#", $www)){return "automobile/planes";}
		if(preg_match("#covoiturage#", $www)){return "automobile/carpool";}
		
		
		if(preg_match("#\.picnik\.com$#", $www)){return "photo";}
		if(preg_match("#\.fotoflexer\.com$#", $www)){return "photo";}
		if(preg_match("#\.photobox\.[a-z\.]+$#", $www)){return "photo";}
		if(preg_match("#\.img-dpreview\.com$#", $www)){return "photo";}
		if(preg_match("#\.mypix\.com$#", $www)){return "photo";}
		if(preg_match("#\.bhphotovideo\.com$#", $www)){return "photo";}
		if(preg_match("#\.rapidstudio\.co\.za$#", $www)){return "photo";}
		if(preg_match("#\.flamingtext\.com$#", $www)){return "photo";}
		if(preg_match("#-photography-#$#", $www)){return "photo";}
		if(preg_match("#photography\.[a-z\.]+$#", $www)){return "photo";}
		if(preg_match("#photography-#", $www)){return "photo";}
		if(preg_match("#(photographe|fotografie|fujifilm|photographier|photobook|photographic)#", $www)){return "photo";}
		
		
		if(preg_match("#\.ryland\.com$#", $www)){return "housing/builders";}
		if(preg_match("#-batiment\.fr$#", $www)){return "housing/builders";}
		if(preg_match("#\.newlandcommunities\.com$#", $www)){return "housing/builders";}
		if(preg_match("#house.*?build#", $www)){return "housing/builders";}
		if(preg_match("#constructeur.*?maison#", $www)){return "housing/builders";}
		if(preg_match("#(maison|hous|wood).*?(construction|construccion)#", $www)){return "housing/builders";}
		if(preg_match("#(terrassement|baticourtage|construction|construccion)#", $www)){return "housing/builders";}
		
		if(preg_match("#^toolbar\..+#", $www)){return "browsersplugins";}
		if(preg_match("#\.toolbar\.yahoo\.[a-z]+$#", $www)){return "browsersplugins";}
		if(preg_match("#\.widdit\.[a-z]+$#", $www)){return "browsersplugins";}
		

		
		if(preg_match("#^maps\.google\..+?$#", $www)){return "maps";}
		if(preg_match("#^mt[0-9]+\.google\.[a-z]+$#", $www)){return "maps";}
		if(preg_match("#^cbk[0-9]+\.google\.[a-z]+$#", $www)){return "maps";}
		if(preg_match("#\.ovi\.com$#", $www)){return "maps";}
		if(preg_match("#\.nearmap\.[a-z]+$#", $www)){return "maps";}
		if(preg_match("#\.mappy\.[a-z]+#", $www)){return "maps";}

		
		if(preg_match("#\.modlife\.com$#", $www)){return "celebrity";}
		if(preg_match("#\.closermag\.[a-z]+$#", $www)){return "celebrity";}
		if(preg_match("#\.celebritywise\.com$#", $www)){return "celebrity";}
		
		if(preg_match("#constructeur-de-maisons#", $www)){return "housing/builders";}

		
		if(preg_match("#\.51\.la$#", $www)){return "spyware";}
		if(preg_match("#^toplist\.sk$#", $www)){return "spyware";}
		if(preg_match("#\.toplist\.sk$#", $www)){return "spyware";}
		if(preg_match("#wibiya\.[a-z]+$#", $www)){return "spyware";}
		if(preg_match("#\.maxonline\.com\.sg$#", $www)){return "spyware";}
		if(preg_match("#\.conduit-data\.com$#", $www)){return "spyware";}
		if(preg_match("#(paroisse|religious|religion|funeraire)#", $www)){return "religion";}
		if(preg_match("#\.meshfriend\.com$#", $www)){return "reaffected";}
		if(preg_match("#^bible\.[a-z]+$#", $www)){return "reaffected";}
		
		

		
		
		if(preg_match("#garage.*?floor#", $www)){return "housing/accessories";}
		if(preg_match("#^garage-.*?\.(fr|com|ca|be)$#", $www)){return "automobile/cars";}
		
		
		if(preg_match("#\.motoblouz\.com$#", $www)){return "automobile/bikes";}
		if(preg_match("#harley([\-|\.])davidson#", $www)){return "automobile/bikes";}
		if(preg_match("#\.321moto\.com$#", $www)){return "automobile/bikes";}
		if(preg_match("#^honda\.[a-z\.]+$#", $www)){return "automobile/bikes";}
		if(preg_match("#\.honda\.[a-z\.]+$#", $www)){return "automobile/bikes";}
		if(preg_match("#\.[0-9](moto|auto)\.[a-z]+$#", $www)){return "automobile/bikes";}
		if(preg_match("#\.harley-davidson\.com$#", $www)){return "automobile/bikes";}
		if(preg_match("#\.mobcustom\.[a-z]+$#", $www)){return "automobile/bikes";}
		if(preg_match("#(motospiece|honda|bikes|scooter|suzuki|piecesmoto|permis-moto|-moto-|motostore)#", $www)){return "automobile/bikes";}
		
		if(preg_match("#(alcool|alcohol)##", $www)){return "alcohol";}
		if(preg_match("#\.persona\.org$#", $www)){return "browsersplugins";}
		if(preg_match("#eleicoes.*?\.[a-z]+#", $www)){return "politic";}
		
		if(preg_match("#^garage-.*?\.fr$#", $www)){return "automobile/cars";}
		if(preg_match("#^brasserie-#",$www)){return "recreation/nightout";}
		if(preg_match("#\.myticket\.[a-z]+#", $www)){return "recreation/nightout";}
		
		
		
		
		
		if(preg_match("#(cooking|recette|marmiton|cuisiner|receitas|culinaire|gastronomie|gastronomy|gastronome)#", $www)){return "hobby/cooking";}
		if(preg_match("#^recettes-.*?\.fr$#", $www)){return "hobby/cooking";}
		if(preg_match("#\.cuisineblog\.fr$#", $www)){return "hobby/cooking";}
		if(preg_match("#\.meltyfood\.fr$#", $www)){return "hobby/cooking";}
		if(preg_match("#\.rezeptwiese\.[a-z]+$#", $www)){return "hobby/cooking";}
		if(preg_match("#\.atelierdeschefs\.[a-z]+$#", $www)){return "hobby/cooking";}
		if(preg_match("#\.cuisineaz\.com$#", $www)){return "hobby/cooking";}
		
		
		if(preg_match("#(astronomy|astronomie|astronome|telescope|cosmologie|astrophoto)#", $www)){return "science/astronomy";}
		if(preg_match("#\.fis[0-9]+\.info$#", $www)){return "suspicious";}
		if(preg_match("#blue[0-9]+xyz#", $www)){return "suspicious";}
		if(preg_match("#vuittonlouis#", $www)){return "suspicious";}
		if(preg_match("#^[0-9]+\.info$#", $www)){return "suspicious";}
		if(preg_match("#\.st\.mycdn\.me$#", $www)){return "suspicious";}
		if(preg_match("#\.sysinfonet\.cc$#", $www)){return "suspicious";}
		if(preg_match("#\.zrdt6-[0-9]+\.zv$#", $www)){return "suspicious";}
		if(preg_match("#\.beautifulwallpapers\.info$#", $www)){return "suspicious";}
		if(preg_match("#\.kddflk\.com$#", $www)){return "suspicious";}
		if(preg_match("#\.mix[0-9]+\.com$#", $www)){return "suspicious";}
		if(preg_match("#\.reg-check\.com$#", $www)){return "suspicious";}
		if(preg_match("#\.ekind-xml\.com$#", $www)){return "suspicious";}
		if(preg_match("#\.made2u\.biz$#", $www)){return "suspicious";}
		if(preg_match("#\.keukenstickers.nl$#", $www)){return "suspicious";}
		if(preg_match("#\.istats\.su$#", $www)){return "malware";}
		if(preg_match("#\.wguards\.cc$#", $www)){return "malware";}
		if(preg_match("#\.iguards\.su$#", $www)){return "malware";}
		if(preg_match("#\.eguards\.su$#", $www)){return "malware";}
		if(preg_match("#\.[a-z]+guards\.su$#", $www)){return "malware";}
		if(preg_match("#\.hiprotections\.cc$#", $www)){return "malware";}
		if(preg_match("#protections.cc$#", $www)){return "malware";}
		if(preg_match("#protections\.su$#", $www)){return "malware";}
		if(preg_match("#\.zrdt6-null\.zv#", $www)){return "reaffected";}
		if(preg_match("#\.w-lo(t|r)o\.com$#", $www)){return "suspicious";}
		if(preg_match("#\.yupiromo\.ru$#", $www)){return "suspicious";}
		if(preg_match("#\.gs[0-9]+\.info$#", $www)){return "suspicious";}
		if(preg_match("#ccfxxs\.com#", $www)){return "suspicious";}
		if(preg_match("#(chemistry|chimie|chimica)#", $www)){return "science/chemistry";}
		if(preg_match("#(polizei|gendarmerie)#", $www)){return "police";}
		
		if(preg_match("#(books|livres|gibertjeune|ecrivain|bibliotecas)#", $www)){return "books";}
		if(preg_match("#^bibliotheques\.#", $www)){return "books";}
		if(preg_match("#^revue-#", $www)){return "books";}
		if(preg_match("#-book\.[a-z\.]+#", $www)){return "books";}
		if(preg_match("#^edition-.*?\.(com|fr|de)#", $www)){return "books";}
		
		
		if(preg_match("#\.voirdessinanime\.com$#", $www)){return "children";}
		if(preg_match("#(enfant|children|toysrus|enfance)#", $www)){return "children";}
		if(preg_match("#\.ptitclic\.net$#", $www)){return "children";}
		if(preg_match("#faire.*?part.*?naissance#", $www)){return "children";}
		
		
		if(preg_match("#(coloriage|coloriages).*?\.[a-z]+$#", $www)){return "children";}
		if(preg_match("#\.cargocollective\.com$#", $www)){return "hobby/arts";}
		if(preg_match("#-art\.[a-z]+$#", $www)){return "hobby/arts";}
		if(preg_match("#\.book\.fr$#", $www)){return "hobby/arts";}
		if(preg_match("#(artisticas|cineast)#", $www)){return "hobby/arts";}
		if(preg_match("#clovisgauzy\.fr$#", $www)){return "hobby/arts";}
		if(preg_match("#^formation-.*?#", $www)){return "learning";}
		if(preg_match("#(-|\.)formation\.#", $www)){return "learning";}
		if(preg_match("#(-|\.)formation-[a-z0-9]+\.#", $www)){return "learning";}
		if(preg_match("#formation\.[a-z]+$#", $www)){return "learning";}
		if(preg_match("#^formation[a-z]+\.[a-z]+$#", $www)){return "learning";}
		if(preg_match("#formation.*?(continue|altern)#", $www)){return "learning";}
		if(preg_match("#\.itysoft\.com$#", $www)){return "learning";}
		

		if(preg_match("#mountainbike#", $www)){return "bicycle";}
		
		if(preg_match("#^peche-#", $www)){return "hobby/fishing";}
		if(preg_match("#-peche.*#", $www)){return "hobby/fishing";}
		if(preg_match("#pecherie#", $www)){return "hobby/fishing";}
		if(preg_match("#-nautic\.#", $www)){return "automobile/boats";}
		if(preg_match("#fleetmon\.com#", $www)){return "automobile/boats";}
		if(preg_match("#nautic-#", $www)){return "automobile/boats";}
		if(preg_match("#^restaurant-#",$www)){return "recreation/nightout";}
		if(preg_match("#finance-#",$www)){return "financial";}
		if(preg_match("#chemical#",$www)){return "science/chemistry";}
		if(preg_match("#programming#",$www)){return "science/computing";}
		

		
	}
}