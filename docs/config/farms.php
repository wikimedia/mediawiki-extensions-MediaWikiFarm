<?php

return array(

	# Placeholder configuration for a first installation as detailled in the documentation
	'mywiki' => array(

		'server' => 'mywiki\.example\.org',
		'suffix' => 'wiki',
		'wikiID' => 'mywiki',
		'config' => array(
			array( 'file' => 'LocalSettings.php',
			       'executable' => true,
			),
		),
	),
);

// @codingStandardsIgnoreStart MediaWiki.Commenting.IllegalSingleLineComment.MissingCommentEndding
/*

return array(

	# Configuration similar to the Wikimedia farm
	'wikimedia' => array(

		'server' => '(?P<lang>[a-z-]+)\.(?<family>[a-z]+)\.org',

		'variables' => array(

			array( 'variable' => 'family',
			),
			array( 'variable' => 'lang',
			       'file'     => 'org/$family.dblist',
			),
		),
		'suffix' => '$family',
		'wikiID' => '$lang$family',
		'versions' => 'wikiversions.json',
		'data' => '/srv/data/org/$family/$lang',
		'config' => array(
			array( 'file' => 'org/DefaultSettings.yml',
			       'key' => '*',
			),
			array( 'file' => 'org/Settings-$family.yml',
			       'key' => '*$family',
			       'default' => '$family',
			),
			array( 'file' => 'org/PrivateSettings.yml',
			       'key' => '*',
			),
			array( 'file' => 'org/ExecSettings.php',
			       'executable' => true,
			),
		),
	),

	# Configuration for a small wiki farm
	'com-example' => array(

		'server' => '(?P<client>[a-z]+)-(?<wiki>[a-z]+)\.example\.com',

		'variables' => array(

			array( 'variable' => 'client',
			       'file'     => 'com/example/clients.yml',
			),
			array( 'variable' => 'wiki',
			       'file'     => 'com/example/$client/wikis.yml',
			),
		),
		'suffix' => '$client',
		'wikiID' => '$wiki-$client',
		'data' => '/srv/data/com/example/$client/$wiki',
		'config' => array(
			array( 'file' => 'com/example/DefaultSettings.yml',
			       'key' => 'default',
			),
			array( 'file' => 'com/example/InitialiseSettings.yml',
			       'key' => '*',
			),
			array( 'file' => 'com/example/PrivateSettings.yml',
			       'key' => '*',
			),
			array( 'file' => 'com/example/ExecSettings.php',
			       'executable' => true,
			),
		),
	),

	# Aliases

	'com-example-redirect' => array(

		'server' => '(?P<client>[a-z]+)_(?<wiki>[a-z]+)\.example\.com',
		'redirect' => '$client-$wiki.example.com',
	),
);

*/
