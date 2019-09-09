<?php
/**
 * Classes MediaWikiFarm and MWFConfigurationException.
 *
 * @package MediaWikiFarm
 * @license GPL-3.0-or-later
 * @license AGPL-3.0-or-later
 *
 * @codingStandardsIgnoreFile MediaWiki.Files.OneClassPerFile.MultipleFound
 *
 * DEVELOPERS: given its nature, this extension must work with all MediaWiki versions and
 *             PHP 5.2+, so please do not use "new" syntaxes (namespaces, arrays with [], etc.).
 */

// @codeCoverageIgnoreStart
require_once dirname( __FILE__ ) . '/Utils.php';
require_once dirname( __FILE__ ) . '/MediaWikiFarmConfiguration.php';
// @codeCoverageIgnoreEnd

/**
 * Exception triggered when a configuration file is “wrong” or other internal issue.
 *
 * A wrong file can be a missing file, a badly-formatted file, or a file which does not respect
 * the schema. An internal issue can be when the webserver does not pass HTTP_HOST to PHP.
 */
class MWFConfigurationException extends RuntimeException {}


/**
 * Main runtime class.
 *
 * This class computes the configuration of a specific wiki from a set of configuration files.
 * The configuration is composed of the list of authorised wikis and different configuration
 * files, possibly with different permissions. Files can be written in YAML, JSON, or PHP.
 *
 * Various methods are tagged with nonstandard PHPDoc tags:
 *   @mediawikifarm-const the function is garanted not to change any object property, even static properties;
 *   @mediawikifarm-idempotent the function is garanted to always return the same result, always set the same
 *                             values for the affected object properties, always set the same values for global
 *                             variables, and always write the same content in the same files, provided the
 *                             configuration files did not change  andfor given same input parameters, but
 *                             independently of the current object state or global variables.
 */
class MediaWikiFarm {

	/*
	 * Properties
	 * ---------- */

	/** @var array State: EntryPoint (string) and InnerMediaWiki (bool). */
	protected $state = array(
		'EntryPoint' => '',
		'InnerMediaWiki' => null,
	);

	/** @var string Farm code directory. */
	protected $farmDir = '';

	/** @var string Farm configuration directory. */
	protected $configDir = '';

	/** @var string|null MediaWiki code directory, where each subdirectory is a MediaWiki installation. */
	protected $codeDir = null;

	/** @var string|false MediaWiki cache directory. */
	protected $cacheDir = '/tmp/mw-cache';

	/** @var array Configuration for this farm. */
	protected $farmConfig = array(
		'coreconfig' => array(),
	);

	/** @var string[] Variables related to the current request. */
	protected $variables = array(
		'$FARM' => '',
		'$SERVER' => '',
		'$SUFFIX' => '',
		'$WIKIID' => '',
		'$VERSION' => null,
		'$CODE' => '',
	);

	/** @var MediaWikiFarmConfiguration|null Object containing the configuration of the current (single) wiki. */
	protected $configuration = null;

	/** @var array Logs. */
	public $log = array();



	/*
	 * Accessors
	 * --------- */

	/**
	 * Get the inner state.
	 *
	 * @api
	 *
	 * @param string $key Parameter name.
	 * @return mixed|null Requested state or null if nonexistant.
	 */
	public function getState( $key ) {
		if( array_key_exists( $key, $this->state ) ) {
			return $this->state[$key];
		}
		return null;
	}

	/**
	 * Get farm this farm code directory.
	 *
	 * @api
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 *
	 * @return string|null Farm code directory.
	 */
	public function getFarmDir() {
		return $this->farmDir;
	}

	/**
	 * Get config directory.
	 *
	 * @api
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 *
	 * @return string|null Config directory.
	 */
	public function getConfigDir() {
		return $this->configDir;
	}

	/**
	 * Get code directory, where subdirectories are MediaWiki versions.
	 *
	 * @api
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 *
	 * @return string|null Code directory, or null if currently installed as a classical extension (monoversion installation).
	 */
	public function getCodeDir() {
		return $this->codeDir;
	}

	/**
	 * Get cache directory.
	 *
	 * @api
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 *
	 * @return string|false Cache directory.
	 */
	public function getCacheDir() {
		return $this->cacheDir;
	}

	/**
	 * Get the farm configuration.
	 *
	 * This is the farm configuration extracted from the farm configuration file, unchanged.
	 * Variables are the alter-ego variable adapted to the current request.
	 *
	 * @api
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 *
	 * @return array Farm configuration.
	 */
	public function getFarmConfiguration() {
		return $this->farmConfig;
	}

	/**
	 * Get the variables related to the current request.
	 *
	 * This associative array contains two data types:
	 *   - lowercase keys: parts of the URL captured by the farm regular expression;
	 *   - uppercase keys: variables from the farm configuration adapted to the current request.
	 * All keys are prefixed by '$'.
	 *
	 * @api
	 * @mediawikifarm-const
	 *
	 * @return string[] Request variables.
	 */
	public function getVariables() {
		return $this->variables;
	}

	/**
	 * Get a variable related to the current request.
	 *
	 * @api
	 * @mediawikifarm-const
	 *
	 * @param string $varname Variable name (prefixed with '$').
	 * @param mixed $default Default value returned when the variable does not exist.
	 * @return string|mixed Requested variable or default value if the variable does not exist.
	 */
	public function getVariable( $varname, $default = null ) {
		return array_key_exists( $varname, $this->variables ) ? $this->variables[$varname] : $default;
	}

