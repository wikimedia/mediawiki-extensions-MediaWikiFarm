<?php
/**
 * Class FunctionsTest.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

require_once __DIR__ . '/MediaWikiFarmTestCase.php';
require_once dirname( dirname( __DIR__ ) ) . '/src/MediaWikiFarm.php';

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
	 * Test the exception is thrown when the YAML library is not installed.
	 *
	 * Note that this test will be probably never get executed because PHPUnit depends
	 * on this very library; just for completeness; commented out to avoid skippy test.
	 *
	 * @codingStandardsIgnoreStart MediaWiki.Commenting.PhpunitAnnotations.NotTestFunction
	 * @covers MediaWikiFarmUtils::readYAML
	 * @codingStandardsIgnoreEnd
	 */
	/*public function testUninstalledYAMLLibrary() {

		if( class_exists( 'Symfony\Component\Yaml\Yaml' ) ) {
			$this->markTestSkipped(
				'The optional YAML library is installed.'
			);
		}

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Unavailable YAML library, please install it if you want to read YAML files' );

		MediaWikiFarmUtils::readYAML( self::$wgMediaWikiFarmConfigDir . '/testreading.yml' );
	}*/

	/**
	 * Test reading a missing file in the YAML function.
	 *
	 * @covers MediaWikiFarmUtils::readYAML
	 */
	public function testReadMissingFileYAMLFunction() {

		if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) ) {
			$this->markTestSkipped(
				'The optional YAML library was not installed.'
			);
		}

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Missing file' );

		MediaWikiFarmUtils::readYAML( self::$wgMediaWikiFarmConfigDir . '/missingfile.yml' );
	}

	/**
	 * Test reading a badly-formatted YAML file in the YAML function.
	 *
	 * @covers MediaWikiFarmUtils::readYAML
	 */
	public function testBadSyntaxFileYAMLFunction() {

		if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) ) {
			$this->markTestSkipped(
				'The optional YAML library was not installed.'
			);
		}

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Badly-formatted YAML file' );

		MediaWikiFarmUtils::readYAML( self::$wgMediaWikiFarmConfigDir . '/badsyntax.yaml' );
	}

	/**
	 * Test arrayMerge.
	 *
	 * @covers MediaWikiFarmUtils::arrayMerge
	 */
	public function testArrayMerge() {

		$this->assertEquals(
			[
				'a' => 'A',
				'b' => 'BB',
				'c' => 0,
				'd' => null,
				'e' => 'E',
				'f' => false,
			],
			MediaWikiFarmUtils::arrayMerge(
				[
					'a' => 'A',
					'b' => 'B',
					'c' => 0,
					'd' => null,
				],
				[
					'e' => 'E',
					'b' => 'BB',
					'f' => false,
				]
			)
		);

		$this->assertEquals(
			[
				'a' => true,
				'b' => false,
				'c' => false,
				1 => 11,
				2 => 12,
				3 => 121,
				4 => 13,
			],
			MediaWikiFarmUtils::arrayMerge(
				[
					'a' => false,
					'b' => true,
					'c' => false,
					1 => 11,
					2 => 12,
				],
				null,
				[
					'b' => false,
					'a' => true,
					2 => 121,
					'c' => false,
					3 => 13,
				]
			)
		);

		$this->assertEquals(
			[
				1 => [
					'1a' => '1A',
					'1b' => '1B',
					'1c' => 12,
					'1d' => null,
					'1e' => true,
				],
				2 => [
					'1f' => '1F',
					'1b' => '1BB',
					'1g' => false,
					'1e' => false,
				],
				4 => 44,
				'k' => [
					'ka' => 'kA',
					'kb' => [
						0 => 7,
					],
					'kc' => 1012,
					'kd' => null,
					'ke' => false,
					'kf' => 'kF',
					'kg' => false,
				],
			],
			MediaWikiFarmUtils::arrayMerge(
				null,
				[
					1 => [
						'1a' => '1A',
						'1b' => '1B',
						'1c' => 12,
						'1d' => null,
						'1e' => true,
					],
					'k' => [
						'ka' => 'kA',
						'kb' => 'kB',
						'kc' => 1012,
						'kd' => null,
						'ke' => true,
					],
				],
				[
					1 => [
						'1f' => '1F',
						'1b' => '1BB',
						'1g' => false,
						'1e' => false,
					],
					4 => 44,
				],
				[
					'k' => [
						'kf' => 'kF',
						'kb' => [
							7
						],
						'kg' => false,
						'ke' => false,
					],
				]
			)
		);
	}
}
