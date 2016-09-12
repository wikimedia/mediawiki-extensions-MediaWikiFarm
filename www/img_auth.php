<?php
/**
 * Entry point img_auth.php in the context of a multiversion MediaWiki farm.
 *
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */
// @codeCoverageIgnoreStart

# Default MediaWikiFarm configuration
$wgMediaWikiFarmCodeDir = dirname( dirname( dirname( __FILE__ ) ) );
$wgMediaWikiFarmConfigDir = '/etc/mediawiki';
$wgMediaWikiFarmCacheDir = '/tmp/mw-cache';

# Check the entry point is installed in a multiversion MediaWiki farm or in the classical MediaWiki extensions directory
if( is_file( dirname( $wgMediaWikiFarmCodeDir ) . '/includes/DefaultSettings.php' ) ) {
	exit;
}

# Override default MediaWikiFarm configuration
@include_once dirname( dirname( __FILE__ ) ) . '/config/MediaWikiFarmDirectories.php';

# Include library
require_once dirname( dirname( __FILE__ ) ) . '/src/MediaWikiFarm.php';

# Redirect to the requested version
if( MediaWikiFarm::load( 'img_auth.php' ) == 200 ) {
	require 'img_auth.php';
}
// @codeCoverageIgnoreEnd
