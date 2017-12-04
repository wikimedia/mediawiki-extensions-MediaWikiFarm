<?php
/**
 * Configuration managed by MediaWikiFarm -- monoversion case.
 */

# Protect against web entry
if( !defined( 'MEDIAWIKI' ) && PHP_SAPI != 'cli' && PHP_SAPI != 'phpdbg' ) {
	exit;
}

// Configuration directory.
// Type: string
// There must be a file 'farms.yml' or 'farms.php' or 'farms.json' inside.
$wgMediaWikiFarmConfigDir = '/etc/mediawiki';

// Syslog tag.
// Type: string|false
$wgMediaWikiFarmSyslog = 'mediawikifarm';


# Include the code.
require "$IP/extensions/MediaWikiFarm/MediaWikiFarm.php";

# Do not add other configuration here, but instead in the config files
# read by MediaWikiFarm -- even for global settings.
