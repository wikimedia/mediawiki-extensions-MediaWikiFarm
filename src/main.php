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

# Class where the logic is
require_once __DIR__ . '/MediaWikiFarm.php';


/*
 * Verify existence of the wiki
 */

$wgMediaWikiFarm = MediaWikiFarm::initialise();

if( !$wgMediaWikiFarm->checkExistence() ) {
	
	$version = $_SERVER['SERVER_PROTOCOL'] && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.0' ? '1.0' : '1.1';
	header( "HTTP/$version 404 Not Found" );
	echo 'Error: unknown wiki.';
	exit;
}


/*
 * MediaWiki
 */

# Load general MediaWiki configuration
$wgMediaWikiFarm->loadMediaWikiConfig();


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


/*
 * Load other parameters
 */

foreach( $wgMediaWikiFarm->params['globals']['execFiles'] as $execFile ) {
	
	@include $execFile;
}

