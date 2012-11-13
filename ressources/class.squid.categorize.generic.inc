<?php
class generic_categorize{
	//http://adblockrules.org/download.php?type=all
	// http://winhelp2002.mvps.org/hosts.txt
	//http://www.oxymium.net/policyrss.php?type=sender-domain
	
	private function Blog($www){
		if(preg_match("#^blogsearch.*#", $www)){return "blog";}
		if(preg_match("#^blog\..+?#", $www)){return "blog";}
		if(preg_match("#blog\.[a-z]+$#", $www)){return "blog";}
		if(preg_match("#\.canalblog\.com$#", $www)){return "blog";}
		if(preg_match("#\.blogia\.com$#", $www)){return "blog";}
		if(preg_match("#\.canalblog\.com$#", $www)){return "blog";}
		if(strpos($www, ".wordpress.com")>0){return "blog";}
		if(strpos($www, ".freewebspace.com")>0){return "blog";}
		if(strpos($www, ".hubpages.com")>0){return "blog";}
		if(strpos($www, ".e-monsite.com")>0){return "blog";}
		if(preg_match("#\.yuku\.com$#", $www)){return "blog";}
		if(preg_match("#\.tumblr\.com$#", $www)){return "blog";}
		if(preg_match("#\.over-blog\.[a-z]+$#",$www)>0){return "blog";}
		if(strpos($www, ".ucoz.ru")>0){return "blog";}
		if(strpos($www, ".punt.nl")>0){return "blog";}
		if(strpos($www, ".grygorczuk.pl")>0){return "blog";}
		if(preg_match("#\.skyblog\.com$#", $www)){return "blog";}
		if(preg_match("#\.freedomblogging\.com$#", $www)){return "blog";}
		if(preg_match("#\.bloguez\.com$#", $www)){return "blog";}
		if(preg_match("#\.pagesperso-orange\.fr$#", $www)){return "blog";}
		if(preg_match("#\.areavoices\.com$#", $www)){return "blog";}
		if(preg_match("#\.blogspot\.[a-z]+$#", $www)){return "blog";}
		if(preg_match("#\.blogs\.liberation\.fr$#", $www)){return "blog";}
		if(preg_match("#\.blogs\.nouvelobs\.com$#", $www)){return "blog";}
		if(preg_match("#\.100webspace\.net$#", $www)){return "blog";}
		if(preg_match("#\.blogspot\.com\.[a-z]+$#", $www)){return "blog";}
		if(preg_match("#\.blog\.lemonde\.[a-z]+$#", $www)){return "blog";}
		if(preg_match("#\.typepad\.com$#", $www)){return "blog";}
		if(preg_match("#\.monsite-orange\.[a-z]+$#", $www)){return "blog";}
		if(preg_match("#\.blogspirit\.com$#", $www)){return "blog";}
		if(preg_match("#\.blogspace\.fr$#", $www)){return "blog";}
		if(preg_match("#\.livejournal\.com$#", $www)){return "blog";}
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
		
	}
	
	private function Tracker($www){
		include_once(dirname(__FILE__)."/class.squid.categorize.tracker.php");
		$track=new categorize_tracker();
		$val=$track->Tracker($www);
		if($val<>null){return $val;}
		if(preg_match("#\.xiti\.com$#", $www)){return "tracker";}
		if(preg_match("#\.resultspage\.com$#", $www)){return "tracker";}
		if(preg_match("#\.memecounter\.com$#", $www)){return "tracker";}
		if(preg_match("#\.raster-xml\.info$#", $www)){return "tracker";}
		if(preg_match("#\.quantcount\.com$#", $www)){return "tracker";}
	}
	
	private function Filehosting($www){
		if(preg_match("#\.image.*?\.edgesuite\.net$#",$www)){return "imagehosting";}
		if(strpos($www, ".cloudfront.net")>0){return "filehosting";}
		if(strpos($www, ".edgesuite.net")>0){return "filehosting";}
		if(strpos($www, ".amazonaws.com")>0){return "filehosting";}		
		if(strpos($www, "vo.msecnd.net")>0){return "filehosting";}	
		if(strpos($www, ".smcloud.net")>0){return "filehosting";}
		if(strpos($www, ".mediafire.com")>0){return "filehosting";}
		if(strpos($www, ".akamai.net")>0){return "filehosting";}
		if(preg_match("#mobstor\..*?\.yahoo\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#4shared\.[a-z]+$#",$www)){return "filehosting";}
		if(preg_match("#\.dropbox\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.docs\.google\.[a-z]+$#", $www)){return "filehosting";}
		if(preg_match("#\.drive\.google\.[a-z]+$#", $www)){return "filehosting";}		
		if(preg_match("#\.ftpperso\.free\.fr$#",$www)){return "filehosting";}
		if(preg_match("#\.yousendit\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.scene7\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.cloudfiles\.rackspacecloud\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.static69\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.cachefly\.net$#",$www)){return "filehosting";}
		if(preg_match("#^files\.static[0-9]+\.com$#",$www)){return "filehosting";}
		if(preg_match("#noop-q\.cddb2\.cddbp\.net$#",$www)){return "filehosting";}
		if(preg_match("#\.media\.tumblr\.com$#",$www)){return "filehosting";}
		if(preg_match("#\.depositfiles\.com$#",$www)){return "filehosting";}
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
	}
	
	
	private function imagehosting($www){
		if(strpos($www, ".photobucket.com")>0){return "imagehosting";}
		if(strpos($www, ".servimg.com")>0){return "imagehosting";}
		if(strpos($www, ".edgefcs.net")>0){return "imagehosting";} 
		if(strpos($www, "iofferphoto.com")>0){return "imagehosting";}	
		if(strpos($www, ".rackcdn.com")>0){return "imagehosting";}	
		if(strpos($www, ".t26.net")>0){return "imagehosting";}
		if(strpos($www, ".tinypic.com")>0){return "imagehosting";}
		if(strpos($www, ".yimg.com")>0){return "imagehosting";}
		if(strpos($www, ".imageshack.")>0){return "imagehosting";}
		if(strpos($www, ".turboimagehost.com")>0){return "imagehosting";}
		if(strpos($www, ".imagevenue.com")>0){return "imagehosting";}
		if(preg_match("#fsimg\.ru$#",$www)){return "imagehosting";}
		if(preg_match("#xooimage.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.ytimg.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.chomikuj.pl$#",$www)){return "imagehosting";}
		if(preg_match("#\.ggpht.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.akamai\.net$#",$www)){return "imagehosting";}
		if(preg_match("#\.upload69\.net$#",$www)){return "imagehosting";}
		if(preg_match("#\.image.*?\.edgesuite\.net$#",$www)){return "imagehosting";}
		if(preg_match("#\.slide\.com$#",$www)){return "imagehosting";}
		if(preg_match("#\.edgecastcdn.net$#",$www)){return "imagehosting";}
		if(preg_match("#\.pic\.centerblog\.net$#",$www)){return "imagehosting";}
		if(preg_match("#\.googleusercontent\.com$#",$www)){return "imagehosting";}
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
		
	}
	
