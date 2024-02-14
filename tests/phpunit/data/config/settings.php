<?php

return [
	# Name and URL
	'wgSitename' => 'Sid It',
	'wgUsePathInfo' => true,

	# Database
	'wgDBprefix' => '',

	# Cache
	'wgMainCacheType' => 2,
	// 'wgMessageCacheType': 'CACHE_NONE' // todo: recognise it
	'wgMemCachedServers' => [
		0 => '127.0.0.1:11211',
	],
	'wgMemCachedTimeout' => 100000,

	# Rights
	'+wgGroupPermissions' => [
		'user' => [
			'apihighlimits' => true,
			'delete' => false,
		],
		'sysop' => [
			'fancypermission' => true,
			'overfancypermission' => false,
		],
	],

	# Skins
	'wgDefaultSkin' => 'vector',
	'wgUseSkinVector' => true,
	'wgUseSkinMonoBook' => false,

	# Extensions
	'wgUseExtensionParserFunctions' => true,
	'wgUseExtensionCentralAuth' => false,
	'wgUseExtensionConfirmEdit/QuestyCaptcha' => true,

	# Local extensions (flags used in some PHP file)
	'wgUseLocalExtensionSmartLinks' => true,
	'wgUseLocalExtensionChangeTabs' => false,
];
