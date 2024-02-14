<?php
/**
 * Class MediaWikiFarmHooksTest.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

require_once __DIR__ . '/MediaWikiFarmTestCase.php';
require_once dirname( dirname( __DIR__ ) ) . '/src/Hooks.php';

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
	public function testOnUnitTestsList() {

		$testFiles = array_merge(
			glob( __DIR__ . '/*Test.php' ),
			glob( __DIR__ . '/*/*Test.php' )
		);

		$array = [];
		MediaWikiFarmHooks::onUnitTestsList( $array );
		sort( $array );

		$this->assertEquals( $testFiles, $array );
	}
}
