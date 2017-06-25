<?php
/**
 * Class ConfigurationTest.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0, or (at your option) any later version.
 * @license AGPL-3.0+ GNU Affero General Public License v3.0, or (at your option) any later version.
 */

require_once 'MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/MediaWikiFarm.php';

/**
 * Installation-independant methods tests.
 *
 * These tests operate on constant methods, i.e. which do not modify the internal state of the
 * object.
 *
 * @group MediaWikiFarm
 */
class ConfigurationTest extends MediaWikiFarmTestCase {

	/**
	 * Test compiling a configuration.
	 *
	 * @covers MediaWikiFarm::compileConfiguration
	 * @covers MediaWikiFarm::getConfiguration
	 * @covers MediaWikiFarm::replaceVariables
	 * @covers MediaWikiFarmConfiguration::setComposer
	 * @covers MediaWikiFarmConfiguration::setEnvironment
	 * @covers MediaWikiFarmConfiguration::getConfiguration
	 * @covers MediaWikiFarmConfiguration::populateSettings
	 * @covers MediaWikiFarmConfiguration::detectLoadingMechanism
	 * @covers MediaWikiFarmConfiguration::activateExtensions
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::getConfigDir
	 * @uses MediaWikiFarm::getFarmConfiguration
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::cacheFile
	 * @uses MediaWikiFarm::arrayMerge
	 * @uses MediaWikiFarmConfiguration::__construct
	 * @uses MediaWikiFarmConfiguration::getConfiguration
	 * @uses MediaWikiFarmConfiguration::setEnvironment
	 * @uses AbstractMediaWikiFarmScript::rmdirr
	 */
	function testHighlevelConfiguration() {

		$result = array(
			'settings' => array(
				'wgUseExtensionMediaWikiFarm' => true,
				'wgSitename' => 'Sid It',
				'wgUsePathInfo' => true,
				'wgDBprefix' => '',
				'wgMainCacheType' => 2,
				'wgMemCachedServers' => array(
					0 => '127.0.0.1:11211',
				),
				'wgMemCachedTimeout' => 97116,
				'wgDefaultSkin' => 'vector',
				'wgUseSkinVector' => true,
				'wgUseSkinMonoBook' => false,
				'wgUseExtensionParserFunctions' => true,
				'wgUseExtensionCentralAuth' => false,
				'wgUseExtensionConfirmEditQuestyCaptcha' => true,
				'wgUseLocalExtensionSmartLinks' => true,
				'wgUseLocalExtensionChangeTabs' => false,
				'wgServer' => 'https://a.testfarm-monoversion.example.org',
				'wgSkipSkins' => array(
					0 => 'MySkin',
					1 => 'Chick',
				),
				'wgActionPaths' => array(
					'edit' => '/edit/$1',
				),
			),
			'arrays' => array(
				'wgGroupPermissions' => array(
					'user' => array(
						'apihighlimits' => true,
						'delete' => false,
					),
					'sysop' => array(
						'fancypermission' => true,
						'overfancypermission' => false,
					),
				),
				'wgFileExtensions' => array(
					0 => 'pdf',
				),
			),
			'extensions' => array(
				'ExtensionMediaWikiFarm' => array( 'MediaWikiFarm', 'extension', null, 0 ),
				'SkinVector' => array( 'Vector', 'skin', null, 1 ),
				'SkinMonoBook' => array( 'MonoBook', 'skin', null, 2 ),
				'ExtensionParserFunctions' => array( 'ParserFunctions', 'extension', null, 3 ),
				'ExtensionCentralAuth' => array( 'CentralAuth', 'extension', null, 4 ),
				'ExtensionConfirmEdit/QuestyCaptcha' => array( 'ConfirmEdit/QuestyCaptcha', 'extension', null, 5 ),
			),
			'composer' => array(),
			'execFiles' => array(
				0 => dirname( __FILE__ ) . '/data/config/LocalSettings.php',
			),
		);

		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, null, self::$wgMediaWikiFarmCacheDir,
		                           array( 'EntryPoint' => 'index.php' ), array( 'ExtensionRegistry' => true )
			);