	private function audiovideo($www){
		if(strpos($www, ".googlevideo.com")>0){return "audio-video";}
		if(strpos($www, ".youtube.com")>0){return "audio-video";}
		if(strpos($www, ".atmcdn.pl")>0){return "audio-video";}
		if(preg_match("#^video\..+?#",$www)>0){return "audio-video";}
		if(preg_match("#gamaniak\.com$#",$www)>0){return "audio-video";}
		if(preg_match("#\.video\.msn\.com$#",$www)>0){return "audio-video";}
		if(preg_match("#\.castup\.net$#",$www)>0){return "audio-video";}
		if(preg_match("#\.rocktube\.us$#",$www)>0){return "audio-video";}
		if(preg_match("#\.firststreaming.com$#",$www)>0){return "audio-video";}
		if(preg_match("#\.video.[a-z]-msn\.com$#",$www)>0){return "audio-video";}
		if(preg_match("#\.videoegg\.com$#",$www)>0){return "audio-video";}
		if(preg_match("#\.lkimg.com$#",$www)>0){return "audio-video";}
		if(preg_match("#^video[0-9]+\.#",$www)>0){return "audio-video";}
		if(preg_match("#\.dailymotion\.com$#",$www)>0){return "audio-video";}
		if(preg_match("#\.trilulilu\.ro$#",$www)>0){return "audio-video";}
		if(preg_match("#\.video-loader\.com$#",$www)>0){return "audio-video";}
		if(preg_match("#\.videoplaza\.tv$#",$www)>0){return "audio-video";}
		if(preg_match("#^video[0-9\-]+\.rutube\.ru$#", $www)){return "audio-video";}
		if(preg_match("#\.pop-music-dvds\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.media.*?.cddb[0-9]+\.cddbp.[a-z]+$#", $www)){return "audio-video";}
		if(preg_match("#\.video\.ckcdn\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.windowsmedia\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.vimeocdn\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.freemake\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.tare\.ro$#", $www)){return "audio-video";}
		if(preg_match("#\.livestream\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.video\.yahoo\.[a-z]+$#", $www)){return "audio-video";}
		if(preg_match("#\.video\.mynet\.com$#", $www)){return "audio-video";}
		if(preg_match("#^video.+?\.pagesjaunes\.[a-z]$#", $www)){return "audio-video";}
		if(preg_match("#^video-.*?\.pagesjaunes\.[a-z]$#", $www)){return "audio-video";}
		if(preg_match("#^video-[a-z][0-9]+\.pagesjaunes\.fr$#", $www)){return "audio-video";}
		if(preg_match("#\.nowvideo\.eu$#", $www)){return "audio-video";}
		if(preg_match("#\.vimeo\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.dropshots\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.allmyvideos\.net$#", $www)){return "audio-video";}
		if(preg_match("#\.pcvideo\.com\.cn$#", $www)){return "audio-video";}
		if(preg_match("#\.loadup\.ru$#", $www)){return "audio-video";}
		if(preg_match("#video.*?\.akamaihd\.net$#", $www)){return "audio-video";}
		if(preg_match("#\.videobam\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.popscreencdn\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.ultimedia\.com$#", $www)){return "audio-video";}
		if(preg_match("#\.videohub\.com$#", $www)){return "audio-video";}
	}
	
	
	
	
	private function Books($www){	
		if(strpos($www, "books.google.")>0){return "books";}
		if(preg_match("#^books\.#", $www)){return "books";}
		if(preg_match("#elsevier\.com$#", $www)){return "books";}
		if(preg_match("#\.booksite\.com$#", $www)){return "books";}
		if(preg_match("#\.vonibo\.com$#", $www)){return "books";}
		if(preg_match("#\.eyrolles\.[a-z]+$#", $www)){return "books";}
		if(preg_match("#^librairie\.#", $www)){return "books";}
		if(preg_match("#\.cyberpresse\.[a-z]+$#", $www)){return "books";}
		if(preg_match("#\.berghahnbooks\.com+$#", $www)){return "books";} 
		if(preg_match("#\.yoolib\.[a-z]+$#", $www)){return "books";} 
		if(preg_match("#\.wikibooks\.org$#", $www)){return "books";} 
	}
	
	private function Children($www){
		if(strpos($www, "maternelle")>0){return "children";}
		if(preg_match("#\.treehousetv\.com$#",$www)){return "children";}
		if(preg_match("#\.clubpenguin\.com$#",$www)){return "children";}
		
	}
	
	private function MobilePhone($www){
		if(preg_match("#\.globile\.co$#",$www)){return "mobile-phone";}
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
		if(preg_match("#mobilephone#",$www)){return "mobile-phone";} 
		if(preg_match("#\.blackberry\.[a-z]+$#",$www)){return "mobile-phone";}
		if(preg_match("#\.android\.[a-z]+$#",$www)){return "mobile-phone";} 
		if(preg_match("#^gsm[0-9]+\.[a-z]+$#",$www)){return "mobile-phone";}
		if(preg_match("#\.sonymobile\.[a-z]+$#",$www)){return "mobile-phone";}
	}
	
	Private function humanitarian($www){
		if(strpos($www, ".croix-rouge.fr")>0){return "humanitarian";}
		if(preg_match("#\.donnerenligne\.fr$#", $www)){return "humanitarian";}
	}
	
	private function schools($www){
		include_once(dirname(__FILE__)."/class.squid.catz.schools.inc");
		$pub=new squid_catz_schools();$cat=$pub->parse($www);if($cat<>null){return "recreation/schools";}
		if(preg_match("#\.state\.oh\.us$#", $www)){return "recreation/schools";}
		if(preg_match("#\.tu-harburg\.de$#", $www)){return "recreation/schools";}
		if(preg_match("#\.sch\.uk$#", $www)){return "recreation/schools";}
		
	}
	
	private function sports($www){
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
		if(preg_match("#football#", $www)){return "recreation/sports";}
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
	}
	
	private function shopping($www){
		include_once(dirname(__FILE__)."/class.squid.catz.shopping.inc");
		$pub=new squid_catz_shopping();$cat=$pub->parse($www);if($cat<>null){return "shopping";}		
		if(preg_match("#\.blizko\.ru#",$www)){return "shopping";}
		if(preg_match("#\.focalprice\.[a-z]+$#",$www)){return "shopping";}
		
	}
	private function pictureslib($www){
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
	}
	
	private function governments($www){
		if(preg_match("#\.justice\.gouv\.fr$#", $www)){return "justice";}
		if(preg_match("#\.tribunal-administratif\.[a-z]+$#", $www)){return "justice";}
		if(preg_match("#\.gouv\.fr$#", $www)){return "governments";}
		if(preg_match("#\.gov$#", $www)){return "governments";}
		if(preg_match("#\.gov\.cn$#", $www)){return "governments";}
		if(preg_match("#gouv\.qc\.ca$#", $www)){return "governments";}	
		if(preg_match("#\.gov\.gi$#", $www)){return "governments";}
		if(preg_match("#\.gov\.uk$#", $www)){return "governments";}
		if(preg_match("#\.gov\.pt$#", $www)){return "governments";}		
		if(preg_match("#\.gov\.au$#", $www)){return "governments";}
		if(preg_match("#\.gov\.my$#", $www)){return "governments";}
		if(preg_match("#\.gov\.br$#", $www)){return "governments";}		
		if(preg_match("#\.govt\.nz$#", $www)){return "governments";}	
		if(preg_match("#\.gouv\.[a-z]+$#", $www)){return "governments";}	
		if(preg_match("#^ville-.*?\.fr$#", $www)){return "governments";}	
		if(preg_match("#mairie\-.+?\.#", $www)){return "governments";}	
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
		if(preg_match("#^sde[0-9]+\.fr$#", $www)){return "governments";}	
		
	}
	
	private function searchengines($www){
		if(strpos($www, ".l.google.com")>0){return "searchengines";}
		if(strpos($www, "ipv6.yahoo.com")>0){return "searchengines";}	
		if(strpos($www, ".b.yahoo.com")>0){return "searchengines";}	
		if(preg_match("#^client([0-9]+)\.google\.([a-z\.]+)$#", $www)){return "searchengines";}	
		if(preg_match("#^google\.([a-z\.]+)$#", $www)){return "searchengines";}	
		if(preg_match("#^clients[0-9]+\.google\.([a-z\.]+)$#", $www)){return "searchengines";}
		if(preg_match("#\.yandex\.net$#", $www)){return "searchengines";} 
		if(preg_match("#\.yandex\.ru$#", $www)){return "searchengines";}
		if(preg_match("#\.repertoire-web\.com$#", $www)){return "searchengines";}	
		if(preg_match("#\.9msn\.com\.au$#", $www)){return "searchengines";}	
		if(preg_match("#\.web\.de$#", $www)){return "searchengines";}	
		if(preg_match("#\.webmarchand\.com$#", $www)){return "searchengines";}	
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
		
	}
	
	private function dictionaries($www){
		if(preg_match("#about\.com$#",$www)){return "dictionaries";}
		if(preg_match("#\.wikipedia\.org$#",$www)){return "dictionaries";}
		if(preg_match("#\.wikia.com$#",$www)){return "dictionaries";}
		if(preg_match("#\.docstoc\.com$#",$www)){return "dictionaries";}
		if(preg_match("#\.wikidot\.com$#",$www)){return "dictionaries";}
		if(preg_match("#\.wikispaces\.com$#",$www)){return "dictionaries";}
		if(preg_match("#\.wikimedia\.org$#",$www)){return "dictionaries";}
		if(preg_match("#\.wikiquote\.org$#",$www)){return "dictionaries";}
		if(preg_match("#\.wikisource\.org$#",$www)){return "dictionaries";}
		if(preg_match("#\.yourdictionary\.[a-z]+$#",$www)){return "dictionaries";}
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
		
	}
	
