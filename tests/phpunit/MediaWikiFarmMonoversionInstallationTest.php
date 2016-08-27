<?php

/**
 * @group MediaWikiFarm
 * @covers MediaWikiFarm
 */
class MediaWikiFarmMonoversionInstallationTest extends MediaWikiTestCase {
	
	/** @var MediaWikiFarm|null Test object. */
	protected $farm = null;
	
	/**
	 * Construct a default MediaWikiFarm object with a sample correct configuration file.
	 *
	 * Use the current MediaWiki installation to simulate a monoversion installation.
	 *
	 * @param string $host Host name.
	 * @return MediaWikiFarm
	 */
	static function constructMediaWikiFarm( $host ) {
		
		$wgMediaWikiFarmConfigDirTest = dirname( __FILE__ ) . '/data/config';
		$farm = new MediaWikiFarm( $host, $wgMediaWikiFarmConfigDirTest, null, false, 'index.php' );
		
		return $farm;
	}
	
	/**
	 * Set up versions files with the current MediaWiki installation.
	 */
	static function setUpBeforeClass() {

		global $IP;

		$dirIP = basename( $IP );

		# Create varwikiversions.php: the list of existing values for variable '$wiki' with their associated versions
		$versionsFile = <<<PHP
<?php

return array(
	'a' => '$dirIP',
);

PHP;
		file_put_contents( dirname( __FILE__ ) . '/data/config/varwikiversions.php', $versionsFile );
	}
	
	/**
	 * Set up the default MediaWikiFarm object with a sample correct configuration file.
	 */
	protected function setUp() {
		
		parent::setUp();
		
		$this->farm = self::constructMediaWikiFarm( 'a.testfarm-monoversion.example.org' );
	}
	
	/**
	 * Test a successful initialisation of MediaWikiFarm with a correct configuration file farms.php.
	 *
	 * @covers MediaWikiFarm::__construct
	 * @covers MediaWikiFarm::selectFarm
	 * @covers MediaWikiFarm::getEntryPoint
	 * @covers MediaWikiFarm::getFarmConfiguration
	 */
	function testSuccessfulConstruction() {
		
		$this->assertEquals( 'a.testfarm-monoversion.example.org', $this->farm->getVariable( '$SERVER' ) );

		$this->assertEquals( 'index.php', $this->farm->getEntryPoint( 'index.php' ) );

		$farmConfig = array(
			'server' => '(?P<wiki>[a-z])\.testfarm-monoversion\.example\.org',
			'variables' => array(
				array( 'variable' => 'wiki', ),
			),
			'suffix' => 'testfarm',
			'wikiID' => '$wikitestfarm',
			'config' => array(
				array( 'file' => 'settings.php',
				       'key' => 'default',
				),
				array( 'file' => 'localsettings.php',
				       'key' => '*testfarm',
				       'default' => 'testfarm',
				),
				array( 'file' => 'LocalSettings.php',
				       'exec' => true,
				),
			),
		);
		$this->assertEquals( $farmConfig, $this->farm->getFarmConfiguration() );
	}
	
	/**
	 * Test when there is no configuration file farms.yml/json/php.
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage No configuration file found
	 */
	function testFailedConstruction() {
		
		$wgMediaWikiFarmConfigDirBadTest = dirname( __FILE__ ) . '/data';
		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', $wgMediaWikiFarmConfigDirBadTest, null, false );
	}
	
	/**
	 * Test bad arguments in constructor.
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Missing host name in constructor
	 */
	function testFailedConstruction2() {
		
		$wgMediaWikiFarmConfigDirBadTest = dirname( __FILE__ ) . '/data/config';
		$farm = new MediaWikiFarm( 0, $wgMediaWikiFarmConfigDirBadTest );
	}
	
	/**
	 * Test bad arguments in constructor.
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Invalid directory for the farm configuration
	 */
	function testFailedConstruction3() {
		
		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', 0 );
	}
	
	/**
	 * Test bad arguments in constructor.
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Invalid directory for the farm configuration
	 */
	function testFailedConstruction4() {
		
		$wgMediaWikiFarmConfigDirBadTest = dirname( __FILE__ ) . '/data/config/farms.php';
		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', $wgMediaWikiFarmConfigDirBadTest );
	}
	
	/**
	 * Test bad arguments in constructor.
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Code directory must be null or a directory
	 */
	function testFailedConstruction5() {
		
		$wgMediaWikiFarmConfigDirTest = dirname( __FILE__ ) . '/data/config';
		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', $wgMediaWikiFarmConfigDirTest, 0 );
	}
	
