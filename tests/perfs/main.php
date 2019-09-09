<?php
/**
 * Main program, creating the MediaWikiFarm object, then loading MediaWiki configuration.
 *
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */
// @codeCoverageIgnoreStart

if( $_SERVER['REMOTE_ADDR'] != '127.0.0.1' && $_SERVER['REMOTE_ADDR'] != '::1' ) {
	exit;
}

MediaWikiFarmTestPerfs::startCounter( 'config' );

require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/main.php';

MediaWikiFarmTestPerfs::stopCounter( 'config' );
MediaWikiFarmTestPerfs::writeResults();
// @codeCoverageIgnoreEnd
