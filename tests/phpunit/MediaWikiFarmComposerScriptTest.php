<?php
/**
 * Class MediaWikiFarmComposerScriptTest.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0, or (at your option) any later version.
 * @license AGPL-3.0+ GNU Affero General Public License v3.0, or (at your option) any later version.
 */

require_once 'MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/MediaWikiFarm.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/MediaWikiFarmComposerScript.php';

/**
 * Tests about class Composer Script.
 *
 * @group MediaWikiFarm
 */
class MediaWikiFarmComposerScriptTest extends MediaWikiFarmTestCase {

	/** @var string Path to [farm]/bin/mwscript.php. */
	public static $mwcomposerPath = '';

	/** @var string Short help displayed in case of error. */
	public static $shortHelp = '';

	/** @var string Long help displayed when requested. */
	public static $longHelp = '';

	/**
	 * Set up some "constants" to be used accross the tests.
	 */
	static function setUpBeforeClass() {

		parent::setUpBeforeClass();

		# Set test configuration parameters
		self::$mwcomposerPath = $mwcomposerPath = dirname( dirname( dirname( __FILE__ ) ) ) . '/bin/mwcomposer.php';

		self::$shortHelp = <<<HELP

    Usage: php $mwcomposerPath --wiki=hostname …

    Parameters:

      - hostname: hostname of the wiki, e.g. "mywiki.example.org"


HELP;

		self::$longHelp = <<<HELP

    Usage: php $mwcomposerPath --wiki=hostname …

    Parameters:

      - hostname: hostname of the wiki, e.g. "mywiki.example.org"

    | For easier use, you can alias it in your shell:
    |
    |     alias mwcomposer='php $mwcomposerPath'
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
	 * @covers MediaWikiFarmComposerScript::__construct
	 * @uses AbstractMediaWikiFarmScript::__construct
	 */
	function testConstruction() {

		$wgMediaWikiFarmComposerScript = new MediaWikiFarmComposerScript( 1, array( self::$mwcomposerPath ) );

		$this->assertEquals( 1, $wgMediaWikiFarmComposerScript->argc );
		$this->assertEquals( array( self::$mwcomposerPath ), $wgMediaWikiFarmComposerScript->argv );
	}

	/**
	 * Test usage method.
	 *
	 * @covers MediaWikiFarmComposerScript::main
	 * @uses MediaWikiFarmComposerScript::__construct
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::usage
	 * @uses AbstractMediaWikiFarmScript::premain
	 */
	function testUsage() {

		$this->expectOutputString( self::$longHelp );

		$wgMediaWikiFarmComposerScript = new MediaWikiFarmComposerScript( 2, array( self::$mwcomposerPath, '-h' ) );

		$this->assertFalse( $wgMediaWikiFarmComposerScript->main() );

		$this->assertEquals( 0, $wgMediaWikiFarmComposerScript->status );
	}

	/**
	 * Test missing '--wiki' argument.
	 *
	 * @covers MediaWikiFarmComposerScript::main
	 * @uses MediaWikiFarmComposerScript::__construct
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::usage
	 * @uses AbstractMediaWikiFarmScript::premain
	 * @uses AbstractMediaWikiFarmScript::getParam
	 */
	function testMissingArgumentWiki() {

		$this->expectOutputString( self::$shortHelp );

		$wgMediaWikiFarmComposerScript = new MediaWikiFarmComposerScript( 1, array( self::$mwcomposerPath ) );

		$wgMediaWikiFarmComposerScript->main();

		$this->assertEquals( 4, $wgMediaWikiFarmComposerScript->status );
	}

	/**
	 * Test missing host.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarmComposerScript::main
	 * @uses MediaWikiFarmComposerScript::__construct
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::premain
	 * @uses AbstractMediaWikiFarmScript::getParam
	 * @uses MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::compileConfiguration
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::populateSettings
	 * @uses MediaWikiFarm::activateExtensions
	 * @uses MediaWikiFarm::detectComposer
	 * @uses MediaWikiFarm::setEnvironment
	 * @uses MediaWikiFarm::sortExtensions
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::prepareLog
	 * @uses MediaWikiFarm::issueLog
	 */
	function testMissingHost() {

		$this->backupAndUnsetGlobalVariable( 'wgMediaWikiFarm' );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmSyslog', false );

		$wgMediaWikiFarmComposerScript = new MediaWikiFarmComposerScript( 2,
			array( self::$mwcomposerPath, '--wiki=c.testfarm-monoversion-with-file-variable-without-version.example.org' )
		);

		$wgMediaWikiFarmComposerScript->main();

		$this->assertEquals( 1, $wgMediaWikiFarmComposerScript->status );
		$this->assertEquals( 'c.testfarm-monoversion-with-file-variable-without-version.example.org', $wgMediaWikiFarmComposerScript->host );
		$this->assertEquals( 0, $wgMediaWikiFarmComposerScript->argc );
		$this->assertEquals( array(), $wgMediaWikiFarmComposerScript->argv );
	}

	/**
	 * Test internal problem.
	 *
	 * @backupGlobals enabled
	 * @covers MediaWikiFarmComposerScript::main
	 * @uses MediaWikiFarmComposerScript::__construct
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::premain
	 * @uses AbstractMediaWikiFarmScript::getParam
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
	 */
	function testInternalProblem() {

		$this->backupAndUnsetGlobalVariable( 'wgMediaWikiFarm' );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmSyslog', false );

		$wgMediaWikiFarmComposerScript = new MediaWikiFarmComposerScript( 2,
			array( self::$mwcomposerPath, '--wiki=a.testfarm-with-badly-formatted-file-variable.example.org' )
		);

		$wgMediaWikiFarmComposerScript->main();

		$this->assertEquals( 5, $wgMediaWikiFarmComposerScript->status );
		$this->assertEquals( 'a.testfarm-with-badly-formatted-file-variable.example.org', $wgMediaWikiFarmComposerScript->host );
		$this->assertEquals( 0, $wgMediaWikiFarmComposerScript->argc );
		$this->assertEquals( array(), $wgMediaWikiFarmComposerScript->argv );
	}

	/**
	 * Test successful loading.
	 *
	 * @large
	 * @backupGlobals enabled
	 * @covers MediaWikiFarmComposerScript::main
	 * @covers MediaWikiFarmComposerScript::composer2mediawiki
	 * @uses MediaWikiFarmComposerScript::__construct
	 * @uses MediaWikiFarmComposerScript::getParam
	 * @uses MediaWikiFarmComposerScript::exportArguments
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::premain
	 * @uses AbstractMediaWikiFarmScript::copyr
	 * @uses AbstractMediaWikiFarmScript::rmdirr
	 * @uses MediaWikiFarm::load
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::checkExistence
	 * @uses MediaWikiFarm::checkHostVariables
	 * @uses MediaWikiFarm::setVersion
	 * @uses MediaWikiFarm::setOtherVariables
	 * @uses MediaWikiFarm::compileConfiguration
	 * @uses MediaWikiFarm::isLocalSettingsFresh
	 * @uses MediaWikiFarm::populateSettings
	 * @uses MediaWikiFarm::activateExtensions
	 * @uses MediaWikiFarm::detectComposer
	 * @uses MediaWikiFarm::setEnvironment
	 * @uses MediaWikiFarm::sortExtensions
	 * @uses MediaWikiFarm::getConfigFile
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::arrayMerge
	 * @uses MediaWikiFarm::composerKey
	 * @uses MediaWikiFarm::isMediaWiki
	 * @uses MediaWikiFarm::prepareLog
	 * @uses MediaWikiFarm::issueLog
	 */
	function testSuccessfulLoading() {

		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarm', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', dirname( __FILE__ ) . '/data/mediawiki' );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmSyslog', false );

		$this->backupGlobalVariable( 'argc' );
		$this->backupGlobalVariable( 'argv' );
		$this->backupGlobalSubvariable( '_SERVER', 'argc' );
		$this->backupGlobalSubvariable( '_SERVER', 'argv' );

		$this->expectOutputString( '' );

		AbstractMediaWikiFarmScript::copyr( self::$wgMediaWikiFarmCodeDir . '/vstub', self::$wgMediaWikiFarmCodeDir . '/vstub3' );
		chdir( self::$wgMediaWikiFarmCodeDir . '/vstub3' );

		$wgMediaWikiFarmComposerScript = new MediaWikiFarmComposerScript( 3, array( self::$mwcomposerPath, '--wiki=y.testfarm-multiversion.example.org', '-q' ) );

		# This function is the main procedure, it runs Composer in background 7 times so it can take 20-25 seconds
		$wgMediaWikiFarmComposerScript->main();

		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/vendor' ) );
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/vendor/composer' ) );
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/vendor/composerc4538db9' ) ); // SemanticMediaWiki
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/vendor/composer56cb950e' ) ); // PageForms
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/vendor/composer2a684956' ) ); // SemanticFormsSelect
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/vendor/composer/installers' ) );
		$this->assertTrue( is_file( self::$wgMediaWikiFarmCodeDir . '/vstub3/vendor/autoload.php' ) );
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/skins/chameleon' ) );
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/extensions/PageForms' ) );
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/extensions/SemanticFormsSelect' ) );
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/extensions/SemanticMediaWiki' ) );

		AbstractMediaWikiFarmScript::rmdirr( self::$wgMediaWikiFarmCodeDir . '/vstub3' );
	}
	/**/
}
