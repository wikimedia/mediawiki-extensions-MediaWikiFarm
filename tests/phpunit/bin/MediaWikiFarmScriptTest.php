<?php
/**
 * Class MediaWikiFarmScriptTest.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

require_once dirname( dirname( __FILE__ ) ) . '/MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/src/MediaWikiFarm.php';
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/src/bin/MediaWikiFarmScript.php';

/**
 * Tests about class Script.
 *
 * @group MediaWikiFarm
 */
class MediaWikiFarmScriptTest extends MediaWikiFarmTestCase {

	/** @var string Path to [farm]/bin/mwscript.php. */
	public static $mwscriptPath = '';

	/** @var string Short help displayed in case of error. */
	public static $shortHelp = '';

	/** @var string Long help displayed when requested. */
	public static $longHelp = '';

	/**
	 * Set up some "constants" to be used across the tests.
	 */
	public static function setUpBeforeClass() {

		parent::setUpBeforeClass();

		# Set test configuration parameters
		self::$mwscriptPath = $mwscriptPath = self::$wgMediaWikiFarmFarmDir . '/bin/mwscript.php';

		self::$shortHelp = <<<HELP

    Usage: php $mwscriptPath MediaWikiScript --wiki=hostname …

    Parameters:

      - MediaWikiScript: name of the script, e.g. "maintenance/runJobs.php"
      - hostname: hostname of the wiki, e.g. "mywiki.example.org"


HELP;

		self::$longHelp = <<<HELP

    Usage: php $mwscriptPath MediaWikiScript --wiki=hostname …

    Parameters:

      - MediaWikiScript: name of the script, e.g. "maintenance/runJobs.php"
      - hostname: hostname of the wiki, e.g. "mywiki.example.org"

    | Note simple names as "runJobs" will be converted to "maintenance/runJobs.php".
    |
    | For easier use, you can alias it in your shell:
    |
    |     alias mwscript='php $mwscriptPath'
    |
    | Return codes:
    | 0 = success
    | 1 = missing wiki (similar to HTTP 404)
    | 4 = user error, like a missing parameter (similar to HTTP 400)
    | 5 = internal error in farm configuration (similar to HTTP 500)


HELP;
	}

	/**
	 * Test construction.
	 *
	 * @covers MediaWikiFarmScript::__construct
	 * @covers AbstractMediaWikiFarmScript::__construct
	 */
	public function testConstruction() {

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 1, array( self::$mwscriptPath ) );

		$this->assertEquals( 1, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( array( self::$mwscriptPath ), $wgMediaWikiFarmScript->argv );
	}

	/**
	 * Test usage method.
	 *
	 * @covers MediaWikiFarmScript::usage
	 * @covers AbstractMediaWikiFarmScript::usage
	 * @uses MediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::__construct
	 */
	public function testUsage1() {

		$this->expectOutputString( self::$shortHelp );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 1, array( self::$mwscriptPath ) );

