<?php

class array_category_shopping{
	
	
	
	function return_array($cleanedkey=false){
		$MAIN["clessitrada.de"]=true;
		$MAIN["privalia.com"]=true;
		$MAIN["twenga.com"]=True;
		$MAIN["twenga.fr"]=True;
		$MAIN["grouponcdn.com"]=True;
		$MAIN["groupon.fr"]=True;
		$MAIN["girlshopes.com"]=True;
		$MAIN["images-amazon.com"]=True;
		$MAIN["amazon.com"]=True;
		$MAIN["amazon.fr"]=True;
		$MAIN["amazon.co.jp"]=True;
		$MAIN["amazon.de"]=True;
		$MAIN["amazon.it"]=True;
		$MAIN["amazon.eu"]=True;
		$MAIN["ssl-images-amazon.com"]=True;
		$MAIN["heureka.cz"]=True;
		$MAIN["made-in-china.com"]=True;
		$MAIN["mistergooddeal.com"]=True;
		$MAIN["einsundeinsshop.de"]=True;
		$MAIN["amazonwebstore.com"]=True;
		$MAIN["mybigcommerce.com"]=True;
		$MAIN["corecommerce.com"]=True;
		$MAIN["grandsavediscount.com"]=True;
		$MAIN["shop.by"]=True;
		$MAIN["theshoppe.com"]=True;
		$MAIN["taobao.com"]=True;
		$MAIN["safeshopper.com"]=True;
		$MAIN["store.yahoo.net"]=True;
		$MAIN["e-shop.gr"]=True;
		$MAIN["multiply.com"]=True;
		$MAIN["centrecommercial-auchan.fr"]=True;
		$MAIN["heureka.sk"]=True;
		$MAIN["alibaba.com"]=True;
		$MAIN["bigcartel.com"]=True;
		$MAIN["kingeshop.com"]=True;
		$MAIN["e-shop.info"]=True;
		$MAIN["storenvy.com"]=True;
		$MAIN["bigstep.com"]=True;
		$MAIN["bizland.com"]=True;
		$MAIN["iai-shop.com"]=True;
		$MAIN["stores.yahoo.net"]=True;
		$MAIN["shopserve.jp"]=True;
		$MAIN["iai-shop.com"]=True;
		$MAIN["tiu.ru"]=True;
		$MAIN["sluzby.cz"]=True;
		$MAIN["mixmarket.biz"]=True;
		$MAIN["mercadoshops.com.ar"]=True;
		$MAIN["mercadolibre.com.ar"]=True;
		$MAIN["carrefour.fr"]=True;
		$MAIN["franceprix.fr"]=True;
		$MAIN["twenga.com"]=True;
		$MAIN["twenga.fr"]=True;
		$MAIN["cdiscount.com"]=True;
		$MAIN["uaprom.net"]=True;
		$MAIN["prom.ua"]=True;
		$MAIN["auchan.fr"]=True;
		$MAIN["tmall.com"]=True;
		$MAIN["shopping.com"]=True;
		$MAIN["oxatis.com"]=True;
		$MAIN["1688.com"]=True;
		$MAIN["360buy.com"]=True;
		$MAIN["indonetwork.co.id"]=True;
		$MAIN["globalsources.com"]=True;
		$MAIN["tradeee.com"]=True;
		$MAIN["mercadolibre.com.mx"]=True;
		$MAIN["mercadolibre.cl"]=True;
		$MAIN["fnac.com"]=True;
		$MAIN["sklep.pl"]=True;
		$MAIN["makeshop.jp"]=True;
		$MAIN["rueducommerce.fr"]=True;
		$MAIN["groupon.com.au"]=True;
		$MAIN["groupon.com.ua"]=True;
		$MAIN["groupon.com"]=True;
		$MAIN["groupon.com.tw"]=True;
		$MAIN["groupon.com.hk"]=True;
		$MAIN["groupon.com.ar"]=True;
		$MAIN["groupon.com.br"]=True;
		$MAIN["groupon.com.pe"]=True;
		$MAIN["groupon.com.mx"]=True;
		$MAIN["groupon.sg"]=True;
		$MAIN["groupon.it"]=True;
		$MAIN["groupon.fr"]=True;
		$MAIN["groupon.de"]=True;
		$MAIN["groupon.pl"]=True;
		$MAIN["groupon.pt"]=True;
		$MAIN["groupon.es"]=True;
		$MAIN["groupon.co.il"]=True;
		$MAIN["groupon.ch"]=True;
		$MAIN["groupon.co.za"]=True;
		$MAIN["groupon.be"]=True;
		$MAIN["groupon.hk"]=True;
		$MAIN["groupon.my"]=True;
		$MAIN["groupon.ie"]=True;
		$MAIN["groupon.ru"]=True;
		$MAIN["groupon.at"]=True;
		$MAIN["groupon.nl"]=True;
		$MAIN["groupon.ae"]=True;
		$MAIN["groupon.at"]=True;
		$MAIN["groupon.dk"]=True;
		$MAIN["groupon.co.id"]=True;
		$MAIN["groupon.co.in"]=True;
		$MAIN["groupon.co.uk"]=True;
		
		$array=array("1jour-1bonplan","wholesalepowertools","cdiscount","blizko","focalprice","cherchons","diplotop",
				"360buy","mbstatic","groupon","zura","amazon","htfacile","leguide","crystalcommerce","babidou","jd.com","coovee.net",
				"couponcodes","alloy.ru","franceprix","marst.ru","souqcdn.com","outlet","consopremium","trustpilot",
				"officedepot","topachat","bonsplansdemarc","netodr.fr","selectra.info","avtovideoglaz.ru","topshopdeals.com",
				"shoppingshadow.com","services-carrefour","teamfanshop.com","prooxi.fr","livepepper.com","abo.com.ua","hmmm.cz",
				"myshopify.com","darty-services.com","darty.com","darty.es",
				
		);
		
		while (list ($www, $cat) = each ($MAIN) ){
			$array[]=$www;
		
		}
		
		if(!$cleanedkey){return $array;}
		while (list ($index, $www) = each ($array) ){
			if(strpos(" $www", ".")==0){continue;}
			if(strpos(" $www", "*")>0){continue;}
			$XRETURN[$www]=true;
		}
		
		return $XRETURN;
	}
	
	
}
