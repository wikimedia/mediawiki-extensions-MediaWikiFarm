<?php
/**
 * Class MediaWikiFarmHooksTest.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later GNU General Public License v3.0, or (at your option) any later version.
 * @license AGPL-3.0-or-later GNU Affero General Public License v3.0, or (at your option) any later version.
 */

require_once dirname( __FILE__ ) . '/MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/Hooks.php';

/**
 * MediaWiki hooks tests.
 *
 * @group MediaWikiFarm
 */
class MediaWikiFarmHooksTest extends MediaWikiFarmTestCase {

	/**
	 * Test onUnitTestsList hook.
	 *
	 * @covers MediaWikiFarmHooks::onUnitTestsList
	 */
	function testOnUnitTestsList() {

		$testFiles = array_merge(
			glob( dirname( __FILE__ ) . '/*Test.php' ),
			glob( dirname( __FILE__ ) . '/*/*Test.php' )
		);

		$array = array();
		MediaWikiFarmHooks::onUnitTestsList( $array );
		sort( $array );

		$this->assertEquals( $testFiles, $array );
	}
}