	/**
	 * Get MediaWiki configuration.
	 *
	 * This associative array contains four sections:
	 *   - 'settings': associative array of MediaWiki configuration (e.g. 'wgServer' => '//example.org');
	 *   - 'arrays': associative array of MediaWiki configuration of type array (e.g. 'wgGroupPermissions' => array( 'edit' => false ));
	 *   - 'extensions': list of extensions and skins (e.g. 0 => array( 'ParserFunctions', 'extension', 'wfLoadExtension' ));
	 *   - 'composer': list of Composer-installed extensions and skins (e.g. 0 => 'ExtensionSemanticMediaWiki');
	 *   - 'execFiles': list of PHP files to execute at the end.
	 *
	 * @api
	 * @mediawikifarm-const
	 *
	 * @param string|false|null $key Key of the wanted section or false for the whole array or null for the object configuration.
	 * @param string|false $key2 Subkey (specific to each entry) or false for the whole entry.
	 * @return array|MediaWikiFarmConfiguration MediaWiki configuration, either entire, either a part depending on the parameter, or the configuration object.
	 */
	public function getConfiguration( $key = false, $key2 = false ) {
		if( $this->configuration === null ) {
			$that =& $this;
			$this->configuration = new MediaWikiFarmConfiguration( $that );
		}
		if( $key === null ) {
			return $this->configuration;
		}
		return $this->configuration->getConfiguration( $key, $key2 );
	}



	/*
	 * Entry points in normal operation
	 * -------------------------------- */

	/**
	 * This is the main function to initialise the MediaWikiFarm and check for existence of the wiki.
	 *
	 * In multiversion installations, this function is called very early during the loading,
	 * even before MediaWiki is loaded (first function called by multiversion-dedicated entry points
	 * like `www/index.php`).
	 *
	 * @api
	 *
	 * @param string $entryPoint Name of the entry point, e.g. 'index.php', 'load.php'…
	 * @param string|null $host Host name (string) or null to use the global variables HTTP_HOST or SERVER_NAME.
	 * @param string|null $path Path (string) or null to use the global variables REQUEST_URI.
	 * @param array $state Parameters, see object property $state.
	 * @param array $environment Environment which determines a given configuration.
	 * @return string $entryPoint Identical entry point as passed in input.
	 */
	public static function load( $entryPoint = '', $host = null, $path = null, $state = array(), $environment = array() ) {

		global $wgMediaWikiFarm;
		global $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir, $wgMediaWikiFarmCacheDir, $wgMediaWikiFarmSyslog;

		try {
			# Initialise object
			$wgMediaWikiFarm = new MediaWikiFarm( $host, $path,
				$wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir, $wgMediaWikiFarmCacheDir,
				array_merge( $state, array( 'EntryPoint' => $entryPoint ) ),
				$environment
			);

			# Check existence
			$exists = $wgMediaWikiFarm->checkExistence();

			# Compile configuration
			if( $exists ) {
				$wgMediaWikiFarm->compileConfiguration();
			}
		}
		catch( Exception $exception ) {

			if( !headers_sent() ) {
				$httpProto = array_key_exists( 'SERVER_PROTOCOL', $_SERVER ) && $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1' ? 'HTTP/1.1' : 'HTTP/1.0'; // @codeCoverageIgnore
				header( "$httpProto 500 Internal Server Error" ); // @codeCoverageIgnore
			}

			self::issueLog( self::prepareLog( $wgMediaWikiFarmSyslog, $wgMediaWikiFarm, $exception ) );
			return 500;
		}

		if( !$exists ) {

			# Display an informational page when the requested wiki doesn’t exist, only when a page was requested, not a resource, to avoid waste resources
			if( !headers_sent() ) {
				$httpProto = array_key_exists( 'SERVER_PROTOCOL', $_SERVER ) && $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1' ? 'HTTP/1.1' : 'HTTP/1.0'; // @codeCoverageIgnore
				header( "$httpProto 404 Not Found" ); // @codeCoverageIgnore
			}
			if( $entryPoint == 'index.php' && array_key_exists( 'HTTP404', $wgMediaWikiFarm->farmConfig ) ) {
				$file404 = $wgMediaWikiFarm->replaceVariables( $wgMediaWikiFarm->farmConfig['HTTP404'] );
				if( is_file( $file404 ) ) {
					include $file404;
				}
			}

			self::issueLog( self::prepareLog( $wgMediaWikiFarmSyslog, $wgMediaWikiFarm ) );
			return 404;
		}

		# Go to version directory
		if( getcwd() != $wgMediaWikiFarm->variables['$CODE'] ) {
			chdir( $wgMediaWikiFarm->variables['$CODE'] );
		}

		# Define config callback to avoid creating a stub LocalSettings.php (experimental)
		// define( 'MW_CONFIG_CALLBACK', 'MediaWikiFarm::loadConfig' );

		# Define config file to avoid creating a stub LocalSettings.php
		if( !defined( 'MW_CONFIG_FILE' ) ) {
			/**
			 * Definition of a specific file in lieu of LocalSettings.php.
			 *
			 * @since MediaWiki 1.17
			 * @package MediaWiki
			 */
			define( 'MW_CONFIG_FILE', $wgMediaWikiFarm->getConfigFile() ); // @codeCoverageIgnore
		}

		# Define we are now inside MediaWiki
		$wgMediaWikiFarm->state['InnerMediaWiki'] = true;

		self::issueLog( self::prepareLog( $wgMediaWikiFarmSyslog, $wgMediaWikiFarm ) );
		return 200;
	}



	/*
	 * Functions of interest in normal operations
	 * ------------------------------------------ */

