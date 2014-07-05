<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.index.progress";
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');

$GLOBALS["PREFIX"]="/usr/share/artica-postfix/bin/wp-cli.phar";
$GLOBALS["SUFFIX"]="--path=\"/usr/share/wordpress-src\" --allow-root --debug --no-color";



$GLOBALS["SINGLE_DEBUG"]=false;
$GLOBALS["NOT_FORCE_PROXY"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["CHANGED"]=false;
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--force-nightly#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;$GLOBALS["FORCE"]=true;$GLOBALS["FORCE_NIGHTLY"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string'," Fatal..:");
	ini_set('error_append_string',"\n");
}
$GLOBALS["OUTPUT"]=true;
if($argv[1]=="--scan"){scan();exit;}
config($argv[1]);

function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	if($GLOBALS["VERBOSE"]){echo "[$pourc]: $text\n";}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/freeweb.rebuild.progress", serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}

function config($servername){
	$GLOBALS["SERVICE_NAME"]="Wordpress $servername";
	$unix=new unix();
	$q=new mysql();
	$cp=$unix->find_program("cp");
	$sock=new sockets();
	$Salts=null;
	$DB_HOST=$q->mysql_server;
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: MySQL host: $DB_HOST\n";}
	
	if(  ($q->mysql_server=="127.0.0.1") OR ($q->mysql_server=="localhost") OR ($q->mysql_server=="localhost:") ){
		if($q->SocketPath==null){$q->SocketPath="/var/run/mysqld/mysqld.sock";}
		$DB_HOST="localhost:$q->SocketPath";
	}
	
	
	build_progress("$servername: {testing_configuration}",40);
	
	$free=new freeweb($servername);
	$WORKING_DIRECTORY=$free->www_dir;
	@unlink("$WORKING_DIRECTORY/wp-config.php");
	
	
	if(!scan($WORKING_DIRECTORY)){
		build_progress("$servername: {installing}...",42);
		@mkdir($WORKING_DIRECTORY);
		shell_exec("$cp -rf /usr/share/wordpress-src/* $WORKING_DIRECTORY/");
		if(!scan($WORKING_DIRECTORY)){
			build_progress("$servername: {installing} {failed}...",110);
			return;
		}
		
	}
	
	
	$wordpressDB=$free->mysql_database;
	if($wordpressDB==null){$wordpressDB=$free->CreateDatabaseName();}
	$WordPressDBPass=$free->mysql_password;
	$DB_USER=$free->mysql_username;
	if($DB_USER==null){
			$DB_USER="wordpress";
			$free->mysql_username=$DB_USER;
			$free->CreateSite(true);
	}
	if($WordPressDBPass==null){
		$WordPressDBPass=md5(time());
		$free->mysql_password=$WordPressDBPass;
		$free->CreateSite(true);
			
	}
	
	$DB_PASSWORD=$WordPressDBPass;
	
	if(is_file("$WORKING_DIRECTORY/salts.php")){
		$Salts=@file_get_contents("$WORKING_DIRECTORY/salts.php");
	}
	
	if($Salts==null){
		$TMP=$unix->FILE_TEMP();
		build_progress("$servername: Acquiring Salts...",44);
		$curl=new ccurl("https://api.wordpress.org/secret-key/1.1/salt/");
		if(!$curl->GetFile("$TMP")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: Unable to download salts !!\n";}
			build_progress("$servername: Acquiring Salts {failed}...",110);
			return;
		}
		
		
		$ASASLT=false;
		$fa=explode("\n",@file_get_contents($TMP));
		@unlink($TMP);
		while (list ($num, $ligne) = each ($fa)){
			if(preg_match("#define\(#", $ligne)){$ASASLT=true;break;}
			
		}
				
		if(!$ASASLT){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: Unable to download salts !!\n";}
			build_progress("$servername: Acquiring Salts {failed}...",110);
			return;
		}		
		
		@file_put_contents("$WORKING_DIRECTORY/salts.php", @implode("\n", $fa));
	}
	
	
	build_progress("$servername: checking...",48);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: MySQL host...........: \"$DB_HOST\"\n";}
	if(!$q->DATABASE_EXISTS($wordpressDB)){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: Create MySQL database: \"$wordpressDB\"\n";}
		$q->CREATE_DATABASE($wordpressDB);
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: MySQL database.......: \"$wordpressDB\"\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: MySQL user...........: \"$DB_USER\"\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: MySQL Password.......: \"$DB_PASSWORD\"\n";}
	$q->PRIVILEGES($DB_USER,$WordPressDBPass,$wordpressDB);
	
	


	
