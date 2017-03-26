<?php
/**
 * Class LoadingTest.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0, or (at your option) any later version.
 * @license AGPL-3.0+ GNU Affero General Public License v3.0, or (at your option) any later version.
 */

require_once 'MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/MediaWikiFarm.php';

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
	 * @covers MediaWikiFarm::compileConfiguration
	 * @covers MediaWikiFarm::detectLoadingMechanism
	 * @covers MediaWikiFarm::loadMediaWikiConfig
	 * @covers MediaWikiFarm::activateExtensions
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
	 * @uses MediaWikiFarm::compileConfiguration
	 * @uses MediaWikiFarm::populateSettings
	 * @uses MediaWikiFarm::detectComposer
	 * @uses MediaWikiFarm::setEnvironment
	 * @uses MediaWikiFarm::getConfigFile
	 * @uses MediaWikiFarm::sortExtensions
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::arrayMerge
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::composerKey
	 * @uses MediaWikiFarm::prepareLog
	 * @uses MediaWikiFarm::issueLog
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
			),
			'arrays' => array(
				'wgFileExtensions' => array(
					0 => 'djvu',
				),
			),
			'extensions' => array(
				'ExtensionTestExtensionComposer2' => array( 'TestExtensionComposer2', 'extension', 'composer', 0 ),
				'SkinTestSkinComposer' => array( 'TestSkinComposer', 'skin', 'composer', 1 ),
				'SkinTestSkinRequireOnce' => array( 'TestSkinRequireOnce', 'skin', 'require_once', 2 ),
				'ExtensionTestExtensionRequireOnce' => array( 'TestExtensionRequireOnce', 'extension', 'require_once', 3 ),
				'SkinTestSkinWfLoadSkin' => array( 'TestSkinWfLoadSkin', 'skin', 'wfLoadSkin', 4 ),
				'SkinTestSkinBiLoading' => array( 'TestSkinBiLoading', 'skin', 'wfLoadSkin', 5 ),
				'ExtensionTestExtensionComposer' => array( 'TestExtensionComposer', 'extension', 'wfLoadExtension', 6 ),
				'ExtensionMediaWikiFarm' => array( 'MediaWikiFarm', 'extension', 'wfLoadExtension', 7 ),
				'ExtensionTestExtensionWfLoadExtension' => array( 'TestExtensionWfLoadExtension', 'extension', 'wfLoadExtension', 8 ),
				'ExtensionTestExtensionBiLoading' => array( 'TestExtensionBiLoading', 'extension', 'wfLoadExtension', 9 ),
			),
			'composer' => array(
				1 => 'SkinTestSkinComposer',
				# 'ExtensionTestExtensionComposer', # Autoloader included in TestSkinComposer autoloader
				0 => 'ExtensionTestExtensionComposer2', # Autoloader included in TestSkinComposer autoloader but this is added before
				                                        # in the config file (would be better to remove it but harmless anyway)
			),
		);
		$this->backupGlobalVariables( array_keys( $result['settings'] ) );
		$this->backupAndUnsetGlobalVariable( 'wgFileExtensions' );

		$exists = MediaWikiFarm::load( 'index.php', 'a.testfarm-multiversion-test-extensions.example.org', array(), array( 'ExtensionRegistry' => true ) );
		$this->assertEquals( 200, $exists );
		$this->assertEquals( 'vstub', $wgMediaWikiFarm->getVariable( '$VERSION' ) );

		$wgMediaWikiFarm->loadMediaWikiConfig();
		$this->assertEquals( $result['settings'], $wgMediaWikiFarm->getConfiguration( 'settings' ) );
		$this->assertEquals( $result['arrays'], $wgMediaWikiFarm->getConfiguration( 'arrays' ) );
		$this->assertEquals( $result['extensions'], $wgMediaWikiFarm->getConfiguration( 'extensions' ) );
		$this->assertEquals( $result['composer'], $wgMediaWikiFarm->getConfiguration( 'composer' ) );

		$trueGlobals = array();
		foreach( $GLOBALS as $key => $value ) {
			if( $value === true ) {
				$trueGlobals[] = $key;
			}
		}

		# Check that $result['settings'] (whose all values are 'true') is a subset of $trueGlobals
		unset( $result['settings']['wgUseExtensionTestMissingExtensionComposer'] );
		$this->assertEquals( array(), array_diff( array_keys( $result['settings'] ), $trueGlobals ) );
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
	 * @uses MediaWikiFarm::compileConfiguration
	 * @uses MediaWikiFarm::populateSettings
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::activateExtensions
	 * @uses MediaWikiFarm::setEnvironment
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::arrayMerge
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::compileConfiguration
	 * @uses MediaWikiFarm::detectLoadingMechanism
	 */
	function testRegistrationMediaWikiFarm() {

		$this->backupGlobalVariable( 'wgAutoloadClasses' );

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion.example.org',
			self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false,
			array(), array( 'ExtensionRegistry' => false )
		);
		$farm->checkExistence();
		$farm->compileConfiguration();
		$farm->loadMediaWikiConfig();

		$this->assertArrayHasKey( 'wgAutoloadClasses', $GLOBALS );
		$this->assertArrayHasKey( 'MediaWikiFarm', $GLOBALS['wgAutoloadClasses'] );
		$this->assertEquals( 'src/MediaWikiFarm.php', $GLOBALS['wgAutoloadClasses']['MediaWikiFarm'] );
	}

	/**
	 * Test exceptions in loading mechanisms.
	 *
	 * @covers MediaWikiFarm::compileConfiguration
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
	 * @uses MediaWikiFarm::compileConfiguration
	 * @uses MediaWikiFarm::populateSettings
	 * @uses MediaWikiFarm::activateExtensions
	 * @uses MediaWikiFarm::detectComposer
	 * @uses MediaWikiFarm::setEnvironment
	 * @uses MediaWikiFarm::sortExtensions
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::arrayMerge
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::prepareLog
	 * @uses MediaWikiFarm::issueLog
	 */
	function testExceptionsLoadingMechanisms() {

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

				'wgUseExtensionTestExtensionEmpty' => false,
				'wgUseSkinTestSkinEmpty' => false,
			),
			'extensions' => array(),
		);
		if( class_exists( 'ExtensionRegistry' ) ) {
			$result['settings']['wgUseExtensionTestExtensionWfLoadExtension'] = true;
			$result['settings']['wgUseSkinTestSkinWfLoadSkin'] = true;
			$result['extensions'] = array(
				'SkinTestSkinWfLoadSkin' => array( 'TestSkinWfLoadSkin', 'skin', 'wfLoadSkin', 0 ),
				'ExtensionMediaWikiFarm' => array( 'MediaWikiFarm', 'extension', 'wfLoadExtension', 1 ),
				'ExtensionTestExtensionWfLoadExtension' => array( 'TestExtensionWfLoadExtension', 'extension', 'wfLoadExtension', 2 ),
			);
		} else {
			$result['extensions'] = array(
				'ExtensionMediaWikiFarm' => array( 'MediaWikiFarm', 'extension', 'require_once', 0 ),
			);
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
	protected function tearDown() {

		if( class_exists( 'ExtensionRegistry' ) ) {
			ExtensionRegistry::getInstance()->clearQueue();
		}

		parent::tearDown();
	}
}
