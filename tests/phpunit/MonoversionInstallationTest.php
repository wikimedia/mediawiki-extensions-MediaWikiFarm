<?php

/**
 * @group MediaWikiFarm
 * @covers MediaWikiFarm
 */
class MonoversionInstallationTest extends MediaWikiTestCase {
	
	/** @var string Configuration directory for tests. */
	static $wgMediaWikiFarmConfigDir = '';

	/** @var string Cache directory for tests. */
	static $wgMediaWikiFarmCacheDir = '';

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
		
		$farm = new MediaWikiFarm( $host, self::$wgMediaWikiFarmConfigDir, null, false, 'index.php' );
		
		return $farm;
	}
	
	/**
	 * Set up versions files with the current MediaWiki installation.
	 */
	static function setUpBeforeClass() {

		global $IP;

		$dirIP = basename( $IP );

		# Set test configuration parameters
		self::$wgMediaWikiFarmConfigDir = dirname( __FILE__ ) . '/data/config';
		self::$wgMediaWikiFarmCacheDir = dirname( __FILE__ ) . '/data/cache';

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
				'$HTTP404' => 'phpunitHTTP404.php',
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
		
		unlink( self::$wgMediaWikiFarmConfigDir . '/varwikiversions.php' );
	}
}
