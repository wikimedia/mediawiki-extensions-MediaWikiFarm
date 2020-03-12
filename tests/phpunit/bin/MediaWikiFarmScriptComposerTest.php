<?php
/**
 * Class MediaWikiFarmScriptComposerTest.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

require_once dirname( dirname( __FILE__ ) ) . '/MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/src/MediaWikiFarm.php';
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/src/bin/MediaWikiFarmScriptComposer.php';

/**
 * Tests about class Composer Script.
 *
 * @group MediaWikiFarm
 */
class MediaWikiFarmScriptComposerTest extends MediaWikiFarmTestCase {

	/** @var string Path to [farm]/bin/mwscript.php. */
	public static $mwcomposerPath = '';

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
		self::$mwcomposerPath = $mwcomposerPath = self::$wgMediaWikiFarmFarmDir . '/bin/mwcomposer.php';

		self::$shortHelp = <<<HELP

    Usage: php $mwcomposerPath …

    You must be inside a Composer-managed MediaWiki directory.

    Parameters: regular Composer parameters


HELP;

		self::$longHelp = <<<HELP

    Usage: php $mwcomposerPath …

    You must be inside a Composer-managed MediaWiki directory.

    Parameters: regular Composer parameters

    | For easier use, you can alias it in your shell:
    |
    |     alias mwcomposer='php $mwcomposerPath'
    |
    | Return codes:
    | 0 = success
    | 4 = user error, like a missing parameter (similar to HTTP 400)
    | 5 = internal error in farm configuration (similar to HTTP 500)


HELP;
	}

	/**
	 * Test construction.
	 *
	 * @covers MediaWikiFarmScriptComposer::__construct
	 * @uses AbstractMediaWikiFarmScript::__construct
	 */
	public function testConstruction() {

		$wgMediaWikiFarmScriptComposer = new MediaWikiFarmScriptComposer( 1, array( self::$mwcomposerPath ) );

		$this->assertEquals( 1, $wgMediaWikiFarmScriptComposer->argc );
		$this->assertEquals( array( self::$mwcomposerPath ), $wgMediaWikiFarmScriptComposer->argv );
	}

	/**
	 * Test usage method.
	 *
	 * @covers MediaWikiFarmScriptComposer::main
	 * @uses MediaWikiFarmScriptComposer::__construct
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::usage
	 * @uses AbstractMediaWikiFarmScript::premain
	 */
	public function testUsage() {

		$this->expectOutputString( self::$longHelp );

		$wgMediaWikiFarmScriptComposer = new MediaWikiFarmScriptComposer( 2, array( self::$mwcomposerPath, '-h' ) );

		$this->assertFalse( $wgMediaWikiFarmScriptComposer->main() );

		$this->assertSame( 0, $wgMediaWikiFarmScriptComposer->status );
	}

	/**
	 * Test when we are not in a Composer-managed MediaWiki directory.
	 *
	 * @covers MediaWikiFarmScriptComposer::main
	 * @uses MediaWikiFarmScriptComposer::__construct
	 * @uses AbstractMediaWikiFarmScript::__construct
	 * @uses AbstractMediaWikiFarmScript::usage
	 * @uses AbstractMediaWikiFarmScript::premain
	 * @uses AbstractMediaWikiFarmScript::getParam
	 * @uses MediaWikiFarmUtils::isMediaWiki
	 */
	public function testWrongDirectory() {

		$this->expectOutputString( self::$shortHelp );
		$cwd = getcwd();
		chdir( self::$wgMediaWikiFarmCodeDir . '/vstub2' );

		$wgMediaWikiFarmScriptComposer = new MediaWikiFarmScriptComposer( 1, array( self::$mwcomposerPath ) );

		$wgMediaWikiFarmScriptComposer->main();

		$this->assertEquals( 4, $wgMediaWikiFarmScriptComposer->status );

		chdir( $cwd );
	}

	/**
	 * Test successful loading.
	 *
	 * @group large
	 * @backupGlobals enabled
	 * @covers MediaWikiFarmScriptComposer::main
	 * @covers MediaWikiFarmScriptComposer::composer2mediawiki
	 * @uses MediaWikiFarmScriptComposer::__construct
	 * @uses MediaWikiFarmScriptComposer::getParam
	 * @uses MediaWikiFarmScriptComposer::exportArguments
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
	 * @uses MediaWikiFarm::getConfigFile
	 * @uses MediaWikiFarm::setVariable
	 * @uses MediaWikiFarm::replaceVariables
	 * @uses MediaWikiFarm::getVariable
	 * @uses MediaWikiFarm::updateVersion
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarm::prepareLog
	 * @uses MediaWikiFarm::issueLog
	 * @uses MediaWikiFarmConfiguration::populateSettings
	 * @uses MediaWikiFarmConfiguration::activateExtensions
	 * @uses MediaWikiFarmConfiguration::detectComposer
	 * @uses MediaWikiFarmConfiguration::setEnvironment
	 * @uses MediaWikiFarmConfiguration::sortExtensions
	 * @uses MediaWikiFarmConfiguration::composerKey
	 * @uses MediaWikiFarmUtils::arrayMerge
	 * @uses MediaWikiFarmUtils::isMediaWiki
	 * @uses MediaWikiFarmUtils::readFile
	 */
	public function testSuccessfulLoading() {

		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarm', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfigDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', self::$wgMediaWikiFarmCodeDir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmSyslog', false );

		$this->backupGlobalVariable( 'argc' );
		$this->backupGlobalVariable( 'argv' );
		$this->backupGlobalSubvariable( '_SERVER', 'argc' );
		$this->backupGlobalSubvariable( '_SERVER', 'argv' );

		$this->expectOutputString( '' );

		AbstractMediaWikiFarmScript::rmdirr( self::$wgMediaWikiFarmCodeDir . '/vstub3' );
		AbstractMediaWikiFarmScript::copyr( self::$wgMediaWikiFarmCodeDir . '/vstub', self::$wgMediaWikiFarmCodeDir . '/vstub3', true );
		$cwd = getcwd();
		chdir( self::$wgMediaWikiFarmCodeDir . '/vstub3' );

		$wgMediaWikiFarmScriptComposer = new MediaWikiFarmScriptComposer( 2, array( self::$mwcomposerPath, 'install', '--no-dev', '-q' ) );

		# This function is the main procedure, it runs Composer in background 7 times so it can take 20-25 seconds
		$wgMediaWikiFarmScriptComposer->main();

		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/vendor' ) );
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/vendor/composer' ) );
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/vendor/composerc4538db9' ) ); # SemanticMediaWiki
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/vendor/composer56cb950e' ) ); # PageForms
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/vendor/composer2a684956' ) ); # SemanticFormsSelect
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/vendor/composer/installers' ) );
		$this->assertTrue( is_file( self::$wgMediaWikiFarmCodeDir . '/vstub3/vendor/autoload.php' ) );

		# There is no composer.lock in this specific example
		// $this->assertTrue( is_file( self::$wgMediaWikiFarmCodeDir . '/vstub3/composer.lock' ) );
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/skins/chameleon' ) );
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/extensions/PageForms' ) );
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/extensions/SemanticFormsSelect' ) );
		$this->assertTrue( is_dir( self::$wgMediaWikiFarmCodeDir . '/vstub3/extensions/SemanticMediaWiki' ) );

		AbstractMediaWikiFarmScript::rmdirr( self::$wgMediaWikiFarmCodeDir . '/vstub3' );
		chdir( $cwd );
	}
}
