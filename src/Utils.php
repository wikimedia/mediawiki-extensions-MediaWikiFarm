<?php
/**
 * Class MediaWikiFarmUtils.
 *
 * @package MediaWikiFarm
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
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
	public static function readFile( $filename, $cacheDir, array &$log, $directory = '', $cache = true ) {

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

		# Cached version - avoid the case where the timestamps are the same, the two files could have non-coherent versions
		if( $cachedFile && is_string( $format ) && is_file( $cachedFile ) && filemtime( $cachedFile ) > filemtime( $prefixedFile ) ) {

			return self::readFile( $filename . '.php', $cacheDir, $log, $cacheDir . '/config', false );
		}

		# Format PHP
		elseif( $format == '.php' ) {

			try {
				$array = @include $prefixedFile;
			} catch( Throwable $e ) {
				$array = null;
			}
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
				if( $array === null ) {
					$array = array();
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
		elseif( $format !== null ) {
			return false;
		}

		# A null value is an empty file or value 'null'
		if( ( $array === null || $array === false ) && $cachedFile && is_file( $cachedFile ) ) {

			$log[] = 'Unreadable file \'' . $filename . '\'';
			$log['unreadable-file'] = true;

			return self::readFile( $filename . '.php', $cacheDir, $log, $cacheDir . '/config', false );
		}

		# Regular return for arrays
		if( is_array( $array ) ) {

			# Cache this version - avoid the case where the timestamps are the same, the two files could have non-coherent versions
			if( $cachedFile && $directory != $cacheDir . '/config' && ( !is_file( $cachedFile ) || ( filemtime( $cachedFile ) <= filemtime( $prefixedFile ) ) ) ) {
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
	public static function cacheFile( $array, $filename, $directory ) {

		$prefixedFile = $directory . '/' . $filename;
		if( !preg_match( '/\.php$/', $prefixedFile ) ) {
			return;
		}

		# Prepare string
		if( is_array( $array ) ) {
			$php = "<?php\n\n// WARNING: file automatically generated: do not modify.\n\nreturn " . var_export( $array, true ) . ';';
		} else {
			$php = (string) $array;
		}

		# Create parent directories
		if( !is_dir( dirname( $prefixedFile ) ) ) {
			$path = '';
			foreach( explode( '/', dirname( $prefixedFile ) ) as $dir ) {
				$path .= '/' . $dir;
				if( !is_dir( $path ) ) {
					if( file_exists( $path ) ) {
						return;
					}
					mkdir( $path );
				}
			}
		}

		# Write the file with an exclusive lock
		// @codingStandardsIgnoreLine MediaWiki.ControlStructures.AssignmentInControlStructures.AssignmentInControlStructures
		if( ( $handle = fopen( $prefixedFile, 'c' ) ) !== false ) {
			if( flock( $handle, LOCK_EX ) !== false ) {
				if( ftruncate( $handle, 0 ) !== false && rewind( $handle ) !== false ) {
					fwrite( $handle, $php );
					fflush( $handle );
				}
				flock( $handle, LOCK_UN );
			}
			fclose( $handle );
		}
	}

	/**
	 * Read a file with any of the listed extensions.
	 *
	 * If multiple files exist with different extensions, the first (without syntax error)
	 * in the extensions list is returned. If some previous files had syntax errors, these
	 * syntax errors appear in the log.
	 *
	 * The available extensions are listed in the function MediaWikiFarmUtils::readFile.
	 *
	 * @param string $filename File name without the extension.
	 * @param string $directory Directory containing the file.
	 * @param string $cacheDir Cache directory.
	 * @param string[] $log Error log.
	 * @param string[] $formats List of possible extensions of the file.
	 * @param bool $cache The successfully file read must be cached.
	 * @return array 2-tuple with the result (array) and file read (string); in case no files were found, the second value is an empty string.
	 */
	public static function readAnyFile( $filename, $directory, $cacheDir, array &$log, $formats = array( 'yml', 'php', 'json' ), $cache = true ) {

		foreach( $formats as $format ) {

			$array = self::readFile( $filename . '.' . $format, $cacheDir, $log, $directory, $cache );
			if( is_array( $array ) ) {
				return array( $array, $filename . '.' . $format );
			}
		}

		return array( array(), '' );
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
	public static function isMediaWiki( $dir ) {
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
	// @codingStandardsIgnoreLine MediaWiki.Commenting.FunctionComment.SuperfluousVariadicArgComment
	public static function arrayMerge( $array1 /* ... */ ) {
		$out = $array1;
		if ( $out === null ) {
			$out = array();
		}
		$argsCount = func_num_args();
		for ( $i = 1; $i < $argsCount; $i++ ) {
			$array = func_get_arg( $i );
			if ( $array === null ) {
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
