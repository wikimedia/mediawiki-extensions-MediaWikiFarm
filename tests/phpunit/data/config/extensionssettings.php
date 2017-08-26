<?php

return array(
	'wgUseExtensionTestExtensionWfLoadExtension' => array(
		'atestextensionsfarm' => true,
		'btestextensionsfarm' => true,
	),
	'wgUseExtensionTestExtensionBiLoading' => array(
		'atestextensionsfarm' => true,
		'ctestextensionsfarm' => 'require_once',
		'dtestextensionsfarm' => 'require_once',
		'etestextensionsfarm' => 'wfLoadExtension',
	),
	'wgUseExtensionTestExtensionRequireOnce' => array(
		'atestextensionsfarm' => true,
	),
	'wgUseExtensionTestExtensionComposer2' => array(
		'atestextensionsfarm' => 'composer',
	),
	# TestSkinComposer should be before TestExtensionComposer:
	# since TSC depends on TEC (see data/mediawiki/vstub/vendor/
	# MediaWikiExtensions.php), the final order should put
	# TEC before TSC, at the contrary of the canonical order, this
	# is another thing which could break in the future and hence
	# should be unit-tested.
	'wgUseSkinTestSkinComposer' => array(
		'atestextensionsfarm' => true,
	),
	# TSC depends on TEC and given MediaWikiFarm is aware of this
	# fact, it should not load the Composer autoloader of TEC
	# (since, if Composer did correctly its job, this autoloader is
	# included in TSC autoloader) but it should enable the
	# wfLoadExtension if there is one (this is the case)
	# 'wgUseExtensionTestExtensionComposer' => array(
	# 	'atestextensionsfarm' => true,
	# ),
	'wgUseSkinTestSkinWfLoadSkin' => array(
		'atestextensionsfarm' => true,
		'btestextensionsfarm' => true,
	),
	'wgUseSkinTestSkinBiLoading' => array(
		'atestextensionsfarm' => true,
		'dtestextensionsfarm' => 'require_once',
		'etestextensionsfarm' => 'wfLoadSkin',
	),
	'wgUseSkinTestSkinRequireOnce' => array(
		'atestextensionsfarm' => true,
	),
	'wgUseExtensionTestMissingExtensionComposer' => array(
		'atestextensionsfarm' => 'composer',
	),
	'+wgFileExtensions' => array(
		'atestextensionsfarm' => array(
			0 => 'djvu',
		),
	),
	'wgUseExtensionConfirmEdit/QuestyCaptcha' => array(
		'btestextensionsfarm' => true,
	),
	'wgUsePathInfo' => array(
		'btestextensionsfarm' => true,
	),
	'wgUseExtensionTestExtensionMissing' => array(
		'btestextensionsfarm' => true,
	),
	'wgUseSkinTestSkinMissing' => array(
		'btestextensionsfarm' => true,
	),
	'wgUseExtensionTestExtensionEmpty' => array(
		'btestextensionsfarm' => true,
	),
	'wgUseSkinTestSkinEmpty' => array(
		'btestextensionsfarm' => true,
	),
	'wgExtensionDirectory' => array(
		'etestextensionsfarm' => dirname( dirname( __FILE__ ) ) . '/mediawiki/vstub/extensions',
	),
	'wgStyleDirectory' => array(
		'etestextensionsfarm' => dirname( dirname( __FILE__ ) ) . '/mediawiki/vstub/skins',
	),
);
