<?php

return array(
	
	# Placeholder configuration for a first installation as detailled in the documentation
	'mywiki\.example\.org' => array(
		
		'suffix' => 'wiki',
		'wikiID' => 'mywiki',
		'config' => array(
			array( 'file' => 'LocalSettings.php',
			       'exec' => true,
			),
		),
	),
);

/*
	# Configuration similar to the Wikimedia farm
	'(?<lang>[a-z-]+)\.(?<family>[a-z]+)\.org' => array(
		
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
			array( 'file' => 'org/InitialiseSettings.php',
			       'key' => '*',
			),
			array( 'file' => 'org/PrivateSettings.php',
			       'key' => '*',
			),
			array( 'file' => 'org/ExecSettings.php',
			       'exec' => true,
			),
		),
	),
	
	# Configuration for a small wiki farm
	'(?<client>[a-z]+)-(?<wiki>[a-z]+)\.example\.com' => array(
		
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
			       'exec' => true,
			),
		),
	),
	
	# Aliases
	
	'(?<client>[a-z]+)_(?<wiki>[a-z]+)\.example\.com' => array(
		
		'redirect' => '$client-$wiki.example.com',
	),
);*/
