<?php
/**
 * Class MediaWikiFarmUtils5_3.
 *
 * It was splitted from the other files to keep basic compatibility with PHP 5.2 (lack of namespaces) and permit graceful unfeaturing in this PHP version.
 *
 * @package MediaWikiFarm
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */


/**
 * Class containing various utilities requiring PHP 5.3+.
 */
class MediaWikiFarmUtils5_3 { // @codingStandardsIgnoreLine Squiz.Classes.ValidClassName.NotCamelCaps

	/**
	 * Read a YAML file.
	 *
	 * @param string $filename Name of the YAML file.
	 * @return array|string|int|bool|null Content of the YAML file or null in case of error.
	 * @throws RuntimeException When YAML library is not available, file is missing, or file is badly-formatted.
	 */
	public static function readYAML( $filename ) {

		if( !is_file( $filename ) ) {
			throw new RuntimeException( 'Missing file \'' . $filename . '\'' );
		}

		# Load Composer
		if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) && is_file( dirname( __FILE__ ) . '/../vendor/autoload.php' ) ) {
			include_once dirname( __FILE__ ) . '/../vendor/autoload.php'; // @codeCoverageIgnore
		}

		# Check YAML library was loaded
		# This is harly testable given PHPUnit depends on this library
		if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) || !class_exists( 'Symfony\Component\Yaml\Exception\ParseException' ) ) {
			throw new RuntimeException( 'Unavailable YAML library, please install it if you want to read YAML files' ); // @codeCoverageIgnore
		}

		# Return the array read from YAML or an error
		try {
			return Symfony\Component\Yaml\Yaml::parse( file_get_contents( $filename ) );
		}
		catch( Symfony\Component\Yaml\Exception\ParseException $e ) {
			throw new RuntimeException( 'Badly-formatted YAML file \'' . $filename . '\': ' . $e->getMessage() );
		}
	}
}
