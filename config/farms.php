<?php

return array(
	
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
		'cache' => '/tmp/mw-cache/org-$version-$family-$lang',
		'config' => array(
			array( 'file' => 'org/InitialiseSettings.php',
			       'key' => '*'
			),
			array( 'file' => 'org/PrivateSettings.php',
			       'key' => '*'
			),
			array( 'file' => 'org/ExecSettings.php',
			       'exec' => true
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
		'cache' => '/tmp/mw-cache/com-example-$version-$client-$wiki',
		'config' => array(
			array( 'file' => 'com/example/DefaultSettings.yml',
			       'key' => 'default'
			),
			array( 'file' => 'com/example/InitialiseSettings.yml',
			       'key' => '*'
			),
			array( 'file' => 'com/example/PrivateSettings.yml',
			       'key' => '*'
			),
			array( 'file' => 'com/example/ExecSettings.php',
			       'exec' => true
			),
		),
	),
	
	# Aliases
	
	'(?<client>[a-z]+)_(?<wiki>[a-z]+)\.example\.com' => array(
		
		'redirect' => '$client-$wiki.example.com',
	),
);
