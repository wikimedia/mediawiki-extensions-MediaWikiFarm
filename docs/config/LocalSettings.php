<?php
/**
 * Configuration managed by MediaWikiFarm -- MonoVersion case.
 */

# Protect against web entry
if( !defined( 'MEDIAWIKI' ) && PHP_SAPI != 'cli' ) exit;

// Configuration directory.
// There must be a file 'farms.yml' or 'farms.php' or 'farms.json' inside.
$wgMediaWikiFarmConfigDir = '/etc/mediawiki';

# Include the code.
require "$IP/extensions/MediaWikiFarm/MediaWikiFarm.php";

# Do not add other configuration here, but instead in the config files
# read by MediaWikiFarm -- even for global settings.
