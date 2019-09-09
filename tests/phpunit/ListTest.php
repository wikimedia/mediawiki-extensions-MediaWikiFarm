<?php
/**
 * Class MediaWikiFarmListTest.
 *
 * @package MediaWikiFarm\Tests
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

require_once dirname( __FILE__ ) . '/MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/MediaWikiFarm.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/List.php';

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
			array( 'aa.testfarm2-multiversion.example.org',
			       'ab.testfarm2-multiversion.example.org',
			       'ba.testfarm2-multiversion.example.org',
			       'bb.testfarm2-multiversion.example.org',
			       'a.testfarm2-multiversion-bis.example.org',
			       'b.testfarm2-multiversion-bis.example.org' ),
			$urlsList,
			'The list of wikis does not correspond.'
		);
	}

	/**
	 * [unit] Test the construction of a list of wikis (fail).
	 *
	 * @covers MediaWikiFarmList::__construct
	 * @uses MediaWikiFarmUtils
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Invalid directory for the farm configuration.
	 */
	public function testConstruction() {

		new MediaWikiFarmList( 0, false );
	}

	/**
	 * [unit] Test the construction of a list of wikis (fail).
	 *
	 * @covers MediaWikiFarmList::__construct
	 * @uses MediaWikiFarmUtils
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Invalid directory for the farm configuration.
	 */
	public function testConstruction2() {

		new MediaWikiFarmList( __FILE__, false );
	}

	/**
	 * [unit] Test the construction of a list of wikis (fail).
	 *
	 * @covers MediaWikiFarmList::__construct
	 * @uses MediaWikiFarmUtils
	 * @uses AbstractMediaWikiFarmScript
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Cache directory must be false or a directory.
	 */
	public function testConstruction3() {

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
		$this->assertEquals( array(), $wgMediaWikiFarmList->log );
	}
}
