<?php

/**
 * @group MediaWikiFarm
 * @covers MediaWikiFarm
 */
class MediaWikiFarmTest extends MediaWikiTestCase {
	
	/**
	 * Set up the default MediaWikiFarm object with a sample correct configuration file.
	 */
	public function setUp() {
		
		parent::setUp();
		
		$this->setMwGlobals( 'wgMediaWikiFarmConfigDir', dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'config' );
		MediaWikiFarm::getInstance( 'a.testfarm.example.org' );
	}
	
	/**
	 * Test when there is no configuration file farms.yml/json/php.
	 */
	public function testFailedConstruction() {
		
		$wgMediaWikiFarmBadConfigDir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'data';
		$wgMediaWikiFarm = new MediaWikiFarm( 'a.testfarm.example.org', $wgMediaWikiFarmBadConfigDir );
		$this->assertTrue( $wgMediaWikiFarm->unusable );
	}
	
	/**
	 * Test a successful initialisation of MediaWikiFarm with a correct configuration file farms.php.
	 */
	public function testSuccessfulConstruction() {
		
		$this->assertFalse( MediaWikiFarm::getInstance()->unusable );
	}
	
	/**
	 * Test the path of the executable file src/main.php is correct.
	 */
	public function testFarmLocalSettingsFile() {
		
		$this->assertEquals( MediaWikiFarm::getInstance()->getConfigFile(), dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'main.php' );
	}
	
	/**
	 * Test the variable read for the URL is correct.
	 */
	public function testVariables() {
		
		$this->assertEquals( MediaWikiFarm::getInstance()->variables, array( 'wiki' => 'a' ) );
	}
}
