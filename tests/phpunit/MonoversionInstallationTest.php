<?php
/**
 * Class MonoversionInstallationTest.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */


require_once dirname( __FILE__ ) . '/MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/MediaWikiFarm.php';

/**
 * Class testing the extension installed in monoversion mode.
 *
 * @group MediaWikiFarm
 * @covers MediaWikiFarm
 * @covers MediaWikiFarmUtils::readFile
 * @uses MediaWikiFarmUtils::readAnyFile
 */
class MonoversionInstallationTest extends MediaWikiFarmTestCase {

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
	public static function constructMediaWikiFarm( $host ) {

		$farm = new MediaWikiFarm( $host, null, self::$wgMediaWikiFarmConfigDir, null, false, array( 'EntryPoint' => 'index.php' ) );

		return $farm;
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
	public function testURLVariables() {

		$this->assertEquals(
			array(
				'$wiki' => 'a',
				'$FARM' => 'testfarm-monoversion',
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
	public function testVariables() {

		global $IP;

		$this->farm->checkExistence();

		# Check variables
		$this->assertEquals(
			array(
				'$wiki' => 'a',
				'$FARM' => 'testfarm-monoversion',
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
	public function testMissingSuffixVariable() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-with-missing-suffix.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test when a farm definition has no wikiID.
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Missing key 'wikiID' in farm configuration.
	 */
	public function testMissingWikiIDVariable() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-with-missing-wikiid.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test when a mandatory variable has a bad definition.
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Wrong type (non-string) for key 'suffix' in farm configuration.
	 */
	public function testBadTypeMandatoryVariable() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-with-bad-type-mandatory.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test when a non-mandatory variable has a bad definition.
	 */
	public function testBadTypeNonMandatoryVariable() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-with-bad-type-nonmandatory.example.org' );
		$farm->checkExistence();
		$this->assertNull( $farm->getVariable( '$DATA' ) );
	}

	/**
	 * Test edge cases when reading config file: missing defined variables, missing versions file.
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Only explicitly-defined wikis declared in existence lists are allowed in monoversion mode.
	 */
	public function testEdgeCasesConfigFile() {

		# Check a config file without defined variables
		$farm = self::constructMediaWikiFarm( 'a.testfarm-novariables.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test a existing host in a farm with a file variable without version defined inside.
	 */
	public function testVariableFileWithoutVersion() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-monoversion-with-file-variable-without-version.example.org' );
		$this->assertTrue( $farm->checkExistence() );
	}

	/**
	 * Test a nonexistant host in a farm with a file variable without version defined inside.
	 */
	public function testVariableFileWithoutVersionNonexistant() {

		$farm = self::constructMediaWikiFarm( 'c.testfarm-monoversion-with-file-variable-without-version.example.org' );
		$this->assertFalse( $farm->checkExistence() );
	}

	/**
	 * Test a existing host in a farm with a “values” variable without version defined inside.
	 */
	public function testVariableValuesWithoutVersion() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-monoversion-with-values-variable-without-version.example.org' );
		$this->assertTrue( $farm->checkExistence() );
	}

	/**
	 * Test a nonexistant host in a farm with a “values” variable without version defined inside.
	 */
	public function testVariableValuesWithoutVersionNonexistant() {

		$farm = self::constructMediaWikiFarm( 'c.testfarm-monoversion-with-values-variable-without-version.example.org' );
		$this->assertFalse( $farm->checkExistence() );
	}

	/**
	 * Test a existing host in a farm with a file variable with version defined inside.
	 */
	public function testVariableFileWithVersion() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-monoversion-with-file-variable-with-version.example.org' );
		$this->assertTrue( $farm->checkExistence() );
	}

	/**
	 * Test a nonexistant host in a farm with a file variable with version defined inside.
	 */
	public function testVariableFileWithVersionNonexistant() {

		$farm = self::constructMediaWikiFarm( 'b.testfarm-monoversion-with-file-variable-with-version.example.org' );
		$this->assertFalse( $farm->checkExistence() );
	}

	/**
	 * Test an undefined variable, declared in the host regex but not in the list of variables.
	 *
	 * This test is mainly used to add code coverage; the assertion is tested elsewhere.
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Only explicitly-defined wikis declared in existence lists are allowed in monoversion mode.
	 */
	public function testUndefinedVariable() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-monoversion-with-undefined-variable.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test memoisation of checkExistence().
	 *
	 * This test is mainly used to add code coverage; the assertion is tested elsewhere.
	 */
	public function testMemoisationCheckExistence() {

		$this->farm->checkExistence();
		$this->assertTrue( $this->farm->checkExistence() );
	}

	/**
	 * Test memoisation of checkHostVariables().
	 *
	 * This test is mainly used to add code coverage; the assertion is tested elsewhere.
	 */
	public function testMemoisationCheckHostVariables() {

		$this->farm->checkExistence();
		$this->assertTrue( $this->farm->checkHostVariables() );
	}
}
