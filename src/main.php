<?php
/**
 * Main program, creating the MediaWikiFarm object, then loading MediaWiki configuration.
 * 
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */

# Protect against web entry
if( !defined( 'MEDIAWIKI' ) ) exit;

require_once __DIR__ . '/MediaWikiFarm.php';

$wgMediaWikiFarm = MediaWikiFarm::initialise( $GLOBALS['_SERVER']['HTTP_HOST'] );


/*
 * Check existence
 */

if( !$wgMediaWikiFarm->checkExistence() ) {
	
	echo 'Error: unknown wiki.';
	exit;
}


/*
 * MediaWiki
 */

// Load general MediaWiki configuration
$wgMediaWikiFarm->loadMediaWikiConfig();


// Set system parameters
$wvgClient = $wgMediaWikiFarm->variables['client'];
$wvgWiki = $wgMediaWikiFarm->variables['wiki'];

$wgUploadDirectory = '/srv/www/mediawiki-farm/data/'.$wvgClient.'/'.$wvgWiki.'/images';
$wgCacheDirectory = '/srv/www/mediawiki-farm/data/'.$wvgClient.'/'.$wvgWiki.'/cache';


/*
 * Skins
 */

# Load skins with the require_once mechanism
foreach( $wgMediaWikiFarm->params['globals']['skins'] as $skin => $value ) {
	
	if( $value['_loading'] == 'require_once' )
		require_once "$IP/skins/$skin/$skin.php";
}

# Load skins with the wfLoadSkin mechanism
$wgMediaWikiFarm->loadSkinsConfig();


/*
 * Extensions
 */

# Load extensions with the require_once mechanism
foreach( $wgMediaWikiFarm->params['globals']['extensions'] as $extension => $value ) {
	
	if( $value['_loading'] == 'require_once' )
		require_once "$IP/extensions/$extension/$extension.php";
}

# Load extensions with the wfLoadExtension mechanism
$wgMediaWikiFarm->loadExtensionsConfig();

// L’éditeur visuel cherchant toujours à se faire remarquer par les sysadmins, la
// ligne suivante est nécessaire tant qu’il est chargé avec require_once, car
// l’inclusion écrase cette valeur (même si spécifiée dans les fichiers YAML)
$wgDefaultUserOptions['visualeditor-enable'] = 1;

