<?php
class squid_catz_shopping{
	
	function parse($www){
		if(strpos($www, "multiply.com")>0){return "shopping";}
		if(strpos($www, ".teambuy.com")>0){return "shopping";}
		if(preg_match("#^shop\.#",$www)){return "shopping";}
		if(preg_match("#istore\.pl$#",$www)){return "shopping";}
		if(preg_match("#\.playserver1\.com$#",$www)){return "shopping";}
		if(preg_match("#marktplaats\.nl$#",$www)){return "shopping";}
		if(preg_match("#twenga\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#speurders\.nl$#",$www)){return "shopping";}
		if(preg_match("#ebayclassifieds\.com$#",$www)){return "shopping";}
		if(preg_match("#\.lbox\.me$#",$www)){return "shopping";} 
		if(preg_match("#\.shopping\.com$#",$www)){return "shopping";} 
		if(preg_match("#\.volusion\.com$#",$www)){return "shopping";}  
		if(preg_match("#shopping\..+?#",$www)){return "shopping";} 
		if(preg_match("#\.vente-privee\.com$#",$www)){return "shopping";} 
		if(preg_match("#\.graysonline\.com\.com$#",$www)){return "shopping";}
		if(preg_match("#\.3suisses\.fr$#",$www)){return "shopping";}  
		if(preg_match("#\.laredoute\.fr$#",$www)){return "shopping";}
		if(preg_match("#\.globalsources\.com$#",$www)){return "shopping";}
		if(preg_match("#\.shoppingshadow\.com$#",$www)){return "shopping";}
		if(preg_match("#\.amazon\.com$#",$www)){return "shopping";}
		if(preg_match("#\.rueducommerce\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#\.ebay\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#\.vozavi\.com$#",$www)){return "shopping";}
		if(preg_match("#\.galerieslafayette\.com$#",$www)){return "shopping";}
		if(preg_match("#\.faller\.fr$#",$www)){return "shopping";}
		if(preg_match("#\.shopmountain\.com$#",$www)){return "shopping";}
		if(strpos($www, "discount")>0){return "shopping";}
		if(strpos($www, "market")>0){return "shopping";}
		if(preg_match("#\.letsbonus\.com$#",$www)){return "shopping";}
		if(preg_match("#\.thekrazycouponlady\.com$#",$www)){return "shopping";}
		if(preg_match("#\.lbdn\.com$#",$www)){return "shopping";}
		if(preg_match("#\.laboutiquedunet\.com$#",$www)){return "shopping";}
		if(preg_match("#\.plus\.de$#",$www)){return "shopping";}
		if(preg_match("#\.ebayimg\.com$#",$www)){return "shopping";}
		if(preg_match("#\.taobaocdn\.com$#",$www)){return "shopping";}
		if(preg_match("#\.ebaystatic\.com$#",$www)){return "shopping";}
		if(preg_match("#\.ebaydesc\.com$#",$www)){return "shopping";}
		if(preg_match("#\.images-amazon\.com$#",$www)){return "shopping";}
		if(preg_match("#\.ssl-images-amazon\.com$#",$www)){return "shopping";}
		if(preg_match("#\.tinydeal\.com$#",$www)){return "shopping";}
		if(preg_match("#\.static-nextag\.com$#",$www)){return "shopping";}
		if(preg_match("#\.shopfactory\.com$#",$www)){return "shopping";}
		if(preg_match("#\.china-direct-buy\.com$#",$www)){return "shopping";}
		if(preg_match("#\.rozetka\.ua$#",$www)){return "shopping";}
		if(preg_match("#\.electrocomponents\.com$#",$www)){return "shopping";}
		if(preg_match("#\.taobao\.com$#",$www)){return "shopping";}
		if(preg_match("#\eprice\.com\.tw$#",$www)){return "shopping";}
		if(preg_match("#\.pepitastore\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#\.loveandtoast\.com$#",$www)){return "shopping";}
		if(preg_match("#\.troc\.com$#",$www)){return "shopping";}
		if(preg_match("#\.bonprix\.fr$#",$www)){return "shopping";}
		if(preg_match("#\.tf1conso\.fr$#",$www)){return "shopping";}
		if(preg_match("#\.carrefour\.com$#",$www)){return "shopping";}
		if(preg_match("#\.darty\.fr$#",$www)){return "shopping";}
		if(preg_match("#\.auchan\.fr$#",$www)){return "shopping";}
		if(preg_match("#\.apreslachat\.com$#",$www)){return "shopping";}
		if(preg_match("#\.tiu\.ru$#",$www)){return "shopping";}
		if(preg_match("#\.belibu\.com$#",$www)){return "shopping";}
		if(preg_match("#\.grouponcdn\.com$#",$www)){return "shopping";}
		if(preg_match("#\.ebay\.co\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#\.oxatis\.com$#",$www)){return "shopping";}
		if(preg_match("#\.webalogues\.fr$#",$www)){return "shopping";}
		if(preg_match("#\.e-merchant\.com$#",$www)){return "shopping";}
		if(preg_match("#\.brocabrac\.fr$#",$www)){return "shopping";}
		if(preg_match("#\.pas-cher\.fr$#",$www)){return "shopping";}
		if(preg_match("#\.ecomm-search\.com$#",$www)){return "shopping";}
		if(preg_match("#\.shoptime\.cl$#",$www)){return "shopping";}
		if(preg_match("#\.acnshop\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#\.shopacnrep\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#\.groupon\.co\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#\.groupon\.[a-z]+$#",$www)){return "shopping";}
		if(preg_match("#\.alibaba\.com$#",$www)){return "shopping";}
		if(preg_match("#\.ebayrtm\.com$#",$www)){return "shopping";}
		if(preg_match("#\.mercadolivre\.com\.br#",$www)){return "shopping";}
	}
	
}