		$wgMediaWikiFarmScript->usage();
	}

	/**
	 * Test usage method.
	 *
	 * @covers MediaWikiFarmScript::usage
	 * @covers AbstractMediaWikiFarmScript::usage
	 * @uses MediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::__construct
	 */
	public function testUsage2() {

		$this->expectOutputString( self::$longHelp );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 1, array( self::$mwscriptPath ) );

		$wgMediaWikiFarmScript->usage( true );
	}

	/**
	 * Test usage method.
	 *
	 * @covers MediaWikiFarmScript::main
	 * @covers AbstractMediaWikiFarmScript::premain
	 * @uses MediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::usage
	 */
	public function testUsage3() {

		$this->expectOutputString( self::$longHelp );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 2, array( self::$mwscriptPath, '-h' ) );

		$this->assertFalse( $wgMediaWikiFarmScript->main() );

		$this->assertSame( 0, $wgMediaWikiFarmScript->status );
	}

	/**
	 * Test usage method.
	 *
	 * @covers MediaWikiFarmScript::main
	 * @covers AbstractMediaWikiFarmScript::premain
	 * @uses MediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::usage
	 */
	public function testUsage4() {

		$this->expectOutputString( self::$longHelp );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 2, array( self::$mwscriptPath, '--help' ) );

		$this->assertFalse( $wgMediaWikiFarmScript->main() );

		$this->assertSame( 0, $wgMediaWikiFarmScript->status );
	}

	/**
	 * Test export.
	 *
	 * @covers MediaWikiFarmScript::exportArguments
	 * @uses MediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @backupGlobals enabled
	 */
	public function testExport() {

		$this->backupAndSetGlobalVariable( 'argc', 0 );
		$this->backupAndSetGlobalVariable( 'argv', array() );
		$this->backupAndSetGlobalSubvariable( '_SERVER', 'argc', 0 );
		$this->backupAndSetGlobalSubvariable( '_SERVER', 'argv', array() );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 1, array( self::$mwscriptPath ) );
		$wgMediaWikiFarmScript->host = 'a.testfarm-multiversion.example.org';

		$wgMediaWikiFarmScript->exportArguments();

		$this->assertEquals( 1, $GLOBALS['argc'] );
		$this->assertEquals( array( self::$mwscriptPath ), $GLOBALS['argv'] );
		$this->assertEquals( 1, $_SERVER['argc'] );
		$this->assertEquals( array( self::$mwscriptPath ), $_SERVER['argv'] );
	}

	/**
	 * Test getParam.
	 *
	 * @covers MediaWikiFarmScript::getParam
	 * @uses MediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::__construct
	 */
	public function testGetParam() {

		$parameters = array( self::$mwscriptPath, '--wiki=a.testfarm-multiversion.example.org', 'showJobs', '--test', 'abc' );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 5, $parameters );

		$valueParamWiki = $wgMediaWikiFarmScript->getParam( 'wiki', false );
		$this->assertEquals( 'a.testfarm-multiversion.example.org', $valueParamWiki );
		$this->assertEquals( 5, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( $parameters, $wgMediaWikiFarmScript->argv );

		$valueParamTest = $wgMediaWikiFarmScript->getParam( 'test', false );
		$this->assertEquals( 'abc', $valueParamTest );
		$this->assertEquals( 5, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( $parameters, $wgMediaWikiFarmScript->argv );

		$valueParamScript = $wgMediaWikiFarmScript->getParam( 2, false );
		$this->assertEquals( 'showJobs', $valueParamScript );
		$this->assertEquals( 5, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( $parameters, $wgMediaWikiFarmScript->argv );

		$valueParamOutOfBounds = $wgMediaWikiFarmScript->getParam( 5, false );
		$this->assertNull( $valueParamOutOfBounds );
		$this->assertEquals( 5, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( $parameters, $wgMediaWikiFarmScript->argv );

		$valueParammwscript = $wgMediaWikiFarmScript->getParam( 0 );
		$this->assertEquals( self::$mwscriptPath, $valueParammwscript );
		$this->assertEquals( 4, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( array_slice( $parameters, 1 ), $wgMediaWikiFarmScript->argv );
	}

	/**
	 * Test load.
	 *
	 * @ backupGlobals enabled
	 * @ covers MediaWikiFarmScript::load
	 * @ uses MediaWikiFarmScript::__construct
	 * @ uses AbstractMediaWikiFarmScript::__construct
	 */
	/*
	public function testLoad() {

		global $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir, $wgMediaWikiFarmCacheDir;

		self::$mwscriptPath = self::$wgMediaWikiFarmFarmDir . '/bin/mwscript.php';
		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 3, array( self::$mwscriptPath, '--wiki=a.testfarm-multiversion.example.org', 'showJobs' ) );

		$wgMediaWikiFarmScript->load();

		$this->assertTrue( true );
	}
	*/

	/**
	 * Test missing '--wiki' argument.
	 *
	 * @covers MediaWikiFarmScript::main
	 * @uses MediaWikiFarmScript::__construct
	 * @uses MediaWikiFarmScript::getParam
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::usage
	 * @uses AbstractMediaWikiFarmScript::premain
	 */
	public function testMissingArgumentWiki() {

		$this->expectOutputString( self::$shortHelp );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 2, array( self::$mwscriptPath, 'showJobs' ) );

		$wgMediaWikiFarmScript->main();

		$this->assertEquals( 4, $wgMediaWikiFarmScript->status );
	}

	/**
	 * Test missing Script argument.
	 *
	 * @covers MediaWikiFarmScript::main
	 * @uses MediaWikiFarmScript::__construct
	 * @uses MediaWikiFarmScript::getParam
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::usage
	 * @uses AbstractMediaWikiFarmScript::premain
	 */
	public function testMissingArgumentScript() {

		$this->expectOutputString( self::$shortHelp );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 2, array( self::$mwscriptPath, '--wiki=a.testfarm-multiversion.example.org' ) );

		$wgMediaWikiFarmScript->main();

		$this->assertEquals( 4, $wgMediaWikiFarmScript->status );
		$this->assertSame( '', $wgMediaWikiFarmScript->script );
		$this->assertEquals( 1, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( array( self::$mwscriptPath ), $wgMediaWikiFarmScript->argv );
	}

	/**
	 * Test missing host.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarmScript::main
	 * @uses MediaWikiFarmScript::__construct
	 * @uses MediaWikiFarmScript::getParam
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::premain
	 * @uses MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::getConfigDir
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::getFarmConfiguration
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::compileConfiguration
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::prepareLog
	 * @uses MediaWikiFarm::issueLog
	 * @uses MediaWikiFarmConfiguration::__construct
	 * @uses MediaWikiFarmConfiguration::populateSettings
	 * @uses MediaWikiFarmConfiguration::activateExtensions
	 * @uses MediaWikiFarmConfiguration::detectComposer
	 * @uses MediaWikiFarmConfiguration::setEnvironment
	 * @uses MediaWikiFarmConfiguration::sortExtensions
	 * @uses MediaWikiFarmConfiguration::getConfiguration
	 * @uses MediaWikiFarmUtils
	 */
	public function testMissingHost() {

		$this->backupAndUnsetGlobalVariable( 'wgMediaWikiFarm' );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmSyslog', false );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 3,
			array( self::$mwscriptPath, '--wiki=c.testfarm-monoversion-with-file-variable-without-version.example.org', 'showJobs' )
		);

		$wgMediaWikiFarmScript->main();

		$this->assertEquals( 1, $wgMediaWikiFarmScript->status );
		$this->assertEquals( 'c.testfarm-monoversion-with-file-variable-without-version.example.org', $wgMediaWikiFarmScript->host );
		$this->assertEquals( 'maintenance/showJobs.php', $wgMediaWikiFarmScript->script );
		$this->assertEquals( 1, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( array( 'maintenance/showJobs.php' ), $wgMediaWikiFarmScript->argv );
	}

	/**
	 * Test internal problem.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarmScript::main
	 * @uses MediaWikiFarmScript::__construct
	 * @uses MediaWikiFarmScript::getParam
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::premain
	 * @uses MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::prepareLog
	 * @uses MediaWikiFarm::issueLog
	 * @uses MediaWikiFarmUtils
	 */
	public function testInternalProblem() {

		$this->backupAndUnsetGlobalVariable( 'wgMediaWikiFarm' );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmSyslog', false );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 3,
			array( self::$mwscriptPath, '--wiki=a.testfarm-with-badly-formatted-file-variable.example.org', 'showJobs' )
		);

		$wgMediaWikiFarmScript->main();

		$this->assertEquals( 5, $wgMediaWikiFarmScript->status );
		$this->assertEquals( 'a.testfarm-with-badly-formatted-file-variable.example.org', $wgMediaWikiFarmScript->host );
		$this->assertEquals( 'maintenance/showJobs.php', $wgMediaWikiFarmScript->script );
		$this->assertEquals( 1, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( array( 'maintenance/showJobs.php' ), $wgMediaWikiFarmScript->argv );
	}

	/**
	 * Test missing script.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarmScript::main
	 * @uses MediaWikiFarmScript::__construct
	 * @uses MediaWikiFarmScript::getParam
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::premain
	 * @uses MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::compileConfiguration
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::prepareLog
	 * @uses MediaWikiFarm::issueLog
	 * @uses MediaWikiFarm::getConfigDir
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::getFarmConfiguration
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarmConfiguration::__construct
	 * @uses MediaWikiFarmConfiguration::populateSettings
	 * @uses MediaWikiFarmConfiguration::activateExtensions
	 * @uses MediaWikiFarmConfiguration::detectComposer
	 * @uses MediaWikiFarmConfiguration::setEnvironment
	 * @uses MediaWikiFarmConfiguration::sortExtensions
	 * @uses MediaWikiFarmConfiguration::getConfiguration
	 * @uses MediaWikiFarmUtils::arrayMerge
	 * @uses MediaWikiFarmUtils
	 */
	public function testMissingScript() {

		$this->backupAndUnsetGlobalVariable( 'wgMediaWikiFarm' );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmSyslog', false );
		$this->backupAndSetGlobalVariable( 'IP', self::$wgMediaWikiFarmCodeDir . '/vstub' );
		chdir( self::$wgMediaWikiFarmCodeDir . '/vstub' );

		$this->expectOutputString( "Script not found.\n" );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 3, array( self::$mwscriptPath, '--wiki=a.testfarm-monoversion.example.org', 'veryMissingScript' ) );

		$wgMediaWikiFarmScript->main();

		$this->assertEquals( 4, $wgMediaWikiFarmScript->status );
		$this->assertEquals( 'a.testfarm-monoversion.example.org', $wgMediaWikiFarmScript->host );
		$this->assertEquals( 'maintenance/veryMissingScript.php', $wgMediaWikiFarmScript->script );
		$this->assertEquals( 1, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( array( 'maintenance/veryMissingScript.php' ), $wgMediaWikiFarmScript->argv );
	}

	/**
	 * Test successful loading.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarmScript::main
	 * @covers AbstractMediaWikiFarmScript::premain
	 * @uses MediaWikiFarmScript::__construct
	 * @uses MediaWikiFarmScript::getParam
	 * @uses MediaWikiFarmScript::exportArguments
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @uses MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::compileConfiguration
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::prepareLog
	 * @uses MediaWikiFarm::issueLog
	 * @uses MediaWikiFarm::getConfigDir
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::getFarmConfiguration
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarmConfiguration::__construct
	 * @uses MediaWikiFarmConfiguration::populateSettings
	 * @uses MediaWikiFarmConfiguration::activateExtensions
	 * @uses MediaWikiFarmConfiguration::detectComposer
	 * @uses MediaWikiFarmConfiguration::setEnvironment
	 * @uses MediaWikiFarmConfiguration::sortExtensions
	 * @uses MediaWikiFarmConfiguration::getConfiguration
	 * @uses MediaWikiFarmUtils
	 */
	public function testSuccessfulLoading() {

		global $IP;

		$this->backupAndUnsetGlobalVariable( 'wgMediaWikiFarm' );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', self::$wgMediaWikiFarmCodeDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmSyslog', false );
		$this->backupGlobalVariable( 'argc' );
		$this->backupGlobalVariable( 'argv' );
		$this->backupGlobalSubvariable( '_SERVER', 'argc' );
		$this->backupGlobalSubvariable( '_SERVER', 'argv' );
		$IP = self::$wgMediaWikiFarmCodeDir . '/vstub';

		$this->expectOutputString( <<<OUTPUT

Wiki:    testfarm-multiversion-subdirectories.example.org/a (wikiID: atestfarm; suffix: testfarm)
Version: vstub: $IP
Script:  maintenance/showJobs.php


OUTPUT
		);

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 3, array( self::$mwscriptPath, '--wiki=testfarm-multiversion-subdirectories.example.org/a', 'showJobs' ) );

		$wgMediaWikiFarmScript->main();

		$this->assertSame( 0, $wgMediaWikiFarmScript->status );
		$this->assertEquals( 'testfarm-multiversion-subdirectories.example.org', $wgMediaWikiFarmScript->host );
		$this->assertEquals( '/a', $wgMediaWikiFarmScript->path );
		$this->assertEquals( 'maintenance/showJobs.php', $wgMediaWikiFarmScript->script );
		$this->assertEquals( 1, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( array( 'maintenance/showJobs.php' ), $wgMediaWikiFarmScript->argv );
	}

	/**
	 * Test restInPeace.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarmScript::restInPeace
	 * @uses MediaWikiFarmScript::main
	 * @uses MediaWikiFarmScript::__construct
	 * @uses MediaWikiFarmScript::getParam
	 * @uses MediaWikiFarmScript::exportArguments
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::premain
	 * @uses MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::updateVersionAfterMaintenance
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::compileConfiguration
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::prepareLog
	 * @uses MediaWikiFarm::issueLog
	 * @uses MediaWikiFarm::getConfigDir
	 * @uses MediaWikiFarm::getConfiguration
	 * @uses MediaWikiFarm::getFarmConfiguration
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarmConfiguration::__construct
	 * @uses MediaWikiFarmConfiguration::populateSettings
	 * @uses MediaWikiFarmConfiguration::activateExtensions
	 * @uses MediaWikiFarmConfiguration::detectComposer
	 * @uses MediaWikiFarmConfiguration::setEnvironment
	 * @uses MediaWikiFarmConfiguration::sortExtensions
	 * @uses MediaWikiFarmConfiguration::getConfiguration
	 * @uses MediaWikiFarmUtils
	 */
	public function testRestInPeace() {

		global $IP;

		$this->backupAndUnsetGlobalVariable( 'wgMediaWikiFarm' );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmSyslog', false );
		$this->backupGlobalVariable( 'argc' );
		$this->backupGlobalVariable( 'argv' );
		$this->backupGlobalSubvariable( '_SERVER', 'argc' );
		$this->backupGlobalSubvariable( '_SERVER', 'argv' );
		$this->backupAndSetGlobalVariable( 'IP', self::$wgMediaWikiFarmCodeDir . '/vstub' );
		chdir( self::$wgMediaWikiFarmCodeDir . '/vstub' );

		$this->expectOutputString( <<<OUTPUT

Wiki:    a.testfarm-monoversion.example.org (wikiID: atestfarm; suffix: testfarm)
Version: current: $IP
Script:  maintenance/showJobs.php


OUTPUT
		);

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 3, array( self::$mwscriptPath, '--wiki=a.testfarm-monoversion.example.org', 'showJobs' ) );

		$wgMediaWikiFarmScript->main();
		$wgMediaWikiFarmScript->restInPeace();

		$this->assertSame( 0, $wgMediaWikiFarmScript->status );

		# For coverage
		unset( $GLOBALS['wgMediaWikiFarm'] );
		$wgMediaWikiFarmScript->restInPeace();
	}

	/**
	 * Test routines for copying and deleting directories.
	 *
	 * @covers MediaWikiFarmScript::copyr
	 * @covers MediaWikiFarmScript::rmdirr
	 * @uses MediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::__construct
	 */
	public function testRecursiveCopyAndDelete() {

		$destDir = dirname( dirname( __FILE__ ) ) . '/data/copie';

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 3, array( self::$mwscriptPath, '--wiki=a.testfarm-monoversion.example.org', 'showJobs' ) );

		MediaWikiFarmScript::copyr(
			self::$wgMediaWikiFarmCodeDir . '/vstub', $destDir,
			true, array( '/skins/TestSkinEmpty', 'TestSkinRequireOnce' ) );
		MediaWikiFarmScript::copyr( self::$wgMediaWikiFarmCodeDir . '/vstub/includes/DefaultSettings.php', $destDir . '/newdir', true );
		$this->assertTrue( is_file( $destDir . '/includes/DefaultSettings.php' ) );
		$this->assertTrue( is_file( $destDir . '/newdir/DefaultSettings.php' ) );
		$this->assertFalse( file_exists( $destDir . '/skins/TestSkinEmpty' ) );
		$this->assertFalse( file_exists( $destDir . '/skins/TestSkinRequireOnce' ) );

		MediaWikiFarmScript::rmdirr( $destDir . '/includes/DefaultSettings.php', true );
		$this->assertFalse( file_exists( $destDir . '/includes/DefaultSettings.php' ) );

		MediaWikiFarmScript::copyr(
			self::$wgMediaWikiFarmCodeDir . '/vstub', $destDir,
			true, array(), array( '/', '/includes', '/includes/.*' ) );
		$this->assertTrue( is_file( $destDir . '/includes/DefaultSettings.php' ) );

		MediaWikiFarmScript::rmdirr( $destDir, true );
		$this->assertFalse( file_exists( $destDir ) );
	}

	/**
	 * Test routines for copying and deleting directories.
	 *
	 * @todo this test (and the code?) should be improved: currently the executed sub-processes
	 *       read the real farm configuration indirectly given by /config/MediaWikiFarmConfiguration.php
	 *       instead of the config given in /tests/phpunit/data/config2. Consequently the results
	 *       could be wrong because the config is not controlled by unit tests.
	 *
	 * @covers MediaWikiFarmList
	 * @covers MediaWikiFarmScript::main
	 * @covers MediaWikiFarmScript::executeMulti
	 * @uses MediaWikiFarmScript::main
	 * @uses MediaWikiFarmScript::copyr
	 * @uses MediaWikiFarmScript::rmdirr
	 * @uses MediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::getParam
	 * @uses AbstractMediaWikiFarmScript::premain
	 * @uses MediaWikiFarmUtils
	 */
	public function testBatch() {

		$this->backupAndUnsetGlobalVariable( 'wgMediaWikiFarm' );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfig2Dir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', self::$wgMediaWikiFarmCodeDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmSyslog', false );

		$binary = MediaWikiFarmScript::binary();
		$mwscriptPath = self::$mwscriptPath;

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 3, array( self::$mwscriptPath, '--wiki=*', 'showJobs' ) );

		$this->assertFalse( $wgMediaWikiFarmScript->main() );
	}
}
