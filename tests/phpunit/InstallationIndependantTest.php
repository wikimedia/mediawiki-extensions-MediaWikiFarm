<?php
/**
 * Class InstallationIndependantTest.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

require_once dirname( __FILE__ ) . '/MediaWikiFarmTestCase.php';
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

	/** @var boolean Assert the object was not modified. */
	protected $shouldBeConstant = true;

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
	public static function constructMediaWikiFarm( $host ) {

		return new MediaWikiFarm( $host, null, self::$wgMediaWikiFarmConfigDir, null, false );
	}

	/**
	 * Set up the default MediaWikiFarm object with a sample correct configuration file.
	 */
	protected function setUp() {

		parent::setUp();

		if( $this->farm === null ) {
			$this->farm = self::constructMediaWikiFarm( 'a.testfarm-monoversion.example.org' );
		}
		$this->shouldBeConstant = true;
		$this->control = clone $this->farm;
	}

	/**
	 * Test a successful reading of a YAML file.
	 *
	 * @requires Symfony\Component\Yaml\Yaml::parse
	 * @covers MediaWikiFarm::readFile
	 * @covers MediaWikiFarmUtils::readFile
	 * @covers MediaWikiFarmUtils5_3::readYAML
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testSuccessfulReadingYAML() {

		if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) ) {
			$this->markTestSkipped(
				'The optional YAML library was not installed.'
			);
		}

		$result = $this->farm->readFile( 'testreading.yml', self::$wgMediaWikiFarmConfigDir );
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
	 * @covers MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testSuccessfulReadingPHP() {

		$result = $this->farm->readFile( 'testreading.php', self::$wgMediaWikiFarmConfigDir );
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
	 * @covers MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testSuccessfulReadingJSON() {

		$result = $this->farm->readFile( 'testreading.json', self::$wgMediaWikiFarmConfigDir );
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
	 * @covers MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testSuccessfulReadingSER() {

		$result = $this->farm->readFile( 'testreading.ser', self::$wgMediaWikiFarmConfigDir );
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
	 * @covers MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testSuccessfulReadingDblist() {

		$result = $this->farm->readFile( 'testreading.dblist', self::$wgMediaWikiFarmConfigDir );
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
	 * @covers MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testReadMissingFile() {

		$result = $this->farm->readFile( 'missingfile.yml', self::$wgMediaWikiFarmConfigDir );
		$this->assertFalse( $result );
	}

	/**
	 * Test an unrecognised format in readFile.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @covers MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testUnrecognisedFormatReadFile() {

		$result = $this->farm->readFile( 'wrongformat.txt', self::$wgMediaWikiFarmConfigDir );
		$this->assertFalse( $result );

		$result = $this->farm->readFile( 'wrongformat', self::$wgMediaWikiFarmConfigDir );
		$this->assertFalse( $result );
	}

	/**
	 * Test a wrong argument type in readFile.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @covers MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testBadArgumentReadFile() {

		$result = $this->farm->readFile( 0 );
		$this->assertFalse( $result );
	}

	/**
	 * Test reading a badly-formatted YAML file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @covers MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarmUtils5_3::readYAML
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testBadSyntaxFileReadingYAML() {

		if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) ) {
			$this->markTestSkipped(
				'The optional YAML library was not installed.'
			);
		}

		$result = $this->farm->readFile( 'badsyntax.yaml', self::$wgMediaWikiFarmConfigDir );
		$this->assertFalse( $result );
		$this->shouldBeConstant = false;
	}

	/**
	 * Test reading a badly-formatted JSON file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @covers MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testBadSyntaxFileReadingJSON() {

		$result = $this->farm->readFile( 'badsyntax.json', self::$wgMediaWikiFarmConfigDir );
		$this->assertFalse( $result );
	}

	/**
	 * Test reading a badly-formatted PHP file.
	 *
	 * @requires PHP 7.0
	 * @covers MediaWikiFarm::readFile
	 * @covers MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testBadSyntaxFileReadingPHP() {

		$result = $this->farm->readFile( 'badsyntax.php', self::$wgMediaWikiFarmConfigDir );
		$this->assertFalse( $result );
	}

	/**
	 * Test a successful reading of an empty PHP file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @covers MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarmUtils5_3::readYAML
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testEmptyFileReadingPHP() {

		$result = $this->farm->readFile( 'empty.php', self::$wgMediaWikiFarmConfigDir );
		$this->assertEquals( array(), $result );
	}

	/**
	 * Test a successful reading of an empty YAML file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @covers MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarmUtils5_3::readYAML
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testEmptyFileReadingYAML() {

		if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) ) {
			$this->markTestSkipped(
				'The optional YAML library was not installed.'
			);
		}

		$result = $this->farm->readFile( 'empty.yml', self::$wgMediaWikiFarmConfigDir );
		$this->assertEquals( array(), $result );
	}

	/**
	 * Test a successufl reading of an empty JSON file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @covers MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testEmptyFileReadingJSON() {

		$result = $this->farm->readFile( 'empty.json', self::$wgMediaWikiFarmConfigDir );
		$this->assertEquals( array(), $result );
	}

	/**
	 * Test a successufl reading an empty SER file.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @covers MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testEmptyFileReadingSER() {

		$result = $this->farm->readFile( 'empty.ser', self::$wgMediaWikiFarmConfigDir );
		$this->assertEquals( array(), $result );
	}

	/**
	 * Test a bad content (not an array), in a JSON file here.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @covers MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testBadContentReadFile() {

		$result = $this->farm->readFile( self::$wgMediaWikiFarmConfigDir . '/string.json' );
		$this->assertFalse( $result );
	}

	/**
	 * Test when there is no cache.
	 *
	 * This test is mainly used to add code coverage; the assertion is tested elsewhere.
	 *
	 * @covers MediaWikiFarmUtils::cacheFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::getCacheDir
	 * @uses MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testNoCache() {

		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', null, self::$wgMediaWikiFarmConfigDir, null, false );

		$farm->readFile( 'testreading.json', self::$wgMediaWikiFarmConfigDir );

		$this->assertFalse( $farm->getCacheDir() );
	}

	/**
	 * Test cache file.
	 *
	 * @todo This test targets mainly MediaWikiFarmUtils::cacheFile. This function was previously protected, so it was tested through
	 *       MediaWikiFarmUtils::readFile. Now it is public-static, hence this test should be rewritten to directly test it.
	 *
	 * @covers MediaWikiFarm::readFile
	 * @covers MediaWikiFarmUtils::cacheFile
	 * @covers MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses AbstractMediaWikiFarmScript::rmdirr
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testCacheFile() {

		$farm = new MediaWikiFarm( 'a.testfarm-monoversion.example.org', null, self::$wgMediaWikiFarmConfigDir, null, self::$wgMediaWikiFarmCacheDir );

		# Simulate an old origin file, so that the cached version (with current time) will be more recent as it should be
		copy( self::$wgMediaWikiFarmConfigDir . '/testreading.json', self::$wgMediaWikiFarmConfigDir . '/testreading2.json' );
		touch( self::$wgMediaWikiFarmConfigDir . '/testreading2.json', time() - 300 );

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
		touch( self::$wgMediaWikiFarmConfigDir . '/testreading2.json', time() + 300 );
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
		MediaWikiFarmUtils::cacheFile( array(), 'nonexistant.json', self::$wgMediaWikiFarmCacheDir );
		$this->assertFalse( is_file( self::$wgMediaWikiFarmCacheDir . '/nonexistant.json' ) );
	}

	/**
	 * [unit] Test reading an extension-less file.
	 *
	 * @covers MediaWikiFarmUtils::readAnyFile
	 * @uses MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm // should be removed: only used in setUp
	 */
	public function testReadAnyFileSuccess() {

		$log = array();
		$result = MediaWikiFarmUtils::readAnyFile( 'success', self::$wgMediaWikiFarmTestDataDir . '/readAnyFile', false, $log );

		$this->assertTrue(
			is_array( $result ) && array_keys( $result ) === array( 0, 1 ),
			'The returned value should be a list with two elements.'
		);
		$this->assertEquals( 'success.php', $result[1], 'The read file should be the PHP file.' );
		$this->assertEquals(
			array(
				0 => 'element1',
				1 => array(
					'element2' => 'element3',
				),
			),
			$result[0],
			'The result should be the specified array.'
		);
	}

	/**
	 * [unit] Test reading an extension-less file but non-existant file.
	 *
	 * @covers MediaWikiFarmUtils::readAnyFile
	 * @uses MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm // should be removed: only used in setUp
	 */
	public function testReadAnyFileNonExistant() {

		$log = array();
		$result = MediaWikiFarmUtils::readAnyFile( 'nonexistant', self::$wgMediaWikiFarmTestDataDir . '/readAnyFile', false, $log );

		$this->assertTrue(
			is_array( $result ) && array_keys( $result ) === array( 0, 1 ),
			'The returned value should be a list with two elements.'
		);
		$this->assertSame( '', $result[1], 'There should be no read file.' );
		$this->assertEquals(
			array(),
			$result[0],
			'The result should be an empty array.'
		);
	}

	/**
	 * [unit] Test reading an extension-less file with a bad syntax.
	 *
	 * @covers MediaWikiFarmUtils::readAnyFile
	 * @uses MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarm // should be removed: only used in setUp
	 */
	public function testReadAnyFileBadSyntax() {

		$log = array();
		$result = MediaWikiFarmUtils::readAnyFile( 'badsyntax', self::$wgMediaWikiFarmTestDataDir . '/readAnyFile', false, $log );

		$this->assertTrue(
			is_array( $result ) && array_keys( $result ) === array( 0, 1 ),
			'The returned value should be a list with two elements.'
		);
		$this->assertSame( '', $result[1], 'There should be no read file because the only file has a syntax error.' );
		$this->assertEquals(
			array(),
			$result[0],
			'The result should be an empty array.'
		);
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
	 * @uses MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarmUtils::readAnyFile
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Missing or badly formatted file 'badsyntax.json' defining existing values for variable 'wiki'
	 */
	public function testBadlyFormattedFileVariable() {

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
	 * @uses MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarmUtils::readAnyFile
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Argument of MediaWikiFarm->replaceVariables() must be a string or an array.
	 */
	public function testWrongTypeReplaceVariables() {

		$result = $this->farm->replaceVariables( 1 );
	}

	/**
	 * Test the writing of the file LocalSettings.php.
	 *
	 * @covers MediaWikiFarmConfiguration::createLocalSettings
	 * @covers MediaWikiFarmConfiguration::writeArrayAssignment
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::getCodeDir
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testCreateLocalSettings() {

		$this->backupAndSetGlobalVariable( 'IP', self::$wgMediaWikiFarmCodeDir . '/vstub' );

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
				'ExtensionSemanticMediaWiki' => array( 'SemanticMediaWiki', 'extension', 'composer', 0 ),
				'SkinMonoBook' => array( 'MonoBook', 'skin', 'require_once', 1 ),
				'ExtensionEcho' => array( 'Echo', 'extension', 'require_once', 2 ),
				'SkinVector' => array( 'Vector', 'skin', 'wfLoadSkin', 3 ),
				'ExtensionMediaWikiFarm' => array( 'MediaWikiFarm', 'extension', 'wfLoadExtension', 4 ),
				'ExtensionParserFunctions' => array( 'ParserFunctions', 'extension', 'wfLoadExtension', 5 ),
			),
			'composer' => array(
				'ExtensionSemanticMediaWiki',
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
wfLoadExtension( 'MediaWikiFarm' );
wfLoadExtension( 'ParserFunctions' );

# Included files
include 'freeLS.php';

# Post-config

PHP;

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion.example.org', null, self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false );
		$extensionJson = var_export( self::$wgMediaWikiFarmFarmDir . '/extension.json', true );
		$localSettings2 = str_replace( 'wfLoadExtension( \'MediaWikiFarm\' );', "wfLoadExtension( 'MediaWikiFarm', $extensionJson );", $localSettings );
		$this->assertEquals( $localSettings2,
			MediaWikiFarmConfiguration::createLocalSettings( $configuration, (bool) $farm->getCodeDir(), "# Pre-config\n", "# Post-config\n" )
		);

		# Test with wgExtensionDirectory and wgStyleDirectory
		$configuration['settings']['wgExtensionDirectory'] = '/mediawiki/extensions';
		$configuration['settings']['wgStyleDirectory'] = '/mediawiki/skins';
		$this->assertEquals(
			str_replace(
				array( '$IP/extensions', '$IP/skins', "'127.0.0.1:11211',\n);" ),
				array( '/mediawiki/extensions', '/mediawiki/skins',
				"'127.0.0.1:11211',\n);\n\$wgExtensionDirectory = '/mediawiki/extensions';\n\$wgStyleDirectory = '/mediawiki/skins';" ),
				$localSettings2
			),
			MediaWikiFarmConfiguration::createLocalSettings( $configuration, (bool) $farm->getCodeDir(), "# Pre-config\n", "# Post-config\n" )
		);
	}

	/**
	 * Test Composer key.
	 *
	 * @covers MediaWikiFarmConfiguration::composerKey
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils::readFile
	 * @uses MediaWikiFarmUtils::readAnyFile
	 */
	public function testComposerKey() {

		$this->assertEquals( 'c4538db9', MediaWikiFarmConfiguration::composerKey( 'ExtensionSemanticMediaWiki' ) );
		$this->assertSame( '', MediaWikiFarmConfiguration::composerKey( '' ) );
	}

	/**
	 * Assert that object did not change during test.
	 *
	 * Methods tested here are supposed to be constant: the internal properties should not change.
	 */
	public function assertPostConditions() {

		if( $this->shouldBeConstant ) {
			$this->assertEquals( $this->control, $this->farm, 'Methods tested in InstallationIndependantTest are supposed to be constant.' );
		}
	}
}
