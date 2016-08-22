<?php
/**
 * MediaWikiFarm extension for MediaWiki.
 * 
 * This extension turns a MediaWiki installation into a farm consisting of multiple independant wikis.
 * 
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */

# Protect against web entry
if( !defined( 'MEDIAWIKI' ) && PHP_SAPI != 'cli' && PHP_SAPI != 'phpdbg' ) exit;

# Load MediaWiki configuration
if( defined( 'MEDIAWIKI' ) ) {
	
	# Load class definition
	if( !class_exists( 'MediaWikiFarm' ) )
		require_once dirname( __FILE__ ) . '/src/MediaWikiFarm.php';
	
	# Load MediaWikiFarm
	MediaWikiFarm::load();
	
	# Load MediaWiki configuration
	require_once MediaWikiFarm::getInstance()->getConfigFile();
}
