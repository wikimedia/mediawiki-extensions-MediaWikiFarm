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
		// @codingStandardsIgnoreStart MediaWiki.Usage.DirUsage.FunctionFound
		require_once dirname( dirname( __FILE__ ) ) . '/src/MediaWikiFarm.php';
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Export global variables modified by this class.
	 *
	 * NB: although it can be seen as superfluous, this is required in some cases to wipe off
	 * the presence of MediaWikiFarm. The MediaWiki script 'tests/phpunit/phpunit.php' and PHPUnit
	 * need it (precisely $_SERVER['argv']; the other are for consistency).
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
	 * Although it returns void, the 'status' property can say if there was an error or not,
	 * and if it becomes different than 0, the main program will (should) return.
	 *
	 * @return void.
	 */
	function premain() {

		# Return usage
		if( $this->argc == 2 && ( $this->argv[1] == '-h' || $this->argv[1] == '--help' ) ) {
			$this->usage( true );
			$this->status = 204;
			return;
		}
	}

	/**
	 * Main program for the script, preliminary part.
	 *
	 * Although it returns void, the 'status' property says if there was an error or not.
	 *
	 * @return void.
	 */
	abstract function main();

	/**
	 * Main program for the script, postliminary part.
	 *
	 * @return void.
	 */
	function postmain() {

		# Export symbols
		$this->exportArguments();

		$this->status = 200;
	}

	/**
	 * Post-execution of the main script, only needed in the case 'maintenance/update.php' is run (see main documentation).
	 *
	 * @return void.
	 */
	function restInPeace() {}
}
