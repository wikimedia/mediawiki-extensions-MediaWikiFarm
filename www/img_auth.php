<?php
/**
 * Entry point img_auth.php in the context of a multiversion MediaWiki farm.
 *
 * @package MediaWikiFarm
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */
// @codeCoverageIgnoreStart

# Default MediaWikiFarm configuration
$wgMediaWikiFarmCodeDir = dirname( dirname( __DIR__ ) );
$wgMediaWikiFarmConfigDir = '/etc/mediawiki';
$wgMediaWikiFarmCacheDir = '/tmp/mw-cache';
$wgMediaWikiFarmSyslog = 'mediawikifarm';

# Check the entry point is installed in a multiversion MediaWiki farm or in the classical MediaWiki extensions directory
if( is_file( dirname( $wgMediaWikiFarmCodeDir ) . '/includes/DefaultSettings.php' ) ) {
	exit;
}

# Override default MediaWikiFarm configuration
@include_once dirname( __DIR__ ) . '/config/MediaWikiFarmDirectories.php';

# Include library
require_once dirname( __DIR__ ) . '/src/MediaWikiFarm.php';

# Redirect to the requested version
if( MediaWikiFarm::load( 'img_auth.php' ) == 200 ) {
	require 'img_auth.php';
}
// @codeCoverageIgnoreEnd