	/**
	 * Check the existence of the wiki, given variables values and files listing existing wikis.
	 *
	 * A wiki exists if:
	 *   - variables with a file attached are defined, and
	 *   - a wikiID can be computed, and
	 *   - a version is found and does exist, and
	 *   - the various properties of the wiki are defined.
	 *
	 * @api
	 *
	 * @return bool The wiki does exist.
	 * @throws MWFConfigurationException
	 * @throws InvalidArgumentException
	 */
	public function checkExistence() {

		# In the multiversion case, informations are already loaded and nonexistent wikis are already verified
		if( $this->variables['$CODE'] ) {
			return true;
		}

		# Replace variables in the host name and possibly retrieve the version
		$explicitExistence = $this->checkHostVariables();
		if( $explicitExistence === false ) {
			return false;
		}

		# Set wikiID, the unique identifier of the wiki
		$this->setVariable( 'suffix', true );
		$this->setVariable( 'wikiID', true );

		# Set the version of the wiki
		if( !$this->setVersion( (bool) $explicitExistence ) ) {
			return false;
		}

		# Set other variables of the wiki
		$this->setOtherVariables();

		# Cache the result
		if( $this->cacheDir ) {
			$variables = $this->variables;
			$variables['$CORECONFIG'] = $this->farmConfig['coreconfig'];
			$variables['$CONFIG'] = $this->farmConfig['config'];
			MediaWikiFarmUtils::cacheFile( $variables, $this->variables['$SERVER'] . '.php', $this->cacheDir . '/wikis' );

			# Cache the list of wikis
			$hosts = $this->readFile( 'wikis.php', $this->cacheDir, false );
			$host = substr( $variables['$SERVER'] . '/', 0, strpos( $variables['$SERVER'] . '/', '/' ) );
			$path = substr( $variables['$SERVER'], strlen( $host ) );
			if( !is_array( $hosts ) ) {
				$hosts = array();
			}
			if( !array_key_exists( $host, $hosts ) || !preg_match( $hosts[$host], $path . '/' ) ) {
				$path = preg_quote( $path, '/' );
				$path = ( array_key_exists( $host, $hosts ) ? substr( $hosts[$host], 3, -4 ) . '|' : '' ) . $path;
				$hosts[$host] = '/^(' . $path . ')\\//';
				MediaWikiFarmUtils::cacheFile( $hosts, 'wikis.php', $this->cacheDir );
			}
		}

		return true;
	}

	/**
	 * Compile configuration as much as it can.
	 *
	 * @api
	 */
	public function compileConfiguration() {

		if( $this->isLocalSettingsFresh() ) {

			$composerFile = $this->readFile( $this->variables['$SERVER'] . '.php', $this->cacheDir . '/composer', false );
			if( is_array( $composerFile ) ) {
				if( $this->configuration === null ) {
					$that =& $this;
					$this->configuration = new MediaWikiFarmConfiguration( $that );
				}
				$this->configuration->setComposer( $composerFile );
			}

			return;
		}

		# Init configuration object
		if( $this->configuration === null ) {
			$that =& $this;
			$this->configuration = new MediaWikiFarmConfiguration( $that );
		}

		# Transform configuration files to a unique configuration
		if( count( $this->getConfiguration( 'settings' ) ) == 0 ) {

			# Compile the configuration
			$this->configuration->populateSettings();

			# Activate the extensions (possibly not finished here
			# if we do not know the entire MediaWiki environment)
			$this->configuration->activateExtensions();

			# Save Composer key if available
			if( $this->cacheDir && !array_key_exists( 'unreadable-file', $this->log ) ) {
				MediaWikiFarmUtils::cacheFile( $this->getConfiguration( 'composer' ),
					$this->variables['$SERVER'] . '.php',
					$this->cacheDir . '/composer'
				);
			}
		}

		# When the MediaWiki environment is set
		if( $this->state['InnerMediaWiki'] ) {

			# Set environment
			$this->configuration->setEnvironment();

			# Finalise the extension activation
			$this->configuration->activateExtensions();

			# Create the final LocalSettings.php
			if( $this->cacheDir && !array_key_exists( 'unreadable-file', $this->log ) ) {
				MediaWikiFarmUtils::cacheFile( MediaWikiFarmConfiguration::createLocalSettings( $this->getConfiguration(), (bool) $this->codeDir ),
					$this->variables['$SERVER'] . '.php',
					$this->cacheDir . '/LocalSettings'
				);
			}
		}
	}

	/**
	 * This function loads MediaWiki configuration.
	 *
	 * Parameters are written in global variables, skins and extensions are loaded with
	 * the MediaWiki functions wfLoadSkin/wfLoadExtension (introduced in MediaWiki 1.25), and
	 * plain PHP executables files are executed at the end.
	 *
	 * This function can be called either by the file src/main.php or by MediaWiki through the
	 * constant MW_CONFIG_CALLBACK (introduced in MediaWiki 1.15).
	 *
	 * WARNING: it does not load skins and extensions which require the require_once mechanism.
	 * Rationale: it is not possible given it is not the global scope, you have to use another
	 * mechanism if you need to load extensions/skins with require_once. The nearest path found
	 * to still load global variables (there is no issue for functions and classes, they are
	 * always in the global scopes) was to use extract( $GLOBALS, EXTR_REFS ) but newly-created
	 * variables can not be detected and hence exported to global scope (and it is the main use
	 * case: extensions declaring new parameters as global variables); the only case where it
	 * would work is if the extension declares their global variables with
	 * $GLOBALS['wgMyExtensionMyParameter'], but it is quite rare.
	 *
	 * @api
	 *
	 * @return void
	 */
	public function loadMediaWikiConfig() {

		# Set general parameters as global variables
		foreach( $this->getConfiguration( 'settings' ) as $setting => $value ) {

			$GLOBALS[$setting] = $value;
		}

		# Merge general array parameters into global variables
		foreach( $this->getConfiguration( 'arrays' ) as $setting => $value ) {

			if( !array_key_exists( $setting, $GLOBALS ) ) {
				$GLOBALS[$setting] = array();
			}
			$GLOBALS[$setting] = MediaWikiFarmUtils::arrayMerge( $GLOBALS[$setting], $value );
		}

		# Load extensions and skins with the wfLoadExtension/wfLoadSkin mechanism
		foreach( $this->getConfiguration( 'extensions' ) as $key => $extension ) {

			if( $extension[2] == 'wfLoadExtension' ) {

				if( $key != 'ExtensionMediaWikiFarm' ) {
					wfLoadExtension( $extension[0] );
				} else {
					wfLoadExtension( 'MediaWikiFarm', $this->farmDir . '/extension.json' );
				}
			}
			elseif( $extension[2] == 'wfLoadSkin' ) {

				wfLoadSkin( $extension[0] );
			}
			elseif( $extension[2] == 'require_once' && $key == 'ExtensionMediaWikiFarm' ) {
				self::selfRegister();
			}
		}
	}