	private function webtv($www){
		if(preg_match("#blip\.tv$#",$www)){return "webtv";}
		if(preg_match("#\.ustream\.tv$#",$www)){return "webtv";}
		if(preg_match("#\.zap2it\.com$#",$www)){return "webtv";}
		if(preg_match("#\.tv\.yahoo\.[a-z]+$#",$www)){return "webtv";}
		if(preg_match("#\.tv\.search\.[a-z]+$#",$www)){return "webtv";}
		if(preg_match("#\.rtl\.lu$#",$www)){return "webtv";}
		if(preg_match("#^tv.+?\.orange\.[a-z]+$#",$www)){return "webtv";}
		if(preg_match("#\.arte\.tv$#",$www)){return "webtv";}
		if(preg_match("#\.tvrage\.com$#",$www)){return "webtv";}
		if(preg_match("#\.tv2\.dk$#",$www)){return "webtv";}
		if(preg_match("#\.canalplus\.fr$#",$www)){return "webtv";}
		if(preg_match("#\.diziizle\.net$#",$www)){return "webtv";}
		if(preg_match("#\.mtv\.[a-z]+$#",$www)){return "webtv";}
		if(preg_match("#\.wat\.[a-z]+$#",$www)){return "webtv";}
		if(preg_match("#\.mtv\.com$#", $www)){return "webtv";}
		if(preg_match("#\.bfmtv\.com$#", $www)){return "webtv";}
		if(preg_match("#\.france2\.fr$#", $www)){return "webtv";}
		if(preg_match("#\.france3\.fr$#", $www)){return "webtv";}
		if(preg_match("#\.francetelevisions\.tv$#", $www)){return "webtv";}
		if(preg_match("#\.20min-tv\.ch$#", $www)){return "webtv";}
		if(preg_match("#\.crystal-tv\.[a-z]+$#", $www)){return "webtv";}
		if(preg_match("#\.programme-tv\.net#",$www)){return "webtv";}
		if(preg_match("#\.france3\.fr$#", $www)){return "webtv";}
		if(preg_match("#\.natgeotv\.com$#", $www)){return "webtv";}
		if(preg_match("#freetv#", $www)){return "webtv";}		
		
	}
	
private function socialnet($www){
		if(preg_match("#\.pipl\.com$#", $www)){return "socialnet";}
		if(preg_match("#^facebook\.[a-z]+$#", $www)){return "socialnet";}
		if(preg_match("#\.facebook\.[a-z]+$#", $www)){return "socialnet";}
		if(strpos($www, ".smeet.com")>0){return "socialnet";}
		if(strpos($www, "hostmonarchy.com")>0){return "socialnet";}	
		if(strpos($www, ".facebook.")>0){return "socialnet";}
		if(strpos($www, ".disqus.com")>0){return "socialnet";}
		if(strpos($www, "opensocial.googleusercontent.com")>0){return "socialnet";}
		if(strpos($www, ".zankyou.com")>0){return "socialnet";}
		if(strpos($www, ".thestudentroom.co.uk")>0){return "socialnet";}
		if(preg_match("#\.fbcdn\.net$#", $www)){return "socialnet";}
		if(preg_match("#^[a-z0-9]+tagstat\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.pinterest\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.p4fb\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.myspacecdn\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.cmcdn\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.social\.[a-z]-msn\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.mylife\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.twimg\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.badoocdn\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.opensocial\.googleusercontent\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.licdn\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.poco\.cn$#", $www)){return "socialnet";}
		if(preg_match("#\.perfspot\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.pixnet\.net$#", $www)){return "socialnet";}
		if(preg_match("#\.tagstat\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.social\.msn\.[a-z]+$#", $www)){return "socialnet";}
		if(preg_match("#\.vk\.com$#", $www)){return "socialnet";}
		if(preg_match("#\.social\.microsoft\.[a-z]+$#", $www)){return "socialnet";}
		if(preg_match("#\.odnoklassniki\.ru$#", $www)){return "socialnet";}
		if(preg_match("#\.vkontakte\.ru$#", $www)){return "socialnet";}
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
	}	
	
	private function publicite($www){
		include_once(dirname(__FILE__)."/class.squid.catz.publicite.inc");
		$pub=new squid_catz_publicite();$cat=$pub->parse($www);if($cat<>null){return "publicite";}
		if(preg_match("#\.adsmogo\.net$#", $www)){return "publicite";}
		if(preg_match("#\.ic-live\.com$#", $www)){return "publicite";}
		if(preg_match("#\.gestionpub\.com$#", $www)){return "publicite";}
		if(preg_match("#\.bidvertiser\.[a-z]+$#", $www)){return "publicite";}
		if(preg_match("#\.banners-service\.[a-z]+$#", $www)){return "publicite";}
		if(preg_match("#\.easypush\.eu$#", $www)){return "publicite";}
		if(preg_match("#\.ovoadv\.[a-z]+$#", $www)){return "publicite";}
	}
	
	private function ScienceComputing($www){
		if(preg_match("#([\.|-])photoshop([-|\.])#", $www)){return "science/computing";}
		if(strpos($www, ".googlecode.com")>0){return "science/computing";}
		if(strpos($www, ".sourceforge.net")>0){return "science/computing";}
		if(strpos($www, ".opendns.com")>0){return "science/computing";}
		if(strpos($www, ".symantec.com")>0){return "science/computing";}
		if(strpos($www, ".nabble.com")>0){return "science/computing";}		
		if(preg_match("#\.hp\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.citrix\.com$#", $www)){return "science/computing";}
		if(strpos($www, ".ubuntu.")>0){return "science/computing";}
		if(preg_match("#\.opendns\.com#", $www)){return "science/computing";}
		if(preg_match("#\.addthis\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.clubic\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.dell\.[a-z]+$#", $www)){return "science/computing";}
		if(preg_match("#\.us\.archive\.org$#", $www)){return "science/computing";}
		if(preg_match("#\.help\.outlook\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.jouve\.fr$#", $www)){return "science/computing";} 
		if(preg_match("#\.spiceworks\.com$#", $www)){return "science/computing";} 
		if(preg_match("#\.codes-sources\.[a-z]+$#", $www)){return "science/computing";} 
		if(preg_match("#\.ldlc\.com$#", $www)){return "science/computing";} 
		if(preg_match("#\.nas-central\.org$#", $www)){return "science/computing";} 
		if(preg_match("#\.pc120\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.ibm\.[a-z]+$#", $www)){return "science/computing";}  
		if(preg_match("#\.digitalpoint\.com$#", $www)){return "science/computing";}  
		if(preg_match("#\.blob\.core\.windows\.net$#", $www)){return "science/computing";}  
		if(preg_match("#\.windows\.microsoft\.com$#", $www)){return "science/computing";}  
		if(preg_match("#\.userapi\.com$#", $www)){return "science/computing";}  
		if(preg_match("#\.parallels\.com$#", $www)){return "science/computing";}  
		if(preg_match("#[a-z]+\.sourceforge\.[a-z]+$#", $www)){return "science/computing";} 
		if(preg_match("#\.canon-europe\.com$#", $www)){return "science/computing";} 
		if(preg_match("#\.as41690\.net$#", $www)){return "science/computing";} 
		if(preg_match("#\.bigair\.net\.au$#", $www)){return "science/computing";}
		if(preg_match("#\.acapela-group\.com$#", $www)){return "science/computing";} 
		if(preg_match("#\.ie\.microsoft\.com$#", $www)){return "science/computing";}
		if(preg_match("#informatique#", $www)){return "science/computing";} 
		if(preg_match("#\.technet\.microsoft\.[a-z]+#", $www)){return "science/computing";} 
		if(preg_match("#\.answers\.microsoft\.[a-z]+#", $www)){return "science/computing";}
		if(preg_match("#\.github\.com$#", $www)){return "science/computing";} 
		if(preg_match("#\.fullproduct\.download\.microsoft\.[a-z]+#", $www)){return "science/computing";} 
		if(preg_match("#(hack|crack|serialz).*?\.(blogspot|altervista).*?\.[a-z]+$#", $www)>0){return "hacking";}
		if(preg_match("#(soft|software).*?\.(blogspot|altervista).*?\.[a-z]+$#", $www)>0){return "hacking";}
		if(preg_match("#\.ebp\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.hp\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.intel\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.verytech\.it$#", $www)){return "science/computing";}
		if(preg_match("#\.isleofwightspeedtes\.[a-z]+$#", $www)){return "science/computing";}
		
		
		
	}
	
	private function music($www){
		
		if(strpos($www, ".universalmusic.")>0){return "music";}
		if(strpos($www, ".sonymusic.")>0){return "music";}
		if(strpos($www, "hulkshare.com")>0){return "music";}
		if(strpos($www, "projectplaylist.com")>0){return "music";}
		if(strpos($www, ".pitchfork.com")>0){return "music";}
		if(strpos($www, ".kazaa.com")>0){return "music";}
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
		if(preg_match("#\.sheetmusicengine\.com$#", $www)){return "music";}
		if(preg_match("#\.scorser\.com$#", $www)){return "music";}
		if(preg_match("#\.musicblog\.fr$#", $www)){return "music";}
		if(preg_match("#\.midomi\.com$#", $www)){return "music";}
		if(preg_match("#\.noomiz\.com$#", $www)){return "music";}
		if(preg_match("#\.zimbalam\.com$#", $www)){return "music";}
		if(preg_match("#\.believe\.fr$#", $www)){return "music";}
		if(preg_match("#musique#", $www)){return "music";}
	}
	
