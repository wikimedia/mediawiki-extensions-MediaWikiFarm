<?php
/**
 * Class LoadingTest.
 *
 * @package MediaWikiFarm\Tests
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

require_once __DIR__ . '/MediaWikiFarmTestCase.php';
require_once dirname( dirname( __DIR__ ) ) . '/src/MediaWikiFarm.php';

if( !function_exists( 'wfLoadExtension' ) ) {
	/**
	 * Placeholder for wfLoadExtension when standalone PHPUnit is executed.
	 *
	 * @package MediaWiki\Tests
	 *
	 * @param string $ext Extension name.
	 * @param string|null $path Absolute path of the extension.json file.
	 * @return void
	 */
	function wfLoadExtension( $ext, $path = null ) {}
}

if( !function_exists( 'wfLoadSkin' ) ) {
	/**
	 * Placeholder for wfLoadSkin when standalone PHPUnit is executed.
	 *
	 * @package MediaWiki\Tests
	 *
	 * @param string $skin Skin name.
	 * @param string|null $path Absolute path of the skin.json file.
	 * @return void
	 */
	function wfLoadSkin( $skin, $path = null ) {}
}

/**
 * Tests about extensions+skins loading.
 *
 * @group MediaWikiFarm
 */
class LoadingTest extends MediaWikiFarmTestCase {

