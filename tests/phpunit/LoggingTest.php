<?php
/**
 * Class LoggingTest.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

require_once dirname( __FILE__ ) . '/MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/MediaWikiFarm.php';

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
		$this->assertEquals( array(), MediaWikiFarm::prepareLog( false, null, new Exception( 'test exception' ) ) );
	}

	/**
	 * Test when the configuration parameter has a wrong type.
	 *
	 * @covers MediaWikiFarm::prepareLog
	 */
	public function testBadLoggingConfigurationParameter() {

		$this->assertEquals( array( 'Logging parameter must be false or a string', 'test exception' ),
		                     MediaWikiFarm::prepareLog( 0, null, new Exception( 'test exception' ) ) );

		closelog();
	}

	/**
	 * Test logging an exception.
	 *
	 * @covers MediaWikiFarm::prepareLog
	 */
	public function testLoggingAnException() {

		$this->assertEquals( array( 'test exception' ), MediaWikiFarm::prepareLog( 'mediawikifarmtest', null, new Exception( 'test exception' ) ) );

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
				array( 'EntryPoint' => 'index.php' ) );
		$farm->log = array( 'test message' );

		$this->assertEquals( array( 'test message' ), MediaWikiFarm::prepareLog( 'mediawikifarmtest', $farm ) );

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
				array( 'EntryPoint' => 'index.php' ) );
		$farm->log = array( 'test message' );

		$this->assertEquals( array( 'test exception', 'test message' ), MediaWikiFarm::prepareLog( 'mediawikifarmtest', $farm, new Exception( 'test exception' ) ) );

		closelog();
	}
}
