<?php
/**
 * Entry point for CLI scripts in the context of a monoversion or multiversion MediaWiki farm.
 *
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */
// @codeCoverageIgnoreStart

# Protect against web entry
if( PHP_SAPI != 'cli' && PHP_SAPI != 'phpdbg' ) {
	exit;
}

# Load classes
require_once dirname( dirname( __FILE__ ) ) . '/src/Script.php';

# Prepare environment
$wgMediaWikiFarmScript = new MediaWikiFarmScript( $argc, $argv );

$wgMediaWikiFarmScript->load();

$wgMediaWikiFarmScript->main();

if( $wgMediaWikiFarmScript->status == 200 ) {

	# Execute the script
	require $wgMediaWikiFarmScript->argv[0];

	# Post-execution
	$wgMediaWikiFarmScript->restInPeace();
}
else {
	exit( $wgMediaWikiFarmScript->status );
}
// @codeCoverageIgnoreEnd
