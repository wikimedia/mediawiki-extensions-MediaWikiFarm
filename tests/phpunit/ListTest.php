<?php
/**
 * Class MediaWikiFarmListTest.
 *
 * @package MediaWikiFarm\Tests
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

require_once __DIR__ . '/MediaWikiFarmTestCase.php';
require_once dirname( dirname( __DIR__ ) ) . '/src/MediaWikiFarm.php';
require_once dirname( dirname( __DIR__ ) ) . '/src/List.php';

/**
 * MediaWiki hooks tests.
 *
 * @group MediaWikiFarm
 */
class MediaWikiFarmListTest extends MediaWikiFarmTestCase {

	/**
	 * [integration] Test the whole computation of a list of wikis.
	 *
	 * @covers MediaWikiFarmList::__construct
	 * @covers MediaWikiFarmList::getURLsList
	 * @covers MediaWikiFarmList::getVariablesList
	 * @covers MediaWikiFarmList::obtainVariables
	 * @covers MediaWikiFarmList::generateVariablesList
	 * @uses MediaWikiFarmUtils
	 */
	public function testConstructionSuccess() {

		$wgMediaWikiFarmList = new MediaWikiFarmList( self::$wgMediaWikiFarmConfig2Dir, false );
		$urlsList = $wgMediaWikiFarmList->getURLsList();

		$this->assertEquals(
			[ 'aa.testfarm2-multiversion.example.org',
			       'ab.testfarm2-multiversion.example.org',
			       'ba.testfarm2-multiversion.example.org',
			       'bb.testfarm2-multiversion.example.org',
			       'a.testfarm2-multiversion-bis.example.org',
			       'b.testfarm2-multiversion-bis.example.org' ],
			$urlsList,
			'The list of wikis does not correspond.'
		);
	}

	/**
	 * [unit] Test the construction of a list of wikis (fail).
	 *
	 * @covers MediaWikiFarmList::__construct
	 * @uses MediaWikiFarmUtils
	 */
	public function testConstruction() {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid directory for the farm configuration.' );

		new MediaWikiFarmList( 0, false );
	}

	/**
	 * [unit] Test the construction of a list of wikis (fail).
	 *
	 * @covers MediaWikiFarmList::__construct
	 * @uses MediaWikiFarmUtils
	 */
	public function testConstruction2() {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid directory for the farm configuration.' );

		new MediaWikiFarmList( __FILE__, false );
	}

	/**
	 * [unit] Test the construction of a list of wikis (fail).
	 *
	 * @covers MediaWikiFarmList::__construct
	 * @uses MediaWikiFarmUtils
	 * @uses AbstractMediaWikiFarmScript
	 */
	public function testConstruction3() {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Cache directory must be false or a directory.' );

		new MediaWikiFarmList( self::$wgMediaWikiFarmConfig2Dir, 0 );
	}

	/**
	 * [unit] Test the construction of a list of wikis (success).
	 *
	 * @covers MediaWikiFarmList::__construct
	 * @uses MediaWikiFarmUtils
	 * @uses AbstractMediaWikiFarmScript
	 */
	public function testConstruction5() {

		$wgMediaWikiFarmList = new MediaWikiFarmList( self::$wgMediaWikiFarmConfig2Dir, self::$wgMediaWikiFarmCacheDir );
		$this->assertEquals( [], $wgMediaWikiFarmList->log );
	}
}