	/**
	 * Register MediaWikiFarm with require_once mechanism.
	 */
	public static function selfRegister() {

		$dir = dirname( dirname( __FILE__ ) );

		$json = file_get_contents( $dir . '/extension.json' );
		if( $json === false ) {
			return;
		}

		$json = json_decode( $json, true );
		if( $json === null ) {
			return;
		}

		$GLOBALS['wgExtensionCredits'][$json['type']][] = array(
			'path' => $dir . '/MediaWikiFarm.php',
			'name' => $json['name'],
			'version' => $json['version'],
			'author' => $json['author'],
			'url' => $json['url'],
			'descriptionmsg' => $json['descriptionmsg'],
			'license-name' => $json['license-name'],
		);

		$GLOBALS['wgAutoloadClasses'] = array_merge( $GLOBALS['wgAutoloadClasses'], $json['AutoloadClasses'] );
		$GLOBALS['wgMessagesDirs']['MediaWikiFarm'] = $dir . '/' . $json['MessagesDirs']['MediaWikiFarm'][0];
		foreach( $json['Hooks'] as $hook => $func ) {
			if( !array_key_exists( $hook, $GLOBALS['wgHooks'] ) ) {
				$GLOBALS['wgHooks'][$hook] = array();
			}
			$GLOBALS['wgHooks'][$hook] = array_merge( $GLOBALS['wgHooks'][$hook], $json['Hooks'][$hook] );
		}
	}

	/**
	 * Synchronise the version in the 'expected version' and deployment files.
	 *
	 * @api
	 *
	 * @return void
	 */
	public function updateVersionAfterMaintenance() {

		if( $this->variables['$VERSION'] ) {
			$this->updateVersion( $this->variables['$VERSION'] );
		}
	}

	/**
	 * Return the file where must be loaded the configuration from.
	 *
	 * This function is important to avoid the two parts of the extension (checking of
	 * existence and loading of configuration) are located in the same directory in the
	 * case mono- and multi-version installations are mixed. Without it, this class
	 * could be defined by two different files, and PHP doesn’t like it.
	 * Additionally, it returns either the "template" LocalSettings.php (src/main.php)
	 * or the cached per-wiki LocalSettings.php depending if the cache is fresh.
	 *
	 * @api
	 * @mediawikifarm-const
	 *
	 * @return string File where is loaded the configuration.
	 */
	public function getConfigFile() {

		if( !$this->isLocalSettingsFresh() ) {
			return $this->farmDir . '/src/main.php';
		}

		return $this->cacheDir . '/LocalSettings/' . $this->variables['$SERVER'] . '.php';
	}

	/**
	 * Prepare log messages and open syslog channel.
	 *
	 * @internal
	 *
	 * @param string|false $wgMediaWikiFarmSyslog Syslog tag or deactivate logging.
	 * @param MediaWikiFarm|null $wgMediaWikiFarm MediaWikiFarm object if any, in order to retrieve existing log messages.
	 * @param Exception|Throwable|null $exception Caught exception if any.
	 * @return string[] All log messages ready to be sent to syslog.
	 */
	public static function prepareLog( $wgMediaWikiFarmSyslog, $wgMediaWikiFarm, $exception = null ) {

		$log = array();
		if( $wgMediaWikiFarmSyslog === false || $wgMediaWikiFarmSyslog === null ) {
			return $log;
		}

		if( ( $wgMediaWikiFarm instanceof MediaWikiFarm && count( $wgMediaWikiFarm->log ) ) || $exception instanceof Exception || $exception instanceof Throwable ) {

			# Init logging
			if( !is_string( $wgMediaWikiFarmSyslog ) ) {
				$wgMediaWikiFarmSyslog = 'mediawikifarm';
				$log[] = 'Logging parameter must be false or a string';
			}
			if( !openlog( $wgMediaWikiFarmSyslog, LOG_CONS, LOG_USER ) ) {
				$log[] = 'Unable to initialise logging'; // @codeCoverageIgnore
			}

			# Log exception
			if( $exception instanceof Exception || $exception instanceof Throwable ) {
				$log[] = $exception->getMessage();
			}

			# Add logging issues
			if( $wgMediaWikiFarm instanceof MediaWikiFarm ) {
				$wgMediaWikiFarm->log = array_merge( $log, $wgMediaWikiFarm->log );
				$log = $wgMediaWikiFarm->log;
			}

		}

		return $log;
	}

	/**
	 * Issue log messages to syslog.
	 *
	 * @internal
	 * @codeCoverageIgnore
	 *
	 * @param string[] $log Log messages.
	 * @return void
	 */
	public static function issueLog( $log ) {

		foreach( $log as $id => $error ) {
			if( is_numeric( $id ) ) {
				syslog( LOG_ERR, $error );
			}
		}

		if( count( $log ) ) {
			closelog();
		}
	}

	/*
	 * Internals
	 * --------- */

