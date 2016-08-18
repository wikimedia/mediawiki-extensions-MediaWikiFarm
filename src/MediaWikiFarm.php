<?php
/**
 * Class MediaWikiFarm.
 * 
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
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
	
	/** @var MediaWikiFarm|null Singleton. */
	private static $self = null;
	
	/** @var string Farm code directory. */
	private $farmDir = '';
	
	/** @var string Farm configuration directory. */
	private $configDir = '';
	
	/** @var string|null MediaWiki code directory, where each subdirectory is a MediaWiki installation. */
	private $codeDir = null;
	
	/** @var string|false MediaWiki cache directory. */
	private $cacheDir = '/tmp/mw-cache';
	
	/** @var array Configuration for this farm. */
	private $farmConfig = array();
	
	/** @var string[] Variables related to the current request. */
	private $variables = array(
		'$SERVER' => '',
		'$SUFFIX' => '',
		'$WIKIID' => '',
		'$VERSION' => null,
		'$CODE' => '',
	);
	
	/** @var array Configuration parameters for this wiki. */
	private $configuration = array(
		'general' => array(),
		'skins' => array(),
		'extensions' => array(),
		'execFiles' => array(),
	);
	
	
	
	/* 
	 * Accessors
	 * --------- */
	
	/**
	 * Get code directory, where subdirectories are MediaWiki versions.
	 * 
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
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
	 * @return string Cache directory.
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
	 * @return string[] Request variables.
	 */
	function getVariables() {
		return $this->variables;
	}
	
	/**
	 * Get a variable related to the current request.
	 * 
	 * @mediawikifarm-const
	 * @param string $var Variable name (prefixed with '$').
	 * @return string|null Requested variable or null if the variable does not exist.
	 */
	function getVariable( $var ) {
		return array_key_exists( $var, $this->variables ) ? $this->variables[$var] : null;
	}
	
	/**
	 * Get MediaWiki configuration.
	 * 
	 * This associative array contains four sections:
	 *   - 'general': associative array of MediaWiki configuration (e.g. 'wgServer' => '//example.org');
	 *   - 'skins': associative array of skins configuration (e.g. 'Vector' => array( '_loading' => 'wfLoadSkin' ));
	 *   - 'extensions': associative array of extensions configuration (e.g. 'ParserFunctions' => array( '_loading' => 'wfLoadExtension' ));
	 *   - 'execFiles': list of PHP files to execute at the end.
	 * 
	 * @mediawikifarm-const
	 * @param string|null $key Key of the wanted section or null for the whole array.
	 * @return array MediaWiki configuration, either entire, either a part depending on the parameter.
	 */
	function getConfiguration( $key = null ) {
		switch( $key ) {
			case 'general':
				return $this->configuration['general'];
			case 'skins':
				return $this->configuration['skins'];
			case 'extensions':
				return $this->configuration['extensions'];
			case 'execFiles':
				return $this->configuration['execFiles'];
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
	 * @return string $entryPoint Identical entry point as passed in input.
	 */
	static function load( $entryPoint = '' ) {
		
		global $wgMediaWikiFarm;
		
		try {
			# Initialise object
			$wgMediaWikiFarm = self::getInstance();
			
			# Check existence
			$exists = $wgMediaWikiFarm->checkExistence();
		}
		catch( Exception $e ) {
			
			if( PHP_SAPI == 'cli' )
				exit( 1 );
			
			$httpProto = $_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
			header( "$httpProto 500 Internal Server Error" );
			exit;
		}
		
		if( !$exists ) {
			
			if( PHP_SAPI == 'cli' )
				exit( 1 );
			
			# Display an informational page when the requested wiki doesn’t exist, only when a page was requested, but not a resource, to avoid waste resources
			$httpProto = $_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
			header( "$httpProto 404 Not Found" );
			if( $entryPoint == 'index.php' && array_key_exists( '$HTTP404', $wgMediaWikiFarm->variables ) && is_file( $wgMediaWikiFarm->variables['$HTTP404'] ) )
				include $wgMediaWikiFarm->variables['$HTTP404'];
			exit;
		}
		
		# Go to version directory
		if( getcwd() != $wgMediaWikiFarm->variables['$CODE'] )
			chdir( $wgMediaWikiFarm->variables['$CODE'] );
		
		# Define config callback to avoid creating a stub LocalSettings.php (experimental)
		#define( 'MW_CONFIG_CALLBACK', 'MediaWikiFarm::loadConfig' );
		
		# Define config file to avoid creating a stub LocalSettings.php
		if( !defined( 'MW_CONFIG_FILE' ) )
			define( 'MW_CONFIG_FILE', $wgMediaWikiFarm->getConfigFile() );
		
		return $entryPoint;
	}
	
	/**
	 * Create or return the unique object of type MediaWikiFarm for this request.
	 * 
	 * There is no explicit parameter, but $_SERVER['HTTP_HOST'] or $_SERVER['SERVER_NAME'] must be defined.
	 * 
	 * @throws MWFConfigurationException When there is no $_SERVER['HTTP_HOST'] nor $_SERVER['SERVER_NAME'] or issue with main configuration file.
	 * @return MediaWikiFarm Singleton.
	 */
	static function getInstance() {
		
		global $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir, $wgMediaWikiFarmCacheDir;
		
		# Object was already created
		if( self::$self )
			return self::$self;
		
		# Detect the current host
		# Warning: do not use $GLOBALS['_SERVER']['HTTP_HOST']: bug with PHP7: it is not initialised in early times of a script
		if( array_key_exists( 'HTTP_HOST', $_SERVER ) || array_key_exists( 'SERVER_NAME', $_SERVER ) )
			$host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
		else throw new MWFConfigurationException( 'Undefined host' );
		
		# Create the object for this host
		self::$self = new self( $host, $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir, $wgMediaWikiFarmCacheDir );
		
		return self::$self;
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
	 * @throws MWFConfigurationException
	 * @throws InvalidArgumentException
	 * @return bool The wiki does exist.
	 */
	function checkExistence() {
		
		# In the multiversion case, informations are already loaded and nonexistent wikis are already verified
		if( $this->variables['$CODE'] )
			return true;
		
		# Set HTTP 404 early in case it is needed
		$this->setVariable( 'HTTP404' );
		
		# Replace variables in the host name and possibly retrieve the version
		if( !$this->checkHostVariables() )
			return false;
		
		# Set wikiID, the unique identifier of the wiki
		$this->setVariable( 'suffix', true );
		$this->setVariable( 'wikiID', true );
		
		# Set the version of the wiki
		$this->setVersion();
		
		# Set other variables of the wiki
		$this->setOtherVariables();
		
		# Set available suffixes and wikis
		// This is not useful since nobody else use available suffixes and wikis
		// For now, remove loading of one config file to improve a bit performance
		//$this->setWgConf();
		
		if( !$this->variables['$CODE'] || !is_dir( $this->variables['$CODE'] ) )
			return false;
		
		return true;
	}
	
	/**
	 * This function loads MediaWiki configuration (parameters) in global variables.
	 * 
	 * @return void
	 */
	function loadMediaWikiConfig() {
		
		if( count( $this->configuration['general'] ) == 0 )
			
			$this->getMediaWikiConfig();
		
		# Set general parameters as global variables
		foreach( $this->configuration['general'] as $setting => $value ) {
			
			$GLOBALS[$setting] = $value;
		}
	}
	
	/**
	 * This function load the skins configuration (wfLoadSkin loading mechanism and parameters) in global variables.
	 * 
	 * WARNING: it doesn’t load the skins with the require_once mechanism (it is not possible in
	 * a function because variables would inherit the non-global scope); such skins must be loaded
	 * in the global scope.
	 * 
	 * @mediawikifarm-const
	 * @return void
	 */
	function loadSkinsConfig() {
		
		# Load skins with the wfLoadSkin mechanism
		foreach( $this->configuration['skins'] as $skin => $value ) {
			
			if( $value['_loading'] == 'wfLoadSkin' )
				
				wfLoadSkin( $skin );
		}
		
		# Set skin parameters as global variables
		foreach( $this->configuration['skins'] as $skin => $settings ) {
			
			foreach( $settings as $setting => $value )
				
				$GLOBALS[$setting] = $value;
		}
	}
	
	/**
	 * This function load the extensions configuration (wfLoadSkin loading mechanism and parameters) in global variables.
	 * 
	 * WARNING: it doesn’t load the extensions with the require_once mechanism (it is not possible in
	 * a function because variables would inherit the non-global scope); such extensions must be loaded
	 * in the global scope.
	 * 
	 * @mediawikifarm-const
	 * @return void
	 */
	function loadExtensionsConfig() {
		
		# Register this extension MediaWikiFarm to appear in Special:Version
		if( function_exists( 'wfLoadExtension' ) ) {
			wfLoadExtension( 'MediaWikiFarm', $this->codeDir ? $this->farmDir . '/extension.json' : null );
		}
		else {
			$GLOBALS['wgExtensionCredits']['other'][] = array(
				'path' => $this->farmDir . '/MediaWikiFarm.php',
				'name' => 'MediaWikiFarm',
				'version' => '0.2.0',
				'author' => 'Seb35',
				'url' => 'https://www.mediawiki.org/wiki/Extension:MediaWikiFarm',
				'descriptionmsg' => 'mediawikifarm-desc',
				'license-name' => 'GPL-3.0+'
			);

			$GLOBALS['wgAutoloadClasses']['MediaWikiFarm'] = 'src/MediaWikiFarm.php';
			$GLOBALS['wgAutoloadClasses']['MWFConfigurationException'] = 'src/MediaWikiFarm.php';
			$GLOBALS['wgMessagesDirs']['MediaWikiFarm'] = array( 'i18n' );
			$GLOBALS['wgHooks']['UnitTestsList'] = array( 'MediaWikiFarm::onUnitTestsList' );
		}
		
		# Load extensions with the wfLoadExtension mechanism
		foreach( $this->configuration['extensions'] as $extension => $value ) {
			
			if( $value['_loading'] == 'wfLoadExtension' )
				
				wfLoadExtension( $extension );
		}
		
		# Set extension parameters as global variables
		foreach( $this->configuration['extensions'] as $extension => $settings ) {
			
			foreach( $settings as $setting => $value )
				
				$GLOBALS[$setting] = $value;
		}
	}
	
	/**
	 * Synchronise the version in the 'expected version' and deployment files.
	 * 
	 * @return void
	 */
	function updateVersionAfterMaintenance() {
		
		if( !$this->variables['$VERSION'] )
			return;
		
		$this->updateVersion( $this->variables['$VERSION'] );
	}
	
	/**
	 * Return the file where must be loaded the configuration from.
	 * 
	 * This function is important to avoid the two parts of the extension (checking of
	 * existence and loading of configuration) are located in the same directory in the
	 * case mono- and multi-version installations are mixed. Without it, this class
	 * could be defined by two different files, and PHP doesn’t like it.
	 * 
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 * @return string File where is loaded the configuration.
	 */
	function getConfigFile() {
		
		return $this->farmDir . '/src/main.php';
	}
	
	/**
	 * Load the whole configuration in the case MW_CONFIG_CALLBACK is registered (experimental).
	 * 
	 * This is about the same thing as the file src/main.php, but given it is not possible
	 * to "execute require_once in a global scope", the extensions/skins loaded with
	 * require_once are not called (existing global variables could be introduced with
	 * extract( $GLOBALS, EXTR_REFS ) but newly-created variables can not be detected and
	 * exported to global scope).
	 * 
	 * NB: this loading mechanism (constant MW_CONFIG_CALLBACK) exists since MediaWiki 1.15.
	 * 
	 * @return void
	 */
	static function loadConfig() {
		
		# Load general MediaWiki configuration
		MediaWikiFarm::getInstance()->loadMediaWikiConfig();
		
		# Load skins with the wfLoadSkin mechanism
		MediaWikiFarm::getInstance()->loadSkinsConfig();
		
		# Load extensions with the wfLoadExtension mechanism
		MediaWikiFarm::getInstance()->loadExtensionsConfig();
		
		foreach( MediaWikiFarm::getInstance()->configuration['execFiles'] as $execFile ) {
			
			@include $execFile;
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
	 * @param string $host Requested host.
	 * @param string $configDir Configuration directory.
	 * @param string|null $codeDir Code directory; if null, the current MediaWiki installation is used.
	 * @param string|false|null $cacheDir Cache directory; if false, the cache is disabled.
	 * @throws MWFConfigurationException When no farms.yml/php/json is found.
	 * @throws InvalidArgumentException When wrong input arguments are passed.
	 * @return MediaWikiFarm
	 */
	function __construct( $host, $configDir, $codeDir = null, $cacheDir = null ) {
		
		# Default value for $cacheDir
		if( is_null( $cacheDir ) ) $cacheDir = '/tmp/mw-cache';
		
		# Check parameters
		if( !is_string( $host ) ) {
			throw new InvalidArgumentException( 'Missing host name in constructor' );
		}
		if( !is_string( $configDir ) || !is_dir( $configDir ) ) {
			throw new InvalidArgumentException( 'Invalid directory for the farm configuration' );
		}
		if( !is_null( $codeDir ) && (!is_string( $codeDir ) || !is_dir( $codeDir )) ) {
			throw new InvalidArgumentException( 'Code directory must be null or a directory' );
		}
		if( !is_string( $cacheDir ) && $cacheDir !== false ) {
			throw new InvalidArgumentException( 'Cache directory must be false, null, or a directory' );
		}
		
		# Set parameters
		$this->farmDir = dirname( dirname( __FILE__ ) );
		$this->configDir = $configDir;
		$this->codeDir = $codeDir;
		$this->cacheDir = $cacheDir;
		
		# Create cache directory
		if( $this->cacheDir && !is_dir( $this->cacheDir ) ) {
			mkdir( $this->cacheDir );
		}
		
		# Now select the right farm amoung all farms
		$result = $this->selectFarm( $host, false, 5 );
		
		# Success
		if( $result['farm'] ) {
			$this->farmConfig = $result['config'];
			$this->variables = array_merge( $result['variables'], $this->variables );
			if( $this->cacheDir ) {
				$this->cacheDir .= '/' . $result['farm'];
			}
			if( $this->cacheDir && !is_dir( $this->cacheDir ) ) {
				mkdir( $this->cacheDir );
			}
			$this->variables['$SERVER'] = $result['host'];
		}
		
		# Hard fail
		elseif( !$result['farms'] ) {
			throw new MWFConfigurationException( 'No configuration file found' );
		}
		elseif( $result['redirects'] <= 0 ) {
			throw new MWFConfigurationException( 'Infinite or too long redirect detected' );
		}
		elseif( !$result['farm'] ) {
			throw new MWFConfigurationException( 'No farm corresponding to this host' );
		}
	}
	
	/**
	 * Select the farm.
	 * 
	 * Constant function (do not write any object property).
	 * 
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
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
			if( $farms = $this->readFile( 'farms.yml', $this->configDir ) );
			elseif( $farms = $this->readFile( 'farms.php', $this->configDir ) );
			elseif( $farms = $this->readFile( 'farms.json', $this->configDir ) );
			else return array( 'host' => $host, 'farm' => false, 'config' => false, 'variables' => false, 'farms' => false, 'redirects' => $redirects );
		}
		
		# For each proposed farm, check if the host matches
		foreach( $farms as $farm => $config ) {
			
			if( !preg_match( '/^' . $config['server'] . '$/i', $host, $matches ) )
				continue;
			
			# Initialise variables from the host
			$variables = array();
			foreach( $matches as $key => $value ) {
				if( is_string( $key ) ) {
					$variables['$'.$key] = $value;
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
	 * @mediawikifarm-idempotent
	 * @throws MWFConfigurationException When the farm configuration doesn’t define 'variables' or when a file defining the existing values for a variable is missing or badly formatted.
	 * @throws InvalidArgumentException
	 * @return string|null|false If an existing version is found in files, returns a string; if no version is found, returns null; if the host is missing in existence files, returns false.
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	function checkHostVariables() {
		
		$this->variables['$VERSION'] = null;
		
		if( !array_key_exists( 'variables', $this->farmConfig ) ) {
			throw new MWFConfigurationException( 'Undefined key \'variables\' in the farm configuration' );
		}
		
		# For each variable, in the given order, check if the variable exists, check if the
		# wiki exists in the corresponding listing file, and get the version if available
		foreach( $this->farmConfig['variables'] as $variable ) {
			
			$key = $variable['variable'];
			# If the variable doesn’t exist, continue
			if( !array_key_exists( '$'.$key, $this->variables ) )
				continue;
			
			$value = $this->variables['$'.strtolower($key)];
			
			# If every values are correct, continue
			if( !array_key_exists( 'file', $variable ) || !is_string( $variable['file'] ) )
				continue;
			
			# Really check if the variable is in the listing file
			$choices = $this->readFile( $this->replaceVariables( $variable['file'] ), $this->configDir );
			if( $choices === false ) {
				throw new MWFConfigurationException( 'Missing or badly formatted file \'' . $variable['file'] . '\' defining existing values for variable \'' . $variable['key'] . '\'' );
			}
			
			# Check if the array is a simple list of wiki identifiers without version information…
			if( array_keys( $choices ) === range( 0, count( $choices ) - 1 ) ) {
				if( !in_array( $value, $choices ) )
					return false;
			
			# …or a dictionary with wiki identifiers and corresponding version information
			} else {
				
				if( !array_key_exists( $value, $choices ) )
					return false;
				
				if( is_string( $this->codeDir ) && self::isMediaWiki( $this->codeDir . '/' . ((string) $choices[$value]) ) ) {
					
					$this->variables['$VERSION'] = (string) $choices[$value];
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Setting of the version, either from the input if already got, either from a file.
	 * 
	 * @throws MWFConfigurationException When the file defined by 'versions' is missing or badly formatted.
	 * @throws LogicException
	 * @return bool The version was set, and the wiki could exist.
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	private function setVersion() {
		
		global $IP, $mwfScript;
		
		# Special case for the update: new (uncached) version must be used
		$force = false;
		if( is_string( $mwfScript ) && $mwfScript == 'maintenance/update.php' )
			$force = true;
		
		# Read cache file
		$deployments = array();
		$this->setVariable( 'deployments' );
		if( array_key_exists( '$DEPLOYMENTS', $this->variables ) && !$force ) {
			if( strrchr( $this->variables['$DEPLOYMENTS'], '.' ) != '.php' ) $this->variable['$DEPLOYMENTS'] .= '.php';
			$deployments = $this->readFile( $this->variables['$DEPLOYMENTS'], $this->configDir );
			if( $deployments === false ) $deployments = array();
		}
		if( is_string( $this->codeDir ) && array_key_exists( $this->variables['$WIKIID'], $deployments ) ) {
			$this->variables['$VERSION'] = $deployments[$this->variables['$WIKIID']];
		}
		# In the case multiversion is configured and version is already known
		elseif( is_string( $this->codeDir ) && is_string( $this->variables['$VERSION'] ) ) {
			
			# Cache the version
			if( !$force )
				$this->updateVersion( $this->variables['$VERSION'] );
		}
		
		# In the case multiversion is configured, but version is not known as of now
		elseif( is_string( $this->codeDir ) && is_null( $this->variables['$VERSION'] ) ) {
			
			# Replace variables in the file name containing all versions, if existing
			$this->setVariable( 'versions' );
			
			$versions = $this->readFile( $this->variables['$VERSIONS'], $this->configDir );
			
			if( !$versions ) {
				throw new MWFConfigurationException( 'Missing or badly formatted file \'' . $this->variables['$VERSIONS'] . '\' containing the versions for wikis.' );
			}
			
			if( array_key_exists( $this->variables['$WIKIID'], $versions ) && self::isMediaWiki( $this->codeDir . '/' . $versions[$this->variables['$WIKIID']] ) )
				$this->variables['$VERSION'] = $versions[$this->variables['$WIKIID']];
			
			elseif( array_key_exists( $this->variables['$SUFFIX'], $versions ) && self::isMediaWiki( $this->codeDir . '/' . $versions[$this->variables['$SUFFIX']] ) )
				$this->variables['$VERSION'] = $versions[$this->variables['$SUFFIX']];
			
			elseif( array_key_exists( 'default', $versions ) && self::isMediaWiki( $this->codeDir . '/' . $versions['default'] ) )
				$this->variables['$VERSION'] = $versions['default'];
			
			else return false;
			
			# Cache the version
			if( !$force )
				$this->updateVersion( $this->variables['$VERSION'] );
		}
		
		# In the case this is a monoversion installation
		elseif( is_null( $this->codeDir ) ) {
			
			$this->variables['$VERSION'] = '';
			$this->variables['$CODE'] = $IP;
		}
		else {
			throw new LogicException( 'Wrong combinaison of $this->codeDir and $this->variables[\'$VERSION\'].' );
		}
		
		# Set the version in the wiki configuration and as a variable
		if( $this->variables['$VERSION'] ) {
			$this->variables['$CODE'] = $this->codeDir . '/' . $this->variables['$VERSION'];
		}
		
		return true;
	}
	
	/**
	 * Computation of secondary variables.
	 * 
	 * These can reuse previously-computed variables: URL variables (lowercase), '$WIKIID', '$SUFFIX', '$VERSION', '$CODE'.
	 * 
	 * @throws InvalidArgumentException
	 * @return void
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
	private function updateVersion( $version ) {
	
		# Check a deployment file is wanted
		if( !array_key_exists( '$DEPLOYMENTS', $this->variables ) )
			return;
		
		# Read current deployments
		if( strrchr( $this->variables['$DEPLOYMENTS'], '.' ) != '.php' ) $this->variables['$DEPLOYMENTS'] .= '.php';
		$deployments = $this->readFile( $this->variables['$DEPLOYMENTS'], $this->configDir );
		if( $deployments === false ) $deployments = array();
		elseif( array_key_exists( $this->variables['$WIKIID'], $deployments ) && $deployments[$this->variables['$WIKIID']] == $version )
			return;
		
		# Update the deployment file
		$deployments[$this->variables['$WIKIID']] = $version;
		$this->cacheFile( $deployments, $this->variables['$DEPLOYMENTS'], $this->configDir );
	}
	
	/**
	 * Set available suffixes and wikis.
	 * 
	 * @todo Still hacky: before setting parameters in stone in farms.yml, various configurations should be reviewed to select accordingly the rights management modelisation
	 * @return void
	 */
	/*function setWgConf() {
		
		global $wgConf;
		
		$wgConf->suffixes = array( $this->variables['$SUFFIX'] );
		$wikiIDs = $this->readFile( $this->variables['$SUFFIX'] . '/wikis.yml', $this->configDir );
		foreach( array_keys( $wikiIDs ) as $wiki ) {
			$wgConf->wikis[] = $wiki . '-' . $this->variables['$SUFFIX'];
		}
	}*/
	
	/**
	 * Get or compute the configuration (MediaWiki, skins, extensions) for a wiki.
	 * 
	 * This function uses a caching mechanism in order to avoid recomputing each time the
	 * configuration; it is rebuilt when origin configuration files are changed.
	 * 
	 * The returned array has the following format:
	 * array( 'general' => array( 'wgSitename' => 'Foo', ... ),
	 *        'skins' => array( '_loading' => 'wfLoadSkin'|'require_once',
	 *                          'wgFlowParsoidTimeout' => 100, ...
	 *                        ),
	 *        'extensions' => array( '_loading' => 'wfLoadExtension'|'require_once',
	 *                               'wgFlowParsoidTimeout' => 100, ...
	 *                             )
	 *      )
	 * 
	 * @return array Global parameter variables and loading mechanisms for skins and extensions.
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 */
	function getMediaWikiConfig() {
		
		global $wgConf;
		
		# In MediaWiki 1.16, $wgConf is not created by default
		if( is_null( $wgConf ) ) {
			$wgConf = new SiteConfiguration();
		}
		
		$myWiki = $this->variables['$WIKIID'];
		$mySuffix = $this->variables['$SUFFIX'];
		if( $this->variables['$VERSION'] ) $cacheFile = $this->replaceVariables( 'config-$VERSION-$SUFFIX-$WIKIID.php' );
		else $cacheFile = $this->replaceVariables( 'config-$SUFFIX-$WIKIID.php' );
		
		# Check modification time of original config files
		$oldness = 0;
		foreach( $this->farmConfig['config'] as $configFile ) {
			if( !is_string( $configFile['file'] ) ) continue;
			$oldness = max( $oldness, @filemtime( $this->configDir . '/' . $this->replaceVariables( $configFile['file'] ) ) );
		}
		
		# Use cache file or recompile the config
		if( is_string( $this->cacheDir ) && is_file( $this->cacheDir . '/' . $cacheFile ) && @filemtime( $this->cacheDir . '/' . $cacheFile ) >= $oldness )
			$this->configuration = $this->readFile( $cacheFile, $this->cacheDir );
		
		else {
			
			$globals =& $this->configuration;
			
			# Populate wgConf
			if( !$this->populatewgConf() )
				return false;
			
			# Get specific configuration for this wiki
			# Do not use SiteConfiguration::extractAllGlobals or the configuration caching would become
			# ineffective and there would be inconsistencies in this process
			$globals['general'] = $wgConf->getAll( $myWiki, $mySuffix, array( 'data' => $this->variables['$DATA'] ) );
			
			# For the permissions array, fix a small strangeness: when an existing default permission
			# is true, it is not possible to make it false in the specific configuration
			if( array_key_exists( '+wgGroupPermissions', $wgConf->settings ) )
				
				$globals['general']['wgGroupPermissions'] = MediaWikiFarm::arrayMerge( $wgConf->get( '+wgGroupPermissions', $myWiki, $mySuffix ), $globals['general']['wgGroupPermissions'] );
			
			//if( array_key_exists( '+wgDefaultUserOptions', $wgConf->settings ) )
				//$globals['general']['wgDefaultUserOptions'] = MediaWikiFarm::arrayMerge( $wgConf->get( '+wgDefaultUserOptions', $myWiki, $mySuffix ), $globals['general']['wgDefaultUserOptions'] );
			
			# Extract from the general configuration skin and extension configuration
			$this->extractSkinsAndExtensions();
			
			# Save this configuration in a serialised file
			$this->cacheFile( $globals, $cacheFile );
		}
		
		$wgConf->siteParamsCallback = array( $this, 'SiteConfigurationSiteParamsCallback' );
	}
	
	/**
	 * Popuplate wgConf from config files.
	 * 
	 * @return bool Success.
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	function populatewgConf() {
		
		global $wgConf;
		
		foreach( $this->farmConfig['config'] as $configFile ) {
			
			if( !is_array( $configFile ) ) continue;
			
			# Replace variables
			$configFile = $this->replaceVariables( $configFile );
			
			# Executable config files
			if( array_key_exists( 'exec', $configFile ) && $configFile['exec'] ) {
				
				$this->configuration['execFiles'][] = $this->configDir . '/' . $configFile['file'];
				continue;
			}
			
			$theseSettings = $this->readFile( $configFile['file'], $this->configDir );
			if( $theseSettings === false ) {
				# If a file is unavailable, skip it
				continue;
			}
			
			# Key 'default' => no choice of the wiki
			if( $configFile['key'] == 'default' ) {
				
				foreach( $theseSettings as $setting => $value ) {
					
					$wgConf->settings[$setting]['default'] = $value;
				}
			}
			
			# Key '*' => choice of any wiki
			elseif( $configFile['key'] == '*' ) {
				
				foreach( $theseSettings as $setting => $value ) {
					
					foreach( $value as $suffix => $val ) {
						
						$wgConf->settings[$setting][$suffix] = $val;
					}
				}
			}
			
			# Other key
			else {
				
				$defaultKey = '';
				$classicKey = '';
				if( array_key_exists( 'default', $configFile ) && is_string( $configFile['default'] ) )
					$defaultKey = $this->replaceVariables( $configFile['default'] );
				elseif( is_string( $configFile['key'] ) )
					$classicKey = $this->replaceVariables( $configFile['key'] );
				
				foreach( $theseSettings as $setting => $values ) {
					
					foreach( $values as $wiki => $val ) {
						
						if( $wiki == 'default' && $defaultKey ) $wgConf->settings[$setting][$defaultKey] = $val;
						else $wgConf->settings[$setting][str_replace( '*', $wiki, $classicKey )] = $val;
					}
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Callback to use in SiteConfiguration.
	 * 
	 * It is not possible to retrieve the language because SiteConfiguration will loop.
	 * It is not ideal since other parameters from other suffixes are not known.
	 * 
	 * @mediawikifarm-const
	 * @param SiteConfiguration $wgConf SiteConfiguration object.
	 * @param string $dbName Database name.
	 * @return array
	 */
	function SiteConfigurationSiteParamsCallback( $wgConf, $wikiID ) {
		
		if( substr( $wikiID, strlen( $wikiID ) - strlen( $this->variables['$SUFFIX'] ) ) != $this->variables['$SUFFIX'] )
			return null;
		
		return array(
			'suffix' => $this->variables['$SUFFIX'],
			'lang' => '',
			'tags' => array(),
			'params' => array(),
		);
	}
	
	/**
	 * Extract skin and extension configuration from the general configuration.
	 * 
	 * @return void
	 */
	function extractSkinsAndExtensions() {
		
		$globals =& $this->configuration;
		
		# Search for skin and extension activation
		$unsetPrefixes = array();
		foreach( $globals['general'] as $setting => $value ) {
			if( preg_match( '/^wgUse(Extension|Skin)(.+)$/', $setting, $matches ) && $value === true ) {
				
				$type = strtolower( $matches[1] );
				$name = $matches[2];
				$loadingMechanism = $this->detectLoadingMechanism( $type, $name );
				
				if( is_null( $loadingMechanism ) ) $unsetPrefixes[] = $name;
				else $globals[$type.'s'][$name] = array( '_loading' => $loadingMechanism );
				
				unset( $globals['general'][$setting] );
			}
			elseif( preg_match( '/^wgUse(?:Skin|Extension|LocalExtension)(.+)$/', $setting, $matches ) && $value !== true ) {
				
				$unsetPrefixes[] = $matches[1];
				unset( $globals['general'][$setting] );
			}
		}
		
		# Extract skin and extension configuration from the general configuration
		$regexSkins = count( $globals['skins'] ) ? '/^wg(' . implode( '|',
			array_map(
				array( 'MediaWikiFarm', 'protectRegex' ),
				array_keys( $globals['skins'] )
			)
		) . ')/' : false;
		$regexExtensions = count( $globals['extensions'] ) ? '/^wg(' . implode( '|',
			array_map(
				array( 'MediaWikiFarm', 'protectRegex' ),
				array_keys( $globals['extensions'] )
			)
		) . ')/' : false;
		$regexUnsetPrefixes = count( $unsetPrefixes ) ? '/^wg(' . implode( '|',
			array_map(
				array( 'MediaWikiFarm', 'protectRegex' ),
				$unsetPrefixes
			)
		) . ')/' : false;
		foreach( $globals['general'] as $setting => $value ) {
			
			if( $regexSkins && preg_match( $regexSkins, $setting, $matches ) ) {
				$globals['skins'][$matches[1]][$setting] = $value;
				unset( $setting );
			}
			elseif( $regexExtensions && preg_match( $regexExtensions, $setting, $matches ) ) {
				$globals['extensions'][$matches[1]][$setting] = $value;
				unset( $setting );
			}
			elseif( $regexUnsetPrefixes && preg_match( $regexUnsetPrefixes, $setting, $matches ) )
				unset( $matches[1] );
		}
	}
	
	/**
	 * Detection of the loading mechanism of extensions and skins.
	 * 
	 * @mediawikifarm-const
	 * @param string $type Type, in ['extension', 'skin'].
	 * @param string $name Name of the extension/skin.
	 * @return string|null Loading mechnism in ['wfLoadExtension', 'wfLoadSkin', 'require_once', 'composer'] or null if all mechanisms failed.
	 */
	function detectLoadingMechanism( $type, $name ) {
		
		if( !is_dir( $this->variables['$CODE'].'/'.$type.'s/'.$name ) )
			return null;
		
		# An extension.json/skin.json file is in the directory -> assume it is the loading mechanism
		if( function_exists( 'wfLoad'.ucfirst($type) ) && is_file( $this->variables['$CODE'].'/'.$type.'s/'.$name.'/'.$type.'.json' ) )
			return 'wfLoad'.ucfirst($type);
		
		# A MyExtension.php file is in the directory -> assume it is the loading mechanism
		elseif( is_file( $this->variables['$CODE'].'/'.$type.'s/'.$name.'/'.$name.'.php' ) )
			return 'require_once';
		
		# A composer.json file is in the directory -> assume it is the loading mechanism if previous mechanisms didn’t succeed
		elseif( is_file( $this->variables['$CODE'].'/'.$type.'s/'.$name.'/composer.json' ) )
			return 'composer';
		
		return null;
	}
	
	/**
	 * Set a wiki property and replace placeholders (property name version).
	 * 
	 * @param string $name Name of the property.
	 * @param bool This variable is mandatory.
	 * @throws MWFConfigurationException When the variable is mandatory and missing.
	 * @throws InvalidArgumentException
	 * @return void
	 */
	function setVariable( $name, $mandatory = false ) {
		
		if( !array_key_exists( $name, $this->farmConfig ) ) {
			if( $mandatory ) throw new MWFConfigurationException( 'Missing key \'$name\' in farm configuration.' );
			return;
		}
		
		if( !is_string( $this->farmConfig[$name] ) )
			return;
		
		$this->variables['$'.strtoupper($name)] = $this->replaceVariables( $this->farmConfig[$name] );
	}
	
	/**
	 * Replace variables in a string.
	 * 
	 * Constant function (do not write any object property).
	 * 
	 * @param string|string[] $value Value of the property.
	 * @throws InvalidArgumentException When argument type is incorrect.
	 * @return string|string[] Input where variables were replaced.
	 */
	function replaceVariables( $value ) {
		
		if( is_string( $value ) )
			return str_replace( array_keys( $this->variables ), $this->variables, $value );
		
		elseif( is_array( $value ) ) {
			
			foreach( $value as &$subvalue ) {
				$subvalue = str_replace( array_keys( $this->variables ), $this->variables, $subvalue );
			}
			return $value;
		}
		
		throw new InvalidArgumentException( 'Argument of MediaWikiFarm->replaceVariables() must be a string or an array.' );
	}
	
	/**
	 * Add files for unit testing.
	 * 
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 * @param string[] $files The test files.
	 * @return true
	 */
	static function onUnitTestsList( array &$files ) {
		
		$dir = dirname( dirname( __FILE__ ) ) . '/tests/phpunit/';
		
		$files[] = $dir . 'MediaWikiFarmMonoversionInstallationTest.php';
		$files[] = $dir . 'MediaWikiFarmMultiversionInstallationTest.php';
		
		return true;
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
	 * @param string $filename Name of the requested file.
	 * @param string $directory Parent directory.
	 * @return array|false The interpreted array in case of success, else false.
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 */
	function readFile( $filename, $directory = '' ) {
		
		# Check parameter
		if( !is_string( $filename ) )
			return false;
		
		# Detect the format
		$format = strrchr( $filename, '.' );
		
		# Check the file exists
		$prefixedFile = $directory ? $directory . '/' . $filename : $filename;
		if( !is_file( $prefixedFile ) )
			return false;
		
		# Format PHP
		if( $format == '.php' )
			
			$array = @include $prefixedFile;
		
		# Format 'serialisation'
		elseif( $format == '.ser' ) {
			
			$content = file_get_contents( $prefixedFile );
			
			if( !$content )
				return array();
			
			$array = @unserialize( $content );
		}
		
		# Cached version
		elseif( is_string( $this->cacheDir ) && is_file( $this->cacheDir . '/' . $filename . '.php' ) && @filemtime( $this->cacheDir . '/' . $filename . '.php' ) >= filemtime( $prefixedFile ) )
			
			return $this->readFile( $filename . '.php', $this->cacheDir );
		
		# Format YAML
		elseif( $format == '.yml' || $format == '.yaml' ) {
			
			# Load Composer libraries
			# There is no warning if not present because to properly handle the error by returning false
			# This is only included here to avoid delays (~3ms without OPcache) during the loading using cached files or other formats
			if( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
				
				require_once dirname( __FILE__ ) . '/Yaml.php';
				
				$array = MediaWikiFarm_readYAML( $prefixedFile );
				if( is_null( $array ) )
					return false;
			}
		}
		
		# Format JSON
		elseif( $format == '.json' )
			
			$array = json_decode( file_get_contents( $prefixedFile ), true );
		
		# Format 'dblist' (simple list of strings separated by newlines)
		elseif( $format == '.dblist' ) {
			
			$content = file_get_contents( $prefixedFile );
			
			if( !$content )
				return array();
			
			return explode( "\n", $content );
		}
		
		# Error for any other format
		else return false;
		
		# A null value is an empty file or value 'null'
		if( is_null( $array ) )
			$array = array();
		
		# Regular return for arrays
		if( is_array( $array ) ) {
			
			if( $format != '.php' && $format != '.ser' )
				$this->cacheFile( $array, $filename.'.php' );
			
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
	 * @param array $array Array of the data to be cached.
	 * @param string $filename Name of the cache file; this filename must have an extension '.ser' or '.php' else no cache file is saved.
	 * @param string|null $directory Name of the parent directory; null for default cache directory
	 * @return void
	 */
	private function cacheFile( $array, $filename, $directory = null ) {
		
		if( is_null( $directory ) )
			$directory = $this->cacheDir;
		
		if( !is_string( $directory ) || !is_dir( $directory ) )
			return;
		
		$prefixedFile = $directory . '/' . $filename;
		
		# Create temporary file
		if( !is_dir( dirname( $prefixedFile ) ) ) {
			mkdir( dirname( $prefixedFile ) );
		}
		$tmpFile = $prefixedFile . '.tmp';
		
		if( preg_match( '/\.php$/', $filename ) ) {
			if( file_put_contents( $tmpFile, "<?php\n\n// WARNING: file automatically generated: do not modify.\n\nreturn ".var_export( $array, true ).';' ) ) {
				rename( $tmpFile, $prefixedFile );
			}
		}
		elseif( preg_match( '/\.ser$/', $filename ) ) {
			if( file_put_contents( $tmpFile, serialize( $array ) ) ) {
				rename( $tmpFile, $prefixedFile );
			}
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
	 * @param string $dir The base directory which could contain MediaWiki.
	 * @return bool The directory really contains MediaWiki.
	 */
	static function isMediaWiki( $dir ) {
		return is_file( $dir . '/includes/DefaultSettings.php' );
	}
	
	/**
	 * Helper function used in extractSkinsAndExtensions.
	 * 
	 * Isolate this function is needed for compatibility with PHP 5.2.
	 * 
	 * @mediawikifarm-const
	 * @mediawikifarm-idempotent
	 * @param string $a String to be regex-escaped.
	 * @return string Escaped string.
	 */
	static function protectRegex( $a ) {
		
		return preg_quote( $a, '/' );
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
	 * @param array $array1.
	 * @return array
	 * @SuppressWarning(PHPMD.StaticAccess)
	 */
	static function arrayMerge( $array1/* ... */ ) {
		$out = $array1;
		$argsCount = func_num_args();
		for ( $i = 1; $i < $argsCount; $i++ ) {
			foreach ( func_get_arg( $i ) as $key => $value ) {
				if( array_key_exists( $key, $out ) && is_array( $out[$key] ) && is_array( $value ) ) {
					$out[$key] = self::arrayMerge( $out[$key], $value );
				} elseif( !array_key_exists( $key, $out ) && !is_numeric( $key ) ) {
					$out[$key] = $value;
				} elseif( is_numeric( $key ) ) {
					$out[] = $value;
				}
			}
		}
		
		return $out;
	}
}
