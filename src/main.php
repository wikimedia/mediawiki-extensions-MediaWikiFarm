<?php
/**
 * Main program, creating the MediaWikiFarm object, then loading MediaWiki configuration.
 *
 * @package MediaWikiFarm
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */
// @codeCoverageIgnoreStart

# Protect against web entry
// NB: to run MediaWiki 1.1, comment this
if( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

# Old MediaWiki installations doesn’t load DefaultSettings.php before LocalSettings.php
if( !isset( $wgVersion ) ) {
	if( !$IP ) {
		$IP = realpath( '.' ) ? realpath( '.' ) : dirname( __DIR__ );
	}
	// NB: to run MediaWiki 1.1, comment this
	require_once "$IP/includes/DefaultSettings.php";
}

# Compile MediaWiki configuration
$wgMediaWikiFarm->compileConfiguration();

# Load skins with the require_once mechanism
foreach( $wgMediaWikiFarm->getConfiguration( 'extensions' ) as $key => $extension ) {

	if( $extension[1] == 'skin' && $extension[2] == 'require_once' ) {
		if( array_key_exists( 'wgStyleDirectory', $wgMediaWikiFarm->getConfiguration( 'settings' ) ) ) {
			require_once $wgMediaWikiFarm->getConfiguration( 'settings', 'wgStyleDirectory' ) . "/{$extension[0]}/{$extension[0]}.php";
		} else {
			require_once "$IP/skins/{$extension[0]}/{$extension[0]}.php";
		}
	}
}

# Load extensions with the require_once mechanism
foreach( $wgMediaWikiFarm->getConfiguration( 'extensions' ) as $key => $extension ) {

	if( $extension[1] == 'extension' && $extension[2] == 'require_once' && $key != 'ExtensionMediaWikiFarm' ) {
		if( array_key_exists( 'wgExtensionDirectory', $wgMediaWikiFarm->getConfiguration( 'settings' ) ) ) {
			require_once $wgMediaWikiFarm->getConfiguration( 'settings', 'wgExtensionDirectory' ) . "/{$extension[0]}/{$extension[0]}.php";
		} else {
			require_once "$IP/extensions/{$extension[0]}/{$extension[0]}.php";
		}
	}
}

# Load general MediaWiki configuration
$wgMediaWikiFarm->loadMediaWikiConfig();

# Executable configuration
foreach( $wgMediaWikiFarm->getConfiguration( 'execFiles' ) as $execFile ) {

	if( !is_file( $execFile ) ) {
		continue;
	}

	include $execFile;
}
// @codeCoverageIgnoreEnd
