<?php
/**
 * Class MediaWikiFarmScriptListWikisTest.
 *
 * @package MediaWikiFarm\Tests
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

require_once dirname( dirname( __FILE__ ) ) . '/MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/src/MediaWikiFarm.php';
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/src/bin/ScriptListWikis.php';

/**
 * Tests about class MediaWikiFarmScriptListWikis.
 *
 * @group MediaWikiFarm
 */
class MediaWikiFarmScriptListWikisTest extends MediaWikiFarmTestCase {

	/** @var string Path to [farm]/bin/mwlistwikis.php. */
	public static $mwlistwikisPath = '';

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
		self::$mwlistwikisPath = $mwlistwikisPath = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/bin/mwlistwikis.php';

		self::$shortHelp = <<<HELP

    Usage: php $mwlistwikisPath …


HELP;

		self::$longHelp = <<<HELP

    Usage: php $mwlistwikisPath

    | For easier use, you can alias it in your shell:
    |
    |     alias mwlistwikis='php $mwlistwikisPath'
    |
    | Return codes:
    | 0 = success
    | 5 = internal error in farm configuration (similar to HTTP 500)


HELP;
	}

	/**
	 * [unit] Test construction.
	 *
	 * @covers MediaWikiFarmScriptListWikis::__construct
	 * @uses AbstractMediaWikiFarmScript
	 */
	public function testConstruction() {

		$wgMediaWikiFarmScriptListWikis = new MediaWikiFarmScriptListWikis( 1, array( self::$mwlistwikisPath ) );

		$this->assertEquals( 1, $wgMediaWikiFarmScriptListWikis->argc );
		$this->assertEquals( array( self::$mwlistwikisPath ), $wgMediaWikiFarmScriptListWikis->argv );
	}

	/**
	 * [unit] Test usage method.
	 *
	 * @covers MediaWikiFarmScriptListWikis::main
	 * @uses AbstractMediaWikiFarmScript
	 * @uses MediaWikiFarmScriptListWikis
	 */
	public function testUsage() {

		$this->expectOutputString( self::$longHelp );

		$wgMediaWikiFarmScriptListWikis = new MediaWikiFarmScriptListWikis( 2, array( self::$mwlistwikisPath, '-h' ) );

		$this->assertFalse( $wgMediaWikiFarmScriptListWikis->main() );

		$this->assertSame( 0, $wgMediaWikiFarmScriptListWikis->status );
	}

	/**
	 * [integration] Test output.
	 *
	 * @covers MediaWikiFarmScriptListWikis::__construct
	 * @covers MediaWikiFarmScriptListWikis::main
	 * @uses AbstractMediaWikiFarmScript
	 * @uses MediaWikiFarmList
	 * @uses MediaWikiFarmUtils
	 */
	public function testIntegration() {

		$this->backupAndUnsetGlobalVariable( 'wgMediaWikiFarm' );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmConfigDir', self::$wgMediaWikiFarmConfig2Dir );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCodeDir', null );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmCacheDir', false );
		$this->backupAndSetGlobalVariable( 'wgMediaWikiFarmSyslog', false );

		$this->expectOutputString( <<<OUTPUT
aa.testfarm2-multiversion.example.org
ab.testfarm2-multiversion.example.org
ba.testfarm2-multiversion.example.org
bb.testfarm2-multiversion.example.org
a.testfarm2-multiversion-bis.example.org
b.testfarm2-multiversion-bis.example.org

OUTPUT
		);

		$wgMediaWikiFarmScript = new MediaWikiFarmScriptListWikis( 1, array( self::$mwlistwikisPath ) );

		$this->assertTrue( $wgMediaWikiFarmScript->main() );

		$this->assertSame( 0, $wgMediaWikiFarmScript->status );
		$this->assertEquals( 1, $wgMediaWikiFarmScript->argc );
		$this->assertEquals( array( self::$mwlistwikisPath ), $wgMediaWikiFarmScript->argv );
	}
}
