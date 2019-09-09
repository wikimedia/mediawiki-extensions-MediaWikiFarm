<?php
/**
 * Class ConstructionTest.
 *
 * @package MediaWikiFarm\Tests
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

require_once dirname( __FILE__ ) . '/MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/MediaWikiFarm.php';

/**
 * Test object construction.
 *
 * @group MediaWikiFarm
 */
class ConstructionTest extends MediaWikiFarmTestCase {

	/**
	 * Test a successful initialisation of multiversion MediaWikiFarm with a correct configuration file farms.php.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getState
	 * @covers MediaWikiFarm::getFarmConfiguration
	 * @covers MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testSuccessfulConstructionMultiversion() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );

		$this->assertEquals( 'a.testfarm-multiversion.example.org', $farm->getVariable( '$SERVER' ) );

		$this->assertEquals( 'index.php', $farm->getState( 'EntryPoint' ) );
		$this->assertNull( $farm->getState( 'nonexistant' ) );

		$farmConfig = array(
			'server' => '(?P<wiki>[a-z])\.testfarm-multiversion\.example\.org',
			'variables' => array(
				array( 'variable' => 'wiki', ),
			),
			'suffix' => 'testfarm',
			'wikiID' => '$wikitestfarm',
			'versions' => 'versions.php',
			'coreconfig' => array(
				'farms.php',
			),
			'config' => array(
				array( 'file' => 'settings.php',
				       'key' => 'default',
				),
				array( 'file' => 'localsettings.php',
				       'key' => '*testfarm',
				       'default' => 'testfarm',
				),
				array( 'file' => 'globalsettings.php',
				       'key' => '*',
				),
				array( 'file' => 'LocalSettings.php',
				       'executable' => true,
				),
			),
		);

		$this->assertEquals( $farmConfig, $farm->getFarmConfiguration() );
	}

	/**
	 * Test a successful initialisation of MediaWikiFarm with a correct configuration file farms.php.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getState
	 * @covers MediaWikiFarm::getFarmConfiguration
	 * @covers MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testSuccessfulConstructionMonoversion() {

		$farm = new MediaWikiFarm(
				'a.testfarm-monoversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				null,
				false,
				array( 'EntryPoint' => 'index.php' ) );

		$this->assertEquals( 'a.testfarm-monoversion.example.org', $farm->getVariable( '$SERVER' ) );

		$this->assertEquals( 'index.php', $farm->getState( 'EntryPoint' ) );

		$farmConfig = array(
			'server' => '(?P<wiki>[a-z])\.testfarm-monoversion\.example\.org',
			'variables' => array(
				array( 'variable' => 'wiki',
			               'file' => 'varwiki.php', ),
			),
			'suffix' => 'testfarm',
			'wikiID' => '$wikitestfarm',
			'HTTP404' => 'phpunitHTTP404.php',
			'coreconfig' => array(
				'farms.php',
			),
			'config' => array(
				array( 'file' => 'settings.php',
				       'key' => 'default',
				),
				'settings.php',
				array( 'file' => 'missingfile.php',
				       'key' => 'default',
				),
				array( 'file' => 'localsettings.php',
				       'key' => '*testfarm',
				       'default' => 'testfarm',
				),
				array( 'file' => 'globalsettings.php',
				       'key' => '*',
				),
				array( 'file' => 'atestfarmsettings.php',
				       'key' => 'atestfarm',
				),
				array( 'file' => 'testfarmsettings.php',
				       'key' => 'testfarm',
				),
				array( 'file' => 'otherfarmsettings.php',
				       'key' => 'otherfarm',
				),
				array( 'file' => 'LocalSettings.php',
				       'executable' => true,
				),
			),
		);
		$this->assertEquals( $farmConfig, $farm->getFarmConfiguration() );
	}

	/**
	 * Test a successful initialisation of multiversion MediaWikiFarm selected by subdirectories with a correct configuration file farms.php.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getState
	 * @covers MediaWikiFarm::getFarmConfiguration
	 * @covers MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testSuccessfulConstructionMultiversionSubdirectories() {

		$farm = new MediaWikiFarm(
				'testfarm-multiversion-subdirectories.example.org',
				'/a',
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );

		$this->assertEquals( 'testfarm-multiversion-subdirectories.example.org/a', $farm->getVariable( '$SERVER' ) );

		$this->assertEquals( 'index.php', $farm->getState( 'EntryPoint' ) );
		$this->assertNull( $farm->getState( 'nonexistant' ) );

		$farmConfig = array(
			'server' => 'testfarm-multiversion-subdirectories\.example\.org/(?P<wiki>[a-z])',
			'variables' => array(
				array( 'variable' => 'wiki', ),
			),
			'suffix' => 'testfarm',
			'wikiID' => '$wikitestfarm',
			'versions' => 'versions.php',
			'coreconfig' => array(
				'farms.php',
			),
			'config' => array(
				array( 'file' => 'settings.php',
				       'key' => 'default',
				),
				array( 'file' => 'localsettings.php',
				       'key' => '*testfarm',
				       'default' => 'testfarm',
				),
				array( 'file' => 'globalsettings.php',
				       'key' => '*',
				),
				array( 'file' => 'LocalSettings.php',
				       'executable' => true,
				),
			),
		);

		$this->assertEquals( $farmConfig, $farm->getFarmConfiguration() );
	}

	/**
	 * Test when there is no configuration file farms.yml/json/php.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage No configuration file found
	 */
	public function testFailedConstruction() {

		$wgMediaWikiFarmConfigDir = dirname( self::$wgMediaWikiFarmConfigDir );

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Missing host name in constructor
	 */
	public function testFailedConstruction2() {

		$farm = new MediaWikiFarm(
				0,
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Invalid directory for the farm configuration
	 */
	public function testFailedConstruction3() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				0,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Invalid directory for the farm configuration
	 */
	public function testFailedConstruction4() {

		$wgMediaWikiFarmConfigDir = self::$wgMediaWikiFarmConfigDir . '/farms.php';

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Code directory must be null or a directory
	 */
	public function testFailedConstruction5() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				0,
				false,
				array( 'EntryPoint' => 'index.php' ) );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Code directory must be null or a directory
	 */
	public function testFailedConstruction6() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir . '/farms.php',
				false,
				array( 'EntryPoint' => 'index.php' ) );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Cache directory must be false or a directory
	 */
	public function testFailedConstruction7() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				0,
				array( 'EntryPoint' => 'index.php' ) );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage State must be an array
	 */
	public function testFailedConstruction8() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				0 );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Undefined host
	 */
	public function testFailedConstruction9() {

		$this->backupAndUnsetGlobalSubvariable( '_SERVER', 'HTTP_HOST' );
		$this->backupAndUnsetGlobalSubvariable( '_SERVER', 'SERVER_NAME' );

		$farm = new MediaWikiFarm(
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Entry point must be a string
	 */
	public function testFailedConstruction10() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 0 ) );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage InnerMediaWiki state must be a bool
	 */
	public function testFailedConstruction11() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'InnerMediaWiki' => 0 ) );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Environment must be an array
	 */
	public function testFailedConstruction12() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array(),
				0 );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage ExtensionRegistry parameter must be a bool
	 */
	public function testFailedConstruction13() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array(),
				array( 'ExtensionRegistry' => 'true' ) );
	}

	/**
	 * Test successful construction with global variable for the host.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testSuccessfulConstructionWithGlobalVariable() {

		$this->backupAndSetGlobalSubvariable( '_SERVER', 'HTTP_HOST', 'a.testfarm-multiversion.example.org' );
		$this->backupAndUnsetGlobalSubvariable( '_SERVER', 'SERVER_NAME' );

		$farm = new MediaWikiFarm(
				null,
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );

		$this->assertEquals( 'a.testfarm-multiversion.example.org', $farm->getVariable( '$SERVER' ) );
	}

	/**
	 * Test successful construction with global variable for the host.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testSuccessfulConstructionWithGlobalVariable2() {

		$this->backupAndUnsetGlobalSubvariable( '_SERVER', 'HTTP_HOST' );
		$this->backupAndSetGlobalSubvariable( '_SERVER', 'SERVER_NAME', 'a.testfarm-multiversion.example.org' );

		$farm = new MediaWikiFarm(
				null,
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );

		$this->assertEquals( 'a.testfarm-multiversion.example.org', $farm->getVariable( '$SERVER' ) );
	}

	/**
	 * Test successful construction with global variable for the path.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testSuccessfulConstructionWithGlobalVariable3() {

		$this->backupAndSetGlobalSubvariable( '_SERVER', 'HTTP_HOST', 'a.testfarm-multiversion.example.org' );
		$this->backupAndUnsetGlobalSubvariable( '_SERVER', 'SERVER_NAME' );
		$this->backupAndSetGlobalSubvariable( '_SERVER', 'REQUEST_URI', '' );

		$farm = new MediaWikiFarm(
				null,
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );

		$this->assertEquals( 'a.testfarm-multiversion.example.org', $farm->getVariable( '$SERVER' ) );
	}

	/**
	 * Test a normal path is correctly recognised.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testNormalPath() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				'/',
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );

		$this->assertEquals( 'a.testfarm-multiversion.example.org', $farm->getVariable( '$SERVER' ) );
	}

	/**
	 * Test a normal path is correctly recognised.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testNormalPath2() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				'/wiki/Main_Page',
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );

		$this->assertEquals( 'a.testfarm-multiversion.example.org', $farm->getVariable( '$SERVER' ) );
	}

	/**
	 * Test when the path is written in the server name.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage No farm corresponding to this host
	 */
	public function testFailedPath() {

		$farm = new MediaWikiFarm(
				'testfarm-multiversion-subdirectories.example.org/a',
				'',
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );
	}

	/**
	 * Test when the path is written in the server name.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage No farm corresponding to this host
	 */
	public function testFailedPath2() {

		$farm = new MediaWikiFarm(
				'testfarm-multiversion-subdirectories.example.or',
				'g/a',
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );
	}

	/**
	 * Test when the path is written in the server name.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage No farm corresponding to this host
	 */
	public function testFailedPath3() {

		$farm = new MediaWikiFarm(
				'testfarm-multiversion-subdirectories.example.org',
				'/A',
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );
	}

	/**
	 * Test creation of cache directory.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getCacheDir
	 * @uses MediaWikiFarm::readFile
	 * @uses AbstractMediaWikiFarmScript
	 * @uses MediaWikiFarmUtils
	 */
	public function testCacheDirectoryCreation() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				self::$wgMediaWikiFarmCacheDir,
				array( 'EntryPoint' => 'index.php' ) );

		$this->assertEquals( self::$wgMediaWikiFarmCacheDir, $farm->getCacheDir() );
	}

	/**
	 * Test the basic object properties (code, cache, and farm directories).
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getFarmDir
	 * @covers MediaWikiFarm::getCodeDir
	 * @covers MediaWikiFarm::getCacheDir
	 * @covers MediaWikiFarm::getConfigFile
	 * @covers MediaWikiFarm::getConfiguration
	 * @covers MediaWikiFarm::isLocalSettingsFresh
	 * @covers MediaWikiFarmConfiguration::__construct
	 * @covers MediaWikiFarmConfiguration::getConfiguration
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testCheckBasicObjectPropertiesMultiversion() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );

		# Check farm directory
		$this->assertEquals( self::$wgMediaWikiFarmFarmDir, $farm->getFarmDir() );

		# Check code directory
		$this->assertEquals( self::$wgMediaWikiFarmCodeDir, $farm->getCodeDir() );

		# Check cache directory
		$this->assertFalse( $farm->getCacheDir() );

		# Check executable file [farm]/src/main.php
		$this->assertEquals( self::$wgMediaWikiFarmFarmDir . '/src/main.php', $farm->getConfigFile() );

		$this->assertEquals(
			array(
				'settings' => array(),
				'arrays' => array(),
				'extensions' => array(),
				'execFiles' => array(),
				'composer' => array(),
			),
			$farm->getConfiguration()
		);
	}

	/**
	 * Test the basic object properties (code, cache, and farm directories).
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getCodeDir
	 * @covers MediaWikiFarm::getCacheDir
	 * @covers MediaWikiFarm::getConfigFile
	 * @covers MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testCheckBasicObjectPropertiesMonoversion() {

		$farm = new MediaWikiFarm(
				'a.testfarm-monoversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				null,
				false,
				array( 'EntryPoint' => 'index.php' ) );

		# Check code directory
		$this->assertNull( $farm->getCodeDir() );

		# Check cache directory
		$this->assertFalse( $farm->getCacheDir() );

		# Check executable file [farm]/src/main.php
		$this->assertEquals( self::$wgMediaWikiFarmFarmDir . '/src/main.php', $farm->getConfigFile() );
	}

	/**
	 * Test a normal redirect.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testNormalRedirect() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion-redirect.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );

		$this->assertEquals( 'a.testfarm-multiversion.example.org', $farm->getVariable( '$SERVER' ) );
	}

	/**
	 * Test an infinite redirect.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Infinite or too long redirect detected
	 */
	public function testInfiniteRedirect() {

		$farm = new MediaWikiFarm(
				'a.testfarm-infinite-redirect.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );
	}

	/**
	 * Test a missing farm.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage No farm corresponding to this host
	 */
	public function testMissingFarm() {

		$farm = new MediaWikiFarm(
				'a.testfarm-missing.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );
	}

	/**
	 * Test the 'loading' function with existant wiki.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::getConfigDir
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::getFarmConfiguration
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarm::getConfigFile
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::compileConfiguration
	 * @uses MediaWikiFarm::prepareLog
	 * @uses MediaWikiFarm::issueLog
	 * @uses MediaWikiFarmConfiguration::__construct
	 * @uses MediaWikiFarmConfiguration::populateSettings
	 * @uses MediaWikiFarmConfiguration::activateExtensions
	 * @uses MediaWikiFarmConfiguration::sortExtensions
	 * @uses MediaWikiFarmConfiguration::detectComposer
	 * @uses MediaWikiFarmConfiguration::setEnvironment
	 * @uses MediaWikiFarmConfiguration::getConfiguration
	 * @uses MediaWikiFarmUtils
	 */
	public function testLoadingCorrect() {

		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarm', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', self::$wgMediaWikiFarmCodeDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmSyslog', false );
		$this->backupAndSetGlobalSubvariable( '_SERVER', 'HTTP_HOST', 'a.testfarm-multiversion.example.org' );
		$cwd = getcwd();

		$code = MediaWikiFarm::load( 'index.php' );

		$this->assertEquals( 200, $code );
		$this->assertEquals( 'a.testfarm-multiversion.example.org', $GLOBALS['wgMediaWikiFarm']->getVariable( '$SERVER' ) );
		$this->assertEquals( self::$wgMediaWikiFarmCodeDir . '/vstub', getcwd() );
		$this->assertEquals( $GLOBALS['wgMediaWikiFarmCodeDir'] . '/' . $GLOBALS['wgMediaWikiFarm']->getVariable( '$VERSION' ), getcwd() );

		# For PHPUnit 3.4 which does not restore the current path
		if( getcwd() != $cwd ) {
			chdir( $cwd );
		}
	}

	/**
	 * Test the 'loading' function with nonexistant wiki in an existant farm.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::getConfigDir
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::getFarmConfiguration
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::compileConfiguration
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::prepareLog
	 * @uses MediaWikiFarm::issueLog
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarm::getFarmConfiguration
	 * @uses MediaWikiFarmConfiguration::__construct
	 * @uses MediaWikiFarmConfiguration::populateSettings
	 * @uses MediaWikiFarmConfiguration::activateExtensions
	 * @uses MediaWikiFarmConfiguration::detectComposer
	 * @uses MediaWikiFarmConfiguration::sortExtensions
	 * @uses MediaWikiFarmConfiguration::setEnvironment
	 * @uses MediaWikiFarmConfiguration::getConfiguration
	 * @uses MediaWikiFarmUtils
	 */
	public function testLoadingSoftMissingError() {

		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarm', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmSyslog', false );
		$this->backupAndSetGlobalSubvariable( '_SERVER', 'HTTP_HOST', null );
		$this->backupAndSetGlobalSubvariable( '_SERVER', 'SERVER_NAME', 'z.testfarm-monoversion-with-file-variable-without-version.example.org' );
		$this->backupAndUnsetGlobalVariable( 'wgMediaWikiFarmHTTP404Executed' );

		$code = MediaWikiFarm::load( 'index.php' );

		$wgMediaWikiFarm = array_key_exists( 'wgMediaWikiFarm', $GLOBALS ) ? $GLOBALS['wgMediaWikiFarm'] : null;
		$this->assertNotNull( $wgMediaWikiFarm );

		$this->assertEquals( 404, $code, 'The host was not evaluated as “soft-missing” (existing farm, nonexistant wiki).' );
		$farmConfig = $wgMediaWikiFarm->getFarmConfiguration();
		$this->assertEquals( 'phpunitHTTP404.php', $wgMediaWikiFarm->replaceVariables( $farmConfig['HTTP404'] ) );

		$this->assertTrue(
			array_key_exists( 'wgMediaWikiFarmHTTP404Executed', $GLOBALS ) && $GLOBALS['wgMediaWikiFarmHTTP404Executed'] === true,
			'The PHP file corresponding to HTTP errors “404 Not Found” was not executed.'
		);
	}

	/**
	 * Test the 'loading' function with a nonexistant farm.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::prepareLog
	 * @uses MediaWikiFarm::issueLog
	 * @uses MediaWikiFarmUtils
	 */
	public function testLoadingHardMissingError() {

		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarm', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmSyslog', false );
		$this->backupAndSetGlobalSubvariable( '_SERVER', 'HTTP_HOST', 'a.testfarm-nonexistant.example.org' );

		$code = MediaWikiFarm::load( 'index.php' );

		$this->assertEquals( 500, $code, 'The host was not evaluated as “hard-missing” (nonexistant farm).' );
	}

	/**
	 * Load a YAML config file.
	 *
	 * @covers MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 * @uses MediaWikiFarmUtils5_3
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Infinite or too long redirect detected
	 */
	public function testYAMLConfigFile() {

		$farm = new MediaWikiFarm(
				'a.testfarm-infinite-redirect.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir . '/yaml',
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );
	}

	/**
	 * Load a JSON config file.
	 *
	 * @covers MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Infinite or too long redirect detected
	 */
	public function testJSONConfigFile() {

		$farm = new MediaWikiFarm(
				'a.testfarm-infinite-redirect.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir . '/json',
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );
	}
}
