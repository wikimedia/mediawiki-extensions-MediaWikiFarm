<?php
/**
 * Class MediaWikiFarm.
 * 
 * @author Sébastien Beyou ~ Seb35 <seb35@seb35.fr>
 * @license GPL-3.0+ GNU General Public License v3.0 ou version ultérieure
 * @license AGPL-3.0+ GNU Affero General Public License v3.0 ou version ultérieure
 */

/**
 * This class computes the configuration of a specific wiki from a set of configuration files.
 * The configuration is composed of the list of authorised wikis and different configuration
 * files, possibly with different permissions. Files can be written in YAML, JSON, or PHP.
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
	
	/** @var bool This object cannot be used because of an emergency error. */
	public $unusable = false;
	
	/** @var array Variables related to the current request. */
	public $variables = array();
	
	/** @var array Configuration parameters for this wiki. */
	public $params = array();
	
	
	
	/* 
	 * Public Methods
	 * -------------- */
	
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
		
		# Initialise object
		$wgMediaWikiFarm = self::getInstance();
		
		# Check existence
		if( !$wgMediaWikiFarm->checkExistence() ) {
			
			if( PHP_SAPI == 'cli' )
				exit( 1 );
			
			# Display an informational page when the requested wiki doesn’t exist, only when a page was requested, but not a resource, to avoid waste resources
			$version = $_SERVER['SERVER_PROTOCOL'] && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.0' ? '1.0' : '1.1';
			header( "HTTP/$version 404 Not Found" );
			if( $entryPoint == 'index.php' && array_key_exists( 'nonexistant', $wgMediaWikiFarm->params ) && is_file( $wgMediaWikiFarm->params['nonexistant'] ) )
				include $wgMediaWikiFarm->params['nonexistant'];
			exit;
		}
		
		# Go to version directory
		if( getcwd() != $wgMediaWikiFarm->params['code'] )
			chdir( $wgMediaWikiFarm->params['code'] );
		
		# Define config callback to avoid creating a stub LocalSettings.php (experimental)
		#define( 'MW_CONFIG_CALLBACK', 'MediaWikiFarm::loadConfig' );
		
		# Define config file to avoid creating a stub LocalSettings.php
		if( !defined( 'MW_CONFIG_FILE' ) )
			define( 'MW_CONFIG_FILE', $wgMediaWikiFarm->getConfigFile() );
		
		return $entryPoint;
	}
	
	/**
	 * Return (and if needed initialise) the unique object of type MediaWikiFarm.
	 * 
	 * @throws DomainException When there is no $_SERVER['HTTP_HOST'] nor $_SERVER['SERVER_NAME'].
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
		else throw new DomainException( 'Undefined host' );
		
		# Create the object for this host
		self::$self = new self( $host, $wgMediaWikiFarmConfigDir, $wgMediaWikiFarmCodeDir, $wgMediaWikiFarmCacheDir );
		
		return self::$self;
	}
	
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
	 */
	function checkExistence() {
		
		if( $this->unusable )
			return false;
		
		# In the multiversion case, informations are already loaded and nonexistent wikis are already verified
		if( array_key_exists( 'version', $this->params ) )
			return true;
		
		# Replace variables in the host name and possibly retrieve the version
		if( ($version = $this->replaceHostVariables()) === false )
			return false;
		
		# Set wikiID, the unique identifier of the wiki
		if( !$this->setWikiID() )
			return false;
		
		# Set the version of the wiki
		if( !$this->setVersion( $version ) )
			return false;
		
		# Set other properties of the wiki
		if( !$this->setWikiProperties() )
			return false;
		
		# Set available suffixes and wikis
		// This is not useful since nobody else use available suffixes and wikis
		// For now, remove loading of one config file to improve a bit performance
		//$this->setWgConf();
		
		return true;
	}
	
	/**
	 * This function loads MediaWiki configuration (parameters).
	 * 
	 * @return void
	 */
	function loadMediaWikiConfig() {
		
		if( $this->unusable )
			return;
		
		if( !array_key_exists( 'globals', $this->params ) || !is_array( $this->params['globals'] ) )
			
			$this->getMediaWikiConfig();
		
		// Set general parameters as global variables
		foreach( $this->params['globals']['general'] as $setting => $value ) {
			
			$GLOBALS[$setting] = $value;
		}
	}
	
	/**
	 * This function load the skins configuration (wfLoadSkin loading mechanism and parameters).
	 * 
	 * WARNING: it doesn’t load the skins with the require_once mechanism (it is not possible in
	 * a function because variables would inherit the non-global scope); such skins must be loaded
	 * before calling this function.
	 * 
	 * @return void
	 */
	function loadSkinsConfig() {
		
		if( $this->unusable )
			return;
		
		// Load skins with the wfLoadSkin mechanism
		foreach( $this->params['globals']['skins'] as $skin => $value ) {
			
			if( $value['_loading'] == 'wfLoadSkin' )
				
				wfLoadSkin( $skin );
			
			unset( $this->params['globals']['skins'][$skin]['_loading'] );
		}
		
		// Set skin parameters as global variables
		foreach( $this->params['globals']['skins'] as $skin => $settings ) {
			
			foreach( $settings as $setting => $value )
				
				$GLOBALS[$setting] = $value;
		}
	}
	
	/**
	 * This function load the skins configuration (wfLoadSkin loading mechanism and parameters).
	 * 
	 * WARNING: it doesn’t load the skins with the require_once mechanism (it is not possible in
	 * a function because variables would inherit the non-global scope); such skins must be loaded
	 * before calling this function.
	 * 
	 * @return void
	 */
	function loadExtensionsConfig() {
		
		if( $this->unusable )
			return;
		
		# Register this extension MediaWikiFarm to appear in Special:Version
		if( function_exists( 'wfLoadExtension' ) ) {
			wfLoadExtension( 'MediaWikiFarm', $this->codeDir ? $this->farmDir . '/extension.json' : null );
			unset( $this->params['globals']['extensions']['MediaWikiFarm']['_loading'] );
		}
		else {
			$GLOBALS['wgExtensionCredits']['other'][] = array(
				'path' => dirname( dirname( __FILE__ ) ) . '/MediaWikiFarm.php',
				'name' => 'MediaWikiFarm',
				'version' => '0.1.0',
				'author' => 'Seb35',
				'url' => 'https://www.mediawiki.org/wiki/Extension:MediaWikiFarm',
				'descriptionmsg' => 'mediawikifarm-desc',
				'license-name' => 'GPL-3.0+'
			);

		}
		
		// Load extensions with the wfLoadExtension mechanism
		foreach( $this->params['globals']['extensions'] as $extension => $value ) {
			
			if( $value['_loading'] == 'wfLoadExtension' )
				
				wfLoadExtension( $extension );
			
			unset( $this->params['globals']['extensions'][$extension]['_loading'] );
		}
		
		// Set extension parameters as global variables
		foreach( $this->params['globals']['extensions'] as $extension => $settings ) {
			
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
		
		if( !array_key_exists( 'version', $this->params ) || !$this->params['version'] )
			return;
		
		$this->updateVersion( $this->params['version'] );
	}
	
	/**
	 * Return the file where is loaded the configuration.
	 * 
	 * This function is important to avoid the two parts of the extension (checking of
	 * existence and loading of configuration) are located in the same directory in the
	 * case mono- and multi-version installations are mixed. Without it, this class
	 * could be defined by two different files, and PHP doesn’t like it.
	 * 
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
		
		foreach( MediaWikiFarm::getInstance()->params['globals']['execFiles'] as $execFile ) {
			
			@include $execFile;
		}
	}
	
	
	
	/*
	 * Private Methods
	 * --------------- */
	
	/**
	 * Construct a MediaWiki farm.
	 * 
	 * This constructor sets the directories (configuration and code) and select the right
	 * farm depending of the host (when there are multiple farms). In case of error (unreadable
	 * directory or file, or unrecognized host), no exception is thrown but the property
	 * 'unusable' becomes true.
	 * It is a public method for testing needs, but it should never directly called in real code.
	 * 
	 * @param string $host Requested host.
	 * @param string $configDir Configuration directory.
	 * @param string|null $codeDir Code directory; if null, the current MediaWiki installation is used.
	 * @param string|false|null $cacheDir Cache directory; if null, the cache is disabled.
	 */
	public function __construct( $host, $configDir, $codeDir = null, $cacheDir = null ) {
		
		# Default value for $cacheDir
		if( is_null( $cacheDir ) ) $cacheDir = '/tmp/mw-cache';
		
		# Check parameters
		if( !is_string( $host ) ||
		    !(is_string( $configDir ) && is_dir( $configDir )) ||
		    !(is_null( $codeDir ) xor (is_string( $codeDir ) && is_dir( $codeDir ))) ||
		    !(is_string( $cacheDir ) xor $cacheDir === false)
		  ) {
		  	
			$this->unusable = true;
			return;
		}
		
		# Set parameters
		$this->farmDir = dirname( dirname( __FILE__ ) );
		$this->configDir = $configDir;
		$this->codeDir = $codeDir;
		$this->cacheDir = $cacheDir;
		
		# If installed in the classical extensions directory, force to a monoversion installation
		if( is_file( dirname( dirname( $this->farmDir ) ) . '/includes/DefaultSettings.php' ) )
			$this->codeDir = null;
		
		if( $this->cacheDir && !is_dir( $this->cacheDir ) )
			mkdir( $this->cacheDir );
		
		# Read the farms configuration
		if( $farms = $this->readFile( 'farms.yml', $this->configDir ) );
		elseif( $farms = $this->readFile( '/farms.php', $this->configDir ) );
		elseif( $farms = $this->readFile( '/farms.json', $this->configDir ) );
		else $this->unusable = true;
		
		# Now select the right configuration amoung all farms
		$this->unusable = !$this->selectFarm( $farms, $host );
	}
	
	/**
	 * Select the farm.
	 * 
	 * @param array $farms All farm configurations.
	 * @param string $host Requested host.
	 * @return bool One of the farm has been selected.
	 */
	private function selectFarm( $farms, $host ) {
		
		if( $this->unusable )
			return false;
		
		static $redirects = 0;
		if( $redirects >= 5 ) {
			$this->unusable = true;
			return false;
		}
		
		# Re-initialise some variables for the 'redirect' case
		$this->variables = array();
		
		# For each proposed farm, check if the host matches
		foreach( $farms as $farm => $config ) {
			
			if( !preg_match( '/^' . $config['server'] . '$/i', $host, $matches ) )
				continue;
			
			# Initialise variables from the host
			foreach( $matches as $key => $value ) {
				
				if( is_string( $key ) )
					$this->variables[$key] = $value;
			}
			
			# Redirect to another farm
			if( array_key_exists( 'redirect', $config ) ) {
				
				$redirects++;
				return $this->selectFarm( $farms, $this->replaceVariables( $config['redirect'] ) );
			}
			
			# Get the selected configuration
			$this->params = $config;
			if( $this->cacheDir ) {
				
				$this->cacheDir .= '/' . $farm;
				
				# Create cache directory
				if( !is_dir( $this->cacheDir ) )
					mkdir( $this->cacheDir );
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Replacement of the variables in the host name.
	 * 
	 * @todo Rename this function to checkHostVariables.
	 * @todo Change the current behavious to one where the variables available during a step are only the previously-checked variables (currently all variables are available). The documentation already mention this future behavious to avoid users assume the current behaviour.
	 * @return string|null|false If an existing version is found in files, returns a string; if no version is found, returns null; if the host is missing in existence files, returns false; if an existence file is missing or badly formatted, return false and turns this object into a unusable state.
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	private function replaceHostVariables() {
		
		$version = null;
		
		# For each variable, in the given order, check if the variable exists, check if the
		# wiki exists in the corresponding listing file, and get the version if available
		foreach( $this->params['variables'] as $variable ) {
			
			$key = $variable['variable'];
			
			# If the variable doesn’t exist, continue
			if( !array_key_exists( $key, $this->variables ) )
				continue;
			
			$value = $this->variables[$key];
			
			# If every values are correct, continue
			if( !array_key_exists( 'file', $variable ) )
				continue;
			
			# Really check if the variable is in the listing file
			$choices = $this->readFile( $this->replaceVariables( $variable['file'] ), $this->configDir );
			if( $choices === false ) {
				$this->unusable = true;
				return false;
			}
			
			# Check if the array is a simple list of wiki identifiers without version information…
			if( array_keys( $choices ) === range( 0, count( $choices ) - 1 ) ) {
				if( !in_array( $value, $choices ) )
					return false;
			
			# …or a dictionary with wiki identifiers and corresponding version information
			} else {
				
				if( !array_key_exists( $value, $choices ) )
					return false;
				
				if( is_string( $this->codeDir ) && is_dir( $this->codeDir . '/' . ((string) $choices[$value]) ) && is_file( $this->codeDir . '/' . ((string) $choices[$value]) . '/includes/DefaultSettings.php' ) )
					$version = (string) $choices[$value];
			}
		}
		
		return $version;
	}
	
	/**
	 * Computation of the suffix and wikiID.
	 * 
	 * This function is the central point to get the unique identifier of the wiki, wikiID.
	 * 
	 * @return bool The wikiID and suffix were set, and the wiki could exist.
	 */
	private function setWikiID() {
		
		$this->params['version'] = null;
		$this->params['globals'] = null;
		
		# Set suffix
		$this->setWikiProperty( 'suffix' );
		$this->variables['SUFFIX'] = $this->params['suffix'];
		
		# Set wikiID
		$this->setWikiProperty( 'wikiID' );
		$this->variables['WIKIID'] = $this->params['wikiID'];
		
		# Check consistency
		if( !$this->params['suffix'] || !$this->params['wikiID'] ) {
			$this->unusable = true;
			return false;
		}
		
		return true;
	}
	
	/**
	 * Setting of the version, either from the input if already got, either from a file.
	 * 
	 * @param string|null $version If a string, this is the version already got, just set it.
	 * @return bool The version was set, and the wiki could exist.
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	private function setVersion( $version = null ) {
		
		global $IP, $mwfScript;
		
		# Special case for the update: new (uncached) version must be used
		$force = false;
		if( is_string( $mwfScript ) && $mwfScript == 'maintenance/update.php' )
			$force = true;
		
		# Read cache file
		$deployments = array();
		$this->setWikiProperty( 'deployments' );
		if( array_key_exists( 'deployments', $this->params ) && !$force ) {
			if( strrchr( $this->params['deployments'], '.' ) != '.php' ) $this->params['deployments'] .= '.php';
			$deployments = $this->readFile( $this->params['deployments'], $this->configDir );
			if( $deployments === false ) $deployments = array();
		}
		if( array_key_exists( $this->params['wikiID'], $deployments ) ) {
			$version = $deployments[$this->params['wikiID']];
			$this->params['code'] = $this->codeDir . '/' . $version;
		}
		# In the case multiversion is configured and version is already known
		elseif( is_string( $this->codeDir ) && is_string( $version ) ) {
			
			# Cache the version
			if( !$force )
				$this->updateVersion( $version );
			
			$this->params['code'] = $this->codeDir . '/' . $version;
		}
		
		# In the case multiversion is configured, but version is not known as of now
		elseif( is_string( $this->codeDir ) && is_null( $version ) ) {
			
			# Replace variables in the file name containing all versions, if existing
			$this->setWikiProperty( 'versions' );
			
			$versions = $this->readFile( $this->params['versions'], $this->configDir );
			
			if( !$versions ) {
				$this->unusable = true;
				return false;
			}
			
			if( array_key_exists( $this->params['wikiID'], $versions ) && is_file( $this->codeDir . '/' . $versions[$this->params['wikiID']] . '/includes/DefaultSettings.php' ) )
				$version = $versions[$this->params['wikiID']];
			
			elseif( array_key_exists( $this->params['suffix'], $versions ) && is_file( $this->codeDir . '/' . $versions[$this->params['suffix']] . '/includes/DefaultSettings.php' ) )
				$version = $versions[$this->params['suffix']];
			
			elseif( array_key_exists( 'default', $versions ) && is_file( $this->codeDir . '/' . $versions['default'] . '/includes/DefaultSettings.php' ) )
				$version = $versions['default'];
			
			else return false;
			
			# Cache the version
			if( !$force )
				$this->updateVersion( $version );
			
			$this->params['code'] = $this->codeDir . '/' . $version;
		}
		
		# In the case this is a monoversion installation
		elseif( is_null( $this->codeDir ) ) {
			
			$version = '';
			$this->params['code'] = $IP;
		}
		else {
			$this->unusable = true;
			return false;
		}
		
		# Set the version in the wiki configuration and as a variable to be used later
		$this->variables['VERSION'] = $version;
		$this->params['version'] = $version;
		
		return true;
	}
	
	/**
	 * Update the version in the deployment file.
	 * 
	 * @param string $version The new version, should be the version found in the 'expected version' file.
	 * @return void
	 */
	private function updateVersion( $version ) {
	
		# Check a deployment file is wanted
		if( !array_key_exists( 'deployments', $this->params ) )
			return;
		
		# Read current deployments
		if( strrchr( $this->params['deployments'], '.' ) != '.php' ) $this->params['deployments'] .= '.php';
		$deployments = $this->readFile( $this->params['deployments'], $this->configDir );
		if( $deployments === false ) $deployments = array();
		elseif( array_key_exists( $this->params['wikiID'], $deployments ) && $deployments[$this->params['wikiID']] == $version )
			return;
		
		# Update the deployment file
		$deployments[$this->params['wikiID']] = $version;
		$this->cacheFile( $deployments, $this->params['deployments'], $this->configDir );
	}
	
	/**
	 * Computation of the properties, which could depend on the suffix, wikiID, or other variables.
	 * 
	 * @return bool The wiki properties were set, and the wiki could exist.
	 */
	private function setWikiProperties() {
		
		if( !array_key_exists( 'config', $this->params ) )
			$this->params['config'] = array();
		
		$this->setWikiProperty( 'data' );
		$this->setWikiProperty( 'config' );
		
		return true;
	}
	
	/**
	 * Set available suffixes and wikis.
	 * 
	 * @todo Still hacky: before setting parameters in stone in farms.yml, various configurations should be reviewed to select accordingly the rights management modelisation
	 * @return void
	 */
	/*private function setWgConf() {
		
		global $wgConf;
		
		$wgConf->suffixes = array( $this->params['suffix'] );
		$wikiIDs = $this->readFile( $this->params['suffix'] . '/wikis.yml', $this->configDir );
		foreach( array_keys( $wikiIDs ) as $wiki ) {
			$wgConf->wikis[] = $wiki . '-' . $this->params['suffix'];
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
	private function getMediaWikiConfig() {
		
		global $wgConf;
		
		if( $this->unusable )
			return false;
		
		# In MediaWiki 1.16, $wgConf is not created by default
		if( is_null( $wgConf ) ) {
			$wgConf = new SiteConfiguration();
		}
		
		$myWiki = $this->params['wikiID'];
		$mySuffix = $this->params['suffix'];
		if( $this->params['version'] ) $cacheFile = $this->replaceVariables( 'config-$VERSION-$SUFFIX-$WIKIID.php' );
		else $cacheFile = $this->replaceVariables( 'config-$SUFFIX-$WIKIID.php' );
		$this->params['globals'] = false;
		
		# Check modification time of original config files
		$oldness = 0;
		foreach( $this->params['config'] as $configFile )
			$oldness = max( $oldness, @filemtime( $this->configDir . '/' . $configFile['file'] ) );
		
		# Use cache file or recompile the config
		if( is_string( $this->cacheDir ) && is_file( $this->cacheDir . '/' . $cacheFile ) && @filemtime( $this->cacheDir . '/' . $cacheFile ) >= $oldness )
			$this->params['globals'] = $this->readFile( $cacheFile, $this->cacheDir );
		
		else {
			
			$this->params['globals'] = array(
				'general' => array(),
				'skins' => array(),
				'extensions' => array(),
				'execFiles' => array(),
			);
			$globals =& $this->params['globals'];
			
			# Populate wgConf
			if( !$this->populatewgConf() )
				return false;
			
			# Get specific configuration for this wiki
			# Do not use SiteConfiguration::extractAllGlobals or the configuration caching would become
			# ineffective and there would be inconsistencies in this process
			$globals['general'] = $wgConf->getAll( $myWiki, $mySuffix, array( 'data' => $this->params['data'] ) );
			
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
	private function populatewgConf() {
		
		global $wgConf;
		
		if( $this->unusable )
			return false;
		
		foreach( $this->params['config'] as $configFile ) {
			
			# Executable config files
			if( array_key_exists( 'exec', $configFile ) ) {
				
				$this->params['globals']['execFiles'][] = $this->configDir . '/' . $configFile['file'];
				continue;
			}
			
			$theseSettings = $this->readFile( $configFile['file'], $this->configDir );
			if( $theseSettings === false ) {
				# If a file is unavailable, skip it
				continue;
				# Exiting is fatal and, in case of mistake, is worse than some parameters missing
				#$this->unusable = true;
				#return false;
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
				
				$defaultKey = null;
				if( array_key_exists( 'default', $configFile ) )
					$defaultKey = $this->replaceVariables( $configFile['default'] );
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
	 * @param SiteConfiguration $wgConf SiteConfiguration object.
	 * @param string $dbName Database name.
	 * @return array
	 */
	function SiteConfigurationSiteParamsCallback( $wgConf, $wikiID ) {
		
		if( substr( $wikiID, strlen( $wikiID ) - strlen( $this->params['suffix'] ) ) != $this->params['suffix'] )
			return null;
		
		return array(
			'suffix' => $this->params['suffix'],
			'lang' => '',
			'tags' => array(),
			'params' => array(),
		);
	}
	
	/**
	 * Extract from the general configuration skin and extension configuration
	 * 
	 * @return void
	 */
	private function extractSkinsAndExtensions() {
		
		$globals =& $this->params['globals'];
		
		# Search for skin and extension activation
		$unsetPrefixes = array();
		foreach( $globals['general'] as $setting => $value ) {
			if( preg_match( '/^wgUseSkin(.+)$/', $setting, $matches ) && $value === true ) {
				
				$skin = $matches[1];
				$loadingMechanism = $this->detectLoadingMechanism( 'skin', $skin );
				
				if( is_null( $loadingMechanism ) ) $unsetPrefixes[] = $skin;
				else $globals['skins'][$skin] = array( '_loading' => $loadingMechanism );
				
				unset( $globals['general'][$setting] );
			}
			elseif( preg_match( '/^wgUseExtension(.+)$/', $setting, $matches ) && $value === true ) {
				
				$extension = $matches[1];
				$loadingMechanism = $this->detectLoadingMechanism( 'extension', $extension );
				
				if( is_null( $loadingMechanism ) ) $unsetPrefixes[] = $extension;
				else $globals['extensions'][$extension] = array( '_loading' => $loadingMechanism );
				
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
	 * Helper function used in extractSkinsAndExtensions.
	 * 
	 * @param string $a String to be regex-escaped.
	 * @return string Escaped string.
	 */
	static private function protectRegex( $a ) {
		
		return preg_quote( $a, '/' );
	}
	
	/**
	 * Detection of the loading mechanism of extensions and skins.
	 * 
	 * @param string $type Type, in ['extension', 'skin'].
	 * @param string $name Name of the extension/skin.
	 * @return string|null Loading mechnism in ['wfLoadExtension', 'wfLoadSkin', 'require_once', 'composer'] or null if all mechanisms failed.
	 */
	private function detectLoadingMechanism( $type, $name ) {
		
		if( !is_dir( $this->params['code'].'/'.$type.'s/'.$name ) )
			return null;
		
		# An extension.json/skin.json file is in the directory -> assume it is the loading mechanism
		if( function_exists( 'wfLoad'.ucfirst($type) ) && is_file( $this->params['code'].'/'.$type.'s/'.$name.'/'.$type.'.json' ) )
			return 'wfLoad'.ucfirst($type);
		
		# A MyExtension.php file is in the directory -> assume it is the loading mechanism
		elseif( is_file( $this->params['code'].'/'.$type.'s/'.$name.'/'.$name.'.php' ) )
			return 'require_once';
		
		# A composer.json file is in the directory -> assume it is the loading mechanism if previous mechanisms didn’t succeed
		elseif( is_file( $this->params['code'].'/'.$type.'s/'.$name.'/composer.json' ) )
			return 'composer';
		
		return null;
	}
	
	
	
	/*
	 * Helper Methods
	 * -------------- */
	
	/**
	 * Read a file either in PHP, YAML (if library available), JSON, dblist, or serialised, and returns the interpreted array.
	 * 
	 * The choice between the format depends on the extension: php, yml, yaml, json, dblist, serialised.
	 * 
	 * @param string $filename Name of the requested file.
	 * @param string $directory Parent directory.
	 * @return array|false The interpreted array in case of success, else false.
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 */
	private function readFile( $filename, $directory = '' ) {
		
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
				
				$array = require 'Yaml.php';
				if( $array === false )
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
		if( !is_dir( dirname( $prefixedFile ) ) )
			mkdir( dirname( $prefixedFile ) );
		$tmpFile = $prefixedFile . '.tmp';
		
		if( preg_match( '/\.php$/', $filename ) ) {
			if( file_put_contents( $tmpFile, "<?php\n\n// WARNING: file automatically generated: do not modify.\n\nreturn ".var_export( $array, true ).';' ) )
				rename( $tmpFile, $prefixedFile );
		}
		elseif( preg_match( '/\.ser$/', $filename ) ) {
			if( file_put_contents( $tmpFile, serialize( $array ) ) ) {
				rename( $tmpFile, $prefixedFile );
			}
		}
	}
	
	/**
	 * Set a wiki property and replace placeholders (property name version).
	 * 
	 * @param string $name Name of the property.
	 * @return void
	 */
	private function setWikiProperty( $name ) {
		
		if( !array_key_exists( $name, $this->params ) )
			return;
		
		$this->params[$name] = $this->replaceVariables( $this->params[$name] );
	}
	
	/**
	 * Replace variables in a string.
	 * 
	 * @param string|null $value Value of the property.
	 * @return string Input where variables were replaced.
	 */
	private function replaceVariables( $value ) {
		
		static $rkeys = array(), $rvalues = array();
		if( count( $this->variables ) != count( $rkeys ) ) {
			
			$rkeys = array();
			$rvalues = array();
			
			foreach( $this->variables as $key => $val ) {
				$rkeys[] = '$' . $key;
				$rvalues[] = $val;
			}
		}
		
		if( is_null( $value ) )
			return '';
		
		elseif( is_string( $value ) )
			$value = str_replace( $rkeys, $rvalues, $value );
		
		elseif( !is_array( $value ) ) {
			
			$this->unusable = true;
			return '';
		}
		elseif( is_array( $value ) ) {
			
			foreach( $value as &$subvalue ) {
				foreach( $subvalue as &$subsubvalue )
					$subsubvalue = str_replace( $rkeys, $rvalues, $subsubvalue );
			}
		}
		
		return $value;
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
	 * @param array $array1.
	 * @return array
	 * @SuppressWarning(PHPMD.StaticAccess)
	 */
	static private function arrayMerge( $array1/* ... */ ) {
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
	
	/**
	 * Add files for unit testing.
	 * 
	 * @param string[] $files The test files.
	 */
	public static function onUnitTestsList( array &$files ) {
		
		$dir = dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'phpunit' . DIRECTORY_SEPARATOR;
		
		$files[] = $dir . 'MediaWikiFarmTest.php';
		
		return true;
	}
}
