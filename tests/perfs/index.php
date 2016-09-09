<?php
/**
 * Entry point index.php in the context of a multiversion MediaWiki farm.
 *
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */
// @codeCoverageIgnoreStart

# Include library
// @codingStandardsIgnoreStart MediaWiki.Usage.DirUsage.FunctionFound
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/MediaWikiFarm.php';
require_once dirname( __FILE__ ) . '/MediaWikiFarmTestPerfs.php';
// @codingStandardsIgnoreEnd

if( $_SERVER['REMOTE_ADDR'] != '127.0.0.1' && $_SERVER['REMOTE_ADDR'] != '::1' ) {
	exit;
}

switch( MediaWikiFarmTestPerfs::getEntryPointProfile( 'index.php' ) ) {

	# Farm
	case 0:

		# Beginning of performance counter for the bootstrap part
		MediaWikiFarmTestPerfs::startCounter( 'bootstrap' );

		# Default MediaWikiFarm configuration
		$wgMediaWikiFarmCodeDir = dirname( dirname( dirname( dirname( __FILE__ ) ) ) );
		$wgMediaWikiFarmConfigDir = '/etc/mediawiki';
		$wgMediaWikiFarmCacheDir = '/tmp/mw-cache';

		# Check the entry point is installed in a multiversion MediaWiki farm or in the classical MediaWiki extensions directory
		if( is_file( dirname( $wgMediaWikiFarmCodeDir ) . '/includes/DefaultSettings.php' ) ) exit;

		# Override default MediaWikiFarm configuration
		@include_once dirname( dirname( dirname( __FILE__ ) ) ) . '/config/MediaWikiFarmDirectories.php';

		# Redirect to the requested version
		if( MediaWikiFarmTestPerfs::load( 'index.php' ) == 200 ) {

			# End of performance counter for the bootstrap part
			MediaWikiFarmTestPerfs::stopCounter( 'bootstrap' );

			require 'index.php';
		}

		break;

	# Classical LocalSettings.php in a classical installation
	case 1:

		$wgMediaWikiFarmMetadata = include_once dirname( __FILE__ ) . '/results/metadata.php';

		chdir( $wgMediaWikiFarmMetadata['IP'] );
		require 'index.php';
}
// @codeCoverageIgnoreEnd
