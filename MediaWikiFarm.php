<?php
/**
 * MediaWikiFarm extension for MediaWiki.
 *
 * This extension turns a MediaWiki installation into a farm consisting of multiple independant wikis.
 *
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */
// @codeCoverageIgnoreStart

# Protect against web entry
if( !defined( 'MEDIAWIKI' ) && PHP_SAPI != 'cli' && PHP_SAPI != 'phpdbg' ) {
	exit;
}

# Load MediaWiki configuration
if( !defined( 'MEDIAWIKI' ) ) {
	return;
}

# Load class definition
if( !class_exists( 'MediaWikiFarm' ) ) {
	require_once dirname( __FILE__ ) . '/src/MediaWikiFarm.php';
}

# Load MediaWikiFarm
if( MediaWikiFarm::load() == 200 ) {

	# Load MediaWiki configuration
	require_once $wgMediaWikiFarm->getConfigFile();
} else {
	# TODO check the situations where it would be needed exit( 0 ), else remove
	# Do not execute during installer
	if( defined( 'MEDIAWIKI_INSTALL' ) ) {
		return;
	}
	exit( 0 );
}
// @codeCoverageIgnoreEnd
