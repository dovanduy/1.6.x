<?php

ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
require_once(dirname(__FILE__)."/ressources/kayaco/kyIncludes.php");
define('DEBUG', true);

kyConfig::set(new kyConfig("http://www.articatech.net/support/api/index.php", "bea5b97c-a838-8ca4-9dda-aec5043cccae", "MmUxYzRmMDMtM2RjNC1iY2Y0LTRkOGEtNjYzN2Q3N2U3MWE2N2FiZjk2NzEtMzQxZS0yMTA0LTUxN2YtOWZiY2VkOTQyNTMy"));


$registered_user_group = kyUserGroup::getAll()
    ->filterByTitle("30 days free")
    ->first();
    
    
echo "registered_user_group: $registered_user_group\n";
 
//load some user organization

    
$org=new kyUserOrganization();
$org->setName("MyCompany");
$data=$org->buildData(true);
$org->create();
print_r($data);

    
    $user_organizations = kyUserOrganization::getAll();
     foreach ($user_organizations as $user_organization) {  
     	 $id=$user_organization->getId();
     	 $company=$user_organization->getName();
     	echo "Company: $company -> $id\n";
     	
     }

     
     
     
    

return;
 
/**
 * Create new user in Registered group:
 * fullname: Anno Ying
 * email: anno.ying@example.com
 * password: qwerty123
 */
$user = $registered_user_group
    ->newUser("Daniel touzeau", "webmaster@artica.fr", "qwerty123")
    ->setUserOrganization($user_organization) //userorganizationid
    ->setSalutation(kyUser::SALUTATION_MR) //salutation
    ->setSendWelcomeEmail(true) //sendwelcomeemail
    ->create();