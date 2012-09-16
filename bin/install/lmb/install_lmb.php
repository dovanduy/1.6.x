<?php
// *************************************************************************************************************
// SCRIPT D'INSTALLATION DE LA SOLUTION LUNDI MATIN BUSINESS
// *************************************************************************************************************
/**
*
* @package install
* @version $Id: install_lmb.php,v 2.0 2008/09/18 
* @copyright (c) 2008 Lundi Matin
* @license http://www.lundimatin.fr/site/licence_opensource_lmpl.php
*
*/
$VERSION_INSTALLEUR_LMB = "2.6";


if (isset($_REQUEST['phpinfo'])) { phpinfo(); exit(); }

// *********************************************************************************************************
// ******************************  Initialisation des variables  *******************************************
// *********************************************************************************************************
$version_file 			= "install_lmb_version.txt";
$install_file_name 		= "install_lmb.php";

$install_config_name	= "install_lmb.config.php";

$distant_install_stat	= "http://www.lundimatin.fr/scripts/install_stats/"; 
$distant_install_url 	= "http://ftp2.lundimatin.fr/install_online/";
$distant_install_js		= $distant_install_url."js/";
$distant_install_css	= $distant_install_url."css/";
$distant_install_images	= $distant_install_url."images/"; 

$ftp_serveur 			= "ftp.lundimatin.fr"; 

$distant_install_ftp 		= "/install_online/";
$distant_install_ftp_files	= $distant_install_ftp."files/";
$distant_install_bdd_files	= $distant_install_ftp."bdd/";

$local_install_dir	= "__install_lmb_files/";			// Fichiers temporaires de l'installation
$local_install_bdd	= $local_install_dir."bdd/";
$societe_infos_file = "install_lmb.infos.php";

$xml_liste_fichiers = "lmb_liste_fichiers.xml";
$bdd_liste_tables 	= "lmb_liste_tables.txt";
$download_infos_file = $local_install_dir."lmb_download_state.tmp";
$total_size = 0;

$bdd_order_sql_files = "bdd_liste_files.txt"; // Fichiers listant dans l'ordre les différents fichiers sql à executer

// $use_only_localfiles permet l'installation sans téléchargement des fichiers (les fichiers sont déjà placés sur l'hébergement ou le seveur local.
$use_only_localfiles = 0; //0 installation par téléchargement. 1 installation par fichiers déjà téléchargés

$xml_infos = array();
$install_files = array();
$install_dirs = array();
$install_infos = array();


@ini_set('memory_limit', '128M');




// *********************************************************************************************************
// Etape de l'installation
$etape = 0;
if (isset($_REQUEST['etape'])) {
	$etape = $_REQUEST['etape'];
}

$etapes_ok = array(0, 1, "1a", 2, 3, "3a", 4, "4a", 5, "5a", "5b", "5c", "5d");
if (!in_array($etape, $etapes_ok)) {
	erreur ("Une erreur est survenue durant la procédure d'installation.");
	exit();
}


// IUI: Identifiant unique d'installation permettant de générer des statistiques sur la réussite ou l'échec des installations
if (isset($_REQUEST['iui'])) {
	$iui = $_REQUEST['iui'];
}
else {
	$iui = create_unique_install_id ();

}

// REF_PARRAIN: Identifiant du partenaire
$ref_parrain = "";
if (file_exists ($install_config_name)){
	require_once ($install_config_name);
}
@file($distant_install_stat."install_states.php?iui=".$iui."&etape=".$etape."&ref_parrain=".$ref_parrain);



// *********************************************************************************************************
// ******************************************  DEBUT DU SCRIPT  ********************************************
// *********************************************************************************************************

