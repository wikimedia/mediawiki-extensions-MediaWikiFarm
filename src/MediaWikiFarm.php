<?php
/**
 * Class MediaWikiFarm.
 *
 * @package MediaWikiFarm
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 *
 * DEVELOPERS: given its nature, this extension must work with all MediaWiki versions and
 *             PHP 5.2+, so please do not use "new" syntaxes (namespaces, arrays with [], etc.).
 */

/**
 * Exception triggered when a configuration file is missing, badly formatted, or does not respect the schema,
 * or when server does not pass HTTP_HOST to PHP.
 */
class MWFConfigurationException extends RuntimeException {}


/**
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

	/** @var array Environment. */
	protected $environment = array(
		'ExtensionRegistry' => null,
	);

	/** @var array Configuration parameters for this wiki. */
	protected $configuration = array(
		'settings' => array(),
		'arrays' => array(),
		'extensions' => array(),
		'execFiles' => array(),
		'composer' => array(),
	);

	/** @var array Logs. */
	public $log = array();



	/*
	 * Accessors
	 * --------- */

	/**
	 * Get the inner state.
	 *
	 * @param string $key Parameter name.
	 * @return mixed|null Requested state or null if nonexistant.
	 */
	function getState( $key ) {
		if( array_key_exists( $key, $this->state ) ) {
			return $this->state[$key];
		}
		return null;
	}

	/**
	 * Get code directory, where subdirectories are MediaWiki versions.
	 *
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 *
	 * @return string|null Code directory, or null if currently installed as a classical extension (monoversion installation).
	 */
	function getCodeDir() {
		return $this->codeDir;
	}

	/**
	 * Get cache directory.
	 *
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 *
	 * @return string|false Cache directory.
	 */
	function getCacheDir() {
		return $this->cacheDir;
	}

	/**
	 * Get the farm configuration.
	 *
	 * This is the farm configuration extracted from the farm configuration file, unchanged.
	 * Variables are the alter-ego variable adapted to the current request.
	 *
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 *
	 * @return array Farm configuration.
	 */
	function getFarmConfiguration() {
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
	 * @mediawikifarm-const
	 *
	 * @return string[] Request variables.
	 */
	function getVariables() {
		return $this->variables;
	}

	/**
	 * Get a variable related to the current request.
	 *
	 * @mediawikifarm-const
	 *
	 * @param string $varname Variable name (prefixed with '$').
	 * @param mixed $default Default value returned when the variable does not exist.
	 * @return string|mixed Requested variable or default value if the variable does not exist.
	 */
	function getVariable( $varname, $default = null ) {
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
	 * @mediawikifarm-const
	 *
	 * @param string|null $key Key of the wanted section or null for the whole array.
	 * @param string|null $key2 Subkey (specific to each entry) or null for the whole entry.
	 * @return array MediaWiki configuration, either entire, either a part depending on the parameter.
	 */
	function getConfiguration( $key = null, $key2 = null ) {
		if( $key !== null ) {
			if( array_key_exists( $key, $this->configuration ) ) {
				if( $key2 !== null && array_key_exists( $key2, $this->configuration[$key] ) ) {
					return $this->configuration[$key][$key2];
				} elseif( $key2 !== null ) {
					return null;
				}
				return $this->configuration[$key];
			}
			return null;
		}
		return $this->configuration;
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
	 * @param string $entryPoint Name of the entry point, e.g. 'index.php', 'load.php'…
	 * @param string|null $host Host name (string) or null to use the global variables HTTP_HOST or SERVER_NAME.
	 * @param array $state Parameters, see object property $state.
	 * @param array $environment Environment which determines a given configuration.
	 * @return string $entryPoint Identical entry point as passed in input.
	 */
	static function load( $entryPoint = '', $host = null, $state = array(), $environment = array() ) {

		global $wgMediaWikiFarm;
		global $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir, $wgMediaWikiFarmCacheDir, $wgMediaWikiFarmSyslog;

		try {
			# Initialise object
			$wgMediaWikiFarm = new MediaWikiFarm( $host,
				$wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir, $wgMediaWikiFarmCacheDir,
				array_merge( $state, array( 'EntryPoint' => $entryPoint ) ),
				$environment
			);

			# Check existence
			$exists = $wgMediaWikiFarm->checkExistence();

			# Compile configuration
			$wgMediaWikiFarm->compileConfiguration();
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
	 * @return bool The wiki does exist.
	 * @throws MWFConfigurationException
	 * @throws InvalidArgumentException
	 */
	function checkExistence() {

		# In the multiversion case, informations are already loaded and nonexistent wikis are already verified
		if( $this->variables['$CODE'] ) {
			return true;
		}

		# Replace variables in the host name and possibly retrieve the version
		if( !$this->checkHostVariables() ) {
			return false;
		}

		# Set wikiID, the unique identifier of the wiki
		$this->setVariable( 'suffix', true );
		$this->setVariable( 'wikiID', true );

		# Set the version of the wiki
		if( !$this->setVersion() ) {
			return false;
		}

		# Set other variables of the wiki
		$this->setOtherVariables();

		# Cache the result
		if( $this->cacheDir ) {
			$variables = $this->variables;
			$variables['$CORECONFIG'] = $this->farmConfig['coreconfig'];
			$variables['$CONFIG'] = $this->farmConfig['config'];
			self::cacheFile( $variables, $this->variables['$SERVER'] . '.php', $this->cacheDir . '/wikis' );
		}

		return true;
	}

	/**
	 * Compile configuration as much as it can.
	 */
	function compileConfiguration() {

		if( $this->isLocalSettingsFresh() ) {

			$composerFile = $this->readFile( $this->variables['$SERVER'] . '.php', $this->cacheDir . '/composer', false );
			if( is_array( $composerFile ) ) {
				$this->configuration['composer'] = $composerFile;
			}

			return;
		}

		# Transform configuration files to a unique configuration
		if( count( $this->configuration['settings'] ) == 0 ) {

			# Compile the configuration
			$this->populateSettings();

			# Activate the extensions (possibly not finished here
			# if we do not know the entire MediaWiki environment)
			$this->activateExtensions();

			# Save Composer key if available
			if( $this->cacheDir && !array_key_exists( 'unreadable-file', $this->log ) ) {
				self::cacheFile( $this->configuration['composer'],
					$this->variables['$SERVER'] . '.php',
					$this->cacheDir . '/composer'
				);
			}
		}

		# When the MediaWiki environment is set
		if( $this->setEnvironment() ) {

			# Finalise the extension activation
			$this->activateExtensions();

			# Create the final LocalSettings.php
			if( $this->cacheDir && !array_key_exists( 'unreadable-file', $this->log ) ) {
				self::cacheFile( $this->createLocalSettings( $this->configuration ),
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
	 * @return void
	 */
	function loadMediaWikiConfig() {

		# Set general parameters as global variables
		foreach( $this->configuration['settings'] as $setting => $value ) {

			$GLOBALS[$setting] = $value;
		}

		# Merge general array parameters into global variables
		foreach( $this->configuration['arrays'] as $setting => $value ) {

			if( !array_key_exists( $setting, $GLOBALS ) ) {
				$GLOBALS[$setting] = array();
			}
			$GLOBALS[$setting] = self::arrayMerge( $GLOBALS[$setting], $value );
		}

		# Load extensions and skins with the wfLoadExtension/wfLoadSkin mechanism
		foreach( $this->configuration['extensions'] as $key => $extension ) {

			if( $extension[2] == 'wfLoadExtension' ) {

				if( $key != 'ExtensionMediaWikiFarm' || !$this->codeDir ) {
					wfLoadExtension( $extension[0] );
				} else {
					wfLoadExtension( 'MediaWikiFarm', $this->farmDir . '/extension.json' );
				}
			}
			elseif( $extension[2] == 'wfLoadSkin' ) {

				wfLoadSkin( $extension[0] );
			}
		}

		# Register this extension MediaWikiFarm to appear in Special:Version
		if( array_key_exists( 'ExtensionMediaWikiFarm', $this->configuration['extensions'] ) &&
		     $this->configuration['extensions']['ExtensionMediaWikiFarm'][2] == 'require_once' &&
		     $this->codeDir ) {
			$GLOBALS['wgExtensionCredits']['other'][] = array(
				'path' => $this->farmDir . '/MediaWikiFarm.php',
				'name' => 'MediaWikiFarm',
				'version' => '0.4.0',
				'author' => '[https://www.mediawiki.org/wiki/User:Seb35 Seb35]',
				'url' => 'https://www.mediawiki.org/wiki/Extension:MediaWikiFarm',
				'descriptionmsg' => 'mediawikifarm-desc',
				'license-name' => 'GPL-3.0+',
			);

			$GLOBALS['wgAutoloadClasses']['MediaWikiFarm'] = 'src/MediaWikiFarm.php';
			$GLOBALS['wgAutoloadClasses']['AbstractMediaWikiFarmScript'] = 'src/AbstractMediaWikiFarmScript.php';
			$GLOBALS['wgAutoloadClasses']['MediaWikiFarmScript'] = 'src/MediaWikiFarmScript.php';
			$GLOBALS['wgAutoloadClasses']['MediaWikiFarmHooks'] = 'src/Hooks.php';
			$GLOBALS['wgAutoloadClasses']['MWFConfigurationException'] = 'src/MediaWikiFarm.php';
			$GLOBALS['wgMessagesDirs']['MediaWikiFarm'] = array( 'i18n' );
			$GLOBALS['wgHooks']['UnitTestsList'][] = array( 'MediaWikiFarmHooks::onUnitTestsList' );
		}
	}

	/**
	 * Synchronise the version in the 'expected version' and deployment files.
	 *
	 * @return void
	 */
	function updateVersionAfterMaintenance() {

		if( !$this->variables['$VERSION'] ) {
			return;
		}

		$this->updateVersion( $this->variables['$VERSION'] );
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
	 * @mediawikifarm-const
	 *
	 * @return string File where is loaded the configuration.
	 */
	function getConfigFile() {

		if( !$this->isLocalSettingsFresh() ) {
			return $this->farmDir . '/src/main.php';
		}

		return $this->cacheDir . '/LocalSettings/' . $this->variables['$SERVER'] . '.php';
	}

	/**
	 * Prepare log messages and open syslog channel.
	 *
	 * @param string|false $wgMediaWikiFarmSyslog Syslog tag or deactivate logging.
	 * @param MediaWikiFarm|null $wgMediaWikiFarm MediaWikiFarm object if any, in order to retrieve existing log messages.
	 * @param Exception|Throwable|null $exception Caught exception if any.
	 * @return string[] All log messages ready to be sent to syslog.
	 */
	static function prepareLog( $wgMediaWikiFarmSyslog, $wgMediaWikiFarm, $exception = null ) {

		$log = array();
		if( $wgMediaWikiFarmSyslog === false ) {
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
	 * @codeCoverageIgnore
	 *
	 * @param string[] $log Log messages.
	 * @return void
	 */
	static function issueLog( $log ) {

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
	 * @param string|null $host Requested host.
	 * @param string $configDir Configuration directory.
	 * @param string|null $codeDir Code directory; if null, the current MediaWiki installation is used.
	 * @param string|false $cacheDir Cache directory; if false, the cache is disabled.
	 * @param array $state Inner state: EntryPoint (string) and InnerMediaWiki (bool).
	 * @param array $environment MediaWiki environment: ExtensionRegistry (bool).
	 * @return MediaWikiFarm
	 * @throws MWFConfigurationException When no farms.yml/php/json is found.
	 * @throws InvalidArgumentException When wrong input arguments are passed.
	 */
	function __construct( $host, $configDir, $codeDir = null, $cacheDir = false, $state = array(), $environment = array() ) {

		# Default value for host
		# Warning: do not use $GLOBALS['_SERVER']['HTTP_HOST']: bug with PHP7: it is not initialised in early times of a script
		# Rationale: nginx put the regex of the server name in SERVER_NAME; HTTP_HOST seems to be always clean from this side,
		#            and it will be checked against available hosts in constructor
		if( is_null( $host ) ) {
			if( array_key_exists( 'HTTP_HOST', $_SERVER ) && $_SERVER['HTTP_HOST'] ) {
				$host = $_SERVER['HTTP_HOST'];
			} elseif( array_key_exists( 'SERVER_NAME', $_SERVER ) && $_SERVER['SERVER_NAME'] ) {
				$host = $_SERVER['SERVER_NAME'];
			} else {
				throw new InvalidArgumentException( 'Undefined host' );
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

		# Sanitise host
		$host = preg_replace( '/[^a-zA-Z0-9\\._-]/', '', $host );

		# Set parameters
		$this->farmDir = dirname( dirname( __FILE__ ) );
		$this->configDir = $configDir;
		$this->codeDir = $codeDir;
		$this->cacheDir = $cacheDir;
		$this->state = array_merge( array(
			'EntryPoint' => '',
			'InnerMediaWiki' => null,
		), $state );
		$this->environment = array_merge( array(
			'ExtensionRegistry' => null,
		), $environment );

		# Shortcut loading
		// @codingStandardsIgnoreLine
		if( $this->cacheDir && ( $result = $this->readFile( $host . '.php', $this->cacheDir . '/wikis', false ) ) ) {
			$fresh = true;
			$myfreshness = filemtime( $this->cacheDir . '/wikis/' . $host . '.php' );
			foreach( $result['$CORECONFIG'] as $coreconfig ) {
				if( filemtime( $this->configDir . '/' . $coreconfig ) > $myfreshness ) {
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
			} elseif( is_file( $this->cacheDir . '/LocalSettings/' . $host . '.php' ) ) {
				unlink( $this->cacheDir . '/LocalSettings/' . $host . '.php' );
			}
		}

		# Now select the right farm amoung all farms
		$result = $this->selectFarm( $host, false, 5 );

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
			throw new MWFConfigurationException( 'Infinite or too long redirect detected (host=\'' . $host . '\')' );
		}
		throw new MWFConfigurationException( 'No farm corresponding to this host (host=\'' . $host . '\')' );
	}

	/**
	 * Select the farm.
	 *
	 * Constant function (do not write any object property).
	 *
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 *
	 * @param string $host Requested host.
	 * @param array $farms All farm configurations.
	 * @param integer $redirects Number of remaining internal redirects before error.
	 * @return array
	 */
	function selectFarm( $host, $farms, $redirects ) {

		if( $redirects <= 0 ) {
			return array( 'host' => $host, 'farm' => false, 'config' => false, 'variables' => false, 'farms' => $farms, 'redirects' => $redirects );
		}

		# Read the farms configuration
		if( !$farms ) {
			// @codingStandardsIgnoreStart
			if( $farms = $this->readFile( 'farms.yml', $this->configDir ) ) {
				$this->farmConfig['coreconfig'][] = 'farms.yml';
			} elseif( $farms = $this->readFile( 'farms.php', $this->configDir ) ) {
				$this->farmConfig['coreconfig'][] = 'farms.php';
			} elseif( $farms = $this->readFile( 'farms.json', $this->configDir ) ) {
				$this->farmConfig['coreconfig'][] = 'farms.json';
			} else {
				return array( 'host' => $host, 'farm' => false, 'config' => false, 'variables' => false, 'farms' => false, 'redirects' => $redirects );
			}
			// @codingStandardsIgnoreEnd
		}

		# For each proposed farm, check if the host matches
		foreach( $farms as $farm => $config ) {

			if( !preg_match( '/^' . $config['server'] . '$/i', $host, $matches ) ) {
				continue;
			}

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
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 *
	 * @return bool The wiki exists.
	 * @throws MWFConfigurationException When the farm configuration doesn’t define 'variables' or when a file defining the
	 *         existing values for a variable is missing or badly formatted.
	 * @throws InvalidArgumentException
	 */
	function checkHostVariables() {

		if( $this->variables['$VERSION'] ) {
			return true;
		}

		if( !array_key_exists( 'variables', $this->farmConfig ) ) {
			throw new MWFConfigurationException( 'Undefined key \'variables\' in the farm configuration' );
		}

		# For each variable, in the given order, check if the variable exists, check if the
		# wiki exists in the corresponding listing file, and get the version if available
		foreach( $this->farmConfig['variables'] as $variable ) {

			$key = $variable['variable'];

			# If the variable doesn’t exist, continue
			if( !array_key_exists( '$' . strtolower( $key ), $this->variables ) ) {
				continue;
			}
			$value = $this->variables[ '$' . strtolower( $key ) ];

			# If every values are correct, continue
			if( !array_key_exists( 'file', $variable ) || !is_string( $variable['file'] ) ) {
				continue;
			}

			# Really check if the variable is in the listing file
			$filename = $this->replaceVariables( $variable['file'] );
			$choices = $this->readFile( $filename, $this->configDir );
			if( $choices === false ) {
				throw new MWFConfigurationException( 'Missing or badly formatted file \'' . $variable['file'] .
					'\' defining existing values for variable \'' . $key . '\''
				);
			}
			$this->farmConfig['coreconfig'][] = $filename;

			# Check if the array is a simple list of wiki identifiers without version information…
			if( array_keys( $choices ) === range( 0, count( $choices ) - 1 ) ) {
				if( !in_array( $value, $choices ) ) {
					return false;
				}

			# …or a dictionary with wiki identifiers and corresponding version information
			} else {

				if( !array_key_exists( $value, $choices ) ) {
					return false;
				}

				if( is_string( $this->codeDir ) && self::isMediaWiki( $this->codeDir . '/' . ( (string) $choices[$value] ) ) ) {

					$this->variables['$VERSION'] = (string) $choices[$value];
				}
			}
		}

		return true;
	}

	/**
	 * Set the version.
	 *
	 * Depending of the installation mode, use a cache file, search the version in a file, or does nothing for monoversion case.
	 *
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 *
	 * @return bool The version was set, and the wiki could exist.
	 * @throws MWFConfigurationException When the file defined by 'versions' is missing or badly formatted.
	 * @throws LogicException
	 */
	function setVersion() {

		global $IP;

		# Special case for the update: new (uncached) version must be used
		$cache = ( $this->state['EntryPoint'] != 'maintenance/update.php' );

		# Read cache file
		$deployments = array();
		$this->setVariable( 'deployments' );
		if( array_key_exists( '$DEPLOYMENTS', $this->variables ) && $cache ) {
			if( strrchr( $this->variables['$DEPLOYMENTS'], '.' ) != '.php' ) {
				$this->variables['$DEPLOYMENTS'] .= '.php';
			}
			$deployments = $this->readFile( $this->variables['$DEPLOYMENTS'], $this->configDir );
			if( !is_array( $deployments ) ) {
				$deployments = array();
			}
		}

		# Multiversion mode – use cached file
		if( is_string( $this->codeDir ) && array_key_exists( $this->variables['$WIKIID'], $deployments ) ) {
			$this->variables['$VERSION'] = $deployments[$this->variables['$WIKIID']];
			$this->variables['$CODE'] = $this->codeDir . '/' . $this->variables['$VERSION'];
			$this->farmConfig['coreconfig'][] = $this->variables['$DEPLOYMENTS'];
		}
		# Multiversion mode – version was given in a ‘variables’ file
		elseif( is_string( $this->codeDir ) && is_string( $this->variables['$VERSION'] ) ) {

			# Cache the version
			$this->variables['$CODE'] = $this->codeDir . '/' . $this->variables['$VERSION'];
			if( $cache ) {
				$this->updateVersion( $this->variables['$VERSION'] );
			}
		}
		# Multiversion mode – version is given in a ‘versions’ file
		elseif( is_string( $this->codeDir ) && is_null( $this->variables['$VERSION'] ) ) {

			$this->setVariable( 'versions' );
			$versions = $this->readFile( $this->variables['$VERSIONS'], $this->configDir );
			if( !is_array( $versions ) ) {
				throw new MWFConfigurationException( 'Missing or badly formatted file \'' . $this->variables['$VERSIONS'] .
					'\' containing the versions for wikis.'
				);
			}

			# Search wiki in a hierarchical manner
			if( array_key_exists( $this->variables['$WIKIID'], $versions ) && self::isMediaWiki( $this->codeDir . '/' . $versions[$this->variables['$WIKIID']] ) ) {
				$this->variables['$VERSION'] = $versions[$this->variables['$WIKIID']];
			}
			elseif( array_key_exists( $this->variables['$SUFFIX'], $versions ) && self::isMediaWiki( $this->codeDir . '/' . $versions[$this->variables['$SUFFIX']] ) ) {
				$this->variables['$VERSION'] = $versions[$this->variables['$SUFFIX']];
			}
			elseif( array_key_exists( 'default', $versions ) && self::isMediaWiki( $this->codeDir . '/' . $versions['default'] ) ) {
				$this->variables['$VERSION'] = $versions['default'];
			}

			else {
				return false;
			}

			# Cache the version
			$this->farmConfig['coreconfig'][] = $this->variables['$VERSIONS'];
			$this->variables['$CODE'] = $this->codeDir . '/' . $this->variables['$VERSION'];
			if( $cache ) {
				$this->updateVersion( $this->variables['$VERSION'] );
			}
		}
		# Monoversion mode
		elseif( is_null( $this->codeDir ) ) {

			$this->variables['$VERSION'] = '';
			$this->variables['$CODE'] = $IP;
		}

		return true;
	}

	/**
	 * Computation of secondary variables.
	 *
	 * These can reuse previously-computed variables: URL variables (lowercase), '$WIKIID', '$SUFFIX', '$VERSION', '$CODE'.
	 *
	 * @mediawikifarm-idempotent
	 *
	 * @return void
	 * @throws InvalidArgumentException
	 */
	function setOtherVariables() {

		$this->setVariable( 'data' );
	}

	/**
	 * Update the version in the deployment file.
	 *
	 * @param string $version The new version, should be the version found in the 'expected version' file.
	 * @return void
	 */
	protected function updateVersion( $version ) {

		# Check a deployment file is wanted
		if( !array_key_exists( '$DEPLOYMENTS', $this->variables ) ) {
			return;
		}

		# Read current deployments
		if( strrchr( $this->variables['$DEPLOYMENTS'], '.' ) != '.php' ) {
			$this->variables['$DEPLOYMENTS'] .= '.php';
		}
		$deployments = $this->readFile( $this->variables['$DEPLOYMENTS'], $this->configDir );
		if( $deployments === false ) {
			$deployments = array();
		} elseif( array_key_exists( $this->variables['$WIKIID'], $deployments ) && $deployments[$this->variables['$WIKIID']] == $version ) {
			return;
		}

		# Update the deployment file
		$deployments[$this->variables['$WIKIID']] = $version;
		self::cacheFile( $deployments, $this->variables['$DEPLOYMENTS'], $this->configDir );
	}

	/**
	 * Is the cache configuration file LocalSettings.php for the requested wiki fresh?
	 *
	 * @mediawikifarm-const
	 *
	 * @return bool The cached configuration file LocalSettings.php for the requested wiki is fresh.
	 */
	function isLocalSettingsFresh() {

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
	 * Popuplate the settings array directly from config files (without wgConf).
	 *
	 * There should be no major differences is results between this function and results
	 * of SiteConfiguration::getAll(), but probably some edge cases. At the contrary of
	 * Siteconfiguration, this implementation is focused on performance for current wiki:
	 * only the parameters for current wikis are issued, contrary to wgConf’s strategy
	 * map-all-and-reduce. An additional loop over config files is here; wgConf delegates
	 * this externally if wanted.
	 *
	 * The priories used here are implicit in wgConf but exist and behave similarly:
	 * 0 = default value from MW; 1 = explicit very default value from a specific file;
	 * 2 = standard default value; 3 = value with suffix-priority;
	 * 4 = value with tag-priority; 5 = value with specific-wiki-priority. Files are
	 * processed in-order and linearly; for a given setting, only values with a greater
	 * or equal priority can override a previous value.
	 *
	 * There are two resulting arrays in the object property array 'configuration':
	 *   - settings: scalar (or array) values overriding the default MW values;
	 *   - arrays: array values merged into the default MW values.
	 * They are processed differently given their different nature, and to facilitate
	 * mass-import into global scope (or other configuration object). Another minor
	 * reason is: if this processing is done on a MediaWiki installation in a version
	 * different from the target version (sort of cross-compilation), the compiling
	 * MW is not aware of the default array value of the target MW, so it is
	 * safer to only manipulate the known difference.
	 *
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 *
	 * @return bool Success.
	 */
	function populateSettings() {

		$settings = &$this->configuration['settings'];
		$priorities = array();
		$settingsArray = &$this->configuration['arrays'];
		$prioritiesArray = array();

		$extensions =& $this->configuration['extensions'];

		$settings['wgUseExtensionMediaWikiFarm'] = true;
		$extensions['ExtensionMediaWikiFarm'] = array( 'MediaWikiFarm', 'extension', null, 0 );

		foreach( $this->farmConfig['config'] as $configFile ) {

			if( !is_array( $configFile ) ) {
				continue;
			}

			# Replace variables
			$configFile = $this->replaceVariables( $configFile );

			# Executable config files
			if( array_key_exists( 'executable', $configFile ) && $configFile['executable'] ) {

				$this->configuration['execFiles'][] = $this->configDir . '/' . $configFile['file'];
				continue;
			}

			$theseSettings = $this->readFile( $configFile['file'], $this->configDir );
			if( $theseSettings === false ) {
				# If a file is unavailable, skip it
				continue;
			}

			# Defined key
			if( strpos( $configFile['key'], '*' ) === false ) {

				$priority = 0;
				if( $configFile['key'] == 'default' ) {
					$priority = 1;
				} elseif( $configFile['key'] == $this->variables['$SUFFIX'] ) {
					$priority = 3;
				} elseif( $configFile['key'] == $this->variables['$WIKIID'] ) {
					$priority = 5;
				} else {
					/*foreach( $tags as $tag ) {
						if( $configFile['key'] == $tag ) {
							$priority = 4;
							break;
						}
					}*/
					if( $priority == 0 ) {
						continue;
					}
				}

				foreach( $theseSettings as $rawSetting => $value ) {

					# Sanitise the setting name
					$setting = preg_replace( '/[^a-zA-Z0-9_\x7f\xff]/', '', $rawSetting );

					if( substr( $rawSetting, 0, 1 ) == '+' ) {
						if( !array_key_exists( $setting, $prioritiesArray ) || $prioritiesArray[$setting] <= $priority ) {
							$settingsArray[$setting] = $value;
							$prioritiesArray[$setting] = $priority;
						}
					}
					elseif( !array_key_exists( $setting, $priorities ) || $priorities[$setting] <= $priority ) {
						$settings[$setting] = $value;
						$priorities[$setting] = $priority;
						if( substr( $setting, 0, 14 ) == 'wgUseExtension' ) {
							$extensions['Extension' . substr( $rawSetting, 14 )] = array( substr( $rawSetting, 14 ), 'extension', null, count( $extensions ) );
						} elseif( substr( $setting, 0, 9 ) == 'wgUseSkin' ) {
							$extensions['Skin' . substr( $rawSetting, 9 )] = array( substr( $rawSetting, 9 ), 'skin', null, count( $extensions ) );
						}
					}
				}
			}

			# Regex key
			else {

				// $tags = array(); # @todo data sources not implemented, but code to selection parameters from a tag is below

				$defaultKey = '';
				$classicKey = '';
				if( array_key_exists( 'default', $configFile ) && is_string( $configFile['default'] ) ) {
					$defaultKey = $this->replaceVariables( $configFile['default'] );
				}
				if( is_string( $configFile['key'] ) ) {
					$classicKey = $this->replaceVariables( $configFile['key'] );
				}

				# These are precomputations of the condition `$classicKey == $wikiID` (is current wiki equal to key indicated in config file?)
				# to avoid recompute it each time in the loop. This is a bit more complex to take into account the star: $wikiID is the part
				# corresponding to the star from the variable $WIKIID if $classicKey can match $WIKIID when remplacing the star by something
				# (the star will be the key in the files). This reasonning is “inversed” compared to a loop checking each key in the files
				# in order to use array_key_exists, assumed to be quicker than a direct loop.
				$wikiIDKey = (bool) preg_match( '/^'.str_replace( '*', '(.+)', $classicKey ).'$/', $this->variables['$WIKIID'], $matches );
				$wikiID = $wikiIDKey ? $matches[1] : $this->variables['$WIKIID'];
				$suffixKey = (bool) preg_match( '/^'.str_replace( '*', '(.+)', $classicKey ).'$/', $this->variables['$SUFFIX'], $matches );
				$suffix = $suffixKey ? $matches[1] : $this->variables['$SUFFIX'];
				/*$tagKey = array();
				foreach( $tags as $tag ) {
					$tagKey[$tag] = ($classicKey == $tag);
				}*/
				if( $defaultKey ) {
					$suffixDefaultKey = (bool) preg_match( '/^'.str_replace( '*', '(.+)', $defaultKey ).'$/', $this->variables['$SUFFIX'], $matches );
					// $tagDefaultKey = in_array( $defaultKey, $tags );
				}

				foreach( $theseSettings as $rawSetting => $values ) {

					# Sanitise the setting name
					$setting = preg_replace( '/[^a-zA-Z0-9_\x7f\xff]/', '', $rawSetting );

					# Depending if it is an array diff or not, create and initialise the variables
					if( substr( $rawSetting, 0, 1 ) == '+' ) {
						$settingIsArray = true;
						if( !array_key_exists( $setting, $prioritiesArray ) ) {
							$settingsArray[$setting] = array();
							$prioritiesArray[$setting] = 0;
						}
						$thisSetting =  &$settingsArray[$setting];
						$thisPriority = &$prioritiesArray[$setting];
					} else {
						$settingIsArray = false;
						if( !array_key_exists( $setting, $priorities ) ) {
							$settings[$setting] = null;
							$priorities[$setting] = 0;
						}
						$thisSetting =  &$settings[$setting];
						$thisPriority = &$priorities[$setting];
						if( substr( $setting, 0, 14 ) == 'wgUseExtension' ) {
							$extensions['Extension' . substr( $rawSetting, 14 )] = array( substr( $rawSetting, 14 ), 'extension', null, count( $extensions ) );
						} elseif( substr( $setting, 0, 9 ) == 'wgUseSkin' ) {
							$extensions['Skin' . substr( $rawSetting, 9 )] = array( substr( $rawSetting, 9 ), 'skin', null, count( $extensions ) );
						}
					}

					# Set value if there is a label corresponding to wikiID
					if( $wikiIDKey ) {
						if( array_key_exists( $wikiID, $values ) ) {
							$thisSetting = $values[$wikiID];
							$thisPriority = 5;
							continue;
						}
						if( array_key_exists( '+'.$wikiID, $values ) && is_array( $values['+'.$wikiID] ) ) {
							$thisSetting = self::arrayMerge( $thisSetting, $values['+'.$wikiID] );
							$thisPriority = 3;
						}
					}

					# Set value if there are labels corresponding to given tags
					/*$setted = false;
					foreach( $tags as $tag ) {
						if( $tagKey[$tag] && $thisPriority <= 4 ) {
							if( array_key_exists( $tag, $values ) ) {
								$thisSetting = $value[$tag];
								$thisPriority = 4;
								$setted = true;
								# NB: for strict equivalence with wgConf there should be here a `break`, but by consistency
								# (last value kept) and given the case should not appear, there is no.
							}
							elseif( array_key_exists( '+'.$tag, $values ) && is_array( $values['+'.$tag] ) ) {
								$thisSetting = self::arrayMerge( $thisSetting, $values['+'.$tag] );
								$thisPriority = 3;
							}
						}
					}
					if( $setted ) {
						continue;
					}*/

					# Set value if there is a label corresponding to suffix
					if( $suffixKey && $thisPriority <= 3 ) {
						if( array_key_exists( $suffix, $values ) ) {
							$thisSetting = $values[$suffix];
							$thisPriority = 3;
							continue;
						}
						if( array_key_exists( '+'.$suffix, $values ) && is_array( $values['+'.$suffix] ) ) {
							$thisSetting = self::arrayMerge( $thisSetting, $values['+'.$suffix] );
							$thisPriority = 3;
						}
					}

					# Default value
					if( $thisPriority <= 2 && array_key_exists( 'default', $values ) ) {
						$thisSetting = $values['default'];
						$thisPriority = 2;
						if( $defaultKey ) {
							if( $suffixDefaultKey ) {
								$thisPriority = 3;
							/*} elseif( $tagDefaultKey ) {
								$thisPriority = 4;*/
							}
						}
						continue;
					}

					# Nothing was selected, clean up
					if( $thisPriority == 0 ) {
						if( $settingIsArray ) {
							unset( $settingsArray[$setting] );
							unset( $prioritiesArray[$setting] );
						} else {
							unset( $settings[$setting] );
							unset( $priorities[$setting] );
							if( substr( $setting, 0, 14 ) == 'wgUseExtension' ) {
								unset( $extensions['Extension' . substr( $rawSetting, 14 )] );
							} elseif( substr( $setting, 0, 9 ) == 'wgUseSkin' ) {
								unset( $extensions['Skin' . substr( $rawSetting, 9 )] );
							}
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Set environment, i.e. every 'environment variables' which lead to a known configuration.
	 *
	 * For now, the only environment variable is ExtensionRegistry (is the MediaWiki version
	 * capable of loading extensions/skins with wfLoadExtension/wfLoadSkin?).
	 *
	 * @return void
	 */
	function setEnvironment() {

		if( !$this->state['InnerMediaWiki'] ) {
			return false;
		}

		# Set environment
		$this->environment['ExtensionRegistry'] = class_exists( 'ExtensionRegistry' );

		return true;
	}

	/**
	 * Activate extensions and skins depending on their autoloading and activation mechanisms.
	 *
	 * When the environment parameter ExtensionRegistry is not set (null), only Composer-enabled
	 * extensions and skins are Composer-autoloaded; and if ExtensionRegistry is set to true or
	 * false, extensions and skins are activated through wfLoadExtension/wfLoadSkin or require_once.
	 *
	 * The part related to Composer is a bit complicated (partly since optimised): when an extension
	 * is Composer-managed, it is checked if it was already loaded by another extension (in which
	 * case Composer has autoloaded its code), then it is registered as Composer-managed, then its
	 * required extensions are registered. This last part is important, else Composer would have
	 * silently autoloaded the required extensions, but these would not be known by MediaWikiFarm
	 * and, more importantly, an eventual wfLoadExtension would not be triggered (e.g. PageForms 4.0+
	 * is a Composer dependency of the Composer-installed SemanticFormsSelect; if you activate SFS
	 * but not PF, PF would not be wfLoadExtension’ed – since unknown from MediaWikiFarm – and the
	 * wfLoadExtension’s SFS issues a fatal error since PF is not wfLoadExtension’ed, even if it is
	 * Composer-installed).
	 *
	 * @return void
	 */
	function activateExtensions() {

		# Autodetect if ExtensionRegistry is here
		$ExtensionRegistry = $this->environment['ExtensionRegistry'];

		# Load Composer dependencies if available
		$composerLoaded = array();
		$dependencies = $this->readFile( 'MediaWikiExtensions.php', $this->variables['$CODE'] . '/vendor', false );
		if( !$dependencies ) {
			$dependencies = array();
		}

		# Search for skin and extension activation
		foreach( $this->configuration['extensions'] as $key => &$extension ) {

			$type = $extension[1];
			$name = $extension[0];
			$status =& $extension[2];

			$setting = 'wgUse' . preg_replace( '/[^a-zA-Z0-9_\x7f\xff]/', '', $key );
			$value =& $this->configuration['settings'][$setting];

			if( $ExtensionRegistry === null || $value === 'composer' ) {
				if( $this->detectComposer( $type, $name ) ) {
					$status = 'composer';
					$value = true;
				} elseif( $value === 'composer' ) {
					$value = false;
					unset( $this->configuration['extensions'][$key] );
				}
			} elseif( $value === 'require_once' || $value === 'wfLoad' . ucfirst( $type ) ) {
				$status = $value;
				$value = true;
			// @codingStandardsIgnoreLine
			} elseif( $value !== false && ( $status = $this->detectLoadingMechanism( $type, $name ) ) ) {
				$value = true;
			} elseif( $key != 'ExtensionMediaWikiFarm' ) {
				if( $value ) {
					$this->log[] = "Requested but missing $type $name for wiki {$this->variables['$WIKIID']} in version {$this->variables['$VERSION']}";
				}
				$value = false;
				unset( $this->configuration['extensions'][$key] );
			} else {
				$status = $ExtensionRegistry ? 'wfLoadExtension' : 'require_once';
			}

			if( $status == 'composer' ) {
				if( in_array( $key, $composerLoaded ) ) {
					continue;
				}
				$this->configuration['composer'][] = $key;
				if( array_key_exists( $key, $dependencies ) ) {
					$composerLoaded = array_merge( $composerLoaded, $dependencies[$key] );
					foreach( $dependencies[$key] as $dep ) {
						if( !array_key_exists( $dep, $this->configuration['extensions'] ) ) {
							$this->configuration['settings']['wgUse' . preg_replace( '/[^a-zA-Z0-9_\x7f\xff]/', '', $dep )] = true;
							preg_match( '/^(Extension|Skin)(.+)$/', $dep, $matches );
							$this->configuration['extensions'][$dep] = array( $matches[2], strtolower( $matches[1] ), 'composer', - count( $this->configuration['extensions'] ) );
						} else {
							$this->configuration['extensions'][$dep][2] = 'composer';
							$this->configuration['extensions'][$dep][3] = - abs( $this->configuration['extensions'][$dep][3] );
						}
					}
				}
			}
		}

		# Sort extensions
		uasort( $this->configuration['extensions'], array( 'MediaWikiFarm', 'sortExtensions' ) );
		$i = 0;
		foreach( $this->configuration['extensions'] as $key => &$extension ) {
			$extension[3] = $i++;
		}
	}

	/**
	 * Detect if the extension can be loaded by Composer.
	 *
	 * This use the backend-generated key; without it, no extension can be loaded with Composer in MediaWikiFarm.
	 *
	 * @mediawikifarm-const
	 *
	 * @param string $type Type, in ['extension', 'skin'].
	 * @param string $name Name of the extension/skin.
	 * @return boolean The extension/skin is Composer-managed (at least for its installation).
	 */
	function detectComposer( $type, $name ) {

		if( is_file( $this->variables['$CODE'] . '/' . $type . 's/' . $name . '/composer.json' ) &&
		    is_dir( $this->variables['$CODE'] . '/vendor/composer' . self::composerKey( ucfirst( $type ) . $name ) ) ) {

			return true;
		}
		return false;
	}

	/**
	 * Detection of the loading mechanism of extensions and skins.
	 *
	 * @mediawikifarm-const
	 *
	 * @param string $type Type, in ['extension', 'skin'].
	 * @param string $name Name of the extension/skin.
	 * @return string|null Loading mechnism in ['wfLoadExtension', 'wfLoadSkin', 'require_once', 'composer'] or null if all mechanisms failed.
	 */
	function detectLoadingMechanism( $type, $name ) {

		if( !is_dir( $this->variables['$CODE'].'/'.$type.'s/'.$name ) ) {
			return null;
		}

		# An extension.json/skin.json file is in the directory -> assume it is the loading mechanism
		if( $this->environment['ExtensionRegistry'] && is_file( $this->variables['$CODE'].'/'.$type.'s/'.$name.'/'.$type.'.json' ) ) {
			return 'wfLoad' . ucfirst( $type );
		}

		# A composer.json file is in the directory and the extension is properly autoloaded by Composer
		elseif( $this->detectComposer( $type, $name ) ) {
			return 'composer';
		}

		# A MyExtension.php file is in the directory -> assume it is the loading mechanism
		elseif( is_file( $this->variables['$CODE'].'/'.$type.'s/'.$name.'/'.$name.'.php' ) ) {
			return 'require_once';
		}

		return null;
	}

	/**
	 * Sort extensions.
	 *
	 * The extensions are sorted first by loading mechanism, then, for Composer-managed
	 * extensions, according to their dependency order.
	 *
	 * @param array $a First element.
	 * @param array $b Second element.
	 * @return int Relative order of the two elements.
	 */
	function sortExtensions( $a, $b ) {

		static $loading = array(
			'' => 0,
			'composer' => 10,
			'require_once' => 20,
			'wfLoadSkin' => 30,
			'wfLoadExtension' => 30,
		);
		static $type = array(
			'skin' => 1,
			'extension' => 2,
		);

		$loadA = $a[2] === null ? '' : $a[2];
		$loadB = $b[2] === null ? '' : $b[2];
		$weight = $loading[$loadA] + $type[$a[1]] - $loading[$loadB] - $type[$b[1]];
		$stability = $a[3] - $b[3];

		if( $a[2] == 'composer' && $b[2] == 'composer' ) {
			# Read the two composer.json, if one is in the require section, it must be before
			$nameA = ucfirst( $a[1] ) . $a[0];
			$nameB = ucfirst( $b[1] ) . $b[0];
			$dependencies = $this->readFile( 'MediaWikiExtensions.php', $this->variables['$CODE'] . '/vendor', false );
			if( !$dependencies || !array_key_exists( $nameA, $dependencies ) || !array_key_exists( $nameB, $dependencies ) ) {
				return $weight ? $weight : $stability;
			}
			$ArequiresB = in_array( $nameB, $dependencies[$nameA] );
			$BrequiresA = in_array( $nameA, $dependencies[$nameB] );
			if( $ArequiresB && $BrequiresA ) {
				return $stability;
			} elseif( $BrequiresA ) {
				return -1;
			} elseif( $ArequiresB ) {
				return 1;
			}
		}

		return $weight ? $weight : $stability;
	}

	/**
	 * Create a LocalSettings.php.
	 *
	 * A previous mechanism tested in this extension was to load each category of
	 * parameters separately (general settings, arrays, skins, extensions) given the
	 * cached file [cache]/[farm]/config-VERSION-SUFFIX-WIKIID.php, but comparison with
	 * a classical LocalSettings.php was proven to be quicker. Additionally debug will
	 * be easier since a LocalSettings.php is easier to read than a 2D array.
	 *
	 * @param array $configuration Array with the schema defined for $this->configuration.
	 * @param string $preconfig PHP code to be added at the top of the file.
	 * @param string $postconfig PHP code to be added at the end of the file.
	 * @return string Content of the file LocalSettings.php.
	 */
	function createLocalSettings( $configuration, $preconfig = '', $postconfig = '' ) {

		$localSettings = "<?php\n";

		if( $preconfig ) {
			$localSettings .= "\n" . $preconfig;
		}

		# Sort extensions and skins by loading mechanism
		$extensions = array(
			'extension' => array(
				'require_once' => '',
				'wfLoadExtension' => '',
			),
			'skin' => array(
				'require_once' => '',
				'wfLoadSkin' => '',
			),
		);
		foreach( $configuration['extensions'] as $key => $extension ) {
			if( $extension[2] == 'require_once' && ( $key != 'ExtensionMediaWikiFarm' || !$this->codeDir ) ) {
				$extensions[$extension[1]]['require_once'] .= "require_once \"\$IP/{$extension[1]}s/{$extension[0]}/{$extension[0]}.php\";\n";
			} elseif( $key == 'ExtensionMediaWikiFarm' && $extension[2] == 'wfLoadExtension' && $this->codeDir ) {
				$extensions['extension']['wfLoadExtension'] .= "wfLoadExtension( 'MediaWikiFarm', " . var_export( $this->farmDir . '/extension.json', true ) . " );\n";
			} elseif( $extension[2] == 'wfLoad' . ucfirst( $extension[1] ) ) {
				$extensions[$extension[1]]['wfLoad' . ucfirst( $extension[1] )] .= 'wfLoad' . ucfirst( $extension[1] ) . '( ' . var_export( $extension[0], true ) . " );\n";
			}
		}

		# Skins loaded with require_once
		if( $extensions['skin']['require_once'] ) {
			$localSettings .= "\n# Skins loaded with require_once\n";
			$localSettings .= $extensions['skin']['require_once'];
		}

		# Extensions loaded with require_once
		if( $extensions['extension']['require_once'] ) {
			$localSettings .= "\n# Extensions loaded with require_once\n";
			$localSettings .= $extensions['extension']['require_once'];
		}

		# General settings
		$localSettings .= "\n# General settings\n";
		foreach( $configuration['settings'] as $setting => $value ) {
			$localSettings .= "\$$setting = " . var_export( $value, true ) . ";\n";
		}

		# Array settings
		$localSettings .= "\n# Array settings\n";
		foreach( $configuration['arrays'] as $setting => $value ) {
			$localSettings .= "if( !array_key_exists( '$setting', \$GLOBALS ) ) {\n\t\$GLOBALS['$setting'] = array();\n}\n";
		}
		foreach( $configuration['arrays'] as $setting => $value ) {
			$localSettings .= self::writeArrayAssignment( $value, "\$$setting" );
		}

		# Skins loaded with wfLoadSkin
		if( $extensions['skin']['wfLoadSkin'] ) {
			$localSettings .= "\n# Skins\n";
			$localSettings .= $extensions['skin']['wfLoadSkin'];
		}

		# Extensions loaded with wfLoadExtension
		if( $extensions['extension']['wfLoadExtension'] ) {
			$localSettings .= "\n# Extensions\n";
			$localSettings .= $extensions['extension']['wfLoadExtension'];
		}

		# Included files
		$localSettings .= "\n# Included files\n";
		foreach( $configuration['execFiles'] as $execFile ) {
			$localSettings .= "include '$execFile';\n";
		}

		if( $postconfig ) {
			$localSettings .= "\n" . $postconfig;
		}

		return $localSettings;
	}

	/**
	 * Set a wiki property and replace placeholders (property name version).
	 *
	 * @param string $name Name of the property.
	 * @param bool $mandatory This variable is mandatory.
	 * @return void
	 * @throws MWFConfigurationException When the variable is mandatory and missing.
	 * @throws InvalidArgumentException
	 */
	function setVariable( $name, $mandatory = false ) {

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
	 * @param string|string[] $value Value of the property.
	 * @return string|string[] Input where variables were replaced.
	 * @throws InvalidArgumentException When argument type is incorrect.
	 */
	function replaceVariables( $value ) {

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
	function readFile( $filename, $directory = '', $cache = true ) {

		# Check parameter
		if( !is_string( $filename ) ) {
			return false;
		}

		# Detect the format
		$format = strrchr( $filename, '.' );
		$array = false;

		# Check the file exists
		$prefixedFile = $directory ? $directory . '/' . $filename : $filename;
		$cachedFile = $this->cacheDir && $cache ? $this->cacheDir . '/config/' . $filename . '.php' : false;
		if( !is_file( $prefixedFile ) ) {
			$format = null;
		}

		# Format PHP
		if( $format == '.php' ) {

			$array = include $prefixedFile;
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

		# Cached version
		elseif( $cachedFile && is_string( $format ) && is_file( $cachedFile ) && filemtime( $cachedFile ) >= filemtime( $prefixedFile ) ) {

			return $this->readFile( $filename . '.php', $this->cacheDir . '/config', false );
		}

		# Format YAML
		elseif( $format == '.yml' || $format == '.yaml' ) {

			# Load Composer libraries
			# There is no warning if not present because to properly handle the error by returning false
			# This is only included here to avoid delays (~3ms without OPcache) during the loading using cached files or other formats
			if( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {

				require_once dirname( __FILE__ ) . '/Yaml.php';

				try {
					$array = wfMediaWikiFarm_readYAML( $prefixedFile );
				}
				catch( RuntimeException $e ) {
					$this->log[] = $e->getMessage();
					$this->log['unreadable-file'] = true;
					$array = false;
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
		elseif( !is_null( $format ) ) {
			return false;
		}

		# A null value is an empty file or value 'null'
		if( ( is_null( $array ) || $array === false ) && $cachedFile && is_file( $cachedFile ) ) {

			$this->log[] = 'Unreadable file \'' . $filename . '\'';
			$this->log['unreadable-file'] = true;

			return $this->readFile( $filename . '.php', $this->cacheDir . '/config', false );
		}

		# Regular return for arrays
		if( is_array( $array ) ) {

			if( $cachedFile && $directory != $this->cacheDir . '/config' && ( !is_file( $cachedFile ) || ( filemtime( $cachedFile ) < filemtime( $prefixedFile ) ) ) ) {
				self::cacheFile( $array, $filename . '.php', $this->cacheDir . '/config' );
			}

			return $array;
		}

		# Error for any other type
		return false;
	}

	/**
	 * Create a cache file.
	 *
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 *
	 * @param array|string $array Array of the data to be cached.
	 * @param string $filename Name of the cache file; this filename must have an extension '.php' else no cache file is saved.
	 * @param string $directory Name of the parent directory; null for default cache directory
	 * @return void
	 */
	static function cacheFile( $array, $filename, $directory ) {

		if( !preg_match( '/\.php$/', $filename ) ) {
			return;
		}

		$prefixedFile = $directory . '/' . $filename;
		$tmpFile = $prefixedFile . '.tmp';

		# Prepare string
		if( is_array( $array ) ) {
			$php = "<?php\n\n// WARNING: file automatically generated: do not modify.\n\nreturn " . var_export( $array, true ) . ';';
		} else {
			$php = (string) $array;
		}

		# Create parent directories
		if( !is_dir( dirname( $tmpFile ) ) ) {
			$path = '';
			foreach( explode( '/', dirname( $prefixedFile ) ) as $dir ) {
				$path .= '/' . $dir;
				if( !is_dir( $path ) ) {
					mkdir( $path );
				}
			}
		}

		# Create temporary file and move it to final file
		if( file_put_contents( $tmpFile, $php ) ) {
			rename( $tmpFile, $prefixedFile );
		}
	}

	/**
	 * Guess if a given directory contains MediaWiki.
	 *
	 * This heuristic (presence of [dir]/includes/DefaultSettings.php) has no false negatives
	 * (every MediaWiki from 1.1 to (at least) 1.27 has such a file) and probably has a few, if
	 * any, false positives (another software which has the very same file).
	 *
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 *
	 * @param string $dir The base directory which could contain MediaWiki.
	 * @return bool The directory really contains MediaWiki.
	 */
	static function isMediaWiki( $dir ) {
		return is_file( $dir . '/includes/DefaultSettings.php' );
	}

	/**
	 * Merge multiple arrays together.
	 * On encountering duplicate keys, merge the two, but ONLY if they're arrays.
	 * PHP's array_merge_recursive() merges ANY duplicate values into arrays,
	 * which is not fun
	 * This function is almost the same as SiteConfiguration::arrayMerge, with the
	 * difference an existing scalar value has precedence EVEN if evaluated to false,
	 * in order to override permissions array with removed rights.
	 *
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 * @SuppressWarning(PHPMD.StaticAccess)
	 *
	 * @param array $array1 First array.
	 * @return array
	 */
	static function arrayMerge( $array1 /* ... */ ) {
		$out = $array1;
		if ( is_null( $out ) ) {
			$out = array();
		}
		$argsCount = func_num_args();
		for ( $i = 1; $i < $argsCount; $i++ ) {
			$array = func_get_arg( $i );
			if ( is_null( $array ) ) {
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

	/**
	 * Write an 'array diff' (when only a subarray is modified) in plain PHP.
	 *
	 * Note that, given PHP lists and dictionaries use the same syntax, this function
	 * try to recognise a list when the array diff has exactly the keys 0, 1, 2, 3,…
	 * but there could be false positives.
	 *
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 * @SuppressWarning(PHPMD.StaticAccess)
	 *
	 * @param array $array The 'array diff' (part of an array to be modified).
	 * @param string $prefix The beginning of the plain PHP, should be something like '$myArray'.
	 * @return string The plain PHP for this array assignment.
	 */
	static function writeArrayAssignment( $array, $prefix ) {

		$result = '';
		$isList = ( count( array_diff( array_keys( $array ), range( 0, count( $array ) ) ) ) == 0 );
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

	/**
	 * Composer key depending on the activated extensions and skins.
	 *
	 * Extension names should follow the form 'ExtensionMyWonderfulExtension';
	 * Skin names should follow the form 'SkinMyWonderfulSkin'.
	 *
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 *
	 * @param string $name Name of extension or skin.
	 * @return string Composer key.
	 */
	static function composerKey( $name ) {

		if( $name == '' ) {
			return '';
		}

		return substr( md5( $name ), 0, 8 );
	}
}
