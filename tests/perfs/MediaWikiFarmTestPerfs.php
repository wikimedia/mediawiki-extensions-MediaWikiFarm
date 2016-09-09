<?php

if( $_SERVER['REMOTE_ADDR'] != '127.0.0.1' && $_SERVER['REMOTE_ADDR'] != '::1' ) {
	exit;
}

class MediaWikiFarmTestPerfs extends MediaWikiFarm {

	/** @var float Beginning of time count. */
	protected static $time0 = array();

	/** @var float Resulting counters. */
	protected static $counters = array();

	/** @var float Entry point (bis). */
	protected static $entryPoint2 = '';

	/** @var float Entry point (bis). */
	protected static $profile = 0;

	/**
	 * To do A/B test with and without farm, this returns 0 or 1.
	 *
	 * It should return alternatively 0 and 1, the value is stored in a file.
	 *
	 * @param string $entryPoint The entry point we want the profile, e.g. 'index.php'.
	 * @return int The profile, either 0 or 1.
	 */
	static function getEntryPointProfile( $entryPoint ) {

		if( !is_dir( dirname( __FILE__ ) . '/results' ) ) {
			mkdir( dirname( __FILE__ ) . '/results' );
		}
		if( !is_file( dirname( __FILE__ ) . "/results/profile-$entryPoint.php" ) ) {
			file_put_contents( dirname( __FILE__ ) . "/results/profile-$entryPoint.php", "<?php return 0;\n" );
		}

		self::$entryPoint2 = $entryPoint;
		self::$profile = include dirname( __FILE__ ) . "/results/profile-$entryPoint.php";

		$profile = (self::$profile+1)%2;
		file_put_contents( dirname( __FILE__ ) . "/results/profile-$entryPoint.php", "<?php return $profile;\n" );

		return self::$profile;
	}

	/**
	 * Start the counter.
	 *
	 * @param string $name Name of the counter.
	 * @return void.
	 */
	static function startCounter( $name ) {

		self::$time0[$name] = microtime( true );
	}

	/**
	 * Stop the counter.
	 *
	 * @param string $name Name of the counter.
	 * @return void.
	 */
	static function stopCounter( $name ) {

		$time = microtime( true );
		self::$counters[$name] = $time - self::$time0[$name];
	}

	/**
	 * Write down results and select the next profile.
	 *
	 * @return void.
	 */
	static function writeResults() {

		global $IP;

		$entryPoint = self::$entryPoint2;

		if( !is_file( dirname( __FILE__ ) . "/results/measures-$entryPoint.php" ) ) {
			file_put_contents( dirname( __FILE__ ) . "/results/measures-$entryPoint.php", "<?php return array( 0 => array(), 1 => array() );\n" );
		}

		# Load existing state
		$profile = self::$profile;
		$measures = include dirname( __FILE__ ) . "/results/measures-$entryPoint.php";

		# Update with current measure
		$measures[$profile][] = self::$counters;

		# Write results
		file_put_contents( dirname( __FILE__ ) . "/results/measures-$entryPoint.php", '<?php return ' . var_export( $measures, true ) . ";\n" );

		if( !is_file( dirname( __FILE__ ) . '/results/metadata.php' ) && $profile == 0 ) {
			$server = $GLOBALS['wgMediaWikiFarm']->getVariable( '$SERVER' );
			file_put_contents( dirname( __FILE__ ) . '/results/metadata.php', "<?php return array( 'IP' => '$IP', 'server' => '$server' );\n" );

			$localSettings = "<?php\n";
			$localSettings .= "\n# Start counter\nMediaWikiFarmTestPerfs::startCounter( 'config' );\n";
			$localSettings .= "\n# General settings\n";
			foreach( $GLOBALS['wgMediaWikiFarm']->getConfiguration( 'settings' ) as $setting => $value ) {
				$localSettings .= "\$$setting = " . var_export( $value, true ) . ";\n";
			}
			foreach( $GLOBALS['wgMediaWikiFarm']->getConfiguration( 'arrays' ) as $setting => $value ) {
				$localSettings .= self::writeArrayAssignment( $value, "\$$setting" );
			}
			$localSettings .= "\n# Skins\n";
			foreach( $GLOBALS['wgMediaWikiFarm']->getConfiguration( 'skins' ) as $skin => $loading ) {
				if( $loading == 'wfLoadSkin' ) {
					$localSettings .= "wfLoadSkin( '$skin' );\n";
				}
				elseif( $loading == 'require_once' ) {
					$localSettings .= "require_once \"\$IP/skins/$skin/$skin.php\";\n";
				}
			}
			$localSettings .= "\n# Extensions\n";
			foreach( $GLOBALS['wgMediaWikiFarm']->getConfiguration( 'extensions' ) as $extension => $loading ) {
				if( $loading == 'wfLoadExtension' ) {
					$localSettings .= "wfLoadExtension( '$extension' );\n";
				}
				elseif( $loading == 'require_once' ) {
					$localSettings .= "require_once \"\$IP/extensions/$extension/$extension.php\";\n";
				}
			}
			$localSettings .= "\n# Included files\n";
			foreach( $GLOBALS['wgMediaWikiFarm']->getConfiguration( 'execFiles' ) as $execFile ) {
				$localSettings .= "include '$execFile';\n";
			}
			$localSettings .= "\n# Stop counter\nMediaWikiFarmTestPerfs::stopCounter( 'config' );\nMediaWikiFarmTestPerfs::writeResults();\n";
			file_put_contents( dirname( __FILE__ ) . '/results/LocalSettings.php', $localSettings );

			#if( !is_file( $IP . '/LocalSettings.php' ) ) {
			copy( dirname( __FILE__ ) . '/results/LocalSettings.php', $IP . '/LocalSettings.php' );
			#}
		}
	}

