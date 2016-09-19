<?php
/**
 * Script class.
 *
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */

// @codeCoverageIgnoreStart
require_once dirname( __FILE__ ) . '/MediaWikiFarm.php';
// @codeCoverageIgnoreEnd

/**
 * This class contains the major part of the script utility, mainly in the main() method.
 * Using a class instead of a raw script it better for testability purposes and to use
 * less global variables (in fact none; the only global variable written are for
 * compatibility purposes, e.g. PHPUnit expects $_SERVER['argv']).
 *
 * It is recommended to use the following values for status (in CLI, it must be a number
 * between 0 and 255) and explain it in long help:
 *   - 0 = success
 *   - 1 = missing wiki (similar to HTTP 404)
 *   - 4 = user error, like a missing parameter (similar to HTTP 400)
 *   - 5 = internal error in farm configuration (similar to HTTP 500)
 */
abstract class AbstractMediaWikiFarmScript {

	/** @var int Number of input arguments. */
	public $argc = 0;

	/** @var string[] Input arguments. */
	public $argv = array();

	/** @var string Short usage, displayed on request or error. */
	public $shortUsage = '';

	/** @var string Long usage, displayed on request. */
	public $longUsage = '';

	/** @var string Host name. */
	public $host = '';

	/** @var int Status. */
	public $status = 0;

	/**
	 * Create the object with a copy of $argc and $argv.
	 *
	 * @param int $argc Number of input arguments.
	 * @param string[] $argv Input arguments.
	 * @return AbstractMediaWikiFarmScript
	 */
	function __construct( $argc, $argv ) {

		$this->argc = $argc;
		$this->argv = $argv;
	}

	/**
	 * Get a command line parameter.
	 *
	 * The parameter can be removed from the list.
	 *
	 * @param string|integer $name Parameter name or position (from 0).
	 * @param bool $shift Remove this parameter from the list?
	 * @return string|null Value of the parameter.
	 */
	function getParam( $name, $shift = true ) {

		$posArg = 0;
		$nbArgs = 0;
		$value = null;

		# Search a named parameter
		if( is_string( $name ) ) {

			for( $posArg = 1; $posArg < $this->argc; $posArg++ ) {

				if( substr( $this->argv[$posArg], 0, strlen( $name ) + 3 ) == '--'.$name.'=' ) {
					$value = substr( $this->argv[$posArg], strlen( $name ) + 3 );
					$nbArgs = 1;
					break;
				}
				elseif( $this->argv[$posArg] == '--'.$name && $posArg < $this->argc - 1 ) {
					$value = $this->argv[$posArg+1];
					$nbArgs = 2;
					break;
				}
			}
		}

		# Search a positional parameter
		elseif( is_int( $name ) ) {
			if( $name >= $this->argc ) {
				return null;
			}
			$value = $this->argv[$name];
			$posArg = $name;
			$nbArgs = 1;
		}

		# Remove the parameter from the list
		if( $shift ) {

			$this->argc -= $nbArgs;
			$this->argv = array_merge( array_slice( $this->argv, 0, $posArg ), array_slice( $this->argv, $posArg+$nbArgs ) );
		}

		return $value;
	}

	/**
	 * Display help.
	 *
	 * @param bool $long Show extended usage.
	 * @return void.
	 */
	function usage( $long = false ) {

		# Minimal help, be it an error or not
		if( $this->shortUsage ) {
			echo $this->shortUsage . "\n";
		}

		# Regular help
		if( $long && $this->longUsage ) {
			echo $this->longUsage . "\n";
		}
	}

