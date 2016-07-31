<?php

/**
 * @group MediaWikiFarm
 * @covers MediaWikiFarm
 */
class MediaWikiFarmTest extends MediaWikiTestCase {
	
	/** @var MediaWikiFarm|null Test object. */
	protected $farm = null;
	
	/**
	 * Set up the default MediaWikiFarm object with a sample correct configuration file.
	 */
	protected function setUp() {
		
		parent::setUp();
		
		$wgMediaWikiFarmTestConfigDir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'config';
		$this->farm = new MediaWikiFarm( 'a.testfarm.example.org', $wgMediaWikiFarmTestConfigDir );
	}
	
	/**
	 * Test when there is no configuration file farms.yml/json/php.
	 */
	public function testFailedConstruction() {
		
		$wgMediaWikiFarmBadConfigDir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'data';
		$farm = new MediaWikiFarm( 'a.testfarm.example.org', $wgMediaWikiFarmBadConfigDir );
		$this->assertTrue( $farm->unusable );
	}
	
	/**
	 * Test a successful initialisation of MediaWikiFarm with a correct configuration file farms.php.
	 */
	public function testSuccessfulConstruction() {
		
		$this->assertFalse( $this->farm->unusable );
	}
	
	/**
	 * Test the path of the executable file src/main.php is correct.
	 */
	public function testFarmLocalSettingsFile() {
		
		$this->assertEquals( $this->farm->getConfigFile(), dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'main.php' );
	}
	
	/**
	 * Test the variable read for the URL is correct.
	 */
	public function testVariables() {
		
		$this->assertEquals( $this->farm->variables, array( 'wiki' => 'a' ) );
	}
}
