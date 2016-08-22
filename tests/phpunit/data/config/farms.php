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
			array( 'file' => 'settings.yml',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'exec' => true,
			),
		),
	),

	'testfarm-monoversion' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm-monoversion\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki', ),
		),
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'config' => array(
			array( 'file' => 'settings.yml',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'exec' => true,
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
			array( 'file' => 'settings.yml',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'exec' => true,
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
		'config' => array(
			array( 'file' => 'settings.yml',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'exec' => true,
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
			array( 'file' => 'settings.yml',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'exec' => true,
			),
		),
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
			array( 'file' => 'settings.yml',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'exec' => true,
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
			array( 'file' => 'settings.yml',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'exec' => true,
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
			array( 'file' => 'settings.yml',
			       'key' => 'default',
			),
			array( 'file' => 'LocalSettings.php',
			       'exec' => true,
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
			       'exec' => true,
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
			array( 'variable' => 'wiki', ),
		),
		'suffix' => '$wiki',
		'wikiID' => '$wikitestfarm',
		'versions' => 'versions.php',
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
			       'exec' => true,
			),
		),
	),
);