	private function maps($www){
		if(preg_match("#\.tomtom\.com$#", $www)){return "maps";}
		if(preg_match("#\.usnaviguide\.com$#", $www)){return "maps";}
		if(preg_match("#\.garmin\.com$#", $www)){return "maps";}
		if(preg_match("#\.virtualearth\.net$#", $www)){return "maps";}
		if(preg_match("#\.city-nav\.eu$#", $www)){return "maps";}
		if(preg_match("#maps\.yahoo\.com$#", $www)){return "maps";}
		if(preg_match("#\.viamichelin\.[a-z]+$#", $www)){return "maps";}
		if(preg_match("#\.map.ymap.[a-z\.]+$#", $www)){return "maps";}
		if(preg_match("#\.map24\.com$#", $www)){return "maps";}
		if(preg_match("#\.viva-images\.com$#", $www)){return "maps";}
		if(preg_match("#\.vivastreet\.com$#", $www)){return "maps";}
		if(preg_match("#\.streetdirectory.com$#", $www)){return "maps";}
		if(preg_match("#\.urbandive\.net$#", $www)){return "maps";}
		if(preg_match("#\.viewsurf\.com$#", $www)){return "maps";}
		if(preg_match("#\.1bis\.com$#", $www)){return "maps";}
		if(preg_match("#\.map\.baidu\.com$#", $www)){return "maps";}
		if(preg_match("#\.mapswithme\.com$#", $www)){return "maps";}
		if(preg_match("#\.mapquest\.com$#", $www)){return "maps";}
		if(preg_match("#\.mapservice\.co\.za$#", $www)){return "maps";}
		if(preg_match("#\.mapit\.co\.za$#", $www)){return "maps";}
		if(preg_match("#\.lewatmana\.com$#", $www)){return "maps";}
		if(preg_match("#\.wikimapia\.org$#", $www)){return "maps";}
		if(preg_match("#\.opencyclemap\.org$#", $www)){return "maps";}
	}
	
	private function webmail($www){
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
		if(preg_match("#\.member\..+?\.yahoo.[a-z]+$#",$www)){return "webmail";}
		if(preg_match("#\.login.vip\..+?\.yahoo.[a-z]+$#",$www)){return "webmail";}
		if(preg_match("#\.mail\.ru$#",$www)){return "webmail";}
		if(preg_match("#\.my\.msn\.com$#",$www)){return "webmail";}
		if(preg_match("#^webmail[a-z0-9]\.#",$www)){return "webmail";}
	}
	
	private function chat($www){
		if(preg_match("#livechatinc\.[a-z]+$#", $www)){return "chat";}
		if(preg_match("#\.envolve.com$#", $www)){return "chat";}
		if(preg_match("#\.messenger\.live\.[a-z]+$#",$www)){return "chat";}
		if(preg_match("#\.chatango.com$#",$www)){return "chat";}
		if(preg_match("#\.activengage\.com$#",$www)){return "chat";}
		if(preg_match("#\.msg\.yahoo\.com$#",$www)){return "chat";}
		if(preg_match("#\.liveperson\.net$#",$www)){return "chat";}
		if(preg_match("#async\.olark\.com$#",$www)){return "chat";}
		if(preg_match("#\.bowobcloud[0-9]+\.com$#",$www)){return "chat";}
		if(preg_match("#^chatserver\.#",$www)){return "chat";}
		if(preg_match("#\.chat[0-9]+\.ningim\.com$#",$www)){return "chat";}
		if(preg_match("#\.freeshoutbox\.[a-z]+$#",$www)){return "chat";}
		if(preg_match("#\.way2sms\.com$#",$www)){return "chat";}
		if(preg_match("#\.ltalk\.ru$#",$www)){return "chat";}
		if(preg_match("#\.chat\.yahoo\.[a-z]+$#",$www)){return "chat";}
		if(preg_match("#\.coco\.fr$#",$www)){return "chat";}
		if(preg_match("#\.kakao\.co\.kr$#",$www)){return "chat";}
		if(preg_match("#\.iadvize\.com$#",$www)){return "chat";}
		

	}	
	
	private function porn($www){
		include_once(dirname(__FILE__)."/class.squid.catz.porn.inc");
		$pub=new squid_catz_porn();$cat=$pub->parse($www);if($cat<>null){return "porn";}		
		if(preg_match("#\.utrehter\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.smutxx\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.nastymovs\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.hardsextube\.com$#", $www)>0){return "porn";}
		if(preg_match("#^sexyblog\.*$#", $www)>0){return "porn";}
		if(preg_match("#\.doublepimp\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.mesvip\.com$#", $www)>0){return "porn";}
	}
	
	private function movies($www){
		if(strpos($www, ".cellcastmedia.com")>0){return "movies";}
		if(strpos($www, ".streamtheworld.com")>0){return "movies";}
		if(preg_match("#\.megavod\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.studiocanal\.com$#", $www)){return "movies";}
		if(preg_match("#\.tvsubtitles\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.openfilm\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.supergoodmovies\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.movies\.yahoo\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.kinepolis\.com$#", $www)){return "movies";}	
		if(preg_match("#(movie|film|cinema|streaming).*?\.(blogspot|altervista).*?\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#^.+?-lefilm\.com$#", $www)){return "movies";}
		if(preg_match("#\.netflix\.com$#", $www)){return "movies";}
		if(preg_match("#\.*?starmovies\.[a-z]+$#", $www)){return "movies";}
		
		
	}
	
	
	private function games($www){	
		include_once(dirname(__FILE__)."/class.squid.catz.games.inc");
		$pub=new squid_catz_games();$cat=$pub->parse($www);if($cat<>null){return "games";}	
		if(preg_match("#\.majorleaguegaming\.com$#", $www)){return "games";}
		if(preg_match("#\.gamefaqs\.com$#", $www)){return "games";}
		if(preg_match("#\.playme[0-9]+\.[a-z]+$#",$www)){return "games";}
	}
	
	private function automobile($www){
		if(strpos($www, ".otomoto.pl")>0){return "automobile/cars";}
		if(preg_match("#\.automotive\.com$#",$www)){return "automobile/cars";}
		if(preg_match("#voiture#",$www)){return "automobile/cars";}
		if(preg_match("#automobile#",$www)){return "automobile/cars";}
		if(preg_match("#peugeot#",$www)){return "automobile/cars";}
		if(preg_match("#ebizautos\.com$#",$www)){return "automobile/cars";}
		if(preg_match("#stemco\.com$#",$www)){return "automobile/cars";}
		if(preg_match("#\.texasdirectauto\.com$#",$www)){return "automobile/cars";}
		if(preg_match("#auto\.sanook\.com$#",$www)){return "automobile/cars";}
		if(preg_match("#\.mercedes-benz\.[a-z]+$#",$www)){return "automobile/cars";}
		if(preg_match("#\.webmotors\.com\.br$#",$www)){return "automobile/cars";}
		
	}
	
	private function mailing($www){
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
	}
	
	private function sslsites($www){
		
		if(preg_match("#\.verisign\.com$#",$www)){return "sslsites";}
		if(preg_match("#\.geotrust\.com$#",$www)){return "sslsites";}
		if(preg_match("#\.digicert\.com$#", $www)){return "sslsites";}
		if(preg_match("#\.entrust\.net$#", $www)){return "sslsites";}
		if(preg_match("#\.startssl\.com$#", $www)){return "sslsites";}
	}
	
	private function gamble($www){
		
		if(preg_match("#\.check4d\.com$#",$www)){return "gamble";}
		if(preg_match("#\.pmu\.fr$#",$www)){return "gamble";}
		if(preg_match("#(casino|bet(ting|s)|lott(ery|o)|gam(e[rs]|ing|bl(e|ing))|sweepstake|poker).*?\.(blogspot|altervista).*?\.[a-z]+$#", $www)>0){return "gamble";}
	}
	
	private function downloads_catz($www){
		if(strpos($www, ".rapidshare.com")>0){return "downloads";}
		if(preg_match("#\.rapidgator\.[a-z]+$#",$www)>0){return "downloads";}
		if(preg_match("#\.softonic\.[a-z]+$#",$www)>0){return "downloads";}
		if(preg_match("#\.sftcdn\.net$#", $www)){return "downloads";}
		if(preg_match("#\.torrentino\.[a-z]+$#", $www)){return "downloads";}
		if(preg_match("#\.yourfiledownloader\.com$#", $www)){return "downloads";}
		if(preg_match("#\.rutracker\.org$#", $www)){return "downloads";}
		if(preg_match("#\.alphacoders\.com$#", $www)){return "downloads";}
		if(preg_match("#\.altools\.com$#", $www)){return "downloads";}	
		if(preg_match("#\.utorrent\.com$#", $www)){return "downloads";}	
		if(preg_match("#\.bittorrent\.com$#", $www)){return "downloads";}	
	}
	
