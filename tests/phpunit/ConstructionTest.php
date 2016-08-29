<?php

/**
 * @group MediaWikiFarm
 */
class ConstructionTest extends MediaWikiTestCase {

	/** @var string Configuration directory for tests. */
	static $wgMediaWikiFarmConfigDir = '';

	/** @var string Code directory for tests. */
	static $wgMediaWikiFarmCodeDir = '';

	/** @var string Cache directory for tests. */
	static $wgMediaWikiFarmCacheDir = '';

	/**
	 * Set up versions files with the current MediaWiki installation.
	 */
	static function setUpBeforeClass() {

		global $IP;

		$dirIP = basename( $IP );

		# Set test configuration parameters
		self::$wgMediaWikiFarmConfigDir = dirname( __FILE__ ) . '/data/config';
		self::$wgMediaWikiFarmCodeDir = dirname( $IP );
		self::$wgMediaWikiFarmCacheDir = dirname( __FILE__ ) . '/data/cache';

		# Create versions.php: the list of existing values for variable '$WIKIID' with their associated versions
		$versionsFile = <<<PHP
<?php

return array(
	'atestfarm' => '$dirIP',
);

PHP;
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/versions.php', $versionsFile );

		# Create varwikiversions.php: the list of existing values for variable '$wiki' with their associated versions
		$versionsFile = <<<PHP
<?php

return array(
	'a' => '$dirIP',
);

PHP;
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/varwikiversions.php', $versionsFile );

		# Move http404.php to [farm]/www
		copy( self::$wgMediaWikiFarmConfigDir . '/http404.php', 'phpunitHTTP404.php' );
	}

