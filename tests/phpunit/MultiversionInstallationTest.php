<?php
/**
 * Class MultiversionInstallationTest.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0, or (at your option) any later version.
 * @license AGPL-3.0+ GNU Affero General Public License v3.0, or (at your option) any later version.
 */

require_once 'MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/MediaWikiFarm.php';

/**
 * Class testing the extension installed in multiversion mode.
 *
 * @group MediaWikiFarm
 * @covers MediaWikiFarm
 */
class MultiversionInstallationTest extends MediaWikiFarmTestCase {

	/** @var MediaWikiFarm|null Test object. */
	protected $farm = null;

	/**
	 * Set up MediaWikiFarm parameters and versions files with the current MediaWiki installation.
	 */
	static function setUpBeforeClass() {

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
	static function constructMediaWikiFarm( $host, $entryPoint = 'index.php' ) {

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
	function testURLVariables() {

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
	function testVariables() {

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

		$farm = self::constructMediaWikiFarm( 'a.testfarm-with-bad-type-nonmandatory.example.org' );
		$farm->checkExistence();
		$this->assertNull( $farm->getVariable( '$DATA' ) );
	}

	/**
	 * Test setting variables.
	 *
	function testReplaceVariables() {

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

	/**
	 * Test edge cases when reading config file: missing defined variables, missing versions file.
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Undefined key 'variables' in the farm configuration
	 */
	function testEdgeCasesConfigFile() {

		# Check a config file without defined variables
		$farm = self::constructMediaWikiFarm( 'a.testfarm-novariables.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test a existing host in a farm with a file variable without version defined inside.
	 */
	function testVariableFileWithoutVersion() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-multiversion-with-file-variable-without-version.example.org' );
		$this->assertTrue( $farm->checkExistence() );
	}

	/**
	 * Test a nonexistant host in a farm with a file variable without version defined inside.
	 */
	function testVariableFileWithoutVersionMissingVersion() {

		$farm = self::constructMediaWikiFarm( 'b.testfarm-multiversion-with-file-variable-without-version.example.org' );
		$this->assertFalse( $farm->checkExistence() );
	}

	/**
	 * Test a nonexistant host in a farm with a file variable without version defined inside.
	 */
	function testVariableFileWithoutVersionNonexistant() {

		$farm = self::constructMediaWikiFarm( 'c.testfarm-multiversion-with-file-variable-without-version.example.org' );
		$this->assertFalse( $farm->checkExistence() );
	}

	/**
	 * Test a existing host in a farm with a file variable with version defined inside.
	 */
	function testVariableFileWithVersion() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-multiversion-with-file-variable-with-version.example.org' );
		$this->assertTrue( $farm->checkExistence() );
	}

	/**
	 * Test a nonexistant host in a farm with a file variable with version defined inside.
	 */
	function testVariableFileWithVersionNonexistant() {

		$farm = self::constructMediaWikiFarm( 'c.testfarm-multiversion.example.org' );
		$this->assertFalse( $farm->checkExistence() );
	}

	/**
	 * Test families and default versions for families.
	 */
	function testFamilyFarm() {

		$farm = new MediaWikiFarm( 'a.a.testfarm-multiversion-with-file-versions-other-keys.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', false, array( 'EntryPoint' => 'index.php' )
			);
		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( 'afamilytestfarm', $farm->getVariable( '$SUFFIX' ) );
		$this->assertEquals( 'aafamilytestfarm', $farm->getVariable( '$WIKIID' ) );
		$this->assertEquals( 'vstub', $farm->getVariable( '$VERSION' ) );

		$farm = new MediaWikiFarm( 'b.a.testfarm-multiversion-with-file-versions-other-keys.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', false, array( 'EntryPoint' => 'index.php' )
			);
		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( 'afamilytestfarm', $farm->getVariable( '$SUFFIX' ) );
		$this->assertEquals( 'bafamilytestfarm', $farm->getVariable( '$WIKIID' ) );
		$this->assertEquals( 'vstub', $farm->getVariable( '$VERSION' ) );

		$farm = new MediaWikiFarm( 'a.b.testfarm-multiversion-with-file-versions-other-keys.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', false, array( 'EntryPoint' => 'index.php' )
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
	function testUndefinedVariable() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-multiversion-with-undefined-variable.example.org' );
		$this->assertTrue( $farm->checkExistence() );
	}

	/**
	 * Test a badly-formatted 'versions' file.
	 *
	 * @expectedException MWFConfigurationException
	 * @expectedExceptionMessage Missing or badly formatted file 'badsyntax.json' containing the versions for wikis.
	 */
	function testBadlyFormattedVersionsFile() {

		$farm = self::constructMediaWikiFarm( 'a.testfarm-multiversion-with-bad-file-versions.example.org' );
		$farm->checkExistence();
	}

	/**
	 * Test the feature 'deployments' with deployed versions.
	 */
	function testDeploymedVersions() {

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-with-file-versions-with-deployments.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', false, array( 'EntryPoint' => 'index.php' )
			);

		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( 'vstub', $farm->getVariable( '$VERSION' ) );
		$this->assertTrue( is_file( self::$wgMediaWikiFarmConfigDir . '/deployments.php' ) );

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-with-file-versions-with-deployments.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', false, array( 'EntryPoint' => 'index.php' )
			);
		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( 'vstub', $farm->getVariable( '$VERSION' ) );
	}

	/**
	 * Test the feature 'deployments' with deployed versions.
	 *
	 * @depends testDeploymedVersions
	 */
	function testDeploymedVersions2() {

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
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php', $versionsFile );
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/deployments.php', $deploymentsFile );

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-with-file-versions-with-deployments.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', false, array( 'EntryPoint' => 'index.php' )
			);

		$this->assertTrue( is_file( self::$wgMediaWikiFarmConfigDir . '/deployments.php' ) );
		$this->assertTrue( $farm->checkExistence() );
		$this->assertEquals( 'vstub', $farm->getVariable( '$VERSION' ) );
	}

	/**
	 * Test the feature 'deployments' with deployed versions.
	 *
	 * @depends testDeploymedVersions2
	 */
	function testDeploymedVersions3() {

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
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php', $versionsFile );
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/deployments.php', $deploymentsFile );

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-with-file-versions-with-deployments.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', false, array( 'EntryPoint' => 'maintenance/update.php' )
			);
		$farm->updateVersionAfterMaintenance();

		$this->assertTrue( is_file( self::$wgMediaWikiFarmConfigDir . '/deployments.php' ) );
		$this->assertTrue( $farm->checkExistence() );

		if( strpos( phpversion(), 'hhvm' ) !== false ) {
			return; # Multiple calls of 'include' do not check for file modification HHVM 3.5.0 https://github.com/facebook/hhvm/issues/4797
		}

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
	 * @depends testDeploymedVersions3
	 */
	function testDeploymedVersions4() {

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
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/testdeploymentsfarmversions.php', $versionsFile );
		file_put_contents( self::$wgMediaWikiFarmConfigDir . '/deployments.php', $deploymentsFile );

		$farm = new MediaWikiFarm( 'a.testfarm-multiversion-with-file-versions-with-deployments.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, dirname( __FILE__ ) . '/data/mediawiki', false, array( 'EntryPoint' => 'index.php' )
			);

		$this->assertTrue( is_file( self::$wgMediaWikiFarmConfigDir . '/deployments.php' ) );
		$this->assertTrue( $farm->checkExistence() );

		if( strpos( phpversion(), 'hhvm' ) !== false ) {
			return; # Multiple calls of 'include' do not check for file modification HHVM 3.5.0 https://github.com/facebook/hhvm/issues/4797
		}

		$this->assertEquals( 'vstub2', $farm->getVariable( '$VERSION' ) );
	}

	/**
	 * Test memoisation of checkExistence().
	 *
	 * This test is mainly used to add code coverage; the assertion is tested elsewhere.
	 */
	function testMemoisationCheckExistence() {

		$this->farm->checkExistence();
		$this->assertTrue( $this->farm->checkExistence() );
	}

	/**
	 * Test memoisation of checkHostVariables().
	 *
	 * This test is mainly used to add code coverage; the assertion is tested elsewhere.
	 */
	function testMemoisationCheckHostVariables() {

		$this->farm->checkExistence();
		$this->assertTrue( $this->farm->checkHostVariables() );
	}

	/**
	 * Test cache of checkExistence().
	 *
	 * @medium
	 * @uses MediaWikiFarmConfiguration
	 * @uses AbstractMediaWikiFarmScript::rmdirr
	 */
	function testCacheExistence() {

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
		sleep( 2 );
		$this->assertTrue( touch( self::$wgMediaWikiFarmConfigDir . '/farms.php' ) );

		# Check the existence cache is understood as invalidated
		$farm = new MediaWikiFarm( 'a.testfarm-multiversion.example.org', null,
		                           self::$wgMediaWikiFarmConfigDir, self::$wgMediaWikiFarmCodeDir, self::$wgMediaWikiFarmCacheDir,
			                   array( 'EntryPoint' => 'index.php' )
			);
		$this->assertFalse( is_file( self::$wgMediaWikiFarmCacheDir . '/LocalSettings/a.testfarm-multiversion.example.org.php' ) );
	}

	/**
	 * Remove config files.
	 */
	static function tearDownAfterClass() {

		if( is_file( dirname( __FILE__ ) . '/data/config/deployments.php' ) ) {
			unlink( dirname( __FILE__ ) . '/data/config/deployments.php' );
		}
		if( is_file( dirname( __FILE__ ) . '/data/config/testdeploymentsfarmversions.php' ) ) {
			unlink( dirname( __FILE__ ) . '/data/config/testdeploymentsfarmversions.php' );
		}

		parent::tearDownAfterClass();
	}
}
