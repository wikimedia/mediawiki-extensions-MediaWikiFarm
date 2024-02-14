<?php

return [
	'wgServer' => [
		'a' => 'https://a.testfarm-monoversion.example.org',
	],
	'wgMemCachedTimeout' => [
		'default' => 200000,
	],
	'wgSitename' => [
		'b' => 'https://b.testfarm-monoversion.example.org',
	],
	'wgEnableUploads' => [
		'b' => true,
	],
	'wgSkipSkins' => [
		'+a' => [
			'MySkin',
		],
		'+testfarm' => [
			'Chick',
		],
	],
	'+wgFileExtensions' => [
		'a' => [
			'pdf',
		],
	],
	'+wgFileBlacklist' => [
		'b' => [
			'phpt',
		],
	],
];