	/**
	 * Load related global parameters and symbols.
	 *
	 * Note that, these files being loaded in a restricted scope, only the three global variables explicitely
	 * declared as global will be affected (but obviously the variables using $GLOBALS in these files); this
	 * mitigate the risk of declaring too soon some globals.
	 *
	 * @codeCoverageIgnore
	 *
	 * @return void.
	 */
	function load() {

		global $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir, $wgMediaWikiFarmCacheDir, $IP;

		# Default values
		$wgMediaWikiFarmCodeDir = dirname( dirname( dirname( __FILE__ ) ) );
		$wgMediaWikiFarmConfigDir = '/etc/mediawiki';
		$wgMediaWikiFarmCacheDir = '/tmp/mw-cache';

		if( is_file( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/includes/DefaultSettings.php' ) ) {

			$IP = dirname( dirname( dirname( dirname( __FILE__ ) ) ) );
			require "$IP/LocalSettings.php";
		}

		# Specific values
		@include_once dirname( dirname( __FILE__ ) ) . '/config/MediaWikiFarmDirectories.php';

		# Load MediaWikiFarm class symbol
		require_once dirname( dirname( __FILE__ ) ) . '/src/MediaWikiFarm.php';
	}

	/**
	 * Export global variables modified by this class.
	 *
	 * NB: although it can be seen as superfluous, this is required in some cases to wipe off
	 * the presence of MediaWikiFarm. The MediaWiki script 'tests/phpunit/phpunit.php' and PHPUnit
	 * need it (precisely $_SERVER['argv']; the others are for consistency).
	 * Perhaps in the future some other globals will be changed, like in $_SERVER: PWD, PHP_SELF,
	 * SCRIPT_NAME, SCRIPT_FILENAME, PATH_TRANSLATED, if it is needed.
	 *
	 * @return void.
	 */
	function exportArguments() {

		global $argc, $argv;

		$argc = $this->argc;
		$argv = $this->argv;

		$_SERVER['argc'] = $this->argc;
		$_SERVER['argv'] = $this->argv;
	}

	/**
	 * Main program for the script, preliminary part.
	 *
	 * @return bool If false, the main program should return.
	 */
	function premain() {

		# Return usage
		if( $this->argc == 2 && ( $this->argv[1] == '-h' || $this->argv[1] == '--help' ) ) {
			$this->usage( true );
			return false;
		}

		return true;
	}

	/**
	 * Main program for the script, preliminary part.
	 *
	 * This function return true in case of success (else false), but a more detailled status should be indicated in
	 * the object property 'status'.
	 *
	 * @return bool If false, there was an error in the program.
	 */
	abstract function main();

	/**
	 * Post-execution of the main script, only needed in the case 'maintenance/update.php' is run (see main documentation).
	 *
	 * @return void.
	 */
	function restInPeace() {}



	/*
	 * Utility functions
	 * ----------------- */

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $dir Directory path.
	 * @param bool $deleteDir Delete the root directory (or leave it empty).
	 * @return void.
	 */
	static function rmdirr( $dir, $deleteDir = true ) {

		if( !is_dir( $dir ) ) {
			if( is_file( $dir ) || is_link( $dir ) ) {
				unlink( $dir );
			}
			return;
		}

		$files = array_diff( scandir( $dir ), array( '.', '..' ) );
		foreach( $files as $file ) {
			if( is_dir( $dir . '/' . $file ) ) {
				self::rmdirr( $dir . '/' . $file );
			} else {
				unlink( $dir . '/' . $file );
			}
		}

		if( $deleteDir ) {
			rmdir( $dir );
		}
	}

	/**
	 * Recursively copy a directory.
	 *
	 * @param string $source Source path, can be a normal file or a directory.
	 * @param string $dest Destination path, should be a directory.
	 * @param bool $force If true, delete the destination directory before beginning.
	 * @param string[] $blacklist Regular expression to blacklist some files; if begins
	 *                 with '/', only files from the root directory will be considered.
	 * @param string[] $whitelist Regular expression to whitelist only some files; if begins
	 *                 with '/', only files from the root directory will be considered.
	 * @param string $base Internal parameter to track the base directory.
	 * @return void.
	 */
	function copyr( $source, $dest, $force = false, $blacklist = array(), $whitelist = null, $base = '' ) {

		# Return if we are considering a blacklisted file
		foreach( $blacklist as $file ) {
			if( preg_match( '|' . ( $file{0} == '/' ? '^' : '' ) . $file . '$|', $base ) ) {
				return;
			}
		}

		# Return if we are considering a non-whitelisted file
		if( is_array( $whitelist ) && $base ) {
			$isWhitelisted = false;
			foreach( $whitelist as $file ) {
				if( preg_match( '|' . ( $file{0} == '/' ? '^' : '' ) . $file . '$|', $base ) ) {
					$isWhitelisted = true;
					break;
				}
			}
			if( !$isWhitelisted ) {
				return;
			}
		}

		# Delete the destination directory (only in the first call, not in recursion)
		if( $force && is_dir( $dest ) ) {
			self::rmdirr( $dest, false );
		}
		/*elseif( is_dir( $source ) ) {
			$dest = dirname( $dest );
		}*/

		# Leaf: file; stop the recursion by copying the file
		if( is_file( $source ) ) {
			if( !is_dir( $dest ) ) {
				mkdir( $dest );
			}
			copy( $source, $dest . '/' . basename( $source ) );
		}

		# General node: directory - continue the recursion by calling the function on files and directories
		elseif( is_dir( $source ) ) {
			$files = array_diff( scandir( $source ), array( '.', '..' ) );
			if( !is_dir( $dest ) ) {
				mkdir( $dest );
			}
			foreach( $files as $file ) {
				if( is_file( $source . '/' . $file ) ) {
					self::copyr( $source . '/' . $file, $dest, false, $blacklist, $whitelist, $base . '/' . $file );
				}
				elseif( is_dir( $source . '/' . $file ) ) {
					self::copyr( $source . '/' . $file, $dest . '/' . $file, false, $blacklist, $whitelist, $base . '/' . $file );
				}
			}
		}
	}
}
