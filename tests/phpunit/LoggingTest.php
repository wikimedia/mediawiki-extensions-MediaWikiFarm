<?php
/**
 * Class LoggingTest.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

require_once __DIR__ . '/MediaWikiFarmTestCase.php';
require_once dirname( dirname( __DIR__ ) ) . '/src/MediaWikiFarm.php';

/**
 * Logging-related tests.
 *
 * @group MediaWikiFarm
 */
class LoggingTest extends MediaWikiFarmTestCase {

	/**
	 * Test when logging is deactivated.
	 *
	 * @covers MediaWikiFarm::prepareLog
	 */
	public function testNoLogging() {

		# Check no log message is issued
		$this->assertEquals( [], MediaWikiFarm::prepareLog( false, null, new Exception( 'test exception' ) ) );
	}

	/**
	 * Test when the configuration parameter has a wrong type.
	 *
	 * @covers MediaWikiFarm::prepareLog
	 */
	public function testBadLoggingConfigurationParameter() {

		$this->assertEquals( [ 'Logging parameter must be false or a string', 'test exception' ],
		                     MediaWikiFarm::prepareLog( 0, null, new Exception( 'test exception' ) ) );

		closelog();
	}

	/**
	 * Test logging an exception.
	 *
	 * @covers MediaWikiFarm::prepareLog
	 */
	public function testLoggingAnException() {

		$this->assertEquals( [ 'test exception' ], MediaWikiFarm::prepareLog( 'mediawikifarmtest', null, new Exception( 'test exception' ) ) );

		closelog();
	}

	/**
	 * Test logging a normal message.
	 *
	 * @covers MediaWikiFarm::prepareLog
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testLoggingAMessage() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				[ 'EntryPoint' => 'index.php' ] );
		$farm->log = [ 'test message' ];

		$this->assertEquals( [ 'test message' ], MediaWikiFarm::prepareLog( 'mediawikifarmtest', $farm ) );

		closelog();
	}

	/**
	 * Test logging a normal message and an exception.
	 *
	 * @covers MediaWikiFarm::prepareLog
	 * @uses MediaWikiFarm::__construct
	 * @uses MediaWikiFarm::selectFarm
	 * @uses MediaWikiFarm::readFile
	 * @uses MediaWikiFarmUtils
	 */
	public function testLoggingAMessageAndAnException() {

		$farm = new MediaWikiFarm(
				'a.testfarm-multiversion.example.org',
				null,
				self::$wgMediaWikiFarmConfigDir,
				self::$wgMediaWikiFarmCodeDir,
				false,
				[ 'EntryPoint' => 'index.php' ] );
		$farm->log = [ 'test message' ];

		$this->assertEquals( [ 'test exception', 'test message' ], MediaWikiFarm::prepareLog( 'mediawikifarmtest', $farm, new Exception( 'test exception' ) ) );

		closelog();
	}
}
