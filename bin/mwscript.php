<?php
/**
 * Entry point for CLI scripts in the context of a MediaWiki farm.
 *
 * @package MediaWikiFarm
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */
// @codeCoverageIgnoreStart

# Protect against web entry
if( PHP_SAPI != 'cli' && PHP_SAPI != 'phpdbg' ) {
	exit;
}

# Load classes
require_once dirname( dirname( __FILE__ ) ) . '/src/bin/MediaWikiFarmScript.php';

# Prepare environment
$wgMediaWikiFarmScript = new MediaWikiFarmScript( $argc, $argv );

$wgMediaWikiFarmScript->load();

if( $wgMediaWikiFarmScript->main() ) {

	# Execute the script
	require $wgMediaWikiFarmScript->argv[0];

	# Post-execution
	$wgMediaWikiFarmScript->restInPeace();
}
else {
	exit( $wgMediaWikiFarmScript->status );
}
// @codeCoverageIgnoreEnd
