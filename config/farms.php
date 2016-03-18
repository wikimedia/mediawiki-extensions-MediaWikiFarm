<?php

return array(
	
	# Configuration similar to the Wikimedia farm
	'(?<lang>[a-z]+)\.(?<family>[a-z]+)\.org' => array(
		
		'variables' => array(
		
			array( 'variable' => 'family',
			       'file'     => '$family.dblist',
			),
			array( 'variable' => 'lang',
			       'type'     => 'language',
			),
		),
		'data' => '/srv/data/org/$family/$lang',
		'cache' => '/tmp/mw-cache/org-$version-$family-$lang',
		'config' => array(
			'org/InitialiseSettings.php',
			'org/PrivateSettings.php',
		),
	),
	
	# Configuration for a small wiki farm
	'(?<client>[a-z]+)-(?<wiki>[a-z]+)\.example\.com' => array(
		
		'variables' => array(
			
			array( 'variable' => 'client',
			       'file'     => 'clients.yml',
			       'config'   => 'com/example/$client/InitialiseSettings.yml',
			),
			array( 'variable' => 'wiki',
			       'file'     => '$client/wikis.yml',
			),
		),
		'data' => '/srv/data/com/example/$client/$wiki',
		'cache' => '/tmp/mw-cache/com-example-$version-$client-$wiki',
		'config' => 'com/example/InitialiseSettings.yml',
		'post-config' => array(
			'com/example/PrivateSettings.yml',
			'com/example/GlobalSettings.php',
		),
	),
);
