<?php

return [
	'wgUseExtensionTestExtensionWfLoadExtension' => [
		'atestextensionsfarm' => true,
		'btestextensionsfarm' => true,
	],
	'wgUseExtensionTestExtensionBiLoading' => [
		'atestextensionsfarm' => true,
		'ctestextensionsfarm' => 'require_once',
		'dtestextensionsfarm' => 'require_once',
		'etestextensionsfarm' => 'wfLoadExtension',
	],
	'wgUseExtensionTestExtensionRequireOnce' => [
		'atestextensionsfarm' => true,
	],
	'wgUseExtensionTestExtensionComposer2' => [
		'atestextensionsfarm' => 'composer',
	],
	# TestSkinComposer should be before TestExtensionComposer:
	# since TSC depends on TEC (see data/mediawiki/vstub/vendor/
	# MediaWikiExtensions.php), the final order should put
	# TEC before TSC, at the contrary of the canonical order, this
	# is another thing which could break in the future and hence
	# should be unit-tested.
	'wgUseSkinTestSkinComposer' => [
		'atestextensionsfarm' => true,
	],
	# TSC depends on TEC and given MediaWikiFarm is aware of this
	# fact, it should not load the Composer autoloader of TEC
	# (since, if Composer did correctly its job, this autoloader is
	# included in TSC autoloader) but it should enable the
	# wfLoadExtension if there is one (this is the case)
	# 'wgUseExtensionTestExtensionComposer' => [
	# 	'atestextensionsfarm' => true,
	# ],
	'wgUseSkinTestSkinWfLoadSkin' => [
		'atestextensionsfarm' => true,
		'btestextensionsfarm' => true,
	],
	'wgUseSkinTestSkinBiLoading' => [
		'atestextensionsfarm' => true,
		'dtestextensionsfarm' => 'require_once',
		'etestextensionsfarm' => 'wfLoadSkin',
	],
	'wgUseSkinTestSkinRequireOnce' => [
		'atestextensionsfarm' => true,
	],
	'wgUseExtensionTestMissingExtensionComposer' => [
		'atestextensionsfarm' => 'composer',
	],
	'+wgFileExtensions' => [
		'atestextensionsfarm' => [
			0 => 'djvu',
		],
	],
	'wgUseExtensionConfirmEdit/QuestyCaptcha' => [
		'btestextensionsfarm' => true,
	],
	'wgUsePathInfo' => [
		'btestextensionsfarm' => true,
	],
	'wgUseExtensionTestExtensionMissing' => [
		'btestextensionsfarm' => true,
	],
	'wgUseSkinTestSkinMissing' => [
		'btestextensionsfarm' => true,
	],
	'wgUseExtensionTestExtensionEmpty' => [
		'btestextensionsfarm' => true,
	],
	'wgUseSkinTestSkinEmpty' => [
		'btestextensionsfarm' => true,
	],
	'wgExtensionDirectory' => [
		'etestextensionsfarm' => dirname( __DIR__ ) . '/mediawiki/vstub/extensions',
	],
	'wgStyleDirectory' => [
		'etestextensionsfarm' => dirname( __DIR__ ) . '/mediawiki/vstub/skins',
	],
];
