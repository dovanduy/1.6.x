<?php
class squid_catz_publicite{
	
	function parse($www){
		if(strpos($www, "partners-aedgency.com")>0){return "publicite";}
		if(strpos($www, "doubleclick.net")>0){return "publicite";}
		if(strpos($www, ".linkbucks.com")>0){return "publicite";}
		if(strpos($www, "smartadserver.com")>0){return "publicite";}
		if(strpos($www, ".tubemogul.com")>0){return "publicite";}
		if(strpos($www, ".criteo.com")>0){return "publicite";}
		if(strpos($www, ".orangeads.")>0){return "publicite";}
		if(strpos($www, ".fwmrm.net")>0){return "publicite";}
		if(strpos($www, ".media6degrees.com")>0){return "publicite";}
		if(strpos($www, ".everyscreenmedia.com")>0){return "publicite";}
		if(strpos($www, ".ad6media.fr")>0){return "publicite";}
		if(preg_match("#\.adserverpub\.com$#", $www)){return "publicite";}
		if(preg_match("#\.ad42\.com$#", $www)){return "publicite";}
		if(preg_match("#^advertising.#", $www)){return "publicite";}
		if(preg_match("#^ads\.#",$www)){return "publicite";}
		if(preg_match("#^adv[0-9]+\..+?#",$www)){return "publicite";}
		if(preg_match("#^pubs\.#",$www)){return "publicite";}
		if(preg_match("#^adserve\.#",$www)){return "publicite";}
		if(preg_match("#^adserver\.#",$www)){return "publicite";}
		if(preg_match("#^adserver[0-9]+\.#",$www)){return "publicite";}	
		if(preg_match("#^ads[0-9]+\.#",$www)){return "publicite";}	
		if(preg_match("#^img\.ads\.$#", $www)){return "publicite";}	
		if(preg_match("#\.llnwd\.net$#", $www)){return "publicite";}
		if(preg_match("#\.intellitxt\.com$#", $www)){return "publicite";}	
		if(preg_match("#\.gameleads\.ru$#", $www)){return "publicite";}		
		if(preg_match("#\.affinity\.com$#", $www)){return "publicite";}	
		if(preg_match("#\.adnxs\.com$#", $www)){return "publicite";}
		if(preg_match("#\.alenty\.com$#", $www)){return "publicite";}		
		if(preg_match("#\.linter\.fr$#", $www)){return "publicite";}
		if(preg_match("#^affiliates\..*#", $www)){return "publicite";}
		if(preg_match("#\.googlesyndication\.com$#", $www)){return "publicite";}
		if(preg_match("#\.googleadservices.com$#", $www)){return "publicite";}	
		if(preg_match("#^banner\..+?$#", $www)){return "publicite";}	
		if(preg_match("#^banners\..+?$#", $www)){return "publicite";}
		if(preg_match("#^banniere\..+?$#", $www)){return "publicite";}
		if(preg_match("#^bannieres\..+?$#", $www)){return "publicite";}			
		if(preg_match("#^banner[0-9]+\..+?$#", $www)){return "publicite";}
		if(preg_match("#^adserving\..+?$#", $www)){return "publicite";}	
		if(preg_match("#\.adsafe.fr$#", $www)){return "publicite";}	
		if(preg_match("#\.contentcastsyndication\.com$#", $www)){return "publicite";}	
		if(preg_match("#\.zxxds\.net$#", $www)){return "publicite";}
		if(preg_match("#\.clickexperts\.net$#", $www)){return "publicite";}
		if(preg_match("#\.tradeads\.eu$#", $www)){return "publicite";}
		if(preg_match("#\.ivwbox\.de$#", $www)){return "publicite";}
		if(preg_match("#adsyndication\.msn\.com$#", $www)){return "publicite";}
		if(preg_match("#\.visualdna\.com$#", $www)){return "publicite";}
		if(preg_match("#\.yx8\.us$#", $www)){return "publicite";}
		if(preg_match("#\.paperg\.com$#", $www)){return "publicite";}
		if(preg_match("#\.relestar\.com$#", $www)){return "publicite";}
		if(preg_match("#\.clickbooth.com$#", $www)){return "publicite";}
		if(preg_match("#\.upickem\.net$#", $www)){return "publicite";}
		if(preg_match("#\.contextmatters\.de$#", $www)){return "publicite";}
		if(preg_match("#\.indieclick\.com$#", $www)){return "publicite";}
		if(preg_match("#\.monetate\.net$#", $www)){return "publicite";}
		if(preg_match("#\.sometrics\.com$#", $www)){return "publicite";}
		if(preg_match("#\.doubleadv\.com$#", $www)){return "publicite";}
		if(preg_match("#\.lomadee\.com$#", $www)){return "publicite";}
		if(preg_match("#\.auditude\.com$#", $www)){return "publicite";}
		if(preg_match("#\.mystat-in\.net$#", $www)){return "publicite";}
		if(preg_match("#\.adsbyisocket\.com$#", $www)){return "publicite";}
		if(preg_match("#\.infolinks\.com$#", $www)){return "publicite";}
		if(preg_match("#\.adshost[0-9]+\.com$#", $www)){return "publicite";}
		if(preg_match("#\.advolution\.de$#", $www)){return "publicite";}
		if(preg_match("#\.taggify\.net$#", $www)){return "publicite";}
		if(preg_match("#^adsrv\..+#", $www)){return "publicite";}
		if(preg_match("#\.wysistat\.com$#", $www)){return "publicite";}
		if(preg_match("#-adserver\..+?$#", $www)){return "publicite";}
		if(preg_match("#\.bannerhosting\.[a-z]+$#", $www)){return "publicite";}
		if(preg_match("#\.elephant-traffic\.[a-z]+$#", $www)){return "publicite";}
		if(preg_match("#^adsimage[s\.]#", $www)){return "publicite";}
		if(preg_match("#^adstxt\..+?$#", $www)){return "publicite";}
		if(preg_match("#freeads#", $www)){return "publicite";}
		if(preg_match("#publicite#", $www)){return "publicite";}
		if(preg_match("#^adserv[0-9]+\..+?$#", $www)){return "publicite";}
		if(preg_match("#\.maxiad\.de$#", $www)){return "publicite";}
		if(preg_match("#\.adshost[0-9]+\.com$#", $www)){return "publicite";}
		if(preg_match("#\.adserver\.yahoo\.com$#", $www)){return "publicite";}
		if(preg_match("#\.adspirit\.net$#", $www)){return "publicite";}
		if(preg_match("#\.adclear\.net$#", $www)){return "publicite";}
		if(preg_match("#\.ad-srv\.net$#", $www)){return "publicite";}
		if(preg_match("#\.12mlbe\.com$#", $www)){return "publicite";}
		if(preg_match("#\.dotandad\.com$#", $www)){return "publicite";}
		if(preg_match("#\.medialand\.ru$#", $www)){return "publicite";}
		if(preg_match("#\.cprpt\.com$#", $www)){return "publicite";}
		if(preg_match("#\.zarabotki\.ru$#", $www)){return "publicite";}
		if(preg_match("#\.wlresources.com$#", $www)){return "publicite";}
		if(preg_match("#^ad[0-9]+\..+#", $www)){return "publicite";}
		if(preg_match("#^ads[0-9]+\..+#", $www)){return "publicite";}
		if(preg_match("#^adcl\..+#", $www)){return "publicite";}
		if(preg_match("#^adimg\..+#", $www)){return "publicite";}
		if(preg_match("#^adserving\..+#", $www)){return "publicite";}
		if(preg_match("#^adv\..+#", $www)){return "publicite";}
		if(preg_match("#^ad4game\.com$#", $www)){return "publicite";}
		if(preg_match("#\.toboads\.com$#", $www)){return "publicite";}
		if(preg_match("#\.adjunky\.com$#", $www)){return "publicite";}
		if(preg_match("#\.buysellads\.com$#", $www)){return "publicite";}
		if(preg_match("#\.bsuiteads\.com$#", $www)){return "publicite";}
		if(preg_match("#\.redintelligence\.net$#", $www)){return "publicite";}
		if(preg_match("#\.rsadvert\.ru$#", $www)){return "publicite";}
		if(preg_match("#\.adsz[0-9]+\.net$#", $www)){return "publicite";}
		if(preg_match("#\.adresult-sp\.jp$#", $www)){return "publicite";}
		if(preg_match("#\.ad-inside\.com$#", $www)){return "publicite";}
		if(preg_match("#\.adpub\.mobi$#", $www)){return "publicite";}
		if(preg_match("#\.effiliation\.com$#", $www)){return "publicite";}
		if(preg_match("#\.affilnet\.net$#", $www)){return "publicite";}
		if(preg_match("#\.tlcrewards\.com$#", $www)){return "publicite";}
		if(preg_match("#\.banizi\.com$#", $www)){return "publicite";}
		if(preg_match("#\.is-teledata\.com$#", $www)){return "publicite";}
		if(preg_match("#\.affilnet\.net$#", $www)){return "publicite";}
		if(preg_match("#\.ero-advertisings\.com$#", $www)){return "publicite";}
		if(preg_match("#\.jeuconcours\.fr$#", $www)){return "publicite";}
		if(preg_match("#\.fun2quiz\.com$#", $www)){return "publicite";}
		if(preg_match("#\.seedcornppc-xml\.com$#", $www)){return "publicite";}
		if(preg_match("#\.qualitycpv-xml\.com$#", $www)){return "publicite";}
		if(preg_match("#\.ezanga-xml\.com$#", $www)){return "publicite";}
		if(preg_match("#\.falktec\.com$#", $www)){return "publicite";}
		if(preg_match("#\.publicidad\.net$#", $www)){return "publicite";}
		if(preg_match("#\.mmads.mobi$#", $www)){return "publicite";}
		if(preg_match("#\.grt01\.com$#", $www)){return "publicite";}
		if(preg_match("#\.adsmogo\.mobi$#", $www)){return "publicite";}
		if(preg_match("#^affiliates\.$#", $www)){return "publicite";}
		if(preg_match("#^affiliate\.$#", $www)){return "publicite";}
		if(preg_match("#\.noraust\.com$#", $www)){return "publicite";}
		if(preg_match("#\.outdoornetwork\.com$#", $www)){return "publicite";}
		if(preg_match("#\.web-boosting\.net$#", $www)){return "publicite";}
		if(preg_match("#\.bet[a-z]+affiliates\.[a-z]+$#", $www)){return "publicite";}
		if(preg_match("#\.banner(s|\-)service\.[a-z]+$#", $www)){return "publicite";}
		if(preg_match("#\.domaingateway\.com$#", $www)){return "publicite";}
		if(preg_match("#\.adnxs\.com$#", $www)){return "publicite";}
		if(preg_match("#\.zenoviaexchange\.com$#", $www)){return "publicite";}		
	}
	
}