<?php

/**
 * @group MediaWikiFarm
 * @covers MediaWikiFarm
 */
class MediaWikiFarmMultiversionInstallationTest extends MediaWikiTestCase {
	
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
		
		global $IP;
		
		$wgMediaWikiFarmConfigDirTest = dirname( __FILE__ ) . '/data/config';
		$wgMediaWikiFarmCodeDirTest = dirname( $IP );
		$farm = new MediaWikiFarm( $host, $wgMediaWikiFarmConfigDirTest, $wgMediaWikiFarmCodeDirTest, false );
		
		return $farm;
	}
	
	/**
	 * Set up fake 'data/config/versions.php' config file.
	 */
	static function setUpBeforeClass() {
		
		global $IP;
		
		$dirIP = basename( $IP );
		$versionsFile = <<<PHP
<?php

return array(
	'atestfarm' => '$dirIP',
);

PHP;
		file_put_contents( dirname( __FILE__ ) . '/data/config/versions.php', $versionsFile );
	}
	
	/**
	 * Set up the default MediaWikiFarm object with a sample correct configuration file.
	 */
	protected function setUp() {
		
		parent::setUp();
		
		$this->farm = self::constructMediaWikiFarm( 'a.testfarm-multiversion.example.org' );
	}
	
	/**
	 * Test a successful initialisation of MediaWikiFarm with a correct configuration file farms.php.
	 */
	function testSuccessfulConstruction() {
		
		$this->assertEquals( 'a.testfarm-multiversion.example.org', $this->farm->getVariable( '$SERVER' ) );
	}
	
	/**
	 * Test when there is no configuration file farms.yml/json/php.
	 * 
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage No configuration file found
	 */
	function testFailedConstruction() {
		
		$wgMediaWikiFarmConfigDirBadTest = dirname( __FILE__ ) . '/data';
		$farm = new MediaWikiFarm( 'a.testfarm-multiversion.example.org', $wgMediaWikiFarmConfigDirBadTest );
	}
	
	/**
	 * Test the basic object properties (code, cache, and farm directories).
	 */
	function testCheckBasicObjectProperties() {
		
		global $IP;
		
		/** Check code directory. */
		$this->assertEquals( dirname( $IP ), $this->farm->getCodeDir() );
		
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
				'$SERVER' => 'a.testfarm-multiversion.example.org',
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
		
		$farm = self::constructMediaWikiFarm( 'a.testfarm-multiversion-redirect.example.org' );
		$this->assertEquals( 'a.testfarm-multiversion.example.org', $this->farm->getVariable( '$SERVER' ) );
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
	 * Test further properties.
	 */
	function testProperties() {
		
		global $IP;
		
		$this->farm->checkExistence();
		
		/** Check variables. */
		$this->assertEquals(
			array(
				'$wiki' => 'a',
				'$SERVER' => 'a.testfarm-multiversion.example.org',
				'$SUFFIX' => 'testfarm',
				'$WIKIID' => 'atestfarm',
				'$VERSIONS' => 'versions.php',
				'$VERSION' => basename( $IP ),
				'$CODE' => $IP,
			),
			$this->farm->getVariables() );
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
	}
	
	/**
	 * Remove fake 'data/config/versions.php' config file.
	 */
	static function tearDownAfterClass() {
		
		unlink( dirname( __FILE__ ) . '/data/config/versions.php' );
	}
}
