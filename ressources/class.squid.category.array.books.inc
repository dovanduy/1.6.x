<?php
include_once(dirname(__FILE__)."/class.squid.familysites.inc");
class array_category_books{
	
	private function _ext($MAIN,$www){
		$f=new squid_familysite();
		return $f->filldomains($MAIN, $www);
		
		
	}
	
	
	function return_array($cleanedkey=false){
		
		
		
		$MAIN["bookspace.fr"]=True;
		$MAIN["elsevier.com"]=True;
		$MAIN["tradepub.com"]=True;
		$MAIN["oreilly.com"]=True;
		$MAIN["quickbooks.com"]=True;
		$MAIN["livreshebdo.fr"]=True;
		$MAIN["bnf.fr"]=True;
		$MAIN["ultra-book.com"]=True;
		$MAIN["kobobooks.com"]=True;
		$MAIN["wikibooks.org"]=True;
		$MAIN["alexanderstreet.com"]=True;
		$MAIN["quwebooks.com"]=True;
		
		$array=array("booksite",'vonibo','bookos',"lyellcollection.org","eyrolles","wattpad.com","bibliosansfrontieres.org","cyberpresse","berghahnbooks","yoolib",'wikibooks',
				"electre",'tradepub','docstoc-store','oreilly');
		
		$MAIN=$this->_ext($MAIN, "books.google");
		
		
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
