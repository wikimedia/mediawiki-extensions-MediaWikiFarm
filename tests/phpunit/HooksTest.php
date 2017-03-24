<?php

require_once 'MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/Hooks.php';

/**
 * MediaWiki hooks tests.
 *
 * @group MediaWikiFarm
 */
class MediaWikiFarmHooksTest extends MediaWikiFarmTestCase {

	/**
	 * Test onUnitTestsList hook
	 *
	 * @covers MediaWikiFarmHooks::onUnitTestsList
	 */
	function testOnUnitTestsList() {

		$testFiles = glob( dirname( __FILE__ ) . '/*Test.php' );

		$array = array();
		MediaWikiFarmHooks::onUnitTestsList( $array );
		sort( $array );

		$this->assertEquals( $testFiles, $array );
	}
}
