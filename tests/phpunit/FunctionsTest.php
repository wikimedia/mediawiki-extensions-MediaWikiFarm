<?php

/**
 * Functions tests.
 *
 * These tests operate on either global functions either static functions, without any
 * interaction with the global state or other external states (singletons).
 *
 * @group MediaWikiFarm
 */
class FunctionsTest extends MediaWikiTestCase {

	/**
	 * Symbol for MediaWikiFarm_readYAML, which is normally loaded just-in-time in the main class.
	 */
	static function setUpBeforeClass() {

		require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/Yaml.php';
	}

	/**
	 * Test the exception is thrown when the YAML library is not installed.
	 *
	 * Note that this test will be probably never get executed because PHPUnit depends
	 * on this very library; just for completeness; commented out to avoid skippy test.
	 *
	 * @covers ::MediaWikiFarm_readYAML
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

		MediaWikiFarm_readYAML( dirname( __FILE__ ) . '/data/config/testreading.yml' );
	}*/


	/**
	 * Test reading a missing file in the YAML function.
	 *
	 * @covers ::MediaWikiFarm_readYAML
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

		MediaWikiFarm_readYAML( dirname( __FILE__ ) . '/data/config/missingfile.yml' );
	}

	/**
	 * Test reading a badly-formatted YAML file in the YAML function.
	 *
	 * @covers ::MediaWikiFarm_readYAML
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

		MediaWikiFarm_readYAML( dirname( __FILE__ ) . '/data/config/badsyntax.yaml' );
	}

	/**
	 * Test onUnitTestsList hook
	 *
	 * @covers MediaWikiFarm::onUnitTestsList
	 */
	function testOnUnitTestsListHook() {	

		$array = array();
		MediaWikiFarm::onUnitTestsList( $array );
		$this->assertEquals(
			array(
				dirname( __FILE__ ) . '/MediaWikiFarmMonoversionInstallationTest.php',
				dirname( __FILE__ ) . '/MediaWikiFarmMultiversionInstallationTest.php',
			),
			$array );
	}

	/**
	 * Test protectRegex
	 *
	 * @covers MediaWikiFarm::protectRegex
	 */
	function testProtectRegex() {

		$this->assertEquals( '\/\(a\.\\\\', MediaWikiFarm::protectRegex( '/(a.\\' ) );
	}
}
