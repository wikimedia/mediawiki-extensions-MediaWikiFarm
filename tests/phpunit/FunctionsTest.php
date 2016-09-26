<?php

require_once 'MediaWikiFarmTestCase.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/MediaWikiFarm.php';

/**
 * Functions tests.
 *
 * These tests operate on either global functions either static functions, without any
 * interaction with the global state or other external states (singletons).
 *
 * @group MediaWikiFarm
 */
class FunctionsTest extends MediaWikiFarmTestCase {

	/**
	 * Symbol for wfMediaWikiFarm_readYAML, which is normally loaded just-in-time in the main class.
	 */
	static function setUpBeforeClass() {

		parent::setUpBeforeClass();

		require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/Yaml.php';
	}

	/**
	 * Test the exception is thrown when the YAML library is not installed.
	 *
	 * Note that this test will be probably never get executed because PHPUnit depends
	 * on this very library; just for completeness; commented out to avoid skippy test.
	 *
	 * @covers ::wfMediaWikiFarm_readYAML
	 *
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Unavailable YAML library, please install it if you want to read YAML files
	 */
	/*function testUninstalledYAMLLibrary() {

		if( class_exists( 'Symfony\Component\Yaml\Yaml' ) ) {
			$this->markTestSkipped(
				'The optional YAML library is installed.'
			);
		}

		wfMediaWikiFarm_readYAML( dirname( __FILE__ ) . '/data/config/testreading.yml' );
	}*/


	/**
	 * Test reading a missing file in the YAML function.
	 *
	 * @covers ::wfMediaWikiFarm_readYAML
	 *
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Missing file
	 */
	function testReadMissingFileYAMLFunction() {

		if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) ) {
			$this->markTestSkipped(
				'The optional YAML library was not installed.'
			);
		}

		wfMediaWikiFarm_readYAML( dirname( __FILE__ ) . '/data/config/missingfile.yml' );
	}

	/**
	 * Test reading a badly-formatted YAML file in the YAML function.
	 *
	 * @covers ::wfMediaWikiFarm_readYAML
	 *
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Badly-formatted YAML file
	 */
	function testBadSyntaxFileYAMLFunction() {

		if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) ) {
			$this->markTestSkipped(
				'The optional YAML library was not installed.'
			);
		}

		wfMediaWikiFarm_readYAML( dirname( __FILE__ ) . '/data/config/badsyntax.yaml' );
	}

	/**
	 * Test onUnitTestsList hook
	 *
	 * @covers MediaWikiFarm::onUnitTestsList
	 */
	function testOnUnitTestsListHook() {

		$testFiles = glob( dirname( __FILE__ ) . '/*Test.php' );

		$array = array();
		MediaWikiFarm::onUnitTestsList( $array );

		$this->assertEquals( $testFiles, $array );
	}

	/**
	 * Test arrayMerge
	 *
	 * @covers MediaWikiFarm::arrayMerge
	 */
	function testArrayMerge() {

		$this->assertEquals(
			array(
				'a' => 'A',
				'b' => 'BB',
				'c' => 0,
				'd' => null,
				'e' => 'E',
				'f' => false,
			),
			MediaWikiFarm::arrayMerge(
				array(
					'a' => 'A',
					'b' => 'B',
					'c' => 0,
					'd' => null,
				),
				array(
					'e' => 'E',
					'b' => 'BB',
					'f' => false,
				)
			)
		);

		$this->assertEquals(
			array(
				'a' => true,
				'b' => false,
				'c' => false,
				1 => 11,
				2 => 12,
				3 => 121,
				4 => 13,
			),
			MediaWikiFarm::arrayMerge(
				array(
					'a' => false,
					'b' => true,
					'c' => false,
					1 => 11,
					2 => 12,
				),
				null,
				array(
					'b' => false,
					'a' => true,
					2 => 121,
					'c' => false,
					3 => 13,
				)
			)
		);

		$this->assertEquals(
			array(
				1 => array(
					'1a' => '1A',
					'1b' => '1B',
					'1c' => 12,
					'1d' => null,
					'1e' => true,
				),
				2 => array(
					'1f' => '1F',
					'1b' => '1BB',
					'1g' => false,
					'1e' => false,
				),
				4 => 44,
				'k' => array(
					'ka' => 'kA',
					'kb' => array(
						0 => 7,
					),
					'kc' => 1012,
					'kd' => null,
					'ke' => false,
					'kf' => 'kF',
					'kg' => false,
				),
			),
			MediaWikiFarm::arrayMerge(
				null,
				array(
					1 => array(
						'1a' => '1A',
						'1b' => '1B',
						'1c' => 12,
						'1d' => null,
						'1e' => true,
					),
					'k' => array(
						'ka' => 'kA',
						'kb' => 'kB',
						'kc' => 1012,
						'kd' => null,
						'ke' => true,
					),
				),
				array(
					1 => array(
						'1f' => '1F',
						'1b' => '1BB',
						'1g' => false,
						'1e' => false,
					),
					4 => 44,
				),
				array(
					'k' => array(
						'kf' => 'kF',
						'kb' => array(
							7
						),
						'kg' => false,
						'ke' => false,
					),
				)
			)
		);
	}
}
