<?php

require_once 'MediaWikiFarmTestCase.php';

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
			'skins' => array(),
			'extensions' => array(),
			'execFiles' => array(
				0 => dirname( __FILE__ ) . '/data/config/LocalSettings.php',
			),
			'general' => array(),
		);

		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org',
		                           self::$wgMediaWikiFarmConfigDir, null, self::$wgMediaWikiFarmCacheDir, 'index.php'
			);

		$this->assertTrue( $farm->checkExistence() );

		$this->assertTrue( $farm->populateSettings() );

		$this->assertEquals( $result['settings'], $farm->getConfiguration( 'settings' ) );
		$this->assertEquals( $result['arrays'], $farm->getConfiguration( 'arrays' ) );
		$this->assertEquals( $result['skins'], $farm->getConfiguration( 'skins' ) );
		$this->assertEquals( $result['extensions'], $farm->getConfiguration( 'extensions' ) );
		$this->assertEquals( $result['execFiles'], $farm->getConfiguration( 'execFiles' ) );
		$this->assertEquals( $result['general'], $farm->getConfiguration( 'general' ) );
		$this->assertEquals( $result, $farm->getConfiguration() );
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
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::populateSettings
	 * @ uses MediaWikiFarm::populatewgConf
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::cacheFile
	 * @uses MediaWikiFarm::arrayMerge
	 * @uses MediaWikiFarm::isMediaWiki
	 * @ uses MediaWikiFarm::SiteConfigurationSiteParamsCallback
	 */
	function testLoadMediaWikiConfigMultiversion() {

		$farm = new MediaWikiFarm( 'b.testfarm-multiversion-test-extensions.example.org',
		                           self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', self::$wgMediaWikiFarmCacheDir, 'index.php'
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
		$farm = new MediaWikiFarm( 'b.testfarm-multiversion-test-extensions.example.org',
		                           self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', self::$wgMediaWikiFarmCacheDir, 'index.php'
			);
		$this->assertTrue( $farm->checkExistence() );
		$farm->getMediaWikiConfig( true );
		$config = $farm->getConfiguration( 'settings' );
		$this->assertTrue( $config['wgUsePathInfo'] );
		$this->assertFalse( array_key_exists( 'wgUseExtensionConfirmEdit/QuestyCaptcha', $config ) );
		$this->assertTrue( array_key_exists( 'wgUseExtensionConfirmEditQuestyCaptcha', $config ) );
		$this->assertFalse( $config['wgUseExtensionConfirmEditQuestyCaptcha'] );

		$this->assertEquals(
			self::$wgMediaWikiFarmCacheDir . '/testfarm-multiversion-test-extensions'
				. '/LocalSettings-vstub-testextensionsfarm-btestextensionsfarm.php',
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
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::populateSettings
	 * @ uses MediaWikiFarm::populatewgConf
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::cacheFile
	 * @uses MediaWikiFarm::arrayMerge
	 * @ uses MediaWikiFarm::SiteConfigurationSiteParamsCallback
	 */
	function testLoadMediaWikiConfigMonoversion() {

		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', self::$wgMediaWikiFarmConfigDir, null, self::$wgMediaWikiFarmCacheDir, 'index.php' );

		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( dirname( dirname( dirname( __FILE__ ) ) ) . '/src/main.php', $farm->getConfigFile() );

		# First load
		$farm->getMediaWikiConfig();
		$config = $farm->getConfiguration( 'settings' );
		$this->assertEquals( 200000, $config['wgMemCachedTimeout'] );

		# Re-load to use config cache
		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', self::$wgMediaWikiFarmConfigDir, null, self::$wgMediaWikiFarmCacheDir, 'index.php' );
		$this->assertTrue( $farm->checkExistence() );
		$farm->getMediaWikiConfig(); # This is for code coverage
		$farm->getMediaWikiConfig( true );
		$config = $farm->getConfiguration( 'settings' );
		$this->assertEquals( 200000, $config['wgMemCachedTimeout'] );

		$this->assertEquals( self::$wgMediaWikiFarmCacheDir . '/testfarm-monoversion/LocalSettings-testfarm-atestfarm.php', $farm->getConfigFile() );
	}
}