	/**
	 * Construct a MediaWiki farm.
	 *
	 * This constructor sets the directories (configuration and code) and select the right
	 * farm depending of the host (when there are multiple farms). In case of error (unreadable
	 * directory or file, or unrecognized host), an InvalidArgumentException is thrown.
	 *
	 * @internal
	 *
	 * @param string|null $host Requested host.
	 * @param string|null $path Requested path.
	 * @param string $configDir Configuration directory.
	 * @param string|null $codeDir Code directory; if null, the current MediaWiki installation is used.
	 * @param string|false $cacheDir Cache directory; if false, the cache is disabled.
	 * @param array $state Inner state: EntryPoint (string) and InnerMediaWiki (bool).
	 * @param array $environment MediaWiki environment: ExtensionRegistry (bool).
	 * @return MediaWikiFarm
	 * @throws MWFConfigurationException When no farms.yml/php/json is found.
	 * @throws InvalidArgumentException When wrong input arguments are passed.
	 */
	public function __construct( $host, $path, $configDir, $codeDir = null, $cacheDir = false, $state = array(), $environment = array() ) {

		# Default value for host
		# Warning: do not use $GLOBALS['_SERVER']['HTTP_HOST']: bug with PHP7: it is not initialised in early times of a script
		# Rationale: nginx put the regex of the server name in SERVER_NAME; HTTP_HOST seems to be always clean from this side,
		#            and it will be checked against available hosts in constructor
		if( is_null( $host ) ) {
			if( array_key_exists( 'HTTP_HOST', $_SERVER ) && $_SERVER['HTTP_HOST'] ) {
				$host = (string) $_SERVER['HTTP_HOST'];
			} elseif( array_key_exists( 'SERVER_NAME', $_SERVER ) && $_SERVER['SERVER_NAME'] ) {
				$host = (string) $_SERVER['SERVER_NAME'];
			} else {
				throw new InvalidArgumentException( 'Undefined host' );
			}
		}

		# Default value for path
		if( !is_string( $path ) ) {
			if( array_key_exists( 'REQUEST_URI', $_SERVER ) ) {
				$path = (string) $_SERVER['REQUEST_URI'];
			} else {
				$path = '';
			}
		}

		# Check parameters
		if( !is_string( $host ) ) {
			throw new InvalidArgumentException( 'Missing host name in constructor' );
		}
		if( !is_string( $configDir ) || !is_dir( $configDir ) ) {
			throw new InvalidArgumentException( 'Invalid directory for the farm configuration' );
		}
		if( !is_null( $codeDir ) && ( !is_string( $codeDir ) || !is_dir( $codeDir ) ) ) {
			throw new InvalidArgumentException( 'Code directory must be null or a directory' );
		}
		if( !is_string( $cacheDir ) && $cacheDir !== false ) {
			throw new InvalidArgumentException( 'Cache directory must be false or a directory' );
		}
		if( !is_array( $state ) ) {
			throw new InvalidArgumentException( 'State must be an array' );
		} else {
			if( array_key_exists( 'EntryPoint', $state ) && !is_string( $state['EntryPoint'] ) ) {
				throw new InvalidArgumentException( 'Entry point must be a string' );
			}
			if( array_key_exists( 'InnerMediaWiki', $state ) && !is_bool( $state['InnerMediaWiki'] ) ) {
				throw new InvalidArgumentException( 'InnerMediaWiki state must be a bool' );
			}
		}
		if( !is_array( $environment ) ) {
			throw new InvalidArgumentException( 'Environment must be an array' );
		} else {
			if( array_key_exists( 'ExtensionRegistry', $environment ) && !is_bool( $environment['ExtensionRegistry'] ) ) {
				throw new InvalidArgumentException( 'ExtensionRegistry parameter must be a bool' );
			}
		}

		# Sanitise host and path
		$host = preg_replace( '/[^a-zA-Z0-9\\._-]/', '', $host );
		$path = '/' . substr( $path, 1 );
		if( $path === '/' ) {
			$path = '';
		}

		# Set parameters
		$this->farmDir = dirname( dirname( __FILE__ ) );
		$this->configDir = $configDir;
		$this->codeDir = $codeDir;
		$this->cacheDir = $cacheDir;
		$this->state = array_merge( array(
			'EntryPoint' => '',
			'InnerMediaWiki' => null,
		), $state );
		if( $environment ) {
			$environment = array_merge( array(
				'ExtensionRegistry' => null,
			), $environment );

			$that =& $this;
			$this->configuration = new MediaWikiFarmConfiguration( $that );
			$this->configuration->setEnvironment( 'ExtensionRegistry', $environment['ExtensionRegistry'] );
		}

		# Special case for MediaWiki update
		if( ( PHP_SAPI == 'cli' || PHP_SAPI == 'phpdbg' ) && $this->state['EntryPoint'] == 'maintenance/update.php' ) {
			$this->cacheDir = false;
		}

		# Shortcut loading
		// @codingStandardsIgnoreLine MediaWiki.ControlStructures.AssignmentInControlStructures.AssignmentInControlStructures
		if( $this->cacheDir && ( $hosts = $this->readFile( 'wikis.php', $this->cacheDir, false ) )
		    && array_key_exists( $host, $hosts ) && preg_match( $hosts[$host], $path . '/', $matches )
		    && ( $result = $this->readFile( $host . $matches[1] . '.php', $this->cacheDir . '/wikis', false ) ) ) {
			$path = $matches[1];
			$fresh = true;
			$myfreshness = filemtime( $this->cacheDir . '/wikis/' . $host . $path . '.php' );
			foreach( $result['$CORECONFIG'] as $coreconfig ) {
				if( !is_file( $this->configDir . '/' . $coreconfig )
				    || filemtime( $this->configDir . '/' . $coreconfig ) > $myfreshness ) {
					$fresh = false;
					break;
				}
			}
			if( $fresh ) {
				$this->farmConfig['config'] = $result['$CONFIG'];
				unset( $result['$CONFIG'] );
				unset( $result['$CORECONFIG'] );
				$this->variables = $result;
				return;
			} else {
				unlink( $this->cacheDir . '/wikis/' . $host . $path . '.php' );
				if( is_file( $this->cacheDir . '/LocalSettings/' . $host . $path . '.php' ) ) {
					unlink( $this->cacheDir . '/LocalSettings/' . $host . $path . '.php' );
				}
				if( is_file( $this->cacheDir . '/composer/' . $host . $path . '.php' ) ) {
					unlink( $this->cacheDir . '/composer/' . $host . $path . '.php' );
				}
				$hosts[$host] = preg_replace( '/\|?' . preg_quote( $path, '/' ) . '/', '', $hosts[$host] );
				if( $hosts[$host] == '/^()\\//' ) {
					unset( $hosts[$host] );
				}
				MediaWikiFarmUtils::cacheFile( $hosts, 'wikis.php', $this->cacheDir );
			}
		}

		# Now select the right farm amoung all farms
		$result = $this->selectFarm( $host . $path, false, 5 );

		# Success
		if( $result['farm'] ) {
			$this->farmConfig = array_merge( $result['config'], $this->farmConfig );
			$this->variables = array_merge( $result['variables'], $this->variables );
			$this->variables['$SERVER'] = $result['host'];
			$this->variables['$FARM'] = $result['farm'];
			return;
		}

		# Hard fail
		elseif( !$result['farms'] ) {
			throw new MWFConfigurationException( 'No configuration file found' );
		}
		elseif( $result['redirects'] <= 0 ) {
			throw new MWFConfigurationException( 'Infinite or too long redirect detected (host=\'' . $host . '\', path=\'' . $path . '\')' );
		}
		throw new MWFConfigurationException( 'No farm corresponding to this host (host=\'' . $host . '\', path=\'' . $path . '\')' );
	}