		$this->assertTrue( $farm->checkExistence() );

		$configurationObject = $farm->getConfiguration( null );
		$this->assertTrue( $configurationObject->populateSettings() );

		$this->assertNull( $farm->getConfiguration( 'nonexistant' ) );
		$this->assertNull( $farm->getConfiguration( 'settings', 'nonexistant' ) );
		$this->assertEquals( $result['settings']['wgActionPaths'], $farm->getConfiguration( 'settings', 'wgActionPaths' ) );

		$this->assertEquals( $result['settings'], $farm->getConfiguration( 'settings' ) );
		$this->assertEquals( $result['arrays'], $farm->getConfiguration( 'arrays' ) );
		$this->assertEquals( $result['extensions'], $farm->getConfiguration( 'extensions' ) );
		$this->assertEquals( $result['composer'], $farm->getConfiguration( 'composer' ) );
		$this->assertEquals( $result['execFiles'], $farm->getConfiguration( 'execFiles' ) );
		$this->assertEquals( $result, $farm->getConfiguration() );
	}

	/**
	 * Test the different extensions/skins loading mechanisms.
	 *
	 * @covers MediaWikiFarm::compileConfiguration
	 * @covers MediaWikiFarmConfiguration::setComposer
	 * @covers MediaWikiFarmConfiguration::setEnvironment
	 * @covers MediaWikiFarmConfiguration::populateSettings
	 * @covers MediaWikiFarmConfiguration::activateExtensions
	 * @covers MediaWikiFarmConfiguration::detectComposer
	 * @covers MediaWikiFarmConfiguration::detectLoadingMechanism
	 * @covers MediaWikiFarmConfiguration::sortExtensions
	 * @covers MediaWikiFarmConfiguration::createLocalSettings
	 * @covers MediaWikiFarmConfiguration::setEnvironment
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::getConfigDir
	 * @uses MediaWikiFarm::getFarmConfiguration
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::compileConfiguration
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::isMediaWiki
	 * @uses MediaWikiFarmConfiguration::__construct
	 * @uses MediaWikiFarmConfiguration::getConfiguration
	 * @uses MediaWikiFarmConfiguration::composerKey
	 */
	function testLoadingMechanisms() {

		# First, without ExtensionRegistry
		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-test-extensions.example.org', null,
			self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', false,
			array( 'EntryPoint' => 'index.php' ), array( 'ExtensionRegistry' => false )
		);

		$farm->checkExistence();
		$farm->compileConfiguration();
		$extensions = $farm->getConfiguration( 'extensions' );
		$this->assertContains( array( 'TestExtensionBiLoading', 'extension', 'require_once', 6 ), $extensions );
		$this->assertContains( array( 'TestExtensionRequireOnce', 'extension', 'require_once', 7 ), $extensions );
		$this->assertContains( array( 'MediaWikiFarm', 'extension', 'require_once', 5 ), $extensions );

		# Now with ExtensionRegistry
		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-test-extensions.example.org', null,
			self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', false,
			array( 'EntryPoint' => 'index.php' ), array( 'ExtensionRegistry' => true )
		);

		$farm->checkExistence();
		$farm->compileConfiguration();
		$extensions = $farm->getConfiguration( 'extensions' );
		$this->assertContains( array( 'TestExtensionBiLoading', 'extension', 'wfLoadExtension', 9 ), $extensions );
		$this->assertContains( array( 'TestExtensionWfLoadExtension', 'extension', 'wfLoadExtension', 8 ), $extensions );
		$this->assertContains( array( 'MediaWikiFarm', 'extension', 'wfLoadExtension', 7 ), $extensions );

		# Now with imposed loading mechanism (1)
		$farm = new MediaWikiFarm( 'c.testfarm-multiversion-test-extensions.example.org', null,
			self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', false,
			array( 'EntryPoint' => 'index.php' ), array( 'ExtensionRegistry' => true )
		);

		$farm->checkExistence();
		$farm->compileConfiguration();
		$settings = $farm->getConfiguration( 'settings' );
		$extensions = $farm->getConfiguration( 'extensions' );
		$this->assertTrue( $settings['wgUseExtensionTestExtensionBiLoading'] );
		$this->assertEquals( $extensions['ExtensionTestExtensionBiLoading'][2], 'require_once' );
		$this->assertContains( array( 'TestExtensionBiLoading', 'extension', 'require_once', 0 ), $extensions );

		# Now with imposed loading mechanism (2)
		$farm = new MediaWikiFarm( 'd.testfarm-multiversion-test-extensions.example.org', null,
			self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', false,
			array( 'EntryPoint' => 'index.php' ), array( 'ExtensionRegistry' => true )
		);

		$farm->checkExistence();
		$farm->compileConfiguration();
		$settings = $farm->getConfiguration( 'settings' );
		$extensions = $farm->getConfiguration( 'extensions' );
		$this->assertTrue( $settings['wgUseExtensionTestExtensionBiLoading'] );
		$this->assertTrue( $settings['wgUseSkinTestSkinBiLoading'] );
		$this->assertContains( array( 'TestExtensionBiLoading', 'extension', 'require_once', 1 ), $extensions );
		$this->assertContains( array( 'TestSkinBiLoading', 'skin', 'require_once', 0 ), $extensions );

		# Now with imposed loading mechanism (2)
		$farm = new MediaWikiFarm( 'e.testfarm-multiversion-test-extensions.example.org', null,
			self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', false,
			array( 'EntryPoint' => 'index.php' ), array( 'ExtensionRegistry' => true )
		);

		$farm->checkExistence();
		$farm->compileConfiguration();
		$settings = $farm->getConfiguration( 'settings' );
		$extensions = $farm->getConfiguration( 'extensions' );
		$this->assertTrue( $settings['wgUseExtensionTestExtensionBiLoading'] );
		$this->assertTrue( $settings['wgUseSkinTestSkinBiLoading'] );
		$this->assertContains( array( 'TestExtensionBiLoading', 'extension', 'wfLoadExtension', 2 ), $extensions );
		$this->assertContains( array( 'TestSkinBiLoading', 'skin', 'wfLoadSkin', 0 ), $extensions );
	}

	/**
	 * Test loading a compiled configuration into global scope (multiversion case).
	 *
	 * @covers MediaWikiFarm::compileConfiguration
	 * @covers MediaWikiFarm::isLocalSettingsFresh
	 * @covers MediaWikiFarm::compileConfiguration
	 * @covers MediaWikiFarm::getConfigFile
	 * @covers MediaWikiFarm::cacheFile
	 * @covers MediaWikiFarmConfiguration::setComposer
	 * @covers MediaWikiFarmConfiguration::setEnvironment
	 * @covers MediaWikiFarmConfiguration::populateSettings
	 * @covers MediaWikiFarmConfiguration::detectLoadingMechanism
	 * @covers MediaWikiFarmConfiguration::createLocalSettings
	 * @covers MediaWikiFarmConfiguration::writeArrayAssignment
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::getConfigDir
	 * @uses MediaWikiFarm::getFarmConfiguration
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::arrayMerge
	 * @uses MediaWikiFarm::isMediaWiki
	 * @uses MediaWikiFarmConfiguration::__construct
	 * @uses MediaWikiFarmConfiguration::sortExtensions
	 * @uses MediaWikiFarmConfiguration::activateExtensions
	 * @uses MediaWikiFarmConfiguration::detectComposer
	 * @uses MediaWikiFarmConfiguration::setEnvironment
	 * @uses MediaWikiFarmConfiguration::getConfiguration
	 * @uses MediaWikiFarmConfiguration::setComposer
	 * @uses AbstractMediaWikiFarmScript::rmdirr
	 */
	function testLoadMediaWikiConfigMultiversion() {

		$farm = new MediaWikiFarm( 'b.testfarm-multiversion-test-extensions.example.org', null,
			self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', self::$wgMediaWikiFarmCacheDir,
			array( 'EntryPoint' => 'index.php', 'InnerMediaWiki' => true )
		);

		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( dirname( dirname( dirname( __FILE__ ) ) ) . '/src/main.php', $farm->getConfigFile() );

		# First load
		$farm->compileConfiguration();
		$config = $farm->getConfiguration( 'settings' );
		$this->assertTrue( $config['wgUsePathInfo'] );
		$this->assertFalse( array_key_exists( 'wgUseExtensionConfirmEdit/QuestyCaptcha', $config ) );
		$this->assertTrue( array_key_exists( 'wgUseExtensionConfirmEditQuestyCaptcha', $config ) );
		$this->assertFalse( $config['wgUseExtensionConfirmEditQuestyCaptcha'] );

		# Re-load to use config cache
		AbstractMediaWikiFarmScript::rmdirr( self::$wgMediaWikiFarmCacheDir . '/versions.php' );
		$farm = new MediaWikiFarm( 'b.testfarm-multiversion-test-extensions.example.org', null,
			self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', self::$wgMediaWikiFarmCacheDir,
			array( 'EntryPoint' => 'index.php', 'InnerMediaWiki' => true )
		);
		$this->assertTrue( $farm->checkExistence() );
		$farm->compileConfiguration();
		$this->assertEquals(
			self::$wgMediaWikiFarmCacheDir . '/LocalSettings/b.testfarm-multiversion-test-extensions.example.org.php',
			$farm->getConfigFile()
		);
	}

	/**
	 * Test loading a compiled configuration into global scope (monoversion case).
	 *
	 * @covers MediaWikiFarm::compileConfiguration
	 * @covers MediaWikiFarm::isLocalSettingsFresh
	 * @covers MediaWikiFarm::compileConfiguration
	 * @covers MediaWikiFarm::getConfigFile
	 * @covers MediaWikiFarm::cacheFile
	 * @covers MediaWikiFarmConfiguration::setComposer
	 * @covers MediaWikiFarmConfiguration::setEnvironment
	 * @covers MediaWikiFarmConfiguration::activateExtensions
	 * @covers MediaWikiFarmConfiguration::populateSettings
	 * @covers MediaWikiFarmConfiguration::detectLoadingMechanism
	 * @covers MediaWikiFarmConfiguration::createLocalSettings
	 * @covers MediaWikiFarmConfiguration::writeArrayAssignment
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::getConfigDir
	 * @uses MediaWikiFarm::getFarmConfiguration
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::arrayMerge
	 * @uses MediaWikiFarmConfiguration::__construct
	 * @uses MediaWikiFarmConfiguration::getConfiguration
	 * @uses MediaWikiFarmConfiguration::setComposer
	 * @uses MediaWikiFarmConfiguration::detectComposer
	 * @uses MediaWikiFarmConfiguration::setEnvironment
	 * @uses MediaWikiFarmConfiguration::sortExtensions
	 * @uses MediaWikiFarmConfiguration::composerKey
	 * @uses AbstractMediaWikiFarmScript::rmdirr
	 */
	function testLoadMediaWikiConfigMonoversion() {

		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', null,
			self::$wgMediaWikiFarmConfigDir, null, self::$wgMediaWikiFarmCacheDir,
			array( 'EntryPoint' => 'index.php', 'InnerMediaWiki' => true )
		);

		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( dirname( dirname( dirname( __FILE__ ) ) ) . '/src/main.php', $farm->getConfigFile() );

		# First load
		$farm->compileConfiguration();
		$config = $farm->getConfiguration( 'settings' );
		$this->assertEquals( 97116, $config['wgMemCachedTimeout'] );

		# Re-load to use config cache
		AbstractMediaWikiFarmScript::rmdirr( self::$wgMediaWikiFarmCacheDir . '/wikis/a.testfarm-monoversion.example.org.php' );
		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', null,
			self::$wgMediaWikiFarmConfigDir, null, self::$wgMediaWikiFarmCacheDir,
			array( 'EntryPoint' => 'index.php', 'InnerMediaWiki' => true )
		);
		$this->assertTrue( $farm->checkExistence() );
		$farm->compileConfiguration();
		$this->assertEquals( self::$wgMediaWikiFarmCacheDir . '/LocalSettings/a.testfarm-monoversion.example.org.php', $farm->getConfigFile() );
	}

	/**
	 * Test the sorting of extensions/skins.
	 *
	 * @covers MediaWikiFarmConfiguration::sortExtensions
	 * @covers MediaWikiFarmConfiguration::setComposer
	 * @covers MediaWikiFarmConfiguration::setEnvironment
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::getConfigDir
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::cacheFile
	 * @uses MediaWikiFarm::isMediaWiki
	 * @uses MediaWikiFarmConfiguration::__construct
	 * @uses AbstractMediaWikiFarmScript::rmdirr
	 */
	function testSort() {

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-test-extensions.example.org', null,
			self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', self::$wgMediaWikiFarmCacheDir,
			array( 'EntryPoint' => 'index.php', 'InnerMediaWiki' => false )
		);
		$farm->checkExistence();
		$configuration = $farm->getConfiguration( null );

		$this->assertEquals( -100,
			$configuration->sortExtensions(
				array( 'IrrealSkinComposerForTesting', 'skin', 'composer', 0 ),
				array( 'FictiveSkinComposerForTesting', 'skin', 'composer', 100 )
			),
			'The order of two inter-dependent Composer packages should be their original order.'
		);

		$this->assertEquals( 1,
			$configuration->sortExtensions(
				array( 'UnknownExtensionComposerForTesting', 'extension', 'composer', 100 ),
				array( 'IrrealSkinComposerForTesting', 'skin', 'composer', 0 )
			),
			'The order of two different-type Composer packages whose dependency graph is partly unknown should be skin-then-extension.'
		);

		$this->assertEquals( 100,
			$configuration->sortExtensions(
				array( 'UnknownSkinComposerForTesting', 'skin', 'composer', 100 ),
				array( 'IrrealSkinComposerForTesting', 'skin', 'composer', 0 )
			),
			'The order of two same-type Composer packages whose dependency graph is partly unknown should be their original order.'
		);

		$this->assertEquals( 1,
			$configuration->sortExtensions(
				array( 'TestSkinComposer', 'skin', 'composer', 100 ),
				array( 'TestExtensionComposer', 'extension', 'composer', 0 )
			),
			'The order of two known Composer packages whose the first depends on the second should change the order.'
		);

		$this->assertEquals( -1,
			$configuration->sortExtensions(
				array( 'TestExtensionComposer', 'extension', 'composer', 0 ),
				array( 'TestSkinComposer', 'skin', 'composer', 100 )
			),
			'The order of two known Composer packages whose the second depends on the first should keep the order.'
		);

		$this->assertEquals( 1,
			$configuration->sortExtensions(
				array( 'Wonderfun', 'extension', 'require_once', 0 ),
				array( 'Wonderfun', 'skin', 'require_once', 100 )
			),
			'The order of two same-loading extension and skin should place the skin before the extension.'
		);

		$this->assertEquals( 11,
			$configuration->sortExtensions(
				array( 'Wonderfun', 'extension', 'wfLoadExtension', 0 ),
				array( 'Wonderfun', 'skin', 'require_once', 100 )
			),
			'The order of two different-loading extensions depends on the loading mechanism (unknown, composer, require_once, wfLoad).'
		);

		$this->assertEquals( -100,
			$configuration->sortExtensions(
				array( 'Wonderfun', 'extension', 'wfLoadExtension', 0 ),
				array( 'Wonderful', 'extension', 'wfLoadExtension', 100 )
			),
			'The order of two same-loading same-type extensions is the original order.'
		);
	}
}