	/**
	 * Test regular loading mechanisms.
	 *
	 * @covers MediaWikiFarm::compileConfiguration
	 * @covers MediaWikiFarm::loadMediaWikiConfig
	 * @covers MediaWikiFarmConfiguration::detectLoadingMechanism
	 * @covers MediaWikiFarmConfiguration::activateExtensions
	 * @uses MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::getConfigDir
	 * @uses MediaWikiFarm::getFarmConfiguration
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::compileConfiguration
	 * @uses MediaWikiFarm::getConfigFile
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::prepareLog
	 * @uses MediaWikiFarm::issueLog
	 * @uses MediaWikiFarmConfiguration::__construct
	 * @uses MediaWikiFarmConfiguration::sortExtensions
	 * @uses MediaWikiFarmConfiguration::composerKey
	 * @uses MediaWikiFarmConfiguration::setEnvironment
	 * @uses MediaWikiFarmConfiguration::populateSettings
	 * @uses MediaWikiFarmConfiguration::detectComposer
	 * @uses MediaWikiFarmConfiguration::getConfiguration
	 * @uses MediaWikiFarmUtils
	 */
	public function testAllLoadingMechanisms() {

		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarm', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', self::$wgMediaWikiFarmCodeDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalVariable( 'wgExtensionDirectory', self::$wgMediaWikiFarmCodeDir . '/vstub/extensions' );
		$this->backupAndSetGlobalVariable( 'wgStyleDirectory', self::$wgMediaWikiFarmCodeDir . '/vstub/skins' );
		$wgMediaWikiFarm = &$GLOBALS['wgMediaWikiFarm'];
		$wgExtensionDirectory = $GLOBALS['wgExtensionDirectory'];
		$wgStyleDirectory = $GLOBALS['wgStyleDirectory'];

		$result = [
			'settings' => [
				'wgUseExtensionMediaWikiFarm' => true,
				'wgUseExtensionTestExtensionWfLoadExtension' => true,
				'wgUseExtensionTestExtensionBiLoading' => true,
				'wgUseExtensionTestExtensionRequireOnce' => true,
				'wgUseExtensionTestExtensionComposer' => true,
				'wgUseExtensionTestExtensionComposer2' => true,
				'wgUseExtensionTestMissingExtensionComposer' => false,

				'wgUseSkinTestSkinWfLoadSkin' => true,
				'wgUseSkinTestSkinBiLoading' => true,
				'wgUseSkinTestSkinRequireOnce' => true,
				'wgUseSkinTestSkinComposer' => true,
			],
			'arrays' => [
				'wgFileExtensions' => [
					0 => 'djvu',
				],
			],
			'extensions' => [
				'ExtensionTestExtensionComposer' => [ 'TestExtensionComposer', 'extension', 'composer', 0 ],
				'ExtensionTestExtensionComposer2' => [ 'TestExtensionComposer2', 'extension', 'composer', 1 ],
				'SkinTestSkinComposer' => [ 'TestSkinComposer', 'skin', 'composer', 2 ],
				'SkinTestSkinRequireOnce' => [ 'TestSkinRequireOnce', 'skin', 'require_once', 3 ],
				'ExtensionTestExtensionRequireOnce' => [ 'TestExtensionRequireOnce', 'extension', 'require_once', 4 ],
				'SkinTestSkinWfLoadSkin' => [ 'TestSkinWfLoadSkin', 'skin', 'wfLoadSkin', 5 ],
				'SkinTestSkinBiLoading' => [ 'TestSkinBiLoading', 'skin', 'wfLoadSkin', 6 ],
				'ExtensionMediaWikiFarm' => [ 'MediaWikiFarm', 'extension', 'wfLoadExtension', 7 ],
				'ExtensionTestExtensionWfLoadExtension' => [ 'TestExtensionWfLoadExtension', 'extension', 'wfLoadExtension', 8 ],
				'ExtensionTestExtensionBiLoading' => [ 'TestExtensionBiLoading', 'extension', 'wfLoadExtension', 9 ],
			],
			'composer' => [
				1 => 'SkinTestSkinComposer',
				# 'ExtensionTestExtensionComposer', # Autoloader included in TestSkinComposer autoloader
				0 => 'ExtensionTestExtensionComposer2', # Autoloader included in TestSkinComposer autoloader but this is added before
				                                        # in the config file (would be better to remove it but harmless anyway)
			],
		];
		$this->backupGlobalVariables( array_keys( $result['settings'] ) );
		$this->backupAndUnsetGlobalVariable( 'wgFileExtensions' );

		$exists = MediaWikiFarm::load( 'index.php', 'a.testfarm-multiversion-test-extensions.example.org', null, [], [ 'ExtensionRegistry' => true ] );
		$this->assertEquals( 200, $exists );
		$this->assertEquals( 'vstub', $wgMediaWikiFarm->getVariable( '$VERSION' ) );

		if( class_exists( 'ExtensionRegistry' ) ) {
			$extensionRegistry = new ExtensionRegistry();
			$wgMediaWikiFarm->loadMediaWikiConfig( $extensionRegistry );
		} else {
			$wgMediaWikiFarm->loadMediaWikiConfig();
		}
		$this->assertEquals( $result['settings'], $wgMediaWikiFarm->getConfiguration( 'settings' ) );
		$this->assertEquals( $result['arrays'], $wgMediaWikiFarm->getConfiguration( 'arrays' ) );
		$this->assertEquals( $result['extensions'], $wgMediaWikiFarm->getConfiguration( 'extensions' ) );
		$this->assertEquals( $result['composer'], $wgMediaWikiFarm->getConfiguration( 'composer' ) );

		$trueGlobals = [];
		foreach( $GLOBALS as $key => $value ) {
			if( $value === true ) {
				$trueGlobals[] = $key;
			}
		}

		# Check that $result['settings'] (whose all values are 'true') is a subset of $trueGlobals
		unset( $result['settings']['wgUseExtensionTestMissingExtensionComposer'] );
		$this->assertEquals( [], array_diff( array_keys( $result['settings'] ), $trueGlobals ) );
		$this->assertTrue( array_key_exists( 'wgFileExtensions', $GLOBALS ) );
		$this->assertEquals( $result['arrays']['wgFileExtensions'], $GLOBALS['wgFileExtensions'] );

		# Check that extensions+skins are in ExtensionRegistry queue
		if( class_exists( 'ExtensionRegistry' ) ) {
			$this->assertContains( self::$wgMediaWikiFarmFarmDir . '/extension.json', array_keys( $extensionRegistry->getQueue() ) );
			$this->assertContains( $wgExtensionDirectory . '/TestExtensionWfLoadExtension/extension.json', array_keys( $extensionRegistry->getQueue() ) );
			$this->assertContains( $wgExtensionDirectory . '/TestExtensionBiLoading/extension.json', array_keys( $extensionRegistry->getQueue() ) );
			$this->assertContains( $wgStyleDirectory . '/TestSkinWfLoadSkin/skin.json', array_keys( $extensionRegistry->getQueue() ) );
			$this->assertContains( $wgStyleDirectory . '/TestSkinBiLoading/skin.json', array_keys( $extensionRegistry->getQueue() ) );
		}
	}

