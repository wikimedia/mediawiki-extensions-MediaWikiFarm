<?php

# This is typically an old skin: no $GLOBALS, no descriptionmsg
# This could create issues if included in a limited scope (not global scope)
$wgExtensionCredits['skin'][] = array(
	'path' => dirname( __FILE__ ) . '/TestSkinRequireOnce.php',
	'name' => 'TestSkinRequireOnce',
	'version' => '1.0.0',
	'author' => 'Seb35',
	'description' => 'Stub skin for testing',
	'license-name' => 'GPL-3.0-or-later',
);
