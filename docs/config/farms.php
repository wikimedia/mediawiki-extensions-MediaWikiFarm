<?php

return [

	# Placeholder configuration for a first installation as detailled in the documentation
	'mywiki' => [

		'server' => 'mywiki\.example\.org',
		'suffix' => 'wiki',
		'wikiID' => 'mywiki',
		'config' => [
			[ 'file' => 'LocalSettings.php',
			       'executable' => true,
			],
		],
	],
];

// @codingStandardsIgnoreStart MediaWiki.Commenting.IllegalSingleLineComment.MissingCommentEndding
/*

return [

	# Configuration similar to the Wikimedia farm
	'wikimedia' => [

		'server' => '(?P<lang>[a-z-]+)\.(?<family>[a-z]+)\.org',

		'variables' => [

			[ 'variable' => 'family',
			],
			[ 'variable' => 'lang',
			       'file'     => 'org/$family.dblist',
			],
		],
		'suffix' => '$family',
		'wikiID' => '$lang$family',
		'versions' => 'wikiversions.json',
		'data' => '/srv/data/org/$family/$lang',
		'config' => [
			[ 'file' => 'org/DefaultSettings.yml',
			       'key' => '*',
			],
			[ 'file' => 'org/Settings-$family.yml',
			       'key' => '*$family',
			       'default' => '$family',
			],
			[ 'file' => 'org/PrivateSettings.yml',
			       'key' => '*',
			],
			[ 'file' => 'org/ExecSettings.php',
			       'executable' => true,
			],
		],
	],

	# Configuration for a small wiki farm
	'com-example' => [

		'server' => '(?P<client>[a-z]+)-(?<wiki>[a-z]+)\.example\.com',

		'variables' => [

			[ 'variable' => 'client',
			       'file'     => 'com/example/clients.yml',
			],
			[ 'variable' => 'wiki',
			       'file'     => 'com/example/$client/wikis.yml',
			],
		],
		'suffix' => '$client',
		'wikiID' => '$wiki-$client',
		'data' => '/srv/data/com/example/$client/$wiki',
		'config' => [
			[ 'file' => 'com/example/DefaultSettings.yml',
			       'key' => 'default',
			],
			[ 'file' => 'com/example/InitialiseSettings.yml',
			       'key' => '*',
			],
			[ 'file' => 'com/example/PrivateSettings.yml',
			       'key' => '*',
			],
			[ 'file' => 'com/example/ExecSettings.php',
			       'executable' => true,
			],
		],
	],

	# Aliases

	'com-example-redirect' => [

		'server' => '(?P<client>[a-z]+)_(?<wiki>[a-z]+)\.example\.com',
		'redirect' => '$client-$wiki.example.com',
	],
];

*/
