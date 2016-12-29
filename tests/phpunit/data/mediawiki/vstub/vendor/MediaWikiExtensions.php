<?php

// WARNING: file automatically generated: do not modify.

return array (

	'ExtensionTestExtensionComposer' => array(),

	'ExtensionTestExtensionComposer2' => array(),

	'SkinTestSkinComposer' => array(
		'ExtensionTestExtensionComposer',
		'ExtensionTestExtensionComposer2',
	),

	'SkinFictiveSkinComposerForTesting' => array(
		'SkinIrrealSkinComposerForTesting',
	),

	'SkinIrrealSkinComposerForTesting' => array(
		'SkinFictiveSkinComposerForTesting',
	),
);
