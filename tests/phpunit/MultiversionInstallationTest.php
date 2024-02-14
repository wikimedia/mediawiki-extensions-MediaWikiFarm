<?php
/**
 * Class MultiversionInstallationTest.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

require_once __DIR__ . '/MediaWikiFarmTestCase.php';
require_once dirname( dirname( __DIR__ ) ) . '/src/MediaWikiFarm.php';

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
	public static function setUpBeforeClass() : void {

		parent::setUpBeforeClass();

		# Create versions.php: the list of existing values for variable '$WIKIID' with their associated versions
		$versionsFile = <<<PHP
<?php

return [
	'atestdeploymentsfarm' => 'vstub',
];

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

		$farm = new MediaWikiFarm( $host, null, self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, [ 'EntryPoint' => $entryPoint ] );

		return $farm;
	}

	/**
	 * Set up the default MediaWikiFarm object with a sample correct configuration file.
	 */
	protected function setUp() : void {

		parent::setUp();

		$this->farm = self::constructMediaWikiFarm( 'a.testfarm-multiversion.example.org' );
	}

	/**
	 * Test the variable read for the URL is correct.
	 */
	public function testURLVariables() {

		$this->assertEquals(
			[
				'$wiki' => 'a',
				'$FARM' => 'testfarm-multiversion',
				'$SERVER' => 'a.testfarm-multiversion.example.org',
				'$SUFFIX' => '',
				'$WIKIID' => '',
				'$VERSION' => null,
				'$CODE' => '',
			],
			$this->farm->getVariables() );
	}

	/**
	 * Test basic variables.
	 */
	public function testVariables() {

		$this->farm->checkExistence();

		# Check variables
		$this->assertEquals(
			[
				'$wiki' => 'a',
				'$FARM' => 'testfarm-multiversion',
				'$SERVER' => 'a.testfarm-multiversion.example.org',
				'$SUFFIX' => 'testfarm',
				'$WIKIID' => 'atestfarm',
				'$VERSION' => 'vstub',
				'$CODE' => self::$wgMediaWikiFarmCodeDir . '/vstub',
				'$VERSIONS' => 'versions.php',
			],
			$this->farm->getVariables() );
	}

	/**
	 * Test when a farm definition has no suffix.
	 */
	public function testMissingSuffixVariable() {

		$this->expectException( MWFConfigurationException::class );
		$this->expectExceptionMessage( 'Missing key \'suffix\' in farm configuration.' );

		$farm = self::constructMediaWikiFarm( 'a.testfarm-with-missing-suffix.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test when a farm definition has no wikiID.
	 */
	public function testMissingWikiIDVariable() {

		$this->expectException( MWFConfigurationException::class );
		$this->expectExceptionMessage( 'Missing key \'wikiID\' in farm configuration.' );

		$farm = self::constructMediaWikiFarm( 'a.testfarm-with-missing-wikiid.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test when a mandatory variable has a bad definition.
	 */
	public function testBadTypeMandatoryVariable() {

		$this->expectException( MWFConfigurationException::class );
		$this->expectExceptionMessage( 'Wrong type (non-string) for key \'suffix\' in farm configuration.' );

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
			[
				'$wiki' => 'a',
				'$SUFFIX' => 'testfarm',
				'$WIKIID' => 'atestfarm',
				'$VERSIONS' => 'versions.php',
				'$VERSION' => 'master',
				'$CODE' => $IP,
			],
			$this->farm->getVariables() );
	}
	*/

	/**
	 * Test edge cases when reading config file: missing defined variables, missing versions file.
	 */
	public function testEdgeCasesConfigFile() {

		$this->expectException( MWFConfigurationException::class );
		$this->expectExceptionMessage( 'Missing key \'versions\' in farm configuration.' );

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
	 */
	public function testVariableFileWithoutVersionMissingVersion() {

		$this->expectException( MWFConfigurationException::class );
		$this->expectExceptionMessage( 'No version declared for this wiki.' );

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
	 */
	public function testVersionDefaultFamily() {

		$this->expectException( MWFConfigurationException::class );
		// phpcs:ignore Generic.Files.LineLength.TooLong
		$this->expectExceptionMessage( 'Only explicitly-defined wikis declared in existence lists are allowed to use the “default versions” mechanism (suffix) in multiversion mode.' );

		$farm = self::constructMediaWikiFarm( 'a.testfarm-multiversion-with-version-default-family.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test a nonexistant host in a farm with a file variable with version defined inside.
	 */
	public function testVersionDefaultDefault() {

		$this->expectException( MWFConfigurationException::class );
		// phpcs:ignore Generic.Files.LineLength.TooLong
		$this->expectExceptionMessage( 'Only explicitly-defined wikis declared in existence lists are allowed to use the “default versions” mechanism (default) in multiversion mode.' );

		$farm = self::constructMediaWikiFarm( 'a.testfarm-multiversion-with-version-default-default.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test families and default versions for families.
	 */
	public function testFamilyFarm() {

		$farm = new MediaWikiFarm( 'a.a.testfarm-multiversion-with-file-versions-other-keys.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, [ 'EntryPoint' => 'index.php' ]
			);
		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( 'afamilytestfarm', $farm->getVariable( '$SUFFIX' ) );
		$this->assertEquals( 'aafamilytestfarm', $farm->getVariable( '$WIKIID' ) );
		$this->assertEquals( 'vstub', $farm->getVariable( '$VERSION' ) );

		$farm = new MediaWikiFarm( 'b.a.testfarm-multiversion-with-file-versions-other-keys.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, [ 'EntryPoint' => 'index.php' ]
			);
		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( 'afamilytestfarm', $farm->getVariable( '$SUFFIX' ) );
		$this->assertEquals( 'bafamilytestfarm', $farm->getVariable( '$WIKIID' ) );
		$this->assertEquals( 'vstub', $farm->getVariable( '$VERSION' ) );

		$farm = new MediaWikiFarm( 'a.b.testfarm-multiversion-with-file-versions-other-keys.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, [ 'EntryPoint' => 'index.php' ]
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
	 */
	public function testBadlyFormattedVersionsFile() {

		$this->expectException( MWFConfigurationException::class );
		$this->expectExceptionMessage( 'Missing or badly formatted file \'badsyntax.json\' containing the versions for wikis.' );

		$farm = self::constructMediaWikiFarm( 'a.testfarm-multiversion-with-bad-file-versions.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test the feature 'deployments' with deployed versions.
	 */
	public function testDeployedVersions() {

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-with-file-versions-with-deployments.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, [ 'EntryPoint' => 'index.php' ]
			);

		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( 'vstub', $farm->getVariable( '$VERSION' ) );
		$this->assertTrue( is_file( self::$wgMediaWikiFarmConfigDir . '/deployments.php' ) );

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-with-file-versions-with-deployments.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, [ 'EntryPoint' => 'index.php' ]
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

return [
	'atestdeploymentsfarm' => 'vstub2',
];

PHP;

		# Create deployments.php: the list of existing values for variable '$WIKIID' with their associated deployed versions
		$deploymentsFile = <<<PHP
<?php

return [
	'atestdeploymentsfarm' => 'vstub',
];

PHP;
		$time = time();
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php', $versionsFile );
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/deployments.php', $deploymentsFile );
		touch( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php', $time - 10 );
		touch( self::$wgMediaWikiFarmConfigDir . '/deployments.php', $time );

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-with-file-versions-with-deployments.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, [ 'EntryPoint' => 'index.php' ]
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

return [
	'atestdeploymentsfarm' => 'vstub2',
];

PHP;

		# Create deployments.php: the list of existing values for variable '$WIKIID' with their associated deployed versions
		$deploymentsFile = <<<PHP
<?php

return [
	'atestdeploymentsfarm' => 'vstub',
];

PHP;
		$time = time();
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php', $versionsFile );
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/deployments.php', $deploymentsFile );
		touch( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php', $time - 10 );
		touch( self::$wgMediaWikiFarmConfigDir . '/deployments.php', $time );

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-with-file-versions-with-deployments.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, [ 'EntryPoint' => 'maintenance/update.php' ]
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

return [
	'atestdeploymentsfarm' => 'vstub2',
];

PHP;

		# Create deployments.php: the list of existing values for variable '$WIKIID' with their associated deployed versions
		$deploymentsFile = <<<PHP
<?php

return [
	'atestdeploymentsfarm' => 'vstub2',
];

PHP;
		$time = time();
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php', $versionsFile );
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/deployments.php', $deploymentsFile );
		touch( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php', $time - 10 );
		touch( self::$wgMediaWikiFarmConfigDir . '/deployments.php', $time );

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-with-file-versions-with-deployments.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, [ 'EntryPoint' => 'index.php' ]
			);

		$this->assertTrue( is_file( self::$wgMediaWikiFarmConfigDir . '/deployments.php' ) );
		$this->assertTrue( $farm->checkExistence() );

		$this->assertEquals( 'vstub2', $farm->getVariable( '$VERSION' ) );
	}

	/**
	 * Test the feature 'deployments' with deployed versions.
	 *
	 * @depends testDeployedVersions4
	 */
	public function testDeployedVersions5() {

		$this->expectException( MWFConfigurationException::class );
		$this->expectExceptionMessage( 'No version declared for this wiki.' );

		# Create testdeploymentsfarmversions.php: the list of existing values for variable '$WIKIID' with their associated versions
		$versionsFile = <<<PHP
<?php

return [
];

PHP;

		# Create deployments.php: the list of existing values for variable '$WIKIID' with their associated deployed versions
		$deploymentsFile = <<<PHP
<?php

return [
	'atestdeploymentsfarm' => 'vstub2',
];

PHP;
		$time = time();
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions5.php', $versionsFile );
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/deployments5.php', $deploymentsFile );
		touch( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions5.php', $time );
		touch( self::$wgMediaWikiFarmConfigDir . '/deployments5.php', $time - 10 );

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-with-file-versions-with-deployments5.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, false, [ 'EntryPoint' => 'index.php' ]
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
			                   [ 'EntryPoint' => 'index.php' ]
			);

		$this->assertTrue( $farm->checkExistence() );
		$this->assertTrue( is_file( self::$wgMediaWikiFarmCacheDir . '/wikis/a.testfarm-multiversion.example.org.php' ) );
		$this->assertFalse( is_file( self::$wgMediaWikiFarmCacheDir . '/LocalSettings/a.testfarm-multiversion.example.org.php' ) );

		# Read the existence cache
		$farm = new MediaWikiFarm( 'a.testfarm-multiversion.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, self::$wgMediaWikiFarmCacheDir,
		                           [ 'EntryPoint' => 'index.php' ]
		);

		$this->assertTrue( $farm->checkExistence() );
		$this->assertTrue( $farm->setVersion() );

		# Populate the configuration cache
		$farm = new MediaWikiFarm( 'a.testfarm-multiversion.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, self::$wgMediaWikiFarmCacheDir,
			                   [ 'EntryPoint' => 'index.php', 'InnerMediaWiki' => true ],
			                   [ 'ExtensionRegistry' => true ]
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
			                   [ 'EntryPoint' => 'index.php' ]
			);
		$this->assertFalse( is_file( self::$wgMediaWikiFarmCacheDir . '/LocalSettings/a.testfarm-multiversion.example.org.php' ) );

		# Reinit mtime of farms.php for further tests
		$this->assertTrue( touch( self::$wgMediaWikiFarmConfigDir . '/farms.php', time() - 5 ) );
	}

	/**
	 * Remove config files.
	 */
	public static function tearDownAfterClass() : void {

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
