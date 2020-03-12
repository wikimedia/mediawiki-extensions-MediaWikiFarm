<?php
/**
 * Class MultiversionInstallationTest.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

require_once dirname( __FILE__ ) . '/MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/MediaWikiFarm.php';

/**
 * Class testing the extension installed in multiversion mode.
 *
 * @group MediaWikiFarm
 * @covers MediaWikiFarm
 * @covers MediaWikiFarmUtils::isMediaWiki
 * @covers MediaWikiFarmUtils::cacheFile
 * @covers MediaWikiFarmUtils::readFile
 * @uses MediaWikiFarmUtils::readAnyFile
 */
class MultiversionInstallationTest extends MediaWikiFarmTestCase {

	/** @var MediaWikiFarm|null Test object. */
	protected $farm = null;

	/**
	 * Set up MediaWikiFarm parameters and versions files with the current MediaWiki installation.
	 */
	public static function setUpBeforeClass() {

		parent::setUpBeforeClass();

		# Create versions.php: the list of existing values for variable '$WIKIID' with their associated versions
		$versionsFile = <<<PHP
<?php

return array(
	'atestdeploymentsfarm' => 'vstub',
);

PHP;
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php', $versionsFile );
	}

	/**
	 * Construct a default MediaWikiFarm object with a sample correct configuration file.
	 *
	 * Use the current MediaWiki installation to simulate a multiversion installation.
	 *
	 * @param string $host Host name.
	 * @param string $entryPoint Entry point, else 'index.php'.
	 * @return MediaWikiFarm
	 */
	public static function constructMediaWikiFarm( $host, $entryPoint = 'index.php' ) {

		$farm = new MediaWikiFarm( $host, null, self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, array( 'EntryPoint' => $entryPoint ) );

		return $farm;
	}

	/**
	 * Set up the default MediaWikiFarm object with a sample correct configuration file.
	 */
	protected function setUp() {

		parent::setUp();

		$this->farm = self::constructMediaWikiFarm( 'a.testfarm-multiversion.example.org' );
	}

