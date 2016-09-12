<?php

return array(
	'wgServer' => array(
		'a' => 'https://a.testfarm-monoversion.example.org',
	),
	'wgMemCachedTimeout' => array(
		'default' => 200000,
	),
	'wgSitename' => array(
		'b' => 'https://b.testfarm-monoversion.example.org',
	),
	'wgEnableUploads' => array(
		'b' => true,
	),
	'wgSkipSkins' => array(
		'+a' => array(
			'MySkin',
		),
		'+testfarm' => array(
			'Chick',
		),
	),
	'+wgFileExtensions' => array(
		'a' => array(
			'pdf',
		),
	),
	'+wgFileBlacklist' => array(
		'b' => array(
			'phpt',
		),
	),
);