	static function writeArrayAssignment( $array, $prefix ) {

		$result = '';
		$isList = (count( array_diff( array_keys( $array ), range( 0, count( $array ) ) ) ) == 0);
		foreach( $array as $key => $value ) {
			$newkey = '[' . var_export( $key, true ) . ']';
			if( $isList ) {
				$result .= $prefix . '[] = ' . var_export( $value, true ) . ";\n";
			} elseif( is_array( $value ) ) {
				$result .= self::writeArrayAssignment( $value, $prefix . $newkey );
			} else {
				$result .= $prefix . $newkey . ' = ' . var_export( $value, true ) . ";\n";
			}
		}

		return $result;
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
	 * @mediawikifarm-idempotent
	 *
	 * @return string File where is loaded the configuration.
	 */
	function getConfigFile() {

		return $this->farmDir . '/tests/perfs/main.php';
	}

	function loadMediaWikiConfig() {

		MediaWikiFarmTestPerfs::startCounter( 'compilation' );

		parent::loadMediaWikiConfig();

		MediaWikiFarmTestPerfs::stopCounter( 'compilation' );
		MediaWikiFarmTestPerfs::startCounter( 'loading-require_once-skins' );
	}

	function loadSkinsConfig() {

		MediaWikiFarmTestPerfs::stopCounter( 'loading-require_once-skins' );
		MediaWikiFarmTestPerfs::startCounter( 'loading-wfLoadSkins' );

		parent::loadSkinsConfig();

		MediaWikiFarmTestPerfs::stopCounter( 'loading-wfLoadSkins' );
		MediaWikiFarmTestPerfs::startCounter( 'loading-require_once-extensions' );
	}

	function loadExtensionsConfig() {

		MediaWikiFarmTestPerfs::stopCounter( 'loading-require_once-extensions' );
		MediaWikiFarmTestPerfs::startCounter( 'loading-wfLoadExtensions' );

		parent::loadExtensionsConfig();

		MediaWikiFarmTestPerfs::stopCounter( 'loading-wfLoadExtensions' );
		MediaWikiFarmTestPerfs::startCounter( 'loading-execFiles' );
	}

	function getMediaWikiConfig() {

		MediaWikiFarmTestPerfs::startCounter( 'loading-getMediaWikiConfig' );

		parent::getMediaWikiConfig();

		MediaWikiFarmTestPerfs::stopCounter( 'loading-getMediaWikiConfig' );
	}
}
