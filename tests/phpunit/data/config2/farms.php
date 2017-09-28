<?php

return array(

	'testfarm2-multiversion' => array(

		'server' => '(?P<wiki>[a-z])(?P<wiki2>[a-z])\.testfarm2-multiversion\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki',
			       'file' => 'varwiki.php' ),
			array( 'variable' => 'wiki2',
			       'file' => 'varwiki.php' ),
		),
		'suffix' => 'testfarm2',
		'wikiID' => '$wiki$wiki2testfarm2',
		'versions' => 'versions.php',
		'config' => array(),
	),

	'testfarm2-multiversion-redirect' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm2-multiversion-redirect\.example\.org',
		'redirect' => '$wiki.testfarm2-multiversion.example.org',
	),

	'testfarm2-multiversion-bis' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm2-multiversion-bis\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki',
			       'file' => 'varwikibis.php' ),
		),
		'suffix' => 'testfarm2',
		'wikiID' => '$wikitestfarm2',
		'config' => array(),
	),

	'testfarm2-multiversion-ter' => array(

		'server' => '(?P<wiki>[a-z])\.testfarm2-multiversion-ter\.example\.org',
		'variables' => array(
			array( 'variable' => 'wiki' ),
		),
		'suffix' => 'testfarm2',
		'wikiID' => '$wikitestfarm2',
		'versions' => 'versionster.php',
		'config' => array(),
	),
);
