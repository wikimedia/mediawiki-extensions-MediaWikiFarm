<?php

require_once 'MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/MediaWikiFarm.php';

/**
 * @group MediaWikiFarm
 */
class ConstructionTest extends MediaWikiFarmTestCase {

	/**
	 * Test a successful initialisation of multiversion MediaWikiFarm with a correct configuration file farms.php.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getParameter
	 * @covers MediaWikiFarm::getFarmConfiguration
	 * @covers MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::readFile
	 */
	function testSuccessfulConstructionMultiversion() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );

		$this->assertEquals( 'a.testfarm-multiversion.example.org', $farm->getVariable( '$SERVER' ) );

		$this->assertEquals( 'index.php', $farm->getParameter( 'EntryPoint' ) );
		$this->assertNull( $farm->getParameter( 'entryPoint' ) );

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
	 * @covers MediaWikiFarm::getParameter
	 * @covers MediaWikiFarm::getFarmConfiguration
	 * @covers MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::readFile
	 */
	function testSuccessfulConstructionMonoversion() {

		$farm = new MediaWikiFarm(
				'a.testfarm-monoversion.example.org',
				self::$wgMediaWikiFarmConfigDir,
				null,
				false,
				array( 'EntryPoint' => 'index.php' ) );

		$this->assertEquals( 'a.testfarm-monoversion.example.org', $farm->getVariable( '$SERVER' ) );

		$this->assertEquals( 'index.php', $farm->getParameter( 'EntryPoint' ) );

		$farmConfig = array(
			'server' => '(?P<wiki>[a-z])\.testfarm-monoversion\.example\.org',
			'variables' => array(
				array( 'variable' => 'wiki', ),
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
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage No configuration file found
	 */
	function testFailedConstruction() {

		$wgMediaWikiFarmConfigDir = dirname( self::$wgMediaWikiFarmConfigDir );

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
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
	function testFailedConstruction2() {

		$farm = new MediaWikiFarm(
				0,
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
	function testFailedConstruction3() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
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
	function testFailedConstruction4() {

		$wgMediaWikiFarmConfigDir = self::$wgMediaWikiFarmConfigDir . '/farms.php';

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
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
	function testFailedConstruction5() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
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
	function testFailedConstruction6() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
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
	function testFailedConstruction7() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
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
	 * @expectedExceptionMessage Parameters must be an array
	 */
	function testFailedConstruction8() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
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
	function testFailedConstruction9() {

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
	 * @expectedExceptionMessage ExtensionRegistry parameter must be a bool
	 */
	function testFailedConstruction10() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'ExtensionRegistry' => 'true' ) );
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
	function testFailedConstruction11() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 0 ) );
	}

	/**
	 * Test successful construction with global variable.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::readFile
	 */
	function testSuccessfulConstructionWithGlobalVariable() {

		$this->backupAndSetGlobalSubvariable( '_SERVER', 'HTTP_HOST', 'a.testfarm-multiversion.example.org' );
		$this->backupAndUnsetGlobalSubvariable( '_SERVER', 'SERVER_NAME' );

		$farm = new MediaWikiFarm(
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );

		$this->assertEquals( 'a.testfarm-multiversion.example.org', $farm->getVariable( '$SERVER' ) );
	}

	/**
	 * Test successful construction with global variable.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::readFile
	 */
	function testSuccessfulConstructionWithGlobalVariable2() {

		$this->backupAndUnsetGlobalSubvariable( '_SERVER', 'HTTP_HOST' );
		$this->backupAndSetGlobalSubvariable( '_SERVER', 'SERVER_NAME', 'a.testfarm-multiversion.example.org' );

		$farm = new MediaWikiFarm(
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );

		$this->assertEquals( 'a.testfarm-multiversion.example.org', $farm->getVariable( '$SERVER' ) );
	}

	/**
	 * Test creation of cache directory.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getCacheDir
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::cacheFile
	 * @uses AbstractMediaWikiFarmScript::rmdirr
	 */
	function testCacheDirectoryCreation() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				self::$wgMediaWikiFarmCacheDir,
				array( 'EntryPoint' => 'index.php' ) );

		$this->assertEquals( self::$wgMediaWikiFarmCacheDir . '/testfarm-multiversion', $farm->getCacheDir() );
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCacheDir ) );
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCacheDir . '/testfarm-multiversion' ) );
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
	 */
	function testCheckBasicObjectPropertiesMultiversion() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				array( 'EntryPoint' => 'index.php' ) );

		/** Check code directory. */
		$this->assertEquals( self::$wgMediaWikiFarmCodeDir, $farm->getCodeDir() );

		/** Check cache directory. */
		$this->assertFalse( $farm->getCacheDir() );

		/** Check executable file [farm]/src/main.php. */
		$this->assertEquals( dirname( dirname( dirname( __FILE__ ) ) ) . '/src/main.php', $farm->getConfigFile() );
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
	 */
	function testCheckBasicObjectPropertiesMonoversion() {

		$farm = new MediaWikiFarm(
				'a.testfarm-monoversion.example.org',
				self::$wgMediaWikiFarmConfigDir,
				null,
				false,
				array( 'EntryPoint' => 'index.php' ) );

		/** Check code directory. */
		$this->assertNull( $farm->getCodeDir() );

		/** Check cache directory. */
		$this->assertFalse( $farm->getCacheDir() );

		/** Check executable file [farm]/src/main.php. */
		$this->assertEquals( dirname( dirname( dirname( __FILE__ ) ) ) . '/src/main.php', $farm->getConfigFile() );
	}

	/**
	 * Test a normal redirect.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::readFile
	 */
	function testNormalRedirect() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion-redirect.example.org',
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
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Infinite or too long redirect detected
	 */
	function testInfiniteRedirect() {

		$farm = new MediaWikiFarm(
				'a.testfarm-infinite-redirect.example.org',
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
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage No farm corresponding to this host
	 */
	function testMissingFarm() {

		$farm = new MediaWikiFarm(
				'a.testfarm-missing.example.org',
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
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarm::isMediaWiki
	 * @uses MediaWikiFarm::getConfigFile
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 */
	function testLoadingCorrect() {

		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarm', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', self::$wgMediaWikiFarmCodeDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalSubvariable( '_SERVER', 'HTTP_HOST', 'a.testfarm-multiversion.example.org' );
		chdir( self::$wgMediaWikiFarmCodeDir );

		$code = MediaWikiFarm::load( 'index.php' );

		$this->assertEquals( 200, $code );
		$this->assertEquals( 'a.testfarm-multiversion.example.org', $GLOBALS['wgMediaWikiFarm']->getVariable( '$SERVER' ) );
		$this->assertEquals( self::$wgMediaWikiFarmCodeDir . '/vstub', getcwd() );
		$this->assertEquals( $GLOBALS['wgMediaWikiFarmCodeDir'] . '/' . $GLOBALS['wgMediaWikiFarm']->getVariable( '$VERSION' ), getcwd() );
	}

	/**
	 * Test the 'loading' function with non-existant wiki.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 */
	function testLoadingNonExistant() {

		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarm', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalSubvariable( '_SERVER', 'HTTP_HOST', null );
		$this->backupAndSetGlobalSubvariable( '_SERVER', 'SERVER_NAME', 'c.testfarm-monoversion-with-file-variable-without-version.example.org' );
		$this->backupAndUnsetGlobalVariable( 'wgMediaWikiFarmHTTP404Executed' );

		$code = MediaWikiFarm::load( 'index.php' );

		$this->assertEquals( 404, $code );
		$this->assertTrue( $GLOBALS['wgMediaWikiFarmHTTP404Executed'] );
	}

	/**
	 * Test the 'loading' function with farm error.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 */
	function testLoadingError() {

		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarm', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalSubvariable( '_SERVER', 'HTTP_HOST', 'a.testfarm-missing.example.org' );

		$code = MediaWikiFarm::load( 'index.php' );

		$this->assertEquals( 500, $code );
	}
}
