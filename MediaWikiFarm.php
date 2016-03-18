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

$wgMediaWikiFarm = MediaWikiFarm::initialise( $GLOBALS['_SERVER']['HTTP_HOST'] );



# Create a MediaWiki farm
//$farm = new MediaWikiFarm( $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir );

# Select the wiki given the HTTP Host
//$farm->selectWikiFromHost( $GLOBALS['_SERVER']['HTTP_HOST'] );

# Select the configuration and export it
//$farm->getConfig();

var_dump( $wgMediaWikiFarm );echo "\n\n<br /><br />";

# Get client and wiki

$wvgClient = $wgMediaWikiFarm->variables['client'];
$wvgWiki = $wgMediaWikiFarm->variables['wiki'];

var_dump( $wvgClient );
var_dump( $wvgWiki );
echo "\n\n<br /><br />";


# Check existence

var_dump( $wgMediaWikiFarm->checkExistence() );
echo "\n\n<br /><br />";

if( !$wgMediaWikiFarm->checkExistence() ) {
	
	echo 'Error: unknown wiki.';
	exit;
}


$wgConf->suffixes = array( $wvgClient );

// Wikis: a simple list of the wikis for the requested client, e.g. array( 'da', 'cv' )
$wvgClientWikis = $wgMediaWikiFarm->readFile( $wgMediaWikiFarmConfigDir.'/'.$wvgClient.'/wikis.yml' );
$wvgVersion = false;
foreach( $wvgClientWikis as $wiki => $value ) {
	$wgConf->wikis[] = $wiki.'-'.$wvgClient;
}

// Get version
$wvgVersion = $wvgClientWikis[$wvgWiki];

if( !preg_match( '/^1\.\d{1,2}/', $wvgVersion ) ) {
	echo 'Error: unknown wiki.';
	exit;
}

exit;

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