	/**
	 * Test the variable read for the URL is correct.
	 */
	public function testURLVariables() {

		$this->assertEquals(
			array(
				'$wiki' => 'a',
				'$FARM' => 'testfarm-multiversion',
				'$SERVER' => 'a.testfarm-multiversion.example.org',
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

		$this->farm->checkExistence();

		# Check variables
		$this->assertEquals(
			array(
				'$wiki' => 'a',
				'$FARM' => 'testfarm-multiversion',
				'$SERVER' => 'a.testfarm-multiversion.example.org',
				'$SUFFIX' => 'testfarm',
				'$WIKIID' => 'atestfarm',
				'$VERSION' => 'vstub',
				'$CODE' => self::$wgMediaWikiFarmCodeDir . '/vstub',
				'$VERSIONS' => 'versions.php',
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
	 * Test setting variables.
	 */
	/*
	public function testReplaceVariables() {

		$this->farm->checkExistence();

		# Check variables
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
	*/

	/**
	 * Test edge cases when reading config file: missing defined variables, missing versions file.
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Missing key 'versions' in farm configuration.
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

		$farm = self::constructMediaWikiFarm( 'a.testfarm-multiversion-with-file-variable-without-version.example.org' );
		$this->assertTrue( $farm->checkExistence() );
	}

	/**
	 * Test a nonexistant host in a farm with a file variable without version defined inside.
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage No version declared for this wiki.
	 */
	public function testVariableFileWithoutVersionMissingVersion() {

		$farm = self::constructMediaWikiFarm( 'b.testfarm-multiversion-with-file-variable-without-version.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test a nonexistant host in a farm with a file variable without version defined inside.
	 */
	public function testVariableFileWithoutVersionNonexistant() {

		$farm = self::constructMediaWikiFarm( 'c.testfarm-multiversion-with-file-variable-without-version.example.org' );
		$this->assertFalse( $farm->checkExistence() );
	}

	/**
	 * Test a existing host in a farm with a file variable with version defined inside.
	 */
	public function testVariableFileWithVersion() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-multiversion-with-file-variable-with-version.example.org' );
		$this->assertTrue( $farm->checkExistence() );
	}

	/**
	 * Test a nonexistant host in a farm with a file variable with version defined inside.
	 */
	public function testVariableFileWithVersionNonexistant() {

		$farm = self::constructMediaWikiFarm( 'c.testfarm-multiversion.example.org' );
		$this->assertFalse( $farm->checkExistence() );
	}

	/**
	 * Test a nonexistant host in a farm with a file variable with version defined inside.
	 *
	 * @expectedException MWFConfigurationException
	 * @codingStandardsIgnoreStart Generic.Files.LineLength.TooLong
	 * @expectedExceptionMessage Only explicitly-defined wikis declared in existence lists are allowed to use the “default versions” mechanism (suffix) in multiversion mode.
	 * @codingStandardsIgnoreEnd
	 */
	public function testVersionDefaultFamily() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-multiversion-with-version-default-family.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test a nonexistant host in a farm with a file variable with version defined inside.
	 *
	 * @expectedException MWFConfigurationException
	 * @codingStandardsIgnoreStart Generic.Files.LineLength.TooLong
	 * @expectedExceptionMessage Only explicitly-defined wikis declared in existence lists are allowed to use the “default versions” mechanism (default) in multiversion mode.
	 * @codingStandardsIgnoreEnd
	 */
	public function testVersionDefaultDefault() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-multiversion-with-version-default-default.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test families and default versions for families.
	 */
	public function testFamilyFarm() {

		$farm = new MediaWikiFarm( 'a.a.testfarm-multiversion-with-file-versions-other-keys.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, array( 'EntryPoint' => 'index.php' )
			);
		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( 'afamilytestfarm', $farm->getVariable( '$SUFFIX' ) );
		$this->assertEquals( 'aafamilytestfarm', $farm->getVariable( '$WIKIID' ) );
		$this->assertEquals( 'vstub', $farm->getVariable( '$VERSION' ) );

		$farm = new MediaWikiFarm( 'b.a.testfarm-multiversion-with-file-versions-other-keys.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, array( 'EntryPoint' => 'index.php' )
			);
		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( 'afamilytestfarm', $farm->getVariable( '$SUFFIX' ) );
		$this->assertEquals( 'bafamilytestfarm', $farm->getVariable( '$WIKIID' ) );
		$this->assertEquals( 'vstub', $farm->getVariable( '$VERSION' ) );

		$farm = new MediaWikiFarm( 'a.b.testfarm-multiversion-with-file-versions-other-keys.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, array( 'EntryPoint' => 'index.php' )
			);
		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( 'bfamilytestfarm', $farm->getVariable( '$SUFFIX' ) );
		$this->assertEquals( 'abfamilytestfarm', $farm->getVariable( '$WIKIID' ) );
		$this->assertEquals( 'vstub', $farm->getVariable( '$VERSION' ) );
	}

	/**
	 * Test an undefined variable, declared in the host regex but not in the list of variables.
	 *
	 * This test is mainly used to add code coverage; the assertion is tested elsewhere.
	 */
	public function testUndefinedVariable() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-multiversion-with-undefined-variable.example.org' );
		$this->assertTrue( $farm->checkExistence() );
	}

	/**
	 * Test a badly-formatted 'versions' file.
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Missing or badly formatted file 'badsyntax.json' containing the versions for wikis.
	 */
	public function testBadlyFormattedVersionsFile() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-multiversion-with-bad-file-versions.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test the feature 'deployments' with deployed versions.
	 */
	public function testDeployedVersions() {

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-with-file-versions-with-deployments.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, array( 'EntryPoint' => 'index.php' )
			);

		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( 'vstub', $farm->getVariable( '$VERSION' ) );
		$this->assertTrue( is_file( self::$wgMediaWikiFarmConfigDir . '/deployments.php' ) );

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-with-file-versions-with-deployments.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, array( 'EntryPoint' => 'index.php' )
			);
		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( 'vstub', $farm->getVariable( '$VERSION' ) );
	}

	/**
	 * Test the feature 'deployments' with deployed versions.
	 *
	 * @depends testDeployedVersions
	 */
	public function testDeployedVersions2() {

		# Create testdeploymentsfarmversions.php: the list of existing values for variable '$WIKIID' with their associated versions
		$versionsFile = <<<PHP
<?php

return array(
	'atestdeploymentsfarm' => 'vstub2',
);

PHP;

		# Create deployments.php: the list of existing values for variable '$WIKIID' with their associated deployed versions
		$deploymentsFile = <<<PHP
<?php

return array(
	'atestdeploymentsfarm' => 'vstub',
);

PHP;
		$time = time();
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php', $versionsFile );
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/deployments.php', $deploymentsFile );
		touch( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php', $time - 10 );
		touch( self::$wgMediaWikiFarmConfigDir . '/deployments.php', $time );

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-with-file-versions-with-deployments.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, array( 'EntryPoint' => 'index.php' )
			);

		$this->assertTrue( is_file( self::$wgMediaWikiFarmConfigDir . '/deployments.php' ) );
		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( 'vstub', $farm->getVariable( '$VERSION' ) );
	}

	/**
	 * Test the feature 'deployments' with deployed versions.
	 *
	 * @depends testDeployedVersions2
	 */
	public function testDeployedVersions3() {

		# Create testdeploymentsfarmversions.php: the list of existing values for variable '$WIKIID' with their associated versions
		$versionsFile = <<<PHP
<?php

return array(
	'atestdeploymentsfarm' => 'vstub2',
);

PHP;

		# Create deployments.php: the list of existing values for variable '$WIKIID' with their associated deployed versions
		$deploymentsFile = <<<PHP
<?php

return array(
	'atestdeploymentsfarm' => 'vstub',
);

PHP;
		$time = time();
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php', $versionsFile );
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/deployments.php', $deploymentsFile );
		touch( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php', $time - 10 );
		touch( self::$wgMediaWikiFarmConfigDir . '/deployments.php', $time );

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-with-file-versions-with-deployments.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, array( 'EntryPoint' => 'maintenance/update.php' )
			);
		$farm->updateVersionAfterMaintenance();

		$this->assertTrue( is_file( self::$wgMediaWikiFarmConfigDir . '/deployments.php' ) );
		$this->assertTrue( $farm->checkExistence() );

		$this->assertEquals( 'vstub2', $farm->getVariable( '$VERSION' ) );

		$farm->updateVersionAfterMaintenance();
		$this->assertEquals( 'vstub2', $farm->getVariable( '$VERSION' ) );

		# Mainly for code coverage to check the file is not re-written with the very same data
		$farm->updateVersionAfterMaintenance();
		$this->assertEquals( 'vstub2', $farm->getVariable( '$VERSION' ) );
	}

	/**
	 * Test the feature 'deployments' with deployed versions.
	 *
	 * @depends testDeployedVersions3
	 */
	public function testDeployedVersions4() {

		# Create testdeploymentsfarmversions.php: the list of existing values for variable '$WIKIID' with their associated versions
		$versionsFile = <<<PHP
<?php

return array(
	'atestdeploymentsfarm' => 'vstub2',
);

PHP;

		# Create deployments.php: the list of existing values for variable '$WIKIID' with their associated deployed versions
		$deploymentsFile = <<<PHP
<?php

return array(
	'atestdeploymentsfarm' => 'vstub2',
);

PHP;
		$time = time();
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php', $versionsFile );
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/deployments.php', $deploymentsFile );
		touch( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php', $time - 10 );
		touch( self::$wgMediaWikiFarmConfigDir . '/deployments.php', $time );

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-with-file-versions-with-deployments.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, array( 'EntryPoint' => 'index.php' )
			);

		$this->assertTrue( is_file( self::$wgMediaWikiFarmConfigDir . '/deployments.php' ) );
		$this->assertTrue( $farm->checkExistence() );

		$this->assertEquals( 'vstub2', $farm->getVariable( '$VERSION' ) );
	}

	/**
	 * Test the feature 'deployments' with deployed versions.
	 *
	 * @depends testDeployedVersions4
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage No version declared for this wiki.
	 */
	public function testDeployedVersions5() {

		# Create testdeploymentsfarmversions.php: the list of existing values for variable '$WIKIID' with their associated versions
		$versionsFile = <<<PHP
<?php

return array(
);

PHP;

		# Create deployments.php: the list of existing values for variable '$WIKIID' with their associated deployed versions
		$deploymentsFile = <<<PHP
<?php

return array(
	'atestdeploymentsfarm' => 'vstub2',
);

PHP;
		$time = time();
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions5.php', $versionsFile );
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/deployments5.php', $deploymentsFile );
		touch( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions5.php', $time );
		touch( self::$wgMediaWikiFarmConfigDir . '/deployments5.php', $time - 10 );

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-with-file-versions-with-deployments5.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, array( 'EntryPoint' => 'index.php' )
			);

		$this->assertTrue( is_file( self::$wgMediaWikiFarmConfigDir . '/deployments5.php' ) );
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

	/**
	 * Test cache of checkExistence().
	 *
	 * @group medium
	 * @uses MediaWikiFarmConfiguration
	 * @uses AbstractMediaWikiFarmScript::rmdirr
	 */
	public function testCacheExistence() {

		# Populate the existence cache
		$farm = new MediaWikiFarm( 'a.testfarm-multiversion.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, self::$wgMediaWikiFarmCacheDir,
			                   array( 'EntryPoint' => 'index.php' )
			);

		$this->assertTrue( $farm->checkExistence() );
		$this->assertTrue( is_file( self::$wgMediaWikiFarmCacheDir . '/wikis/a.testfarm-multiversion.example.org.php' ) );
		$this->assertFalse( is_file( self::$wgMediaWikiFarmCacheDir . '/LocalSettings/a.testfarm-multiversion.example.org.php' ) );

		# Read the existence cache
		$farm = new MediaWikiFarm( 'a.testfarm-multiversion.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, self::$wgMediaWikiFarmCacheDir,
		                           array( 'EntryPoint' => 'index.php' )
		);

		$this->assertTrue( $farm->checkExistence() );
		$this->assertTrue( $farm->setVersion() );

		# Populate the configuration cache
		$farm = new MediaWikiFarm( 'a.testfarm-multiversion.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, self::$wgMediaWikiFarmCacheDir,
			                   array( 'EntryPoint' => 'index.php', 'InnerMediaWiki' => true ),
			                   array( 'ExtensionRegistry' => true )
			);

		$this->assertTrue( $farm->checkExistence() );
		$farm->compileConfiguration();
		$this->assertTrue( is_file( self::$wgMediaWikiFarmCacheDir . '/wikis/a.testfarm-multiversion.example.org.php' ) );
		$this->assertTrue( is_file( self::$wgMediaWikiFarmCacheDir . '/LocalSettings/a.testfarm-multiversion.example.org.php' ) );

		# Invalidate the existence cache
		$this->assertTrue( touch( self::$wgMediaWikiFarmConfigDir . '/farms.php', time() + 300 ) );

		# Check the existence cache is understood as invalidated
		$farm = new MediaWikiFarm( 'a.testfarm-multiversion.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, self::$wgMediaWikiFarmCacheDir,
			                   array( 'EntryPoint' => 'index.php' )
			);
		$this->assertFalse( is_file( self::$wgMediaWikiFarmCacheDir . '/LocalSettings/a.testfarm-multiversion.example.org.php' ) );

		# Reinit mtime of farms.php for further tests
		$this->assertTrue( touch( self::$wgMediaWikiFarmConfigDir . '/farms.php', time() - 5 ) );
	}

	/**
	 * Remove config files.
	 */
	public static function tearDownAfterClass() {

		if( is_file( self::$wgMediaWikiFarmConfigDir . '/deployments.php' ) ) {
			unlink( self::$wgMediaWikiFarmConfigDir . '/deployments.php' );
		}
		if( is_file( self::$wgMediaWikiFarmConfigDir . '/deployments5.php' ) ) {
			unlink( self::$wgMediaWikiFarmConfigDir . '/deployments5.php' );
		}
		if( is_file( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php' ) ) {
			unlink( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php' );
		}
		if( is_file( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions5.php' ) ) {
			unlink( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions5.php' );
		}

		parent::tearDownAfterClass();
	}
}
