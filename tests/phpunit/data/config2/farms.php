<?php

return [

	'testfarm2-multiversion' => [

		'server' => '(?P<wiki>[a-z])(?P<wiki2>[a-z])\.testfarm2-multiversion\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki',
			       'file' => 'varwiki.php' ],
			[ 'variable' => 'wiki2',
			       'file' => 'varwiki.php' ],
		],
		'suffix' => 'testfarm2',
		'wikiID' => '$wiki$wiki2testfarm2',
		'versions' => 'versions.php',
		'config' => [],
	],

	'testfarm2-multiversion-redirect' => [

		'server' => '(?P<wiki>[a-z])\.testfarm2-multiversion-redirect\.example\.org',
		'redirect' => '$wiki.testfarm2-multiversion.example.org',
	],

	'testfarm2-multiversion-bis' => [

		'server' => '(?P<wiki>[a-z])\.testfarm2-multiversion-bis\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki',
			       'file' => 'varwikibis.php' ],
		],
		'suffix' => 'testfarm2',
		'wikiID' => '$wikitestfarm2',
		'config' => [],
	],

	'testfarm2-multiversion-ter' => [

		'server' => '(?P<wiki>[a-z])\.testfarm2-multiversion-ter\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki' ],
		],
		'suffix' => 'testfarm2',
		'wikiID' => '$wikitestfarm2',
		'versions' => 'versionster.php',
		'config' => [],
	],
];
