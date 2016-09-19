<?php

return array(
	# Name and URL
	'wgSitename' => 'Sid It',
	'wgUsePathInfo' => true,

	# Database
	'wgDBprefix' => '',

	# Cache
	'wgMainCacheType' => 2,
	// 'wgMessageCacheType': 'CACHE_NONE' // todo: recognise it
	'wgMemCachedServers' => array(
		0 => '127.0.0.1:11211',
	),
	'wgMemCachedTimeout' => 100000,

	# Rights
	'+wgGroupPermissions' => array(
		'user' => array(
			'apihighlimits' => true,
			'delete' => false,
		),
		'sysop' => array(
			'fancypermission' => true,
			'overfancypermission' => false,
		),
	),

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
);