	/**
	 * Select the farm.
	 *
	 * Constant function (do not write any object property).
	 *
	 * @internal
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 *
	 * @param string $host Requested host.
	 * @param array $farms All farm configurations.
	 * @param int $redirects Number of remaining internal redirects before error.
	 * @return array
	 */
	public function selectFarm( $host, $farms, $redirects ) {

		if( $redirects <= 0 ) {
			return array( 'host' => $host, 'farm' => false, 'config' => false, 'variables' => false, 'farms' => $farms, 'redirects' => $redirects );
		}

		# Read the farms configuration
		if( !$farms ) {
			list( $farms, $file ) = MediaWikiFarmUtils::readAnyFile( 'farms', $this->configDir, $this->cacheDir, $this->log );
			if( $file ) {
				$this->farmConfig['coreconfig'][] = $file;
			} else {
				return array( 'host' => $host, 'farm' => false, 'config' => false, 'variables' => false, 'farms' => false, 'redirects' => $redirects );
			}
		}

		# For each proposed farm, check if the host matches
		foreach( $farms as $farm => $config ) {

			# Cut the host from the beginning to the first slash
			# A slash is added at the end to be sure there is a slash
			$confighost = substr( $config['server'], 0, strpos( $config['server'] . '/', '/' ) );
			# Added a trailing slash either unuseful if there is already a subdirectory either it will act as a separator between host and path
			$configpath = substr( $config['server'] . '/', strlen( $confighost ) );
			# Protect the slashes but let other characters to keep the regex
			$configpath = str_replace( '/', '\/', $configpath );
			# The host is case-insensitive, the path is case-sensitive
			# The tested host must have a trailing slash because the regex has at least one slash
			if( ! preg_match( '/^' . $confighost . '(?-i)' . $configpath . '/i', $host . '/', $matches ) ) {
				continue;
			}
			# Get the resulting host; this must not be the tested host because it has the article name, etc and is less safe than
			# the config host; this is the interpretation of the configured regex tested against the client host. Remove the
			# last character, which is always the slash added in $configpath.
			$host = substr( $matches[0], 0, -1 );

			# Initialise variables from the host
			$variables = array();
			foreach( $matches as $key => $value ) {
				if( is_string( $key ) ) {
					$variables[ '$' . strtolower( $key ) ] = $value;
				}
			}

			# Silently redirect to another farm
			if( array_key_exists( 'redirect', $config ) ) {
				return $this->selectFarm( str_replace( array_keys( $variables ), $variables, $config['redirect'] ), $farms, --$redirects );
			}

			return array( 'host' => $host, 'farm' => $farm, 'config' => $config, 'variables' => $variables, 'farms' => $farms, 'redirects' => $redirects );
		}

		return array( 'host' => $host, 'farm' => false, 'config' => false, 'variables' => false, 'farms' => $farms, 'redirects' => $redirects );
	}

	/**
	 * Check the variables in the host name to verify the wiki exists.
	 *
	 * If the wiki exists and a version is found, the version is written in $this->variables['$VERSION'].
	 *
	 * This function generates strange code coverage, some lines (e.g. $this->variables['$VERSION']) are indicated as covered,
	 * but its parent “if” is not.
	 *
	 * @internal
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 *
	 * @return bool|null If true, the wiki exists; if false, the wiki does not exist; if null, the wiki might exist if defined latter.
	 * @throws MWFConfigurationException When a file defining the existing values for a variable is missing or badly formatted.
	 * @throws InvalidArgumentException
	 */
	public function checkHostVariables() {

		if( $this->variables['$VERSION'] ) {
			return true;
		}

		$explicitExistence = null;
		$explicitExistenceUnderScrutiny = false;
		if( !array_key_exists( 'variables', $this->farmConfig ) ) {
			return null;
		}
		if( !array_key_exists( 'wikiID', $this->farmConfig ) ) {
			throw new MWFConfigurationException( "Missing key 'wikiID' in farm configuration." );
		}

		# For each variable, in the given order, check if the variable exists, check if the
		# wiki exists in the corresponding listing file, and get the version if available
		foreach( $this->farmConfig['variables'] as $variable ) {

			$key = $variable['variable'];
			$explicitExistenceUnderScrutiny = strpos( '$' . strtolower( $key ), $this->farmConfig['wikiID'] ) !== false;

			# If the variable doesn’t exist, continue
			if( !array_key_exists( '$' . strtolower( $key ), $this->variables ) ) {
				$explicitExistence = $explicitExistenceUnderScrutiny ? false : $explicitExistence;
				continue;
			}
			$value = $this->variables[ '$' . strtolower( $key ) ];

			# Get the values
			if( array_key_exists( 'file', $variable ) && is_string( $variable['file'] ) ) {

				# Really check if the variable is in the listing file
				$filename = $this->replaceVariables( $variable['file'] );
				$choices = $this->readFile( $filename, $this->configDir );
				if( $choices === false ) {
					throw new MWFConfigurationException( 'Missing or badly formatted file \'' . $variable['file'] .
						'\' defining existing values for variable \'' . $key . '\''
					);
				}
				$this->farmConfig['coreconfig'][] = $filename;

			} elseif( array_key_exists( 'values', $variable ) && is_array( $variable['values'] ) ) {

				$choices = $variable['values'];

			} else {
				$explicitExistence = $explicitExistenceUnderScrutiny ? false : $explicitExistence;
				continue;
			}

			# Check if the array is a simple list of wiki identifiers without version information…
			if( array_keys( $choices ) === range( 0, count( $choices ) - 1 ) ) {
				if( !in_array( $value, $choices ) ) {
					$this->updateVersion( null );
					return false;
				}
				$explicitExistence = $explicitExistence === null ? true : $explicitExistence;

			# …or a dictionary with wiki identifiers and corresponding version information
			} else {

				if( !array_key_exists( $value, $choices ) ) {
					$this->updateVersion( null );
					return false;
				}

				if( is_string( $this->codeDir ) && MediaWikiFarmUtils::isMediaWiki( $this->codeDir . '/' . ( (string) $choices[$value] ) ) ) {

					$this->variables['$VERSION'] = (string) $choices[$value];
				}
				$explicitExistence = $explicitExistence === null ? true : $explicitExistence;
			}
		}

		return $explicitExistence === true ? true : null;
	}

