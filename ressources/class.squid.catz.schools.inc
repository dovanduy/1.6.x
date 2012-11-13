<?php
class squid_catz_schools{
	
	function parse($www){
		if(preg_match("#^lycee-#", $www)){return "recreation/schools";}
		if(preg_match("#\.edu$#", $www)){return "recreation/schools";}
		if(preg_match("#ucl\.ac\.be$#", $www)){return "recreation/schools";}
		if(preg_match("#\.uc\.pt$#", $www)){return "recreation/schools";}
		if(preg_match("#\.edu\.tw$#", $www)){return "recreation/schools";}
		if(preg_match("#\.edu\.my$#", $www)){return "recreation/schools";}
		if(preg_match("#\.edu\.au$#", $www)){return "recreation/schools";}
		if(preg_match("#\.ac-[a-z\-]+\.fr$#", $www)){return "recreation/schools";}	
		if(preg_match("#\.studentmidwife.net$#", $www)){return "recreation/schools";}	
		if(preg_match("#\.studyisland\.com$#", $www)){return "recreation/schools";}
		if(preg_match("#\.educ.ar$#", $www)){return "recreation/schools";}	
		if(preg_match("#\.educaedu\.com\.ar$#", $www)){return "recreation/schools";}
		if(preg_match("#\.bc\.ca$#", $www)){return "recreation/schools";}	
		if(preg_match("#\.hec\.fr$#", $www)){return "recreation/schools";}	
		if(preg_match("#\.helium\.com$#", $www)){return "recreation/schools";}
		if(preg_match("#\.sesamath\.net$#", $www)){return "recreation/schools";}
		if(preg_match("#\.iufm\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.univ-paris-diderot\.fr$#", $www)){return "recreation/schools";}	
		if(preg_match("#\.univ-(.*?)\.fr$#", $www)){return "recreation/schools";}		
		if(preg_match("#\.u-paris[0-9]+\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.parisdescartes\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.ens-lyon\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.sciences-po\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.ec-lyon\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.u-psud\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.em-lyon\.com$#", $www)){return "recreation/schools";}
		if(preg_match("#\.uwaterloo\.ca$#", $www)){return "recreation/schools";}
		if(preg_match("#\.utoronto\.ca$#", $www)){return "recreation/schools";}
		if(preg_match("#soutien-scolaire#", $www)){return "recreation/schools";}
		if(preg_match("#\.u-bordeaux\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#resultats.*?brevet.*?\.[a-z]+$#", $www)){return "recreation/schools";}
		if(preg_match("#\.rug\.nl$#", $www)){return "recreation/schools";}
		if(preg_match("#\.u-bordeaux[0-9]+\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.uvsq\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.unilim\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.uvt\.nl$#", $www)){return "recreation/schools";}
		if(preg_match("#\.omnivox\.ca$#", $www)){return "recreation/schools";}
		if(preg_match("#\.usherbrooke.ca$#", $www)){return "recreation/schools";}
		if(preg_match("#^crous-(.+?)\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#^cnous-(.+?)\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.crous-(.+?)\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.glyndwr\.ac\.uk$#", $www)){return "recreation/schools";}
		if(preg_match("#\.educagri\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#cfametiers[0-9]+\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.jussieu\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.uwo\.ca$#", $www)){return "recreation/schools";}
		if(preg_match("#\.emgcdn\.net$#", $www)){return "recreation/schools";}
		if(preg_match("#\.ensmp\.fr$#", $www)){return "recreation/schools";}
		if(preg_match("#\.unipd\.it$#", $www)){return "recreation/schools";}
		if(preg_match("#\.unip\.br$#", $www)){return "recreation/schools";}
		if(preg_match("#\.edu\.pl$#", $www)){return "recreation/schools";}
	}
	
}