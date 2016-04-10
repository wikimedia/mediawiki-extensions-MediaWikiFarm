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


/*
 * MediaWiki configuration
 */

# Old MediaWiki installations doesn’t load DefaultSettings.php before LocalSettings.php
if( !isset( $wgVersion ) ) {
	require_once "$IP/includes/DefaultSettings.php";
}

# Load general MediaWiki configuration
MediaWikiFarm::getInstance()->loadMediaWikiConfig();


/*
 * Skins configuration
 */

# Load skins with the require_once mechanism
foreach( MediaWikiFarm::getInstance()->params['globals']['skins'] as $skin => $value ) {
	
	if( $value['_loading'] == 'require_once' )
		require_once "$IP/skins/$skin/$skin.php";
}

# Load skins with the wfLoadSkin mechanism
MediaWikiFarm::getInstance()->loadSkinsConfig();


/*
 * Extensions configuration
 */

# Load extensions with the require_once mechanism
foreach( MediaWikiFarm::getInstance()->params['globals']['extensions'] as $extension => $value ) {
	
	if( $value['_loading'] == 'require_once' )
		require_once "$IP/extensions/$extension/$extension.php";
}

# Load extensions with the wfLoadExtension mechanism
MediaWikiFarm::getInstance()->loadExtensionsConfig();


/*
 * Executable configuration
 */

foreach( MediaWikiFarm::getInstance()->params['globals']['execFiles'] as $execFile ) {
	
	@include $execFile;
}

