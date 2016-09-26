<?php

require_once 'MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/MediaWikiFarm.php';

if( !function_exists( 'wfLoadExtension' ) ) {
	function wfLoadExtension( $ext, $path = null ) {}
}

if( !function_exists( 'wfLoadSkin' ) ) {
	function wfLoadSkin( $skin, $path = null ) {}
}

/**
 * Tests about extensions+skins loading.
 *
 * @group MediaWikiFarm
 */
class LoadingTest extends MediaWikiFarmTestCase {

	/**
	 * Set up the default MediaWikiFarm object with a sample correct configuration file.
	 */
	protected function setUp() {

		parent::setUp();

		if( class_exists( 'ExtensionRegistry' ) ) {
			ExtensionRegistry::getInstance()->loadFromQueue();
		}
	}

	/**
	 * Assert that ExtensionRegistry (MediaWiki 1.25+) queue is really emtpy as it should be.
	 */
	function assertPreConditions() {

		if( class_exists( 'ExtensionRegistry' ) ) {

			$this->assertEmpty( ExtensionRegistry::getInstance()->getQueue() );
		}
	}

	/**
	 * Test regular loading mechanisms.
	 *
	 * @covers MediaWikiFarm::extractSkinsAndExtensions
	 * @covers MediaWikiFarm::detectLoadingMechanism
	 * @covers MediaWikiFarm::loadMediaWikiConfig
	 * @uses MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::isMediaWiki
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::getMediaWikiConfig
	 * @uses MediaWikiFarm::populateSettings
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::arrayMerge
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::getVariable
	 */
	function testAllLoadingMechanisms() {

		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarm', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', dirname( __FILE__ ) . '/data/mediawiki' );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalVariable( 'wgExtensionDirectory', dirname( __FILE__ ) . '/data/mediawiki/vstub/extensions' );
		$this->backupAndSetGlobalVariable( 'wgStyleDirectory', dirname( __FILE__ ) . '/data/mediawiki/vstub/skins' );
		$wgMediaWikiFarm = &$GLOBALS['wgMediaWikiFarm'];
		$wgExtensionDirectory = $GLOBALS['wgExtensionDirectory'];
		$wgStyleDirectory = $GLOBALS['wgStyleDirectory'];

		$result = array(
			'settings' => array(
				'wgUseTestExtensionWfLoadExtension' => true,
				'wgUseTestExtensionBiLoading' => true,
				'wgUseTestExtensionRequireOnce' => true,
				'wgUseTestExtensionComposer' => true,
				'wgUseExtensionMediaWikiFarm' => true,

				'wgUseExtensionTestExtensionWfLoadExtension' => true,
				'wgUseExtensionTestExtensionBiLoading' => true,
				'wgUseExtensionTestExtensionRequireOnce' => true,
				'wgUseExtensionTestExtensionComposer' => true,

				'wgUseTestSkinWfLoadSkin' => true,
				'wgUseTestSkinBiLoading' => true,
				'wgUseTestSkinRequireOnce' => true,
				'wgUseTestSkinComposer' => true,

				'wgUseSkinTestSkinWfLoadSkin' => true,
				'wgUseSkinTestSkinBiLoading' => true,
				'wgUseSkinTestSkinRequireOnce' => true,
				'wgUseSkinTestSkinComposer' => true,
			),
			'arrays' => array(
				'wgFileExtensions' => array(
					0 => 'djvu',
				),
			),
			'extensions' => array(
				'TestExtensionWfLoadExtension' => 'wfLoadExtension',
				'TestExtensionBiLoading' => 'wfLoadExtension',
				'TestExtensionRequireOnce' => 'require_once',
				'TestExtensionComposer' => 'composer',
				'MediaWikiFarm' => 'wfLoadExtension',
			),
			'skins' => array(
				'TestSkinWfLoadSkin' => 'wfLoadSkin',
				'TestSkinBiLoading' => 'wfLoadSkin',
				'TestSkinRequireOnce' => 'require_once',
				'TestSkinComposer' => 'composer',
			),
		);
		$this->backupGlobalVariables( array_keys( $result['settings'] ) );
		$this->backupAndUnsetGlobalVariable( 'wgFileExtensions' );

		$exists = MediaWikiFarm::load( 'index.php', 'a.testfarm-multiversion-test-extensions.example.org', array( 'ExtensionRegistry' => true ) );
		$this->assertEquals( 200, $exists );
		$this->assertEquals( 'vstub', $wgMediaWikiFarm->getVariable( '$VERSION' ) );

		$wgMediaWikiFarm->loadMediaWikiConfig();
		$this->assertEquals( $result['settings'], $wgMediaWikiFarm->getConfiguration( 'settings' ) );
		$this->assertEquals( $result['arrays'], $wgMediaWikiFarm->getConfiguration( 'arrays' ) );
		$this->assertEquals( $result['extensions'], $wgMediaWikiFarm->getConfiguration( 'extensions' ) );
		$this->assertEquals( $result['skins'], $wgMediaWikiFarm->getConfiguration( 'skins' ) );

		$trueGlobals = array();
		foreach( $GLOBALS as $key => $value ) {
			if( $value === true ) {
				$trueGlobals[] = $key;
			}
		}

