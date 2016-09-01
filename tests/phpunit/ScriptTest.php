<?php

require_once 'MediaWikiFarmTestCase.php';

/**
 * Tests about class Script.
 *
 * @group MediaWikiFarm
 */
class MediaWikiFarmScriptTest extends MediaWikiFarmTestCase {

	/** @var string Path to [farm]/bin/mwscript.php. */
	static $mwscriptPath = '';

	/** @var string Short help displayed in case of error. */
	static $shortHelp = '';

	/** @var string Long help displayed when requested. */
	static $longHelp = '';

	/**
	 * Symbol for MediaWikiFarm_readYAML, which is normally loaded just-in-time in the main class.
	 */
	static function setUpBeforeClass() {

		parent::setUpBeforeClass();

		# Set test configuration parameters
		self::$mwscriptPath = $mwscriptPath = dirname( dirname( dirname( __FILE__ ) ) ) . '/bin/mwscript.php';

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


HELP;
	}

	/**
	 * Test construction.
	 *
	 * @covers MediaWikiFarmScript::__construct
	 */
	function testConstruction() {

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 1, array( self::$mwscriptPath ) );

		$this->assertEquals( 1, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( array( self::$mwscriptPath ), $wgMediaWikiFarmScript->argv );
	}

	/**
	 * Test usage method.
	 *
	 * @covers MediaWikiFarmScript::usage
	 * @uses MediaWikiFarmScript::__construct
	 */
	function testUsage1() {

		$this->expectOutputString( self::$shortHelp );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 1, array( self::$mwscriptPath ) );

