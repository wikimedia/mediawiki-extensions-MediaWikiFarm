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
			array( 'file' => 'LocalSettings.php',
			       'exec' => true,
			),
		),
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