		# Check that $result['settings'] (whose all values are 'true') is a subset of $trueGlobals
		$this->assertEmpty( array_diff( array_keys( $result['settings'] ), $trueGlobals ) );
		$this->assertTrue( array_key_exists( 'wgFileExtensions', $GLOBALS ) );
		$this->assertEquals( $result['arrays']['wgFileExtensions'], $GLOBALS['wgFileExtensions'] );

		# Check that extensions+skins are in ExtensionRegistry queue
		if( class_exists( 'ExtensionRegistry' ) ) {
			$this->assertContains( dirname( dirname( dirname( __FILE__ ) ) ) . '/extension.json', array_keys( ExtensionRegistry::getInstance()->getQueue() ) );
			$this->assertContains( $wgExtensionDirectory . '/TestExtensionWfLoadExtension/extension.json', array_keys( ExtensionRegistry::getInstance()->getQueue() ) );
			$this->assertContains( $wgExtensionDirectory . '/TestExtensionBiLoading/extension.json', array_keys( ExtensionRegistry::getInstance()->getQueue() ) );
			$this->assertContains( $wgStyleDirectory . '/TestSkinWfLoadSkin/skin.json', array_keys( ExtensionRegistry::getInstance()->getQueue() ) );
			$this->assertContains( $wgStyleDirectory . '/TestSkinBiLoading/skin.json', array_keys( ExtensionRegistry::getInstance()->getQueue() ) );
		}
	}

	/**
	 * Test regular loading mechanisms.
	 *
	 * @covers MediaWikiFarm::loadMediaWikiConfig
	 * @uses MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::isMediaWiki
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::getMediaWikiConfig
	 * @uses MediaWikiFarm::populateSettings
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::arrayMerge
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::extractSkinsAndExtensions
	 * @uses MediaWikiFarm::detectLoadingMechanism
	 */
	function testRegistrationMediaWikiFarm() {

		$this->backupGlobalVariable( 'wgAutoloadClasses' );

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion.example.org',
			self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false,
			array( 'ExtensionRegistry' => false )
		);
		$farm->checkExistence();
		$farm->loadMediaWikiConfig();

		$this->assertArrayHasKey( 'MediaWikiFarm', $GLOBALS['wgAutoloadClasses'] );
		$this->assertEquals( 'src/MediaWikiFarm.php', $GLOBALS['wgAutoloadClasses']['MediaWikiFarm'] );
	}

	/**
	 * Test exceptions in loading mechanisms.
	 *
	 * @covers MediaWikiFarm::extractSkinsAndExtensions
	 * @covers MediaWikiFarm::detectLoadingMechanism
	 * @uses MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::isMediaWiki
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::getMediaWikiConfig
	 * @uses MediaWikiFarm::populateSettings
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::arrayMerge
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::getVariable
	 */
	function testExceptionsLoadingMechanisms() {

		global $wgMediaWikiFarm;

		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarm', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', dirname( __FILE__ ) . '/data/mediawiki' );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$wgMediaWikiFarm = &$GLOBALS['wgMediaWikiFarm'];

		$result = array(
			'settings' => array(
				'wgUseExtensionTestExtensionWfLoadExtension' => false,
				'wgUseSkinTestSkinWfLoadSkin' => false,
				'wgUseExtensionMediaWikiFarm' => true,

				'wgUsePathInfo' => true,

				'wgUseExtensionTestExtensionMissing' => false,
				'wgUseSkinTestSkinMissing' => false,
				'wgUseExtensionConfirmEditQuestyCaptcha' => false,
				'wgUseTestExtensionMissing' => true,
				'wgUseTestSkinMissing' => true,

				'wgUseExtensionTestExtensionEmpty' => false,
				'wgUseSkinTestSkinEmpty' => false,
				'wgUseTestExtensionEmpty' => true,
				'wgUseTestSkinEmpty' => true,
			),
			'extensions' => array(),
			'skins' => array(),
		);
		if( class_exists( 'ExtensionRegistry' ) ) {
			$result['settings']['wgUseExtensionTestExtensionWfLoadExtension'] = true;
			$result['settings']['wgUseSkinTestSkinWfLoadSkin'] = true;
			$result['extensions']['TestExtensionWfLoadExtension'] = 'wfLoadExtension';
			$result['extensions']['MediaWikiFarm'] = 'wfLoadExtension';
			$result['skins']['TestSkinWfLoadSkin'] = 'wfLoadSkin';
		}

		$exists = MediaWikiFarm::load( 'index.php', 'b.testfarm-multiversion-test-extensions.example.org' );
		$this->assertEquals( 200, $exists );
		$this->assertEquals( 'vstub', $wgMediaWikiFarm->getVariable( '$VERSION' ) );

		$wgMediaWikiFarm->getMediaWikiConfig();
		$this->assertEquals( $result['settings'], $wgMediaWikiFarm->getConfiguration( 'settings' ) );
		$this->assertEquals( $result['extensions'], $wgMediaWikiFarm->getConfiguration( 'extensions' ) );
		$this->assertEquals( $result['skins'], $wgMediaWikiFarm->getConfiguration( 'skins' ) );
	}

	/**
	 * Remove cache directory and clear queue of ExtensionRegistry (to avoid pollute it).
	 */
	protected function tearDown() {

		if( class_exists( 'ExtensionRegistry' ) ) {
			ExtensionRegistry::getInstance()->clearQueue();
		}

		parent::tearDown();
	}
}
