<?php

require_once 'MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/MediaWikiFarm.php';

/**
 * Installation-independant methods tests.
 *
 * These tests operate on constant methods, i.e. which do not modify the internal state of the
 * object. This constantness is tested as a post-condition for all tests.
 *
 * @group MediaWikiFarm
 */
class InstallationIndependantTest extends MediaWikiFarmTestCase {

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

		return new MediaWikiFarm( $host, self::$wgMediaWikiFarmConfigDir, null, false );
	}

	/**
	 * Set up the default MediaWikiFarm object with a sample correct configuration file.
	 */
	protected function setUp() {

		parent::setUp();

		if( is_null( $this->farm ) ) {
			$this->farm = self::constructMediaWikiFarm( 'a.testfarm-monoversion.example.org' );
		}
		$this->control = clone $this->farm;
	}

	/**
	 * Test a successful reading of a YAML file.
	 *
	 * @requires Symfony\Component\Yaml\Yaml::parse
	 * @covers MediaWikiFarm::readFile
	 * @covers ::wfMediaWikiFarm_readYAML
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
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

		$result = $this->farm->readFile( 'wrongformat', dirname( __FILE__ ) . '/data/config' );
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
	 * @uses ::wfMediaWikiFarm_readYAML
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
	 * @uses ::wfMediaWikiFarm_readYAML
	 */
	function testEmptyFileReadingYAML() {

		if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) ) {
			$this->markTestSkipped(
				'The optional YAML library was not installed.'
			);
		}

		$result = $this->farm->readFile( 'empty.yml', dirname( __FILE__ ) . '/data/config' );
		$this->assertEquals( false, $result );
	}

	/**
	 * Test a successufl reading an empty JSON file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
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

		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', self::$wgMediaWikiFarmConfigDir, null, false );

		$farm->readFile( 'testreading.json', self::$wgMediaWikiFarmConfigDir );

		$this->assertFalse( $farm->getCacheDir() );
	}

	/**
	 * Test cache file.
	 *
	 * @todo This test targets mainly MediaWikiFarm::cacheFile. This function was previously protected, so it was tested through
	 *       MediaWikiFarm::readFile. Now it is public-static, hence this test should be rewritten to directly test it.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @covers MediaWikiFarm::cacheFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses AbstractMediaWikiFarmScript::rmdirr
	 */
	function testCacheFile() {

		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', self::$wgMediaWikiFarmConfigDir, null, self::$wgMediaWikiFarmCacheDir );

		copy( self::$wgMediaWikiFarmConfigDir . '/testreading.json', self::$wgMediaWikiFarmConfigDir . '/testreading2.json' );

		# Read original file and check the cached version is written
		$farm->readFile( 'testreading2.json', self::$wgMediaWikiFarmConfigDir );
		$this->assertTrue( is_file( self::$wgMediaWikiFarmCacheDir . '/config/testreading2.json.php' ) );

		# Read cached version
		$result = $farm->readFile( 'testreading2.json', self::$wgMediaWikiFarmConfigDir );
		$this->assertEquals(
			array(
				'element1',
				array( 'element2' => 'element3' ),
			),
			$result );

		# Put the original file in badsyntax and check it fallbacks to cached version
		# Touch the file to simulate a later edit by the user
		copy( self::$wgMediaWikiFarmConfigDir . '/badsyntax.json', self::$wgMediaWikiFarmConfigDir . '/testreading2.json' );
		touch( self::$wgMediaWikiFarmConfigDir . '/testreading2.json', time() + 10 );
		$result = $farm->readFile( 'testreading2.json', self::$wgMediaWikiFarmConfigDir );
		$this->assertEquals(
			array(
				'element1',
				array( 'element2' => 'element3' ),
			),
			$result );
		unlink( self::$wgMediaWikiFarmConfigDir . '/testreading2.json' );

		$farm->readFile( 'subdir/testreading2.json', self::$wgMediaWikiFarmConfigDir );
		$this->assertTrue( is_file( self::$wgMediaWikiFarmCacheDir . '/config/subdir/testreading2.json.php' ) );

		# Test when it is requested to cache non-PHP file
		MediaWikiFarm::cacheFile( array(), 'nonexistant.json', self::$wgMediaWikiFarmCacheDir );
		$this->assertFalse( is_file( self::$wgMediaWikiFarmCacheDir . '/nonexistant.json' ) );
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
	 * Test the writing of the file LocalSettings.php.
	 *
	 * @covers MediaWikiFarm::createLocalSettings
	 * @covers MediaWikiFarm::writeArrayAssignment
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 */
	function testCreateLocalSettings() {

		$configuration = array(
			'settings' => array(
				'wgSitename' => 'Sid It',
				'wgMemCachedServers' => array( '127.0.0.1:11211' ),
			),
			'arrays' => array(
				'wgExtraNamespaces' => array( 100 => 'Bibliography' ),
				'wgNamespaceAliases' => array( 'Bibliography' => 100 ),
				'wgFileExtensions' => array( 'djvu' ),
				'wgVirtualRestConfig' => array(
					'modules' => array(
						'parsoid' => array(
							'domain' => 'localhost',
							'prefix' => 'localhost',
						),
					),
				),
			),
			'extensions' => array(
				array( 'ParserFunctions', 'extension', 'wfLoadExtension' ),
				array( 'Echo', 'extension', 'require_once' ),
				array( 'SemanticMediaWiki', 'extension', 'composer' ),
				array( 'Vector', 'skin', 'wfLoadSkin' ),
				array( 'MonoBook', 'skin', 'require_once' ),
				array( 'MediaWikiFarm', 'extension', 'wfLoadExtension' ),
			),
			'execFiles' => array(
				'freeLS.php',
			),
		);

		$localSettings = <<<PHP
<?php

# Pre-config

# Skins loaded with require_once
require_once "\$IP/skins/MonoBook/MonoBook.php";

# Extensions loaded with require_once
require_once "\$IP/extensions/Echo/Echo.php";

# General settings
\$wgSitename = 'Sid It';
\$wgMemCachedServers = array (
  0 => '127.0.0.1:11211',
);

# Array settings
if( !array_key_exists( 'wgExtraNamespaces', \$GLOBALS ) ) {
	\$GLOBALS['wgExtraNamespaces'] = array();
}
if( !array_key_exists( 'wgNamespaceAliases', \$GLOBALS ) ) {
	\$GLOBALS['wgNamespaceAliases'] = array();
}
if( !array_key_exists( 'wgFileExtensions', \$GLOBALS ) ) {
	\$GLOBALS['wgFileExtensions'] = array();
}
if( !array_key_exists( 'wgVirtualRestConfig', \$GLOBALS ) ) {
	\$GLOBALS['wgVirtualRestConfig'] = array();
}
\$wgExtraNamespaces[100] = 'Bibliography';
\$wgNamespaceAliases['Bibliography'] = 100;
\$wgFileExtensions[] = 'djvu';
\$wgVirtualRestConfig['modules']['parsoid']['domain'] = 'localhost';
\$wgVirtualRestConfig['modules']['parsoid']['prefix'] = 'localhost';

# Skins
wfLoadSkin( 'Vector' );

# Extensions
wfLoadExtension( 'ParserFunctions' );
wfLoadExtension( 'MediaWikiFarm' );

# Included files
include 'freeLS.php';

# Post-config

PHP;

		$this->assertEquals( $localSettings, $this->farm->createLocalSettings( $configuration, "# Pre-config\n", "# Post-config\n" ) );
	}

	/**
	 * Assert that object did not change during test.
	 *
	 * Methods tested here are supposed to be constant: the internal properties should not change.
	 */
	function assertPostConditions() {

		$this->assertEquals( $this->control, $this->farm, 'Methods tested in InstallationIndependantTest are supposed to be constant.' );
	}
}
