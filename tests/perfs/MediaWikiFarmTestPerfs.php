<?php
/**
 * Class MediaWikiFarmTestPerfs.
 *
 * @package MediaWikiFarm\Tests
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 */

// @codeCoverageIgnoreStart
if( $_SERVER['REMOTE_ADDR'] != '127.0.0.1' && $_SERVER['REMOTE_ADDR'] != '::1' ) {
	exit;
}

require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/src/MediaWikiFarm.php';
// @codeCoverageIgnoreEnd

/**
 * Class used in lieu et place of the main class in order to test perfs.
 */
class MediaWikiFarmTestPerfs extends MediaWikiFarm {

	/** @var float Beginning of time count. */
	protected static $time0 = array();

	/** @var float Resulting counters. */
	protected static $counters = array();

	/** @var float Entry point (bis). */
	protected static $entryPoint = '';

	/** @var float Profile (0=farm, 1=classical installation). */
	protected static $profile = 0;

	/**
	 * To do A/B test with and without farm, this returns 0 or 1.
	 *
	 * It should return alternatively 0 and 1, the value is stored in a file.
	 *
	 * @param string $entryPoint The entry point we want the profile, e.g. 'index.php'.
	 * @return int The profile, either 0 or 1.
	 */
	public static function getEntryPointProfile( $entryPoint ) {

		if( !is_dir( dirname( __FILE__ ) . '/results' ) ) {
			mkdir( dirname( __FILE__ ) . '/results' );
		}
		if( !is_file( dirname( __FILE__ ) . "/results/profile-$entryPoint.php" ) ) {
			file_put_contents( dirname( __FILE__ ) . "/results/profile-$entryPoint.php", "<?php return 0;\n" );
		}

		self::$entryPoint = $entryPoint;
		self::$profile = include dirname( __FILE__ ) . "/results/profile-$entryPoint.php";

		$profile = ( self::$profile + 1 ) % 2;
		file_put_contents( dirname( __FILE__ ) . "/results/profile-$entryPoint.php", "<?php return $profile;\n" );

		return self::$profile;
	}

	/**
	 * Start the counter.
	 *
	 * @param string $name Name of the counter.
	 * @return void
	 */
	public static function startCounter( $name ) {

		self::$time0[$name] = microtime( true );
	}

	/**
	 * Stop the counter.
	 *
	 * @param string $name Name of the counter.
	 * @return void
	 */
	public static function stopCounter( $name ) {

		$time = microtime( true );
		self::$counters[$name] = $time - self::$time0[$name];
	}

	/**
	 * Write down results and select the next profile.
	 *
	 * @return void
	 */
	public static function writeResults() {

		global $IP;

		$profile = self::$profile;
		$entryPoint = self::$entryPoint;

		if( !is_file( dirname( __FILE__ ) . "/results/measures-$entryPoint.php" ) && $profile == 0 ) {

			$server = $GLOBALS['wgMediaWikiFarm']->getVariable( '$SERVER' );
			$localSettingsFile = $GLOBALS['wgMediaWikiFarm']->getConfigFile();

			file_put_contents( dirname( __FILE__ ) . '/results/metadata.php',
				"<?php return array( 'IP' => '$IP', 'server' => '$server', 'MW_CONFIG_FILE' => '$localSettingsFile' );\n"
			);
			file_put_contents( dirname( __FILE__ ) . "/results/measures-$entryPoint.php",
				"<?php return array( 'IP' => '$IP', 'server' => '$server', 'MW_CONFIG_FILE' => '$localSettingsFile', " .
				"0 => array(), 1 => array() );\n"
			);
		}

		# Load existing state
		$measures = include dirname( __FILE__ ) . "/results/measures-$entryPoint.php";

		# Update with current measure
		$measures[$profile][] = self::$counters;

		# Write results
		file_put_contents( dirname( __FILE__ ) . "/results/measures-$entryPoint.php", '<?php return ' . var_export( $measures, true ) . ";\n" );
	}



	/*
	 * Overloaded methods
	 * ------------------ */

	/**
	 * Return the file where must be loaded the configuration from.
	 *
	 * This function returns a file which starts and stops a counter and
	 * launch the original file.
	 *
	 * @mediawikifarm-const
	 *
	 * @return string File where is loaded the configuration.
	 */
	public function getConfigFile() {

		if( !$this->isLocalSettingsFresh() ) {
			return $this->farmDir . '/tests/perfs/main.php';
		}

		if( $this->variables['$VERSION'] ) {
			$localSettingsFile = $this->replaceVariables( 'LocalSettings-$VERSION-$SUFFIX-$WIKIID.php' );
		} else {
			$localSettingsFile = $this->replaceVariables( 'LocalSettings-$SUFFIX-$WIKIID.php' );
		}

		return $this->cacheDir . '/' . $localSettingsFile;
	}

	/**
	 * Create a LocalSettings.php.
	 *
	 * This function is very similar to its parent but adds counters in the file.
	 *
	 * @param array $configuration Array with the schema defined for $this->configuration.
	 * @param bool $isMonoversion Is MediaWikiFarm configured for monoversion?
	 * @param string $preconfig PHP code to be added at the top of the file.
	 * @param string $postconfig PHP code to be added at the end of the file.
	 * @return string Content of the file LocalSettings.php.
	 */
	public static function createLocalSettings( $configuration, $isMonoversion, $preconfig = '', $postconfig = '' ) {

		return parent::createLocalSettings( $configuration, $isMonoversion,
			"# Start counter\nif( class_exists( 'MediaWikiFarmTestPerfs' ) ) {\n\tMediaWikiFarmTestPerfs::startCounter( 'config' );\n}\n",
			"# Stop counter\nif( class_exists( 'MediaWikiFarmTestPerfs' ) ) {\n\tMediaWikiFarmTestPerfs::stopCounter( 'config' );" .
				"\n\tMediaWikiFarmTestPerfs::writeResults();\n}\n"
		);
	}

	/**
	 * Get or compute the configuration (MediaWiki, skins, extensions) for a wiki.
	 *
	 * This function is very similar to its parent but is performance-spied.
	 *
	 * @param bool $force Whether to force loading in $this->configuration even if there is a LocalSettings.php
	 * @return void
	 */
	public function getMediaWikiConfig( $force = false ) {

		self::startCounter( 'compilation' );

		parent::getMediaWikiConfig( $force );

		self::stopCounter( 'compilation' );
	}
}
