<?php

// Add necessaries classes
include_once('/usr/share/artica-postfix/ressources/class.templates.inc');
include_once('/usr/share/artica-postfix/ressources/class.ldap.inc');
include_once('/usr/share/artica-postfix/ressources/class.users.menus.inc');
include_once('/usr/share/artica-postfix/ressources/class.mysql.inc');
include_once('/usr/share/artica-postfix/ressources/class.user.inc');




// RUN IN VERBOSE MODE TRUE/FALSE
$VERBOSE=false;
if($VERBOSE){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// bulk import ...

$filecontent=explode(";",@file_get_contents("/my/file/name.csv"));

// Start the loop on the CSV file
while (list($index,$line)=each($filecontent)){
	$line=trim($line);
	
	$attributes=explode(";",$line);
	$uid=$attributes[0]; // jhon.malvid
	$DisplayName=$attributes[1]; // Jhon Malvid
	$password=$attributes[2]; // Mailbox password
	$telephoneNumber=$attributes[3];
	$mail=$attributes[4];
	$mobile=$attributes[5];

	// 	.../.. to be continued
		
	// loading the class and add the Unique userid. ( example jhon.malvid
	$users=new user($uid);
	
	// Check if user already exists : TRUE OR false
	
	if($user->UserExists){ echo "TRUE\n";}
	
	// If a new user, give the Organization 
	//$users->ou="MyOrganization";
	$users->DisplayName=$DisplayName;
	$users->password=$password;
	$users->telephoneNumber=$telephoneNumber;
	$users->mail=$mail;
	$users->mobile=$mobile;
	$users->sn="Malvid";
	$users->givenName="Jhon";
	
	// if user is a Zarafa Contact
	//$users->AsZarafaContact=true;
	
	// If user is an Zarafa Hidden mailbox
	// $users->zarafaHidden=true;
	
	// Create user, return true or false.
	$users->add_user();
	
	// Add mail aliases
	//$users->add_alias("jm@domain.tld");
}