switch ($etape) {

// ***************************************************************************** Vérification de la version de l'installeur
case "0":

	$version_actuelle = $VERSION_INSTALLEUR_LMB;
	// Verification de la version d'installation
	if (remote_file_exists ($distant_install_url.$version_file)) {
		$fp = @fopen($distant_install_url.$version_file, "r");
		if (!$fp) {
			erreur("Vérification de version impossible");
		}
		$version_actuelle = file_get_contents($distant_install_url.$version_file);
	} else {
		erreur("Le système d'installation ne peut se connecter au serveur Lundi 
		Matin afin de vérifier la version du programme d'installation.
		<br>Soit votre serveur ne dispose pas d'un accès à Internet (Auquel cas 
		vous ne pourrez pas obtenir les fichiers du programme ainsi que les 
		mises à jour,<br>
		Soit la fonction fopen() est désactivée sur votre serveur. (Auquel cas 
		il vous faut l'activer ou demander à votre administrateur de le 
		faire.)<br><br>
		
		Pour plus d'information, veuillez vous rendre sur le forum assistance 
		<a href=\"http://www.lundimatin.fr/site/forum\" target=\"_blank\" >www.lundimatin.fr/site/forum</a>
		
		<br><br>
		Si vous souhaitez outrepasser cette alerte et continuer l'installation 
		(résultat incertain), <a href=\"install_lmb.php?etape=1&iui=".$iui."\">cliquez ici</a>");
	}
		
	if ($version_actuelle == $VERSION_INSTALLEUR_LMB) {
		header ("Location: install_lmb.php?etape=1&iui=".$iui);
		exit(); 
	} 
	
	download_lmb_installeur();

	// Redirection vers le nouvel installeur
	header ("Location: ".$install_file_name."?etape=1&iui=".$iui);
	exit();
	
break;

// ***************************************************************************** BIENVENUE: affichage licence
case "1":
	// Chargement du texte de la licence LMPL
	$licence_txt = "" ;
	$file_licence = "http://www.lundimatin.fr/site/licence_lmpl.txt";
	$fp = @fopen($file_licence, "r");
	if ($fp) {
		$licence_txt = file_get_contents($file_licence);
	}
	else {
		$licence_txt = " La licence d'utilisation est la LMPL. 
		Vous pouvez en prendre connaissance sur http://www.lundimatin.fr/site/licence_opensource_lmpl.php ";
	}

	// Chargement du contenu du menu
	$menu = menu($etape);
	
	//contenu de la page d'installation
	$contenu_page = '
		<div class="title_content" >Bienvenue</div>
		<p class="bold_text">Vous êtes sur le point d\'installer Lundi Matin Business.<br/> Lundi Matin Business est l\'application Open Source de gestion d\'entreprise :<br /></p>
		
			<p class="grey_rounded" style="width:580px;">
				<span class="grey_text" style="font-weight:bolder">
				Gestion de votre annuaire (Clients, Fournisseurs, Collaborateurs, Etc.)
				<br />
				Gestion de votre catalogue de produits
				<br />
				Devis, Commande, Livraison, Facturation<br />
				Gestion des stocks
				<br />
				Comptabilité simplifiée (Suivi des règlements, gestion comptes bancaires, etc.)
				</span>
			</p>
		
		<br />
		<div class="sous_titre1">Licence d&rsquo;utilisation&nbsp;:</div>
		<p class="bold_text">Afin de proc&eacute;der &agrave; l&rsquo;installation,  vous devez accepter les termes de la licence ci-dessous&nbsp;:</p>
		<p>
			<textarea name="glp" rows="10" style="width:580px" readonly="readonly">'.$licence_txt.'</textarea>
		</p>
		<form action="install_lmb.php" method="POST" id="licence_form">
		<p class="green_text">
		<input type="radio" name="accep_licence" id="accepted" value="1" /> J\'accepte &nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" name="accep_licence" id="refused" value="0" checked="checked" /> Je refuse
		<input type="hidden" name="etape" value="2"  />
		<input type="hidden" name="iui" value="'.$iui.'"  />
		</p>
		
		<p> <input type="submit" id="sub_accep_licence" disabled="disabled" class="bt_suite" value="Suivant" /> </p>
		</form>
		<script type="text/javascript">
		Event.observe("accepted", "click", function(evt){
			$("sub_accep_licence").disabled = "";
		}, false);	
		Event.observe("refused", "click", function(evt){
			$("sub_accep_licence").disabled = "disabled";
		}, false);	
		</script>
			';
	
	// Affichage de la page
	Response_html ($menu, $contenu_page);

break;



// ***************************************************************************** SECURITE: affichage infos
case "2":
	// Chargement du contenu du menu
	$menu = menu($etape);
	//contenu de la page d'installation

	$contenu_page = '
		<div class="title_content" >Sécurité &amp; confidentialité</div>
		<br />
		<div class="sous_titre1">Informations sur la sécurité et la confidentialité de vos données</div>
		<p><div class="grey_rounded"><span class="bold_text">L\'équipe de développement de Lundi Matin Business prends la sécurité de vos données très au sérieux.
		<br />
		L\'application que nous vous proposons est sûre à plus d\'un titre :<span></div>
		</p><br />

		<p class="bold_text">Accès sécurisé par mot de passe<br />
		
		<span class="grey_text">Chacun de vos collaborateurs accède à l\'interface de Lundi Matin Business au moyen d\'un accès sécurisé par mot de passe, crypté et individuel.</span>
		</p>
		<p class="bold_text">Développement en Open Source 
		<br />
		<span class="grey_text">Un contrôle strict et rigoureux de la fiabilité de notre application est exercé par une communauté internationale de développeurs indépendants.</span>
		</p>
		<p class="bold_text"> Technologies mondialement reconnues
		<br />
		<span class="grey_text">Les technologies employées dans l\'infrastructure logicielle Lundi Matin Business sont réputées être parmi les plus fiables au monde. (Apache, PHP, MySQL)</span>
		</p>
		<p>
		<div class="sous_titre1">Afin de maximiser la sécurité et la confidentialité de vos données
		</div>
		<p class="bold_text">Ne communiquez jamais votre mot de passe personnel.
		<br />
		<span class="grey_text">Lundi Matin Business inclus une fonctionnalité permettant d\'offrir un accès restreint à un administrateur externe chargé d\'effectuer des opérations de maintenance pour votre compte.
		
		L\'équipe de Lundi Matin Business ne vous demandera jamais votre mot de passe personnel.</span></p>
		<p class="bold_text">
		Faites appel à un professionnel de l\'hébergement Internet certifié « Partenaire Lundi Matin ».
		<br />
		<span class="grey_text">Nous avons testé et sélectionné pour vous de nombreux prestataires partenaires qui vous garantissent une infrastructure technique sécurisée et des services adaptés à vos besoins.</span>
		</p>
		<p class="bold_text">Protégez votre poste de travail.
		<br />
		<span class="grey_text">Vérifiez régulièrement votre ordinateur afin qu\'il soit exempt de virus et de logiciels espions.
		</span></p>
		<p class="bold_text">Sauvegardez vos données !
		<br />
		<span class="grey_text">Comme pour toute autre application professionnelle, la sauvegarde régulière de vos données est la garantie indispensable afin de vous permettre de profiter pleinement de Lundi Matin Business.
		</span></p>
		<form action="install_lmb.php" method="POST" id="licence_form">
		<p>
		<input type="hidden" name="etape" value="3"  />
		<input type="hidden" name="iui" value="'.$iui.'"  />
		<input type="submit" name="go_etape_2" class="bt_suite" value="Suivant" />
		</p>
		</form>
		';
	
	//affichage de la page
	Response_html ($menu, $contenu_page);
break;



// ***************************************************************************** CONFIGURATION: Test de la compatibilité
case "3":

	$config_test_result = array();
	
	// Vérification de la version php 
	test_php_version();

	// Vérification du getimagesize()
	test_getimage_size();
	
	// Droit en écriture locale
	test_file_auth();
	
	// Test présence mysql
	test_mysql();

	// Test de la présence de la librairie PDO
	test_pdo();
	
	//text xml
	test_xml_dispo();
	
	// Texte par defaut (si test réussi c'est le texte affiché)
	$response_test = "";
	
	// si une erreur
	if (count($config_test_result)) {
		$test_result = "";
		// Affichage des résultats erronnés
		foreach ($config_test_result as $result) {
			$test_result .= '
			<div style="display:block; height:28px">
			<span class="system">'.$result["sys"].'</span><span class="require">'.$result["requis"].'</span><span class="result_ligne">'.$result["result"].'</span>&nbsp;</div>';
		}
	
		// Affiché si en erreur
		$response_test = "<div class=\"grey_rounded\">
		Le paramétrage de votre système est incorrect pour une utilisation optimale de Lundi Matin Business.<br />
	Veuillez contacter votre administrateur système ou votre prestataire en hébergement Internet pour résoudre ce problème.<br /> <br />
		<span class=\"system\">Votre système</span><span class=\"require\">Requis</span><span class=\"result\">Résultat du test</span><br />".$test_result."<br />
</div>";
	
	
	}

	
	// ***************************************************************************** TEST BDD: demande des paramètres
	$form_connexion = "";
	$bdd_creer = 0;
	$bdd_creer_check = "";
	//si aucunes erreurs de configuration on vérifie l'existance du fichier de config BDD
	//ou on propose de renseigner ses informations
	if (!count($config_test_result)) {
		if (file_exists($install_config_name)){
			require_once ($install_config_name);
			if (isset($bdd_hote) && isset($bdd_user) && isset($bdd_pass) && isset($bdd_base)) {
				//on passe à l'étape suivante avec les informations renseignées dans le config
				header ("Location: install_lmb.php?etape=3a&iui=".$iui);
				exit(); 
			} else {
				
				//il manque des parametres
				//on complète l'affichage avec la demande des infos de connexion ftp et bdd		
				$form_connexion = BDD_form();
			}
		} else {
			//on complète l'affichage avec la demande des infos de connexion ftp et bdd		
			$form_connexion = BDD_form();
		}
	}
	
	//on génère l'affichage
	//chargement du contenu du menu
	$menu = menu($etape);
	//affichage de la page en cas d'erreur de config et/ou d'absence des infos de connexions
	$contenu_page = '<div class="title_content" >Configuration du Système</div>
	<div class="sous_titre1">Configuration du Système</div><br />
<br />
'.$response_test.$form_connexion;
	//affichage de la page
	Response_html ($menu, $contenu_page);
break;

// ***************************************************************************** TEST BDD : test des serveurs
case "3a":

	// Informations sur les paramètres d'accès à la base de données
	if (file_exists($install_config_name)){
		// Si définies dans le fichier de configuration
		require_once ($install_config_name);
	} else {
		// Si précisées par l'utilisateur ($_REQUEST)	
		if (isset($_REQUEST["bdd_hote"])) {$bdd_hote = $_REQUEST["bdd_hote"]; }
		if (isset($_REQUEST["bdd_user"])) {$bdd_user = $_REQUEST["bdd_user"]; }
		if (isset($_REQUEST["bdd_pass"])) {$bdd_pass = $_REQUEST["bdd_pass"]; }
		if (isset($_REQUEST["bdd_base"])) {$bdd_base = $_REQUEST["bdd_base"]; }		
		if (isset($_REQUEST["bdd_creer"])) {$bdd_creer = 1; }	
	}
	// Si il manque un élément ce n'est pas normal
	if ( !isset($bdd_hote) || !isset($bdd_user) || !isset($bdd_pass) || !isset($bdd_base) ) {
		erreur("Les paramètres de connexion à la base de données n'ont pas été transmis");	
	}
	
	// Test de la base de données
	$form_connexion = test_bdd ();
	
	// Affichage 
	$menu = menu(substr($etape, 0, 1));
	$contenu_page = '<div class="title_content" >Configuration du Système</div>
	<div class="sous_titre1">Configuration du Système</div><br />
<br />
'.$form_connexion;

	Response_html ($menu, $contenu_page);
break;



// ***************************************************************************** SOCIETE: Demande des infos
case "4":

	//on génère l'affichage
	//chargement du contenu du menu
	$menu = menu(substr($etape, 0, 1));
	//contenu de la page
	$contenu_page = '<div class="title_content">Vos informations</div>
	<span class="bold_text">Veuillez maintenant indiquer les informations concernant l\'identité de votre société.<br />
	Ces informations permettront un premier paramétrage de l\'application.</span>
	<div style="width:100%"><br />

	<form action="install_lmb.php" method="post" id="infos_contact">
	<div class="sous_titre1">Identité</div>
	<table class="minimizetable">
		<tr>
			<td><img src="'.$distant_install_images.'blank.gif" width="100%" height="1" id="imgsizeform"/></td>
			<td><img src="'.$distant_install_images.'blank.gif" width="100%" height="1" id="imgsizeform"/></td>
		</tr>	
		<tr>
			<td  class="size_strict">
					<span class="labelled_ralonger">Cat&eacute;gorie:</span>
			</td>
			<td>
				<select id="id_categorie" name="id_categorie" class="classinput_xsize">
					<option value="1">Particulier</option>
					<option value="2" selected="selected">Soci&eacute;t&eacute;</option>
					<option value="3">Administration</option>
					<option value="4">Association</option>
					<option value="5">Autre</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<span class="labelled_ralonger">Civilit&eacute;:</span>
			</td>
			<td>
				<select name="civilite" id="civilite" class="classinput_xsize">
					<option value="5"></option>
					<option value="20">E.A.R.L</option>
					<option value="16">E.I.</option>
					<option value="9">E.U.R.L.</option>
					<option value="12">Ets.</option>
					<option value="6">Me</option>
					<option value="4">S.A.</option>
					<option value="3">S.A.R.L.</option>
					<option value="10">S.A.S.</option>
					<option value="11">S.A.S.U.</option>
					<option value="18">S.E.L.</option>
					<option value="17">S.E.M.</option>
					<option value="13">S.C.I.</option>
					<option value="19">S.C.P.</option>
					<option value="14">S.N.C.</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<span class="labelled_ralonger">Nom: *</span>
			</td>
			<td>
			<textarea id="nom" name="nom" rows="2"  class="classinput_xsize"></textarea>
			</td>
		</tr>
		<tr id="line_siret" style="display:">
			<td>
				<span class="labelled_ralonger" title="Numéro de Siret">Siret:</span>
			</td>
			<td>
			<input type="text" id="siret" name="siret" maxlength="20" class="classinput_xsize"/>
			</td>
		</tr>
		<tr id="line_tva_intra" style="display:">
			<td>
				<span class="labelled_ralonger" title="Numéro de TVA intracommunautaire">TVA intra.:</span>
			</td>
			<td>
			<input type="text" id="tva_intra" name="tva_intra" class="classinput_xsize"/>
			</td>
		</tr>
	</table>
	<br/>
	<div class="sous_titre1">Adresse</div>
	<table class="minimizetable">
		<tr class="smallheight">
			<td><img src="'.$distant_install_images.'blank.gif" width="100%" height="1" id="imgsizeform"/></td>
			<td><img src="'.$distant_install_images.'blank.gif" width="100%" height="1" id="imgsizeform"/></td>
		</tr>
		<tr>
			<td  class="size_strict">
			<span class="labelled_ralonger">Adresse: *</span>
			</td><td>
			<textarea id="adresse_adresse" name="adresse_adresse" rows="2" class="classinput_xsize"/></textarea>
			</td>
		</tr><tr>
			<td>
			<span class="labelled_ralonger">Code Postal: *</span> </td>
			<td>
			<input id="adresse_code" name="adresse_code" class="classinput_xsize"/>
			</td>
		</tr><tr>
			<td>
			<span class="labelled_ralonger">Ville: *</span>
			</td>
			<td>
			<div style="position:relative; top:0px; left:0px; width:100%; height:0px;">
			<iframe id="iframe_choix_adresse_ville" frameborder="0" scrolling="no" src="about:_blank"  class
	="choix_complete_ville"></iframe>
			<div id="choix_adresse_ville"  class="choix_complete_ville"></div></div>
			<input name="adresse_ville" id="adresse_ville" class="classinput_xsize"/>
			</td>
		</tr>
	</table>
	<br/>
	<div class="sous_titre1">Coordonnées</div>
	<table class="minimizetable">
		<tr>
			<td class="size_strict"><img src="'.$distant_install_images.'blank.gif" width="100%" height="1" id="imgsizeform"/></td>
			<td><img src="'.$distant_install_images.'blank.gif" width="100%" height="1" id="imgsizeform"/></td>
		</tr><tr>
			<td  class="size_strict">
				<span class="labelled_ralonger">T&eacute;l&eacute;phone 1:</span>
			</td><td>
				<input id="coordonnee_tel1" name="coordonnee_tel1" class="classinput_xsize"/>
			</td>
		</tr><tr>
			<td>
				<span class="labelled_ralonger">T&eacute;l&eacute;phone 2:</span>
			</td><td>
				<input id="coordonnee_tel2" name="coordonnee_tel2" class="classinput_xsize"/>
			</td>
		</tr><tr>
			<td>
				<span class="labelled_ralonger">Email:</span>
			</td><td>
				<input id="coordonnee_email" name="coordonnee_email" class="classinput_xsize"/>
			</td>
		</tr><tr>
			<td>
				<span class="labelled_ralonger">Fax:</span>
			</td><td>
				<input id="coordonnee_fax" name="coordonnee_fax" class="classinput_xsize"/>
			</td>
		</tr>
	</table>
	<br/>
	<div class="sous_titre1">Site Internet</div>
	<table class="minimizetable">
		<tr>
			<td class="size_strict"><img src="'.$distant_install_images.'blank.gif" width="100%" height="1" id="imgsizeform"/></td>
			<td><img src="'.$distant_install_images.'blank.gif" width="100%" height="1" id="imgsizeform"/></td>
		</tr><tr>
			<td  class="size_strict">
			<span class="labelled_ralonger">Adresse URL:</span>
			</td>
			<td>
			<input id="site_url" name="site_url" value="http://'.$_SERVER["SERVER_NAME"].'" class="classinput_xsize"/>
			</td>
		</tr>
	</table>
	<br/>
	<input type="checkbox" checked="checked" name="subscribe_to_lmb" value="1"/> Transmettre vos coordonnées à la société Lundi Matin afin d\'être informé des nouveautés et mises à jours.<br /> Vos coordonnées ne seront pas transmises à des tiers.
	<br/><br />

	<div class="sous_titre1">Administrateur de l\'application</div><br />
	L\'administrateur est la personne responsable de la gestion technique de Lundi Matin Business au sein de votre entreprise.
		
	<table class="minimizetable">
		<tr>
			<td class="size_strict"><img src="'.$distant_install_images.'blank.gif" width="100%" height="1" id="imgsizeform"/></td>
			<td><img src="'.$distant_install_images.'blank.gif" width="100%" height="1" id="imgsizeform"/></td>
		</tr><tr>
			<td  class="size_strict">
			<span class="labelled_ralonger">Pseudonyme: *</span>
			</td>
			<td>
			<input id="admin_pseudo" name="admin_pseudo" value="" class="classinput_xsize"/>
			</td>
		</tr><tr>
			<td>
			<span class="labelled_ralonger">Email de l\'administrateur: *</span>
			</td>
			<td>
			<input id="admin_email1" name="admin_email1" value="" class="classinput_xsize"/>
			</td>
		</tr><tr>
			<td>
			<span class="labelled_ralonger">Confirmer l\'adresse Email: *</span>
			</td>
			<td>
			<input id="admin_email2" name="admin_email2" value="" class="classinput_xsize"/>
			</td>
		</tr><tr>
			<td>
			<span class="labelled_ralonger">Mot de passe: *</span>
			</td>
			<td>
			<input type="password" id="admin_password1" name="admin_password1" value="" class="classinput_xsize"/>
			</td>
		</tr><tr>
			<td>
			<span class="labelled_ralonger">Confirmer le mot de passe: *</span>
			</td>
			<td>
			<input type="password" id="admin_password2" name="admin_password2" value="" class="classinput_xsize"/>
			</td>
		</tr>
	</table><br />
	
			<input type="hidden" name="etape" value="4a"  />
			<input type="hidden" name="iui" value="'.$iui.'"  />
			<input type="submit" name="go_etape_4a" class="bt_suite" value="Suivant" />
	</form>
	<script type="text/javascript">
	
	Event.observe("id_categorie", "change",  function(evt){
		if ($("id_categorie").value == "2") {
			$("line_siret").show(); 
			$("line_tva_intra").show();
		} else {
			$("line_siret").hide(); 
			$("line_tva_intra").hide();
		}
	}, false);

	start_civilite("id_categorie", "civilite");
	
	Event.observe("infos_contact", "submit",  function(evt){Event.stop(evt); check_infos_contact(); }, false);
	
	
	</script>
	</div>
	';
	//affichage de la page
	Response_html ($menu, $contenu_page);
break;

// ***************************************************************************** SOCIETE: Enregistrement des infos
case "4a":
	// On créé un fichier temporaire pour y inscrire les infos de la société
	@mkdir($local_install_dir);
	chmod($local_install_dir, 0777);
	if (!is_dir($local_install_dir)) { 
		erreur("Erreur lors de la creation du dossier d'installation"); 
		erreur($erreur);
	}

	if (!$file_infos_soc = @fopen ($local_install_dir.$societe_infos_file, "w")) {
		$erreur = " Vous n'avez pas les droits de lecture sur le fichier ".$societe_infos_file.""; 
		erreur($erreur);
	}

	$file_content = "<?PHP 
\$ENTREPRISE_REF_SERVEUR = \"000000\";

// CONTACT
\$ENTREPRISE_REF_CONTACT = \"C-000000-00001\";
\$ENTREPRISE_ID_CIVILITE = \"".$_REQUEST["civilite"]."\";
\$ENTREPRISE_NOM = \"".addslashes($_REQUEST["nom"])."\";
\$ENTREPRISE_ID_CATEGORIE = \"".$_REQUEST["id_categorie"]."\";
\$ENTREPRISE_NOTE = \"\";
\$ENTREPRISE_SIRET = \"".addslashes($_REQUEST["siret"])."\";
\$ENTREPRISE_TVA_INTRA = \"".addslashes($_REQUEST["tva_intra"])."\";

// ADRESSES
\$ENTREPRISE_ADRESSES[0]['ref_adresse'] = \"ADR-000000-00001\";
\$ENTREPRISE_ADRESSES[0]['lib_adresse'] = \"\";
\$ENTREPRISE_ADRESSES[0]['text_adresse'] = \"".addslashes($_REQUEST["adresse_adresse"])."\";
\$ENTREPRISE_ADRESSES[0]['code_postal'] = \"".addslashes($_REQUEST["adresse_code"])."\"; 
\$ENTREPRISE_ADRESSES[0]['ville'] = \"".addslashes(strtoupper($_REQUEST["adresse_ville"]))."\";
\$ENTREPRISE_ADRESSES[0]['id_pays'] = \"77\";
\$ENTREPRISE_ADRESSES[0]['ordre'] = \"1\";


// COORDONNEES
\$ENTREPRISE_COORDONNEES[0]['ref_coord'] = \"COO-000000-00001\";
\$ENTREPRISE_COORDONNEES[0]['lib_coord'] = \"\";
\$ENTREPRISE_COORDONNEES[0]['tel1'] = \"".addslashes($_REQUEST["coordonnee_tel1"])."\";
\$ENTREPRISE_COORDONNEES[0]['tel2'] = \"".addslashes($_REQUEST["coordonnee_tel2"])."\"; 
\$ENTREPRISE_COORDONNEES[0]['fax'] = \"".addslashes($_REQUEST["coordonnee_fax"])."\";
\$ENTREPRISE_COORDONNEES[0]['email'] = \"".addslashes($_REQUEST["coordonnee_email"])."\";
\$ENTREPRISE_COORDONNEES[0]['ordre'] = \"1\";


// SITES
\$ENTREPRISE_SITES[0]['ref_site'] = \"SIT-000000-00001\";
\$ENTREPRISE_SITES[0]['lib_site_web'] = \"\";
\$ENTREPRISE_SITES[0]['url'] = \"".addslashes($_REQUEST["site_url"])."\";
\$ENTREPRISE_SITES[0]['ordre'] = \"1\";

// USERS
\$ENTREPRISE_USER['ref_user'] = \"U-000000-00001\";
\$ENTREPRISE_USER['pseudo'] = \"".addslashes($_REQUEST["admin_pseudo"])."\"; 
\$ENTREPRISE_USER['code'] = \"".addslashes($_REQUEST["admin_password1"])."\"; 
\$ENTREPRISE_USER['id_langage'] = \"1\";

";
//si les email de l'user et de l'entreprise sont différents on cré un seconde coord qui sera utilisée pour l'user
if ($_REQUEST["admin_email1"] != $_REQUEST["coordonnee_email"]) {
	$file_content .= "
\$ENTREPRISE_COORDONNEES[1]['ref_coord'] = \"COO-000000-00002\";
\$ENTREPRISE_COORDONNEES[1]['lib_coord'] = \"\";
\$ENTREPRISE_COORDONNEES[1]['tel1'] = \"\";
\$ENTREPRISE_COORDONNEES[1]['tel2'] = \"\"; 
\$ENTREPRISE_COORDONNEES[1]['fax'] = \"\";
\$ENTREPRISE_COORDONNEES[1]['email'] = \"".addslashes($_REQUEST["admin_email1"])."\";
\$ENTREPRISE_COORDONNEES[1]['ordre'] = \"2\";

";
}

$email_envoi =  $_REQUEST["coordonnee_email"];
if (!$email_envoi) {
$email_envoi =  $_REQUEST["admin_email1"];
}
// Si les informations peuvent etre transmises à lundimatin
if (isset($_REQUEST["subscribe_to_lmb"]) && $_REQUEST["subscribe_to_lmb"] == "1") {
	$file_content .= "\$subscribe_to_lmb = true;
	";
	@file($distant_install_stat."install_subscribe.php?ref_parrain=".$ref_parrain."&civilite=".$_REQUEST["civilite"]."&id_categorie=".$_REQUEST["id_categorie"]."&nom=".urlencode(addslashes(str_replace (CHR(13), "" ,str_replace (CHR(10), "" ,preg_replace ("#((\r\n)+)#", "", nl2br(($_REQUEST["nom"])))))))."&siret=".urlencode(addslashes($_REQUEST["siret"]))."&adresse=".urlencode(addslashes(str_replace (CHR(13), "" ,str_replace (CHR(10), "" ,preg_replace ("#((\r\n)+)#", "", nl2br(($_REQUEST["adresse_adresse"])))))))."&code=".urlencode(addslashes($_REQUEST["adresse_code"]))."&ville=".urlencode(addslashes($_REQUEST["adresse_ville"]))."&tel1=".urlencode(addslashes($_REQUEST["coordonnee_tel1"]))."&tel2=".urlencode(addslashes($_REQUEST["coordonnee_tel2"]))."&fax=".urlencode(addslashes($_REQUEST["coordonnee_fax"]))."&email=".urlencode(addslashes($email_envoi))."&url=".urlencode(addslashes($_REQUEST["site_url"])));
}

$file_content .= "
?>
";


if (!fwrite ($file_infos_soc, $file_content)) {
	$erreur = " Vous n'avez pas les droits en écriture sur le fichier ".$societe_infos_file.""; 
	erreur($erreur);
}


header ("Location: install_lmb.php?etape=5&iui=".$iui);
exit(); 

break;



// ***************************************************************************** INSTALLATION: Préparation et affichage
case "5":

	// Génération de l'affichage
	$menu = menu(substr($etape, 0, 1));

	$title_bt_lauch = "Lancer le téléchargement";
	if ($use_only_localfiles) {$title_bt_lauch = "Lancer l'installation";}
	
	$title_text_install = "Téléchargement";
	if ($use_only_localfiles) {$title_text_install = "Installation";}
	
	$title_text_etat_dowload_file = 'Téléchargement : <span id="file_dl">0</span> MB sur <span id="file_tt">0</span> MB   -   Temps restant estimé : <span id="file_time">0</span> minutes';
	if ($use_only_localfiles) {$title_text_etat_dowload_file = 'Installation des fichiers. <span id="file_dl" style="display:none"></span><span id="file_tt" style="display:none"></span><span id="file_time" style="display:none"></span>';}
	
	$title_text_etat_dowload_bdd = 'Téléchargement : <span id="sql_dl">0</span> MB sur <span id="sql_tt">0</span> MB   -   Temps restant estimé : <span id="sql_time">0</span> minutes';
	if ($use_only_localfiles) {$title_text_etat_dowload_bdd = 'Installation des fichiers. <span id="sql_dl" style="display:none"></span> <span id="sql_tt" style="display:none"></span><span id="sql_time" style="display:none"></span> ';}
	
	// Contenu de la page
	$contenu_page = '
			<div  class="title_content">Installation</div>
			<br />
			<p class="bold_text">
				Installation de Lundi Matin Business.<br />
				Nous allons désormais procéder à l\'installation du programme. Cette opération peut prendre plusieurs minutes.</p>
			
			<div style="text-align:center; margin:50px 0px;">
				<div style="width:500px;	margin:0px auto;">
					<div class="white_rounded_top">
						<div class="head_download">
							<span class="bold_text">T&eacute;l&eacute;chargement de Lundi Matin Business</span><br />
							Ceci peut prendre plusieurs minutes. Vous pouvez utiliser votre ordinateur<br />
							pour d&rsquo;autres t&acirc;ches durant l&rsquo;installation			
						</div>
					</div>
					<div class="grey_rounded_bottom">
						<div style="text-align:center; margin:5px 0px;">
							<div id="install_box" style="width:400px;	margin:0px auto;">
							<input type="hidden" name="iui" id="iui" value="'.$iui.'"  />
							<input type="submit" id="go_etape_5a" name="go_etape_5a" class="bt" value="'.$title_bt_lauch.'" />
							<span id="do_install_wait" style="display:none">Installation en cours. Veuillez patienter.</span>
							<br />
								<br />
								<div style="text-align:left" class="bold_text">'.$title_text_install.' des fichiers </div>
								<div id="progress_barre" class="progress_barre">
									<div id="files_progress" class="files_progress"></div>
								</div>
								<div style="text-align:left" class="sbold_ita_text">'.$title_text_etat_dowload_file.'</div>
								<br /><br />

								
								
								<div style="text-align:left" class="bold_text">'.$title_text_install.' de la base de données</div>
								<div id="sql_barre" class="progress_barre">
									<div id="sql_progress" class="files_progress"></div>
								</div>
								<div style="text-align:left" class="sbold_ita_text">'.$title_text_etat_dowload_bdd.'</div>
								<div style="text-align:left" id="prog_ins_sql" class="sbold_ita_text"><br />
<br />
</div>
								<div id="debug_barre" style="text-align:left" class="sbold_ita_text">
								</div>
							</div>
							
						</div><br />

		
					</div>
				</div>
			</div>
	<script type="text/javascript">
	Event.observe("go_etape_5a", "click",  function(evt){Event.stop(evt); $("files_progress").style.width = "1%";  lauch_install_file(); }, false);
	</script>
	';
	//affichage de la page
	Response_html ($menu, $contenu_page);
	
	
break;

// ***************************************************************************** INSTALLATION: Téléchargement des fichiers
case "5a":
	
	if ($use_only_localfiles) {
	
	echo "
	<script type=\"text/javascript\">	
		stp_prog_file = true;
		$(\"files_progress\").style.width = \"100%\";
		lauch_install_sql();
	</script>
	";
	exit;
	}
	
	// Téléchargement des fichiers du code source
	download_lmb_files();
	
	echo "
	<script type=\"text/javascript\">	
		stp_prog_file = true;
		$(\"files_progress\").style.width = \"100%\";
		$(\"file_dl\").innerHTML = $(\"file_tt\").innerHTML;
		$(\"file_time\").innerHTML = \"0\";
		lauch_install_sql();
	</script>
	";
	
break;

// ***************************************************************************** INSTALLATION: Téléchargement base de données
case "5b":
	//infos de connexion à la base
	require_once ($install_config_name);

	$bdd = new PDO("mysql:host=".$bdd_hote."; dbname=".$bdd_base."", $bdd_user, $bdd_pass, NULL);
	$bdd->setAttribute (PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$bdd->setAttribute (PDO::ATTR_EMULATE_PREPARES, true);
	$bdd->setAttribute (PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
	
	
	if (!$use_only_localfiles) {
		// Téléchargement des fichiers de la base de données
		download_lmb_bdd();
	}

	// Vérification des tables
	set_time_limit(300);
	$tables = file($local_install_bdd.$bdd_liste_tables);

	// Sauvegarde des tables existantes portant un nom identique à celui d'une table à créer pour LMB
	save_existing_bdd ($tables);

		
	echo "
	<script type=\"text/javascript\">	
		stp_prog_sql = true;
		$(\"sql_progress\").style.width = \"50%\";
		$(\"sql_dl\").innerHTML = $(\"sql_tt\").innerHTML;
		$(\"sql_time\").innerHTML = \"0\";
		setTimeout (\"lauch_querys_sql('0')\", 3000);
		$(\"prog_ins_sql\").innerHTML = \"Execution des requetes SQL<br /><br />\";
	</script>
	";
break;

// ***************************************************************************** INSTALLATION: Création base de données
case "5c":
	//infos de connexion à la base
	require_once ($install_config_name);
	
	// Ouverture du fichier indiquant l'ordre des fichiers SQL à executer
	$bdd_files_liste = file ($local_install_bdd.$bdd_order_sql_files);

	$tmp ="";
	$id = $_REQUEST["id_file"];
	$count_files = count($bdd_files_liste);


	// Lancement des fichiers SQL 1 à 1
	if  (isset($bdd_files_liste[$id])) {
		set_time_limit(300);
		// Ouverture du fichier $bdd_files_liste[$i] comme dans PhpMyadmin
		$fp_tmp = file($local_install_bdd.trim(str_replace("\n","",$bdd_files_liste[$id])));

		// On supprime juste la première ligne car le jeu de caractère entraine une erreur sql
		unset($fp_tmp[0]);

		$tmp = implode("",$fp_tmp);
		$query_maj_sql = explode("!-*-!", $tmp);
		
		try {
			//$bdd->exec ($tmp);
		
			$link = mysql_connect($bdd_hote, $bdd_user, $bdd_pass);
			mysql_select_db($bdd_base, $link);
			
			foreach ($query_maj_sql as $q) {
				mysql_query($q, $link);
			}
		} catch (Exception $e) {
			echo "<script type='text/javascript'>setTimeout ('lauch_querys_sql($id)', 4000);</script>";
			echo $e;
			exit();
		}
	}
	
	if (isset($bdd_files_liste[$_REQUEST["id_file"]+1])) {
		$next_id = $_REQUEST["id_file"]+1;
		$percent = 50+(round((50/$count_files) * $next_id));
		echo "<script type='text/javascript'>setTimeout ('lauch_querys_sql($next_id)', 4000);
		$(\"sql_progress\").style.width = \"$percent%\";
		$(\"prog_ins_sql\").innerHTML = \"Execution des requetes SQL $next_id/$count_files<br /><br />\";
		</script>";
	} else {
	echo "
		<script type=\"text/javascript\">	
			setTimeout ('lauch_install_infos_sql()', 4000);
		$(\"sql_progress\").style.width = \"100%\";
			$(\"prog_ins_sql\").innerHTML = \"Execution des requetes SQL $count_files/$count_files <br/> Finalisation de l'installation<br />\";
		</script>
		";
	}
	
	
break;
// ***************************************************************************** INSTALLATION: insertion des données dans la base
case "5d":
	
	// Infos de connexion à la base
	require_once ($install_config_name);
	require ($local_install_dir.$societe_infos_file);
	$bdd = new PDO("mysql:host=".$bdd_hote."; dbname=".$bdd_base."", $bdd_user, $bdd_pass, NULL);
	$bdd->setAttribute (PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$bdd->setAttribute (PDO::ATTR_EMULATE_PREPARES, true);
	$bdd->setAttribute (PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
	
	// Inscription des informations de la base dans le fichier de config
	if (!$file_config_bdd = @fopen ("config/config_bdd.inc.php", "w")) {
		$erreur = "Impossible de créer le fichier de configuration config/config_bdd.inc.php "; 
		erreur($erreur);
	}
	$file_content = "<?php

// *************************************************************************************************************
// PARAMETRES DE CONNEXION A LA BASE DE DONNEE
// *************************************************************************************************************

// Base TRAVAIL
\$bdd_hote = \"".$bdd_hote."\"; 
\$bdd_user = \"".$bdd_user."\";  
\$bdd_pass = \"".$bdd_pass."\"; 
\$bdd_base = \"".$bdd_base."\";

?>";
	
	if (!fwrite ($file_config_bdd, $file_content)) {
		$erreur = "Impossible d'écrire dans le fichier de configuration config/config_bdd.inc.php "; 
		erreur($erreur);
	}
	fclose ($file_config_bdd);
	
	//debut de l'inscription des information de la socièté
	$bdd->beginTransaction();
	
	$query = "SET FOREIGN_KEY_CHECKS=0;";
	$bdd->exec ($query);
	
	
	// *************************************************************************************************************
	// CREATION DU COMPTE DE L'ENTREPRISE
	
	// CONTACT
	$query = "INSERT INTO annuaire (ref_contact, id_civilite, nom, siret, tva_intra, id_categorie, note, date_creation, date_modification)
						VALUES ('".$ENTREPRISE_REF_CONTACT."', '".$ENTREPRISE_ID_CIVILITE."', '".$ENTREPRISE_NOM."', 
										'".($ENTREPRISE_SIRET)."', '".($ENTREPRISE_TVA_INTRA)."',
										'".$ENTREPRISE_ID_CATEGORIE."', '".addslashes($ENTREPRISE_NOTE)."',NOW(), NOW() ) ";
	$bdd->exec ($query);
	
	// PROFILS DU CONTACT
	$query = "INSERT INTO annuaire_profils (ref_contact, id_profil)
						VALUES ('".$ENTREPRISE_REF_CONTACT."', '2'), 
									 ('".$ENTREPRISE_REF_CONTACT."', '3') ";
	$bdd->exec ($query);
	
	// PROFIL ADMIN
	$query = "INSERT INTO annu_admin (ref_contact, type_admin)
						VALUES ('".$ENTREPRISE_REF_CONTACT."', 'Interne') ";
	$bdd->exec ($query);
	
	// PROFIL COLLAB
	$query = "INSERT INTO annu_collab (ref_contact, numero_secu, date_naissance, lieu_naissance, id_pays_nationalite, situation_famille, nbre_enfants)
						VALUES ('".$ENTREPRISE_REF_CONTACT."', '', '00-00-0000', '', NULL, '', NULL) ";
	$bdd->exec ($query);
	
	
	// ADRESSES
	foreach ($ENTREPRISE_ADRESSES as $adresse) {
		$query = "INSERT INTO adresses (ref_adresse, ref_contact, lib_adresse, text_adresse, code_postal, ville, id_pays, ordre, note)
							VALUES ('".$adresse['ref_adresse']."', '".$ENTREPRISE_REF_CONTACT."', '".addslashes($adresse['lib_adresse'])."',
											'".($adresse['text_adresse'])."', '".$adresse['code_postal']."', 
											'".($adresse['ville'])."', '".$adresse['id_pays']."', '".$adresse['ordre']."', '') "; 
		$bdd->exec ($query);
	}
	
	// COORDONNEES
	foreach ($ENTREPRISE_COORDONNEES as $coordonnee) {
		$query = "INSERT INTO coordonnees (ref_coord, ref_contact, lib_coord, tel1, tel2, fax, email, ordre, note)
							VALUES ('".$coordonnee['ref_coord']."', '".$ENTREPRISE_REF_CONTACT."', 
											'".addslashes($coordonnee['lib_coord'])."',
											'".$coordonnee['tel1']."', '".$coordonnee['tel2']."', '".$coordonnee['fax']."', 
											'".$coordonnee['email']."', '".$coordonnee['ordre']."', '') "; 
		$bdd->exec ($query);
	}
	
	// SITES
	$url_site = "";
	if (is_array($ENTREPRISE_SITES)) {
	foreach ($ENTREPRISE_SITES as $site) {
		$query = "INSERT INTO sites_web (ref_site, ref_contact, lib_site_web, url, ordre, note, login, pass)
							VALUES ('".$site['ref_site']."', '".$ENTREPRISE_REF_CONTACT."', 
											'".addslashes($site['lib_site_web'])."', '".$site['url']."', '".$site['ordre']."', '', '', '') "; 
		$bdd->exec ($query);
		$url_site = $site['lib_site_web'];
	}
	}
	
	
	// *************************************************************************************************************
	// CREATION DE L'UTILISATEUR PRINCIPAL
	
	// UTILISATEUR
	$ref_coord = $ENTREPRISE_COORDONNEES[0]['ref_coord'];
	$email = $ENTREPRISE_COORDONNEES[0]['email'];
	
	if (isset($ENTREPRISE_COORDONNEES[1]['ref_coord'])) {$ref_coord = $ENTREPRISE_COORDONNEES[1]['ref_coord'];}
	if (isset($ENTREPRISE_COORDONNEES[1]['email'])) {$email = $ENTREPRISE_COORDONNEES[1]['email'];}
	
	$query = "INSERT INTO users (ref_user ,ref_contact ,ref_coord_user ,master ,pseudo ,code ,actif ,ordre, id_langage)
						VALUES ('".$ENTREPRISE_USER['ref_user']."', '".$ENTREPRISE_REF_CONTACT."', 
										'".$ref_coord."', '1', '".$ENTREPRISE_USER['pseudo']."', 
										md5('".$ENTREPRISE_USER['code']."') , '1', '1', '".$ENTREPRISE_USER['id_langage']."') ";
	$bdd->exec ($query);
	
	// DROITS
	$query = "INSERT INTO users_permissions (ref_user,id_permission,value) SELECT DISTINCT '".$ENTREPRISE_USER['ref_user']."',id_permission,'ALL' FROM permissions;";
	$bdd->exec ($query);

	$query = "DELETE FROM users_permissions WHERE id_permission IN (42,43);";
	$bdd->exec ($query);
	//FONCTIONS
	
	$query = "INSERT INTO annu_collab_fonctions (ref_contact, id_fonction)
						VALUES ('".$ENTREPRISE_REF_CONTACT."', '1')";
	$bdd->exec ($query);
	
	// *************************************************************************************************************
	// CREATION DU STOCK ET MAGASIN
	$query = "INSERT INTO stocks (id_stock, lib_stock, ref_adr_stock, actif, abrev_stock)
						VALUES (1, 'Stock principal', '".$ENTREPRISE_ADRESSES[0]['ref_adresse']."', 1, 'Stock')";
	$bdd->exec ($query);
	
	$nom_entreprise = str_replace (CHR(13), " " ,str_replace (CHR(10), " " , $ENTREPRISE_NOM));

	$query = "REPLACE INTO `magasins_enseignes` (
						`id_mag_enseigne` ,
						`lib_enseigne`
						)
						VALUES (
						'1' , '".$nom_entreprise."'
						);";
	$bdd->exec ($query);

	$bdd->Commit();
	

	//mise à jour des textes de bas de page des pdf
	maj_configuration_file ("config_generale.inc.php", "maj_line", "\$PIED_DE_PAGE_GAUCHE_0 =", "\$PIED_DE_PAGE_GAUCHE_0 = \"".addslashes(stripslashes( str_replace("\n", " ", $ENTREPRISE_NOM)." - ".str_replace("\n", " ", $ENTREPRISE_ADRESSES[0]['text_adresse'])." ".$ENTREPRISE_ADRESSES[0]['code_postal']." ".$ENTREPRISE_ADRESSES[0]['ville']))."\";", "config/");

	maj_configuration_file ("config_generale.inc.php", "maj_line", "\$PIED_DE_PAGE_GAUCHE_1 =", "\$PIED_DE_PAGE_GAUCHE_1 = \"Siret: ".addslashes(stripslashes( $ENTREPRISE_NOTE))."\";", "config/");
	
	maj_configuration_file ("config_generale.inc.php", "maj_line", "\$PIED_DE_PAGE_DROIT_0 =", "\$PIED_DE_PAGE_DROIT_0 = \"Site Internet: ".addslashes(stripslashes( $url_site))."\";", "config/");
	
	maj_configuration_file ("config_generale.inc.php", "maj_line", "\$PIED_DE_PAGE_DROIT_1 =", "\$PIED_DE_PAGE_DROIT_1 = \"Email: ".addslashes(stripslashes( $ENTREPRISE_COORDONNEES[0]['email']))."\";", "config/");
	
	//générer image du entete_doc_pdf

	$source = imagecreatefromjpeg("fichiers/images/entete_doc_pdf.jpg");
	$nom_contact = explode("\n" , $ENTREPRISE_NOM);
	$l = 60 ;
	$c = 10 ;
	$size = 30;
	$couleur = imagecolorallocate($source, 0, 0, 0);
	$font = 'fichiers/arial.ttf';
	
	foreach ($nom_contact as $line) {
		imagettftext($source, $size, 0, $c, $l, $couleur, $font, utf8_encode(str_replace("\S","",str_replace("	","", $line))) );
		$l += 60;
	}
	imagejpeg($source, "fichiers/images/entete_doc_pdf.jpg", 100);
	
	
	//créations des taches post installation de l'administrateur
	require_once ("_tache_admin.class.php");
	
	$taches_admin = new tache_admin();
	
	$taches_admin->create_tache ("Renseignements sur l'entreprise", "Veuillez définir la date de debut d'activité de votre entreprise.", "configuration_activite.php") ;
	$taches_admin->create_tache ("Configuration du catalogue", "Etape importante et délicate, vous permet de définir les règles de gestion de votre catalogue d'article.", "configuration_catalogue.php") ;
	$taches_admin->create_tache ("Gérer les lieux de stockage", "Si vous possédez plusieurs lieux de stockage et désirez gérer le stock pour chacun d'entre eux.<br />Par exemple : Magasin, Entrepôt ", "catalogue_stockage.php") ;
	$taches_admin->create_tache ("Configuration les paramètres tarifaires", "Permet de définir les données par défaut pour la gestion des Prix ausi que les grilles de tarifs", "configuration_tarifs.php") ;
	$taches_admin->create_tache ("Gérer les points de vente", "Si vous possédez plusieurs points de vente. (Un magasin de e-commerce est considéré comme un point de vente à part entière)<br />Par exemple : Magasin Le Vigan, Magasin Montpellier, monsite.fr", "catalogue_magasins.php") ;
	$taches_admin->create_tache ("Gérer les catégories de clients", "Par exemple : Particulier, Revendeur<br />
(Vous pourrez paramétrer par la même occasion l'application automatique d'une grille tarifaire aux différentes catégories de clients.) <br />Permet également d'avoir des statistiques détaillées sur vos ventes.", "annuaire_gestion_categories_client.php") ;
	$taches_admin->create_tache ("Gérer les catégories de fournisseurs", "Par exemple : Prestataires de services, Grossistes.<br />Permet également d'avoir des statistiques détaillées sur vos achats.", "annuaire_gestion_categories_fournisseur.php") ;
	$taches_admin->create_tache ("Gestion des exercices comptables", "Veuillez définir la date de fin de votre premier exercice comptable.", "compta_exercices.php") ;
	$taches_admin->create_tache ("Gestion des catégories d'article du catalogue général", "Vous permet de définir les catégories et sous catégories d'articles. Ainsi, vous pouvez définir les différents taux de marge pour chacune d'elles.<br />Permet également d'obtenir des statistiques détaillées sur vos ventes.", "catalogue_categorie.php") ;
	$taches_admin->create_tache ("Gestion des comptes bancaires", "Permet de suivre vos comptes bancaires et d'automatiser certaines taches de comptabilité avec Lundi Matin Business.", "compta_compte_bancaire.php") ;
	$taches_admin->create_tache ("Gestion des TPE", "Si vous utiliser un Terminal de Paiement Electronique.<br />Permet de gérer les encaissements par Carte Bancaire.", "compta_compte_tpes.php") ;
	$taches_admin->create_tache ("Gestion des caisses", "Si vous utilisez plusieurs caisses (réparties sur 1 ou plusieurs points de vente).<br />Permet de gérer les encaissements par chèque et espèces.", "compta_compte_caisse.php") ;
	$taches_admin->create_tache ("Gestion des cartes bancaires", "Si vous possédez une carte bancaire.<br />
Permet de gérer les règlements par Carte Bancaire (décaissement).", "compta_compte_cbs.php") ;
	$taches_admin->create_tache ("Configuration PDF", "Permet de personnalisés les documents imprimés (Devis, Factures.)", "configuration_pdf.php") ;
	
	
	// On efface les fichier d'installation
	@unlink("install_lmb.config.php");
	@unlink("install_lmb.php");
	
		try {
			supprimer_repertoire("__install_lmb_files");
		} catch (Exception $e) {
			erreur($e);
		}
	
	$ref_user = $ENTREPRISE_USER['ref_user'];
	$code_user = $ENTREPRISE_USER['code'];
	echo "
	<form action=\"site/__user_login.php\" method=\"post\" name=\"form_login\" id=\"form_login\">
	<input type=\"hidden\" name=\"page_from\" value=\"profil_admin/#accueil.php\">
	<input type=\"hidden\" name=\"login\" value=\"$ref_user\">
	<input type=\"hidden\" name=\"code\" value=\"$code_user\">
	<input type=\"hidden\" name=\"id_profil_force\" value=\"2\">
	</form>
		<script type=\"text/javascript\">	
		setTimeout ('$(\"form_login\").submit()', 4000);
		$(\"sql_progress\").style.width = \"100%\";
		$(\"prog_ins_sql\").innerHTML = $(\"prog_ins_sql\").innerHTML + \"Lancement de Lundi Matin Business\";
		</script>
	";

	@file($distant_install_stat."install_states.php?iui=".$iui."&etape=6&ref_parrain=".$ref_parrain);
break;


}




// *********************************************************************************************************
// ******************************************  Librairie de fonctions  *************************************
// *********************************************************************************************************


// *********************************************************************************************************
// ******************************************  Gestion des erreurs *****************************************
// *********************************************************************************************************
function erreur ($texte) {
	global $distant_install_stat;
	global $iui;
	global $etape;
	
	// Génération de l'affichage
	$menu = menu(substr($etape, 0, 1));

	// Contenu de la page
	$contenu_page = "
	Erreur à l'installation: 
	<span id='view_rapport' style='cursor: pointer;' onClick='javascript:document.getElementById(\"erreur_report\").style.display=\"\";' >cliquez ici</span>
			<div id='erreur_report' style='display: none;'>
			
			<div  class=\"title_content\">Une erreur est survenue durant l'installation</div>".$texte."
			<br />
			<br />
			Veuillez contacter <a href=\"mailto:dev_team@lundimatin.fr?subject=Erreur installation LMB&body=".substr_replace("\n", "", substr_replace("\r", "", $texte))."\" >l'équipe de Lundi Matin</a> pour signaler ce problème.
			</div>";
	//envois du rapport d'erreur
	$r_url = $distant_install_stat."install_erreur_report.php?iui=".$iui."&etape=".$etape."&erreur=".urlencode(nl2br($texte));
	
	@file($r_url);		
	
	//affichage de la page
	
	header('Content-type: text/html; charset=iso-8859-15');
	echo ( $contenu_page);

	exit();
}



// *********************************************************************************************************
// ******************************************  Connection FTP & Downloads  *********************************
// *********************************************************************************************************

function connect_ftp () {
	global $ftp_serveur;

	$ftp_id_connect 	= ftp_connect($ftp_serveur);
	$ftp_login_result 	= ftp_login($ftp_id_connect, "anonymous", ""); 
	ftp_pasv($ftp_id_connect, true);
	
	// Vérification de la connexion
	if ((!$ftp_id_connect) || (!$ftp_login_result)) {
		$error = "La connexion FTP a échoué : ".$ftp_serveur." "; 
		erreur ($error);
		exit; 
	}
	return $ftp_id_connect;
}


// Download l'ensemble des fichiers du code source
function download_lmb_files() {
	global $local_install_dir;
	global $distant_install_ftp;
	global $distant_install_ftp_files;

	global $xml_liste_fichiers;
	global $total_size;

	// Connexion au serveur FTP
	$ftp_id_connect = connect_ftp();
	
	// Téléchargement du fichier XML et chronométrage pour obtenir un délai estimé
	set_time_limit(300);
	$debut_download = microtime(true);
	if (!ftp_get ($ftp_id_connect, $local_install_dir.$xml_liste_fichiers, $distant_install_ftp.$xml_liste_fichiers, FTP_BINARY)) {
		$error = "Impossible de récupérer la liste des fichiers d'installation. (Fichier XML).<br /> Essayez l'installation manuelle.<br />
				Pour plus de renseignement: <br />
				<a href='http://www.lundimatin.fr/site/forum/viewtopic.php?f=3&t=14' target='_blank'>
					http://www.lundimatin.fr/site/forum/viewtopic.php?f=3&t=14</a>";
		erreur($error);
	}
	$fin_download 	= microtime(true);
	$downloaded 	= filesize ($local_install_dir.$xml_liste_fichiers);

	// Lecture de ce fichier
	$xml_infos = read_xml_file();
	
	// Inscription des informations sur l'état du téléchargement 
	$total_size = $xml_infos['install_infos'][0]['TOTAL_SIZE'];
	make_download_state (0, 0, $fin_download);

	// Création de l'arborescence des répertoires
	$dir_list = $xml_infos['install_dirs'];
	foreach ($dir_list as $dir) {
		@mkdir ($dir['SRC']);
	}

	// Téléchargement des fichiers 1 à 1
	$files_list = $xml_infos['install_files'];
	foreach ($files_list as $file) {
		set_time_limit(300);

		// Téléchargement du fichier
		ftp_get ($ftp_id_connect, "./".$file['SRC'], $distant_install_ftp_files.$file['SRC'], FTP_BINARY);
		
		// Inscription des informations sur l'état du téléchargement
		$downloaded 	+= filesize ("./".$file['SRC']);
		make_download_state ($downloaded, $file['ID']);
	}
	//Fin du téléchargement des fichiers
	
	//Vérification au moins une fois du bon téléchargement des fichiers
	foreach ($files_list as $file) {
		set_time_limit(300);
		//le fichier
		if (!file_exists ("./".$file['SRC'])) {
			// Téléchargement du fichier
			ftp_get ($ftp_id_connect, "./".$file['SRC'], $distant_install_ftp_files.$file['SRC'], FTP_BINARY);
		}
	}
	
	//relance de la vérification
	$liste_missing_files = array();
	foreach ($files_list as $file) {
		set_time_limit(300);
		//le fichier
		if (!file_exists ("./".$file['SRC'])) {
			$liste_missing_files[] = $file['SRC'];
		}
	}
	if (count($liste_missing_files)){
		$error = "Les fichiers suivants n'ont pas étés téléchargés <br />
		     <span id='view_rapport' style='cursor: pointer;' onClick='javascript:document.getElementById(\"view_missing_files\").style.display=\"\";' >Voir les fichiers manquants</span>
				 <div id='view_missing_files' style='display: none'>(".implode(", ", $liste_missing_files).")</div>.<br /> Essayez l'installation manuelle.<br />
				Pour plus de renseignement: <br />
				<a href='http://www.lundimatin.fr/site/forum/viewtopic.php?f=3&t=14' target='_blank'>
					http://www.lundimatin.fr/site/forum/viewtopic.php?f=3&t=14</a>";
		erreur($error);
	}
	return true;
}


// Download l'ensemble des fichiers nécessaires à l'installation de la base de données
function download_lmb_bdd() {	
	global $local_install_bdd;
	global $distant_install_bdd_files;

	global $xml_liste_fichiers;
	global $total_size;

	
	// Connexion au serveur FTP
	$ftp_id_connect = connect_ftp();
	
	// Lecture de la liste des fichiers à télécharger
	set_time_limit(300);
	$bdd_files = ftp_nlist($ftp_id_connect, $distant_install_bdd_files);
	
	
	// Calcul du poids total de ces fichiers
	$total_size = 0;
	foreach ($bdd_files as $file) {
		$total_size += ftp_size($ftp_id_connect, $distant_install_bdd_files.$file);
	}
	//
	make_download_state (0, 0);

	//création du fichier tmp de bdd
	@mkdir($local_install_bdd, 0777);
	
	// Téléchargement des fichiers 1 à 1
	for ($i=0; $i<count($bdd_files); $i++) {
			$file = $bdd_files[$i];
			if ($file[0]=='/'){$file = substr($file,1);}
			$file = substr($file,strrpos($file,"/")+1);
			
		if ($file != "." && $file !=  "..") {
	
			// Téléchargement du fichier
			set_time_limit(300);
			ftp_get ($ftp_id_connect, $local_install_bdd.$file, $distant_install_bdd_files.$file, FTP_BINARY);
		
			// Inscription des informations sur l'état du téléchargement
			$downloaded 	+= filesize ($local_install_bdd.$file);
			make_download_state ($downloaded, $i);
		}
	}
	
	return true;
}


// Télécharge la dernière version de l'installeur
function download_lmb_installeur () {
	global $distant_install_ftp;
	global $install_file_name;

	// Connexion au serveur FTP
	$ftp_id_connect = connect_ftp();
	
	// Téléchargement du dernier fichier d'installation
	if (!ftp_get ($ftp_id_connect, $install_file_name.".tmp", $distant_install_ftp.$install_file_name, FTP_BINARY)) {
		$erreur = " Le fichier d'installation n'est pas à jour. <br />
								L'import automatique via FTP de la nouvelle version semble ne pas fonctionner<br /><br />
								
								Veuillez le télécharger à nouveau sur www.lundimatin.fr ";
		erreur($erreur);
	}
	
	// Suppression du fichier d'installation actuel pour le remplacer
	if (!unlink($install_file_name)) {
		$error = " Vous ne possedez pas les droits en écriture sur le fichier d'installation. (".$install_file_name.")<br />
					La version de ce fichier n'est pas à jour. <br /><br />

					Veuillez télécharger à nouveau sur www.lundimatin.fr";
		erreur($error);
	}
	rename($install_file_name.".tmp", $install_file_name);

	return true;
}





// *********************************************************************************************************
// ******************************************  Affichage   *************************************************
// *********************************************************************************************************

function Response_html ($menu = "", $content = "") {
	global $distant_install_url;
 	global $distant_install_js;
 	global $distant_install_css;
 	global $distant_install_images;

	
	header('Content-type: text/html; charset=iso-8859-1');
	header('Cache-Control: private, no-cache="set-cookie"');
	header('Expires: 0');
	header('Pragma: no-cache');
	
	$content_html = '
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<title>Installation de LundiMatin Business</title>
	<link href="'.$distant_install_css.'_install_style.css" rel="stylesheet" type="text/css" />
	<script src="'.$distant_install_js.'prototype.js"/></script>
	<script src="'.$distant_install_js.'selectupdater.js"/></script>
	<script src="'.$distant_install_js.'script.js"/></script>
	</head>
	<body>
	<div class="header" style="background-image:url('.$distant_install_images.'head_bg.gif); background-repeat:repeat-x; height:61px">
	<div class="title_install">Lundi Matin Business <span class="compl_title">Le logiciel qui réveille votre activité !</span></div>
	</div>
	<br />
	<div class="radius_main">
		<table class="conteneir">
		<tr>
			<td class="bgmain_menu">
			'.$menu.'
			</td>
			<td class="content"><div style="display:block">'.$content.'</div></td>
		</tr>
	</table>
</div>
</body>
	</html>';
	
	echo $content_html;
	
	return true;
}


// Génération du menu
function menu($etape) {
	
	$content_menu = '
	<ul>
	<li id="menu_etape_1" class="bgmain_menuli">1. Bienvenue</li>
	<li id="menu_etape_2" class="bgmain_menuli">2. Sécurité et Confidentialité</li>
	<li id="menu_etape_3" class="bgmain_menuli">3. Configuration système </li>
	<li id="menu_etape_4" class="bgmain_menuli">4. Vos informations</li>
	<li id="menu_etape_5" class="bgmain_menuli">5. Installation</li>
	</ul>
	<script type="text/javascript">
	//highlight de la ligne correspondant à l\'étape
	$("menu_etape_'.$etape.'").className = "menu_li";
	</script>
	';
	
	return $content_menu;
}


// Génération des formulaires de connexions pour la BDD et le FTP
function BDD_form() {
	global $_REQUEST;
	global $iui;
	
	$bdd_hote = ""; 
	$bdd_user = "";  
	$bdd_pass = ""; 
	$bdd_base = "";
	$bdd_creer = 0;
	$bdd_creer_check = 'checked = "checked"';
	
	//on réinjecte dans le fomulaire les $_REQUEST au cas des erreurs aient été détectées
	if (isset($_REQUEST["bdd_hote"])) {$bdd_hote = $_REQUEST["bdd_hote"]; }
	if (isset($_REQUEST["bdd_user"])) {$bdd_user = $_REQUEST["bdd_user"]; }
	if (isset($_REQUEST["bdd_pass"])) {$bdd_pass = $_REQUEST["bdd_pass"]; }
	if (isset($_REQUEST["bdd_base"])) {$bdd_base = $_REQUEST["bdd_base"]; }
	if (isset($_REQUEST["bdd_creer"])) {$bdd_creer = 1;}
  if (!isset($_REQUEST["bdd_creer"]) && isset($_REQUEST["bdd_base"])) {$bdd_creer_check = "";}
	
	$content_form = '
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td style="width:50%"><p class="green_text">Afin de proc&eacute;der &agrave; l&rsquo;installation de<br />
				Lundi Matin Business, merci de <br />
				renseigner les param&egrave;tres de connexion<br />
&agrave; la base de donn&eacute;es</p><p>&nbsp;</p>
			<p>Veuillez entrer le nom du serveur<br />
				sur lequel Lundi Matin Business<br />
			va &ecirc;tre install&eacute;.</p><p>&nbsp;</p>
			<p>Entrer le nom d&rsquo;utilisateur, le mot<br />
				de passe et le nom de la base de donn&eacute;es<br />
				que vous allez utiliser pour Lundi Matin<br />
			Business.</p></td>
			<td>
			<div class="grey_rounded">
			<form id="connexion_param" name="connexion_param" method="post" action="install_lmb.php">
			<input type="hidden" name="etape" value="3a"  />
			<div style="text-align:center" class="sub_t_install">Paramètres de Connexion</div>
			<div style="padding:15px">
				<table width="100%" border="0" cellspacing="3" cellpadding="0">
					<tr>
						<td align="center">
							<table width="100%" align="center" border="0" cellspacing="3" cellpadding="0">
								<tr>
									<td style="width:50%"><span class="bold_text">Nom du serveur:</span><br /> 
										<input type="text" name="bdd_hote" value="'.$bdd_hote.'" />
										<br />
										<br />
									</td>
									<td class="sbold_ita_text">
										Habituellement «localhost» ou «127.0.0.1» pour un serveur local					</td>
								</tr>
								<tr>
									<td><span class="bold_text">Nom d\'utilisateur:</span><br />
									 <input type="text" name="bdd_user" value="'.$bdd_user.'" />
									 <br />
									 <br />
								</td>
									<td class="sbold_ita_text">
										Soit «root» ou un nom d\'utilisateur fourni par votre hébergeur.							</td>
								</tr>
								<tr>
									<td><span class="bold_text">Mot de passe:</span><br />
									 <input type="text" name="bdd_pass" value="'.$bdd_pass.'" /><br />
									<br />
								</td>
									<td class="sbold_ita_text">
									Pour la sécurité du site, l\'utilisation d\'un mot de passe est recommandé pour le compte mysql.							</td>
								</tr>
								<tr>
									<td><span class="bold_text">Nom de la base de données:</span><br />
									 <input type="text" name="bdd_base" value="'.$bdd_base.'" />
									 <br />
									 <br />
									</td>
									<td class="sbold_ita_text">
									Le choix du nom de la base de données est libre.
									</td>
								</tr>
								<tr>
									<td style="vertical-align: middle; text-align:left; line-height:24px" colspan="2"> 
									<input style="vertical-align: middle;" type="checkbox" name="bdd_creer" id="bdd_creer" '.$bdd_creer_check.'/>
									
									<span class="sbold_ita_text">Création automatique de la base de données si nécessaire</span>
									</td>
								</tr>
							</table>
						</td>
					<tr>
						<td >
							<div align="right">
							<input type="hidden" name="iui" value="'.$iui.'"  />
							<input type="submit" name="Submit" value="Tester" />
							</div>
						</td>
					</tr>
				</table>
			</div>
			</form>
			</div>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
	</table>
	';
	
	return $content_form;
}



// *********************************************************************************************************
// Fonctions de test de la configuration
// *********************************************************************************************************

// Vérification de la version php 
function test_php_version() {
	global $config_test_result;

	if (version_compare(PHP_VERSION, '5.2.0') < 0) {
		$config_test_result [] = array ("sys"=>'Version PHP '.PHP_VERSION, "requis"=>'PHP 5.2.0', "result"=>'erreur') ;
		return false;
	}
	return true;
}


// Vérification des droits en écriture local
function test_file_auth() {
	global $config_test_result;
	
	$bad_test_result = array ("sys"=>'Droits en écriture de fichiers absents', "requis"=>'Droits en écriture de fichiers requis', "result"=>'erreur') ;
	
	// Création d'un fichier test
	$test_file = @fopen("lmb_test.txt","w");
	@fclose($test_file);
	// Test de son existence
	if (!is_file("lmb_test.txt")) {
		$config_test_result[] = $bad_test_result;
		return false;
	}

	// Suppression du fichier de test
	@unlink("lmb_test.txt");
	if (is_file("lmb_test.txt")) {
		$config_test_result[] = $bad_test_result;
		return false;
	}

	// Création d'un dossier de test
	if (!@mkdir("lmb_test", 0777)) {
		$config_test_result[] = $bad_test_result;
		return false;
	}
	
	// Création d'un fichier dans le dossier que l'on efface
	$test_file = @fopen("lmb_test/lmb_test.txt","w");
	@fclose($test_file);	
	if (!is_file("lmb_test/lmb_test.txt")) {
		$config_test_result[] = $bad_test_result;
		return false;
	}
	@unlink("lmb_test/lmb_test.txt");
	if (is_file("lmb_test/lmb_test.txt")) {
		$config_test_result[] = $bad_test_result;
		return false;
	}

	// Suppression du dossier
	@rmdir("lmb_test");
	if (is_dir("lmb_test")) {
		$config_test_result[] = $bad_test_result;
		return false;
	}

	return true;
}


// Test de la version de mysql
function test_mysql() {
	global $config_test_result;
	
	if (!@extension_loaded('mysql')) {
		$config_test_result []= array ("sys"=>'Base de données MySQL absente', "requis"=>'Base de données MySQL Requis', "result"=>'erreur');
		return false;
	}
	/*
	if (version_compare(mysql_get_client_info(), '5.0') < 0) {
		$config_test_result []= array ("sys"=>'Base de données MySQL v'.mysql_get_client_info(), "requis"=>'Base de données MySQL v5.0', "result"=>'erreur') ;
		return false;
	}
	*/
	return true;
}

// Test de présence de PDO
function test_pdo() {
	global $config_test_result;
	
	if (!method_exists('PDO', 'exec')) {
		$config_test_result []= array ("sys"=>'L\'extension PDO est indisponible', "requis"=>'Extension PDO requise', "result"=>'erreur');
	}
}

// Test de présence de la librairie GD
function test_getimage_size() {
	global $config_test_result;
	
	if (!@extension_loaded('gd')) {
		$config_test_result []= array ("sys"=>'Librairie GD indisponible', "requis"=>'librairie GD disponible', "result"=>'erreur');
	}
}

// Test de présence de la fonction xml() 
function test_xml_dispo() {
	global $config_test_result;
	
	if (!@function_exists('xml_parser_create')) {
		$config_test_result []= array ("sys"=>'Support XML', "requis"=>'XML requis', "result"=>'erreur');
	}
}


function test_bdd () {
	global $bdd_hote; 
	global $bdd_user;  
	global $bdd_pass;
	global $bdd_base;
	global $ref_parrain;
	global $install_config_name;
	global $iui;
	global $bdd_creer;
	
	$text_retour = "";

// La case de création de base de données est cochée
if (isset ($bdd_creer) && $bdd_creer) {
	
	// Tente une connexion au serveur MySQL (en cas d'erreur je passe outre)
    $link = @mysql_connect($bdd_hote, $bdd_user, $bdd_pass);
	
    if (!$link) {
		$text_retour .= "<div class=\"red_info\">Impossible de se connecter au serveur MySQL : ". mysql_error().".<br /><br /></div>".BDD_form();
	return $text_retour ;
	}
	
	// Tente la création de la base de données
	$sql = "CREATE DATABASE ".$bdd_base;
	if (!mysql_query($sql, $link)) {
  	$text_retour .= "<div class=\"red_info\">Impossible de créer la base de données : ". mysql_error().".<br /><br /></div>";

	}

}

	// Tente d'établir une connexion avec la base de données
	try {
		$bdd = new PDO("mysql:host=".$bdd_hote."; dbname=".$bdd_base."", $bdd_user, $bdd_pass, NULL);
	} catch (Exception $e) {
		//	echo 'Exception reçue : ',  $e->getMessage(), "\n";
		$text_retour .= "<div class=\"red_info\">Les paramètres  de connexion à la base de données sont incorrectes<br />Veuillez contacter votre administrateur système ou votre prestataire en hébergement Internet pour obtenir les paramètres corrects.<br /><br /></div>".BDD_form();
		return $text_retour;
	}
	
	// Si pas d'erreur lors de la création, le texte de retour est écrasé
	$text_retour = "";


	// Test de création d'une table
	$query = "CREATE TABLE IF NOT EXISTS `table_test` (`test` FLOAT NULL) ENGINE = innodb;";
	$bdd->query($query);
	$table_test_ok = mysql_table_exists($bdd, "table_test");
	
	if (!$table_test_ok) {
		$text_retour .= "<div class=\"red_info\">Vos droits sur la base de données ".$bdd_base." sont insuffisants. (Impossible de créer une table)<br />Veuillez contacter votre administrateur système ou votre prestataire en hébergement Internet pour résoudre ce problème.<br /><br /></div>".BDD_form();
		return $text_retour;
	}


	// Test de supression d'une table 
	$query = "DROP TABLE IF EXISTS `table_test`;";
	$bdd->query($query);
	$table_test_deleted = mysql_table_exists($bdd, "table_test");
	
	if ($table_test_deleted) {
		$text_retour .= "<div class=\"red_info\">Vos droits sur la base de données ".$bdd_base." sont insuffisants. (Impossible de supprimer une table)<br />Veuillez contacter votre administrateur système ou votre prestataire en hébergement Internet pour résoudre ce problème.<br /><br /></div>".BDD_form();
		return $text_retour;
	}


	// Test d'existence d'une précédente installation (non bloquant)
	$table_annu_exist = mysql_table_exists($bdd, "annuaire");
	if($table_annu_exist) {
		$text_retour .= "<div class=\"red_info\">Le système d'installation a détecté une version précédente de l'application Lundi Matin Business<br />Si vous complétez le processus d'installation, les anciennes données seront archivées et sauvegardées.<br /><br /></div>";
	}
	$text_retour .= " La configuration du système est correcte.<br />
						La connexion à votre base de données s'est déroulée avec succès.<br />
	
										<form action=\"install_lmb.php\" method=\"POST\" id=\"licence_form\">
										<p>
										<input type=\"hidden\" name=\"etape\" value=\"4\"  />
		<input type=\"hidden\" name=\"iui\" value=\"".$iui."\"  />
										<input type=\"submit\" class=\"bt_suite\" name=\"go_etape_4\" class=\"bt_suite\" value=\"Suivant\" />
										</p>
										</form>";


	// Configuration de la base de données OK
	
	// On inscrit les informations dans le fichiers de config.
	if (!$file_config_bdd = @fopen ($DIR.$install_config_name, "w")) {
		$erreur = "Vous n'avez pas les droits en lecture sur le fichier ".$install_config_name." "; 
		erreur($erreur);
	}
	$file_content = "<?php
// *************************************************************************************************************
// PARAMETRES DU SERVEUR
// *************************************************************************************************************
// BASE DE DONNEE
\$bdd_hote = \"".addslashes($bdd_hote)."\"; 
\$bdd_user = \"".addslashes($bdd_user)."\";  
\$bdd_pass = \"".addslashes($bdd_pass)."\"; 
\$bdd_base = \"".addslashes($bdd_base)."\";

\$ref_parrain = \"".$ref_parrain."\";

?>";

	if (!fwrite ($file_config_bdd, $file_content)) {
		$erreur = "Vous n'avez pas les droits en écriture sur le fichier ".$install_config_name." "; 
		erreur($erreur);
	}
	fclose ($file_config_bdd);

	return $text_retour;
	
}

function supprimer_repertoire($dir)  { 
	$current_dir = opendir($dir); 
	while($entryname = readdir($current_dir))  { 
		if(is_dir("$dir/$entryname") and ($entryname != "." and $entryname!=".."))  { 
			supprimer_repertoire("${dir}/${entryname}"); 
		}  
		elseif($entryname != "." and $entryname!="..") { 
			unlink("${dir}/${entryname}"); 
		} 
	} //Fin tant que 
	
	closedir($current_dir); 
	rmdir(${dir}); 
} 

// Test de la presence d'une table
function mysql_table_exists($bdd, $table){
	global $bdd_base;
	
	$query_test = "SHOW TABLES FROM `".$bdd_base."` LIKE '".$table."' ";
	$result = $bdd->query($query_test);
	if($tmp = $result->fetchObject()) {
		return TRUE;
	}

	return FALSE;
} 


// Vérification intégrale des tables pré-existantes et sauvegarde le cas échéant
function save_existing_bdd ($tables) {
	global $bdd_base; 
	global $bdd;

	$query = "SHOW TABLES FROM `".$bdd_base."` ";
	$result = $bdd->query($query);
	$query  ="";
	
	while ($table = $result->fetchObject()) {
		$lib_table = $table->{"Tables_in_".$bdd_base};
		foreach($tables as $lmb_table) {
			if ($lib_table != trim(str_replace("\n","",$lmb_table))) { continue; }
			// Suppression d'une ancienne sauvegarde si besoin
			$query .= "
								SET FOREIGN_KEY_CHECKS=0; 
								DROP TABLE IF EXISTS zzz_".$lib_table."; 
								RENAME TABLE ".$lib_table." TO zzz_".$lib_table." ;
								DROP TABLE IF EXISTS ".$lib_table.";";
		}
	}
	if ($query) {
		$bdd->exec($query);
	}
		
	return true;
}




// *********************************************************************************************************
// Fonctions de lecture du fichier XML d'information sur le code source
// *********************************************************************************************************
	
// Lit le fichier d'information sur le code source.
function read_xml_file () {
	global $xml_liste_fichiers;
	global $local_install_dir;
	
	global $xml_infos;
	global $install_files;
	global $install_dirs;
	global $install_infos;
	
	// Création du parseur XML
	$parseurXML = xml_parser_create("ISO-8859-1");

	// Nom des fonctions à appeler lorsque des balises ouvrantes ou fermantes sont rencontrées
	xml_set_element_handler($parseurXML, "fonctionBaliseOuvrante" , "fonctionBaliseFermante");

	// Nom de la fonction à appeler lorsque du texte est rencontré
	xml_set_character_data_handler($parseurXML, "fonctionTexte");

	// Ouverture du fichier
	if (!$fp = @fopen($local_install_dir.$xml_liste_fichiers, "r")) {
		$erreur = "Impossible de lire le fichier XML (Listing des fichiers à Uploader)";
	}
	if (!$fp) erreur("Impossible d'ouvrir le fichier XML");

	// Lecture ligne par ligne
	while ( $ligneXML = fgets($fp, 1024)) {
		// Analyse de la ligne
		// REM: feof($fp) retourne TRUE s'il s'agit de la dernière ligne du fichier.
		xml_parse($parseurXML, $ligneXML, feof($fp)) or erreur("Fichier incorrect sur LM.fr");
	}

	xml_parser_free($parseurXML);
	fclose($fp);

	$xml_infos = array("install_files"=>$install_files, "install_dirs"=>$install_dirs, "install_infos"=>$install_infos);
	return $xml_infos;
}

// Fontion de lecture des balises ouvrantes
function fonctionBaliseOuvrante($parseur, $nomBalise, $tableauAttributs) {
	global $derniereBaliseRencontree;
	global $install_files;
	global $install_dirs;
	global $install_infos;
	
	$derniereBaliseRencontree = $nomBalise;

	switch ($nomBalise) {
			case "DIR": 
					$install_dirs[] = $tableauAttributs;
					break;
			case "FILE": 
					$install_files[] = $tableauAttributs;
					break;
			case "INSTALL": 
					$install_infos[] = $tableauAttributs;
					break;
	} 
}

// Fonction de traitement des balises fermantes
function fonctionBaliseFermante($parseur, $nomBalise) {
	// On oublie la dernière balise rencontrée
	global $derniereBaliseRencontree;

	$derniereBaliseRencontree = "";
}

//Fonction de traitement du texte qui est appelée par le "parseur" (non utilisée car pas de texte entre les balises)
function fonctionTexte($parseur, $texte)
{
}




// *********************************************************************************************************
// Fonctions de création du fichier d'état de téléchargement
// *********************************************************************************************************
function make_download_state ($downloaded, $id_file, $fin_download = 0) {
	/******************************
	* Structure du fichier :
	Taille à télécharger 
	Taille téléchargée
	Temps restant
	ID du Fichier en cours
	*/
	global $total_size;
	global $download_infos_file;
	global $debut_download;

	if (!$fin_download) { 
		$fin_download = microtime(true);
	}
	if (!$downloaded) { 
		$downloaded = 1;
	}
	
	// Calcul du temps restant à télécharger
	// Ko_restant * Durée écoulée / Ko_téléchargé
	$temps_restant = ($total_size - $downloaded) * ($fin_download - $debut_download) / $downloaded;

	//on plafonne le temps de téléchargement restant à 15 minutes
	if (($temps_restant/60000000) >= 15) {
		$temps_restant = 15*60000000;
	}
	
	$entete_download_state  = $total_size."\n";			// Taille totale à télécharger
	$entete_download_state .= $downloaded."\n";			// Taille téléchargée
	$entete_download_state .= $temps_restant."\n";		// Temps restant
	$entete_download_state .= $id_file."\n";			// ID du fichier en cours

	$infos_file = @fopen ($download_infos_file, "w");
	
	@fwrite ($infos_file, $entete_download_state."0\n0");
	@fclose($infos_file);
	
	return true;
}


// *********************************************************************************************************
// Fonctions de suivi statistique des installations
// *********************************************************************************************************
function create_unique_install_id () {
	$date = date ("Ymd");
	$id		= rand(0,99999);
	// Il doit y avoir plus jolie mais pas le temps de chercher ;)
	if ($id<10) { $id = "0000".$id; } elseif ($id<100) { $id = "000".$id; } elseif ($id<1000) { $id = "00".$id; } elseif ($id<10000) { $id = "0".$id; } 
	
	$unique_id = $date.$id;
	return $unique_id;
}

//fonction de maj des fichier de config (ici principalement utilisé dans le cas de maj_line
//ou l'on peu choisir soit la ligne dans le fichier à modifier, soit le nom de la variable à modifier
function maj_configuration_file ($filename, $action, $line_number, $line_texte = "", $dir_file) {

	// Suppression des espaces en fin de ligne & Ajout d'un saut de ligne
	$line_texte = rtrim($line_texte)."\n";

	$new_file = array();
	$old_file = file ($dir_file.$filename);

	switch ($action) {
		case "add_line":
			for ($i=0; $i<count($old_file); $i++) {
				if ($i == $line_number-1) { $new_file[] = $line_texte; }
				$new_file[] = $old_file[$i];
			}
		break;
		case "del_line":
			for ($i=0; $i<count($old_file); $i++) {
				if ($i == $line_number-1) { continue; }
				$new_file[] = $old_file[$i];
			}
		break;
		case "maj_line":
			//on vérifi que c'est un numéro de ligne qui est indiqué
			if (is_numeric($line_number)) {
				$new_file = $old_file;
				$new_file[$line_number-1] = $line_texte;
			} else {
				//alors on a indiqué le nom de la variabe que l'on vas chercher dans tout le fichier
				$new_file = $old_file;
				
				for ($i=0; $i<count($old_file); $i++) {
					if (substr_count($old_file[$i], $line_number)) {
						$new_file[$i] = $line_texte;
					}
				}
			}
		break;
	}
	// Création du nouveau fichier de configuration
	$new_file_id = fopen ($dir_file."tmp_".$filename, "w");
	foreach ($new_file as $line) {
		fwrite($new_file_id, $line);
	}
	fclose($new_file_id);

	// Remplacement du fichier existant
	unlink($dir_file.$filename);
	rename ($dir_file."tmp_".$filename, $dir_file.$filename);
	
	return true;
}

//fonction retournant l'existence d'un fichier (distant ou local)
function remote_file_exists ($url)
{
/*
   Return error codes:
   1 = Invalid URL host
   2 = Unable to connect to remote host
*/ 
   $head = "";
   $url_p = parse_url ($url);
 
   if (isset ($url_p["host"]))
   { $host = $url_p["host"]; }
   else
   { return false; }
 
   if (isset ($url_p["path"]))
   { $path = $url_p["path"]; }
   else
   { $path = ""; }
 
 	 restore_error_handler();
	 error_reporting(0);
   $fp = fsockopen ($host, 80, $errno, $errstr, 20);
	 
   if (!$fp)
   { return false; }
   else
   {
       $parse = parse_url($url);
       $host = $parse['host'];
     
       fputs($fp, "HEAD ".$url." HTTP/1.1\r\n" );
       fputs($fp, "HOST: ".$host."\r\n" );
       fputs($fp, "Connection: close\r\n\r\n" );
       $headers = "";
       while (!feof ($fp))
       { $headers .= fgets ($fp, 128); }
   }
   fclose ($fp);
   $arr_headers = explode("\n", $headers);
   $return = false;
   if (isset ($arr_headers[0]))
   { $return = strpos ($arr_headers[0], "404" ) === false; }
   return $return;
}
?>