	/**
	 * Test exceptions in loading mechanisms.
	 *
	 * @covers MediaWikiFarm::compileConfiguration
	 * @covers MediaWikiFarmConfiguration::detectLoadingMechanism
	 * @uses MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::getConfigDir
	 * @uses MediaWikiFarm::getFarmConfiguration
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::compileConfiguration
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::prepareLog
	 * @uses MediaWikiFarm::issueLog
	 * @uses MediaWikiFarmConfiguration::__construct
	 * @uses MediaWikiFarmConfiguration::detectComposer
	 * @uses MediaWikiFarmConfiguration::populateSettings
	 * @uses MediaWikiFarmConfiguration::activateExtensions
	 * @uses MediaWikiFarmConfiguration::setEnvironment
	 * @uses MediaWikiFarmConfiguration::sortExtensions
	 * @uses MediaWikiFarmConfiguration::getConfiguration
	 * @uses MediaWikiFarmUtils
	 */
	public function testExceptionsLoadingMechanisms() {

		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarm', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', self::$wgMediaWikiFarmCodeDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$wgMediaWikiFarm = &$GLOBALS['wgMediaWikiFarm'];

		$result = [
			'settings' => [
				'wgUseExtensionTestExtensionWfLoadExtension' => false,
				'wgUseSkinTestSkinWfLoadSkin' => false,
				'wgUseExtensionMediaWikiFarm' => true,

				'wgUsePathInfo' => true,

				'wgUseExtensionTestExtensionMissing' => false,
				'wgUseSkinTestSkinMissing' => false,
				'wgUseExtensionConfirmEditQuestyCaptcha' => false,

				'wgUseExtensionTestExtensionEmpty' => false,
				'wgUseSkinTestSkinEmpty' => false,
			],
			'extensions' => [],
		];

		if( class_exists( 'ExtensionRegistry' ) ) {
			$result['settings']['wgUseExtensionTestExtensionWfLoadExtension'] = true;
			$result['settings']['wgUseSkinTestSkinWfLoadSkin'] = true;
			$result['extensions'] = [
				'SkinTestSkinWfLoadSkin' => [ 'TestSkinWfLoadSkin', 'skin', 'wfLoadSkin', 0 ],
				'ExtensionMediaWikiFarm' => [ 'MediaWikiFarm', 'extension', 'wfLoadExtension', 1 ],
				'ExtensionTestExtensionWfLoadExtension' => [ 'TestExtensionWfLoadExtension', 'extension', 'wfLoadExtension', 2 ],
			];
		} else {
			$result['extensions'] = [
				'ExtensionMediaWikiFarm' => [ 'MediaWikiFarm', 'extension', 'require_once', 0 ],
			];
		}

		$exists = MediaWikiFarm::load( 'index.php', 'b.testfarm-multiversion-test-extensions.example.org' );
		$this->assertEquals( 200, $exists );
		$this->assertEquals( 'vstub', $wgMediaWikiFarm->getVariable( '$VERSION' ) );

		$wgMediaWikiFarm->compileConfiguration();
		$this->assertEquals( $result['settings'], $wgMediaWikiFarm->getConfiguration( 'settings' ) );
		$this->assertEquals( $result['extensions'], $wgMediaWikiFarm->getConfiguration( 'extensions' ) );
	}

	/**
	 * Remove cache directory and clear queue of ExtensionRegistry (to avoid pollute it).
	 */
	protected function tearDown() : void {

		if( class_exists( 'ExtensionRegistry' ) ) {
			ExtensionRegistry::getInstance()->clearQueue();
		}

		parent::tearDown();
	}
}
