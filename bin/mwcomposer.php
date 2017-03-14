<?php
/**
 * Wrapper around Composer to create as many autoloaders as MediaWiki extensions.
 *
 * @package MediaWikiFarm
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
require_once dirname( dirname( __FILE__ ) ) . '/src/MediaWikiFarmComposerScript.php';

# Prepare environment
$wgMediaWikiFarmComposerScript = new MediaWikiFarmComposerScript( $argc, $argv );

$wgMediaWikiFarmComposerScript->load();

$wgMediaWikiFarmComposerScript->main();

if( $wgMediaWikiFarmComposerScript->status != 200 ) {
	exit( $wgMediaWikiFarmComposerScript->status );
}
// @codeCoverageIgnoreEnd
