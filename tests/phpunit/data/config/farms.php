<?php

return [

	'testfarm-multiversion' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki', ],
		],
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'versions' => 'versions.php',
		'config' => [
			[ 'file' => 'settings.php',
			       'key' => 'default',
			],
			[ 'file' => 'localsettings.php',
			       'key' => '*testfarm',
			       'default' => 'testfarm',
			],
			[ 'file' => 'globalsettings.php',
			       'key' => '*',
			],
			[ 'file' => 'LocalSettings.php',
			       'executable' => true,
			],
		],
	],

	'testfarm-monoversion' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-monoversion\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki',
			       'file' => 'varwiki.php', ],
		],
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'HTTP404' => 'phpunitHTTP404.php',
		'config' => [
			[ 'file' => 'settings.php',
			       'key' => 'default',
			],
			'settings.php',
			[ 'file' => 'missingfile.php',
			       'key' => 'default',
			],
			[ 'file' => 'localsettings.php',
			       'key' => '*testfarm',
			       'default' => 'testfarm',
			],
			[ 'file' => 'globalsettings.php',
			       'key' => '*',
			],
			[ 'file' => 'atestfarmsettings.php',
			       'key' => 'atestfarm',
			],
			[ 'file' => 'testfarmsettings.php',
			       'key' => 'testfarm',
			],
			[ 'file' => 'otherfarmsettings.php',
			       'key' => 'otherfarm',
			],
			[ 'file' => 'LocalSettings.php',
			       'executable' => true,
			],
		],
	],

	'testfarm-multiversion-subdirectories' => [

		'server' => 'testfarm-multiversion-subdirectories\.example\.org/(?P<wiki>[a-z])',
		'variables' => [
			[ 'variable' => 'wiki', ],
		],
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'versions' => 'versions.php',
		'config' => [
			[ 'file' => 'settings.php',
			       'key' => 'default',
			],
			[ 'file' => 'localsettings.php',
			       'key' => '*testfarm',
			       'default' => 'testfarm',
			],
			[ 'file' => 'globalsettings.php',
			       'key' => '*',
			],
			[ 'file' => 'LocalSettings.php',
			       'executable' => true,
			],
		],
	],

	'testfarm-multiversion-with-file-variable-without-version' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-with-file-variable-without-version\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki',
			       'file' => 'varwiki.php', ],
		],
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'versions' => 'versions.php',
		'config' => [
			[ 'file' => 'settings.php',
			       'key' => 'default',
			],
			[ 'file' => 'LocalSettings.php',
			       'executable' => true,
			],
		],
	],

	'testfarm-monoversion-with-file-variable-without-version' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-monoversion-with-file-variable-without-version\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki',
			       'file' => 'varwiki.php', ],
		],
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'HTTP404' => 'phpunitHTTP404.php',
		'config' => [
			[ 'file' => 'settings.php',
			       'key' => 'default',
			],
			[ 'file' => 'LocalSettings.php',
			       'executable' => true,
			],
		],
	],

	'testfarm-monoversion-with-values-variable-without-version' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-monoversion-with-values-variable-without-version\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki',
			       'values' => [ 'a', 'b' ], ],
		],
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'HTTP404' => 'phpunitHTTP404.php',
		'config' => [
			[ 'file' => 'settings.php',
			       'key' => 'default',
			],
			[ 'file' => 'LocalSettings.php',
			       'executable' => true,
			],
		],
	],

	'testfarm-multiversion-with-file-variable-with-version' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-with-file-variable-with-version\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki',
			       'file' => 'varwikiversions.php', ],
		],
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'versions' => 'versions.php',
		'config' => [
			[ 'file' => 'settings.php',
			       'key' => 'default',
			],
			[ 'file' => 'LocalSettings.php',
			       'executable' => true,
			],
		],
	],

	'testfarm-multiversion-with-version-default-family' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-with-version-default-family\.example\.org',
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'versions' => 'versions-default.php',
	],

	'testfarm-multiversion-with-version-default-default' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-with-version-default-default\.example\.org',
		'suffix' => 'testotherfarm',
		'wikiID' => '$wikitestotherfarm',
		'versions' => 'versions-default.php',
	],

	'testfarm-monoversion-with-file-variable-with-version' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-monoversion-with-file-variable-with-version\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki',
			       'file' => 'varwikiversions.php', ],
		],
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'config' => [
			[ 'file' => 'settings.php',
			       'key' => 'default',
			],
			[ 'file' => 'LocalSettings.php',
			       'executable' => true,
			],
		],
	],

	'testfarm-multiversion-with-undefined-variable' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-with-undefined-variable\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki', ],
			[ 'variable' => 'domain', ],
		],
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'versions' => 'versions.php',
		'config' => [
			[ 'file' => 'settings.php',
			       'key' => 'default',
			],
			[ 'file' => 'LocalSettings.php',
			       'executable' => true,
			],
		],
	],

	'testfarm-monoversion-with-undefined-variable' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-monoversion-with-undefined-variable\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki', ],
			[ 'variable' => 'domain', ],
		],
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'config' => [
			[ 'file' => 'settings.php',
			       'key' => 'default',
			],
			[ 'file' => 'LocalSettings.php',
			       'executable' => true,
			],
		],
	],

	'testfarm-with-badly-formatted-file-variable' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-with-badly-formatted-file-variable\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki',
			       'file' => 'badsyntax.json', ],
		],
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'config' => [
			[ 'file' => 'LocalSettings.php',
			       'executable' => true,
			],
		],
	],

	'testfarm-with-missing-suffix' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-with-missing-suffix\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki', ],
		],
		'wikiID' => '$wikitestfarm',
		'versions' => 'versions.php',
	],


	'testfarm-with-missing-wikiid' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-with-missing-wikiid\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki', ],
		],
		'suffix' => 'testfarm',
		'versions' => 'versions.php',
	],

	'testfarm-with-bad-type-mandatory' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-with-bad-type-mandatory\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki', ],
		],
		'suffix' => 0,
		'wikiID' => '$wikitestfarm',
		'versions' => 'versions.php',
	],

	'testfarm-with-bad-type-nonmandatory' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-with-bad-type-nonmandatory\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki',
		               'file' => 'varwikiversions.php', ],
		],
		'suffix' => '$wiki',
		'wikiID' => '$wikitestfarm',
		'data' => 0,
	],

	'testfarm-multiversion-redirect' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-redirect\.example\.org',
		'redirect' => '$wiki.testfarm-multiversion.example.org',
	],

	'testfarm-monoversion-redirect' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-monoversion-redirect\.example\.org',
		'redirect' => '$wiki.testfarm-monoversion.example.org',
	],

	'testfarm-infinite-redirect' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-infinite-redirect\.example\.org',
		'redirect' => '$wiki.testfarm-infinite-redirect.example.org',
	],

	'testfarm-novariables' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-novariables\.example\.org',
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'config' => [
			[ 'file' => 'LocalSettings.php',
			       'executable' => true,
			],
		],
	],

	'testfarm-multiversion-test-extensions' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-test-extensions\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki', ],
		],
		'suffix' => 'testextensionsfarm',
		'wikiID' => '$wikitestextensionsfarm',
		'versions' => 'testextensionsfarmversions.php',
		'config' => [
			[ 'file' => 'extensionssettings.php',
			       'key' => '*',
			],
			[ 'file' => 'missingfile.php',
			       'executable' => true,
			],
		],
	],

	'testfarm-multiversion-with-bad-file-versions' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-with-bad-file-versions\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki',
			       'file' => 'varwiki.php', ],
		],
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'versions' => 'badsyntax.json',
		'config' => [
			[ 'file' => 'settings.php',
			       'key' => 'default',
			],
			[ 'file' => 'LocalSettings.php',
			       'executable' => true,
			],
		],
	],

	'testfarm-multiversion-with-file-versions-other-keys' => [

		'server' => '(?P<wiki>[a-z])\.(?P<family>[a-z])\.testfarm-multiversion-with-file-versions-other-keys\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki',
			       'file' => 'varwiki.php', ],
			[ 'variable' => 'family',
			       'file' => 'varwiki.php', ],
		],
		'suffix' => '$familyfamilytestfarm',
		'wikiID' => '$wiki$familyfamilytestfarm',
		'versions' => 'testfamilyfarmversions.php',
		'config' => [
			[ 'file' => 'settings.php',
			       'key' => 'default',
			],
			[ 'file' => 'LocalSettings.php',
			       'executable' => true,
			],
		],
	],

	'testfarm-multiversion-with-file-versions-with-deployments' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-with-file-versions-with-deployments\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki',
			       'file' => 'varwiki.php', ],
		],
		'suffix' => 'testdeploymentsfarm',
		'wikiID' => '$wikitestdeploymentsfarm',
		'versions' => 'testdeploymentsfarmversions.php',
		'deployments' => 'deployments',
		'config' => [
			[ 'file' => 'settings.php',
			       'key' => 'default',
			],
			[ 'file' => 'LocalSettings.php',
			       'executable' => true,
			],
		],
	],

	'testfarm-multiversion-with-file-versions-with-deployments5' => [

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-with-file-versions-with-deployments5\.example\.org',
		'variables' => [
			[ 'variable' => 'wiki',
			       'file' => 'varwiki.php', ],
		],
		'suffix' => 'testdeploymentsfarm',
		'wikiID' => '$wikitestdeploymentsfarm',
		'versions' => 'testdeploymentsfarmversions5.php',
		'deployments' => 'deployments5',
		'config' => [
			[ 'file' => 'settings.php',
			       'key' => 'default',
			],
			[ 'file' => 'LocalSettings.php',
			       'executable' => true,
			],
		],
	],
];
