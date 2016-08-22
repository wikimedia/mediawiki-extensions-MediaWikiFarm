<?php

/**
 * Helper program returning the parsed content of a YAML file.
 *
 * It was splitted from the main class MediaWikiFarm to ensure compatibility with
 * PHP 5.2 (which doesn’t understand namespaces).
 *
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */

# Load Composer
if( is_file( dirname( __FILE__ ) . '/../vendor/autoload.php' ) && !class_exists( 'Symfony\Component\Yaml\Yaml' ) )
	include_once dirname( __FILE__ ) . '/../vendor/autoload.php';

/**
 * Read a YAML file.
 *
 * Isolate this function is needed for compatibility with PHP 5.2.
 *
 * @param string $filename Name of the YAML file.
 * @return array|string|int|bool|null Content of the YAML file or null in case of error.
 */
function MediaWikiFarm_readYAML( $filename ) {

	# If the class or the file don’t exist, return an error
	if( !class_exists( 'Symfony\Component\Yaml\Yaml' ) || !class_exists( 'Symfony\Component\Yaml\Exception\ParseException' ) ) {
		return null;
	}

	if( !is_file( $filename ) ) {
		return null;
	}

	# Return the array read from YAML or an error
	try {
		return Symfony\Component\Yaml\Yaml::parse( file_get_contents( $filename ) );
	}
	catch( Symfony\Component\Yaml\Exception\ParseException $e ) {}

	return null;
}
