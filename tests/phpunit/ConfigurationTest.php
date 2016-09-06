<?php

require_once 'MediaWikiFarmTestCase.php';

/**
/**
 * Installation-independant methods tests.
 *
 * These tests operate on constant methods, i.e. which do not modify the internal state of the
 * object.
 *
 * @group MediaWikiFarm
 */
class ConfigurationTest extends MediaWikiFarmTestCase {

	/** @var MediaWikiFarm|null Test object. */
	protected $farm = null;

	/**
	 * Construct a default MediaWikiFarm object with a sample correct configuration file.
	 *
	 * Use the current MediaWiki installation to simulate a multiversion installation.
	 *
	 * @param string $host Host name.
	 * @return MediaWikiFarm
	 */
	static function constructMediaWikiFarm( $host ) {

		return new MediaWikiFarm( $host, self::$wgMediaWikiFarmConfigDir, null, false );
	}

	/**
	 * Set up the default MediaWikiFarm object with a sample correct configuration file.
	 */
	protected function setUp() {

		parent::setUp();

		$this->farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', self::$wgMediaWikiFarmConfigDir, null, self::$wgMediaWikiFarmCacheDir, 'index.php' );
	}

	/**
	 * Test a successful reading of a YAML file.
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

		$this->assertTrue( $this->farm->checkExistence() );

		$this->assertTrue( $this->farm->populateSettings() );

		$this->assertEquals( $result['settings'], $this->farm->getConfiguration( 'settings' ) );
		$this->assertEquals( $result['arrays'], $this->farm->getConfiguration( 'arrays' ) );
		$this->assertEquals( $result['skins'], $this->farm->getConfiguration( 'skins' ) );
		$this->assertEquals( $result['extensions'], $this->farm->getConfiguration( 'extensions' ) );
		$this->assertEquals( $result['execFiles'], $this->farm->getConfiguration( 'execFiles' ) );
		$this->assertEquals( $result['general'], $this->farm->getConfiguration( 'general' ) );
		$this->assertEquals( $result, $this->farm->getConfiguration() );
	}

	/**
	 * Test a successful reading of a YAML file.
	 *
	 * @covers MediaWikiFarm::loadMediaWikiConfig
	 * @covers MediaWikiFarm::getMediaWikiConfig
	 * @covers MediaWikiFarm::extractSkinsAndExtensions
	 * @covers MediaWikiFarm::detectLoadingMechanism
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::populateSettings
	 * @ uses MediaWikiFarm::populatewgConf
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::cacheFile
	 * @uses MediaWikiFarm::arrayMerge
	 * @uses MediaWikiFarm::SiteConfigurationSiteParamsCallback
	 */
	function testLoadMediaWikiConfig() {

		$this->assertTrue( $this->farm->checkExistence() );

		//$this->assertTrue( $this->farm->populateSettings() );

		$this->farm->loadMediaWikiConfig();
		$this->assertEquals( 200000, $GLOBALS['wgMemCachedTimeout'] );

		# Re-load to use config cache
		$this->farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', self::$wgMediaWikiFarmConfigDir, null, self::$wgMediaWikiFarmCacheDir, 'index.php' );
		$this->assertTrue( $this->farm->checkExistence() );
		$this->farm->getMediaWikiConfig();
		$this->assertEquals( 200000, $GLOBALS['wgMemCachedTimeout'] );
	}
}
