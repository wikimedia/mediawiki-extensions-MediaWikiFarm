<?php
/**
 * Class MediaWikiFarmScript.
 *
 * @package MediaWikiFarm
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

// @codeCoverageIgnoreStart
require_once dirname( __FILE__ ) . '/AbstractMediaWikiFarmScript.php';
require_once dirname( dirname( __FILE__ ) ) . '/List.php';
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

	/** @var bool $ilent header. */
	public $silent = false;

	/**
	 * Create the object with a copy of $argc and $argv.
	 *
	 * @api
	 * @param int $argc Number of input arguments.
	 * @param string[] $argv Input arguments.
	 * @return MediaWikiFarmScript
	 */
	public function __construct( $argc, $argv ) {

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
	public function main() {

		# Manage mandatory arguments.
		if( !$this->premain() ) {
			return false;
		}

		$this->silent = $this->getParam( '-q', true, true );

		# Get wiki
		$this->host = $this->getParam( 'wiki' );
		$this->path = '';
		$host = $this->host;
		if( $this->host === null ) {
			$this->usage();
			$this->status = 4;
			return false;
		}
		if( strpos( $this->host, '*' ) !== false ) {
			$this->executeMulti();
			return false;
		}
		$posSlash = strpos( $this->host, '/' );
		if( $posSlash !== false ) {
			$this->path = substr( $this->host, $posSlash );
			$this->host = substr( $this->host, 0, $posSlash );
		}

		# Get script
		$this->script = $this->getParam( 1, false );
		$this->script = preg_replace( '/^([a-zA-Z0-9-]+)(\.php)?$/', 'maintenance/$1.php', $this->script );

		if( !$this->script ) {
			$this->usage();
			$this->status = 4;
			return false;
		}

		# Replace the caller script by the MediaWiki script
		$this->getParam( 0 );
		$this->argv[0] = $this->script;


		# Initialise the requested version
		$code = MediaWikiFarm::load( $this->script, $this->host, $this->path );
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
		if( !$this->silent ) {
			echo "\n" .
			     "Wiki:    $host (wikiID: $wikiID; suffix: $suffix)\n" .
			     "Version: $version: $code\n" .
			     "Script:  {$this->script}\n" .
			     "\n";
		}

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
	 * @return void
	 */
	public function restInPeace() {

		if( !array_key_exists( 'wgMediaWikiFarm', $GLOBALS ) || !$GLOBALS['wgMediaWikiFarm'] instanceof MediaWikiFarm ) {
			return;
		}

		# Update version after maintenance/update.php (the only case where another version is given before execution)
		$GLOBALS['wgMediaWikiFarm']->updateVersionAfterMaintenance();
	}

	/**
	 * Execute the script on multiple wikis.
	 */
	public function executeMulti() {

		$spechost = '/^' . str_replace( '\*', '(.*)', preg_quote( $this->host, '/' ) ) . '$/';
		$maxsize = 0;
		$hosts = array();

		$wgMediaWikiFarmList = new MediaWikiFarmList( $GLOBALS['wgMediaWikiFarmConfigDir'], $GLOBALS['wgMediaWikiFarmCacheDir'] );
		foreach( $wgMediaWikiFarmList->getURLsList() as $host ) {
			if( !preg_match( $spechost, $host ) ) {
				continue;
			}
			$hosts[] = $host;
			if( strlen( $host ) > $maxsize ) {
				$maxsize = strlen( $host );
			}
		}
		$command = array(
			self::binary(),
			$this->argv[0],
			'-q',
			'--wiki',
			'',
		);
		$command = array_merge( $command, array_slice( $this->argv, 1 ) );
		$descriptorspec = array(
			1 => array( 'pipe', 'w' ),
			2 => array( 'pipe', 'w' ),
		);

		foreach( $hosts as $host ) {
			$title = false;
			$command[4] = $host;
			$pad = str_pad( '', $maxsize - strlen( $host ) );
			// @codingStandardsIgnoreLine MediaWiki.Usage.ForbiddenFunctions.proc_open
			$res = proc_open( implode( ' ', $command ), $descriptorspec, $pipes );
			if( !$res ) {
				$this->status = 1;
				continue;
			}
			$status = proc_get_status( $res );
			while( $status['running'] ) {
				$status = proc_get_status( $res );
				$stdout = stream_get_contents( $pipes[1] );
				$stderr = stream_get_contents( $pipes[2] );
				if( $stdout ) {
					$stdout = explode( "\n", $stdout );
					$nblines = count( $stdout );
					foreach( $stdout as &$line ) {
						$nblines--;
						if( $nblines ) {
							$line = "$host: $pad$line";
						}
					}
					echo implode( "\n", $stdout );
				}
				if( $stderr ) {
					$stderr = explode( "\n", $stderr );
					$nblines = count( $stderr );
					foreach( $stderr as &$line ) {
						$nblines--;
						if( $nblines ) {
							$line = "$host- $pad$line";
						}
					}
					fwrite( STDERR, implode( "\n", $stderr ) );
				}
			}
			fclose( $pipes[1] );
			fclose( $pipes[2] );
			proc_close( $res );
			if( $status['exitcode'] && $this->status === 0 ) {
				$this->status = (int) $status['exitcode'];
			}
		}
	}

	/**
	 * Obtain the PHP executable.
	 *
	 * @codeCoverageIgnore
	 * @return string Command line of the "best" PHP executable.
	 */
	public static function binary() {

		static $binary = null;

		if( $binary === null ) {
			$binary = defined( 'PHP_OS' ) && PHP_OS == 'Windows' ? 'php' : '/usr/bin/php';
			if( defined( 'PHP_BINARY' ) ) {
				$binary = PHP_BINARY;
			} elseif( array_key_exists( '_SERVER', $GLOBALS ) && array_key_exists( '_', $GLOBALS['_SERVER'] ) && $GLOBALS['_SERVER']['_'] ) {
				$binary = $GLOBALS['_SERVER']['_'];
			}
			if( defined( 'PHP_SAPI' ) && PHP_SAPI == 'phpdbg' ) {
				$binary .= ' -qrr';
			}
		}

		return $binary;
	}
}
