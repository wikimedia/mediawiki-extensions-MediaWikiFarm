<?php

return array(

	'testfarm-multiversion' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki', ),
		),
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'versions' => 'versions.php',
		'config' => array(
			array( 'file' => 'settings.php',
			       'key' => 'default',
			),
			array( 'file' => 'localsettings.php',
			       'key' => '*testfarm',
			       'default' => 'testfarm',
			),
			array( 'file' => 'globalsettings.php',
			       'key' => '*',
			),
			array( 'file' => 'LocalSettings.php',
			       'executable' => true,
			),
		),
	),

	'testfarm-monoversion' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-monoversion\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki',
			       'file' => 'varwiki.php', ),
		),
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'HTTP404' => 'phpunitHTTP404.php',
		'config' => array(
			array( 'file' => 'settings.php',
			       'key' => 'default',
			),
			'settings.php',
			array( 'file' => 'missingfile.php',
			       'key' => 'default',
			),
			array( 'file' => 'localsettings.php',
			       'key' => '*testfarm',
			       'default' => 'testfarm',
			),
			array( 'file' => 'globalsettings.php',
			       'key' => '*',
			),
			array( 'file' => 'atestfarmsettings.php',
			       'key' => 'atestfarm',
			),
			array( 'file' => 'testfarmsettings.php',
			       'key' => 'testfarm',
			),
			array( 'file' => 'otherfarmsettings.php',
			       'key' => 'otherfarm',
			),
			array( 'file' => 'LocalSettings.php',
			       'executable' => true,
			),
		),
	),

	'testfarm-multiversion-subdirectories' => array(

		'server' => 'testfarm-multiversion-subdirectories\.example\.org/(?P<wiki>[a-z])',
		'variables' => array(
			array( 'variable' => 'wiki', ),
		),
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'versions' => 'versions.php',
		'config' => array(
			array( 'file' => 'settings.php',
			       'key' => 'default',
			),
			array( 'file' => 'localsettings.php',
			       'key' => '*testfarm',
			       'default' => 'testfarm',
			),
			array( 'file' => 'globalsettings.php',
			       'key' => '*',
			),
			array( 'file' => 'LocalSettings.php',
			       'executable' => true,
			),
		),
	),

	'testfarm-multiversion-with-file-variable-without-version' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-with-file-variable-without-version\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki',
			       'file' => 'varwiki.php', ),
		),
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'versions' => 'versions.php',
		'config' => array(
			array( 'file' => 'settings.php',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'executable' => true,
			),
		),
	),

	'testfarm-monoversion-with-file-variable-without-version' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-monoversion-with-file-variable-without-version\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki',
			       'file' => 'varwiki.php', ),
		),
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'HTTP404' => 'phpunitHTTP404.php',
		'config' => array(
			array( 'file' => 'settings.php',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'executable' => true,
			),
		),
	),

	'testfarm-monoversion-with-values-variable-without-version' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-monoversion-with-values-variable-without-version\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki',
			       'values' => array( 'a', 'b' ), ),
		),
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'HTTP404' => 'phpunitHTTP404.php',
		'config' => array(
			array( 'file' => 'settings.php',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'executable' => true,
			),
		),
	),

	'testfarm-multiversion-with-file-variable-with-version' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-with-file-variable-with-version\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki',
			       'file' => 'varwikiversions.php', ),
		),
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'versions' => 'versions.php',
		'config' => array(
			array( 'file' => 'settings.php',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'executable' => true,
			),
		),
	),

	'testfarm-multiversion-with-version-default-family' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-with-version-default-family\.example\.org',
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'versions' => 'versions-default.php',
	),

	'testfarm-multiversion-with-version-default-default' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-with-version-default-default\.example\.org',
		'suffix' => 'testotherfarm',
		'wikiID' => '$wikitestotherfarm',
		'versions' => 'versions-default.php',
	),

	'testfarm-monoversion-with-file-variable-with-version' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-monoversion-with-file-variable-with-version\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki',
			       'file' => 'varwikiversions.php', ),
		),
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'config' => array(
			array( 'file' => 'settings.php',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'executable' => true,
			),
		),
	),

	'testfarm-multiversion-with-undefined-variable' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-with-undefined-variable\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki', ),
			array( 'variable' => 'domain', ),
		),
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'versions' => 'versions.php',
		'config' => array(
			array( 'file' => 'settings.php',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'executable' => true,
			),
		),
	),

	'testfarm-monoversion-with-undefined-variable' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-monoversion-with-undefined-variable\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki', ),
			array( 'variable' => 'domain', ),
		),
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'config' => array(
			array( 'file' => 'settings.php',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'executable' => true,
			),
		),
	),

	'testfarm-with-badly-formatted-file-variable' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-with-badly-formatted-file-variable\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki',
			       'file' => 'badsyntax.json', ),
		),
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'config' => array(
			array( 'file' => 'LocalSettings.php',
			       'executable' => true,
			),
		),
	),

	'testfarm-with-missing-suffix' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-with-missing-suffix\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki', ),
		),
		'wikiID' => '$wikitestfarm',
		'versions' => 'versions.php',
	),


	'testfarm-with-missing-wikiid' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-with-missing-wikiid\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki', ),
		),
		'suffix' => 'testfarm',
		'versions' => 'versions.php',
	),

	'testfarm-with-bad-type-mandatory' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-with-bad-type-mandatory\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki', ),
		),
		'suffix' => 0,
		'wikiID' => '$wikitestfarm',
		'versions' => 'versions.php',
	),

	'testfarm-with-bad-type-nonmandatory' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-with-bad-type-nonmandatory\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki',
		               'file' => 'varwikiversions.php', ),
		),
		'suffix' => '$wiki',
		'wikiID' => '$wikitestfarm',
		'data' => 0,
	),

	'testfarm-multiversion-redirect' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-redirect\.example\.org',
		'redirect' => '$wiki.testfarm-multiversion.example.org',
	),

	'testfarm-monoversion-redirect' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-monoversion-redirect\.example\.org',
		'redirect' => '$wiki.testfarm-monoversion.example.org',
	),

	'testfarm-infinite-redirect' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-infinite-redirect\.example\.org',
		'redirect' => '$wiki.testfarm-infinite-redirect.example.org',
	),

	'testfarm-novariables' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-novariables\.example\.org',
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'config' => array(
			array( 'file' => 'LocalSettings.php',
			       'executable' => true,
			),
		),
	),

	'testfarm-multiversion-test-extensions' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-test-extensions\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki', ),
		),
		'suffix' => 'testextensionsfarm',
		'wikiID' => '$wikitestextensionsfarm',
		'versions' => 'testextensionsfarmversions.php',
		'config' => array(
			array( 'file' => 'extensionssettings.php',
			       'key' => '*',
			),
			array( 'file' => 'missingfile.php',
			       'executable' => true,
			),
		),
	),

	'testfarm-multiversion-with-bad-file-versions' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-with-bad-file-versions\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki',
			       'file' => 'varwiki.php', ),
		),
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'versions' => 'badsyntax.json',
		'config' => array(
			array( 'file' => 'settings.php',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'executable' => true,
			),
		),
	),

	'testfarm-multiversion-with-file-versions-other-keys' => array(

		'server' => '(?P<wiki>[a-z])\.(?P<family>[a-z])\.testfarm-multiversion-with-file-versions-other-keys\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki',
			       'file' => 'varwiki.php', ),
			array( 'variable' => 'family',
			       'file' => 'varwiki.php', ),
		),
		'suffix' => '$familyfamilytestfarm',
		'wikiID' => '$wiki$familyfamilytestfarm',
		'versions' => 'testfamilyfarmversions.php',
		'config' => array(
			array( 'file' => 'settings.php',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'executable' => true,
			),
		),
	),

	'testfarm-multiversion-with-file-versions-with-deployments' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-with-file-versions-with-deployments\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki',
			       'file' => 'varwiki.php', ),
		),
		'suffix' => 'testdeploymentsfarm',
		'wikiID' => '$wikitestdeploymentsfarm',
		'versions' => 'testdeploymentsfarmversions.php',
		'deployments' => 'deployments',
		'config' => array(
			array( 'file' => 'settings.php',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'executable' => true,
			),
		),
	),

	'testfarm-multiversion-with-file-versions-with-deployments5' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-multiversion-with-file-versions-with-deployments5\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki',
			       'file' => 'varwiki.php', ),
		),
		'suffix' => 'testdeploymentsfarm',
		'wikiID' => '$wikitestdeploymentsfarm',
		'versions' => 'testdeploymentsfarmversions5.php',
		'deployments' => 'deployments5',
		'config' => array(
			array( 'file' => 'settings.php',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'executable' => true,
			),
		),
	),
);