$f[]="<?php";
$f[]=$Salts;
$f[]="/**";
$f[]=" * The base configurations of the WordPress.";
$f[]=" *";
$f[]=" * This file has the following configurations: MySQL settings, Table Prefix,";
$f[]=" * Secret Keys, WordPress Language, and ABSPATH. You can find more information";
$f[]=" * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing";
$f[]=" * wp-config.php} Codex page. You can get the MySQL settings from your web host.";
$f[]=" *";
$f[]=" * This file is used by the wp-config.php creation script during the";
$f[]=" * installation. You don't have to use the web site, you can just copy this file";
$f[]=" * to \"wp-config.php\" and fill in the values.";
$f[]=" *";
$f[]=" * @package WordPress";
$f[]=" */";
$f[]="";
$f[]="// ** MySQL settings - You can get this info from your web host ** //";
$f[]="/** The name of the database for WordPress */";
$f[]="define('DB_NAME', '$wordpressDB');";
$f[]="";
$f[]="/** MySQL database username */";
$f[]="define('DB_USER', '$DB_USER');";
$f[]="";
$f[]="/** MySQL database password */";
$f[]="define('DB_PASSWORD', '$DB_PASSWORD');";
$f[]="";
$f[]="/** MySQL hostname */";
$f[]="define('DB_HOST', '$DB_HOST');";
$f[]="";
$f[]="/** Database Charset to use in creating database tables. */";
$f[]="define('DB_CHARSET', 'utf8');";
$f[]="";
$f[]="/** The Database Collate type. Don't change this if in doubt. */";
$f[]="define('DB_COLLATE', '');";
$f[]="";
$f[]="/**#@+";
$f[]=" * Authentication Unique Keys and Salts.";
$f[]=" *";
$f[]=" * Change these to different unique phrases!";
$f[]=" * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}";
$f[]=" * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.";
$f[]=" *";
$f[]=" * @since 2.6.0";
$f[]=" */";

$f[]="";
$f[]="/**#@-*/";
$f[]="";
$f[]="/**";
$f[]=" * WordPress Database Table prefix.";
$f[]=" *";
$f[]=" * You can have multiple installations in one database if you give each a unique";
$f[]=" * prefix. Only numbers, letters, and underscores please!";
$f[]=" */";
$f[]="\$table_prefix  = 'wp_';";
$f[]="";
$f[]="/**";
$f[]=" * WordPress Localized Language, defaults to English.";
$f[]=" *";
$f[]=" * Change this to localize WordPress. A corresponding MO file for the chosen";
$f[]=" * language must be installed to wp-content/languages. For example, install";
$f[]=" * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German";
$f[]=" * language support.";
$f[]=" */";
$f[]="define('WPLANG', '');";
$f[]="";
$f[]="/**";
$f[]=" * For developers: WordPress debugging mode.";
$f[]=" *";
$f[]=" * Change this to true to enable the display of notices during development.";
$f[]=" * It is strongly recommended that plugin and theme developers use WP_DEBUG";
$f[]=" * in their development environments.";
$f[]=" */";
$f[]="define('WP_DEBUG', false);";
$f[]="";
$f[]="/* That's all, stop editing! Happy blogging. */";
$f[]="";
$f[]="/** Absolute path to the WordPress directory. */";
$f[]="if ( !defined('ABSPATH') )";
$f[]="	define('ABSPATH', dirname(__FILE__) . '/');";
$f[]="";
$f[]="/** Sets up WordPress vars and included files. */";
$f[]="require_once(ABSPATH . 'wp-settings.php');";
$f[]="?>";

@file_put_contents("$WORKING_DIRECTORY/wp-config.php", @implode("\n", $f));
build_progress("$servername: wp-config.php {done}...",50);

if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: $WORKING_DIRECTORY/wp-config.php done...\n";}
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: Testing configuration...\n";}


$admin=$unix->shellEscapeChars($free->groupware_admin);
$password=$unix->shellEscapeChars($free->groupware_password);
$WORKING_DIRECTORY_CMDLINE=$unix->shellEscapeChars($WORKING_DIRECTORY);
$cmd=array();
$cmd[]="/usr/share/artica-postfix/bin/wp-cli.phar core install";
$cmd[]="--url=\"$servername\"";
$cmd[]="--title=\"$servername\"";
$cmd[]="--admin_user=$admin";
$cmd[]="--admin_password=$password";
$cmd[]="--admin_email=$admin@$servername"; 
$cmd[]="--path=$WORKING_DIRECTORY_CMDLINE";
$cmd[]="--allow-root --debug --no-color 2>&1";
$cmdline=@implode(" ", $cmd);
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: $cmdline\n";}
exec($cmdline,$results1);
while (list ($num, $ligne) = each ($results1) ){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $servername: $ligne\n";}
}

build_progress("$servername: {enforce_security}",52);
secure_wp($WORKING_DIRECTORY);
}