	/**
	 * Set the version.
	 *
	 * Depending of the installation mode, use a cache file, search the version in a file, or does nothing for monoversion case.
	 *
	 * @internal
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 *
	 * @param bool $explicitExistence The wiki was explicitely defined as existent in a previous file.
	 * @return bool The version was set, and the wiki could exist.
	 * @throws MWFConfigurationException When the file defined by 'versions' is missing or badly formatted.
	 * @throws LogicException
	 */
	public function setVersion( $explicitExistence = false ) {

		# From cache
		if( $this->variables['$CODE'] ) {

			return true;
		}

		# Monoversion mode
		if( is_null( $this->codeDir ) ) {

			# Verify the explicit existence of the wiki
			if( !$explicitExistence ) {
				throw new MWFConfigurationException( 'Only explicitly-defined wikis declared in existence lists are allowed in monoversion mode.' );
			}

			$this->variables['$VERSION'] = '';
			$this->variables['$CODE'] = $GLOBALS['IP'];

			return true;
		}

		# Special case for the update: new (uncached) version must be used
		$canUseDeployments = ( $this->state['EntryPoint'] != 'maintenance/update.php' );

		# Read 'deployments' file
		$deployments = array();
		$this->setVariable( 'deployments' );
		if( array_key_exists( '$DEPLOYMENTS', $this->variables ) && $canUseDeployments ) {
			if( strrchr( $this->variables['$DEPLOYMENTS'], '.' ) != '.php' ) {
				$this->variables['$DEPLOYMENTS'] .= '.php';
			}
			$deployments = $this->readFile( $this->variables['$DEPLOYMENTS'], $this->configDir, false );

			$this->setVariable( 'versions' );
			if( $deployments === false ) {
				if( $this->variables['$VERSIONS'] && ( $deployments = $this->readFile( $this->variables['$VERSIONS'], $this->configDir ) ) ) {
					MediaWikiFarmUtils::cacheFile( $deployments, $this->variables['$DEPLOYMENTS'], $this->configDir, false );
				}
			}

			$fresh = true;
			$myfreshness = filemtime( $this->configDir . '/' . $this->variables['$DEPLOYMENTS'] );
			foreach( $this->farmConfig['coreconfig'] as $coreconfig ) {
				if( !is_file( $this->configDir . '/' . $coreconfig )
				    || filemtime( $this->configDir . '/' . $coreconfig ) >= $myfreshness ) {
					$fresh = false;
					break;
				}
			}
			if( !$fresh || ( !is_string( $this->variables['$VERSION'] ) && array_key_exists( '$VERSIONS', $this->variables ) && filemtime( $this->configDir . '/' . $this->variables['$VERSIONS'] ) >= $myfreshness ) || $deployments === false ) {
				$deployments = array();
			}
		}

		# Multiversion mode – use 'deployments' file
		if( array_key_exists( $this->variables['$WIKIID'], $deployments ) ) {

			$this->variables['$VERSION'] = $deployments[$this->variables['$WIKIID']];
			$this->variables['$CODE'] = $this->codeDir . '/' . $this->variables['$VERSION'];
		}

		# Multiversion mode – version was given in a ‘variables’ file
		elseif( is_string( $this->variables['$VERSION'] ) ) {

			$this->variables['$CODE'] = $this->codeDir . '/' . $this->variables['$VERSION'];
		}

		# Multiversion mode – version is given in a ‘versions’ file
		else {

			$this->setVariable( 'versions', true );
			$versions = $this->readFile( $this->variables['$VERSIONS'], $this->configDir );
			if( $versions === false ) {
				throw new MWFConfigurationException( 'Missing or badly formatted file \'' . $this->variables['$VERSIONS'] .
					'\' containing the versions for wikis.'
				);
			}

			# Search wiki in a hierarchical manner
			if( array_key_exists( $this->variables['$WIKIID'], $versions )
			    && MediaWikiFarmUtils::isMediaWiki( $this->codeDir . '/' . $versions[$this->variables['$WIKIID']] ) ) {
				$this->variables['$VERSION'] = $versions[$this->variables['$WIKIID']];
			}
			elseif( array_key_exists( $this->variables['$SUFFIX'], $versions )
			        && MediaWikiFarmUtils::isMediaWiki( $this->codeDir . '/' . $versions[$this->variables['$SUFFIX']] ) ) {
				if( !$explicitExistence ) {
					throw new MWFConfigurationException( 'Only explicitly-defined wikis declared in existence lists ' .
						'are allowed to use the “default versions” mechanism (suffix) in multiversion mode.' );
				}
				$this->variables['$VERSION'] = $versions[$this->variables['$SUFFIX']];
			}
			elseif( array_key_exists( 'default', $versions ) && MediaWikiFarmUtils::isMediaWiki( $this->codeDir . '/' . $versions['default'] ) ) {
				if( !$explicitExistence ) {
					throw new MWFConfigurationException( 'Only explicitly-defined wikis declared in existence lists ' .
						'are allowed to use the “default versions” mechanism (default) in multiversion mode.' );
				}
				$this->variables['$VERSION'] = $versions['default'];
			}
			else {
				$this->updateVersion( null );
				if( $explicitExistence ) {
					throw new MWFConfigurationException( 'No version declared for this wiki.' );
				}
				return false;
			}

			$this->farmConfig['coreconfig'][] = $this->variables['$VERSIONS'];
		}

		# Update the 'deployments' file
		if( array_key_exists( '$DEPLOYMENTS', $this->variables ) && $canUseDeployments ) {

			$this->updateVersion( $this->variables['$VERSION'] );
			$this->farmConfig['coreconfig'][] = $this->variables['$DEPLOYMENTS'];
		}

		# Important set
		$this->variables['$CODE'] = $this->codeDir . '/' . $this->variables['$VERSION'];

		return true;
	}

