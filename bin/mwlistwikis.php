<?php
/**
 * Script listing all known wikis in the farms.
 *
 * @package MediaWikiFarm
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */
// @codeCoverageIgnoreStart

# Protect against web entry
if( PHP_SAPI != 'cli' && PHP_SAPI != 'phpdbg' ) {
	exit;
}

# Load classes
require_once dirname( dirname( __FILE__ ) ) . '/src/bin/ScriptListWikis.php';

# Prepare environment
$wgMediaWikiFarmScriptListWikis = new MediaWikiFarmScriptListWikis( $argc, $argv );

$wgMediaWikiFarmScriptListWikis->load();

if( !$wgMediaWikiFarmScriptListWikis->main() ) {
	exit( $wgMediaWikiFarmScriptListWikis->status );
}
// @codeCoverageIgnoreEnd
