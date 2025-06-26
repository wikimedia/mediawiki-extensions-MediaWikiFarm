<?php
/**
 * Class ConstructionTest.
 *
 * @package MediaWikiFarm\Tests
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

require_once __DIR__ . '/MediaWikiFarmTestCase.php';
require_once dirname( dirname( __DIR__ ) ) . '/src/MediaWikiFarm.php';

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
				[ 'EntryPoint' => 'index.php' ] );

		$this->assertEquals( 'a.testfarm-multiversion.example.org', $farm->getVariable( '$SERVER' ) );

		$this->assertEquals( 'index.php', $farm->getState( 'EntryPoint' ) );
		$this->assertNull( $farm->getState( 'nonexistant' ) );

		$farmConfig = [
			'server' => '(?P<wiki>[a-z])\.testfarm-multiversion\.example\.org',
			'variables' => [
				[ 'variable' => 'wiki', ],
			],
			'suffix' => 'testfarm',
			'wikiID' => '$wikitestfarm',
			'versions' => 'versions.php',
			'coreconfig' => [
				'farms.php',
			],
			'config' => [
				[ 'file' => 'settings.php',
				       'key' => 'default',
				],
				[ 'file' => 'localsettings.php',
				       'key' => '*testfarm',
				       'default' => 'testfarm',
				],
				[ 'file' => 'globalsettings.php',
				       'key' => '*',
				],
				[ 'file' => 'LocalSettings.php',
				       'executable' => true,
				],
			],
		];

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
				[ 'EntryPoint' => 'index.php' ] );

		$this->assertEquals( 'a.testfarm-monoversion.example.org', $farm->getVariable( '$SERVER' ) );

		$this->assertEquals( 'index.php', $farm->getState( 'EntryPoint' ) );

		$farmConfig = [
			'server' => '(?P<wiki>[a-z])\.testfarm-monoversion\.example\.org',
			'variables' => [
				[ 'variable' => 'wiki',
			               'file' => 'varwiki.php', ],
			],
			'suffix' => 'testfarm',
			'wikiID' => '$wikitestfarm',
			'HTTP404' => 'phpunitHTTP404.php',
			'coreconfig' => [
				'farms.php',
			],
			'config' => [
				[ 'file' => 'settings.php',
				       'key' => 'default',
				],
				'settings.php',
				[ 'file' => 'missingfile.php',
				       'key' => 'default',
				],
				[ 'file' => 'localsettings.php',
				       'key' => '*testfarm',
				       'default' => 'testfarm',
				],
				[ 'file' => 'globalsettings.php',
				       'key' => '*',
				],
				[ 'file' => 'atestfarmsettings.php',
				       'key' => 'atestfarm',
				],
				[ 'file' => 'testfarmsettings.php',
				       'key' => 'testfarm',
				],
				[ 'file' => 'otherfarmsettings.php',
				       'key' => 'otherfarm',
				],
				[ 'file' => 'LocalSettings.php',
				       'executable' => true,
				],
			],
		];
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
				[ 'EntryPoint' => 'index.php' ] );

		$this->assertEquals( 'testfarm-multiversion-subdirectories.example.org/a', $farm->getVariable( '$SERVER' ) );

		$this->assertEquals( 'index.php', $farm->getState( 'EntryPoint' ) );
		$this->assertNull( $farm->getState( 'nonexistant' ) );

		$farmConfig = [
			'server' => 'testfarm-multiversion-subdirectories\.example\.org/(?P<wiki>[a-z])',
			'variables' => [
				[ 'variable' => 'wiki', ],
			],
			'suffix' => 'testfarm',
			'wikiID' => '$wikitestfarm',
			'versions' => 'versions.php',
			'coreconfig' => [
				'farms.php',
			],
			'config' => [
				[ 'file' => 'settings.php',
				       'key' => 'default',
				],
				[ 'file' => 'localsettings.php',
				       'key' => '*testfarm',
				       'default' => 'testfarm',
				],
				[ 'file' => 'globalsettings.php',
				       'key' => '*',
				],
				[ 'file' => 'LocalSettings.php',
				       'executable' => true,
				],
			],
		];

		$this->assertEquals( $farmConfig, $farm->getFarmConfiguration() );
	}

	/**
	 * Test when there is no configuration file farms.yml/json/php.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testFailedConstruction() {

		$this->expectException( MWFConfigurationException::class );
		$this->expectExceptionMessage( 'No configuration file found' );

		$wgMediaWikiFarmConfigDir = dirname( self::$wgMediaWikiFarmConfigDir );

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				[ 'EntryPoint' => 'index.php' ] );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 */
	public function testFailedConstruction2() {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Missing host name in constructor' );

		$farm = new MediaWikiFarm(
				0,
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				[ 'EntryPoint' => 'index.php' ] );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 */
	public function testFailedConstruction3() {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid directory for the farm configuration' );

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				0,
				self::$wgMediaWikiFarmCodeDir,
				false,
				[ 'EntryPoint' => 'index.php' ] );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 */
	public function testFailedConstruction4() {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid directory for the farm configuration' );

		$wgMediaWikiFarmConfigDir = self::$wgMediaWikiFarmConfigDir . '/farms.php';

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				[ 'EntryPoint' => 'index.php' ] );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 */
	public function testFailedConstruction5() {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Code directory must be null or a directory' );

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				0,
				false,
				[ 'EntryPoint' => 'index.php' ] );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 */
	public function testFailedConstruction6() {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Code directory must be null or a directory' );

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir . '/farms.php',
				false,
				[ 'EntryPoint' => 'index.php' ] );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 */
	public function testFailedConstruction7() {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Cache directory must be false or a directory' );

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				0,
				[ 'EntryPoint' => 'index.php' ] );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 */
	public function testFailedConstruction8() {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'State must be an array' );

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
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 */
	public function testFailedConstruction9() {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Undefined host' );

		$this->backupAndUnsetGlobalSubvariable( '_SERVER', 'HTTP_HOST' );
		$this->backupAndUnsetGlobalSubvariable( '_SERVER', 'SERVER_NAME' );

		$farm = new MediaWikiFarm(
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				[ 'EntryPoint' => 'index.php' ] );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 */
	public function testFailedConstruction10() {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Entry point must be a string' );

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				[ 'EntryPoint' => 0 ] );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 */
	public function testFailedConstruction11() {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'InnerMediaWiki state must be a bool' );

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				[ 'InnerMediaWiki' => 0 ] );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 */
	public function testFailedConstruction12() {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Environment must be an array' );

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				[],
				0 );
	}

	/**
	 * Test bad arguments in constructor.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 */
	public function testFailedConstruction13() {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'ExtensionRegistry parameter must be a bool' );

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				[],
				[ 'ExtensionRegistry' => 'true' ] );
	}

	/**
	 * Test successful construction with global variable for the host.
	 *
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
				[ 'EntryPoint' => 'index.php' ] );

		$this->assertEquals( 'a.testfarm-multiversion.example.org', $farm->getVariable( '$SERVER' ) );
	}

	/**
	 * Test successful construction with global variable for the host.
	 *
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
				[ 'EntryPoint' => 'index.php' ] );

		$this->assertEquals( 'a.testfarm-multiversion.example.org', $farm->getVariable( '$SERVER' ) );
	}

	/**
	 * Test successful construction with global variable for the path.
	 *
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
				[ 'EntryPoint' => 'index.php' ] );

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
				[ 'EntryPoint' => 'index.php' ] );

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
				[ 'EntryPoint' => 'index.php' ] );

		$this->assertEquals( 'a.testfarm-multiversion.example.org', $farm->getVariable( '$SERVER' ) );
	}

	/**
	 * Test when the path is written in the server name.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testFailedPath() {

		$this->expectException( MWFConfigurationException::class );
		$this->expectExceptionMessage( 'No farm corresponding to this host' );

		$farm = new MediaWikiFarm(
				'testfarm-multiversion-subdirectories.example.org/a',
				'',
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				[ 'EntryPoint' => 'index.php' ] );
	}

	/**
	 * Test when the path is written in the server name.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testFailedPath2() {

		$this->expectException( MWFConfigurationException::class );
		$this->expectExceptionMessage( 'No farm corresponding to this host' );

		$farm = new MediaWikiFarm(
				'testfarm-multiversion-subdirectories.example.or',
				'g/a',
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				[ 'EntryPoint' => 'index.php' ] );
	}

	/**
	 * Test when the path is written in the server name.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testFailedPath3() {

		$this->expectException( MWFConfigurationException::class );
		$this->expectExceptionMessage( 'No farm corresponding to this host' );

		$farm = new MediaWikiFarm(
				'testfarm-multiversion-subdirectories.example.org',
				'/A',
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				[ 'EntryPoint' => 'index.php' ] );
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
				[ 'EntryPoint' => 'index.php' ] );

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
				[ 'EntryPoint' => 'index.php' ] );

		# Check farm directory
		$this->assertEquals( self::$wgMediaWikiFarmFarmDir, $farm->getFarmDir() );

		# Check code directory
		$this->assertEquals( self::$wgMediaWikiFarmCodeDir, $farm->getCodeDir() );

		# Check cache directory
		$this->assertFalse( $farm->getCacheDir() );

		# Check executable file [farm]/src/main.php
		$this->assertEquals( self::$wgMediaWikiFarmFarmDir . '/src/main.php', $farm->getConfigFile() );

		$this->assertEquals(
			[
				'settings' => [],
				'arrays' => [],
				'extensions' => [],
				'execFiles' => [],
				'composer' => [],
			],
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
				[ 'EntryPoint' => 'index.php' ] );

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
				[ 'EntryPoint' => 'index.php' ] );

		$this->assertEquals( 'a.testfarm-multiversion.example.org', $farm->getVariable( '$SERVER' ) );
	}

	/**
	 * Test an infinite redirect.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testInfiniteRedirect() {

		$this->expectException( MWFConfigurationException::class );
		$this->expectExceptionMessage( 'Infinite or too long redirect detected' );

		$farm = new MediaWikiFarm(
				'a.testfarm-infinite-redirect.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				[ 'EntryPoint' => 'index.php' ] );
	}

	/**
	 * Test a missing farm.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testMissingFarm() {

		$this->expectException( MWFConfigurationException::class );
		$this->expectExceptionMessage( 'No farm corresponding to this host' );

		$farm = new MediaWikiFarm(
				'a.testfarm-missing.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				[ 'EntryPoint' => 'index.php' ] );
	}

	/**
	 * Test the 'loading' function with existant wiki.
	 *
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
	 */
	public function testYAMLConfigFile() {

		$this->expectException( MWFConfigurationException::class );
		$this->expectExceptionMessage( 'Infinite or too long redirect detected' );

		$farm = new MediaWikiFarm(
				'a.testfarm-infinite-redirect.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir . '/yaml',
				self::$wgMediaWikiFarmCodeDir,
				false,
				[ 'EntryPoint' => 'index.php' ] );
	}

	/**
	 * Load a JSON config file.
	 *
	 * @covers MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testJSONConfigFile() {

		$this->expectException( MWFConfigurationException::class );
		$this->expectExceptionMessage( 'Infinite or too long redirect detected' );

		$farm = new MediaWikiFarm(
				'a.testfarm-infinite-redirect.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir . '/json',
				self::$wgMediaWikiFarmCodeDir,
				false,
				[ 'EntryPoint' => 'index.php' ] );
	}
}
