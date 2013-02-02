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
		if(preg_match("#\.blogspot\.[\.a-z]+$#", $www)){return "blog";}
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
	}
	
	private function Tracker($www){
		if(preg_match("#[0-9\-]+\.fbx\.proxad\.net$#", $www)){return "dynamic";}
		include_once(dirname(__FILE__)."/class.squid.categorize.tracker.php");
		$track=new categorize_tracker();
		$val=$track->Tracker($www);
		if($val<>null){return $val;}
		if(preg_match("#\.xiti\.com$#", $www)){return "tracker";}
		if(preg_match("#\.resultspage\.com$#", $www)){return "tracker";}
		if(preg_match("#\.memecounter\.com$#", $www)){return "tracker";}
		if(preg_match("#\.raster-xml\.info$#", $www)){return "tracker";}
		if(preg_match("#\.quantcount\.com$#", $www)){return "tracker";}
		if(preg_match("#\.zeroredirect\.com$#", $www)){return "tracker";}
		if(preg_match("#\.lognormal\.[a-z]+$#", $www)){return "tracker";}
		if(preg_match("#\.trw[0-9]+\.com$#", $www)){return "tracker";}
		if(preg_match("#\.estadisticasgratis\.es$#", $www)){return "tracker";}
		if(preg_match("#\.2o7\.net$#", $www)){return "tracker";}
		if(preg_match("#^webanalytics\.#", $www)){return "tracker";}
		if(preg_match("#\.traficstat\.[a-z]+$#",$www)){return "tracker";}
		if(preg_match("#\.popstats\.com\.br$#",$www)){return "tracker";}
		if(preg_match("#\.trck\.me$#",$www)){return "tracker";}
		if(preg_match("#^nalog[0-9]+\.kaluga\.net$#",$www)){return "tracker";}
		if(preg_match("#\.web-force\.eu$#",$www)){return "tracker";}
		if(preg_match("#\.trafficg\.com$#",$www)){return "tracker";}
		if(preg_match("#\.meetrics\.net$#",$www)){return "tracker";}
		if(preg_match("#\.online-metrix\.net$#",$www)){return "tracker";}
		if(preg_match("#\.track\.clicksure\.com$#",$www)){return "tracker";}
		if(preg_match("#\.effectivemeasure\.net$#",$www)){return "tracker";}
		if(preg_match("#\.irotracker\.com$#",$www)){return "tracker";}
		if(preg_match("#^cedexis-#",$www)){return "tracker";}
		if(preg_match("#\.tracker\.prq\.to$#",$www)){return "tracker";}
		
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
		if(preg_match("#\.akamai\.net$#",$www)){return "imagehosting";}
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
		if(strpos($www, ".googlevideo.com")>0){return "audio-video";}
		if(strpos($www, ".youtube.com")>0){return "audio-video";}
		if(strpos($www, ".atmcdn.pl")>0){return "audio-video";}
		
		if(preg_match("#(videomore|moviclip)\.[a-z]+$#",$www)>0){return "audio-video";}
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
		if(preg_match("#\.bnf\.fr$#", $www)){return "books";}
		if(preg_match("#\.electre\.com$#", $www)){return "books";}
		if(preg_match("#\.tradepub\.com$#", $www)){return "books";}
		if(preg_match("#\.docstoc-store\.com$#", $www)){return "books";}
		if(preg_match("#bouquins#", $www)){return "books";}
	}
	
	private function Children($www){
		if(preg_match("#(maternelle|mercerie)#",$www)){return "children";}
		if(preg_match("#\.treehousetv\.com$#",$www)){return "children";}
		if(preg_match("#\.clubpenguin\.com$#",$www)){return "children";}
		if(preg_match("#\.gulli\.fr$#",$www)){return "children";}
		if(preg_match("#\.pour-enfants\.fr$#",$www)){return "children";}
		
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
		if(preg_match("#(mobilephone|blackberry|smartphone|nokia)#",$www)){return "mobile-phone";} 
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
		
	}
	
	Private function humanitarian($www){
		if(strpos($www, ".croix-rouge.fr")>0){return "humanitarian";}
		if(preg_match("#\.donnerenligne\.fr$#", $www)){return "humanitarian";}
		if(preg_match("#\.oxfam\.[a-z\.]+$#", $www)){return "humanitarian";}
	}
	
	private function schools($www){
		include_once(dirname(__FILE__)."/class.squid.catz.schools.inc");
		$pub=new squid_catz_schools();$cat=$pub->parse($www);if($cat<>null){return "recreation/schools";}
		if(preg_match("#\.state\.oh\.us$#", $www)){return "recreation/schools";}
		if(preg_match("#\.tu-harburg\.de$#", $www)){return "recreation/schools";}
		if(preg_match("#\.sch\.uk$#", $www)){return "recreation/schools";}
		if(preg_match("#\.mathletics\.com$#", $www)){return "recreation/schools";}
		if(preg_match("#\.grenet\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.raisoni\.net$#", $www)){return "recreation/schools";}
		if(preg_match("#\.insa-.*?\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.edu\.[a-z]+$#", $www)){return "recreation/schools";}
		if(preg_match("#\.schoology\.com$#", $www)){return "recreation/schools";}
		if(preg_match("#\.ac.th$#", $www)){return "recreation/schools";}
		if(preg_match("#\.sjks\.be$#", $www)){return "recreation/schools";}
		if(preg_match("#\.sch\.uk$#", $www)){return "recreation/schools";}
		if(preg_match("#\.powerschool\.com$#", $www)){return "recreation/schools";}
		if(preg_match("#\.pleiad\.net$#", $www)){return "recreation/schools";}
		if(preg_match("#\.k12\.ia\.us$#", $www)){return "recreation/schools";}
		if(preg_match("#\.ac\.[a-z]+$#", $www)){return "recreation/schools";}
		if(preg_match("#\.tableau-noir\.net$#", $www)){return "recreation/schools";}
		if(preg_match("#(college|lycee|ecole).*?\.free\.fr$#", $www)){return "recreation/schools";}
		
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
		if(preg_match("#(football|intersport|sportetstyle|footeo|judoclub)#", $www)){return "recreation/sports";}
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
	}
	
	private function shopping($www){
		include_once(dirname(__FILE__)."/class.squid.catz.shopping.inc");
		$pub=new squid_catz_shopping();$cat=$pub->parse($www);if($cat<>null){return "shopping";}		
		if(preg_match("#\.blizko\.ru#",$www)){return "shopping";}
		if(preg_match("#\.focalprice\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#\.cherchons\.com$#",$www)){return "shopping";}
		if(preg_match("#\.diplotop\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#\.360buy\.com$#",$www)){return "shopping";}
		if(preg_match("#\.mbstatic\.net$#",$www)){return "shopping";}
		if(preg_match("#\.groupon\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#\.zura\.com\.br$#",$www)){return "shopping";}
		if(preg_match("#.*?-amazon\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#\.amazon\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#\.leboncoin\.fr$#",$www)){return "shopping";}
		if(preg_match("#achetez#",$www)){return "shopping";}
		if(preg_match("#\.htfacile\.fr$#",$www)){return "shopping";}
		if(preg_match("#\.leguide\.com$#",$www)){return "shopping";}
		if(preg_match("#\.crystalcommerce\.com$#",$www)){return "shopping";}
		if(preg_match("#\.digitalriver\.com$#",$www)){return "shopping";}
		if(preg_match("#\.m6boutique\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#\.cataloguepromo\.fr$#",$www)){return "shopping";}
		if(preg_match("#\.ftuan\.com$#",$www)){return "shopping";}
		if(preg_match("#\.rdc\.fr$#",$www)){return "shopping";}
		if(preg_match("#\.shoptondeal\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#ventes-privees#",$www)){return "shopping";}
		if(preg_match("#(beauteprivee|achetez)#",$www)){return "shopping";}
		if(preg_match("#alibaba\.com#",$www)){return "shopping";}
		if(preg_match("#\.dx\.com#",$www)){return "shopping";}
		if(preg_match("#\.ilamparas\.com#",$www)){return "shopping";}
		if(preg_match("#\.auctions\.yahoo\.#",$www)){return "shopping";}
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
		if(preg_match("#\.fliphotos\.com$#",$www)){return "pictureslib";}
		if(preg_match("#\.photoshelter\.com$#",$www)){return "pictureslib";}
		if(preg_match("#\.2photo\.ru$#",$www)){return "pictureslib";}
		if(preg_match("#\.scribol\.com$#",$www)){return "pictureslib";}
		if(preg_match("#\.caption\.it$#",$www)){return "pictureslib";}
		if(preg_match("#\.shutterstock\.com$#",$www)){return "pictureslib";}
		
	}
	
	private function governments($www){
		if(preg_match("#\.justice\.gouv\.fr$#", $www)){return "justice";}
		if(preg_match("#\.tribunal-administratif\.[a-z]+$#", $www)){return "justice";}
		if(preg_match("#\.gouv\.fr$#", $www)){return "governments";}
		if(preg_match("#\.gov$#", $www)){return "governments";}
		if(preg_match("#\.gov\.cn$#", $www)){return "governments";}
		if(preg_match("#gouv\.qc\.ca$#", $www)){return "governments";}	
		if(preg_match("#\.gov\.[a-z]+$#", $www)){return "governments";}
		if(preg_match("#\.govt\.[a-z]+$#", $www)){return "governments";}
		if(preg_match("#\.gouv\.[a-z]+$#", $www)){return "governments";}	
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
		
	}
	
	private function searchengines($www){
		if(strpos($www, ".l.google.com")>0){return "searchengines";}
		if(strpos($www, "ipv6.yahoo.com")>0){return "searchengines";}
		if(preg_match("#amarillas#", $www)){return "searchengines";}
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
		if(preg_match("#\.telephone-adresse\.com$#", $www)){return "searchengines";}
		if(preg_match("#\.nomao\.com$#", $www)){return "searchengines";}
		if(preg_match("#\.szukacz\.pl$#", $www)){return "searchengines";}
		if(preg_match("#\.yalwa\.co\.za$#", $www)){return "searchengines";}
		if(preg_match("#\.pai.pt$#",$www)){return "searchengines";}
		if(preg_match("#\.msn\.com$#",$www)){return "searchengines";}
		if(preg_match("#\.paginasamarillas\.[a-z\.]+$#", $www)){return "searchengines";}
		if(preg_match("#\.xo\.gr$#", $www)){return "searchengines";}
		
	}
	
	private function dictionaries($www){
		if(preg_match("#about\.com$#",$www)){return "dictionaries";}
		if(preg_match("#\.wikipedia\.[0-9\.]+$#",$www)){return "dictionaries";}
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
		if(preg_match("#\.tpub\.com$#",$www)){return "dictionaries";}
		
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
		if(preg_match("#offrescanal#", $www)){return "webtv";}
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
		if(preg_match("#\.yammer\.com$#", $www)){return "socialnet";}
		
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

		if(preg_match("#opensocial\.googleusercontent$#", $www)){return "socialnet";}
		if(preg_match("#\.cyworld\.com$#", $www)){return "socialnet";}
		
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
		if(preg_match("#\.adhood\.com$#", $www)){return "publicite";}
		if(preg_match("#\.betrad\.com$#", $www)){return "publicite";}
		if(preg_match("#\.smaato\.net$#", $www)){return "publicite";}
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
		if(preg_match("#\.fmpub\.net$#", $www)){return "publicite";}
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
			
		
	}
	
	private function ScienceComputing($www){
		if(preg_match("#([\.|-])photoshop([-|\.])#", $www)){return "science/computing";}
		if(preg_match("#(informatique|computing|computer|ordinateur|logiciel|software|sourcecode|freebox)#", $www)){return "science/computing";}
		if(strpos($www, ".googlecode.com")>0){return "science/computing";}
		if(strpos($www, ".sourceforge.net")>0){return "science/computing";}
		if(strpos($www, ".opendns.com")>0){return "science/computing";}
		if(strpos($www, ".symantec.com")>0){return "science/computing";}
		if(strpos($www, ".nabble.com")>0){return "science/computing";}		
		if(preg_match("#\.hp\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.citrix\.com$#", $www)){return "science/computing";}
		if(strpos($www, ".ubuntu.")>0){return "science/computing";}
		if(preg_match("#\.opendns\.com$#", $www)){return "science/computing";}
		if(preg_match("#\.hyves\.org$#", $www)){return "science/computing";}
		if(preg_match("#\.sanasecurity\.[a-z]+$#", $www)){return "science/computing";}
		if(preg_match("#\.wdc\.com$#", $www)){return "science/computing";}
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
	}
	

	private function music($www){
		
		if(strpos($www, ".universalmusic.")>0){return "music";}
		if(strpos($www, ".sonymusic.")>0){return "music";}
		if(strpos($www, "hulkshare.com")>0){return "music";}
		if(strpos($www, "projectplaylist.com")>0){return "music";}
		if(strpos($www, ".pitchfork.com")>0){return "music";}
		if(strpos($www, ".kazaa.com")>0){return "music";}
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
		if(preg_match("#\.sheetmusicengine\.com$#", $www)){return "music";}
		if(preg_match("#\.scorser\.com$#", $www)){return "music";}
		if(preg_match("#\.musicblog\.fr$#", $www)){return "music";}
		if(preg_match("#\.midomi\.com$#", $www)){return "music";}
		if(preg_match("#\.noomiz\.com$#", $www)){return "music";}
		if(preg_match("#\.zimbalam\.com$#", $www)){return "music";}
		if(preg_match("#\.believe\.fr$#", $www)){return "music";}
		if(preg_match("#(musique|musician|harmonica|musik|musical|guitar)#", $www)){return "music";}
		if(preg_match("#promusic[0-9]+\.[a-z]+$#", $www)){return "music";}
		if(preg_match("#\.zzounds\.com$#", $www)){return "music";}
		if(preg_match("#\.worldclips\.ru$#", $www)){return "music";}
		if(preg_match("#\.soundclick\.com$#", $www)){return "music";}
		if(preg_match("#\.grosse-caisse\.fr$#", $www)){return "music";}
		if(preg_match("#1ting\.com$#", $www)){return "music";}
		if(preg_match("#\.lyrics\.com$#", $www)){return "music";}
		if(preg_match("#\.playgroundmag\.net$#", $www)){return "music";}
		
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
		if(preg_match("#\.mapquest\.[a-z\.]+$#", $www)){return "maps";}
		if(preg_match("#\.mapservice\.[a-z\.]+$#", $www)){return "maps";}
		if(preg_match("#\.mapit\.[a-z\.]+$#", $www)){return "maps";}
		if(preg_match("#\.lewatmana\.com$#", $www)){return "maps";}
		if(preg_match("#\.wikimapia\.org$#", $www)){return "maps";}
		if(preg_match("#\.opencyclemap\.org$#", $www)){return "maps";}
		if(preg_match("#\.maptiles-maptp[0-9]+\.[a-z]+$#",$www)){return "maps";}
		if(preg_match("#\.onyourmap\.[a-z]+$#",$www)){return "maps";}
		if(preg_match("#\.intermaps\.[a-z]+$#",$www)){return "maps";}
		if(preg_match("#\.mapbar\.com$#",$www)){return "maps";}
		if(preg_match("#\.sytadin\.fr$#",$www)){return "maps";}
		if(preg_match("#\.maps\.[a-z]+$#",$www)){return "maps";}
		if(preg_match("#\.wikimapia\.org$#",$www)){return "maps";}
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
		if(preg_match("#\.chatroll\.com$#",$www)){return "chat";}
		if(preg_match("#^chat\..*\.[a-z]+$#",$www)){return "chat";}
		if(preg_match("#\.livetex\.ru$#",$www)){return "chat";}
		if(preg_match("#\.chat\.professionali\.ru$#",$www)){return "chat";}
		if(preg_match("#\.msg\.ac[0-9]+\.yahoo.[a-z\.]+$#",$www)){return "chat";}
		if(preg_match("#\.olark\.com$#",$www)){return "chat";}

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
		if(preg_match("#\.celebsempire\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.smut\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.bfdare\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.brutaltops\.com$#", $www)>0){return "porn";}
		if(preg_match("#\.ventedvdx\.net$#", $www)>0){return "porn";}
		if(preg_match("#\.orgasmatrix\.com$#", $www)>0){return "porn";}
		if(preg_match("#(fucking|youporn)#", $www)){return "porn";}
		if(preg_match("#^gay-#", $www)){return "porn";}
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
		if(preg_match("#\.adbucks\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.jasminfetishstatic\.com#i",$www)){return "porn";}
		if(preg_match("#\.lj\.com#i",$www)){return "porn";}
		if(preg_match("#\.ljstatic\.com#i",$www)){return "porn";}
		if(preg_match("#\.perfectgirls\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.mydirtyhobby\.[a-z]+#i",$www)){return "porn";}
		if(preg_match("#\.amateurcommunity\.com$#i",$www)){return "porn";}
		if(preg_match("#\.xxxallaccesspass\.com$#i",$www)){return "porn";}
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
		if(preg_match("#(movie|film|cinema|streaming|filmandvideolighting).*?\.(blogspot|altervista).*?\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#^.+?-lefilm\.com$#", $www)){return "movies";}
		if(preg_match("#\.netflix\.com$#", $www)){return "movies";}
		if(preg_match("#\.*?starmovies\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.streaming\.claranet\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.moviesmobile\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.[0-9]+starmovies\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#\.cotecine\.fr$#", $www)){return "movies";}
		if(preg_match("#\.ariamovie[0-9]+\.[a-z]+$#", $www)){return "movies";}
		if(preg_match("#bollywood#", $www)){return "movies";}
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
		
	}
	
	
	private function games($www){	
		include_once(dirname(__FILE__)."/class.squid.catz.games.inc");
		$pub=new squid_catz_games();$cat=$pub->parse($www);if($cat<>null){return "games";}	
		if(preg_match("#\.majorleaguegaming\.com$#", $www)){return "games";}
		if(preg_match("#\.gamefaqs\.com$#", $www)){return "games";}
		if(preg_match("#\.playme[0-9]+\.[a-z]+$#",$www)){return "games";}
		if(preg_match("#\.roblox\.com$#",$www)){return "games";}
		if(preg_match("#\.torn\.com$#",$www)){return "games";}
		if(preg_match("#(playstation|nintendo|minecraft|xboxlive)#",$www)){return "games";}
		if(preg_match("#\.midasplayer.com$#",$www)){return "games";}
		if(preg_match("#\.king.com$#",$www)){return "games";}
		if(preg_match("#\.gamingfacts\.[a-z]+$#",$www)){return "games";}
		if(preg_match("#\.torn\.com$#",$www)){return "games";}
		if(preg_match("#\.spele\.nl$#",$www)){return "games";}
		if(preg_match("#\.happyoz\.com$#",$www)){return "games";}
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
	}
	
	private function automobile($www){
		if(strpos($www, ".otomoto.pl")>0){return "automobile/cars";}
		if(preg_match("#\.automotive\.com$#",$www)){return "automobile/cars";}
		if(preg_match("#(peugeot|hyundai|citroen|automobile|voiture)#",$www)){return "automobile/cars";}
		if(preg_match("#ebizautos\.com$#",$www)){return "automobile/cars";}
		if(preg_match("#stemco\.com$#",$www)){return "automobile/cars";}
		if(preg_match("#\.texasdirectauto\.com$#",$www)){return "automobile/cars";}
		if(preg_match("#auto\.sanook\.com$#",$www)){return "automobile/cars";}
		if(preg_match("#\.mercedes-benz\.[a-z]+$#",$www)){return "automobile/cars";}
		if(preg_match("#\.webmotors\.com\.br$#",$www)){return "automobile/cars";}
		if(preg_match("#\.carrental\.[a-z]+$#",$www)){return "automobile/cars";}
		if(preg_match("#\.hertz.com$#",$www)){return "automobile/cars";}
		if(preg_match("#\.audi\.[a-z\.]+$#",$www)){return "automobile/cars";}
	}
	
	private function mailing($www){
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
		if(preg_match("#(newsletter|mailsender|clicksmail|emailing)#", $www)){return "mailing";}
		if(preg_match("#\.mailingreport\.com$#", $www)){return "mailing";}
		if(preg_match("#\.magical-ears\.com$#", $www)){return "mailing";}
		if(preg_match("#\.adsender\.us$#", $www)){return "mailing";}
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
		if(preg_match("#\.vip-file\.com$#", $www)){return "downloads";}
		if(preg_match("#\.rargb\.me$#", $www)){return "downloads";}
		if(preg_match("#torrent\.#", $www)){return "downloads";}
		if(preg_match("#torrents\.#", $www)){return "downloads";}
		if(preg_match("#torrent-#", $www)){return "downloads";}
		if(preg_match("#torrents-#", $www)){return "downloads";}
		if(preg_match("#-torrent(\-|\.)#", $www)){return "downloads";}
		if(preg_match("#divdrip#", $www)){return "downloads";}
		if(preg_match("#\.softsalad\.com$#", $www)){return "downloads";}
		if(preg_match("#\.software\.informer\.com$#", $www)){return "downloads";}
	}
	
	private function dynamic($www){
		include_once(dirname(__FILE__)."/class.squid.catz.dynamic.inc");
		$pub=new squid_catz_dynamic();$cat=$pub->parse($www);if($cat<>null){return "dynamic";}
		if(preg_match("#^ppp.*?\.access\.hol\.gr$#", $www)){return "dynamic";}
		if(preg_match("#^ppp.*?\.tis-dialog\.ru$#", $www)){return "dynamic";}
		if(preg_match("#^ppp.*?\.gorge\.net$#", $www)){return "dynamic";}
		if(preg_match("#^ppp.*?\.argontech\.net$#", $www)){return "dynamic";}
		if(preg_match("#^ppp.*?\.internode\.on\.net$#", $www)){return "dynamic";}
		if(preg_match("#^ppp.*?\.htc\.net$#", $www)){return "dynamic";}
		if(preg_match("#^ppp.*?\.omkc\.ru$#", $www)){return "dynamic";}
		if(preg_match("#\.highway\.[a-z][0-9]+\.net$#", $www)){return "dynamic";}
		if(preg_match("#[0-9\-\.]+\.telenet\.ru$#", $www)){return "dynamic";}
		if(preg_match("#[0-9\-\.]+\.interra\.ru$#", $www)){return "dynamic";}
		if(preg_match("#.bredband\.telia\.com$#", $www)){return "dynamic";}
		if(preg_match("#-pool.*?\.sccoast\.net$#", $www)){return "dynamic";}
		if(preg_match("#\.uio\.satnet\.net$#", $www)){return "dynamic";}
		if(preg_match("#\.hawaiiantel\.net$#", $www)){return "dynamic";}
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
		if(preg_match("#\.homedns\.org$#", $www)){return "dynamic";}
		if(preg_match("#\.dyndns\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#\.dyndns-.*?\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#[0-9]+\.serverel\.net$#", $www)){return "dynamic";}
		if(preg_match("#\.dip\.t-dialin\.net$#", $www)){return "dynamic";}
		if(preg_match("#\.pool\.telekom\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#\.adsl\.tpnet\.[a-z]+$#", $www)){return "dynamic";}
		if(preg_match("#\.(dynamicip|adsl-dyn|dsl|dsl-dynamic)\.#", $www)){return "dynamic";}
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
	}
	
	
	private function travel($www){
		if(strpos($www, "voyages")>0){return "recreation/travel";}
		if(preg_match("#\.expedia\.fr$#", $www)){return "recreation/travel";}
		if(preg_match("#^travel\..*?$#", $www)){return "recreation/travel";}
		if(preg_match("#\.chatel\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#-travel\.#", $www)){return "recreation/travel";}
		if(preg_match("#travel-.*?\.#", $www)){return "recreation/travel";}
		if(strpos($www, "trivago.com")>0){return "recreation/travel";}
		if(preg_match("#\.voyage\.#", $www)){return "recreation/travel";}
		if(preg_match("#\.voyages\.#", $www)){return "recreation/travel";}
		if(preg_match("#^hotel-[a-z]+#", $www)){return "recreation/travel";}
		if(preg_match("#^hotels-[a-z\.]+#", $www)){return "recreation/travel";}
		if(preg_match("#^hotel[a-z]+\.[a-z]+$#", $www)){return "recreation/travel";}
		if(preg_match("#\.isango\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#\.aeroport\.fr$#", $www)){return "recreation/travel";}
		if(preg_match("#\.hotels\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#^voyage.+?#", $www)){return "recreation/travel";}
		if(preg_match("#^voyages.+?#", $www)){return "recreation/travel";}
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
		if(preg_match("#gite.*?-de-france#", $www)){return "recreation/travel";}
		if(preg_match("#\.emirates\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#\.travimp\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#\.airplus\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#\.disneylandparis\.com$#", $www)){return "recreation/travel";}
		if(preg_match("#\.atraveo\.[a-z]+$#", $www)){return "recreation/travel";}
		if(preg_match("#\.tourinsoft\.eu$#", $www)){return "recreation/travel";}
		
		if(preg_match("#(holiday|transpole|vacances|voyage|hoteliere|travel)#", $www)){return "recreation/travel";}
	}
	
	
	function news($www){
		if(preg_match("#-news\.[a-z]+$#",$www)){return "news";}
		if(preg_match("#\.orbyt\.[a-z]+$#",$www)){return "news";}
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
		if(preg_match("#\.kijiji\.[a-z]+$#", $www)){return "smallads";}
		if(preg_match("#^kijiji\.[a-z]+$#", $www)){return "smallads";}
		if(preg_match("#\.tboks\.com$#", $www)){return "smallads";}
		if(preg_match("#\.mundoanuncio\.[a-z\.]+$#", $www)){return "smallads";}
		if(preg_match("#\.adicio\.com$#", $www)){return "smallads";}
		if(preg_match("#\.freecycle\.org$#", $www)){return "smallads";}
		if(preg_match("#\.slando\.[a-z\.]+$#", $www)){return "smallads";}
		if(preg_match("#^avito\.[a-z\.]+$#", $www)){return "smallads";}
		if(preg_match("#\.avito\.[a-z\.]+$#", $www)){return "smallads";}
	}
	
	private function realestate($www){
		if(preg_match("#\.century21\.fr$#", $www)){return "finance/realestate";}
		if(preg_match("#\.poliris\.com$#", $www)){return "finance/realestate";}
		if(preg_match("#^sahlm[0-9]+\.fr$#", $www)){return "finance/realestate";}
		if(preg_match("#^laforet-immobilier-.*?\.[a-z]+#", $www)){return "finance/realestate";}
		if(preg_match("#\.kyero\.com$#", $www)){return "finance/realestate";}
		if(preg_match("#\.cybelagence\.net#", $www)){return "finance/realestate";}
		if(strpos($www, "realestate")){return "finance/realestate";}
		if(preg_match("#^realestate\.#", $www)){return "finance/realestate";}
		if(preg_match("#habitat[0-9]+\.fr$#", $www)){return "finance/realestate";}	
		if(preg_match("#^opmhlm-.*?\.fr$#", $www)){return "finance/realestate";}	
		if(preg_match("#\.img.*?-otodom\..*?$#", $www)){return "finance/realestate";}	
		if(preg_match("#\.seeff\.com$#", $www)){return "finance/realestate";}	
		if(preg_match("#(immobilier|logement|habitat|realestate)#", $www)){return "finance/realestate";}	
		if(preg_match("#\.trouver-un-logement-neuf\.com$#", $www)){return "finance/realestate";}	
	}
	
	private function banking($www){
		if(preg_match("#\.pncbank\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.citibank\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.bornpay\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.e-merchant\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.pepitastore\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.credit-agricole\.fr$#",$www)){return "finance/banking";}
		if(preg_match("#\.banquepopulaire\.fr$#",$www)){return "finance/banking";}
		if(preg_match("#\.societegenerale\.fr$#",$www)){return "finance/banking";}
		if(preg_match("#\.labanquepostale\.fr$#",$www)){return "finance/banking";}

		if(preg_match("#\.clickandbuy\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.postbank\.de$#",$www)){return "finance/banking";}
		if(preg_match("#\.isabel\.be$#",$www)){return "finance/banking";}
		if(preg_match("#\.americanexpress\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.cbc\.be$#",$www)){return "finance/banking";}	
		if(preg_match("#\.vend-o\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.natixis\.[a-z]+$#",$www)){return "finance/banking";}
		if(preg_match("#\.bil\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.paypal\.[a-z]+$#",$www)){return "finance/banking";}
		if(preg_match("#\.paypal\.[a-z]+\.[a-z]+$#",$www)){return "finance/banking";}
		if(preg_match("#\.americanexpress\.[a-z\.]+$#",$www)){return "finance/banking";}
		if(preg_match("#\.ca-languedoc\.[a-z]+$#",$www)){return "finance/banking";}
		if(preg_match("#^paypal\.[a-z\.]+$#",$www)){return "finance/banking";}
		if(preg_match("#\.micropayment\.[a-z\.]+$#",$www)){return "finance/banking";}
		if(preg_match("#\.mastercardconnect\.com$#",$www)){return "finance/banking";}
		if(preg_match("#\.bnpparibas\.[a-z\.]+$#",$www)){return "finance/banking";}
		if(preg_match("#\.caisse-epargne\.fr$#",$www)){return "finance/banking";}
		if(preg_match("#\.deutsche-bank\.de$#",$www)){return "finance/banking";}
		if(preg_match("#\.credit-agricole-.*?\.[a-z\.]+$#",$www)){return "finance/banking";}
		if(preg_match("#\.harrisbank\.[a-z\.]+$#",$www)){return "finance/banking";}
		
	}
	
	function GetCategories($www){
		$www=strtolower($www);
		$cat=null;
		$cat=$this->sslsites($www);if($cat<>null){return $cat;}
		$cat=$this->publicite($www);if($cat<>null){return $cat;}
		$cat=$this->porn($www);if($cat<>null){return $cat;}
		$cat=$this->Tracker($www);if($cat<>null){return $cat;}
		$cat=$this->mailing($www);if($cat<>null){return $cat;}
		$cat=$this->chat($www);if($cat<>null){return $cat;}
		$cat=$this->dynamic($www);if($cat<>null){return $cat;}
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
		$cat=$this->ScienceComputing($www);if($cat<>null){return $cat;}
		
		if(preg_match("#\.[a-z]rsi\.symantec\.com$#", $www)){return "cleaning";}
		if(preg_match("#\.malwarehelp\.org$#", $www)){return "cleaning";}
		if(preg_match("#(genealogist|genealogy)#", $www)){return "genealogy";}
		if(preg_match("#\.passion\.ru$#", $www)){return "mixed_adult";}
		if(preg_match("#(doctissimo|zoophilie)#", $www)){return "mixed_adult";}
		if(preg_match("#doctissimo\.fr$#", $www)){return "mixed_adult";}
		if(preg_match("#\.thechive\.com$#", $www)){return "mixed_adult";}
		if(preg_match("#^thechive\.com$#", $www)){return "mixed_adult";}
		if(preg_match("#^avocat[\-\.]#", $www)){return "justice";}
		if(preg_match("#^avocats-.+?\..+?$#", $www)){return "justice";}
		if(preg_match("#-avocat\.[a-z]+$#", $www)){return "justice";}
		if(preg_match("#^huissier(\.|\-)#", $www)){return "justice";}
		if(preg_match("#\.notaires\.fr$#", $www)){return "justice";}
		if(preg_match("#(avocat|notaire|huissier|justice|tribunal)#", $www)){return "justice";}
		
		if(preg_match("#(geologie|sciences)#", $www)){return "sciences";}
		if(preg_match("#\.researchgate\.net$#", $www)){return "sciences";}
		if(preg_match("#cnrs-.*?\.fr$#", $www)){return "sciences";}
		
		if(preg_match("#\.asso\.fr$#", $www)){return "associations";}
		if(preg_match("#^asso.*\.free\.fr$#", $www)){return "associations";}
		if(preg_match("#^mfr[0-9]+\.org$#", $www)){return "associations";}
		if(preg_match("#^associations\..*?\.[a-z]$#", $www)){return "associations";}
		if(preg_match("#\.asso-web\.com$#", $www)){return "associations";}
		if(preg_match("#^asso-.*?\.fr$#", $www)){return "associations";}
		if(preg_match("#-association\.[a-z]+$#", $www)){return "associations";}
		if(preg_match("#\..*?-telethon\.[a-z]+$#", $www)){return "associations";}
		if(preg_match("#-asso\.fr$#", $www)){return "associations";}
		if(preg_match("#\.vgwort.de$#", $www)){return "associations";}
		
		if(preg_match("#\.justjared\.com$#", $www)){return "celebrity";}
		if(preg_match("#\.bunte.de$#", $www)){return "celebrity";}
		
		if(preg_match("#\.fanpop\.com$#", $www)){return "celebrity";}
		if(preg_match("#\.zimbio.com$#", $www)){return "celebrity";}
		if(preg_match("#\.dailysingle\.com$#", $www)){return "celebrity";}
		
		if(preg_match("#\.swagbucks\.com$#", $www)){return "paytosurf";}
		if(preg_match("#\.bitterstrawberry\.com$#", $www)){return "paytosurf";}
		if(preg_match("#howtomakemoneyonline#", $www)){return "paytosurf";}
		
		if(preg_match("#\.voyance\.avigora\.com$#", $www)){return "astrology";}
		if(preg_match("#\.tarot\.com$#", $www)){return "astrology";}
		if(preg_match("#\.mangareader\.net$#", $www)){return "manga";}
		
		if(preg_match("#\.vodeclic\.[a-z]+$#", $www)){return "learning";}
		if(preg_match("#\.sprachcaffe\.com$#", $www)){return "learning";}
		if(preg_match("#\.telelangue\.com$#", $www)){return "learning";}
		if(preg_match("#\.englishtown\.com$#", $www)){return "learning";}
		if(preg_match("#formations#", $www)){return "learning";}
		if(preg_match("#\.afpa\.fr$#", $www)){return "learning";}
		
		
		if(strpos($www, "phobos.apple.com")>0){return "updatesites";}
		if(preg_match("#\.windowsupdate.com$#",$www)>0){return "updatesites";}
		if(preg_match("#\.update\.microsoft\.com$#",$www)>0){return "updatesites";}
		if(preg_match("#\.cloud\.avg\.com$#",$www)>0){return "updatesites";}
		if(preg_match("#\.avira-update\.[a-z]+$#",$www)>0){return "updatesites";}
		if(preg_match("#\.bandwidthplace\.com$#",$www)>0){return "updatesites";}
		if(preg_match("#[0-9]+.*?(mp|g)\.avast\.com$#",$www)>0){return "updatesites";}
		if(preg_match("#[a-z][0-9]+[a-z]+\.avast\.com$#",$www)>0){return "updatesites";}
		
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
		
		if(preg_match("#cosmetics#",$www)){return "cosmetics";}
		if(preg_match("#cosmetic#",$www)){return "cosmetics";}
		if(preg_match("#cosmetiques#",$www)){return "cosmetics";}
		if(preg_match("#cosmetique#",$www)){return "cosmetics";}
		if(preg_match("#^parfums-#",$www)){return "cosmetics";}
		if(preg_match("#(makeup|marionnaud|parfum)#",$www)){return "cosmetics";}
		if(preg_match("#\.nivea\.fr$#",$www)){return "cosmetics";}
		
		if(preg_match("#\.skinstore\.[a-z]+$#",$www)){return "cosmetics";}
		
		if(preg_match("#\.louisvuitton\.[a-z]+$#",$www)){return "luxury";}
		if(preg_match("#^bijoux#",$www)){return "luxury";}
		
		if(preg_match("#handicap.*?\.#",$www)){return "handicap";}
		if(preg_match("#adapei-[0-9]+\.fr$#",$www)){return "handicap";}

		if(preg_match("#\.flip-zone\.com$#",$www)){return "models";}
		
		if(preg_match("#\.tinyurl\.[a-z]+$#",$www)){return "redirector";}
		if(preg_match("#\.myrlk\.com$#",$www)){return "redirector";}
		if(preg_match("#\.serving-sys\.com$#", $www)){return "malware";}
		if(preg_match("#\.moycd\.ru$#", $www)){return "malware";}
		if(preg_match("#\.xxuz\.com$#", $www)){return "malware";}
		if(preg_match("#\.xxxl-cash\.net$#", $www)){return "malware";}
		if(preg_match("#\.torntv\.com$#", $www)){return "malware";}
		if(preg_match("#\.spamfighter\.com$#", $www)){return "malware";}
		if(preg_match("#\.ourtoolbar\.com$#", $www)){return "malware";}
		
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
		
		if(preg_match("#^extranet\..+?$#", $www)){return "webapps";}
		if(preg_match("#\.ingenie\.fr$#", $www)){return "webapps";}
		if(preg_match("#\.dmvalet\.com$#", $www)){return "webapps";}
		if(preg_match("#\.office\.microsoft\.[a-z]+$#", $www)){return "webapps";}
		if(preg_match("#\.microsoftemail\.com$#", $www)){return "phishing";}
		if(preg_match("#\.vkomdente\.ru$#", $www)){return "phishing";}
		if(preg_match("#\.officeapps\.#", $www)){return "webapps";}
		if(preg_match("#\.force\.com$#", $www)){return "webapps";}
		if(preg_match("#\.infusionsoft\.com$#", $www)){return "webapps";}
		if(preg_match("#\.sgizmo\.com$#", $www)){return "webapps";}
		if(preg_match("#\.appspot\.com$#", $www)){return "webapps";}
		if(preg_match("#google-analytics\.#", $www)){return "webapps";}
		if(preg_match("#\.sharethis\.com$#", $www)){return "webapps";}
		if(preg_match("#\.questionmarket\.com$#", $www)){return "webapps";}

		
		if(preg_match("#literie(\-|\.)#", $www)){return "housing/accessories";}
		if(preg_match("#\.fly\.fr$#", $www)){return "housing/accessories";}
		if(preg_match("#mobilier-et-decoration#", $www)){return "housing/accessories";}
		if(preg_match("#\.cuisines-schmidt\.com$#", $www)){return "housing/accessories";}
		if(preg_match("#\.ikea\.[a-z]+$#", $www)){return "housing/accessories";}
		if(preg_match("#^cuisine-.+?\.#", $www)){return "housing/accessories";}
		if(preg_match("#cuisine\..+?#", $www)){return "housing/accessories";}
		if(preg_match("#leroymerlin\.[a-z]+#", $www)){return "housing/accessories";}
		if(preg_match("#\.lpo\.fr$#", $www)){return "animals";}
		if(preg_match("#\.ref-union\.org$#", $www)){return "hobby/other";}
		if(preg_match("#kalligraphie#", $www)){return "hobby/other";}
		if(preg_match("#(salledebain|poeleabois)#", $www)){return "housing/accessories";}
		
		
		
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
		
		if(preg_match("#(sellerie|marechalerie|cheveaux|equitation|equestre|horses|ecuries|horseware)#", $www)){return "horses";}
		if(preg_match("#^ecurie-#", $www)){return "horses";}
		if(preg_match("#\.racingpost\.com$#", $www)){return "horses";}
		
		
		if(preg_match("#(radio|webradio)#", $www)){return "webradio";}
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
		if(preg_match("#(jobsearch|offre-emploi|newjob)#", $www)){return "jobsearch";}
		if(preg_match("#\.jobsonline\.net$#", $www)){return "jobsearch";}
		if(preg_match("#\.viadeo\.com$#", $www)){return "jobsearch";}
		if(preg_match("#\.naukri\.com$#", $www)){return "jobsearch";}
		if(preg_match("#\.praca\.onet\.pl$#", $www)){return "jobsearch";}
		if(preg_match("#\.trabajando\.com$#", $www)){return "jobsearch";}
		if(preg_match("#\.monster\.[a-z]+$#", $www)){return "jobsearch";}	
		if(preg_match("#\.newjobs\.[a-z]+$#", $www)){return "jobsearch";}
		if(preg_match("#\.zonajobs\.[a-z\.]+$#", $www)){return "jobsearch";}
		if(preg_match("#\.profilsearch\.com$#", $www)){return "jobsearch";}
		if(preg_match("#\.ilconte\.ru$#", $www)){return "jobsearch";}
		
		
		if(strpos($www, "collegehumor.com")>0){return "recreation/humor";}
		if(preg_match("#\.bu2z\.com$#",$www)){return "recreation/humor";}
		if(preg_match("#\.cariboom\.com$#",$www)){return "weapons";}
		
		
		if(preg_match("#\.punchbowl\.com$#",$www)){return "gifts";}
		if(preg_match("#\.wonderbox\.com$#",$www)){return "gifts";}
		if(preg_match("#\.americangreetings\.com$#",$www)){return "gifts";}

		if(preg_match("#\.bankrate\.com$#",$www)){return "finance/moneylending";}
		if(preg_match("#\.cofinoga\.[a-z]+$#",$www)){return "finance/moneylending";}
		if(preg_match("#\.cetelem\.[a-z]+$#",$www)){return "finance/moneylending";}
		if(preg_match("#\.onicon\.ru$#",$www)){return "webplugins";} 
		
		if(preg_match("#\.talkinglayers\.com$#",$www)){return "webplugins";} 
		if(preg_match("#\.xmlrequest\.info$#",$www)){return "webplugins";} 
		
		if(preg_match("#\.petitionsduweb\.fr$#",$www)){return "politic";}
		if(preg_match("#\.cgt\.fr$#",$www)){return "politic";}
		if(preg_match("#(socialist|referendum|petition)#",$www)){return "politic";}
		
		
		if(preg_match("#(hack|crack|serialz).*?\.(blogspot|altervista).*?\.[a-z]+$#", $www)>0){return "hacking";}
		if(preg_match("#(soft|software).*?\.(blogspot|altervista).*?\.[a-z]+$#", $www)>0){return "hacking";}
		if(preg_match("#\.elhacker.net$#",$www)){return "hacking";}
		if(preg_match("#\.hackers\.[a-z\.]+$#",$www)){return "hacking";}
		if(preg_match("#(hacking|hacker|hackz|hackerz|crackz|serialz)#",$www)){return "hacking";}
		
		if(preg_match("#(beton|mortier|imprimerie|expert-comptable)#",$www)){return "industry";}
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
		if(preg_match("#\.mindvalley.[a-z\.]+$#",$www)){return "industry";}
		if(preg_match("#\.bunchball\.com$#",$www)){return "industry";}
		if(preg_match("#\.chat2deal\.com$#",$www)){return "industry";}
		if(preg_match("#\.nachtmann\.com$#",$www)){return "industry";}
		
		if(preg_match("#\.ancestry\.com$#",$www)){return "genealogy";}
		if(preg_match("#\.geneawiki\.com$#",$www)){return "genealogy";}
		
		if(preg_match("#\.generali\.fr$#",$www)){return "finance/insurance";}
		if(preg_match("#\.ameli\.fr$#",$www)){return "finance/insurance";}
		if(preg_match("#.*?-assurances\.[a-z]+$#",$www)){return "finance/insurance";}
		if(preg_match("#.*?-assurance\.[a-z]+$#",$www)){return "finance/insurance";}
		if(preg_match("#(mutuelle|assurance|insurance|allianz|swisslife)#",$www)){return "finance/insurance";}
		if(preg_match("#\.darva\.com$#",$www)){return "finance/insurance";}
		
				
		if(preg_match("#\.arkive\.org$#",$www)){return "nature";}
		if(preg_match("#\.wwf\.org\.uk$#",$www)){return "green";}
		if(preg_match("#\.durable\.com$#",$www)){return "green";}
		if(preg_match("#^crpf-(.*?)\.fr$#",$www)){return "green";}
		if(preg_match("#\.nationalgeographic\.[a-z]+$#",$www)){return "green";}
		
		
		if(preg_match("#^192\.168\.[0-9]+\.[0-9]+$#", $www)){return "internal";}
		if(preg_match("#^172\.[0-9]+\.[0-9]+\.[0-9]+$#", $www)){return "internal";}
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
		if(preg_match("#\.canal-plus\.com$#", $www)){return "webtv";}
		if(preg_match("#\.tf1\.fr$#", $www)){return "webtv";}
		if(preg_match("#\.chiens-de-france\.[a-z]+$#", $www)){return "hobby/pets";}
		if(preg_match("#(\.|\-)chiens(\.|\-)#", $www)){return "hobby/pets";}
		if(preg_match("#(labradors|malinois)#", $www)){return "hobby/pets";}
		
		
		
		if(preg_match("#\.monuments-nationaux\.fr+$#", $www)){return "culture";}
		if(preg_match("#^musee(\-|\.]).*?\.fr$#", $www)){return "culture";}
		if(preg_match("#^exposition-.*?\.[a-z]+$#", $www)){return "culture";}
		if(preg_match("#^culture-.*?\.[a-z]+$#", $www)){return "culture";}
		if(preg_match("#^opera-.+?\.[a-z]+$#", $www)){return "culture";}
		if(preg_match("#\.structurae\.[a-z]+$#", $www)){return "culture";}
		if(preg_match("#\.antoloji\.com$#", $www)){return "culture";}
		if(preg_match("#\.beniculturali\.it$#", $www)){return "culture";}
		if(preg_match("#(theatre|culturali|museum|musee|conservatoire)#", $www)){return "culture";}
		
		if(preg_match("#-rencontre#", $www)){return "dating";}
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
		if(preg_match("#\.lovezx\.ru$#", $www)){return "dating";}
		if(preg_match("#\.lovein\.kz$#", $www)){return "dating";}
		if(preg_match("#polishhearts\.[a-z]+$#", $www)){return "dating";}
		
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
		if(preg_match("#-soins-#", $www)){return "health";}
		if(preg_match("#(health|physiotherapie|psychiatrie|osteopath|neurologie|cardiologie|dermatology|opticien|dentiste|psychologue|antigymnastique)#", $www)){return "health";}
		if(preg_match("#-medical$#", $www)){return "medical";}
		if(preg_match("#medical-$#", $www)){return "medical";}
		
			
		if(preg_match("#\.aufeminin\.com$#", $www)){return "womanbrand";}
		if(preg_match("#\.prime-beaute\.com$#", $www)){return "womanbrand";}
		if(preg_match("#\.mmasculin\.com$#", $www)){return "womanbrand";}
		if(preg_match("#\.vogue.[a-z\.]+$#", $www)){return "womanbrand";}
		if(preg_match("#\.grazia\.fr$#", $www)){return "womanbrand";}
		if(preg_match("#\.gzmama\.com$#", $www)){return "womanbrand";}
		if(preg_match("#modeparis#", $www)){return "womanbrand";}
		if(preg_match("#\.vogue\.fr$#", $www)){return "womanbrand";}
		if(preg_match("#\.meltyfashion\.[a-z\.]+$#", $www)){return "womanbrand";}
		if(preg_match("#(fashion|beaute|mode|glamour)#", $www)){return "womanbrand";}
		
		if(preg_match("#\.inserm\.fr$#", $www)){return "sciences";}
		if(preg_match("#\.inria\.fr$#", $www)){return "sciences";}
		if(preg_match("#\.genethique\.[a-z]+$#", $www)){return "sciences";}
		if(preg_match("#\.ird\.fr$#", $www)){return "sciences";}
		if(preg_match("#\.cnrs\.fr$#", $www)){return "sciences";}
		if(preg_match("#\.inrialpes\.fr$#", $www)){return "sciences";}
		
		if(preg_match("#(wedding|mariage|marriage|married|noces)#", $www)){return "getmarried";}
		if(preg_match("#\.edressbridal\.com#", $www)){return "getmarried";}

		if(preg_match("#\.lg\.[a-z]+$#", $www)){return "electricalapps";}
		if(preg_match("#\.comhttp$#", $www)){return "reaffected";}
		if(preg_match("#\.cpom$#", $www)){return "reaffected";}
		
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
		if(preg_match("#(chaussure|timberland|clothes|clothing|vetement|marroquineri)#", $www)){return "clothing";}
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
		if(preg_match("#\.howards\.[a-z]+$#", $www)){return "clothing";}
		if(preg_match("#\.orchestra\.[a-z]+$#", $www)){return "clothing";}
		if(preg_match("#\.scarosso\.com+$#", $www)){return "clothing";}
		
		if(preg_match("#^newsletter\..+#", $www)){return "mailing";}
		if(preg_match("#purlsmail.com$#", $www)){return "mailing";}
		if(preg_match("#pompes.*funebres#", $www)){return "religion";}
		if(preg_match("#\.catholique\.fr$#", $www)){return "religion";}
		if(preg_match("#\.cef\.fr$#", $www)){return "religion";}
		if(preg_match("#funeral.*home#", $www)){return "religion";}
		if(preg_match("#\.netministry\.com$#", $www)){return "religion";}
		if(preg_match("#obseque#", $www)){return "religion";}
		if(preg_match("#\.mustafahosny\.com$#", $www)){return "religion";}
		
		
		if(preg_match("#allocine\.fr$#",$www)){return "movies";}
		if(preg_match("#\.logmein\.com$#",$www)){return "remote-control";}
		if(preg_match("#(fitness|magnetiseur|dieteticien)#",$www)){return "recreation/wellness";}
		if(preg_match("#psychologies\.com$#",$www)){return "recreation/wellness";}
		if(preg_match("#\.becomegorgeous\.com$#",$www)){return "recreation/wellness";}
		if(preg_match("#\.homestyler\.com$#",$www)){return "housing/doityourself";}
		if(preg_match("#\.deco-travaux\.com$#",$www)){return "housing/doityourself";}
		if(preg_match("#devis.*?travaux#",$www)){return "housing/doityourself";}
		if(preg_match("#\.ath\.cx$#",$www)){return "dynamic";}
		if(preg_match("#\.synology\.me$#",$www)){return "dynamic";}
		if(preg_match("#\.dyndns-mail\.com$#",$www)){return "dynamic";}
		 if(preg_match("#\.dyndns\.org$#",$www)){return "dynamic";}
		if(preg_match("#\.hidemyass\.com$#",$www)){return "proxy";}
		if(preg_match("#\.cameleo\.ru$#",$www)){return "proxy";}
		
		if(preg_match("#\.anonymsp\.ru$#",$www)){return "proxy";}
		if(preg_match("#ega3\.ru$#",$www)){return "proxy";}
		if(preg_match("#arendadorogo\.ru$#",$www)){return "proxy";}
		if(preg_match("#aradero\.ru$#",$www)){return "proxy";}
		if(preg_match("#serqus\.ru$#",$www)){return "proxy";}
		if(preg_match("#forfolks\.ru$#",$www)){return "proxy";}
		if(preg_match("#amaleto\.ru$#",$www)){return "proxy";}
		if(preg_match("#gredor\.ru$#",$www)){return "proxy";}
		if(preg_match("#parsekus\.ru$#",$www)){return "proxy";}
		if(preg_match("#raufen\.ru$#",$www)){return "proxy";}
		if(preg_match("#\.vkontakte\.ru$#", $www)){return "proxy";}
		if(preg_match("#\.vkontakte\.ru$#", $www)){return "proxy";}
		if(preg_match("#\.vkadre.ru$#", $www)){return "proxy";}
		if(preg_match("#\.vkomsakte\.ru$#", $www)){return "proxy";}
		if(preg_match("#\.vkontashe\.ru$#", $www)){return "proxy";}
		if(preg_match("#\.fkontakle\.ru$#", $www)){return "proxy";}
		if(preg_match("#\.vkvkvkvkvk\.ru$#", $www)){return "proxy";}	
		if(preg_match("#\.serqus\.ru$#", $www)){return "proxy";}	
		if(preg_match("#\.generalxy4\.ru$#", $www)){return "proxy";}
		if(preg_match("#\.blokunet\.ru$#", $www)){return "proxy";}
	

		
		if(preg_match("#\.electro-services[0-9]+\.[a-z]+$#", $www)){return "electricalapps";}

		if(preg_match("#\.vcst\.net$#", $www)){return "wine";}
		if(preg_match("#^wine-.*?\.[a-z\.]+$#", $www)){return "wine";}
		if(preg_match("#^vin-.*?\.[a-z\.]+$#", $www)){return "wine";}
		if(preg_match("#-wine-.*?\.[a-z\.]+$#", $www)){return "wine";}
		if(preg_match("#-vin-.*?\.[a-z\.]+$#", $www)){return "wine";}
		
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
		if(preg_match("#\.email-match\.com$#", $www)){return "marketingware";}
		if(preg_match("#\.lb-clients\.com$#", $www)){return "marketingware";}
		if(preg_match("#\.dolead\.com$#", $www)){return "marketingware";}
		

		if(strpos($www, ".schneider-electric.com")>0){return "industry";}
		if(preg_match("#\.sony-europe.com$#", $www)){return "industry";}
		if(preg_match("#\.afacerist\.ro$#", $www)){return "industry";}
		if(preg_match("#\.hubb\.fr$#", $www)){return "industry";}
		if(preg_match("#\.telenav\.com$#", $www)){return "industry";}
		if(strpos($www, ".lyonnaise-des-eaux.fr")>0){return "industry";} 
		if(strpos($www, ".danfoss.com")>0){return "industry";} 
		if(preg_match("#\.batiactu\.com$#", $www)){return "industry";}
		if(preg_match("#(telemarketing|charcuterie|electricite|europages)#", $www)){return "industry";}
		if(preg_match("#\.schunk\.com$#", $www)){return "industry";}
		if(preg_match("#\.advertory\.fr$#", $www)){return "industry";}
		if(preg_match("#\.samsung\.[a-z\.]+$#", $www)){return "industry";}
		if(preg_match("#^samsung\.[a-z\.]+$#", $www)){return "industry";}
		if(preg_match("#\.pro$#", $www)){return "industry";}
		if(preg_match("#\.datazug\.ch$#", $www)){return "industry";}
		if(preg_match("#\.surinternet.com$#", $www)){return "industry";}
	
		if(strpos($www, "weatherzone")>0){return "science/weather";}
		if(preg_match("#\.accu-weather\.com$#", $www)){return "science/weather";}
		if(preg_match("#\.lachainemeteo\.com$#", $www)){return "science/weather";}
		if(preg_match("#-meteo\.[a-z]+$#", $www)){return "science/weather";}
		if(preg_match("#^meteo\..+?#", $www)){return "science/weather";}
		if(preg_match("#^meteo-.*?\.[a-z]+#", $www)){return "science/weather";}
		if(preg_match("#\.meteofrance\.com$#", $www)){return "science/weather";}
		if(preg_match("#\.meteosun\.com$#", $www)){return "science/weather";}
		if(preg_match("#\.wetter\.com$#", $www)){return "science/weather";}
		if(preg_match("#\.meteo-.*?\.[a-z]+$#", $www)){return "science/weather";}
		if(preg_match("#\.wetter\.#", $www)){return "science/weather";}
		if(preg_match("#\.accuweather\.com$#", $www)){return "science/weather";}
		if(preg_match("#\.gismeteo\.[a-z]+$#", $www)){return "science/weather";}
		if(preg_match("#\.weather\.[a-z]+$#", $www)){return "science/weather";}
		if(preg_match("#^weather\.#", $www)){return "science/weather";}
		if(preg_match("#(\.|\-)(meteo|weather|wetter)(\.|\-)#", $www)){return "science/weather";}
		
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
		
		
		
		if(preg_match("#\.bankfirstcorp.com$#", $www)){return "industry";}
		
		
		if(preg_match("#\.prositehosting\.co\.uk$#", $www)){return "isp";}
		if(preg_match("#\.bouyguestelecom\.fr$#", $www)){return "isp";}
		if(preg_match("#\.onlinehome-server\.info$#", $www)){return "isp";}
		if(preg_match("#\.cdnplanet\.com$#", $www)){return "isp";}
		if(preg_match("#\.cdn77\.net$#", $www)){return "isp";}
		
		if(preg_match("#(clinique|hospital|hopital|hopitaux|polyclinique)#", $www)){return "hospitals";}
		if(preg_match("#ap-hop-paris\.fr$#", $www)){return "hospitals";}
		if(preg_match("#^clinique([-\.]+).+?\.$#", $www)){return "hospitals";}
		if(preg_match("#^chu-.+?\.fr$#", $www)){return "hospitals";}
		if(preg_match("#^chi-.+?\.fr$#", $www)){return "hospitals";}
		if(preg_match("#^chi[0-9]+\.fr$#", $www)){return "hospitals";}
		if(preg_match("#\.cgos\.info$#", $www)){return "hospitals";}
		
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
		if(preg_match("#\.funmehr\.com$#", $www)){return "society";}
		
		if(preg_match("#(aviation|aeroclub|avionique)#", $www)){return "automobile/planes";}
		if(preg_match("#covoiturage#", $www)){return "automobile/carpool";}
		
		
		if(preg_match("#\.picnik\.com$#", $www)){return "photo";}
		if(preg_match("#\.fotoflexer\.com$#", $www)){return "photo";}
		if(preg_match("#\.photobox\.[a-z\.]+$#", $www)){return "photo";}
		if(preg_match("#\.img-dpreview\.com$#", $www)){return "photo";}
		if(preg_match("#\.mypix\.com$#", $www)){return "photo";}
		if(preg_match("#\.bhphotovideo\.com$#", $www)){return "photo";}
		if(preg_match("#\.rapidstudio\.co\.za$#", $www)){return "photo";}
		if(preg_match("#\.flamingtext\.com$#", $www)){return "photo";}
		
		if(preg_match("#\.ryland\.com$#", $www)){return "housing/builder";}
		if(preg_match("#-batiment\.fr$#", $www)){return "housing/builder";}
		if(preg_match("#\.newlandcommunities\.com$#", $www)){return "housing/builder";}
		if(preg_match("#house.*?build#", $www)){return "housing/builder";}
		if(preg_match("#constructeur.*?maison#", $www)){return "housing/builder";}
		
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
		if(preg_match("#\.ovi\.com$#", $www)){return "maps";}
		if(preg_match("#\.nearmap\.[a-z]+$#", $www)){return "maps";}
		if(preg_match("#\.mappy\.[a-z]+#", $www)){return "maps";}
		if(preg_match("#\.ups\.com$#", $www)){return "transport";}
		if(preg_match("#carriers#", $www)){return "transport";}
		if(preg_match("#\.ptrss\.com$#", $www)){return "transport";}
		if(preg_match("#^transport-.*?\.[a-z]+#", $www)){return "transport";}
		if(preg_match("#\.chronopost\.[a-z]+$#", $www)){return "transport";}
		if(preg_match("#\.modlife\.com$#", $www)){return "celebrity";}
		if(preg_match("#\.closermag\.[a-z]+$#", $www)){return "celebrity";}
		if(preg_match("#\.celebritywise\.com$#", $www)){return "celebrity";}
		
		if(preg_match("#constructeur-de-maisons#", $www)){return "housing/builders";}
		if(preg_match("#\.cargocollective\.coms#", $www)){return "hobby/arts";}
		
		if(preg_match("#\.51\.la$#", $www)){return "spyware";}
		if(preg_match("#\.maxonline\.com\.sg$#", $www)){return "spyware";}
		if(preg_match("#(paroisse|religious|religion|funeraire)#", $www)){return "religion";}
		if(preg_match("#\.meshfriend\.com$#", $www)){return "reaffected";}
		if(preg_match("#^bible\.[a-z]+$#", $www)){return "reaffected";}
		
		
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
		if(preg_match("#(vehicule|volkswagen|voiture|eurotyre|automobile|gonka|mazda)#", $www)){return "automobile/cars";}
		if(preg_match("#\.chevroletconnect\.[a-z]+$#", $www)){return "automobile/cars";}
		if(preg_match("#\.img.*?-otomoto\.*$#", $www)){return "automobile/cars";}
		if(preg_match("#\.auto-noproblem\.com$#", $www)){return "automobile/cars";}	
		if(preg_match("#\.rc-car-online\.[a-z]+$#", $www)){return "automobile/cars";}
		
		
		if(preg_match("#\.motoblouz\.com$#", $www)){return "automobile/bikes";}
		if(preg_match("#harley([\-|\.])davidson#", $www)){return "automobile/bikes";}
		if(preg_match("#\.321moto\.com$#", $www)){return "automobile/bikes";}
		if(preg_match("#^honda\.[a-z\.]+$#", $www)){return "automobile/bikes";}
		if(preg_match("#\.honda\.[a-z\.]+$#", $www)){return "automobile/bikes";}
		if(preg_match("#\.[0-9](moto|auto)\.[a-z]+$#", $www)){return "automobile/bikes";}
		if(preg_match("#\.harley-davidson\.com$#", $www)){return "automobile/bikes";}
		if(preg_match("#\.mobcustom\.[a-z]+$#", $www)){return "automobile/bikes";}
		if(preg_match("#(motospiece|honda\.|bikes)#", $www)){return "automobile/bikes";}
		
		
		if(preg_match("#\.persona\.org$#", $www)){return "browsersplugins";}
		if(preg_match("#eleicoes.*?\.[a-z]+#", $www)){return "politic";}
		
		if(preg_match("#^garage-.*?\.fr$#", $www)){return "automobile/cars";}
		if(preg_match("#^brasserie-#",$www)){return "recreation/nightout";}
		if(preg_match("#\.myticket\.[a-z]+#", $www)){return "recreation/nightout";}
		
		
		if(preg_match("#(cooking|recette|marmiton|cuisiner|culinaire)#", $www)){return "hobby/cooking";}
		if(preg_match("#^recettes-.*?\.fr$#", $www)){return "hobby/cooking";}
		if(preg_match("#\.cuisineblog\.fr$#", $www)){return "hobby/cooking";}
		if(preg_match("#\.meltyfood\.fr$#", $www)){return "hobby/cooking";}
		if(preg_match("#\.rezeptwiese\.[a-z]+$#", $www)){return "hobby/cooking";}
		if(preg_match("#\.atelierdeschefs\.[a-z]+$#", $www)){return "hobby/cooking";}
		
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
		if(preg_match("#\.hiprotections\.cc$#", $www)){return "malware";}
		if(preg_match("#\.zrdt6-null\.zv#", $www)){return "reaffected";}
		
		if(preg_match("#(books|livres)#", $www)){return "books";}
		if(preg_match("#(enfant|children)#", $www)){return "children";}
		if(preg_match("#(informatique|computing|ordinateur|computer)#", $www)){return "science/computing";}
		
		
		$cat=$this->Tracker($www);if($cat<>null){return $cat;}
		$cat=$this->mailing($www);if($cat<>null){return $cat;}
		
	}
}