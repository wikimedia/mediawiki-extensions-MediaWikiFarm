<?php

# This is typically an old extension: no $GLOBALS, no descriptionmsg
# This could create issues if included in a limited scope (not global scope)
$wgExtensionCredits['other'][] = array(
	'path' => dirname( __FILE__ ) . '/TestExtensionRequireOnce.php',
	'name' => 'TestExtensionRequireOnce',
	'version' => '1.0.0',
	'author' => 'Seb35',
	'description' => 'Stub extension for testing',
	'license-name' => 'GPL-3.0-or-later',
);
