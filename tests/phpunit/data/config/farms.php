<?php

return array(
	
	'testfarm' => array(
		
		'server' => '(?P<wiki>[a-z])\.testfarm\.example\.org',
		'suffix' => 'testfarm',
		'wikiID' => '$wikitestfarm',
		'config' => array(
			array( 'file' => 'LocalSettings.php',
			       'exec' => true,
			),
		),
	),
	
	'redirect-testfarm' => array(
		
		'server' => '(?P<wiki>[a-z])-redirect\.testfarm\.example\.org',
		'redirect' => '$wiki.testfarm.example.org',
	),
);