	private function travel($www){
		if(strpos($www, "voyages")>0){return "recreation/travel";}
		if(preg_match("#^travel\..*?$#", $www)){return "recreation/travel";}
		if(preg_match("#\.chatel\.com$#", $www)){return "recreation/travel";}
		if(strpos($www, "trivago.com")>0){return "recreation/travel";}
		if(preg_match("#\.voyage\.#", $www)){return "recreation/travel";}
		if(preg_match("#^hotel-[a-z]+#", $www)){return "recreation/travel";}
		if(preg_match("#^hotels-[a-z\.]+#", $www)){return "recreation/travel";}
		if(preg_match("#^hotel[a-z]+\.[a-z]+$#", $www)){return "recreation/travel";}
		if(preg_match("#\.isango\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#\.aeroport\.fr$#", $www)){return "recreation/travel";}
		if(preg_match("#\.hotels\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#^voyage.+?#", $www)){return "recreation/travel";}
		if(preg_match("#^vols\..+?#", $www)){return "recreation/travel";}
		if(preg_match("#^camping[-\.].+?\..+?$#", $www)){return "recreation/travel";}
		if(preg_match("#\.ihg\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#^hotel[a-z]+.+?\.[a-z]+$#", $www)){return "recreation/travel";}
		if(preg_match("#\.makemytrip\.com+$#", $www)){return "recreation/travel";}	
		if(preg_match("#^kyriad-(.+?)\.fr$#", $www)){return "recreation/travel";}	
		if(preg_match("#\.opodo\.fr$#", $www)){return "recreation/travel";}
		if(preg_match("#\.cityway\.fr$#", $www)){return "recreation/travel";}	
		if(preg_match("#\.airfrance\.[a-z]+$#", $www)){return "recreation/travel";}	
		if(preg_match("#\.hoteles\.[a-z]+$#", $www)){return "recreation/travel";}
		if(preg_match("#\.gho\.fr$#", $www)){return "recreation/travel";}
		if(preg_match("#\.ihacom\.co\.uk$#", $www)){return "recreation/travel";}
		if(preg_match("#(\.|\-)tourisme\.#", $www)){return "recreation/travel";}
		if(preg_match("#\.l-et-v\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#^gites(\-|\.)#", $www)){return "recreation/travel";}
		if(preg_match("#\.urbita\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#\.turistforeningen\.[a-z]+$#", $www)){return "recreation/travel";}
		if(preg_match("#\.lvpingphoto\.com$#",$www)){return "recreation/travel";}
		if(preg_match("#\.partirpascher\.com$#", $www)){return "recreation/travel";}
	}
	
	
	function news($www){
		if(preg_match("#\.news\.com\.au$#",$www)){return "news";}
		if(preg_match("#\.3news\.co\.nz$#",$www)){return "news";}
		if(preg_match("#\.journaldunet\.com$#",$www)){return "news";}
		if(preg_match("#\.linternaute\.com$#",$www)){return "news";}
		if(preg_match("#\.feedsportal\.com$#",$www)){return "news";}
		if(preg_match("#\.townnews\.com$#",$www)){return "news";}
		if(preg_match("#\.cnn\.com$#",$www)){return "news";}
		if(preg_match("#\.newser\.com$#",$www)){return "news";}
		if(preg_match("#\.newsinc\.com$#",$www)){return "news";}
		if(preg_match("#\.wsj\.net$#",$www)){return "news";}
		if(preg_match("#\.news\.com$#",$www)){return "news";}
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
		if(preg_match("#(watch|pap|cast|herald|sentinel|courier|gazet|tribune|chronicle|daily|news).*?\.(blogspot|altervista).*?\.[a-z]+$#", $www)>0){return "news";}
	}
	
	private function smallads($www){
		
		if(preg_match("#\.traderonline\.com$#", $www)){return "smallads";}
		if(preg_match("#\.videdressing.com$#", $www)){return "smallads";}
		if(preg_match("#\.clubannonces.com$#", $www)){return "smallads";}
		if(preg_match("#\.sulekha\.com$#", $www)){return "smallads";}
		if(preg_match("#\.tweedehands\.nl$#", $www)){return "smallads";}
		if(preg_match("#\.lespac\.com$#", $www)){return "smallads";}
		if(preg_match("#\.olx\.[a-z]+$#", $www)){return "smallads";}
		if(preg_match("#\.olx\.[a-z]+\.[a-z]+$#", $www)){return "smallads";}
		if(preg_match("#craigslist\.org$#", $www)){return "smallads";}	
		if(preg_match("#\.pap\.fr$#", $www)){return "smallads";}
		if(preg_match("#\.craigslist\.fr$#", $www)){return "smallads";}
		if(preg_match("#\.[a-z]+-annonces\.[a-z]+$#", $www)){return "smallads";}	
		if(preg_match("#\.hyperannonces\.fr$#", $www)){return "smallads";}	
		if(preg_match("#\.slando\.ru$#", $www)){return "smallads";}
		if(preg_match("#\.gratka\.pl$#", $www)){return "smallads";}
		if(preg_match("#\.freepropertyads\.[a-z\.]+$#", $www)){return "smallads";}	
		if(preg_match("#\.alamaulastatic\.com$#", $www)){return "smallads";}
		if(preg_match("#\.olx-st\.com$#", $www)){return "smallads";}
		if(preg_match("#\.olx\.[a-z]+$#", $www)){return "smallads";}
	}
	
	private function realestate($www){
		if(preg_match("#\.century21\.fr$#", $www)){return "finance/realestate";}
		if(preg_match("#\.poliris\.com$#", $www)){return "finance/realestate";}
		if(preg_match("#^sahlm[0-9]+\.fr$#", $www)){return "finance/realestate";}
		if(preg_match("#^laforet-immobilier-.*?\.[a-z]+#", $www)){return "finance/realestate";}
		if(preg_match("#\.kyero\.com$#", $www)){return "finance/realestate";}
		if(preg_match("#\.cybelagence\.net#", $www)){return "finance/realestate";}
		if(strpos($www, "realestate")){return "finance/realestate";}
		if(preg_match("#immobilier#", $www)){return "finance/realestate";}
		if(preg_match("#^realestate\.#", $www)){return "finance/realestate";}
		if(preg_match("#habitat[0-9]+\.fr$#", $www)){return "finance/realestate";}		
	}
	
	private function banking($www){
		if(preg_match("#\.pncbank\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.citibank\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.bornpay\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.credit-agricole\.fr$#",$www)){return "finance/banking";}
		if(preg_match("#\.banquepopulaire\.fr$#",$www)){return "finance/banking";}
		if(preg_match("#\.societegenerale\.fr$#",$www)){return "finance/banking";}
		if(preg_match("#\.labanquepostale\.fr$#",$www)){return "finance/banking";}
		if(preg_match("#\.isabel\.be$#",$www)){return "finance/banking";}
		if(preg_match("#\.americanexpress\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.cbc\.be$#",$www)){return "finance/banking";}	
		if(preg_match("#\.vend-o\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.natixis\.[a-z]+$#",$www)){return "finance/banking";}
		if(preg_match("#\.bil\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.paypal\.[a-z]+$#",$www)){return "finance/banking";}
		if(preg_match("#\.paypal\.[a-z]+\.[a-z]+$#",$www)){return "finance/banking";}
		
	}
	
