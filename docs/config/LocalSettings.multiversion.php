<?php
/**
 * Configuration managed by MediaWikiFarm -- MultiVersion case.
 */

# Protect against web entry
if( !defined( 'MEDIAWIKI' ) ) exit;

# Include the code.
require_once MediaWikiFarm::getInstance()->getConfigFile();

# Do not add other configuration here, but instead in the config files
# read by MediaWikiFarm -- even for global settings.
