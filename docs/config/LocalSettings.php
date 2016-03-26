<?php

/**
 * Configuration managed by MediaWikiFarm.
 * 
 * Just change the configuration directory, preferably in a directory not exposed on the Web.
 * Then add the sample file farms.yml inside and start customising it.
 */

# Configuration directory.
# There must be a file 'farms.yml' or 'farms.php' or 'farms.json' inside.
$wgMediaWikiFarmConfigDir = '/etc/mediawiki';

# Include the code.
require_once "$IP/extensions/MediaWikiFarm/MediaWikiFarm.php";

# Do not add other configuration here, but instead in the config files
# read by MediaWikiFarm -- even for global settings.
