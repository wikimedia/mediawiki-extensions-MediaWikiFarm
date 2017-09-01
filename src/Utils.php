<?php
/**
 * Class MediaWikiFarmUtils.
 *
 * @package MediaWikiFarm
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */


/**
 * Class containing various utilities.
 */
class MediaWikiFarmUtils {

	/**
	 * Read a file either in PHP, YAML (if library available), JSON, dblist, or serialised, and returns the interpreted array.
	 *
	 * The choice between the format depends on the extension: php, yml, yaml, json, dblist, serialised.
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 *
	 * @param string $filename Name of the requested file.
	 * @param string $cacheDir Cache directory.
	 * @param string[] $log Error log.
	 * @param string $directory Parent directory.
	 * @param bool $cache The successfully file read must be cached.
	 * @return array|false The interpreted array in case of success, else false.
	 */
	static function readFile( $filename, $cacheDir, array &$log, $directory = '', $cache = true ) {

		# Check parameter
		if( !is_string( $filename ) ) {
			return false;
		}

		# Detect the format
		$format = strrchr( $filename, '.' );
		$array = false;

		# Check the file exists
		$prefixedFile = $directory ? $directory . '/' . $filename : $filename;
		$cachedFile = $cacheDir && $cache ? $cacheDir . '/config/' . $filename . '.php' : false;
		if( !is_file( $prefixedFile ) ) {
			$format = null;
		}

		# Format PHP
		if( $format == '.php' ) {

			$array = include $prefixedFile;
		}

		# Format 'serialisation'
		elseif( $format == '.ser' ) {

			$content = file_get_contents( $prefixedFile );

			if( preg_match( "/^\r?\n?$/m", $content ) ) {
				$array = array();
			}
			else {
				$array = unserialize( $content );
			}
		}

		# Cached version
		elseif( $cachedFile && is_string( $format ) && is_file( $cachedFile ) && filemtime( $cachedFile ) >= filemtime( $prefixedFile ) ) {

			return self::readFile( $filename . '.php', $cacheDir, $log, $cacheDir . '/config', false );
		}

		# Format YAML
		elseif( $format == '.yml' || $format == '.yaml' ) {

			# Load Composer libraries
			# There is no warning if not present because to properly handle the error by returning false
			# This is only included here to avoid delays (~3ms without OPcache) during the loading using cached files or other formats
			if( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {

				require_once dirname( __FILE__ ) . '/Utils5_3.php';

				try {
					$array = MediaWikiFarmUtils5_3::readYAML( $prefixedFile );
				}
				catch( RuntimeException $e ) {
					$log[] = $e->getMessage();
					$log['unreadable-file'] = true;
					$array = false;
				}
			}
		}

		# Format JSON
		elseif( $format == '.json' ) {

			$content = file_get_contents( $prefixedFile );

			if( preg_match( "/^null\r?\n?$/m", $content ) ) {
				$array = array();
			}
			else {
				$array = json_decode( $content, true );
			}
		}

		# Format 'dblist' (simple list of strings separated by newlines)
		elseif( $format == '.dblist' ) {

			$content = file_get_contents( $prefixedFile );

			$array = array();
			$arraytmp = explode( "\n", $content );
			foreach( $arraytmp as $line ) {
				if( $line != '' ) {
					$array[] = $line;
				}
			}
		}

		# Error for any other format
		elseif( !is_null( $format ) ) {
			return false;
		}

		# A null value is an empty file or value 'null'
		if( ( is_null( $array ) || $array === false ) && $cachedFile && is_file( $cachedFile ) ) {

			$log[] = 'Unreadable file \'' . $filename . '\'';
			$log['unreadable-file'] = true;

			return self::readFile( $filename . '.php', $cacheDir, $log, $cacheDir . '/config', false );
		}

		# Regular return for arrays
		if( is_array( $array ) ) {

			if( $cachedFile && $directory != $cacheDir . '/config' && ( !is_file( $cachedFile ) || ( filemtime( $cachedFile ) < filemtime( $prefixedFile ) ) ) ) {
				self::cacheFile( $array, $filename . '.php', $cacheDir . '/config' );
			}

			return $array;
		}

		# Error for any other type
		return false;
	}

	/**
	 * Create a cache file.
	 *
	 * @param array|string $array Array of the data to be cached.
	 * @param string $filename Name of the cache file; this filename must have an extension '.php' else no cache file is saved.
	 * @param string $directory Name of the parent directory; null for default cache directory
	 * @return void
	 */
	static function cacheFile( $array, $filename, $directory ) {

		if( !preg_match( '/\.php$/', $filename ) ) {
			return;
		}

		$prefixedFile = $directory . '/' . $filename;
		$tmpFile = $prefixedFile . '.tmp';

		# Prepare string
		if( is_array( $array ) ) {
			$php = "<?php\n\n// WARNING: file automatically generated: do not modify.\n\nreturn " . var_export( $array, true ) . ';';
		} else {
			$php = (string) $array;
		}

		# Create parent directories
		if( !is_dir( dirname( $tmpFile ) ) ) {
			$path = '';
			foreach( explode( '/', dirname( $prefixedFile ) ) as $dir ) {
				$path .= '/' . $dir;
				if( !is_dir( $path ) ) {
					mkdir( $path );
				}
			}
		}

		# Create temporary file and move it to final file
		if( file_put_contents( $tmpFile, $php ) ) {
			rename( $tmpFile, $prefixedFile );
		}
	}

	/**
	 * Guess if a given directory contains MediaWiki.
	 *
	 * This heuristic (presence of [dir]/includes/DefaultSettings.php) has no false negatives
	 * (every MediaWiki from 1.1 to (at least) 1.27 has such a file) and probably has a few, if
	 * any, false positives (another software which has the very same file).
	 *
	 * @param string $dir The base directory which could contain MediaWiki.
	 * @return bool The directory really contains MediaWiki.
	 */
	static function isMediaWiki( $dir ) {
		return is_file( $dir . '/includes/DefaultSettings.php' );
	}

	/**
	 * Merge multiple arrays together.
	 *
	 * On encountering duplicate keys, merge the two, but ONLY if they're arrays.
	 * PHP's array_merge_recursive() merges ANY duplicate values into arrays,
	 * which is not fun.
	 * This function is almost the same as SiteConfiguration::arrayMerge, with the
	 * difference an existing scalar value has precedence EVEN if evaluated to false,
	 * in order to override permissions array with removed rights.
	 *
	 * @SuppressWarning(PHPMD.StaticAccess)
	 *
	 * @param array $array1 First array.
	 * @return array
	 */
	static function arrayMerge( $array1 /* ... */ ) {
		$out = $array1;
		if ( is_null( $out ) ) {
			$out = array();
		}
		$argsCount = func_num_args();
		for ( $i = 1; $i < $argsCount; $i++ ) {
			$array = func_get_arg( $i );
			if ( is_null( $array ) ) {
				continue;
			}
			foreach ( $array as $key => $value ) {
				if( array_key_exists( $key, $out ) && is_string( $key ) && is_array( $out[$key] ) && is_array( $value ) ) {
					$out[$key] = self::arrayMerge( $out[$key], $value );
				} elseif( !array_key_exists( $key, $out ) || !is_numeric( $key ) ) {
					$out[$key] = $value;
				} elseif( is_numeric( $key ) ) {
					$out[] = $value;
				}
			}
		}

		return $out;
	}
}