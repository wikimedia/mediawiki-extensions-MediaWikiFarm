<?php
/**
 * Function wfMediaWikiFarm_readYAML.
 *
 * It was splitted from the main class MediaWikiFarm to ensure compatibility with
 * PHP 5.2 (which doesn’t understand namespaces).
 *
 * @package MediaWikiFarm
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */

// @codeCoverageIgnoreStart
if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) && is_file( dirname( __FILE__ ) . '/../vendor/autoload.php' ) ) {
	include_once dirname( __FILE__ ) . '/../vendor/autoload.php';
}
// @codeCoverageIgnoreEnd

/**
 * Read a YAML file.
 *
 * Isolate this function is needed for compatibility with PHP 5.2.
 *
 * @param string $filename Name of the YAML file.
 * @return array|string|int|bool|null Content of the YAML file or null in case of error.
 * @throws RuntimeException When YAML library is not available, file is missing, or file is badly-formatted.
 */
function wfMediaWikiFarm_readYAML( $filename ) {

	if( !is_file( $filename ) ) {
		throw new RuntimeException( 'Missing file \'' . $filename . '\'' );
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