	/**
	 * Test a successful initialisation of multiversion MediaWikiFarm with a correct configuration file farms.php.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getEntryPoint
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
				'index.php' );

		$this->assertEquals( 'a.testfarm-multiversion.example.org', $farm->getVariable( '$SERVER' ) );

		$this->assertEquals( 'index.php', $farm->getEntryPoint( 'index.php' ) );

		$farmConfig = array(
			'server' => '(?P<wiki>[a-z])\.testfarm-multiversion\.example\.org',
			'variables' => array(
				array( 'variable' => 'wiki', ),
			),
			'suffix' => 'testfarm',
			'wikiID' => '$wikitestfarm',
			'versions' => 'versions.php',
			'config' => array(
				array( 'file' => 'settings.php',
				       'key' => 'default',
				),
				array( 'file' => 'LocalSettings.php',
				       'exec' => true,
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
	 * @covers MediaWikiFarm::getEntryPoint
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
				'index.php' );

		$this->assertEquals( 'a.testfarm-monoversion.example.org', $farm->getVariable( '$SERVER' ) );

		$this->assertEquals( 'index.php', $farm->getEntryPoint( 'index.php' ) );

		$farmConfig = array(
			'server' => '(?P<wiki>[a-z])\.testfarm-monoversion\.example\.org',
			'variables' => array(
				array( 'variable' => 'wiki', ),
			),
			'suffix' => 'testfarm',
			'wikiID' => '$wikitestfarm',
			'HTTP404' => 'phpunitHTTP404.php',
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
				       'exec' => true,
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
				'index.php' );
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
				'index.php' );
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
				'index.php' );
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
				'index.php' );
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
				'index.php' );
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
				'index.php' );
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
				'index.php' );
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

		$_SERVER['HTTP_HOST'] = null;
		$_SERVER['SERVER_NAME'] = null;

		$farm = new MediaWikiFarm(
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				'index.php' );
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

		$_SERVER['HTTP_HOST'] = 'a.testfarm-multiversion.example.org';
		$_SERVER['SERVER_NAME'] = null;

		$farm = new MediaWikiFarm(
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				'index.php' );

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

		$_SERVER['HTTP_HOST'] = null;
		$_SERVER['SERVER_NAME'] = 'a.testfarm-multiversion.example.org';

		$farm = new MediaWikiFarm(
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				'index.php' );

		$this->assertEquals( 'a.testfarm-multiversion.example.org', $farm->getVariable( '$SERVER' ) );
	}

	/**
	 * Test creation of cache directory.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getCacheDir
	 * @uses MediaWikiFarm::readFile
	 */
	function testCacheDirectoryCreation() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				self::$wgMediaWikiFarmCacheDir,
				'index.php' );

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
	 * @uses MediaWikiFarm::readFile
	 */
	function testCheckBasicObjectPropertiesMultiversion() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				'index.php' );

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
	 * @uses MediaWikiFarm::readFile
	 */
	function testCheckBasicObjectPropertiesMonoversion() {

		$farm = new MediaWikiFarm(
				'a.testfarm-monoversion.example.org',
				self::$wgMediaWikiFarmConfigDir,
				null,
				false,
				'index.php' );

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
				'index.php' );

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
				'index.php' );
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
				'index.php' );
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
	 */
	function testLoadingCorrect() {

		global $wgMediaWikiFarm, $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir, $wgMediaWikiFarmCacheDir;

		$wgMediaWikiFarm = null;
		$wgMediaWikiFarmConfigDir = self::$wgMediaWikiFarmConfigDir;
		$wgMediaWikiFarmCodeDir = self::$wgMediaWikiFarmCodeDir;
		$wgMediaWikiFarmCacheDir = false;
		$_SERVER['HTTP_HOST'] = 'a.testfarm-multiversion.example.org';
		$curdir = getcwd();

		chdir( dirname( $curdir ) );

		$code = MediaWikiFarm::load( 'index.php' );

		$this->assertEquals( 200, $code );
		$this->assertEquals( 'a.testfarm-multiversion.example.org', $wgMediaWikiFarm->getVariable( '$SERVER' ) );
		$this->assertEquals( $curdir, getcwd() );
		$this->assertEquals( $wgMediaWikiFarmCodeDir . '/' . $wgMediaWikiFarm->getVariable( '$VERSION' ), getcwd() );

		chdir( $curdir );
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

		global $wgMediaWikiFarm, $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir, $wgMediaWikiFarmCacheDir, $wgMediaWikiFarmHTTP404Executed;

		$wgMediaWikiFarm = null;
		$wgMediaWikiFarmConfigDir = self::$wgMediaWikiFarmConfigDir;
		$wgMediaWikiFarmCodeDir = null;
		$wgMediaWikiFarmCacheDir = false;
		$_SERVER['HTTP_HOST'] = null;
		$_SERVER['SERVER_NAME'] = 'c.testfarm-monoversion-with-file-variable-without-version.example.org';

		$code = MediaWikiFarm::load( 'index.php' );

		$this->assertEquals( 404, $code );

		$this->assertTrue( $wgMediaWikiFarmHTTP404Executed );
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

		global $wgMediaWikiFarm, $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir, $wgMediaWikiFarmCacheDir;

		$wgMediaWikiFarm = null;
		$wgMediaWikiFarmConfigDir = self::$wgMediaWikiFarmConfigDir;
		$wgMediaWikiFarmCodeDir = null;
		$wgMediaWikiFarmCacheDir = false;
		$_SERVER['HTTP_HOST'] = 'a.testfarm-missing.example.org';

		$code = MediaWikiFarm::load( 'index.php' );

		$this->assertEquals( 500, $code );
	}

	/**
	 * Remove cache directory.
	 */
	protected function tearDown() {

		wfRecursiveRemoveDir( self::$wgMediaWikiFarmCacheDir );

		parent::tearDown();
	}

	/**
	 * Remove 'data/config/versions.php' config file.
	 */
	static function tearDownAfterClass() {

		unlink( self::$wgMediaWikiFarmConfigDir . '/versions.php' );
		unlink( self::$wgMediaWikiFarmConfigDir . '/varwikiversions.php' );
		unlink( dirname( dirname( dirname( __FILE__ ) ) ) . '/www/phpunitHTTP404.php' );
	}
}