		$wgMediaWikiFarmScript->usage();
	}

	/**
	 * Test usage method.
	 *
	 * @covers MediaWikiFarmScript::usage
	 * @uses MediaWikiFarmScript::__construct
	 */
	function testUsage2() {

		$this->expectOutputString( self::$longHelp );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 1, array( self::$mwscriptPath ) );

		$wgMediaWikiFarmScript->usage( false );
	}

	/**
	 * Test usage method.
	 *
	 * @covers MediaWikiFarmScript::main
	 * @uses MediaWikiFarmScript::__construct
	 * @uses MediaWikiFarmScript::usage
	 */
	function testUsage3() {

		$this->expectOutputString( self::$longHelp );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 2, array( self::$mwscriptPath, '-h' ) );

		$wgMediaWikiFarmScript->main();

		$this->assertEquals( 204, $wgMediaWikiFarmScript->status );
	}

	/**
	 * Test usage method.
	 *
	 * @covers MediaWikiFarmScript::main
	 * @uses MediaWikiFarmScript::__construct
	 * @uses MediaWikiFarmScript::usage
	 */
	function testUsage4() {

		$this->expectOutputString( self::$longHelp );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 2, array( self::$mwscriptPath, '--help' ) );

		$wgMediaWikiFarmScript->main();

		$this->assertEquals( 204, $wgMediaWikiFarmScript->status );
	}

	/**
	 * Test export.
	 *
	 * @covers MediaWikiFarmScript::exportArguments
	 * @uses MediaWikiFarmScript::__construct
	 * @backupGlobals enabled
	 */
	function testExport() {

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
	 */
	function testGetParam() {

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 5, array( self::$mwscriptPath, '--wiki=a.testfarm-multiversion.example.org', 'showJobs', '--test', 'abc' ) );

		$valueParamWiki = $wgMediaWikiFarmScript->getParam( 'wiki', false );
		$this->assertEquals( 'a.testfarm-multiversion.example.org', $valueParamWiki );
		$this->assertEquals( 5, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( array( self::$mwscriptPath, '--wiki=a.testfarm-multiversion.example.org', 'showJobs', '--test', 'abc' ), $wgMediaWikiFarmScript->argv );

		$valueParamTest = $wgMediaWikiFarmScript->getParam( 'test', false );
		$this->assertEquals( 'abc', $valueParamTest );
		$this->assertEquals( 5, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( array( self::$mwscriptPath, '--wiki=a.testfarm-multiversion.example.org', 'showJobs', '--test', 'abc' ), $wgMediaWikiFarmScript->argv );

		$valueParamScript = $wgMediaWikiFarmScript->getParam( 2, false );
		$this->assertEquals( 'showJobs', $valueParamScript );
		$this->assertEquals( 5, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( array( self::$mwscriptPath, '--wiki=a.testfarm-multiversion.example.org', 'showJobs', '--test', 'abc' ), $wgMediaWikiFarmScript->argv );

		$valueParamOutOfBounds = $wgMediaWikiFarmScript->getParam( 5, false );
		$this->assertNull( $valueParamOutOfBounds );
		$this->assertEquals( 5, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( array( self::$mwscriptPath, '--wiki=a.testfarm-multiversion.example.org', 'showJobs', '--test', 'abc' ), $wgMediaWikiFarmScript->argv );

		$valueParammwscript = $wgMediaWikiFarmScript->getParam( 0 );
		$this->assertEquals( self::$mwscriptPath, $valueParammwscript );
		$this->assertEquals( 4, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( array( '--wiki=a.testfarm-multiversion.example.org', 'showJobs', '--test', 'abc' ), $wgMediaWikiFarmScript->argv );
	}

	/**
	 * Test load.
	 *
	 * @ backupGlobals enabled
	 * @ covers MediaWikiFarmScript::load
	 * @ uses MediaWikiFarmScript::__construct
	 *
	function testLoad() {

		global $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir, $wgMediaWikiFarmCacheDir;

		self::$mwscriptPath = dirname( dirname( dirname( __FILE__ ) ) ) . '/bin/mwscript.php';
		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 3, array( self::$mwscriptPath, '--wiki=a.testfarm-multiversion.example.org', 'showJobs' ) );

		$wgMediaWikiFarmScript->load();

		$this->assertTrue( true );
	}

	/**
	 * Test missing '--wiki' argument.
	 *
	 * @covers MediaWikiFarmScript::main
	 * @uses MediaWikiFarmScript::__construct
	 * @uses MediaWikiFarmScript::getParam
	 * @uses MediaWikiFarmScript::usage
	 */
	function testMissingArgumentWiki() {

		$this->expectOutputString( self::$shortHelp );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 2, array( self::$mwscriptPath, 'showJobs' ) );

		$wgMediaWikiFarmScript->main();

		$this->assertEquals( 400, $wgMediaWikiFarmScript->status );
	}

	/**
	 * Test missing Script argument.
	 *
	 * @covers MediaWikiFarmScript::main
	 * @uses MediaWikiFarmScript::__construct
	 * @uses MediaWikiFarmScript::getParam
	 * @uses MediaWikiFarmScript::usage
	 */
	function testMissingArgumentScript() {

		$this->expectOutputString( self::$shortHelp );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 2, array( self::$mwscriptPath, '--wiki=a.testfarm-multiversion.example.org' ) );

		$wgMediaWikiFarmScript->main();

		$this->assertEquals( 400, $wgMediaWikiFarmScript->status );
		$this->assertNull( $wgMediaWikiFarmScript->script );
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
	 * @uses MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::readFile
	 */
	function testMissingHost() {

		$this->backupAndUnsetGlobalVariable( 'wgMediaWikiFarm' );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 3, array( self::$mwscriptPath, '--wiki=c.testfarm-monoversion-with-file-variable-without-version.example.org', 'showJobs' ) );

		$wgMediaWikiFarmScript->main();

		$this->assertEquals( 404, $wgMediaWikiFarmScript->status );
		$this->assertEquals( 'c.testfarm-monoversion-with-file-variable-without-version.example.org', $wgMediaWikiFarmScript->host );
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
	 * @uses MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::readFile
	 */
	function testMissingScript() {

		$this->backupAndUnsetGlobalVariable( 'wgMediaWikiFarm' );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );

		$this->expectOutputString( "Script not found.\n" );

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 3, array( self::$mwscriptPath, '--wiki=a.testfarm-monoversion.example.org', 'veryMissingScript' ) );

		$wgMediaWikiFarmScript->main();

		$this->assertEquals( 400, $wgMediaWikiFarmScript->status );
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
	 * @uses MediaWikiFarmScript::__construct
	 * @uses MediaWikiFarmScript::getParam
	 * @uses MediaWikiFarmScript::exportArguments
	 * @uses MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::readFile
	 */
	function testSuccessfulLoading() {

		global $IP;

		$this->backupAndUnsetGlobalVariable( 'wgMediaWikiFarm' );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupGlobalVariable( 'argc' );
		$this->backupGlobalVariable( 'argv' );
		$this->backupGlobalSubvariable( '_SERVER', 'argc' );
		$this->backupGlobalSubvariable( '_SERVER', 'argv' );

		$this->expectOutputString( <<<OUTPUT

Wiki:    a.testfarm-monoversion.example.org (wikiID: atestfarm; suffix: testfarm)
Version: current: $IP
Script:  maintenance/showJobs.php


OUTPUT
		);

		$wgMediaWikiFarmScript = new MediaWikiFarmScript( 3, array( self::$mwscriptPath, '--wiki=a.testfarm-monoversion.example.org', 'showJobs' ) );

		$wgMediaWikiFarmScript->main();

		$this->assertEquals( 200, $wgMediaWikiFarmScript->status );
		$this->assertEquals( 'a.testfarm-monoversion.example.org', $wgMediaWikiFarmScript->host );
		$this->assertEquals( 'maintenance/showJobs.php', $wgMediaWikiFarmScript->script );
		$this->assertEquals( 1, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( array( 'maintenance/showJobs.php' ), $wgMediaWikiFarmScript->argv );
	}

	/**
	 * 
	 *
	 * @covers MediaWikiFarmScript::
	 * @uses MediaWikiFarmScript::__construct
	 *
	function test() {

	}
	/**/
}
