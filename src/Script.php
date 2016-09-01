<?php
/**
 * Script class.
 *
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */

/**
 * This class contains the major part of the script utility, mainly in the main() method.
 * Using a class instead of a raw script it better for testability purposes and to use
 * less global variables (in fact none; the only global variable written are for
 * compatibility purposes, e.g. PHPUnit expects $_SERVER['argv']).
 */
class MediaWikiFarmScript {

	/** @var int Number of input arguments. */
	public $argc = 0;

	/** @var string[] Input arguments. */
	public $argv = array();

	/** @var string Host name. */
	public $host = '';

	/** @var string Script name. */
	public $script = '';

	/** @var int Status. */
	public $status = 0;

	/**
	 * Create the object with a copy of $argc and $argv.
	 *
	 * @param int $argc Number of input arguments.
	 * @param string[] $argv Input arguments.
	 * @return MediaWikiFarmScript
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

				if( substr( $this->argv[$posArg], 0, strlen($name)+3 ) == '--'.$name.'=' ) {
					$value = substr( $this->argv[$posArg], strlen($name)+3 );
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
			if( $name >= $this->argc )
				return null;
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
	 * Display help and return success or error.
	 * 
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 *
	 * @param bool $error Return an error code?
	 * @return void
	 */
	function usage( $error = true ) {

		$fullPath = realpath( $this->argv[0] );

		# Minimal help, be it an error or not
		echo <<<HELP

    Usage: php {$this->argv[0]} MediaWikiScript --wiki=hostname …

    Parameters:

      - MediaWikiScript: name of the script, e.g. "maintenance/runJobs.php"
      - hostname: hostname of the wiki, e.g. "mywiki.example.org"


HELP;

		# Regular help
		if( !$error ) echo <<<HELP
    | Note simple names as "runJobs" will be converted to "maintenance/runJobs.php".
    |
    | For easier use, you can alias it in your shell:
    |
    |     alias mwscript='php $fullPath'


HELP;
	}

	/**
	 * Load related global parameters and symbols
	 *
	 * Note that, these files being loaded in a restricted scope, only the three global variables explicitely
	 * declared as global will be affected (but obviously the variables using $GLOBALS in these files); this
	 * mitigate the risk of declaring too soon some globals.
	 *
	 * @codeCoverageIgnore
	 *
	 * @return void
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
	 * @return void
	 */
	function exportArguments() {

		global $argc, $argv;

		$argc = $this->argc;
		$argv = $this->argv;

		$_SERVER['argc'] = $this->argc;
		$_SERVER['argv'] = $this->argv;
	}

	/**
	 * Main program for the script.
	 *
	 * @return int HTTP-like return code, in [200, 400, 404, 500].
	 */
	function main() {

		# Return usage
		if( $this->argc == 2 && ($this->argv[1] == '-h' || $this->argv[1] == '--help') ) {
			$this->usage( false );
			$this->status = 204;
			return;
		}

		# Get wiki
		$this->host = $this->getParam( 'wiki' );
		if( is_null( $this->host ) ) {
			$this->usage();
			$this->status = 400;
			return;
		}

		# Get script
		$this->script = $this->getParam( 1, false );
		if( preg_match( '/^[a-zA-Z-]+$/', $this->script ) ) {
			$this->script = 'maintenance/' . $this->script . '.php';
		}

		if( is_null( $this->script ) ) {
			$this->usage();
			$this->status = 400;
			return;
		}

		# Replace the caller script by the MediaWiki script
		$this->getParam( 0 );
		$this->argv[0] = $this->script;


		# Initialise the requested version
		$code = MediaWikiFarm::load( $this->script, $this->host );
		if( $code != 200 ) {
			$this->status = $code;
			return;
		}
		if( !is_file( $this->script ) ) {
			echo "Script not found.\n";
			$this->status = 400;
			return;
		}


		# Display parameters
		# NB: avoid to use `global $wgMediaWikiFarm;` because it would create the global variable
		# and set it to null if it does not exist
		$wgMediaWikiFarm = $GLOBALS['wgMediaWikiFarm'];
		$wikiID = $wgMediaWikiFarm->getVariable( '$WIKIID' );
		$suffix = $wgMediaWikiFarm->getVariable( '$SUFFIX' );
		$version = $wgMediaWikiFarm->getVariable( '$VERSION' ) ? $wgMediaWikiFarm->getVariable( '$VERSION' ) : 'current';
		$code = $wgMediaWikiFarm->getVariable( '$CODE' );
		echo <<<PARAMS

Wiki:    {$this->host} (wikiID: $wikiID; suffix: $suffix)
Version: $version: $code
Script:  {$this->script}


PARAMS;


		# Export symbols
		$this->exportArguments();

		$this->status = 200;
	}

	/**
	 * Post-execution of the main script, only needed in the case 'maintenance/update.php' is run (see main documentation).
	 *
	 * @return void
	 */
	function restInPeace() {

		if( !array_key_exists( 'wgMediaWikiFarm', $GLOBALS ) || !$GLOBALS['wgMediaWikiFarm'] instanceof MediaWikiFarm ) {
			return;
		}

		# Update version after maintenance/update.php (the only case where another version is given before execution)
		$GLOBALS['wgMediaWikiFarm']->updateVersionAfterMaintenance();
	}
}
