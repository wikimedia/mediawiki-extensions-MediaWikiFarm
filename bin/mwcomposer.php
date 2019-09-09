<?php
/**
 * Wrapper around Composer to create as many autoloaders as MediaWiki extensions.
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
require_once dirname( dirname( __FILE__ ) ) . '/src/bin/MediaWikiFarmScriptComposer.php';

# Prepare environment
$wgMediaWikiFarmScriptComposer = new MediaWikiFarmScriptComposer( $argc, $argv );

$wgMediaWikiFarmScriptComposer->load();

$wgMediaWikiFarmScriptComposer->main();

if( $wgMediaWikiFarmScriptComposer->status != 200 ) {
	exit( $wgMediaWikiFarmScriptComposer->status );
}
// @codeCoverageIgnoreEnd
