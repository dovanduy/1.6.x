<?php
function CategoryCodeToCatName($category){
	if(preg_match("#^art(.+)#", $category,$re)){$category=$re[1];}
	if(preg_match("#^tls(.+)#", $category,$re)){$category=$re[1];}
	if($category=="listebu"){$category="liste_bu";}
	if($category=="adult"){$category="porn";}
	if($category=="agressivecat"){$category="agressive";}
	if($category=="automobile_bikes"){$category="automobile/bikes";}
	if($category=="automobile_boats"){$category="automobile/boats";}
	if($category=="automobile_planes"){$category="automobile/planes";}
	if($category=="automobile_cars"){$category="automobile/cars";}
	if($category=="finance_banking"){$category="finance_banking";}
	if($category=="finance_banking"){$category="finance/banking";}
	if($category=="finance_insurance"){$category="finance/insurance";}
	if($category=="finance_moneylending"){$category="finance/moneylending";}
	if($category=="finance_realestate"){$category="finance/realestate";}
	if($category=="hobby_cooking"){$category="hobby/cooking";}
	if($category=="hobby_arts"){$category="hobby/arts";}
	if($category=="hobby_pets"){$category="hobby/pets";}
	if($category=="lingerie"){$category="sex/lingerie";}
	if($category=="dangerousmaterial"){$category="dangerous_material";}
	if($category=="mixedadult"){$category="mixed_adult";}
	if($category=="listebu"){$category="liste_bu";}
	if($category=="adult"){$category="porn";}
	if($category=="audiovideo"){$category="audio-video";}
	return $category;

}