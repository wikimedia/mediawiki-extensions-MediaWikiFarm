<?php

/**
 * Installation-independant methods tests.
 *
 * These tests operate on constant methods, i.e. which do not modify the internal state of the
 * object.
 *
 * @group MediaWikiFarm
 */
class ConfigurationTest extends MediaWikiTestCase {

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

		$wgMediaWikiFarmConfigDirTest = dirname( __FILE__ ) . '/data/config';
		$farm = new MediaWikiFarm( $host, $wgMediaWikiFarmConfigDirTest, null, false );

		return $farm;
	}

	/**
	 * Set up the default MediaWikiFarm object with a sample correct configuration file.
	 */
	protected function setUp() {
		
		parent::setUp();
		
		$this->farm = self::constructMediaWikiFarm( 'a.testfarm-monoversion.example.org', null, false, true );
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
	 * Remove 'data/cache' cache directory.
	 */
	protected function tearDown() {

		wfRecursiveRemoveDir( dirname( __FILE__ ) . '/data/cache' );

		parent::tearDown();
	}
}
