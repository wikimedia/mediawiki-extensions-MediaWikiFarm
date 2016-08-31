<?php
/**
 * Main program, creating the MediaWikiFarm object, then loading MediaWiki configuration.
 *
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */
// @codeCoverageIgnoreStart

# Protect against web entry
if( !defined( 'MEDIAWIKI' ) ) exit;


/*
 * MediaWiki configuration
 */

# Old MediaWiki installations doesn’t load DefaultSettings.php before LocalSettings.php
if( !isset( $wgVersion ) ) {
	if( !$IP ) {
		$IP = realpath( '.' ) ? realpath( '.' ) : dirname( __DIR__ );
	}
	require_once "$IP/includes/DefaultSettings.php";
}


# Load general MediaWiki configuration
$wgMediaWikiFarm->loadMediaWikiConfig();


/*
 * Skins configuration
 */

# Load skins with the require_once mechanism
foreach( $wgMediaWikiFarm->getConfiguration( 'skins' ) as $skin => $value ) {

	if( $value == 'require_once' ) {
		require_once "$IP/skins/$skin/$skin.php";
	}
}

# Load skins with the wfLoadSkin mechanism
$wgMediaWikiFarm->loadSkinsConfig();


/*
 * Extensions configuration
 */

# Load extensions with the require_once mechanism
foreach( $wgMediaWikiFarm->getConfiguration( 'extensions' ) as $extension => $value ) {

	if( $value == 'require_once' ) {
		require_once "$IP/extensions/$extension/$extension.php";
	}
}

# Load extensions with the wfLoadExtension mechanism
$wgMediaWikiFarm->loadExtensionsConfig();


/*
 * Executable configuration
 */
foreach( $wgMediaWikiFarm->getConfiguration( 'execFiles' ) as $execFile ) {

	@include $execFile;
}
// @codeCoverageIgnoreEnd