	/**
	 * Test bad arguments in constructor.
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Code directory must be null or a directory
	 */
	function testFailedConstruction6() {
		
		$wgMediaWikiFarmConfigDirTest = dirname( __FILE__ ) . '/data/config';
		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', $wgMediaWikiFarmConfigDirTest, $wgMediaWikiFarmConfigDirTest . '/farms.php' );
	}
	
	/**
	 * Test bad arguments in constructor.
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Cache directory must be false, null, or a directory
	 */
	function testFailedConstruction7() {
		
		global $IP;
		
		$wgMediaWikiFarmConfigDirTest = dirname( __FILE__ ) . '/data/config';
		$wgMediaWikiFarmCodeDirTest = dirname( $IP );
		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', $wgMediaWikiFarmConfigDirTest, $wgMediaWikiFarmCodeDirTest, 0 );
	}
	
	/**
	 * Test bad arguments in constructor.
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Entry point must be a string
	 */
	function testFailedConstruction8() {
		
		global $IP;
		
		$wgMediaWikiFarmConfigDirTest = dirname( __FILE__ ) . '/data/config';
		$wgMediaWikiFarmCodeDirTest = dirname( $IP );
		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', $wgMediaWikiFarmConfigDirTest, $wgMediaWikiFarmCodeDirTest, false, 0 );
	}
	
	/**
	 * Test creation of cache directory.
	 */
	function testCacheDirectoryCreation() {
		
		global $IP;
		
		$wgMediaWikiFarmConfigDirTest = dirname( __FILE__ ) . '/data/config';
		$wgMediaWikiFarmCodeDirTest = dirname( $IP );
		$wgMediaWikiFarmCacheDirTest = dirname( __FILE__ ) . '/data/cache';
		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', $wgMediaWikiFarmConfigDirTest, $wgMediaWikiFarmCodeDirTest, $wgMediaWikiFarmCacheDirTest );
		
		$this->assertEquals( $wgMediaWikiFarmCacheDirTest . '/testfarm-monoversion', $farm->getCacheDir() );
		$this->assertTrue( is_dir( $wgMediaWikiFarmCacheDirTest ) );
		$this->assertTrue( is_dir( $wgMediaWikiFarmCacheDirTest . '/testfarm-monoversion' ) );
	}
	
	/**
	 * Test the basic object properties (code, cache, and farm directories).
	 */
	function testCheckBasicObjectProperties() {
		
		global $IP;
		
		/** Check code directory. */
		$this->assertNull( $this->farm->getCodeDir() );
		
		/** Check cache directory. */
		$this->assertFalse( $this->farm->getCacheDir() );
		
		/** Check executable file [farm]/src/main.php. */
		$this->assertEquals( dirname( dirname( dirname( __FILE__ ) ) ) . '/src/main.php', $this->farm->getConfigFile() );
	}
	
	/**
	 * Test the variable read for the URL is correct.
	 */
	function testURLVariables() {
		
		$this->assertEquals(
			array(
				'$wiki' => 'a',
				'$SERVER' => 'a.testfarm-monoversion.example.org',
				'$SUFFIX' => '',
				'$WIKIID' => '',
				'$VERSION' => null,
				'$CODE' => '',
			),
			$this->farm->getVariables() );
	}
	
	/**
	 * Test a normal redirect.
	 */
	function testNormalRedirect() {
		
		$farm = self::constructMediaWikiFarm( 'a.testfarm-monoversion-redirect.example.org' );
		$this->assertEquals( 'a.testfarm-monoversion.example.org', $this->farm->getVariable( '$SERVER' ) );
	}
	
	/**
	 * Test an infinite redirect.
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Infinite or too long redirect detected
	 */
	function testInfiniteRedirect() {
		
		$farm = self::constructMediaWikiFarm( 'a.testfarm-infinite-redirect.example.org' );
	}
	
	/**
	 * Test a missing farm.
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage No farm corresponding to this host
	 */
	function testMissingFarm() {
		
		$farm = self::constructMediaWikiFarm( 'a.testfarm-missing.example.org' );
	}

	/**
	 * Test basic variables.
	 */
	function testVariables() {
		
		global $IP;
		
		$this->farm->checkExistence();
		
		/** Check variables. */
		$this->assertEquals(
			array(
				'$wiki' => 'a',
				'$SERVER' => 'a.testfarm-monoversion.example.org',
				'$SUFFIX' => 'testfarm',
				'$WIKIID' => 'atestfarm',
				'$VERSION' => '',
				'$CODE' => $IP,
			),
			$this->farm->getVariables() );
	}

