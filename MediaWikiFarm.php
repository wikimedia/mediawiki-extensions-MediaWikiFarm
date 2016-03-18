<?php

use Symfony\Component\Yaml\Yaml;

# Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

/**
 * Configuration directory.
 * 
 * This parameter should be specified in your LocalSettings.php, before the require_once.
 * The value must be a readable directory. It is recommended this directory is not readable
 * from the Web.
 */
$wgMediaWikiFarmConfigDir = '/etc/mediawiki';

$wgMediaWikiFarmConfigDir = '/srv/www/mediawiki-farm/config';




/**
 * Code.
 * 
 * Please do not remove the following lines.
 */

require_once "$IP/extensions/MediaWikiFarm/src/MediaWikiFarm.php";

MediaWikiFarm::initialise();



# Create a MediaWiki farm
//$farm = new MediaWikiFarm( $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir );

# Select the wiki given the HTTP Host
//$farm->selectWikiFromHost( $GLOBALS['_SERVER']['HTTP_HOST'] );

# Select the configuration and export it
//$farm->getConfig();

function wvgGetWikiFromURL() {
	
	if( !preg_match( '/^([a-zA-Z0-9]+)-([a-zA-Z0-9]+)\.example.com$/', $GLOBALS['_SERVER']['HTTP_HOST'], $matches ) ) {
		echo 'Error: unknown wiki.';
		exit;
	}
	
	return array( $matches[2], $matches[1] );
}

list( $wvgWiki, $wvgClient ) = wvgGetWikiFromURL();

// Suffixes (clients): only the current client is saved to avoid any information leak to other clients, e.g. array( 'wikipedia' )
$wgConf->suffixes = array( $wvgClient );
if( !in_array( $wvgClient, Yaml::parse( file_get_contents( $wgMediaWikiFarmConfigDir . '/clients.yml' ) ) ) ) {
	echo 'Error: unknown wiki.';
	exit;
}

// Wikis: a simple list of the wikis for the requested client, e.g. array( 'da', 'cv' )
$wvgClientWikis = Yaml::parse( file_get_contents( $wgMediaWikiFarmConfigDir.'/'.$wvgClient.'/wikis.yml' ) );
$wvgVersion = false;
foreach( $wvgClientWikis as $wiki => $value ) {
	$wgConf->wikis[] = $wiki.'-'.$wvgClient;
}

if( !in_array( $wvgWiki.'-'.$wvgClient, $wgConf->wikis ) ) {
	echo 'Error: unknown wiki.';
	exit;
}

// Get version
$wvgVersion = $wvgClientWikis[$wvgWiki];

if( !preg_match( '/^1\.\d{1,2}/', $wvgVersion ) ) {
	echo 'Error: unknown wiki.';
	exit;
}

// Obtain the global configuration
$wvgGlobals = MediaWikiFarm::getMediaWikiConfig( $wvgWiki, $wvgClient, $wvgVersion, $wgConf,
                                                 array( 'codeDir' => $wgMediaWikiFarmCodeDir,
                                                        'cacheFile' => '/tmp/mw-cache/conf-$version-$wiki-$suffix',
                                                        'generalYamlFilename' => '/InitialiseSettings.yml',
                                                        'suffixedYamlFilename' => '/$suffix/InitialiseSettings.yml',
                                                        'privateYamlFilename' => '/PrivateSettings.yml',
                                                      )
);

// Load general MediaWiki configuration
MediaWikiFarm::loadMediaWikiConfig( $wvgGlobals['general'] );

// Set system parameters
$wgUploadDirectory = $wgMediaWikiFarmConfigDir.'/'.$wvgClient.'/'.$wvgWiki.'/images';
$wgCacheDirectory = $wgMediaWikiFarmConfigDir.'/'.$wvgClient.'/'.$wvgWiki.'/cache';

// Load skins with the require_once mechanism
foreach( $wvgGlobals['skins'] as $skin => $value ) {
	
	if( $value['_loading'] == 'require_once' )
		require_once "$IP/skins/$skin/$skin.php";
}

// Load skin configuration
MediaWikiFarm::loadSkinsConfig( $wvgGlobals['skins'] );

// Load extensions with the require_once mechanism
foreach( $wvgGlobals['extensions'] as $extension => $value ) {
	
	if( $value['_loading'] == 'require_once' )
		require_once "$IP/extensions/$extension/$extension.php";
}

// Load extension configuration
MediaWikiFarm::loadExtensionsConfig( $wvgGlobals['extensions'] );

// L’éditeur visuel cherchant toujours à se faire remarquer par les sysadmins, la
// ligne suivante est nécessaire tant qu’il est chargé avec require_once, car
// l’inclusion écrase cette valeur (même si spécifiée dans les fichiers YAML)
$wgDefaultUserOptions['visualeditor-enable'] = 1;