function secure_wp($maindir){
	
	$ToDelete["wp-admin/import.php"]=true;
	$ToDelete["wp-admin/install.php"]=true;
	$ToDelete["wp-admin/install-helper.php"]=true;
	$ToDelete["wp-admin/upgrade.php"]=true;
	$ToDelete["wp-admin/upgrade-functions.php"]=true;
	$ToDelete["readme.html"]=true;
	$ToDelete["license.txt"]=true;
	
	while (list ($filename,$none) = each ($ToDelete) ){
		if(!is_file("$maindir/$filename")){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Removing $filename\n";}
		@unlink("$maindir/$filename");
	
	}
	
}


function scan($maindir){
	

	
$f['wp-admin/async-upload.php'] = True;
$f['wp-admin/admin-footer.php'] = True;
$f['wp-admin/options.php'] = True;
$f['wp-admin/ms-upgrade-network.php'] = True;
$f['wp-admin/user-edit.php'] = True;
$f['wp-admin/ms-admin.php'] = True;
$f['wp-admin/network/freedoms.php'] = True;
$f['wp-admin/network/plugins.php'] = True;
$f['wp-admin/network/site-settings.php'] = True;
$f['wp-admin/network/settings.php'] = True;
$f['wp-admin/network/user-edit.php'] = True;
$f['wp-admin/network/user-new.php'] = True;
$f['wp-admin/network/index.php'] = True;
$f['wp-admin/network/admin.php'] = True;
$f['wp-admin/network/theme-editor.php'] = True;
$f['wp-admin/network/edit.php'] = True;
$f['wp-admin/network/site-new.php'] = True;
$f['wp-admin/network/credits.php'] = True;
$f['wp-admin/network/plugin-editor.php'] = True;
$f['wp-admin/network/theme-install.php'] = True;
$f['wp-admin/network/themes.php'] = True;
$f['wp-admin/network/site-info.php'] = True;
$f['wp-admin/network/users.php'] = True;
$f['wp-admin/network/menu.php'] = True;
$f['wp-admin/network/about.php'] = True;
$f['wp-admin/network/update-core.php'] = True;
$f['wp-admin/network/profile.php'] = True;
$f['wp-admin/network/site-themes.php'] = True;
$f['wp-admin/network/setup.php'] = True;
$f['wp-admin/network/upgrade.php'] = True;
$f['wp-admin/network/sites.php'] = True;
$f['wp-admin/network/update.php'] = True;
$f['wp-admin/network/site-users.php'] = True;
$f['wp-admin/network/plugin-install.php'] = True;
$f['wp-admin/user-new.php'] = True;
$f['wp-admin/index.php'] = True;
$f['wp-admin/admin.php'] = True;
$f['wp-admin/edit-link-form.php'] = True;
$f['wp-admin/tools.php'] = True;
$f['wp-admin/theme-editor.php'] = True;
$f['wp-admin/edit.php'] = True;
$f['wp-admin/ms-sites.php'] = True;
$f['wp-admin/ms-themes.php'] = True;
$f['wp-admin/link.php'] = True;
$f['wp-admin/custom-background.php'] = True;
$f['wp-admin/edit-comments.php'] = True;
$f['wp-admin/network.php'] = True;
$f['wp-admin/edit-form-comment.php'] = True;
$f['wp-admin/ms-users.php'] = True;
$f['wp-admin/options-permalink.php'] = True;
$f['wp-admin/admin-header.php'] = True;
$f['wp-admin/options-general.php'] = True;
$f['wp-admin/my-sites.php'] = True;
$f['wp-admin/credits.php'] = True;
$f['wp-admin/options-head.php'] = True;
$f['wp-admin/media.php'] = True;
$f['wp-admin/admin-functions.php'] = True;
$f['wp-admin/edit-form-advanced.php'] = True;
$f['wp-admin/plugin-editor.php'] = True;
$f['wp-admin/link-parse-opml.php'] = True;
$f['wp-admin/revision.php'] = True;
$f['wp-admin/theme-install.php'] = True;
$f['wp-admin/load-styles.php'] = True;
$f['wp-admin/themes.php'] = True;
$f['wp-admin/comment.php'] = True;
$f['wp-admin/media-upload.php'] = True;
$f['wp-admin/export.php'] = True;
$f['wp-admin/widgets.php'] = True;
$f['wp-admin/media-new.php'] = True;
$f['wp-admin/users.php'] = True;
$f['wp-admin/menu-header.php'] = True;
$f['wp-admin/menu.php'] = True;
$f['wp-admin/ms-delete-site.php'] = True;
$f['wp-admin/moderation.php'] = True;
$f['wp-admin/ms-options.php'] = True;
$f['wp-admin/user/freedoms.php'] = True;
$f['wp-admin/user/user-edit.php'] = True;
$f['wp-admin/user/index.php'] = True;
$f['wp-admin/user/admin.php'] = True;
$f['wp-admin/user/credits.php'] = True;
$f['wp-admin/user/menu.php'] = True;
$f['wp-admin/user/about.php'] = True;
$f['wp-admin/user/profile.php'] = True;
$f['wp-admin/post.php'] = True;
$f['wp-admin/about.php'] = True;
$f['wp-admin/options-discussion.php'] = True;
$f['wp-admin/link-add.php'] = True;
$f['wp-admin/admin-ajax.php'] = True;
$f['wp-admin/update-core.php'] = True;
$f['wp-admin/admin-post.php'] = True;
$f['wp-admin/profile.php'] = True;
$f['wp-admin/ms-edit.php'] = True;
$f['wp-admin/maint/repair.php'] = True;
$f['wp-admin/load-scripts.php'] = True;
$f['wp-admin/options-reading.php'] = True;
$f['wp-admin/link-manager.php'] = True;
$f['wp-admin/edit-tags.php'] = True;
$f['wp-admin/nav-menus.php'] = True;
$f['wp-admin/edit-tag-form.php'] = True;
$f['wp-admin/upload.php'] = True;
$f['wp-admin/press-this.php'] = True;
$f['wp-admin/customize.php'] = True;
$f['wp-admin/setup-config.php'] = True;
$f['wp-admin/options-writing.php'] = True;
$f['wp-admin/update.php'] = True;
$f['wp-admin/plugin-install.php'] = True;
$f['wp-admin/includes/class-wp-plugins-list-table.php'] = True;
$f['wp-admin/includes/class-wp-themes-list-table.php'] = True;
$f['wp-admin/includes/class-wp-theme-install-list-table.php'] = True;
$f['wp-admin/includes/plugin.php'] = True;
$f['wp-admin/includes/user.php'] = True;
$f['wp-admin/includes/ms-deprecated.php'] = True;
$f['wp-admin/includes/class-wp-ms-sites-list-table.php'] = True;
$f['wp-admin/includes/class-wp-filesystem-ftpsockets.php'] = True;
$f['wp-admin/includes/import.php'] = True;
$f['wp-admin/includes/admin.php'] = True;
$f['wp-admin/includes/class-wp-posts-list-table.php'] = True;
$f['wp-admin/includes/file.php'] = True;
$f['wp-admin/includes/image.php'] = True;
$f['wp-admin/includes/bookmark.php'] = True;
$f['wp-admin/includes/class-ftp-sockets.php'] = True;
$f['wp-admin/includes/ms.php'] = True;
$f['wp-admin/includes/template.php'] = True;
$f['wp-admin/includes/class-wp-ms-themes-list-table.php'] = True;
$f['wp-admin/includes/class-wp-upgrader.php'] = True;
$f['wp-admin/includes/class-wp-filesystem-direct.php'] = True;
$f['wp-admin/includes/continents-cities.php'] = True;
$f['wp-admin/includes/media.php'] = True;
$f['wp-admin/includes/meta-boxes.php'] = True;
$f['wp-admin/includes/class-ftp.php'] = True;
$f['wp-admin/includes/schema.php'] = True;
$f['wp-admin/includes/revision.php'] = True;
$f['wp-admin/includes/theme-install.php'] = True;
$f['wp-admin/includes/class-wp-importer.php'] = True;
$f['wp-admin/includes/class-wp-plugin-install-list-table.php'] = True;
$f['wp-admin/includes/comment.php'] = True;
$f['wp-admin/includes/taxonomy.php'] = True;
$f['wp-admin/includes/export.php'] = True;
$f['wp-admin/includes/class-wp-upgrader-skins.php'] = True;
$f['wp-admin/includes/class-wp-links-list-table.php'] = True;
$f['wp-admin/includes/theme.php'] = True;
$f['wp-admin/includes/widgets.php'] = True;
$f['wp-admin/includes/ajax-actions.php'] = True;
$f['wp-admin/includes/class-ftp-pure.php'] = True;
$f['wp-admin/includes/menu.php'] = True;
$f['wp-admin/includes/class-wp-media-list-table.php'] = True;
$f['wp-admin/includes/class-pclzip.php'] = True;
$f['wp-admin/includes/class-wp-ms-users-list-table.php'] = True;
$f['wp-admin/includes/post.php'] = True;
$f['wp-admin/includes/misc.php'] = True;
$f['wp-admin/includes/update-core.php'] = True;
$f['wp-admin/includes/class-wp-filesystem-base.php'] = True;
$f['wp-admin/includes/class-wp-filesystem-ftpext.php'] = True;
$f['wp-admin/includes/class-wp-filesystem-ssh2.php'] = True;
$f['wp-admin/includes/deprecated.php'] = True;
$f['wp-admin/includes/nav-menu.php'] = True;
$f['wp-admin/includes/upgrade.php'] = True;
$f['wp-admin/includes/class-wp-list-table.php'] = True;
$f['wp-admin/includes/class-wp-terms-list-table.php'] = True;
$f['wp-admin/includes/image-edit.php'] = True;
$f['wp-admin/includes/list-table.php'] = True;
$f['wp-admin/includes/screen.php'] = True;
$f['wp-admin/includes/class-wp-comments-list-table.php'] = True;
$f['wp-admin/includes/dashboard.php'] = True;
$f['wp-admin/includes/update.php'] = True;
$f['wp-admin/includes/plugin-install.php'] = True;
$f['wp-admin/includes/class-wp-users-list-table.php'] = True;
$f['wp-admin/custom-header.php'] = True;
$f['wp-links-opml.php'] = True;
$f['index.php'] = True;
$f['wp-cron.php'] = True;
$f['wp-activate.php'] = True;
$f['wp-load.php'] = True;
$f['wp-signup.php'] = True;
$f['wp-login.php'] = True;
$f['wp-content/index.php'] = True;
$f['wp-content/plugins/index.php'] = True;
$f['wp-content/plugins/akismet/index.php'] = True;
$f['wp-content/plugins/akismet/class.akismet.php'] = True;
$f['wp-content/plugins/akismet/views/get.php'] = True;
$f['wp-content/plugins/akismet/views/notice.php'] = True;
$f['wp-content/plugins/akismet/views/config.php'] = True;
$f['wp-content/plugins/akismet/views/start.php'] = True;
$f['wp-content/plugins/akismet/views/strict.php'] = True;
$f['wp-content/plugins/akismet/views/stats.php'] = True;
$f['wp-content/plugins/akismet/class.akismet-widget.php'] = True;
$f['wp-content/plugins/akismet/class.akismet-admin.php'] = True;
$f['wp-content/plugins/akismet/wrapper.php'] = True;
$f['wp-content/plugins/akismet/akismet.php'] = True;
$f['wp-content/plugins/hello.php'] = True;
$f['wp-content/themes/index.php'] = True;
$f['wp-content/themes/twentythirteen/content-image.php'] = True;
$f['wp-content/themes/twentythirteen/single.php'] = True;
$f['wp-content/themes/twentythirteen/sidebar.php'] = True;
$f['wp-content/themes/twentythirteen/comments.php'] = True;
$f['wp-content/themes/twentythirteen/header.php'] = True;
$f['wp-content/themes/twentythirteen/content-link.php'] = True;
$f['wp-content/themes/twentythirteen/inc/back-compat.php'] = True;
$f['wp-content/themes/twentythirteen/inc/custom-header.php'] = True;
$f['wp-content/themes/twentythirteen/content-audio.php'] = True;
$f['wp-content/themes/twentythirteen/index.php'] = True;
$f['wp-content/themes/twentythirteen/search.php'] = True;
$f['wp-content/themes/twentythirteen/image.php'] = True;
$f['wp-content/themes/twentythirteen/taxonomy-post_format.php'] = True;
$f['wp-content/themes/twentythirteen/content-quote.php'] = True;
$f['wp-content/themes/twentythirteen/content-chat.php'] = True;
$f['wp-content/themes/twentythirteen/footer.php'] = True;
$f['wp-content/themes/twentythirteen/functions.php'] = True;
$f['wp-content/themes/twentythirteen/content-status.php'] = True;
$f['wp-content/themes/twentythirteen/tag.php'] = True;
$f['wp-content/themes/twentythirteen/author.php'] = True;
$f['wp-content/themes/twentythirteen/archive.php'] = True;
$f['wp-content/themes/twentythirteen/category.php'] = True;
$f['wp-content/themes/twentythirteen/sidebar-main.php'] = True;
$f['wp-content/themes/twentythirteen/page.php'] = True;
$f['wp-content/themes/twentythirteen/404.php'] = True;
$f['wp-content/themes/twentythirteen/content-none.php'] = True;
$f['wp-content/themes/twentythirteen/content-video.php'] = True;
$f['wp-content/themes/twentythirteen/content.php'] = True;
$f['wp-content/themes/twentythirteen/author-bio.php'] = True;
$f['wp-content/themes/twentythirteen/content-aside.php'] = True;
$f['wp-content/themes/twentythirteen/content-gallery.php'] = True;
$f['wp-content/themes/twentytwelve/content-image.php'] = True;
$f['wp-content/themes/twentytwelve/single.php'] = True;
$f['wp-content/themes/twentytwelve/sidebar.php'] = True;
$f['wp-content/themes/twentytwelve/comments.php'] = True;
$f['wp-content/themes/twentytwelve/header.php'] = True;
$f['wp-content/themes/twentytwelve/content-link.php'] = True;
$f['wp-content/themes/twentytwelve/inc/custom-header.php'] = True;
$f['wp-content/themes/twentytwelve/index.php'] = True;
$f['wp-content/themes/twentytwelve/search.php'] = True;
$f['wp-content/themes/twentytwelve/image.php'] = True;
$f['wp-content/themes/twentytwelve/content-quote.php'] = True;
$f['wp-content/themes/twentytwelve/footer.php'] = True;
$f['wp-content/themes/twentytwelve/functions.php'] = True;
$f['wp-content/themes/twentytwelve/content-status.php'] = True;
$f['wp-content/themes/twentytwelve/tag.php'] = True;
$f['wp-content/themes/twentytwelve/author.php'] = True;
$f['wp-content/themes/twentytwelve/archive.php'] = True;
$f['wp-content/themes/twentytwelve/sidebar-front.php'] = True;
$f['wp-content/themes/twentytwelve/category.php'] = True;
$f['wp-content/themes/twentytwelve/content-page.php'] = True;
$f['wp-content/themes/twentytwelve/page.php'] = True;
$f['wp-content/themes/twentytwelve/404.php'] = True;
$f['wp-content/themes/twentytwelve/page-templates/full-width.php'] = True;
$f['wp-content/themes/twentytwelve/page-templates/front-page.php'] = True;
$f['wp-content/themes/twentytwelve/content-none.php'] = True;
$f['wp-content/themes/twentytwelve/content.php'] = True;
$f['wp-content/themes/twentytwelve/content-aside.php'] = True;
$f['wp-content/themes/twentyfourteen/content-image.php'] = True;
$f['wp-content/themes/twentyfourteen/single.php'] = True;
$f['wp-content/themes/twentyfourteen/sidebar.php'] = True;
$f['wp-content/themes/twentyfourteen/comments.php'] = True;
$f['wp-content/themes/twentyfourteen/header.php'] = True;
$f['wp-content/themes/twentyfourteen/sidebar-content.php'] = True;
$f['wp-content/themes/twentyfourteen/content-link.php'] = True;
$f['wp-content/themes/twentyfourteen/inc/customizer.php'] = True;
$f['wp-content/themes/twentyfourteen/inc/template-tags.php'] = True;
$f['wp-content/themes/twentyfourteen/inc/featured-content.php'] = True;
$f['wp-content/themes/twentyfourteen/inc/widgets.php'] = True;
$f['wp-content/themes/twentyfourteen/inc/back-compat.php'] = True;
$f['wp-content/themes/twentyfourteen/inc/custom-header.php'] = True;
$f['wp-content/themes/twentyfourteen/content-audio.php'] = True;
$f['wp-content/themes/twentyfourteen/index.php'] = True;
$f['wp-content/themes/twentyfourteen/search.php'] = True;
$f['wp-content/themes/twentyfourteen/image.php'] = True;
$f['wp-content/themes/twentyfourteen/sidebar-footer.php'] = True;
$f['wp-content/themes/twentyfourteen/taxonomy-post_format.php'] = True;
$f['wp-content/themes/twentyfourteen/content-quote.php'] = True;
$f['wp-content/themes/twentyfourteen/footer.php'] = True;
$f['wp-content/themes/twentyfourteen/content-featured-post.php'] = True;
$f['wp-content/themes/twentyfourteen/featured-content.php'] = True;
$f['wp-content/themes/twentyfourteen/functions.php'] = True;
$f['wp-content/themes/twentyfourteen/tag.php'] = True;
$f['wp-content/themes/twentyfourteen/author.php'] = True;
$f['wp-content/themes/twentyfourteen/archive.php'] = True;
$f['wp-content/themes/twentyfourteen/category.php'] = True;
$f['wp-content/themes/twentyfourteen/content-page.php'] = True;
$f['wp-content/themes/twentyfourteen/page.php'] = True;
$f['wp-content/themes/twentyfourteen/404.php'] = True;
$f['wp-content/themes/twentyfourteen/page-templates/full-width.php'] = True;
$f['wp-content/themes/twentyfourteen/page-templates/contributors.php'] = True;
$f['wp-content/themes/twentyfourteen/content-none.php'] = True;
$f['wp-content/themes/twentyfourteen/content-video.php'] = True;
$f['wp-content/themes/twentyfourteen/content.php'] = True;
$f['wp-content/themes/twentyfourteen/content-aside.php'] = True;
$f['wp-content/themes/twentyfourteen/content-gallery.php'] = True;
$f['xmlrpc.php'] = True;
$f['wp-includes/plugin.php'] = True;
$f['wp-includes/class-wp-customize-manager.php'] = True;
$f['wp-includes/user.php'] = True;
$f['wp-includes/wp-diff.php'] = True;
$f['wp-includes/class-wp.php'] = True;
$f['wp-includes/vars.php'] = True;
$f['wp-includes/class-feed.php'] = True;
$f['wp-includes/ms-deprecated.php'] = True;
$f['wp-includes/feed-rss2-comments.php'] = True;
$f['wp-includes/pluggable-deprecated.php'] = True;
$f['wp-includes/post-template.php'] = True;
$f['wp-includes/class-oembed.php'] = True;
$f['wp-includes/cron.php'] = True;
$f['wp-includes/class-wp-admin-bar.php'] = True;
$f['wp-includes/feed-atom.php'] = True;
$f['wp-includes/theme-compat/sidebar.php'] = True;
$f['wp-includes/theme-compat/comments.php'] = True;
$f['wp-includes/theme-compat/header.php'] = True;
$f['wp-includes/theme-compat/footer.php'] = True;
$f['wp-includes/theme-compat/comments-popup.php'] = True;
$f['wp-includes/author-template.php'] = True;
$f['wp-includes/script-loader.php'] = True;
$f['wp-includes/feed-atom-comments.php'] = True;
$f['wp-includes/category-template.php'] = True;
$f['wp-includes/canonical.php'] = True;
$f['wp-includes/feed-rss.php'] = True;
$f['wp-includes/class.wp-scripts.php'] = True;
$f['wp-includes/template-loader.php'] = True;
$f['wp-includes/load.php'] = True;
$f['wp-includes/functions.wp-scripts.php'] = True;
$f['wp-includes/class.wp-styles.php'] = True;
$f['wp-includes/ms-settings.php'] = True;
$f['wp-includes/post-formats.php'] = True;
$f['wp-includes/class-wp-http-ixr-client.php'] = True;
$f['wp-includes/class-wp-walker.php'] = True;
$f['wp-includes/class-json.php'] = True;
$f['wp-includes/class-wp-ajax-response.php'] = True;
$f['wp-includes/meta.php'] = True;
$f['wp-includes/class-wp-image-editor-gd.php'] = True;
$f['wp-includes/atomlib.php'] = True;
$f['wp-includes/general-template.php'] = True;
$f['wp-includes/bookmark-template.php'] = True;
$f['wp-includes/bookmark.php'] = True;
$f['wp-includes/rss-functions.php'] = True;
$f['wp-includes/class-simplepie.php'] = True;
$f['wp-includes/nav-menu-template.php'] = True;
$f['wp-includes/template.php'] = True;
$f['wp-includes/admin-bar.php'] = True;
$f['wp-includes/link-template.php'] = True;
$f['wp-includes/class-pop3.php'] = True;
$f['wp-includes/date.php'] = True;
$f['wp-includes/pluggable.php'] = True;
$f['wp-includes/media.php'] = True;
$f['wp-includes/pomo/entry.php'] = True;
$f['wp-includes/pomo/po.php'] = True;
$f['wp-includes/pomo/mo.php'] = True;
$f['wp-includes/pomo/translations.php'] = True;
$f['wp-includes/pomo/streams.php'] = True;
$f['wp-includes/js/tinymce/wp-tinymce.php'] = True;
$f['wp-includes/js/tinymce/wp-mce-help.php'] = True;
$f['wp-includes/revision.php'] = True;
$f['wp-includes/compat.php'] = True;
$f['wp-includes/functions.php'] = True;
$f['wp-includes/class-wp-customize-section.php'] = True;
$f['wp-includes/comment.php'] = True;
$f['wp-includes/taxonomy.php'] = True;
$f['wp-includes/formatting.php'] = True;
$f['wp-includes/registration-functions.php'] = True;
$f['wp-includes/default-constants.php'] = True;
$f['wp-includes/class-smtp.php'] = True;
$f['wp-includes/http.php'] = True;
$f['wp-includes/theme.php'] = True;
$f['wp-includes/version.php'] = True;
$f['wp-includes/locale.php'] = True;
$f['wp-includes/class-wp-customize-widgets.php'] = True;
$f['wp-includes/widgets.php'] = True;
$f['wp-includes/category.php'] = True;
$f['wp-includes/class-wp-embed.php'] = True;
$f['wp-includes/rewrite.php'] = True;
$f['wp-includes/class-wp-customize-control.php'] = True;
$f['wp-includes/class-wp-error.php'] = True;
$f['wp-includes/kses.php'] = True;
$f['wp-includes/post-thumbnail-template.php'] = True;
$f['wp-includes/rss.php'] = True;
$f['wp-includes/class-wp-customize-setting.php'] = True;
$f['wp-includes/feed.php'] = True;
$f['wp-includes/query.php'] = True;
$f['wp-includes/l10n.php'] = True;
$f['wp-includes/ID3/module.audio-video.asf.php'] = True;
$f['wp-includes/ID3/module.audio.ogg.php'] = True;
$f['wp-includes/ID3/module.tag.lyrics3.php'] = True;
$f['wp-includes/ID3/module.tag.id3v2.php'] = True;
$f['wp-includes/ID3/module.audio.flac.php'] = True;
$f['wp-includes/ID3/module.audio-video.quicktime.php'] = True;
$f['wp-includes/ID3/module.audio.mp3.php'] = True;
$f['wp-includes/ID3/module.tag.id3v1.php'] = True;
$f['wp-includes/ID3/module.audio-video.matroska.php'] = True;
$f['wp-includes/ID3/module.audio-video.flv.php'] = True;
$f['wp-includes/ID3/getid3.lib.php'] = True;
$f['wp-includes/ID3/module.tag.apetag.php'] = True;
$f['wp-includes/ID3/module.audio.ac3.php'] = True;
$f['wp-includes/ID3/module.audio.dts.php'] = True;
$f['wp-includes/ID3/module.audio-video.riff.php'] = True;
$f['wp-includes/ID3/getid3.php'] = True;
$f['wp-includes/default-filters.php'] = True;
$f['wp-includes/class.wp-dependencies.php'] = True;
$f['wp-includes/post.php'] = True;
$f['wp-includes/ms-functions.php'] = True;
$f['wp-includes/capabilities.php'] = True;
$f['wp-includes/class-wp-image-editor.php'] = True;
$f['wp-includes/class-IXR.php'] = True;
$f['wp-includes/cache.php'] = True;
$f['wp-includes/feed-rdf.php'] = True;
$f['wp-includes/media-template.php'] = True;
$f['wp-includes/wp-db.php'] = True;
$f['wp-includes/option.php'] = True;
$f['wp-includes/class-phpass.php'] = True;
$f['wp-includes/shortcodes.php'] = True;
$f['wp-includes/deprecated.php'] = True;
$f['wp-includes/ms-blogs.php'] = True;
$f['wp-includes/class-wp-image-editor-imagick.php'] = True;
$f['wp-includes/nav-menu.php'] = True;
$f['wp-includes/ms-default-constants.php'] = True;
$f['wp-includes/class-wp-theme.php'] = True;
$f['wp-includes/functions.wp-styles.php'] = True;
$f['wp-includes/class-wp-editor.php'] = True;
$f['wp-includes/ms-default-filters.php'] = True;
$f['wp-includes/SimplePie/XML/Declaration/Parser.php'] = True;
$f['wp-includes/SimplePie/Misc.php'] = True;
$f['wp-includes/SimplePie/Credit.php'] = True;
$f['wp-includes/SimplePie/Sanitize.php'] = True;
$f['wp-includes/SimplePie/HTTP/Parser.php'] = True;
$f['wp-includes/SimplePie/Net/IPv6.php'] = True;
$f['wp-includes/SimplePie/Parser.php'] = True;
$f['wp-includes/SimplePie/gzdecode.php'] = True;
$f['wp-includes/SimplePie/Author.php'] = True;
$f['wp-includes/SimplePie/Caption.php'] = True;
$f['wp-includes/SimplePie/Exception.php'] = True;
$f['wp-includes/SimplePie/Core.php'] = True;
$f['wp-includes/SimplePie/Decode/HTML/Entities.php'] = True;
$f['wp-includes/SimplePie/Cache/DB.php'] = True;
$f['wp-includes/SimplePie/Cache/Base.php'] = True;
$f['wp-includes/SimplePie/Cache/File.php'] = True;
$f['wp-includes/SimplePie/Cache/Memcache.php'] = True;
$f['wp-includes/SimplePie/Cache/MySQL.php'] = True;
$f['wp-includes/SimplePie/Registry.php'] = True;
$f['wp-includes/SimplePie/Item.php'] = True;
$f['wp-includes/SimplePie/File.php'] = True;
$f['wp-includes/SimplePie/Parse/Date.php'] = True;
$f['wp-includes/SimplePie/Category.php'] = True;
$f['wp-includes/SimplePie/IRI.php'] = True;
$f['wp-includes/SimplePie/Locator.php'] = True;
$f['wp-includes/SimplePie/Restriction.php'] = True;
$f['wp-includes/SimplePie/Enclosure.php'] = True;
$f['wp-includes/SimplePie/Source.php'] = True;
$f['wp-includes/SimplePie/Copyright.php'] = True;
$f['wp-includes/SimplePie/Cache.php'] = True;
$f['wp-includes/SimplePie/Rating.php'] = True;
$f['wp-includes/SimplePie/Content/Type/Sniffer.php'] = True;
$f['wp-includes/ms-files.php'] = True;
$f['wp-includes/class-phpmailer.php'] = True;
$f['wp-includes/class-http.php'] = True;
$f['wp-includes/registration.php'] = True;
$f['wp-includes/comment-template.php'] = True;
$f['wp-includes/Text/Diff/Engine/xdiff.php'] = True;
$f['wp-includes/Text/Diff/Engine/string.php'] = True;
$f['wp-includes/Text/Diff/Engine/native.php'] = True;
$f['wp-includes/Text/Diff/Engine/shell.php'] = True;
$f['wp-includes/Text/Diff/Renderer.php'] = True;
$f['wp-includes/Text/Diff/Renderer/inline.php'] = True;
$f['wp-includes/Text/Diff.php'] = True;
$f['wp-includes/feed-rss2.php'] = True;
$f['wp-includes/update.php'] = True;
$f['wp-includes/default-widgets.php'] = True;
$f['wp-includes/class-wp-xmlrpc-server.php'] = True;
$f['wp-includes/class-snoopy.php'] = True;
$f['wp-includes/ms-load.php'] = True;
$f['wp-mail.php'] = True;
$f['wp-settings.php'] = True;
$f['wp-blog-header.php'] = True;
	

	
	while (list ($filename,$none) = each ($f) ){
		if(!is_file("$maindir/$filename")){return false;}
		
	}
	
	return true;
	
}

?>