	/**
	 * Test when a farm definition has no suffix.
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Missing key 'suffix' in farm configuration.
	 */
	function testMissingSuffixVariable() {
		
		$farm = self::constructMediaWikiFarm( 'a.testfarm-with-missing-suffix.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test when a farm definition has no wikiID.
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Missing key 'wikiID' in farm configuration.
	 */
	function testMissingWikiIDVariable() {
		
		$farm = self::constructMediaWikiFarm( 'a.testfarm-with-missing-wikiid.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test when a mandatory variable has a bad definition.
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Wrong type (non-string) for key 'suffix' in farm configuration.
	 */
	function testBadTypeMandatoryVariable() {
		
		$farm = self::constructMediaWikiFarm( 'a.testfarm-with-bad-type-mandatory.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test when a non-mandatory variable has a bad definition.
	 */
	function testBadTypeNonMandatoryVariable() {
		
		global $IP;
		
		$farm = self::constructMediaWikiFarm( 'a.testfarm-with-bad-type-nonmandatory.example.org' );
		$farm->checkExistence();
		$this->assertNull( $farm->getVariable( '$DATA' ) );
	}

	/**
	 * Test edge cases when reading config file: missing defined variables, missing versions file.
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Undefined key 'variables' in the farm configuration
	 */
	function testEdgeCasesConfigFile() {	
		
		/** Check a config file without defined variables. */
		$farm = self::constructMediaWikiFarm( 'a.testfarm-novariables.example.org' );
		$farm->checkExistence();
	}
	
	/**
	 * Test a existing host in a farm with a file variable without version defined inside.
	 */
	function testVariableFileWithoutVersion() {
		
		$farm = self::constructMediaWikiFarm( 'a.testfarm-monoversion-with-file-variable-without-version.example.org' );
		$this->assertTrue( $farm->checkExistence() );
	}

	/**
	 * Test a nonexistant host in a farm with a file variable without version defined inside.
	 */
	function testVariableFileWithoutVersionNonexistant() {
		
		$farm = self::constructMediaWikiFarm( 'c.testfarm-monoversion-with-file-variable-without-version.example.org' );
		$this->assertFalse( $farm->checkExistence() );
	}

	/**
	 * Test a existing host in a farm with a file variable with version defined inside.
	 */
	function testVariableFileWithVersion() {
		
		$farm = self::constructMediaWikiFarm( 'a.testfarm-monoversion-with-file-variable-with-version.example.org' );
		$this->assertTrue( $farm->checkExistence() );
	}

	/**
	 * Test a nonexistant host in a farm with a file variable with version defined inside.
	 */
	function testVariableFileWithVersionNonexistant() {
		
		$farm = self::constructMediaWikiFarm( 'b.testfarm-monoversion-with-file-variable-with-version.example.org' );
		$this->assertFalse( $farm->checkExistence() );
	}

	/**
	 * Test an undefined variable, declared in the host regex but not in the list of variables.
	 *
	 * This test is mainly used to add code coverage; the assertion is tested elsewhere.
	 */
	function testUndefinedVariable() {
		
		$farm = self::constructMediaWikiFarm( 'a.testfarm-monoversion-with-undefined-variable.example.org' );
		$this->assertTrue( $farm->checkExistence() );
	}

	/**
	 * Test memoisation of checkExistence()
	 *
	 * This test is mainly used to add code coverage; the assertion is tested elsewhere.
	 */
	function testMemoisationCheckExistence() {
		
		$this->farm->checkExistence();
		$this->assertTrue( $this->farm->checkExistence() );
	}

	/**
	 * Test memoisation of checkHostVariables()
	 *
	 * This test is mainly used to add code coverage; the assertion is tested elsewhere.
	 */
	function testMemoisationCheckHostVariables() {
		
		$this->farm->checkExistence();
		$this->assertTrue( $this->farm->checkHostVariables() );
	}

	/**
	 * Test further properties.
	 *
	function testProperties2() {
		
		global $IP;
		
		$this->farm->checkExistence();
		
		/** Check variables. *
		$this->assertEquals(
			array(
				'$wiki' => 'a',
				'$SUFFIX' => 'testfarm',
				'$WIKIID' => 'atestfarm',
				'$VERSIONS' => 'versions.php',
				'$VERSION' => 'master',
				'$CODE' => $IP,
			),
			$this->farm->getVariables() );
	}*/
	
	/**
	 * Remove 'data/cache' cache directory.
	 */
	protected function tearDown() {
		
		wfRecursiveRemoveDir( dirname( __FILE__ ) . '/data/cache' );
		
		parent::tearDown();
	}
	
	/**
	 * Remove 'data/config/versions.php' config file.
	 */
	static function tearDownAfterClass() {
		
		unlink( dirname( __FILE__ ) . '/data/config/varwikiversions.php' );
	}
}
