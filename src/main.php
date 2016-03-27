<?php
/**
 * Main program, creating the MediaWikiFarm object, then loading MediaWiki configuration.
 * 
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */

# Protect against web entry
if( !defined( 'MEDIAWIKI' ) && PHP_SAPI != 'cli' ) exit;

# Class where the logic is
require_once dirname( __FILE__ ) . '/MediaWikiFarm.php';


/*
 * MediaWikiFarm loading
 */

MediaWikiFarm::load();


/*
 * MediaWiki configuration
 */

# Load general MediaWiki configuration
$wgMediaWikiFarm->loadMediaWikiConfig();


/*
 * Skins configuration
 */

# Load skins with the require_once mechanism
foreach( $wgMediaWikiFarm->params['globals']['skins'] as $skin => $value ) {
	
	if( $value['_loading'] == 'require_once' )
		require_once "$IP/skins/$skin/$skin.php";
}

# Load skins with the wfLoadSkin mechanism
$wgMediaWikiFarm->loadSkinsConfig();


/*
 * Extensions configuration
 */

# Load extensions with the require_once mechanism
foreach( $wgMediaWikiFarm->params['globals']['extensions'] as $extension => $value ) {
	
	if( $value['_loading'] == 'require_once' )
		require_once "$IP/extensions/$extension/$extension.php";
}

# Load extensions with the wfLoadExtension mechanism
$wgMediaWikiFarm->loadExtensionsConfig();


/*
 * Executable configuration
 */

foreach( $wgMediaWikiFarm->params['globals']['execFiles'] as $execFile ) {
	
	@include $execFile;
}

