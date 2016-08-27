<?php

/**
 * Installation-independant methods tests.
 *
 * These tests operate on constant methods, i.e. which do not modify the internal state of the
 * object. This constantness is tested as a post-condition for all tests.
 *
 * @group MediaWikiFarm
 */
class InstallationIndependantTest extends MediaWikiTestCase {

	/** @var MediaWikiFarm|null Test object. */
	protected $farm = null;

	/** @var MediaWikiFarm|null Control object, must never be modified by tests, should always be identical to $farm after the tests. */
	private $control = null;

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
		
		if( is_null( $this->farm ) ) {
			$this->farm = self::constructMediaWikiFarm( 'a.testfarm-monoversion.example.org', null, false );
		}
		$this->control = clone $this->farm;
	}

	/**
	 * Test a successful reading of a YAML file.
	 *
	 * @requires Symfony\Component\Yaml\Yaml::parse
	 * @covers MediaWikiFarm::readFile
	 * @covers ::MediaWikiFarm_readYAML
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::cacheFile
	 */
	function testSuccessfulReadingYAML() {

		if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) ) {
			$this->markTestSkipped(
				'The optional YAML library was not installed.'
			);
		}

		$result = $this->farm->readFile( 'testreading.yml', dirname( __FILE__ ) . '/data/config' );
		$this->assertEquals(
			array(
				'element1',
				array( 'element2' => 'element3' ),
			),
			$result );
	}

	/**
	 * Test a successful reading of a PHP file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 */
	function testSuccessfulReadingPHP() {

		$result = $this->farm->readFile( 'testreading.php', dirname( __FILE__ ) . '/data/config' );
		$this->assertEquals(
			array(
				'element1',
				array( 'element2' => 'element3' ),
			),
			$result );
	}

	/**
	 * Test a successful reading of a JSON file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::cacheFile
	 */
	function testSuccessfulReadingJSON() {

		$result = $this->farm->readFile( 'testreading.json', dirname( __FILE__ ) . '/data/config' );
		$this->assertEquals(
			array(
				'element1',
				array( 'element2' => 'element3' ),
			),
			$result );
	}

	/**
	 * Test a successful reading of a SER file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::cacheFile
	 */
	function testSuccessfulReadingSER() {

		$result = $this->farm->readFile( 'testreading.ser', dirname( __FILE__ ) . '/data/config' );
		$this->assertEquals(
			array(
				'element1',
				array( 'element2' => 'element3' ),
			),
			$result );
	}

	/**
	 * Test a successful reading of a .dblist file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::cacheFile
	 */
	function testSuccessfulReadingDblist() {

		$result = $this->farm->readFile( 'testreading.dblist', dirname( __FILE__ ) . '/data/config' );
		$this->assertEquals(
			array(
				'element1',
				'element2',
			),
			$result );
	}

	/**
	 * Test reading a missing file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 */
	function testReadMissingFile() {

		$result = $this->farm->readFile( 'missingfile.yml', dirname( __FILE__ ) . '/data/config' );
		$this->assertFalse( $result );
	}

	/**
	 * Test an unrecognised format in readFile.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 */
	function testUnrecognisedFormatReadFile() {

		$result = $this->farm->readFile( 'wrongformat.txt', dirname( __FILE__ ) . '/data/config' );
		$this->assertFalse( $result );
	}

	/**
	 * Test a wrong argument type in readFile.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 */
	function testBadArgumentReadFile() {

		$result = $this->farm->readFile( 0 );
		$this->assertFalse( $result );
	}

	/**
	 * Test reading a badly-formatted YAML file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses ::MediaWikiFarm_readYAML
	 */
	function testBadSyntaxFileReadingYAML() {

		if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) ) {
			$this->markTestSkipped(
				'The optional YAML library was not installed.'
			);
		}

		$result = $this->farm->readFile( 'badsyntax.yaml', dirname( __FILE__ ) . '/data/config' );
		$this->assertFalse( $result );
	}

	/**
	 * Test reading a badly-formatted JSON file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 */
	function testBadSyntaxFileReadingJSON() {

		$result = $this->farm->readFile( 'badsyntax.json', dirname( __FILE__ ) . '/data/config' );
		$this->assertFalse( $result );
	}

	/**
	 * Test reading a badly-formatted YAML file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::cacheFile
	 * @uses ::MediaWikiFarm_readYAML
	 */
	function testEmptyFileReadingYAML() {

		if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) ) {
			$this->markTestSkipped(
				'The optional YAML library was not installed.'
			);
		}

		$result = $this->farm->readFile( 'empty.yml', dirname( __FILE__ ) . '/data/config' );
		$this->assertEquals( array(), $result );
	}

	/**
	 * Test a successufl reading an empty JSON file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::cacheFile
	 */
	function testEmptyFileReadingJSON() {

		$result = $this->farm->readFile( 'empty.json', dirname( __FILE__ ) . '/data/config' );
		$this->assertEquals( array(), $result );
	}

	/**
	 * Test a successufl reading an empty SER file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::cacheFile
	 */
	function testEmptyFileReadingSER() {

		$result = $this->farm->readFile( 'empty.ser', dirname( __FILE__ ) . '/data/config' );
		$this->assertEquals( array(), $result );
	}

	/**
	 * Test a bad content (not an array), in a JSON file here.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 */
	function testBadContentReadFile() {

		$result = $this->farm->readFile( dirname( __FILE__ ) . '/data/config/string.json' );
		$this->assertFalse( $result );
	}

	/**
	 * Test when there is no cache.
	 *
	 * This test is mainly used to add code coverage; the assertion is tested elsewhere.
	 *
	 * @covers MediaWikiFarm::cacheFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::getCacheDir
	 */
	function testNoCache() {

		$wgMediaWikiFarmConfigDirTest = dirname( __FILE__ ) . '/data/config';
		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', $wgMediaWikiFarmConfigDirTest, null, false );

		$farm->readFile( 'testreading.json', $wgMediaWikiFarmConfigDirTest );

		$this->assertFalse( $farm->getCacheDir() );
	}

	/**
	 * Test cache file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @covers MediaWikiFarm::cacheFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 */
	function testCacheFile() {

		$wgMediaWikiFarmConfigDirTest = dirname( __FILE__ ) . '/data/config';
		$wgMediaWikiFarmCacheDirTest = dirname( __FILE__ ) . '/data/cache';
		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', $wgMediaWikiFarmConfigDirTest, null, $wgMediaWikiFarmCacheDirTest );

		$farm->readFile( 'testreading.json', $wgMediaWikiFarmConfigDirTest );

		$this->assertTrue( is_file( $wgMediaWikiFarmCacheDirTest . '/testfarm-monoversion/testreading.json.php' ) );

		$result = $farm->readFile( 'testreading.json', $wgMediaWikiFarmConfigDirTest );

		$this->assertEquals(
			array(
				'element1',
				array( 'element2' => 'element3' ),
			),
			$result );
	}

	/**
	 * Test a farm with a badly-formatted ‘variables’ file.
	 *
	 * This test is mainly used to add code coverage; the assertion is tested elsewhere.
	 *
	 * @covers MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Missing or badly formatted file 'badsyntax.json' defining existing values for variable 'wiki'
	 */
	function testBadlyFormattedFileVariable() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-with-badly-formatted-file-variable.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test passing a wrong type to replaceVariables().
	 *
	 * @covers MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Argument of MediaWikiFarm->replaceVariables() must be a string or an array.
	 */
	function testWrongTypeReplaceVariables() {

		$result = $this->farm->replaceVariables( 1 );
	}

	/**
	 * Assert that object did not change during test.
	 *
	 * Methods tested here are supposed to be constant: the internal properties should not change.
	 */
	function assertPostConditions() {

		$this->assertEquals( $this->control, $this->farm, 'Methods tested in InstallationIndependantTest are supposed to be constant.' );
	}

	/**
	 * Remove 'data/cache' cache directory.
	 */
	protected function tearDown() {

		wfRecursiveRemoveDir( dirname( __FILE__ ) . '/data/cache' );

		parent::tearDown();
	}
}
