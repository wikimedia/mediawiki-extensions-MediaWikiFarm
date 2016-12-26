<?php

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
	 * @covers MediaWikiFarm::populateSettings
	 * @covers MediaWikiFarm::getConfiguration
	 * @covers MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::cacheFile
	 * @uses MediaWikiFarm::extractSkinsAndExtensions
	 * @uses MediaWikiFarm::detectLoadingMechanism
	 * @uses MediaWikiFarm::arrayMerge
	 * @uses AbstractMediaWikiFarmScript::rmdirr
	 */
	function testHighlevelConfiguration() {

		$result = array(
			'settings' => array(
				'wgSitename' => 'Sid It',
				'wgUsePathInfo' => true,
				'wgDBprefix' => '',
				'wgMainCacheType' => 2,
				'wgMemCachedServers' => array(
					0 => '127.0.0.1:11211',
				),
				'wgMemCachedTimeout' => 200000,
				'wgDefaultSkin' => 'vector',
				'wgUseSkinVector' => true,
				'wgUseSkinMonoBook' => false,
				'wgUseExtensionParserFunctions' => true,
				'wgUseExtensionCentralAuth' => false,
				'wgUseExtensionConfirmEdit/QuestyCaptcha' => true,
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
			'extensions' => array(),
			'execFiles' => array(
				0 => dirname( __FILE__ ) . '/data/config/LocalSettings.php',
			),
		);

		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org',
		                           self::$wgMediaWikiFarmConfigDir, null, self::$wgMediaWikiFarmCacheDir, array( 'EntryPoint' => 'index.php' )
			);

		$this->assertTrue( $farm->checkExistence() );

		$this->assertTrue( $farm->populateSettings() );

		$this->assertEquals( $result['settings'], $farm->getConfiguration( 'settings' ) );
		$this->assertEquals( $result['arrays'], $farm->getConfiguration( 'arrays' ) );
		$this->assertEquals( $result['extensions'], $farm->getConfiguration( 'extensions' ) );
		$this->assertEquals( $result['execFiles'], $farm->getConfiguration( 'execFiles' ) );
		$this->assertEquals( $result, $farm->getConfiguration() );
	}

	/**
	 * Test the different extensions/skins loading mechanisms.
	 *
	 * @covers MediaWikiFarm::extractSkinsAndExtensions
	 * @covers MediaWikiFarm::detectLoadingMechanism
	 * @covers MediaWikiFarm::createLocalSettings
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::getMediaWikiConfig
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::populateSettings
	 * @uses MediaWikiFarm::sortExtensions
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::isMediaWiki
	 */
	function testLoadingMechanisms() {

		# First, without ExtensionRegistry
		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-test-extensions.example.org',
			self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', false,
			array( 'EntryPoint' => 'index.php', 'ExtensionRegistry' => false )
		);

		$farm->checkExistence();
		$farm->getMediaWikiConfig();
		$extensions = $farm->getConfiguration( 'extensions' );
		$this->assertContains( array( 'TestExtensionBiLoading', 'extension', 'require_once', 0 ), $extensions );
		$this->assertContains( array( 'TestExtensionRequireOnce', 'extension', 'require_once', 1 ), $extensions );
		$this->assertContains( array( 'MediaWikiFarm', 'extension', 'require_once', 6 ), $extensions );

		# Now with ExtensionRegistry
		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-test-extensions.example.org',
			self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', false,
			array( 'EntryPoint' => 'index.php', 'ExtensionRegistry' => true )
		);

		$farm->checkExistence();
		$farm->getMediaWikiConfig();
		$extensions = $farm->getConfiguration( 'extensions' );
		$this->assertContains( array( 'TestExtensionBiLoading', 'extension', 'wfLoadExtension', 1 ), $extensions );
		$this->assertContains( array( 'TestExtensionWfLoadExtension', 'extension', 'wfLoadExtension', 0 ), $extensions );
		$this->assertContains( array( 'MediaWikiFarm', 'extension', 'wfLoadExtension', 8 ), $extensions );

		# Now with imposed loading mechanism (1)
		$farm = new MediaWikiFarm( 'c.testfarm-multiversion-test-extensions.example.org',
			self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', false,
			array( 'EntryPoint' => 'index.php' )
		);

		$farm->checkExistence();
		$farm->getMediaWikiConfig();
		$settings = $farm->getConfiguration( 'settings' );
		$extensions = $farm->getConfiguration( 'extensions' );
		$this->assertTrue( $settings['wgUseExtensionTestExtensionBiLoading'] );
		$this->assertContains( array( 'TestExtensionBiLoading', 'extension', 'require_once', 0 ), $extensions );

		# Now with imposed loading mechanism (2)
		$farm = new MediaWikiFarm( 'd.testfarm-multiversion-test-extensions.example.org',
			self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', false,
			array( 'EntryPoint' => 'index.php' )
		);

		$farm->checkExistence();
		$farm->getMediaWikiConfig();
		$settings = $farm->getConfiguration( 'settings' );
		$extensions = $farm->getConfiguration( 'extensions' );
		$skins = $farm->getConfiguration( 'skins' );
		$this->assertTrue( $settings['wgUseExtensionTestExtensionBiLoading'] );
		$this->assertTrue( $settings['wgUseSkinTestSkinBiLoading'] );
		$this->assertContains( array( 'TestExtensionBiLoading', 'extension', 'require_once', 0 ), $extensions );
		$this->assertContains( array( 'TestSkinBiLoading', 'skin', 'require_once', 1 ), $extensions );
	}

	/**
	 * Test loading a compiled configuration into global scope (multiversion case).
	 *
	 * @covers MediaWikiFarm::getMediaWikiConfig
	 * @covers MediaWikiFarm::isLocalSettingsFresh
	 * @covers MediaWikiFarm::extractSkinsAndExtensions
	 * @covers MediaWikiFarm::detectLoadingMechanism
	 * @covers MediaWikiFarm::createLocalSettings
	 * @covers MediaWikiFarm::writeArrayAssignment
	 * @covers MediaWikiFarm::getConfigFile
	 * @covers MediaWikiFarm::cacheFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::populateSettings
	 * @uses MediaWikiFarm::sortExtensions
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
	 * @uses AbstractMediaWikiFarmScript::rmdirr
	 */
	function testLoadMediaWikiConfigMultiversion() {

		$farm = new MediaWikiFarm( 'b.testfarm-multiversion-test-extensions.example.org',
			self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', self::$wgMediaWikiFarmCacheDir,
			array( 'EntryPoint' => 'index.php' )
		);

		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( dirname( dirname( dirname( __FILE__ ) ) ) . '/src/main.php', $farm->getConfigFile() );

		# First load
		$farm->getMediaWikiConfig();
		$config = $farm->getConfiguration( 'settings' );
		$this->assertTrue( $config['wgUsePathInfo'] );
		$this->assertFalse( array_key_exists( 'wgUseExtensionConfirmEdit/QuestyCaptcha', $config ) );
		$this->assertTrue( array_key_exists( 'wgUseExtensionConfirmEditQuestyCaptcha', $config ) );
		$this->assertFalse( $config['wgUseExtensionConfirmEditQuestyCaptcha'] );

		# Re-load to use config cache
		AbstractMediaWikiFarmScript::rmdirr( self::$wgMediaWikiFarmCacheDir . '/versions.php' );
		$farm = new MediaWikiFarm( 'b.testfarm-multiversion-test-extensions.example.org',
			self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', self::$wgMediaWikiFarmCacheDir,
			array( 'EntryPoint' => 'index.php' )
		);
		$this->assertTrue( $farm->checkExistence() );
		$farm->getMediaWikiConfig( true );
		$config = $farm->getConfiguration( 'settings' );
		$this->assertTrue( $config['wgUsePathInfo'] );
		$this->assertFalse( array_key_exists( 'wgUseExtensionConfirmEdit/QuestyCaptcha', $config ) );
		$this->assertTrue( array_key_exists( 'wgUseExtensionConfirmEditQuestyCaptcha', $config ) );
		$this->assertFalse( $config['wgUseExtensionConfirmEditQuestyCaptcha'] );

		$this->assertEquals(
			self::$wgMediaWikiFarmCacheDir . '/LocalSettings/b.testfarm-multiversion-test-extensions.example.org.php',
			$farm->getConfigFile()
		);
	}

	/**
	 * Test loading a compiled configuration into global scope (monoversion case).
	 *
	 * @covers MediaWikiFarm::getMediaWikiConfig
	 * @covers MediaWikiFarm::isLocalSettingsFresh
	 * @covers MediaWikiFarm::extractSkinsAndExtensions
	 * @covers MediaWikiFarm::detectLoadingMechanism
	 * @covers MediaWikiFarm::createLocalSettings
	 * @covers MediaWikiFarm::writeArrayAssignment
	 * @covers MediaWikiFarm::getConfigFile
	 * @covers MediaWikiFarm::cacheFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::populateSettings
	 * @uses MediaWikiFarm::sortExtensions
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::arrayMerge
	 * @uses AbstractMediaWikiFarmScript::rmdirr
	 */
	function testLoadMediaWikiConfigMonoversion() {

		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org',
			self::$wgMediaWikiFarmConfigDir, null, self::$wgMediaWikiFarmCacheDir,
			array( 'EntryPoint' => 'index.php' )
		);

		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( dirname( dirname( dirname( __FILE__ ) ) ) . '/src/main.php', $farm->getConfigFile() );

		# First load
		$farm->getMediaWikiConfig();
		$config = $farm->getConfiguration( 'settings' );
		$this->assertEquals( 200000, $config['wgMemCachedTimeout'] );

		# Re-load to use config cache
		AbstractMediaWikiFarmScript::rmdirr( self::$wgMediaWikiFarmCacheDir . '/wikis/a.testfarm-monoversion.example.org.php' );
		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org',
			self::$wgMediaWikiFarmConfigDir, null, self::$wgMediaWikiFarmCacheDir,
			array( 'EntryPoint' => 'index.php' )
		);
		$this->assertTrue( $farm->checkExistence() );
		$farm->getMediaWikiConfig(); # This is for code coverage
		$farm->getMediaWikiConfig( true );
		$config = $farm->getConfiguration( 'settings' );
		$this->assertEquals( 200000, $config['wgMemCachedTimeout'] );

		$this->assertEquals( self::$wgMediaWikiFarmCacheDir . '/LocalSettings/a.testfarm-monoversion.example.org.php', $farm->getConfigFile() );
	}
}
