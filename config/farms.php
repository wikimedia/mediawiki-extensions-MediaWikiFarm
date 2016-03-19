<?php

return array(
	
	# Configuration similar to the Wikimedia farm
	'(?<lang>[a-z]+)\.(?<family>[a-z]+)\.org' => array(
		
		'variables' => array(
		
			array( 'variable' => 'family',
			),
			array( 'variable' => 'lang',
			       'file'     => 'org/$family.dblist',
			       'type'     => 'language',
			),
		),
		'suffix' => '$family',
		'wikiID' => '$lang$family',
		'data' => '/srv/data/org/$family/$lang',
		'cache' => '/tmp/mw-cache/org-$version-$family-$lang',
		'config' => array(
			'org/InitialiseSettings.php',
			'org/PrivateSettings.php',
		),
		'exec-config' => 'org/ExecSettings.php',
	),
	
	# Configuration for a small wiki farm
	'(?<client>[a-z]+)-(?<wiki>[a-z]+)\.example\.com' => array(
		
		'variables' => array(
			
			array( 'variable' => 'client',
			       'file'     => 'com/example/clients.yml',
			       'config'   => 'com/example/$client/InitialiseSettings.yml',
			),
			array( 'variable' => 'wiki',
			       'file'     => 'com/example/$client/wikis.yml',
			),
		),
		'suffix' => '$family',
		'wikiID' => '$wiki-$client',
		'data' => '/srv/data/com/example/$client/$wiki',
		'cache' => '/tmp/mw-cache/com-example-$version-$client-$wiki',
		'config' => 'com/example/InitialiseSettings.yml',
		'post-config' => array(
			'com/example/PrivateSettings.yml',
			'com/example/GlobalSettings.yml',
		),
		'exec-config' => 'com/example/ExecSettings.php',
	),
);
