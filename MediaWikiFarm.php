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
if( !defined( 'MEDIAWIKI' ) ) exit;

/*
 * Parameters
 * ========== */

/**
 * Configuration directory.
 * 
 * Type: string (path).
 * 
 * This parameter should be specified in your LocalSettings.php, before the require_once.
 * The value must be a readable directory. Depending of your openness policy, you could
 * publish all or parts of the configuration files, but probably you don’t want to publish
 * private informations like database configuration, upgrade key, etc.
 */
$wgMediaWikiFarmConfigDir = '/etc/mediawiki';


/**
 * Code directory.
 * 
 * Type: string|null (path).
 * 
 * If your farm can manage multiple MediaWiki versions, set this parameter to a directory
 * where each subdirectory is a MediaWiki installation in a given version+flavour. Although
 * it is probably easier to name the subdirectories with the MediaWiki version, the names
 * are entirely independent from the real version inside the subdirectory.
 */
$wgMediaWikiFarmCodeDir = null;





/*
 *    Code
 * ========== */

require_once __DIR__ . '/src/main.php';

