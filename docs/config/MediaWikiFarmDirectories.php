<?php

/**
 * Configuration of MediaWikiFarm -- MultiVersion case.
 * 
 * This file is optional. In the case standard multiversion entry points are used,
 * this can be used to customise your configuration directory, your cache directory,
 * and your MediaWiki code directory, and must be copied in the /config directory
 * of the MediaWikiFarm extension with the name MediaWikiFarmDirectories.php
 */


/**
 * Configuration directory.
 * 
 * Type: string.
 * 
 * The value must be a readable directory. Depending of your openness policy, you could
 * publish all or parts of the configuration files, but probably you don’t want to publish
 * private informations like database configuration, upgrade key, etc.
 */
$wgMediaWikiFarmConfigDir = '/etc/mediawiki';


/**
 * Code directory.
 * 
 * Type: string.
 * 
 * Since you want your farm can manage multiple MediaWiki versions, set this parameter to a
 * directory where each subdirectory is a MediaWiki installation in a given version+flavour.
 * Although it is probably easier to name the subdirectories with the MediaWiki version, the
 * names are entirely independent from the real version inside the subdirectory.
 */
$wgMediaWikiFarmCodeDir = dirname( dirname( dirname( __FILE__ ) ) );