	function GetCategories($www){
		$cat=null;
		$cat=$this->sslsites($www);if($cat<>null){return $cat;}
		$cat=$this->publicite($www);if($cat<>null){return $cat;}
		$cat=$this->porn($www);if($cat<>null){return $cat;}
		$cat=$this->mailing($www);if($cat<>null){return $cat;}
		$cat=$this->chat($www);if($cat<>null){return $cat;}
		$cat=$this->games($www);if($cat<>null){return $cat;}
		$cat=$this->gamble($www);if($cat<>null){return $cat;}
		$cat=$this->movies($www);if($cat<>null){return $cat;}
		
		$cat=$this->Blog($www);if($cat<>null){return $cat;}
		$cat=$this->news($www);if($cat<>null){return $cat;}
		$cat=$this->travel($www);if($cat<>null){return $cat;}
		$cat=$this->sports($www);if($cat<>null){return $cat;}
		$cat=$this->webmail($www);if($cat<>null){return $cat;}
		$cat=$this->maps($www);if($cat<>null){return $cat;}
		$cat=$this->webtv($www);if($cat<>null){return $cat;}
		$cat=$this->dictionaries($www);if($cat<>null){return $cat;}
		$cat=$this->ScienceComputing($www);if($cat<>null){return $cat;}
		$cat=$this->music($www);if($cat<>null){return $cat;}
		$cat=$this->automobile($www);if($cat<>null){return $cat;}
		
		$cat=$this->Filehosting($www);if($cat<>null){return $cat;}
		$cat=$this->imagehosting($www);if($cat<>null){return $cat;}
		
		$cat=$this->downloads_catz($www);if($cat<>null){return $cat;}
		$cat=$this->realestate($www);if($cat<>null){return $cat;}
		$cat=$this->banking($www);if($cat<>null){return $cat;}
		$cat=$this->smallads($www);if($cat<>null){return $cat;}
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
		$cat=$this->shopping($www);if($cat<>null){return $cat;}
		
		if(preg_match("#\.dyndns-home\.com#", $www)){return "dynamic";}
		if(preg_match("#^avocat[\-\.]#", $www)){return "justice";}
		if(preg_match("#^avocats-.+?\..+?$#", $www)){return "justice";}
		if(preg_match("#-avocat\.[a-z]+$#", $www)){return "justice";}
		if(preg_match("#^huissier(\.|\-)#", $www)){return "justice";}
		
		if(preg_match("#geologie#", $www)){return "sciences";}
		if(preg_match("#sciences#", $www)){return "sciences";}
		
		if(preg_match("#\.asso\.fr$#", $www)){return "associations";}
		if(preg_match("#^associations\..*?\.[a-z]$#", $www)){return "associations";}
		if(preg_match("#\.asso-web\.com$#", $www)){return "associations";}
		if(preg_match("#-association\.[a-z]$#", $www)){return "associations";}
		
		if(preg_match("#\.justjared\.com$#", $www)){return "celebrity";}
		if(preg_match("#\.bunte.de$#", $www)){return "celebrity";}
		if(preg_match("#\.cg[0-9]+\.fr$#", $www)){return "governments";}
		
		if(preg_match("#\.swagbucks\.com$#", $www)){return "paytosurf";}
		if(preg_match("#\.voyance\.avigora\.com$#", $www)){return "astrology";}
		if(preg_match("#\.mangareader\.net$#", $www)){return "manga";}
		
		if(preg_match("#\.vodeclic\.[a-z]+$#", $www)){return "learning";}
		if(preg_match("#\.sprachcaffe\.com$#", $www)){return "learning";}
		if(preg_match("#\.telelangue\.com$#", $www)){return "learning";}
		if(preg_match("#\.afpa\.fr$#", $www)){return "learning";}
		if(strpos($www, "phobos.apple.com")>0){return "updatesites";}
		if(preg_match("#\.cloud\.avg\.com#",$www)>0){return "updatesites";}
		
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
		
		if(preg_match("#cosmetics#",$www)){return "cosmetics";}
		if(preg_match("#cosmetic#",$www)){return "cosmetics";}
		if(preg_match("#cosmetiques#",$www)){return "cosmetics";}
		if(preg_match("#cosmetique#",$www)){return "cosmetics";}
		if(preg_match("#makeup#",$www)){return "cosmetics";}
		if(preg_match("#\.skinstore\.[a-z]+$#",$www)){return "cosmetics";}
		
		if(preg_match("#handicap.*?\.#",$www)){return "handicap";}

		
		if(preg_match("#\.tinyurl\.[a-z]+#",$www)){return "redirector";}
		if(preg_match("#\.serving-sys\.com$#", $www)){return "malware";}
		if(preg_match("#\.moycd\.ru$#", $www)){return "malware";}
		
		if(preg_match("#orange-business.com$#", $www)){return "isp";}
		if(preg_match("#\.freehostyou\.com$#",$www)){return "isp";}
		if(preg_match("#\.freeola\.com$#", $www)){return "isp";}
		if(preg_match("#\.freeola\.co\.uk$#", $www)){return "isp";}
		if(preg_match("#\.vfimg\.it$#", $www)){return "isp";}
		if(preg_match("#\.movistar\.com\.pe$#", $www)){return "isp";}
		if(preg_match("#\.attccc\.com$#", $www)){return "isp";}
		if(preg_match("#\.189\.cn$#", $www)){return "isp";}
		if(preg_match("#\.bell\.ca$#", $www)){return "isp";}
		
		if(preg_match("#^extranet\..+?$#", $www)){return "webapps";}
		if(preg_match("#\.ingenie\.fr$#", $www)){return "webapps";}
		if(preg_match("#\.dmvalet\.com$#", $www)){return "webapps";}
		if(preg_match("#\.office\.microsoft\.[a-z]+$#", $www)){return "webapps";}
		if(preg_match("#\.microsoftemail\.com$#", $www)){return "phishing";}
		if(preg_match("#\.officeapps\.#", $www)){return "webapps";}
		if(preg_match("#\.force\.com$#", $www)){return "webapps";}

		
		if(preg_match("#literie(\-|\.)#", $www)){return "housing/accessories";}
		if(preg_match("#\.fly\.fr$#", $www)){return "housing/accessories";}
		if(preg_match("#mobilier-et-decoration#", $www)){return "housing/accessories";}
		if(preg_match("#\.cuisines-schmidt\.com$#", $www)){return "housing/accessories";}
		if(preg_match("#\.ikea\.[a-z]+$#", $www)){return "housing/accessories";}
		
		if(preg_match("#\.lpo\.fr$#", $www)){return "animals";}
		
		
		if(preg_match("#\.sliderocket.com$#", $www)){return "webapps";}
		if(preg_match("#\.quora\.com$#", $www)){return "webapps";}
		if(preg_match("#\.hostedexchange\.fr$#", $www)){return "webapps";}
		if(preg_match("#\.officelive\.com$#", $www)){return "webapps";}
		if(preg_match("#\.salesforce\.com$#", $www)){return "webapps";}
		if(preg_match("#\.aspect\.com$#", $www)){return "webapps";}
		if(preg_match("#\.mescontenus\.orange\.fr$#", $www)){return "webapps";}
		if(preg_match("#\.netviewer\.com$#", $www)){return "webapps";}
		if(preg_match("#docsviewer\.googleusercontent\.com$#", $www)){return "webapps";}
		if(preg_match("#\.connect\.astaro\.com$#", $www)){return "webapps";}
		if(preg_match("#\.inetpsa\.com$#", $www)){return "webapps";}
		if(preg_match("#\.cpn\.vwg$#", $www)){return "webapps";}
		
		if(preg_match("#marechalerie#", $www)){return "horses";}
		if(preg_match("#cheveaux#", $www)){return "horses";}
		if(preg_match("#equitation#", $www)){return "horses";}
		if(preg_match("#horseware#", $www)){return "horses";}
		
		if(strpos($www, ".skyrock.net")>0){return "webradio";}
		if(preg_match("#\.radiobeta\.info$#", $www)){return "webradio";}
		if(preg_match("#\.rtl2\.fr$#", $www)){return "webradio";}
		if(preg_match("#\.slacker\.com$#", $www)){return "webradio";}
		if(preg_match("#\.pandora\.com$#", $www)){return "webradio";}
		if(preg_match("#\.2dayfm\.com\.au$#", $www)){return "webradio";}
		if(preg_match("#\.antenne\.de$#", $www)){return "webradio";}
		if(preg_match("#\.radiosuisseclassique\.ch$#", $www)){return "webradio";}
		if(preg_match("#\.radiocdn\.com$#", $www)){return "webradio";}
		
		if(strpos($www, ".nrj.fr")>0){return "webradio";}  
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
		if(preg_match("#^offre-emploi\..+#", $www)){return "jobsearch";}
		if(preg_match("#\.jobsonline\.net$#", $www)){return "jobsearch";}
		if(preg_match("#\.viadeo\.com$#", $www)){return "jobsearch";}
		if(preg_match("#\.naukri\.com$#", $www)){return "jobsearch";}
		if(preg_match("#\.praca\.onet\.pl$#", $www)){return "jobsearch";}
		if(preg_match("#\.trabajando\.com$#", $www)){return "jobsearch";}
		if(preg_match("#\.monster\.[a-z]+$#", $www)){return "jobsearch";}
		if(preg_match("#\.newjobs\.[a-z]+$#", $www)){return "jobsearch";}
		
		
		if(strpos($www, "collegehumor.com")>0){return "recreation/humor";}
		if(preg_match("#\.bu2z\.com$#",$www)){return "recreation/humor";}
		
		
		if(preg_match("#\.punchbowl\.com$#",$www)){return "gifts";}
		if(preg_match("#\.wonderbox\.com$#",$www)){return "gifts";}
		if(preg_match("#\.americangreetings\.com$#",$www)){return "gifts";}

		if(preg_match("#\.bankrate\.com$#",$www)){return "finance/moneylending";}
		if(preg_match("#\.cofinoga\.fr$#",$www)){return "finance/moneylending";}
		
		if(preg_match("#\.petitionsduweb\.fr$#",$www)){return "politic";}
		
		
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
		
		if(preg_match("#\.ancestry\.com$#",$www)){return "genealogy";}
		if(preg_match("#\.geneawiki\.com$#",$www)){return "genealogy";}
		
		if(preg_match("#\.generali\.fr$#",$www)){return "finance/insurance";}
		if(preg_match("#\.ameli\.fr$#",$www)){return "finance/insurance";}
		if(preg_match("#.*?-assurances\.[a-z]+$#",$www)){return "finance/insurance";}
		if(preg_match("#.*?-assurance\.[a-z]+$#",$www)){return "finance/insurance";}
				
		if(preg_match("#\.arkive\.org$#",$www)){return "nature";}
		if(preg_match("#\.wwf\.org\.uk$#",$www)){return "green";}
		if(preg_match("#\.durable\.com$#",$www)){return "green";}
		if(preg_match("#^crpf-(.*?)\.fr$#",$www)){return "green";}
		
		if(preg_match("#^192\.168\.[0-9]+\.[0-9]+$#", $www)){return "internal";}
		if(preg_match("#^172\.18\.[0-9]+\.[0-9]+$#", $www)){return "internal";}
		if(preg_match("#^172\.16\.[0-9]+\.[0-9]+$#", $www)){return "internal";}		
		if(preg_match("#^10\.[0-9]+\.[0-9]+\.[0-9]+$#", $www)){return "internal";}
		
		if(preg_match("#\.lan$#", $www)){return "internal";}
		if(preg_match("#\.local$#", $www)){return "internal";}
		if(preg_match("#\.intra$#", $www)){return "internal";}
		//if(preg_match("#^130\.117\.[0-9]+\.[0-9]+$#", $www)){return "internal";}
		
		if(preg_match("#\.teamviewer\.com$#", $www)){return "remote-control";}
		if(preg_match("#\.webex\.com$#", $www)){return "remote-control";}
		if(preg_match("#\.justice\.fr$#", $www)){return "justice";}
		if(preg_match("#\.lemondedudroit\.fr$#", $www)){return "justice";}
		if(preg_match("#\.lgdj\.fr$#", $www)){return "justice";}
		
		if(strpos($www, ".m6web.fr")>0){return "webtv";}
		if(preg_match("#\.plusbellelavie\.fr$#", $www)){return "webtv";}
		if(preg_match("#\.tf1\.fr$#", $www)){return "webtv";}
		if(preg_match("#\.chiens-de-france\.[a-z]+$#", $www)){return "hobby/pets";}
		
		if(preg_match("#\.monuments-nationaux\.fr+$#", $www)){return "culture";}
		if(preg_match("#^musee(\-|\.]).*?\.fr$#", $www)){return "culture";}
		if(preg_match("#^exposition-.*?\.[a-z]+$#", $www)){return "culture";}
		if(preg_match("#^culture-.*?\.[a-z]+$#", $www)){return "culture";}
		if(preg_match("#^opera-.+?\.[a-z]+$#", $www)){return "culture";}
		if(preg_match("#\.structurae\.[a-z]+$#", $www)){return "culture";}
		if(preg_match("#theatre#", $www)){return "culture";}
		
		if(strpos($www, "edenflirt.com")>0){return "dating";}
		if(preg_match("#\.badoo.com$#", $www)){return "dating";}
		if(preg_match("#\.datinggold.com$#", $www)){return "dating";}
		if(preg_match("#\.xflirt\.com$#", $www)){return "dating";}
		if(preg_match("#\.wekiss\.com$#", $www)){return "dating";}
		if(preg_match("#\.amoureux\.com$#", $www)){return "dating";}
		if(preg_match("#\.illimeet\.com$#", $www)){return "dating";}
		if(preg_match("#\.wekiss\.com$#", $www)){return "dating";}
		if(preg_match("#\.monamour\.ru$#", $www)){return "dating";}
		if(preg_match("#\.rencx\.mobi$#", $www)){return "dating";}
		if(preg_match("#\.123-cougars\.com$#", $www)){return "dating";}
		if(preg_match("#\.easy-rencontres\.com$#", $www)){return "dating";}
		
		if(preg_match("#(tattooing|tatouage|tattoo|tatouer|piercing)#", $www)){return "tattooing";}

		if(preg_match("#ecoles48\.net$#", $www)){return "teens";}
		if(preg_match("#\.melty\.fr$#", $www)){return "teens";}
		if(preg_match("#\.mboa\.info$#", $www)){return "teens";}
		
		if(preg_match("#\.europeana\.eu$#", $www)){return "culture";}
		if(preg_match("#\.leschampslibres\.fr$#", $www)){return "culture";}
		if(preg_match("#\.chateauversailles\.fr$#", $www)){return "culture";}
		if(preg_match("#\.culture\.fr$#", $www)){return "culture";}
		if(preg_match("#^musees-(.*?)\.[a-z]+$#", $www)){return "culture";}
		
		if(preg_match("#\.healthcentral\.com$#", $www)){return "health";}
		if(preg_match("#\.lgds\.eu$#", $www)){return "health";}
		if(preg_match("#\.apigem\.org$#", $www)){return "health";}
		if(preg_match("#\.curie\.fr$#", $www)){return "health";}
		if(preg_match("#cardiologie#", $www)){return "health";}
		if(preg_match("#health#", $www)){return "health";}
		if(preg_match("#-medical$#", $www)){return "medical";}
		if(preg_match("#medical-$#", $www)){return "medical";}
		
			
		if(preg_match("#\.aufeminin\.com$#", $www)){return "womanbrand";}
		if(preg_match("#\.prime-beaute\.com$#", $www)){return "womanbrand";}
		if(preg_match("#\.mmasculin\.com$#", $www)){return "womanbrand";}
		
		
		
		if(preg_match("#\.inserm\.fr$#", $www)){return "sciences";}
		if(preg_match("#\.inria\.fr$#", $www)){return "sciences";}
		if(preg_match("#\.genethique\.[a-z]+$#", $www)){return "sciences";}
		if(preg_match("#\.ird\.fr$#", $www)){return "sciences";}
		if(preg_match("#\.cnrs\.fr$#", $www)){return "sciences";}
		
		if(preg_match("#(wedding|mariage|married|noces)#", $www)){return "getmarried";}

		if(preg_match("#\.lg\.[a-z]+$#", $www)){return "electricalapps";}
		if(preg_match("#\.comhttp$#", $www)){return "reaffected";}
		if(preg_match("#\.cpom$#", $www)){return "reaffected";}
		
		if(strpos($www, "forum")>0){return "forums";}
		if(strpos($www, "sites.googlegroups.com")>0){return "forums";}
		if(preg_match("#^forum\.#", $www)){return "forums";}
		if(preg_match("#^forums\.#", $www)){return "forums";}
		if(preg_match("#answers\.yahoo\.com$#", $www)){return "forums";}
		if(preg_match("#\.kaskus\.us$#", $www)){return "forums";}
		
		if(preg_match("#revolvermaps\.com$#", $www)){return "webplugins";}
		if(preg_match("#\.googleapis\.com$#", $www)){return "webplugins";}
		if(preg_match("#\.geo[0-9]+\.com$#", $www)){return "webplugins";}
		if(preg_match("#\.wufoo\.com$#", $www)){return "webplugins";}
		if(preg_match("#\.psplugin\.com$#", $www)){return "webplugins";}
		
		
		if(preg_match("#\.centres-sociaux\.fr$#", $www)){return "humanitarian";}

		
		
		if(preg_match("#^wpad\.#", $www)){return "internal";}

		if(preg_match("#\.glam\.com$#", $www)){return "clothing";}
		if(preg_match("#\.c-mod\.com$#", $www)){return "clothing";}
		if(preg_match("#\.etnies\.com$#", $www)){return "clothing";}
		if(preg_match("#\.spartoo\.[a-z]+$#", $www)){return "clothing";}
		if(preg_match("#\.placedestendances\.com$#", $www)){return "clothing";}
		if(preg_match("#\.spreadshirt\.fr$#",$www)){return "clothing";}
		if(preg_match("#\.etam\.com$#",$www)){return "clothing";}
		if(preg_match("#\.zalando\.[a-z]+$#", $www)){return "clothing";}
		if(preg_match("#\.ztat\.net+$#", $www)){return "clothing";}
		if(preg_match("#\.shopstyle\.[a-z]+$#", $www)){return "clothing";}
		if(preg_match("#\.forzieri\.[a-z]+$#", $www)){return "clothing";}
		
		if(preg_match("#^newsletter\..+#", $www)){return "mailing";}
		if(preg_match("#purlsmail.com$#", $www)){return "mailing";}
		if(preg_match("#pompes.*funebres#", $www)){return "religion";}
		if(preg_match("#\.catholique\.fr$#", $www)){return "religion";}
		if(preg_match("#\.cef\.fr$#", $www)){return "religion";}
		if(preg_match("#funeral.*home#", $www)){return "religion";}
		if(preg_match("#\.netministry\.com$#", $www)){return "religion";}
		
		
		if(preg_match("#allocine\.fr$#",$www)){return "movies";}
		if(preg_match("#\.logmein\.com$#",$www)){return "remote-control";}
		if(preg_match("#psychologies\.com$#",$www)){return "recreation/wellness";}
		if(preg_match("#\.becomegorgeous\.com$#",$www)){return "recreation/wellness";}
		if(preg_match("#\.homestyler\.com$#",$www)){return "housing/doityourself";}
		if(preg_match("#\.ath\.cx$#",$www)){return "dynamic";}
		if(preg_match("#\.synology\.me$#",$www)){return "dynamic";}
		if(preg_match("#\.dyndns-mail\.com$#",$www)){return "dynamic";}
		if(preg_match("#\.hidemyass\.com$#",$www)){return "proxy";}
		if(preg_match("#\.cameleo\.ru$#",$www)){return "proxy";}
		
	

		
		if(preg_match("#\.electro-services[0-9]+\.[a-z]+$#", $www)){return "electricalapps";}

		if(preg_match("#\.vcst\.net$#", $www)){return "wine";}
		
		if(preg_match("#\.merchantcircle\.com$#", $www)){return "financial";}
		if(preg_match("#\.moneycontrol\.+#", $www)){return "financial";}
		if(preg_match("#^finance\..+?$#", $www)){return "financial";}
		
		if(preg_match("#\.bharatmatrimony\.com$#", $www)){return "dating";}
		if(preg_match("#\.datingfactory\.com$#", $www)){return "dating";}
		if(strpos($www, ".babylon.com")>0){return "translators";}
		if(preg_match("#^translate.*\.#", $www)){return "translators";}
		if(preg_match("#\.babelfish\.yahoo.com$#", $www)){return "translators";}
		if(preg_match("#\.nciku\.com$#", $www)){return "translators";}
		if(preg_match("#\.woxikon\.de$#", $www)){return "translators";}
		
		
		if(preg_match("#^translate\.google\.[a-z]+$#", $www)){return "translators";}
		if(preg_match("#\.scorecardresearch\.com$#", $www)){return "marketingware";}
		if(preg_match("#\.lb-clients\.com$#", $www)){return "marketingware";}
		if(preg_match("#\.dolead\.com$#", $www)){return "marketingware";}
		

		if(strpos($www, ".schneider-electric.com")>0){return "industry";}
		if(preg_match("#\.afacerist\.ro$#", $www)){return "industry";}
		if(preg_match("#\.hubb\.fr$#", $www)){return "industry";}
		if(preg_match("#\.telenav\.com$#", $www)){return "industry";}
		if(strpos($www, ".lyonnaise-des-eaux.fr")>0){return "industry";} 
		if(strpos($www, ".danfoss.com")>0){return "industry";} 
		if(preg_match("#\.batiactu\.com$#", $www)){return "industry";}
	
		if(strpos($www, "weatherzone")>0){return "science/weather";}
		if(preg_match("#\.accu-weather\.com$#", $www)){return "science/weather";}
		if(preg_match("#\.lachainemeteo\.com$#", $www)){return "science/weather";}
		if(preg_match("#-meteo\.[a-z]+$#", $www)){return "science/weather";}
		if(preg_match("#^meteo\..+?#", $www)){return "science/weather";}
		if(preg_match("#^meteo-.*?\.[a-z]+#", $www)){return "science/weather";}
		if(preg_match("#\.meteofrance\.com$#", $www)){return "science/weather";}
		if(preg_match("#\.meteosun\.com$#", $www)){return "science/weather";}
		if(preg_match("#\.meteo-.*?\.[a-z]+$#", $www)){return "science/weather";}
		if(preg_match("#\.wetter\.#", $www)){return "science/weather";}
		if(preg_match("#\.accuweather\.com$#", $www)){return "science/weather";}
		if(preg_match("#\.gismeteo\.[a-z]+$#", $www)){return "science/weather";}
		
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
		if(preg_match("#\.bankfirstcorp.com$#", $www)){return "industry";}
		
		
		if(preg_match("#\.prositehosting\.co\.uk$#", $www)){return "isp";}
		if(preg_match("#\.bouyguestelecom\.fr$#", $www)){return "isp";}
		if(preg_match("#\.onlinehome-server\.info$#", $www)){return "isp";}
		if(preg_match("#\.cdn77\.net$#", $www)){return "isp";}
		if(preg_match("#^clinique([-\.]+).+?\.$#", $www)){return "hospitals";}
		if(preg_match("#^chu-.+?\.fr$#", $www)){return "hospitals";}
		if(preg_match("#^chi-.+?\.fr$#", $www)){return "hospitals";}
		if(preg_match("#^chi[0-9]+\.fr$#", $www)){return "hospitals";}
		if(preg_match("#\.cgos\.info$#", $www)){return "hospitals";}
		
		if(preg_match("#\.thinkpage\.cn$#", $www)){return "society";}
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
		
		if(preg_match("#^aeroclub-.*?\.[a-z]+$#", $www)){return "automobile/planes";}
		if(preg_match("#covoiturage#", $www)){return "automobile/carpool";}
		
		if(preg_match("#\.picnik\.com$#", $www)){return "photo";}
		if(preg_match("#\.fotoflexer\.com$#", $www)){return "photo";}
		if(preg_match("#\.photobox\.[a-z\.]+$#", $www)){return "photo";}
		if(preg_match("#\.img-dpreview\.com$#", $www)){return "photo";}
		if(preg_match("#\.mypix\.com$#", $www)){return "photo";}
		if(preg_match("#\.bhphotovideo\.com$#", $www)){return "photo";}
		
		if(preg_match("#\.ryland\.com$#", $www)){return "housing/builder";}
		if(preg_match("#-batiment\.fr$#", $www)){return "housing/builder";}
		if(preg_match("#\.newlandcommunities\.com$#", $www)){return "housing/builder";}
		
		if(preg_match("#^toolbar\..+#", $www)){return "browsersplugins";}
		if(preg_match("#\.toolbar\.yahoo\.[a-z]+$#", $www)){return "browsersplugins";}
		if(preg_match("#\.widdit\.[a-z]+$#", $www)){return "browsersplugins";}
		
		if(preg_match("#\.sandhills\.com$#", $www)){return "industry";}
		if(preg_match("#\.adp\.com$#", $www)){return "industry";}
		if(preg_match("#.cat\.com$#", $www)){return "industry";}
		if(preg_match("#\.choosit\.com$#", $www)){return "industry";}
		
		if(preg_match("#^maps\.google\..+?$#", $www)){return "maps";}
		if(preg_match("#^mt[0-9]+\.google\.[a-z]+$#", $www)){return "maps";}
		if(preg_match("#^cbk[0-9]+\.google\.[a-z]+$#", $www)){return "maps";}
		if(preg_match("#\.nearmap\.[a-z]+$#", $www)){return "maps";}
		if(preg_match("#\.mappy\.[a-z]+#", $www)){return "maps";}
		if(preg_match("#\.ups\.com$#", $www)){return "transport";}
		if(preg_match("#\.chronopost\.com$#", $www)){return "transport";}
		if(preg_match("#\.modlife\.com$#", $www)){return "celebrity";}
		if(preg_match("#\.closermag\.[a-z]+$#", $www)){return "celebrity";}
		if(preg_match("#\.celebritywise\.com$#", $www)){return "celebrity";}
		
		if(preg_match("#constructeur-de-maisons#", $www)){return "housing/builders";}
		if(preg_match("#\.cargocollective\.coms#", $www)){return "hobby/arts";}
		
		if(preg_match("#\.51\.la$#", $www)){return "spyware";}
		if(preg_match("#paroisse.+?\.[a-z]+$#", $www)){return "religion";}
		if(preg_match("#\.meshfriend\.com$#", $www)){return "reaffected";}
		
		if(preg_match("#\.actionmotorsports\.com$#", $www)){return "automobile/cars";}
		if(preg_match("#\.auto-selection\.com$#", $www)){return "automobile/cars";}
		if(preg_match("#\.vrum\.com\.br$#", $www)){return "automobile/cars";}
		if(preg_match("#auto-moto\.[a-z]+$$#", $www)){return "automobile/cars";}
		if(preg_match("#\.[0-9]+auto\.com$#", $www)){return "automobile/cars";}
		if(preg_match("#\.autoscout[0-9]+\.net$#", $www)){return "automobile/cars";}
		if(preg_match("#\.wdlarson\.com$#", $www)){return "automobile/cars";}
		if(preg_match("#\.auto\.ifeng\.com$#", $www)){return "automobile/cars";}
		if(preg_match("#\.carpartswholesale\.com$#", $www)){return "automobile/cars";}
		if(preg_match("#\.seat\.fr$#", $www)){return "automobile/cars";}
		if(preg_match("#yokohama#", $www)){return "automobile/cars";}		
		
		if(preg_match("#\.motoblouz\.com$#", $www)){return "automobile/bikes";}
		if(preg_match("#harley([\-|\.])davidson#", $www)){return "automobile/bikes";}
		if(preg_match("#\.321moto\.com$#", $www)){return "automobile/bikes";}
		if(preg_match("#\.[0-9](moto|auto)\.[a-z]+$#", $www)){return "automobile/bikes";}
		if(preg_match("#\.harley-davidson\.com$#", $www)){return "automobile/bikes";}
		if(preg_match("#\.mobcustom\.[a-z]+$#", $www)){return "automobile/bikes";}
		
		
		
		if(preg_match("#\.persona\.org$#", $www)){return "browsersplugins";}
		if(preg_match("#eleicoes.*?\.[a-z]+#", $www)){return "politic";}
		if(preg_match("#polyclinique#", $www)){return "hospitals";}
		if(preg_match("#^garage-.*?\.fr$#", $www)){return "automobile/cars";}
		if(preg_match("#^brasserie-#",$www)){return "recreation/nightout";}
		if(preg_match("#^recettes-.*?\.fr$#", $www)){return "hobby/cooking";}
		if(preg_match("#\.cuisineblog\.fr$#", $www)){return "hobby/cooking";}
		if(preg_match("#\.meltyfood\.fr$#", $www)){return "hobby/cooking";}
		if(preg_match("#\.rezeptwiese\.[a-z]+$#", $www)){return "hobby/cooking";}
		if(preg_match("#\.marmitoncdn\.org$#", $www)){return "hobby/cooking";}
		if(preg_match("#\.zrdt6-[0-9]+\.zv$#", $www)){return "suspicious";}
		if(preg_match("#\.reg-check\.com$#", $www)){return "suspicious";}
		if(preg_match("#\.ekind-xml\.com$#", $www)){return "suspicious";}
		if(preg_match("#\.keukenstickers.nl$#", $www)){return "suspicious";}
		if(preg_match("#\.zrdt6-null\.zv#", $www)){return "reaffected";}
		if(preg_match("#informatique#", $www)){return "science/computing";}
		if(preg_match("#computing#", $www)){return "science/computing";}
		
		$cat=$this->Tracker($www);if($cat<>null){return $cat;}
		$cat=$this->mailing($www);if($cat<>null){return $cat;}
		
	}
}