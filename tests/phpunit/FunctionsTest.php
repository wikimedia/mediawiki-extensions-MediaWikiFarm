<?php
/**
 * Class FunctionsTest.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

require_once dirname( __FILE__ ) . '/MediaWikiFarmTestCase.php';
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
	 * Symbol for MediaWikiFarmUtils5_3::readYAML, which is normally loaded just-in-time in the main class.
	 */
	public static function setUpBeforeClass() {

		parent::setUpBeforeClass();

		if( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
			require_once self::$wgMediaWikiFarmFarmDir . '/src/Utils5_3.php';
		}
	}

	/**
	 * Test the exception is thrown when the YAML library is not installed.
	 *
	 * Note that this test will be probably never get executed because PHPUnit depends
	 * on this very library; just for completeness; commented out to avoid skippy test.
	 *
	 * @codingStandardsIgnoreStart MediaWiki.Commenting.PhpunitAnnotations.NotTestFunction
	 * @covers MediaWikiFarmUtils5_3::readYAML
	 *
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Unavailable YAML library, please install it if you want to read YAML files
	 * @codingStandardsIgnoreEnd
	 */
	/*function testUninstalledYAMLLibrary() {

		if( class_exists( 'Symfony\Component\Yaml\Yaml' ) ) {
			$this->markTestSkipped(
				'The optional YAML library is installed.'
			);
		}

		MediaWikiFarmUtils5_3::readYAML( self::$wgMediaWikiFarmConfigDir . '/testreading.yml' );
	}*/

	/**
	 * Test reading a missing file in the YAML function.
	 *
	 * @covers MediaWikiFarmUtils5_3::readYAML
	 *
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Missing file
	 */
	public function testReadMissingFileYAMLFunction() {

		if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) ) {
			$this->markTestSkipped(
				'The optional YAML library was not installed.'
			);
		}

		MediaWikiFarmUtils5_3::readYAML( self::$wgMediaWikiFarmConfigDir . '/missingfile.yml' );
	}

	/**
	 * Test reading a badly-formatted YAML file in the YAML function.
	 *
	 * @covers MediaWikiFarmUtils5_3::readYAML
	 *
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Badly-formatted YAML file
	 */
	public function testBadSyntaxFileYAMLFunction() {

		if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) ) {
			$this->markTestSkipped(
				'The optional YAML library was not installed.'
			);
		}

		MediaWikiFarmUtils5_3::readYAML( self::$wgMediaWikiFarmConfigDir . '/badsyntax.yaml' );
	}

	/**
	 * Test arrayMerge.
	 *
	 * @covers MediaWikiFarmUtils::arrayMerge
	 */
	public function testArrayMerge() {

		$this->assertEquals(
			array(
				'a' => 'A',
				'b' => 'BB',
				'c' => 0,
				'd' => null,
				'e' => 'E',
				'f' => false,
			),
			MediaWikiFarmUtils::arrayMerge(
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
			MediaWikiFarmUtils::arrayMerge(
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
			MediaWikiFarmUtils::arrayMerge(
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