	/**
	 * Computation of secondary variables.
	 *
	 * These can reuse previously-computed variables: URL variables (lowercase), '$WIKIID', '$SUFFIX', '$VERSION', '$CODE'.
	 *
	 * @internal
	 * @mediawikifarm-idempotent
	 *
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function setOtherVariables() {

		$this->setVariable( 'data' );
	}

	/**
	 * Update the version in the deployment file.
	 *
	 * @internal
	 *
	 * @param string|null $version The new version, should be the version found in the 'expected version' file.
	 * @return void
	 */
	protected function updateVersion( $version ) {

		# Check a deployment file is wanted
		$this->setVariable( 'deployments' );
		if( !array_key_exists( '$DEPLOYMENTS', $this->variables ) ) {
			return;
		}

		# Read current deployments
		if( strrchr( $this->variables['$DEPLOYMENTS'], '.' ) != '.php' ) {
			$this->variables['$DEPLOYMENTS'] .= '.php';
		}
		$deployments = $this->readFile( $this->variables['$DEPLOYMENTS'], $this->configDir, false );

		$update = ( $this->state['EntryPoint'] == 'maintenance/update.php' );
		$isKey = is_array( $deployments ) && array_key_exists( $this->variables['$WIKIID'], $deployments );

		if( $update || ( $isKey && is_null( $version ) ) || ( !$isKey && !is_null( $version ) ) ) {

			if( is_null( $version ) ) {
				unset( $deployments[$this->variables['$WIKIID']] );
			} else {
				$deployments[$this->variables['$WIKIID']] = $version;
			}
			MediaWikiFarmUtils::cacheFile( $deployments, $this->variables['$DEPLOYMENTS'], $this->configDir );
		}
	}

	/**
	 * Is the cache configuration file LocalSettings.php for the requested wiki fresh?
	 *
	 * @api
	 * @mediawikifarm-const
	 *
	 * @return bool The cached configuration file LocalSettings.php for the requested wiki is fresh.
	 */
	public function isLocalSettingsFresh() {

		if( !$this->cacheDir ) {
			return false;
		}

		$localSettingsFile = $this->cacheDir . '/LocalSettings/' . $this->variables['$SERVER'] . '.php';

		# Check there is a LocalSettings.php file
		if( !is_file( $localSettingsFile ) ) {
			return false;
		}

		# Check modification time of original config files
		$oldness = 0;
		foreach( $this->farmConfig['config'] as $configFile ) {
			if( !is_array( $configFile ) || !is_string( $configFile['file'] ) || ( array_key_exists( 'executable', $configFile ) && $configFile['executable'] ) ) {
				continue;
			}
			$file = $this->configDir . '/' . $this->replaceVariables( $configFile['file'] );
			if( !is_file( $file ) ) {
				continue;
			}
			$oldness = max( $oldness, filemtime( $file ) );
		}

		return filemtime( $localSettingsFile ) >= $oldness;
	}

	/**
	 * Set a wiki property and replace placeholders (property name version).
	 *
	 * @internal
	 *
	 * @param string $name Name of the property.
	 * @param bool $mandatory This variable is mandatory.
	 * @return void
	 * @throws MWFConfigurationException When the variable is mandatory and missing.
	 * @throws InvalidArgumentException
	 */
	public function setVariable( $name, $mandatory = false ) {

		if( !array_key_exists( $name, $this->farmConfig ) ) {
			if( $mandatory ) {
				throw new MWFConfigurationException( "Missing key '$name' in farm configuration." );
			}
			return;
		}

		if( !is_string( $this->farmConfig[$name] ) ) {
			if( $mandatory ) {
				throw new MWFConfigurationException( "Wrong type (non-string) for key '$name' in farm configuration." );
			}
			return;
		}

		$this->variables[ '$' . strtoupper( $name ) ] = $this->replaceVariables( $this->farmConfig[$name] );
	}

	/**
	 * Replace variables in a string.
	 *
	 * Constant function (do not write any object property).
	 *
	 * @internal
	 *
	 * @param string|string[] $value Value of the property.
	 * @return string|string[] Input where variables were replaced.
	 * @throws InvalidArgumentException When argument type is incorrect.
	 */
	public function replaceVariables( $value ) {

		if( is_string( $value ) ) {

			return str_replace( array_keys( $this->variables ), $this->variables, $value );
		}

		elseif( is_array( $value ) ) {

			foreach( $value as &$subvalue ) {
				if( is_string( $subvalue ) || is_array( $subvalue ) ) {
					$subvalue = $this->replaceVariables( $subvalue );
				}
			}
			return $value;
		}

		throw new InvalidArgumentException( 'Argument of MediaWikiFarm->replaceVariables() must be a string or an array.' );
	}



	/*
	 * Helper Methods
	 * -------------- */

	/**
	 * Read a file either in PHP, YAML (if library available), JSON, dblist, or serialised, and returns the interpreted array.
	 *
	 * The choice between the format depends on the extension: php, yml, yaml, json, dblist, serialised.
	 *
	 * @internal
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 *
	 * @param string $filename Name of the requested file.
	 * @param string $directory Parent directory.
	 * @param bool $cache The successfully file read must be cached.
	 * @return array|false The interpreted array in case of success, else false.
	 */
	public function readFile( $filename, $directory = '', $cache = true ) {

		return MediaWikiFarmUtils::readFile( $filename, $this->cacheDir, $this->log, $directory, $cache );
	}
}
