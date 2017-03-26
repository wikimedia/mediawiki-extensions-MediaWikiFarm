<?php
/**
 * Class MediaWikiFarmScript.
 *
 * @package MediaWikiFarm
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */

// @codeCoverageIgnoreStart
require_once dirname( __FILE__ ) . '/AbstractMediaWikiFarmScript.php';
// @codeCoverageIgnoreEnd

/**
 * Wrapper around MediaWiki scripts.
 *
 * This class contains the major part of the script utility, mainly in the main() method.
 * Using a class instead of a raw script it better for testability purposes and to use
 * less global variables (in fact none; the only global variable written are for
 * compatibility purposes, e.g. PHPUnit expects $_SERVER['argv']).
 */
class MediaWikiFarmScript extends AbstractMediaWikiFarmScript {

	/** @var string Script name. */
	public $script = '';

	/**
	 * Create the object with a copy of $argc and $argv.
	 *
	 * @api
	 * @param int $argc Number of input arguments.
	 * @param string[] $argv Input arguments.
	 * @return MediaWikiFarmScript
	 */
	function __construct( $argc, $argv ) {

		parent::__construct( $argc, $argv );

		$this->shortUsage = "
    Usage: php {$this->argv[0]} MediaWikiScript --wiki=hostname …

    Parameters:

      - MediaWikiScript: name of the script, e.g. \"maintenance/runJobs.php\"
      - hostname: hostname of the wiki, e.g. \"mywiki.example.org\"
";

		$fullPath = realpath( $this->argv[0] );
		$this->longUsage = "    | Note simple names as \"runJobs\" will be converted to \"maintenance/runJobs.php\".
    |
    | For easier use, you can alias it in your shell:
    |
    |     alias mwscript='php $fullPath'
    |
    | Return codes:
    | 0 = success
    | 1 = missing wiki (similar to HTTP 404)
    | 4 = user error, like a missing parameter (similar to HTTP 400)
    | 5 = internal error in farm configuration (similar to HTTP 500)
";
	}

	/**
	 * Main program for the script.
	 *
	 * This function return true in case of success (else false), but a more detailled status should be indicated in
	 * the object property 'status'.
	 *
	 * @api
	 *
	 * @return bool If false, there was an error in the program.
	 */
	function main() {

		# Manage mandatory arguments.
		if( !$this->premain() ) {
			return false;
		}

		# Get wiki
		$this->host = $this->getParam( 'wiki' );
		if( is_null( $this->host ) ) {
			$this->usage();
			$this->status = 4;
			return false;
		}

		# Get script
		$this->script = $this->getParam( 1, false );
		if( preg_match( '/^[a-zA-Z-]+$/', $this->script ) ) {
			$this->script = 'maintenance/' . $this->script . '.php';
		}

		if( is_null( $this->script ) ) {
			$this->usage();
			$this->status = 4;
			return false;
		}

		# Replace the caller script by the MediaWiki script
		$this->getParam( 0 );
		$this->argv[0] = $this->script;


		# Initialise the requested version
		$code = MediaWikiFarm::load( $this->script, $this->host );
		if( $code == 404 ) {
			$this->status = 1;
			return false;
		} elseif( $code == 500 ) {
			$this->status = 5;
			return false;
		}
		if( !is_file( $this->script ) ) {
			echo "Script not found.\n";
			$this->status = 4;
			return false;
		}


		# Display parameters
		# NB: avoid to use `global $wgMediaWikiFarm;` because it would create the global variable
		# and set it to null if it does not exist
		$wgMediaWikiFarm = $GLOBALS['wgMediaWikiFarm'];
		$wikiID = $wgMediaWikiFarm->getVariable( '$WIKIID' );
		$suffix = $wgMediaWikiFarm->getVariable( '$SUFFIX' );
		$version = $wgMediaWikiFarm->getVariable( '$VERSION' ) ? $wgMediaWikiFarm->getVariable( '$VERSION' ) : 'current';
		$code = $wgMediaWikiFarm->getVariable( '$CODE' );
		echo "
Wiki:    {$this->host} (wikiID: $wikiID; suffix: $suffix)
Version: $version: $code
Script:  {$this->script}

";

		# Export symbols
		$this->exportArguments();
		$GLOBALS['_SERVER']['HTTP_HOST'] = $this->host;

		return true;
	}

	/**
	 * Post-execution of the main script, only needed in the case 'maintenance/update.php' is run (see main documentation).
	 *
	 * @api
	 *
	 * @return void.
	 */
	function restInPeace() {

		if( !array_key_exists( 'wgMediaWikiFarm', $GLOBALS ) || !$GLOBALS['wgMediaWikiFarm'] instanceof MediaWikiFarm ) {
			return;
		}

		# Update version after maintenance/update.php (the only case where another version is given before execution)
		$GLOBALS['wgMediaWikiFarm']->updateVersionAfterMaintenance();
	}
}
