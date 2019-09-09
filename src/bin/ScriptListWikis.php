<?php
/**
 * Class MediaWikiFarmScriptListWikis.
 *
 * @package MediaWikiFarm
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

// @codeCoverageIgnoreStart
require_once dirname( __FILE__ ) . '/AbstractMediaWikiFarmScript.php';
require_once dirname( dirname( __FILE__ ) ) . '/List.php';
// @codeCoverageIgnoreEnd

/**
 * Compute the list of wikis.
 */
class MediaWikiFarmScriptListWikis extends AbstractMediaWikiFarmScript {

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
    Usage: php {$this->argv[0]}
";

		$fullPath = realpath( $this->argv[0] );
		$this->longUsage = "    | For easier use, you can alias it in your shell:
    |
    |     alias mwlistwikis='php $fullPath'
    |
    | Return codes:
    | 0 = success
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

		global $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCacheDir;

		# Manage mandatory arguments.
		if( !$this->premain() ) {
			return false;
		}

		# Compute the list
		$wgMediaWikiFarmList = new MediaWikiFarmList( $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCacheDir );
		$urlsList = $wgMediaWikiFarmList->getURLsList();

		foreach( $urlsList as $url ) {
			echo $url . "\n";
		}

		return true;
	}
}
